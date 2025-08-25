<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Main framework class that contains everything
 *
 * Convention: All public properties should be only instances of the components (except special property: manifest)
 * PHP Version: 7.4 or higher
 */
if ( ! class_exists( '_Fw', false ) ) {
	final class _Fw {
		/** @var bool If already loaded */
		private static bool $loaded = false;

		/** @var FW_Framework_Manifest */
		public FW_Framework_Manifest $manifest;

		/** @var _FW_Component_Extensions */
		public _FW_Component_Extensions $extensions;

		/** @var _FW_Component_Backend */
		public _FW_Component_Backend $backend;

		/** @var _FW_Component_Theme */
		public _FW_Component_Theme $theme;

		public function __construct() {
			if ( self::$loaded ) {
				trigger_error( 'Framework already loaded', E_USER_ERROR );
			} else {
				self::$loaded = true;
			}

			// Framework directory
			$fw_dir = function_exists( 'fw_get_framework_directory' )
				? fw_get_framework_directory()
				: __DIR__;

			// manifest
			{
				require_once $fw_dir . '/manifest.php';
				/** @var array $manifest */

				$this->manifest = new FW_Framework_Manifest( $manifest );

				add_action( 'fw_init', [ $this, '_check_requirements' ], 1 );
			}

			// components
			{
				$this->extensions = new _FW_Component_Extensions();
				$this->backend    = new _FW_Component_Backend();
				$this->theme      = new _FW_Component_Theme();
			}
		}

		/**
		 * @internal
		 */
		public function _check_requirements(): void {
			if ( is_admin() && ! $this->manifest->check_requirements() ) {
				FW_Flash_Messages::add(
					'fw_requirements',
					__( 'Framework requirements not met:', 'fw' ) . ' ' . $this->manifest->get_not_met_requirement_text(),
					'warning'
				);
			}
		}
	}
}

/**
 * @return _Fw Framework instance
 */
if ( ! function_exists( 'fw' ) ) {
	function fw(): _Fw {
		static $FW = null; // cache

		if ( $FW === null ) {
			$FW = new _Fw();
		}

		return $FW;
	}
}
