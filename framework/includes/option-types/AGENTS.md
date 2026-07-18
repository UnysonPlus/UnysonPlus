# Option types — index & contracts

This is the **map** of every option type in `framework/includes/option-types/`. Read it before
adding or wiring an option; it tells you the **saved value shape** (the #1 source of bugs) and
which types have a **deep-dive** `AGENTS.md` in their own folder for the gotchas.

> **Authoring or editing an option-type CLASS** (not just using one)? Read **`AUTHORING.md`** in this
> folder — the shared build contract: the `_render` / `_get_value_from_input` / `_enqueue_static`
> lifecycle, registration, the **min-asset trap**, the alias pattern, and a new-type checklist. This
> index + the deep-dives cover the **USE** contract; `AUTHORING.md` covers the **BUILD** contract.

## How option types work (the two things that bite)

1. **The type string is NOT stored.** Values are keyed by option **id**; the `type` lives only in
   the PHP schema. So **renaming a type needs no data migration** — but **changing an existing
   option's type is a value-SHAPE change** (e.g. `select` string → `multi-picker` array) and DOES
   need migration tolerance in the view/consumer, and often a JS editor-load migrator for
   page-builder items. See `multi-picker/AGENTS.md` → "Editor-load migration gotcha".
2. **`_get_value_from_input` is where the submitted shape is normalized** — a `unit-input` decodes
   its JSON string to `{value,unit}`, a compact color parses to `{predefined,custom}`, etc. When
   you consume a value, consume the **normalized** shape below, and tolerate the legacy shape if the
   option changed type.

Wrapper class in the DOM is **`.fw-backend-option-type-{type}`** (backend) / `.fw-option-type-{type}`
— derived from the schema type, not the PHP class name.

**Legend:** 📖 = has a deep-dive `AGENTS.md` in its folder · ⚠ = value-shape / usage gotcha, read the
deep-dive before using · *(alias)* = thin wrapper over another type.

## Scalars & text

| Type | Purpose | Saved value |
|---|---|---|
| `switch` | On/off toggle | the on/off value (configurable per option) |
| `radio-text` | Radio group of text choices | choice string |
| `code-editor` | CodeMirror code field | code string |
| `wp-editor` | WYSIWYG (TinyMCE) | HTML string |
| `oembed` | oEmbed URL | url string |
| `slider` | Single numeric slider | number |
| `range-slider` | Two-handle numeric range | `{ from, to }` |

## Color ⚠ (all swapped to Coloris — see `color-picker/AGENTS.md`)

| Type | Purpose | Saved value |
|---|---|---|
| `color-picker` 📖 | Hex/rgba color (Coloris; **alpha is opt-in** `'alpha' => true`) | color **string** (`#rgb`/`#rrggbb`/`#rrggbbaa`, or `rgba()`) |
| `rgba-color-picker` 📖 *(variant)* | Color that emits `rgba()`; delegates to `color-picker` | color string (`rgba()` or hex) |
| `predefined-colors-color-picker` ⚠ | Palette-preset dropdown + custom picker | `{ predefined, custom }` (preset wins) |
| `predefined-colors-color-picker-compact` 📖 | Compact preset + inline custom on one row (`sc_color_field_compact()`) | `{ predefined, custom }` |
| `predefined-colors` | Bare preset-slug select | preset-slug string |
| `gradient` | Simple two-stop gradient | `{ primary, secondary }` |
| `gradient-v2` ⚠ | Multi-stop gradient (stops still use wp-color-picker internally) | `{ type, angle, stops[] }` |

## Typography 📖 (see `typography/AGENTS.md` — `typography` is canonical, v2 is an alias)

| Type | Purpose | Saved value |
|---|---|---|
| `typography` 📖 | Font family/style/weight/size/line-height/spacing/color. Optional `size_format` (unit-input) & `color_format` (preset). | `{ family, style, weight, size, line-height, letter-spacing, color, google_font, subset, variation }` |
| `typography-v2` *(alias)* | Deprecated alias of `typography` (identical shape) | same as `typography` |

`size` may be an int (legacy), `{value,unit}`, or a JSON string — resolve it with
`fw_typography_size_css()` (in `framework/helpers/general.php`).

## Icons ⚠ (see `icon-v2/AGENTS.md`; consume via `sc_icon_render()`)

| Type | Purpose | Saved value |
|---|---|---|
| `icon-v2` 📖 | Icon picker (font / SVG / emoji / upload) | `{ type, icon-class, icon-class-without-root, pack-name, pack-css-uri }` |
| `icon-v3` *(variant)* | Newer icon picker, **same value shape** as icon-v2 | same as `icon-v2` |
| `icon` | Legacy Font-Awesome-class picker | icon-class **string** (`README.md` = data-update note) |

## Dimensions & layout

| Type | Purpose | Saved value |
|---|---|---|
| `unit-input` 📖 | Number + unit dropdown | `{ value, unit }` (submitted as a JSON string → decoded on save) |
| `position-box` | Top/Right/Bottom/Left, each with unit | `{ top,right,bottom,left: {value,unit} }` |
| `spacing` 📖 | Margin+Padding, responsive (Bootstrap utility classes) | nested `{ margin, padding, advanced:{md,lg} }` |
| `box-shadow` ⚠ | Shadow builder (color uses wp-color-picker) | `{ x, y, blur, spread, color, inset }` |
| `column-split` | Column-width fraction picker | fraction string (e.g. `"1/2"`) |
| `split-slider` | Draggable multi-segment widths | array of `{ w, name }` |

## Media & background

| Type | Purpose | Saved value |
|---|---|---|
| `upload` | Single file/image | attachment url/id |
| `multi-upload` | Multiple attachments | array of attachments |
| `background-image` | Single background image | url string |
| `background-pro` 📖 | Full background: color/gradient/image/video/overlay | large nested hash (see folder) |
| `map` | Google-map coordinate picker | `{ coordinates: { lat, lng } }` |

## Pickers & presets

| Type | Purpose | Saved value |
|---|---|---|
| `image-picker` ⚠ | Visual choice tiles. Supports **`'multiple' => true`** → array (e.g. border sides) | choice-key **string** (or array in multiple mode) |
| `multi-select` | Multi-value select | array of values |
| `border-style-picker` / `button-style-picker` / `table-style-picker` | Preset-slug selectors | slug string |
| `border-presets` / `button-presets` / `table-presets` | Palette-defined preset lists | array (preset refs) |
| `button-hover-animation` | Hover-animation key | animation-key string |

## Containers & composites ⚠

| Type | Purpose | Saved value |
|---|---|---|
| `multi` ⚠ | Generic container. **`fw_extract_only_options` does NOT descend into its inner-options** — walk `['inner-options']` manually when introspecting. | hash keyed by inner ids |
| `multi-picker` 📖 | Picker whose choice reveals sub-options | `{ <picker_id>: choice, <choice>: {…} }` |
| `multi-inline` 📖 | N small controls on ONE row (Width·Style·Color, or Open·Close icons) | hash keyed by sub-option id |
| `fw-multi-inline` *(legacy alias)* | Off-convention older name for `multi-inline` — **use `multi-inline` in new code** | same as `multi-inline` |
| `responsive` ⚠ | Wraps ONE inner control per breakpoint | `{ base, md, lg }` |
| `popover` | Inline-expanding container (no own value) | — (container) |
| `popup` | Modal-hosted set of options | hash keyed by `popup-options` ids |
| `addable-box` / `addable-option` / `addable-popup` | Repeatable rows (popup = edited in a modal; used by header/footer columns) | array of row hashes |

## Date & time

| Type | Purpose | Saved value |
|---|---|---|
| `date-picker` | Date | date string |
| `datetime-picker` | Date + time | datetime string |
| `datetime-range` | Two datetimes | `{ from, to }` |

## Writing / updating a deep-dive

A folder gets its own `AGENTS.md` **only when the contract isn't obvious from the code** — a hidden
whitelist, an alias relationship, a non-string value shape, a migration trap. Keep each one to:
**What it is · Saved value shape · How to consume (resolver/helper) · Gotchas · Canonical snippet.**
Document the *contract*, not the code — a prose restatement of the PHP is what goes stale. The
cross-cutting rules (color presets, typography, multi-picker label placement) live in the workspace
`CLAUDE.md`; link to it rather than duplicating, so the two never disagree.

The **build** knowledge is shared across all 51 types, so it lives ONCE in `AUTHORING.md`, not per
folder — put per-folder *implementation* gotchas (like multi-inline's render whitelist) in that
type's deep-dive, next to its USE contract.
