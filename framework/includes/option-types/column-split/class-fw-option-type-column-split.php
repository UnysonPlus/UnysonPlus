<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Column Split — a visual two-pane split control.
 *
 * Renders a long rounded bar divided into a LEFT and RIGHT pane by a draggable
 * divider; dragging sets how the row is shared between them. Each pane is labelled
 * (a dashicon / image + text) to show what it represents — e.g. "Image | Content"
 * for [image_content], "Content | Button" for a call-to-action.
 *
 * The stored value is a single integer: the LEFT pane's column span out of
 * `denominator` (default 12). The right pane fills the rest. This is the same value
 * shape as a plain `slider` storing a column count, so it is a drop-in replacement
 * for such a slider with no value migration.
 *
 * Config:
 *   'value'         int    left pane span (default 6)
 *   'denominator'   int    total columns (default 12)
 *   'min' / 'max'   int    clamp for the left span (default 1 / denominator-1, so
 *                          each side always keeps at least one column)
 *   'show_fraction' bool   show each pane's lowest-form fraction (default true)
 *   'panes'         array  [ left, right ] — each: array('label' => '', 'icon' => '')
 *                          where `icon` is a dashicons-* class OR an image URL.
 */
class FW_Option_Type_Column_Split extends FW_Option_Type {

	public function _get_type() {
		return 'column-split';
	}

	/**
	 * The framework's abstract option-type identifier (mirrors the slider type:
	 * a public get_type() wrapping the internal _get_type()).
	 */
	public function get_type() {
		return $this->_get_type();
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

	private function clamp( $value, $option ) {
		$denominator = max( 2, (int) $option['denominator'] );
		$min         = max( 1, (int) $option['min'] );
		$max         = (int) $option['max'];
		if ( $max <= 0 || $max > $denominator - 1 ) {
			$max = $denominator - 1;
		}
		if ( $min > $max ) {
			$min = $max;
		}
		$value = (int) $value;
		if ( $value < $min ) { $value = $min; }
		if ( $value > $max ) { $value = $max; }
		return array( $value, $min, $max, $denominator );
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$ver = fw()->manifest->get_version();

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'fw-option-' . $this->_get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->_get_type() . '/static/css/styles.css' ),
			array(),
			$ver
		);

		wp_enqueue_script(
			'fw-option-' . $this->_get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->_get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'fw-events' ),
			$ver,
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		list( $value, $min, $max, $denominator ) = $this->clamp( $data['value'], $option );

		$panes = is_array( $option['panes'] ) ? array_values( $option['panes'] ) : array();
		$left  = isset( $panes[0] ) && is_array( $panes[0] ) ? $panes[0] : array();
		$right = isset( $panes[1] ) && is_array( $panes[1] ) ? $panes[1] : array();

		$cfg = array(
			'denominator'   => $denominator,
			'min'           => $min,
			'max'           => $max,
			'show_fraction' => ! empty( $option['show_fraction'] ),
		);

		$option['attr']['data-fw-column-split'] = json_encode( $cfg );
		$option['attr']['class'] = trim(
			( isset( $option['attr']['class'] ) ? $option['attr']['class'] : '' ) . ' fw-option-type-column-split'
		);

		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->_get_type() . '/view.php' ),
			array(
				'id'     => $id,
				'option' => $option,
				'data'   => $data,
				'value'  => $value,
				'cfg'    => $cfg,
				'left'   => $left,
				'right'  => $right,
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_null( $input_value ) ) {
			$input_value = $option['value'];
		}
		list( $value ) = $this->clamp( $input_value, $option );
		return $value;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'         => 6,
			'denominator'   => 12,
			'min'           => 1,
			'max'           => 11,
			'show_fraction' => true,
			'panes'         => array(
				array( 'label' => __( 'Left', 'fw' ),  'icon' => 'dashicons-align-pull-left' ),
				array( 'label' => __( 'Right', 'fw' ), 'icon' => 'dashicons-align-pull-right' ),
			),
		);
	}
}
