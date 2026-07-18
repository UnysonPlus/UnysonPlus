<?php declare(strict_types=1);

if (!defined('FW')) die('Forbidden');

/**
 * PHP Version: 7.4 or higher
 */

/**
 * Used in fw()->backend
 * @internal
 */
class FW_Settings_Form_Theme extends FW_Settings_Form {
	/** @var bool Whether the late side-tabs re-resolution already ran this request. */
	private bool $side_tabs_resolved = false;

	/**
	 * Re-resolve `settings_form_side_tabs` late (on an admin hook), after every extension
	 * has loaded.
	 *
	 * This form is constructed during backend init — loop 1 of the framework boot
	 * (`_init()` on each component) — where it reads the theme config, resolving AND caching
	 * it. But extensions only ACTIVATE in loop 2 (`_after_components_init()`), so any plugin
	 * that injects Theme Settings and sets a side-tabs default through the `fw_theme_config`
	 * filter (e.g. the Shortcodes extension forcing side tabs on a generic, non-Unyson theme
	 * that ships no config.php) registers that filter AFTER the config was already cached —
	 * i.e. the value captured in `_init()` can be stale (horizontal tabs when it should be a
	 * side rail). On a theme whose own config.php sets the flag this never bites, because the
	 * value is read straight from the file, pre-filter.
	 *
	 * By the time any admin hook fires, every `fw_theme_config` filter is registered, so drop
	 * the possibly-stale cached theme config and read the flag again. Runs once per request.
	 *
	 * @internal
	 */
	private function resolve_side_tabs_late(): void {
		if ( $this->side_tabs_resolved ) {
			return;
		}
		$this->side_tabs_resolved = true;

		if ( class_exists( 'FW_Cache' ) ) {
			// Config is cached under '<theme-component-cache-key>/config' = 'fw_theme/config'.
			FW_Cache::del( 'fw_theme/config' );
		}

		$this->set_is_side_tabs( (bool) fw()->theme->get_config( 'settings_form_side_tabs' ) );
	}

	protected function _init() {
		$this
			->set_is_ajax_submit( fw()->theme->get_config('settings_form_ajax_submit') )
			->set_is_side_tabs( fw()->theme->get_config('settings_form_side_tabs') )
			->set_string( 'title', __('Theme Settings', 'fw') );

		{
			add_action('admin_init', [$this, '_action_get_title_from_menu']);
			add_action('admin_menu', [$this, '_action_admin_menu']);
			add_action('admin_enqueue_scripts', [$this, '_action_admin_enqueue_scripts'],
				/**
				 * In case some custom defined option types are using script/styles registered
				 * in actions with default priority 10 (make sure the enqueue is executed after register)
				 * @see _FW_Component_Backend::add_actions()
				 */
				11
			);
		}
	}

	public function get_options() {
		return fw()->theme->get_settings_options();
	}

	public function set_values($values) {
		fw_set_db_settings_option(null, $values);

		return $this;
	}

	public function get_values() {
		return fw_get_db_settings_option();
	}

	/**
	 * User can overwrite Theme Settings menu, move it and change its title
	 * extract that title from WP menu
	 * @internal
	 */
	public function _action_get_title_from_menu() {
		$this->resolve_side_tabs_late();

		if ($this->get_is_side_tabs()) {
			$title = fw()->theme->manifest->get_name();

			if (fw()->theme->manifest->get('author')) {
				if (fw()->theme->manifest->get('author_uri')) {
					$title .= ' '. fw_html_tag('a', [
							'href' => fw()->theme->manifest->get('author_uri'),
							'target' => '_blank'
						], '<small>' . __('by', 'fw') . ' ' . fw()->theme->manifest->get('author') . '</small>');
				} else {
					$title .= ' <small>' . fw()->theme->manifest->get('author') . '</small>';
				}
			}

			$this->set_string('title', $title);
		} else {
			// Extract page title from menu title
			do {
				global $menu, $submenu;

				if (is_array($menu)) {
					foreach ($menu as $_menu) {
						if ($_menu[2] === fw()->backend->_get_settings_page_slug()) {
							$title = $_menu[0];
							break 2;
						}
					}
				}

				if (is_array($submenu)) {
					foreach ($submenu as $_menu) {
						foreach ($_menu as $_submenu) {
							if ($_submenu[2] === fw()->backend->_get_settings_page_slug()) {
								$title = $_submenu[0];
								break 3;
							}
						}
					}
				}
			} while(false);

			if (isset($title)) {
				$this->set_string('title', $title);
			}
		}
	}

	/**
	 * @internal
	 */
	public function _action_admin_menu() {
		$this->resolve_side_tabs_late();

		$data = [
			'capability'       => 'manage_options',
			'slug'             => fw()->backend->_get_settings_page_slug(),
			'content_callback' => [$this, 'render'],
		];

		if ( ! current_user_can( $data['capability'] ) ) {
			return;
		}

		if (fw()->theme->get_config('disable_theme_settings_page', false)) {
			return;
		}

		/**
		 * Whether the Theme Settings menu under Appearance should be registered.
		 * Default: true only when the active theme ships its own
		 * framework-customizations/theme/options/settings.php.
		 *
		 * Plugins that inject options via the `fw_settings_options` filter can
		 * hook this to force the menu on themes that don't provide their own
		 * settings.php — otherwise their injected options have no host.
		 *
		 * @param bool $should_register Default check result.
		 */
		$should_register = (bool) fw()->theme->locate_path('/options/settings.php');
		$should_register = (bool) apply_filters( 'fw_theme_settings_menu_register', $should_register );
		if ( ! $should_register ) {
			return;
		}

		/**
		 * Collect $hookname that contains $data['slug'] before the action
		 * and skip them in verification after action
		 */
		{
			global $_registered_pages;

			$found_hooknames = [];

			if ( ! empty( $_registered_pages ) ) {
				foreach ( $_registered_pages as $hookname => $b ) {
					if ( strpos( $hookname, $data['slug'] ) !== false ) {
						$found_hooknames[ $hookname ] = true;
					}
				}
			}
		}

		/**
		 * Use this action if you what to add the settings page in a custom place in menu
		 * Usage example http://pastebin.com/gvAjGRm1
		 */
		do_action( 'fw_backend_add_custom_settings_menu', $data );

		/**
		 * Check if settings menu was added in the action above
		 */
		{
			$menu_exists = false;

			if ( ! empty( $_registered_pages ) ) {
				foreach ( $_registered_pages as $hookname => $b ) {
					if ( isset( $found_hooknames[ $hookname ] ) ) {
						continue;
					}

					if ( strpos( $hookname, $data['slug'] ) !== false ) {
						$menu_exists = true;
						break;
					}
				}
			}
		}

		if ( $menu_exists ) {
			return;
		}

		add_theme_page(
			__( 'Theme Settings', 'fw' ),
			__( 'Theme Settings', 'fw' ),
			$data['capability'],
			$data['slug'],
			$data['content_callback']
		);

		add_action( 'admin_menu', [$this, '_action_admin_change_theme_settings_order'], 9999 );
	}

	/**
	 * @internal
	 */
	public function _action_admin_change_theme_settings_order() {
		global $submenu;

		if ( ! isset( $submenu['themes.php'] ) ) {
			// probably current user doesn't have this item in menu
			return;
		}

		$id    = fw()->backend->_get_settings_page_slug();
		$index = null;

		foreach ( $submenu['themes.php'] as $key => $sm ) {
			if ( $sm[2] == $id ) {
				$index = $key;
				break;
			}
		}

		if ( ! empty( $index ) ) {
			$item = $submenu['themes.php'][ $index ];
			unset( $submenu['themes.php'][ $index ] );
			array_unshift( $submenu['themes.php'], $item );
		}
	}

	/**
	 * @internal
	 */
	public function _action_admin_enqueue_scripts()
	{
		global $plugin_page;

		/**
		 * Enqueue settings options static in <head>
		 */
		{
			if (fw()->backend->_get_settings_page_slug() === $plugin_page) {
				$this->enqueue_static();

				do_action('fw_admin_enqueue_scripts:settings');
			}
		}
	}
}
