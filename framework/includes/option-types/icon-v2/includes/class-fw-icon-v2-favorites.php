<?php

if (! defined('FW')) { die('Forbidden'); }

class FW_Icon_V2_Favorites_Manager
{
	private $key = 'fw-icon-v2-favorites';

	public function attach_ajax_actions()
	{
		add_action(
			'wp_ajax_fw_icon_v2_update_favorites',
			array($this, 'set_favorites_action')
		);

		add_action(
			'wp_ajax_fw_icon_v2_get_favorites',
			array($this, 'get_favorites_action')
		);

		add_action(
			'wp_ajax_fw_icon_v2_get_icons',
			array($this, 'get_icon_packs')
		);

		// Pack-aware SVG search (Lucide, Tabler, …). The old lucide-only action
		// name is kept as an alias so any cached picker JS keeps working.
		add_action(
			'wp_ajax_fw_icon_v2_svg_search',
			array($this, 'svg_search_action')
		);
		add_action(
			'wp_ajax_fw_icon_v2_lucide_search',
			array($this, 'svg_search_action')
		);
	}

	public function get_icon_packs() {
		wp_send_json_success(
			fw()->backend->option_type('icon-v2')->packs_loader->get_packs(true)
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

		$pack  = FW_Request::POST( 'pack', 'lucide' );
		$pack  = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $pack ) );
		if ( $pack === '' ) { $pack = 'lucide'; }

		$query = FW_Request::POST( 'q', '' );
		$names = fw_icon_svg_pack_search( $pack, $query, 150 );

		$result = array();
		foreach ( $names as $name ) {
			$result[] = array(
				'name'   => $name,
				'id'     => $pack . '/' . $name,
				'markup' => fw_icon_svg_pack_markup( $pack . '/' . $name ),
			);
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
