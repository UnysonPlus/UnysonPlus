<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Font-size presets — defaults + getter + mobile auto-scaler. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_default_font_size_presets' ) ) :
	function unysonplus_default_font_size_presets() {
		return apply_filters( 'unysonplus_default_font_size_presets', array(
			array( 'name' => 'Display 1', 'size' => '96', 'class' => 'display-1' ),
			array( 'name' => 'Display 2', 'size' => '88', 'class' => 'display-2' ),
			array( 'name' => 'Display 3', 'size' => '72', 'class' => 'display-3' ),
			array( 'name' => 'Display 4', 'size' => '56', 'class' => 'display-4' ),
			array( 'name' => 'Display 5', 'size' => '48', 'class' => 'display-5' ),
			array( 'name' => 'Lead',      'size' => '22', 'class' => 'lead' ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_font_size_presets' ) ) :
	function unysonplus_get_font_size_presets() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'font_sizes', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_font_size_presets', $saved );
			}
		}
		return apply_filters( 'unysonplus_font_size_presets', unysonplus_default_font_size_presets() );
	}
endif;

if ( ! function_exists( 'unysonplus_mobile_font_size_scale' ) ) :
	/**
	 * Tiered auto-reducer for mobile font sizes. Returns the mobile px value
	 * for a given desktop px size. Display text shrinks aggressively; body
	 * text stays at desktop size. Floors at 14px for a11y.
	 *
	 * @param int|float $desktop_px Desktop pixel value (number, no unit).
	 * @param string    $context    Optional tag for filter consumers (e.g. 'h1', 'body').
	 */
	function unysonplus_mobile_font_size_scale( $desktop_px, $context = '' ) {
		$desktop_px = floatval( $desktop_px );
		if ( $desktop_px <= 0 ) { return $desktop_px; }

		if      ( $desktop_px >= 60 ) { $scale = 0.60; }
		elseif  ( $desktop_px >= 32 ) { $scale = 0.75; }
		elseif  ( $desktop_px >= 20 ) { $scale = 0.85; }
		elseif  ( $desktop_px >= 16 ) { $scale = 0.90; } // gentle step-down so h4–h6-scale sizes (16–19px) still go fluid
		else                          { $scale = 1.00; } // < 16px is already at the a11y floor — stays fixed

		$scale = apply_filters( 'unysonplus_mobile_font_scale', $scale, $desktop_px, $context );

		return max( 14, round( $desktop_px * $scale ) );
	}
endif;

if ( ! function_exists( 'unysonplus_fluid_type_range' ) ) :
	/**
	 * Viewport range (px) over which fluid type interpolates: at/below `min` the
	 * mobile floor applies, at/above `max` the authored size applies. Filterable.
	 */
	function unysonplus_fluid_type_range() {
		$r   = apply_filters( 'unysonplus_fluid_type_viewport', array( 'min' => 360, 'max' => 1280 ) );
		$min = isset( $r['min'] ) ? floatval( $r['min'] ) : 360;
		$max = isset( $r['max'] ) ? floatval( $r['max'] ) : 1280;
		if ( $max <= $min ) { $max = $min + 1; }
		return array( 'min' => $min, 'max' => $max );
	}
endif;

if ( ! function_exists( 'unysonplus_font_size_to_px' ) ) :
	/**
	 * Parse a CSS length expressed in px / rem / em into a px number (rem/em use
	 * the 16px root assumption). Returns null for anything else — calc(), clamp(),
	 * %, vw, unitless — so those values are left untouched by the fluid rewriter.
	 */
	function unysonplus_font_size_to_px( $value, $root_px = 16 ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^(\d+(?:\.\d+)?)px$/', $value, $m ) )   { return floatval( $m[1] ); }
		if ( preg_match( '/^(\d+(?:\.\d+)?)r?em$/', $value, $m ) ) { return floatval( $m[1] ) * $root_px; }
		return null;
	}
endif;

if ( ! function_exists( 'unysonplus_fluid_font_clamp' ) ) :
	/**
	 * Build an accessibility-safe fluid clamp() that scales a font size from a
	 * mobile floor (`$min_px`) up to the authored size (`$max_px`) across the
	 * fluid viewport range. The preferred value is `rem + vw` — NOT vw alone —
	 * so browser zoom and the user's font-size preference still scale the text
	 * (WCAG 2.1 SC 1.4.4). The clamp max is in rem for the same reason. Returns
	 * null when there is nothing to interpolate (min >= max, e.g. body text).
	 *
	 * @param int|float $max_px Authored (desktop) size in px.
	 * @param int|float $min_px Mobile floor in px.
	 * @param int       $root_px Root font-size assumption for px→rem (default 16).
	 * @return string|null clamp(...) or null.
	 */
	function unysonplus_fluid_font_clamp( $max_px, $min_px, $root_px = 16 ) {
		$max_px = floatval( $max_px );
		$min_px = floatval( $min_px );
		if ( $max_px <= 0 || $min_px <= 0 || $min_px >= $max_px ) { return null; }

		$vp     = unysonplus_fluid_type_range();
		$min_vw = $vp['min'];
		$max_vw = $vp['max'];

		// Linear interpolation between (min_vw, min_px) and (max_vw, max_px).
		$slope     = ( $max_px - $min_px ) / ( $max_vw - $min_vw ); // px per px of viewport
		$intercept = $min_px - $slope * $min_vw;                    // px at viewport 0

		$min_rem   = round( $min_px / $root_px, 4 );
		$max_rem   = round( $max_px / $root_px, 4 );
		$icept_rem = round( $intercept / $root_px, 4 );
		$vw_coeff  = round( $slope * 100, 4 ); // slope (px/px) → vw

		// vw_coeff is always positive here (max_px > min_px, max_vw > min_vw); a
		// negative intercept simply reads as "-Xrem + Yvw", which is valid CSS.
		$preferred = $icept_rem . 'rem + ' . $vw_coeff . 'vw';

		return 'clamp(' . $min_rem . 'rem, ' . $preferred . ', ' . $max_rem . 'rem)';
	}
endif;

/* ===================================================================== *
 * Phase 1 — the type-scale engine.
 *
 * A modular-scale generator (the Utopia model): from a base size, a scale
 * ratio at each end of the viewport range, and a step count, it produces a
 * coherent set of fluid clamp() steps. Headings/elements reference the steps
 * rather than each carrying an independent hand-tuned size, which keeps the
 * whole scale consistent. Typography V3 authors this from the UI; here it is
 * pure, side-effect-free math so it can be unit-tested and reused by both the
 * plugin and the theme.
 * ===================================================================== */

if ( ! function_exists( 'unysonplus_type_scale_config' ) ) :
	/**
	 * Default type-scale configuration, filterable. `ratio` is the step
	 * multiplier at the max viewport (desktop); `ratio_mobile` is the gentler
	 * multiplier at the min viewport (so headings shrink more than body on
	 * small screens). Viewport range comes from unysonplus_fluid_type_range().
	 */
	function unysonplus_type_scale_config() {
		$vp = unysonplus_fluid_type_range();
		return apply_filters( 'unysonplus_type_scale_config', array(
			'base_px'       => 16,     // body size at the max viewport
			'ratio'         => 1.25,   // desktop step ratio (major third)
			'ratio_mobile'  => 1.2,    // mobile step ratio (minor third)
			'min_vw'        => $vp['min'],
			'max_vw'        => $vp['max'],
			'body_floor_px' => 14,     // a11y floor for the min endpoint
			'steps_up'      => 6,      // steps above base (…→ display sizes)
			'steps_down'    => 2,      // steps below base (small / caption)
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_fluid_type_a11y_report' ) ) :
	/**
	 * WCAG 1.4.4 (resize text to 200%) guardrail / readout for one fluid step.
	 *
	 * Because the clamp min AND max are rem-anchored, browser zoom always
	 * doubles both endpoints, so the size can reach 200%. The residual risk is
	 * a strongly negative rem intercept in the preferred value: that means the
	 * rem term fights the vw term and mid-range sizes barely respond to zoom —
	 * the tell of an over-steep curve (max far larger than min over a wide
	 * viewport range). We flag that so the UI can warn "widen the range or
	 * lower the ratio". Returns { ok, intercept_px, zoom_ratio, note }.
	 */
	function unysonplus_fluid_type_a11y_report( $max_px, $min_px, $root_px = 16 ) {
		$max_px = floatval( $max_px );
		$min_px = floatval( $min_px );
		if ( $min_px >= $max_px || $max_px <= 0 ) {
			return array( 'ok' => true, 'intercept_px' => $max_px, 'zoom_ratio' => 2.0, 'note' => 'fixed size — zoom scales it directly' );
		}
		$vp    = unysonplus_fluid_type_range();
		$slope = ( $max_px - $min_px ) / ( $vp['max'] - $vp['min'] );
		$icept = $min_px - $slope * $vp['min']; // px intercept of the preferred line
		// zoom_ratio: how much the rem-anchored max grows at 200% zoom (always 2×
		// for a rem max — kept explicit for the readout). ok flags the steep case.
		$ok    = ( $icept >= 0 );
		return array(
			'ok'           => $ok,
			'intercept_px' => round( $icept, 2 ),
			'zoom_ratio'   => 2.0,
			'note'         => $ok ? 'passes 200% zoom' : 'over-steep curve — widen the viewport range or lower the ratio',
		);
	}
endif;

if ( ! function_exists( 'unysonplus_generate_type_scale' ) ) :
	/**
	 * Build the modular scale. Returns an ordered map of step-key => info:
	 *   { n, max_px, min_px, value (clamp string or rem for a fixed step), a11y }.
	 * Step keys: 'n2','n1' (below base), '0' (base), '1'..'N' (above base).
	 *
	 * @param array $opts Overrides merged over unysonplus_type_scale_config().
	 */
	function unysonplus_generate_type_scale( $opts = array() ) {
		$c = array_merge( unysonplus_type_scale_config(), is_array( $opts ) ? $opts : array() );

		$base  = floatval( $c['base_px'] );
		$rmax  = floatval( $c['ratio'] );
		$rmin  = floatval( $c['ratio_mobile'] );
		$floor = floatval( $c['body_floor_px'] );
		$up    = max( 0, intval( $c['steps_up'] ) );
		$down  = max( 0, intval( $c['steps_down'] ) );

		$steps = array();
		for ( $n = -$down; $n <= $up; $n++ ) {
			$max = $base * pow( $rmax, $n );
			$min = $base * pow( $rmin, $n );
			// Never let the mobile endpoint exceed desktop (true for down-steps,
			// where the gentler mobile ratio would otherwise overshoot), then floor
			// it for a11y — but never ABOVE max, so small caption steps (whose max is
			// already below the floor) stay a fixed small size instead of inverting.
			$min = min( $min, $max );
			$min = max( $min, min( $floor, $max ) );

			$key = ( $n === 0 ) ? '0' : ( $n > 0 ? (string) $n : 'n' . abs( $n ) );

			$value = ( $min < $max )
				? unysonplus_fluid_font_clamp( $max, $min )
				: round( $max / 16, 4 ) . 'rem';

			// Suggested line-height: tight for large display sizes, progressively
			// looser as the size shrinks toward body/caption (a readable default the
			// role tokens carry; authored per-heading line-heights still override).
			$line_height = round( max( 1.1, min( 1.6, 1.7 - 0.012 * $max ) ), 3 );

			$steps[ $key ] = array(
				'n'           => $n,
				'max_px'      => round( $max, 2 ),
				'min_px'      => round( $min, 2 ),
				'value'       => $value,
				'line_height' => $line_height,
				'a11y'        => unysonplus_fluid_type_a11y_report( $max, $min ),
			);
		}
		return $steps;
	}
endif;
