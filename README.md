# [Unyson+](https://github.com/UnysonPlus/UnysonPlus) Framework

Unyson+ is a **community-maintained fork** of the original [Unyson](https://wordpress.org/plugins/unyson/) framework for [WordPress](http://wordpress.org/).

This project continues where the original Unyson (by [ThemeFuse](http://themefuse.com/)) left off after development was discontinued, with the goal of keeping the framework alive, stable, and more developer-friendly — and of growing it into a modern, page-builder-first website toolkit.

---

## 🔹 Current Version

| Component | Version |
| --- | --- |
| Framework | **2.13.77** |
| Shortcodes extension | **1.8.56** |
| Page Builder extension | **1.6.66** |
| Unyson+ Theme | **2.2.37** |
| Requires WordPress | **5.8+** (tested up to 6.7) |
| Requires PHP | **7.4+** |

> Versions follow an independent per-component sequence. Site owners receive automatic updates from GitHub releases via **Plugin Update Checker v5** — no WP.org required.

---

## 🔹 Key Differences from the Original Unyson

* **Brizy removed** along with all references to it.
* **PHP 7.4+ / 8.x baseline.** Dropped support for PHP 5.6 / 7.0 / 7.1 / 7.2 / 7.3.
* **No Bootstrap dependency.** The plugin is fully self-sufficient — the bundled Bootstrap stylesheet was removed and the handful of utilities shortcodes need are now shipped by the plugin itself.
* **Modern page-builder-first toolkit** — a large library of new shortcodes/elements, a reusable Component Presets system, Dynamic Content tags, responsive per-device editing, and a clean, un-bloated frontend DOM.
* **Active security & modernization track** — see *Updates Done* below.
* **Community maintained** under the [UnysonPlus](https://github.com/UnysonPlus) GitHub org, with each extension split into its own repository.

---

## 🔹 Updates Done

### Page builder & layout

* **Section family overhaul** — the Section gains Min Height (40/60/80/100vh) + content vertical-align for full-screen heroes; the bleed layout was extracted into a dedicated **Bleed Section**; a new **Masonry Section** packs columns left-to-right with no gaps. The standalone Hero Section was retired (the upgraded Section supersedes it).
* **Nested columns** — a column can host other columns one level deep, with Bootstrap-style nested grids synthesized at render time (fully backward-compatible).
* **Background Pro** — a single Background control on Sections (and reusable everywhere): stacked color / gradient / image (with position / size / repeat / Fixed-parallax) / looping-video layers, with lossless migration of legacy background fields.
* **Per-device responsive editing** — Phone / Tablet / Desktop switcher for spacing (mobile-first Bootstrap breakpoints), plus a **Device Preview toggle** (Desktop / Tablet / Phone) that re-previews the canvas at each breakpoint.
* **Per-element Custom CSS + per-page dynamic-CSS pipeline** — each element gets a scoped Custom CSS field (Advanced tab) that rides template export/import; the framework collapses inline `<style>` blocks into one global + one per-page hashed stylesheet for a clean view-source.
* **Page Builder Templates: Import / Export** — export any saved Full / Sections / Columns template to a portable `.json` and import it on another install.
* **Bootstrap fully removed** — the bundled Bootstrap 5 stylesheet and its setting are gone; `.btn`, tabs, the testimonials carousel (Splide), and the flex/grid utilities are now provided by the plugin's own CSS.

### Component Presets system (theme-independent)

Reusable, plugin-owned preset libraries — **Colors, Typography / Font Sizes, Spacing, Gaps, Buttons, Borders / Boxes, and Tables** — each producing a named CSS class and editable from a dedicated **Component Presets** page under the Unyson+ menu. Presets are stored theme-independently so they work under any active theme. A **Styling Presets** master switch can turn the entire styling layer off for a bare, structure-only builder (for developers bringing their own CSS).

### New shortcodes / elements (40+)

A large library of new elements was added, including:

* **Content** — Animated Counter, Countdown, Animated Heading, Highlight Text, Blockquote, Tag List, Steps, Timeline, Table of Contents, Tooltip, Feature List.
* **Media** — Gallery, Lottie, Audio Player, Video Popup, Before/After, Image Box, Image Hotspots, Flip Box, Carousel.
* **Marketing** — Pricing Table, Comparison Table, Logo Grid, Business Info, Newsletter, Modal Popup, Social Share, Star Rating, Call to Action.
* **Dynamic post elements** — Post Title / Excerpt / Content / Meta / Date / Author / Terms, Post Carousel, Author Box, Avatar, Featured Image.
* **Layout & utility** — Container, Flexbox, Progress, Scroll-to-Top.

The Table shortcode was rebuilt around a real **spreadsheet editor** (inline-editable cells, drag-reorder, merge/unmerge, HTML/Word/CSV import, Excel paste, CSV/TSV export) with a dependency-free front-end enhancer for **sorting / search / pagination**.

### Dynamic Content

Elementor-Pro-style **dynamic tags**: Text / Textarea / Rich-Editor option fields (and the classic post editor) show a searchable picker that inserts tokens like `{{site_name}}`, `{{current_year}}`, `{{post_meta|key=subtitle}}`, live permalinks, "Last Updated", and WooCommerce product fields — resolved at render time through one registry, extensible via the `fw:dynamic-content:tags` filter.

### New option types

`background-pro`, `gradient-v2`, `spacing` (composite, per-device), `popover` (with tabs), `column-split`, `split-slider`, `unit-input`, `medium-text`, `button-style-picker`, `border-style-picker`, `button-hover-animation`, `table` (spreadsheet), and a free **Leaflet / OpenStreetMap** fallback for the `map` picker when no Google Maps key is set.

### New bundled extensions

* **Site Converter** *(Unyson+ → Convert)* — an AI-generated-site → WordPress importer. Imports a **Google Stitch** design deterministically (no AI), captures a source site by URL via a local capture service (the **Site Analyzer**), generates a self-sufficient child/standalone theme reproducing the source's header/footer/fonts/colors, and brings over media, menus, and page content.
* **Theme Builder** *(foundation)* — the Unyson+ take on Divi's Theme Builder; introduces Body Templates and conditional Templates (Use On / Exclude From rules) and owns the header/footer parts. Replaces the former Header & Footer Builder.
* **Post Types** — a no-code custom-post-type creator.
* **Custom Fields** — an ACF-style field-group builder (text, media, WYSIWYG, repeater, …) with location refinements, hide-on-screen, REST exposure, and JSON import/export; read with `fw_get_field()`.

### Options modal & DX modernization

* **Inline + server-side validation** for option modals (`fw.validateOptionsForm`, `get_value_error()`, the `fw_option_value_error` filter).
* **Non-blocking UI primitives** — `fw.notify()` toasts and the promise-based `fw.confirm()` dialog replaced blocking `alert()` / `confirm()` across the framework.
* **`fw_image_tag()`** — a shared, modern responsive-image builder (`srcset`/`sizes`, width/height for CLS, `fetchpriority` for LCP).
* **Asset build pipeline** (`build/`) — esbuild + PostCSS minifies the core backend CSS/JS, served via `fw_get_framework_asset_uri()` with a safe unminified fallback.

### Security hardening (multi-phase)

* **Pre-fork CVE backlog addressed** (AI-assisted review): **CVE-2023-44472** (Missing Authorization), **CVE-2024-34814** (CSRF), **CVE-2022-2219** (Reflected XSS).
* SQL identifiers wrapped in `esc_sql()`, `SHOW TABLES LIKE` converted to `$wpdb->prepare()`, legacy unserialize hardened (`allowed_classes = false`), and `$atts` escaped across shortcode views.
* Public Calendar AJAX locked down (provider whitelist + `is_callable()` guard); CSRF nonces added to the Mailer test and ~14 mutating Builder / Sidebars / Backend-options AJAX handlers.
* `fw_rand_md5()` weak randomness replaced with CSPRNG-backed `random_bytes()`; unescaped echoes fixed with `wp_kses_post` / `esc_html` per context; text-domain and `esc_url()` cleanups for WP.org readiness.

### Modernization & carry-over

* `uniqid()` DOM IDs replaced with `wp_unique_id()`; strict return types on activation hooks.
* **Plugin Update Checker v5** wired to GitHub releases (automatic updates without WP.org).
* Full **PHP 8.x** compatibility sweeps (`create_function`, `each()`, `$arr{0}`, `implode()` arg order); Bootstrap 3 → 5 migration of admin/option UIs; deprecated patterns and heavy jQuery dependencies retired incrementally.

---

## 🔹 Plans for This Project

* **jQuery cleanup** — incrementally retire `jquery.fs.wallpaper.js` and other heavy jQuery dependencies in shortcode JS.
* **Theme Builder front-end wiring** — land the admin grid, conditions UI, and `template_include` body rendering on top of the resolver foundation.
* **Continued PHP 8.x sweep** — systematic pass for any remaining legacy patterns outside core.
* **Ongoing shortcode improvements** — more elements, flexibility, and developer-friendly APIs.
* **Gutenberg integration** — improve Block Editor compatibility while keeping Classic Editor support.
* **Options Framework DX** — make the admin options system even more developer-friendly (inspired by ACF / Carbon Fields).
* **Migration tools** — help existing Unyson users transition smoothly without breaking sites.
* **Automated testing** — PHPUnit + WordPress test suite for long-term stability.
* **Semantic versioning + tagged releases** — formalize the release cadence (already in motion via Plugin Update Checker v5).
* **Extension registry** — an open system for community-driven add-ons and modules.
* **Multisite compatibility audit** — ensure full compatibility in multisite environments.

---

## Table of Contents

* [Installation](#installation)
* [Documentation](#documentation)
* [Extensions](#extensions)
* [Contributing](#contributing)
* [Bug Reports](#bug-reports)
* [License](#license)

## Installation

1. Download or clone the repository into your WordPress `plugins` directory.
2. Activate **Unyson+** from the WordPress dashboard under **Plugins**.
3. Configure the framework by going to the **Unyson+ menu**.

⚠ **Warning:** If you currently have the original Unyson plugin installed, create a staging site or backup your site first. Unyson+ shares the same function names as Unyson, so running both at the same time will cause fatal errors. Uninstall Unyson before installing Unyson+.

## Documentation

The original Unyson manual at `manual.unyson.io` has been taken down. Historical Unyson documentation can still be accessed via the Internet Archive:
👉 [Archived Unyson Documentation Manual](https://web.archive.org/web/20221130053349/http://manual.unyson.io/)

Unyson+-specific documentation is published at 👉 [unysonplus.github.io](https://unysonplus.github.io/) (source: [`UnysonPlus/UnysonPlus.github.io`](https://github.com/UnysonPlus/UnysonPlus.github.io)). Contributions are welcome.

## Extensions

Unyson+ supports the same modular extension system as the original Unyson — extensions can be enabled/disabled as needed — and ships a growing set of modernized, independently-versioned extensions, each in its own repository:

| Extension | Repository |
| --- | --- |
| Page Builder | [`UnysonPlus-PageBuilder-Extension`](https://github.com/UnysonPlus/UnysonPlus-PageBuilder-Extension) |
| Shortcodes | [`UnysonPlus-Shortcodes-Extension`](https://github.com/UnysonPlus/UnysonPlus-Shortcodes-Extension) |
| Builder (base option type) | [`UnysonPlus-Builder-Extension`](https://github.com/UnysonPlus/UnysonPlus-Builder-Extension) |
| Site Converter | [`UnysonPlus-Site-Converter-Extension`](https://github.com/UnysonPlus/UnysonPlus-Site-Converter-Extension) |
| Theme Builder | [`UnysonPlus-Theme-Builder-Extension`](https://github.com/UnysonPlus/UnysonPlus-Theme-Builder-Extension) |
| Post Types | [`UnysonPlus-Post-Types-Extension`](https://github.com/UnysonPlus/UnysonPlus-Post-Types-Extension) |
| Custom Fields | [`UnysonPlus-Custom-Fields-Extension`](https://github.com/UnysonPlus/UnysonPlus-Custom-Fields-Extension) |
| Mega Menu | [`UnysonPlus-MegaMenu-Extension`](https://github.com/UnysonPlus/UnysonPlus-MegaMenu-Extension) |
| Sidebars | [`UnysonPlus-Sidebars-Extension`](https://github.com/UnysonPlus/UnysonPlus-Sidebars-Extension) |
| Portfolio | [`UnysonPlus-Portfolio-Extension`](https://github.com/UnysonPlus/UnysonPlus-Portfolio-Extension) |
| Forms | [`UnysonPlus-Forms-Extension`](https://github.com/UnysonPlus/UnysonPlus-Forms-Extension) |
| Breadcrumbs | [`UnysonPlus-Breadcrumbs-Extension`](https://github.com/UnysonPlus/UnysonPlus-Breadcrumbs-Extension) |
| Mailer | [`UnysonPlus-Mailer-Extension`](https://github.com/UnysonPlus/UnysonPlus-Mailer-Extension) |
| Blog Posts | [`UnysonPlus-Blog-Extension`](https://github.com/UnysonPlus/UnysonPlus-Blog-Extension) |
| Snippets | [`UnysonPlus-Snippets-Extension`](https://github.com/UnysonPlus/UnysonPlus-Snippets-Extension) |
| Asset Optimizer | [`UnysonPlus-Asset-Optimizer-Extension`](https://github.com/UnysonPlus/UnysonPlus-Asset-Optimizer-Extension) |
| WooCommerce | [`UnysonPlus-WooCommerce-Extension`](https://github.com/UnysonPlus/UnysonPlus-WooCommerce-Extension) |
| Live Editor | [`UnysonPlus-Live-Editor-Extension`](https://github.com/UnysonPlus/UnysonPlus-Live-Editor-Extension) |
| Update | [`UnysonPlus-Update-Extension`](https://github.com/UnysonPlus/UnysonPlus-Update-Extension) |

Additional extensions (Sliders, Social, SEO, Analytics, Translation, Events, Feedback, Backups, Learning, …) are also tracked under the [UnysonPlus](https://github.com/UnysonPlus) org.

## Contributing

You can help keep Unyson+ alive and growing!

Ways to contribute:
- **Code contributions** via Pull Requests
- **Documentation improvements**
- **Bug fixes** and reporting
- **New or modernized extensions**

Contributor guidelines will be published soon in `CONTRIBUTING.md`.

## Bug Reports

Please submit issues here:
👉 [Unyson+ Issues](https://github.com/UnysonPlus/UnysonPlus/issues)

When reporting a bug:
- Provide detailed steps to reproduce the issue
- Include WordPress version, PHP version, and theme/plugin details
- Share error logs or screenshots if possible

## License

Unyson+ is released under the [GPL-3.0 License](https://github.com/UnysonPlus/UnysonPlus/blob/master/framework/LICENSE).

Original code copyright © 2014 ThemeFuse LTD.
Fork maintained and extended by the Unyson+ community.

---
> ⚡ *Unyson+ — carrying on the legacy of Unyson for modern WordPress development.*
