<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * One-time seed of the extensions that should be ON out of the box.
 *
 * The framework decides what is active purely from the `fw_active_extensions`
 * option, so a freshly installed plugin starts with everything off and the
 * admin looks like stock wp-admin until the user goes hunting in Extensions.
 * This seeds the ones a new site is expected to want.
 *
 * It runs ONCE, guarded by an option. That is the whole point: an "ensure it
 * is always on" version could never be switched off, because the next admin
 * page load would turn it straight back on. After the seed the user owns the
 * setting, and deactivating an extension sticks.
 */
if ( ! function_exists( 'fw_upw_seed_default_extensions' ) ) :
	function fw_upw_seed_default_extensions() {
		if ( get_option( 'unysonplus_default_extensions_v1' ) ) {
			return;
		}

		// Mark it done FIRST. If anything below fails we still never re-run and
		// never fight the user's own choice.
		update_option( 'unysonplus_default_extensions_v1', 1, true );

		$defaults = apply_filters( 'fw_upw_default_extensions', array( 'admin-skin' ) );

		$active = get_option( 'fw_active_extensions', array() );
		if ( ! is_array( $active ) ) {
			return;
		}

		$changed = array();
		foreach ( (array) $defaults as $slug ) {
			// Only seed what is actually shipped in this build — the public zip is
			// core-only, so most extensions arrive later through the manager.
			if ( ! file_exists( fw_get_framework_directory( '/extensions/' . $slug . '/manifest.php' ) ) ) {
				continue;
			}
			if ( array_key_exists( $slug, $active ) ) {
				continue;
			}
			$active[ $slug ] = array();
			$changed[]       = $slug;
		}

		if ( ! $changed ) {
			return;
		}

		update_option( 'fw_active_extensions', $active );

		// Tells the extension to introduce itself once, so a user who did not ask
		// for a restyled admin is not left wondering what happened or how to undo it.
		update_option( 'unysonplus_admin_skin_intro_notice', 1, false );
	}

	add_action( 'admin_init', 'fw_upw_seed_default_extensions', 5 );
endif;
