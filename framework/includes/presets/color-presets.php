<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Color presets — defaults + getter + slug map. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_default_color_presets' ) ) :
	function unysonplus_default_color_presets() {
		return apply_filters( 'unysonplus_default_color_presets', array(
			// Semantic Bootstrap-derived colours — prepended so they're the
			// FIRST picks across every shortcode's Color Preset selector.
			// Sites that customise these via Theme Settings → Color Presets
			// flow through the unysonplus-presets stylesheet automatically
			// (the `text-primary` / `bg-primary` utility classes override
			// Bootstrap's own definitions via :root specificity + !important).
			array( 'name' => 'Primary',     'color' => '#0d6efd' ),
			array( 'name' => 'Secondary',   'color' => '#6c757d' ),
			array( 'name' => 'Accent',      'color' => '#fd7e14' ),
			array( 'name' => 'Muted',       'color' => '#adb5bd' ),
			array( 'name' => 'Black',       'color' => '#000'    ),
			array( 'name' => 'White',       'color' => '#fff'    ),
			array( 'name' => 'Gray',        'color' => '#636c72' ),
			array( 'name' => 'Light Gray',  'color' => '#bdbdbd' ),
			array( 'name' => 'Red',         'color' => '#dc3545' ),
			array( 'name' => 'Pink',        'color' => '#e91e63' ),
			array( 'name' => 'Purple',      'color' => '#9c27b0' ),
			array( 'name' => 'Deep Purple', 'color' => '#673ab7' ),
			array( 'name' => 'Indigo',      'color' => '#3f51b5' ),
			array( 'name' => 'Blue',        'color' => '#286090' ),
			array( 'name' => 'Light Blue',  'color' => '#03a9f4' ),
			array( 'name' => 'Cyan',        'color' => '#00bcd4' ),
			array( 'name' => 'Teal',        'color' => '#009688' ),
			array( 'name' => 'Green',       'color' => '#5cb85c' ),
			array( 'name' => 'Light Green', 'color' => '#8bc34a' ),
			array( 'name' => 'Lime',        'color' => '#cddc39' ),
			array( 'name' => 'Yellow',      'color' => '#ffeb3b' ),
			array( 'name' => 'Amber',       'color' => '#ffc107' ),
			array( 'name' => 'Orange',      'color' => '#ff9800' ),
			array( 'name' => 'Deep Orange', 'color' => '#ff5722' ),
			array( 'name' => 'Brown',       'color' => '#795548' ),
			array( 'name' => 'Blue Gray',   'color' => '#607d8b' ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_color_presets' ) ) :
	function unysonplus_get_color_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'theme_colors', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_color_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_color_presets', unysonplus_default_color_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_color_preset_slug_map' ) ) :
	/**
	 * Returns a flat [ slug => hex ] lookup of the current Color Presets.
	 * Slug is derived from the preset name the same way css-tokens.php emits
	 * `:root --color-{slug}` (lower, strip non-alnum, join with '-').
	 */
	function unysonplus_color_preset_slug_map() {
		$out = array();
		if ( ! function_exists( 'unysonplus_get_color_presets' ) ) { return $out; }
		foreach ( unysonplus_get_color_presets() as $entry ) {
			if ( empty( $entry['name'] ) || empty( $entry['color'] ) ) { continue; }
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $entry['name'] ) ), '-' );
			if ( $slug === '' ) { continue; }
			$out[ $slug ] = $entry['color'];
		}
		return $out;
	}
endif;
