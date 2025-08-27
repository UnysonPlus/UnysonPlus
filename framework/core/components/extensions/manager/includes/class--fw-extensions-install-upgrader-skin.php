<?php if (!defined('FW')) die('Forbidden');

/**
 * PHP Version: 7.4 or higher
 */

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

class _FW_Extensions_Install_Upgrader_Skin extends WP_Upgrader_Skin
{
    /**
     * @param array<string,mixed>|null $data
     */
    public function after(?array $data = []): void
    {
        $update_actions = [
            'extensions_page' => fw_html_tag(
                'a',
                [
                    'href'   => fw_akg('extensions_page_link', $data ?? [], '#'),
                    'title'  => __('Go to extensions page', 'fw'),
                    'target' => '_parent',
                ],
                __('Return to Extensions page', 'fw')
            )
        ];

        $this->feedback(implode(' | ', (array) $update_actions));

        if ($this->result) {
            // used for popup ajax form submit result
            $this->feedback('<span success></span>');
        }
    }
}
