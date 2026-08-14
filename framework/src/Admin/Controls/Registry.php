<?php
/**
 * The React control layer's PHP side.
 *
 * @package UnysonPlus
 */

declare( strict_types = 1 );

namespace UnysonPlus\Admin\Controls;

if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Registers the framework's React control bundle.
 *
 * ## What this is (and what it is not)
 *
 * The framework renders every option type in PHP — `FW_Option_Type::_render()`
 * returns HTML, and JavaScript enhances that HTML on `fw:options:init`. That
 * contract is unchanged and is not going away; it is what lets an extension
 * author ship a working option type in pure PHP.
 *
 * This class registers a **second renderer** for the same option schema: a set
 * of React components that can draw an option where server-rendered HTML cannot
 * go — a Gutenberg block inspector, or a new React admin screen. Nothing is
 * removed. An option type may have a PHP renderer only (most do today), or both.
 *
 * ## Why there is no React in this repository
 *
 * WordPress already ships React in wp-admin and exposes it as the `wp-element`
 * script handle. The bundle registered here depends on that handle and reads
 * `wp.element` off the global — it never imports or bundles a React of its own.
 * That is what keeps a second copy of React off the page, and it is why these
 * components drop into a block inspector unchanged.
 *
 * @since 2.16.11
 */
final class Registry {

	/**
	 * Script handle for the control bundle.
	 */
	public const HANDLE = 'fw-controls';

	/**
	 * Framework-relative path to the built bundle, without the extension.
	 */
	private const BUNDLE = '/static/js/controls/build/fw-controls';

	/**
	 * Whether register() has already run.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Register the bundle with WordPress.
	 *
	 * Registers only — callers enqueue. Safe to call more than once.
	 */
	public static function register(): void {
		if ( self::$registered ) {
			return;
		}

		self::$registered = true;

		wp_register_script(
			self::HANDLE,
			fw_get_framework_directory_uri( self::asset_path() ),
			self::dependencies(),
			self::version(),
			true
		);
	}

	/**
	 * Register (if needed) and enqueue the bundle.
	 */
	public static function enqueue(): void {
		self::register();
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * The WordPress-provided script handles the bundle reads off the global.
	 *
	 * `react` / `react-dom` are deliberately absent: the bundle consumes
	 * `wp.element`, and `wp-element` already pulls React in as its own
	 * dependency. Declaring React directly would invite a second copy.
	 *
	 * @return string[]
	 */
	private static function dependencies(): array {
		return array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-media-utils' );
	}

	/**
	 * Minified bundle in production, readable bundle under SCRIPT_DEBUG.
	 */
	private static function asset_path(): string {
		$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

		return self::BUNDLE . ( $debug ? '.js' : '.min.js' );
	}

	/**
	 * Cache-busting version — the framework version, so a bump busts the cache.
	 */
	private static function version(): string {
		$fw = function_exists( 'fw' ) ? fw() : null;

		if ( $fw && isset( $fw->manifest ) ) {
			return (string) $fw->manifest->get_version();
		}

		return '0';
	}
}
