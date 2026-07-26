<?php
/**
 * PHP Version: 8.2 or higher
 */

if (!defined('FW')) { die('Forbidden'); }

class FW_Option_Type_Icon extends FW_Option_Type
{
    private array $enqueued_font_styles = [];
    public ?FW_Icon_Packs_Loader $packs_loader = null;
    private ?FW_Icon_Favorites_Manager $favorites = null; // previously dynamic property

    public function get_type(): string
    {
        return 'icon';
    }

    protected function _get_data_for_js($id, $option, $data = [])
    {
        return false;
    }

    public function _init(): void
    {
        /**
         * CSS for each pack is not loaded by default in frontend.
         *
         * You should load it by yourself in your theme, like this:
         *
         * fw()->backend->option_type( 'icon' )->packs_loader->enqueue_frontend_css()
         */
        $this->packs_loader = new FW_Icon_Packs_Loader();

        if (!is_admin()) {
            return;
        }

        // Properly declared property
        $this->favorites = new FW_Icon_Favorites_Manager();
    }

    protected function _enqueue_static($id, $option, $data): void
    {
        add_action(
            'admin_print_footer_scripts',
            [$this, 'load_templates']
        );

        wp_enqueue_media();

        $this->packs_loader->enqueue_admin_css();

        // The picker's admin assets. NOTE: the internal handle / CSS-class / JS
        // wp.template names still carry a legacy `…-v3` / `fw-icon-v3-*` prefix —
        // that's an internal naming leftover (also referenced by the live-editor),
        // NOT a second option type. There is only ONE icon type now.
        $static_URI = fw_get_framework_directory_uri(
            '/includes/option-types/icon/static/'
        );

        wp_enqueue_style('fw-selectize');

        wp_enqueue_script(
            'fw-option-type-icon-v3-backend-previews',
            $static_URI . 'js/render-icon-previews.js',
            ['jquery', 'fw', 'fw-events', 'fw-selectize'],
            fw()->manifest->get_version()
        );

        wp_enqueue_script(
            'fw-option-type-icon-v3-backend-picker-v2',
            $static_URI . 'js/icon-picker.js',
            ['fw'],
            fw()->manifest->get_version(),
            true
        );

        wp_enqueue_style(
            'fw-option-type-icon-v3-backend-picker',
            $static_URI . 'css/picker.css',
            [],
            fw()->manifest->get_version()
        );

        wp_localize_script(
            'fw-option-type-icon-v3-backend-previews',
            'fw_icon_v3_data',
            [
                'edit_icon_label' => __('Change Icon', 'fw'),
                'add_icon_label' => __('Add Icon', 'fw'),
                'no_results' => __('No icons found', 'fw'),
                'add_to_favorites' => __('Add to Favorites', 'fw')
            ]
        );

        // Animated-tab player runtimes, per enabled technology — so the modal can
        // PREVIEW the animation. Each is loaded only when the opt-in Animated
        // Icons extension has enabled that technology (the Animated tab + its
        // panels are gated the same way in views/templates.php). On the frontend
        // sc_icon_render() still lazily loads these when an animated icon is output.
        $ver    = fw()->manifest->get_version();
        $lottie = function_exists( 'fw_icon_lottie_enabled' ) && fw_icon_lottie_enabled();
        $rive   = function_exists( 'fw_icon_rive_enabled' ) && fw_icon_rive_enabled();

        if ( $lottie ) {
            $lottie_uri = fw_get_framework_directory_uri('/static/libs/lottie');
            wp_enqueue_style('upw-lottie', $lottie_uri . '/upw-lottie.css', [], $ver);
            wp_enqueue_script('lottie-web', $lottie_uri . '/lottie.min.js', [], $ver, true);
            wp_enqueue_script('upw-lottie', $lottie_uri . '/upw-lottie.js', ['lottie-web'], $ver, true);
        }

        if ( $rive ) {
            // Heavy (~2 MB WASM); only loaded when Rive is explicitly enabled.
            $rive_uri = fw_get_framework_directory_uri('/static/libs/rive');
            wp_enqueue_style('upw-rive', $rive_uri . '/upw-rive.css', [], $ver);
            wp_enqueue_script('rive-canvas', $rive_uri . '/rive.js', [], $ver, true);
            wp_enqueue_script('upw-rive', $rive_uri . '/upw-rive.js', ['rive-canvas'], $ver, true);
            wp_add_inline_script('upw-rive', 'window.upwRiveWasm=' . wp_json_encode($rive_uri . '/rive.wasm') . ';', 'before');
        }

        if ( $lottie || $rive ) {
            // Data the picker's upload handlers need (ajax URL + per-tech nonces + i18n).
            wp_localize_script(
                'fw-option-type-icon-v3-backend-picker-v2',
                'fwIconV3',
                [
                    'ajaxUrl'     => admin_url('admin-ajax.php'),
                    'lottieNonce' => wp_create_nonce('fw_icon_lottie_upload'),
                    'riveNonce'   => wp_create_nonce('fw_icon_rive_upload'),
                    'i18n'        => [
                        'uploading'    => __('Uploading…', 'fw'),
                        'uploadFailed' => __('Upload failed.', 'fw'),
                    ],
                ]
            );
        }
    }

    public function load_templates(): void
    {
        // Print the picker's wp.template markup once per request (the templates
        // are keyed by fixed ids, so a second print would duplicate them).
        static $printed = false;
        if ($printed) { return; }
        $printed = true;

        echo fw_render_view(
            dirname(__FILE__) . '/views/templates.php',
            [
                'packs_loader' => $this->packs_loader
            ]
        );
    }

    /**
     * Normalize a stored value to the canonical array shape.
     *
     * Reclaiming the `icon` type means legacy scalar values (the old `icon`
     * option stored a bare class string like 'fa fa-linux', or 'dashicons
     * dashicons-book') can now reach this engine. Convert them to the font-icon
     * array shape so `$value['type']` access never triggers an illegal-string-
     * offset. Anything already an array is returned untouched.
     */
    public function normalize_value($value)
    {
        if (is_string($value)) {
            $value = ($value === '')
                ? ['type' => 'none']
                : ['type' => 'icon-font', 'icon-class' => $value];
        }

        if (!is_array($value) || !isset($value['type'])) {
            $value = ['type' => 'none'];
        }

        return $value;
    }

    protected function _render($id, $option, $data)
    {
        $json = $this->_get_json_value_to_insert_in_html($data);

        $option['attr']['value'] = $json;

        return fw_render_view(
            dirname(__FILE__) . '/views/view.php',
            compact('id', 'option', 'data', 'json')
        );
    }

    protected function _get_value_from_input($option, $input_value)
    {
        if (is_null($input_value)) {
            // A reclaimed `icon` option may declare a legacy string default
            // (e.g. 'value' => 'fa fa-linux'); hand back the canonical shape.
            return $this->normalize_value($option['value']);
        }

        return $this->_get_db_value_from_json($input_value);
    }

    protected function _get_db_value_from_json($input_value)
    {
        $input = $input_value;

        /**
         * When icon-v3 is used as a multi-picker picker it, the value
         * comes straight as array, you should parse it.
         */
        if (!is_array($input_value)) {
            $decoded = json_decode($input_value, true);
            // A legacy `icon` scalar ('fa fa-star') is not valid JSON — treat
            // the raw string as a font-icon class instead of a decode failure.
            $input = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $input_value;
        }

        $input = $this->normalize_value($input);

        $result = [];

        $result['type'] = $input['type'];

        if ($input['type'] === 'icon-font') {
            $result['icon-class'] = $input['icon-class'];

            $result['icon-class-without-root'] = $this->packs_loader->class_without_root_for(
                $input['icon-class']
            );

            $pack = $this->packs_loader->pack_name_for(
                $input['icon-class']
            );

            $result['pack-name'] = $pack['name'];
            $result['pack-css-uri'] = $pack['css_file_uri'];
        }

        if ($input['type'] === 'custom-upload') {
            $result['attachment-id'] = isset($input['attachment-id']) ? $input['attachment-id'] : false;
            $result['url'] = isset($input['url']) ? $input['url'] : false;
        }

        if ($input['type'] === 'emoji') {
            $result['char'] = isset($input['char']) ? (string) $input['char'] : '';
        }

        if ($input['type'] === 'svg') {
            // library | upload | inline (paste)
            $source = isset($input['svg-source']) ? (string) $input['svg-source'] : '';
            $result['svg-source'] = $source;

            // Keep svg-id ONLY for a library pick — drop a stale one when the
            // user switched this icon to a pasted/uploaded SVG, so it can't win
            // over the markup at render time.
            if ($source !== 'inline' && $source !== 'upload' && !empty($input['svg-id'])) {
                $result['svg-id'] = (string) $input['svg-id'];
            }
            if (!empty($input['attachment-id'])) { $result['attachment-id'] = $input['attachment-id']; }
            if (!empty($input['url']))          { $result['url']           = (string) $input['url']; }

            // Sanitise inline / pasted markup once, on the way in, so what's
            // stored is already clean (render also sanitises, defence-in-depth).
            if (!empty($input['markup'])) {
                $markup = (string) $input['markup'];
                $result['markup'] = function_exists('sc_icon_sanitize_svg')
                    ? sc_icon_sanitize_svg($markup)
                    : $markup;
            }
        }

        if ($input['type'] === 'lottie') {
            $result['src'] = isset($input['src'])
                ? esc_url_raw( function_exists('fw_upw_normalize_legacy_upload_url') ? fw_upw_normalize_legacy_upload_url((string) $input['src']) : (string) $input['src'] )
                : '';
            $trigger = isset($input['trigger']) ? preg_replace('/[^a-z]/', '', (string) $input['trigger']) : 'loop';
            $result['trigger'] = in_array($trigger, ['loop', 'once', 'hover', 'click'], true) ? $trigger : 'loop';
            $speed = isset($input['speed']) ? (float) $input['speed'] : 1;
            $result['speed'] = ($speed > 0 && $speed <= 8) ? $speed : 1;
        }

        if ($input['type'] === 'rive') {
            $result['src'] = isset($input['src'])
                ? esc_url_raw( function_exists('fw_upw_normalize_legacy_upload_url') ? fw_upw_normalize_legacy_upload_url((string) $input['src']) : (string) $input['src'] )
                : '';
            $trigger = isset($input['trigger']) ? preg_replace('/[^a-z]/', '', (string) $input['trigger']) : 'loop';
            $result['trigger'] = in_array($trigger, ['loop', 'once', 'hover', 'click'], true) ? $trigger : 'loop';
        }

        return $result;
    }

    protected function _get_json_value_to_insert_in_html($data): string
    {
        // A reclaimed `icon` option may carry a legacy string value; normalize
        // before touching $value['type'] so the picker preview never fatals on
        // a pre-existing item (mirrors the multi-picker editor-load gotcha).
        $data['value'] = $this->normalize_value($data['value']);

        $result = [];

        $result['type'] = $data['value']['type'];

        if ($data['value']['type'] === 'icon-font') {
            $result['icon-class'] = $data['value']['icon-class'];
        }

        if ($data['value']['type'] === 'custom-upload') {
            $result['attachment-id'] = isset($data['value']['attachment-id']) ? $data['value']['attachment-id'] : false;
            $result['url'] = isset($data['value']['url']) ? $data['value']['url'] : false;
        }

        if ($data['value']['type'] === 'emoji') {
            $result['char'] = isset($data['value']['char']) ? (string) $data['value']['char'] : '';
        }

        if ($data['value']['type'] === 'svg') {
            $result['svg-source'] = isset($data['value']['svg-source']) ? (string) $data['value']['svg-source'] : '';
            foreach (['svg-id', 'attachment-id', 'url', 'markup'] as $k) {
                if (!empty($data['value'][$k])) { $result[$k] = $data['value'][$k]; }
            }
            // A LIBRARY icon set programmatically (or by the Site Converter) carries only
            // its `svg-id`, not `markup` — so the picker preview (which renders from
            // `markup`) shows an empty box. Resolve the markup from the id here, mirroring
            // the frontend's fw_icon_lucide_markup() fallback, so id-only values preview
            // too. (The user-picked path already stores markup, so this only fills the gap.)
            if ($result['svg-source'] === 'library' && empty($result['markup'])
                && ! empty($result['svg-id']) && function_exists('fw_icon_lucide_markup')) {
                $mk = fw_icon_lucide_markup($result['svg-id']);
                if ($mk) { $result['markup'] = $mk; }
            }
        }

        // Seed the Animated tab's player kinds so reopening the picker on a
        // stored value pre-fills its URL / trigger (without this the tab opens
        // empty on an existing item).
        if ($data['value']['type'] === 'lottie') {
            $result['src']     = isset($data['value']['src']) ? (string) $data['value']['src'] : '';
            $result['trigger'] = isset($data['value']['trigger']) ? (string) $data['value']['trigger'] : 'loop';
            $result['speed']   = isset($data['value']['speed']) ? (float) $data['value']['speed'] : 1;
        }

        if ($data['value']['type'] === 'rive') {
            $result['src']     = isset($data['value']['src']) ? (string) $data['value']['src'] : '';
            $result['trigger'] = isset($data['value']['trigger']) ? (string) $data['value']['trigger'] : 'loop';
        }

        return json_encode($result);
    }

    protected function _get_defaults()
    {
        return [
            'value' => [
                'type' => 'none', // none | icon-font | custom-upload

                // ONLY IF icon-font
                'icon-class' => '',
                'icon-class-without-root' => false,
                'pack-name' => false,
                'pack-css-uri' => false

                // ONLY IF custom-upload
                // 'attachment-id' => false,
                // 'url' => false
            ],

            'preview_size' => 'medium',
            'popup_size' => 'medium'
        ];
    }

    public function _get_backend_width_type(): string
    {
        return 'full';
    }
}
