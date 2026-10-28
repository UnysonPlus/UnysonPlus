<?php
/**
 * PHP Version: 7.4 or higher
 */
if (!defined('FW')) die('Forbidden');

/**
 * Work with $_SESSION
 *
 * Advantages: Do not session_start() on every refresh, but only when it is accessed
 */
class FW_Session {
	private static function start_session() {
		/** Filters whether PHP sessions may be started (default true), letting a site disable session_start() usage. */
		if ( ! apply_filters( 'fw_use_sessions', true ) || session_id() ) {
			return;
		}

		/**
		 * session_start() sends Set-Cookie, so it cannot run once output has begun.
		 *
		 * This could not happen while the front-end hook started a session on every
		 * request; now that it only RESUMES an existing one (see includes/hooks.php),
		 * a write with no session already going and output already sent would raise
		 * "headers already sent". Skip quietly instead: the only front-end writer is
		 * the contact form, which runs on wp_loaded long before anything is printed,
		 * so in practice this guard never fires - it is here so that a future caller
		 * in the wrong place degrades to a lost message rather than a PHP warning on
		 * a live page.
		 */
		if ( headers_sent() ) {
			return;
		}

		session_start();
	}

	public static function get( $key, $default_value = null ) {
		if ( ! apply_filters( 'fw_use_sessions', true ) ) {
			return array();
		}

		self::start_session();

		// No session could be started (output already sent): there is nothing to read.
		if ( ! isset( $_SESSION ) ) {
			return $default_value;
		}

		return fw_akg( $key, $_SESSION, $default_value );
	}

	public static function set( $key, $value ) {
		self::start_session();

		if ( ! isset( $_SESSION ) ) {
			return;
		}

		fw_aks( $key, $value, $_SESSION );
	}

	public static function del( $key ) {
		self::start_session();

		if ( ! isset( $_SESSION ) ) {
			return;
		}

		fw_aku( $key, $_SESSION );
	}
}
