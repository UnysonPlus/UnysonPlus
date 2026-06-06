<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Spacing scale + Gap scale (+ default-gap getters). Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_default_spacing_scale' ) ) :
	/**
	 * Returns the default spacing scale as an array of {name, size} entries.
	 * Names 0–5 match Bootstrap's stock spacer scale; sites can extend beyond
	 * via Theme Settings → General → Spacing.
	 */
	function unysonplus_default_spacing_scale() {
		return apply_filters( 'unysonplus_default_spacing_scale', array(
			array( 'name' => '0', 'size' => '0' ),
			array( 'name' => '1', 'size' => '0.25rem' ),
			array( 'name' => '2', 'size' => '0.5rem' ),
			array( 'name' => '3', 'size' => '1rem' ),
			array( 'name' => '4', 'size' => '1.5rem' ),
			array( 'name' => '5', 'size' => '3rem' ),
			array( 'name' => '6', 'size' => '3.5rem' ),
			array( 'name' => '7', 'size' => '4rem' ),
			array( 'name' => '8', 'size' => '4.5rem' ),
			array( 'name' => '9', 'size' => '5rem' ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_spacing_scale' ) ) :
	/**
	 * Returns the spacing scale as an array of {name, size} entries. Each entry's
	 * name slug becomes the suffix of Bootstrap-style utility classes (.m-{slug},
	 * .p-{slug}, .mt-{slug}, etc.) emitted by the bridge. Theme override (Theme
	 * Settings → General → Spacing) takes precedence over plugin defaults.
	 *
	 * Defensive migration: if a site has Phase-1-era flat-dict data saved
	 * ({sp_0 => '...', sp_1 => '...'}), this getter transparently converts it
	 * to the entry-array shape on read.
	 */
	function unysonplus_get_spacing_scale() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'spacing_scale', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				// Phase-1 flat-dict shape — migrate to entry array
				if ( isset( $saved['sp_0'] ) || isset( $saved['sp_1'] ) ) {
					$migrated = array();
					for ( $i = 0; $i <= 5; $i++ ) {
						$key = 'sp_' . $i;
						if ( isset( $saved[ $key ] ) && $saved[ $key ] !== '' ) {
							$migrated[] = array( 'name' => (string) $i, 'size' => $saved[ $key ] );
						}
					}
					return apply_filters( 'unysonplus_spacing_scale', $migrated );
				}
				// Phase 2.5 entry-array shape — return as-is
				return apply_filters( 'unysonplus_spacing_scale', $saved );
			}
		}
		return apply_filters( 'unysonplus_spacing_scale', unysonplus_default_spacing_scale() );
	}
endif;

/* -----------------------------------------------------------------------------
 * Gap scale (Bootstrap row gutter / CSS grid `gap`)
 *
 * Separate scale from `spacing_scale` because the practical range for column
 * gaps tops out around 3rem — anything bigger is section-level spacing, not
 * a gap. Defaults mirror Bootstrap 5's `$spacers` so `g-{slug}` mental models
 * line up. Site overrides via Theme Settings → General → Spacing → Gaps.
 *
 * Slugs become:
 *   - CSS variables                  (e.g. `--gap-3`)
 *   - section modifier classes       (e.g. `.section--gap-3`)
 *   - row utility classes            (e.g. `.g-3`, `.gx-3`, `.gy-3`)
 *
 * The Default Gap getters return the slug picked in Theme Settings, or `''`
 * meaning "no site-wide override — leave Bootstrap's stock gutter behaviour
 * (1.5rem horizontal, 0 vertical) alone."
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'unysonplus_default_gap_scale' ) ) :
	/**
	 * Default gap scale — Bootstrap 5's `$spacers` verbatim. Caps at 3rem
	 * because gaps beyond that are section-level spacing in disguise; users
	 * who really want a 4rem gutter can add it via Theme Settings.
	 */
	function unysonplus_default_gap_scale() {
		return apply_filters( 'unysonplus_default_gap_scale', array(
			array( 'name' => '0', 'size' => '0'       ),
			array( 'name' => '1', 'size' => '0.25rem' ),
			array( 'name' => '2', 'size' => '0.5rem'  ),
			array( 'name' => '3', 'size' => '1rem'    ),
			array( 'name' => '4', 'size' => '1.5rem'  ),
			array( 'name' => '5', 'size' => '3rem'    ),
		) );
	}
endif;

if ( ! function_exists( 'unysonplus_get_gap_scale' ) ) :
	/**
	 * Returns the live gap scale ({name, size} entries). Theme Settings
	 * override takes precedence over plugin defaults — same pattern as
	 * `unysonplus_get_spacing_scale()`.
	 */
	function unysonplus_get_gap_scale() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'gap_scale', null );
			if ( is_array( $saved ) && ! empty( $saved ) ) {
				return apply_filters( 'unysonplus_gap_scale', $saved );
			}
		}
		return apply_filters( 'unysonplus_gap_scale', unysonplus_default_gap_scale() );
	}
endif;

if ( ! function_exists( 'unysonplus_get_default_gap' ) ) :
	/**
	 * Slug picked as the site-wide default column gap. `''` means "no
	 * override — let Bootstrap's stock `.row` gutter behaviour stand."
	 */
	function unysonplus_get_default_gap() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'default_gap', '' );
			if ( is_string( $saved ) && $saved !== '' ) {
				return $saved;
			}
		}
		return '';
	}
endif;

if ( ! function_exists( 'unysonplus_get_default_gap_x' ) ) :
	/**
	 * Optional horizontal-axis override on top of `default_gap`. `''` means
	 * "inherit from Default Gap." Honoured even when Default Gap is itself
	 * blank — useful for "horizontal-only" tweaks against Bootstrap stock.
	 */
	function unysonplus_get_default_gap_x() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'default_gap_x', '' );
			if ( is_string( $saved ) && $saved !== '' ) {
				return $saved;
			}
		}
		return '';
	}
endif;

if ( ! function_exists( 'unysonplus_get_default_gap_y' ) ) :
	/**
	 * Vertical counterpart to `unysonplus_get_default_gap_x()`.
	 */
	function unysonplus_get_default_gap_y() {
		if ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = unysonplus_preset_store_get( 'default_gap_y', '' );
			if ( is_string( $saved ) && $saved !== '' ) {
				return $saved;
			}
		}
		return '';
	}
endif;
