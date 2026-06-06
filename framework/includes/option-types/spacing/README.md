# Option Type: `spacing`

Composite spacing widget (Margin + Padding) with a plus-cross layout. Each
section has an "All Sides" select on top, and a Top / Right / Bottom / Left
quadrant arranged like a `+` so the position of each input matches the CSS
axis it controls.

Saved value is a nested array of Bootstrap utility class names
(e.g. `m-3`, `pt-2`). Choices are generated from the type's own spacing
scale, which defaults to Bootstrap 5's `$spacers`. Themes / plugins can swap
in a different scale via the `fw_option_type_spacing_scale` filter — the
type itself has no compile-time dependency on any code outside this folder
(no shortcodes-extension helpers, no theme preset getters).

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

The saved value is a nested array of Bootstrap-utility class names. To apply
on the frontend, collect the non-empty leaves and append them to your wrapper
element's `class` attribute.

```php
$spacing = fw_get_db_settings_option( 'wrapper_spacing' ); // your saved option
$classes = array();

foreach ( array( 'margin', 'padding' ) as $section ) {
    if ( empty( $spacing[ $section ] ) || ! is_array( $spacing[ $section ] ) ) {
        continue;
    }
    foreach ( $spacing[ $section ] as $val ) {
        $val = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $val );
        if ( $val !== '' ) { $classes[] = $val; }
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
┌──────────── Margin ───────────┬──────────── Padding ──────────┐
│        [ All sides ▼ ]        │        [ All sides ▼ ]        │
│   Applies to all sides…       │   Applies to all sides…       │
│                               │                               │
│           [ Top ▼ ]           │           [ Top ▼ ]           │
│ [ Left ▼ ]      [ Right ▼ ]   │ [ Left ▼ ]      [ Right ▼ ]   │
│          [ Bottom ▼ ]         │          [ Bottom ▼ ]         │
└───────────────────────────────┴───────────────────────────────┘
```

Below ~600px the two columns stack vertically (single-column widget).

## Value shape

```php
array(
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
    'advanced' => array(), // reserved for v2 (e.g. per-breakpoint values)
)
```

When `mode` is `'margin'` or `'padding'`, the inactive subtree stays at the
empty defaults regardless of what was submitted.
