<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * RGBA Color Picker
 */
class FW_Option_Type_Rgba_Color_Picker extends FW_Option_Type {
	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'auto';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		// Same Coloris engine as `color-picker`. Delegating to its enqueue loads Coloris +
		// the SHARED init, which configures rgba-color-picker inputs (via a scoped
		// Coloris.setInstance) to use `format:'rgb'` + opacity — so this type keeps emitting
		// `rgba(r,g,b,a)`, its existing stored value shape. No wp-color-picker-alpha / Iris.
		fw()->backend->option_type( 'color-picker' )->enqueue_static();

		// This type's own input styles (preview / layout).
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);
	}

	public function get_type() {
		return 'rgba-color-picker';
	}

	/**
	 * @param string $id
	 * @param array  $option
	 * @param array  $data
	 *
	 * @return string
	 */
	protected function _render( $id, $option, $data ) {
		$option['attr']['value']        = (string) $data['value'];
		$option['attr']['class']       .= ' code';
		$option['attr']['size']         = '22'; // fits "rgba(255, 255, 255, 0.55)"
		$option['attr']['autocomplete'] = 'off';
		$option['attr']['data-coloris']  = '';
		$option['attr']['data-default']  = $option['value'];

		return '<input type="text" ' . fw_attr_to_html( $option['attr'] ) . '>';
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if (
			is_null( $input_value )
			||
			(
				// do not use `!is_null()` allow empty values https://github.com/ThemeFuse/Unyson/issues/2025
				! empty( $input_value )
				&&
				! (
					// hex (3/4/6/8), OR rgb()/rgba() — Coloris (format:'rgb') emits rgb() when a
					// colour is fully opaque and rgba() when it has alpha; both are valid CSS.
					preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $input_value )
					||
					preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\)$/i', $input_value )
				)
			)
		) {
			return (string) $option['value'];
		} else {
			return (string) $input_value;
		}
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'    => '',
			'palettes' => true,
		);
	}
}
