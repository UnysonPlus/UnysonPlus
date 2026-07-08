<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Bundled inline-SVG icon libraries ("SVG packs") — the multi-pack engine behind
 * the icon picker's SVG tab. Each pack ships two compact JSON files in data/:
 *   <slug>-icons.json   = { "<name>": "<inner svg markup>" }
 *   <slug>-search.json  = { "<name>": "<space-joined keywords>" }
 * and a registry entry giving its title + the shared outer <svg …> attributes
 * (only the inner paths are stored, so the files stay small).
 *
 * A picked SVG stores { type:'svg', svg-source:'library', svg-id:'<pack>/<name>',
 * markup:'<svg…>' }; the frontend resolves the id via fw_icon_svg_pack_markup()
 * (a fallback — the picker also stores the resolved markup so a page needn't load
 * any bundle). All markup uses currentColor, so icons recolour with the element.
 *
 * Add a pack by dropping its two JSON files in data/ and registering it on the
 * `fw_icon_svg_packs` filter (or in fw_icon_svg_pack_registry() below).
 */

if ( ! function_exists( 'fw_icon_svg_pack_registry' ) ) :
	/** id => { title, slug, svg_open }. Filterable so more packs can be added. */
	function fw_icon_svg_pack_registry() {
		$stroke = 'width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
			. ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

		return apply_filters( 'fw_icon_svg_packs', array(
			'lucide' => array(
				'title'    => 'Lucide',
				'slug'     => 'lucide',
				'svg_open' => '<svg xmlns="http://www.w3.org/2000/svg" ' . $stroke . ' class="lucide">',
			),
			'tabler' => array(
				'title'    => 'Tabler',
				'slug'     => 'tabler',
				'svg_open' => '<svg xmlns="http://www.w3.org/2000/svg" ' . $stroke . ' class="tabler-icon">',
			),
		) );
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_install_dir' ) ) :
	/**
	 * Absolute path of the on-demand icon-pack install root, where the installer
	 * writes packs fetched from the remote catalog (one sub-dir per pack:
	 * unysonplus-icon-packs/<slug>/<slug>-icons.json + -search.json). Lives under
	 * wp-content/uploads so it survives plugin updates and never bloats the plugin.
	 */
	function fw_icon_svg_pack_install_dir() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'unysonplus-icon-packs';
		return apply_filters( 'fw_icon_svg_pack_install_dir', $dir );
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_file' ) ) :
	/**
	 * Resolve a pack data file to a readable path, checking the BUNDLED data/ dir
	 * first, then the INSTALLED uploads dir. This is what lets a pack be shipped in
	 * the plugin OR downloaded on demand and still resolve transparently.
	 *
	 * @param string $slug e.g. 'lucide'
	 * @param string $kind 'icons' | 'search'
	 * @return string Readable absolute path, or '' if the pack isn't present anywhere.
	 */
	function fw_icon_svg_pack_file( $slug, $kind ) {
		$name = $slug . '-' . $kind . '.json';

		$bundled = dirname( __FILE__ ) . '/../data/' . $name;
		if ( is_readable( $bundled ) ) { return $bundled; }

		$installed = trailingslashit( fw_icon_svg_pack_install_dir() ) . $slug . '/' . $name;
		if ( is_readable( $installed ) ) { return $installed; }

		return '';
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_data' ) ) :
	/** name => inner-markup map for a pack (cached; empty if the file is absent). */
	function fw_icon_svg_pack_data( $pack ) {
		static $cache = array();
		if ( isset( $cache[ $pack ] ) ) { return $cache[ $pack ]; }

		$reg  = fw_icon_svg_pack_registry();
		$slug = isset( $reg[ $pack ]['slug'] ) ? $reg[ $pack ]['slug'] : $pack;
		$file = fw_icon_svg_pack_file( $slug, 'icons' );

		$data = array();
		if ( $file !== '' ) {
			$json = json_decode( file_get_contents( $file ), true );
			if ( is_array( $json ) ) { $data = $json; }
		}
		$cache[ $pack ] = $data;
		return $data;
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_search_data' ) ) :
	function fw_icon_svg_pack_search_data( $pack ) {
		static $cache = array();
		if ( isset( $cache[ $pack ] ) ) { return $cache[ $pack ]; }

		$reg  = fw_icon_svg_pack_registry();
		$slug = isset( $reg[ $pack ]['slug'] ) ? $reg[ $pack ]['slug'] : $pack;
		$file = fw_icon_svg_pack_file( $slug, 'search' );

		$data = array();
		if ( $file !== '' ) {
			$json = json_decode( file_get_contents( $file ), true );
			if ( is_array( $json ) ) { $data = $json; }
		}
		$cache[ $pack ] = $data;
		return $data;
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_available' ) ) :
	/**
	 * True when a pack has bundled data (used to gate the settings + picker).
	 * Checks the data file's presence + non-emptiness on disk rather than
	 * json-decoding it — availability is queried on every builder page load, so
	 * decoding a multi-megabyte set (e.g. Tabler ~1.2 MB) here would be wasteful.
	 */
	function fw_icon_svg_pack_available( $pack ) {
		static $cache = array();
		if ( isset( $cache[ $pack ] ) ) { return $cache[ $pack ]; }

		$reg  = fw_icon_svg_pack_registry();
		$slug = isset( $reg[ $pack ]['slug'] ) ? $reg[ $pack ]['slug'] : $pack;
		$file = fw_icon_svg_pack_file( $slug, 'icons' );

		// A populated JSON object is at least a few bytes ("{}" = empty).
		$cache[ $pack ] = ( $file !== '' && filesize( $file ) > 4 );
		return $cache[ $pack ];
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_markup' ) ) :
	/**
	 * Resolve a 'pack/name' id (e.g. 'lucide/star', 'tabler/home') to full inline
	 * <svg> markup with currentColor. Returns '' for an unknown pack/name. The
	 * markup still passes through sc_icon_sanitize_svg() at render.
	 */
	function fw_icon_svg_pack_markup( $id ) {
		$id    = ltrim( (string) $id, '/' );
		$slash = strpos( $id, '/' );
		if ( $slash === false ) { return ''; }

		$pack = substr( $id, 0, $slash );
		$name = substr( $id, $slash + 1 );

		$reg = fw_icon_svg_pack_registry();
		if ( ! isset( $reg[ $pack ] ) ) { return ''; }

		$icons = fw_icon_svg_pack_data( $pack );
		if ( empty( $icons[ $name ] ) ) { return ''; }

		$open = isset( $reg[ $pack ]['svg_open'] )
			? $reg[ $pack ]['svg_open']
			: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';

		return $open . $icons[ $name ] . '</svg>';
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_all' ) ) :
	/** All icon names in a pack (sorted). */
	function fw_icon_svg_pack_all( $pack, $limit = 0 ) {
		$names = array_keys( fw_icon_svg_pack_data( $pack ) );
		sort( $names );
		return ( $limit > 0 ) ? array_slice( $names, 0, $limit ) : $names;
	}
endif;

if ( ! function_exists( 'fw_icon_svg_pack_search' ) ) :
	/**
	 * Names in a pack matching a query (name + keywords). Empty query → first slice.
	 * @param string $pack
	 * @param string $query
	 * @param int    $limit  Max results (0 = no limit).
	 */
	function fw_icon_svg_pack_search( $pack, $query, $limit = 120 ) {
		$query = trim( strtolower( (string) $query ) );
		if ( $query === '' ) {
			return fw_icon_svg_pack_all( $pack, $limit );
		}

		$search  = fw_icon_svg_pack_search_data( $pack );
		$matches = array();
		foreach ( $search as $name => $keywords ) {
			if ( strpos( $name, $query ) !== false || strpos( (string) $keywords, $query ) !== false ) {
				$rank      = ( $name === $query ) ? 0 : ( strpos( $name, $query ) === 0 ? 1 : 2 );
				$matches[] = array( $name, $rank );
			}
		}
		usort( $matches, function ( $a, $b ) {
			return ( $a[1] === $b[1] ) ? strcmp( $a[0], $b[0] ) : ( $a[1] - $b[1] );
		} );
		$names = array_map( function ( $m ) { return $m[0]; }, $matches );
		return ( $limit > 0 ) ? array_slice( $names, 0, $limit ) : $names;
	}
endif;

/* -----------------------------------------------------------------------------
 * Back-compat: the original Lucide-only helpers now delegate to the generic
 * engine, so existing callers (and stored 'lucide/…' values) keep working.
 * -------------------------------------------------------------------------- */
if ( ! function_exists( 'fw_icon_lucide_markup' ) ) :
	function fw_icon_lucide_markup( $name ) {
		$name = ltrim( (string) $name, '/' );
		if ( strpos( $name, 'lucide/' ) !== 0 ) { $name = 'lucide/' . $name; }
		return fw_icon_svg_pack_markup( $name );
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_search' ) ) :
	function fw_icon_lucide_search( $query, $limit = 120 ) {
		return fw_icon_svg_pack_search( 'lucide', $query, $limit );
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_all' ) ) :
	function fw_icon_lucide_all() {
		return fw_icon_svg_pack_all( 'lucide' );
	}
endif;
