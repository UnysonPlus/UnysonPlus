<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );


/**
 * Time-only picker — a thin subclass of the datetime-picker forced to time-only
 * (Air Datepicker `onlyTimepicker`). Gives a clean `'type' => 'time-picker'`
 * instead of hand-configuring a datetime-picker with `datepicker => false`.
 *
 * Everything (render, enqueue, save/validation, the Air Datepicker init JS) is
 * inherited from FW_Option_Type_Datetime_Picker; only the type id and the
 * time-only defaults differ. The shared datetime-picker script also initialises
 * `.fw-option-type-time-picker`, so no extra JS/CSS is needed here.
 *
 * Value: a time string (default format 'H:i'; set 'format' => 'h:i A' for 12-hour).
 */
class FW_Option_Type_Time_Picker extends FW_Option_Type_Datetime_Picker {

	public function get_type() {
		return 'time-picker';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'attr'  => array(),
			'value' => '',
			'datetime-picker' => array(
				'format'        => 'H:i',
				'extra-formats' => array(),
				'moment-format' => 'HH:mm',
				'maxDate'       => false,
				'minDate'       => false,
				'timepicker'    => true,
				'datepicker'    => false, // time only
				'defaultTime'   => '12:00',
			),
		);
	}
}
