<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );
/**
 * Button Set — a segmented control for choosing one, or several, of a fixed
 * set of choices. A presentation alternative to `radio` (single) and
 * `checkboxes` (multi) that shows every choice at once on a single row,
 * instead of a vertical list or a collapsed dropdown.
 *
 * Built on NATIVE radio / checkbox inputs with the input visually hidden and
 * its <label> styled as the button. That is deliberate: keyboard navigation,
 * screen-reader group semantics, focus handling and form serialization all
 * come from the browser. There is no third-party library and none is wanted —
 * a div-and-JS implementation would have to reimplement all of that in ARIA.
 *
 * VALUE SHAPE — matches the existing types, so an option can switch from
 * `radio` or `checkboxes` to `button-set` with NO data migration:
 *   single (default) : the choice key as a plain string   (same as `radio`)
 *   multiple => true : array( 'choice_key' => true, ... ) (same as `checkboxes`)
 *
 * Config:
 *   'choices'        array   key => label, or key => array( 'label' => …, 'icon' => … ).
 *                            'icon' is any icon font class (e.g. a dashicon:
 *                            'dashicons dashicons-editor-alignleft').
 *   'value'          mixed   string key, or array( key => true ) when multiple.
 *   'multiple'       bool    false = radio semantics, true = checkbox semantics.
 *   'allow_deselect' bool    single mode only — click the active button to clear
 *                            the value. Off by default (radio semantics); useful
 *                            for builder options meaning "no override".
 *   'stretch'        bool    buttons share the row width evenly instead of
 *                            sizing to their content.
 *   'show_labels'    bool    false renders icon-only buttons; the label is kept
 *                            as the accessible name (aria-label) and tooltip, so
 *                            nothing is lost for screen readers. Ignored for
 *                            choices with no icon.
 *
 * Colors are intentionally NOT configurable: the control inherits
 * --wp-admin-theme-color via the framework's --fw-accent token, so it follows
 * the admin color scheme the USER chose. A per-option color would override that
 * preference, break panel consistency, and move contrast/accessibility policing
 * onto the option author.
 */
if ( ! class_exists( 'FW_Option_Type_Button_Set' ) ) :

	class FW_Option_Type_Button_Set extends FW_Option_Type {

		public function get_type() {
			return 'button-set';
		}

		/**
		 * @internal
		 */
		protected function _get_data_for_js( $id, $option, $data = array() ) {
			return false;
		}

		/**
		 * @internal
		 */
		public function _get_backend_width_type() {
			// Public, not protected: the base class declares this one public
			// (unlike _render/_get_defaults/_get_value_from_input), so narrowing
			// it is a fatal error.
			return 'auto';
		}

		/**
		 * @internal
		 */
		protected function _get_defaults() {
			return array(
				'value'          => null,
				'choices'        => array(),
				'multiple'       => false,
				'allow_deselect' => false,
				'stretch'        => false,
				'show_labels'    => true,
			);
		}

		/**
		 * @internal
		 */
		protected function _enqueue_static( $id, $option, $data ) {
			wp_enqueue_style(
				'fw-option-' . $this->get_type(),
				fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
				array(),
				fw()->manifest->get_version()
			);
			wp_enqueue_script(
				'fw-option-' . $this->get_type(),
				fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
				array( 'jquery', 'fw-events' ),
				fw()->manifest->get_version(),
				true
			);
		}

		/**
		 * Normalize a choice to array( 'label' => string, 'icon' => string ).
		 * Accepts a bare string label for the common case.
		 */
		private function normalize_choice( $choice ) {
			if ( ! is_array( $choice ) ) {
				return array( 'label' => (string) $choice, 'icon' => '' );
			}

			return array(
				'label' => isset( $choice['label'] ) ? (string) $choice['label'] : '',
				'icon'  => isset( $choice['icon'] ) ? (string) $choice['icon'] : '',
			);
		}

		/**
		 * @internal
		 */
		protected function _render( $id, $option, $data ) {
			if ( empty( $option['choices'] ) || ! is_array( $option['choices'] ) ) {
				return '';
			}

			$multiple = ! empty( $option['multiple'] );
			$value    = $data['value'];

			if ( $multiple ) {
				$value = is_array( $value ) ? $value : array();
			} else {
				$value = is_array( $value ) ? null : $value;
			}

			$wrapper_attr = $option['attr'];
			unset( $wrapper_attr['value'], $wrapper_attr['name'] );

			$wrapper_attr['class'] = trim(
				( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' )
				. ( empty( $option['stretch'] ) ? '' : ' fw-button-set-stretch' )
				. ( empty( $option['show_labels'] ) ? ' fw-button-set-icon-only' : '' )
			);

			$wrapper_attr['role']                 = $multiple ? 'group' : 'radiogroup';
			$wrapper_attr['data-multiple']        = $multiple ? 'true' : 'false';
			$wrapper_attr['data-allow-deselect']  = ( ! $multiple && ! empty( $option['allow_deselect'] ) ) ? 'true' : 'false';

			$base_name = $option['attr']['name'];
			$base_id   = $option['attr']['id'];

			$html = '<div ' . fw_attr_to_html( $wrapper_attr ) . '>';

			// A multi-select with nothing checked posts no keys at all, which is
			// indistinguishable from "the field was never rendered". This empty
			// marker makes an intentionally-empty selection survive the round trip.
			if ( $multiple ) {
				$html .= '<input type="hidden" name="' . esc_attr( $base_name ) . '[__empty]" value="1">';
			} elseif ( ! empty( $option['allow_deselect'] ) ) {
				// A radio group with nothing checked posts nothing, which would be
				// read as "absent" and fall back to the default — so a deselect
				// would never stick. This hidden field posts an empty value; a
				// checked radio later in the form overrides it by name.
				$html .= '<input type="hidden" name="' . esc_attr( $base_name ) . '" value="">';
			}

			$i = 0;

			foreach ( $option['choices'] as $key => $choice ) {
				$choice     = $this->normalize_choice( $choice );
				$choice_id  = $base_id . '-' . $i;
				$is_checked = $multiple
					? ( ! empty( $value[ $key ] ) )
					: ( (string) $value === (string) $key );

				$input_attr = array(
					'type'  => $multiple ? 'checkbox' : 'radio',
					'id'    => $choice_id,
					'name'  => $multiple ? $base_name . '[' . $key . ']' : $base_name,
					'value' => $multiple ? 'true' : $key,
					'class' => 'fw-button-set-input',
				);

				if ( $is_checked ) {
					$input_attr['checked'] = 'checked';
				}

				$label_attr = array(
					'for'   => $choice_id,
					'class' => 'fw-button-set-button',
				);

				// Icon-only buttons still need an accessible name, and a tooltip
				// for sighted users.
				if ( empty( $option['show_labels'] ) && $choice['icon'] !== '' && $choice['label'] !== '' ) {
					$label_attr['title']      = $choice['label'];
					$label_attr['aria-label'] = $choice['label'];
				}

				$inner = '';

				if ( $choice['icon'] !== '' ) {
					$inner .= '<i class="' . esc_attr( $choice['icon'] ) . '" aria-hidden="true"></i>';
				}

				if ( ! empty( $option['show_labels'] ) || $choice['icon'] === '' ) {
					$inner .= '<span class="fw-button-set-label">' . esc_html( $choice['label'] ) . '</span>';
				}

				$html .= '<input ' . fw_attr_to_html( $input_attr ) . '>'
				       . '<label ' . fw_attr_to_html( $label_attr ) . '>' . $inner . '</label>';

				$i ++;
			}

			$html .= '</div>';

			return $html;
		}

		/**
		 * @internal
		 */
		protected function _get_value_from_input( $option, $input_value ) {
			$choices  = ( isset( $option['choices'] ) && is_array( $option['choices'] ) ) ? $option['choices'] : array();
			$multiple = ! empty( $option['multiple'] );

			if ( $multiple ) {
				// Nothing submitted at all: fall back to the declared default,
				// filtered to valid keys. The __empty marker means the field WAS
				// rendered and the user deselected everything — respect that.
				if ( is_null( $input_value ) ) {
					$value = is_array( $option['value'] ) ? $option['value'] : array();
				} else {
					$value = is_array( $input_value ) ? $input_value : array();
					unset( $value['__empty'] );
				}

				$result = array();

				foreach ( $value as $key => $enabled ) {
					if ( isset( $choices[ $key ] ) && ! empty( $enabled ) ) {
						$result[ $key ] = true;
					}
				}

				return $result;
			}

			// Single. An unknown or absent key falls back to the declared default,
			// then to the first choice — never to an invalid value.
			$candidate = is_null( $input_value ) ? $option['value'] : $input_value;

			if ( is_array( $candidate ) ) {
				$candidate = null;
			}

			if ( ! is_null( $candidate ) && isset( $choices[ $candidate ] ) ) {
				return $candidate;
			}

			// Deselect is only legitimate when the option opted in.
			if ( ! is_null( $input_value ) && '' === $input_value && ! empty( $option['allow_deselect'] ) ) {
				return '';
			}

			if ( ! is_null( $option['value'] ) && ! is_array( $option['value'] ) && isset( $choices[ $option['value'] ] ) ) {
				return $option['value'];
			}

			if ( ! empty( $option['allow_deselect'] ) ) {
				return '';
			}

			$keys = array_keys( $choices );

			return count( $keys ) ? $keys[0] : '';
		}
	}

	FW_Option_Type::register( 'FW_Option_Type_Button_Set' );

endif;
