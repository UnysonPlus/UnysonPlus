<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Vector SVG (roadmap 7g): a drawn shape stored as clean inline SVG markup.
 *
 * The control shows a preview, a "Draw / edit" button that opens the vector editor (pen with points and handles,
 * rectangle, ellipse, polygon, star, boolean union / subtract / intersect / exclude, undo) and an "SVG code" box for
 * pasting or fine-tuning. The editor runs on paper.js, loaded only when it opens.
 *
 * Value: the SVG markup string, sanitised by fw_upw_sanitize_svg() on save ('' when empty).
 */
if ( ! class_exists( 'FW_Option_Type_Vector_Svg' ) ) :

	class FW_Option_Type_Vector_Svg extends FW_Option_Type {

		public function get_type() {
			return 'vector-svg';
		}

		protected function _get_defaults() {
			return array( 'value' => '' );
		}

		protected function _enqueue_static( $id = '', $option = array(), $data = array() ) {
			$base = fw_get_framework_directory_uri( '/includes/option-types/vector-svg/static' );
			$ver  = fw()->manifest->get_version();
			wp_enqueue_style( 'fw-option-vector-svg', fw_get_framework_asset_uri( '/includes/option-types/vector-svg/static/css/styles.css' ), array(), $ver );
			wp_enqueue_script( 'fw-option-vector-svg', fw_get_framework_asset_uri( '/includes/option-types/vector-svg/static/js/vector-svg.js' ), array( 'jquery', 'fw-events' ), $ver, true );
			wp_localize_script( 'fw-option-vector-svg', '_fwVectorSvg', array(
				'paper' => $base . '/vendor/paper/paper-core.min.js',
			) );
		}

		protected function _render( $id, $option, $data ) {
			$value = is_string( $data['value'] ) ? $data['value'] : '';
			$attr  = $option['attr'];
			unset( $attr['value'] );
			$attr['name'] = $option['attr']['name'];
			return '<div class="fw-vsvg" data-fw-vsvg>'
				. '<div class="fw-vsvg__preview" aria-label="' . esc_attr__( 'Shape preview', 'fw' ) . '">' . ( '' !== $value ? $this->safe( $value ) : '<span class="fw-vsvg__empty">' . esc_html__( 'No shape yet', 'fw' ) . '</span>' ) . '</div>'
				. '<div class="fw-vsvg__bar">'
				. '<button type="button" class="button button-primary fw-vsvg__open">' . esc_html__( 'Draw / edit shape', 'fw' ) . '</button> '
				. '<button type="button" class="button fw-vsvg__code-toggle">' . esc_html__( 'SVG code', 'fw' ) . '</button>'
				. '</div>'
				. '<textarea class="fw-vsvg__code" rows="6" spellcheck="false" ' . fw_attr_to_html( $attr ) . '>' . esc_textarea( $value ) . '</textarea>'
				. '</div>';
		}

		protected function _get_value_from_input( $option, $input_value ) {
			if ( null === $input_value ) {
				return $option['value'];
			}
			return $this->safe( (string) $input_value );
		}

		/** Sanitised SVG, or '' when the input holds no SVG. */
		private function safe( $markup ) {
			$markup = trim( (string) $markup );
			if ( '' === $markup || false === stripos( $markup, '<svg' ) ) {
				return '';
			}
			return function_exists( 'fw_upw_sanitize_svg' ) ? (string) fw_upw_sanitize_svg( $markup ) : '';
		}
	}



endif;
