<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * Compatibility with themes built for the original Unyson.
 *
 * Many of those themes declare Unyson as a required plugin through the TGM Plugin Activation library, so after a
 * site switches to Unyson+ they keep showing "The following required plugin is currently inactive: Unyson" with a
 * link that would re-activate the old framework next to this one. Unyson+ provides everything that plugin did, so
 * mark the requirement as met: TGMPA 2.5+ treats a plugin whose `is_callable` callback exists as active; older
 * TGMPA versions have no such field, so the requirement is removed instead.
 */
if ( ! function_exists( '_fw_unyson_compat_tgmpa' ) ) :
	function _fw_unyson_compat_tgmpa() {
		$tgmpa = isset( $GLOBALS['tgmpa'] ) ? $GLOBALS['tgmpa'] : null;
		if ( ! $tgmpa && class_exists( 'TGM_Plugin_Activation' ) && method_exists( 'TGM_Plugin_Activation', 'get_instance' ) ) {
			$tgmpa = TGM_Plugin_Activation::get_instance();
		}
		if ( ! is_object( $tgmpa ) || ! isset( $tgmpa->plugins ) || ! is_array( $tgmpa->plugins ) || ! isset( $tgmpa->plugins['unyson'] ) ) {
			return;
		}
		if ( defined( 'TGM_Plugin_Activation::TGMPA_VERSION' ) && version_compare( TGM_Plugin_Activation::TGMPA_VERSION, '2.5.0', '>=' ) ) {
			$tgmpa->plugins['unyson']['is_callable'] = 'fw';
		} else {
			unset( $tgmpa->plugins['unyson'] );
		}
	}
	add_action( 'tgmpa_register', '_fw_unyson_compat_tgmpa', 1000 );
endif;
