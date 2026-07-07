<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Icon-pack enable/disable wiring.
 *
 * The checklist itself lives in Theme Settings -> Icons (its schema is injected
 * by the shortcodes extension's theme-settings loader). This core file READS the
 * saved setting and:
 *   - filters which FONT packs the icon picker offers (fw:option_type:icon-v2:filter_packs), and
 *   - exposes helpers the icon-v2 template uses to show/hide the Icon Fonts tab
 *     and each bundled SVG library tab (currently Lucide).
 *
 * Disabling a pack only hides it when PICKING a new icon — icons already placed
 * on pages keep rendering, because enqueue_pack_for_icon() / sc_icon_render()
 * resolve markup against the full, unfiltered pack list. So toggles are safe.
 */

if ( ! function_exists( 'unysonplus_font_icon_pack_ids' ) ) :
	/** Ids of the webfont packs (Dashicons, Font Awesome, Entypo, …). */
	function unysonplus_font_icon_pack_ids() {
		$ids = array();
		if ( function_exists( 'fw' ) ) {
			$ot = fw()->backend->option_type( 'icon-v2' );
			if ( $ot && isset( $ot->packs_loader ) ) {
				$ids = array_keys( $ot->packs_loader->get_packs_unfiltered() );
			}
		}
		return $ids;
	}
endif;

if ( ! function_exists( 'unysonplus_svg_icon_pack_ids' ) ) :
	/** Ids of the bundled inline-SVG libraries. More join here as they are added. */
	function unysonplus_svg_icon_pack_ids() {
		return array( 'lucide' );
	}
endif;

if ( ! function_exists( 'unysonplus_icon_pack_choices' ) ) :
	/** Every selectable pack: id => label (font packs first, then SVG libraries). */
	function unysonplus_icon_pack_choices() {
		$choices = array();
		if ( function_exists( 'fw' ) ) {
			$ot = fw()->backend->option_type( 'icon-v2' );
			if ( $ot && isset( $ot->packs_loader ) ) {
				foreach ( $ot->packs_loader->get_packs_unfiltered() as $id => $pack ) {
					$choices[ $id ] = isset( $pack['title'] ) ? $pack['title'] : ucfirst( $id );
				}
			}
		}
		$svg_labels = array( 'lucide' => __( 'Lucide (SVG)', 'fw' ) );
		foreach ( unysonplus_svg_icon_pack_ids() as $id ) {
			$choices[ $id ] = isset( $svg_labels[ $id ] ) ? $svg_labels[ $id ] : ( ucfirst( $id ) . ' (SVG)' );
		}
		return $choices;
	}
endif;

if ( ! function_exists( 'unysonplus_all_icon_pack_ids' ) ) :
	function unysonplus_all_icon_pack_ids() {
		return array_keys( unysonplus_icon_pack_choices() );
	}
endif;

if ( ! function_exists( 'unysonplus_enabled_icon_packs' ) ) :
	/**
	 * Ids of the enabled packs. Defaults to ALL when the setting was never saved
	 * (existing sites are unchanged) and never returns empty (falls back to all),
	 * so a stray "uncheck everything" can't blank the picker entirely.
	 */
	function unysonplus_enabled_icon_packs() {
		$all = unysonplus_all_icon_pack_ids();
		if ( ! function_exists( 'fw_get_db_settings_option' ) ) { return $all; }

		$saved = fw_get_db_settings_option( 'icon_packs_enabled' );
		if ( ! is_array( $saved ) || ! $saved ) { return $all; }

		$enabled = array();
		foreach ( $saved as $id => $on ) { if ( $on ) { $enabled[] = $id; } }
		return $enabled ? $enabled : $all;
	}
endif;

if ( ! function_exists( 'unysonplus_icon_pack_enabled' ) ) :
	function unysonplus_icon_pack_enabled( $id ) {
		return in_array( $id, unysonplus_enabled_icon_packs(), true );
	}
endif;

if ( ! function_exists( 'unysonplus_any_font_pack_enabled' ) ) :
	/** True if at least one webfont pack is enabled (gates the Icon Fonts tab). */
	function unysonplus_any_font_pack_enabled() {
		return (bool) array_intersect( unysonplus_enabled_icon_packs(), unysonplus_font_icon_pack_ids() );
	}
endif;

/**
 * Filter the FONT packs the picker shows to the enabled subset. SVG libraries
 * (Lucide) are separate picker tabs, gated in the template via
 * unysonplus_icon_pack_enabled(). $names is the list of all font-pack names.
 */
add_filter( 'fw:option_type:icon-v2:filter_packs', function ( $names ) {
	if ( ! function_exists( 'unysonplus_enabled_icon_packs' ) ) { return $names; }
	return array_values( array_intersect( (array) $names, unysonplus_enabled_icon_packs() ) );
} );
