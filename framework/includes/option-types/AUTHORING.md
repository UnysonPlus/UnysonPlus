# Authoring an option type — the shared build contract

How to **code** an option type (not how to *use* one — for that, see `AGENTS.md` here and the
per-folder deep-dives). All 51 types share this lifecycle; only the value shape + gotchas differ.
Read this before writing or editing an option-type class. Base class:
`framework/core/extends/class-fw-option-type.php`.

## Anatomy of a folder

```
option-types/<type>/
  class-fw-option-type-<type>.php   ← the class (extends FW_Option_Type) + register() call
  view.php                          ← markup (rendered by _render via fw_render_view)
  static/
    css/styles.css                  ← enqueued in _enqueue_static
    js/scripts.js                   ← enqueued in _enqueue_static
```

`view.php` is optional (small types build HTML inline in `_render`). The folder name should equal
`get_type()` — the loader resolves types by folder.

## The methods you override

| Method | Required | Contract |
|---|---|---|
| `get_type()` | **yes** (abstract) | Return the type string. NOT stored in the DB — it lives only in the schema, so renaming is migration-free. |
| `_render($id, $option, $data)` | **yes** (abstract) | Return HTML. `$option` is already merged with `_get_defaults()`; `$data = { value, id_prefix, name_prefix }`. The `value` is the saved value (post-`_get_value_from_input`). See `:39-47`. |
| `_get_value_from_input($option, $input_value)` | **yes** (abstract) | **Normalize the submitted form value into the stored shape.** This is where a JSON-string decodes to `{value,unit}`, a select is validated against choices, etc. Return `$option['value']` when input is empty. See `:49-58`. |
| `_get_defaults()` | **yes** (abstract) | Return the option's default array, incl. the default `value`. Lets a schema author write just `[ 'type' => '<type>' ]`. See `:60-74`. |
| `_enqueue_static($id, $option, $data)` | no | Enqueue CSS/JS. Called **once per request by default**; return `true` to be called again per-instance (only if you depend on per-option params). See `:24-37`. |
| `_get_data_for_js($id, $option, $data)` | no | Data exposed to JS per instance (default: `{ option }`). See `:76-83`. |
| `_get_backend_width_type()` | no | `'auto'` (shrink-to-fit) or `'fixed'` (full column). Default `'fixed'`. See `:513`. |

Register at the bottom of the class file, guarded so a stale theme-side copy can't fatal:

```php
if ( ! class_exists( 'FW_Option_Type_<Type>' ) ) :
class FW_Option_Type_<Type> extends FW_Option_Type { /* … */ }
FW_Option_Type::register( 'FW_Option_Type_<Type>' );
endif;
```

## The value contract (where bugs live)

- **`_get_value_from_input` is the single normalization point.** Consumers read the value it returns —
  never the raw `$_POST`. If your control posts a compound value as a JSON string (unit-input does),
  decode it HERE so the stored shape is the hash, not the string.
- **Changing an existing option's TYPE is a value-shape change.** The type isn't stored, so renaming a
  type needs no migration — but a `select` (string) → `multi-picker` (array) DOES: tolerate the legacy
  shape in `_render` + the consumer, and for page-builder items add a JS editor-load migrator (the item
  modal opens with RAW saved atts; `_get_value_from_input` does NOT run on normal editor load). See
  `multi-picker/AGENTS.md` → "Editor-load migration gotcha".
- **Composite types delegate.** When your type hosts child options, run each child through its own
  `get_value_from_input` (see `multi-inline`'s generic loop) rather than trusting the raw sub-input.

## Rendering + the wrapper class

`_render` returns HTML; use `fw_render_view( __DIR__ . '/view.php', [ 'id'=>…, 'option'=>…, 'data'=>… ] )`
for anything non-trivial. The backend wraps your output in
**`.fw-option fw-option-type-{type}`** automatically (`class-fw-option-type.php:186`) — the class is
derived from the schema `type`, NOT your PHP class name. Scope your CSS to that wrapper. For a type
that has an **alias** (see below), scope to `[class*="fw-option-type-{base}"]` so both the canonical
and alias wrappers match (this bit the typography promotion).

## Assets — and the min-asset trap ⚠

`_enqueue_static` should build URIs with **`fw_get_framework_asset_uri( '/includes/option-types/<type>/static/...' )`**,
which serves the **`.min` sibling** when the source path is listed in `framework/build-manifest.php`
and `SCRIPT_DEBUG` is off (`framework/helpers/general.php:200-222`).

**Consequence: for any asset in the manifest, editing the non-min source alone has NO visible effect —
the framework serves the `.min`.** There is currently **no `build.mjs` on disk** to regenerate them, so
you must **hand-patch BOTH the source and its `.min` sibling** (144 option-type assets are in the
manifest). Symptom of forgetting: your CSS/JS change "does nothing" in the browser. To confirm whether a
path is minified: `grep '<type>/static' framework/build-manifest.php`.

To depend on another type's assets, delegate instead of hardcoding a URL — e.g. rgba-color-picker does
`fw()->backend->option_type( 'color-picker' )->enqueue_static();`. Enqueues dedupe by handle.

## JS init pattern

Namespace the handle `fw-option-<type>` (matches `_enqueue_static`). If your control is powered by a
vendor lib that builds its own DOM on ready (Coloris did), **initialize on `window.load`, not
`DOMContentLoaded`** — configuring before the lib's own ready threw "className of undefined". Also set
the lib to tolerate detached inputs (builder templates render option HTML off-DOM) — Coloris used
`wrap:false`. Fire `fwEvents` where legacy consumers listen.

## The alias pattern (deprecating a type name)

To rename a type without breaking saved data or duplicating code, make the old name a **thin subclass**:

```php
class FW_Option_Type_Typography_v2 extends FW_Option_Type_Typography {
    public function get_type() { return 'typography-v2'; }
}
```

Keep a `const ASSET_BASE = '<canonical>'` on the canonical class and use it (instead of `get_type()`)
in `_enqueue_static`/`_render` so the alias reuses the same `static/` assets. Saved values load
unchanged (type isn't stored). Examples: `typography-v2`→`typography`, `fw-multi-inline`→`multi-inline`.

## Checklist for a NEW option type

1. Folder `option-types/<type>/`, class extends `FW_Option_Type`, `get_type()` returns `<type>`.
2. Implement the four abstracts; `_get_defaults()` must include a sensible `value`.
3. `_get_value_from_input` returns the **exact stored shape** and tolerates empty → `$option['value']`.
4. `view.php` + `static/css|js`; enqueue via `fw_get_framework_asset_uri()`.
5. `FW_Option_Type::register(...)` inside a `class_exists` guard.
6. Add a row to this folder's `AGENTS.md` index (value shape!); write a deep-dive **only if** the
   contract isn't obvious from the code.
7. If it will hold sub-options of other types, delegate save/enqueue/render per child.
8. Test save→reload→re-render: the value must round-trip and the modal must reopen without error.

## Gotchas recap

- Non-min edit "does nothing" → the manifest is serving the `.min`; patch the min too.
- New sub-option in a composite renders blank → the composite's `view.php` render whitelist doesn't
  know that child type (see `multi-inline/AGENTS.md`); the save path being generic hides it.
- Vendor-lib control throws on load → init on `window.load`, tolerate detached inputs.
- CSS unstyled after a rename → wrapper class is `fw-option-type-{type}`; use `[class*=…]` for aliases.
- Changed a type on an existing option → value-shape migration + a JS editor-load migrator for builder
  items.

Cross-cutting product rules (color presets, typography, multi-picker label placement, the metabox-holder
settings layout) live in the workspace `CLAUDE.md` — link, don't duplicate.
