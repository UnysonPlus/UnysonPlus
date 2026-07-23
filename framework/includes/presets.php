<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Preset libraries — defaults · getters · slug maps for the plugin's design
 * tokens (colors, font sizes, spacing/gap, buttons, borders, tables).
 *
 * Split into one file per library under presets/ for maintainability; this file
 * is just the loader. The plugin owns the defaults AND the editor UI (the
 * Shortcodes extension Settings form); saved overrides live in the
 * THEME-INDEPENDENT store `fw_ext_settings_options:shortcodes` (see
 * presets/store.php). Consumed by css-tokens.php, the preset option types, the
 * theme, and the shortcodes extension — so it stays in core and loads here,
 * before css-tokens.php (bootstrap.php). Load order within presets/ is
 * irrelevant: all cross-references are runtime calls.
 */

$fw_presets_dir = __DIR__ . '/presets/';

require_once $fw_presets_dir . 'store.php';           // unysonplus_preset_store_get()
require_once $fw_presets_dir . 'color-presets.php';
require_once $fw_presets_dir . 'font-size-presets.php';
require_once $fw_presets_dir . 'spacing-presets.php';  // spacing scale + gap scale + default-gap getters
require_once $fw_presets_dir . 'button-presets.php';   // colors + sizes + hover animations + migration
require_once $fw_presets_dir . 'border-presets.php';
require_once $fw_presets_dir . 'table-presets.php';
require_once $fw_presets_dir . 'section-style-presets.php'; // .section--{slug} skins
require_once $fw_presets_dir . 'pattern-presets.php';       // .pattern-{slug} background patterns
require_once $fw_presets_dir . 'pattern-scope.php';         // pattern cleanup/scope transform
require_once $fw_presets_dir . 'image-style-presets.php';   // .imgs-{slug} image treatments (radius/mask/filter/scrim)

unset( $fw_presets_dir );
