<?php

if (! defined('FW')) { die('Forbidden'); }

class FW_Icon_Favorites_Manager
{
	private $key = 'fw-icon-v3-favorites';

	/** Nonce action shared by every picker endpoint below. */
	const NONCE = 'fw_icon_v3_picker';

	/**
	 * Guard for the picker's AJAX endpoints.
	 *
	 * These are admin-only editor endpoints, but wp_ajax_ fires for ANY logged-in
	 * user regardless of role — so without this a Subscriber could read the icon
	 * data, and (via set_favorites_action) overwrite the site-wide favorites
	 * option. The missing nonce also made that write CSRF-able against an admin.
	 *
	 * `edit_posts` matches the capability the icon pack / Lottie / Rive upload
	 * endpoints in this option type already require.
	 */
	private function guard() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fw' ) ), 403 );
		}

		check_ajax_referer( self::NONCE, 'nonce' );
	}

	public function attach_ajax_actions()
	{
		add_action(
			'wp_ajax_fw_icon_v3_update_favorites',
			array($this, 'set_favorites_action')
		);

		add_action(
			'wp_ajax_fw_icon_v3_get_favorites',
			array($this, 'get_favorites_action')
		);

		add_action(
			'wp_ajax_fw_icon_v3_get_icons',
			array($this, 'get_icon_packs')
		);

		// Pack-aware SVG search (Lucide, Tabler, …). The old lucide-only action
		// name is kept as an alias so any cached picker JS keeps working.
		add_action(
			'wp_ajax_fw_icon_v3_svg_search',
			array($this, 'svg_search_action')
		);
		add_action(
			'wp_ajax_fw_icon_v3_lucide_search',
			array($this, 'svg_search_action')
		);

		// Resolve stored SVG-id favorites ('<pack>/<name>') back to their inline
		// markup. Favorites are persisted as a flat list of id strings, so an SVG
		// favorite can't render from the id alone — the Favorites tab fetches the
		// markup for its SVG favorites through this on open.
		add_action(
			'wp_ajax_fw_icon_v3_resolve_svg',
			array($this, 'resolve_svg_action')
		);
	}

	/**
	 * Given a list of SVG-pack ids ('<pack>/<name>'), return a { id => markup }
	 * map so the picker's Favorites tab can render icons it only has ids for.
	 */
	public function resolve_svg_action() {
		$this->guard();

		$ids = json_decode( FW_Request::POST( 'ids', '[]' ), true );
		$out = array();

		if ( is_array( $ids ) && function_exists( 'fw_icon_svg_pack_markup' ) ) {
			foreach ( $ids as $id ) {
				$id = (string) $id;
				if ( strpos( $id, '/' ) === false ) {
					continue;
				}
				$markup = fw_icon_svg_pack_markup( $id );
				if ( $markup ) {
					$out[ $id ] = $markup;
				}
			}
		}

		wp_send_json_success( $out );
	}

	public function get_icon_packs() {
		$this->guard();

		wp_send_json_success(
			fw()->backend->option_type( 'icon' )->packs_loader->get_packs(true)
		);
	}

	/**
	 * Search a bundled inline-SVG pack (Lucide, Tabler, …) for the picker grid.
	 * Reads the POSTed `pack` (default 'lucide') + `q`, and returns up to a
	 * capped number of { name, id:'<pack>/<name>', markup } entries (empty query
	 * → the first slice of the pack).
	 */
	public function svg_search_action() {
		$this->guard();

		if ( ! function_exists( 'fw_icon_svg_pack_search' ) ) {
			wp_send_json_success( array() );
		}

		$pack   = FW_Request::POST( 'pack', 'lucide' );
		$pack   = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $pack ) );
		$query  = FW_Request::POST( 'q', '' );
		$offset = max( 0, (int) FW_Request::POST( 'offset', 0 ) );

		// BROWSE: a single pack with an empty query → paginate the WHOLE pack in
		// batches so the picker can lazy-load (infinite-scroll) every icon.
		// Returns { items, offset, total, has_more } (distinct from the flat
		// array the search paths return).
		if ( $pack !== 'all' && $pack !== '' && trim( $query ) === '' && function_exists( 'fw_icon_svg_pack_all' ) ) {
			$batch = 120;
			$all   = fw_icon_svg_pack_all( $pack );
			$slice = array_slice( $all, $offset, $batch );

			$items = array();
			foreach ( $slice as $name ) {
				$items[] = array(
					'name'   => $name,
					'id'     => $pack . '/' . $name,
					'pack'   => $pack,
					'markup' => fw_icon_svg_pack_markup( $pack . '/' . $name ),
				);
			}

			wp_send_json_success( array(
				'items'    => $items,
				'offset'   => $offset,
				'total'    => count( $all ),
				'has_more' => ( $offset + $batch ) < count( $all ),
			) );
		}

		// 'all' → every ENABLED inline-SVG pack (for the merged search); else the
		// single named pack (keyword search within one library).
		$packs = array();
		if ( $pack === 'all' || $pack === '' ) {
			if ( function_exists( 'unysonplus_svg_icon_pack_ids' ) ) {
				foreach ( unysonplus_svg_icon_pack_ids() as $pid ) {
					if ( function_exists( 'unysonplus_icon_pack_enabled' ) && ! unysonplus_icon_pack_enabled( $pid ) ) {
						continue;
					}
					$packs[] = $pid;
				}
			}
			if ( ! $packs ) { $packs = array( 'lucide' ); }
		} else {
			$packs = array( $pack );
		}

		// Cap per pack so a multi-pack search stays bounded.
		$per    = ( count( $packs ) > 1 ) ? 90 : 150;
		$result = array();
		foreach ( $packs as $pid ) {
			foreach ( fw_icon_svg_pack_search( $pid, $query, $per ) as $name ) {
				$result[] = array(
					'name'   => $name,
					'id'     => $pid . '/' . $name,
					'pack'   => $pid,
					'markup' => fw_icon_svg_pack_markup( $pid . '/' . $name ),
				);
			}
		}

		wp_send_json_success( $result );
	}

	public function set_favorites_action()
	{
		$this->guard();

		$favorites = json_decode(FW_Request::POST( 'favorites' ), true);

		$this->set_favorites($favorites);

		$this->get_favorites_action();
	}

	public function get_favorites_action()
	{
		$this->guard();

		wp_send_json(
			$this->get_favorites()
		);
	}

	public function get_favorites()
	{
		return FW_WP_Option::get(
			$this->key,
			null,
			array()
		);
	}

	public function set_favorites($favorites)
	{
		FW_WP_Option::set(
			$this->key,
			null,
			$this->sanitize_favorites( $favorites )
		);
	}

	/**
	 * Favorites are a flat list of icon ids — either a font icon's class string
	 * or an SVG id ('<pack>/<name>'). The value arrives as decoded JSON from the
	 * request, so coerce it to that shape rather than storing whatever was sent.
	 *
	 * @param mixed $favorites
	 * @return array
	 */
	private function sanitize_favorites( $favorites ) {
		if ( ! is_array( $favorites ) ) {
			return array();
		}

		$clean = array();

		foreach ( $favorites as $id ) {
			if ( ! is_scalar( $id ) ) {
				continue;
			}

			$id = trim( (string) $id );

			// Ids are class strings / '<pack>/<name>' / attachment ids — no markup,
			// no whitespace runs, and bounded in length.
			if ( $id === '' || strlen( $id ) > 200 ) {
				continue;
			}

			if ( ! preg_match( '~^[A-Za-z0-9 _\-/\.:]+$~', $id ) ) {
				continue;
			}

			$clean[] = $id;
		}

		return array_values( array_unique( $clean ) );
	}
}
