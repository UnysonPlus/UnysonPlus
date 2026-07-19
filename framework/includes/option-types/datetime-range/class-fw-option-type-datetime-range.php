<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );


/**
 * Date/time RANGE, rendered as a SINGLE Air Datepicker input (range mode).
 *
 * Value shape: a plain indexed array of two formatted strings, [from, to]
 * (empty array when nothing is selected). This is Air Datepicker's native
 * two-date representation — simpler than the legacy {from,to} associative map
 * the old xdsoft version used.
 *
 * Config (all optional, sensible defaults):
 *   'timepicker' => bool   (default false)  include a time picker
 *   'datepicker' => bool   (default true)   include the calendar
 *   'format'     => string (default derived) PHP date() format of each end
 *   'min-date'   => 'd/m/…' string|null      earliest selectable
 *   'max-date'   => 'd/m/…' string|null      latest selectable
 */
class FW_Option_Type_Datetime_Range extends FW_Option_Type {

	const SEPARATOR = ' — ';

	private function _get_static_uri() {
		return fw_get_framework_directory_uri('/includes/option-types/datetime-range/static');
	}

	public function get_type() {
		return 'datetime-range';
	}

	public function _get_backend_width_type() {
		return 'auto';
	}

	protected function _get_data_for_js($id, $option, $data = array()) {
		return false;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'      => array(), // [from, to] formatted strings; empty = nothing selected
			'format'     => null,    // null => derived from timepicker/datepicker
			'timepicker' => false,
			'datepicker' => true,
			'min-date'   => null,
			'max-date'   => null,
		);
	}

	/**
	 * Resolve the effective PHP date format from the timepicker/datepicker flags
	 * (unless an explicit 'format' was provided).
	 */
	private function resolve_format($option) {
		if ( ! empty( $option['format'] ) ) {
			return $option['format'];
		}
		$has_time = ! empty( $option['timepicker'] );
		$has_date = ( $option['datepicker'] !== false );

		if ( ! $has_date ) {
			return 'H:i';
		}
		return $has_time ? 'Y/m/d H:i' : 'Y/m/d';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static($id, $option, $data)
	{
		FW_Option_Type_Date_Picker::enqueue_air_datepicker();

		wp_enqueue_style(
			'fw-option-datetime-range-CSS',
			$this->_get_static_uri() . '/css/styles.css',
			array( 'fw-air-datepicker' ),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-datetime-range-js',
			$this->_get_static_uri() . '/js/script.js',
			array( 'jquery', 'fw-events', 'fw-air-datepicker' ),
			fw()->manifest->get_version(),
			true
		);

		fw()->backend->option_type( 'text' )->enqueue_static();
	}

	protected function _render( $id, $option, $data ) {
		$option['format'] = $this->resolve_format( $option );

		return fw_render_view( dirname(__FILE__) . '/view.php', array(
			'id'     => $id,
			'option' => $option,
			'data'   => $data,
		) );
	}

	/**
	 * @internal
	 * The hidden field posts a JSON array [from, to]. Validate and normalize it.
	 */
	protected function _get_value_from_input($option, $input_value)
	{
		$decoded = is_string( $input_value ) ? json_decode( $input_value, true ) : $input_value;

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$decoded = array_values( $decoded );
		$from = isset( $decoded[0] ) ? (string) $decoded[0] : '';
		$to   = isset( $decoded[1] ) ? (string) $decoded[1] : '';

		if ( $from === '' || $to === '' ) {
			return array();
		}

		$ts_from = strtotime( $from );
		$ts_to   = strtotime( $to );

		// Reject unparsable or out-of-order ranges (keep the previous value).
		if ( $ts_from === false || $ts_to === false || $ts_from > $ts_to ) {
			return is_array( $option['value'] ) ? $option['value'] : array();
		}

		return array( $from, $to );
	}
}
