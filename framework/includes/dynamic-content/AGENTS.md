# Dynamic Content (`framework/includes/dynamic-content/`)

Elementor-Pro-style "dynamic tags" for Unyson+. An editor inserts a token into a Text /
Short Text / Textarea / Rich Editor option; at render time the token becomes a live value
(post title, site name, current year, a custom field, a WooCommerce product field, …).

The feature is branded **"Dynamic Content"** (code key: `dynamic_content`).

## Token syntax

```
{{tag_id}}                                  simple
{{tag_id|param=value|fallback=Some text}}   parameterized, optional fallback
```

- Double braces + pipe-delimited params. Collision-resistant and survives
  `wp_kses_post()` / `esc_attr()` untouched, so the consuming view's existing escaping
  applies to the **resolved** value (the resolver only ever returns plain strings).
- `fallback` is a reserved param: used when the tag resolves to an empty string.
- Unknown tags are left literally in the output — never a fatal, never a blank.

Examples: `{{site_name}}`, `Welcome to {{site_name}} — © {{copyright_year}}`,
`{{post_meta|key=subtitle|fallback=N/A}}`, `{{current_date|format=Y}}`.

## Architecture (one registry, two consumers)

- `class-fw-dynamic-content.php` — `FW_Dynamic_Content` singleton, accessed via
  `fw_dynamic_content()`. The single source of truth.
  - `get_tags()` — builds the registry once via `apply_filters('fw:dynamic-content:tags', [])`.
  - `resolve($text, $context)` / `resolve_recursive($value, $context)` — replace tokens.
  - `get_tags_for_js()` — grouped, callback-free list for the picker.
  - `trigger_html()` — the picker icon markup.
- **Admin picker** (`static/js/dynamic-content.js` + `static/css/dynamic-content.css`):
  the icon is appended centrally in `FW_Option_Type::render()` for any option whose
  definition has `dynamic_content => true` (default on the four field types). The JS reads
  `data-fw-option-type` / `data-fw-option-id` off the `.fw-backend-option-descriptor`
  wrapper, opens a searchable popover, and inserts the `{{token}}`.
- **Frontend resolver**: registered in the shortcodes extension at
  `extensions/shortcodes/includes/dynamic-content-resolver.php`, hooked on
  `fw_shortcode_render_view:atts` (fires right before the view renders/escapes). Scope =
  shortcodes + page-builder.

## Enabling / disabling per field

On by default for `text`, `short-text`, `textarea`, `wp-editor`. To hide the picker on a
specific field, set `'dynamic_content' => false` in its option array. To enable it on some
other (custom) text-like option type, add `'dynamic_content' => true` to that type's
`_get_defaults()`.

## Adding a tag provider later (e.g. ACF / Pods / Toolset)

One file, one filter — no changes to the class, the picker, or the resolver:

```php
// framework/includes/dynamic-content/tags/acf.php  (then require it in the class's
// load_bundled_tags(), or add_filter from anywhere that loads before first use)
add_filter( 'fw:dynamic-content:tags', function ( $tags ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $tags; // ACF inactive — group stays invisible
    }
    $tags['acf_field'] = array(
        'label'   => __( 'ACF Field', 'fw' ),
        'group'   => __( 'ACF', 'fw' ),
        'params'  => array(
            array( 'id' => 'name', 'label' => __( 'Field name', 'fw' ), 'type' => 'text', 'default' => '' ),
        ),
        'resolve' => function ( $params, $context ) {
            $post_id = ! empty( $context['post_id'] ) ? (int) $context['post_id'] : get_the_ID();
            $val = get_field( $params['name'], $post_id );
            return is_scalar( $val ) ? (string) $val : '';
        },
    );
    return $tags;
} );
```

Tag array shape: `label`, `group`, optional `params` (each
`array('id','label','type','default')`), and `resolve` — a callable
`function( array $params, array $context ): scalar`. `$context['post_id']` is the current
post when available; fall back to `get_the_ID()`.

Always guard provider availability **inside** the callback/filter (it runs after `init`),
not at file load — that keeps the group invisible when the integration is inactive (see
`tags/woocommerce.php` for the canonical example).

## Bundled tag files

- `tags/core.php` — Post, Site, Author, Date & Time.
- `tags/unysonplus.php` — `post_meta` (Unyson+ post option, falls back to native meta).
- `tags/woocommerce.php` — product fields, only when WooCommerce is active.

## Gotchas

- Resolvers must return a **scalar**; arrays/objects are coerced to `''`.
- Don't echo HTML from a resolver — return text; the consuming view escapes it.
- Param values are stripped of `{`, `}`, `|` by the picker to protect the token grammar.
- Resolution is scoped to the shortcode render path. To resolve a token elsewhere
  (a theme template), call `fw_dynamic_content()->resolve($text, array('post_id' => $id))`
  directly.
