<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Preset store seam — reads saved presets from the THEME-SCOPED Theme Settings store. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_preset_store_get' ) ) :
	/**
	 * Read a saved preset value from the THEME-SCOPED Theme Settings store
	 * (fw_theme_settings_options:{theme-id}). The presets are injected into the
	 * Theme Settings page (see extensions/shortcodes/includes/theme-settings-presets.php)
	 * and saved there by the framework, so each theme keeps its own presets and a
	 * theme switch resets/restores them. Centralized so the storage location is a
	 * single, filterable seam.
	 *
	 * @param string $key           Option key (e.g. 'button_colors', 'theme_colors').
	 * @param mixed  $default_value Returned when the key isn't saved.
	 * @return mixed
	 */
	function unysonplus_preset_store_get( $key, $default_value = null ) {
		if ( ! class_exists( 'FW_WP_Option' ) ) {
			return $default_value;
		}

		/**
		 * Read the RAW saved value directly from the wp_option, NOT via
		 * fw_get_db_settings_option(). The latter loads the full settings schema and
		 * runs it through the option types to merge defaults — which (a) recurses
		 * (the preset color pickers call back into the color presets) and (b) fires
		 * `fw_option_types_init` early (e.g. during the preset-CSS enqueue), before
		 * the page-builder loads shortcodes, so the Table shortcode's `table` option
		 * type registers too late ("Undefined option type: table"). The plugin
		 * defaults already provide the fallback, so the raw read is all we need.
		 */
		$theme_id = function_exists( 'fw' ) ? fw()->theme->manifest->get_id() : 'default';
		$sentinel = '__upw_preset_unset__';
		$value    = FW_WP_Option::get( 'fw_theme_settings_options:' . $theme_id, $key, $sentinel );
		if ( $value !== $sentinel ) {
			return $value;
		}

		/**
		 * Pre-migration fallback: presets used to live in the theme-INDEPENDENT
		 * extension store. Until the one-time move (see unysonplus_migrate_presets_to_theme_store)
		 * seeds this site's active theme, read the legacy store so nothing breaks on
		 * upgrade. Once migrated, each theme reads only its own settings (so a fresh
		 * theme correctly falls through to the plugin defaults below). Filterable for
		 * back-compat.
		 */
		if ( ! get_option( 'upw_presets_theme_migrated' ) ) {
			$ext = apply_filters( 'unysonplus_preset_store_extension', 'shortcodes' );
			return FW_WP_Option::get( 'fw_ext_settings_options:' . $ext, $key, $default_value );
		}

		return $default_value;
	}
endif;
