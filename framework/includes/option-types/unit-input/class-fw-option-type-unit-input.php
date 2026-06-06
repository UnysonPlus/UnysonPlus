<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Unit Input option type.
 *
 * A numeric field paired with a small unit dropdown. Units are fully
 * configurable — defaults to px / em / rem, but a consumer can supply any list
 * (%, vh, inches, cm, m, c, f, …) for sizes, lengths, temperatures, etc.
 *
 * Config keys:
 *   'units'    => array        // sequential list ['px','em'] (label==value) OR
 *                               // assoc map ['px'=>'Pixels'] (value=>label)
 *   'separate' => bool         // affects to_string() only: false -> "24px",
 *                               // true -> "24 inches" (space-separated)
 *   'min','max','step'         // optional numeric input attributes ('' = omit;
 *                               // step omitted falls back to "any" for decimals)
 *
 * Saved value (always an array, robust round-trip like box-shadow):
 *   array( 'value' => '24', 'unit' => 'px' )
 *
 * Consume with the static helper:
 *   echo FW_Option_Type_Unit_Input::to_string( $saved, $separate );
 */
class FW_Option_Type_Unit_Input extends FW_Option_Type {

	public function get_type() {
		return 'unit-input';
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
			'value'    => array( 'value' => '', 'unit' => 'px' ),
			'units'    => array( 'px', 'em', 'rem' ),
			'separate' => false,
			'min'      => '',
			'max'      => '',
			'step'     => '',
		);
	}

	/**
	 * @internal
	 */
	protected function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}

	/**
	 * Normalize a units config into an ordered value=>label map.
	 * Accepts a sequential list (label == value) or an assoc map (value=>label).
	 */
	public static function normalize_units( $units ) {
		$out = array();
		if ( ! is_array( $units ) || empty( $units ) ) {
			$units = array( 'px', 'em', 'rem' );
		}
		foreach ( $units as $key => $label ) {
			if ( is_int( $key ) ) {
				$value = (string) $label; // sequential: value is the entry, label same
				$label = $value;
			} else {
				$value = (string) $key;
				$label = (string) $label;
			}
			$value = trim( $value );
			if ( $value === '' ) {
				continue;
			}
			$out[ $value ] = ( $label === '' ) ? $value : $label;
		}
		if ( empty( $out ) ) {
			$out = array( 'px' => 'px', 'em' => 'em', 'rem' => 'rem' );
		}
		return $out;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
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
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_string( $input_value ) ) {
			$decoded = json_decode( $input_value, true );
			if ( is_array( $decoded ) ) {
				$input_value = $decoded;
			}
		}

		$fallback = is_array( $option['value'] ) ? $option['value'] : array( 'value' => '', 'unit' => 'px' );

		if ( ! is_array( $input_value ) ) {
			return $fallback;
		}

		$units = self::normalize_units( isset( $option['units'] ) ? $option['units'] : array() );

		// Numeric value: allow blank, integers, decimals, leading minus. Reject junk.
		$raw = isset( $input_value['value'] ) ? trim( (string) $input_value['value'] ) : '';
		$value = ( $raw === '' || is_numeric( $raw ) ) ? $raw : '';

		// Unit must be one of the configured units; else first configured unit.
		$unit = isset( $input_value['unit'] ) ? (string) $input_value['unit'] : '';
		if ( ! isset( $units[ $unit ] ) ) {
			$keys = array_keys( $units );
			$unit = isset( $keys[0] ) ? $keys[0] : '';
		}

		return array( 'value' => $value, 'unit' => $unit );
	}

	/**
	 * Compile a saved value to a string.
	 *
	 * @param array $value    array('value'=>..,'unit'=>..)
	 * @param bool  $separate true -> "24 inches" (space), false -> "24px" (joined)
	 * @return string '' when the numeric value is blank
	 */
	public static function to_string( $value, $separate = false ) {
		if ( ! is_array( $value ) ) {
			return '';
		}
		$num  = isset( $value['value'] ) ? trim( (string) $value['value'] ) : '';
		$unit = isset( $value['unit'] ) ? trim( (string) $value['unit'] ) : '';
		if ( $num === '' ) {
			return '';
		}
		$sep = $separate ? ' ' : '';
		return $num . ( $unit !== '' ? $sep . $unit : '' );
	}
}
