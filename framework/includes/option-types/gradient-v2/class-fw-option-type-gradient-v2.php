<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Advanced gradient picker: unlimited color stops, linear/radial mode,
 * angle control, HEX + RGBA colors, live preview.
 *
 * Renders as a read-only input showing the generated CSS string; clicking it
 * opens a dropdown with the full editor. BLANK BY DEFAULT — an empty value
 * (zero stops) means "no gradient", so consumers don't need a separate enable
 * switch. A gradient needs >= 2 stops to be considered set.
 *
 * Saved value shape:
 *   array(
 *     'type'  => 'linear' | 'radial',
 *     'angle' => int 0-360,
 *     'stops' => array(                         // EMPTY array = no gradient (off)
 *       array('color' => '#hex' | 'rgba(...)', 'position' => float 0-100),
 *       ...                                      // >= 2 stops = a real gradient
 *     ),
 *   )
 */
class FW_Option_Type_Gradient_V2 extends FW_Option_Type {

	public function get_type() {
		return 'gradient-v2';
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
		// Blank by default: zero stops = no gradient. The editor seeds a starter
		// gradient only when the user actually opens the panel and interacts.
		return array(
			'value' => array(
				'type'  => 'linear',
				'angle' => 90,
				'stops' => array(),
			),
		);
	}

	/**
	 * The starter gradient the editor offers when a blank picker is opened.
	 * Not the saved default — the value stays empty until the user interacts.
	 */
	public static function starter_stops() {
		return array(
			array( 'color' => '#2A7B9B', 'position' => 0 ),
			array( 'color' => '#EDDD53', 'position' => 100 ),
		);
	}

	/**
	 * Compile a saved value into a CSS gradient string, or '' when empty
	 * (fewer than 2 stops). Single canonical builder for consumers
	 * (Background Pro, etc.) — mirrors the JS buildCSS().
	 *
	 * @param array $value
	 * @return string e.g. "linear-gradient(90deg, #abc 0%, #def 100%)" or ''
	 */
	public static function to_css( $value ) {
		if ( ! is_array( $value ) || empty( $value['stops'] ) || ! is_array( $value['stops'] ) ) {
			return '';
		}
		$stops = $value['stops'];
		if ( count( $stops ) < 2 ) {
			return '';
		}

		usort( $stops, function ( $a, $b ) {
			return ( (float) $a['position'] ) <=> ( (float) $b['position'] );
		} );

		$parts = array();
		foreach ( $stops as $stop ) {
			if ( ! isset( $stop['color'], $stop['position'] ) ) { continue; }
			$parts[] = $stop['color'] . ' ' . floatval( $stop['position'] ) . '%';
		}
		if ( count( $parts ) < 2 ) {
			return '';
		}

		$type = ( isset( $value['type'] ) && $value['type'] === 'radial' ) ? 'radial' : 'linear';
		if ( $type === 'radial' ) {
			return 'radial-gradient(circle, ' . implode( ', ', $parts ) . ')';
		}
		$angle = isset( $value['angle'] ) ? intval( $value['angle'] ) : 90;
		return 'linear-gradient(' . $angle . 'deg, ' . implode( ', ', $parts ) . ')';
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		// Reuse the rgba-color-picker's wp-color-picker + iris + alpha stack
		// so each stop gets a full HEX/RGBA picker for free.
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
			array( 'jquery', 'fw-events', 'wp-color-picker', 'underscore' ),
			fw()->manifest->get_version(),
			true
		);

		wp_localize_script(
			'fw-option-' . $this->get_type(),
			'_fw_option_type_gradient_v2_localized',
			array(
				'l10n' => array(
					'linear'     => esc_html__( 'Linear', 'fw' ),
					'radial'     => esc_html__( 'Radial', 'fw' ),
					'angle'      => esc_html__( 'Angle', 'fw' ),
					'add_stop'   => esc_html__( '+ Add color stop', 'fw' ),
					'remove'     => esc_html__( 'Remove', 'fw' ),
					'min_stops'  => esc_html__( 'A gradient must have at least 2 color stops.', 'fw' ),
				),
			)
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
		// Input arrives as a JSON string in a single hidden field, or as the
		// already-decoded default array on first render.
		if ( is_string( $input_value ) ) {
			$decoded = json_decode( $input_value, true );
			if ( is_array( $decoded ) ) {
				$input_value = $decoded;
			}
		}

		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		$type = ( isset( $input_value['type'] ) && in_array( $input_value['type'], array( 'linear', 'radial' ), true ) )
			? $input_value['type']
			: 'linear';

		$angle = isset( $input_value['angle'] ) ? (int) $input_value['angle'] : 90;
		if ( $angle < 0 )   { $angle = 0; }
		if ( $angle > 360 ) { $angle = 360; }

		$hex_regex  = '/^#([a-f0-9]{3}){1,2}$/i';
		$rgba_regex = '/^rgba?\(\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*,\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*,\s*([01]?\d\d?|2[0-4]\d|25[0-5])\s*(?:,\s*(0|1|0?\.\d+)\s*)?\)$/i';

		$stops = array();
		if ( isset( $input_value['stops'] ) && is_array( $input_value['stops'] ) ) {
			foreach ( $input_value['stops'] as $stop ) {
				if ( ! is_array( $stop ) || ! isset( $stop['color'], $stop['position'] ) ) {
					continue;
				}
				$color = (string) $stop['color'];
				if ( ! preg_match( $hex_regex, $color ) && ! preg_match( $rgba_regex, $color ) ) {
					continue;
				}
				$position = (float) $stop['position'];
				if ( $position < 0 )   { $position = 0; }
				if ( $position > 100 ) { $position = 100; }

				$stops[] = array(
					'color'    => $color,
					'position' => $position,
				);
			}
		}

		// Fewer than 2 valid stops = no gradient (off). Store the EMPTY form
		// rather than forcing the default back in, so blank stays blank.
		if ( count( $stops ) < 2 ) {
			$stops = array();
		}

		return array(
			'type'  => $type,
			'angle' => $angle,
			'stops' => $stops,
		);
	}
}
