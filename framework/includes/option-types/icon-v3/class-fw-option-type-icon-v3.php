<?php
/**
 * PHP Version: 8.2 or higher
 */

if (!defined('FW')) { die('Forbidden'); }

class FW_Option_Type_Icon_v3 extends FW_Option_Type
{
    private array $enqueued_font_styles = [];
    public ?FW_Icon_V3_Packs_Loader $packs_loader = null;
    private ?FW_Icon_V3_Favorites_Manager $favorites = null; // previously dynamic property

    public function get_type(): string
    {
        return 'icon-v3';
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
         * fw()->backend->option_type('icon-v3')->packs_loader->enqueue_frontend_css()
         */
        $this->packs_loader = new FW_Icon_V3_Packs_Loader();

        if (!is_admin()) {
            return;
        }

        // Properly declared property
        $this->favorites = new FW_Icon_V3_Favorites_Manager();
    }

    protected function _enqueue_static($id, $option, $data): void
    {
        add_action(
            'admin_print_footer_scripts',
            [$this, 'load_templates']
        );

        wp_enqueue_media();

        $this->packs_loader->enqueue_admin_css();

        $static_URI = fw_get_framework_directory_uri(
            '/includes/option-types/' . $this->get_type() . '/static/'
        );

        wp_enqueue_style('fw-selectize');

        wp_enqueue_script(
            'fw-option-type-' . $this->get_type() . '-backend-previews',
            $static_URI . 'js/render-icon-previews.js',
            ['jquery', 'fw', 'fw-events', 'fw-selectize'],
            fw()->manifest->get_version()
        );

        wp_enqueue_script(
            'fw-option-type-' . $this->get_type() . '-backend-picker-v2',
            $static_URI . 'js/icon-picker-v3.js',
            ['fw'],
            fw()->manifest->get_version(),
            true
        );

        wp_enqueue_style(
            'fw-option-type-' . $this->get_type() . '-backend-picker',
            $static_URI . 'css/picker.css',
            [],
            fw()->manifest->get_version()
        );

        wp_localize_script(
            'fw-option-type-' . $this->get_type() . '-backend-previews',
            'fw_icon_v3_data',
            [
                'edit_icon_label' => __('Change Icon', 'fw'),
                'add_icon_label' => __('Add Icon', 'fw'),
                'no_results' => __('No icons found', 'fw')
            ]
        );
    }

    public function load_templates(): void
    {
        // This option type is registered under two ids ('icon-v3' and the
        // reclaimed 'icon'), which means two instances each hook
        // admin_print_footer_scripts. The picker templates are keyed by fixed
        // ids (tmpl-fw-icon-v3-*), so print them only once per request.
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
