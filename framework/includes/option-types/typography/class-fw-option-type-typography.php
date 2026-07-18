<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Typography — the canonical rich typography control (family + Google-font
 * variation/subset + size / line-height / letter-spacing + color).
 *
 * This IS the former `typography-v2` implementation, promoted to the clean
 * `typography` name (UnysonPlus is a fresh restart of Unyson, so the `-v2`
 * suffix is being retired). `typography-v2` still works — it is now a thin
 * deprecation ALIAS (FW_Option_Type_Typography_v2) that subclasses this class
 * and only overrides get_type(); it shares this class's view, JS, CSS and
 * value logic. So both `'type' => 'typography'` and `'type' => 'typography-v2'`
 * render and save identically.
 *
 * NOTE: the enqueue base + view are pinned to the literal 'typography' folder
 * (not $this->get_type()), precisely so the alias reuses THESE assets instead
 * of looking for its own under typography-v2/.
 */
class FW_Option_Type_Typography extends FW_Option_Type {

	/**
	 * The folder / asset / handle base shared by this type and its alias.
	 * Pinned so FW_Option_Type_Typography_v2 (get_type() === 'typography-v2')
	 * still enqueues and renders THESE assets.
	 */
	const ASSET_BASE = 'typography';

	public function _get_backend_width_type() {
		return 'full';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		wp_enqueue_style(
			'fw-option-' . self::ASSET_BASE,
			fw_get_framework_asset_uri( '/includes/option-types/' . self::ASSET_BASE . '/static/css/styles.css' ),
			array( 'fw-selectize' ),
			fw()->manifest->get_version()
		);

		fw()->backend->option_type( 'color-picker' )->enqueue_static();
		// The Size field renders a unit-input in the default 'unit' format.
		fw()->backend->option_type( 'unit-input' )->enqueue_static();

		wp_enqueue_script(
			'fw-option-' . self::ASSET_BASE,
			fw_get_framework_asset_uri( '/includes/option-types/' . self::ASSET_BASE . '/static/js/scripts.js' ),
			array( 'jquery', 'underscore', 'fw', 'fw-selectize' ),
			fw()->manifest->get_version()
		);

		wp_localize_script(
			'fw-option-' . self::ASSET_BASE,
			'fw_typography_v2_fonts',
			$this->get_fonts()
		);
	}

	public function get_type() {
		return 'typography';
	}

	/**
	 * Returns fonts
	 * @return array
	 */
	public function get_fonts() {
		$cache_key = 'fw_option_type/' . self::ASSET_BASE;

		try {
			return FW_Cache::get( $cache_key );
		} catch ( FW_Cache_Not_Found_Exception $e ) {
			$fonts = array(
				'standard' => apply_filters( 'fw_option_type_typography_v2_standard_fonts', array(
					"Arial",
					"Verdana",
					"Trebuchet",
					"Georgia",
					"Times New Roman",
					"Tahoma",
					"Palatino",
					"Helvetica",
					"Calibri",
					"Myriad Pro",
					"Lucida",
					"Arial Black",
					"Gill Sans",
					"Geneva",
					"Impact",
					"Serif"
				) ),
				'google' => apply_filters(
					'fw_option_type_typography_v2_google_fonts',
					json_decode( fw_get_google_fonts_v2(), true )
				)
			);

			FW_Cache::set( $cache_key, $fonts );

			return $fonts;
		}
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . self::ASSET_BASE . '/view.php' ),
			array(
				'typography_v2' => $this,
				'id'            => $id,
				'option'        => $option,
				'data'          => $data,
				'defaults'      => $this->get_defaults()
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {

		$default = $this->get_defaults();
		$values  = array_merge( $default['value'], $option['value'], is_array( $input_value ) ? $input_value : array() );

		// In the 'unit' size format the Size field is a unit-input, which submits its
		// value as a JSON string ('{"value":"24","unit":"rem"}'). Decode it so the stored
		// size is a clean { value, unit } array (mirrors the multi-inline fix). A legacy
		// bare number (number format, or an untouched pre-migration value) is left as-is.
		$size_format = isset( $option['size_format'] ) ? $option['size_format'] : $default['size_format'];
		if ( $size_format === 'unit' && isset( $values['size'] ) && is_string( $values['size'] ) ) {
			$trimmed = trim( $values['size'] );
			if ( $trimmed !== '' && $trimmed[0] === '{' ) {
				$decoded = json_decode( $trimmed, true );
				if ( is_array( $decoded ) ) { $values['size'] = $decoded; }
			}
		}

		if ( ! empty( $values['color'] ) && ! preg_match( '/^#([a-f0-9]{3}){1,2}$/i', $values['color'] ) ) {
			$values['color'] = isset( $option['value']['color'] ) ? $option['value']['color'] : $default['value']['color'];
		}

		$components = array_merge( $default['components'], $option['components'] );
		foreach ( $components as $component => $enabled ) {
			if ( ! $enabled ) {
				$values[ $component ] = false;
			}
		}

		if ( $values['family'] === false ) {
			$values = array_merge( $values, array(
				'google_font' => false,
				'style'       => false,
				'weight'      => false,
				'subset'      => false,
				'variation'   => false
			) );
		} elseif ( $this->get_google_font( $values['family'] ) ) {
			$values = array_merge( $values, array(
				'google_font' => true,
				'style'       => false,
				'weight'      => false
			) );
		} else {
			$values = array_merge( $values, array(
				'google_font' => false,
				'subset'      => false,
				'variation'   => false

			) );
		}

		return $values;

	}

	public function get_google_font( $font ) {
		$fonts = $this->get_fonts();

		foreach ( $fonts['google']['items'] as $g_font ) {
			if ( $font === $g_font['family'] ) {
				return $g_font;
			}
		}

		return false;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			// Which control renders the Size field:
			//   'unit'   -> a unit-input (px / rem / em) — the default. Value is a
			//               { value, unit } array; a legacy bare-number save is tolerated
			//               (shown as px) and upgraded on the next save.
			//   'number' -> the legacy plain number input (bare px integer). Use this
			//               where a consumer needs a raw pixel NUMBER (e.g. the cursor
			//               engine feeds size into JS), not a CSS length string.
			'size_format' => 'unit',
			// Which control renders the Color field:
			//   'picker' -> the basic color-picker (whose swatch row already shows the
			//               Color Presets) — the default; stores a hex. Reserved for a
			//               future 'preset' (reference-storing) mode.
			'color_format' => 'picker',
			'value' => array(
				'google_font'    => false,
				'subset'         => false,
				'variation'      => false,
				'family'         => 'Arial',
				'style'          => 'normal',
				'weight'         => '400',
				'size'           => 12,
				'line-height'    => 15,
				'letter-spacing' => 0,
				'color'          => '#000000'
			),
			'components' => array(
				'family'         => true,
				'size'           => true,
				'line-height'    => true,
				'letter-spacing' => true,
				'color'          => true,
				'weight'         => true,
				'style'          => true,
				'variation'      => true,
				'subset'         => true, // the "Script" (Google-font charset) selector; gateable like the rest
			)
		);
	}

}
