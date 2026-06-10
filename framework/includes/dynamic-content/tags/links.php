<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * "Links" Dynamic Content tags: a Permalink tag per public post type, each with a
 * gear dropdown of that type's published items. Resolves to a live get_permalink(),
 * so a link built from one of these tokens follows the target's slug if it ever
 * changes — e.g. drop {{page_permalink|id=42}} into any link field.
 *
 * The item dropdown (choices) is built only in the admin, where the picker lives.
 * The frontend resolver also builds the tag registry (to resolve tokens), but it
 * only needs the resolve callback + the id carried in the token — never the choices
 * — so we skip the get_posts() queries there entirely.
 */
add_filter( 'fw:dynamic-content:tags', '_fw_dynamic_content_register_link_tags' );

if ( ! function_exists( '_fw_dynamic_content_register_link_tags' ) ) :
	function _fw_dynamic_content_register_link_tags( $tags ) {
		$g_links = __( 'Links', 'fw' );

		// Max items listed per post type (keeps a huge type from making a giant
		// dropdown). Filterable for sites that want more / fewer.
		$limit = (int) apply_filters( 'fw:dynamic-content:permalink_choices_limit', 200 );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $pt ) {
			if ( in_array( $pt->name, array( 'attachment' ), true ) ) {
				continue; // media items aren't link targets
			}

			$choices = array();

			// Only the picker (admin) needs the dropdown; the frontend never does.
			if ( is_admin() ) {
				$items = get_posts( array(
					'post_type'        => $pt->name,
					'post_status'      => 'publish',
					'numberposts'      => $limit,
					'orderby'          => 'title',
					'order'            => 'ASC',
					'suppress_filters' => false,
				) );

				foreach ( $items as $item ) {
					$title = ( '' !== $item->post_title )
						? $item->post_title
						/* translators: %d: post ID. */
						: sprintf( __( '(no title) #%d', 'fw' ), $item->ID );
					// Ordered array (not a map): JS reorders numeric object keys,
					// which would break the by-title ordering.
					$choices[] = array( 'value' => (string) $item->ID, 'label' => $title );
				}

				// Nothing published in this type → don't show an empty picker entry.
				if ( empty( $choices ) ) {
					continue;
				}
			}

			$singular = isset( $pt->labels->singular_name ) && '' !== $pt->labels->singular_name
				? $pt->labels->singular_name
				: $pt->name;

			$tags[ $pt->name . '_permalink' ] = array(
				/* translators: %s: post type singular name, e.g. "Page". */
				'label'   => sprintf( __( '%s Permalink', 'fw' ), $singular ),
				'group'   => $g_links,
				'params'  => array(
					array(
						'id'      => 'id',
						'label'   => $singular,
						'type'    => 'select',
						'choices' => $choices,
						'default' => '',
						'help'    => __( 'Pick the target — its permalink is inserted live, so the link follows any future slug change. (Lists up to 200 items, by title.)', 'fw' ),
					),
				),
				'resolve' => function ( $params, $context ) {
					$id = ! empty( $params['id'] ) ? (int) $params['id'] : 0;

					return $id ? (string) get_permalink( $id ) : '';
				},
			);
		}

		return $tags;
	}
endif;
