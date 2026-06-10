# Option Type: `spacing`

Composite spacing widget (Margin + Padding) with a compact, Elementor-style
layout: a Phone / Tablet / Desktop switcher in the header, then one inline row
per section. Each row has a link toggle — linked shows a single "All" select,
unlinked shows four Top / Right / Bottom / Left selects.

Saved value is a nested array of Bootstrap utility class names. The **base
(phone)** layer lives under `margin` / `padding` (e.g. `m-3`, `pt-2`) and, being
min-width:0 utilities, applies at every width. **Tablet** (≥768px) and
**Desktop** (≥992px) overrides live under `advanced.md` / `advanced.lg` and carry
Bootstrap's responsive infix (e.g. `m-md-3`, `pt-lg-2`). The cascade is
mobile-first and Bootstrap-native — larger screens inherit the smaller layer
unless explicitly overridden.

The device switcher reuses the shared component in
[framework/includes/device-tabs.php](../../device-tabs.php) +
[framework/static/js/fw-device-tabs.js](../../../static/js/fw-device-tabs.js),
which keeps the tabs in sync with the page builder's global device toggle
(`window.fwPbDevice` / the `fw:builder:device-preview` event).

Choices are generated from the type's own spacing scale, which defaults to
Bootstrap 5's `$spacers`. Themes / plugins can swap in a different scale via the
`fw_option_type_spacing_scale` filter — the type itself has no compile-time
dependency on any code outside this folder (no shortcodes-extension helpers, no
theme preset getters).

## Usage

In any `options.php`:

```php
'spacing' => array(
    'type'  => 'spacing',
    'label' => __( 'Spacing', 'unysonplus' ),
    'desc'  => __( 'Margin + padding for the wrapper element.', 'unysonplus' ),

    // Optional. 'both' (default) | 'margin' | 'padding'.
    // When set to a single mode, only that column renders; the inactive
    // subtree is force-reset to defaults on save (a tampered POST can't
    // sneak values in via the hidden side).
    'mode'  => 'both',

    // Optional. Same shape as _get_defaults() — partial values are merged in.
    'value' => array(
        'margin'  => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
        'padding' => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
    ),

    // Optional. Whatever string FW's option renderer should put in the
    // help icon. The type doesn't impose any helper — pass a plain string
    // or your own theme's help URL builder.
    'help'  => '',
),
```

### `mode` examples

```php
// Margin only (single-column widget)
'wrapper_margin' => array(
    'type'  => 'spacing',
    'mode'  => 'margin',
    'label' => __( 'Margin', 'unysonplus' ),
),

// Padding only
'wrapper_padding' => array(
    'type'  => 'spacing',
    'mode'  => 'padding',
    'label' => __( 'Padding', 'unysonplus' ),
),
```

## Output / rendering

The saved value is a nested array of Bootstrap-utility class names. To apply on
the frontend, collect the non-empty leaves from the base layer **and** the
per-device overrides, then append them to your wrapper element's `class`
attribute. In the shortcodes extension, `sc_flatten_spacing_value()` does exactly
this — prefer it over hand-rolling the walk.

```php
$spacing = fw_get_db_settings_option( 'wrapper_spacing' ); // your saved option
$classes = array();

// Walk the base layer plus the md / lg override layers.
$layers = array( $spacing );
if ( ! empty( $spacing['advanced'] ) && is_array( $spacing['advanced'] ) ) {
    foreach ( array( 'md', 'lg' ) as $dev ) {
        if ( isset( $spacing['advanced'][ $dev ] ) ) { $layers[] = $spacing['advanced'][ $dev ]; }
    }
}

foreach ( $layers as $layer ) {
    foreach ( array( 'margin', 'padding' ) as $section ) {
        if ( empty( $layer[ $section ] ) || ! is_array( $layer[ $section ] ) ) { continue; }
        foreach ( $layer[ $section ] as $val ) {
            $val = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $val );
            if ( $val !== '' ) { $classes[] = $val; }
        }
    }
}

$class_attr = $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';
echo '<div' . $class_attr . '>…</div>';
```

The actual CSS rules that turn `m-3`, `pt-2`, etc. into real `margin` and
`padding` declarations are NOT shipped by this option type — that's a
deliberate separation of concerns. Theme code is expected to emit those rules
(Bootstrap 5 ships them out of the box; in this project,
[framework/includes/css-tokens.php](../../../includes/css-tokens.php) generates
them from the same scale).

## Customising the scale

The type defaults to Bootstrap 5's `$spacers`:

| Slug | Size      |
|------|-----------|
| 0    | `0`       |
| 1    | `0.25rem` |
| 2    | `0.5rem`  |
| 3    | `1rem`    |
| 4    | `1.5rem`  |
| 5    | `3rem`    |

To override site-wide — for example, to mirror a custom design-token scale —
hook the `fw_option_type_spacing_scale` filter and return your own array.
Each entry is `array( 'name' => slug, 'size' => CSS length )`. The slug
becomes the second half of the utility class (`m-{slug}`, `pt-{slug}`, …);
the size is shown in parentheses next to the slug in the dropdown.

```php
add_filter( 'fw_option_type_spacing_scale', function ( $default_scale ) {
    return array(
        array( 'name' => 'xs',   'size' => '4px'  ),
        array( 'name' => 'sm',   'size' => '8px'  ),
        array( 'name' => 'md',   'size' => '16px' ),
        array( 'name' => 'lg',   'size' => '24px' ),
        array( 'name' => 'huge', 'size' => '64px' ),
    );
} );
```

After hooking the filter, the corresponding utility classes
(`m-xs`, `mt-md`, `p-huge`, …) still need to exist in your stylesheet —
the option type only generates the dropdown values, not the CSS that
makes them visible.

## Layout sketch

```
                                        ┌ 🖥 📱 📲 ┐  ← device switcher
Margin   🔗 [ All ▼ ]          (linked, the single value applies to all sides)
Padding  ⛓ [T▼][R▼][B▼][L▼]   (unlinked, per-side)
```

Only the active device's panel is shown; the link toggle flips each row between
the single "All" select and the four side selects.

## Value shape

```php
array(
    // Base / phone layer — Bootstrap min-width:0 utilities (apply at all widths).
    'margin'   => array(
        'all'    => 'm-3',   // e.g. 'm-0', 'm-1', '' for default
        'top'    => '',
        'right'  => '',
        'bottom' => '',
        'left'   => '',
    ),
    'padding'  => array(
        'all'    => '',
        'top'    => 'pt-2',
        'right'  => '',
        'bottom' => '',
        'left'   => '',
    ),
    // Per-device overrides (mobile-first): md ≥768px, lg ≥992px. Values carry
    // Bootstrap's responsive infix (m-md-3, pt-lg-2), emitted as @media
    // (min-width) utilities by css-tokens.php.
    'advanced' => array(
        'md' => array(
            'margin'  => array( 'all' => 'm-md-4', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
            'padding' => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
        ),
        'lg' => array(
            'margin'  => array( 'all' => 'm-lg-5', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
            'padding' => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
        ),
    ),
)
```

When `mode` is `'margin'` or `'padding'`, the inactive subtree stays at the
empty defaults regardless of what was submitted (across every device layer).
