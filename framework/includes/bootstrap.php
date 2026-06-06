<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Bundled Bootstrap 5 stylesheet enqueue.
 *
 * The plugin used to lean on whatever theme it was running under to ship
 * Bootstrap. That coupled the page-builder shortcodes (Button, Notification,
 * Table, Tabs, etc.) and the theme's nav menu / forms / cards to a
 * theme-side stylesheet outside the plugin's control — and made Theme
 * Settings → Default Gap unreliable, because Bootstrap's stock
 * `.row { --bs-gutter-x: 1.5rem; }` was loading AFTER our presets and
 * silently clobbering them via source order.
 *
 * Now the plugin ships Bootstrap itself (`framework/static/css/bootstrap.min.css`)
 * and enqueues it at `wp_enqueue_scripts` priority 5 so:
 *
 *   - It loads BEFORE plugin shortcode CSS and theme CSS (default priority 10).
 *   - It loads BEFORE `unysonplus-presets` (bumped to priority 20), so the
 *     Theme Settings overrides win the cascade naturally — no `!important`
 *     gymnastics required in `css-tokens.php`.
 *
 * Two opt-outs:
 *
 *   - Page Builder Settings → "Bootstrap 5 Stylesheet" checkbox. Default
 *     checked. Power users running a Tailwind / custom-CSS theme can uncheck
 *     to skip the enqueue (and accept that shortcode buttons / alerts /
 *     tables will lose their styling unless they replace them).
 *
 *   - Any other code that has already registered or enqueued the `bootstrap`
 *     handle wins — the plugin steps aside. Lets a third-party theme that
 *     ships its own customised Bootstrap build keep using it without dupe.
 */

if ( ! function_exists( 'unysonplus_enqueue_bootstrap' ) ) :
	function unysonplus_enqueue_bootstrap() {
		// Opt-OUT checkbox (Page Builder Settings → "Dequeue Bootstrap 5 CSS").
		// Default `false` (unchecked) so fresh / never-saved sites load Bootstrap
		// automatically; checking it disables the enqueue.
		if ( function_exists( 'fw_get_db_ext_settings_option' ) ) {
			$disabled = fw_get_db_ext_settings_option( 'page-builder', 'disable_bootstrap', false );
			// FW's checkbox storage has been seen as bool true, string '1', int 1.
			// Treat any "on" shape as "disable".
			if ( $disabled === true || $disabled === '1' || $disabled === 1 || $disabled === 'yes' ) {
				return;
			}
		}

		if ( wp_style_is( 'bootstrap', 'registered' ) || wp_style_is( 'bootstrap', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style(
			'bootstrap',
			fw_get_framework_directory_uri( '/static/css/bootstrap.min.css' ),
			array(),
			'5.3.3'
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'unysonplus_enqueue_bootstrap', 5 );
