# `icon-v2` option type — how to USE it (value shape + `sc_icon_render`)

## What it is
An icon PICKER: the modal chooses a **Font-Awesome / pack glyph**, an **uploaded image**, an
**emoji**, or an **SVG** (library / pasted / uploaded). Add it as `'type' => 'icon-v2'`.

`icon-v2` is a thin subclass — `class-fw-option-type-icon-v2.php:36` overrides only `get_type()`;
**all logic (value shape, render, enqueue, templates) lives in the parent `icon-v3`**
(`icon-v3/class-fw-option-type-icon-v3.php`). So the sibling **`icon-v3` shares the SAME value
shape** (the newer variant; it only ADDS the `'lottie' type`). The **legacy `icon` type** stored a
bare font-class string instead — that scalar is still bridged (see Gotchas).

## Saved value shape
An array keyed by `type` (NOT a bare string):
`[ 'type' => 'none'|'icon-font'|'custom-upload'|'emoji'|'svg', 'icon-class' => '',
'icon-class-without-root' => false, 'pack-name' => false, 'pack-css-uri' => false ]`
Per-type keys are ADDED by `_get_db_value_from_json` (`icon-v3/…icon-v3.php:188`):
- `icon-font` → `icon-class`, `icon-class-without-root`, `pack-name`, `pack-css-uri` (`:209`)
- `custom-upload` → `url`, `attachment-id` (`:224`)
- `emoji` → `char` (`:229`)
- `svg` → `svg-source` (`library|upload|inline`), `svg-id` / `markup` / `url` (`:233`)
- `icon-v3` only: `lottie` → `src`, `trigger`, `speed` (`:257`)

## How to consume
Render with **`sc_icon_render( $value, $args )`** (`shortcode-styling-helper.php:2307`). It returns
`<i>` for `icon-font` (`:2358`), `<img>` for `custom-upload` (`:2373`), an emoji `<span>` (`:2383`),
or an inline `<svg>` wrapper (`:2410`) — and it **TOLERATES a legacy bare string** (treats it as an
`icon-font` class, `:2321`). Pass args like `[ 'class' => '…', 'aria_hidden' => true ]`.

## Gotchas
- **Enqueue non-FA packs FIRST or the glyph is a blank box.** `sc_icon_render` auto-enqueues the
  pack for `icon-font` when `enqueue => true` (the default, `:2353`), but frontend `view.php`s still
  enqueue explicitly before render — mirror the logo / `icon_text` / `icon_box` elements and
  `unysonplus_render_menu_toggle()`:
  `fw()->backend->option_type( 'icon-v2' )->packs_loader->enqueue_pack_for_icon( $icon );`
- **`sc_icon_render` lives in the SHORTCODES extension.** Calling it from the theme / another
  extension → guard `function_exists( 'sc_icon_render' )` and fall back.
- **SIZING:** `sc_icon_render` emits a font `<i>` (sized by CSS `font-size`) OR an `<svg>`/`<img>`
  (sized by `width`/`height`). A container that sets only `font-size` leaves an uploaded SVG/image
  **UNBOUNDED** — set `width`/`height` too when the icon type is variable.
- **Legacy scalar bridge:** a stored string (old `icon` value, `'fa fa-linux'`) is normalized to
  `[ 'type' => 'icon-font', 'icon-class' => … ]` in `normalize_value` (`icon-v3.php:150`), so
  `$value['type']` never throws *illegal string offset*. New saves use the array shape.
- Usable inside a **`multi-inline`** (e.g. an Open/Close icon pair) — the array value round-trips.

## Canonical snippet
```php
// option:
'my_icon' => [ 'type' => 'icon-v2', 'label' => __( 'Icon', 'fw' ) ],

// consume (view.php): enqueue the pack, then render.
fw()->backend->option_type( 'icon-v2' )->packs_loader->enqueue_pack_for_icon( $value );
echo sc_icon_render( $value, [ 'class' => 'my-icon', 'aria_hidden' => true ] );
```
