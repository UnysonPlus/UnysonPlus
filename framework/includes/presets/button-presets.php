<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Button presets — color presets, sizes, hover animations, slug maps + the one-shot button_colors migration. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_button_preset_slug_map' ) ) :
	/**
	 * Returns [ preset-id => css-slug ] for the current Button Presets, so the
	 * generated CSS class is readable and override-friendly: a preset named
	 * "Primary" → `.btn-primary` (overrides Bootstrap's own `.btn-primary`),
	 * "Primary Outline" → `.btn-primary-outline`.
	 *
	 * Slug = lower-case, non-alphanumerics → '-', trimmed. Collisions get a
	 * numeric suffix (-2, -3, …) in preset order. Empty/symbol-only names fall
	 * back to the sanitized numeric id so every preset still gets a stable class.
	 *
	 * Single source of truth shared by css-tokens.php (rule generation), the
	 * Button shortcode's Style dropdown (`sc_get_button_style_choices`), and the
	 * admin dropdown-preview emitters — so the class, the saved option value, and
	 * the preview always agree.
	 */
	function unysonplus_button_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_button_color_presets' ) ) { return $map; }

		foreach ( unysonplus_get_button_color_presets() as $bp ) {
			if ( ! is_array( $bp ) || empty( $bp['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $bp['id'] );
			if ( $id === '' ) { continue; }

			$name = isset( $bp['color_name'] ) ? (string) $bp['color_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) {
				$slug = $id; // name was empty / symbols-only — keep a stable class
			}

			// De-dupe: second "Primary" → primary-2, third → primary-3, …
			$base = $slug;
			$n    = 1;
			while ( isset( $seen[ $slug ] ) ) {
				$n++;
				$slug = $base . '-' . $n;
			}
			$seen[ $slug ] = true;

			$map[ $id ] = $slug;
		}

		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_default_button_color_presets' ) ) :
	/**
	 * Default button color presets, in the SKIN shape consumed by the
	 * `button-presets` option type: each preset has a nested `states` map
	 * (default / hover / active / focus / disabled) whose color fields are
	 * compact-picker values { predefined: <color-preset-slug>, custom: '' }.
	 * Empty states (active/focus/disabled) inherit the default look at render
	 * time. Slugs reference Color Presets (see unysonplus_default_color_presets):
	 * white/gray/light-gray/blue/green/cyan/amber/red/black are always present.
	 */
	function unysonplus_default_button_color_presets() {
		// p() = predefined-slug compact value; $empty = "no color" (inherit/none).
		$p     = function ( $slug ) { return array( 'predefined' => (string) $slug, 'custom' => '' ); };
		$empty = array( 'predefined' => '', 'custom' => '' );

		// SOLID: white/black text on a filled bg; border tracks the bg; hover
		// shifts bg+border to a related slug. Other states inherit the default.
		$solid = function ( $id, $name, $text, $bg, $hover_bg ) use ( $p ) {
			return array(
				'id'         => $id,
				'color_name' => $name,
				'states'     => array(
					'default'  => array( 'text_color' => $p( $text ), 'bg_color' => $p( $bg ), 'border_color' => $p( $bg ) ),
					'hover'    => array( 'bg_color' => $p( $hover_bg ), 'border_color' => $p( $hover_bg ) ),
					'active'   => array(),
					'focus'    => array(),
					'disabled' => array(),
				),
			);
		};

		// OUTLINE: transparent bg, text + border the SAME color, 2px solid border
		// (2px reads as a deliberate outline rather than a hairline). Classic
		// Bootstrap behavior: fills with the color on hover, text flips to $fill.
		$outline = function ( $id, $name, $color, $fill ) use ( $p, $empty ) {
			$bw = array( 'value' => '2', 'unit' => 'px' );
			return array(
				'id'         => $id,
				'color_name' => $name,
				'states'     => array(
					'default'  => array( 'text_color' => $p( $color ), 'bg_color' => $empty, 'border_color' => $p( $color ), 'border_width' => $bw, 'border_style' => 'solid' ),
					'hover'    => array( 'text_color' => $p( $fill ), 'bg_color' => $p( $color ), 'border_color' => $p( $color ) ),
					'active'   => array(),
					'focus'    => array(),
					'disabled' => array(),
				),
			);
		};

		// GRADIENT: white text on a Gradient V2 background (no solid bg, no border).
		// The gradient lives on the default state; hover is left empty so it inherits
		// (gradients cannot CSS-transition, so this avoids a snap on hover). Showcases
		// the per-state Background Gradient field added to button presets.
		$gradient = function ( $id, $name, $text, $grad, $hover_grad ) use ( $p, $empty ) {
			return array(
				'id'         => $id,
				'color_name' => $name,
				'states'     => array(
					'default'  => array( 'text_color' => $p( $text ), 'bg_color' => $empty, 'border_color' => $empty, 'border_style' => 'none', 'gradient' => $grad ),
					'hover'    => array( 'gradient' => $hover_grad ),
					'active'   => array(),
					'focus'    => array(),
					'disabled' => array(),
				),
			);
		};

		// LINK: text only — no background, no border (Bootstrap's .btn-link).
		// Darkens the text on hover. For tertiary / low-emphasis actions.
		$link = function ( $id, $name, $color, $hover ) use ( $p, $empty ) {
			return array(
				'id'         => $id,
				'color_name' => $name,
				'states'     => array(
					'default'  => array( 'text_color' => $p( $color ), 'bg_color' => $empty, 'border_color' => $empty, 'border_style' => 'none' ),
					'hover'    => array( 'text_color' => $p( $hover ) ),
					'active'   => array(),
					'focus'    => array(),
					'disabled' => array(),
				),
			);
		};

		return apply_filters( 'unysonplus_default_button_color_presets', array(
			// Solid — Primary first (most-used), then Secondary, then the rest.
			$solid( '0000000002', 'Primary',   'white', 'primary',   'indigo' ),
			$solid( '0000000001', 'Secondary', 'white', 'secondary', 'gray' ),
			$solid( '0000000003', 'Success',   'white', 'green',     'teal' ),
			$solid( '0000000004', 'Info',      'white', 'cyan',      'light-blue' ),
			$solid( '0000000005', 'Warning',   'black', 'amber',     'orange' ),
			$solid( '0000000006', 'Danger',    'white', 'red',       'pink' ),
			// Outline (transparent bg, text+border same color, fills on hover)
			$outline( '0000000011', 'Secondary Outline', 'secondary', 'white' ),
			$outline( '0000000012', 'Primary Outline',   'primary',   'white' ),
			$outline( '0000000013', 'Success Outline',   'green',     'white' ),
			$outline( '0000000014', 'Info Outline',      'cyan',      'white' ),
			$outline( '0000000015', 'Warning Outline',   'amber',     'black' ),
			$outline( '0000000016', 'Danger Outline',    'red',       'white' ),
			// Gradient (white text on a linear gradient; showcases the gradient field).
			// Hover reverses the gradient (swapped stops) for a clear state change —
			// gradients can't CSS-fade, so a distinct flip reads better than a subtle one.
			$gradient( '0000000031', 'Gradient', 'white',
				array(
					'type'  => 'linear',
					'angle' => 135,
					'stops' => array(
						array( 'color' => '#667EEA', 'position' => 0 ),
						array( 'color' => '#764BA2', 'position' => 100 ),
					),
				),
				array(
					'type'  => 'linear',
					'angle' => 135,
					'stops' => array(
						array( 'color' => '#764BA2', 'position' => 0 ),
						array( 'color' => '#667EEA', 'position' => 100 ),
					),
				)
			),
			// Link (text only, no bg/border, darkens on hover)
			$link( '0000000021', 'Link', 'primary', 'indigo' ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_button_color_presets' ) ) :
	/**
	 * Returns the user's saved button color presets (from Theme Settings →
	 * Buttons → Button Color Presets) or the slug-based plugin defaults.
	 * Each entry: { id, color_name, normal_text_color, normal_bg_color,
	 * hover_text_color, hover_bg_color } where color fields hold Color Preset
	 * slugs (the frontend bridge resolves slug → hex). Empty hover fields fall
	 * back to the normal value at render time.
	 */
	function unysonplus_get_button_color_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'button_colors', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_button_color_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_button_color_presets', unysonplus_default_button_color_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_default_button_size_presets' ) ) :
	/**
	 * Default button size presets. Each entry produces a `.btn-{slug}` class
	 * on the frontend (emitted by the bridge in css-tokens.php). Mirrors the
	 * theme's `unysonplus_option_button_size_defaults()` so behaviour is
	 * unchanged on the official theme, but lives in the plugin so any theme
	 * benefits from sensible defaults.
	 */
	function unysonplus_default_button_size_presets() {
		// Size = dimensions only (font-size, line-height, padding Y/X, radius).
		// font_size / padding_* / border_radius use the unit-input shape
		// array('value'=>.., 'unit'=>..); line_height stays a plain (often
		// unitless) string. border-width is NOT a size concern — it lives on the
		// Button Preset (skin).
		$u = function ( $value, $unit = 'px' ) { return array( 'value' => (string) $value, 'unit' => $unit ); };
		return apply_filters( 'unysonplus_default_button_size_presets', array(
			array( 'id' => '0000010005', 'size_name' => 'Extra Large', 'slug' => 'xl', 'font_size' => $u( 22 ), 'line_height' => '1.4', 'padding_y' => $u( 14 ), 'padding_x' => $u( 24 ), 'border_radius' => $u( 10 ) ),
			array( 'id' => '0000010004', 'size_name' => 'Large',       'slug' => 'lg', 'font_size' => $u( 20 ), 'line_height' => '1.4', 'padding_y' => $u( 12 ), 'padding_x' => $u( 20 ), 'border_radius' => $u( 8 )  ),
			array( 'id' => '0000010003', 'size_name' => 'Medium',      'slug' => 'md', 'font_size' => $u( 16 ), 'line_height' => '1.4', 'padding_y' => $u( 8 ),  'padding_x' => $u( 16 ), 'border_radius' => $u( 6 )  ),
			array( 'id' => '0000010002', 'size_name' => 'Small',       'slug' => 'sm', 'font_size' => $u( 13 ), 'line_height' => '1.4', 'padding_y' => $u( 6 ),  'padding_x' => $u( 12 ), 'border_radius' => $u( 5 )  ),
			array( 'id' => '0000010001', 'size_name' => 'Extra Small', 'slug' => 'xs', 'font_size' => $u( 12 ), 'line_height' => '1.4', 'padding_y' => $u( 2 ),  'padding_x' => $u( 6 ),  'border_radius' => $u( 3 )  ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_button_size_presets' ) ) :
	/**
	 * Returns the user's saved button size presets (Theme Settings → Buttons →
	 * Sizes) or the plugin defaults. Each entry: { id, size_name, slug,
	 * font_size, line_height, padding{top,right,bottom,left}, border_width,
	 * border_radius }. Slug becomes the CSS class suffix `.btn-{slug}`.
	 */
	function unysonplus_get_button_size_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'button_sizes', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_button_size_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_button_size_presets', unysonplus_default_button_size_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_default_custom_hover_animations' ) ) :
	/**
	 * Sample custom hover animations seeded into Theme Settings → Buttons → Hover
	 * Animations, so users start with working references to learn from / duplicate
	 * (rather than a blank list). Each entry: { id, name, css }. The CSS uses the
	 * editor tokens: {{BTN}} = this button (.btnfx-c-{slug}), {{ANIM}} = a unique
	 * @keyframes name. All motion-only (transform / box-shadow) so they layer over
	 * any button preset. These are SAMPLES — the 22 built-in effects live in
	 * hover-fx.css and are not duplicated here.
	 */
	function unysonplus_default_custom_hover_animations() {
		return apply_filters( 'unysonplus_default_custom_hover_animations', array(
			array(
				'id'   => '0000020001',
				'name' => 'Pulse Ring',
				'css'  => '{{BTN}}:hover { animation: {{ANIM}} 1.1s ease infinite; }
@keyframes {{ANIM}} {
  0%   { box-shadow: 0 0 0 0 rgba(0,0,0,.35); }
  70%  { box-shadow: 0 0 0 12px rgba(0,0,0,0); }
  100% { box-shadow: 0 0 0 0 rgba(0,0,0,0); }
}',
			),
			array(
				'id'   => '0000020002',
				'name' => 'Swing',
				'css'  => '{{BTN}}:hover { transform-origin: top center; animation: {{ANIM}} .7s ease; }
@keyframes {{ANIM}} {
  20%  { transform: rotate(8deg); }
  40%  { transform: rotate(-6deg); }
  60%  { transform: rotate(4deg); }
  80%  { transform: rotate(-2deg); }
  100% { transform: rotate(0); }
}',
			),
			array(
				'id'   => '0000020003',
				'name' => 'Rubber Band',
				'css'  => '{{BTN}}:hover { animation: {{ANIM}} .8s ease; }
@keyframes {{ANIM}} {
  0%   { transform: scale(1, 1); }
  30%  { transform: scale(1.25, .75); }
  40%  { transform: scale(.75, 1.25); }
  55%  { transform: scale(1.15, .85); }
  70%  { transform: scale(.95, 1.05); }
  100% { transform: scale(1, 1); }
}',
			),
			array(
				'id'   => '0000020004',
				'name' => 'Squeeze',
				'css'  => '{{BTN}}:hover { animation: {{ANIM}} .45s ease; }
@keyframes {{ANIM}} {
  0%, 100% { transform: scale(1, 1); }
  50%      { transform: scale(1.1, .85); }
}',
			),
			array(
				'id'   => '0000020005',
				'name' => 'Raise & Glow',
				'css'  => '{{BTN}} { transition: transform .25s ease, box-shadow .25s ease; }
{{BTN}}:hover { transform: translateY(-4px); box-shadow: 0 10px 20px -8px rgba(0,0,0,.45); }',
			),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_custom_hover_animations' ) ) :
	/**
	 * Returns the user's saved custom hover animations (Theme Settings → Buttons →
	 * Hover Animations) or the seeded samples. Each entry: { id, name, css }. Slug
	 * (from name) becomes the class suffix `.btnfx-c-{slug}`.
	 */
	function unysonplus_get_custom_hover_animations() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'button_animations', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_custom_hover_animations', $saved );
			}
		}
		return apply_filters( 'unysonplus_custom_hover_animations', unysonplus_default_custom_hover_animations() );
	}
endif;

if ( ! function_exists( 'unysonplus_custom_hover_animation_slug_map' ) ) :
	/**
	 * id => slug map for custom hover animations (name → slug, with -2/-3 dedupe),
	 * mirroring unysonplus_button_preset_slug_map(). Shared by the CSS generator
	 * (css-tokens) and the shortcode dropdown choices so they always agree.
	 */
	function unysonplus_custom_hover_animation_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_custom_hover_animations' ) ) { return $map; }

		foreach ( unysonplus_get_custom_hover_animations() as $a ) {
			if ( ! is_array( $a ) || empty( $a['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $a['id'] );
			if ( $id === '' ) { continue; }

			$name = isset( $a['name'] ) ? (string) $a['name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) {
				$slug = $id; // name empty / symbols-only — keep a stable class
			}

			// De-dupe: second "Pulse Ring" => pulse-ring-2, third => pulse-ring-3, …
			$base = $slug;
			$n    = 1;
			while ( isset( $seen[ $slug ] ) ) {
				$n++;
				$slug = $base . '-' . $n;
			}
			$seen[ $slug ] = true;

			$map[ $id ] = $slug;
		}

		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_maybe_migrate_button_colors' ) ) :
	/**
	 * One-shot DB migration for `button_colors`:
	 *   1. Convert any hex value (`#abc123`) into the matching Color Preset
	 *      slug via hex-equality lookup.
	 *   2. Detect any value that's a slug NOT present in the current Color
	 *      Presets (e.g. legacy `primary`/`info`/`danger` left over from an
	 *      earlier defaults version where Bootstrap semantic colors were
	 *      assumed available). Treat those as empty for the next step.
	 *   3. Fill any empty field with the slug from the plugin's default
	 *      button preset whose `color_name` matches this entry's (case-
	 *      insensitive). Restores working defaults for entries whose old
	 *      slugs / hexes can't be resolved.
	 *
	 * Runs once on `admin_init`, guarded by `unysonplus_button_colors_migrated_v3`.
	 */
	function unysonplus_maybe_migrate_button_colors() {
		if ( get_option( 'unysonplus_button_colors_migrated_v3' ) ) { return; }
		if ( ! function_exists( 'fw_get_db_settings_option' ) || ! function_exists( 'fw_set_db_settings_option' ) ) { return; }

		$saved = fw_get_db_settings_option( 'button_colors', null );
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			update_option( 'unysonplus_button_colors_migrated_v3', '1' );
			return;
		}

		$keys = array( 'normal_text_color', 'normal_bg_color', 'hover_text_color', 'hover_bg_color' );

		$slug_map = unysonplus_color_preset_slug_map();
		$hex_to_slug = array();
		foreach ( $slug_map as $slug => $hex ) {
			$hex_to_slug[ strtolower( $hex ) ] = $slug;
		}

		$defaults_by_name = array();
		foreach ( unysonplus_default_button_color_presets() as $dbp ) {
			if ( ! empty( $dbp['color_name'] ) ) {
				$defaults_by_name[ strtolower( $dbp['color_name'] ) ] = $dbp;
			}
		}

		$changed = false;
		foreach ( $saved as &$entry ) {
			if ( ! is_array( $entry ) ) { continue; }
			$default = '';
			$name_key = isset( $entry['color_name'] ) ? strtolower( (string) $entry['color_name'] ) : '';
			if ( $name_key !== '' && isset( $defaults_by_name[ $name_key ] ) ) {
				$default = $defaults_by_name[ $name_key ];
			}

			foreach ( $keys as $k ) {
				$cur = isset( $entry[ $k ] ) ? (string) $entry[ $k ] : '';
				// Step 1: hex → slug.
				if ( $cur !== '' && $cur[0] === '#' ) {
					$cur = isset( $hex_to_slug[ strtolower( $cur ) ] ) ? $hex_to_slug[ strtolower( $cur ) ] : '';
					$entry[ $k ] = $cur;
					$changed = true;
				}
				// Step 2: unresolvable slug → empty.
				if ( $cur !== '' && ! isset( $slug_map[ $cur ] ) ) {
					$entry[ $k ] = '';
					$cur = '';
					$changed = true;
				}
				// Step 3: fill empty from plugin default (matched by color_name).
				if ( $cur === '' && is_array( $default ) && isset( $default[ $k ] ) && $default[ $k ] !== '' ) {
					$entry[ $k ] = $default[ $k ];
					$changed = true;
				}
			}
		}
		unset( $entry );

		if ( $changed ) {
			fw_set_db_settings_option( 'button_colors', $saved );
		}
		update_option( 'unysonplus_button_colors_migrated_v3', '1' );
	}
endif;
add_action( 'admin_init', 'unysonplus_maybe_migrate_button_colors' );
