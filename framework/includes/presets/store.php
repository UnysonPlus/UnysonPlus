<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/** Preset store seam — reads saved presets from the theme-independent extension settings. Loaded by ../presets.php. */

if ( ! function_exists( 'unysonplus_preset_store_get' ) ) :
	/**
	 * Read a saved preset value from the plugin-owned, theme-independent store
	 * (the Shortcodes extension settings). Centralized so the storage location
	 * is a single, filterable seam.
	 *
	 * @param string $key           Option key (e.g. 'button_colors', 'theme_colors').
	 * @param mixed  $default_value Returned when the key isn't saved.
	 * @return mixed
	 */
	function unysonplus_preset_store_get( $key, $default_value = null ) {
		$ext = apply_filters( 'unysonplus_preset_store_extension', 'shortcodes' );

		/**
		 * Read the RAW saved value directly from the extension-settings wp_option,
		 * NOT via fw_get_db_ext_settings_option(). The latter loads the extension's
		 * settings schema and runs it through the option types to merge defaults —
		 * which (a) recurses (the schema's color pickers call back into the color
		 * presets) and (b) fires `fw_option_types_init` early (e.g. during the
		 * preset-CSS enqueue), before the page-builder loads shortcodes, so the
		 * Table shortcode's `table` option type registers too late ("Undefined
		 * option type: table"). The plugin defaults below already provide the
		 * fallback, so the raw read is all we need.
		 */
		if ( class_exists( 'FW_WP_Option' ) ) {
			return FW_WP_Option::get( 'fw_ext_settings_options:' . $ext, $key, $default_value );
		}
		return $default_value;
	}
endif;
