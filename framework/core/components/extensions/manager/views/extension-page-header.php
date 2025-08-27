<?php
// PHP Version: 7.4 or higher
if (!defined('FW')) {
    die('Forbidden');
}

/**
 * @var string      $extension_name
 * @var array<string, mixed> $extension_data
 * @var string      $extension_title
 * @var string      $link_delete
 * @var string      $link_extension
 * @var string|null $tab
 * @var bool        $is_supported
 */
$tab ??= ''; // ensure $tab is a string
?>

<h2 class="fw-extension-page-title">
    <span class="fw-pull-right">
        <?php
        switch ($tab) {
            case 'settings':
                if (empty($extension_data['path']) || !file_exists($extension_data['path'] . '/readme.md.php')) {
                    break;
                }
                if ($is_supported) {
                    // do not show install instructions for supported extensions
                    break;
                }
                ?>
                <a href="<?php echo esc_attr($link_extension ?? '#'); ?>&extension=<?php echo esc_attr($extension_name ?? ''); ?>&tab=docs" class="button-primary">
                    <?php _e('Install Instructions', 'fw'); ?>
                </a>
                <?php
                break;

            case 'docs':
                $extension_obj = fw()->extensions->get($extension_name ?? '');
                if (!$extension_obj || !$extension_obj->get_settings_options()) {
                    break;
                }
                ?>
                <a href="<?php echo esc_attr($link_extension ?? '#'); ?>&extension=<?php echo esc_attr($extension_name ?? ''); ?>" class="button-primary">
                    <?php _e('Settings', 'fw'); ?>
                </a>
                <?php
                break;
        }
        ?>
    </span>

    <?php
    switch ($tab) {
        case 'settings':
            echo sprintf(__('%s Settings', 'fw'), esc_html($extension_title ?? ''));
            break;
        case 'docs':
            echo sprintf(__('%s Install Instructions', 'fw'), esc_html($extension_title ?? ''));
            break;
        default:
            echo __('Unknown tab:', 'fw') . ' ' . fw_htmlspecialchars($tab ?? '');
    }
    ?>
</h2>
<br/>
