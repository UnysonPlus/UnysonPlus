<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Theme override detection.
 *
 * The framework honours a long-standing rule inherited from its predecessor: an
 * active theme may ship `framework-customizations/extensions/<ext>/shortcodes/<name>/`
 * and its files win over the framework's own (see FW_Shortcode::locate_path(),
 * which walks the rewrite paths before the declared path). That is a FEATURE —
 * child themes legitimately replace a view or extend an options schema.
 *
 * It becomes a problem when a theme written for the predecessor framework is
 * activated. Those themes ship whole shortcode folders under names the framework
 * also uses (section, button, column, accordion, …). Their views predate the
 * current wrapper API — they never call sc_build_wrapper_attr() — so every
 * feature that rides on the wrapper silently stops working: responsive
 * "Hide on <device>" classes, animation hooks, preset classes. Nothing errors;
 * the option saves and simply never reaches the markup.
 *
 * This file does two things:
 *
 *  - detects which of the framework's own shortcodes the ACTIVE theme overrides,
 *    and whether each override looks stale (no wrapper-API call in its view);
 *  - stores the user's per-shortcode choice to ignore a given override, which
 *    the shortcodes loader reads when it assembles each shortcode's rewrite paths.
 *
 * Detection is filesystem-only and cached in a transient keyed by the active
 * theme + its version, so a theme switch or update re-scans.
 */

/** Option holding the shortcode tags whose theme override the user turned OFF. */
define( 'FW_UPW_THEME_OVERRIDES_OPT', 'unysonplus_disabled_theme_overrides' );

/** Transient holding the detected override map. */
define( 'FW_UPW_THEME_OVERRIDES_CACHE', 'fw_upw_theme_overrides' );

/**
 * Cache key component that changes when the active theme (or its version) does,
 * so switching or updating a theme invalidates the scan without a manual flush.
 *
 * It also folds in the modification time of each customizations directory. Theme
 * identity alone is not enough: dropping an override folder into a theme that has
 * already been scanned leaves its name and version untouched, so the cached (and
 * now wrong) result would survive until the transient expired. Adding or removing
 * a child directory updates the parent's mtime, which is two stat calls and makes
 * the cache self-invalidating for the case that actually matters.
 *
 * @return string
 */
function fw_upw_theme_overrides_fingerprint() {
	$theme = wp_get_theme();

	$parts = array(
		(string) get_template(),
		(string) get_stylesheet(),
		(string) ( $theme ? $theme->get( 'Version' ) : '' ),
	);

	foreach ( array(
		fw_get_template_customizations_directory( '/extensions/shortcodes/shortcodes' ),
		is_child_theme() ? fw_get_stylesheet_customizations_directory( '/extensions/shortcodes/shortcodes' ) : null,
	) as $dir ) {
		$parts[] = ( $dir && is_dir( $dir ) ) ? (string) filemtime( $dir ) : '0';
	}

	return md5( implode( '|', $parts ) );
}

/**
 * Every shortcode the ACTIVE theme (child first, then parent) overrides.
 *
 * Only reports folders that exist on BOTH sides — a theme folder with no
 * framework counterpart is the theme adding its own shortcode, which is not an
 * override and not a problem.
 *
 * @param bool $force Skip the cache and re-scan.
 *
 * @return array tag => array( 'dir', 'title', 'source', 'path', 'stale' )
 */
function fw_upw_theme_shortcode_overrides( $force = false ) {
	$cache_key = FW_UPW_THEME_OVERRIDES_CACHE . '_' . fw_upw_theme_overrides_fingerprint();

	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$found = array();

	// The framework's own shortcodes — the set an override can collide with.
	$core_dir = fw_get_framework_directory( '/extensions/shortcodes/shortcodes' );
	if ( ! is_dir( $core_dir ) ) {
		set_transient( $cache_key, $found, DAY_IN_SECONDS );

		return $found;
	}

	$core = array();
	foreach ( (array) glob( $core_dir . '/*', GLOB_ONLYDIR ) as $dir ) {
		$core[ strtolower( basename( $dir ) ) ] = true;
	}

	// Child theme wins over parent, so scan it last and let it overwrite.
	$sources = array();
	if ( is_child_theme() ) {
		$sources['parent'] = fw_get_template_customizations_directory( '/extensions/shortcodes/shortcodes' );
		$sources['child']  = fw_get_stylesheet_customizations_directory( '/extensions/shortcodes/shortcodes' );
	} else {
		$sources['theme'] = fw_get_template_customizations_directory( '/extensions/shortcodes/shortcodes' );
	}

	foreach ( $sources as $source => $base ) {
		if ( ! $base || ! is_dir( $base ) ) {
			continue;
		}

		foreach ( (array) glob( $base . '/*', GLOB_ONLYDIR ) as $dir ) {
			$name = strtolower( basename( $dir ) );

			if ( ! isset( $core[ $name ] ) ) {
				continue; // theme's own shortcode, not an override
			}

			$found[ str_replace( '-', '_', $name ) ] = array(
				'dir'    => $name,
				'title'  => ucwords( str_replace( array( '-', '_' ), ' ', $name ) ),
				'source' => $source,
				'path'   => $dir,
				'stale'  => fw_upw_override_is_stale( $dir ),
			);
		}
	}

	ksort( $found );
	set_transient( $cache_key, $found, DAY_IN_SECONDS );

	return $found;
}

/**
 * Whether an override predates the current wrapper API.
 *
 * A view that never calls sc_build_wrapper_attr() cannot emit the wrapper classes
 * the framework relies on, so anything riding on them (responsive hide, animation
 * hooks, preset classes) is silently dropped for that element. That is the
 * distinction worth surfacing to the user: a deliberate child-theme override is
 * usually current; a predecessor-era theme's is not.
 *
 * @param string $dir Absolute path to the override folder.
 *
 * @return bool
 */
function fw_upw_override_is_stale( $dir ) {
	$view = $dir . '/views/view.php';

	if ( ! file_exists( $view ) ) {
		return false; // overrides only options/static — the framework view still renders
	}

	$src = file_get_contents( $view );

	if ( false === $src ) {
		return false;
	}

	return ( false === strpos( $src, 'sc_build_wrapper_attr' ) );
}

/** Option holding shortcode tags the user restored after a theme removed them. */
define( 'FW_UPW_RESTORED_SC_OPT', 'unysonplus_restored_shortcodes' );

/**
 * Shortcodes the ACTIVE THEME removes outright via the public
 * `fw_ext_shortcodes_disable_shortcodes` filter.
 *
 * This is a second, separate way a theme interferes, and a worse one than
 * overriding a view. An override changes how an element renders; the filter makes
 * the element cease to exist, so a page already using it renders "shortcode not
 * found" with nothing in the UI naming the cause. Predecessor-era themes do this
 * routinely, to hide framework elements they ship their own equivalents of.
 *
 * Attribution matters here: the framework itself hooks that filter twice (the
 * Shortcodes settings screen, and the Theme Builder element scoping), and those
 * removals are intentional and must not be reported as the theme's doing. So each
 * callback is traced back to the file that declared it, and only callbacks living
 * inside the active theme are run to see what they contribute.
 *
 * @return array tag => true
 */
function fw_upw_theme_disabled_shortcodes() {
	global $wp_filter;

	$hook = 'fw_ext_shortcodes_disable_shortcodes';

	if ( empty( $wp_filter[ $hook ] ) ) {
		return array();
	}

	$theme_dirs = array_filter( array(
		get_template_directory(),
		is_child_theme() ? get_stylesheet_directory() : null,
	) );

	$from_theme = array();

	foreach ( $wp_filter[ $hook ] as $callbacks ) {
		foreach ( (array) $callbacks as $cb ) {
			if ( ! isset( $cb['function'] ) || ! is_callable( $cb['function'] ) ) {
				continue;
			}

			$file = fw_upw_callback_file( $cb['function'] );

			if ( ! $file ) {
				continue;
			}

			$file = wp_normalize_path( $file );
			$owned_by_theme = false;
			foreach ( $theme_dirs as $dir ) {
				if ( 0 === strpos( $file, wp_normalize_path( $dir ) ) ) {
					$owned_by_theme = true;
					break;
				}
			}

			if ( ! $owned_by_theme ) {
				continue;
			}

			// Run this callback alone: what it adds to an empty list is what the
			// theme removes, independent of what anything else contributed.
			$added = call_user_func( $cb['function'], array() );

			if ( is_array( $added ) ) {
				foreach ( $added as $tag ) {
					$from_theme[ str_replace( '-', '_', strtolower( (string) $tag ) ) ] = true;
				}
			}
		}
	}

	// Only report tags the framework actually ships — a theme disabling something
	// that does not exist here (its own element, or a stale entry) is not a loss.
	$core_dir = fw_get_framework_directory( '/extensions/shortcodes/shortcodes' );
	$exists   = array();
	foreach ( (array) glob( $core_dir . '/*', GLOB_ONLYDIR ) as $dir ) {
		$exists[ str_replace( '-', '_', strtolower( basename( $dir ) ) ) ] = true;
	}

	return array_intersect_key( $from_theme, $exists );
}

/**
 * Absolute path of the file that declared a callback, or '' when it cannot be
 * determined (an internal function, or a closure bound to nothing useful).
 *
 * @param callable $fn
 *
 * @return string
 */
function fw_upw_callback_file( $fn ) {
	try {
		if ( is_string( $fn ) && function_exists( $fn ) ) {
			$r = new ReflectionFunction( $fn );
		} elseif ( $fn instanceof Closure ) {
			$r = new ReflectionFunction( $fn );
		} elseif ( is_array( $fn ) && count( $fn ) === 2 ) {
			$r = new ReflectionMethod( $fn[0], $fn[1] );
		} else {
			return '';
		}

		return (string) $r->getFileName();
	} catch ( ReflectionException $e ) {
		return '';
	}
}

/**
 * Shortcode tags the user asked to keep despite the theme removing them.
 *
 * @return array tag => true
 */
function fw_upw_restored_shortcodes() {
	return fw_upw_read_theme_choices( FW_UPW_RESTORED_SC_OPT );
}

/**
 * Persist the restore list.
 *
 * @param array $tags
 */
function fw_upw_set_restored_shortcodes( array $tags ) {
	fw_upw_write_theme_choices( FW_UPW_RESTORED_SC_OPT, $tags );
}

/**
 * Take the user's restored tags back out of the disable list.
 *
 * Runs at a very late priority so it sees everything the theme (and anything else)
 * added, whatever order the filters were registered in. Both the hyphenated and
 * underscored spellings are stripped, because themes use them interchangeably --
 * Jevelin's list, for instance, carries both "call-to-action" and "call_to_action".
 *
 * @param array $disabled
 *
 * @return array
 */
function fw_upw_restore_disabled_shortcodes( $disabled ) {
	$restored = fw_upw_restored_shortcodes();

	if ( empty( $restored ) || ! is_array( $disabled ) ) {
		return $disabled;
	}

	return array_values( array_filter( $disabled, function ( $tag ) use ( $restored ) {
		$norm = str_replace( '-', '_', strtolower( (string) $tag ) );

		return empty( $restored[ $norm ] );
	} ) );
}

add_filter( 'fw_ext_shortcodes_disable_shortcodes', 'fw_upw_restore_disabled_shortcodes', 9999 );

/**
 * The layout primitives, whose page-builder tab a theme may not move.
 *
 * These three are the framework's structural contract rather than content: every
 * tutorial, screenshot and doc page tells a new user to open Classic Layout and
 * pick a Section. A theme that files its own Section under a different tab leaves
 * Classic Layout looking empty and the documentation wrong, with nothing naming
 * the cause — which is a navigation problem, not a styling preference.
 *
 * The line is deliberately narrow. Where a theme puts its buttons, headings, media
 * or its own fifty elements is genuinely the theme's business and is left alone;
 * only these are pinned.
 *
 * (`column` is listed for completeness and is a no-op in practice: its width tiles
 * are registered by Page_Builder_Column_Item, not from config, so a theme's column
 * config cannot relocate them.)
 *
 * @return array
 */
function fw_upw_pinned_layout_tags() {
	/** Filters the shortcode tags whose page-builder tab an active theme cannot override. */
	return apply_filters( 'fw_upw_pinned_layout_tags', array( 'section', 'column', 'container' ) );
}

/**
 * The tab the FRAMEWORK declares for a shortcode, read from its own config.php.
 *
 * Read rather than hardcoded so the value stays correct (and translated) if the
 * framework ever renames the tab.
 *
 * @param string $tag
 *
 * @return string '' when the framework declares none.
 */
function fw_upw_framework_tab_for( $tag ) {
	static $cache = array();

	if ( isset( $cache[ $tag ] ) ) {
		return $cache[ $tag ];
	}

	$dir  = str_replace( '_', '-', $tag );
	$file = fw_get_framework_directory( '/extensions/shortcodes/shortcodes/' . $dir . '/config.php' );

	$tab = '';
	if ( file_exists( $file ) ) {
		$vars = fw_get_variables_from_file( $file, array( 'cfg' => null ) );
		if ( isset( $vars['cfg']['page_builder']['tab'] ) ) {
			$tab = (string) $vars['cfg']['page_builder']['tab'];
		}
	}

	return $cache[ $tag ] = $tab;
}

/**
 * Pin a layout primitive's builder tab back to the framework's own.
 *
 * Only the `tab` key is touched: the theme keeps every other config choice, and
 * this is independent of whether the user runs the theme's view or the framework's
 * — "where do I find it" and "whose markup does it emit" are separate questions.
 *
 * @param array  $config
 * @param string $tag
 *
 * @return array
 */
function fw_upw_pin_layout_tab( $config, $tag ) {
	if ( ! is_array( $config ) || ! isset( $config['page_builder']['tab'] ) ) {
		return $config;
	}

	if ( ! in_array( $tag, fw_upw_pinned_layout_tags(), true ) ) {
		return $config;
	}

	$framework_tab = fw_upw_framework_tab_for( $tag );

	if ( '' !== $framework_tab && $config['page_builder']['tab'] !== $framework_tab ) {
		$config['page_builder']['tab'] = $framework_tab;
	}

	return $config;
}

add_filter( 'fw_shortcode_get_config', 'fw_upw_pin_layout_tab', 10, 2 );

/**
 * Layout primitives whose tab the active theme tried to move (and which were
 * pinned back). Surfaced in the Theme overrides tab so the behaviour is visible
 * rather than mysterious.
 *
 * @return array tag => array( 'theme_tab', 'framework_tab' )
 */
function fw_upw_relocated_layout_tags() {
	$out = array();

	foreach ( fw_upw_theme_shortcode_overrides() as $tag => $info ) {
		if ( ! in_array( $tag, fw_upw_pinned_layout_tags(), true ) ) {
			continue;
		}

		$file = $info['path'] . '/config.php';
		if ( ! file_exists( $file ) ) {
			continue;
		}

		$vars      = fw_get_variables_from_file( $file, array( 'cfg' => null ) );
		$theme_tab = isset( $vars['cfg']['page_builder']['tab'] ) ? (string) $vars['cfg']['page_builder']['tab'] : '';
		$fw_tab    = fw_upw_framework_tab_for( $tag );

		if ( '' !== $theme_tab && '' !== $fw_tab && $theme_tab !== $fw_tab ) {
			$out[ $tag ] = array( 'theme_tab' => $theme_tab, 'framework_tab' => $fw_tab );
		}
	}

	return $out;
}

/**
 * Key under which a theme's choices are stored.
 *
 * The stylesheet (the child theme when there is one), because the choices are
 * about the overrides THAT theme supplies.
 *
 * @return string
 */
function fw_upw_theme_choice_key() {
	return (string) get_stylesheet();
}

/**
 * Read a per-theme choice map, migrating the flat shape in place.
 *
 * These options were originally a flat `tag => true` list, which silently applied
 * one theme's decisions to the next: switch themes and a choice made about the old
 * theme's Section quietly governed the new theme's, with no notice and no way to
 * tell. Choices are scoped to the theme they were made about.
 *
 * A flat map found on read is attributed to the active theme — the only theme it
 * could have been made about — and rewritten in the new shape.
 *
 * @param string $option
 *
 * @return array tag => true for the active theme
 */
function fw_upw_read_theme_choices( $option ) {
	$stored = get_option( $option, array() );

	if ( ! is_array( $stored ) || empty( $stored ) ) {
		return array();
	}

	$key = fw_upw_theme_choice_key();

	// Legacy flat shape: every value is a scalar rather than a per-theme array.
	$is_flat = true;
	foreach ( $stored as $v ) {
		if ( is_array( $v ) ) {
			$is_flat = false;
			break;
		}
	}

	if ( $is_flat ) {
		$migrated = array( $key => $stored );
		update_option( $option, $migrated, false );

		return $stored;
	}

	return isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();
}

/**
 * Write a per-theme choice map, leaving other themes' entries untouched.
 *
 * @param string $option
 * @param array  $tags
 */
function fw_upw_write_theme_choices( $option, array $tags ) {
	$stored = get_option( $option, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	// Collapse a legacy flat map first, so the write does not mix shapes.
	$is_flat = ! empty( $stored );
	foreach ( $stored as $v ) {
		if ( is_array( $v ) ) {
			$is_flat = false;
			break;
		}
	}
	if ( $is_flat ) {
		$stored = array( fw_upw_theme_choice_key() => $stored );
	}

	$clean = array();
	foreach ( $tags as $tag ) {
		$tag = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $tag ) );
		if ( '' !== $tag ) {
			$clean[ $tag ] = true;
		}
	}

	$key = fw_upw_theme_choice_key();

	if ( empty( $clean ) ) {
		unset( $stored[ $key ] );
	} else {
		$stored[ $key ] = $clean;
	}

	update_option( $option, $stored, false );
}

/**
 * Shortcode tags whose theme override the user switched off, for the ACTIVE theme.
 *
 * @return array tag => true
 */
function fw_upw_disabled_theme_overrides() {
	return fw_upw_read_theme_choices( FW_UPW_THEME_OVERRIDES_OPT );
}

/**
 * Whether the theme's override for this shortcode should be ignored.
 *
 * Read by FW_Shortcodes_Loader when it assembles a shortcode's rewrite paths:
 * when true the theme paths are dropped and the framework's own files render.
 *
 * @param string $tag Shortcode tag (underscored).
 *
 * @return bool
 */
function fw_upw_theme_override_disabled( $tag ) {
	$disabled = fw_upw_disabled_theme_overrides();

	return ! empty( $disabled[ $tag ] );
}

/**
 * Persist the user's choices and drop the detection cache.
 *
 * @param array $tags Shortcode tags to ignore the theme override for.
 */
function fw_upw_set_disabled_theme_overrides( array $tags ) {
	fw_upw_write_theme_choices( FW_UPW_THEME_OVERRIDES_OPT, $tags );
	delete_transient( FW_UPW_THEME_OVERRIDES_CACHE . '_' . fw_upw_theme_overrides_fingerprint() );
}

/**
 * Admin notice when the active theme overrides framework shortcodes with views
 * that predate the wrapper API.
 *
 * Only stale overrides are announced: a current child-theme override is a normal,
 * intentional customization and warning about it would be noise. Dismissal is
 * stored per theme fingerprint, so a different theme (or a theme update) asks again.
 */
function fw_upw_theme_overrides_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && isset( $screen->id ) && false !== strpos( $screen->id, 'fw-shortcodes' ) ) {
		return; // the settings screen shows the full list; no need to repeat it
	}

	$fingerprint = fw_upw_theme_overrides_fingerprint();
	if ( get_user_meta( get_current_user_id(), 'fw_upw_overrides_dismissed_' . $fingerprint, true ) ) {
		return;
	}

	$overrides = fw_upw_theme_shortcode_overrides();
	$disabled  = fw_upw_disabled_theme_overrides();

	$stale = array();
	foreach ( $overrides as $tag => $info ) {
		if ( $info['stale'] && empty( $disabled[ $tag ] ) ) {
			$stale[] = $info['title'];
		}
	}

	// Elements the theme removes outright, minus any the user chose to keep.
	$removed  = array_diff_key( fw_upw_theme_disabled_shortcodes(), fw_upw_restored_shortcodes() );
	$removed  = array_keys( $removed );

	if ( empty( $stale ) && empty( $removed ) ) {
		return;
	}

	$url = admin_url( 'admin.php?page=fw-shortcodes#theme-overrides' );

	echo '<div class="notice notice-warning is-dismissible fw-upw-overrides-notice" data-fingerprint="' . esc_attr( $fingerprint ) . '">';
	echo '<p><strong>' . esc_html__( 'Your theme is changing the page-builder elements.', 'fw' ) . '</strong></p>';

	// Removals first: they break pages that already use the element, where an
	// override only drops newer settings.
	if ( ! empty( $removed ) ) {
		$titles = array();
		foreach ( $removed as $tag ) {
			$titles[] = ucwords( str_replace( '_', ' ', $tag ) );
		}
		echo '<p>' . sprintf(
			/* translators: %s: comma-separated list of element names */
			esc_html__( 'The active theme removes these elements entirely: %s. Any page already using one shows “shortcode not found” until the element is restored.', 'fw' ),
			'<strong>' . esc_html( implode( ', ', $titles ) ) . '</strong>'
		) . '</p>';
	}

	if ( ! empty( $stale ) ) {
		echo '<p>' . sprintf(
			/* translators: %s: comma-separated list of element names */
			esc_html__( 'It also ships its own version of: %s. Those were written for an older framework, so newer per-element settings — including “Hide on Desktop / Tablet / Mobile” — save correctly but never reach the page.', 'fw' ),
			'<strong>' . esc_html( implode( ', ', $stale ) ) . '</strong>'
		) . '</p>';
	}

	echo '<p><a href="' . esc_url( $url ) . '" class="button button-primary">' . esc_html__( 'Review theme overrides', 'fw' ) . '</a></p>';
	echo '</div>';
}

add_action( 'admin_notices', 'fw_upw_theme_overrides_notice' );

/** Remember a dismissal per theme fingerprint. */
function fw_upw_dismiss_overrides_notice() {
	check_ajax_referer( 'fw_upw_dismiss_overrides', 'nonce' );

	$fingerprint = isset( $_POST['fingerprint'] )
		? preg_replace( '/[^a-f0-9]/', '', (string) $_POST['fingerprint'] )
		: '';

	if ( $fingerprint && get_current_user_id() ) {
		update_user_meta( get_current_user_id(), 'fw_upw_overrides_dismissed_' . $fingerprint, 1 );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_fw_upw_dismiss_overrides', 'fw_upw_dismiss_overrides_notice' );

/** Inline script that records the dismissal (no separate asset for ~15 lines). */
function fw_upw_overrides_notice_script() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<script>
	( function () {
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.notice-dismiss' ) : null;
			if ( ! btn ) { return; }
			var notice = btn.closest( '.fw-upw-overrides-notice' );
			if ( ! notice ) { return; }
			var body = new FormData();
			body.append( 'action', 'fw_upw_dismiss_overrides' );
			body.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'fw_upw_dismiss_overrides' ) ); ?>' );
			body.append( 'fingerprint', notice.getAttribute( 'data-fingerprint' ) || '' );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } );
		} );
	} )();
	</script>
	<?php
}

add_action( 'admin_print_footer_scripts', 'fw_upw_overrides_notice_script' );
