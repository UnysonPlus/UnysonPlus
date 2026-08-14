<?php
if (!defined('ABSPATH')) {
    exit('Forbidden');
}
/**
 * PHP Version: 7.4 or higher
 */

if (defined('WP_CLI') && WP_CLI && !isset($_SERVER['HTTP_HOST'])) {
    // Provide default values when running inside WP-CLI without a server context
    $_SERVER['HTTP_HOST']   = 'unyson.io';
    $_SERVER['SERVER_NAME'] = 'unyson';
    $_SERVER['SERVER_PORT'] = '80';
}

if (defined('FW')) {
    /**
     * The framework is already loaded.
     */
} else {
    define('FW', true);

    /**
     * Load the framework on 'after_setup_theme' action when the theme information is available
     * To prevent `undefined constant TEMPLATEPATH` errors when the framework is used as plugin
     */
    add_action('after_setup_theme', '_action_init_framework');

    if (!function_exists('_action_init_framework')) {
        /**
         * Initialize the Unyson framework
         *
         * Ensures it is loaded only once and in the proper WordPress lifecycle.
         *
         * @return void
         */
        function _action_init_framework(): void
        {
            if (did_action('fw_init')) {
                return;
            }

            do_action('fw_before_init');

            $dir = __DIR__;

            require $dir . '/autoload.php';

            // Load helper functions
            foreach (['general', 'meta', 'fw-storage', 'database'] as $file) {
                require $dir . '/helpers/' . $file . '.php';
            }

            // Load core
            require $dir . '/core/Fw.php';
            fw();

            // Shared uploads-dir helper (uploads/unysonplus/<subdir>/) + the
            // one-time consolidation migration. Loaded early so every dir helper
            // below can route through fw_upw_uploads_dir().
            require $dir . '/includes/uploads-dir.php';
            // Shared post-type choice list (fw_upw_post_type_choices()) used by the
            // Post Types + Custom Fields extensions.
            require $dir . '/includes/post-type-choices.php';
            require $dir . '/includes/hooks.php';
            // Admin-only: confirm dialog before a manual plugin update (back-up reminder).
            if ( is_admin() ) {
                require $dir . '/includes/update-guard/update-guard.php';
            }
            require $dir . '/includes/presets.php';
            require $dir . '/includes/css-tokens.php';
            require $dir . '/includes/device-tabs.php';
            require $dir . '/includes/dynamic-css.php';
            require $dir . '/includes/dynamic-content/class-fw-dynamic-content.php';
            require $dir . '/includes/dynamic-content/classic-editor.php';
            // NOTE: the next few files live in the shortcodes EXTENSION but are CORE dependencies
            // (required here at framework init). They're guarded with file_exists() so a release that
            // ever bundles a core ahead of an older shortcodes extension (or a partial download / a
            // hand-edited install) DEGRADES a feature instead of fataling the whole site with a
            // "Failed to open stream" error before WP can even render an admin notice. A green
            // release is still guaranteed by build-release's completeness check (check-release.php);
            // this guard is the last-resort safety net.
            if ( file_exists( $dir . '/extensions/shortcodes/includes/shortcode-styling-helper.php' ) ) {
                require $dir . '/extensions/shortcodes/includes/shortcode-styling-helper.php';
            }
            // Shared image-mask shape library (Image Style preset + Image Box shortcode).
            if ( file_exists( $dir . '/extensions/shortcodes/includes/image-mask-library.php' ) ) {
                require $dir . '/extensions/shortcodes/includes/image-mask-library.php';
            }
            // Multi-pack inline-SVG engine (fw_icon_svg_pack_* + the Lucide
            // back-compat wrappers fw_icon_lucide_markup / _all / _search) for
            // the icon type's SVG kind. Each pack ships two JSON files in data/;
            // Lucide is bundled, more packs (Tabler, …) register on the
            // `fw_icon_svg_packs` filter. Loaded always so the frontend resolver
            // is available even on requests that don't touch the option type.
            require $dir . '/includes/option-types/icon/includes/svg-packs.php';
            // Icon-pack enable/disable: reads the Theme Settings -> Icons checklist
            // and filters which packs the icon picker offers. Loaded always so the
            // filter_packs hook + gating helpers exist wherever the picker renders.
            require $dir . '/includes/option-types/icon/includes/pack-settings.php';
            // On-demand SVG icon-pack installer (icon-v3): downloads packs from an
            // external catalog into wp-content/uploads/unysonplus-icon-packs/ so the
            // plugin ships only Lucide/Tabler and stays small. Registers installed
            // packs into the shared fw_icon_svg_packs registry (local meta.json, no
            // network) and exposes the install/uninstall AJAX. Loaded always so the
            // installed-pack registry + frontend markup resolver work everywhere.
            require $dir . '/includes/option-types/icon/includes/pack-installer.php';
            // Font Awesome 4 -> 6 class migration (fw_fa4_to_fa6). Loaded always
            // so legacy `fa fa-*` values are normalised to FA6 wherever icons
            // render, not only in the picker.
            require $dir . '/includes/option-types/icon/includes/fa-migrate.php';
            // unysonplus-theme ships its own (un-guarded) copy of the
            // fw-multi-inline option type. Loading the plugin's copy first
            // would cause a fatal "Cannot redeclare class" when the theme
            // later declares it again. So skip the plugin's copy whenever
            // unysonplus-theme (or a child of it) is active — the theme will
            // declare and register it itself.
            if ( 'unysonplus-theme' !== get_template() ) {
                require $dir . '/includes/option-types/fw-multi-inline/class-fw-option-type-fw-multi-inline.php';
            }
            // multi-inline — the canonical, correctly-named successor to
            // fw-multi-inline (same control, convention-compliant id + class).
            // Standalone (does NOT extend FwMultiInline), so it loads
            // unconditionally regardless of active theme; the class_exists
            // guard inside the file keeps a stale theme-side copy from fataling.
            // Call sites are being migrated fw-multi-inline -> multi-inline
            // one-by-one; both coexist during the transition.
            require $dir . '/includes/option-types/multi-inline/class-fw-option-type-multi-inline.php';
            // button-set — segmented control covering single (radio semantics) and
            // multi (checkbox semantics) selection. Plugin-only, class_exists
            // guard inside the file; eager-required so its FW_Option_Type::register()
            // fires before any options.php uses type 'button-set'.
            require $dir . '/includes/option-types/button-set/class-fw-option-type-button-set.php';
            // background-pro lives only in the plugin — load unconditionally.
            // The class itself is wrapped in a class_exists guard, so a stale
            // theme-side copy on a partially-upgraded deploy won't fatal.
            require $dir . '/includes/option-types/background-pro/class-fw-option-type-background-pro.php';
            // spacing — same story as background-pro. Plugin-only composite,
            // class_exists guard inside the file, eager-required so the
            // FW_Option_Type::register() call at the end of the class file
            // fires before any options.php tries to use type 'spacing'.
            require $dir . '/includes/option-types/spacing/class-fw-option-type-spacing.php';
            // responsive — plugin-only generic per-device wrapper around a single
            // inner control (image-picker / select). class_exists guard inside the
            // file; eager-required so its FW_Option_Type::register() fires before any
            // options.php uses type 'responsive' (Column Content Alignment does).
            require $dir . '/includes/option-types/responsive/class-fw-option-type-responsive.php';
            // position-box — plugin-only composite (four inline unit-input sides
            // for CSS position offsets). class_exists guard inside the file;
            // eager-required so its FW_Option_Type::register() fires before any
            // options.php uses type 'position-box' (the shared Advanced tab does).
            require $dir . '/includes/option-types/position-box/class-fw-option-type-position-box.php';
            // Canonical, plugin-owned schema for the shortcode preset libraries
            // (Color/Typography/Spacing/Buttons/Box/Table). Injected into Appearance
            // -> Theme Settings -> Components by the Shortcodes extension
            // (includes/theme-settings-presets.php) and stored THEME-SCOPED in
            // fw_theme_settings_options:{theme-id}.
            if ( file_exists( $dir . '/extensions/shortcodes/includes/components-options.php' ) ) {
                require $dir . '/extensions/shortcodes/includes/components-options.php';
            }

            // React control layer (fw.controls) — the SECOND renderer for the
            // option schema, for surfaces that cannot consume PHP-rendered HTML
            // (Gutenberg block inspectors, new React admin screens). Registers
            // the script handle only; nothing is enqueued until a consumer calls
            // wp_enqueue_script('fw-controls'). The PHP _render() path is
            // untouched and stays authoritative for the builder + options pages.
            // Autoloaded via the PSR-4 registrar in framework/autoload.php.
            add_action(
                'admin_enqueue_scripts',
                array( 'UnysonPlus\\Admin\\Controls\\Registry', 'register' ),
                5
            );

            /**
             * Init components
             */
            $components = [
                /**
                 * Load the theme's hooks.php first, to give users the possibility to add_action()
                 * for `extensions` and `backend` components actions that can happen while their initialization
                 */
                'theme',
                /**
                 * Load extensions before backend, to give extensions the possibility to add_action()
                 * for the `backend` component actions that can happen while its initialization
                 */
                'extensions',
                'backend'
            ];

            foreach ($components as $component) {
                fw()->{$component}->_init();
            }

            foreach ($components as $component) {
                fw()->{$component}->_after_components_init();
            }

            /**
             * The framework is loaded
             */
            do_action('fw_init');
        }
    }
}
