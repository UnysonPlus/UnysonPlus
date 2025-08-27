<?php
// PHP Version: 7.4 or higher
if (!defined('FW')) {
    die('Forbidden');
}

/**
 * @var array<string, array> $lists
 * @var string $link
 * @var array<string, array> $nonces
 * @var mixed $display_default_value
 * @var string $default_thumbnail
 * @var bool $can_install
 */

$installed_plugins = get_plugins();

foreach ($lists['available'] ?? [] as $name => &$_ext) {
    if (empty($_ext['download']['opts']['plugin'] ?? null)) {
        continue;
    }

    $slug = $_ext['download']['opts']['plugin'];

    if (is_plugin_active($slug)) {
        $lists['active'][$name] = $_ext;
        $lists['installed'][$name] = $_ext;
    } elseif (isset($installed_plugins[$slug])) {
        $lists['installed'][$name] = $_ext;
        $lists['disabled'][$name] = $_ext;
    }

    $lists['supported'][$name] = $_ext;
}

// Set extensions order same as in available extensions list
{
    $ordered = [
        'active' => [],
        'installed' => [],
    ];

    foreach ($lists['available'] ?? [] as $name => &$_ext) {
        foreach ($ordered as $type => &$_exts) {
            if (isset($lists[$type][$name])) {
                $ordered[$type][$name] = $lists[$type][$name];
            }
        }
    }

    foreach ($ordered as $type => &$_exts) {
        if (!empty($ordered[$type])) {
            $lists[$type] = array_merge($ordered[$type], $lists[$type] ?? []);
        }
    }

    unset($ordered, $name, $_ext, $_exts, $type);
}

$extension_view_path = dirname(__FILE__) . '/extension.php';
$displayed = [];

?>
<h3><?php _e('Active Extensions', 'fw'); ?></h3>
<?php
$display_active_extensions = [];

foreach ($lists['active'] ?? [] as $name => &$data) {
    if (!empty($data['display']) || true === fw_akg('display', $data['manifest'] ?? [], $display_default_value)) {
        $display_active_extensions[$name] = &$data;
    }
}
unset($data);

if (empty($display_active_extensions)): ?>
    <div class="fw-extensions-no-active">
        <div class="fw-text-center fw-extensions-title-icon"><span class="dashicons dashicons-screenoptions"></span></div>
        <p class="fw-text-center fw-text-muted">
            <em>
                <?php _e('No extensions activated yet', 'fw'); ?><br/>
                <?php _e('Check the available extensions below', 'fw'); ?>
            </em>
        </p>
    </div>
<?php else: ?>
    <div class="fw-row fw-extensions-list">
        <?php
        foreach ($display_active_extensions as $name => &$data) {
            $ext = fw_ext($name ?? '');
            fw_render_view(
                $extension_view_path,
                [
                    'name' => $name,
                    'title' => $ext ? $ext->manifest->get_name() : ($data['name'] ?? 'No name'),
                    'description' => $ext ? $ext->manifest->get('description') : ($data['description'] ?? ''),
                    'link' => $link ?? '',
                    'lists' => &$lists,
                    'nonces' => $nonces,
                    'default_thumbnail' => $default_thumbnail,
                    'can_install' => $can_install,
                ],
                false
            );
            $displayed[$name] = true;
        }
        unset($data);
        ?>
    </div>
<?php endif; ?>

<div id="fw-extensions-list-available">
    <hr class="fw-extensions-lists-separator"/>
    <h3><?php _e('Available Extensions', 'fw'); ?></h3>
    <div class="fw-row fw-extensions-list">
        <?php $something_displayed = false; ?>

        <?php
        {
            $theme_extensions = [];

            foreach ($lists['disabled'] ?? [] as $name => &$data) {
                if (empty($data['is']['theme'] ?? null)) {
                    continue;
                }
                $theme_extensions[$name] = [
                    'name' => fw_akg('name', $data['manifest'] ?? [], fw_id_to_title($name)),
                    'description' => fw_akg('description', $data['manifest'] ?? [], ''),
                ];
            }
            unset($data);

            foreach (($theme_extensions + ($lists['supported'] ?? [])) as $name => $data) {
                if (!empty($displayed[$name])) {
                    continue;
                } elseif (!empty($lists['installed'][$name]['manifest'])) {
                    if (true !== fw_akg('display', $lists['installed'][$name]['manifest'], $display_default_value)) {
                        continue;
                    }
                } elseif (isset($lists['available'][$name])) {
                    if (!$can_install) continue;
                } else {
                    continue;
                }

                fw_render_view(
                    $extension_view_path,
                    [
                        'name' => $name,
                        'title' => $data['name'] ?? 'No name',
                        'description' => $data['description'] ?? '',
                        'link' => $link ?? '',
                        'lists' => &$lists,
                        'nonces' => $nonces,
                        'default_thumbnail' => $default_thumbnail,
                        'can_install' => $can_install,
                    ],
                    false
                );

                $displayed[$name] = $something_displayed = true;
            }

            unset($theme_extensions);
        }

        foreach ($lists['disabled'] ?? [] as $name => &$data) {
            if (!empty($displayed[$name])) continue;
            if (isset($data['display']) && true !== $data['display']) continue;
            if (isset($data['manifest']) && true !== fw_akg('display', $data['manifest'], $display_default_value)) continue;

            fw_render_view(
                $extension_view_path,
                [
                    'name' => $name,
                    'title' => $data['manifest']['name'] ?? $data['name'] ?? 'No name',
                    'description' => $data['manifest']['description'] ?? $data['description'] ?? '',
                    'link' => $link ?? '',
                    'lists' => &$lists,
                    'nonces' => $nonces,
                    'default_thumbnail' => $default_thumbnail,
                    'can_install' => $can_install,
                ],
                false
            );

            $displayed[$name] = $something_displayed = true;
        }
        unset($data);

        if ($can_install) {
            foreach ($lists['available'] ?? [] as $name => &$data) {
                if (!empty($displayed[$name])) continue;
                if (isset($lists['installed'][$name])) continue;
                if (($data['display'] ?? false) !== true) continue;

                if (in_array($name, ['styling', 'megamenu'], true)) {
                    if (!isset($lists['supported'][$name]) && !(defined('WP_DEBUG') && WP_DEBUG)) continue;
                }

                fw_render_view(
                    $extension_view_path,
                    [
                        'name' => $name,
                        'title' => $data['name'] ?? 'No name',
                        'description' => $data['description'] ?? '',
                        'link' => $link ?? '',
                        'lists' => &$lists,
                        'nonces' => $nonces,
                        'default_thumbnail' => $default_thumbnail,
                        'can_install' => $can_install,
                    ],
                    false
                );

                $something_displayed = true;
            }
            unset($data);
        }
        ?>
    </div>

    <?php if ($something_displayed && apply_filters('fw_extensions_page_show_other_extensions', true)): ?>
        <p class="fw-text-center toggle-not-compat-ext-btn-wrapper">
            <?php
            echo fw_html_tag(
                'a',
                [
                    'href' => '#',
                    'onclick' => 'return false;',
                    'class' => 'button toggle-not-compat-ext-btn',
                    'style' => 'box-shadow:none;',
                ],
                '<span class="the-show-text">' . __('Show other extensions', 'fw') . '</span>' .
                '<span class="the-hide-text fw-hidden">' . __('Hide other extensions', 'fw') . '</span>'
            );
            ?>
        </p>
        <script type="text/javascript">
            jQuery(function($){
                if (!$('.fw-extensions-list .fw-extensions-list-item.not-compatible').length || <?php echo empty($lists['supported']) ? 'true' : 'false'; ?>) {
                    $('#fw-extensions-list-wrapper .toggle-not-compat-ext-btn-wrapper').addClass('fw-hidden');
                } else {
                    $('#fw-extensions-list-wrapper .fw-extensions-list .fw-extensions-list-item.not-compatible').fadeOut('fast');
                    $('#fw-extensions-list-wrapper .toggle-not-compat-ext-btn-wrapper').on('click', function(){
                        $('#fw-extensions-list-wrapper .fw-extensions-list .fw-extensions-list-item.not-compatible')[
                            $(this).find('.the-hide-text').hasClass('fw-hidden') ? 'fadeIn' : 'fadeOut'
                        ]();
                        $(this).find('.the-show-text, .the-hide-text').toggleClass('fw-hidden');
                    });
                }
            });
        </script>
    <?php else: ?>
        <script type="text/javascript">
            jQuery(function($){
                $('#fw-extensions-list-available').remove();
            });
        </script>
    <?php endif; ?>
</div>
