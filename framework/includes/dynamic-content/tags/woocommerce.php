<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * WooCommerce Dynamic Content tags.
 *
 * Registered only when WooCommerce is active. The class_exists() guard runs at
 * resolve/picker time (the filter fires after `init`), so it correctly reflects
 * whether WooCommerce is loaded — and the whole group is invisible otherwise.
 */
add_filter( 'fw:dynamic-content:tags', '_fw_dynamic_content_register_woocommerce_tags' );

if ( ! function_exists( '_fw_dynamic_content_register_woocommerce_tags' ) ) :
	function _fw_dynamic_content_register_woocommerce_tags( $tags ) {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			return $tags;
		}

		$group = __( 'WooCommerce', 'fw' );

		// Resolve the current WC_Product, or null when not on a product.
		$product = function ( $context ) {
			$post_id = ! empty( $context['post_id'] ) ? (int) $context['post_id'] : (int) get_the_ID();
			if ( ! $post_id ) {
				return null;
			}
			$p = wc_get_product( $post_id );

			return $p ? $p : null;
		};

		$tags['product_title'] = array(
			'label'   => __( 'Product Title', 'fw' ),
			'group'   => $group,
			'resolve' => function ( $params, $context ) use ( $product ) {
				$p = $product( $context );

				return $p ? $p->get_name() : '';
			},
		);

		$tags['product_price'] = array(
			'label'   => __( 'Product Price', 'fw' ),
			'group'   => $group,
			'resolve' => function ( $params, $context ) use ( $product ) {
				$p = $product( $context );

				return $p ? wp_strip_all_tags( wc_price( $p->get_price() ) ) : '';
			},
		);

		$tags['product_sku'] = array(
			'label'   => __( 'Product SKU', 'fw' ),
			'group'   => $group,
			'resolve' => function ( $params, $context ) use ( $product ) {
				$p = $product( $context );

				return $p ? $p->get_sku() : '';
			},
		);

		$tags['product_stock'] = array(
			'label'   => __( 'Product Stock', 'fw' ),
			'group'   => $group,
			'resolve' => function ( $params, $context ) use ( $product ) {
				$p = $product( $context );
				if ( ! $p ) {
					return '';
				}
				$qty = $p->get_stock_quantity();

				return ( null !== $qty ) ? (string) $qty : wp_strip_all_tags( wc_get_stock_html( $p ) );
			},
		);

		$tags['product_rating'] = array(
			'label'   => __( 'Product Rating', 'fw' ),
			'group'   => $group,
			'resolve' => function ( $params, $context ) use ( $product ) {
				$p = $product( $context );

				return $p ? (string) $p->get_average_rating() : '';
			},
		);

		return $tags;
	}
endif;
