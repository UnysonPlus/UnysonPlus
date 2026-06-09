<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Button Style Picker option type.
 *
 * A custom dropdown that previews each choice as a REAL button — the trigger and
 * every panel row render `<span class="btn {value}">` so the Button Preset /
 * Size classes paint themselves (the generated preset CSS is already enqueued in
 * wp-admin, see css-tokens.php). Drop-in replacement for a plain <select>: the
 * saved value is the class string (e.g. 'btn-primary'), identical to what the
 * select stored, so consuming views need no change.
 *
 * Reusable by any shortcode that picks a button preset/size — pass `choices`
 * (value => label), e.g. sc_get_button_style_choices() or sc_get_button_size_choices().
 *
 * Config:
 *   'choices'      => array  // value => label  (REQUIRED to show anything)
 *   'preview_text' => string // text inside each preview button (default 'Button')
 *   'preview_base' => string // base class on every preview (default 'btn')
 *   'placeholder'  => string // label shown when nothing is selected
 *   'value'        => string // saved class, e.g. 'btn-primary'
 */
class FW_Option_Type_Button_Style_Picker extends FW_Option_Type {

	public function get_type() {
		return 'button-style-picker';
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'auto';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'        => '',
			'choices'      => array(),
			'preview_text' => __( 'Button', 'fw' ),
			'preview_base' => 'btn',
			'placeholder'  => __( '— Select —', 'fw' ),
			// When false, the picker has no empty "— None —" row and an empty value
			// is not accepted (falls back to the default). Used by the Button Style
			// field so a button always has a real preset (defaults to the first one).
			'allow_none'   => true,
		);
	}

	/**
	 * @internal
	 */
	protected function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		// Only this widget's own chrome. The button preview rules (.btn-primary,
		// .btn-lg, …) are already loaded in admin by css-tokens.php
		// (admin_enqueue_scripts / admin_head), so previews style themselves.
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'fw-events' ),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->get_type() . '/view.php' ),
			array(
				'id'     => $id,
				'option' => $option,
				'data'   => $data,
			)
		);
	}

	/**
	 * @internal
	 *
	 * Whitelist the posted value against the configured choices (like a <select>
	 * would). '' (the "None" row) is allowed only when `allow_none` is true;
	 * anything else must be a known choice key, else we keep the default value.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		$choices    = ( isset( $option['choices'] ) && is_array( $option['choices'] ) ) ? $option['choices'] : array();
		$allow_none = ! isset( $option['allow_none'] ) || $option['allow_none'];

		if ( $input_value === null ) {
			return (string) $option['value'];
		}

		$input_value = (string) $input_value;

		if ( ( $allow_none && $input_value === '' ) || isset( $choices[ $input_value ] ) ) {
			return $input_value;
		}

		return (string) $option['value'];
	}
}
