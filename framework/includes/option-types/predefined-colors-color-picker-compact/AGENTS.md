# `predefined-colors-color-picker-compact` option type — the CONTRACT

The #1 mistake is treating the saved value as a **hex string** — it's a **hash**, and there are
two different ways to consume it. Get that wrong and either the preset link breaks or you emit a
raw offset error. This is the canonical reference.

## What it is
A compact **one-row** control: a palette-**PRESET dropdown** + an inline **custom color picker**
(mutually exclusive halves). It's the exact control the Styling tab's Text Color / Background
Color use. The point is to keep element colors **tied to Theme Settings → Colors** (live-linked
presets) instead of one-off hex values. Backend width type is `auto`
(`class-…-compact.php:127`). **Don't hand-build the option array — use the helper**
`sc_color_field_compact( [ 'label' => …, 'kind' => 'text'|'bg' ] )`
(`shortcode-styling-helper.php:505`): `kind => 'text'` produces `text-{slug}` choices,
`kind => 'bg'` produces `bg-{slug}` — choices come from the live palette
`unysonplus_color_preset_slug_map()` (`:521`).

## Saved value shape
`[ 'predefined' => 'text-red'|'bg-…'|'', 'custom' => '#hex'|'' ]` — a **HASH, not a plain hex
string** (`class-…-compact.php:293`). `predefined` **WINS** when both are set (mutual exclusion —
`shortcode-styling-helper.php:591`). The `predefined` half stores the CSS **class name** verbatim;
the `custom` half stores a hex/rgba string.

## How to consume
Two consumer kinds — pick the matching resolver:

1. **Styling-tab class / inline-style** consumers → run the value through
   `sc_normalize_color_value( $value, $kind )` (`shortcode-styling-helper.php:575`) → returns
   `[ 'class', 'style' ]`. Preset → `class`; custom → `style` (`color:`/`background:`). Also
   **tolerates the legacy plain-string shape**, so a single funnel handles both option types.
2. **CSS-custom-property / JS-hex** consumers (glow vars, WebGL tints) → resolve to a color STRING
   via `sc_color_to_css( $value, $fallback, $as_hex )` (`shortcode-styling-helper.php:631`):
   `predefined` → `var(--color-{slug})` (**STRIP** the `text-`/`bg-` prefix — stays live-linked to
   the preset, `:643`); `custom` → the hex; legacy string → passed through. For JS needing a REAL
   hex (Three.js can't read a CSS var), pass `$as_hex = true` → slug→hex via
   `unysonplus_color_preset_slug_map()` (`:647`).

## Gotchas
- **Guard when OUTSIDE the shortcodes extension** (helper may not be loaded):
  `function_exists( 'sc_color_field_compact' ) ? sc_color_field_compact( … ) : [ 'type' => 'color-picker', … ]`.
- **EXCEPTION — the palette-DEFINITION UI must stay a raw `color-picker`.** You can't pick a preset
  to define a preset. This control is for **CONSUMING** colors on elements, not **defining** the
  palette (`components-color.php` and any swatch-definition field stay raw).
- **Migration = value-SHAPE change.** Flipping an existing `color-picker` (hex string) to this type
  turns a string into a hash. Both `_render()` and `_get_value_from_input()` already rescue a legacy
  string into `{ predefined: <string>, custom: '' }` (`class-…-compact.php:153,283`), and the
  resolvers tolerate the string — but **migrate stored builder JSON** if a consumer can't. New
  options have no saved data, so just call the helper from the start.
- `picker => 'rgba-color-picker'` swaps the custom half to an alpha picker (`_get_defaults`, `:48`).

## Canonical snippet
```php
'glow_color' => sc_color_field_compact( [ 'label' => __( 'Glow color', 'fw' ), 'kind' => 'bg' ] ),
```
