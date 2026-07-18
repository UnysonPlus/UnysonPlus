# `typography` option type — the CONTRACT (canonical vs. `-v2` alias)

Rich typography control: font **family / style / weight / size / line-height / letter-spacing /
color** (+ Google-font subset & variation). `typography` is now the CANONICAL implementation —
it was PROMOTED from the old `typography-v2`. Prefer `'type' => 'typography'` in new code.

## What it is
`FW_Option_Type_Typography` (`class-fw-option-type-typography.php:21`) is the real class.
`typography-v2` is now a **thin deprecation ALIAS**: `FW_Option_Type_Typography_v2 extends
FW_Option_Type_Typography` whose ONLY override is `get_type()` returning `'typography-v2'`
(`class-fw-option-type-typography-v2.php:21-27`). Both types render + save identically.
- **Why it works:** the class pins `const ASSET_BASE = 'typography'` (`:28`) and uses it in
  `_enqueue_static` (`:41,52`) and `_render`'s view path (`:114`) INSTEAD of `$this->get_type()`
  — so the alias reuses the same CSS/JS/view instead of hunting under `typography-v2/`.
- **Migration:** the type string is NOT stored, so `typography-v2` saved values load unchanged
  under `typography`. **No data migration needed.**

## Saved value shape
An array (`_get_defaults` `:214-225`):
`[ 'family', 'style', 'weight', 'size', 'line-height', 'letter-spacing', 'color', 'google_font',
'subset', 'variation' ]`. Two **format options** change two slots:
- `'size_format' => 'unit'` (the DEFAULT — `:208`) → the Size slot is a **unit-input**, so `size`
  saves as `{ value, unit }` (input submits it as a JSON string, decoded in
  `_get_value_from_input` `:137-144`). `'number'` keeps the legacy **bare px integer** (use it
  where a consumer needs a raw pixel NUMBER, e.g. JS-fed cursor sizes).
- `'color_format' => 'picker'` (the DEFAULT — `:213`) → the Color slot is the color-picker
  (swatch row already surfaces Color Presets); stores a hex.

## How to consume
- **Size** may be a **legacy int**, a **`{ value, unit }` hash**, OR a **JSON string** (`unit`
  format). ALWAYS resolve with **`fw_typography_size_css( $size, $default_unit = 'px' )`**
  (`framework/helpers/general.php:2322`) — it handles all three and returns a CSS length.
  Do **NOT** concatenate `$size . 'px'` — it breaks for the array/JSON shapes.
- Other slots are plain: `color` = hex, `family`/`weight`/`line-height`/`letter-spacing` as saved.

## Gotchas
- **CSS wrapper selector must stay `[class*="fw-option-type-typography"]`** (`styles.css:1,10,…`)
  — the substring match hits BOTH the canonical `.fw-option-type-typography` wrapper AND the
  alias `.fw-option-type-typography-v2`. If you scope new CSS to `.fw-option-type-typography-v2`
  ONLY, the canonical option renders **unstyled / broken** (this actually happened during the
  promotion). Never narrow the selector.
- The `unit` Size field needs a wider slot — scoped via `[data-size-format="unit"]`
  (`styles.css:310-316`); don't reuse the narrow `number` width for it.
- Changing an EXISTING `number`-format option to `unit` is a value-shape change (int → hash) —
  the view/consumer tolerates the legacy int, but resolve everywhere through
  `fw_typography_size_css()` so old saves keep working.

## Canonical snippet
```php
'heading' => [
    'type'         => 'typography',
    'label'        => __( 'Heading', 'fw' ),
    'size_format'  => 'unit',   // size saves as { value, unit }
    'color_format' => 'picker', // color-picker with preset swatches
],
```
