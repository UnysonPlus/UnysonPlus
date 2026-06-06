<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Table Style Picker option type.
 *
 * A popover-style dropdown that previews each choice as a REAL mini table — the
 * trigger and every panel row render a small `<table>` wrapped in
 * `<span class="tbl-{slug}">`, so the generated Table-Preset CSS (`.tbl-{slug}`,
 * already enqueued in wp-admin by css-tokens.php) paints the actual header /
 * stripes / borders next to the name. Drop-in replacement for a plain <select>:
 * the saved value is the class string (e.g. 'tbl-striped').
 *
 * Mirrors `border-style-picker`.
 *
 * Config:
 *   'choices'      => array  // value => label  (e.g. sc_get_table_preset_choices())
 *   'preview_text' => string // unused for tables (preview is a mini table)
 *   'placeholder'  => string // label shown when nothing is selected
 *   'allow_none'   => bool   // show the empty "— None —" row (default true)
 *   'value'        => string // saved class, e.g. 'tbl-striped'
 */
class FW_Option_Type_Table_Style_Picker extends FW_Option_Type {

	public function get_type() {
		return 'table-style-picker';
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
			'preview_text' => __( 'Table', 'fw' ),
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
		// Only this widget's own chrome. The table preview rules (.tbl-striped, …)
		// are already loaded in admin by css-tokens.php, so previews style themselves.
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
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
