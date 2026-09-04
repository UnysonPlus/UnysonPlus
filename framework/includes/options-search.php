<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Theme Settings — option search.
 *
 * Builds a small index of every option on the Theme Settings screen (id, label,
 * description, and the tab path it lives under) and hands it to a JS widget that
 * filters as you type and jumps to the option.
 *
 * WHY AN INDEX AND NOT A DOM SEARCH
 * ---------------------------------
 * With `lazy_tabs` on (the default) each tab's rendered HTML is parked in a
 * `data-fw-tab-html` attribute and only injected when the tab is opened, so a
 * plain DOM query would only ever see the tab you are already looking at.
 * The pre-rendered HTML *is* all in the page, but on this install it measures
 * ~30 MB — far too much to parse on every keystroke. Walking the options ARRAY in
 * PHP costs nothing and yields a few KB of JSON.
 *
 * Nothing here changes how options are defined, rendered or saved: it is a read
 * of the same array the form already builds.
 */

if ( ! function_exists( 'fw_upw_settings_search_index' ) ) :
	/**
	 * Flatten the Theme Settings options into a searchable index.
	 *
	 * @return array List of array( id, label, desc, path, tab ) where `tab` is the
	 *               id of the TOP-LEVEL tab to activate and `path` is the
	 *               human-readable trail ("General → Layout").
	 */
	function fw_upw_settings_search_index() {
		if ( ! function_exists( 'fw' ) || ! fw()->theme ) {
			return array();
		}

		$options = fw()->theme->get_settings_options();
		if ( empty( $options ) || ! is_array( $options ) ) {
			return array();
		}

		$index = array();
		fw_upw_settings_search_walk( $options, array(), null, $index );

		return $index;
	}
endif;

if ( ! function_exists( 'fw_upw_settings_search_walk' ) ) :
	/**
	 * Recursive half of the index builder.
	 *
	 * A node is a CONTAINER when it carries its own `options` array — that covers
	 * tab / box / group and any custom container type, without hard-coding the
	 * list (the framework deprecated its own hard-coded version for that reason).
	 * Everything else with a `type` is a leaf option.
	 *
	 * @param array       $nodes    Options array at this level.
	 * @param array       $trail    Titles of the containers walked so far.
	 * @param string|null $tab_id   Id of the nearest enclosing TOP-LEVEL tab.
	 * @param array       $index    Accumulator, by reference.
	 * @param int         $depth    Recursion guard.
	 */
	function fw_upw_settings_search_walk( $nodes, $trail, $tab_id, &$index, $depth = 0, $id_chain = array() ) {
		if ( $depth > 12 || ! is_array( $nodes ) ) {
			return;
		}

		foreach ( $nodes as $id => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			/**
			 * Unyson keeps option arrays ORDERED by nesting each definition inside a
			 * single-key map, so a level often looks like
			 *   array( 0 => array( 'general_settings_container' => array(...) ), 1 => ... )
			 * rather than array( 'general_settings_container' => array(...) ).
			 *
			 * A node with neither `type` nor `options` is one of those wrappers: step
			 * through it so the inner key is read as the id. Missing this returned an
			 * empty index (13 top-level nodes, 0 options found).
			 */
			if ( ! isset( $node['type'] ) && ! isset( $node['options'] ) ) {
				fw_upw_settings_search_walk( $node, $trail, $tab_id, $index, $depth + 1, $id_chain );
				continue;
			}

			if ( isset( $node['options'] ) && is_array( $node['options'] ) ) {
				$title = '';
				foreach ( array( 'title', 'label' ) as $k ) {
					if ( ! empty( $node[ $k ] ) && is_string( $node[ $k ] ) ) {
						$title = $node[ $k ];
						break;
					}
				}

				// The FIRST tab level is what the side navigation switches between;
				// deeper tabs are sub-tabs inside an already-open pane.
				$next_tab = ( null === $tab_id && is_string( $id ) ) ? $id : $tab_id;

				/**
				 * Record the id only for TAB containers. The panes are lazy at every
				 * level, so the JS has to open Header, then Layout, then Navigation in
				 * turn -- but boxes and groups have no tab link, and including them made
				 * the walker wait ~1.1s per id discovering that. Container types are
				 * explicit here (tab / box / group), so the chain can be exact.
				 */
				$is_tab = ( isset( $node['type'] ) && 'tab' === $node['type'] );
				fw_upw_settings_search_walk(
					$node['options'],
					$title !== '' ? array_merge( $trail, array( $title ) ) : $trail,
					$next_tab,
					$index,
					$depth + 1,
					( $is_tab && is_string( $id ) ) ? array_merge( $id_chain, array( $id ) ) : $id_chain
				);
				continue;
			}

			if ( ! isset( $node['type'] ) || ! is_string( $id ) ) {
				continue;
			}

			// `label => false` is used by options whose visible label is rendered by
			// an inner control (multi-pickers do this); skip those rather than index
			// a blank row the user could never recognise.
			$label = ( isset( $node['label'] ) && is_string( $node['label'] ) ) ? $node['label'] : '';
			if ( '' === trim( $label ) ) {
				continue;
			}

			$desc = ( isset( $node['desc'] ) && is_string( $node['desc'] ) ) ? $node['desc'] : '';

			/**
			 * Decode entities before indexing. Titles are authored for HTML output, so
			 * "Color & Backgrounds" arrives as "Color &amp; Backgrounds"; the JS escapes
			 * again when it renders a result, which surfaced the raw "&amp;" in the
			 * breadcrumb. Decode once here so the index holds plain text.
			 */
			$index[] = array(
				'id'    => $id,
				'label' => html_entity_decode( wp_strip_all_tags( $label ), ENT_QUOTES, 'UTF-8' ),
				'desc'  => html_entity_decode( wp_strip_all_tags( $desc ), ENT_QUOTES, 'UTF-8' ),
				'path'  => html_entity_decode( implode( ' → ', $trail ), ENT_QUOTES, 'UTF-8' ),
				'tab'   => $tab_id ? $tab_id : '',
				'tabs'  => $id_chain,
				'type'  => $node['type'],
			);
		}
	}
endif;

if ( ! function_exists( 'fw_upw_settings_search_enqueue' ) ) :
	/**
	 * Ship the widget on the Theme Settings screen only.
	 *
	 * Hooked to the framework's own post-enqueue action so it cannot run on any
	 * other admin screen.
	 */
	function fw_upw_settings_search_enqueue() {
		$index = fw_upw_settings_search_index();
		if ( empty( $index ) ) {
			return;
		}

		$ver = fw()->manifest->get_version();

		wp_enqueue_style(
			'fw-options-search',
			fw_get_framework_asset_uri( '/static/css/backend-options-search.css' ),
			array(),
			$ver
		);

		wp_enqueue_script(
			'fw-options-search',
			fw_get_framework_asset_uri( '/static/js/backend-options-search.js' ),
			array( 'jquery' ),
			$ver,
			true
		);

		wp_localize_script(
			'fw-options-search',
			'_fw_options_search',
			array(
				'index' => $index,
				'l10n'  => array(
					'placeholder' => __( 'Search settings…', 'fw' ),
					'noResults'   => __( 'No matching settings', 'fw' ),
					'results'     => __( 'result(s)', 'fw' ),
					'clear'       => __( 'Clear search', 'fw' ),
				),
			)
		);
	}

	add_action( 'fw_admin_enqueue_scripts:settings', 'fw_upw_settings_search_enqueue' );
endif;
