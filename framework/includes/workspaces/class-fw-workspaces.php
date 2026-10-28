<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Workspaces: let a site open to one app instead of the WordPress dashboard.
 *
 * Some sites are not websites first. A site run as a CRM is used all day by
 * people who never write a post; a members' site has staff who only look after
 * members. For them the WordPress dashboard is noise between them and the one
 * screen they came for. A workspace is that screen, chosen per ROLE:
 *
 *  - people in the role land on the app after login (and from Dashboard);
 *  - their WordPress menu shows the app and Profile, plus what the role's menu
 *    (Workspaces → Menus, FW_Admin_Menu_Editor) brings back;
 *  - the admin bar loses its WordPress items;
 *  - app screens open with the WordPress menu hidden (focus mode) until they
 *    choose otherwise.
 *
 * It lives in core rather than in one extension because "CRM mode" and
 * "Membersite mode" are the same mechanism pointed at different apps. Each
 * app-like extension REGISTERS a workspace; this class decides who sees which.
 *
 * Members are the other half: on the Roles tab a role can be kept out of
 * wp-admin altogether (members of a site, who have an account page instead),
 * and one-click set-ups apply the roles an app suggests.
 *
 * Hiding a menu is not a permission. A hidden screen is still reachable by its
 * URL, so a role that must not use something should not have its capability —
 * which is why the CRM ships its own CRM Manager and Sales Rep roles.
 *
 * Register from an extension, on `fw_workspaces_register`:
 *
 *     add_action( 'fw_workspaces_register', function () {
 *         fw_register_workspace( 'crm', array(
 *             'label'      => __( 'CRM', 'fw' ),
 *             'icon'       => 'dashicons-id-alt',
 *             'page'       => 'fw-crm',
 *             'capability' => 'fw_crm_access',
 *         ) );
 *     } );
 *
 * @since 3.0.55
 */
class FW_Workspaces {

	/** Option holding the per-role choices and the own-menu-item switches. */
	const OPTION = 'fw_workspaces';

	/** User meta: 1 while an administrator has switched back to WordPress. */
	const OFF_META = 'fw_workspace_off';

	/** The settings screen. */
	const PAGE_SLUG   = 'fw-workspaces';
	const PARENT_SLUG = 'fw-extensions';

	/** @var array|null id => args, filled on first use. */
	protected static $workspaces = null;

	/** @var array user id => workspace id|'' for this request. */
	protected static $active = array();

	public static function init() {
		if ( defined( 'FW_WORKSPACES_OFF' ) && FW_WORKSPACES_OFF ) {
			return;
		}

		require_once __DIR__ . '/class-fw-admin-menu-editor.php';
		FW_Admin_Menu_Editor::init();

		// Late: a workspace chosen for the role outranks an extension's own landing page (Membership's account page).
		add_filter( 'login_redirect', array( __CLASS__, '_filter_login_redirect' ), 99, 3 );
		add_filter( 'show_admin_bar', array( __CLASS__, '_filter_show_admin_bar' ), 99 );

		if ( ! is_admin() ) {
			add_action( 'admin_bar_menu', array( __CLASS__, '_action_admin_bar' ), 999 );
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, '_action_settings_menu' ), 40 );
		// Late, so every extension has added its pages before they are moved or hidden.
		add_action( 'admin_menu', array( __CLASS__, '_action_shape_menu' ), PHP_INT_MAX - 10 );
		add_action( 'admin_head', array( __CLASS__, '_action_admin_head' ) );
		add_action( 'load-index.php', array( __CLASS__, '_action_dashboard' ) );
		add_action( 'admin_init', array( __CLASS__, '_action_keep_out' ), 1 );
		add_action( 'admin_init', array( __CLASS__, '_action_switch' ) );
		add_action( 'admin_post_fw_workspaces_save', array( __CLASS__, '_action_save' ) );
		add_action( 'admin_post_fw_workspaces_setup', array( __CLASS__, '_action_setup' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, '_action_admin_bar' ), 999 );
		add_filter( 'fw_admin_app_focus_default', array( __CLASS__, '_filter_focus_default' ) );
	}

	/* ---------------------------------------------------------------------- *
	 * Registry
	 * ---------------------------------------------------------------------- */

	/**
	 * @param string $id
	 * @param array  $args {
	 *     @type string $label       The app's name, e.g. "CRM".
	 *     @type string $icon        A dashicons-* class.
	 *     @type string $page        The admin page slug the app lives on.
	 *     @type string $capability  Needed to use the app. Default 'manage_options'.
	 *     @type string $description One line for the settings screen.
	 *     @type array  $roles       Roles the one-click set-up opens to this app (staff).
	 *     @type array  $members     Roles the one-click set-up keeps out of wp-admin (members).
	 *     @type string $setup       The set-up button's label, e.g. "Set up the CRM".
	 * }
	 *
	 * @return bool
	 */
	public static function register( $id, array $args ) {
		$id = sanitize_key( $id );

		if ( '' === $id || empty( $args['page'] ) ) {
			return false;
		}

		if ( null === self::$workspaces ) {
			self::$workspaces = array();
		}

		self::$workspaces[ $id ] = array_merge( array(
			'label'       => $id,
			'icon'        => 'dashicons-admin-generic',
			'page'        => '',
			'capability'  => 'manage_options',
			'description' => '',
			'roles'       => array(),
			'members'     => array(),
			'setup'       => '',
		), $args, array( 'id' => $id ) );

		return true;
	}

	/** @return array id => args */
	public static function all() {
		if ( null === self::$workspaces ) {
			self::$workspaces = array();
			/** Register workspaces here with fw_register_workspace(). */
			do_action( 'fw_workspaces_register' );
		}

		return self::$workspaces;
	}

	/** @return array|null */
	public static function get( $id ) {
		$all = self::all();

		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/** @return string The URL a workspace opens on. */
	public static function url( $id ) {
		$ws = self::get( $id );

		return $ws ? admin_url( 'admin.php?page=' . $ws['page'] ) : admin_url();
	}

	/* ---------------------------------------------------------------------- *
	 * Settings
	 * ---------------------------------------------------------------------- */

	/** @return array { roles: role => { workspace }, menu: id => 1, members: { roles[], page } } */
	public static function settings() {
		$s = get_option( self::OPTION, array() );
		$s = is_array( $s ) ? $s : array();
		$m = isset( $s['members'] ) && is_array( $s['members'] ) ? $s['members'] : array();

		return array(
			'roles'   => isset( $s['roles'] ) && is_array( $s['roles'] ) ? $s['roles'] : array(),
			'menu'    => isset( $s['menu'] ) && is_array( $s['menu'] ) ? $s['menu'] : array(),
			'members' => array(
				'roles' => isset( $m['roles'] ) && is_array( $m['roles'] ) ? array_values( $m['roles'] ) : array(),
				'page'  => isset( $m['page'] ) ? (int) $m['page'] : 0,
			),
		);
	}

	/**
	 * Clean and store settings. Unknown roles and workspaces are dropped.
	 *
	 * @param array $input Same shape as settings().
	 *
	 * @return array What was saved.
	 */
	public static function save( array $input ) {
		$roles = wp_roles()->get_names();
		$out   = array( 'roles' => array(), 'menu' => array(), 'members' => array( 'roles' => array(), 'page' => 0 ) );

		if ( ! isset( $input['menu'] ) ) {
			$input['menu'] = self::settings()['menu'];
		}
		if ( ! isset( $input['members'] ) ) {
			$input['members'] = self::settings()['members'];
		}

		foreach ( (array) ( $input['members']['roles'] ?? array() ) as $role ) {
			$role = sanitize_key( $role );
			// Never a role that runs the site: keeping it out of wp-admin would lock the owner out.
			$obj = get_role( $role );
			if ( isset( $roles[ $role ] ) && $obj && ! $obj->has_cap( 'manage_options' ) ) {
				$out['members']['roles'][] = $role;
			}
		}
		$out['members']['roles'] = array_values( array_unique( $out['members']['roles'] ) );
		$page                    = (int) ( $input['members']['page'] ?? 0 );
		$out['members']['page']  = $page && 'page' === get_post_type( $page ) ? $page : 0;

		foreach ( (array) ( $input['roles'] ?? array() ) as $role => $row ) {
			$role = sanitize_key( $role );
			$ws   = sanitize_key( $row['workspace'] ?? '' );

			if ( ! isset( $roles[ $role ] ) || '' === $ws || ! self::get( $ws ) ) {
				continue;
			}

			$out['roles'][ $role ] = array( 'workspace' => $ws );
		}

		foreach ( (array) ( $input['menu'] ?? array() ) as $id => $on ) {
			$id = sanitize_key( $id );
			if ( $on && self::get( $id ) ) {
				$out['menu'][ $id ] = 1;
			}
		}

		update_option( self::OPTION, $out, false );
		self::$active = array();

		return $out;
	}

	/**
	 * The workspace a person's roles give them, ignoring the switch-back.
	 *
	 * With several roles, the first role (in the order the user has them) that
	 * names a workspace the person can actually open wins.
	 *
	 * @param WP_User|int $user
	 *
	 * @return string Workspace id, or '' for plain WordPress.
	 */
	public static function for_user( $user ) {
		$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );

		if ( ! $user || ! $user->exists() ) {
			return '';
		}

		$roles = self::settings()['roles'];

		foreach ( (array) $user->roles as $role ) {
			$id = $roles[ $role ]['workspace'] ?? '';
			$ws = $id ? self::get( $id ) : null;

			if ( $ws && user_can( $user, $ws['capability'] ) ) {
				return $id;
			}
		}

		return '';
	}

	/** @return bool Whether this person may step out of their workspace. */
	public static function can_switch( $user_id = 0 ) {
		return user_can( $user_id ? $user_id : get_current_user_id(), 'manage_options' );
	}

	/**
	 * The workspace in force for the current user on this request.
	 *
	 * @return string Workspace id, or ''.
	 */
	public static function active() {
		$uid = get_current_user_id();

		if ( ! $uid ) {
			return '';
		}

		if ( ! isset( self::$active[ $uid ] ) ) {
			$id = self::for_user( $uid );

			if ( $id && self::can_switch( $uid ) && get_user_meta( $uid, self::OFF_META, true ) ) {
				$id = '';
			}

			self::$active[ $uid ] = $id;
		}

		return self::$active[ $uid ];
	}

	/** @return string[] The workspaces that get a top-level menu item for the current user. */
	protected static function promoted() {
		// Only the workspace in force: giving an app its own place in everyone else's
		// menu is the menu editor's "move to top level" now.
		$active = self::active();

		return $active && self::get( $active ) ? array( $active ) : array();
	}

	/* ---------------------------------------------------------------------- *
	 * Members: kept out of wp-admin
	 * ---------------------------------------------------------------------- */

	/**
	 * Whether a person is a member the site keeps out of wp-admin: one of their
	 * roles is ticked, none of their roles opens to an app (staff win), and they
	 * cannot manage the site.
	 *
	 * @param WP_User|int|null $user Default the current user.
	 */
	public static function is_kept_out( $user = null ) {
		$user = null === $user ? wp_get_current_user() : ( $user instanceof WP_User ? $user : get_userdata( (int) $user ) );

		if ( ! $user || ! $user->exists() || user_can( $user, 'manage_options' ) || '' !== self::for_user( $user ) ) {
			return false;
		}

		return (bool) array_intersect( (array) $user->roles, self::settings()['members']['roles'] );
	}

	/**
	 * Where a kept-out member goes instead of wp-admin: the page chosen on the
	 * Roles tab, else what an extension offers (Membership: the account page),
	 * else the home page.
	 */
	public static function member_home() {
		$page = self::settings()['members']['page'];

		if ( $page && 'publish' === get_post_status( $page ) ) {
			return get_permalink( $page );
		}

		/**
		 * Where members kept out of wp-admin are sent. Membership answers with its
		 * account page. Must not be a wp-admin address.
		 *
		 * @param string $url
		 */
		$url = (string) apply_filters( 'fw_workspaces_member_home', home_url( '/' ) );

		return 0 === strpos( $url, admin_url() ) ? home_url( '/' ) : $url;
	}

	/**
	 * @internal A kept-out member opening wp-admin goes to their page. admin-ajax
	 * and admin-post stay open: front-end forms and the site's own features post there.
	 */
	public static function _action_keep_out() {
		global $pagenow;

		if ( wp_doing_ajax() || in_array( $pagenow, array( 'admin-post.php', 'async-upload.php' ), true ) || ! self::is_kept_out() ) {
			return;
		}

		wp_safe_redirect( self::member_home() );
		exit;
	}

	/** @internal No WordPress toolbar on the site for a kept-out member. */
	public static function _filter_show_admin_bar( $show ) {
		return $show && is_user_logged_in() && self::is_kept_out() ? false : $show;
	}

	/* ---------------------------------------------------------------------- *
	 * Landing
	 * ---------------------------------------------------------------------- */

	/** @internal Send people to their workspace after login, unless they were headed somewhere. */
	public static function _filter_login_redirect( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return $redirect_to;
		}

		$requested = (string) $requested;
		$default   = '' === $requested || untrailingslashit( $requested ) === untrailingslashit( admin_url() );

		// A member headed anywhere in wp-admin (a plan's own page is left alone) goes to their page.
		if ( self::is_kept_out( $user ) && 0 === strpos( (string) $redirect_to, admin_url() ) ) {
			return self::member_home();
		}

		if ( ! $default ) {
			return $redirect_to;
		}

		$id = self::for_user( $user );

		if ( $id && self::can_switch( $user->ID ) && get_user_meta( $user->ID, self::OFF_META, true ) ) {
			return $redirect_to;
		}

		return $id ? self::url( $id ) : $redirect_to;
	}

	/** @internal The Dashboard is not a place a workspace user goes. */
	public static function _action_dashboard() {
		$id = self::active();

		if ( $id && ! wp_doing_ajax() ) {
			wp_safe_redirect( self::url( $id ) );
			exit;
		}
	}

	/** @internal App screens open in focus mode in a workspace, until the person chooses. */
	public static function _filter_focus_default( $on ) {
		return self::active() ? true : $on;
	}

	/** @internal Administrators can step out of the workspace and back from the admin bar. */
	public static function _action_switch() {
		// phpcs:ignore WordPress.Security.NonceVerification -- verified below.
		if ( ! isset( $_GET['fw_workspace'] ) ) {
			return;
		}

		check_admin_referer( 'fw_workspace_switch' );

		$uid = get_current_user_id();

		if ( ! self::can_switch( $uid ) ) {
			return;
		}

		$off = 'wordpress' === $_GET['fw_workspace']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		update_user_meta( $uid, self::OFF_META, $off ? 1 : 0 );
		self::$active = array();

		$id = self::for_user( $uid );
		wp_safe_redirect( $off || ! $id ? admin_url() : self::url( $id ) );
		exit;
	}

	/* ---------------------------------------------------------------------- *
	 * The WordPress menu
	 * ---------------------------------------------------------------------- */

	/**
	 * @internal Give promoted workspaces a top-level item, remember the menu for
	 * the editor, then apply the role's menu (FW_Admin_Menu_Editor) or, for a
	 * workspace role without one, show only the workspace and Profile.
	 */
	public static function _action_shape_menu() {
		global $menu;

		$slugs = array();

		foreach ( self::promoted() as $id ) {
			$ws = self::get( $id );

			if ( ! current_user_can( $ws['capability'] ) ) {
				continue;
			}

			$slug = self::promote( $ws );

			if ( $slug ) {
				$slugs[] = $slug;
			}
		}

		FW_Admin_Menu_Editor::capture();

		$active  = self::active();
		$protect = array_merge( array( 'profile.php' ), $slugs );

		if ( FW_Admin_Menu_Editor::apply( $active, $protect ) || ! $active || ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $key => $item ) {
			if ( ! in_array( (string) ( $item[2] ?? '' ), $protect, true ) ) {
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Lift a workspace's page out of the submenu it lives in, to the top level.
	 *
	 * The page stays registered where it is — moving the registration would
	 * change its screen id and the access check WordPress makes against it — so
	 * the submenu entry is only hidden, and a top-level item links to the same URL.
	 *
	 * @return string The top-level slug that now leads to the workspace, or ''.
	 */
	protected static function promote( array $ws ) {
		global $menu, $submenu;

		foreach ( (array) $menu as $k => $item ) {
			// Already a top-level page (the CRM is, for people without the Unyson+ menu).
			if ( ( $item[2] ?? '' ) === $ws['page'] ) {
				$menu[ $k ][4] = trim( ( $item[4] ?? '' ) . ' fw-ws-menu' );
				return $ws['page'];
			}
		}

		foreach ( (array) $submenu as $parent => $items ) {
			foreach ( (array) $items as $i => $item ) {
				if ( ( $item[2] ?? '' ) !== $ws['page'] ) {
					continue;
				}

				$item[3] = $item[3] ?? $item[0];
				$item[4] = trim( ( $item[4] ?? '' ) . ' fw-ws-moved' );
				$submenu[ $parent ][ $i ] = $item;

				$slug = 'admin.php?page=' . $ws['page'];
				$pos  = 3;
				while ( isset( $menu[ (string) $pos ] ) ) {
					$pos += 0.01;
				}

				$menu[ (string) $pos ] = array(
					$ws['label'],
					$ws['capability'],
					$slug,
					$ws['label'],
					'menu-top fw-ws-menu menu-icon-fw-ws-' . $ws['id'],
					// The id WordPress gives the same page registered at the top level, so a
					// menu layout knows the workspace by one key whoever is looking.
					'toplevel_page_' . sanitize_title_with_dashes( $ws['page'] ),
					$ws['icon'],
				);
				ksort( $menu, SORT_NUMERIC );

				return $slug;
			}
		}

		return '';
	}

	/**
	 * @internal Hide the moved submenu entries, and mark the promoted item current.
	 *
	 * Marked in the page rather than through the `parent_file` filter: WordPress
	 * works the parent out again from where the page is REGISTERED (inside the
	 * Unyson+ menu) after that filter has run, so the filter alone never sticks.
	 */
	public static function _action_admin_head() {
		global $plugin_page;

		echo '<style>#adminmenu .fw-ws-moved{display:none}</style>';

		foreach ( self::promoted() as $id ) {
			$ws = self::get( $id );

			if ( ! $plugin_page || $plugin_page !== $ws['page'] ) {
				continue;
			}
			?>
			<script>
			document.addEventListener( 'DOMContentLoaded', function () {
				var item = document.getElementById( <?php echo wp_json_encode( 'toplevel_page_' . sanitize_title_with_dashes( $ws['page'] ) ); ?> );
				if ( ! item ) { return; }
				document.querySelectorAll( '#adminmenu > li.wp-has-current-submenu' ).forEach( function ( li ) {
					li.classList.remove( 'wp-has-current-submenu', 'wp-menu-open' );
					li.classList.add( 'wp-not-current-submenu' );
					var a = li.querySelector( 'a' );
					if ( a ) { a.classList.remove( 'wp-has-current-submenu', 'wp-menu-open' ); a.classList.add( 'wp-not-current-submenu' ); }
				} );
				item.classList.remove( 'wp-not-current-submenu' );
				item.classList.add( 'current' );
				var link = item.querySelector( 'a' );
				link.classList.remove( 'wp-not-current-submenu' );
				link.classList.add( 'current' );
				link.setAttribute( 'aria-current', 'page' );
			} );
			</script>
			<?php
		}
	}

	/* ---------------------------------------------------------------------- *
	 * The admin bar
	 * ---------------------------------------------------------------------- */

	/** @internal Trim the WordPress items in a workspace; offer administrators the way out. */
	public static function _action_admin_bar( $bar ) {
		$uid = get_current_user_id();
		$id  = self::active();

		if ( $id ) {
			foreach ( array( 'wp-logo', 'comments', 'new-content', 'updates', 'customize' ) as $node ) {
				$bar->remove_node( $node );
			}

			$ws = self::get( $id );
			$bar->add_node( array(
				'id'    => 'fw-workspace',
				'title' => '<span class="ab-icon dashicons ' . esc_attr( $ws['icon'] ) . '" style="font:normal 20px/1 dashicons;margin-top:2px"></span>' . esc_html( $ws['label'] ),
				'href'  => self::url( $id ),
			) );
		}

		if ( ! is_admin() || ! self::can_switch( $uid ) ) {
			return;
		}

		$mine = self::for_user( $uid );
		$menu = '' !== FW_Admin_Menu_Editor::role_for_user();

		if ( ! $mine && ! $menu ) {
			return;
		}

		$off = (bool) get_user_meta( $uid, self::OFF_META, true );

		if ( $mine ) {
			$ws = self::get( $mine );
			/* translators: %s: a workspace name, e.g. "CRM". */
			$title = $off ? sprintf( __( 'Switch to %s', 'fw' ), $ws['label'] ) : __( 'Switch to WordPress', 'fw' );
		} else {
			// No workspace, but this role's menu was customised: the way back to the full one.
			$title = $off ? __( 'Use my menu', 'fw' ) : __( 'Show the full menu', 'fw' );
		}

		$bar->add_node( array(
			'id'     => 'fw-workspace-switch',
			'parent' => 'top-secondary',
			'title'  => esc_html( $title ),
			'href'   => wp_nonce_url( add_query_arg( 'fw_workspace', $off ? 'on' : 'wordpress', admin_url() ), 'fw_workspace_switch' ),
		) );
	}

	/* ---------------------------------------------------------------------- *
	 * Settings screen: Unyson+ → Workspaces
	 * ---------------------------------------------------------------------- */

	/** @internal */
	public static function _action_settings_menu() {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Admin Menu', 'fw' ),
			__( 'Admin Menu', 'fw' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/** @internal */
	public static function _action_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change workspaces.', 'fw' ), 403 );
		}

		check_admin_referer( 'fw_workspaces_save' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned in save().
		$input = isset( $_POST['fw_workspaces'] ) ? (array) wp_unslash( $_POST['fw_workspaces'] ) : array();
		self::save( $input );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'roles', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Apply an app's suggested set-up: its staff roles open to it, its member roles
	 * are kept out of wp-admin, and administrators get it as a menu item of its own.
	 * Roles not named are left as they are.
	 *
	 * @param string $id Workspace id.
	 *
	 * @return bool
	 */
	public static function setup( $id ) {
		$ws = self::get( $id );

		if ( ! $ws ) {
			return false;
		}

		$s     = self::settings();
		$roles = wp_roles()->get_names();

		foreach ( (array) $ws['roles'] as $role ) {
			if ( isset( $roles[ $role ] ) ) {
				$s['roles'][ $role ] = array( 'workspace' => $id );
			}
		}

		$s['members']['roles'] = array_merge( $s['members']['roles'], (array) $ws['members'] );
		self::save( $s );

		FW_Admin_Menu_Editor::lift_for_admins( self::PARENT_SLUG, $ws['page'] );

		return true;
	}

	/** @internal */
	public static function _action_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change workspaces.', 'fw' ), 403 );
		}

		check_admin_referer( 'fw_workspaces_setup' );

		$id = isset( $_POST['workspace'] ) ? sanitize_key( wp_unslash( $_POST['workspace'] ) ) : '';
		self::setup( $id );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'roles', 'setup' => $id ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** @internal */
	public static function render() {
		$all      = self::all();
		$settings = self::settings();
		$roles    = wp_roles();
		// phpcs:ignore WordPress.Security.NonceVerification -- which tab to show.
		$tab      = isset( $_GET['tab'] ) && 'roles' === $_GET['tab'] ? 'roles' : 'menus';
		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		?>
		<div class="wrap fw-workspaces">
			<h1><?php esc_html_e( 'Admin Menu', 'fw' ); ?></h1>

			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Admin Menu sections', 'fw' ); ?>">
				<a href="<?php echo esc_url( $base_url ); ?>" class="nav-tab<?php echo 'menus' === $tab ? ' nav-tab-active' : ''; ?>"<?php echo 'menus' === $tab ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Menus', 'fw' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'roles', $base_url ) ); ?>" class="nav-tab<?php echo 'roles' === $tab ? ' nav-tab-active' : ''; ?>"<?php echo 'roles' === $tab ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Roles', 'fw' ); ?></a>
			</nav>

			<?php
			if ( 'menus' === $tab ) {
				FW_Admin_Menu_Editor::render();
				echo '</div>';
				return;
			}
			?>

			<p class="description" style="max-width:70ch;margin-top:16px">
				<?php esc_html_e( 'Choose what each role opens to. A role that opens to an app (a workspace, such as the CRM) lands on it after login and from Dashboard, sees the app and Profile plus what you show for it under Menus, gets a trimmed admin bar, and sees the app without the WordPress menu until it asks for it. Hiding a menu does not take a permission away: a role should only have the capabilities it needs.', 'fw' ); ?>
			</p>

			<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Roles saved.', 'fw' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['setup'] ) && self::get( sanitize_key( $_GET['setup'] ) ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					/* translators: %s: a workspace name, e.g. "CRM". */
					printf( esc_html__( '%s is set up. Check the roles below, and each role\'s menu under Menus.', 'fw' ), esc_html( self::get( sanitize_key( $_GET['setup'] ) )['label'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
					?>
				</p></div>
			<?php endif; ?>

			<?php
			$setups = array_filter( $all, function ( $ws ) {
				return '' !== $ws['setup'] && ( $ws['roles'] || $ws['members'] );
			} );
			?>
			<?php if ( $setups ) : ?>
				<h2><?php esc_html_e( 'Quick set-up', 'fw' ); ?></h2>
				<div class="fw-ws-setups" style="display:flex;flex-wrap:wrap;gap:12px;max-width:960px">
					<?php foreach ( $setups as $id => $ws ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="flex:1 1 280px;padding:14px 16px;border:1px solid #dcdcde;border-radius:8px;background:#fff">
							<input type="hidden" name="action" value="fw_workspaces_setup" />
							<input type="hidden" name="workspace" value="<?php echo esc_attr( $id ); ?>" />
							<?php wp_nonce_field( 'fw_workspaces_setup' ); ?>
							<p style="margin:0 0 6px;font-weight:600"><span class="dashicons <?php echo esc_attr( $ws['icon'] ); ?>" aria-hidden="true"></span> <?php echo esc_html( $ws['setup'] ); ?></p>
							<p class="description" style="margin:0 0 10px">
								<?php
								$names = function ( $list ) use ( $roles ) {
									$out = array();
									foreach ( $list as $r ) {
										$n = $roles->get_names()[ $r ] ?? '';
										if ( $n ) {
											$out[] = translate_user_role( $n );
										}
									}
									return implode( ', ', $out );
								};
								$parts = array();
								if ( $names( $ws['roles'] ) ) {
									/* translators: 1: role names; 2: app name. */
									$parts[] = sprintf( __( 'Opens to %2$s: %1$s', 'fw' ), $names( $ws['roles'] ), $ws['label'] );
								}
								if ( $names( $ws['members'] ) ) {
									/* translators: %s: role names. */
									$parts[] = sprintf( __( 'Kept out of WordPress: %s', 'fw' ), $names( $ws['members'] ) );
								}
								/* translators: %s: app name. */
								$parts[] = sprintf( __( 'Administrators get %s as a menu item of its own', 'fw' ), $ws['label'] );
								echo esc_html( implode( '. ', $parts ) . '.' );
								?>
							</p>
							<?php submit_button( __( 'Apply', 'fw' ), 'secondary', 'submit', false ); ?>
						</form>
					<?php endforeach; ?>
				</div>
				<h2><?php esc_html_e( 'Roles', 'fw' ); ?></h2>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fw_workspaces_save" />
				<?php wp_nonce_field( 'fw_workspaces_save' ); ?>

				<p class="description"><?php esc_html_e( 'Administrators set to an app can switch back to WordPress from the admin bar at any time.', 'fw' ); ?></p>
				<table class="widefat striped" style="max-width:960px;margin-top:12px">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Role', 'fw' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Opens to', 'fw' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Members', 'fw' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Menu', 'fw' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $roles->get_names() as $role => $name ) : ?>
							<?php
							$row  = $settings['roles'][ $role ] ?? array();
							$cur  = $row['workspace'] ?? '';
							$obj  = $roles->get_role( $role );
							$base = "fw_workspaces[roles][{$role}]";
							?>
							<tr>
								<td><strong><?php echo esc_html( translate_user_role( $name ) ); ?></strong></td>
								<td>
									<label class="screen-reader-text" for="fw-ws-<?php echo esc_attr( $role ); ?>">
										<?php
										/* translators: %s: a role name. */
										printf( esc_html__( 'What %s opens to', 'fw' ), esc_html( translate_user_role( $name ) ) );
										?>
									</label>
									<select id="fw-ws-<?php echo esc_attr( $role ); ?>" name="<?php echo esc_attr( $base ); ?>[workspace]"<?php disabled( ! $all ); ?>>
										<option value=""><?php esc_html_e( 'WordPress (as usual)', 'fw' ); ?></option>
										<?php foreach ( $all as $id => $ws ) : ?>
											<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $cur, $id ); ?>><?php echo esc_html( $ws['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<?php if ( $cur && isset( $all[ $cur ] ) && $obj && ! $obj->has_cap( $all[ $cur ]['capability'] ) && 'administrator' !== $role ) : ?>
										<p class="description" style="color:#b32d2e">
											<?php
											/* translators: %s: a workspace name. */
											printf( esc_html__( 'This role cannot open %s, so it keeps WordPress until it is given access.', 'fw' ), esc_html( $all[ $cur ]['label'] ) );
											?>
										</p>
									<?php endif; ?>
								</td>
								<td>
										<?php if ( $obj && $obj->has_cap( 'manage_options' ) ) : ?>
											<span class="description"><?php esc_html_e( 'Runs the site', 'fw' ); ?></span>
										<?php else : ?>
											<label>
												<input type="checkbox" name="fw_workspaces[members][roles][]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $settings['members']['roles'], true ) ); ?> />
												<?php esc_html_e( 'Keep out of WordPress', 'fw' ); ?>
											</label>
										<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( FW_Admin_Menu_Editor::url( $role ) ); ?>">
										<?php
										echo null !== FW_Admin_Menu_Editor::get( $role )
											? esc_html__( 'Customised: edit', 'fw' )
											: ( $cur ? esc_html__( 'Workspace and Profile only: edit', 'fw' ) : esc_html__( 'WordPress menu: edit', 'fw' ) );
										?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Members', 'fw' ); ?></h2>
				<p class="description" style="max-width:70ch">
					<?php esc_html_e( 'People in a role kept out of WordPress never see wp-admin: signing in, the Dashboard or any admin address takes them to the page below, and the site shows them no WordPress toolbar. A role that opens to an app, or runs the site, is never kept out.', 'fw' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fw-ws-member-page"><?php esc_html_e( 'Send members to', 'fw' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'              => 'fw_workspaces[members][page]',
								'id'                => 'fw-ws-member-page',
								'selected'          => $settings['members']['page'],
								'show_option_none'  => class_exists( 'FW_Membership_Settings' ) ? __( 'Their member account page', 'fw' ) : __( 'The home page', 'fw' ),
								'option_none_value' => 0,
							) );
							?>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save roles', 'fw' ) ); ?>
			</form>
		</div>
		<?php
	}
}

/**
 * Register a workspace: an app a role can open to instead of the WordPress dashboard.
 * Call it on the `fw_workspaces_register` action. See FW_Workspaces::register().
 *
 * @param string $id
 * @param array  $args
 *
 * @return bool
 */
function fw_register_workspace( $id, array $args ) {
	return FW_Workspaces::register( $id, $args );
}
