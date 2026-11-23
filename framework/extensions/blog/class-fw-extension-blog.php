<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Extension_Blog extends FW_Extension {
	private $post_type = 'post';

	/** The blog-visibility modes the setting offers. */
	const MODES = array( 'on', 'admin', 'full_live', 'off' );

	/**
	 * @internal
	 */
	public function _init() {
		if ( is_admin() ) {
			// "Posts" -> "Blog Posts" relabel (Settings -> Writing can turn it off).
			if ( $this->rename_enabled() ) {
				add_action( 'admin_menu', array( $this, '_admin_action_rename_post_menu' ) );
				add_action( 'init', array( $this, '_admin_action_change_post_labels' ), 999 );
			}

			// The Blog settings (rename + visibility) on Settings -> Writing.
			add_action( 'admin_init', array( $this, '_register_blog_mode_setting' ) );

			$mode = $this->blog_mode();
			if ( 'on' !== $mode ) {
				// Every disabled mode hides the Blog Posts admin menu.
				add_action( 'admin_menu', array( $this, '_hide_blog_menu' ), 999 );
			}
			if ( in_array( $mode, array( 'full_live', 'off' ), true ) ) {
				// Also clear the "+ New -> Post" shortcut and the post dashboard widgets.
				add_action( 'admin_bar_menu', array( $this, '_remove_new_post_node' ), 999 );
				add_action( 'wp_dashboard_setup', array( $this, '_remove_post_dashboard_widgets' ), 999 );
			}
		} else {
			if ( $this->rename_enabled() ) {
				add_action( 'init', array( $this, '_theme_action_change_post_labels' ), 999 );
			}

			// Full disable: the blog is gone for visitors too (404).
			if ( 'off' === $this->blog_mode() ) {
				add_action( 'template_redirect', array( $this, '_blog_front_end_404' ) );
				add_action( 'admin_bar_menu', array( $this, '_remove_new_post_node' ), 999 ); // front-end admin bar
			}
		}
	}

	/**
	 * Whether to relabel the core "Posts" type to "Blog Posts" (the original
	 * Unyson behaviour). On by default; a site can turn it off to see WordPress's
	 * plain "Posts". Option `upw_blog_rename`, filter `upw_blog_rename`.
	 *
	 * @return bool
	 */
	public function rename_enabled() {
		$value = get_option( 'upw_blog_rename', '1' );
		$on    = ( '' !== (string) $value && '0' !== (string) $value );
		/** Filters whether "Posts" is relabelled to "Blog Posts". */
		return (bool) apply_filters( 'upw_blog_rename', $on );
	}

	/* ---------------------------------------------------------------------- *
	 * Disable the blog on sites that don't have one
	 * ---------------------------------------------------------------------- */

	/**
	 * The current blog-visibility mode: on | admin | full_live | off.
	 *
	 * Read from the `upw_blog_mode` option, overridable in code with the
	 * UPW_BLOG_MODE constant and the `upw_blog_mode` filter. Anything unknown
	 * falls back to 'on', so the blog is never hidden by a bad value.
	 *
	 * @return string
	 */
	public function blog_mode() {
		if ( defined( 'UPW_BLOG_MODE' ) && in_array( UPW_BLOG_MODE, self::MODES, true ) ) {
			$mode = UPW_BLOG_MODE;
		} else {
			$mode = (string) get_option( 'upw_blog_mode', 'on' );
			if ( ! in_array( $mode, self::MODES, true ) ) {
				$mode = 'on';
			}
		}
		/** Filters the blog-visibility mode (on | admin | full_live | off). */
		$mode = (string) apply_filters( 'upw_blog_mode', $mode );
		return in_array( $mode, self::MODES, true ) ? $mode : 'on';
	}

	/** @internal Register the settings + fields on Settings -> Writing. */
	public function _register_blog_mode_setting() {
		register_setting( 'writing', 'upw_blog_rename', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, '_sanitize_bool' ),
			'default'           => '1',
		) );
		register_setting( 'writing', 'upw_blog_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, '_sanitize_blog_mode' ),
			'default'           => 'on',
		) );
		add_settings_section( 'upw_blog_section', __( 'Blog', 'fw' ), array( $this, '_blog_section_intro' ), 'writing' );
		add_settings_field( 'upw_blog_rename', __( 'Rename "Posts"', 'fw' ), array( $this, '_blog_rename_field' ), 'writing', 'upw_blog_section' );
		add_settings_field( 'upw_blog_mode', __( 'Blog visibility', 'fw' ), array( $this, '_blog_mode_field' ), 'writing', 'upw_blog_section' );
	}

	/** @internal */
	public function _sanitize_blog_mode( $value ) {
		$value = (string) $value;
		return in_array( $value, self::MODES, true ) ? $value : 'on';
	}

	/** @internal A checkbox always submits '1' or '0' thanks to the hidden field. */
	public function _sanitize_bool( $value ) {
		return ( '1' === (string) $value ) ? '1' : '0';
	}

	/** @internal The "Rename Posts to Blog Posts" checkbox. */
	public function _blog_rename_field() {
		// A hidden field with the same name guarantees the key is always posted
		// (options.php skips a checkbox that is absent from $_POST), so unticking
		// it actually saves "off".
		echo '<input type="hidden" name="upw_blog_rename" value="0" />';
		echo '<label for="upw_blog_rename"><input type="checkbox" name="upw_blog_rename" id="upw_blog_rename" value="1" ' . checked( $this->rename_enabled(), true, false ) . ' /> '
			. esc_html__( 'Show "Blog Posts" instead of WordPress\'s plain "Posts" in the admin menu and labels', 'fw' )
			. '</label>';
	}

	/** @internal */
	public function _blog_section_intro() {
		echo '<p class="description">' . esc_html__(
			'For sites that do not run a blog. Hiding it cleans up the admin, and disabling it fully also hides the blog from visitors. Pages, portfolios and other post types are not affected.',
			'fw'
		) . '</p>';
	}

	/** @internal */
	public function _blog_mode_field() {
		$mode = (string) get_option( 'upw_blog_mode', 'on' );
		if ( ! in_array( $mode, self::MODES, true ) ) {
			$mode = 'on';
		}
		$choices = array(
			'on'        => __( 'Enabled — show the blog everywhere (default)', 'fw' ),
			'admin'     => __( 'Hide the menu — hide only the Blog Posts admin menu', 'fw' ),
			'full_live' => __( 'Hide in admin — also remove "+ New -> Post" and the post dashboard widgets; the blog still works for visitors', 'fw' ),
			'off'       => __( 'Disable — hide it in admin AND 404 the blog for visitors (single posts, the blog page, category / tag / date / author archives and the feed)', 'fw' ),
		);
		echo '<select name="upw_blog_mode" id="upw_blog_mode"' . ( defined( 'UPW_BLOG_MODE' ) ? ' disabled' : '' ) . '>';
		foreach ( $choices as $value => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $mode, $value, false ), esc_html( $label ) );
		}
		echo '</select>';
		if ( defined( 'UPW_BLOG_MODE' ) ) {
			echo '<p class="description">' . esc_html__( 'Set in code by the UPW_BLOG_MODE constant; this control is read-only.', 'fw' ) . '</p>';
		}
	}

	/** @internal Hide the Blog Posts (core "post" type) admin menu. */
	public function _hide_blog_menu() {
		remove_menu_page( 'edit.php' );
	}

	/** @internal Remove the "+ New -> Post" admin-bar shortcut. */
	public function _remove_new_post_node( $bar ) {
		if ( $bar instanceof WP_Admin_Bar ) {
			$bar->remove_node( 'new-post' );
		}
	}

	/** @internal Remove the post-centric dashboard widgets (Quick Draft, Activity). */
	public function _remove_post_dashboard_widgets() {
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	}

	/**
	 * @internal Full disable: serve a 404 for every blog front-end request so a
	 * site with no blog exposes no blog URLs. Pages and custom post types are
	 * left alone; the real front page is never 404'd even if it is the blog index.
	 */
	public function _blog_front_end_404() {
		if ( is_admin() || is_front_page() ) {
			return;
		}

		$is_blog = is_singular( 'post' )
			|| is_home()
			|| is_post_type_archive( 'post' )
			|| is_category() || is_tag()
			|| is_date() || is_author();

		if ( ! $is_blog && is_feed() ) {
			$is_blog = is_singular( 'post' ) || is_home() || is_category() || is_tag() || is_date() || is_author();
		}

		if ( ! $is_blog ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		$template = get_404_template();
		if ( $template ) {
			include $template;
		}
		exit;
	}

	/**
	 * Changes the labels value od the posts type: post from Post to Blog Post
	 * @internal
	 */
	public function _theme_action_change_post_labels() {
		global $wp_post_types;
		$p = $this->post_type;

		// Someone has changed this post type, always check for that!
		if ( empty ( $wp_post_types[ $p ] )
		     or ! is_object( $wp_post_types[ $p ] )
		     or empty ( $wp_post_types[ $p ]->labels )
		) {
			return;
		}

		$wp_post_types[ $p ]->labels->name               = __( 'Blog', 'fw' );
		$wp_post_types[ $p ]->labels->singular_name      = __( 'Blog', 'fw' );
		$wp_post_types[ $p ]->labels->add_new            = __( 'Add blog post', 'fw' );
		$wp_post_types[ $p ]->labels->add_new_item       = __( 'Add new blog post', 'fw' );
		$wp_post_types[ $p ]->labels->all_items          = __( 'All blog posts', 'fw' );
		$wp_post_types[ $p ]->labels->edit_item          = __( 'Edit blog post', 'fw' );
		$wp_post_types[ $p ]->labels->name_admin_bar     = __( 'Blog Post', 'fw' );
		$wp_post_types[ $p ]->labels->menu_name          = __( 'Blog Post', 'fw' );
		$wp_post_types[ $p ]->labels->new_item           = __( 'New blog post', 'fw' );
		$wp_post_types[ $p ]->labels->not_found          = __( 'No blog posts found', 'fw' );
		$wp_post_types[ $p ]->labels->not_found_in_trash = __( 'No blog posts found in trash', 'fw' );
		$wp_post_types[ $p ]->labels->search_items       = __( 'Search blog posts', 'fw' );
		$wp_post_types[ $p ]->labels->view_item          = __( 'View blog post', 'fw' );
	}

	/**
	 * Changes the labels value od the posts type: post from Post to Blog Post
	 * @internal
	 */
	public function _admin_action_change_post_labels() {
		global $wp_post_types, $wp_taxonomies;
		$p = $this->post_type;

		// Someone has changed this post type, always check for that!
		if ( empty ( $wp_post_types[ $p ] )
		     or ! is_object( $wp_post_types[ $p ] )
		     or empty ( $wp_post_types[ $p ]->labels )
		) {
			return;
		}

		$wp_post_types[ $p ]->labels->name = __( 'Blog Posts', 'fw' );

		if ( empty ( $wp_taxonomies['category'] )
		     or ! is_object( $wp_taxonomies['category'] )
		     or empty ( $wp_taxonomies['category']->labels )
		) {
			return;
		}

		$wp_taxonomies['category']->labels->name = __( 'Blog Categories', 'fw' );
	}

	/**
	 * Changes the name in admin menu from Post to Blog Post
	 * @internal
	 */
	public function _admin_action_rename_post_menu() {
		global $menu;

		if ( isset( $menu[5] ) ) {
			$menu[5][0] = __( 'Blog Posts', 'fw' );
		}
	}
}