# UnysonPlus build pipeline

Autoprefixes and minifies the plugin's static CSS/JS. Source files stay
readable and editable in place; this tooling writes `*.min.css` / `*.min.js`
siblings next to them, and the plugin enqueues the minified versions in
production.

## Usage

```bash
cd unysonplus/build
npm install      # once (downloads devDependencies into build/node_modules)
npm run build    # regenerate the .min files
```

Run `npm run build` again whenever you edit any source file listed in
`build.mjs` (the `CSS_FILES` / `JS_FILES` arrays).

## How the plugin chooses minified vs source

PHP enqueues go through `fw_get_framework_asset_uri()`
(`framework/helpers/general.php`):

- It serves the `.min` sibling when that file **exists** and `SCRIPT_DEBUG`
  is **off** (i.e. normal production).
- It falls back to the unminified source when the `.min` is missing or
  `SCRIPT_DEBUG` is on.

So the plugin works with or without a build — a missing `.min` never 404s,
it just serves the readable original. Define `SCRIPT_DEBUG` true in
`wp-config.php` to force the unminified source while developing.

## What gets processed

- **CSS** → PostCSS with **autoprefixer** (targets in `.browserslistrc`) +
  **cssnano**. Autoprefixer normalizes vendor prefixes for current browsers,
  so obsolete `-o-`/`-ms-`/`-webkit-` prefixes are dropped from the output.
- **JS** → **esbuild** `transform` (minify only, **not** bundled), preserving
  the framework's global-based load order and `wp_enqueue_script` dependency
  graph.

## Shipping / uploading (IMPORTANT)

`build/node_modules/` must **not** be part of the uploaded plugin zip — it's
dev-only and huge. When zipping `unysonplus/` for upload, exclude the
`build/node_modules/` directory (excluding the whole `build/` folder is fine
too; it has no runtime role). The committed `*.min.*` files under
`framework/static/` are what ship.

`node_modules/` and `package-lock.json` are already git-ignored here.
