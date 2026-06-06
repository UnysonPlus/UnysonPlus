<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Border presets — defaults + getter + slug map. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_border_preset_slug_map' ) ) :
	/**
	 * Returns [ preset-id => css-slug ] for the current column Border Presets, so
	 * the generated class is readable: a preset named "Card" → `.colb-card`.
	 * Mirrors unysonplus_button_preset_slug_map() (collisions get -2/-3, empty
	 * names fall back to the id). Shared by css-tokens.php (rule generation) and
	 * the column's Border Preset dropdown so class + value + render agree.
	 */
	function unysonplus_border_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_border_presets' ) ) { return $map; }

		foreach ( unysonplus_get_border_presets() as $bp ) {
			if ( ! is_array( $bp ) || empty( $bp['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $bp['id'] );
			if ( $id === '' ) { continue; }

			$name = isset( $bp['preset_name'] ) ? (string) $bp['preset_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) { $slug = $id; }

			$base = $slug;
			$n    = 1;
			while ( isset( $seen[ $slug ] ) ) { $n++; $slug = $base . '-' . $n; }
			$seen[ $slug ] = true;

			$map[ $id ] = $slug;
		}

		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_default_border_presets' ) ) :
	/**
	 * Default column Border Presets, in the shape consumed by the `border-presets`
	 * option type: border_color is a compact-picker value { predefined: <color-
	 * preset-slug>, custom: '' }; border_width / border_radius are unit-input
	 * { value, unit }; box_shadow is { x, y, blur, spread, color, inset }. Empty
	 * hover fields inherit the default look at render time.
	 */
	function unysonplus_default_border_presets() {
		$col   = function ( $slug ) { return array( 'predefined' => (string) $slug, 'custom' => '' ); };
		$empty = array( 'predefined' => '', 'custom' => '' );
		$u     = function ( $value, $unit = 'px' ) { return array( 'value' => (string) $value, 'unit' => $unit ); };
		$sh    = function ( $y, $blur, $alpha ) {
			return array( 'x' => 0, 'y' => $y, 'blur' => $blur, 'spread' => 0, 'color' => 'rgba(0,0,0,' . $alpha . ')', 'inset' => false );
		};
		// Padding is a `spacing` value (mode 'padding'): margin stays empty, padding
		// carries a Bootstrap-style "all sides" class from the Spacing scale (slug 4
		// = 1.5rem ≈ 24px in the default scale). css-tokens resolves it to a length.
		$pad   = function ( $all ) {
			return array(
				'margin'  => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
				'padding' => array( 'all' => (string) $all, 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
			);
		};

		return apply_filters( 'unysonplus_default_border_presets', array(
			// Card — hairline border + small radius + soft shadow; lifts on hover.
			array(
				'id'            => 'b000000001',
				'preset_name'   => 'Card',
				'border_sides'  => 'all',
				'border_radius' => $u( 8 ),
				'padding'       => $pad( 'p-4' ),
				'transition'    => '200',
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'border_style' => 'solid', 'border_width' => $u( 1 ), 'border_color' => $col( 'light-gray' ), 'box_shadow' => $sh( 1, 3, '0.08' ) ),
					'hover'   => array( 'box_shadow' => $sh( 8, 20, '0.12' ) ),
				),
			),
			// Outline — 2px solid primary, no shadow; border darkens on hover.
			array(
				'id'            => 'b000000002',
				'preset_name'   => 'Outline',
				'border_sides'  => 'all',
				'border_radius' => $u( 6 ),
				'padding'       => $pad( 'p-4' ),
				'transition'    => '200',
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'border_style' => 'solid', 'border_width' => $u( 2 ), 'border_color' => $col( 'primary' ) ),
					'hover'   => array( 'border_color' => $col( 'indigo' ) ),
				),
			),
			// Soft Shadow — no border, generous radius, medium shadow; grows on hover.
			array(
				'id'            => 'b000000003',
				'preset_name'   => 'Soft Shadow',
				'border_sides'  => 'all',
				'border_radius' => $u( 12 ),
				'padding'       => $pad( 'p-4' ),
				'transition'    => '250',
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'border_style' => '', 'border_color' => $empty, 'box_shadow' => $sh( 4, 14, '0.08' ) ),
					'hover'   => array( 'box_shadow' => $sh( 12, 30, '0.16' ) ),
				),
			),
			// Hover Lift — hairline border that turns primary + a shadow appears on hover.
			array(
				'id'            => 'b000000004',
				'preset_name'   => 'Hover Lift',
				'border_sides'  => 'all',
				'border_radius' => $u( 8 ),
				'padding'       => $pad( 'p-4' ),
				'transition'    => '200',
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'border_style' => 'solid', 'border_width' => $u( 1 ), 'border_color' => $col( 'light-gray' ) ),
					'hover'   => array( 'border_color' => $col( 'primary' ), 'box_shadow' => $sh( 10, 24, '0.14' ) ),
				),
			),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_border_presets' ) ) :
	/**
	 * The user's saved column Border Presets (Theme Settings → General → Borders)
	 * or the plugin defaults.
	 */
	function unysonplus_get_border_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'border_presets', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_border_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_border_presets', unysonplus_default_border_presets() );
	}
endif;
