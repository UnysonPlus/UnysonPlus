<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Unyson+ custom-field Dynamic Content tag.
 *
 * Pulls a saved post option (Unyson+ post meta) by key, falling back to native
 * WordPress post meta when no Unyson+ value is stored under that key.
 */
add_filter( 'fw:dynamic-content:tags', '_fw_dynamic_content_register_unysonplus_tags' );

if ( ! function_exists( '_fw_dynamic_content_register_unysonplus_tags' ) ) :
	function _fw_dynamic_content_register_unysonplus_tags( $tags ) {
		$tags['post_meta'] = array(
			'label'   => __( 'Custom Field', 'fw' ),
			'group'   => __( 'Unyson+ Fields', 'fw' ),
			'params'  => array(
				array( 'id' => 'key', 'label' => __( 'Field key', 'fw' ), 'type' => 'text', 'default' => '' ),
			),
			'resolve' => function ( $params, $context ) {
				$key = isset( $params['key'] ) ? trim( $params['key'] ) : '';
				if ( '' === $key ) {
					return '';
				}

				$post_id = ! empty( $context['post_id'] ) ? (int) $context['post_id'] : (int) get_the_ID();
				if ( ! $post_id ) {
					return '';
				}

				// Prefer an Unyson+ post option, then fall back to native meta.
				if ( function_exists( 'fw_get_db_post_option' ) ) {
					$value = fw_get_db_post_option( $post_id, $key, null );

					if ( null !== $value && '' !== $value && is_scalar( $value ) ) {
						return (string) $value;
					}
				}

				$native = get_post_meta( $post_id, $key, true );

				return is_scalar( $native ) ? (string) $native : '';
			},
		);

		return $tags;
	}
endif;
