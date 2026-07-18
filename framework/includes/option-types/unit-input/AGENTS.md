# `unit-input` option type — the CONTRACT (submits JSON, saves a hash)

The #1 gotcha: the control **submits its value as a JSON STRING**, and `_get_value_from_input`
**decodes** it back to a `[ 'value', 'unit' ]` hash (`class-fw-option-type-unit-input.php:126-154`,
the decode is `class-fw-option-type-unit-input.php:127-132`). So the **saved** value is the decoded
hash — but anything reading the *raw submitted input* (pre-normalization) sees a JSON string.
**Always consume the SAVED (decoded) value, never the raw input.**

## What it is
A numeric field + a small unit dropdown (px / em / rem / % / vh / …). Config keys
(`class-fw-option-type-unit-input.php:42-51`):
- `units` — allowed units. Sequential list `[ 'px', 'em' ]` (label == value) OR assoc map
  `[ 'px' => 'Pixels' ]` (value => label); normalized by `normalize_units` (`:64-87`).
- `separate` — `bool`, affects `to_string()` only: `false` → `"24px"`, `true` → `"24 inches"`.
- `min`, `max`, `step` — optional numeric input attrs (`''` = omit; missing `step` → `"any"`).

## Saved value shape
Always a hash: `[ 'value' => '24', 'unit' => 'px' ]` (`:44`, `:153`). `value` may be `''` (blank),
an int, a decimal, or leading-minus; junk is coerced to `''` (`:143-144`). `unit` is forced to one
of the configured units, else the first (`:147-151`).

## How to consume
Use the static helper — it builds `value . unit` and **already guards the empty case** (returns `''`
when the number is blank, `:163-174`):

```php
echo FW_Option_Type_Unit_Input::to_string( $saved, $separate ); // '' when blank — no bare "px"
```

Never hand-concatenate without the blank guard (`$v['value'] === ''` → emit nothing / a default, not
a lone `px`). For a size that might ALSO be a **legacy int or JSON string** (e.g. typography `size`),
use `fw_typography_size_css()` — it tolerates all three shapes.

## Gotchas
- **Consume the decoded hash, not the raw input** (see the top note). A consumer reading the JSON
  string instead of the saved array gets `"{\"value\":\"24\"…}"`, not `24px`.
- **Inside `multi-inline`:** a `unit-input` child passes its `units` / `separate` / `min` / `max` /
  `step` through, so e.g. a border-width row behaves exactly like a standalone unit-input.
- **Migration (scalar → unit-input) is a value-shape change.** An existing bare number/string option
  flipped to `unit-input` won't match the hash shape — same class of trap as the multi-picker
  editor-load migration: a page-builder item opens its modal with the **RAW saved atts**, so a legacy
  scalar reaches the render. Tolerate the scalar in the consumer (treat as
  `[ 'value' => n, 'unit' => <default> ]`), and add a **JS-side migrator** in the item's `scripts.js`
  for pre-existing items (a PHP migration alone doesn't fix the open modal).

## Canonical snippet
```php
'min_height' => [
    'type'  => 'unit-input',
    'label' => __( 'Min height', 'fw' ),
    'units' => [ 'px', 'vh', 'rem' ],
    'value' => [ 'value' => '', 'unit' => 'px' ],
],
```
