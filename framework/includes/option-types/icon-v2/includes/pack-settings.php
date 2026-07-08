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
	/**
	 * Ids of the bundled inline-SVG libraries that actually have data present.
	 * Derived from the multi-pack registry so a new pack (Tabler, …) appears
	 * everywhere just by dropping in its JSON + a registry entry.
	 */
	function unysonplus_svg_icon_pack_ids() {
		if ( ! function_exists( 'fw_icon_svg_pack_registry' ) ) {
			return array( 'lucide' );
		}
		$ids = array();
		foreach ( array_keys( fw_icon_svg_pack_registry() ) as $id ) {
			if ( fw_icon_svg_pack_available( $id ) ) { $ids[] = $id; }
		}
		return $ids ? $ids : array( 'lucide' );
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
		$reg = function_exists( 'fw_icon_svg_pack_registry' ) ? fw_icon_svg_pack_registry() : array();
		foreach ( unysonplus_svg_icon_pack_ids() as $id ) {
			$title = isset( $reg[ $id ]['title'] ) ? $reg[ $id ]['title'] : ucfirst( $id );
			/* translators: %s: SVG icon pack title, e.g. "Lucide" */
			$choices[ $id ] = sprintf( __( '%s (SVG)', 'fw' ), $title );
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
	 *
	 * Iterates the CURRENT pack list, so a pack bundled AFTER the checklist was
	 * last saved (e.g. Tabler, added in a later release) is absent from the saved
	 * value and defaults ON — a new library is opt-out, not silently hidden.
	 * Only a pack explicitly present-and-false in the saved value is disabled.
	 */
	function unysonplus_enabled_icon_packs() {
		$all = unysonplus_all_icon_pack_ids();

		// The unified installer panel (icon-v3) owns the on/off toggle and stores it
		// in its own option; fall back to the legacy theme-settings checklist value.
		if ( function_exists( 'fw_icon_pack_enabled_map' ) ) {
			$saved = fw_icon_pack_enabled_map();
		} elseif ( function_exists( 'fw_get_db_settings_option' ) ) {
			$saved = fw_get_db_settings_option( 'icon_packs_enabled' );
		} else {
			return $all;
		}
		if ( ! is_array( $saved ) || ! $saved ) { return $all; }

		$enabled = array();
		foreach ( $all as $id ) {
			if ( ! array_key_exists( $id, $saved ) || $saved[ $id ] ) { $enabled[] = $id; }
		}
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
