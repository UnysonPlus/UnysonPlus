<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Border Style Picker option type.
 *
 * A popover-style dropdown that previews each choice as a REAL bordered box — the
 * trigger and every panel row render `<span class="{value}">` so the generated
 * Border-Preset CSS (`.boxp-{slug}`, already enqueued in wp-admin by
 * css-tokens.php) paints the actual border / corners / shadow next to its name.
 * Drop-in replacement for a plain <select>: the saved value is the class string
 * (e.g. 'boxp-card'), identical to what the select stored, so consuming views
 * (the column's view.php) need no change.
 *
 * Mirrors `button-style-picker`; the only difference is that the value IS the full
 * preview class (there is no `preview_base`).
 *
 * Config:
 *   'choices'      => array  // value => label  (e.g. sc_get_border_preset_choices())
 *   'preview_text' => string // fallback text inside the preview (default 'Border')
 *   'placeholder'  => string // label shown when nothing is selected
 *   'allow_none'   => bool   // show the empty "— None —" row (default true)
 *   'value'        => string // saved class, e.g. 'boxp-card'
 */
class FW_Option_Type_Border_Style_Picker extends FW_Option_Type {

	public function get_type() {
		return 'border-style-picker';
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
			'preview_text' => __( 'Border', 'fw' ),
			'placeholder'  => __( '— Select —', 'fw' ),
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
		// Only this widget's own chrome. The border preview rules (.boxp-card, …)
		// are already loaded in admin by css-tokens.php, so previews style themselves.
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
