<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Button Hover Animation option type.
 *
 * A gradient-style dropdown: a select-like trigger that opens a panel listing the
 * hover effects in a 3-column grid of REAL buttons. Each cell renders
 * `<span class="btn btn-primary btnfx-{value}">{label}</span>`, so hovering a cell
 * plays its animation live (the effect classes ship in the consumer's stylesheet,
 * see `fx_css`). Selecting writes the class string (e.g. 'btnfx-lift') to a hidden
 * input — the consuming view just appends it to the button.
 *
 * Why a dedicated type (not button-style-picker reuse): option-type instances are
 * singletons, so `_enqueue_static()` runs once per TYPE per page with the first
 * field's $option. With three button-style-picker fields, a per-field effect
 * stylesheet never reliably enqueues. A type used by a single field always runs its
 * own `_enqueue_static`, so `fx_css` loads dependably in the options modal.
 *
 * Config:
 *   'choices'      => array  // value => label  ('' => 'None' supported)
 *   'preview_base' => string // base class on every preview button (default 'btn btn-primary')
 *   'placeholder'  => string // trigger text when nothing is selected
 *   'fx_css'       => string // URL of the stylesheet defining the .btnfx-* classes
 *   'value'        => string // saved class, e.g. 'btnfx-lift'
 */
class FW_Option_Type_Button_Hover_Animation extends FW_Option_Type {

	public function get_type() {
		return 'button-hover-animation';
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
			'preview_base' => 'btn btn-primary',
			'placeholder'  => __( 'None', 'fw' ),
			'fx_css'       => '',
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
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/static/css/styles.css',
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/static/js/scripts.js',
			array( 'jquery', 'fw-events' ),
			fw()->manifest->get_version(),
			true
		);

		// The effect classes (.btnfx-*) live in the consumer's stylesheet — load it
		// here so the previews animate inside the options form (the front end loads
		// it separately with the rendered shortcode). Only this single field uses
		// this type, so this _enqueue_static reliably runs.
		if ( ! empty( $option['fx_css'] ) ) {
			$css_url = (string) $option['fx_css'];
			wp_enqueue_style(
				'fw-bha-fx-' . substr( md5( $css_url ), 0, 8 ),
				$css_url,
				array( 'fw-option-' . $this->get_type() ),
				fw()->manifest->get_version()
			);
		}
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
	 * '' (None) is always allowed; anything else must be a known choice key, else
	 * keep the previously-saved value.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		$choices = ( isset( $option['choices'] ) && is_array( $option['choices'] ) ) ? $option['choices'] : array();

		if ( $input_value === null ) {
			return (string) $option['value'];
		}

		$input_value = (string) $input_value;

		if ( $input_value === '' || isset( $choices[ $input_value ] ) ) {
			return $input_value;
		}

		return (string) $option['value'];
	}
}
