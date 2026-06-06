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
		else                          { $scale = 1.00; }

		$scale = apply_filters( 'unysonplus_mobile_font_scale', $scale, $desktop_px, $context );

		return max( 14, round( $desktop_px * $scale ) );
	}
endif;
