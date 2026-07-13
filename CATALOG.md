# UnysonPlus — Element & Animation Catalog

**Purpose:** the single source of truth for **everything the plugin already ships** — every
page-builder **shortcode/element** and every **Animation Engine** module, effect and animated
element. **Before proposing or building anything new, check this list first** — a new element,
module or effect must be genuinely DISTINCT (not a rename or near-duplicate of something here).
Keep this file updated when elements are added or removed.

_Last scanned: shortcodes ext 1.11.37 · animation-engine 1.1.83. Counts: 78 shortcodes, 21 engine
modules, 5 engine shortcodes._

> Effect-level detail for the Animation Engine lives in
> `framework/extensions/animation-engine/CATALOG.md` — this file summarizes it and adds the full
> shortcode inventory.

---

## 1. Shortcodes (page-builder elements)

`framework/extensions/shortcodes/shortcodes/` — 78 elements.

### Layout & structure
- **Section** — full-width section wrapper (background, spacing, shape dividers).
- **Container** — constrained content container.
- **Row** / **Column** — grid row + columns (twelfths + `1_5` fifths).
- **Flexbox** — flexible box layout.
- **Bleed Section** — edge-to-edge / full-bleed section.
- **Masonry Section** — masonry (Pinterest-style) grid section.
- **Divider** — horizontal rule / spacer.

### Headings & text
- **Special Heading** — styled heading with sub/pre text.
- **Animated Heading** — heading with rotating/typed words.
- **Text Block** — WYSIWYG rich-text block.
- **Highlight Text** — inline highlighted/marker text.
- **Blockquote** — pull-quote.
- **Text Expander** — read-more / show-hide long text.
- **Code Block** — code display (Prism) or verbatim embed (`render_as_code` toggle).
- **Tag List** — list of tags/links (external links open new tab).
- **Table of Contents** — auto TOC from headings.

### Media
- **Image** (media-image) — single image.
- **Video** (media-video) — self-hosted file or oEmbed URL.
- **Gallery** — image gallery grid/lightbox.
- **Carousel / Slider** — image/content slider.
- **Image Box** — image + caption/content card.
- **Image Content** — image beside text.
- **Featured Image** — the post's featured image (dynamic).
- **Logo Grid** — client/partner logo grid.
- **Lottie Animation** — Lottie JSON player.
- **Image Hotspots** — pins/tooltips on an image.
- **Before / After** — draggable image comparison slider.
- **Video Popup** — thumbnail → video lightbox.
- **Audio Player** — audio playback.
- **Avatar** — round avatar image.

### Interactive & disclosure
- **Accordion** — collapsible panels.
- **Tabs** — tabbed content.
- **Modal / Popup** — modal dialog / popup.
- **Tooltip** — hover/click tooltip on content.
- **Flip Box** — front/back flip card.
- **Menu Toggle** — hamburger toggle.

### Data, stats & time
- **Animated Counter** — number count-up (number only; captions via text).
- **Progress Bars** — labeled progress/skill bars.
- **Star Rating** — star score.
- **Countdown Timer** — countdown to a date.
- **Calendar** — event/date calendar.
- **Table** — data table.
- **Comparison Table** — feature comparison grid.
- **Pricing Table** — pricing tiers.
- **Steps / Process** — numbered step/process flow.
- **Timeline** — vertical/horizontal timeline.

### Buttons, CTAs & messaging
- **Button** — link button (style presets).
- **Call To Action** — headline + button CTA band.
- **Announcement Pill** — small badge/announcement.
- **Notification** — dismissible notice/alert.
- **Newsletter** — email signup form.

### Navigation & site
- **Navigation Menu** — WP nav menu.
- **Search** (site-search) — site search field.
- **Site Logo** — site logo (dynamic).
- **Scroll Indicator** — reading-progress bar.
- **Scroll to Top & Progress** — back-to-top button (+ progress ring).
- **Social Icons** — social profile links.
- **Social Share** — share buttons.
- **Widget Area** — render a WP widget/sidebar area.
- **Map** — embedded map.

### People & testimonials
- **Team Member** — person card.
- **Testimonials** — quote/testimonial slider or grid.
- **Author Box** — post author bio.
- **Business Info** — NAP / business details.

### Icons & features
- **Icon** — single icon (font or inline SVG; Icon Size option).
- **Icon Box** — icon + title + text card.
- **Feature List** — icon+text feature list.

### Dynamic / posts (loop & single)
- **Posts** — post grid/list query.
- **Post Carousel** — post slider.
- **Post Title / Content / Excerpt / Meta / Author / Date / Terms** — single-post dynamic fields.

### Dev / test
- **Icon V3 Test** — internal test element (not for production).

---

## 2. Animation Engine

`framework/extensions/animation-engine/` — a bundled, activate-to-use extension. Modules attach to
elements via the **Animations** tab ("Add Animation" inserter), to Sections, or apply site-wide.
Every animation is on-demand (a page ships only the styles it uses) and honors reduced-motion.

**Shared Trigger vocabulary:** event-triggered animations use one image-picker Trigger control —
`view` / `load` / `click` / `hover` (multi-select where events combine) — with the label shown as
the tile's hover title.

### Modules by behavior group

**Entrance** (fires once on a trigger)
- **Entrance Animation** (Animate.css) — the core per-element reveal (in the shortcodes helper),
  ~90 Animate.css effects + attention seekers, with the shared multi-trigger.
- **Text Effects** — 37 typographic effects: reveal family (blur, mask, flip3d, scale, slide,
  bounce, random, skew, split-reveal), scramble, typewriter, countup, splitflap, matrix, shimmer,
  wave, glitch, neon, rainbow, gradient-flow, chromatic, marker, strikebox, outline-fill,
  fill-sweep, letter-jump, expand-spacing, color-wave, magnetic, vf-weight, width-sweep, breathing,
  float, jitter, image-mask, kinetic, rotating-words.

**Scroll** (progress tied to the scrollbar)
- **Scroll Motion** (GSAP) · **Scroll Reveal** (clip reveals) · **Scroll Text Highlight** (scrub
  highlight) · **Parallax** · **Motion Path** · **Scroll Loop** · **Scroll Color Shift** ·
  **Scrollytelling** · **Sticky Stack** · **Horizontal Scroll** · **Scroll Progress**.

**Interaction** (responds to the pointer / keyboard)
- **Hover** — 43 hover effects (lift, tilt, glow, ripple, blob, spotlight, sheen, etc.); fires on
  keyboard focus too.
- **Physics** — 27 effects (draggable, slingshot, spring, gravity, float, orbit, pendulum, jelly,
  wobble, magnetic, …); draggable/slingshot are keyboard-operable (arrow keys).
- **Flip Card** — hover/click front-back flip.
- **Cursor** — custom cursor / follower effects.

**Ambient** (always running)
- **Marquee** — infinite scrolling ticker.
- **Confetti** — 6 burst styles (confetti, stars, fireworks, streamers, hearts, snow).
- **Backgrounds** — 35 animated backgrounds (aurora, conic, …).

**Site-wide**
- **Page Transitions** — inter-page transition effects.
- **Preloader** — page load preloader.

### Animation Engine shortcodes (standalone animated elements)
`animation-engine/shortcodes/`
- **SVG Draw** — self-drawing SVG (presets/code/upload); trigger view/scrub/load/hover; scroll-scrub.
- **SVG Morph** — morph between SVG shapes.
- **Image Sequence** — canvas frame-sequence scrubbed by scroll (Apple-style).
- **WebGL Object** — WebGL/Three.js visual object.
- **Model Viewer** — 3D model (.glb) viewer.

---

## Notes
- **No duplicates:** e.g. image comparison = the **Before / After** shortcode; feature comparison =
  **Comparison Table**; count-up = **Animated Counter** shortcode *and* the Text Effects `countup`.
- **Two catalogs, kept in sync:** this file (plugin-wide) + `animation-engine/CATALOG.md`
  (engine effect-level detail). Update both when the engine changes.
