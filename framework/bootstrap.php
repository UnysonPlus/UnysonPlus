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

            require $dir . '/includes/hooks.php';
            require $dir . '/includes/presets.php';
            require $dir . '/includes/css-tokens.php';
            require $dir . '/includes/device-tabs.php';
            require $dir . '/includes/dynamic-css.php';
            require $dir . '/includes/dynamic-content/class-fw-dynamic-content.php';
            require $dir . '/includes/dynamic-content/classic-editor.php';
            require $dir . '/extensions/shortcodes/includes/shortcode-styling-helper.php';
            // unysonplus-theme ships its own (un-guarded) copy of the
            // fw-multi-inline option type. Loading the plugin's copy first
            // would cause a fatal "Cannot redeclare class" when the theme
            // later declares it again. So skip the plugin's copy whenever
            // unysonplus-theme (or a child of it) is active — the theme will
            // declare and register it itself.
            if ( 'unysonplus-theme' !== get_template() ) {
                require $dir . '/includes/option-types/fw-multi-inline/class-fw-option-type-fw-multi-inline.php';
            }
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
            require $dir . '/extensions/shortcodes/includes/components-options.php';

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
