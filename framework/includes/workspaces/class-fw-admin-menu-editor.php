<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * The admin menu editor: per role, the order, names, icons and visibility of
 * the WordPress menu (Unyson+ → Admin Menu).
 *
 * It completes workspaces. A workspace decides what a role opens to; this
 * decides what the role's menu looks like around it. Everything is applied on
 * the server, on the menu WordPress is about to print, so it works with or
 * without the Admin Skin; when the skin is on, the editor also shows its
 * groups (Workspace, Shop, Tools…) and an item can be dragged between them.
 *
 * Layout per role (option `fw_admin_menu`):
 *
 *     order        item keys, top to bottom (separators included)
 *     hidden       item keys hidden for the role
 *     labels       key => name
 *     icons        key => dashicons-* class
 *     sub_hidden   key => [ submenu slugs ]
 *     sub_order    key => [ submenu slugs, top to bottom ]
 *     map          key => skin group
 *     group_labels skin group => name
 *     slugs        key => menu slug (the hidden-screen guard runs before the menu exists)
 *     lifted       [ { parent, slug } ] sub-items moved out to the top level
 *
 * An item's key is its <li> id, the way WordPress prints it (`menu-posts`,
 * `toplevel_page_fw-crm`), so the same key works on the server and for the
 * skin's grouping in the browser. Items a layout has never seen — a plugin
 * installed since — go to the end of the menu, in their own order, and are
 * shown, except in a workspace role, whose menu is the workspace and only
 * what was chosen.
 *
 * Hiding is not access control. A hidden top-level screen opened by its
 * address is redirected, but a subpage is still reachable by anyone holding
 * its capability.
 *
 * @since 3.0.55
 */
class FW_Admin_Menu_Editor {

	const OPTION = 'fw_admin_menu';

	/** The Admin Skin's earlier, skin-only layout, imported once. */
	const SKIN_OPTION = 'fw_admin_skin_menu_layout';

	/** @var array|null The menu as registered, before any layout touched it. */
	protected static $snapshot = null;

	/** @var string The <li> id of a lifted item whose screen this request is, if any. */
	protected static $current_lift = '';

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, '_guard_hidden' ) );
		add_action( 'admin_post_fw_admin_menu_save', array( __CLASS__, '_action_save' ) );
		add_action( 'admin_post_fw_admin_menu_copy', array( __CLASS__, '_action_copy' ) );
		add_filter( 'fw_ext_admin_skin_menu_groups', array( __CLASS__, '_filter_skin_groups' ), 20 );
		add_action( 'admin_head', array( __CLASS__, '_action_mark_current' ) );
		add_action( 'admin_init', array( __CLASS__, 'migrate_own_menu_items' ), 5 );
	}

	/**
	 * Workspaces once had a site-wide "show as its own menu item" switch. Moving a
	 * sub-item to the top level does the same thing per role, so each switch that
	 * was on becomes that move for every role that sees the Unyson+ menu (the
	 * others already get the app as a top-level menu of its own).
	 */
	public static function migrate_own_menu_items() {
		$s = get_option( FW_Workspaces::OPTION, array() );

		if ( ! is_array( $s ) || empty( $s['menu'] ) ) {
			return;
		}

		foreach ( array_keys( wp_roles()->get_names() ) as $role ) {
			$obj = get_role( $role );

			if ( ! $obj || ! $obj->has_cap( 'manage_options' ) ) {
				continue;
			}

			$l = self::get( $role );
			$l = $l ? $l : self::normalise( array() );

			foreach ( array_keys( (array) $s['menu'] ) as $id ) {
				$ws = FW_Workspaces::get( $id );

				if ( $ws && ! in_array( $ws['page'], wp_list_pluck( $l['lifted'], 'slug' ), true ) ) {
					$l['lifted'][] = array( 'parent' => FW_Workspaces::PARENT_SLUG, 'slug' => $ws['page'] );
				}
			}

			self::save( $role, $l );
		}

		unset( $s['menu'] );
		update_option( FW_Workspaces::OPTION, $s, false );
	}

	/**
	 * Give every role that runs the site (manage_options) a sub-item as a menu item
	 * of its own, unless it already has it.
	 */
	public static function lift_for_admins( $parent, $slug ) {
		foreach ( array_keys( wp_roles()->get_names() ) as $role ) {
			$obj = get_role( $role );

			if ( ! $obj || ! $obj->has_cap( 'manage_options' ) ) {
				continue;
			}

			$l = self::get( $role );
			$l = $l ? $l : self::normalise( array() );

			if ( ! in_array( $slug, wp_list_pluck( $l['lifted'], 'slug' ), true ) ) {
				$l['lifted'][] = array( 'parent' => $parent, 'slug' => $slug );
				self::save( $role, $l );
			}
		}
	}

	/** @return array|null The workspace whose app page this is, so a lifted app keeps its own icon. */
	protected static function workspace_for_page( $slug ) {
		foreach ( FW_Workspaces::all() as $ws ) {
			if ( $ws['page'] === $slug ) {
				return $ws;
			}
		}

		return null;
	}

	/**
	 * Where an item a layout has not placed yet goes. Most go to the end; a lifted
	 * item that was never placed (the switches turned into lifts) goes just after
	 * Dashboard, where the app's own menu item used to be.
	 *
	 * @param array $rows [ { key, w, lift } ] in menu order, w = saved position or null.
	 *
	 * @return array The same rows with every w set.
	 */
	protected static function place_unsaved( array $rows ) {
		$known = 0;
		foreach ( $rows as $r ) {
			if ( null !== $r['w'] ) {
				$known = max( $known, $r['w'] + 1 );
			}
		}

		$dash = null;
		foreach ( $rows as $i => $r ) {
			if ( null === $r['w'] && empty( $r['lift'] ) ) {
				$rows[ $i ]['w'] = $known + $i;
			}
			if ( 'menu-dashboard' === $r['key'] ) {
				$dash = $i;
			}
		}

		foreach ( $rows as $i => $r ) {
			if ( null === $r['w'] ) {
				$rows[ $i ]['w'] = ( null !== $dash ? $rows[ $dash ]['w'] : -1 ) + 0.5 + $i / 100000;
			}
		}

		return $rows;
	}

	/* ---------------------------------------------------------------------- *
	 * Storage
	 * ---------------------------------------------------------------------- */

	/** @return array role => layout */
	public static function all() {
		$all = get_option( self::OPTION, null );

		if ( null === $all ) {
			$all = self::import_skin_layouts();
		}

		return is_array( $all ) ? $all : array();
	}

	/** @return array|null A role's layout, or null when it has none. */
	public static function get( $role ) {
		$all = self::all();

		return isset( $all[ $role ] ) ? self::normalise( $all[ $role ] ) : null;
	}

	protected static function normalise( $l ) {
		$l   = is_array( $l ) ? $l : array();
		$out = array();

		foreach ( array( 'order', 'hidden' ) as $k ) {
			$out[ $k ] = isset( $l[ $k ] ) && is_array( $l[ $k ] ) ? array_values( array_map( 'strval', $l[ $k ] ) ) : array();
		}

		foreach ( array( 'labels', 'icons', 'sub_hidden', 'sub_order', 'map', 'group_labels', 'slugs' ) as $k ) {
			$out[ $k ] = isset( $l[ $k ] ) && is_array( $l[ $k ] ) ? $l[ $k ] : array();
		}

		$out['lifted'] = array();
		foreach ( isset( $l['lifted'] ) && is_array( $l['lifted'] ) ? $l['lifted'] : array() as $lift ) {
			if ( is_array( $lift ) && ! empty( $lift['parent'] ) && ! empty( $lift['slug'] ) ) {
				$out['lifted'][] = array( 'parent' => (string) $lift['parent'], 'slug' => (string) $lift['slug'] );
			}
		}

		return $out;
	}

	/**
	 * Clean and store one role's layout.
	 *
	 * @param string $role
	 * @param array  $layout Same shape as get(), as posted by the editor.
	 *
	 * @return array|false What was saved, or false for an unknown role.
	 */
	public static function save( $role, array $layout ) {
		$role = sanitize_key( $role );

		if ( ! isset( wp_roles()->get_names()[ $role ] ) ) {
			return false;
		}

		$key  = function ( $k ) {
			return substr( preg_replace( '/[^A-Za-z0-9_:.\-]/', '', (string) $k ), 0, 120 );
		};
		$l    = self::normalise( $layout );
		$out  = self::normalise( array() );

		foreach ( array( 'order', 'hidden' ) as $list ) {
			foreach ( $l[ $list ] as $k ) {
				$k = $key( $k );
				if ( '' !== $k ) {
					$out[ $list ][] = $k;
				}
			}
			$out[ $list ] = array_values( array_unique( $out[ $list ] ) );
		}

		foreach ( $l['labels'] as $k => $v ) {
			$v = trim( sanitize_text_field( (string) $v ) );
			if ( '' !== $key( $k ) && '' !== $v ) {
				$out['labels'][ $key( $k ) ] = substr( $v, 0, 60 );
			}
		}

		foreach ( $l['icons'] as $k => $v ) {
			if ( '' !== $key( $k ) && preg_match( '/^dashicons-[a-z0-9-]+$/', (string) $v ) ) {
				$out['icons'][ $key( $k ) ] = (string) $v;
			}
		}

		foreach ( $l['sub_hidden'] as $k => $subs ) {
			$subs = array_values( array_filter( array_map( function ( $s ) {
				return substr( trim( wp_strip_all_tags( (string) $s ) ), 0, 200 );
			}, (array) $subs ) ) );
			// The Admin Menu editor is the way back to this screen - never let it be saved
			// as hidden, whatever the posted layout claims (the disabled checkbox is only UI).
			$subs = array_values( array_diff( $subs, array( FW_Workspaces::PAGE_SLUG ) ) );
			if ( '' !== $key( $k ) && $subs ) {
				$out['sub_hidden'][ $key( $k ) ] = $subs;
			}
		}

		foreach ( $l['sub_order'] as $k => $subs ) {
			$subs = array_values( array_unique( array_filter( array_map( function ( $s ) {
				return substr( trim( wp_strip_all_tags( (string) $s ) ), 0, 200 );
			}, (array) $subs ) ) ) );
			if ( '' !== $key( $k ) && $subs ) {
				$out['sub_order'][ $key( $k ) ] = $subs;
			}
		}

		$groups = self::skin_groups();
		foreach ( $l['map'] as $k => $g ) {
			if ( '' !== $key( $k ) && isset( $groups['groups'][ $g ] ) ) {
				$out['map'][ $key( $k ) ] = $g;
			}
		}
		foreach ( $l['group_labels'] as $g => $v ) {
			$v = trim( sanitize_text_field( (string) $v ) );
			if ( isset( $groups['groups'][ $g ] ) && '' !== $v && $v !== $groups['groups'][ $g ] ) {
				$out['group_labels'][ $g ] = substr( $v, 0, 40 );
			}
		}

		foreach ( $l['slugs'] as $k => $s ) {
			$s = substr( trim( wp_strip_all_tags( (string) $s ) ), 0, 200 );
			if ( '' !== $key( $k ) && '' !== $s ) {
				$out['slugs'][ $key( $k ) ] = $s;
			}
		}

		$seen = array();
		foreach ( $l['lifted'] as $lift ) {
			$parent = substr( trim( wp_strip_all_tags( $lift['parent'] ) ), 0, 200 );
			$slug   = substr( trim( wp_strip_all_tags( $lift['slug'] ) ), 0, 200 );
			$k      = self::lift_key( $slug );
			if ( '' === $parent || '' === $slug || isset( $seen[ $k ] ) ) {
				continue;
			}
			$seen[ $k ]      = true;
			$out['lifted'][] = array( 'parent' => $parent, 'slug' => $slug );
			// A lifted item is a top-level item now, not one of its old parent's sub-items.
			foreach ( $out['sub_hidden'] as $pk => $subs ) {
				$out['sub_hidden'][ $pk ] = array_values( array_diff( $subs, array( $slug ) ) );
				if ( ! $out['sub_hidden'][ $pk ] ) {
					unset( $out['sub_hidden'][ $pk ] );
				}
			}
		}

		$all          = self::all();
		$all[ $role ] = $out;
		update_option( self::OPTION, $all, false );

		return $out;
	}

	/** Remove a role's layout: it gets the WordPress menu again. */
	public static function reset( $role ) {
		$all = self::all();
		unset( $all[ sanitize_key( $role ) ] );
		update_option( self::OPTION, $all, false );
	}

	/**
	 * Copy one role's layout to another.
	 *
	 * @return bool
	 */
	public static function copy( $from, $to ) {
		$layout = self::get( sanitize_key( $from ) );

		if ( null === $layout ) {
			self::reset( $to );
			return true;
		}

		return false !== self::save( $to, $layout );
	}

	/**
	 * The Admin Skin kept its own per-role layout (map, order as key => index,
	 * hidden, group labels, slugs) before this editor existed. Carry it over
	 * once, so nobody's menu changes when the editor moves into core.
	 *
	 * @return array
	 */
	protected static function import_skin_layouts() {
		$old = get_option( self::SKIN_OPTION, array() );
		$all = array();

		foreach ( is_array( $old ) ? $old : array() as $role => $l ) {
			if ( ! is_array( $l ) ) {
				continue;
			}

			$order = isset( $l['order'] ) && is_array( $l['order'] ) ? $l['order'] : array();
			asort( $order );

			$all[ $role ] = self::normalise( array(
				'order'        => array_keys( $order ),
				'hidden'       => $l['hidden'] ?? array(),
				'map'          => $l['map'] ?? array(),
				'group_labels' => $l['labels'] ?? array(),
				'slugs'        => $l['slugs'] ?? array(),
			) );
		}

		update_option( self::OPTION, $all, false );

		return $all;
	}

	/* ---------------------------------------------------------------------- *
	 * Whose layout
	 * ---------------------------------------------------------------------- */

	/**
	 * The role whose layout applies to a user: the first of their roles that
	 * has one. Merging two would produce an order nobody chose.
	 *
	 * @return string Role, or ''.
	 */
	public static function role_for_user( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		$all  = self::all();

		foreach ( (array) $user->roles as $role ) {
			if ( isset( $all[ $role ] ) ) {
				return $role;
			}
		}

		return '';
	}

	/** @return bool Whether the current user is looking at the plain WordPress menu by choice. */
	protected static function switched_off() {
		$uid = get_current_user_id();

		return FW_Workspaces::can_switch( $uid ) && (bool) get_user_meta( $uid, FW_Workspaces::OFF_META, true );
	}

	/* ---------------------------------------------------------------------- *
	 * Reading and shaping the live menu
	 * ---------------------------------------------------------------------- */

	/** The key an item is known by: its <li> id, as WordPress prints it. */
	public static function item_key( $item ) {
		if ( ! empty( $item[5] ) ) {
			return (string) $item[5];
		}

		return 'toplevel_page_' . sanitize_title_with_dashes( remove_accents( (string) ( $item[2] ?? '' ) ) );
	}

	/** The key (and <li> id) of a sub-item lifted to the top level. */
	public static function lift_key( $slug ) {
		return 'fw-lift-' . sanitize_title( str_replace( array( '?', '=', '&', '.' ), '-', (string) $slug ) );
	}

	/**
	 * The address of a sub-item, the way WordPress links it: a core file as is,
	 * a plugin page under a core file's menu on that file, otherwise admin.php.
	 */
	public static function sub_url( $parent, $slug ) {
		if ( false !== strpos( $slug, '.php' ) ) {
			return $slug;
		}

		if ( false !== strpos( $parent, '.php' ) && 0 !== strpos( $parent, 'admin.php' ) ) {
			return add_query_arg( 'page', $slug, $parent );
		}

		return 'admin.php?page=' . $slug;
	}

	/**
	 * Lift one sub-item to the top level for this request.
	 *
	 * The page stays registered under its parent (its screen id, hooks and the
	 * access check WordPress makes all depend on that), so the sub-item is only
	 * hidden there (`fw-ws-moved`) and a top-level item links to the same address.
	 *
	 * @param array $lift { parent, slug }
	 * @param int   $n    Position seed, so lifted items get distinct keys.
	 */
	protected static function lift( array $lift, $n ) {
		global $menu, $submenu, $pagenow;

		foreach ( (array) ( $submenu[ $lift['parent'] ] ?? array() ) as $i => $sub ) {
			if ( (string) ( $sub[2] ?? '' ) !== $lift['slug'] ) {
				continue;
			}

			if ( ! current_user_can( $sub[1] ) ) {
				return;
			}

			$sub[3] = $sub[3] ?? $sub[0];
			$sub[4] = trim( ( $sub[4] ?? '' ) . ' fw-ws-moved' );
			$submenu[ $lift['parent'] ][ $i ] = $sub;

			$key = self::lift_key( $lift['slug'] );
			$url = self::sub_url( $lift['parent'], $lift['slug'] );

			$ws = self::workspace_for_page( $lift['slug'] );

			$menu[ 9000 + $n ] = array(
				$sub[0],
				$sub[1],
				$url,
				self::plain_title( $sub[0] ),
				'menu-top fw-lifted' . ( $ws ? ' fw-ws-menu' : '' ),
				$key,
				$ws ? $ws['icon'] : 'dashicons-admin-generic',
			);

			if ( self::request_matches( $url, $pagenow ) ) {
				self::$current_lift = $key;
			}

			return;
		}
	}

	/**
	 * @internal Mark a lifted item current on its own screen. WordPress works the
	 * current menu out from where the page is registered (the old parent), so it is
	 * marked in the page.
	 */
	public static function _action_mark_current() {
		if ( '' === self::$current_lift ) {
			return;
		}
		?>
		<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var item = document.getElementById( <?php echo wp_json_encode( self::$current_lift ); ?> );
			if ( ! item ) { return; }
			document.querySelectorAll( '#adminmenu > li.wp-has-current-submenu, #adminmenu > li.current' ).forEach( function ( li ) {
				li.classList.remove( 'wp-has-current-submenu', 'wp-menu-open', 'current' );
				li.classList.add( 'wp-not-current-submenu' );
				var a = li.querySelector( 'a' );
				if ( a ) { a.classList.remove( 'wp-has-current-submenu', 'wp-menu-open', 'current' ); a.classList.add( 'wp-not-current-submenu' ); a.removeAttribute( 'aria-current' ); }
			} );
			item.classList.remove( 'wp-not-current-submenu' );
			item.classList.add( 'current' );
			var link = item.querySelector( 'a' );
			link.classList.remove( 'wp-not-current-submenu' );
			link.classList.add( 'current' );
			link.setAttribute( 'aria-current', 'page' );
		} );
		</script>
		<?php
	}

	/**
	 * Sub-items in a saved order. The parent's own first entry stays first: the
	 * parent links to its first sub-item, so moving it would change where the
	 * parent goes. Sub-items the order has never seen keep their place at the end.
	 *
	 * @param array  $subs   Sub-items (WordPress rows, or editor rows).
	 * @param array  $order  Slugs, top to bottom.
	 * @param string $parent The parent's slug.
	 * @param mixed  $field  Where a row keeps its slug: 2 for WordPress rows, 'slug' for editor rows.
	 */
	protected static function sort_subs( array $subs, array $order, $parent, $field ) {
		$pos  = array_flip( $order );
		$rows = array();

		foreach ( array_values( $subs ) as $i => $sub ) {
			$slug   = (string) ( $sub[ $field ] ?? '' );
			$rows[] = array(
				'w'   => $slug === $parent ? -1 : ( isset( $pos[ $slug ] ) ? $pos[ $slug ] : 100000 + $i ),
				'sub' => $sub,
			);
		}

		usort( $rows, function ( $a, $b ) {
			return $a['w'] <=> $b['w'];
		} );

		return wp_list_pluck( $rows, 'sub' );
	}

	protected static function is_separator( $item ) {
		return false !== strpos( (string) ( $item[4] ?? '' ), 'wp-menu-separator' );
	}

	/** A menu title without its count bubble ("Comments 3"). */
	protected static function plain_title( $title ) {
		$title = preg_replace( '#<span[^>]*class="[^"]*(?:update-plugins|awaiting-mod|update-count|plugin-count|menu-counter)[^"]*".*$#s', '', (string) $title );

		return trim( wp_strip_all_tags( $title ) );
	}

	/**
	 * Remember the menu as registered, for the editor. Called by FW_Workspaces
	 * after it has promoted workspaces and before any layout is applied.
	 */
	public static function capture() {
		global $menu, $submenu;

		$rows = (array) $menu;
		ksort( $rows, SORT_NUMERIC );
		$out = array();

		foreach ( $rows as $item ) {
			if ( ! isset( $item[2] ) ) {
				continue;
			}

			$sep  = self::is_separator( $item );
			$subs = array();

			foreach ( $sep ? array() : (array) ( $submenu[ $item[2] ] ?? array() ) as $sub ) {
				$name = self::plain_title( $sub[0] ?? '' );
				if ( '' !== $name && isset( $sub[2] ) ) {
					$subs[] = array( 'slug' => (string) $sub[2], 'title' => $name );
				}
			}

			$icon = (string) ( $item[6] ?? '' );

			$out[] = array(
				'key'   => $sep ? (string) $item[2] : self::item_key( $item ),
				'slug'  => (string) $item[2],
				'title' => $sep ? '' : self::plain_title( $item[0] ?? '' ),
				'icon'  => 0 === strpos( $icon, 'dashicons-' ) ? $icon : '',
				'class' => (string) ( $item[4] ?? '' ),
				'sep'   => $sep,
				'subs'  => $subs,
			);
		}

		self::$snapshot = $out;
	}

	/** @return array The captured menu (empty outside an admin screen). */
	public static function items() {
		return (array) self::$snapshot;
	}

	/**
	 * Apply the current user's layout to the menu. Called by FW_Workspaces.
	 *
	 * @param string   $workspace The workspace in force for the user, or ''.
	 * @param string[] $protect   Menu slugs that are never hidden (the workspace, Profile).
	 *
	 * @return bool Whether a layout was applied.
	 */
	public static function apply( $workspace, array $protect ) {
		global $menu, $submenu;

		if ( self::switched_off() ) {
			return false;
		}

		$role = self::role_for_user();

		if ( '' === $role || ! is_array( $menu ) ) {
			return false;
		}

		$l = self::get( $role );

		foreach ( $l['lifted'] as $n => $lift ) {
			self::lift( $lift, $n );
		}

		$pos   = array_flip( $l['order'] );
		$rows  = $menu;
		ksort( $rows, SORT_NUMERIC );

		$sorted = array();
		$i      = 0;

		foreach ( $rows as $item ) {
			$slug = (string) ( $item[2] ?? '' );
			$sep  = self::is_separator( $item );
			$key  = $sep ? $slug : self::item_key( $item );
			$safe = in_array( $slug, $protect, true );

			$hide = in_array( $key, $l['hidden'], true )
				// A workspace role's menu is the workspace and what was chosen: new items stay out.
				|| ( $workspace && ! isset( $pos[ $key ] ) );

			if ( $hide && ! $safe ) {
				continue;
			}

			if ( ! $sep ) {
				if ( isset( $l['labels'][ $key ] ) ) {
					$title   = (string) $item[0];
					$bubble  = preg_match( '#<span[^>]*class="[^"]*(?:update-plugins|awaiting-mod|update-count|plugin-count|menu-counter)[^"]*".*$#s', $title, $m ) ? ' ' . $m[0] : '';
					$item[0] = esc_html( $l['labels'][ $key ] ) . $bubble;
					$item[3] = $l['labels'][ $key ];
				}

				if ( isset( $l['icons'][ $key ] ) ) {
					$item[6] = $l['icons'][ $key ];
				}

				if ( isset( $l['map'][ $key ] ) ) {
					// The skin groups items by class; this one says where it was put.
					$item[4] = trim( ( $item[4] ?? '' ) . ' fw-mg-' . $l['map'][ $key ] );
				}

				if ( ! empty( $l['sub_order'][ $key ] ) && isset( $submenu[ $slug ] ) ) {
					$submenu[ $slug ] = self::sort_subs( $submenu[ $slug ], $l['sub_order'][ $key ], $slug, 2 );
				}

				if ( ! empty( $l['sub_hidden'][ $key ] ) && isset( $submenu[ $slug ] ) ) {
					$submenu[ $slug ] = array_values( array_filter( $submenu[ $slug ], function ( $sub ) use ( $l, $key ) {
						$sub_slug = (string) ( $sub[2] ?? '' );
						// The Admin Menu editor is the only way back - never hide it, even if a
						// layout saved before this guard recorded it under sub_hidden.
						if ( FW_Workspaces::PAGE_SLUG === $sub_slug ) {
							return true;
						}
						return ! in_array( $sub_slug, $l['sub_hidden'][ $key ], true );
					} ) );
					if ( ! $submenu[ $slug ] ) {
						unset( $submenu[ $slug ] );
					}
				}
			}

			$sorted[] = array( 'key' => $key, 'w' => isset( $pos[ $key ] ) ? $pos[ $key ] : null, 'lift' => 0 === strpos( $key, 'fw-lift-' ), 'item' => $item );
			$i++;
		}

		$sorted = self::place_unsaved( $sorted );

		usort( $sorted, function ( $a, $b ) {
			return $a['w'] <=> $b['w'];
		} );

		$menu = array();
		foreach ( $sorted as $n => $row ) {
			$menu[ ( $n + 1 ) * 10 ] = $row['item'];
		}

		// WordPress sorts the menu again when a plugin asks for a custom order
		// (`custom_menu_order`); answer that sort with this order, last.
		$slugs = wp_list_pluck( wp_list_pluck( $sorted, 'item' ), 2 );
		add_filter( 'menu_order', function () use ( $slugs ) {
			return $slugs;
		}, PHP_INT_MAX );

		return true;
	}

	/**
	 * A hidden top-level screen opened by its address goes elsewhere. Only the
	 * top-level slug is matched: a subpage is a different screen.
	 *
	 * @internal
	 */
	public static function _guard_hidden() {
		global $pagenow;

		if ( wp_doing_ajax() || self::switched_off() ) {
			return;
		}

		$role = self::role_for_user();
		$l    = $role ? self::get( $role ) : null;

		if ( ! $l || ! $l['hidden'] ) {
			return;
		}

		$ws = FW_Workspaces::active();

		foreach ( $l['hidden'] as $key ) {
			$slug = $l['slugs'][ $key ] ?? '';

			if ( '' === $slug || 'profile.php' === $slug || ( $ws && FW_Workspaces::get( $ws )['page'] === $slug ) || ! self::request_matches( $slug, $pagenow ) ) {
				continue;
			}

			$to = $ws ? FW_Workspaces::url( $ws ) : ( 'index.php' === $slug ? admin_url( 'profile.php' ) : admin_url() );
			wp_safe_redirect( $to );
			exit;
		}
	}

	/**
	 * Whether this request IS the screen a menu slug points at. Slugs come as a
	 * plugin page ("fw-extensions"), a core file ("upload.php"), or a core file
	 * with a query that is part of its identity ("edit.php?post_type=page").
	 */
	protected static function request_matches( $slug, $pagenow ) {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( 0 === strpos( $slug, 'admin.php?page=' ) ) {
			$slug = substr( $slug, strlen( 'admin.php?page=' ) );
		}

		if ( false !== strpos( $slug, '?' ) ) {
			list( $file, $query ) = explode( '?', $slug, 2 );

			if ( $file !== $pagenow ) {
				return false;
			}

			parse_str( $query, $args );
			foreach ( $args as $k => $v ) {
				if ( ! isset( $_GET[ $k ] ) || (string) $v !== (string) wp_unslash( $_GET[ $k ] ) ) {
					return false;
				}
			}

			return true;
		}

		if ( '.php' === substr( $slug, -4 ) ) {
			return $slug === $pagenow && ! isset( $_GET['page'] );
		}

		return isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $slug;
		// phpcs:enable
	}

	/* ---------------------------------------------------------------------- *
	 * Admin Skin groups
	 * ---------------------------------------------------------------------- */

	/**
	 * The skin's group config ({groups, map, cpt_group, fallback}), or an empty
	 * one when the skin is not active. The skin answers this filter.
	 *
	 * @return array
	 */
	public static function skin_groups() {
		$g = apply_filters( 'fw_admin_menu_editor_groups', array() );

		return is_array( $g ) && ! empty( $g['groups'] ) ? $g : array( 'groups' => array() );
	}

	/**
	 * Which skin group an item lands in by default — the same substring test the
	 * skin runs in the browser, against the item's id, classes and link.
	 */
	public static function default_group( array $item, array $groups ) {
		$hay = $item['key'] . ' ' . $item['class'] . ' ' . $item['slug'];

		foreach ( (array) ( $groups['map'] ?? array() ) as $needle => $group ) {
			if ( '' !== (string) $needle && false !== strpos( $hay, (string) $needle ) ) {
				return $group;
			}
		}

		if ( preg_match( '/^menu-posts(-|$)/', $item['key'] ) ) {
			return $groups['cpt_group'] ?? 'workspace';
		}

		return $groups['fallback'] ?? 'plugins';
	}

	/**
	 * @internal Tell the skin about the layout: a moved item carries `fw-mg-<group>`,
	 * renamed groups keep their names, and the order is already the menu's own.
	 */
	public static function _filter_skin_groups( $config ) {
		$role = self::switched_off() ? '' : self::role_for_user();
		$l    = $role ? self::get( $role ) : null;
		$map  = array();

		foreach ( array_keys( (array) ( $config['groups'] ?? array() ) ) as $g ) {
			$map[ 'fw-mg-' . $g ] = $g;
		}

		// First: the skin matches its map in order, so a move must be seen before a default.
		$config['map'] = $map + (array) ( $config['map'] ?? array() );

		if ( $l ) {
			foreach ( $l['group_labels'] as $g => $label ) {
				if ( isset( $config['groups'][ $g ] ) ) {
					$config['groups'][ $g ] = $label;
				}
			}
			// The server already printed the menu in the chosen order; the skin keeps it.
			$config['order'] = null;
		}

		return $config;
	}

	/* ---------------------------------------------------------------------- *
	 * The editor (Unyson+ → Admin Menu)
	 * ---------------------------------------------------------------------- */

	/** @internal */
	public static function _action_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change menus.', 'fw' ), 403 );
		}

		check_admin_referer( 'fw_admin_menu_save' );

		$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';

		if ( ! empty( $_POST['reset'] ) ) {
			self::reset( $role );
		} else {
			// One JSON field, not hundreds of inputs: a host's max_input_vars cannot cut it short.
			$layout = json_decode( wp_unslash( (string) ( $_POST['layout'] ?? '' ) ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- cleaned in save().
			self::save( $role, is_array( $layout ) ? $layout : array() );
		}

		wp_safe_redirect( self::url( $role, array( 'saved' => 1 ) ) );
		exit;
	}

	/** @internal */
	public static function _action_copy() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change menus.', 'fw' ), 403 );
		}

		check_admin_referer( 'fw_admin_menu_copy' );

		$to   = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		$from = isset( $_POST['from'] ) ? sanitize_key( wp_unslash( $_POST['from'] ) ) : '';

		if ( $from && $from !== $to ) {
			self::copy( $from, $to );
		}

		wp_safe_redirect( self::url( $to, array( 'copied' => 1 ) ) );
		exit;
	}

	public static function url( $role = '', array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => FW_Workspaces::PAGE_SLUG, 'role' => $role ), $args ), admin_url( 'admin.php' ) );
	}

	/** Icons offered by the picker: the dashicons a menu item is likely to want. */
	protected static function icon_choices() {
		return array(
			'dashicons-dashboard', 'dashicons-admin-home', 'dashicons-admin-post', 'dashicons-admin-page', 'dashicons-admin-media',
			'dashicons-admin-comments', 'dashicons-admin-appearance', 'dashicons-admin-plugins', 'dashicons-admin-users',
			'dashicons-admin-tools', 'dashicons-admin-settings', 'dashicons-admin-generic', 'dashicons-admin-links',
			'dashicons-admin-site-alt3', 'dashicons-id-alt', 'dashicons-groups', 'dashicons-businessperson', 'dashicons-awards',
			'dashicons-email-alt', 'dashicons-megaphone', 'dashicons-calendar-alt', 'dashicons-clock', 'dashicons-chart-bar',
			'dashicons-chart-line', 'dashicons-chart-pie', 'dashicons-cart', 'dashicons-store', 'dashicons-products',
			'dashicons-money-alt', 'dashicons-tickets-alt', 'dashicons-portfolio', 'dashicons-format-gallery', 'dashicons-format-video',
			'dashicons-book', 'dashicons-welcome-learn-more', 'dashicons-clipboard', 'dashicons-list-view', 'dashicons-forms',
			'dashicons-feedback', 'dashicons-format-chat', 'dashicons-sos', 'dashicons-flag', 'dashicons-star-filled',
			'dashicons-heart', 'dashicons-location', 'dashicons-building', 'dashicons-phone', 'dashicons-paperclip',
			'dashicons-database', 'dashicons-cloud', 'dashicons-shield', 'dashicons-lock', 'dashicons-search',
			'dashicons-filter', 'dashicons-tag', 'dashicons-category', 'dashicons-layout', 'dashicons-art',
			'dashicons-code-standards', 'dashicons-rest-api', 'dashicons-randomize', 'dashicons-update', 'dashicons-lightbulb',
		);
	}

	/**
	 * The editor's rows for a role: the captured menu in the role's saved order,
	 * with each row's state.
	 */
	protected static function rows_for( $role ) {
		$l      = self::get( $role );
		$items  = self::items();
		$groups = self::skin_groups();
		$roles  = FW_Workspaces::settings()['roles'];
		$ws     = $roles[ $role ]['workspace'] ?? '';
		$wsdef  = $ws ? FW_Workspaces::get( $ws ) : null;

		if ( $l && $l['lifted'] ) {
			$lifted_slugs = wp_list_pluck( $l['lifted'], 'slug' );

			foreach ( $l['lifted'] as $lift ) {
				foreach ( $items as $parent ) {
					if ( $parent['slug'] !== $lift['parent'] ) {
						continue;
					}
					foreach ( $parent['subs'] as $sub ) {
						if ( $sub['slug'] === $lift['slug'] ) {
							$app     = self::workspace_for_page( $sub['slug'] );
							$items[] = array(
								'key'   => self::lift_key( $sub['slug'] ),
								'slug'  => self::sub_url( $lift['parent'], $sub['slug'] ),
								'title' => $sub['title'],
								'icon'  => $app ? $app['icon'] : 'dashicons-admin-generic',
								'class' => 'menu-top fw-lifted' . ( $app ? ' fw-ws-menu' : '' ),
								'sep'   => false,
								'subs'  => array(),
								'lift'  => $lift,
							);
						}
					}
				}
			}

			foreach ( $items as $i => $item ) {
				$items[ $i ]['subs'] = array_values( array_filter( $item['subs'], function ( $sub ) use ( $lifted_slugs ) {
					return ! in_array( $sub['slug'], $lifted_slugs, true );
				} ) );
			}
		}

		if ( $l ) {
			$pos  = array_flip( $l['order'] );
			$rows = array();
			foreach ( $items as $item ) {
				if ( ! empty( $l['sub_order'][ $item['key'] ] ) ) {
					$item['subs'] = self::sort_subs( $item['subs'], $l['sub_order'][ $item['key'] ], $item['slug'], 'slug' );
				}
				$rows[] = array( 'key' => $item['key'], 'w' => isset( $pos[ $item['key'] ] ) ? $pos[ $item['key'] ] : null, 'lift' => ! empty( $item['lift'] ), 'item' => $item );
			}
			$rows = self::place_unsaved( $rows );
			usort( $rows, function ( $a, $b ) {
				return $a['w'] <=> $b['w'];
			} );
			$items = wp_list_pluck( $rows, 'item' );
		}

		foreach ( $items as $i => $item ) {
			$key       = $item['key'];
			$protected = 'profile.php' === $item['slug'] || ( $wsdef && in_array( $item['slug'], array( $wsdef['page'], 'admin.php?page=' . $wsdef['page'] ), true ) );

			if ( $l ) {
				$hidden = in_array( $key, $l['hidden'], true ) || ( $ws && ! in_array( $key, $l['order'], true ) );
			} else {
				// No layout yet: a workspace role sees its workspace and Profile.
				$hidden = (bool) $ws;
			}

			$items[ $i ]['hidden']     = $hidden && ! $protected;
			$items[ $i ]['protected']  = $protected;
			$items[ $i ]['label']      = $l['labels'][ $key ] ?? '';
			$items[ $i ]['new_icon']   = $l['icons'][ $key ] ?? '';
			// The Admin Menu editor can never be hidden (it is the way back), so never show
			// it as unchecked even if an older saved layout recorded it under sub_hidden.
			$items[ $i ]['sub_hidden'] = array_values( array_diff( $l['sub_hidden'][ $key ] ?? array(), array( FW_Workspaces::PAGE_SLUG ) ) );
			$items[ $i ]['group']      = $groups['groups'] ? ( $l['map'][ $key ] ?? self::default_group( $item, $groups ) ) : '';
		}

		return $items;
	}

	/** @internal Renders inside the Workspaces screen. */
	public static function render() {
		$names = wp_roles()->get_names();
		// phpcs:disable WordPress.Security.NonceVerification
		$role = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
		// phpcs:enable
		if ( ! isset( $names[ $role ] ) ) {
			$role = isset( $names['administrator'] ) ? 'administrator' : (string) key( $names );
		}

		$layout = self::get( $role );
		$rows   = self::rows_for( $role );
		$groups = self::skin_groups();
		$ws     = FW_Workspaces::settings()['roles'][ $role ]['workspace'] ?? '';
		$wsdef  = $ws ? FW_Workspaces::get( $ws ) : null;
		$all    = self::all();

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'fw-admin-menu-editor', fw_get_framework_asset_uri( '/static/css/admin-menu-editor.css' ), array(), fw()->manifest->get_version() );
		wp_enqueue_script( 'fw-admin-menu-editor', fw_get_framework_asset_uri( '/static/js/admin-menu-editor.js' ), array( 'jquery', 'jquery-ui-sortable', 'wp-a11y' ), fw()->manifest->get_version(), true );
		wp_localize_script( 'fw-admin-menu-editor', 'fwMenuEditor', array(
			'icons' => self::icon_choices(),
			'i18n'  => array(
				'noDrop'    => __( 'A sub-item can only go back to its own menu or out to the top level.', 'fw' ),
				'shown'     => __( 'Shown', 'fw' ),
				'hide'      => __( 'Hide', 'fw' ),
				'show'      => __( 'Show', 'fw' ),
				'icon'      => __( 'Change icon', 'fw' ),
				'default'   => __( 'Default icon', 'fw' ),
				'separator' => __( 'Separator', 'fw' ),
				'empty'     => __( 'Drag items here', 'fw' ),
			),
		) );

		$by_group = array();
		if ( $groups['groups'] ) {
			foreach ( array_keys( $groups['groups'] ) as $g ) {
				$by_group[ $g ] = array();
			}
			foreach ( $rows as $row ) {
				if ( $row['sep'] ) {
					continue; // the skin draws group headings instead of separators
				}
				$by_group[ isset( $by_group[ $row['group'] ] ) ? $row['group'] : (string) key( $by_group ) ][] = $row;
			}
		} else {
			$by_group[''] = $rows;
		}
		?>
		<div class="fw-me" id="fw-me">
			<?php if ( ! empty( $_GET['saved'] ) || ! empty( $_GET['copied'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php echo empty( $_GET['copied'] ) ? esc_html__( 'Menu saved.', 'fw' ) : esc_html__( 'Menu copied.', 'fw' ); // phpcs:ignore WordPress.Security.NonceVerification ?></p></div>
			<?php endif; ?>

			<div class="fw-me-bar">
				<form method="get" class="fw-me-role">
					<input type="hidden" name="page" value="<?php echo esc_attr( FW_Workspaces::PAGE_SLUG ); ?>" />
					<label for="fw-me-role"><?php esc_html_e( 'Menu for', 'fw' ); ?></label>
					<select id="fw-me-role" name="role" onchange="this.form.submit()">
						<?php foreach ( $names as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $role ); ?>>
								<?php echo esc_html( translate_user_role( $name ) ); ?><?php echo isset( $all[ $slug ] ) ? ' ' . esc_html__( '(customised)', 'fw' ) : ''; ?>
							</option>
						<?php endforeach; ?>
					</select>
					<noscript><button class="button"><?php esc_html_e( 'Open', 'fw' ); ?></button></noscript>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fw-me-copy">
					<input type="hidden" name="action" value="fw_admin_menu_copy" />
					<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
					<?php wp_nonce_field( 'fw_admin_menu_copy' ); ?>
					<label for="fw-me-from"><?php esc_html_e( 'Copy from', 'fw' ); ?></label>
					<select id="fw-me-from" name="from">
						<?php foreach ( $names as $slug => $name ) : ?>
							<?php if ( $slug !== $role ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( translate_user_role( $name ) ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Copy', 'fw' ); ?></button>
				</form>
			</div>

			<?php if ( $wsdef ) : ?>
				<p class="fw-me-note">
					<?php
					/* translators: %s: a workspace name, e.g. "CRM". */
					printf(
						/* translators: 1: a workspace name, e.g. "CRM"; 2: link to the Roles tab. */
						esc_html__( 'This role opens to %1$s (%2$s). It always keeps that and Profile; anything added to WordPress later stays hidden for it until you show it here.', 'fw' ),
						'<strong>' . esc_html( $wsdef['label'] ) . '</strong>',
						'<a href="' . esc_url( add_query_arg( array( 'page' => FW_Workspaces::PAGE_SLUG, 'tab' => 'roles' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Roles', 'fw' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fw-me-form" id="fw-me-form">
				<input type="hidden" name="action" value="fw_admin_menu_save" />
				<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
				<input type="hidden" name="layout" id="fw-me-layout" value="" />
				<?php wp_nonce_field( 'fw_admin_menu_save' ); ?>

				<div class="fw-me-cols">
					<div class="fw-me-edit">
						<p class="description" id="fw-me-help"><?php esc_html_e( 'Drag items to reorder them. Click an item to open its sub-items: drag them into a new order, or drag one out into the menu to give it a place of its own, and back in to return it. Rename items, change their icons and hide what this role does not need.', 'fw' ); ?>
							<span class="screen-reader-text"><?php esc_html_e( 'With the keyboard: Alt and the up or down arrow moves an item or sub-item; Alt and the left arrow moves a sub-item out into the menu, Alt and the right arrow moves it back.', 'fw' ); ?></span>
						</p>

						<?php foreach ( $by_group as $group => $list ) : ?>
							<section class="fw-me-group" data-group="<?php echo esc_attr( $group ); ?>">
								<?php if ( '' !== $group ) : ?>
									<input class="fw-me-group-name" type="text" data-group="<?php echo esc_attr( $group ); ?>"
										value="<?php echo esc_attr( $layout['group_labels'][ $group ] ?? '' ); ?>"
										placeholder="<?php echo esc_attr( $groups['groups'][ $group ] ); ?>"
										aria-label="<?php esc_attr_e( 'Group name', 'fw' ); ?>" />
								<?php endif; ?>
								<ul class="fw-me-list" data-empty="<?php esc_attr_e( 'Drag items here', 'fw' ); ?>">
									<?php foreach ( $list as $row ) : ?>
										<?php self::render_row( $row ); ?>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endforeach; ?>
					</div>

					<aside class="fw-me-preview" aria-label="<?php esc_attr_e( 'Preview', 'fw' ); ?>">
						<p class="fw-me-preview-title"><?php esc_html_e( 'Preview', 'fw' ); ?></p>
						<ul class="fw-me-preview-menu" id="fw-me-preview" aria-hidden="true"></ul>
					</aside>
				</div>

				<p class="fw-me-note">
					<strong><?php esc_html_e( 'Hiding is not a permission.', 'fw' ); ?></strong>
					<?php esc_html_e( 'A hidden top-level screen opened by its address is sent back, but a sub-page is still reachable by anyone whose role allows it. Roles decide what people may do; this decides what they see.', 'fw' ); ?>
				</p>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save menu', 'fw' ); ?></button>
					<?php if ( $layout ) : ?>
						<button type="submit" name="reset" value="1" class="button" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Give this role the WordPress menu again?', 'fw' ) ) ); ?>)"><?php esc_html_e( 'Reset to the WordPress menu', 'fw' ); ?></button>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}

	protected static function render_row( array $row ) {
		$icon = '' !== $row['new_icon'] ? $row['new_icon'] : ( '' !== $row['icon'] ? $row['icon'] : 'dashicons-admin-generic' );
		?>
		<li class="fw-me-row<?php echo $row['sep'] ? ' is-sep' : ''; ?><?php echo $row['hidden'] ? ' is-hidden' : ''; ?>"
			data-key="<?php echo esc_attr( $row['key'] ); ?>" data-slug="<?php echo esc_attr( $row['slug'] ); ?>"
			data-icon="<?php echo esc_attr( $row['icon'] ); ?>" data-new-icon="<?php echo esc_attr( $row['new_icon'] ); ?>"
			data-title="<?php echo esc_attr( $row['title'] ); ?>"
			<?php if ( ! empty( $row['lift'] ) ) : ?>
				data-lift-parent="<?php echo esc_attr( $row['lift']['parent'] ); ?>" data-lift-slug="<?php echo esc_attr( $row['lift']['slug'] ); ?>"
			<?php endif; ?>>
			<div class="fw-me-line">
				<span class="fw-me-handle dashicons dashicons-menu" aria-hidden="true"></span>
				<?php if ( $row['sep'] ) : ?>
					<span class="fw-me-sep-label"><?php esc_html_e( 'Separator', 'fw' ); ?></span>
				<?php else : ?>
					<button type="button" class="fw-me-icon" aria-label="<?php esc_attr_e( 'Change icon', 'fw' ); ?>" aria-expanded="false">
						<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
					</button>
					<input type="text" class="fw-me-label" value="<?php echo esc_attr( $row['label'] ); ?>" placeholder="<?php echo esc_attr( $row['title'] ); ?>"
						aria-label="<?php /* translators: %s: menu item name */ echo esc_attr( sprintf( __( 'Name for %s', 'fw' ), $row['title'] ) ); ?>" />
				<?php endif; ?>

				<?php if ( $row['protected'] ) : ?>
					<span class="fw-me-lock" title="<?php esc_attr_e( 'Always shown for this role', 'fw' ); ?>"><span class="dashicons dashicons-lock" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Always shown for this role', 'fw' ); ?></span></span>
				<?php else : ?>
					<label class="fw-me-toggle">
						<input type="checkbox" class="fw-me-shown" <?php checked( ! $row['hidden'] ); ?> />
						<span><?php esc_html_e( 'Shown', 'fw' ); ?></span>
					</label>
				<?php endif; ?>

				<?php if ( $row['subs'] ) : ?>
					<button type="button" class="fw-me-more" aria-expanded="false"
						aria-label="<?php /* translators: %s: menu item name */ echo esc_attr( sprintf( __( 'Sub-items of %s', 'fw' ), $row['title'] ) ); ?>">
						<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					</button>
				<?php else : ?>
					<span class="fw-me-more-space" aria-hidden="true"></span>
				<?php endif; ?>
			</div>

			<?php if ( $row['subs'] ) : ?>
				<ul class="fw-me-subs" hidden>
					<?php foreach ( $row['subs'] as $sub ) : ?>
						<?php
						$pinned = $row['slug'] === $sub['slug'];
						// The Admin Menu editor is the only way back to this screen, so it can
						// never be hidden: its checkbox is forced on and locked (see the save /
						// runtime guards that enforce the same thing server-side).
						$locked = FW_Workspaces::PAGE_SLUG === $sub['slug'];
						?>
						<li class="fw-me-sub-row<?php echo $pinned ? ' is-pinned' : ''; ?><?php echo $locked ? ' is-locked' : ''; ?>" data-slug="<?php echo esc_attr( $sub['slug'] ); ?>" data-title="<?php echo esc_attr( $sub['title'] ); ?>"
							data-key="<?php echo esc_attr( self::lift_key( $sub['slug'] ) ); ?>" data-url="<?php echo esc_attr( self::sub_url( $row['slug'], $sub['slug'] ) ); ?>"
							data-icon="<?php $app = self::workspace_for_page( $sub['slug'] ); echo esc_attr( $app ? $app['icon'] : 'dashicons-admin-generic' ); ?>">
							<?php if ( $locked ) : ?>
								<span class="fw-me-sublock dashicons dashicons-lock" aria-hidden="true" title="<?php esc_attr_e( 'Always shown - the way back to this screen', 'fw' ); ?>"></span>
							<?php elseif ( ! $pinned ) : ?>
								<span class="fw-me-subhandle dashicons dashicons-menu" aria-hidden="true"></span>
							<?php endif; ?>
							<label>
								<input type="checkbox" class="fw-me-sub" value="<?php echo esc_attr( $sub['slug'] ); ?>" <?php if ( $locked ) : ?>checked disabled<?php else : ?><?php checked( ! in_array( $sub['slug'], $row['sub_hidden'], true ) ); ?><?php endif; ?> />
								<?php echo esc_html( $sub['title'] ); ?>
								<?php if ( $locked ) : ?><span class="screen-reader-text"><?php esc_html_e( '(always shown)', 'fw' ); ?></span><?php endif; ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
	}
}
