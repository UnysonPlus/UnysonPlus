# `multi-inline` option type — how to USE it (add a child = add a render branch)

The #1 gotcha: the SAVE path is generic but the RENDER path is a **hardcoded whitelist**. Add a
child type whose control the whitelist doesn't know and it saves fine yet renders as a **blank row,
no error**. This is the contract — read it before adding a sub-option.

## What it is
N small controls side-by-side on ONE row (a border's Width · Style · Color, or a pair of icon
pickers Open · Close). Each control's caption sits BELOW it (muted italic). Registered as
`multi-inline`; `_get_backend_width_type()` returns `'auto'` (`class-fw-option-type-multi-inline.php:91`).
Real-world use: Theme Settings → Header → Layout → "Trigger & Close Icons".

## Saved value shape
A hash keyed by sub-option id — `[ 'open' => <icon-v2 value>, 'close' => <icon-v2 value> ]`. The
sub-options are declared under **`fw_multi_options`** (NOT `options`), and the top-level `value` seeds
each key's default.

## How to consume
`_get_value_from_input` is **generic** (`:111`): it runs each child through its own option type's
`get_value_from_input` via the `child_type()` alias map (`:46`), so **saving + `_enqueue_static`
already support ANY child type**. Only `view.php`'s RENDER is whitelisted. That asymmetry is exactly
why a brand-new child type **saves fine but shows blank** — the value is correct, the control just
never drew.

## Gotchas
1. **Blank row = child type not in the render whitelist.** `view.php` only renders these types:
   `short-text`, `text`→short-text, `color`→color-picker, `rgbacolor`→rgba-color-picker,
   `short-select`, `select`, `unit-input`, `predefined-colors-color-picker-compact` / `compact-color`,
   `icon-v2` / `icon` (`view.php:45-145`). Anything else falls through to `if ( $field === '' ) continue;`
   (`:147`) → invisible, no error. **FIX: add a render branch in `view.php`** for that child type;
   the save + enqueue paths are already generic, so nothing else changes.
2. **Caption comes from `$cfg['title']`, NOT `'label'`** (`view.php:32`, `:154`). Use `'label'` and the
   control renders with NO caption. (Both the Border row and the Open/Close icon row use `'title'`.)
3. **Per-child required config:** `select` / `short-select` need `'choices'` (missing → renders empty);
   `unit-input` passes through `units` / `separate` / `min` / `max` / `step`; `compact-color` passes
   through `picker` / `choices`.

## Alias
The legacy `fw-multi-inline` type (off-convention `fw-` prefix, `FwMultiInline` class) is the same
behavior — **USE `multi-inline` in new code** (`class-fw-option-type-multi-inline.php:17-22`).

## Canonical snippet
The Open/Close icon row:

```php
'trigger_icons' => [
    'type'  => 'multi-inline',
    'value' => [ 'open' => '', 'close' => '' ],
    'fw_multi_options' => [
        'open'  => [ 'type' => 'icon-v2', 'title' => __( 'Open', 'fw' ) ],   // caption via 'title'
        'close' => [ 'type' => 'icon-v2', 'title' => __( 'Close', 'fw' ) ],
    ],
],
```
