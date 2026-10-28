<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * The admin app shell: a left sidebar, a top bar and a content column, for an
 * admin screen that is an application rather than a settings page.
 *
 * Some areas of Unyson+ are not one page of fields. They are a dozen screens
 * that belong together — a CRM, a membership area, SEO — and WordPress gives
 * them one submenu entry and a row of horizontal tabs that wraps. This draws
 * the frame instead: the WordPress menu folds to icons, the app's own
 * navigation takes the left edge in named groups, and the view fills the rest.
 *
 * It is a DEVELOPER API, not a user setting. A screen earns this frame by being
 * an application; bolting it onto an ordinary settings page would fold the
 * WordPress menu for nothing.
 *
 * Minimal use, from an admin page class:
 *
 *     $app = new FW_Admin_App( 'seo', array(
 *         'brand' => array( 'label' => __( 'SEO', 'fw' ), 'icon' => 'dashicons-search' ),
 *         'page'  => 'fw-seo-settings',
 *         'nav'   => array(
 *             'main' => array(
 *                 'label' => __( 'Settings', 'fw' ),
 *                 'items' => array(
 *                     'general' => array( 'label' => __( 'General', 'fw' ), 'icon' => 'dashicons-admin-generic' ),
 *                 ),
 *             ),
 *         ),
 *     ) );
 *
 *     $app->attach( $hook_suffix );              // body classes + assets
 *     // …then, inside the page's render callback:
 *     $app->open( $view, array( 'title' => $label ) );
 *     echo $the_view;
 *     $app->close();
 *
 * Everything inside the content column is the caller's: this class renders the
 * frame and nothing else.
 *
 * @since 3.0.48
 */
class FW_Admin_App {

	/**
	 * User meta: 1 while this person prefers app screens without the WordPress menu.
	 *
	 * Deliberately ONE preference for every app rather than one per app. Someone
	 * who wants the WordPress menu out of the way while they work wants that on
	 * every app screen, not re-decided in each.
	 */
	const FOCUS_META = 'fw_admin_app_focus';

	/** The admin-ajax action + nonce that remembers the focus choice. */
	const FOCUS_ACTION = 'fw_admin_app_focus';

	/** @var string A slug identifying this app, e.g. 'seo'. Appears as a body class. */
	protected $id;

	/** @var array */
	protected $args;

	/** @var bool Whether the shared hooks (ajax, assets) have been registered. */
	protected static $booted = false;

	/**
	 * @param string $id   App slug: [a-z0-9_-]. Becomes the `fw-app-<id>` body class.
	 * @param array  $args {
	 *     @type array           $brand      { label, icon (dashicons-*), url } — the sidebar header.
	 *     @type string          $page       The admin page slug, used to build view URLs.
	 *     @type string          $arg        The query arg that picks the view. Default 'view'.
	 *     @type string          $default    The view a bare page URL lands on, whose URL carries no arg.
	 *     @type array|callable  $nav        Groups: key => { label, items: view => { label, icon, cap, soon, url } }.
	 *                                       A callable is called with no arguments and must return the same shape,
	 *                                       which is how an app whose menu depends on runtime state (modules
	 *                                       switched off, a schema read at render time) stays in step.
	 *     @type string          $capability Required to use the app. Default 'manage_options'.
	 *     @type string          $back       Label for the "leave the app" link. Default "Back to WordPress".
	 *     @type string          $back_url   Where that link goes. Default the dashboard.
	 * }
	 */
	public function __construct( $id, array $args = array() ) {
		$this->id = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $id ) );

		$this->args = array_merge( array(
			'brand'      => array(),
			'page'       => '',
			'arg'        => 'view',
			'default'    => '',
			'nav'        => array(),
			'capability' => 'manage_options',
			'back'       => __( 'Back to WordPress', 'fw' ),
			'back_url'   => '',
		), $args );
	}

	/* ---------------------------------------------------------------------- *
	 * Wiring
	 * ---------------------------------------------------------------------- */

	/**
	 * Claim an admin screen: fold the WordPress menu on it and load the shell's
	 * CSS/JS there.
	 *
	 * Call this from `admin_menu`, with the hook suffix `add_submenu_page()`
	 * returned — not from the render callback, which runs far too late to add a
	 * body class.
	 *
	 * @param string $hook_suffix
	 */
	public function attach( $hook_suffix ) {
		if ( ! $hook_suffix ) {
			return;
		}

		$id = $this->id;

		add_filter( 'admin_body_class', function ( $classes ) use ( $hook_suffix, $id ) {
			$screen = get_current_screen();

			if ( ! $screen || $screen->id !== $hook_suffix ) {
				return $classes;
			}

			// `folded` is WordPress's own class for the icons-only admin menu, so
			// the sidebar gets the room without this having to restyle the menu.
			$classes .= ' fw-app-screen fw-app-' . $id . ' folded';

			if ( self::is_focus() ) {
				$classes .= ' fw-app-focus';
			}

			return $classes;
		} );

		add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $hook_suffix ) {
			if ( $hook === $hook_suffix ) {
				self::enqueue();
			}
		} );

		self::boot();
	}

	/**
	 * Register what every app shares, once per request.
	 *
	 * Called from the framework bootstrap on every admin request — including
	 * `admin-ajax.php`, which is the whole point: `attach()` runs on `admin_menu`
	 * and so is never reached by the request the focus toggle posts.
	 */
	public static function boot() {
		if ( self::$booted ) {
			return;
		}

		self::$booted = true;

		add_action( 'wp_ajax_' . self::FOCUS_ACTION, array( __CLASS__, '_ajax_focus' ) );
	}

	/** Enqueue the shell's stylesheet and script. Safe to call more than once. */
	public static function enqueue() {
		$ver = fw()->manifest->get_version();

		wp_enqueue_style( 'fw-admin-app', fw_get_framework_asset_uri( '/static/css/admin-app.css' ), array( 'dashicons' ), $ver );
		wp_enqueue_script( 'fw-admin-app', fw_get_framework_asset_uri( '/static/js/admin-app.js' ), array(), $ver, true );
	}

	/**
	 * @internal admin-ajax: remember the focus-mode choice.
	 *
	 * Capability is `read` deliberately: this writes nothing but a display
	 * preference on the current user's own account, and the apps themselves
	 * differ in what they require to be seen.
	 */
	public static function _ajax_focus() {
		check_ajax_referer( self::FOCUS_ACTION );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( null, 403 );
		}

		$on = ! empty( $_POST['on'] ) && '0' !== $_POST['on']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		update_user_meta( get_current_user_id(), self::FOCUS_META, $on ? 1 : 0 );

		wp_send_json_success( array( 'on' => $on ) );
	}

	/** @return bool Whether this user is hiding the WordPress menu on app screens. */
	public static function is_focus() {
		$on = get_user_meta( get_current_user_id(), self::FOCUS_META, true );

		if ( '' === $on ) {
			/**
			 * Whether app screens hide the WordPress menu for someone who has not
			 * chosen yet. A workspace (FW_Workspaces) turns this on.
			 *
			 * @param bool $on
			 */
			return (bool) apply_filters( 'fw_admin_app_focus_default', false );
		}

		return (bool) $on;
	}

	/* ---------------------------------------------------------------------- *
	 * URLs and navigation
	 * ---------------------------------------------------------------------- */

	/**
	 * The URL of one view.
	 *
	 * The default view's URL carries no `view` arg, so each screen has exactly
	 * one address rather than two that render the same thing.
	 *
	 * @param string $view
	 *
	 * @return string
	 */
	public function url( $view = '' ) {
		$base = admin_url( 'admin.php?page=' . $this->args['page'] );

		if ( '' === (string) $view || $view === (string) $this->args['default'] ) {
			return $base;
		}

		return add_query_arg( $this->args['arg'], $view, $base );
	}

	/**
	 * The view this request is for, or null when the request is not this app's page.
	 *
	 * Reads the request rather than taking a parameter, so code far from the
	 * page — an options schema, a report builder — can ask whether it is being
	 * built for the screen the person is actually looking at.
	 *
	 * @return string|null
	 */
	public function requested_view() {
		// phpcs:disable WordPress.Security.NonceVerification -- reading which screen this is.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( '' === $this->args['page'] || $page !== $this->args['page'] ) {
			return null;
		}

		$view = isset( $_GET[ $this->args['arg'] ] ) ? sanitize_key( wp_unslash( $_GET[ $this->args['arg'] ] ) ) : '';
		// phpcs:enable

		return '' === $view ? (string) $this->args['default'] : $view;
	}

	/**
	 * The navigation, with items the current user may not see removed.
	 *
	 * @return array
	 */
	public function nav() {
		$nav = $this->args['nav'];

		if ( is_callable( $nav ) ) {
			$nav = call_user_func( $nav );
		}

		foreach ( (array) $nav as $key => $group ) {
			$items = array();

			foreach ( (array) ( $group['items'] ?? array() ) as $slug => $item ) {
				if ( ! empty( $item['cap'] ) && ! current_user_can( $item['cap'] ) ) {
					continue;
				}

				if ( isset( $item['visible'] ) && is_callable( $item['visible'] ) && ! call_user_func( $item['visible'], $slug ) ) {
					continue;
				}

				$items[ $slug ] = $item;
			}

			if ( $items ) {
				$nav[ $key ]['items'] = $items;
			} else {
				// A group with nothing left in it is a heading over empty space.
				unset( $nav[ $key ] );
			}
		}

		return (array) $nav;
	}

	/* ---------------------------------------------------------------------- *
	 * The frame
	 * ---------------------------------------------------------------------- */

	/**
	 * Open the frame. Everything echoed after this lands in the content column.
	 *
	 * @param string $view The active view, highlighted in the sidebar.
	 * @param array  $bar  {
	 *     @type string $title   The page title. Defaults to the active item's label.
	 *     @type array  $crumbs  [ [ label, url ], … ] above the title.
	 *     @type string $actions HTML for the right of the top bar — ALREADY ESCAPED.
	 *     @type string $search  HTML for a search form in the top bar — ALREADY ESCAPED.
	 *     @type string $class   Extra classes for the content column, e.g. a `wrap` variant.
	 * }
	 */
	public function open( $view, array $bar = array() ) {
		$nav   = $this->nav();
		$focus = self::is_focus();
		$brand = array_merge( array( 'label' => '', 'icon' => 'dashicons-admin-generic', 'url' => '' ), (array) $this->args['brand'] );
		$title = (string) ( $bar['title'] ?? $this->label_of( $view, $nav ) );
		$crumbs = (array) ( $bar['crumbs'] ?? array() );
		$home   = '' !== $brand['url'] ? $brand['url'] : $this->url();
		?>
		<div class="fw-app" id="fw-app" data-app="<?php echo esc_attr( $this->id ); ?>"
			data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-focus-action="<?php echo esc_attr( self::FOCUS_ACTION ); ?>"
			data-focus-nonce="<?php echo esc_attr( wp_create_nonce( self::FOCUS_ACTION ) ); ?>">

			<nav class="fw-app-side" id="fw-app-side" aria-label="<?php echo esc_attr( $brand['label'] ); ?>">
				<a class="fw-app-side__brand" href="<?php echo esc_url( $home ); ?>">
					<span class="fw-app-side__mark" aria-hidden="true">
						<span class="dashicons <?php echo esc_attr( $brand['icon'] ); ?>"></span>
					</span>
					<span class="fw-app-side__name"><?php echo esc_html( $brand['label'] ); ?></span>
				</a>

				<div class="fw-app-side__groups">
					<?php foreach ( $nav as $group_key => $group ) : ?>
						<div class="fw-app-side__group">
							<?php if ( '' !== (string) ( $group['label'] ?? '' ) ) : ?>
								<p class="fw-app-side__label" id="fw-app-nav-<?php echo esc_attr( $group_key ); ?>"><?php echo esc_html( $group['label'] ); ?></p>
							<?php endif; ?>
							<ul<?php echo '' !== (string) ( $group['label'] ?? '' ) ? ' aria-labelledby="fw-app-nav-' . esc_attr( $group_key ) . '"' : ''; ?>>
								<?php foreach ( $group['items'] as $slug => $item ) : ?>
									<?php $here = ( (string) $slug === (string) $view ); ?>
									<li>
										<a class="fw-app-side__link<?php echo $here ? ' is-active' : ''; ?><?php echo ! empty( $item['soon'] ) ? ' is-soon' : ''; ?>"
											href="<?php echo esc_url( ! empty( $item['url'] ) ? $item['url'] : $this->url( $slug ) ); ?>"
											<?php echo $here ? 'aria-current="page"' : ''; ?>>
											<span class="dashicons <?php echo esc_attr( $item['icon'] ?? 'dashicons-marker' ); ?>" aria-hidden="true"></span>
											<span class="fw-app-side__text"><?php echo esc_html( $item['label'] ?? $slug ); ?></span>
											<?php if ( ! empty( $item['soon'] ) ) : ?>
												<span class="fw-app-side__soon"><?php esc_html_e( 'Soon', 'fw' ); ?></span>
											<?php endif; ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="fw-app-side__foot">
					<button type="button" class="fw-app-side__link fw-app-focus-toggle" aria-pressed="<?php echo $focus ? 'true' : 'false'; ?>"
						data-on="<?php esc_attr_e( 'Show the WordPress menu', 'fw' ); ?>"
						data-off="<?php esc_attr_e( 'Hide the WordPress menu', 'fw' ); ?>">
						<span class="dashicons dashicons-editor-contract" aria-hidden="true"></span>
						<span class="fw-app-side__text"><?php echo $focus ? esc_html__( 'Show the WordPress menu', 'fw' ) : esc_html__( 'Hide the WordPress menu', 'fw' ); ?></span>
					</button>
					<a class="fw-app-side__link fw-app-side__back" href="<?php echo esc_url( $this->args['back_url'] ? $this->args['back_url'] : admin_url() ); ?>">
						<span class="dashicons dashicons-wordpress" aria-hidden="true"></span>
						<span class="fw-app-side__text"><?php echo esc_html( $this->args['back'] ); ?></span>
					</a>
				</div>
			</nav>

			<div class="fw-app-main">
				<header class="fw-app-top">
					<button type="button" class="fw-app-top__menu" aria-controls="fw-app-side" aria-expanded="false">
						<span class="dashicons dashicons-menu-alt" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: the app's name, e.g. "SEO". */
							printf( esc_html__( '%s menu', 'fw' ), esc_html( $brand['label'] ) );
							?>
						</span>
					</button>

					<div class="fw-app-top__title">
						<?php if ( $crumbs ) : ?>
							<ol class="fw-app-crumbs">
								<?php foreach ( $crumbs as $crumb ) : ?>
									<li>
										<?php if ( ! empty( $crumb[1] ) ) : ?>
											<a href="<?php echo esc_url( $crumb[1] ); ?>"><?php echo esc_html( $crumb[0] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $crumb[0] ); ?>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
						<h1><?php echo esc_html( $title ); ?></h1>
					</div>

					<?php
					// Both slots are the caller's own markup, escaped by them.
					// phpcs:disable WordPress.Security.EscapeOutput
					if ( ! empty( $bar['search'] ) ) {
						echo $bar['search'];
					}

					if ( ! empty( $bar['actions'] ) ) {
						echo '<div class="fw-app-top__actions">' . $bar['actions'] . '</div>';
					}
					// phpcs:enable
					?>
				</header>

				<div class="wrap fw-app-body <?php echo esc_attr( (string) ( $bar['class'] ?? '' ) ); ?>">
					<?php // WordPress moves admin notices to just after this marker. ?>
					<hr class="wp-header-end" />
		<?php
	}

	/** Close the frame opened by open(). */
	public function close() {
		?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * The label of a view, for the default page title.
	 *
	 * @param string $view
	 * @param array  $nav
	 *
	 * @return string
	 */
	protected function label_of( $view, array $nav ) {
		foreach ( $nav as $group ) {
			if ( isset( $group['items'][ $view ]['label'] ) ) {
				return (string) $group['items'][ $view ]['label'];
			}
		}

		return '';
	}
}
