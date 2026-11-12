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

	/**
	 * Is there a session to work with: one already running, or a session cookie
	 * the browser sent back (so session_start() would RESUME, not create one).
	 */
	private static function has_session() {
		return (bool) session_id() || isset( $_COOKIE[ session_name() ] );
	}

	/** True for null / '' / an array holding only empty values (e.g. empty flash-message lists). */
	private static function is_empty_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $v ) {
				if ( ! self::is_empty_value( $v ) ) {
					return false;
				}
			}
			return true;
		}
		return null === $value || '' === $value;
	}

	public static function get( $key, $default_value = null ) {
		if ( ! apply_filters( 'fw_use_sessions', true ) ) {
			return array();
		}

		/*
		 * Reading never CREATES a session. With no session cookie there is nothing
		 * stored to read, and session_start() would only send Set-Cookie plus PHP's
		 * Cache-Control: no-store - which made every front-end page uncacheable: the
		 * flash-message printer reads here from wp_footer, and when the page is
		 * output-buffered (the Asset Optimizer buffers it) headers_sent() is still
		 * false there, so the guard in start_session() did not stop it. Seen live on
		 * a WP Engine site: every page carried no-store, the host capped its page
		 * cache at 10 minutes and visitors kept landing on uncached renders.
		 */
		if ( ! self::has_session() ) {
			return $default_value;
		}

		self::start_session();

		// No session could be started (output already sent): there is nothing to read.
		if ( ! isset( $_SESSION ) ) {
			return $default_value;
		}

		return fw_akg( $key, $_SESSION, $default_value );
	}

	public static function set( $key, $value ) {
		// Storing nothing must not create a session either (the flash-message
		// printer writes back an empty list after every page). A real value - a
		// contact form's message - still starts one, as before.
		if ( ! self::has_session() && self::is_empty_value( $value ) ) {
			return;
		}

		self::start_session();

		if ( ! isset( $_SESSION ) ) {
			return;
		}

		fw_aks( $key, $value, $_SESSION );
	}

	public static function del( $key ) {
		if ( ! self::has_session() ) {
			return; // nothing stored, nothing to delete
		}

		self::start_session();

		if ( ! isset( $_SESSION ) ) {
			return;
		}

		fw_aku( $key, $_SESSION );
	}
}
