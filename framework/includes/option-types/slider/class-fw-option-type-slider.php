<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Slider
 * -----*--
 */
class FW_Option_Type_Slider extends FW_Option_Type {

	const NOUISLIDER_VERSION = '15.8.1';
	const WNUMB_VERSION      = '1.2.0';

	/**
	 * This class is extended by 'short-slider' option type
	 * but the type here should be this
	 * @return string
	 */
	private function _get_type() {
		return 'slider';
	}

	protected function _get_data_for_js($id, $option, $data = array()) {
		return false;
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		self::enqueue_nouislider();
	}

	/**
	 * Register + enqueue the shared noUiSlider library (+ wNumb + the adapter
	 * script + theme) ONCE. Both the single `slider` and the double
	 * `range-slider` load through this, so noUiSlider is loaded a single time.
	 */
	public static function enqueue_nouislider() {
		if ( ! wp_script_is( 'fw-nouislider', 'registered' ) ) {
			$base   = fw_get_framework_directory_uri( '/includes/option-types/slider/static' );
			$vendor = $base . '/vendor';

			wp_register_style( 'fw-nouislider', $vendor . '/nouislider/nouislider.min.css', array(), self::NOUISLIDER_VERSION );
			wp_register_style( 'fw-nouislider-theme', $base . '/css/nouislider-theme.css', array( 'fw-nouislider' ), fw()->manifest->get_version() );

			wp_register_script( 'fw-nouislider', $vendor . '/nouislider/nouislider.min.js', array(), self::NOUISLIDER_VERSION, true );
			wp_register_script( 'fw-wnumb', $vendor . '/wnumb/wNumb.min.js', array(), self::WNUMB_VERSION, true );
			wp_register_script(
				'fw-nouislider-adapter',
				$base . '/js/scripts.js',
				array( 'jquery', 'fw-events', 'underscore', 'fw-nouislider', 'fw-wnumb' ),
				fw()->manifest->get_version(),
				true
			);
		}

		wp_enqueue_style( 'fw-nouislider' );
		wp_enqueue_style( 'fw-nouislider-theme' );
		wp_enqueue_script( 'fw-nouislider' );
		wp_enqueue_script( 'fw-wnumb' );
		wp_enqueue_script( 'fw-nouislider-adapter' );
	}

	public function get_type() {
		return $this->_get_type();
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$option['properties']['type'] = 'single';
		$option['properties']['from'] = isset( $data['value'] ) ? $data['value'] : $option['value'];

		if(isset($option['properties']['values']) && is_array($option['properties']['values'])){
			$option['properties']['from'] = array_search($option['properties']['from'], $option['properties']['values']);
		}

		$option['attr']['data-fw-irs-options'] = json_encode(
			$this->default_properties($option['properties'])
		);

		return fw_render_view( fw_get_framework_directory( '/includes/option-types/' . $this->_get_type() . '/view.php' ), array(
			'id'     => $id,
			'option' => $option,
			'data'   => $data,
			'value'  => $data['value']
		) );
	}

	private function default_properties($properties = array()) {
		return array_merge(array(
			'min' => 0,
			'max' => 100,
			'step' => 1,
			/**
			 * For large ranges, this will create https://static.md/6340ebf52a36255649f10b3d0dff3b1c.png
			 */
			'grid_snap' => false,
		), $properties);
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'      => 0,
			'properties' => $this->default_properties(), // https://github.com/IonDen/ion.rangeSlider#settings
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if (is_null($input_value)) {
			return $option['value'];
		} else {
			return floatval($input_value);
		}
	}

}
