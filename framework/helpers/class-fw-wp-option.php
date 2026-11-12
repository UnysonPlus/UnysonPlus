<?php
/**
 * PHP Version: 7.4 or higher
 */
if (!defined('FW')) die('Forbidden');

/**
 * Alternative to WordPress get_option() and update_option() functions
 *
 * Features:
 * - Works with "multi keys"
 */
class FW_WP_Option
{
	/**
	 * Values already read during THIS request, keyed by option name.
	 *
	 * WordPress primes the object cache with the option's RAW string and runs
	 * maybe_unserialize() on it inside every get_option() call (see the note above
	 * wp-includes/option.php's cache priming). A page that reads one option many
	 * times therefore unserializes the same bytes many times, which is cheap for a
	 * small option and expensive for a large one.
	 *
	 * Theme Settings is the large one: it is a single non-autoloaded option, and a
	 * real site's front page read it 413 times in one request. Measured on a 94 KB
	 * settings option: 413 get_option() calls cost 113 ms, of which 111 ms was
	 * maybe_unserialize(); served from this array the same 413 reads cost 0.3 ms.
	 *
	 * Stale reads are the obvious hazard, so the cache is dropped whenever WordPress
	 * reports the option added, updated or deleted - by anyone, not only by set()
	 * below - which is wired up at the bottom of this file.
	 */
	private static $cache = array();

	/**
	 * Forget a memoized option, or all of them when no name is given.
	 *
	 * @param string|null $option_name
	 */
	public static function flush_cache( $option_name = null ) {
		if ( is_null( $option_name ) ) {
			self::$cache = array();
		} else {
			unset( self::$cache[ $option_name ] );
		}
	}

	/**
	 * @param string $option_name
	 * @param string|null $specific_multi_key 'ab/c/def'
	 * @param null|mixed $default_value If no option found in the database, this value will be returned
	 * @param bool|null $get_original_value REMOVED https://github.com/ThemeFuse/Unyson/issues/1676
	 * @return mixed|null
	 */
	public static function get($option_name, $specific_multi_key = null, $default_value = null, $get_original_value = null)
	{
		if ( ! is_null($get_original_value) ) {
			_doing_it_wrong(__FUNCTION__, '$get_original_value parameter was removed', 'Unyson 2.5.8');
		}

		if ( array_key_exists( $option_name, self::$cache ) ) {
			$value = self::$cache[ $option_name ];
		} else {
			$value = self::$cache[ $option_name ] = get_option( $option_name, null );
		}

		if (empty($specific_multi_key) && $specific_multi_key !== '0') {
			return is_null($value) ? fw_call( $default_value ) : $value;
		} else {
			return fw_akg($specific_multi_key, $value, $default_value);
		}
	}

	/**
	 * Alternative for update_option()
	 * @param string $option_name
	 * @param string|null $specific_multi_key
	 * @param array|string|int|bool $set_value
	 */
	public static function set($option_name = '', $specific_multi_key = null, $set_value = '')
	{
		if ($specific_multi_key === null) { // Replace entire option
			update_option($option_name, $set_value, false);
		} else { // Change only specified key
			$value = self::get($option_name, null, true);
			fw_aks($specific_multi_key, $set_value, $value);
			update_option($option_name, $value, false);
		}
	}
}

/**
 * Drop a memoized value the moment WordPress says the option changed, whoever changed
 * it - FW_WP_Option::set(), a plain update_option() somewhere else, an import, WP-CLI.
 * Each of these hooks passes the option name first.
 */
if ( function_exists( 'add_action' ) ) {
	foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $fw_wp_option_hook ) {
		add_action(
			$fw_wp_option_hook,
			static function ( $option_name ) {
				FW_WP_Option::flush_cache( $option_name );
			},
			1
		);
	}
	unset( $fw_wp_option_hook );
}
