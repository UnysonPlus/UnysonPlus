<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Core inline-SVG sanitiser (fw_upw_*). Relocated from the shortcodes extension so the CORE
 * option types that accept SVG — the `icon` type and the image-mask in css-tokens — own their
 * own defence. On a CORE-ONLY public build the shortcodes extension is absent, and the icon
 * type previously fell back to storing SVG markup RAW (a persistent-XSS surface). This is now
 * always present (required early in bootstrap.php).
 *
 * wp_kses allowlist + Illustrator <style>/class flattening + camelCase attr restoration +
 * href restricted to same-document '#fragment'. The shortcodes `sc_icon_*` names delegate
 * here for back-compat (see includes/shortcode-styling-helper.php).
 */

if ( ! function_exists( 'fw_upw_svg_allowed' ) ) :
	/** wp_kses allowlist for inline icon SVG (scripts / handlers / external refs stripped). */
	function fw_upw_svg_allowed() {
		$stroke = array(
			'fill' => true, 'stroke' => true, 'stroke-width' => true,
			'stroke-linecap' => true, 'stroke-linejoin' => true,
			'fill-rule' => true, 'clip-rule' => true, 'class' => true,
			// Presentation attrs real-world brand SVGs rely on (Illustrator
			// exports, gradient fills, reflections). Values are inert - the
			// XSS surface is scripts/handlers/foreignObject, all still absent.
			'opacity' => true, 'fill-opacity' => true, 'stroke-opacity' => true,
			'transform' => true, 'id' => true, 'clip-path' => true, 'mask' => true,
			'stroke-dasharray' => true, 'stroke-dashoffset' => true, 'stroke-miterlimit' => true,
		);
		// <text>/<tspan>: real text in logo SVGs (wordmarks). Font attrs only -
		// no style attr, no event handlers.
		$text = array_merge( $stroke, array(
			'x' => true, 'y' => true, 'dx' => true, 'dy' => true, 'rotate' => true,
			'font-family' => true, 'font-size' => true, 'font-weight' => true,
			'font-style' => true, 'letter-spacing' => true, 'text-anchor' => true,
		) );
		// Gradient plumbing (defs / stops / units). href is allowed for
		// gradient templates + <use>, but fw_upw_sanitize_svg() strips any
		// href that is not a same-document '#fragment' reference.
		$grad = array(
			'id' => true, 'gradientunits' => true, 'gradienttransform' => true,
			'spreadmethod' => true, 'href' => true, 'xlink:href' => true,
			'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true,
			'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'fr' => true,
		);
		$allowed = array(
			'svg'      => array(
				'xmlns' => true, 'xmlns:xlink' => true, 'viewbox' => true, 'width' => true, 'height' => true,
				'x' => true, 'y' => true, 'version' => true, 'id' => true,
				'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'stroke-linecap' => true, 'stroke-linejoin' => true,
				'preserveaspectratio' => true, 'class' => true, 'role' => true,
				'aria-hidden' => true, 'aria-label' => true, 'focusable' => true,
				'xml:space' => true,
			),
			'g'        => $stroke,
			'defs'     => array( 'id' => true ),
			'symbol'   => array_merge( $stroke, array( 'viewbox' => true, 'preserveaspectratio' => true ) ),
			'use'      => array_merge( $stroke, array( 'href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true ) ),
			'path'     => array_merge( $stroke, array( 'd' => true ) ),
			'circle'   => array_merge( $stroke, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
			'ellipse'  => array_merge( $stroke, array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true ) ),
			'rect'     => array_merge( $stroke, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
			'line'     => array_merge( $stroke, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
			'polyline' => array_merge( $stroke, array( 'points' => true ) ),
			'polygon'  => array_merge( $stroke, array( 'points' => true ) ),
			'text'     => $text,
			'tspan'    => $text,
			'lineargradient' => $grad,
			'radialgradient' => $grad,
			'stop'     => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true, 'id' => true ),
			'clippath' => array( 'id' => true, 'clippathunits' => true ),
			'mask'     => array_merge( $stroke, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'maskunits' => true, 'maskcontentunits' => true ) ),
			'title'    => array(),
			'desc'     => array(),
		);

		// SMIL animation elements — allowed ONLY when the Animated Icons extension
		// has enabled "Animated SVG". They declaratively animate an attribute /
		// transform / motion over time and CANNOT execute JavaScript; the XSS
		// surface (scripts, event handlers, <foreignObject>, external refs) stays
		// excluded exactly as before. The camelCase attrs below (attributeName,
		// keyTimes, …) are restored from wp_kses's lowercasing in
		// fw_upw_sanitize_svg(), or the browser would ignore them.
		if ( function_exists( 'fw_icon_svg_animation_enabled' ) && fw_icon_svg_animation_enabled() ) {
			$anim = array(
				'attributename' => true, 'attributetype' => true,
				'from' => true, 'to' => true, 'by' => true, 'values' => true,
				'keytimes' => true, 'keysplines' => true, 'calcmode' => true,
				'dur' => true, 'begin' => true, 'end' => true, 'min' => true, 'max' => true,
				'restart' => true, 'repeatcount' => true, 'repeatdur' => true,
				'fill' => true, 'additive' => true, 'accumulate' => true, 'id' => true,
			);
			$allowed['animate']          = $anim;
			$allowed['set']              = $anim;
			$allowed['animatetransform'] = array_merge( $anim, array( 'type' => true ) );
			$allowed['animatemotion']    = array_merge( $anim, array( 'path' => true, 'keypoints' => true, 'rotate' => true, 'origin' => true ) );
			$allowed['mpath']            = array( 'href' => true, 'xlink:href' => true, 'id' => true );
		}

		return $allowed;
	}
endif;

if ( ! function_exists( 'fw_upw_flatten_svg_css' ) ) :
	/**
	 * Flatten an SVG's internal CSS into presentation attributes so the markup
	 * survives sanitisation intact. Adobe Illustrator exports style everything
	 * through a <style> block of `.stN{...}` classes (plus inline style="...")
	 * - wp_kses strips both, which used to turn AI exports black. This inlines:
	 *   1. every simple single-class rule (`.st0{fill:#123}`) onto the elements
	 *      carrying that class, and
	 *   2. every inline style="prop:val" list,
	 * as plain attributes (fill="#123"), then drops the <style> block. Only a
	 * safe property allowlist is inlined - anything else is discarded.
	 */
	function fw_upw_flatten_svg_css( $markup ) {
		$pal = sc_ui_icon_palette();
		$markup = (string) $markup;
		if ( stripos( $markup, '<style' ) === false && stripos( $markup, 'style=' ) === false ) {
			return $markup;
		}

		// Properties worth inlining (matching SVG presentation attributes 1:1).
		$props = array(
			'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
			'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit',
			'fill-rule', 'clip-rule', 'opacity', 'fill-opacity', 'stroke-opacity',
			'stop-color', 'stop-opacity', 'font-family', 'font-size', 'font-weight',
			'font-style', 'letter-spacing', 'text-anchor', 'clip-path', 'mask',
			'transform',
		);
		$parse_decls = function ( $body ) use ($props, $pal) {
			$out = array();
			foreach ( explode( ';', $body ) as $decl ) {
				$decl = trim( $decl );
				if ( $decl === '' || strpos( $decl, ':' ) === false ) { continue; }
				list( $prop, $val ) = array_map( 'trim', explode( ':', $decl, 2 ) );
				$prop = strtolower( $prop );
				if ( in_array( $prop, $props, true ) && $val !== '' && strpos( $val, '<' ) === false ) {
					$out[ $prop ] = $val;
				}
			}
			// Illustrator writes PostScript font names ('Arial-Black',
			// 'Montserrat-SemiBoldItalic') which browsers do NOT resolve - CSS
			// wants the family name + weight/style. Normalise: strip a trailing
			// weight/style suffix into font-weight / font-style, de-hyphenate
			// the family, and keep the original name as a fallback for
			// environments that do resolve PostScript names (Illustrator).
			if ( isset( $out['font-family'] ) ) {
				$raw = trim( $out['font-family'], " \t'\"" );
				if ( preg_match( '/^([A-Za-z0-9 ]+?)-?(Thin|ExtraLight|UltraLight|Light|Regular|Medium|SemiBold|DemiBold|Bold|ExtraBold|UltraBold|Black|Heavy)?(Italic|Oblique)?$/', $raw, $fm ) ) {
					$family  = trim( preg_replace( '/(?<=[a-z])(?=[A-Z])/', ' ', $fm[1] ) ); // CamelCase -> spaced
					$weights = array(
						'Thin' => '100', 'ExtraLight' => '200', 'UltraLight' => '200',
						'Light' => '300', 'Regular' => '400', 'Medium' => '500',
						'SemiBold' => '600', 'DemiBold' => '600', 'Bold' => '700',
						'ExtraBold' => '800', 'UltraBold' => '800', 'Black' => '900', 'Heavy' => '900',
					);
					if ( $family !== '' && $family !== $raw ) {
						// The spaced full name first ("Arial Black" IS a family of
						// its own), then the bare family + weight, then the raw
						// PostScript name for environments that resolve it.
						$stack = array();
						if ( ! empty( $fm[2] ) ) { $stack[] = "'" . $family . ' ' . $fm[2] . "'"; }
						$stack[] = "'" . $family . "'";
						$stack[] = "'" . $raw . "'";
						$stack[] = 'sans-serif';
						$out['font-family'] = implode( ', ', array_unique( $stack ) );
						if ( ! empty( $fm[2] ) && ! isset( $out['font-weight'] ) && isset( $weights[ $fm[2] ] ) ) {
							$out['font-weight'] = $weights[ $fm[2] ];
						}
						if ( ! empty( $fm[3] ) && ! isset( $out['font-style'] ) ) {
							$out['font-style'] = 'italic';
						}
					}
				}
			}
			return $out;
		};

		// 1. Collect single-class rules from every <style> block, in order (a
		//    later rule for the same class overrides an earlier one, like CSS).
		$class_map = array();
		if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $markup, $style_blocks ) ) {
			foreach ( $style_blocks[1] as $css ) {
				if ( preg_match_all( '/\.([A-Za-z_][\w-]*)\s*\{([^}]*)\}/', $css, $rules, PREG_SET_ORDER ) ) {
					foreach ( $rules as $rule ) {
						$decls = $parse_decls( $rule[2] );
						if ( $decls ) {
							$class_map[ $rule[1] ] = isset( $class_map[ $rule[1] ] )
								? array_merge( $class_map[ $rule[1] ], $decls )
								: $decls;
						}
					}
				}
			}
			$markup = preg_replace( '/<style[^>]*>.*?<\/style>\s*/is', '', $markup );
		}

		// 2. Rewrite each element: inline its classes' declarations + its own
		//    style="" (inline style wins over class rules, like CSS), written as
		//    presentation attributes REPLACING same-name existing attributes
		//    (class/style would have out-cascaded them anyway).
		$markup = preg_replace_callback( '/<([a-zA-Z][\w:-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)(\/?)>/', function ( $m ) use ($class_map, $parse_decls, $pal) {
			$tag  = $m[1];
			$attr = $m[2];
			$decls = array();
			if ( preg_match( '/\sclass\s*=\s*(["\'])(.*?)\1/', $attr, $cm ) ) {
				foreach ( preg_split( '/\s+/', trim( $cm[2] ) ) as $cls ) {
					if ( isset( $class_map[ $cls ] ) ) { $decls = array_merge( $decls, $class_map[ $cls ] ); }
				}
			}
			if ( preg_match( '/\sstyle\s*=\s*(["\'])(.*?)\1/', $attr, $sm ) ) {
				$decls = array_merge( $decls, $parse_decls( $sm[2] ) );
				$attr  = preg_replace( '/\sstyle\s*=\s*(["\'])(?:.*?)\1/', '', $attr );
			}
			foreach ( $decls as $prop => $val ) {
				// Replace an existing same-name attribute, else append.
				$val  = str_replace( array( '"', '<', '>' ), '', $val );
				$attr = preg_replace( '/\s' . preg_quote( $prop, '/' ) . '\s*=\s*(["\'])(?:.*?)\1/', '', $attr );
				$attr .= ' ' . $prop . '="' . $val . '"';
			}
			return '<' . $tag . $attr . $m[3] . '>';
		}, $markup );

		return $markup;
	}
endif;

if ( ! function_exists( 'fw_upw_sanitize_svg' ) ) :
	/** Sanitise inline SVG markup against the shared allowlist. Returns '' if not SVG. */
	function fw_upw_sanitize_svg( $markup ) {
		$markup = (string) $markup;
		if ( stripos( $markup, '<svg' ) === false ) { return ''; }
		// Inline any internal CSS (Illustrator's <style> + classes / style="")
		// as presentation attributes FIRST, so the styling survives wp_kses.
		$markup = fw_upw_flatten_svg_css( $markup );
		$clean = wp_kses( $markup, fw_upw_svg_allowed() );
		// wp_kses lowercases attribute NAMES, but several SVG attributes are
		// case-SENSITIVE and silently break when lowercased — most importantly
		// `viewBox` (a lowercased `viewbox` is ignored by the browser, collapsing
		// the SVG's intrinsic aspect ratio so `width:auto` mis-sizes it). Restore
		// their canonical camelCase on the way out.
		$camel = array(
			'viewbox'             => 'viewBox',
			'preserveaspectratio' => 'preserveAspectRatio',
			'gradientunits'       => 'gradientUnits',
			'gradienttransform'   => 'gradientTransform',
			'spreadmethod'        => 'spreadMethod',
			'clippathunits'       => 'clipPathUnits',
			'maskunits'           => 'maskUnits',
			'maskcontentunits'    => 'maskContentUnits',
			// SMIL animation attrs (only present when Animated SVG is enabled) —
			// camelCase-sensitive; a lowercased `attributename`/`repeatcount`/…
			// is ignored by the browser and the animation silently dies.
			'attributename'       => 'attributeName',
			'attributetype'       => 'attributeType',
			'keytimes'            => 'keyTimes',
			'keysplines'          => 'keySplines',
			'calcmode'            => 'calcMode',
			'repeatcount'         => 'repeatCount',
			'repeatdur'           => 'repeatDur',
			'keypoints'           => 'keyPoints',
		);
		$clean = preg_replace_callback(
			'/\s(viewbox|preserveaspectratio|gradientunits|gradienttransform|spreadmethod|clippathunits|maskunits|maskcontentunits|attributename|attributetype|keytimes|keysplines|calcmode|repeatcount|repeatdur|keypoints)=/i',
			function ( $m ) use ( $camel ) { return ' ' . $camel[ strtolower( $m[1] ) ] . '='; },
			$clean
		);
		// href / xlink:href are only allowed as same-document '#fragment'
		// references (gradient templates, <use>). Strip anything else so an
		// external or javascript: URL can never survive the allowlist.
		$clean = preg_replace( '/\s(href|xlink:href)\s*=\s*(["\'])(?!#)[^"\']*\2/i', '', $clean );
		// SVG forbids negative radii, but an Illustrator mirror-export can emit
		// them (e.g. ry="-14" on a reflected ellipse) - browsers log a console
		// error and SKIP the shape. The geometric intent is the absolute value.
		$clean = preg_replace( '/\s(r|rx|ry)\s*=\s*"-([\d.]+)"/i', ' $1="$2"', $clean );
		$clean = preg_replace( "/\s(r|rx|ry)\s*=\s*'-([\d.]+)'/i", " \$1='\$2'", $clean );
		return $clean;
	}
endif;
