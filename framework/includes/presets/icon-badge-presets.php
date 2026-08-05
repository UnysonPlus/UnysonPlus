<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Icon Badge presets — defaults + getter + slug map. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_icon_badge_preset_slug_map' ) ) :
	/**
	 * Returns [ preset-id => css-slug ] for the current Icon Badge presets, so the
	 * generated class is readable: a preset named "Circle" → `.iconb-circle`. Mirrors
	 * unysonplus_border_preset_slug_map() (collisions get -2/-3, empty names fall back
	 * to the id). Shared by css-tokens.php (rule generation) and the icon_box's Icon
	 * Badge Preset dropdown so class + value + render agree.
	 */
	function unysonplus_icon_badge_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_icon_badge_presets' ) ) { return $map; }

		foreach ( unysonplus_get_icon_badge_presets() as $bp ) {
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

if ( ! function_exists( 'unysonplus_default_icon_badge_presets' ) ) :
	/**
	 * Default Icon Badge presets, in the shape consumed by the `icon-badge-presets`
	 * option type. icon_color / border_color are compact-picker values { predefined:
	 * <color-preset-slug>, custom: '' } (palette-linked); badge_size / icon_size /
	 * border_width / border_radius are unit-input { value, unit }; the tile fill is a
	 * background-pro value ({ color: { value: { predefined, custom } } }); box_shadow
	 * is { x, y, blur, spread, color, inset }. Empty hover fields inherit the default.
	 */
	function unysonplus_default_icon_badge_presets() {
		$col   = function ( $slug ) { return array( 'predefined' => (string) $slug, 'custom' => '' ); };
		$empty = array( 'predefined' => '', 'custom' => '' );
		$u     = function ( $value, $unit = 'px' ) { return array( 'value' => (string) $value, 'unit' => $unit ); };
		$fill  = function ( $hex ) { return array( 'color' => array( 'value' => array( 'predefined' => '', 'custom' => (string) $hex ) ) ); };
		$nofill = array( 'color' => array( 'value' => array( 'predefined' => '', 'custom' => '' ) ) );
		$sh    = function ( $y, $blur, $alpha ) {
			return array( 'x' => 0, 'y' => $y, 'blur' => $blur, 'spread' => 0, 'color' => 'rgba(0,0,0,' . $alpha . ')', 'inset' => false );
		};

		return apply_filters( 'unysonplus_default_icon_badge_presets', array(
			// Circle — solid brand fill, white glyph; lifts + glows on hover.
			array(
				'id'            => 'i000000001',
				'preset_name'   => 'Circle',
				'badge_shape'   => 'circle',
				'badge_size'    => $u( 48 ),
				'icon_size'     => $u( 24 ),
				'border_radius' => $u( '' ),
				'transition'    => '200',
				'hover_fx'      => array( 'lift', 'glow' ),
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'background' => $fill( '#0d6efd' ), 'icon_color' => $col( 'white' ), 'border_style' => '', 'border_color' => $empty, 'box_shadow' => $sh( 4, 12, '0.15' ) ),
					'hover'   => array( 'box_shadow' => $sh( 8, 20, '0.22' ) ),
				),
			),
			// Soft Tile — rounded light tile with a brand glyph; pops on hover.
			array(
				'id'            => 'i000000002',
				'preset_name'   => 'Soft Tile',
				'badge_shape'   => 'rounded',
				'badge_size'    => $u( 52 ),
				'icon_size'     => $u( 26 ),
				'border_radius' => $u( 14 ),
				'transition'    => '200',
				'hover_fx'      => array( 'pop' ),
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'background' => $fill( '#eef2ff' ), 'icon_color' => $col( 'primary' ), 'border_style' => '', 'border_color' => $empty ),
					'hover'   => array( 'background' => $fill( '#e0e7ff' ) ),
				),
			),
			// Outline Ring — transparent circle with a brand ring; fills on hover.
			array(
				'id'            => 'i000000003',
				'preset_name'   => 'Outline Ring',
				'badge_shape'   => 'circle',
				'badge_size'    => $u( 48 ),
				'icon_size'     => $u( 22 ),
				'border_radius' => $u( '' ),
				'transition'    => '200',
				'hover_fx'      => array(),
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'background' => $nofill, 'icon_color' => $col( 'primary' ), 'border_style' => 'solid', 'border_width' => $u( 2 ), 'border_color' => $col( 'primary' ) ),
					'hover'   => array( 'background' => $fill( '#0d6efd' ), 'icon_color' => $col( 'white' ) ),
				),
			),
			// Hexagon — filled accent hexagon, white glyph; glows on hover.
			array(
				'id'            => 'i000000004',
				'preset_name'   => 'Hexagon',
				'badge_shape'   => 'hexagon',
				'badge_size'    => $u( 54 ),
				'icon_size'     => $u( 26 ),
				'border_radius' => $u( '' ),
				'transition'    => '200',
				'hover_fx'      => array( 'glow' ),
				'custom_css'    => '',
				'states'        => array(
					'default' => array( 'background' => $fill( '#6610f2' ), 'icon_color' => $col( 'white' ), 'border_style' => '', 'border_color' => $empty ),
					'hover'   => array(),
				),
			),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_icon_badge_presets' ) ) :
	/**
	 * The user's saved Icon Badge presets (Theme Settings → Components → Icon Badges)
	 * or the plugin defaults.
	 */
	function unysonplus_get_icon_badge_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'icon_badge_presets', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_icon_badge_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_icon_badge_presets', unysonplus_default_icon_badge_presets() );
	}
endif;
