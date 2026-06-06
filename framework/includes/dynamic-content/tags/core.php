<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Core Dynamic Content tags: Post, Site, Author, Date & Time.
 *
 * Each callback receives ( array $params, array $context ) and returns a scalar.
 * $context may carry 'post_id'; otherwise we fall back to the current/global post.
 */
add_filter( 'fw:dynamic-content:tags', '_fw_dynamic_content_register_core_tags' );

if ( ! function_exists( '_fw_dynamic_content_register_core_tags' ) ) :
	function _fw_dynamic_content_register_core_tags( $tags ) {
		$g_post   = __( 'Post', 'fw' );
		$g_site   = __( 'Site', 'fw' );
		$g_author = __( 'Author', 'fw' );
		$g_date   = __( 'Date & Time', 'fw' );

		// Shared help text for the PHP date()/time() format param (shown under the
		// picker input). Leave the format blank to use the site's Settings → General format.
		$date_help = __( "Date Format:\nd=01–31, j=1–31, D=Mon, l=Monday, S=st/nd/th, m=01–12, n=1–12, M=Jan, F=January, y=26, Y=2026. Leave blank for the site date format.", 'fw' );
		$time_help = __( "Time Format:\ng=1–12, G=0–23, h=01–12, H=00–23, i=00–59 (min), s=00–59 (sec), a=am/pm, A=AM/PM, T=timezone. Leave blank for the site time format.", 'fw' );

		// Resolve the post id from context, falling back to the current post.
		$pid = function ( $context ) {
			if ( ! empty( $context['post_id'] ) ) {
				return (int) $context['post_id'];
			}

			return (int) get_the_ID();
		};

		// --- Post -----------------------------------------------------------
		$tags['post_title'] = array(
			'label'   => __( 'Post Title', 'fw' ),
			'group'   => $g_post,
			'resolve' => function ( $params, $context ) use ( $pid ) {
				$id = $pid( $context );

				return $id ? get_the_title( $id ) : '';
			},
		);

		$tags['post_excerpt'] = array(
			'label'   => __( 'Post Excerpt', 'fw' ),
			'group'   => $g_post,
			'resolve' => function ( $params, $context ) use ( $pid ) {
				$id = $pid( $context );

				return $id ? get_the_excerpt( $id ) : '';
			},
		);

		$tags['post_content'] = array(
			'label'   => __( 'Post Content', 'fw' ),
			'group'   => $g_post,
			'resolve' => function ( $params, $context ) use ( $pid ) {
				$id = $pid( $context );
				if ( ! $id ) {
					return '';
				}
				$post = get_post( $id );

				return $post ? wp_strip_all_tags( $post->post_content ) : '';
			},
		);

		$tags['post_id'] = array(
			'label'   => __( 'Post ID', 'fw' ),
			'group'   => $g_post,
			'resolve' => function ( $params, $context ) use ( $pid ) {
				return (string) $pid( $context );
			},
		);

		$tags['post_url'] = array(
			'label'   => __( 'Post URL', 'fw' ),
			'group'   => $g_post,
			'resolve' => function ( $params, $context ) use ( $pid ) {
				$id = $pid( $context );

				return $id ? get_permalink( $id ) : '';
			},
		);

		$tags['post_date'] = array(
			'label'   => __( 'Post Date', 'fw' ),
			'group'   => $g_post,
			'params'  => array(
				array( 'id' => 'format', 'label' => __( 'Format', 'fw' ), 'type' => 'text', 'default' => 'F j, Y', 'help' => $date_help ),
			),
			'resolve' => function ( $params, $context ) use ( $pid ) {
				$id = $pid( $context );
				if ( ! $id ) {
					return '';
				}
				$format = ( isset( $params['format'] ) && '' !== $params['format'] )
					? $params['format']
					: get_option( 'date_format' );

				return get_the_date( $format, $id );
			},
		);

		// --- Site -----------------------------------------------------------
		$tags['site_name'] = array(
			'label'   => __( 'Site Title', 'fw' ),
			'group'   => $g_site,
			'resolve' => function () {
				return get_bloginfo( 'name' );
			},
		);

		$tags['site_tagline'] = array(
			'label'   => __( 'Site Tagline', 'fw' ),
			'group'   => $g_site,
			'resolve' => function () {
				return get_bloginfo( 'description' );
			},
		);

		$tags['site_url'] = array(
			'label'   => __( 'Site URL', 'fw' ),
			'group'   => $g_site,
			'resolve' => function () {
				return home_url( '/' );
			},
		);

		$tags['admin_email'] = array(
			'label'   => __( 'Admin Email', 'fw' ),
			'group'   => $g_site,
			'resolve' => function () {
				return get_bloginfo( 'admin_email' );
			},
		);

		// --- Author ---------------------------------------------------------
		$author_id = function ( $context ) use ( $pid ) {
			$id = $pid( $context );

			return $id ? (int) get_post_field( 'post_author', $id ) : 0;
		};

		$tags['author_name'] = array(
			'label'   => __( 'Author Name', 'fw' ),
			'group'   => $g_author,
			'resolve' => function ( $params, $context ) use ( $author_id ) {
				$uid = $author_id( $context );

				return $uid ? get_the_author_meta( 'display_name', $uid ) : '';
			},
		);

		$tags['author_bio'] = array(
			'label'   => __( 'Author Bio', 'fw' ),
			'group'   => $g_author,
			'resolve' => function ( $params, $context ) use ( $author_id ) {
				$uid = $author_id( $context );

				return $uid ? get_the_author_meta( 'description', $uid ) : '';
			},
		);

		$tags['author_url'] = array(
			'label'   => __( 'Author URL', 'fw' ),
			'group'   => $g_author,
			'resolve' => function ( $params, $context ) use ( $author_id ) {
				$uid = $author_id( $context );

				return $uid ? get_author_posts_url( $uid ) : '';
			},
		);

		// --- Date & Time ----------------------------------------------------
		$tags['current_date'] = array(
			'label'   => __( 'Current Date', 'fw' ),
			'group'   => $g_date,
			'params'  => array(
				array( 'id' => 'format', 'label' => __( 'Format', 'fw' ), 'type' => 'text', 'default' => 'F j, Y', 'help' => $date_help ),
			),
			'resolve' => function ( $params ) {
				$format = ( isset( $params['format'] ) && '' !== $params['format'] )
					? $params['format']
					: get_option( 'date_format' );

				return date_i18n( $format );
			},
		);

		$tags['current_time'] = array(
			'label'   => __( 'Current Time', 'fw' ),
			'group'   => $g_date,
			'params'  => array(
				array( 'id' => 'format', 'label' => __( 'Format', 'fw' ), 'type' => 'text', 'default' => 'g:i a', 'help' => $time_help ),
			),
			'resolve' => function ( $params ) {
				$format = ( isset( $params['format'] ) && '' !== $params['format'] )
					? $params['format']
					: get_option( 'time_format' );

				return date_i18n( $format );
			},
		);

		$tags['current_year'] = array(
			'label'   => __( 'Current Year', 'fw' ),
			'group'   => $g_date,
			'resolve' => function () {
				return date_i18n( 'Y' );
			},
		);

		$tags['current_month'] = array(
			'label'   => __( 'Current Month', 'fw' ),
			'group'   => $g_date,
			'resolve' => function () {
				return date_i18n( 'F' );
			},
		);

		$tags['current_day'] = array(
			'label'   => __( 'Current Day', 'fw' ),
			'group'   => $g_date,
			'resolve' => function () {
				return date_i18n( 'l' );
			},
		);

		// Convenience alias for the common "© 2026" footer use-case.
		$tags['copyright_year'] = array(
			'label'   => __( 'Copyright Year', 'fw' ),
			'group'   => $g_date,
			'resolve' => function () {
				return date_i18n( 'Y' );
			},
		);

		return $tags;
	}
endif;
