---
type: option-type
name: background-pro
slug: background-pro
since: Unyson+ fork
last-verified-against: real theme-settings export, theme 2.1.42 (2026-06-02)
---

# Background Pro option type

Composite background field. Four stacking layers (bottom → top): **color → gradient → image →
video**. One field replaces separate color + image + gradient controls. Class:
`Fw_Option_Type_Background_Pro` at `class-fw-option-type-background-pro.php`.

## Declaration

```php
'my_bg' => array(
	'label' => __( 'Background', 'unysonplus' ),
	'type'  => 'background-pro',
),
```
Worked example: `unysonplus-theme/framework-customizations/theme/options/demo.php` (`demo_background_pro`).
Live usage: General Layout `site_background` (theme `general-layout.php`).

## Saved value shape (VERIFIED from a real export)

```json
{
  "color":    { "value": { "predefined": "#286090", "custom": "" } },
  "gradient": { "data": { "type": "linear", "angle": 90, "stops": [] } },
  "image":    { "src": [], "position": "center center",
                "size": { "selected": "cover", "custom": "" },
                "repeat": "no-repeat", "attachment": "scroll" },
  "video":    { "enabled": "no", "external_url": "", "source_mp4": [], "source_webm": [],
                "poster": [], "fallback": [], "loop": "no", "autoplay": "no",
                "mute": "no", "playsinline": "no" },
  "advanced": []
}
```

Notes from the verified sample:
- **`color.value.predefined` holds a usable color value (a hex like `#286090`), NOT a preset slug.**
  Resolve it with the theme's `unysonplus_get_option_color_picker( $value['color']['value'] )`
  (returns `predefined` if set, else `custom`) — the same resolver every other theme color uses.
- `image.src` is an empty array `[]` until an image is chosen; when set it's an upload value with a
  `url` key (read `image/src/url`).
- `gradient.data.stops` is `[]` when off; a gradient is "on" at **≥ 2 stops**. Each stop:
  `{ "color": "#hex|rgba()", "position": 0-100 }`.
- `size.selected` ∈ `auto|cover|contain|custom`; when `custom`, use `size.custom`.
- `repeat` ∈ `no-repeat|repeat|repeat-x|repeat-y|space|round`; `attachment` ∈ `scroll|fixed|local`.

## Turning a value into CSS (no built-in helper)

The option type does **not** ship a `to_css()`. Consumers build CSS themselves. The reference
implementation is the theme's site background in
`unysonplus-theme/inc/includes/theme-vars.php` (the `site_background` block), which:
- emits `--site-bg-color` from the color layer (via the resolver above);
- stacks `background-image` as `url(image), gradient` (image over gradient), using
  `FW_Option_Type_Gradient_V2::to_css( value.gradient.data )` for the gradient;
- emits `--site-bg-position/-repeat/-size/-attachment` from the image sub-values;
- intentionally does **not** render the `video` layer (a full-bleed background video needs a
  `<video>` element + JS, which is out of scope for a CSS-vars consumer).

Enable logic for a consumer: color when `predefined||custom`; gradient when `count(stops) >= 2`;
image when `image.src.url`; video when `video.enabled === 'yes'`.

## Training note

This doc was filled from a verified theme-settings export (see frontmatter). When the value shape
changes, re-verify here. The page-builder template format + the "sample export → update docs"
workflow live in `../../extensions/shortcodes/extensions/page-builder/AGENTS.md`.
