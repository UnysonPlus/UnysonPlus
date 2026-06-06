<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Box Shadow option type.
 *
 * A structured CSS box-shadow builder with a live preview and a read-only
 * generated CSS string (e.g. "5px 5px 19px 5px rgba(0,0,0,0.32)") — works like
 * the color picker, where you adjust controls and it produces the final CSS.
 *
 * Reuses the rgba-color-picker for the shadow color (same approach gradient-v2
 * uses for its stops). Reusable anywhere an option needs a box-shadow value.
 *
 * Saved value shape:
 *   array(
 *     'x'      => int,    // offset-x px
 *     'y'      => int,    // offset-y px
 *     'blur'   => int,    // blur px (>= 0)
 *     'spread' => int,    // spread px
 *     'color'  => string, // hex or rgba()
 *     'inset'  => bool,
 *   )
 */
class FW_Option_Type_Box_Shadow extends FW_Option_Type {

	public function get_type() {
		return 'box-shadow';
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => array(
				'x'      => 0,
				'y'      => 0,
				'blur'   => 0,
				'spread' => 0,
				'color'  => '',
				'inset'  => false,
			),
		);
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		// Reuse the rgba-color-picker stack for the color field.
		fw()->backend->option_type( 'rgba-color-picker' )->enqueue_static();

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'fw-events', 'wp-color-picker' ),
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
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_string( $input_value ) ) {
			$decoded = json_decode( $input_value, true );
			if ( is_array( $decoded ) ) {
				$input_value = $decoded;
			}
		}

		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		return self::sanitize( $input_value, $option['value'] );
	}

	/** Validate + clamp a raw value array. */
	public static function sanitize( $value, $fallback ) {
		$int = function ( $v, $default, $min = null ) {
			if ( $v === '' || $v === null || ! is_numeric( $v ) ) { return $default; }
			$v = (int) round( (float) $v );
			if ( $min !== null && $v < $min ) { $v = $min; }
			return $v;
		};

		$color = isset( $value['color'] ) ? trim( (string) $value['color'] ) : '';
		if ( $color !== '' ) {
			$hex  = '/^#([a-f0-9]{3}){1,2}$/i';
			$rgba = '/^rgba?\(\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*,\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*,\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*(?:,\s*(0|1|0?\.\d+)\s*)?\)$/i';
			if ( ! preg_match( $hex, $color ) && ! preg_match( $rgba, $color ) ) {
				$color = '';
			}
		}

		return array(
			'x'      => $int( isset( $value['x'] ) ? $value['x'] : '', (int) $fallback['x'] ),
			'y'      => $int( isset( $value['y'] ) ? $value['y'] : '', (int) $fallback['y'] ),
			'blur'   => $int( isset( $value['blur'] ) ? $value['blur'] : '', (int) $fallback['blur'], 0 ),
			'spread' => $int( isset( $value['spread'] ) ? $value['spread'] : '', (int) $fallback['spread'] ),
			'color'  => $color,
			'inset'  => ! empty( $value['inset'] ),
		);
	}

	/**
	 * Compile a saved value to a CSS box-shadow string.
	 * Returns '' when there is effectively no shadow (no offsets/blur/spread).
	 *
	 * @param array $value
	 * @return string e.g. "5px 5px 19px 5px rgba(0,0,0,0.32)" or ""
	 */
	public static function to_css( $value ) {
		if ( ! is_array( $value ) ) { return ''; }

		$x      = isset( $value['x'] ) ? (int) $value['x'] : 0;
		$y      = isset( $value['y'] ) ? (int) $value['y'] : 0;
		$blur   = isset( $value['blur'] ) ? (int) $value['blur'] : 0;
		$spread = isset( $value['spread'] ) ? (int) $value['spread'] : 0;
		$color  = isset( $value['color'] ) ? (string) $value['color'] : '';
		$inset  = ! empty( $value['inset'] );

		// Nothing meaningful set → no shadow.
		if ( $x === 0 && $y === 0 && $blur === 0 && $spread === 0 ) {
			return '';
		}

		$css = ( $inset ? 'inset ' : '' )
			. $x . 'px ' . $y . 'px ' . $blur . 'px ' . $spread . 'px '
			. ( $color !== '' ? $color : 'rgba(0,0,0,0.2)' );

		return $css;
	}
}
