<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * On-demand SVG icon-pack installer (icon-v3).
 *
 * The plugin bundles only a couple of SVG packs (Lucide, Tabler) so it stays
 * small. Every OTHER pack lives in an external catalog and is downloaded on
 * demand into wp-content/uploads/unysonplus-icon-packs/<slug>/ — so a site only
 * carries the icon sets it actually uses, and the plugin never bloats as the
 * catalog grows.
 *
 * Layout of an installed pack (mirrors the bundled data/ shape, so the shared
 * engine in svg-packs.php resolves it transparently via fw_icon_svg_pack_file()):
 *   unysonplus-icon-packs/<slug>/<slug>-icons.json   { name => inner svg markup }
 *   unysonplus-icon-packs/<slug>/<slug>-search.json  { name => keywords }
 *   unysonplus-icon-packs/<slug>/meta.json           { title, slug, svg_open, count }
 *
 * The meta.json lets fw_icon_svg_pack_registry() register installed packs with
 * NO network call (registry is read on every builder load); the remote catalog
 * is fetched only for the installer UI (to list what's available to install).
 */

/* -----------------------------------------------------------------------------
 * Catalog (remote list of installable packs)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_catalog_url' ) ) :
	/**
	 * URL of the catalog.json describing installable packs. Filterable so the host
	 * can point at a mirror/CDN. Default: the raw GitHub content of the icon-packs repo.
	 */
	function fw_icon_pack_catalog_url() {
		return apply_filters(
			'fw_icon_pack_catalog_url',
			'https://raw.githubusercontent.com/UnysonPlus/UnysonPlus-Icon-Packs/master/catalog.json'
		);
	}
endif;

if ( ! function_exists( 'fw_icon_pack_catalog' ) ) :
	/**
	 * Fetch + decode the remote catalog, cached in a transient (12h) so the
	 * installer UI is snappy and we don't hammer the CDN. Returns the decoded
	 * catalog array, or an empty shape on failure.
	 *
	 * @param bool $force Bypass the transient (used by an explicit "refresh").
	 * @return array { version:int, base_url:string, packs:{ slug => {title,slug,svg_open,count} } }
	 */
	function fw_icon_pack_catalog( $force = false ) {
		$key = 'fw_icon_pack_catalog';

		if ( ! $force ) {
			$cached = get_transient( $key );
			if ( is_array( $cached ) ) { return $cached; }
		}

		$empty = array( 'version' => 0, 'base_url' => '', 'packs' => array() );

		$res = wp_remote_get( fw_icon_pack_catalog_url(), array( 'timeout' => 15 ) );
		if ( is_wp_error( $res ) || (int) wp_remote_retrieve_response_code( $res ) !== 200 ) {
			// Cache the miss briefly so a flaky network doesn't retry on every keystroke.
			set_transient( $key, $empty, 5 * MINUTE_IN_SECONDS );
			return $empty;
		}

		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) || empty( $json['packs'] ) || ! is_array( $json['packs'] ) ) {
			set_transient( $key, $empty, 5 * MINUTE_IN_SECONDS );
			return $empty;
		}

		$catalog = array(
			'version'  => isset( $json['version'] ) ? (int) $json['version'] : 1,
			'base_url' => isset( $json['base_url'] ) ? trailingslashit( (string) $json['base_url'] ) : '',
			'packs'    => array(),
		);

		foreach ( $json['packs'] as $slug => $pack ) {
			$slug = sanitize_key( $slug );
			if ( $slug === '' || ! is_array( $pack ) ) { continue; }

			// The card preview is remote SVG — sanitise it before it ever reaches the
			// admin DOM (defends against a tampered catalog / MITM).
			$preview = isset( $pack['preview'] ) && is_string( $pack['preview'] ) ? $pack['preview'] : '';
			if ( $preview !== '' && function_exists( 'sc_icon_sanitize_svg' ) ) {
				$preview = sc_icon_sanitize_svg( $preview );
			}

			$catalog['packs'][ $slug ] = array(
				'title'    => isset( $pack['title'] ) ? (string) $pack['title'] : ucfirst( $slug ),
				'slug'     => $slug,
				'svg_open' => isset( $pack['svg_open'] ) ? (string) $pack['svg_open'] : '',
				'count'    => isset( $pack['count'] ) ? (int) $pack['count'] : 0,
				'preview'  => $preview,
			);
		}

		set_transient( $key, $catalog, 12 * HOUR_IN_SECONDS );
		return $catalog;
	}
endif;

/* -----------------------------------------------------------------------------
 * Installed packs (local scan — no network)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_installed_slugs' ) ) :
	/** Slugs of packs installed under uploads (each a dir with a valid meta.json + icons.json). */
	function fw_icon_pack_installed_slugs() {
		$root = fw_icon_svg_pack_install_dir();
		if ( ! is_dir( $root ) ) { return array(); }

		$slugs = array();
		foreach ( (array) glob( trailingslashit( $root ) . '*', GLOB_ONLYDIR ) as $dir ) {
			$slug = basename( $dir );
			if ( is_readable( $dir . '/meta.json' )
				&& is_readable( $dir . '/' . $slug . '-icons.json' ) ) {
				$slugs[] = $slug;
			}
		}
		sort( $slugs );
		return $slugs;
	}
endif;

if ( ! function_exists( 'fw_icon_pack_installed_meta' ) ) :
	/** Decoded meta.json for one installed pack, or null. */
	function fw_icon_pack_installed_meta( $slug ) {
		$slug = sanitize_key( $slug );
		$file = trailingslashit( fw_icon_svg_pack_install_dir() ) . $slug . '/meta.json';
		if ( ! is_readable( $file ) ) { return null; }
		$meta = json_decode( file_get_contents( $file ), true );
		return is_array( $meta ) ? $meta : null;
	}
endif;

if ( ! function_exists( 'fw_icon_pack_counts' ) ) :
	/**
	 * Glyph counts for the ALWAYS-PRESENT libraries: the webfont packs and the
	 * bundled SVG sets (Lucide/Tabler). Both are static (they don't change between
	 * plugin versions), and computing them is costly — font counts parse each pack's
	 * CSS, bundled-SVG counts decode a multi-MB JSON — so the result is cached in a
	 * per-version transient and a per-request static. Installed packs are excluded
	 * (their count is read cheaply from meta.json in the payload).
	 *
	 * @return array pack_id => int
	 */
	function fw_icon_pack_counts() {
		static $cache = null;
		if ( is_array( $cache ) ) { return $cache; }

		$ver = ( function_exists( 'fw' ) && fw()->manifest ) ? fw()->manifest->get_version() : '0';
		$key = 'fw_icon_pack_counts_' . $ver;
		$t   = get_transient( $key );
		if ( is_array( $t ) ) { return ( $cache = $t ); }

		$counts = array();

		// Webfont packs — load each pack's icon list once, then count.
		if ( function_exists( 'fw' ) ) {
			$ot = fw()->backend->option_type( 'icon-v2' );
			if ( $ot && isset( $ot->packs_loader ) && method_exists( $ot->packs_loader, 'get_packs' ) ) {
				$ot->packs_loader->get_packs( true ); // populates icon_packs[*]['icons']
				if ( isset( $ot->packs_loader->icon_packs ) && is_array( $ot->packs_loader->icon_packs ) ) {
					foreach ( $ot->packs_loader->icon_packs as $id => $pack ) {
						$counts[ $id ] = ( isset( $pack['icons'] ) && is_array( $pack['icons'] ) ) ? count( $pack['icons'] ) : 0;
					}
				}
			}
		}

		// Bundled SVG libraries only (skip installed — payload uses their meta count,
		// so we never decode a large downloaded set here).
		if ( function_exists( 'fw_icon_svg_pack_registry' ) && function_exists( 'fw_icon_svg_pack_data' ) ) {
			$installed = fw_icon_pack_installed_slugs();
			foreach ( array_keys( fw_icon_svg_pack_registry() ) as $slug ) {
				if ( in_array( $slug, $installed, true ) ) { continue; }
				if ( fw_icon_svg_pack_available( $slug ) ) {
					$counts[ $slug ] = count( fw_icon_svg_pack_data( $slug ) );
				}
			}
		}

		set_transient( $key, $counts, WEEK_IN_SECONDS );
		return ( $cache = $counts );
	}
endif;

/**
 * Register every INSTALLED pack into the shared SVG registry, so the picker (and
 * markup resolver) treat downloaded packs exactly like bundled ones. Reads each
 * pack's local meta.json — no network call, safe on every builder load.
 */
add_filter( 'fw_icon_svg_packs', function ( $packs ) {
	foreach ( fw_icon_pack_installed_slugs() as $slug ) {
		if ( isset( $packs[ $slug ] ) ) { continue; } // bundled/already-registered wins
		$meta = fw_icon_pack_installed_meta( $slug );
		if ( ! $meta || empty( $meta['svg_open'] ) ) { continue; }
		$packs[ $slug ] = array(
			'title'    => isset( $meta['title'] ) ? (string) $meta['title'] : ucfirst( $slug ),
			'slug'     => $slug,
			'svg_open' => (string) $meta['svg_open'],
		);
	}
	return $packs;
}, 20 );

/* -----------------------------------------------------------------------------
 * Install / uninstall
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_install' ) ) :
	/**
	 * Download one pack from the catalog into the uploads install dir. Fetches the
	 * two data JSONs (icons + search) from the catalog base_url and writes them plus
	 * a meta.json. Atomic-ish: writes into a temp dir, then renames into place, so a
	 * half-downloaded pack never registers as installed.
	 *
	 * @param string $slug
	 * @return true|WP_Error
	 */
	function fw_icon_pack_install( $slug ) {
		$slug = sanitize_key( $slug );
		if ( $slug === '' ) { return new WP_Error( 'bad_slug', __( 'Invalid pack.', 'fw' ) ); }

		$catalog = fw_icon_pack_catalog();
		if ( empty( $catalog['packs'][ $slug ] ) ) {
			return new WP_Error( 'not_in_catalog', __( 'That pack is not in the catalog.', 'fw' ) );
		}
		if ( $catalog['base_url'] === '' ) {
			return new WP_Error( 'no_base_url', __( 'Catalog is missing a base URL.', 'fw' ) );
		}

		$pack = $catalog['packs'][ $slug ];
		$base = $catalog['base_url'] . 'packs/' . $slug . '/';

		// Pull both data files first (in memory) before touching disk.
		$icons  = fw_icon_pack__fetch_json( $base . $slug . '-icons.json' );
		if ( is_wp_error( $icons ) ) { return $icons; }
		$search = fw_icon_pack__fetch_json( $base . $slug . '-search.json' );
		if ( is_wp_error( $search ) ) { $search = array(); } // search is optional

		if ( empty( $icons ) || ! is_array( $icons ) ) {
			return new WP_Error( 'empty_pack', __( 'The pack had no icons.', 'fw' ) );
		}

		// Ensure the install root exists.
		$root = fw_icon_svg_pack_install_dir();
		if ( ! wp_mkdir_p( $root ) ) {
			return new WP_Error( 'mkdir_failed', __( 'Could not create the icon-pack folder.', 'fw' ) );
		}

		$dest = trailingslashit( $root ) . $slug;
		$tmp  = $dest . '.tmp-' . wp_generate_password( 6, false );
		if ( ! wp_mkdir_p( $tmp ) ) {
			return new WP_Error( 'mkdir_failed', __( 'Could not create a temp folder.', 'fw' ) );
		}

		$meta = array(
			'title'     => $pack['title'],
			'slug'      => $slug,
			'svg_open'  => $pack['svg_open'],
			'count'     => count( $icons ),
			'installed' => gmdate( 'c' ),
		);

		$ok =
			   ( false !== file_put_contents( $tmp . '/' . $slug . '-icons.json',  wp_json_encode( $icons ) ) )
			&& ( false !== file_put_contents( $tmp . '/' . $slug . '-search.json', wp_json_encode( $search ) ) )
			&& ( false !== file_put_contents( $tmp . '/meta.json',                 wp_json_encode( $meta ) ) );

		if ( ! $ok ) {
			fw_icon_pack__rrmdir( $tmp );
			return new WP_Error( 'write_failed', __( 'Could not write the pack files.', 'fw' ) );
		}

		// Swap into place.
		if ( is_dir( $dest ) ) { fw_icon_pack__rrmdir( $dest ); }
		if ( ! @rename( $tmp, $dest ) ) {
			fw_icon_pack__rrmdir( $tmp );
			return new WP_Error( 'install_failed', __( 'Could not finalize the install.', 'fw' ) );
		}

		return true;
	}
endif;

if ( ! function_exists( 'fw_icon_pack_uninstall' ) ) :
	/**
	 * Remove an installed pack. Bundled packs (lucide/tabler) can't be uninstalled —
	 * they have no dir under uploads, so this is a no-op error for them.
	 *
	 * @param string $slug
	 * @return true|WP_Error
	 */
	function fw_icon_pack_uninstall( $slug ) {
		$slug = sanitize_key( $slug );
		$dir  = trailingslashit( fw_icon_svg_pack_install_dir() ) . $slug;
		if ( $slug === '' || ! is_dir( $dir ) ) {
			return new WP_Error( 'not_installed', __( 'That pack is not installed.', 'fw' ) );
		}
		fw_icon_pack__rrmdir( $dir );
		return true;
	}
endif;

/* -----------------------------------------------------------------------------
 * Custom pack upload (user-supplied JSON)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_install_from_json' ) ) :
	/**
	 * Install a user-uploaded icon pack from a JSON string. Accepts either a flat
	 * map { name => markup } or a self-describing { title, svg_open, icons:{…} }.
	 * Each icon's markup may be a full <svg>…</svg> or just the inner paths; both are
	 * sanitised (wp_kses SVG allowlist — strips <script>, on* handlers, etc.),
	 * reduced to inner markup, and stripped of hardcoded colours so they inherit
	 * currentColor. Writes the same three-file pack shape as a catalog install,
	 * tagged origin:"custom".
	 *
	 * @param string $title    Pack name (falls back to the JSON's own title).
	 * @param string $json_raw Raw uploaded file contents.
	 * @return array|WP_Error  { slug, count } on success.
	 */
	function fw_icon_pack_install_from_json( $title, $json_raw ) {
		$title   = trim( wp_strip_all_tags( (string) $title ) );
		$decoded = json_decode( (string) $json_raw, true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'bad_json', __( 'That file was not valid JSON.', 'fw' ) );
		}

		// Self-describing bundle vs flat name => markup map.
		$provided_open = '';
		if ( isset( $decoded['icons'] ) && is_array( $decoded['icons'] ) ) {
			$map = $decoded['icons'];
			if ( $title === '' && ! empty( $decoded['title'] ) ) { $title = trim( wp_strip_all_tags( (string) $decoded['title'] ) ); }
			if ( ! empty( $decoded['svg_open'] ) ) { $provided_open = (string) $decoded['svg_open']; }
		} else {
			$map = $decoded;
		}

		if ( $title === '' ) { return new WP_Error( 'no_title', __( 'Please give the pack a name.', 'fw' ) ); }
		if ( empty( $map ) )  { return new WP_Error( 'empty', __( 'The file had no icons.', 'fw' ) ); }

		$icons     = array();
		$search    = array();
		$pack_open = '';

		foreach ( $map as $name => $markup ) {
			$name = sanitize_key( is_string( $name ) ? $name : '' );
			if ( $name === '' || ! is_string( $markup ) || $markup === '' ) { continue; }

			// Wrap inner-only markup so the sanitiser (which requires an <svg>) accepts it.
			$full = ( stripos( $markup, '<svg' ) !== false )
				? $markup
				: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">' . $markup . '</svg>';

			// Drop <script>/<style>/<foreignObject> blocks INCLUDING their content
			// before the allowlist sanitiser (which would strip the tags but leave
			// the inner text behind as stray nodes).
			$full = preg_replace( '#<(script|style|foreignObject)\b[^>]*>.*?</\1>#is', '', $full );

			$clean = function_exists( 'sc_icon_sanitize_svg' ) ? sc_icon_sanitize_svg( $full ) : $full;
			if ( $clean === '' || stripos( $clean, '<svg' ) === false ) { continue; }

			if ( $pack_open === '' && preg_match( '/<svg[^>]*>/i', $clean, $m ) ) { $pack_open = $m[0]; }

			$inner = preg_replace( '/^.*?<svg[^>]*>/is', '', $clean );
			$inner = preg_replace( '#</svg>\s*$#i', '', $inner );
			$inner = trim( preg_replace( '/\s+/', ' ', $inner ) );
			$inner = preg_replace( '/\s(fill|stroke)="(?!none")(#[0-9a-fA-F]{3,8}|rgb[^"]*|currentColor|[a-zA-Z]+)"/', '', $inner );
			if ( $inner === '' ) { continue; }

			$icons[ $name ]  = $inner;
			$search[ $name ] = fw_icon_pack__keywords( $name );
		}

		if ( empty( $icons ) ) { return new WP_Error( 'no_icons', __( 'No valid SVG icons were found in the file.', 'fw' ) ); }

		$svg_open = $provided_open !== '' ? $provided_open : ( $pack_open !== '' ? $pack_open : '' );
		$svg_open = fw_icon_pack__normalize_open( $svg_open );

		$base_slug = sanitize_title( $title );
		if ( $base_slug === '' ) { $base_slug = 'custom-pack'; }
		$slug = fw_icon_pack__unique_slug( $base_slug );

		$meta = array(
			'title'     => $title,
			'slug'      => $slug,
			'svg_open'  => $svg_open,
			'count'     => count( $icons ),
			'origin'    => 'custom',
			'installed' => gmdate( 'c' ),
		);

		$r = fw_icon_pack__write_pack( $slug, $icons, $search, $meta );
		if ( is_wp_error( $r ) ) { return $r; }

		return array( 'slug' => $slug, 'count' => count( $icons ) );
	}
endif;

if ( ! function_exists( 'fw_icon_pack__keywords' ) ) :
	/** Search keywords for an icon name: the name plus its tokens, deduped. */
	function fw_icon_pack__keywords( $name ) {
		$tokens = array_filter( preg_split( '/[-_]/', $name ) );
		$set    = array();
		foreach ( array_merge( array( $name ), $tokens ) as $t ) {
			if ( ! in_array( $t, $set, true ) ) { $set[] = $t; }
		}
		return implode( ' ', $set );
	}
endif;

if ( ! function_exists( 'fw_icon_pack__normalize_open' ) ) :
	/** Clean an <svg …> opening tag: drop noise attrs, force colours to currentColor. */
	function fw_icon_pack__normalize_open( $open ) {
		$open = trim( (string) $open );
		if ( $open === '' || stripos( $open, '<svg' ) === false ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">';
		}
		if ( preg_match( '/<svg[^>]*>/i', $open, $m ) ) { $open = $m[0]; }

		// Strip presentational / identifying noise so it renders neutrally at any size.
		$open = preg_replace( '/\s(class|id|style|width|height|aria-[a-z-]+|data-[a-z-]+|role|focusable)="[^"]*"/i', '', $open );
		// Any hardcoded colour on the root becomes currentColor.
		$open = preg_replace( '/\s(fill|stroke)="(?!none")(#[0-9a-fA-F]{3,8}|rgb[^"]*|[a-zA-Z]+)"/', ' $1="currentColor"', $open );
		// wp_kses lowercases attribute names; restore the case-sensitive SVG ones so
		// the viewBox is honoured everywhere (not just via HTML parser adjustment).
		$open = str_ireplace(
			array( 'viewbox=', 'preserveaspectratio=' ),
			array( 'viewBox=', 'preserveAspectRatio=' ),
			$open
		);
		if ( stripos( $open, 'xmlns' ) === false ) {
			$open = preg_replace( '/^<svg/i', '<svg xmlns="http://www.w3.org/2000/svg"', $open );
		}
		return $open;
	}
endif;

if ( ! function_exists( 'fw_icon_pack__unique_slug' ) ) :
	/** A slug not already used by a bundled/installed/font/catalog pack. */
	function fw_icon_pack__unique_slug( $base ) {
		$taken = array();
		if ( function_exists( 'fw_icon_svg_pack_registry' ) ) { $taken += fw_icon_svg_pack_registry(); }
		foreach ( fw_icon_pack_installed_slugs() as $s ) { $taken[ $s ] = true; }
		if ( function_exists( 'unysonplus_font_icon_pack_ids' ) ) {
			foreach ( unysonplus_font_icon_pack_ids() as $s ) { $taken[ $s ] = true; }
		}
		$catalog = fw_icon_pack_catalog();
		if ( ! empty( $catalog['packs'] ) ) { $taken += $catalog['packs']; }

		$slug = $base;
		$i    = 2;
		while ( isset( $taken[ $slug ] ) ) { $slug = $base . '-' . $i; $i++; }
		return $slug;
	}
endif;

if ( ! function_exists( 'fw_icon_pack__write_pack' ) ) :
	/** Atomically write a pack's three JSON files into the install dir. */
	function fw_icon_pack__write_pack( $slug, $icons, $search, $meta ) {
		$root = fw_icon_svg_pack_install_dir();
		if ( ! wp_mkdir_p( $root ) ) { return new WP_Error( 'mkdir_failed', __( 'Could not create the icon-pack folder.', 'fw' ) ); }

		$dest = trailingslashit( $root ) . $slug;
		$tmp  = $dest . '.tmp-' . wp_generate_password( 6, false );
		if ( ! wp_mkdir_p( $tmp ) ) { return new WP_Error( 'mkdir_failed', __( 'Could not create a temp folder.', 'fw' ) ); }

		$ok =
			   ( false !== file_put_contents( $tmp . '/' . $slug . '-icons.json',  wp_json_encode( $icons ) ) )
			&& ( false !== file_put_contents( $tmp . '/' . $slug . '-search.json', wp_json_encode( $search ) ) )
			&& ( false !== file_put_contents( $tmp . '/meta.json',                 wp_json_encode( $meta ) ) );

		if ( ! $ok ) { fw_icon_pack__rrmdir( $tmp ); return new WP_Error( 'write_failed', __( 'Could not write the pack files.', 'fw' ) ); }

		if ( is_dir( $dest ) ) { fw_icon_pack__rrmdir( $dest ); }
		if ( ! @rename( $tmp, $dest ) ) { fw_icon_pack__rrmdir( $tmp ); return new WP_Error( 'install_failed', __( 'Could not finalize the pack.', 'fw' ) ); }

		return true;
	}
endif;

/* -----------------------------------------------------------------------------
 * Internal helpers
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack__fetch_json' ) ) :
	/** GET a URL and json_decode it. Returns array|WP_Error. */
	function fw_icon_pack__fetch_json( $url ) {
		$res = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $res ) ) { return $res; }
		if ( (int) wp_remote_retrieve_response_code( $res ) !== 200 ) {
			return new WP_Error( 'http_' . wp_remote_retrieve_response_code( $res ),
				sprintf( __( 'Download failed (%s).', 'fw' ), wp_remote_retrieve_response_code( $res ) ) );
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) ) { return new WP_Error( 'bad_json', __( 'Downloaded file was not valid JSON.', 'fw' ) ); }
		return $json;
	}
endif;

if ( ! function_exists( 'fw_icon_pack__rrmdir' ) ) :
	/** Recursively delete a directory (used for temp + uninstall). */
	function fw_icon_pack__rrmdir( $dir ) {
		if ( ! is_dir( $dir ) ) { return; }
		foreach ( (array) glob( trailingslashit( $dir ) . '*' ) as $item ) {
			is_dir( $item ) ? fw_icon_pack__rrmdir( $item ) : @unlink( $item );
		}
		@rmdir( $dir );
	}
endif;

/* -----------------------------------------------------------------------------
 * AJAX (admin only, capability + nonce gated)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_ajax_nonce' ) ) :
	function fw_icon_pack_ajax_nonce() { return 'fw_icon_pack_manage'; }
endif;

/* -----------------------------------------------------------------------------
 * Enabled state (which libraries the picker offers)
 *
 * The unified installer panel owns the per-pack on/off toggle (folded in from the
 * old "Enabled libraries" checklist). It is stored in its OWN option — NOT in the
 * Theme Settings blob — because the settings form replaces that blob with only its
 * own fields on save, which would wipe a value that no longer has a form field.
 * Keyed by theme id so it stays per-theme, and seeded once from the legacy
 * `icon_packs_enabled` setting so existing curations carry over.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_enabled_option_key' ) ) :
	function fw_icon_pack_enabled_option_key() {
		$theme_id = ( function_exists( 'fw' ) && fw()->theme && fw()->theme->manifest )
			? fw()->theme->manifest->get_id() : 'theme';
		return 'upw_icon_packs_enabled_' . $theme_id;
	}
endif;

if ( ! function_exists( 'fw_icon_pack_enabled_map' ) ) :
	/** { pack_id => bool } saved map. Empty array = never curated (all enabled). */
	function fw_icon_pack_enabled_map() {
		$val = get_option( fw_icon_pack_enabled_option_key(), null );
		if ( is_array( $val ) ) { return $val; }

		// One-time migration from the legacy theme-settings checklist.
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$legacy = fw_get_db_settings_option( 'icon_packs_enabled' );
			if ( is_array( $legacy ) && $legacy ) {
				update_option( fw_icon_pack_enabled_option_key(), $legacy );
				return $legacy;
			}
		}
		return array();
	}
endif;

if ( ! function_exists( 'fw_icon_pack_set_enabled' ) ) :
	/** Flip one pack on/off and persist. Seeds an all-enabled map on first write. */
	function fw_icon_pack_set_enabled( $slug, $on ) {
		$slug = sanitize_key( $slug );
		if ( $slug === '' ) { return; }

		$map = fw_icon_pack_enabled_map();
		if ( empty( $map ) && function_exists( 'unysonplus_all_icon_pack_ids' ) ) {
			foreach ( unysonplus_all_icon_pack_ids() as $id ) { $map[ $id ] = true; }
		}
		$map[ $slug ] = (bool) $on;
		update_option( fw_icon_pack_enabled_option_key(), $map );
	}
endif;

add_action( 'wp_ajax_fw_icon_pack_manage', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'fw' ) ), 403 );
	}
	check_ajax_referer( fw_icon_pack_ajax_nonce(), 'nonce' );

	$action = isset( $_POST['pack_action'] ) ? sanitize_key( $_POST['pack_action'] ) : '';
	$slug   = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';

	if ( $action === 'install' ) {
		$r = fw_icon_pack_install( $slug );
		// A freshly installed pack is enabled by default (absent from the map).
	} elseif ( $action === 'uninstall' ) {
		$r = fw_icon_pack_uninstall( $slug );
	} elseif ( $action === 'enable' || $action === 'disable' ) {
		fw_icon_pack_set_enabled( $slug, $action === 'enable' );
		$r = true;
	} elseif ( $action === 'upload' ) {
		if ( empty( $_FILES['pack_file'] ) || ! is_uploaded_file( $_FILES['pack_file']['tmp_name'] ) ) {
			$r = new WP_Error( 'no_file', __( 'No file was uploaded.', 'fw' ) );
		} elseif ( (int) $_FILES['pack_file']['size'] <= 0 || (int) $_FILES['pack_file']['size'] > 8 * MB_IN_BYTES ) {
			$r = new WP_Error( 'size', __( 'File is empty or larger than 8 MB.', 'fw' ) );
		} else {
			$raw   = file_get_contents( $_FILES['pack_file']['tmp_name'] );
			$title = isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '';
			$r     = fw_icon_pack_install_from_json( $title, $raw );
		}
	} elseif ( $action === 'refresh' ) {
		fw_icon_pack_catalog( true );
		$r = true;
	} else {
		$r = new WP_Error( 'bad_action', __( 'Unknown action.', 'fw' ) );
	}

	if ( is_wp_error( $r ) ) {
		wp_send_json_error( array( 'message' => $r->get_error_message() ) );
	}

	wp_send_json_success( array(
		'installed' => array_values( fw_icon_pack_installed_slugs() ),
		'enabled'   => function_exists( 'unysonplus_enabled_icon_packs' )
			? array_values( unysonplus_enabled_icon_packs() ) : array(),
		// Full, fresh pack list so the JS can render packs it didn't know about at
		// page load (e.g. a just-uploaded custom pack).
		'packs'     => fw_icon_pack_installer_packs(),
	) );
} );

/* -----------------------------------------------------------------------------
 * Installer UI assets (Theme Settings -> Icons)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'fw_icon_pack_installer_packs' ) ) :
	/**
	 * Data the unified installer panel needs. One flat `packs` list, each entry:
	 *   { slug, title, type:'font'|'svg', state:'bundled'|'installed'|'available',
	 *     enabled:bool, count:int }
	 * covering the bundled webfonts (toggle only), bundled + installed SVG libraries
	 * (toggle + Remove for installed), and catalog packs available to install.
	 * Bundled icon counts are omitted (counting them would decode the multi-MB
	 * bundles on a settings-page load); installed/available counts are cheap.
	 */
	function fw_icon_pack_installer_packs() {
		$catalog   = fw_icon_pack_catalog();
		$installed = fw_icon_pack_installed_slugs();
		$reg       = function_exists( 'fw_icon_svg_pack_registry' ) ? fw_icon_svg_pack_registry() : array();
		$enabled   = function_exists( 'unysonplus_enabled_icon_packs' ) ? unysonplus_enabled_icon_packs() : array();
		$font_ids  = function_exists( 'unysonplus_font_icon_pack_ids' ) ? unysonplus_font_icon_pack_ids() : array();
		$counts    = fw_icon_pack_counts(); // webfont + bundled-SVG glyph counts (cached)

		// Font-pack titles (webfonts; always present, toggle-only).
		$font_titles = array();
		if ( function_exists( 'fw' ) ) {
			$ot = fw()->backend->option_type( 'icon-v2' );
			if ( $ot && isset( $ot->packs_loader ) ) {
				foreach ( $ot->packs_loader->get_packs_unfiltered() as $id => $pack ) {
					$font_titles[ $id ] = isset( $pack['title'] ) ? $pack['title'] : ucfirst( $id );
				}
			}
		}

		$packs   = array();
		$present = array();

		foreach ( $font_ids as $id ) {
			$packs[] = array(
				'slug'    => $id,
				'title'   => isset( $font_titles[ $id ] ) ? $font_titles[ $id ] : ucfirst( $id ),
				'type'    => 'font',
				'state'   => 'bundled',
				'enabled' => in_array( $id, $enabled, true ),
				'count'   => isset( $counts[ $id ] ) ? (int) $counts[ $id ] : 0,
			);
			$present[ $id ] = true;
		}

		foreach ( $reg as $slug => $pack ) {
			$is_installed = in_array( $slug, $installed, true );
			$count        = isset( $counts[ $slug ] ) ? (int) $counts[ $slug ] : 0; // bundled Lucide/Tabler
			$origin       = '';
			if ( $is_installed ) {
				$meta   = fw_icon_pack_installed_meta( $slug );
				$count  = ( $meta && isset( $meta['count'] ) ) ? (int) $meta['count'] : 0;
				$origin = ( $meta && isset( $meta['origin'] ) ) ? (string) $meta['origin'] : '';
			}
			$packs[] = array(
				'slug'    => $slug,
				'title'   => isset( $pack['title'] ) ? $pack['title'] : ucfirst( $slug ),
				'type'    => 'svg',
				'state'   => $is_installed ? 'installed' : 'bundled',
				'origin'  => $origin,
				'enabled' => in_array( $slug, $enabled, true ),
				'count'   => $count,
			);
			$present[ $slug ] = true;
		}

		foreach ( ( isset( $catalog['packs'] ) ? $catalog['packs'] : array() ) as $slug => $pack ) {
			if ( isset( $present[ $slug ] ) ) { continue; }
			$packs[] = array(
				'slug'    => $slug,
				'title'   => $pack['title'],
				'type'    => 'svg',
				'state'   => 'available',
				'enabled' => false,
				'count'   => (int) $pack['count'],
				'preview' => isset( $pack['preview'] ) ? (string) $pack['preview'] : '',
			);
		}

		return $packs;
	}
endif;

if ( ! function_exists( 'fw_icon_pack_installer_payload' ) ) :
	function fw_icon_pack_installer_payload() {
		$catalog = fw_icon_pack_catalog();
		return array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( fw_icon_pack_ajax_nonce() ),
			'packs'     => fw_icon_pack_installer_packs(),
			'installed' => array_values( fw_icon_pack_installed_slugs() ),
			'enabled'   => function_exists( 'unysonplus_enabled_icon_packs' ) ? array_values( unysonplus_enabled_icon_packs() ) : array(),
			'catalogOk' => ! empty( $catalog['packs'] ),
			'i18n'      => array(
				'libraryIntro'       => __( 'Turn libraries on or off for the icon picker.', 'fw' ),
				'browseIntro'        => __( 'Install extra SVG icon sets on demand — downloaded packs live in your uploads folder, so the plugin stays lean.', 'fw' ),
				'uploadDone'         => __( 'Uploaded — added to your Library.', 'fw' ),
				'heading'            => __( 'Icon pack library', 'fw' ),
				'subheading'         => __( 'Turn libraries on or off for the icon picker, and install extra SVG sets on demand — downloaded packs live in your uploads folder, so the plugin stays lean.', 'fw' ),
				'yourLibraries'      => __( 'Your libraries', 'fw' ),
				'availableTitle'     => __( 'Available to install', 'fw' ),
				'bundled'            => __( 'Bundled', 'fw' ),
				'font'               => __( 'Font', 'fw' ),
				'fontIcons'          => __( 'font icons', 'fw' ),
				'svgIcons'           => __( 'SVG icons', 'fw' ),
				'on'                 => __( 'On', 'fw' ),
				'off'                => __( 'Off', 'fw' ),
				'install'            => __( 'Install', 'fw' ),
				'remove'             => __( 'Remove', 'fw' ),
				'refresh'            => __( 'Refresh', 'fw' ),
				'custom'             => __( 'Custom', 'fw' ),
				'installing'         => __( 'Installing…', 'fw' ),
				'removing'           => __( 'Removing…', 'fw' ),
				'allInstalled'       => __( 'Everything in the catalog is installed.', 'fw' ),
				'catalogUnavailable' => __( 'The icon-pack catalog is unreachable right now. Bundled packs still work; try Refresh later.', 'fw' ),
				'confirmRemove'      => __( 'Remove the “%s” icon pack? Icons already placed on your pages keep rendering.', 'fw' ),
				'toggleHint'         => __( 'Disabling a library only hides it when picking NEW icons — icons already on your pages keep rendering.', 'fw' ),
				'genericError'       => __( 'Something went wrong. Please try again.', 'fw' ),
				'uploadTitle'        => __( 'Upload your own pack', 'fw' ),
				'uploadDesc'         => __( 'Upload a .json file of SVG icons — either { "name": "<svg>…" } or { "title", "svg_open", "icons" }. Markup is sanitized and recolored to match your theme.', 'fw' ),
				'uploadNameLabel'    => __( 'Pack name', 'fw' ),
				'uploadNamePlaceholder' => __( 'My Icons', 'fw' ),
				'uploadFileLabel'    => __( 'JSON file', 'fw' ),
				'uploadButton'       => __( 'Upload pack', 'fw' ),
				'uploading'          => __( 'Uploading…', 'fw' ),
				'uploadNeedName'     => __( 'Please enter a pack name.', 'fw' ),
				'uploadNeedFile'     => __( 'Please choose a .json file.', 'fw' ),
			),
		);
	}
endif;

/**
 * Enqueue the installer JS/CSS on the Theme Settings page and hand it the payload.
 * Enqueued in the <head> group to match how the framework loads its own settings
 * option runtime (this page favours head over footer scripts).
 */
add_action( 'admin_enqueue_scripts', function ( $hook_suffix = '' ) {
	if ( ! function_exists( 'fw' ) ) { return; }

	$slug = method_exists( fw()->backend, '_get_settings_page_slug' )
		? fw()->backend->_get_settings_page_slug()
		: 'fw-settings';
	$is_settings = ( isset( $GLOBALS['plugin_page'] ) && $GLOBALS['plugin_page'] === $slug )
		|| ( is_string( $hook_suffix ) && $hook_suffix !== '' && strpos( $hook_suffix, $slug ) !== false );
	if ( ! $is_settings ) { return; }

	$base = fw_get_framework_directory_uri( '/includes/option-types/icon-v3/static/installer' );
	$ver  = fw()->manifest->get_version();

	wp_enqueue_style( 'upw-icon-pack-installer', $base . '/installer.css', array(), $ver );
	wp_enqueue_script( 'upw-icon-pack-installer', $base . '/installer.js', array( 'jquery' ), $ver, false );
	wp_localize_script( 'upw-icon-pack-installer', 'upwIconPacks', fw_icon_pack_installer_payload() );
}, 12 );
