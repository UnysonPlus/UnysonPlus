<?php if (!defined('FW')) die('Forbidden');

/**
 * Color Picker — powered by Coloris (modern, vanilla, MIT), replacing Iris/wpColorPicker.
 *
 * The value is a plain colour string in the input — a 6-digit hex by default, or an
 * 8-digit `#rrggbbaa` when the option opts into opacity with `'alpha' => true`. So every
 * existing consumer that reads a hex keeps working unchanged; alpha is opt-in.
 *
 * The swatch grid under the picker shows the theme's Color Presets (all of them, wrapped
 * into rows by Coloris) unless the option overrides `palettes` (an explicit array of
 * colours) or disables it (`false`). Coloris binds via focus delegation, so options added
 * later (page-builder modals) work with no re-init.
 */
class FW_Option_Type_Color_Picker extends FW_Option_Type
{
	public function get_type()
	{
		return 'color-picker';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static($id, $option, $data)
	{
		$base = '/includes/option-types/' . $this->get_type();

		// Our option styles (input preview / layout).
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( $base . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);

		// Coloris (bundled locally, MIT). Not in the build manifest, so served as-is.
		wp_enqueue_style(
			'fw-coloris',
			fw_get_framework_asset_uri( $base . '/static/vendor/coloris/coloris.min.css' ),
			array(),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-coloris',
			fw_get_framework_asset_uri( $base . '/static/vendor/coloris/coloris.min.js' ),
			array(),
			fw()->manifest->get_version(),
			true
		);

		// Our init (Coloris config + presets swatches + alpha instances). We keep
		// 'wp-color-picker' as a dependency purely for COMPAT: sibling option types
		// (box-shadow / gradient-v2 / rgba-color-picker) still call jQuery's
		// .wpColorPicker() and historically leaned on the color-picker option loading it.
		// Coloris does not use it; loading the WP picker assets alongside is harmless.
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( $base . '/static/js/scripts.js' ),
			array( 'fw-coloris', 'fw-events', 'wp-color-picker' ),
			fw()->manifest->get_version(),
			true
		);

		// The brand Color Presets = the global swatch grid.
		$swatches = function_exists( 'unysonplus_color_preset_slug_map' )
			? array_slice( array_values( array_filter( (array) unysonplus_color_preset_slug_map() ) ), 0, 60 )
			: array();
		wp_localize_script( 'fw-option-' . $this->get_type(), 'fwColorisSwatches', $swatches );
	}

	/**
	 * @internal
	 */
	protected function _render($id, $option, $data)
	{
		$option['attr']['value']        = strtolower( (string) $data['value'] );
		$option['attr']['class']       .= ' code';
		$option['attr']['size']         = '9';
		$option['attr']['maxlength']    = '9'; // fits an 8-digit #rrggbbaa when alpha is on
		$option['attr']['autocomplete'] = 'off';
		$option['attr']['data-coloris'] = '';
		$option['attr']['data-default'] = $option['value'];
		$option['attr']['data-alpha']   = ! empty( $option['alpha'] ) ? '1' : '0';

		// Swatch grid = the theme's Color Presets when `palettes` is left at its default
		// `true`. Precedence: an explicit array wins; `false` hides the swatches; `true`
		// (default) → every preset hex (capped) or none when the presets aren't loaded.
		// Guarded so it no-ops when the presets component is inactive. NOTE: clicking a
		// swatch stores the resolved HEX — it is NOT live-linked to the preset (that's the
		// reference-storing predefined-colors picker).
		$palettes = (bool) $option['palettes'];
		if ( $palettes === true && function_exists( 'unysonplus_color_preset_slug_map' ) ) {
			$preset_hexes = array_values( array_filter( (array) unysonplus_color_preset_slug_map() ) );
			if ( ! empty( $preset_hexes ) ) {
				$palettes = array_slice( $preset_hexes, 0, 60 );
			}
		}
		if ( ! empty( $option['palettes'] ) && is_array( $option['palettes'] ) ) {
			$palettes = $option['palettes'];
		}

		$option['attr']['data-swatches'] = json_encode( is_array( $palettes ) ? $palettes : array() );

		return '<input type="text" ' . fw_attr_to_html( $option['attr'] ) . '>';
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input($option, $input_value)
	{
		// Accept a hex colour with 3 / 4 / 6 / 8 digits (4 & 8 carry alpha). Empty stays
		// empty; anything malformed falls back to the option default.
		if (
			is_null( $input_value )
			||
			(
				// allow empty values (https://github.com/ThemeFuse/Unyson/issues/2025)
				! empty( $input_value )
				&&
				! preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', (string) $input_value )
			)
		) {
			return (string) $option['value'];
		}

		return (string) $input_value;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type()
	{
		return 'auto';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults()
	{
		return array(
			'value'    => '',
			'palettes' => true,
			'alpha'    => false, // opacity slider — opt-in; off keeps the 6-digit-hex behaviour
		);
	}
}
