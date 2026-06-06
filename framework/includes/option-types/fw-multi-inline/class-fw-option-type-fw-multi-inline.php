<?php if ( ! defined( 'ABSPATH' ) ) die( 'Direct access forbidden.' );

/**
 * Inline "multi" option type — renders N child fields (short-text, text,
 * color-picker, rgba-color-picker, select, short-select) side-by-side on a
 * single row. Used by the Spacing/Padding T/R/B/L control and similar.
 *
 * Originally shipped only by unysonplus-theme; the plugin now bundles its
 * own copy so Theme Settings → Shortcode Options keeps working on generic
 * themes (twentytwentyfour etc.) where the theme doesn't provide this type.
 *
 * The class registration is gated by `loader.php` via a class_exists check —
 * the file is only required on themes that don't already declare it, so
 * unysonplus-theme's identical class is never duplicated.
 */
if ( ! class_exists( 'FW_Option_Type_FwMultiInline' ) ) :
class FW_Option_Type_FwMultiInline extends FW_Option_Type
{
	public function get_type()
	{
		return 'fw-multi-inline';
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data )
	{
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css'
		);
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type()
	{
		return 'auto';
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data )
	{
		return fw_render_view( dirname( __FILE__ ) . '/view.php', array(
			'id'     => $id,
			'option' => $option,
			'data'   => $data,
		) );
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value )
	{
		if ( is_array( $input_value ) ) {
			return $input_value;
		}
		return $option['value'];
	}

	/**
	 * @internal
	 */
	protected function _get_defaults()
	{
		return array(
			'value' => array(
				'firstoption' => 'select2',
			),
			'fw_multi_options' => array(
				'firstoption' => array(
					'type'    => 'select',
					'title'   => __( 'Title', 'fw' ),
					'choices' => array(
						'select1' => __( 'select1', 'fw' ),
						'select2' => __( 'select2', 'fw' ),
						'select3' => __( 'select3', 'fw' ),
					),
				),
			),
			'groupname' => '',
		);
	}
}

FW_Option_Type::register( 'FW_Option_Type_FwMultiInline' );
endif;
