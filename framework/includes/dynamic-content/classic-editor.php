<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Dynamic Content — classic (TinyMCE) editor integration.
 *
 * The picker is normally attached only to Unyson option fields (Text / Textarea /
 * Rich Editor), via the `.fw-dynamic-content-trigger` icon. The native WordPress
 * post-content editor isn't an Unyson option, so it never got the button.
 *
 * This file:
 *   1. adds an "Insert Dynamic Content" button to the classic editor's media-buttons
 *      row (reusing the exact same picker popover — see the classic-trigger wiring in
 *      static/js/dynamic-content.js), and
 *   2. resolves {{tokens}} typed into post content / excerpts on the frontend, so the
 *      button is actually useful there (option fields are resolved separately, on
 *      `fw_shortcode_render_view:atts`).
 *
 * Both are gated to {{…}} tokens, so they are no-ops when none are present.
 */

/* -----------------------------------------------------------------------------
 * 1. Classic editor button (admin)
 * -------------------------------------------------------------------------- */

if ( ! function_exists( '_fw_dc_classic_editor_button' ) ) :
	/**
	 * Print the Dynamic Content button next to "Add Media" — main post editor only.
	 *
	 * Only the main content editor (id "content") gets it; Unyson Rich Editor option
	 * fields (ids prefixed "fw_wp_editor_") already get their own relocated icon, so we
	 * skip them to avoid a duplicate button.
	 *
	 * @param string $editor_id The editor instance id passed by the `media_buttons` action.
	 */
	function _fw_dc_classic_editor_button( $editor_id ) {
		if ( 'content' !== $editor_id || ! class_exists( 'FW_Dynamic_Content' ) ) {
			return;
		}

		// Normally already enqueued by the option fields on this screen; this is a fallback.
		_fw_dc_enqueue_picker_assets();

		printf(
			'<button type="button" class="button fw-dc-editor-button fw-dc-classic-trigger" data-editor="%s">'
				. '<span class="dashicons dashicons-database"></span> '
				. '<span class="fw-dc-label">%s</span>'
				. '</button>',
			esc_attr( $editor_id ),
			esc_html__( 'Dynamic Content', 'fw' )
		);
	}
	add_action( 'media_buttons', '_fw_dc_classic_editor_button', 20 );
endif;

if ( ! function_exists( '_fw_dc_enqueue_picker_assets' ) ) :
	/**
	 * Ensure the Dynamic Content picker assets are present.
	 *
	 * On a screen that renders Unyson option fields (e.g. the Review meta box) these are
	 * already enqueued by the option-type static loader, so this is a no-op. It only does
	 * work on editor screens that have no Unyson option fields.
	 */
	function _fw_dc_enqueue_picker_assets() {
		if ( ! function_exists( 'fw_dynamic_content' ) ) {
			return;
		}
		if ( wp_script_is( 'fw-dynamic-content', 'enqueued' ) || wp_script_is( 'fw-dynamic-content', 'done' ) ) {
			return;
		}

		$ver = fw()->manifest->get_version();

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style(
			'fw-dynamic-content',
			fw_get_framework_directory_uri( '/includes/dynamic-content/static/css/dynamic-content.css' ),
			array( 'fw' ),
			$ver
		);
		wp_enqueue_script(
			'fw-dynamic-content',
			fw_get_framework_directory_uri( '/includes/dynamic-content/static/js/dynamic-content.js' ),
			array( 'jquery', 'fw-events', 'fw', 'fw-reactive-options' ),
			$ver,
			true
		);
		wp_localize_script( 'fw-dynamic-content', '_fw_dynamic_content', array(
			'tags' => fw_dynamic_content()->get_tags_for_js(),
			'l10n' => array(
				'search'        => __( 'Search…', 'fw' ),
				'no_results'    => __( 'No tags found', 'fw' ),
				'insert'        => __( 'Insert', 'fw' ),
				'back'          => __( 'Back', 'fw' ),
				'fallback'      => __( 'Fallback', 'fw' ),
				'editor_button' => __( 'Dynamic Content', 'fw' ),
			),
		) );
	}
endif;

/* -----------------------------------------------------------------------------
 * 2. Frontend resolution of {{tokens}} in post content / excerpt
 * -------------------------------------------------------------------------- */

if ( ! function_exists( '_fw_dc_resolve_post_content' ) ) :
	/**
	 * Resolve {{tokens}} in a piece of post text against the current post.
	 *
	 * @param string $text
	 * @return string
	 */
	function _fw_dc_resolve_post_content( $text ) {
		if ( ! is_string( $text ) || false === strpos( $text, '{{' ) || ! function_exists( 'fw_dynamic_content' ) ) {
			return $text;
		}

		return fw_dynamic_content()->resolve( $text, array( 'post_id' => get_the_ID() ) );
	}

	// Run before do_shortcode (priority 11) so tokens inside content are resolved first.
	add_filter( 'the_content', '_fw_dc_resolve_post_content', 9 );
	add_filter( 'the_excerpt', '_fw_dc_resolve_post_content', 9 );
endif;
