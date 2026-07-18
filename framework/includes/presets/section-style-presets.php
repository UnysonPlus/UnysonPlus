<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Section Style presets — defaults + getter + slug map + dropdown choices + resolver.
 * Loaded by ../presets.php.
 *
 * A Section Style is a reusable section "skin" the user defines in Theme Settings →
 * Components → Section Styles and applies via a Section's "Section Variant" dropdown.
 * Each preset produces a `.section--{slug}` class (slug derived from the name), so the
 * three built-in defaults keep the slugs `alt` / `light` / `dark` — existing sections
 * that stored `variant: dark` render identically with zero migration.
 *
 * A preset carries: a Background-Pro fill (color / gradient / image), Text / Heading /
 * Link colors (compact color-preset values), its own Border (style / width / color /
 * radius) and a Padding size (Spacing scale). css-tokens.php turns each into a
 * `.section--{slug}` rule; the section view composes nothing else, so a skin never
 * fights a section's own one-off Background / Spacing (those are emitted with higher
 * precedence and win).
 */

if ( ! function_exists( 'unysonplus_section_style_preset_slug_map' ) ) :
	/**
	 * [ preset-id => css-slug ] for the current Section Styles, so the generated class
	 * is readable ("Dark" → `.section--dark`). Mirrors unysonplus_border_preset_slug_map()
	 * (collisions get -2/-3; an empty name falls back to the id). Shared by css-tokens.php
	 * (rule generation), the Section Variant dropdown and the view resolver so class +
	 * value + render always agree.
	 */
	function unysonplus_section_style_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_section_style_presets' ) ) { return $map; }

		foreach ( unysonplus_get_section_style_presets() as $sp ) {
			if ( ! is_array( $sp ) || empty( $sp['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $sp['id'] );
			if ( $id === '' ) { continue; }

			$name = isset( $sp['style_name'] ) ? (string) $sp['style_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) { $slug = strtolower( $id ); }

			$base = $slug;
			$n    = 1;
			while ( isset( $seen[ $slug ] ) ) { $n++; $slug = $base . '-' . $n; }
			$seen[ $slug ] = true;

			$map[ $id ] = $slug;
		}

		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_default_section_style_presets' ) ) :
	/**
	 * The three built-in Section Styles, in the shape the `addable-box` schema saves:
	 * background is a background-pro value; text/heading/link colors are compact-picker
	 * values { predefined, custom }; border_* are scalar/unit-input; padding is a
	 * spacing value (mode 'padding'). Colours reproduce the previous hardcoded
	 * `.section--alt|light|dark` CSS exactly, so nothing changes on upgrade.
	 */
	function unysonplus_default_section_style_presets() {
		$hex    = function ( $h ) { return array( 'predefined' => '', 'custom' => (string) $h ); };
		$empty  = array( 'predefined' => '', 'custom' => '' );
		$bg     = function ( $h ) { return array( 'color' => array( 'value' => array( 'predefined' => '', 'custom' => (string) $h ) ) ); };
		$u0     = array( 'value' => '', 'unit' => 'px' );
		$pad0   = array(
			'margin'  => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
			'padding' => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
		);
		$skin   = function ( $id, $name, $bgv, $text, $heading, $link ) use ( $empty, $u0, $pad0 ) {
			return array(
				'id'            => $id,
				'style_name'    => $name,
				'background'    => $bgv,
				'text_color'    => $text,
				'heading_color' => $heading,
				'link_color'    => $link,
				// Border is now one combined multi-inline row { width, style, color },
				// applied to the sides in border_sides (default all four) at the reach in
				// border_extent (default full). css-tokens.php reads these; a missing
				// border_sides also defaults to all four, so old saves render unchanged.
				'border'        => array( 'width' => $u0, 'style' => '', 'color' => $empty ),
				'border_sides'  => array( 'top', 'right', 'bottom', 'left' ),
				'border_extent' => array( 'mode' => 'full' ),
				'border_radius' => $u0,
				'padding'       => $pad0,
			);
		};

		return apply_filters( 'unysonplus_default_section_style_presets', array(
			// Alt — subtle off-white band for alternating rhythm (inherits theme text).
			$skin( 's000000001', 'Alt',   $bg( '#f7f7f7' ), $empty,        $empty,        $empty ),
			// Light — force a light scheme (light bg + dark text).
			$skin( 's000000002', 'Light', $bg( '#ffffff' ), $hex( '#1a1a1a' ), $empty,     $empty ),
			// Dark — force a dark scheme (dark bg + light text + light-blue links).
			$skin( 's000000003', 'Dark',  $bg( '#1a1a1a' ), $hex( '#ffffff' ), $hex( '#ffffff' ), $hex( '#93c5fd' ) ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_section_style_normalize_border' ) ) :
	/**
	 * Fold a preset's legacy flat border leaves (border_style / border_width /
	 * border_color) into the combined `border` => { width, style, color } shape used
	 * by the multi-inline Border row. A no-op once `border` is already present. Keeps
	 * the reader/consumer working on presets saved before the combine.
	 */
	function unysonplus_section_style_normalize_border( $sp ) {
		if ( ! is_array( $sp ) ) { return $sp; }
		$has_combined = isset( $sp['border'] ) && is_array( $sp['border'] )
			&& ( isset( $sp['border']['width'] ) || isset( $sp['border']['style'] ) || isset( $sp['border']['color'] ) );
		if ( ! $has_combined && ( isset( $sp['border_style'] ) || isset( $sp['border_width'] ) || isset( $sp['border_color'] ) ) ) {
			$w = isset( $sp['border_width'] ) ? $sp['border_width'] : array( 'value' => '', 'unit' => 'px' );
			if ( ! is_array( $w ) ) { $w = array( 'value' => trim( (string) $w ), 'unit' => 'px' ); }
			$c = isset( $sp['border_color'] ) ? $sp['border_color'] : array( 'predefined' => '', 'custom' => '' );
			if ( is_string( $c ) ) { $c = array( 'predefined' => $c, 'custom' => '' ); }
			elseif ( ! is_array( $c ) ) { $c = array( 'predefined' => '', 'custom' => '' ); }
			$sp['border'] = array(
				'width' => $w,
				'style' => isset( $sp['border_style'] ) ? (string) $sp['border_style'] : '',
				'color' => $c,
			);
		}
		unset( $sp['border_style'], $sp['border_width'], $sp['border_color'] );
		return $sp;
	}
endif;

if ( ! function_exists( 'unysonplus_get_section_style_presets' ) ) :
	/**
	 * The user's saved Section Styles (Theme Settings → Components → Section Styles)
	 * or the plugin defaults. Border shape is normalized to the combined row so
	 * consumers only read `$sp['border']`.
	 */
	function unysonplus_get_section_style_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'section_style_presets', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				$saved = array_map( 'unysonplus_section_style_normalize_border', $saved );
				return apply_filters( 'unysonplus_section_style_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_section_style_presets', unysonplus_default_section_style_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_migrate_section_style_border_row' ) ) :
	/**
	 * One-time migration: fold each saved Section Style's legacy flat border leaves
	 * (border_style / border_width / border_color) into the combined `border` row, so
	 * the editor reflects the value and a re-save doesn't drop it. Writes the theme-
	 * scoped settings blob directly (same seam as the preset store), gated by a flag.
	 * Runs after the theme-store migration (priority 20) so it sees the moved presets.
	 */
	function unysonplus_migrate_section_style_border_row() {
		if ( get_option( 'upw_section_border_row_migrated' ) || ! class_exists( 'FW_WP_Option' ) || ! function_exists( 'fw' ) ) {
			return;
		}
		$theme_key = 'fw_theme_settings_options:' . fw()->theme->manifest->get_id();
		$current   = (array) FW_WP_Option::get( $theme_key, null, array() );
		if ( isset( $current['section_style_presets'] ) && is_array( $current['section_style_presets'] ) ) {
			$changed = false;
			foreach ( $current['section_style_presets'] as $i => $sp ) {
				if ( is_array( $sp )
					&& ( isset( $sp['border_style'] ) || isset( $sp['border_width'] ) || isset( $sp['border_color'] ) )
					&& ! ( isset( $sp['border'] ) && is_array( $sp['border'] ) ) ) {
					$current['section_style_presets'][ $i ] = unysonplus_section_style_normalize_border( $sp );
					$changed = true;
				}
			}
			if ( $changed ) {
				FW_WP_Option::set( $theme_key, null, $current );
			}
		}
		update_option( 'upw_section_border_row_migrated', 1 );
	}
	add_action( 'init', 'unysonplus_migrate_section_style_border_row', 21 );
endif;

if ( ! function_exists( 'unysonplus_section_style_choices' ) ) :
	/**
	 * Choices for the Section's "Section Variant" dropdown: '' => Default, then one
	 * entry per style keyed by its SLUG (the stored value — scalar, so converting the
	 * old hardcoded select to this is not a value-shape change).
	 */
	function unysonplus_section_style_choices() {
		$choices = array( '' => __( 'Default', 'fw' ) );
		if ( ! function_exists( 'unysonplus_get_section_style_presets' ) ) { return $choices; }

		$map = unysonplus_section_style_preset_slug_map();
		foreach ( unysonplus_get_section_style_presets() as $sp ) {
			if ( ! is_array( $sp ) || empty( $sp['id'] ) ) { continue; }
			$id   = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $sp['id'] );
			$slug = isset( $map[ $id ] ) ? $map[ $id ] : '';
			if ( $slug === '' ) { continue; }
			$name = ( isset( $sp['style_name'] ) && $sp['style_name'] !== '' ) ? (string) $sp['style_name'] : $slug;
			$choices[ $slug ] = $name;
		}
		return $choices;
	}
endif;

if ( ! function_exists( 'unysonplus_get_section_style_by_slug' ) ) :
	/** Resolve a slug (a section's stored `variant`) back to its full preset, or null. */
	function unysonplus_get_section_style_by_slug( $slug ) {
		$slug = (string) $slug;
		if ( $slug === '' ) { return null; }

		$map = unysonplus_section_style_preset_slug_map();
		foreach ( unysonplus_get_section_style_presets() as $sp ) {
			if ( ! is_array( $sp ) || empty( $sp['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $sp['id'] );
			if ( isset( $map[ $id ] ) && $map[ $id ] === $slug ) { return $sp; }
		}
		return null;
	}
endif;
