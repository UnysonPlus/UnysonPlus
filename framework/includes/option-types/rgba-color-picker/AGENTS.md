# `rgba-color-picker` option type — the CONTRACT

## What it is
A color field that emits an **`rgba()`** string (color + opacity). It is now a **thin variant of
`color-picker`** — both use Coloris. `_enqueue_static` **delegates**: it calls
`fw()->backend->option_type( 'color-picker' )->enqueue_static();` (loads Coloris + the shared init)
and adds only its own preview/layout `styles.css` — see `class-fw-option-type-rgba-color-picker.php:25`
and `:28`. The shared `color-picker/static/js/scripts.js` initializes this input too. `_render`
emits `data-coloris` with **alpha ON** (`:52`) — unlike `color-picker`, where alpha is opt-in — and
the swatch popup is 260px wide. Backend width type is `auto` (`:12`).

## Saved value shape
A **color STRING** — an `rgba()` (or `rgb()` when the pick is fully opaque), and hex 3/4/6/8 is also
accepted. NOT a `{ predefined, custom }` object. Default `value` is `''` (`:89`).

## How to consume
Use the string **directly as a CSS color** — inline `style`, a CSS custom property, etc. No
resolver needed (it's already a literal color).

## Gotchas
- **Opaque picks emit `rgb()` (3 components), not `rgba()`.** Coloris (`format:'rgb'`) drops the
  alpha when a color is fully opaque, so the validator MUST accept optional alpha — `rgba?` with the
  4th component optional (`_get_value_from_input` at `:74`). A regex demanding a 4th component would
  **reject a legitimate opaque pick on save** and silently revert to the default.
- The validator (`:72`–`:74`) accepts `rgb()`/`rgba()` **plus** 3/4/6/8-digit hex; anything else
  falls back to `$option['value']`. Empty string is allowed through (intentional — see the
  Unyson #2025 note at `:66`).
- **Largely redundant now.** `color-picker` with `'alpha' => true` also produces `rgba()`. This type
  is kept for **back-compat** and call sites that explicitly want rgba output. **Prefer
  `color-picker` + `'alpha' => true` in NEW code.**

## Canonical snippet
```php
'overlay' => [
    'type'  => 'rgba-color-picker',
    'label' => __( 'Overlay', 'fw' ),
    'value' => 'rgba(0,0,0,.5)',
],
```
