<?php if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die('Forbidden');
}

/**
 * Remove all plugin and extensions data
 * Search in all extensions and include the uninstall.php file
 *
 * WARNING!
 * The uninstall.php file must not contain:
 * <?php if ( !defined( 'FW' ) ) die('Forbidden');
 * because the framework is not loaded at this point, use:
 * <?php if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) die('Forbidden');
 * PHP Version: 7.4 or higher
 */

if ( ! function_exists( '_include_file_isolated' ) ) {
	/**
	 * Isolated include to avoid variable conflicts.
	 *
	 * @param string $path File path.
	 * @return void
	 */
	function _include_file_isolated( string $path ): void {
		include $path;
	}
}

final class FW_Plugin_Uninstall {
	/**
	 * All extensions with uninstall.php
	 * @var array<string, string>
	 */
	private array $extensions = [];

	public function __construct() {
		$this->read_extensions(
			__DIR__ . '/framework/extensions',
			$this->extensions
		);

		{
			/** @var wpdb $wpdb */
			global $wpdb;

			$this->uninstall();

			// Handle multisite uninstall
			if ( is_multisite() ) { // http://wordpress.stackexchange.com/a/80351/60424
				$original_blog_id = get_current_blog_id();

				foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
					switch_to_blog( (int) $blog_id );
					$this->uninstall();
				}

				switch_to_blog( $original_blog_id );
			}
		}
	}

	/**
	 * Recursively read extensions and find uninstall.php
	 *
	 * @param string $dir
	 * @param array  $extensions
	 * @return void
	 */
	private function read_extensions( string $dir, array &$extensions ): void {
		$ext_dirs = glob( $dir . '/*', GLOB_ONLYDIR ) ?: [];

		if ( empty( $ext_dirs ) ) {
			return;
		}

		foreach ( $ext_dirs as $ext_dir ) {
			if (
				file_exists( $ext_dir . '/manifest.php' ) &&
				file_exists( $ext_dir . '/uninstall.php' )
			) {
				$extensions[ basename( $ext_dir ) ] = $ext_dir . '/uninstall.php';
			}

			$this->read_extensions( $ext_dir . '/extensions', $extensions );
		}
	}

	/**
	 * Perform uninstall process
	 *
	 * @return void
	 */
	private function uninstall(): void {
		// Remove framework data
		{
			// ...
		}

		// Remove extensions data
		foreach ( $this->extensions as $uninstall_file ) {
			_include_file_isolated( $uninstall_file );
		}
	}
}

// Run uninstall handler
new FW_Plugin_Uninstall();
