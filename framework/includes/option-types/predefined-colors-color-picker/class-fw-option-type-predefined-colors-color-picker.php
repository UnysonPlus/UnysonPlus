<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Predefined Colors Color Picker
 *
 * Hybrid control: presents the `predefined-colors` swatch grid alongside a
 * `color-picker` (or `rgba-color-picker`). Selecting one clears the other.
 *
 * @author Pavel Marhaunichy (original)
 * @url http://likeaprothemes.com
 *
 * Moved into the plugin in 2.7.115 so any theme bundled with Unyson+ has
 * access. The class_exists guard means a stale theme-side copy on a
 * partially-upgraded deploy won't trigger a redeclare fatal — the first
 * declaration wins.
 */

if ( ! class_exists( 'FW_Option_Type_Predefined_Colors_Color_Picker' ) ) :

class FW_Option_Type_Predefined_Colors_Color_Picker extends FW_Option_Type {

	public function get_type() {
		return 'predefined-colors-color-picker';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => array(
				'predefined' => '',
				'custom'     => '',
			),
		);
	}

	/**
	 * @internal
	 *
	 * Builder / customizer contexts don't auto-enqueue child option types'
	 * static assets the way Theme Settings pages do, so we enqueue every
	 * dependency explicitly here — defence in depth.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );

		// Predefined Colors dependency (sibling option type in this plugin)
		wp_enqueue_style(
			'fw-option-predefined-colors',
			fw_get_framework_directory_uri() . '/includes/option-types/predefined-colors/static/css/styles.css',
			array(),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-predefined-colors',
			fw_get_framework_directory_uri() . '/includes/option-types/predefined-colors/static/js/scripts.js',
			array( 'fw-events', 'jquery' ),
			fw()->manifest->get_version(),
			true
		);

		// Standard color picker
		wp_enqueue_style(
			'fw-option-color-picker',
			fw_get_framework_directory_uri() . '/includes/option-types/color-picker/static/css/styles.css',
			array(),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-color-picker',
			fw_get_framework_directory_uri() . '/includes/option-types/color-picker/static/js/scripts.js',
			array( 'jquery', 'fw-events', 'wp-color-picker' ),
			fw()->manifest->get_version(),
			true
		);
		wp_localize_script(
			'fw-option-color-picker',
			'_fw_option_type_color_picker_localized',
			array(
				'l10n' => array(
					'reset_to_default' => __( 'Reset', 'fw' ),
					'reset_to_initial' => __( 'Reset', 'fw' ),
				),
			)
		);

		// RGBA color picker
		wp_enqueue_style(
			'fw-option-rgba-color-picker',
			fw_get_framework_directory_uri() . '/includes/option-types/rgba-color-picker/static/css/styles.css',
			array(),
			fw()->manifest->get_version()
		);
		wp_enqueue_script(
			'fw-option-rgba-color-picker',
			fw_get_framework_directory_uri() . '/includes/option-types/rgba-color-picker/static/js/scripts.js',
			array( 'jquery', 'fw-events', 'iris' ),
			fw()->manifest->get_version(),
			true
		);
		wp_localize_script(
			'fw-option-rgba-color-picker',
			'_fw_option_type_rgba_color_picker_localized',
			array(
				'l10n' => array(
					'reset_to_default' => __( 'Reset', 'fw' ),
					'reset_to_initial' => __( 'Reset', 'fw' ),
				),
			)
		);

		// This option type's own assets
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

		$wrapper_attr = $option['attr'];

		unset(
			$wrapper_attr['value'],
			$wrapper_attr['name']
		);

		$html = '';

		foreach ( $option['value'] as $key => $val ) {

			$type = $option['colors'][ $key ]['type'];

			if ( $type === 'predefined' ) {
				$html .= '<div class="fw-option-type-' . $option['type'] . '__inner">';
				$html .= fw()->backend->option_type( 'predefined-colors' )->render(
					$key,
					array(
						'type'    => 'predefined-colors',
						'value'   => fw_akg( $key, $option['value'] ),
						'choices' => fw_akg( $key . '/choices', $option['colors'] ),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			if ( $type === 'custom' ) {
				$picker = $option['colors'][ $key ]['picker'];

				$html .= '<div class="fw-option-type-' . $option['type'] . '__inner">';
				$html .= '<input type="radio" class="fw-option-type-' . $option['type'] . '__radio">';
				$html .= fw()->backend->option_type( $picker )->render(
					$key,
					array(
						'type'     => 'rgba-color-picker',
						'value'    => fw_akg( $key, $option['value'] ),
						'palettes' => '',
						'attr'     => array(
							'class'        => 'fw-option-type-' . $option['type'] . '__text',
							'autocomplete' => 'off',
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}
		}

		return fw_html_tag( 'div', $wrapper_attr, $html );
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_array( $input_value ) ) {
			$value = $input_value;
		} else {
			$value = $option['value'];
		}
		return $value;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'auto'; // auto|fixed|full
	}
}

endif; // class_exists guard
