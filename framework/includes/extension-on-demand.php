<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Switch an extension on the moment a feature needs it.
 *
 * Some extensions are shared plumbing that nobody activates by hand: they are hidden from the
 * Extensions list ($manifest['display'] = false, $manifest['standalone'] = false) and no other
 * extension lists them as a requirement, because most sites never need them. Payments is the
 * first: a catalog-only shop, a free members site and a contact form never take money. The
 * feature that does (selling switched on, a payment action added to a form) calls:
 *
 *     $r = fw_ext_ensure_active( 'payments' );
 *     if ( is_wp_error( $r ) ) { show $r->get_error_message() to the admin }
 *
 * It installs the extension when it is missing (from the Extensions library, like the
 * Extensions screen does) and activates it. Call it from the ADMIN action that turns the
 * feature on: it needs a user who may install or activate extensions, and a freshly activated
 * extension only loads from the NEXT request.
 *
 * In CORE because the callers must be able to ask before the extension exists.
 */
if ( ! function_exists( 'fw_ext_ensure_active' ) ) :
	/**
	 * @param string $name The extension's folder name.
	 *
	 * @return true|WP_Error True when it is active, or will be from the next request.
	 */
	function fw_ext_ensure_active( $name ) {
		$name = (string) $name;
		if ( fw_ext( $name ) ) {
			return true;
		}
		$manager = fw()->extensions->manager;
		if ( ! $manager ) {
			return new WP_Error( 'fw_ext_on_demand', __( 'Extensions cannot be managed on this site.', 'fw' ) );
		}

		$first = function ( $r ) {
			if ( is_wp_error( $r ) ) {
				return $r;
			}
			foreach ( is_array( $r ) ? $r : array() as $res ) {
				if ( is_wp_error( $res ) ) {
					return $res;
				}
			}
			return true;
		};

		$installed = $manager->get_installed_extensions( true );
		if ( ! isset( $installed[ $name ] ) ) {
			// The download needs WordPress's file helpers (admin-only includes) and direct file access.
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( ! WP_Filesystem() || ! FW_WP_Filesystem::has_direct_access( fw_get_framework_directory( '/extensions' ) ) ) {
				return new WP_Error( 'fw_ext_on_demand', __( 'This server needs FTP details to install files, so this feature cannot be switched on from here. Ask your host, or install the plugin package that includes it.', 'fw' ) );
			}
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
			// Installs and activates in one go.
			return $first( $manager->install_extensions( array( $name => array() ), array( 'cancel_on_error' => true ) ) );
		}

		return $first( $manager->activate_extensions( array( $name => array() ), true ) );
	}
endif;
