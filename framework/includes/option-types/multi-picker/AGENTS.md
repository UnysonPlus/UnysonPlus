# `multi-picker` option type — how to USE it (label/desc placement)

The #1 recurring mistake with `multi-picker` is **where the label / desc / help go**. It depends on
whether the picker is **inline** or a **popover**. Get this wrong and the layout breaks (see the
symptom below). This is the canonical reference — mirror it exactly.

## The two shapes

### A. Inline multi-picker (a plain `select` / `switch` / `image-picker` picker, NO `popover`)
The user-visible **label / desc / help live on the PICKER sub-option**. The TOP level is
`label => false, desc => false`.

```php
'my_field' => array(
    'type'         => 'multi-picker',
    'label'        => false,   // ← TOP level: false
    'desc'         => false,   // ← TOP level: false
    'show_borders' => false,
    'value'        => array( 'kind' => 'a' ),
    'picker'       => array(
        'kind' => array(
            'type'    => 'select',
            'label'   => __( 'Style', 'fw' ),          // ← label HERE
            'desc'    => __( 'How it reads.', 'fw' ),  // ← desc HERE
            'value'   => 'a',
            'choices' => array( 'a' => 'A', 'b' => 'B' ),
        ),
    ),
    'choices' => array( 'a' => array( /* revealed options */ ), 'b' => array( /* … */ ) ),
),
```

### B. Popover multi-picker (`'popover' => true`, usually an `image-picker` of tiles)
The **OPPOSITE**: the label / desc / help live on the **TOP level**; the picker sub-option is
`label => false`. (This matches the Animation Engine effect pickers — Physics, Hover, Marquee, …)

```php
'my_field' => array(
    'type'         => 'multi-picker',
    'popover'      => true,
    'label'        => __( 'Physics', 'fw' ),          // ← label HERE (top level)
    'desc'         => __( 'A motion applied to this element.', 'fw' ),
    'help'         => __( '…', 'fw' ),
    'show_borders' => false,
    'value'        => array( 'effect' => 'none' ),
    'picker'       => array(
        'effect' => array(
            'type'    => 'image-picker',
            'label'   => false,   // ← picker: false
            'choices' => array( /* tiles */ ),
        ),
    ),
    'choices' => array( /* per-effect reveals */ ),
),
```

## The symptom (how you know you got an INLINE one wrong)
If you put the **label / desc on the TOP level of an inline multi-picker**, at render time:
- the **label sits oddly at the far left** (the multi-picker's own label column, not aligned with
  the option rows), and
- the **desc floats to the BOTTOM** of the whole block — *below* all the revealed option rows,
  instead of sitting under the picker control.

That out-of-place desc + misaligned label = you used shape A but wrote it like shape B. Move the
label/desc onto the picker sub-option.

## Value shape (unchanged by any of the above)
`array( '<picker_id>' => '<choice_key>', '<choice_key>' => array( <revealed values> ) )`. The
picker-id key stores the selection; each chosen key stores its revealed sub-values. Switching the
picker never loses the other choices' values.

## Other pitfalls (see the root CLAUDE.md for detail)
- Choice keys must be **non-empty** strings (use `'auto'`, never `''`).
- Converting an existing scalar option to a multi-picker is a **breaking value-shape change** —
  the editor loads raw saved atts, so a legacy string hitting the picker's `_render` throws
  *illegal string offset* → blank "error:" modal. Migrate JS-side in the item's `scripts.js` too.
- Wrap the picker with a leading **optgroup** (not a bare loose choice) if you need a choice to sit
  first in a `select` that also has optgroups — loose choices render *after* all optgroups.
