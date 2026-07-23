<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Image Style Picker option type.
 *
 * A popover-style dropdown that previews each choice as a REAL sample image with
 * the treatment applied — the trigger and every panel row render
 * `<span class="imgs-wrap {value}"><img …></span>`, so the generated Image-Style
 * CSS (`.imgs-{slug}` + the shared `.imgs-wrap` base rule, already enqueued in
 * wp-admin by css-tokens.php) paints the actual crop / mask / filter / scrim on a
 * gradient stand-in next to its name. Drop-in replacement for a plain <select>:
 * the saved value is the class string (e.g. 'imgs-diagonal'), identical to what the
 * select stored, so consuming views (which put the class on the image wrapper) need
 * no change.
 *
 * Mirrors `border-style-picker` / `button-style-picker`; the only difference is the
 * preview markup wraps a sample <img> so the image-targeting rules apply.
 *
 * Config:
 *   'choices'     => array  // value => label  (e.g. sc_get_image_style_choices())
 *   'placeholder' => string // label shown when nothing is selected
 *   'allow_none'  => bool   // show the empty "— None —" row (default true)
 *   'value'       => string // saved class, e.g. 'imgs-diagonal'
 */
class FW_Option_Type_Image_Style_Picker extends FW_Option_Type {

	public function get_type() {
		return 'image-style-picker';
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
			'value'       => '',
			'choices'     => array(),
			'placeholder' => __( '— Select an image style —', 'fw' ),
			'allow_none'  => true,
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
		// Only this widget's own chrome. The `.imgs-{slug}` + `.imgs-wrap` rules are
		// already loaded in admin by css-tokens.php, so previews style themselves.
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
	 * Whitelist the posted value against the configured choices (like a <select>).
	 * '' (the "None" row) is allowed only when `allow_none` is true; anything else
	 * must be a known choice key, else we keep the default value.
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
