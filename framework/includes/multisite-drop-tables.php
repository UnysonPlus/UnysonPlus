<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Drop this plugin's per-site tables when a site is deleted from a network.
 *
 * WordPress drops only the tables IT registered ($wpdb->tables). Anything a plugin
 * created with its own dbDelta() is invisible to that, so `wp site delete` — and the
 * network admin's Delete — leave it behind for good. Nothing ever revisits an id that
 * has been freed, so the rows are unreachable and permanent.
 *
 * It was real here: deleting thirteen demo subsites left 28 orphans — wp_2_fw_form_entries,
 * wp_17_fw_seo_links and the rest — across NINE extensions that create tables (bookings,
 * crm, forms, membership, pos-sync, seo, short-links, site-migration, visitor-insights).
 * None of them hooked `wpmu_drop_tables`.
 *
 * WHY THIS IS IN CORE, AND BY CONVENTION. The alternative is nine identical filters, one
 * per extension, each listing its own tables — nine places to keep in step, and a tenth
 * extension would arrive with the same bug. Every table this plugin creates is named
 * `{$wpdb->prefix}fw_…`, so one filter that matches that prefix covers all nine today and
 * anything added later, with nothing to remember. The cost is that the convention is now
 * load-bearing: a per-site table NOT named `fw_*` will still be orphaned.
 *
 * Deliberately NOT dropped:
 *   - tables of OTHER plugins (Elementor's `e_events` was in that orphan set) — theirs to
 *     clean up, and dropping another plugin's data would be worse than leaving it;
 *   - anything whose name does not carry the deleted site's own prefix, so a network-wide
 *     `wp_fw_*` table is never caught by a subsite deletion.
 */
if ( ! function_exists( 'fw_upw_drop_site_tables' ) ) :
	/**
	 * @param array $tables Tables WordPress is about to drop (table name => table name).
	 * @param int   $blog_id The site being deleted.
	 * @return array
	 */
	function fw_upw_drop_site_tables( $tables, $blog_id ) {
		global $wpdb;

		$blog_id = (int) $blog_id;

		// The main site is never deleted, and its prefix has no id segment — matching on it
		// would sweep up the whole network's tables. Refuse rather than rely on the caller.
		if ( $blog_id <= 0 || ( function_exists( 'get_main_site_id' ) && $blog_id === (int) get_main_site_id() ) ) {
			return $tables;
		}

		$prefix = $wpdb->get_blog_prefix( $blog_id );   // e.g. wp_17_
		if ( '' === $prefix || $prefix === $wpdb->base_prefix ) {
			return $tables;                              // no id segment -> not a subsite
		}

		$like = $wpdb->esc_like( $prefix . 'fw_' ) . '%';
		$found = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
		if ( empty( $found ) || ! is_array( $found ) ) {
			return $tables;
		}

		foreach ( $found as $table ) {
			$tables[ $table ] = $table;                  // keyed by name, as WordPress passes them
		}

		return $tables;
	}
endif;

add_filter( 'wpmu_drop_tables', 'fw_upw_drop_site_tables', 10, 2 );
