<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Map
 */
class FW_Option_Type_Map extends FW_Option_Type {
	public function get_type() {
		return 'map';
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		wp_enqueue_style(
			$this->get_type() . '-styles',
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/css/style.css' )
		);

		// When no Google Maps API key is configured, fall back to a free
		// Leaflet/OpenStreetMap picker (with Nominatim address search) so pins
		// can still be added without a Google account / billing.
		if ( '' === (string) self::api_key() ) {
			$this->_enqueue_osm_picker();
			return;
		}

		wp_enqueue_script(
			$this->get_type() . '-scripts',
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'jquery-ui-widget', 'fw-events', 'underscore', 'jquery-ui-autocomplete' ),
			'1.0',
			true
		);

		wp_localize_script(
			$this->get_type() . '-scripts',
			'_fw_option_type_map',
			array(
				'google_maps_js_uri' => 'https://maps.googleapis.com/maps/api/js?'. http_build_query(array(
					'v' => 'quarterly',
					'libraries' => 'places',
					'language' => substr( get_locale(), 0, 2 ),
					'key' => self::api_key(),
				))
			)
		);

		// Some plugins load the map without library places.
		global $wp_scripts;

		foreach( $wp_scripts->queue as $handle ) {

			$url = &$wp_scripts->registered[ $handle ]->src;

			if ( strpos( $url, 'maps.googleapis.com/maps/api/js' ) && ! strpos( $url, 'places' ) ) {
				$url .= '&libraries=places';
			}
		}
	}

	/**
	 * Free Leaflet + Nominatim picker, used when no Google Maps API key is set.
	 * @internal
	 */
	protected function _enqueue_osm_picker() {
		$leaflet_version = '1.9.4';

		wp_enqueue_style(
			'leaflet',
			'https://unpkg.com/leaflet@' . $leaflet_version . '/dist/leaflet.css',
			array(),
			$leaflet_version
		);
		wp_enqueue_script(
			'leaflet',
			'https://unpkg.com/leaflet@' . $leaflet_version . '/dist/leaflet.js',
			array(),
			$leaflet_version,
			true
		);

		wp_enqueue_script(
			$this->get_type() . '-scripts-osm',
			fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts-osm.js' ),
			array( 'jquery', 'fw-events', 'underscore', 'leaflet' ),
			'1.0',
			true
		);

		wp_localize_script(
			$this->get_type() . '-scripts-osm',
			'_fw_option_type_map_osm',
			array(
				'nominatim_search'  => 'https://nominatim.openstreetmap.org/search',
				'nominatim_reverse' => 'https://nominatim.openstreetmap.org/reverse',
				'language'          => substr( get_locale(), 0, 2 ),
				'leaflet_images'    => 'https://unpkg.com/leaflet@' . $leaflet_version . '/dist/images/',
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$data['value']['coordinates'] = isset( $data['value']['coordinates'] )
			? json_encode($data['value']['coordinates'])
			: '';

		$path = fw_get_framework_directory(
			'/includes/option-types/' . $this->get_type() . '/views/view.php'
		);

		return fw_render_view($path, array(
			'id'     => $id,
			'option' => $option,
			'data'   => $data
		));
	}

	public function _get_data_for_js($id, $option, $data = array()) {
		return false;
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if (! is_array( $input_value ) || empty( $input_value )) {
			$input_value = $option['value'];
		}

		$location = array(
			'location'    => ( isset ( $input_value['location'] ) ) ? $input_value['location'] : '',
			'venue'       => ( isset ( $input_value['venue'] ) ) ? $input_value['venue'] : '',
			'address'     => ( isset ( $input_value['address'] ) ) ? $input_value['address'] : '',
			'city'        => ( isset ( $input_value['city'] ) ) ? $input_value['city'] : '',
			'state'       => ( isset ( $input_value['state'] ) ) ? $input_value['state'] : '',
			'country'     => ( isset ( $input_value['country'] ) ) ? $input_value['country'] : '',
			'zip'         => ( isset ( $input_value['zip'] ) ) ? $input_value['zip'] : '',
			'coordinates' => ( isset ( $input_value['coordinates'] ) )
				? ( is_array($input_value['coordinates']) || is_object($input_value['coordinates']) )
					? $input_value['coordinates']
					: json_decode($input_value['coordinates'], true)
				: ''
		);

		return $location;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => array(
				'coordinates' => array(
					'lat' => - 34,
					'lng' => 150,
				)
			)
		);
	}

	public static function api_key() {
		return FW_Option_Type_GMap_Key::get_key();
	}
}
