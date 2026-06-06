<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Predefined Colors
 *
 * Swatch grid bound to a hidden <select>. Pick from a palette; clicking a
 * swatch toggles selection. `blank => true` allows deselection.
 *
 * @author Pavel Marhaunichy (original)
 * @url http://likeaprothemes.com
 *
 * Moved into the plugin in 2.7.115 so any theme bundled with Unyson+ has
 * access. The class_exists guard means a stale theme-side copy on a
 * partially-upgraded deploy won't trigger a redeclare fatal — the first
 * declaration wins.
 */

if ( ! class_exists( 'FW_Option_Type_Predefined_Colors' ) ) :

class FW_Option_Type_Predefined_Colors extends FW_Option_Type {

	public function get_type() {
		return 'predefined-colors';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'   => '',
			'blank'   => true, // if true, the swatch can be deselected
			'choices' => array(
				/*
				'#fff' => '#fff',
				'#000' => '#000',
				*/
			),
		);
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/js/scripts.js',
			array( 'fw-events', 'jquery' ),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {

		{
			$wrapper_attr = array(
				'id'    => $option['attr']['id'],
				'class' => $option['attr']['class'],
			);

			foreach ( $wrapper_attr as $attr_name => $attr_val ) {
				unset( $option['attr'][ $attr_name ] );
			}
		}

		$option['value'] = (string) $data['value'];

		{
			$item = '';
			$html = '<select ' . fw_attr_to_html( $option['attr'] ) . '>';

			// if true, the swatch can be deselected
			if ( ! empty( $option['blank'] ) and $option['blank'] === true ) {
				$html .= '<option value=""></option>';
			}

			foreach ( $option['choices'] as $key => $value ) {
				$attr = array(
					'value' => $value,
				);

				// set selected color by default
				if ( $option['value'] == $value ) {
					$attr['selected'] = 'selected';
				}

				$item .= '<li class="fw-option-type-' . $this->get_type() . '-list__color tooltip" data-option-type-' . $this->get_type() . '="' . $value . '" style="background:' . $value . '"><span class="tooltiptext">' . $key . '</span></li>';

				$html .= fw_html_tag( 'option', $attr, fw_htmlspecialchars( $value ) );
			}

			$html .= '</select>';
		}

		return fw_html_tag( 'div', $wrapper_attr,
			$html . '<ul class="fw-option-type-' . $this->get_type() . '-list">' . $item . '</ul>'
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_null( $input_value ) ) {
			// return default options value
			return $option['value'];
		}

		// else return input value
		return (string) $input_value;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'auto'; // auto|fixed|full
	}
}

endif; // class_exists guard
