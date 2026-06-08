<?php if ( ! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }
/**
 * Plugin Name: Unyson+
 * Plugin URI: https://github.com/UnysonPlus/UnysonPlus
 * Description: A free drag & drop framework that comes with a bunch of built in extensions that will help you develop premium themes fast & easy.
 * Version: 2.9.80
 * Author: Lastimosa.com.ph
 * Author URI: http://lastimosa.com.ph
 * License: GPL2+
 * Text Domain: fw
 * Domain Path: /framework/languages
 * PHP Version: 7.4 or higher
 */

if ( defined( 'FW' ) ) {
        /**
         * The plugin was already loaded (maybe as another plugin with different directory name)
         */
} else {
        require __DIR__ . '/framework/bootstrap.php';

        /**
         * Plugin Update Checker - Enable automatic updates from GitHub
         */
        if ( file_exists( __DIR__ . '/framework/includes/plugin-update-checker/plugin-update-checker.php' ) ) {
                require __DIR__ . '/framework/includes/plugin-update-checker/plugin-update-checker.php';

                $unysonplus_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                        'https://github.com/UnysonPlus/UnysonPlus/',
                        __FILE__,
                        'unysonplus'
                );

                // Set the branch that contains the stable release
                $unysonplus_update_checker->setBranch( 'master' );

                // Enable release assets (use ZIP from GitHub releases if available)
                $unysonplus_update_checker->getVcsApi()->enableReleaseAssets();
        }

        /**
         * Plugin related functionality
         *
         * Note:
         * The framework doesn't know that it's used as a plugin.
         * It can be located in the theme directory or any other directory.
         * Only its path and uri is known
         */
        {
                /** @internal */
                function _action_fw_plugin_activate(): void {
                        update_option( '_fw_plugin_activated', true, false ); // add special option (is used in another action)

                        // @see framework/bootstrap.php
                        // must not be loaded
                        if ( did_action( 'after_setup_theme' ) && ! did_action( 'fw_init' ) ) {
                                _action_init_framework(); // load (prematurely) the plugin
                                do_action( 'fw_plugin_activate' );
                        }
                }

                register_activation_hook( __FILE__, '_action_fw_plugin_activate' );

                /** @internal */
                function _action_fw_plugin_check_if_was_activated(): void {
                        if ( get_option( '_fw_plugin_activated' ) ) {
                                delete_option( '_fw_plugin_activated' );

                                do_action( 'fw_after_plugin_activate' );
                        }
                }
                add_action(
                        'current_screen', // as late as possible, but to be able to make redirects (content not started)
                        '_action_fw_plugin_check_if_was_activated',
                        100
                );

                /**
                 * @param int  $blog_id Blog ID
                 * @param bool $drop    True if blog's table should be dropped. Default is false.
                 * @internal
                 */
                function _action_fw_delete_blog( int $blog_id, bool $drop ): void {
                        if ( $drop ) {
                                global $wpdb; /** @var wpdb $wpdb */

                                // delete old termmeta table.
                                // DROP TABLE is DDL — cannot be parameterised via $wpdb->prepare().
                                // Table identifier is built from $wpdb->prefix (internal value); run
                                // through esc_sql() defensively.
                                $_termmeta_table = esc_sql( $wpdb->prefix . 'fw_termmeta' );
                                $wpdb->query( "DROP TABLE IF EXISTS `{$_termmeta_table}`;" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                        }
                }
                add_action( 'delete_blog', '_action_fw_delete_blog', 10, 2 );

                /** @internal */
                function _filter_fw_plugin_action_list( array $actions ): array {
                        return apply_filters( 'fw_plugin_action_list', $actions );
                }
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), '_filter_fw_plugin_action_list' );

                /** @internal */
                function _action_fw_textdomain(): void {
                        load_plugin_textdomain( 'fw', false, plugin_basename( __DIR__ ) . '/framework/languages' );
                }
                add_action( 'fw_before_init', '_action_fw_textdomain', 3 );

                /** @internal */
                function _filter_fw_tmp_dir( string $dir ): string {
                        /**
                         * Some users force WP_Filesystem to use the 'direct' method <?php define( 'FS_METHOD', 'direct' ); ?> and set chmod 777 to the unyson/ plugin.
                         * By default tmp dir is WP_CONTENT_DIR.'/tmp' and WP_Filesystem can't create it with 'direct' method, then users can't download and install extensions.
                         * In order to prevent this situation, create the temporary directory inside the plugin folder.
                         */
                        return __DIR__ . '/tmp';
                }
                add_filter( 'fw_tmp_dir', '_filter_fw_tmp_dir' );

                /**
                 * Recommend the Classic Editor plugin if it isn't active.
                 *
                 * The Unyson Page Builder sits in a meta box below the post
                 * editor. With Gutenberg active, authors see two editors at
                 * once. Classic Editor restores WordPress's pre-5.0 editor,
                 * which works much better alongside the Page Builder.
                 *
                 * The notice is shown only to users with `install_plugins`
                 * capability and is persistently dismissible per-user via the
                 * `_fw_dismissed_classic_editor_notice` user_meta key.
                 *
                 * @internal
                 */
                function _action_fw_classic_editor_notice(): void {
                        if ( ! current_user_can( 'install_plugins' ) ) {
                                return;
                        }

                        if ( get_user_meta( get_current_user_id(), '_fw_dismissed_classic_editor_notice', true ) ) {
                                return;
                        }

                        if ( ! function_exists( 'is_plugin_active' ) ) {
                                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }

                        $plugin_file = 'classic-editor/classic-editor.php';

                        if ( is_plugin_active( $plugin_file ) ) {
                                return;
                        }

                        $is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );

                        if ( $is_installed ) {
                                $action_url   = wp_nonce_url(
                                        self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ),
                                        'activate-plugin_' . $plugin_file
                                );
                                $action_label = __( 'Activate Classic Editor', 'fw' );
                        } else {
                                $action_url   = wp_nonce_url(
                                        self_admin_url( 'update.php?action=install-plugin&plugin=classic-editor' ),
                                        'install-plugin_classic-editor'
                                );
                                $action_label = __( 'Install Classic Editor', 'fw' );
                        }

                        $dismiss_url = wp_nonce_url(
                                add_query_arg( 'fw-dismiss-classic-editor-notice', '1' ),
                                'fw-dismiss-classic-editor-notice'
                        );
                        ?>
                        <div class="notice notice-info is-dismissible">
                                <p>
                                        <strong>Unyson+:</strong>
                                        <?php esc_html_e( 'For the best Page Builder experience we recommend installing the Classic Editor plugin. It replaces the Gutenberg block editor with WordPress\'s original editor, which works much better alongside the Unyson Page Builder.', 'fw' ); ?>
                                </p>
                                <p>
                                        <a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary"><?php echo esc_html( $action_label ); ?></a>
                                        <a href="https://en-gb.wordpress.org/plugins/classic-editor/" target="_blank" rel="noopener" class="button button-secondary" style="margin-left: 6px;"><?php esc_html_e( 'Learn more', 'fw' ); ?></a>
                                        <a href="<?php echo esc_url( $dismiss_url ); ?>" class="button-link" style="margin-left: 12px;"><?php esc_html_e( 'Dismiss this notice', 'fw' ); ?></a>
                                </p>
                        </div>
                        <?php
                }
                add_action( 'admin_notices', '_action_fw_classic_editor_notice' );

                /** @internal */
                function _action_fw_classic_editor_notice_dismiss(): void {
                        if ( empty( $_GET['fw-dismiss-classic-editor-notice'] ) ) {
                                return;
                        }
                        if ( ! current_user_can( 'install_plugins' ) ) {
                                return;
                        }
                        if (
                                ! isset( $_GET['_wpnonce'] )
                                || ! wp_verify_nonce(
                                        sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
                                        'fw-dismiss-classic-editor-notice'
                                )
                        ) {
                                return;
                        }

                        update_user_meta( get_current_user_id(), '_fw_dismissed_classic_editor_notice', 1 );

                        wp_safe_redirect( remove_query_arg( array( 'fw-dismiss-classic-editor-notice', '_wpnonce' ) ) );
                        exit;
                }
                add_action( 'admin_init', '_action_fw_classic_editor_notice_dismiss' );

                /** @internal */
                final class _FW_Update_Hooks {
                        public static function _init(): void {
                                add_filter( 'upgrader_pre_install',  [ __CLASS__, '_filter_fw_check_if_plugin_pre_update' ],  9999, 2 );
                                add_filter( 'upgrader_post_install', [ __CLASS__, '_filter_fw_check_if_plugin_post_update' ], 9999, 2 );
                                add_action( 'automatic_updates_complete', [ __CLASS__, '_action_fw_automatic_updates_complete' ] );
                        }

                        public static function _filter_fw_check_if_plugin_pre_update( $result, array $data ) {
                                if (
                                        ! is_wp_error( $result )
                                        && isset( $data['plugin'] )
                                        && plugin_basename( __FILE__ ) === $data['plugin']
                                ) {
                                        /**
                                         * Before plugin update
                                         * The plugin was already downloaded and extracted to a temp directory
                                         * and it's right before being replaced with the new downloaded version
                                         */
                                        do_action( 'fw_plugin_pre_update' );
                                }

                                return $result;
                        }

                        public static function _filter_fw_check_if_plugin_post_update( $result, array $data ) {
                                if (
                                        ! is_wp_error( $result )
                                        && isset( $data['plugin'] )
                                        && plugin_basename( __FILE__ ) === $data['plugin']
                                ) {
                                        /**
                                         * After plugin successfully updated
                                         */
                                        do_action( 'fw_plugin_post_update' );
                                }

                                return $result;
                        }

                        public static function _action_fw_automatic_updates_complete( array $results ): void {
                                if ( ! isset( $results['plugin'] ) ) {
                                        return;
                                }

                                foreach ( $results['plugin'] as $plugin ) {
                                        if ( plugin_basename( __FILE__ ) === strtolower( $plugin->item->plugin ) ) {
                                                do_action( 'fw_automatic_update_complete', $plugin->result );
                                                break;
                                        }
                                }
                        }
                }
                _FW_Update_Hooks::_init();
        }
}
