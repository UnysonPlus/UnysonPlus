# [Unyson+](https://github.com/UnysonPlus/UnysonPlus) Framework

Unyson+ is a **community-maintained fork** of the original [Unyson](https://wordpress.org/plugins/unyson/) framework for [WordPress](http://wordpress.org/).

This project continues where the original Unyson (by [ThemeFuse](http://themefuse.com/)) left off after development was discontinued, with the goal of keeping the framework alive, stable, and more developer-friendly.

---

## 🔹 Current Version

| Component | Version |
| --- | --- |
| Framework | **2.7.70** |
| Shortcodes extension | **1.4.6** |
| Requires WordPress | **5.8+** (tested up to 6.7) |
| Requires PHP | **7.4+** |

---

## 🔹 Key Differences from the Original Unyson

* **Brizy removed** along with all references to it.
* **PHP 7.4+ / 8.x baseline.** Dropped support for PHP 5.6 / 7.0 / 7.1 / 7.2 / 7.3.
* **Active security & modernization track** — see *Updates Done* below.
* **Community maintained** under the [UnysonPlus](https://github.com/UnysonPlus) GitHub org.

---

## 🔹 Updates Done

### Security hardening (multi-phase)

* **Pre-fork CVE backlog addressed** (with AI-assisted code review):
  - Missing Authorization & Access Violation — **CVE-2023-44472**
  - Cross-Site Request Forgery — **CVE-2024-34814**
  - Reflected Cross-Site Scripting — **CVE-2022-2219**
* **Phase A + B** *(v2.7.64)* — SQL identifiers wrapped in `esc_sql()`; `SHOW TABLES LIKE` converted to `$wpdb->prepare()`; legacy `fw_termmeta` unserialize hardened with `allowed_classes = false`; `$atts` escaped in *team-member*, *call-to-action*, *notification*, and *breadcrumbs* views; `FW_Request` escaping responsibilities documented.
* **Phase C** *(v2.7.65 / shortcodes 1.4.3)* — public Calendar AJAX endpoint locked down: provider-slug whitelist via `sanitize_key`, `is_callable()` guard before `call_user_func()`, and a new `fw_shortcode_calendar_provider_is_allowed` filter for per-site opt-out.
* **Phase D — Mailer** *(v2.7.66)* — CSRF nonce protection on `fw_ext_mailer_test_connection` (handler + JS caller paired in the same release).
* **Phase D — Builder / Sidebars / Backend options** *(v2.7.67)* — `wp_create_nonce` + `check_ajax_referer` added to ~14 mutating AJAX handlers, including:
  - Builder fullscreen storage (`set`/`unset`)
  - Multi-select autocomplete
  - Builder templates (render / full-load / save / delete)
  - 6 Sidebar handlers (add-new, autocomplete, save-preset, remove-preset, delete, load-preset)
  - 3 Backend-options handlers powering **Theme Settings** save, addable-box re-render, and reactive-options pipelines
* **Phase E.1 — WP.org submission readiness** *(v2.7.68)*:
  - `fw_rand_md5()` weak randomness (`rand` / `mt_rand` / `uniqid`) replaced with **CSPRNG-backed `random_bytes()`** in `framework/helpers/general.php`.
  - 6+ unescaped echoes in portfolio/table shortcode views fixed with `wp_kses_post` / `esc_html` / `esc_textarea` per context.
  - Buggy `echo esc_html_e()` pattern in `about.php` corrected; 11 missing `'fw'` text-domain calls added; plugin-install URL routed through `esc_url()`.
* **Shortcodes 1.4.2 / 1.4.4** — stored-XSS prevention via escaped `$atts` and `wp_kses_post` / `esc_textarea` in *team-member*, *call-to-action*, *notification* views and the table textarea-cell option-type.

### Modernization

* `uniqid()`-based DOM IDs replaced with `wp_unique_id()` in accordion, tabs, and testimonials views (v2.7.68 / shortcodes 1.4.4).
* Strict return types added on plugin activation hooks (`_action_fw_plugin_activate(): void`, typed `int $blog_id`, `bool $drop`).
* **Plugin Update Checker v5** wired to GitHub releases on the `master` branch — site owners get automatic updates without WP.org.

### Shortcodes — Styling tab UX polish *(v2.7.69 → 2.7.70 / shortcodes 1.4.5 → 1.4.6)*

* Every preset-picker field (*Text Color*, *Background Color*, *Font Size*, *Margin/Padding*, *Button Style / Outline / Size*) now exposes a contextual `help` link that opens the relevant **Theme Settings** tab in a new browser tab — implemented via a new `sc_theme_settings_url( $context )` helper with an `sc_theme_settings_url` filter for non-Unyson+ themes.
* White / near-white preset options no longer render as blank rows: new `sc_color_is_light()` uses **BT.601 luminance** to apply a dark contrast backdrop only on light swatches.
* Luminance threshold tightened **0.85 → 0.95** so only true whites (`#fff`, Bootstrap Light `#f8f9fa`) get the backdrop trick — Yellow / Lime / Light Gray now render in their actual hue.
* Backdrop softened **#222 → #444** across all three emitters (color-select, color-preset-select, button-style-select).
* "Add more in Theme Settings → Spacing" help removed from the 8 per-side spacing dropdowns; kept on the two All-Sides fields where one help affordance is enough.

### Pre-fork carry-over

* **Full PHP 8.x compatibility** sweeps in core paths: `create_function`, `each()`, curly-brace array access `$arr{0}`, `implode()` argument order, etc.
* **Bootstrap 3 → Bootstrap 5** migration of admin / option UIs (calendar shortcode still ships its own bundled Bootstrap 3.4.1 — tracked in *Plans*).
* Deprecated PHP patterns removed; jQuery usage cleanup begun.

---

## 🔹 Plans for This Project

* **Bootstrap 5 in the calendar shortcode** — replace the bundled Bootstrap 3.4.1 in `framework/extensions/shortcodes/shortcodes/calendar/` (the last BS3 holdout).
* **jQuery cleanup** — incrementally retire `jquery.fs.wallpaper.js` and other heavy jQuery dependencies in shortcode JS.
* **Continued PHP 8.x sweep** — systematic pass for any remaining `create_function` / `each()` / curly-brace `$arr{0}` patterns outside core.
* **Ongoing shortcode improvements** — more features, flexibility, and developer-friendly APIs.
* **Gutenberg integration** — improve compatibility with the Block Editor while keeping Classic Editor support.
* **Options Framework DX** — make the admin options system more developer-friendly (inspired by ACF / Carbon Fields).
* **Migration tools** — help existing Unyson users transition smoothly to Unyson+ without breaking sites.
* **Automated testing** — PHPUnit + WordPress test suite for long-term stability.
* **Semantic versioning + tagged releases** — already in motion via Plugin Update Checker v5; formalize the release cadence.
* **Extension registry** — open system for community-driven add-ons and modules.
* **Per-extension repos** — Shortcodes already split into [`UnysonPlus/UnysonPlus-Shortcodes-Extension`](https://github.com/UnysonPlus/UnysonPlus-Shortcodes-Extension); continue for the rest.
* **Multisite compatibility audit** — ensure full compatibility in multisite environments.
* **Backward compatibility** — preserved for legacy themes and shortcodes as modernization continues.

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

Future Unyson+-specific documentation will be published here in the repository's **/docs** folder. Contributions are welcome.

## Extensions

Unyson+ supports the same modular extension system as the original Unyson. Extensions can be enabled/disabled as needed.
We aim to gradually improve, fix, and modernize these extensions.

Examples include:

- Page Builder
- Shortcodes
- Mega Menu
- Sidebars
- Sliders
- Portfolio
- Backup & Demo Content
- SEO
- Forms
- Feedback
- Breadcrumbs
- Events
- Analytics
- Mailer
- Social
- Blog Posts
- Translation

👉 Over time, Unyson+ will host updated and modernized versions of these extensions under separate repositories for easier maintenance — see [`UnysonPlus/UnysonPlus-Shortcodes-Extension`](https://github.com/UnysonPlus/UnysonPlus-Shortcodes-Extension) as the first split-out example.

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
