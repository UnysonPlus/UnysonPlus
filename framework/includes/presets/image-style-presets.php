<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Image Style presets — reusable image treatments (radius / crop, clip + SVG
 * masks, CSS filters, and a legibility scrim) applied to any element's image as a
 * scoped `.imgs-{slug}` class. Loaded by ../presets.php. Stored THEME-SCOPED under
 * the `image_styles` key (Components → Image Styles addable-box).
 *
 * ARCHITECTURE — token bundles. Each preset emits ONLY a set of CSS custom
 * properties (`.imgs-{slug}{ --imgs-radius:…; --imgs-filter:… }`); one shared base
 * rule (`.imgs-wrap`, generated once in css-tokens.php) consumes them. That keeps
 * the system robust (one base rule, hard to break), flexible (any combination),
 * and customizable — a power user overrides a single var in an element's
 * Custom CSS (Advanced) rather than fighting specificity. The curated fields below
 * cover ~95%; the long tail is the Custom CSS escape hatch.
 *
 * Each preset entry:
 *   array(
 *     'id'          => string,  // unique
 *     'style_name'  => string,  // label -> .imgs-{slug}
 *     'aspect'      => string,  // auto | 1-1 | 4-3 | 3-4 | 16-9 | 3-2
 *     'shape'       => string,  // rectangle | rounded | circle
 *     'radius'      => string,  // unit (used when shape = rounded)
 *     'mask'        => string,  // none | diagonal | hexagon | arch | blob
 *     'filter'      => string,  // none | grayscale | sepia | contrast | saturate | blur | duotone
 *     'duo_color'   => mixed,   // compact-color value (used when filter = duotone)
 *     'scrim'       => string,  // none | top | bottom | radial
 *     'scrim_color' => mixed,   // compact-color value (used when scrim != none)
 *   )
 *
 * NOTE (hover): animated hover (zoom / pan / tilt / grayscale-on-hover) is the
 * Animation Engine's Hover module — deliberately NOT duplicated here.
 */

if ( ! function_exists( 'unysonplus_default_image_style_presets' ) ) :
	/** Returns the default filterable list of image style presets (borders, shadows, shapes/masks). */
	function unysonplus_default_image_style_presets() {
		// Compact-color helper: build a { predefined, custom } value from a hex/rgba.
		$col = function ( $custom, $predefined = '' ) {
			return array( 'predefined' => $predefined, 'custom' => $custom );
		};
		// The Shape / Mask control is a multi-picker → value shape { mask:'key', custom:{…} }.
		$mask = function ( $key ) {
			return array( 'mask' => $key, 'custom' => array( 'custom_svg' => '', 'custom_clip' => '' ) );
		};
		$base = array(
			'aspect' => 'auto', 'radius' => '',
			'mask' => $mask( 'none' ),
			'filter' => 'none', 'duo_color' => $col( '' ),
			'scrim' => 'none', 'scrim_color' => $col( '' ),
		);
		$p = function ( $id, $name, $over ) use ( $base ) {
			return array_merge( $base, array( 'id' => $id, 'style_name' => $name ), $over );
		};

		// Shape / corners come from the shared mask library (mask key); `radius` is a
		// simple custom-rounding used only when mask = none.
		return array(
			$p( 'rounded',   __( 'Rounded', 'fw' ),        array( 'mask' => $mask( 'rounded' ) ) ),
			$p( 'circle',    __( 'Circle', 'fw' ),         array( 'mask' => $mask( 'circle' ) ) ),
			$p( 'pill',      __( 'Pill', 'fw' ),           array( 'radius' => '999px' ) ),
			$p( 'portrait',  __( 'Portrait Card', 'fw' ),  array( 'aspect' => '3-4', 'radius' => '12px' ) ),
			$p( 'monochrome',__( 'Monochrome', 'fw' ),     array( 'radius' => '12px', 'filter' => 'grayscale' ) ),
			$p( 'duotone',   __( 'Duotone', 'fw' ),        array( 'radius' => '12px', 'filter' => 'duotone', 'duo_color' => $col( '#2f74e6' ) ) ),
			$p( 'diagonal',  __( 'Diagonal', 'fw' ),       array( 'mask' => $mask( 'diagonal' ) ) ),
			$p( 'hexagon',   __( 'Hexagon', 'fw' ),        array( 'mask' => $mask( 'hexagon' ) ) ),
			$p( 'cinematic', __( 'Cinematic', 'fw' ),      array( 'aspect' => '16-9', 'radius' => '10px', 'scrim' => 'bottom', 'scrim_color' => $col( '#0b0b0f' ) ) ),
		);
	}
endif;

if ( ! function_exists( 'unysonplus_get_image_style_presets' ) ) :
	/** Saved image styles (theme-scoped) or the defaults when nothing is saved yet. */
	function unysonplus_get_image_style_presets() {
		$saved = function_exists( 'unysonplus_preset_store_get' )
			? unysonplus_preset_store_get( 'image_styles', null )
			: null;
		if ( is_array( $saved ) ) {
			return $saved;
		}
		return unysonplus_default_image_style_presets();
	}
endif;

if ( ! function_exists( 'unysonplus_image_style_preset_slug_map' ) ) :
	/**
	 * [ preset-id => css-slug ] so a style named "Portrait Card" → `.imgs-portrait-card`.
	 * Slug = lower-case, non-alphanumerics → '-', trimmed; collisions get a numeric
	 * suffix in preset order; empty/symbol-only names fall back to the sanitized id.
	 * Single source of truth for the `.imgs-{slug}` class — shared by the css-tokens
	 * generation and the consumption picker (sc_get_image_style_choices()).
	 */
	function unysonplus_image_style_preset_slug_map() {
		$map  = array();
		$seen = array();
		foreach ( unysonplus_get_image_style_presets() as $s ) {
			if ( ! is_array( $s ) || empty( $s['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $s['id'] );
			if ( $id === '' ) { continue; }
			$name = isset( $s['style_name'] ) ? (string) $s['style_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) { $slug = strtolower( $id ); }
			$b = $slug; $n = 2;
			while ( isset( $seen[ $slug ] ) ) { $slug = $b . '-' . $n; $n++; }
			$seen[ $slug ] = true;
			$map[ $id ]    = $slug;
		}
		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_maybe_add_pill_image_style' ) ) :
	/**
	 * Non-destructive migration: ensure the "Pill" shape preset exists in a site's SAVED
	 * image styles (Pill was added to the defaults in this version). Sites that never
	 * customised Image Styles already get it from the defaults; this only backfills sites
	 * with a saved set that predates Pill. Runs once on `admin_init`, guarded by an option.
	 */
	function unysonplus_maybe_add_pill_image_style() {
		if ( get_option( 'unysonplus_image_styles_pill_added' ) ) { return; }
		if ( ! function_exists( 'unysonplus_preset_store_get' ) || ! function_exists( 'unysonplus_preset_store_set' ) ) { return; }

		$saved = unysonplus_preset_store_get( 'image_styles', null );
		// No saved set → the defaults (which include Pill) are in effect; nothing to do.
		if ( ! is_array( $saved ) || empty( $saved ) ) {
			update_option( 'unysonplus_image_styles_pill_added', '1' );
			return;
		}
		foreach ( $saved as $s ) {
			if ( is_array( $s ) && isset( $s['id'] ) && 'pill' === $s['id'] ) {
				update_option( 'unysonplus_image_styles_pill_added', '1' );
				return; // already present (user may have added their own)
			}
		}
		// Pull the default Pill preset and insert it right after Circle (else append).
		$pill = null;
		foreach ( unysonplus_default_image_style_presets() as $d ) {
			if ( isset( $d['id'] ) && 'pill' === $d['id'] ) { $pill = $d; break; }
		}
		if ( is_array( $pill ) ) {
			$out = array();
			$inserted = false;
			foreach ( $saved as $s ) {
				$out[] = $s;
				if ( ! $inserted && is_array( $s ) && isset( $s['id'] ) && 'circle' === $s['id'] ) {
					$out[]    = $pill;
					$inserted = true;
				}
			}
			if ( ! $inserted ) { $out[] = $pill; }
			unysonplus_preset_store_set( 'image_styles', $out );
		}
		update_option( 'unysonplus_image_styles_pill_added', '1' );
	}
endif;
add_action( 'admin_init', 'unysonplus_maybe_add_pill_image_style' );
