<?php

if (! defined('FW')) { die('Forbidden'); }

class FW_Icon_V3_Favorites_Manager
{
	private $key = 'fw-icon-v3-favorites';

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
	}

	public function get_icon_packs() {
		wp_send_json_success(
			fw()->backend->option_type('icon-v3')->packs_loader->get_packs(true)
		);
	}

	/**
	 * Search a bundled inline-SVG pack (Lucide, Tabler, …) for the picker grid.
	 * Reads the POSTed `pack` (default 'lucide') + `q`, and returns up to a
	 * capped number of { name, id:'<pack>/<name>', markup } entries (empty query
	 * → the first slice of the pack).
	 */
	public function svg_search_action() {
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
		$favorites = json_decode(FW_Request::POST( 'favorites' ), true);

		$this->set_favorites($favorites);

		$this->get_favorites_action();
	}

	public function get_favorites_action()
	{
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
			$favorites
		);
	}
}
