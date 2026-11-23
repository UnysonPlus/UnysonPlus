<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Admin-menu null-slug guard.
 *
 * PHP 8.1+ deprecates passing null to preg_replace(). WordPress core's
 * get_plugin_page_hookname() (wp-admin/includes/plugin.php) runs
 * preg_replace( '!\.php!', '', $slug ) for every item in the admin menu as it
 * renders the sidebar (menu-header.php). A menu or submenu item whose slug
 * (index 2 of the item array) is null — registered that way by any plugin,
 * theme, or a malformed custom-post-type / options page — makes core emit, on
 * every admin page:
 *
 *   preg_replace(): Passing null to parameter #3 ($subject) ... is deprecated
 *
 * It is a harmless notice, but it becomes VISIBLE on the plugin-update screen,
 * which flushes PHP notices inline (the "Deprecated: preg_replace()..." line a
 * user sees above "The update process is starting.").
 *
 * This runs after every admin_menu registration and coerces a null/absent slug
 * to an empty string. '' is a valid preg_replace subject, so the deprecation
 * can no longer fire. A null-slug menu item is already non-navigable, so
 * changing it to '' removes nothing — it only stops core choking on it under
 * PHP 8. Defensive by design: it fixes the notice whatever registered the bad
 * slug, ours or a third party's.
 */
if ( ! function_exists( 'fw_upw_guard_admin_menu_slugs' ) ) {
	function fw_upw_guard_admin_menu_slugs() {
		global $menu, $submenu;

		if ( is_array( $menu ) ) {
			foreach ( $menu as $i => $item ) {
				// isset() is false for both an absent key and a null value.
				if ( is_array( $item ) && ! isset( $item[2] ) ) {
					$menu[ $i ][2] = '';
				}
			}
		}

		if ( is_array( $submenu ) ) {
			foreach ( $submenu as $parent => $items ) {
				if ( ! is_array( $items ) ) {
					continue;
				}
				foreach ( $items as $j => $item ) {
					if ( is_array( $item ) && ! isset( $item[2] ) ) {
						$submenu[ $parent ][ $j ][2] = '';
					}
				}
			}
		}
	}

	// PHP_INT_MAX: after every extension, theme and third-party admin_menu
	// registration, and before core renders the menu in menu-header.php.
	add_action( 'admin_menu', 'fw_upw_guard_admin_menu_slugs', PHP_INT_MAX );
	add_action( 'network_admin_menu', 'fw_upw_guard_admin_menu_slugs', PHP_INT_MAX );
}
