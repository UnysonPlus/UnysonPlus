<?php if (!defined('FW')) die('Forbidden');

class Fw_Option_Type_Image_Picker extends FW_Option_Type
{
	public function get_type()
	{
		return 'image-picker';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults()
	{
		return array(
			'value'    => '',
			'blank'    => false, // if true, images can be deselected
			'multiple' => false, // if true, several tiles can be toggled on; value is an array of choice keys
			'choices'  => array(
				/*
				'value' => '.../small.png'
				// or
				'value' => array(
					'small' => '.../small.png'
					'large' => '.../large.png' // optional
					'data'  => array(...) // (optional) choice extra data for js, available in custom events
				)
				// or
				'value' => array(
					'small' => array(
						'src' => '.../small.png',
						'alt' => '...'
					)
					'large' => array( // optional
						'src' => '.../large.png',
						'alt' => '...'
					)
					'data' => array(...) // (optional) choice extra data for js, available in custom events
				)
				// Categorization: a choice can also be a GROUP, rendered as an <optgroup> (the
				// image-picker plugin turns it into a category header):
				'group_key' => array(
					'label'   => 'Category Name',
					'choices' => array( 'value' => array( 'small' => '...', ... ), ... ),
				)
				*/
			),
			// If truthy, render a search box above the tiles that filters them live. Pass a string
			// to use it as the placeholder. Works standalone and inside multi-picker / popover panels.
			'search'   => false,
			// 'grid' (default) = stacked categories; 'tabs' = one tab per category (+ an "All" tab),
			// clicking a tab shows just that category. Categorization comes from grouped choices.
			'layout'   => 'grid',
			// Show the choice label under each tile WITHOUT a search box or tabs (search/tabs already
			// force labels on). Use for small pickers whose tile SVGs carry no baked-in caption.
			'show_label' => false,
		);
	}

	protected function _get_data_for_js($id, $option, $data = array()) {
		return false;
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static($id, $option, $data)
	{
		wp_enqueue_script(
			'fw-option-' . $this->get_type() . '-image-picker',
			fw_get_framework_asset_uri('/includes/option-types/' . $this->get_type() . '/static/js/image-picker/image-picker.js'),
			array(),
			fw()->manifest->get_version(),
			true
		);

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri('/includes/option-types/' . $this->get_type() . '/static/css/styles.css'),
			array('qtip'),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri('/includes/option-types/' . $this->get_type() . '/static/js/scripts.js'),
			array('fw-events', 'qtip'),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render($id, $option, $data)
	{
		{
			$wrapper_attr = array(
				'id'    => $option['attr']['id'],
				'class' => $option['attr']['class'],
			);

			foreach ($wrapper_attr as $attr_name => $attr_val) {
				unset($option['attr'][$attr_name]);
			}
		}

		$multiple = ! empty( $option['multiple'] );

		if ( $multiple ) {
			// Multi-select: the <select multiple> is a UI-only toggler; a hidden
			// input carries the value as a JSON array of the checked choice keys.
			$selected_values = is_array( $data['value'] ) ? array_map( 'strval', $data['value'] ) : array();
			$hidden_name     = isset( $option['attr']['name'] ) ? $option['attr']['name'] : '';
			unset( $option['attr']['name'] );
			$option['attr']['multiple'] = 'multiple';
		} else {
			$option['value'] = (string)$data['value'];
			unset($option['attr']['multiple']);
			$selected_values = array();
		}

		/**
		 * pre loads images on page load
		 *
		 * fixes glitch with preview:
		 * * hover first time  - show wrong because image not loaded and has no height/width and cannot detect correctly popup position
		 * * hover second time - show correctly
		 */
		$pre_load_images_html = '';

		$html = '';

		{
			$html .= '<select ' . fw_attr_to_html($option['attr']) . '>';

			if ($option['blank'] === true) {
				$html .= '<option value=""></option>';
			}

			// Hold the current value in the <select> even when it has NO tile — e.g. a multi-picker
			// "off"/"none" default whose tile was intentionally removed. Without this option the
			// browser auto-selects the FIRST tile, so on save the field is stored as that first effect,
			// silently ACTIVATING an unused module (all modules turned on after one save). A 1×1
			// transparent image lets the picker plugin build its tile without a 404; that tile is then
			// hidden by CSS (see styles.css) so no stray "off" tile shows.
			if ( ! $multiple ) {
				$off_valid = $this->_flatten_choice_keys( isset( $option['choices'] ) ? $option['choices'] : array() );
				$cur_val   = (string) $option['value'];
				if ( $cur_val !== '' && ! isset( $off_valid[ $cur_val ] ) ) {
					$html .= '<option value="' . esc_attr( $cur_val ) . '" selected="selected"'
						. ' data-img-src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="'
						. ' class="fw-image-picker-off-option"></option>';
				}
			}

			// Build ONE <option> from a leaf choice (a tile). Appends its large preview to the
			// pre-load bucket by reference. Extracted so grouped and ungrouped choices share it.
			$render_leaf = function ( $key, $choice ) use ( $multiple, $selected_values, $option, &$pre_load_images_html ) {
				$attr = array( 'value' => $key );

				if ( $multiple ) {
					if ( in_array( (string) $key, $selected_values, true ) ) {
						$attr['selected'] = 'selected';
					}
				} elseif ( $option['value'] == $key ) {
					$attr['selected'] = 'selected';
				}

				if ( is_string( $choice ) ) { // is 'http://.../small.png'
					$choice = array( 'small' => array( 'src' => $choice ) );
				}
				if ( is_string( $choice['small'] ) ) {
					$choice['small'] = array( 'src' => $choice['small'] );
				}
				$attr['data-small-img-attr'] = json_encode( $choice['small'] );
				$attr['data-img-src']        = $choice['small']['src']; // required by image-picker plugin

				if ( ! empty( $choice['large'] ) ) {
					if ( is_string( $choice['large'] ) ) {
						$choice['large'] = array( 'src' => $choice['large'] );
					}
					$attr['data-large-img-attr'] = json_encode( $choice['large'] );
					$pre_load_images_html       .= fw_html_tag( 'img', $choice['large'] );
				}
				if ( ! empty( $choice['data'] ) ) {
					$attr['data-extra-data'] = json_encode( $choice['data'] );
				}
				if ( ! empty( $choice['attr'] ) ) {
					$attr = array_merge( $choice['attr'], $attr );
				}

				return fw_html_tag( 'option', $attr, fw_htmlspecialchars( isset( $choice['label'] ) ? $choice['label'] : '' ) );
			};

			foreach ( $option['choices'] as $key => $choice ) {
				// A GROUP (array with a nested 'choices') → <optgroup>, which the plugin renders as a
				// category header. Anything else is a leaf tile → <option>.
				if ( is_array( $choice ) && isset( $choice['choices'] ) && is_array( $choice['choices'] ) ) {
					$glabel = isset( $choice['label'] ) ? $choice['label'] : ( isset( $choice['attr']['label'] ) ? $choice['attr']['label'] : '' );
					$html  .= '<optgroup label="' . fw_htmlspecialchars( $glabel ) . '">';
					foreach ( $choice['choices'] as $ckey => $cchoice ) {
						$html .= $render_leaf( $ckey, $cchoice );
					}
					$html .= '</optgroup>';
				} else {
					$html .= $render_leaf( $key, $choice );
				}
			}

			$html .= '</select>';
		}

		if ( $multiple ) {
			$html .= fw_html_tag( 'input', array(
				'type'  => 'hidden',
				'name'  => $hidden_name,
				'value' => json_encode( array_values( $selected_values ) ),
				'class' => 'fw-image-picker-multiple-value',
			) );
		}

		$search_html = '';
		if ( ! empty( $option['search'] ) ) {
			$placeholder = is_string( $option['search'] ) ? $option['search'] : __( 'Search…', 'fw' );
			$search_html = '<input type="text" class="fw-image-picker-search" placeholder="'
				. esc_attr( $placeholder ) . '" autocomplete="off" />';
			$wrapper_attr['class'] = trim( ( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' ) . ' fw-image-picker--searchable' );
		}
		if ( isset( $option['layout'] ) && $option['layout'] === 'tabs' ) {
			// The JS builds the tab bar from the rendered category groups (needs labels too).
			$wrapper_attr['class'] = trim( ( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' ) . ' fw-image-picker--tabs' );
		}
		if ( ! empty( $option['show_label'] ) ) {
			$wrapper_attr['class'] = trim( ( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' ) . ' fw-image-picker--labeled' );
		}

		return fw_html_tag('div', $wrapper_attr,
			$search_html . $html . '<div class="pre-loaded-images"><br/><br/>'. $pre_load_images_html .'</div>'
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input($option, $input_value)
	{
		// Valid keys = every LEAF choice key (recursing into category GROUPS) PLUS the option's own
		// default value — e.g. a multi-picker "off" value like 'none' that has no tile of its own.
		// Without this, grouping (or a tile-less default) makes the flat isset() below fail and the
		// saved selection is silently reset — which broke the front-end entrance animation.
		$valid_keys = $this->_flatten_choice_keys( isset( $option['choices'] ) ? $option['choices'] : array() );
		if ( isset( $option['value'] ) && is_string( $option['value'] ) && $option['value'] !== '' ) {
			$valid_keys[ $option['value'] ] = true;
		}

		if ( ! empty( $option['multiple'] ) ) {
			// Value arrives as a JSON array from the hidden input (or an array
			// already, e.g. programmatic saves). Keep only valid, de-duped keys.
			$values = $input_value;

			if ( is_string( $values ) ) {
				$trimmed = trim( $values );
				$decoded = ( $trimmed !== '' ) ? json_decode( $trimmed, true ) : array();
				$values  = is_array( $decoded ) ? $decoded : ( $trimmed === '' ? array() : array( $trimmed ) );
			}

			if ( ! is_array( $values ) ) {
				$values = is_array( $option['value'] ) ? $option['value'] : array();
			}

			$valid = array();
			foreach ( $values as $v ) {
				$v = (string) $v;
				if ( isset( $valid_keys[ $v ] ) && ! in_array( $v, $valid, true ) ) {
					$valid[] = $v;
				}
			}

			return $valid;
		}

		if (!is_string($input_value)) {
			return $option['value'];
		}

		if (!isset($valid_keys[$input_value])) {
			if ($option['blank']) {
				$input_value = '';
			} elseif (
				! empty($option['choices'])
				&&
				isset($valid_keys[ $option['value'] ])
			) {
				$input_value = $option['value'];
			} else {
				$leaf_keys   = array_keys( $valid_keys );
				$input_value = isset( $leaf_keys[0] ) ? $leaf_keys[0] : '';
			}
		}

		return (string)$input_value;
	}

	/**
	 * Every LEAF choice key, recursing into category GROUPS ( array with a nested 'choices' ).
	 * Returns a set: array( key => true, ... ).
	 */
	private function _flatten_choice_keys( $choices ) {
		$keys = array();
		if ( is_array( $choices ) ) {
			foreach ( $choices as $key => $choice ) {
				if ( is_array( $choice ) && isset( $choice['choices'] ) && is_array( $choice['choices'] ) ) {
					foreach ( $this->_flatten_choice_keys( $choice['choices'] ) as $k => $v ) {
						$keys[ $k ] = true;
					}
				} else {
					$keys[ $key ] = true;
				}
			}
		}
		return $keys;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type()
	{
		return 'auto';
	}
}
