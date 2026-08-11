<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Update guard — a confirmation dialog shown before the user MANUALLY updates the
 * Unyson+ plugin from wp-admin, reminding them to back up their files + database.
 *
 * Covers the two manual update paths:
 *   - Plugins page: the "update now" link AND the bulk "Update" action (both funnel
 *     through wp.updates.updatePlugin(), which the guard JS wraps).
 *   - Dashboard → Updates (update-core.php): the "Update Plugins" button POSTs a
 *     form, which the guard JS intercepts on submit.
 *
 * Background auto-updates run headless (WP-Cron, no UI) and are intentionally NOT
 * intercepted — there is no user present to confirm, and the reminder is about a
 * human making a backup first. Customise the copy via the `fw_update_guard_message`
 * / `fw_update_guard_title` filters.
 */
if ( ! function_exists( 'fw_upw_update_guard_assets' ) ) {
	function fw_upw_update_guard_assets( $hook ) {
		// Only the screens where a plugin update can be triggered.
		if ( 'plugins.php' !== $hook && 'update-core.php' !== $hook ) {
			return;
		}

		// Resolve this plugin's basename/slug from the framework location:
		// this file = <plugin>/framework/includes/update-guard/update-guard.php
		$main     = dirname( __DIR__, 3 ) . '/unysonplus.php';
		$basename = plugin_basename( $main ); // e.g. "unysonplus/unysonplus.php"
		$slug     = dirname( $basename );      // e.g. "unysonplus"

		$ver = function_exists( 'fw' ) && fw()->manifest ? fw()->manifest->get_version() : false;

		wp_enqueue_style(
			'fw-update-guard',
			fw_get_framework_directory_uri( '/includes/update-guard/update-guard.css' ),
			array( 'dashicons' ),
			$ver
		);
		wp_enqueue_script(
			'fw-update-guard',
			fw_get_framework_directory_uri( '/includes/update-guard/update-guard.js' ),
			array( 'jquery', 'updates' ),
			$ver,
			true
		);

		wp_localize_script( 'fw-update-guard', 'fwUpdateGuard', array(
			'plugin'  => $basename,
			'slug'    => $slug,
			'title'   => apply_filters( 'fw_update_guard_title', __( 'Update Unyson+ Framework?', 'fw' ) ),
			'message' => apply_filters(
				'fw_update_guard_message',
				__( "You're about to update the Unyson+ Framework. Make sure you've created a fresh backup of your files and database before you proceed.", 'fw' )
			),
			'proceed' => __( 'Proceed', 'fw' ),
			'cancel'  => __( 'Cancel', 'fw' ),
		) );
	}

	add_action( 'admin_enqueue_scripts', 'fw_upw_update_guard_assets' );
}
