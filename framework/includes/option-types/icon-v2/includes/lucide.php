<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Lucide icon library (https://lucide.dev — ISC licensed, see data/LUCIDE-LICENSE).
 *
 * The `icon` / `icon-v2` option type's SVG kind can pick a Lucide glyph; the
 * stored value is { type:'svg', svg-source:'library', svg-id:'lucide/<name>',
 * markup:'<svg…>' }. The picker (admin) uses fw_icon_lucide_all() /
 * fw_icon_lucide_search() to build its grid; the frontend resolves a name to
 * inline <svg> markup via fw_icon_lucide_markup() (used as a fallback — the
 * picker also stores the resolved markup in the value so a page needn't load
 * this bundle at all).
 *
 * Bundle format: data/lucide-icons.json is { "<name>": "<inner svg markup>" }.
 * Every Lucide icon shares the same outer <svg> attributes, so only the inner
 * paths are stored; fw_icon_lucide_markup() wraps them in the canonical tag.
 */

if ( ! function_exists( 'fw_icon_lucide_data' ) ) :
	/** Load + cache the name → inner-markup map. */
	function fw_icon_lucide_data() {
		static $data = null;
		if ( $data !== null ) { return $data; }

		$file = dirname( __FILE__ ) . '/../data/lucide-icons.json';
		$data = array();
		if ( is_readable( $file ) ) {
			$json = json_decode( file_get_contents( $file ), true );
			if ( is_array( $json ) ) { $data = $json; }
		}
		return $data;
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_search_data' ) ) :
	/** Load + cache the name → keyword-string map (for picker search). */
	function fw_icon_lucide_search_data() {
		static $data = null;
		if ( $data !== null ) { return $data; }

		$file = dirname( __FILE__ ) . '/../data/lucide-search.json';
		$data = array();
		if ( is_readable( $file ) ) {
			$json = json_decode( file_get_contents( $file ), true );
			if ( is_array( $json ) ) { $data = $json; }
		}
		return $data;
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_markup' ) ) :
	/**
	 * Resolve a Lucide icon name to complete inline <svg> markup (currentColor,
	 * so it inherits the surrounding text colour). Returns '' for an unknown
	 * name. The markup still passes through sc_icon_sanitize_svg() at render.
	 */
	function fw_icon_lucide_markup( $name ) {
		$name = ltrim( (string) $name, '/' );
		// Accept both 'lucide/star' and bare 'star'.
		if ( strpos( $name, 'lucide/' ) === 0 ) { $name = substr( $name, 7 ); }

		$icons = fw_icon_lucide_data();
		if ( empty( $icons[ $name ] ) ) { return ''; }

		return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"'
			. ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-'
			. esc_attr( $name ) . '">' . $icons[ $name ] . '</svg>';
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_all' ) ) :
	/** All Lucide icon names (sorted), for the picker grid. */
	function fw_icon_lucide_all() {
		$names = array_keys( fw_icon_lucide_data() );
		sort( $names );
		return $names;
	}
endif;

if ( ! function_exists( 'fw_icon_lucide_search' ) ) :
	/**
	 * Names matching a query against name + keywords. Empty query → all names.
	 * @param string $query
	 * @param int    $limit  Max results (0 = no limit).
	 */
	function fw_icon_lucide_search( $query, $limit = 120 ) {
		$query = trim( strtolower( (string) $query ) );
		if ( $query === '' ) {
			$names = fw_icon_lucide_all();
			return ( $limit > 0 ) ? array_slice( $names, 0, $limit ) : $names;
		}

		$search  = fw_icon_lucide_search_data();
		$matches = array();
		foreach ( $search as $name => $keywords ) {
			if ( strpos( $name, $query ) !== false || strpos( (string) $keywords, $query ) !== false ) {
				// Exact-name and prefix matches rank first.
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
