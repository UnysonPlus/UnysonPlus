<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Plugin bridge — writes a hashed `presets-{hash}.css` to wp-content/uploads/unysonplus/
 * and enqueues it under the handle `unysonplus-presets`. The Asset Optimizer
 * extension absorbs the handle into the combined site CSS automatically.
 *
 * Contents: :root CSS variables and utility-class rules derived from the
 * Font Size + Color + Spacing + Button presets (plugin defaults or
 * user-overridden, see presets.php).
 *
 * Mobile auto-scaling: a second :root block under @media (max-width: 767.98px)
 * shrinks every --font-size-* token via the tiered scaler. Utility classes
 * reference the variables and inherit the mobile shrink for free.
 *
 * Fallback: if the uploads dir is not writable, the emit function falls back
 * to the original behaviour of echoing an inline <style id="unysonplus-presets">
 * during wp_head / admin_head.
 */

if ( ! function_exists( 'unysonplus_build_presets_css_string' ) ) :
	/**
	 * Builds the preset CSS as a string (no echo, no file write).
	 *
	 * @param bool $pretty When true emits indented/multi-line CSS. Defaults to WP_DEBUG.
	 * @return string CSS body without the surrounding <style> tag. Empty string if no presets.
	 */
	function unysonplus_build_presets_css_string( $pretty = null ) {
		if ( $pretty === null ) {
			$pretty = defined( 'WP_DEBUG' ) && WP_DEBUG;
		}
		$font_sizes    = function_exists( 'unysonplus_get_font_size_presets' ) ? unysonplus_get_font_size_presets() : array();
		$color_presets = function_exists( 'unysonplus_get_color_presets' )     ? unysonplus_get_color_presets()     : array();

		$tokens        = array();
		$utility_rules = array();

		// --- Text Styles (font-size scale + optional weight / line-height /
		// letter-spacing / text-transform). Each property is OPT-IN: a style emits
		// only the props that are filled in, scoped to its own class, so any blank
		// prop INHERITS from the element's tag token (a blank weight ≠ thin). Size
		// still keeps a --font-size-{slug} token (+ !important) so it also feeds
		// mobile scaling and beats Bootstrap / component utilities.
		// (Stored under the legacy `font_sizes` key — a size-only Text Style.) ---
		if ( is_array( $font_sizes ) ) {
			foreach ( $font_sizes as $entry ) {
				// Resolve the target selector: an explicit class (e.g. Bootstrap
				// `display-1`) or a `.font-{slug}` derived from the style name.
				if ( ! empty( $entry['class'] ) ) {
					$class_literal = preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( $entry['class'] ) );
					if ( $class_literal === '' ) { continue; }
					$slug     = strtolower( $class_literal );
					$selector = '.' . $class_literal;
				} elseif ( ! empty( $entry['name'] ) ) {
					$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $entry['name'] ) ), '-' );
					if ( $slug === '' ) { continue; }
					$selector = ".font-{$slug}";
				} else {
					continue;
				}

				$decls = array();

				// Size — token + !important (unchanged; also feeds mobile scaling).
				if ( ! empty( $entry['size'] ) ) {
					$size_value          = is_numeric( $entry['size'] ) ? $entry['size'] . 'px' : $entry['size'];
					$var_name            = "--font-size-{$slug}";
					$tokens[ $var_name ] = $size_value;
					$decls[]             = "font-size:var({$var_name}) !important";
				}
				// Weight — numeric. No !important needed: `:root .{class}` (0,2,0)
				// already outranks Bootstrap's .display-N (0,1,0) and the tag-token
				// weight override (hN.heading-title = 0,1,1), while element Custom CSS
				// can still win. Blank ⇒ not emitted ⇒ inherits the tag weight.
				if ( ! empty( $entry['weight'] ) && is_numeric( $entry['weight'] ) ) {
					$decls[] = 'font-weight:' . (int) $entry['weight'];
				}
				// Line-height — unitless number or an explicit length/percentage.
				if ( isset( $entry['line_height'] ) && trim( (string) $entry['line_height'] ) !== '' ) {
					$decls[] = 'line-height:' . preg_replace( '/[^0-9a-z.%\-]/i', '', trim( (string) $entry['line_height'] ) );
				}
				// Letter-spacing — a bare number is treated as em (tracking); a value
				// carrying its own unit passes through.
				if ( isset( $entry['letter_spacing'] ) && trim( (string) $entry['letter_spacing'] ) !== '' ) {
					$ls      = trim( (string) $entry['letter_spacing'] );
					$ls      = is_numeric( $ls ) ? $ls . 'em' : preg_replace( '/[^0-9a-z.%\-]/i', '', $ls );
					$decls[] = 'letter-spacing:' . $ls;
				}
				// Text-transform — whitelisted keyword.
				if ( ! empty( $entry['transform'] ) && in_array( $entry['transform'], array( 'none', 'uppercase', 'lowercase', 'capitalize' ), true ) ) {
					$decls[] = 'text-transform:' . $entry['transform'];
				}

				if ( ! empty( $decls ) ) {
					$utility_rules[ ":root {$selector}" ] = implode( ';', $decls ) . ';';
				}
			}
		}

		// --- Color presets ---
		$color_slug_to_hex = array();
		if ( is_array( $color_presets ) ) {
			foreach ( $color_presets as $entry ) {
				if ( empty( $entry['color'] ) || empty( $entry['name'] ) ) { continue; }
				$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $entry['name'] ) ), '-' );
				if ( $slug === '' ) { continue; }

				$hex = function_exists( 'sanitize_hex_color' ) ? sanitize_hex_color( $entry['color'] ) : $entry['color'];
				if ( empty( $hex ) ) { $hex = $entry['color']; }

				$tokens[ "--color-{$slug}" ] = $hex;
				// Specificity boost (`:root`) + !important so preset utilities
				// reliably override Bootstrap utilities and component CSS.
				$utility_rules[ ":root .text-{$slug}" ] = "color:var(--color-{$slug}) !important;";
				$utility_rules[ ":root .bg-{$slug}" ]   = "background-color:var(--color-{$slug}) !important;";

				$color_slug_to_hex[ $slug ] = $hex;
			}
		}

		// --- Button color presets -> .btn-{id} + .btn-outline-{id} rules ---
		// Each saved entry's color fields hold Color Preset slugs (Phase 5.1+).
		// Resolver below also accepts raw hex (`#…`) for back-compat during the
		// hex→slug migration window. Hover fields fall back to the normal value
		// when empty.
		//
		// CASCADE: button preset rules carry NO `!important`. They win purely on
		// SOURCE ORDER — the preset CSS (handle `unysonplus-presets`) is enqueued
		// AFTER the button extension's base `.btn-*` stylesheet (so a preset beats
		// the stock `.btn-primary` skin) and BEFORE the theme/child stylesheets
		// (so a theme or child theme can override a preset with a plain
		// `.btn-primary { … }` rule, with no `!important` arms race). Using
		// `!important` here used to make presets un-overridable by child themes —
		// which defeats the point of a parent/child setup. Bootstrap's own button
		// CSS is not loaded, so there is nothing higher-specificity to fight.
		$button_presets = function_exists( 'unysonplus_get_button_color_presets' )
			? unysonplus_get_button_color_presets()
			: array();
		$resolve_btn_color = function ( $v ) use ( $color_slug_to_hex ) {
			// Compact color picker shape: { predefined: slug|class, custom: hex }.
			// Custom wins; otherwise resolve the predefined slug to a hex.
			if ( is_array( $v ) ) {
				$custom = isset( $v['custom'] ) ? trim( (string) $v['custom'] ) : '';
				if ( $custom !== '' ) { return $custom; }
				$v = isset( $v['predefined'] ) ? trim( (string) $v['predefined'] ) : '';
			}
			$v = (string) $v;
			if ( $v === '' ) { return ''; }
			if ( $v[0] === '#' ) { return $v; }
			if ( isset( $color_slug_to_hex[ $v ] ) ) { return $color_slug_to_hex[ $v ]; }
			// Tolerate utility-class style values like text-blue / bg-blue / border-blue.
			$slug = preg_replace( '/^(text|bg|background|border|btn)-/', '', $v );
			return isset( $color_slug_to_hex[ $slug ] ) ? $color_slug_to_hex[ $slug ] : '';
		};
		$button_extra_css = ''; // freeform per-preset custom CSS, appended at assembly

		// numeric -> px; pass other CSS units through untouched.
		$len = function ( $v ) {
			$v = trim( (string) $v );
			if ( $v === '' ) { return ''; }
			return preg_match( '/^-?[0-9.]+$/', $v ) ? $v . 'px' : $v;
		};

		// Typography-v2 value -> declarations. Preset typography is IDENTITY only
		// (family / weight / letter-spacing / style). font-size + line-height are
		// the SIZE axis (.btn-{slug}); they are intentionally not emitted here so
		// the two axes stay orthogonal even for presets saved before the split.
		$font_decls = function ( $f ) use ( $len ) {
			$d = array();
			if ( ! is_array( $f ) ) { return $d; }
			if ( ! empty( $f['family'] ) )                              { $d[] = 'font-family:' . $f['family']; }
			if ( ! empty( $f['weight'] ) )                             { $d[] = 'font-weight:' . $f['weight']; }
			if ( isset( $f['letter-spacing'] ) && $f['letter-spacing'] !== '' ) { $d[] = 'letter-spacing:' . $len( (string) $f['letter-spacing'] ); }
			if ( ! empty( $f['style'] ) && $f['style'] !== 'normal' )  { $d[] = 'font-style:' . $f['style']; }
			return $d;
		};

		// One interaction state -> declarations.
		// SKIN only: colors / border (color, style, width) / box-shadow.
		// Dimensional props (padding, font-size, border-radius) live on the SIZE
		// axis (.btn-{slug}), so they are intentionally NOT emitted here.
		$state_decls = function ( $st ) use ( $resolve_btn_color, $len ) {
			$d = array();
			if ( ! is_array( $st ) ) { return $d; }

			$text = $resolve_btn_color( isset( $st['text_color'] )   ? $st['text_color']   : '' );
			$bg   = $resolve_btn_color( isset( $st['bg_color'] )     ? $st['bg_color']     : '' );
			$bdr  = $resolve_btn_color( isset( $st['border_color'] ) ? $st['border_color'] : '' );
			$tt   = isset( $st['text_transform'] ) ? trim( (string) $st['text_transform'] ) : '';
			$bs   = isset( $st['border_style'] )   ? (string) $st['border_style']           : '';
			// border-width is a unit-input value { value, unit } (legacy: string).
			$bw   = ( isset( $st['border_width'] ) && class_exists( 'FW_Option_Type_Unit_Input' ) )
				? FW_Option_Type_Unit_Input::to_string( $st['border_width'] )
				: ( isset( $st['border_width'] ) && ! is_array( $st['border_width'] ) ? (string) $st['border_width'] : '' );

			if ( $text !== '' ) { $d[] = "color:{$text}"; }
			if ( $bg !== '' )   { $d[] = "background-color:{$bg}"; }
			// Optional gradient layers OVER the solid color (background-image), so the
			// resolved bg_color above remains a fallback. Empty value emits nothing.
			if ( isset( $st['gradient'] ) && class_exists( 'FW_Option_Type_Gradient_V2' ) ) {
				$grad = FW_Option_Type_Gradient_V2::to_css( $st['gradient'] );
				if ( $grad !== '' ) { $d[] = "background-image:{$grad}"; }
			}
			if ( $tt !== '' )   { $d[] = "text-transform:{$tt}"; }

			if ( $bs !== '' && $bs !== 'none' ) {
				$d[] = "border-style:{$bs}";
				$w = $len( $bw );
				if ( $w !== '' )   { $d[] = "border-width:{$w}"; }
				if ( $bdr !== '' ) { $d[] = "border-color:{$bdr}"; }
			} elseif ( $bs === 'none' ) {
				$d[] = 'border:0';
			} else {
				if ( $bw !== '' )  { $d[] = 'border-width:' . $len( $bw ); }
				if ( $bdr !== '' ) { $d[] = "border-color:{$bdr}"; }
			}

			if ( class_exists( 'FW_Option_Type_Box_Shadow' ) && isset( $st['box_shadow'] ) ) {
				$sh = FW_Option_Type_Box_Shadow::to_css( $st['box_shadow'] );
				if ( $sh !== '' ) { $d[] = "box-shadow:{$sh}"; }
			}

			return $d;
		};

		// id => readable css slug (preset name → .btn-primary, etc.).
		$btn_slug_map = function_exists( 'unysonplus_button_preset_slug_map' )
			? unysonplus_button_preset_slug_map()
			: array();

		if ( is_array( $button_presets ) ) {
			foreach ( $button_presets as $bp ) {
				if ( ! is_array( $bp ) || empty( $bp['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $bp['id'] );
				if ( $id === '' ) { continue; }

				// Name-based class slug (falls back to the id when unmapped).
				$slug = isset( $btn_slug_map[ $id ] ) ? $btn_slug_map[ $id ] : $id;

				// Migrate legacy flat presets (normal_*/hover_* keys) to states.
				if ( ! isset( $bp['states'] ) && class_exists( 'FW_Option_Type_Button_Presets' ) ) {
					$bp = FW_Option_Type_Button_Presets::maybe_migrate_flat( $bp );
				}
				$states = isset( $bp['states'] ) && is_array( $bp['states'] ) ? $bp['states'] : array();
				$def    = isset( $states['default'] ) && is_array( $states['default'] ) ? $states['default'] : array();

				$transition = isset( $bp['transition'] ) ? trim( (string) $bp['transition'] ) : '';
				$font       = isset( $bp['font'] ) ? $bp['font'] : array();

				/* ---- base: .btn-{slug} (font + transition + default state) ---- */
				$base = $font_decls( $font );
				if ( $transition !== '' ) {
					$tv = preg_match( '/^[0-9.]+$/', $transition ) ? $transition . 'ms' : $transition;
					$base[] = "transition:all {$tv} ease";
				}
				$base = array_merge( $base, $state_decls( $def ) );
				if ( $base ) { $utility_rules[ ".btn-{$slug}" ] = implode( ';', $base ) . ';'; }

				/* ---- interaction states (diffs) ---- */
				$state_sel = array(
					'hover'    => ".btn-{$slug}:hover",
					'active'   => ".btn-{$slug}:active",
					'focus'    => ".btn-{$slug}:focus",
					'disabled' => ".btn-{$slug}:disabled,.btn-{$slug}.disabled",
				);
				foreach ( $state_sel as $state => $sel ) {
					$sv = isset( $states[ $state ] ) ? $states[ $state ] : array();
					$decls = $state_decls( $sv );
					if ( $decls ) { $utility_rules[ $sel ] = implode( ';', $decls ) . ';'; }
				}

				/* ---- outline variant (text+border take the default bg; bg transparent) ---- */
				$nb = $resolve_btn_color( isset( $def['bg_color'] ) ? $def['bg_color'] : '' );
				$nt = $resolve_btn_color( isset( $def['text_color'] ) ? $def['text_color'] : '' );
				if ( $nb !== '' ) {
					$utility_rules[ ".btn-outline-{$slug}" ] = "color:{$nb};background-color:transparent;border-color:{$nb};";
					$op = array();
					if ( $nt !== '' ) { $op[] = "color:{$nt}"; }
					$op[] = "background-color:{$nb}";
					$op[] = "border-color:{$nb}";
					$utility_rules[ ".btn-outline-{$slug}:hover" ] = implode( ';', $op ) . ';';
				}

				/* ---- freeform custom CSS ---- */
				$custom_css = isset( $bp['custom_css'] ) ? (string) $bp['custom_css'] : '';
				if ( trim( $custom_css ) !== '' ) {
					$custom_css = preg_replace( '#</?(style|script)[^>]*>#i', '', $custom_css );
					$custom_css = str_replace( array( '<', '>' ), '', $custom_css );
					$button_extra_css .= "\n" . str_replace( '{{SELECTOR}}', ".btn-{$slug}", $custom_css );
				}
			}
		}

		// --- Button size presets -> .btn-{slug} typography/box rules ---
		// Each saved entry's `slug` becomes the CSS class suffix. Like the color
		// presets above, these carry NO `!important` — they beat the button
		// extension's stock .btn-sm/.btn-lg on source order (presets enqueue
		// after the extension base CSS) while staying overridable by the theme /
		// child theme (which enqueue after the presets).
		$button_sizes = function_exists( 'unysonplus_get_button_size_presets' )
			? unysonplus_get_button_size_presets()
			: array();
		if ( is_array( $button_sizes ) ) {
			// A size value may be a unit-input array { value, unit } (current),
			// a 4-side padding array { top,right,bottom,left } (legacy padding),
			// or a plain string (legacy font_size/radius). Resolve any of them.
			$size_len = function ( $v ) {
				if ( is_array( $v ) && class_exists( 'FW_Option_Type_Unit_Input' ) ) {
					return FW_Option_Type_Unit_Input::to_string( $v );
				}
				return is_array( $v ) ? '' : trim( (string) $v );
			};

			foreach ( $button_sizes as $bs ) {
				if ( ! is_array( $bs ) || empty( $bs['slug'] ) ) { continue; }
				$slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $bs['slug'] );
				if ( $slug === '' ) { continue; }

				$parts = array();

				$fs = $size_len( $bs['font_size'] ?? '' );
				if ( $fs !== '' ) { $parts[] = "font-size:{$fs}"; }

				if ( ! empty( $bs['line_height'] ) ) { $parts[] = "line-height:{$bs['line_height']}"; }

				// Padding: new shape is padding_y / padding_x; legacy is a 4-side
				// padding{top,right,bottom,left} array.
				$py = $size_len( $bs['padding_y'] ?? '' );
				$px = $size_len( $bs['padding_x'] ?? '' );
				if ( $py !== '' || $px !== '' ) {
					$parts[] = 'padding:' . ( $py !== '' ? $py : '0' ) . ' ' . ( $px !== '' ? $px : '0' );
				} elseif ( is_array( $bs['padding'] ?? null )
				     && isset( $bs['padding']['top'], $bs['padding']['right'], $bs['padding']['bottom'], $bs['padding']['left'] )
				     && ( $bs['padding']['top'] !== '' || $bs['padding']['right'] !== '' || $bs['padding']['bottom'] !== '' || $bs['padding']['left'] !== '' ) ) {
					$parts[] = "padding:{$bs['padding']['top']} {$bs['padding']['right']} {$bs['padding']['bottom']} {$bs['padding']['left']}";
				}

				$rad = $size_len( $bs['border_radius'] ?? '' );
				if ( $rad !== '' ) { $parts[] = "border-radius:{$rad}"; }

				$min_w = $size_len( $bs['min_width'] ?? '' );
				if ( $min_w !== '' ) { $parts[] = "min-width:{$min_w}"; }

				$max_w = $size_len( $bs['max_width'] ?? '' );
				if ( $max_w !== '' ) { $parts[] = "max-width:{$max_w}"; }

				if ( ! empty( $parts ) ) {
					$utility_rules[ ".btn-{$slug}" ] = implode( ';', $parts ) . ';';
				}
			}
		}

		// --- Column border presets -> .boxp-{slug} + .boxp-{slug}:hover rules ---
		// Each saved preset is a reusable column "card" border (Theme Settings →
		// General → Borders). border_color is a compact-picker value resolved via
		// $resolve_btn_color; border_width / border_radius are unit-input; box_shadow
		// rides FW_Option_Type_Box_Shadow::to_css. The hover state holds only the
		// diffs (border/shadow) and transitions from the base via `transition`.
		$border_presets  = function_exists( 'unysonplus_get_border_presets' )      ? unysonplus_get_border_presets()      : array();
		$border_slug_map = function_exists( 'unysonplus_border_preset_slug_map' )  ? unysonplus_border_preset_slug_map()  : array();

		// 'all' -> `border`, otherwise the single physical side.
		$border_side_prop = function ( $sides ) {
			switch ( $sides ) {
				case 'top':    return 'border-top';
				case 'end':    return 'border-right';
				case 'bottom': return 'border-bottom';
				case 'start':  return 'border-left';
				default:       return 'border';
			}
		};
		// One state -> border declarations on the chosen side(s) + box-shadow.
		$border_state_decls = function ( $st, $sides ) use ( $resolve_btn_color, $len, $border_side_prop ) {
			$d = array();
			if ( ! is_array( $st ) ) { return $d; }
			$prop  = $border_side_prop( $sides );
			$style = isset( $st['border_style'] ) ? (string) $st['border_style'] : '';
			$color = $resolve_btn_color( isset( $st['border_color'] ) ? $st['border_color'] : '' );
			$bw    = ( isset( $st['border_width'] ) && class_exists( 'FW_Option_Type_Unit_Input' ) )
				? FW_Option_Type_Unit_Input::to_string( $st['border_width'] )
				: ( isset( $st['border_width'] ) && ! is_array( $st['border_width'] ) ? (string) $st['border_width'] : '' );
			$w = $len( $bw );

			if ( $style !== '' ) {
				$d[] = "{$prop}:" . ( $w !== '' ? $w : '1px' ) . " {$style} " . ( $color !== '' ? $color : 'currentColor' ) . ' !important';
			} else {
				if ( $w !== '' )     { $d[] = "{$prop}-width:{$w} !important"; }
				if ( $color !== '' ) { $d[] = "{$prop}-color:{$color} !important"; }
			}

			if ( class_exists( 'FW_Option_Type_Box_Shadow' ) && isset( $st['box_shadow'] ) ) {
				$sh = FW_Option_Type_Box_Shadow::to_css( $st['box_shadow'] );
				if ( $sh !== '' ) { $d[] = "box-shadow:{$sh} !important"; }
			}
			return $d;
		};

		// Resolve a spacing 'padding' class (p-4 / pt-2 …) to a CSS declaration,
		// looking the slug's size up in the spacing scale — the same source the
		// spacing widget uses (Theme Settings → Spacing, else plugin defaults, else
		// Bootstrap 0–5). Returns e.g. 'padding:1.5rem' or null.
		$pad_scale_map = array();
		$pad_scale_src = function_exists( 'unysonplus_get_spacing_scale' ) ? unysonplus_get_spacing_scale() : array();
		if ( ! is_array( $pad_scale_src ) || empty( $pad_scale_src ) ) {
			$pad_scale_src = array(
				array( 'name' => '0', 'size' => '0' ),      array( 'name' => '1', 'size' => '0.25rem' ),
				array( 'name' => '2', 'size' => '0.5rem' ), array( 'name' => '3', 'size' => '1rem' ),
				array( 'name' => '4', 'size' => '1.5rem' ), array( 'name' => '5', 'size' => '3rem' ),
			);
		}
		foreach ( $pad_scale_src as $e ) {
			if ( ! is_array( $e ) || ! isset( $e['name'] ) || $e['name'] === '' ) { continue; }
			$sslug = strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $e['name'] ) );
			if ( $sslug !== '' ) { $pad_scale_map[ $sslug ] = isset( $e['size'] ) ? (string) $e['size'] : ''; }
		}
		$pad_prop = array( 'p' => 'padding', 'pt' => 'padding-top', 'pe' => 'padding-right', 'pb' => 'padding-bottom', 'ps' => 'padding-left' );
		$resolve_pad_class = function ( $cls ) use ( $pad_prop, $pad_scale_map ) {
			$cls = trim( (string) $cls );
			if ( $cls === '' ) { return null; }
			if ( ! preg_match( '/^(p|pt|pe|pb|ps)-(.+)$/', $cls, $m ) ) { return null; }
			$slug = strtolower( $m[2] );
			if ( ! isset( $pad_scale_map[ $slug ] ) ) { return null; }
			return $pad_prop[ $m[1] ] . ':' . $pad_scale_map[ $slug ];
		};

		// A box preset's background-pro value -> CSS background declarations (color, then
		// image over gradient). Self-contained — no shortcodes-ext dependency — so the fill
		// is emitted wherever this preset CSS is built/cached. Video is never emitted (a CSS
		// class can't host a <video>; it's disabled on the box-preset field anyway).
		$bg_pro_decls = function ( $bgv ) {
			if ( ! is_array( $bgv ) ) { return ''; }
			$out   = '';
			$cv    = ( isset( $bgv['color']['value'] ) && is_array( $bgv['color']['value'] ) ) ? $bgv['color']['value'] : array();
			$color = ! empty( $cv['custom'] ) ? (string) $cv['custom'] : ( ! empty( $cv['predefined'] ) ? (string) $cv['predefined'] : '' );
			if ( $color !== '' ) { $out .= 'background-color:' . $color . ';'; }

			$images = array();
			$img    = isset( $bgv['image']['src']['url'] ) ? (string) $bgv['image']['src']['url'] : '';
			if ( $img !== '' ) { $images[] = 'url(' . $img . ')'; }
			$stops = isset( $bgv['gradient']['data']['stops'] ) ? $bgv['gradient']['data']['stops'] : null;
			if ( is_array( $stops ) && count( $stops ) >= 2 && class_exists( 'FW_Option_Type_Gradient_V2' ) ) {
				$grad = FW_Option_Type_Gradient_V2::to_css( $bgv['gradient']['data'] );
				if ( $grad ) { $images[] = $grad; }
			}
			if ( $images ) {
				$out .= 'background-image:' . implode( ', ', $images ) . ';';
				if ( $img !== '' ) {
					$pos  = isset( $bgv['image']['position'] )   ? (string) $bgv['image']['position']   : 'center center';
					$rep  = isset( $bgv['image']['repeat'] )     ? (string) $bgv['image']['repeat']     : 'no-repeat';
					$att  = isset( $bgv['image']['attachment'] ) ? (string) $bgv['image']['attachment'] : 'scroll';
					$ssel = isset( $bgv['image']['size']['selected'] ) ? (string) $bgv['image']['size']['selected'] : 'cover';
					$size = ( $ssel === 'custom' ) ? ( isset( $bgv['image']['size']['custom'] ) ? (string) $bgv['image']['size']['custom'] : 'auto' ) : $ssel;
					if ( $pos )  { $out .= 'background-position:' . $pos . ';'; }
					if ( $rep )  { $out .= 'background-repeat:' . $rep . ';'; }
					if ( $att )  { $out .= 'background-attachment:' . $att . ';'; }
					if ( $size ) { $out .= 'background-size:' . $size . ';'; }
				}
			}
			return $out;
		};

		if ( is_array( $border_presets ) ) {
			foreach ( $border_presets as $bp ) {
				if ( ! is_array( $bp ) || empty( $bp['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $bp['id'] );
				if ( $id === '' ) { continue; }
				$slug = isset( $border_slug_map[ $id ] ) ? $border_slug_map[ $id ] : $id;

				$states = isset( $bp['states'] ) && is_array( $bp['states'] ) ? $bp['states'] : array();
				$def    = isset( $states['default'] ) && is_array( $states['default'] ) ? $states['default'] : array();
				$hover  = isset( $states['hover'] )   && is_array( $states['hover'] )   ? $states['hover']   : array();

				$sides      = isset( $bp['border_sides'] ) ? (string) $bp['border_sides'] : 'all';
				$transition = isset( $bp['transition'] ) ? trim( (string) $bp['transition'] ) : '';

				/* ---- base: .boxp-{slug} (radius + transition + default state) ---- */
				$base   = array();
				$radius = ( isset( $bp['border_radius'] ) && class_exists( 'FW_Option_Type_Unit_Input' ) )
					? FW_Option_Type_Unit_Input::to_string( $bp['border_radius'] )
					: ( isset( $bp['border_radius'] ) && ! is_array( $bp['border_radius'] ) ? (string) $bp['border_radius'] : '' );
				$radius = $len( $radius );
				if ( $radius !== '' ) { $base[] = "border-radius:{$radius} !important"; }

				// Padding (spacing value, mode 'padding') -> per-side declarations.
				// Emitted WITHOUT !important on purpose: the column's own Margin &
				// Padding (Bootstrap `.p-*` utilities, which ARE !important) then
				// overrides the preset's padding whenever set, while the preset still
				// supplies a sensible default when it isn't. 'all' (shorthand) is
				// emitted first so a per-side value overrides it.
				$pad_tree = ( isset( $bp['padding']['padding'] ) && is_array( $bp['padding']['padding'] ) ) ? $bp['padding']['padding'] : array();
				foreach ( array( 'all', 'top', 'right', 'bottom', 'left' ) as $slot ) {
					$dec = $resolve_pad_class( isset( $pad_tree[ $slot ] ) ? $pad_tree[ $slot ] : '' );
					if ( $dec !== null ) { $base[] = $dec; }
				}

				if ( $transition !== '' ) {
					$tv     = preg_match( '/^[0-9.]+$/', $transition ) ? $transition . 'ms' : $transition;
					$base[] = "transition:all {$tv} ease !important";
				}
				$base = array_merge( $base, $border_state_decls( $def, $sides ) );

				// Default-state background fill (box preset) — color/gradient/image. Emitted
				// WITHOUT !important, like padding: a default fill the element can override.
				if ( isset( $def['background'] ) ) {
					$bg_decls = rtrim( $bg_pro_decls( $def['background'] ), ';' );
					if ( $bg_decls !== '' ) { $base[] = $bg_decls; }
				}

				if ( $base ) { $utility_rules[ ".boxp-{$slug}" ] = implode( ';', $base ) . ';'; }

				/* ---- hover diffs (border + background) ---- */
				$hov = $border_state_decls( $hover, $sides );
				if ( isset( $hover['background'] ) ) {
					$bg_decls = rtrim( $bg_pro_decls( $hover['background'] ), ';' );
					if ( $bg_decls !== '' ) { $hov[] = $bg_decls; }
				}
				if ( $hov ) { $utility_rules[ ".boxp-{$slug}:hover" ] = implode( ';', $hov ) . ';'; }

				/* ---- structured hover effects (Box Style → Hover Effects) ---- */
				// A curated set of named advanced hovers layered ON TOP of the Hover
				// state's border/shadow diffs — Lift · Zoom Media · Tilt · Glow · Shine.
				// Emitted as a raw block (not $utility_rules) so combined transforms, the
				// Shine ::before pseudo, and a reduced-motion guard all fit in one place;
				// it sits AFTER the flat rules in the output, so it composes/overrides
				// cleanly. Value is a multi-select array (tolerates a legacy scalar).
				$fx = isset( $bp['hover_fx'] ) ? $bp['hover_fx'] : array();
				if ( is_string( $fx ) ) { $fx = ( $fx === '' ) ? array() : array( $fx ); }
				if ( ! is_array( $fx ) ) { $fx = array(); }
				$fx = array_values( array_filter( array_map( 'strval', $fx ) ) );
				if ( $fx ) {
					$sel   = ".boxp-{$slug}";
					$block = '';

					// base needs relative + clip for Shine / Zoom Media.
					$base_extra = array();
					if ( in_array( 'shine', $fx, true ) || in_array( 'zoom', $fx, true ) ) {
						$base_extra[] = 'position:relative';
						$base_extra[] = 'overflow:hidden';
					}
					if ( $base_extra ) { $block .= "{$sel}{" . implode( ';', $base_extra ) . ';}'; }

					// Lift + Tilt compose into a single box transform (perspective first).
					$tf = array();
					if ( in_array( 'tilt', $fx, true ) ) { $tf[] = 'perspective(600px)'; }
					if ( in_array( 'lift', $fx, true ) ) { $tf[] = 'translateY(-6px)'; }
					if ( in_array( 'tilt', $fx, true ) ) { $tf[] = 'rotateX(4deg)'; }
					if ( $tf ) { $block .= "{$sel}:hover{transform:" . implode( ' ', $tf ) . ' !important;}'; }

					// Glow — merge with the preset's own hover (else default) shadow so it
					// isn't clobbered; derive the halo color from the hover/default border
					// color, falling back to the brand blue.
					if ( in_array( 'glow', $fx, true ) ) {
						$shadow_css = '';
						if ( class_exists( 'FW_Option_Type_Box_Shadow' ) ) {
							if ( isset( $hover['box_shadow'] ) ) { $shadow_css = FW_Option_Type_Box_Shadow::to_css( $hover['box_shadow'] ); }
							if ( $shadow_css === '' && isset( $def['box_shadow'] ) ) { $shadow_css = FW_Option_Type_Box_Shadow::to_css( $def['box_shadow'] ); }
						}
						$glow_color = $resolve_btn_color( isset( $hover['border_color'] ) ? $hover['border_color'] : ( isset( $def['border_color'] ) ? $def['border_color'] : '' ) );
						if ( $glow_color === '' ) { $glow_color = 'rgba(47,116,230,0.45)'; }
						$glow     = '0 0 24px ' . $glow_color;
						$combined = ( $shadow_css !== '' ) ? ( $shadow_css . ', ' . $glow ) : $glow;
						$block   .= "{$sel}:hover{box-shadow:{$combined} !important;}";
					}

					// Zoom Media — scale an inner image/video; the box (overflow:hidden) clips.
					if ( in_array( 'zoom', $fx, true ) ) {
						$m      = "{$sel} img,{$sel} video,{$sel} .wp-post-image";
						$block .= "{$m}{transition:transform .5s ease;transform-origin:center center;}";
						$block .= "{$sel}:hover img,{$sel}:hover video,{$sel}:hover .wp-post-image{transform:scale(1.06) !important;}";
					}

					// Shine — a diagonal sheen sweeps across the box on hover.
					if ( in_array( 'shine', $fx, true ) ) {
						$block .= "{$sel}::before{content:'';position:absolute;top:0;left:-75%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,0.35),transparent);transform:skewX(-20deg);pointer-events:none;transition:left .6s ease;z-index:2;}";
						$block .= "{$sel}:hover::before{left:125%;}";
					}

					// Reduced-motion — neutralize every fx.
					$block .= "@media (prefers-reduced-motion:reduce){{$sel},{$sel}:hover,{$sel} img,{$sel} video,{$sel} .wp-post-image{transition:none !important;transform:none !important;}{$sel}::before{display:none !important;}}";

					$button_extra_css .= "\n" . $block;
				}

				/* ---- freeform custom CSS ---- */
				$custom_css = isset( $bp['custom_css'] ) ? (string) $bp['custom_css'] : '';
				if ( trim( $custom_css ) !== '' ) {
					$custom_css = preg_replace( '#</?(style|script)[^>]*>#i', '', $custom_css );
					$custom_css = str_replace( array( '<', '>' ), '', $custom_css );
					$button_extra_css .= "\n" . str_replace( '{{SELECTOR}}', ".boxp-{$slug}", $custom_css );
				}
			}
		}

			// --- Section Style presets -> .section--{slug} rules (a reusable section
			// "skin", Theme Settings → Components → Section Styles). Background is a
			// background-pro value ($bg_pro_decls); text/heading/link/border colors are
			// compact-picker values ($resolve_btn_color); border_width/radius are unit-
			// input; padding is a spacing value (mode 'padding', $resolve_pad_class). No
			// !important, so a section's own one-off Background / Spacing still wins. The
			// three seeded defaults reproduce the old hardcoded .section--alt|light|dark.
			$section_styles   = function_exists( 'unysonplus_get_section_style_presets' )     ? unysonplus_get_section_style_presets()     : array();
			$section_slug_map = function_exists( 'unysonplus_section_style_preset_slug_map' ) ? unysonplus_section_style_preset_slug_map() : array();

			$unit_len = function ( $u ) use ( $len ) {
				// A unit-input value can arrive as a JSON STRING ('{"value":"1","unit":"px"}')
				// when it rode inside a multi-inline row saved before that control decoded
				// its children. Decode it so the length resolves instead of leaking JSON.
				if ( is_string( $u ) ) {
					$t = trim( $u );
					if ( isset( $t[0] ) && $t[0] === '{' ) {
						$d = json_decode( $t, true );
						if ( is_array( $d ) && ( isset( $d['value'] ) || isset( $d['unit'] ) ) ) { $u = $d; }
					}
				}
				if ( is_array( $u ) && class_exists( 'FW_Option_Type_Unit_Input' ) ) {
					return $len( FW_Option_Type_Unit_Input::to_string( $u ) );
				}
				return $len( is_array( $u ) ? '' : (string) $u );
			};

			if ( is_array( $section_styles ) ) {
				foreach ( $section_styles as $sp ) {
					if ( ! is_array( $sp ) || empty( $sp['id'] ) ) { continue; }
					$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $sp['id'] );
					if ( $id === '' ) { continue; }
					$slug = isset( $section_slug_map[ $id ] ) ? $section_slug_map[ $id ] : $id;
					$sel  = ".section--{$slug}";

					$base = array();
					if ( isset( $sp['background'] ) ) {
						$bg_decls = rtrim( $bg_pro_decls( $sp['background'] ), ';' );
						if ( $bg_decls !== '' ) { $base[] = $bg_decls; }
					}
					$tc = $resolve_btn_color( isset( $sp['text_color'] ) ? $sp['text_color'] : '' );
					if ( $tc !== '' ) { $base[] = 'color:' . $tc; }
					// Border is the combined multi-inline row { width, style, color } applied to
					// the edge(s) chosen in Border Sides at the reach set by Border Extent;
					// tolerate the legacy flat border_style/width/color too. Emitted only when
					// a style is chosen (None = no border), as before.
					$bd     = ( isset( $sp['border'] ) && is_array( $sp['border'] ) ) ? $sp['border'] : array();
					$bstyle = isset( $bd['style'] ) ? (string) $bd['style'] : ( isset( $sp['border_style'] ) ? (string) $sp['border_style'] : '' );
					if ( $bstyle !== '' ) {
						$bw_src = array_key_exists( 'width', $bd ) ? $bd['width'] : ( isset( $sp['border_width'] ) ? $sp['border_width'] : '' );
						$bc_src = array_key_exists( 'color', $bd ) ? $bd['color'] : ( isset( $sp['border_color'] ) ? $sp['border_color'] : '' );
						$bw   = $unit_len( $bw_src );
						$bc   = $resolve_btn_color( $bc_src );
						$bval = ( $bw !== '' ? $bw : '1px' ) . ' ' . $bstyle . ' ' . ( $bc !== '' ? $bc : 'currentColor' );

						// Sides: array of top/right/bottom/left. Legacy presets (no border_sides)
						// = all four (identical to the old all-around border). Tolerate the legacy
						// single-select strings ('top'|'bottom'|'both'). An explicit empty array
						// = no border.
						$sides_raw = array_key_exists( 'border_sides', $sp ) ? $sp['border_sides'] : null;
						$sides     = array();
						if ( is_array( $sides_raw ) ) {
							foreach ( array( 'top', 'right', 'bottom', 'left' ) as $s ) {
								if ( in_array( $s, $sides_raw, true ) ) { $sides[] = $s; }
							}
						} elseif ( is_string( $sides_raw ) && $sides_raw !== '' ) {
							$sides = ( $sides_raw === 'both' )
								? array( 'top', 'bottom' )
								: ( in_array( $sides_raw, array( 'top', 'right', 'bottom', 'left' ), true ) ? array( $sides_raw ) : array() );
						} elseif ( $sides_raw === null ) {
							$sides = array( 'top', 'right', 'bottom', 'left' );
						}

						$do_top   = in_array( 'top', $sides, true );
						$do_bot   = in_array( 'bottom', $sides, true );
						$do_left  = in_array( 'left', $sides, true );
						$do_right = in_array( 'right', $sides, true );

						// Left/right are vertical — always real borders (Extent caps only the
						// horizontal reach).
						if ( $do_left )  { $base[] = 'border-left:' . $bval; }
						if ( $do_right ) { $base[] = 'border-right:' . $bval; }

						// Extent (top/bottom only): full = edge to edge; container/custom = a
						// centered pseudo-element capped at the max width.
						$ext   = ( isset( $sp['border_extent'] ) && is_array( $sp['border_extent'] ) ) ? $sp['border_extent'] : array();
						$emode = isset( $ext['mode'] ) ? (string) $ext['mode'] : 'full';
						$emax  = '';
						if ( $emode === 'container' ) {
							$emax = 'var(--container-max-desktop, var(--site-max-width, 1170px))';
						} elseif ( $emode === 'custom' ) {
							$emax = isset( $ext['custom']['border_extent_width'] ) ? $unit_len( $ext['custom']['border_extent_width'] ) : '';
						}

						if ( $emode === 'full' || $emax === '' ) {
							if ( $do_top ) { $base[] = 'border-top:' . $bval; }
							if ( $do_bot ) { $base[] = 'border-bottom:' . $bval; }
						} else {
							if ( $do_top || $do_bot ) { $base[] = 'position:relative'; }
							$pd = 'content:"";display:block;max-width:' . $emax . ';margin-inline:auto;border-top:' . $bval;
							if ( $do_top ) { $utility_rules[ "{$sel}::before" ] = $pd . ';'; }
							if ( $do_bot ) { $utility_rules[ "{$sel}::after" ]  = $pd . ';'; }
						}
					}
					$br = $unit_len( isset( $sp['border_radius'] ) ? $sp['border_radius'] : '' );
					if ( $br !== '' ) { $base[] = 'border-radius:' . $br; }
					$pad_tree = ( isset( $sp['padding']['padding'] ) && is_array( $sp['padding']['padding'] ) ) ? $sp['padding']['padding'] : array();
					foreach ( array( 'all', 'top', 'right', 'bottom', 'left' ) as $slot ) {
						$dec = $resolve_pad_class( isset( $pad_tree[ $slot ] ) ? $pad_tree[ $slot ] : '' );
						if ( $dec !== null ) { $base[] = $dec; }
					}
					if ( $base ) { $utility_rules[ $sel ] = implode( ';', $base ) . ';'; }

					$lc = $resolve_btn_color( isset( $sp['link_color'] ) ? $sp['link_color'] : '' );
					if ( $lc !== '' ) { $utility_rules[ "{$sel} a" ] = 'color:' . $lc . ';'; }
					$hc = $resolve_btn_color( isset( $sp['heading_color'] ) ? $sp['heading_color'] : '' );
					if ( $hc !== '' ) { $utility_rules[ "{$sel} :is(h1,h2,h3,h4,h5,h6)" ] = 'color:' . $hc . ';'; }
				}
			}

		// --- Table presets -> .tbl-{slug} rules (applied to the table wrapper) ---
		// Each saved preset is a reusable table look (Theme Settings → Components →
		// Tables). Colors are compact-picker values resolved via $resolve_btn_color;
		// widths/radius/font-size are unit-input; box_shadow rides
		// FW_Option_Type_Box_Shadow::to_css. The class sits on the .table wrapper and
		// styles the inner <table> + thead/tbody/tfoot/caption via descendant rules.
		$table_presets  = function_exists( 'unysonplus_get_table_presets' )     ? unysonplus_get_table_presets()     : array();
		$table_slug_map = function_exists( 'unysonplus_table_preset_slug_map' ) ? unysonplus_table_preset_slug_map() : array();

		$tp_len = function ( $u ) use ( $len ) {
			if ( is_array( $u ) && class_exists( 'FW_Option_Type_Unit_Input' ) ) {
				return $len( FW_Option_Type_Unit_Input::to_string( $u ) );
			}
			return $len( is_array( $u ) ? '' : (string) $u );
		};
		// "{w} {style} {color}" border shorthand (or '' when style is empty).
		$tp_border = function ( $style, $width, $color ) use ( $tp_len, $resolve_btn_color ) {
			$style = (string) $style;
			if ( $style === '' ) { return ''; }
			$w = $tp_len( $width );
			$c = $resolve_btn_color( $color );
			return ( $w !== '' ? $w : '1px' ) . ' ' . $style . ' ' . ( $c !== '' ? $c : 'currentColor' );
		};

		if ( is_array( $table_presets ) ) {
			foreach ( $table_presets as $tp ) {
				if ( ! is_array( $tp ) || empty( $tp['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $tp['id'] );
				if ( $id === '' ) { continue; }
				$slug = isset( $table_slug_map[ $id ] ) ? $table_slug_map[ $id ] : $id;
				$sel  = ".tbl-{$slug}";

				$sec = ( isset( $tp['sections'] ) && is_array( $tp['sections'] ) ) ? $tp['sections'] : array();
				$get = function ( $name ) use ( $sec ) { return isset( $sec[ $name ] ) && is_array( $sec[ $name ] ) ? $sec[ $name ] : array(); };
				$header = $get( 'header' ); $body = $get( 'body' ); $striped = $get( 'striped' );
				$hover = $get( 'hover' ); $footer = $get( 'footer' ); $caption = $get( 'caption' );

				/* ---- wrapper frame: border + radius (clipped) + shadow ---- */
				$frame = array();
				$ob = $tp_border( $tp['outer_border_style'] ?? '', $tp['outer_border_width'] ?? '', $tp['outer_border_color'] ?? '' );
				if ( $ob !== '' ) { $frame[] = "border:{$ob} !important"; }
				$radius = $tp_len( $tp['border_radius'] ?? '' );
				if ( $radius !== '' ) { $frame[] = "border-radius:{$radius} !important"; $frame[] = 'overflow:hidden !important'; }
				if ( class_exists( 'FW_Option_Type_Box_Shadow' ) && isset( $tp['outer_shadow'] ) ) {
					$sh = FW_Option_Type_Box_Shadow::to_css( $tp['outer_shadow'] );
					if ( $sh !== '' ) { $frame[] = "box-shadow:{$sh} !important"; }
				}
				if ( $frame ) { $utility_rules[ $sel ] = implode( ';', $frame ) . ';'; }

				/* ---- inner table ---- */
				$tbl = array( 'width:100% !important', 'border-collapse:collapse !important', 'margin:0 !important' );
				$fs  = $tp_len( $tp['cell_font_size'] ?? '' );
				if ( $fs !== '' ) { $tbl[] = "font-size:{$fs} !important"; }
				$utility_rules[ "{$sel} > table" ] = implode( ';', $tbl ) . ';';

				/* ---- cell base: padding + grid lines ---- */
				$cell = array();
				$py = $tp_len( $tp['cell_padding_y'] ?? '' );
				$px = $tp_len( $tp['cell_padding_x'] ?? '' );
				if ( $py !== '' || $px !== '' ) {
					$cell[] = 'padding:' . ( $py !== '' ? $py : '0' ) . ' ' . ( $px !== '' ? $px : '0' ) . ' !important';
				}
				$grid_lines = isset( $tp['grid_lines'] ) ? (string) $tp['grid_lines'] : 'none';
				if ( $grid_lines !== 'none' ) {
					$gstyle = isset( $tp['grid_style'] ) && $tp['grid_style'] !== '' ? (string) $tp['grid_style'] : 'solid';
					$gline  = $tp_border( $gstyle, $tp['grid_width'] ?? '', $tp['grid_color'] ?? '' );
					if ( $gline !== '' ) {
						if ( $grid_lines === 'horizontal' || $grid_lines === 'both' ) { $cell[] = "border-bottom:{$gline} !important"; }
						if ( $grid_lines === 'vertical'   || $grid_lines === 'both' ) { $cell[] = "border-right:{$gline} !important"; }
					}
				}
				if ( $cell ) { $utility_rules[ "{$sel} th,{$sel} td" ] = implode( ';', $cell ) . ';'; }

				/* ---- header ---- */
				$hd = array();
				$hbg = $resolve_btn_color( $header['bg_color'] ?? '' );   if ( $hbg !== '' ) { $hd[] = "background-color:{$hbg} !important"; }
				$htc = $resolve_btn_color( $header['text_color'] ?? '' ); if ( $htc !== '' ) { $hd[] = "color:{$htc} !important"; }
				if ( ! empty( $header['font_weight'] ) )    { $hd[] = 'font-weight:' . preg_replace( '/[^a-z0-9]/i', '', (string) $header['font_weight'] ) . ' !important'; }
				if ( ! empty( $header['text_transform'] ) ) { $hd[] = 'text-transform:' . preg_replace( '/[^a-z]/i', '', (string) $header['text_transform'] ) . ' !important'; }
				$hb = $tp_border( $header['border_style'] ?? '', $header['border_width'] ?? '', $header['border_color'] ?? '' );
				if ( $hb !== '' ) { $hd[] = "border-bottom:{$hb} !important"; }
				if ( $hd ) { $utility_rules[ "{$sel} > table > thead > tr > th,{$sel} > table > thead > tr > td" ] = implode( ';', $hd ) . ';'; }

				/* ---- body ---- */
				$bd = array();
				$bbg = $resolve_btn_color( $body['bg_color'] ?? '' );   if ( $bbg !== '' ) { $bd[] = "background-color:{$bbg} !important"; }
				$btc = $resolve_btn_color( $body['text_color'] ?? '' ); if ( $btc !== '' ) { $bd[] = "color:{$btc} !important"; }
				$transition = isset( $tp['transition'] ) ? trim( (string) $tp['transition'] ) : '';
				if ( $transition !== '' ) {
					$tv   = preg_match( '/^[0-9.]+$/', $transition ) ? $transition . 'ms' : $transition;
					$bd[] = "transition:background-color {$tv} ease,color {$tv} ease";
				}
				if ( $bd ) { $utility_rules[ "{$sel} > table > tbody > tr > td" ] = implode( ';', $bd ) . ';'; }

				/* ---- striped ---- */
				if ( isset( $striped['enabled'] ) && $striped['enabled'] === 'yes' ) {
					$sbg = $resolve_btn_color( $striped['bg_color'] ?? '' );
					if ( $sbg !== '' ) { $utility_rules[ "{$sel} > table > tbody > tr:nth-child(2n) > td" ] = "background-color:{$sbg} !important;"; }
				}

				/* ---- row hover ---- */
				$hv = array();
				$vbg = $resolve_btn_color( $hover['bg_color'] ?? '' );   if ( $vbg !== '' ) { $hv[] = "background-color:{$vbg} !important"; }
				$vtc = $resolve_btn_color( $hover['text_color'] ?? '' ); if ( $vtc !== '' ) { $hv[] = "color:{$vtc} !important"; }
				if ( $hv ) { $utility_rules[ "{$sel} > table > tbody > tr:hover > td" ] = implode( ';', $hv ) . ';'; }

				/* ---- footer ---- */
				$ft = array();
				$fbg = $resolve_btn_color( $footer['bg_color'] ?? '' );   if ( $fbg !== '' ) { $ft[] = "background-color:{$fbg} !important"; }
				$ftc = $resolve_btn_color( $footer['text_color'] ?? '' ); if ( $ftc !== '' ) { $ft[] = "color:{$ftc} !important"; }
				if ( ! empty( $footer['font_weight'] ) ) { $ft[] = 'font-weight:' . preg_replace( '/[^a-z0-9]/i', '', (string) $footer['font_weight'] ) . ' !important'; }
				$fb = $tp_border( $footer['border_style'] ?? '', $footer['border_width'] ?? '', $footer['border_color'] ?? '' );
				if ( $fb !== '' ) { $ft[] = "border-top:{$fb} !important"; }
				if ( $ft ) { $utility_rules[ "{$sel} > table > tfoot > tr > th,{$sel} > table > tfoot > tr > td" ] = implode( ';', $ft ) . ';'; }

				/* ---- caption ---- */
				$cp = array();
				$cc = $resolve_btn_color( $caption['color'] ?? '' ); if ( $cc !== '' ) { $cp[] = "color:{$cc} !important"; }
				$cfs = $tp_len( $caption['font_size'] ?? '' );       if ( $cfs !== '' ) { $cp[] = "font-size:{$cfs} !important"; }
				if ( ! empty( $caption['font_style'] ) )             { $cp[] = 'font-style:' . preg_replace( '/[^a-z]/i', '', (string) $caption['font_style'] ) . ' !important'; }
				if ( $cp ) { $utility_rules[ "{$sel} caption" ] = implode( ';', $cp ) . ';'; }

				/* ---- freeform custom CSS ---- */
				$custom_css = isset( $tp['custom_css'] ) ? (string) $tp['custom_css'] : '';
				if ( trim( $custom_css ) !== '' ) {
					$custom_css = preg_replace( '#</?(style|script)[^>]*>#i', '', $custom_css );
					$custom_css = str_replace( array( '<', '>' ), '', $custom_css );
					$button_extra_css .= "\n" . str_replace( '{{SELECTOR}}', $sel, $custom_css );
				}
			}
		}

		// --- Custom hover animations -> .btnfx-c-{slug} rules ---
		// User-authored effects (Theme Settings → Buttons → Hover Animations). Their
		// CSS uses {{BTN}}/{{ANIM}} tokens, is scrubbed of markup/script tricks, and is
		// appended to the preset stylesheet (loaded front end + admin), so they appear
		// in the Button shortcode's Hover Animation dropdown next to the built-ins.
		$custom_anims  = function_exists( 'unysonplus_get_custom_hover_animations' ) ? unysonplus_get_custom_hover_animations() : array();
		$anim_slug_map = function_exists( 'unysonplus_custom_hover_animation_slug_map' ) ? unysonplus_custom_hover_animation_slug_map() : array();
		if ( is_array( $custom_anims ) ) {
			foreach ( $custom_anims as $ca ) {
				if ( ! is_array( $ca ) || empty( $ca['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $ca['id'] );
				if ( $id === '' || ! isset( $anim_slug_map[ $id ] ) ) { continue; }
				$slug = $anim_slug_map[ $id ];

				$anim_css = isset( $ca['css'] ) ? (string) $ca['css'] : '';
				if ( trim( $anim_css ) === '' ) { continue; }

				// Scrub: no markup, no script/import/expression/protocol tricks.
				$anim_css = preg_replace( '#</?(style|script)[^>]*>#i', '', $anim_css );
				$anim_css = str_replace( array( '<', '>' ), '', $anim_css );
				$anim_css = preg_replace( '/@import\b/i', '', $anim_css );
				$anim_css = preg_replace( '/javascript\s*:/i', '', $anim_css );
				$anim_css = preg_replace( '/expression\s*\(/i', '', $anim_css );

				// Tokens -> concrete, per-entry namespaced selectors.
				$anim_css = str_replace(
					array( '{{BTN}}', '{{ANIM}}' ),
					array( ".btnfx-c-{$slug}", "btnfxc-{$slug}" ),
					$anim_css
				);

				$button_extra_css .= "\n" . $anim_css;
			}
		}

		// --- Background Patterns -> scoped `.pattern-{slug}` blocks (Theme Settings →
		// Components → Background Patterns). Each saved pattern's pasted CSS is scoped to
		// `.pattern-{slug}` and its @keyframes / SVG-filter ids namespaced by
		// unysonplus_pattern_scope(), then appended RAW (it's a full block — its own rules +
		// @keyframes + @media — not a single selector→decls entry, so it can't go through
		// $utility_rules). The pattern's HTML + SVG defs are emitted by the render layer
		// (Section / Container / body); here we only emit the CSS so the classes exist. ---
		$patterns  = function_exists( 'unysonplus_get_pattern_presets' )     ? unysonplus_get_pattern_presets()     : array();
		$pat_slugs = function_exists( 'unysonplus_pattern_preset_slug_map' ) ? unysonplus_pattern_preset_slug_map() : array();
		if ( is_array( $patterns ) && ! empty( $patterns ) && function_exists( 'unysonplus_pattern_scope' ) ) {
			// Base layer mechanics (emitted once): the pattern renders as an aria-hidden
			// `.pattern-layer` behind the host's content. The host carries `.upw-has-pattern`
			// (Section / Container / body wrapper); its non-layer children ride above at z-index 1.
			$button_extra_css .= "\n.pattern-layer{position:absolute;inset:0;z-index:0;overflow:hidden;pointer-events:none;}"
				. "\n.pattern-layer--fixed{position:fixed;z-index:-1;}" // whole-site background (behind everything)
				. "\n.upw-has-pattern{position:relative;}"
				. "\n.upw-has-pattern>:not(.pattern-layer){position:relative;z-index:1;}";

			foreach ( $patterns as $p ) {
				if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
				if ( $id === '' || ! isset( $pat_slugs[ $id ] ) ) { continue; }

				$css_in = isset( $p['css'] ) ? (string) $p['css'] : '';
				if ( trim( $css_in ) === '' ) { continue; }

				$scoped = unysonplus_pattern_scope(
					isset( $p['html'] ) ? (string) $p['html'] : '',
					$css_in,
					$pat_slugs[ $id ]
				);
				if ( ! empty( $scoped['css'] ) ) {
					$button_extra_css .= "\n" . trim( $scoped['css'] );
				}
			}
		}

		// --- Image Styles -> `.imgs-{slug}` token bundles + one shared `.imgs-wrap` base ---
		// Token-bundle model: each preset emits ONLY CSS custom properties; the single
		// base rule (emitted once) consumes them so radius / mask / filter / scrim apply
		// uniformly to any element's image wrapper. A power user overrides one var in an
		// element's Custom CSS. Angular masks use clip-path; organic masks use a
		// self-contained SVG data-URI (CSP-safe); duotone is a grayscale image + a
		// mix-blend `color` tint layer.
		$image_styles = function_exists( 'unysonplus_get_image_style_presets' )      ? unysonplus_get_image_style_presets()      : array();
		$imgs_slugs   = function_exists( 'unysonplus_image_style_preset_slug_map' )  ? unysonplus_image_style_preset_slug_map()  : array();
		if ( is_array( $image_styles ) && ! empty( $image_styles ) ) {
			$imgs_aspect  = array( '1-1' => '1/1', '4-3' => '4/3', '3-2' => '3/2', '16-9' => '16/9', '3-4' => '3/4' );
			$imgs_filter  = array(
				'grayscale' => 'grayscale(1)', 'sepia' => 'sepia(.7)', 'contrast' => 'contrast(1.4)',
				'saturate'  => 'saturate(1.8)', 'blur' => 'blur(2px)', 'duotone' => 'grayscale(1) contrast(1.05)',
			);
			// Shape / mask now comes from the SHARED mask library (also used by Image Box).
			$imgs_masklib = function_exists( 'sc_image_mask_library' ) ? sc_image_mask_library() : array();
			// Shared base rule (once). Class on the image WRAPPER; the <img> inside reads the
			// inherited custom props; ::before = duotone tint, ::after = scrim; isolation
			// contains the blend so it can't leak to siblings.
			$button_extra_css .= "\n.imgs-wrap{position:relative;display:block;isolation:isolate;overflow:hidden;border-radius:var(--imgs-radius,0)}"
				. "\n.imgs-wrap>img,.imgs-wrap img{display:block;width:100%;height:auto;aspect-ratio:var(--imgs-aspect,auto);object-fit:cover;border-radius:var(--imgs-radius,0);filter:var(--imgs-filter,none);clip-path:var(--imgs-clip,none);-webkit-mask-image:var(--imgs-mask,none);mask-image:var(--imgs-mask,none);-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center}"
				. "\n.imgs-wrap::before{content:'';position:absolute;inset:0;border-radius:var(--imgs-radius,0);background:var(--imgs-duo,transparent);mix-blend-mode:color;pointer-events:none}"
				. "\n.imgs-wrap::after{content:'';position:absolute;inset:0;border-radius:var(--imgs-radius,0);background:var(--imgs-scrim,transparent);pointer-events:none}";

			foreach ( $image_styles as $s ) {
				if ( ! is_array( $s ) || empty( $s['id'] ) ) { continue; }
				$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $s['id'] );
				if ( $id === '' || ! isset( $imgs_slugs[ $id ] ) ) { continue; }
				$slug = $imgs_slugs[ $id ];
				$vars = array();

				$ar = isset( $s['aspect'] ) ? (string) $s['aspect'] : 'auto';
				if ( isset( $imgs_aspect[ $ar ] ) ) { $vars[] = '--imgs-aspect:' . $imgs_aspect[ $ar ]; }

				/* Shape / Mask from the shared library: radius → --imgs-radius, clip → --imgs-clip,
				   svg → --imgs-mask; a "square" shape forces --imgs-aspect:1/1 (emitted AFTER the
				   aspect field so it wins). mask=none uses the simple custom `radius` field. */
				// mask is a multi-picker { mask:'key', custom:{custom_svg,custom_clip} } — tolerate a legacy scalar.
				$mraw = isset( $s['mask'] ) ? $s['mask'] : 'none';
				$mask = is_array( $mraw ) ? ( isset( $mraw['mask'] ) ? (string) $mraw['mask'] : 'none' ) : (string) $mraw;
				$mdef = isset( $imgs_masklib[ $mask ] ) ? $imgs_masklib[ $mask ] : array( 'kind' => 'none' );
				$mask_square = false;
				if ( $mask === 'custom' ) {
					$mcustom = ( is_array( $mraw ) && isset( $mraw['custom'] ) && is_array( $mraw['custom'] ) ) ? $mraw['custom'] : array();
					$c_svg  = trim( (string) ( isset( $mcustom['custom_svg'] ) ? $mcustom['custom_svg'] : '' ) );
					$c_clip = trim( (string) ( isset( $mcustom['custom_clip'] ) ? $mcustom['custom_clip'] : '' ) );
					if ( $c_svg !== '' ) {
						if ( stripos( $c_svg, '<svg' ) !== false ) {
							$clean = function_exists( 'sc_imgbox_sanitize_svg' ) ? sc_imgbox_sanitize_svg( $c_svg ) : preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', '', $c_svg );
							if ( trim( (string) $clean ) !== '' ) { $vars[] = '--imgs-mask:url("data:image/svg+xml,' . rawurlencode( $clean ) . '")'; }
						} else {
							$u = preg_replace( '/["\'\s()]/', '', $c_svg ); // url-safe
							if ( $u !== '' ) { $vars[] = '--imgs-mask:url("' . $u . '")'; }
						}
					} elseif ( $c_clip !== '' ) {
						$clip = function_exists( 'sc_imgbox_sanitize_clip' ) ? sc_imgbox_sanitize_clip( $c_clip ) : preg_replace( '/[^a-zA-Z0-9%.,()\/\s"\'#-]/', '', $c_clip );
						if ( trim( (string) $clip ) !== '' ) { $vars[] = '--imgs-clip:' . $clip; }
					}
				} elseif ( $mdef['kind'] === 'radius' ) {
					$vars[] = '--imgs-radius:' . $mdef['value'];
					$mask_square = ! empty( $mdef['square'] );
				} elseif ( $mdef['kind'] === 'clip' ) {
					$vars[] = '--imgs-clip:' . $mdef['value'];
					$mask_square = ! empty( $mdef['square'] );
				} elseif ( $mdef['kind'] === 'svg' && ! empty( $mdef['value'] ) ) {
					$vars[] = '--imgs-mask:' . $mdef['value'];
					$mask_square = ! empty( $mdef['square'] );
				} else {
					// mask = none → simple custom corner radius, if any.
					$rad = $len( isset( $s['radius'] ) ? (string) $s['radius'] : '' );
					if ( $rad !== '' ) { $vars[] = '--imgs-radius:' . $rad; }
				}
				if ( $mask_square ) { $vars[] = '--imgs-aspect:1/1'; }

				$filter = isset( $s['filter'] ) ? (string) $s['filter'] : 'none';
				if ( isset( $imgs_filter[ $filter ] ) ) { $vars[] = '--imgs-filter:' . $imgs_filter[ $filter ]; }
				if ( $filter === 'duotone' ) {
					$duo = $resolve_btn_color( isset( $s['duo_color'] ) ? $s['duo_color'] : '' );
					if ( $duo !== '' ) { $vars[] = '--imgs-duo:' . $duo; }
				}

				$scrim = isset( $s['scrim'] ) ? (string) $s['scrim'] : 'none';
				if ( $scrim !== 'none' ) {
					$sc = $resolve_btn_color( isset( $s['scrim_color'] ) ? $s['scrim_color'] : '' );
					if ( $sc === '' ) { $sc = '#0b0b0f'; }
					$grad = array(
						'bottom' => "linear-gradient(180deg,transparent 55%,{$sc})",
						'top'    => "linear-gradient(0deg,transparent 55%,{$sc})",
						'radial' => "radial-gradient(120% 100% at 50% 100%,{$sc},transparent 60%)",
					);
					if ( isset( $grad[ $scrim ] ) ) { $vars[] = '--imgs-scrim:' . $grad[ $scrim ]; }
				}

				if ( ! empty( $vars ) ) {
					$button_extra_css .= "\n.imgs-{$slug}{" . implode( ';', $vars ) . ";}";
				}

				/* ---- freeform per-preset Custom CSS (advanced) ----
				   {{SELECTOR}} → the wrapper class .imgs-{slug}; sanitised like the Box
				   Preset custom CSS (no <style>/<script>, no raw angle brackets). */
				$imgs_css = isset( $s['custom_css'] ) ? (string) $s['custom_css'] : '';
				if ( trim( $imgs_css ) !== '' ) {
					$imgs_css = preg_replace( '#</?(style|script)[^>]*>#i', '', $imgs_css );
					$imgs_css = str_replace( array( '<', '>' ), '', $imgs_css );
					$button_extra_css .= "\n" . str_replace( '{{SELECTOR}}', ".imgs-{$slug}", $imgs_css );
				}
			}
		}

		// --- Spacing scale -> CSS variables + Bootstrap-class overrides ---
		// Emits `:root { --spacer-{slug} }` plus utility rules that override
		// Bootstrap's `.m-N` / `.p-N` / `.mt-N` etc. Our presets-css now loads
		// AFTER Bootstrap (`unysonplus_enqueue_preset_css` hooks priority 20
		// in `framework/includes/bootstrap.php`'s sibling
		// `unysonplus_enqueue_bootstrap` at priority 5), so plain `.m-N` at
		// the same specificity as Bootstrap's `.m-N` wins via source order
		// — no `:root` boost needed. We keep `!important` because user-saved
		// custom slugs (`.m-huge` etc.) need to win over any utility rule
		// regardless of cascading order from child themes or theme overrides.
		//
		// Rules are emitted in by-property order (least-specific shorthand
		// first, single-side last) so that, with equal specificity, source
		// order makes `.mt-N` reliably override `.m-M`. Matches Bootstrap's
		// own utility ordering so users get the same mental model.
		// Per-breakpoint spacing utilities (mobile-first). The base layer (no
		// infix) is emitted flat into $utility_rules and applies at all widths;
		// the responsive layers (md ≥768px, lg ≥992px) are stashed in
		// $responsive_spacing keyed by min-width and emitted inside @media blocks
		// further down. The spacing option type's per-device tabs save
		// breakpoint-infixed class names (`m-md-3`, `pt-lg-2`) that these rules
		// back. Only md/lg are emitted (the tiers the control exposes); sm/xl/xxl
		// can be added here if the UI ever grows them.
		$responsive_spacing = array();
		$spacing_scale = function_exists( 'unysonplus_get_spacing_scale' ) ? unysonplus_get_spacing_scale() : array();
		if ( is_array( $spacing_scale ) ) {
			$prop_order = array( 'm', 'mx', 'my', 'mt', 'mb', 'ms', 'me',
			                     'p', 'px', 'py', 'pt', 'pb', 'ps', 'pe' );

			// Build the ordered selector→body map for one breakpoint infix
			// ('' = base, 'md', 'lg'). Body is identical across breakpoints;
			// only the selector carries the Bootstrap infix.
			$build_spacing = function ( $infix ) use ( $spacing_scale, $prop_order, &$tokens ) {
				$buckets = array_fill_keys( $prop_order, array() );
				$seg     = ( $infix === '' ) ? '' : '-' . $infix;

				foreach ( $spacing_scale as $entry ) {
					if ( ! is_array( $entry ) ) { continue; }
					if ( ! isset( $entry['name'] ) || $entry['name'] === '' ) { continue; }
					if ( ! isset( $entry['size'] ) || $entry['size'] === '' ) { continue; }

					$slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $entry['name'] );
					if ( $slug === '' ) { continue; }
					$slug = strtolower( $slug );

					$value = $entry['size'];
					$var   = "var(--spacer-{$slug})";

					// The --spacer-{slug} token is breakpoint-independent; emit it
					// once (on the base pass) into the shared $tokens map.
					if ( $infix === '' ) {
						$tokens[ "--spacer-{$slug}" ] = $value;
					}

					// Margin
					$buckets['m'][  ".m{$seg}-{$slug}"  ] = "margin:{$var} !important;";
					$buckets['mx'][ ".mx{$seg}-{$slug}" ] = "margin-right:{$var} !important;margin-left:{$var} !important;";
					$buckets['my'][ ".my{$seg}-{$slug}" ] = "margin-top:{$var} !important;margin-bottom:{$var} !important;";
					$buckets['mt'][ ".mt{$seg}-{$slug}" ] = "margin-top:{$var} !important;";
					$buckets['mb'][ ".mb{$seg}-{$slug}" ] = "margin-bottom:{$var} !important;";
					$buckets['ms'][ ".ms{$seg}-{$slug}" ] = "margin-inline-start:{$var} !important;";
					$buckets['me'][ ".me{$seg}-{$slug}" ] = "margin-inline-end:{$var} !important;";

					// Padding
					$buckets['p'][  ".p{$seg}-{$slug}"  ] = "padding:{$var} !important;";
					$buckets['px'][ ".px{$seg}-{$slug}" ] = "padding-right:{$var} !important;padding-left:{$var} !important;";
					$buckets['py'][ ".py{$seg}-{$slug}" ] = "padding-top:{$var} !important;padding-bottom:{$var} !important;";
					$buckets['pt'][ ".pt{$seg}-{$slug}" ] = "padding-top:{$var} !important;";
					$buckets['pb'][ ".pb{$seg}-{$slug}" ] = "padding-bottom:{$var} !important;";
					$buckets['ps'][ ".ps{$seg}-{$slug}" ] = "padding-inline-start:{$var} !important;";
					$buckets['pe'][ ".pe{$seg}-{$slug}" ] = "padding-inline-end:{$var} !important;";
				}

				$rules = array();
				foreach ( $prop_order as $prop ) {
					foreach ( $buckets[ $prop ] as $sel => $body ) {
						$rules[ $sel ] = $body;
					}
				}
				return $rules;
			};

			// Base layer → flat utility rules (all widths).
			foreach ( $build_spacing( '' ) as $sel => $body ) {
				$utility_rules[ $sel ] = $body;
			}

			// Responsive layers → @media (min-width) blocks. Keyed by min-width
			// so the emitter can sort ascending (md before lg) — at equal
			// specificity + !important, the later (larger) breakpoint wins where
			// both queries match, giving the correct mobile-first cascade.
			$responsive_spacing[768] = $build_spacing( 'md' );
			$responsive_spacing[992] = $build_spacing( 'lg' );
		}

		// --- Gap scale -> CSS variables + row gutter override machinery ---
		// All three layers route through Bootstrap's `--bs-gutter-x` /
		// `--bs-gutter-y` custom properties so that Bootstrap's own
		// `.row > *` padding calc (and our `.fw-row > *` mirror in
		// `framework/extensions/builder/static/css/frontend-grid.css`) reads
		// the overridden values automatically — we never touch column
		// padding rules directly.
		//
		// Presets-css loads AFTER Bootstrap (priority 20 vs Bootstrap's 5),
		// so the cascade is decided by:
		//
		//   1. Site default (`.row, .fw-row`, specificity 0,1,0): beats
		//      Bootstrap's own `.row` on source order.
		//
		//   2. Per-section modifier (`.section--gap-{slug} .row`,
		//      specificity 0,2,0): beats the site default on specificity.
		//
		//   3. Per-row utility (`.g-{slug}` etc., specificity 0,1,0 + !important):
		//      `!important` beats the per-section modifier's higher
		//      specificity — this is the only layer that needs !important
		//      because users add `.g-N` to one specific row to override the
		//      section default it sits inside.
		$gap_scale = function_exists( 'unysonplus_get_gap_scale' ) ? unysonplus_get_gap_scale() : array();
		if ( is_array( $gap_scale ) && ! empty( $gap_scale ) ) {
			$default_gap   = function_exists( 'unysonplus_get_default_gap' )   ? unysonplus_get_default_gap()   : '';
			$default_gap_x = function_exists( 'unysonplus_get_default_gap_x' ) ? unysonplus_get_default_gap_x() : '';
			$default_gap_y = function_exists( 'unysonplus_get_default_gap_y' ) ? unysonplus_get_default_gap_y() : '';

			// Sanitize the picked slugs so a tampered DB value can't inject
			// arbitrary chars into a selector or var() reference.
			$default_gap   = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $default_gap );
			$default_gap_x = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $default_gap_x );
			$default_gap_y = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $default_gap_y );

			// Tokens + utility / modifier classes per scale entry.
			$valid_slugs = array();
			foreach ( $gap_scale as $entry ) {
				if ( ! is_array( $entry ) ) { continue; }
				if ( ! isset( $entry['name'] ) || $entry['name'] === '' ) { continue; }
				if ( ! isset( $entry['size'] ) || $entry['size'] === '' ) { continue; }

				$slug = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $entry['name'] );
				if ( $slug === '' ) { continue; }
				$slug = strtolower( $slug );

				$value = $entry['size'];
				$var   = "var(--gap-{$slug})";

				$tokens[ "--gap-{$slug}" ] = $value;
				$valid_slugs[ $slug ]      = true;

				// Per-row utility classes (override Bootstrap's stock `.g-N` AND
				// our per-section modifier). `!important` because the per-section
				// modifier below has higher specificity (0,2,0 > 0,1,0); without
				// !important the per-section rule would win on a row inside a
				// section, even though "user added .g-N to this specific row"
				// is the more intentional override.
				$utility_rules[ ".g-{$slug}" ]  = "--bs-gutter-x:{$var} !important;--bs-gutter-y:{$var} !important;";
				$utility_rules[ ".gx-{$slug}" ] = "--bs-gutter-x:{$var} !important;";
				$utility_rules[ ".gy-{$slug}" ] = "--bs-gutter-y:{$var} !important;";

				// Flexbox `gap:` utilities for the column / flexbox CONTENT gap (the space
				// between a column's stacked/inline elements). Distinct from the row-gutter
				// .g-* above and from Bootstrap's fixed .gap-N — these resolve to the theme
				// Gap-Scale var. Per-breakpoint, mobile-first: base applies at all widths;
				// md/lg go into the responsive @media blocks via $responsive_spacing.
				$utility_rules[ ".sc-cgap-{$slug}" ] = "gap:{$var} !important;";
				if ( ! isset( $responsive_spacing[768] ) ) { $responsive_spacing[768] = array(); }
				if ( ! isset( $responsive_spacing[992] ) ) { $responsive_spacing[992] = array(); }
				$responsive_spacing[768][ ".sc-cgap-md-{$slug}" ] = "gap:{$var} !important;";
				$responsive_spacing[992][ ".sc-cgap-lg-{$slug}" ] = "gap:{$var} !important;";

				// Per-section modifier classes — scope the gap to every .row
				// inside a section. No !important: specificity (0,2,0) beats
				// the site default's (0,1,0), and the per-row utility above
				// uses !important to win over us when needed.
				$utility_rules[ ".section--gap-{$slug} .row, .section--gap-{$slug} .fw-row" ]     = "--bs-gutter-x:{$var};--bs-gutter-y:{$var};";
				$utility_rules[ ".section--gap-x-{$slug} .row, .section--gap-x-{$slug} .fw-row" ] = "--bs-gutter-x:{$var};";
				$utility_rules[ ".section--gap-y-{$slug} .row, .section--gap-y-{$slug} .fw-row" ] = "--bs-gutter-y:{$var};";

				// Per-device overrides for the Section Gap (mobile-first). Emitted inside the
				// md / lg @media blocks (via $responsive_spacing) so a section can set a
				// different gap per breakpoint; they come after the base rule above, so at
				// their breakpoint they win on source order without needing !important.
				if ( ! isset( $responsive_spacing[768] ) ) { $responsive_spacing[768] = array(); }
				if ( ! isset( $responsive_spacing[992] ) ) { $responsive_spacing[992] = array(); }
				$responsive_spacing[768][ ".section--gap-md-{$slug} .row, .section--gap-md-{$slug} .fw-row" ] = "--bs-gutter-x:{$var};--bs-gutter-y:{$var};";
				$responsive_spacing[992][ ".section--gap-lg-{$slug} .row, .section--gap-lg-{$slug} .fw-row" ] = "--bs-gutter-x:{$var};--bs-gutter-y:{$var};";
				// Per-device Gap X / Y overrides (single-axis) — mirror the both-axis md/lg above.
				$responsive_spacing[768][ ".section--gap-x-md-{$slug} .row, .section--gap-x-md-{$slug} .fw-row" ] = "--bs-gutter-x:{$var};";
				$responsive_spacing[992][ ".section--gap-x-lg-{$slug} .row, .section--gap-x-lg-{$slug} .fw-row" ] = "--bs-gutter-x:{$var};";
				$responsive_spacing[768][ ".section--gap-y-md-{$slug} .row, .section--gap-y-md-{$slug} .fw-row" ] = "--bs-gutter-y:{$var};";
				$responsive_spacing[992][ ".section--gap-y-lg-{$slug} .row, .section--gap-y-lg-{$slug} .fw-row" ] = "--bs-gutter-y:{$var};";
			}

			// Site-wide default gutter on every .row. Resolve the three fields
			// (Default Gap / Default Gap X / Default Gap Y) to a single
			// effective {X, Y} pair — X/Y win when set, fall through to
			// Default Gap otherwise — and emit one combined rule. No
			// !important: presets-css loads AFTER Bootstrap (priority 20 vs 5),
			// so this `.row` rule beats Bootstrap's stock `.row { --bs-gutter-x: 1.5rem }`
			// on source order at the same specificity.
			$eff_x = ( $default_gap_x !== '' && isset( $valid_slugs[ $default_gap_x ] ) ) ? $default_gap_x : ( ( $default_gap !== '' && isset( $valid_slugs[ $default_gap ] ) ) ? $default_gap : '' );
			$eff_y = ( $default_gap_y !== '' && isset( $valid_slugs[ $default_gap_y ] ) ) ? $default_gap_y : ( ( $default_gap !== '' && isset( $valid_slugs[ $default_gap ] ) ) ? $default_gap : '' );

			if ( $eff_x !== '' || $eff_y !== '' ) {
				$body = '';
				if ( $eff_x !== '' ) { $body .= "--bs-gutter-x:var(--gap-{$eff_x});"; }
				if ( $eff_y !== '' ) { $body .= "--bs-gutter-y:var(--gap-{$eff_y});"; }
				$utility_rules[ ".row, .fw-row" ] = $body;
			}
		}

		// --- Mobile overrides for font-size tokens (tiered scaling) ---
		$mobile_overrides = array();
		if ( function_exists( 'unysonplus_mobile_font_size_scale' ) ) {
			foreach ( $tokens as $name => $value ) {
				if ( strpos( $name, '--font-size-' ) !== 0 ) { continue; }
				if ( ! preg_match( '/^(\d+(?:\.\d+)?)px$/', $value, $m ) ) { continue; }
				$desktop_px = floatval( $m[1] );
				$mobile_px  = unysonplus_mobile_font_size_scale( $desktop_px, 'font_size_preset' );
				if ( $mobile_px != $desktop_px ) {
					$mobile_overrides[ $name ] = $mobile_px . 'px';
				}
			}
		}

		// Site-wide Custom CSS (the theme contributes its Theme Settings → Misc
		// → Custom CSS through this filter). Folded into the presets file so it
		// rides the same combiner-absorbed, cacheable handle and is no longer
		// emitted as its own inline <style> block in wp_head.
		$global_extra = trim( (string) apply_filters( 'unysonplus_global_css', '' ) );

		if ( empty( $tokens ) && empty( $utility_rules ) && $global_extra === '' ) { return ''; }

		if ( $pretty ) {
			$css = "";
			if ( ! empty( $tokens ) ) {
				$css .= ":root {\n";
				foreach ( $tokens as $name => $value ) { $css .= "\t{$name}: {$value};\n"; }
				$css .= "}\n";
			}
			foreach ( $utility_rules as $sel => $body ) { $css .= "{$sel} { {$body} }\n"; }
			if ( ! empty( $responsive_spacing ) ) {
				ksort( $responsive_spacing, SORT_NUMERIC );
				foreach ( $responsive_spacing as $minw => $rules ) {
					if ( empty( $rules ) ) { continue; }
					$css .= "@media (min-width: {$minw}px) {\n";
					foreach ( $rules as $sel => $body ) { $css .= "\t{$sel} { {$body} }\n"; }
					$css .= "}\n";
				}
			}
			if ( ! empty( $button_extra_css ) ) { $css .= trim( $button_extra_css ) . "\n"; }
			if ( ! empty( $mobile_overrides ) ) {
				$css .= "@media (max-width: 767.98px) {\n\t:root {\n";
				foreach ( $mobile_overrides as $name => $value ) { $css .= "\t\t{$name}: {$value};\n"; }
				$css .= "\t}\n}\n";
			}
		} else {
			$css = '';
			if ( ! empty( $tokens ) ) {
				$css .= ':root{';
				foreach ( $tokens as $name => $value ) { $css .= $name . ':' . $value . ';'; }
				$css .= '}';
			}
			foreach ( $utility_rules as $sel => $body ) { $css .= $sel . '{' . $body . '}'; }
			if ( ! empty( $responsive_spacing ) ) {
				ksort( $responsive_spacing, SORT_NUMERIC );
				foreach ( $responsive_spacing as $minw => $rules ) {
					if ( empty( $rules ) ) { continue; }
					$css .= '@media (min-width:' . $minw . 'px){';
					foreach ( $rules as $sel => $body ) { $css .= $sel . '{' . $body . '}'; }
					$css .= '}';
				}
			}
			if ( ! empty( $button_extra_css ) ) { $css .= trim( $button_extra_css ); }
			if ( ! empty( $mobile_overrides ) ) {
				$css .= '@media (max-width:767.98px){:root{';
				foreach ( $mobile_overrides as $name => $value ) { $css .= $name . ':' . $value . ';'; }
				$css .= '}}';
			}
		}

		// Append site-wide Custom CSS last so it can override any preset rule.
		if ( $global_extra !== '' ) {
			$css .= ( $pretty ? "\n" : '' ) . $global_extra;
		}

		return $css;
	}
endif;

if ( ! function_exists( 'unysonplus_preset_css_hash' ) ) :
	/**
	 * Stable content hash of every input that drives the preset CSS.
	 * Used as the filename suffix so a preset change yields a new URL
	 * (auto cache-bust for browsers, CDN, and the Asset Optimizer combiner).
	 */
	function unysonplus_preset_css_hash() {
		$inputs = array(
			'schema'    => 21, // bumped: added Image Styles (.imgs-{slug} token bundles + base rule)
			'pretty'    => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'global'    => (string) apply_filters( 'unysonplus_global_css', '' ),
			'fonts'     => function_exists( 'unysonplus_get_font_size_presets' )    ? unysonplus_get_font_size_presets()    : array(),
			'colors'    => function_exists( 'unysonplus_get_color_presets' )        ? unysonplus_get_color_presets()        : array(),
			'btn_color' => function_exists( 'unysonplus_get_button_color_presets' ) ? unysonplus_get_button_color_presets() : array(),
			'border'    => function_exists( 'unysonplus_get_border_presets' )       ? unysonplus_get_border_presets()       : array(),
			'table'     => function_exists( 'unysonplus_get_table_presets' )        ? unysonplus_get_table_presets()        : array(),
			'btn_size'  => function_exists( 'unysonplus_get_button_size_presets' )  ? unysonplus_get_button_size_presets()  : array(),
			'btn_anim'  => function_exists( 'unysonplus_get_custom_hover_animations' ) ? unysonplus_get_custom_hover_animations() : array(),
			'spacing'   => function_exists( 'unysonplus_get_spacing_scale' )        ? unysonplus_get_spacing_scale()        : array(),
			'gap'       => function_exists( 'unysonplus_get_gap_scale' )            ? unysonplus_get_gap_scale()            : array(),
			'gap_def'   => function_exists( 'unysonplus_get_default_gap' )          ? unysonplus_get_default_gap()          : '',
			'gap_def_x' => function_exists( 'unysonplus_get_default_gap_x' )        ? unysonplus_get_default_gap_x()        : '',
			'gap_def_y' => function_exists( 'unysonplus_get_default_gap_y' )        ? unysonplus_get_default_gap_y()        : '',
			'patterns'  => function_exists( 'unysonplus_get_pattern_presets' )      ? unysonplus_get_pattern_presets()      : array(),
			'imgstyles' => function_exists( 'unysonplus_get_image_style_presets' ) ? unysonplus_get_image_style_presets() : array(),
		);
		return substr( md5( wp_json_encode( $inputs ) ), 0, 12 );
	}
endif;

if ( ! function_exists( 'unysonplus_ensure_preset_css_file' ) ) :
	/**
	 * Ensures `wp-content/uploads/unysonplus/presets-{hash}.css` exists for the
	 * current preset state. Lazily purges stale `presets-*.css` files in the
	 * same directory whenever a new hash is generated.
	 *
	 * @return array|false ['url' => string, 'path' => string, 'hash' => string] on success,
	 *                     false on any filesystem failure (caller should fall back to inline).
	 */
	function unysonplus_ensure_preset_css_file() {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$hash = unysonplus_preset_css_hash();
		$dir  = trailingslashit( $upload['basedir'] ) . 'unysonplus';
		$file = 'presets-' . $hash . '.css';
		$path = $dir . '/' . $file;
		$url  = trailingslashit( $upload['baseurl'] ) . 'unysonplus/' . $file;

		if ( file_exists( $path ) ) {
			return array( 'url' => $url, 'path' => $path, 'hash' => $hash );
		}

		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		// Build the CSS body and prepend a tiny header so the file is
		// self-describing when opened in DevTools.
		$css = unysonplus_build_presets_css_string();
		if ( $css === '' ) {
			return false;
		}
		$header = "/* unysonplus presets - auto-generated, do not edit. hash={$hash} */\n";

		// Self-heal: delete any other presets-*.css from a previous hash so the
		// uploads dir doesn't accumulate stale generations. Best-effort.
		$old_files = glob( $dir . '/presets-*.css' );
		if ( is_array( $old_files ) ) {
			foreach ( $old_files as $old ) {
				if ( $old !== $path ) {
					@unlink( $old );
				}
			}
		}

		$written = @file_put_contents( $path, $header . $css, LOCK_EX );
		if ( $written === false ) {
			return false;
		}

		return array( 'url' => $url, 'path' => $path, 'hash' => $hash );
	}
endif;

if ( ! function_exists( 'unysonplus_styling_presets_enabled' ) ) :
	/**
	 * Master switch for the whole styling layer (Styling tab + preset pickers +
	 * Component Presets editor + this preset stylesheet). Default ON. Off = the
	 * "bare, structure-only page builder" mode (Page Builder settings →
	 * Styling Presets). A non-null default short-circuits the ext-settings read so
	 * it never loads the settings schema (avoids firing fw_option_types_init early).
	 */
	function unysonplus_styling_presets_enabled() {
		if ( function_exists( 'fw_get_db_ext_settings_option' ) && function_exists( 'fw_ext' ) && fw_ext( 'page-builder' ) ) { // guard: page-builder must be active or fw_get_db_ext_settings_option() warns "Invalid extension" on a fresh install
			// Opt-OUT checkbox `disable_styling_presets` (default false = enabled).
			$disabled = fw_get_db_ext_settings_option( 'page-builder', 'disable_styling_presets', false );
			return ! ( $disabled === true || $disabled === '1' || $disabled === 1 || $disabled === 'yes' );
		}
		return true;
	}
endif;

if ( ! function_exists( 'unysonplus_enqueue_preset_css' ) ) :
	/**
	 * Enqueues the generated preset CSS file under the handle `unysonplus-presets`.
	 * Hooked early on wp_enqueue_scripts / admin_enqueue_scripts so the Asset
	 * Optimizer absorbs the handle at its priority-9999 pass.
	 *
	 * If the file write fails, the inline fallback below handles emission.
	 */
	function unysonplus_enqueue_preset_css() {
		if ( ! unysonplus_styling_presets_enabled() ) {
			return; // bare mode — no preset stylesheet
		}
		$file = unysonplus_ensure_preset_css_file();
		if ( $file === false ) {
			return;
		}
		// Pass null for $ver because the hash is already in the filename — no
		// need for a `?ver=` query string that would defeat the immutable URL.
		wp_enqueue_style( 'unysonplus-presets', $file['url'], array(), null );
	}
endif;

if ( ! function_exists( 'unysonplus_inline_preset_css_fallback' ) ) :
	/**
	 * Inline `<style id="unysonplus-presets">` fallback that only fires when
	 * the file-based enqueue didn't take (read-only filesystem, etc.).
	 *
	 * Hooked at wp_head / admin_head priority 2 — after wp_enqueue_scripts
	 * has run at priority 1 — so wp_style_is( 'enqueued' ) is reliable here.
	 */
	function unysonplus_inline_preset_css_fallback() {
		if ( ! unysonplus_styling_presets_enabled() ) {
			return; // bare mode — no preset stylesheet
		}
		if ( wp_style_is( 'unysonplus-presets', 'enqueued' ) ) {
			return;
		}
		$css = unysonplus_build_presets_css_string();
		if ( $css === '' ) {
			return;
		}
		echo '<style id="unysonplus-presets">' . $css . '</style>';
	}
endif;

// Late priority so presets land AFTER:
//   - Plugin-bundled Bootstrap (priority 5 in framework/includes/bootstrap.php)
//   - Theme stylesheets (priority 10 by convention)
//   - Plugin extension stylesheets (frontend-grid, breadcrumbs, forms — priority 10)
//   - Per-shortcode CSS (priority 30, hard-coded by Unyson's shortcodes
//     extension at framework/extensions/shortcodes/class-fw-extension-shortcodes.php
//     so it can `wp_add_inline_style('theme-style-handle', ...)` in
//     `fw_ext_shortcodes_enqueue_static:{name}` hooks)
//
// Priority 35 puts us past all four layers and gives child themes / late
// per-page customisations a clean window (priority 40+) to override presets.
// With presets winning source order, the spacing/gap utility rules drop
// their previous `:root` boost and (mostly) `!important` — see the cascade
// notes at the top of `unysonplus_build_presets_css_string()`.
add_action( 'wp_enqueue_scripts',    'unysonplus_enqueue_preset_css', 35 );
add_action( 'admin_enqueue_scripts', 'unysonplus_enqueue_preset_css', 35 );
add_action( 'wp_head',               'unysonplus_inline_preset_css_fallback', 99 );
add_action( 'admin_head',            'unysonplus_inline_preset_css_fallback', 99 );
