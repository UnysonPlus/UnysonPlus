<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Table presets — defaults + getter + slug map. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_table_preset_slug_map' ) ) :
	/**
	 * Returns [ preset-id => css-slug ] for the current Table Presets, so the
	 * generated class is readable: a preset named "Striped" → `.tbl-striped`.
	 * Mirrors unysonplus_border_preset_slug_map() (collisions get -2/-3, empty
	 * names fall back to the id). Shared by css-tokens.php (rule generation) and
	 * the Table shortcode's preset dropdown so class + value + render agree.
	 */
	function unysonplus_table_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_table_presets' ) ) { return $map; }

		foreach ( unysonplus_get_table_presets() as $tp ) {
			if ( ! is_array( $tp ) || empty( $tp['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $tp['id'] );
			if ( $id === '' ) { continue; }

			$name = isset( $tp['preset_name'] ) ? (string) $tp['preset_name'] : '';
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

if ( ! function_exists( 'unysonplus_default_table_presets' ) ) :
	/**
	 * Default Table Presets, in the shape consumed by the `table-presets` option
	 * type. Each preset → a `.tbl-{slug}` class applied to the Table wrapper; the
	 * CSS is emitted by css-tokens.php. Colors are compact-picker values
	 * { predefined: <color-preset-slug>, custom: '#hex' } — built-ins use custom
	 * hex so they render the same on any theme palette; widths/radius are
	 * unit-input { value, unit }; box_shadow is { x,y,blur,spread,color,inset }.
	 *
	 * Per-entry shape:
	 *   id, preset_name,
	 *   cell_padding_y, cell_padding_x      (unit-input)
	 *   grid_lines (none|horizontal|vertical|both), grid_style, grid_width, grid_color
	 *   outer_border_style, outer_border_width, outer_border_color
	 *   border_radius (unit-input), outer_shadow (box-shadow), cell_font_size (unit-input)
	 *   transition (ms), custom_css
	 *   sections => header|body|striped|hover|footer|caption (each a flat field map)
	 */
	function unysonplus_default_table_presets() {
		$c     = function ( $hex ) { return array( 'predefined' => '', 'custom' => (string) $hex ); };
		$empty = array( 'predefined' => '', 'custom' => '' );
		$u     = function ( $value, $unit = 'px' ) { return array( 'value' => (string) $value, 'unit' => $unit ); };
		$noshadow = array( 'x' => 0, 'y' => 0, 'blur' => 0, 'spread' => 0, 'color' => '', 'inset' => false );

		return apply_filters( 'unysonplus_default_table_presets', array(

			// Clean Lines — no frame, horizontal row separators, underlined header.
			array(
				'id'                 => 't000000001',
				'preset_name'        => 'Clean Lines',
				'cell_padding_y'     => $u( 10 ),
				'cell_padding_x'     => $u( 14 ),
				'grid_lines'         => 'horizontal',
				'grid_style'         => 'solid',
				'grid_width'         => $u( 1 ),
				'grid_color'         => $c( '#ededed' ),
				'outer_border_style' => '',
				'outer_border_width' => $u( 0 ),
				'outer_border_color' => $empty,
				'border_radius'      => $u( 0 ),
				'outer_shadow'       => $noshadow,
				'cell_font_size'     => $u( '', 'px' ),
				'transition'         => '150',
				'custom_css'         => '',
				'sections'           => array(
					'header'  => array( 'bg_color' => $empty, 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'text_transform' => 'uppercase', 'border_style' => 'solid', 'border_width' => $u( 2 ), 'border_color' => $c( '#1d2327' ) ),
					'body'    => array( 'bg_color' => $empty, 'text_color' => $c( '#50575e' ) ),
					'striped' => array( 'enabled' => 'no', 'bg_color' => $c( '#f6f8fb' ) ),
					'hover'   => array( 'bg_color' => $c( '#f6f7f8' ), 'text_color' => $empty ),
					'footer'  => array( 'bg_color' => $empty, 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'border_style' => 'solid', 'border_width' => $u( 2 ), 'border_color' => $c( '#d8dbde' ) ),
					'caption' => array( 'color' => $c( '#787c82' ), 'font_size' => $u( 13 ), 'font_style' => 'italic' ),
				),
			),

			// Bordered Grid — full grid + outer frame + tinted header.
			array(
				'id'                 => 't000000002',
				'preset_name'        => 'Bordered Grid',
				'cell_padding_y'     => $u( 9 ),
				'cell_padding_x'     => $u( 12 ),
				'grid_lines'         => 'both',
				'grid_style'         => 'solid',
				'grid_width'         => $u( 1 ),
				'grid_color'         => $c( '#e2e4e7' ),
				'outer_border_style' => 'solid',
				'outer_border_width' => $u( 1 ),
				'outer_border_color' => $c( '#d8dbde' ),
				'border_radius'      => $u( 6 ),
				'outer_shadow'       => $noshadow,
				'cell_font_size'     => $u( '', 'px' ),
				'transition'         => '150',
				'custom_css'         => '',
				'sections'           => array(
					'header'  => array( 'bg_color' => $c( '#f1f4f9' ), 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'text_transform' => '', 'border_style' => 'solid', 'border_width' => $u( 1 ), 'border_color' => $c( '#c9ced3' ) ),
					'body'    => array( 'bg_color' => $c( '#ffffff' ), 'text_color' => $c( '#3c434a' ) ),
					'striped' => array( 'enabled' => 'no', 'bg_color' => $c( '#f8f9fa' ) ),
					'hover'   => array( 'bg_color' => $c( '#eef3fb' ), 'text_color' => $empty ),
					'footer'  => array( 'bg_color' => $c( '#f6f7f8' ), 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'border_style' => 'solid', 'border_width' => $u( 2 ), 'border_color' => $c( '#c9ced3' ) ),
					'caption' => array( 'color' => $c( '#787c82' ), 'font_size' => $u( 13 ), 'font_style' => 'italic' ),
				),
			),

			// Minimal — borderless, airy padding, quiet hover.
			array(
				'id'                 => 't000000003',
				'preset_name'        => 'Minimal',
				'cell_padding_y'     => $u( 14 ),
				'cell_padding_x'     => $u( 18 ),
				'grid_lines'         => 'none',
				'grid_style'         => '',
				'grid_width'         => $u( 0 ),
				'grid_color'         => $empty,
				'outer_border_style' => '',
				'outer_border_width' => $u( 0 ),
				'outer_border_color' => $empty,
				'border_radius'      => $u( 0 ),
				'outer_shadow'       => $noshadow,
				'cell_font_size'     => $u( '', 'px' ),
				'transition'         => '150',
				'custom_css'         => '',
				'sections'           => array(
					'header'  => array( 'bg_color' => $empty, 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'text_transform' => '', 'border_style' => '', 'border_width' => $u( 0 ), 'border_color' => $empty ),
					'body'    => array( 'bg_color' => $empty, 'text_color' => $c( '#50575e' ) ),
					'striped' => array( 'enabled' => 'no', 'bg_color' => $c( '#fafbfc' ) ),
					'hover'   => array( 'bg_color' => $c( '#fafbfc' ), 'text_color' => $empty ),
					'footer'  => array( 'bg_color' => $empty, 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'border_style' => '', 'border_width' => $u( 0 ), 'border_color' => $empty ),
					'caption' => array( 'color' => $c( '#a7aaad' ), 'font_size' => $u( 13 ), 'font_style' => 'italic' ),
				),
			),

			// Striped — zebra rows + bold accent header.
			array(
				'id'                 => 't000000004',
				'preset_name'        => 'Striped',
				'cell_padding_y'     => $u( 10 ),
				'cell_padding_x'     => $u( 14 ),
				'grid_lines'         => 'none',
				'grid_style'         => '',
				'grid_width'         => $u( 0 ),
				'grid_color'         => $empty,
				'outer_border_style' => 'solid',
				'outer_border_width' => $u( 1 ),
				'outer_border_color' => $c( '#e2e4e7' ),
				'border_radius'      => $u( 6 ),
				'outer_shadow'       => $noshadow,
				'cell_font_size'     => $u( '', 'px' ),
				'transition'         => '150',
				'custom_css'         => '',
				'sections'           => array(
					'header'  => array( 'bg_color' => $c( '#2271b1' ), 'text_color' => $c( '#ffffff' ), 'font_weight' => '600', 'text_transform' => '', 'border_style' => '', 'border_width' => $u( 0 ), 'border_color' => $empty ),
					'body'    => array( 'bg_color' => $c( '#ffffff' ), 'text_color' => $c( '#3c434a' ) ),
					'striped' => array( 'enabled' => 'yes', 'bg_color' => $c( '#f6f8fb' ) ),
					'hover'   => array( 'bg_color' => $c( '#eef3fb' ), 'text_color' => $empty ),
					'footer'  => array( 'bg_color' => $c( '#f1f4f9' ), 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'border_style' => '', 'border_width' => $u( 0 ), 'border_color' => $empty ),
					'caption' => array( 'color' => $c( '#787c82' ), 'font_size' => $u( 13 ), 'font_style' => 'italic' ),
				),
			),

			// Dark Header — dark thead, zebra body, rounded frame.
			array(
				'id'                 => 't000000005',
				'preset_name'        => 'Dark Header',
				'cell_padding_y'     => $u( 11 ),
				'cell_padding_x'     => $u( 14 ),
				'grid_lines'         => 'horizontal',
				'grid_style'         => 'solid',
				'grid_width'         => $u( 1 ),
				'grid_color'         => $c( '#ededed' ),
				'outer_border_style' => 'solid',
				'outer_border_width' => $u( 1 ),
				'outer_border_color' => $c( '#e2e4e7' ),
				'border_radius'      => $u( 8 ),
				'outer_shadow'       => $noshadow,
				'cell_font_size'     => $u( '', 'px' ),
				'transition'         => '150',
				'custom_css'         => '',
				'sections'           => array(
					'header'  => array( 'bg_color' => $c( '#1d2327' ), 'text_color' => $c( '#ffffff' ), 'font_weight' => '600', 'text_transform' => 'uppercase', 'border_style' => '', 'border_width' => $u( 0 ), 'border_color' => $empty ),
					'body'    => array( 'bg_color' => $c( '#ffffff' ), 'text_color' => $c( '#3c434a' ) ),
					'striped' => array( 'enabled' => 'yes', 'bg_color' => $c( '#f7f8f9' ) ),
					'hover'   => array( 'bg_color' => $c( '#eef0f2' ), 'text_color' => $empty ),
					'footer'  => array( 'bg_color' => $c( '#f6f7f8' ), 'text_color' => $c( '#1d2327' ), 'font_weight' => '600', 'border_style' => 'solid', 'border_width' => $u( 1 ), 'border_color' => $c( '#e2e4e7' ) ),
					'caption' => array( 'color' => $c( '#787c82' ), 'font_size' => $u( 13 ), 'font_style' => 'italic' ),
				),
			),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_table_presets' ) ) :
	/**
	 * The user's saved Table Presets (Theme Settings → Components → Tables) or the
	 * plugin defaults.
	 */
	function unysonplus_get_table_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'table_presets', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_table_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_table_presets', unysonplus_default_table_presets() );
	}
endif;
