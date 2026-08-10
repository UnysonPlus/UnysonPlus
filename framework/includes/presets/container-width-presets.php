<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Container Width presets — defaults + getter + slug map + dropdown choices + width map.
 * Loaded by ../presets.php. The SIXTH converter-populated preset family (alongside button /
 * section-style / box / color / icon-badge presets).
 *
 * A Container Width is a reusable NAMED content-band width the user defines in Theme Settings →
 * Components → Section Styles (below the Section Styles list) and applies via a Section's
 * "Container Width" dropdown (Layout tab). Each preset is just { name, width }; the section view
 * resolves the picked slug → a `max-width` (see section/views/view.php), so a width lives in ONE
 * place and every section that references it updates together.
 *
 * The three built-in defaults keep the slugs `narrow` / `medium` / `wide` (768 / 896 / 1024px),
 * so existing sections that stored `container_width.preset = narrow` render identically with zero
 * migration. The Site Converter can GATHER a source's distinct container widths, cluster them, and
 * add them here (reusing the standard names where the value matches) so a whole converted site
 * shares named widths instead of repeating a custom value per section.
 */

if ( ! function_exists( 'unysonplus_default_container_width_presets' ) ) :
	/**
	 * The three built-in Container Widths, in the shape the `addable-box` schema saves:
	 * width is a unit-input value { value, unit }. Slugs derive from the names →
	 * narrow / medium / wide, matching the old hardcoded section container_width map.
	 */
	function unysonplus_default_container_width_presets() {
		$w = function ( $id, $name, $px ) {
			return array( 'id' => $id, 'width_name' => $name, 'width' => array( 'value' => (string) $px, 'unit' => 'px' ) );
		};
		return array(
			$w( 'cw_narrow', __( 'Narrow', 'fw' ), 768 ),
			$w( 'cw_medium', __( 'Medium', 'fw' ), 896 ),
			$w( 'cw_wide',   __( 'Wide', 'fw' ),   1024 ),
		);
	}
endif;

if ( ! function_exists( 'unysonplus_container_width_preset_slug_map' ) ) :
	/**
	 * [ preset-id => css-slug ] for the current Container Widths, so the stored value is a readable
	 * slug ("Wide" → `wide`). Collisions get -2/-3; an empty name falls back to the id. Shared by
	 * the Container Width dropdown, the width map and the view resolver so value + render agree.
	 */
	function unysonplus_container_width_preset_slug_map() {
		$map  = array();
		$seen = array();
		if ( ! function_exists( 'unysonplus_get_container_width_presets' ) ) { return $map; }
		foreach ( unysonplus_get_container_width_presets() as $cw ) {
			if ( ! is_array( $cw ) || empty( $cw['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $cw['id'] );
			if ( $id === '' ) { continue; }
			$name = isset( $cw['width_name'] ) ? (string) $cw['width_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) { $slug = strtolower( $id ); }
			$base = $slug; $n = 1;
			while ( isset( $seen[ $slug ] ) ) { $n++; $slug = $base . '-' . $n; }
			$seen[ $slug ] = true;
			$map[ $id ] = $slug;
		}
		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_get_container_width_presets' ) ) :
	/** Saved Container Widths (theme-scoped preset store), else the built-in defaults. */
	function unysonplus_get_container_width_presets() {
		if ( function_exists( 'unysonplus_preset_store_get' ) ) {
			$saved = unysonplus_preset_store_get( 'container_width_presets', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_container_width_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_container_width_presets', unysonplus_default_container_width_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_container_width_choices' ) ) :
	/**
	 * Choices for the Section "Container Width" dropdown: Inherit (global width) → one entry per
	 * named width (keyed by SLUG, the stored value) → Custom. Defaults reproduce narrow/medium/wide,
	 * so a section that stored `preset: narrow` still resolves.
	 */
	function unysonplus_container_width_choices() {
		$choices = array( 'inherit' => __( 'Inherit (global width)', 'fw' ) );
		$map = unysonplus_container_width_preset_slug_map();
		foreach ( unysonplus_get_container_width_presets() as $cw ) {
			if ( ! is_array( $cw ) || empty( $cw['id'] ) ) { continue; }
			$id   = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $cw['id'] );
			$slug = isset( $map[ $id ] ) ? $map[ $id ] : '';
			if ( $slug === '' ) { continue; }
			$name = ( isset( $cw['width_name'] ) && $cw['width_name'] !== '' ) ? (string) $cw['width_name'] : $slug;
			$px   = self_upw_cw_width_string( $cw );
			$choices[ $slug ] = $px !== '' ? sprintf( '%s (%s)', $name, $px ) : $name;
		}
		$choices['custom'] = __( 'Custom…', 'fw' );
		return $choices;
	}
endif;

if ( ! function_exists( 'self_upw_cw_width_string' ) ) :
	/** A preset's width as a CSS length string (e.g. "1024px"), or '' when unset. */
	function self_upw_cw_width_string( $cw ) {
		$wv = isset( $cw['width'] ) && is_array( $cw['width'] ) ? $cw['width'] : array();
		$val = isset( $wv['value'] ) ? trim( (string) $wv['value'] ) : '';
		if ( $val === '' || ! is_numeric( $val ) ) { return ''; }
		$unit = isset( $wv['unit'] ) ? preg_replace( '/[^a-z%]/', '', (string) $wv['unit'] ) : 'px';
		if ( $unit === '' ) { $unit = 'px'; }
		return $val . $unit;
	}
endif;

if ( ! function_exists( 'unysonplus_container_width_map' ) ) :
	/** [ slug => "1024px" ] for the section view's inline max-width resolution. */
	function unysonplus_container_width_map() {
		$out = array();
		$map = unysonplus_container_width_preset_slug_map();
		foreach ( unysonplus_get_container_width_presets() as $cw ) {
			if ( ! is_array( $cw ) || empty( $cw['id'] ) ) { continue; }
			$id   = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $cw['id'] );
			$slug = isset( $map[ $id ] ) ? $map[ $id ] : '';
			$px   = self_upw_cw_width_string( $cw );
			if ( $slug !== '' && $px !== '' ) { $out[ $slug ] = $px; }
		}
		// Safety net: the built-in slugs always resolve even if the library was emptied.
		$out += array( 'narrow' => '768px', 'medium' => '896px', 'wide' => '1024px' );
		return $out;
	}
endif;
