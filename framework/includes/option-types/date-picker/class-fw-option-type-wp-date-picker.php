<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Option_Type_Date_Picker extends FW_Option_Type {
	/**
	 * Bundled Air Datepicker version (static/vendor/air-datepicker). Used for
	 * asset cache-busting, independent of the plugin version.
	 */
	const AIR_DATEPICKER_VERSION = '3.6.0';

	private $internal_options = array();

	public function get_type() {
		return 'date-picker';
	}

	/**
	 * @internal
	 */
	public function _init() {
		$this->internal_options = array(
			'label' => false,
			'type'  => 'text',
			'value' => ''
		);
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		// 'auto' lets the option shrink to the input's own width (190px via CSS);
		// 'fixed' would force the full column and ignore the input width.
		return 'auto';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => '',
			'monday-first' => true, // The week will begin with Monday; for Sunday, set to false
			'min-date' => null, // No minimum by default (past dates ARE selectable). Set 'min-date' => date('d-m-Y') to restrict to today onward, or any 'd-m-Y' string.
			'max-date' => null, // No maximum by default. Set a 'd-m-Y' string to cap the latest selectable date.
		);
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static($id, $option, $data)
	{
		$uri = fw_get_framework_directory_uri('/includes/option-types/' . $this->get_type() . '/static');

		self::enqueue_air_datepicker();

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/date-picker.css',
			array('fw-air-datepicker'),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/js/scripts.js',
			array('jquery', 'fw-events', 'fw-air-datepicker'),
			fw()->manifest->get_version(),
			true
		);

		fw()->backend->option_type( 'text' )->enqueue_static();
	}

	/**
	 * Register + enqueue the shared Air Datepicker library ONCE (a single
	 * physical copy under date-picker/static/vendor), so date-picker,
	 * datetime-picker and datetime-range all load one copy instead of
	 * duplicating it.
	 */
	public static function enqueue_air_datepicker() {
		if ( ! wp_script_is( 'fw-air-datepicker', 'registered' ) ) {
			$base   = fw_get_framework_directory_uri( '/includes/option-types/date-picker/static' );
			$vendor = $base . '/vendor/air-datepicker';
			wp_register_style( 'fw-air-datepicker', $vendor . '/air-datepicker.css', array(), self::AIR_DATEPICKER_VERSION );
			// Shared theme override (WP-admin blue accent + z-index above modals),
			// loaded for every picker type, not just date-picker.
			wp_register_style( 'fw-air-datepicker-theme', $base . '/css/air-datepicker-theme.css', array( 'fw-air-datepicker' ), fw()->manifest->get_version() );
			wp_register_script( 'fw-air-datepicker', $vendor . '/air-datepicker.js', array(), self::AIR_DATEPICKER_VERSION, true );
		}
		wp_enqueue_style( 'fw-air-datepicker' );
		wp_enqueue_style( 'fw-air-datepicker-theme' );
		wp_enqueue_script( 'fw-air-datepicker' );
	}

	/**
	 * @param string $id
	 * @param array $option
	 * @param array $data
	 *
	 * @return string
	 */
	protected function _render( $id, $option, $data ) {
		$language = substr(get_locale(), 0, 2);

		$properties = array(
			'language' => $language,
			'weekStart'  => ( $option['monday-first'] == true ) ? 1 : 0,
			'minDate'  => ( $option['min-date'] !== null ) ? $option['min-date'] : null,
			'maxDate'  => ( $option['max-date'] !== null ) ? $option['max-date'] : null,
		);

		// Editable input: users may type a date directly (Air Datepicker still
		// opens on click). The saved value is taken from the input as-is.
		$option['attr']['autocomplete'] = 'off';
		$option['attr']['data-fw-option-date-picker-opts'] = json_encode( $properties );

		return fw()->backend->option_type( 'text' )->render( $id, $option, $data );
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if (is_null($input_value)) {
			$input_value = $option['value'];
		}

		return (string)$input_value;
	}
}