<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Shared post-type choice list.
 *
 * Several extensions need "every post type a user could meaningfully target",
 * which is the registered set minus WordPress' internal bookkeeping types. That
 * skip-list used to be duplicated verbatim in the Post Types and Custom Fields
 * extensions, so it drifted whenever core added an internal type (the WP Font
 * Library's wp_font_family / wp_font_face were the last ones). One
 * implementation now serves both.
 */

if ( ! function_exists( 'fw_upw_internal_post_types' ) ) :
	/**
	 * Post type keys that are WordPress plumbing, never user content.
	 *
	 * @return string[]
	 */
	function fw_upw_internal_post_types() {
		return apply_filters( 'fw_upw_internal_post_types', array(
			'attachment',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
			'wp_font_family',
			'wp_font_face',
		) );
	}
endif;

if ( ! function_exists( 'fw_upw_post_type_choices' ) ) :
	/**
	 * Choices for any "pick a post type" option, keyed by slug and labelled
	 * "Singular (slug)" so an ambiguous label is still identifiable.
	 *
	 * @param array $args {
	 *     @type bool  $public_only Only public post types. Default false.
	 *     @type array $extra       slug => label pairs to append if not already
	 *                              present — used to surface definitions that are
	 *                              saved but not yet registered.
	 * }
	 *
	 * @return array
	 */
	function fw_upw_post_type_choices( $args = array() ) {
		$args = array_merge( array(
			'public_only' => false,
			'extra'       => array(),
		), (array) $args );

		$skip    = fw_upw_internal_post_types();
		$query   = $args['public_only'] ? array( 'public' => true ) : array();
		$choices = array();

		foreach ( get_post_types( $query, 'objects' ) as $pt ) {
			if ( in_array( $pt->name, $skip, true ) ) {
				continue;
			}

			$label = ( isset( $pt->labels->singular_name ) && $pt->labels->singular_name )
				? $pt->labels->singular_name
				: $pt->name;

			$choices[ $pt->name ] = $label . ' (' . $pt->name . ')';
		}

		foreach ( (array) $args['extra'] as $slug => $label ) {
			$slug = sanitize_key( $slug );
			if ( $slug !== '' && ! isset( $choices[ $slug ] ) ) {
				$choices[ $slug ] = ( $label !== '' ? $label : $slug ) . ' (' . $slug . ')';
			}
		}

		return $choices;
	}
endif;
