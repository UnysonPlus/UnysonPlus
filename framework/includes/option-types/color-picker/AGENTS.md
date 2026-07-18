# `color-picker` option type — how to USE it (Coloris, alpha, timing)

A single colour field. It was **swapped from WP's Iris/`wpColorPicker` to Coloris** (vanilla, MIT,
bundled locally). One shared `scripts.js` inits BOTH `color-picker` and `rgba-color-picker`. This is
the canonical reference — the gotchas below bit us; mirror them.

> **For palette-linked ELEMENT colours, don't reach for this.** Prefer
> `predefined-colors-color-picker-compact` (`sc_color_field_compact()`) so the colour stays tied to
> Theme Settings presets (see the root CLAUDE.md). Use raw `color-picker` only for **one-off** fields
> and **palette-definition** UIs (you can't pick a preset to define a preset).

## What it is
A `<input type="text" data-coloris>` whose value is a colour STRING. Coloris binds via a document
focus listener, so options rendered LATER (page-builder modals) work with no re-init — do NOT
re-call `Coloris()` on `fw:options:init`, re-processing inputs throws.

## Saved value shape
A plain colour **string** (NOT a `{predefined,custom}` hash). `_get_value_from_input`
(`class-fw-option-type-color-picker.php:112`) accepts a hex of **3 / 4 / 6 / 8 digits** — 4 & 8 carry
alpha. Empty stays empty; malformed falls back to the option default. With `alpha => true` it can
hold an 8-digit `#rrggbbaa`; the sibling `rgba-color-picker` stores `rgba(r,g,b,a)`.

## How to consume
Read `$value` as a colour string directly (hex, or `#rrggbbaa`/`rgba()` when alpha is on). No
resolver needed. Clicking a swatch stores the **resolved hex** — it is NOT live-linked to the preset
(that's the compact predefined-colors picker).

## Gotchas
- **`alpha` is opt-in.** Default `alpha => false` (`_get_defaults`, `:148`) — most colours (text
  colour, etc.) have no opacity. Pass `'alpha' => true` on the option to add the slider. `palettes`
  defaults `true` (the live Color Presets, ≤~60, localized as `fwColorisSwatches` → `data-swatches`;
  an explicit array overrides, `false` hides). Popup is 260px wide.
- **Don't remove the `wp-color-picker` enqueue dep** (`:61`). Coloris doesn't use it — it's kept
  purely for COMPAT because legacy siblings (box-shadow, gradient-v2, rgba's old paths) still call
  jQuery `.wpColorPicker()`. Drop it and they fatal with **"wpColorPicker is undefined"**.
- **Init runs on `window.load`, NOT `DOMContentLoaded`** (`scripts.js:110`). Coloris builds its
  picker on its own DOM-ready pass; configuring earlier throws **"className of undefined"**. And
  `wrap:false` is used so detached builder-template inputs don't choke it (the input's colour preview
  is painted manually instead).
- **Alpha slider leaking onto a PLAIN field** (shows up after an rgba/alpha picker was opened first):
  fixed by separate scoped `setInstance` calls — plain = hex/no-alpha via the
  `:not([data-alpha="1"])` selector (`CP_PLAIN`, `:20`/`:77`), alpha = hex+alpha (`:80`), rgba =
  rgb+alpha (`:83`) — PLUS a `focusin` force-toggle of `.clr-alpha` (`:87`). If alpha leaks onto a
  plain field, that selector + force-toggle is the layer to fix, not the global config.
- **Flipping an existing option to `alpha => true` is safe** — the value is already a string, which
  tolerates the extra 2 hex digits. No migration needed (unlike the multi-picker value-shape trap).

## Canonical snippet
```php
// Opaque one-off colour:
'my_color' => [ 'type' => 'color-picker', 'label' => __( 'Color', 'fw' ), 'value' => '#000000' ],

// With opacity (opt-in alpha slider):
'my_color' => [ 'type' => 'color-picker', 'alpha' => true, 'value' => 'rgba(0,0,0,.5)' ],
```
