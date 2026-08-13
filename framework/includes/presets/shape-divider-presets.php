<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Shape Divider presets — the reusable library of SVG edge shapes drawn at the TOP / BOTTOM
 * of a Section (the section shortcode's Styling → Top/Bottom Shape Divider pickers). Loaded by
 * ../presets.php. Stored THEME-SCOPED under the `shape_dividers` key (via the
 * Components → Shape Dividers addable-box, which authors each shape with the `svg-code` option
 * type — paste an <svg> or upload a .svg read client-side).
 *
 * Each preset entry:
 *   array(
 *     'id'           => string,  // unique, stable across renames (the picker's saved value)
 *     'divider_name' => string,  // label shown in the picker
 *     'svg_code'     => string,  // a full <svg viewBox="0 0 1200 120"><path d="…"/></svg>
 *   )
 *
 * The Section render does NOT echo the stored <svg> directly: it pulls the single <path> `d`
 * (and viewBox) via unysonplus_shape_divider_path() into its OWN trusted wrapper, then applies
 * the per-instance Color / Height / Flip. So the stored markup only needs a path; extra layers
 * are ignored for now (single-path shapes, matching the built-ins). The four built-ins keep the
 * slugs the old hardcoded list used (tilt/curve/wave/triangle) so existing sections that saved
 * `divider_top = {shape:'tilt',…}` keep resolving with no migration.
 */

if ( ! function_exists( 'unysonplus_default_shape_divider_presets' ) ) :
	function unysonplus_default_shape_divider_presets() {
		// The original four shapes, now expressed as full SVG so they are editable in the
		// library exactly like a user-added one. viewBox is the shape-divider convention
		// 0 0 1200 120 (1200-wide, 120-tall); the Section view scales it edge-to-edge.
		$d = function ( $id, $name, $path ) {
			return array(
				'id'           => $id,
				'divider_name' => $name,
				'svg_code'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"><path d="' . $path . '"/></svg>',
			);
		};

		return array(
			$d( 'tilt', __( 'Tilt', 'fw' ),
				'M1200 120L0 16.48 0 0 1200 0 1200 120z' ),
			$d( 'curve', __( 'Curve', 'fw' ),
				'M600 7.23C268.63 7.23 0 54.48 0 112.77V0h1200V112.77c0 -58.29-268.63 -105.54-600 -105.54z' ),
			$d( 'wave', __( 'Wave', 'fw' ),
				'M0 0v46.29c47.79 22.2 103.59 32.17 158 28 70.36-5.37 136.33-33.31 206.8-37.5 73.84-4.36 147.54 16.88 218.2 35.26 69.27 18 138.3 24.88 209.4 13.08 36.15-6 69.85-17.84 104.45-29.34C989.49 25 1113-14.29 1200 52.47V0z' ),
			$d( 'triangle', __( 'Triangle', 'fw' ),
				'M1200 0L0 0 598.97 114.72 1200 0z' ),
			// Multi-layer opacity shapes (stored as full SVG — the resolver keeps every layer and
			// recolours them via the section Colour). Already top-oriented like the others.
			array(
				'id'           => 'layered-wave',
				'divider_name' => __( 'Layered Wave', 'fw' ),
				'svg_code'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100"><g fill="#000"><path d="M1000 100C500 100 500 64 0 64V0h1000v100Z" opacity=".5"></path><path d="M1000 100C500 100 500 34 0 34V0h1000v100Z" opacity=".5"></path><path d="M1000 100C500 100 500 4 0 4V0h1000v100Z"></path></g></svg>',
			),
			array(
				'id'           => 'layered-slants',
				'divider_name' => __( 'Layered Slants', 'fw' ),
				'svg_code'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100"><g fill="#000"><path d="M0 100V0h1000v4L0 100z"></path><path d="M0 100V0h1000v24L0 100z" opacity=".5"></path><path d="M0 100V0h1000v44L0 100z" opacity=".4"></path><path d="M0 100V0h1000v64L0 100z" opacity=".4"></path><path d="M0 100V0h1000v84L0 100z" opacity=".2"></path></g></svg>',
			),
		);
	}
endif;

if ( ! function_exists( 'unysonplus_get_shape_divider_presets' ) ) :
	/** Saved shape dividers (theme-scoped) or the built-in four when nothing is saved yet. */
	function unysonplus_get_shape_divider_presets() {
		$saved = function_exists( 'unysonplus_preset_store_get' )
			? unysonplus_preset_store_get( 'shape_dividers', null )
			: null;
		if ( is_array( $saved ) ) {
			return $saved;
		}
		return unysonplus_default_shape_divider_presets();
	}
endif;

if ( ! function_exists( 'fw_upw_extract_svg_path' ) ) :
	/**
	 * Pull the geometry out of an <svg> string for path-only consumers (shape dividers).
	 * Returns array( 'path' => <concatenated d of every top-level <path>>, 'viewBox' => <vb> ).
	 * Multiple <path> elements are joined (a single fill covers them all — fine for a silhouette
	 * edge). Falls back to the shape-divider convention viewBox 0 0 1200 120 when none is declared.
	 * The input is admin-authored + already sanitised on save (svg-code's _get_value_from_input),
	 * so this only parses; it does not re-sanitise.
	 *
	 * @param string $svg
	 * @return array{path:string,viewBox:string}
	 */
	function fw_upw_extract_svg_path( $svg ) {
		$svg  = (string) $svg;
		$path = '';
		if ( preg_match_all( '/<path\b[^>]*\bd\s*=\s*(["\'])(.*?)\1/is', $svg, $pm ) ) {
			$path = trim( implode( ' ', array_map( 'trim', $pm[2] ) ) );
		}
		$viewbox = '0 0 1200 120';
		if ( preg_match( '/\bviewBox\s*=\s*(["\'])(.*?)\1/is', $svg, $vm ) ) {
			$vb = trim( $vm[2] );
			if ( $vb !== '' ) { $viewbox = $vb; }
		}
		return array( 'path' => $path, 'viewBox' => $viewbox );
	}
endif;

if ( ! function_exists( 'fw_upw_normalize_divider_svg' ) ) :
	/**
	 * Prepare a divider's stored SVG for rendering: pull the viewBox, keep the INNER content —
	 * every <g>/<path>, so multi-layer + opacity dividers (the shapedividers.com / svgbackgrounds
	 * style) survive intact — and rewrite every fill to `currentColor` so the per-section Colour
	 * drives every layer while each layer's opacity is preserved. Input is already sanitised on
	 * save (svg-code) or a trusted built-in.
	 *
	 * @param string $svg
	 * @return array{inner:string,viewBox:string}
	 */
	function fw_upw_normalize_divider_svg( $svg ) {
		$svg     = (string) $svg;
		$viewbox = '0 0 1200 120';
		if ( preg_match( '/\bviewBox\s*=\s*(["\'])(.*?)\1/is', $svg, $m ) ) {
			$vb = trim( $m[2] );
			if ( $vb !== '' ) { $viewbox = $vb; }
		}
		// Keep only what is inside <svg>…</svg>.
		$inner = preg_replace( '#^.*?<svg\b[^>]*>#is', '', $svg );
		$inner = preg_replace( '#</svg>\s*$#i', '', (string) $inner );
		// Every fill (attribute or CSS) -> currentColor, except an explicit fill="none". Handles
		// #000, the URL-encoded %23000000 some generators emit, named colours, etc.
		$inner = preg_replace( '/\bfill\s*=\s*(["\'])(?!\s*none\b)[^"\']*\1/i', 'fill="currentColor"', (string) $inner );
		$inner = preg_replace( '/fill\s*:\s*(?!\s*none\b)[^;"\'}]+/i', 'fill:currentColor', (string) $inner );
		return array( 'inner' => trim( (string) $inner ), 'viewBox' => $viewbox );
	}
endif;

if ( ! function_exists( 'unysonplus_shape_divider_markup' ) ) :
	/**
	 * Full render geometry for a divider preset id: array( 'inner' => <svg children>, 'viewBox' ).
	 * Empty inner for 'none'/unknown. The consumer wraps `inner` in its own <svg> and sets `color`.
	 *
	 * @param string $id
	 * @return array{inner:string,viewBox:string}
	 */
	function unysonplus_shape_divider_markup( $id ) {
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $id );
		if ( $id === '' || $id === 'none' ) {
			return array( 'inner' => '', 'viewBox' => '0 0 1200 120' );
		}
		foreach ( unysonplus_get_shape_divider_presets() as $p ) {
			if ( is_array( $p ) && isset( $p['id'] ) && (string) $p['id'] === $id ) {
				return fw_upw_normalize_divider_svg( isset( $p['svg_code'] ) ? $p['svg_code'] : '' );
			}
		}
		return array( 'inner' => '', 'viewBox' => '0 0 1200 120' );
	}
endif;

if ( ! function_exists( 'unysonplus_shape_divider_path' ) ) :
	/**
	 * Resolve a shape-divider preset id to its geometry for the Section render.
	 * Returns array( 'path' => d, 'viewBox' => vb ), or an empty path for 'none'/unknown ids.
	 *
	 * @param string $id
	 * @return array{path:string,viewBox:string}
	 */
	function unysonplus_shape_divider_path( $id ) {
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $id );
		if ( $id === '' || $id === 'none' ) {
			return array( 'path' => '', 'viewBox' => '0 0 1200 120' );
		}
		foreach ( unysonplus_get_shape_divider_presets() as $p ) {
			if ( is_array( $p ) && isset( $p['id'] ) && (string) $p['id'] === $id ) {
				return fw_upw_extract_svg_path( isset( $p['svg_code'] ) ? $p['svg_code'] : '' );
			}
		}
		return array( 'path' => '', 'viewBox' => '0 0 1200 120' );
	}
endif;

if ( ! function_exists( 'unysonplus_shape_divider_select_choices' ) ) :
	/**
	 * `[ 'none' => 'None', <id> => <name>, … ]` for a plain divider <select>. Stored value is the
	 * preset id (stable across renames). Feeds the Section Top/Bottom Shape Divider selects so
	 * user-added shapes appear automatically.
	 */
	function unysonplus_shape_divider_select_choices() {
		$out = array( 'none' => __( 'None', 'fw' ) );
		foreach ( unysonplus_get_shape_divider_presets() as $p ) {
			if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
			if ( $id === '' || $id === 'none' ) { continue; }
			$name = ( isset( $p['divider_name'] ) && $p['divider_name'] !== '' ) ? (string) $p['divider_name'] : $id;
			$out[ $id ] = $name;
		}
		return $out;
	}
endif;

if ( ! function_exists( 'unysonplus_shape_divider_thumb_datauri' ) ) :
	/**
	 * A `data:image/svg+xml` thumbnail of a divider shape — the silhouette filled over a light
	 * card, scaled edge-to-edge (preserveAspectRatio=none, the same way the Section draws it) so
	 * the picker tile reads as the actual edge. Self-contained (no external fetch in the <img>).
	 */
	function unysonplus_shape_divider_thumb_datauri( $inner, $viewbox = '0 0 1200 120', $placement = 'bottom', $w = 200, $h = 60 ) {
		$inner = (string) $inner;
		$vb    = ( $viewbox !== '' ) ? $viewbox : '0 0 1200 120';
		// Shapes are stored top-oriented (the shapedividers.com standard, so a pasted SVG "just
		// works"). The Section draws a BOTTOM divider by rotating 180deg; mirror that in the
		// thumbnail so the Bottom picker previews the real bottom-edge look. Same shape, per placement.
		$rotate = ( $placement === 'bottom' )
			? ' transform="rotate(180 ' . ( (int) $w / 2 ) . ' ' . ( (int) $h / 2 ) . ')"'
			: '';
		// currentColor resolves to the near-black `color` below (matching the black most pasted
		// dividers use); every layer's opacity is preserved, so it reads as the stacked-grey preview.
		$shape = '<svg x="0" y="0" width="' . (int) $w . '" height="' . (int) $h . '" viewBox="' . esc_attr( $vb ) . '" preserveAspectRatio="none" fill="currentColor" style="color:#111827">' . $inner . '</svg>';
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int) $w . '" height="' . (int) $h . '" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '">'
			. '<rect width="' . (int) $w . '" height="' . (int) $h . '" fill="#f8fafc"/>'
			. '<g' . $rotate . '>' . $shape . '</g></svg>';
		return 'data:image/svg+xml,' . rawurlencode( $svg );
	}
endif;

if ( ! function_exists( 'unysonplus_shape_divider_imagepicker_choices' ) ) :
	/**
	 * image-picker `choices` for a visual Shape Divider picker — a `none` tile plus one generated
	 * silhouette thumbnail per preset (keyed by preset id; `none` = no divider).
	 *
	 * `none` MUST stay a real choice (it is the default value and the only reliable "no divider"
	 * the multi-picker can hold through the modal's collect / re-render / lazy-tab cycles). Its
	 * TILE is hidden from popover pickers by the image-picker option type — same design as the
	 * Background Pattern picker.
	 */
	function unysonplus_shape_divider_imagepicker_choices( $placement = 'bottom', $thumb_h = null ) {
		// Per-option tile height: pass an int to size THIS picker's tiles (each Section option
		// calls this independently, so heights never share a global). Defaults keep the stock size.
		$sh = ( $thumb_h !== null ) ? (int) $thumb_h : 66;
		$lh = ( $thumb_h !== null ) ? (int) $thumb_h : 132;
		$none = 'data:image/svg+xml,' . rawurlencode(
			'<svg xmlns="http://www.w3.org/2000/svg" width="200" height="120">'
			. '<rect width="200" height="120" fill="#f8fafc"/>'
			. '<line x1="0" y1="120" x2="200" y2="0" stroke="#e2e8f0" stroke-width="2"/>'
			. '<text x="100" y="67" text-anchor="middle" font-family="-apple-system,Segoe UI,sans-serif" font-size="15" fill="#94a3b8">None</text></svg>'
		);
		$choices = array(
			'none' => array(
				'small' => array( 'src' => $none, 'height' => $sh ),
				'large' => array( 'src' => $none, 'height' => $lh ),
				'label' => __( 'None', 'fw' ),
			),
		);
		foreach ( unysonplus_get_shape_divider_presets() as $p ) {
			if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
			if ( $id === '' || $id === 'none' ) { continue; }
			$name = ( isset( $p['divider_name'] ) && $p['divider_name'] !== '' ) ? (string) $p['divider_name'] : $id;
			$geo  = fw_upw_normalize_divider_svg( isset( $p['svg_code'] ) ? $p['svg_code'] : '' );
			$uri  = unysonplus_shape_divider_thumb_datauri( $geo['inner'], $geo['viewBox'], $placement );
			$choices[ $id ] = array(
				'small' => array( 'src' => $uri, 'height' => $sh ),
				'large' => array( 'src' => $uri, 'height' => $lh ),
				'label' => $name,
			);
		}
		return $choices;
	}
endif;
