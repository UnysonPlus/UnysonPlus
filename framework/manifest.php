<?php if (!defined('FW')) die('Forbidden');

$manifest = array();
$manifest['name'] = __('Unyson+', 'fw');
$manifest['version'] = '2.10.41';

/**
 * Changelog
 * 2.10.35 - Extended the preset Duplicate action (2.10.34) to border presets and table
 *           presets, and added an opt-in Duplicate control to the generic addable-box
 *           option type (set `box-duplicate => true`): it clones a box as a new entry
 *           (fresh increment index) carrying its live values, re-pointing input names
 *           by prefix and ids by the per-box index. Shortcode Settings enable it on the
 *           Color, Font Size, Spacing and Gap preset lists, so every preset manager now
 *           has a one-click duplicate.
 * 2.10.34 - Button presets gain a Duplicate action: a duplicate icon in each preset's
 *           header clones it as a NEW preset (a fresh unique index, so it saves
 *           separately) carrying all its current values — name, per-state colors, font,
 *           transition and custom CSS. Implemented by syncing live form values into the
 *           DOM before cloning, then re-pointing the box index in every name / id / for.
 *           This is the reusable pattern the other preset managers (color / border /
 *           table / font-size / spacing) will follow.
 * 2.10.32 - Registered the Site Converter extension in the extension manager's
 *           available-extensions registry (with its GitHub download source). It now
 *           shows the Remove (trash) action when deactivated and updates through the
 *           GitHub auto-updater — matching Asset Optimizer — instead of needing a
 *           manual plugin re-upload.
 * 2.10.31 - New `column-split` option type: a draggable two-pane split bar — drag the
 *           divider to set how a row is shared between a LEFT and RIGHT pane, each
 *           labelled with a dashicon/image + text (e.g. "Image | Content"). It stores a
 *           single integer (the left pane's column span out of `denominator`, default
 *           12), so it drop-in replaces a column-count slider with NO value migration.
 *           Config: value / denominator / min / max / show_fraction / panes
 *           ([left,right] each {label,icon}). Reusable for any two-up split; first used
 *           by Image Content's "Image / Content Split". Assets ship un-minified (kept
 *           out of build-manifest), so there is no `.min` sibling to maintain.
 * 2.10.29 - Build fix: rebuilt the minified `.min.js` siblings for the slider and
 *           dynamic-content option types. Production serves the `.min` (via
 *           build-manifest.php + fw_get_framework_asset_uri() when SCRIPT_DEBUG is
 *           off), so JS changes are inert on live sites until the `.min` is rebuilt —
 *           the slider fraction labels (2.10.28) and the dynamic-content select-type
 *           params for the permalink picker (2.10.27) only edited the un-minified
 *           source, so they did not actually run until now.
 * 2.10.28 - Slider option type gains an optional `fw_fraction_of` property: when set,
 *           the slider DISPLAYS its value as the lowest-form fraction of value/N (e.g.
 *           6 -> "1/2", 4 -> "1/3") while the stored value stays the integer — labels
 *           only, no value-shape change. Used by Image Content's "Image / Content
 *           Split" so the split reads as familiar fractions (1/2, 1/3, 1/4 …) instead
 *           of "6 / 12".
 * 2.10.27 - Dynamic Content: live link permalinks, a "Last Updated" tag, and
 *           select-type picker params. A new "Links" group registers a Permalink tag
 *           per public post type ({type}_permalink) — e.g. "Page Permalink" — each with
 *           a gear dropdown of that type's published items that resolves to a live
 *           get_permalink(). Because link fields are plain text (Dynamic Content already
 *           applies to them), dropping {{page_permalink|id=42}} into any link field keeps
 *           the URL correct when the target's slug later changes — no bespoke "link"
 *           option type needed. The dropdown is built only in admin (the frontend resolver
 *           runs no extra queries) and capped at 200 items by title (filter
 *           fw:dynamic-content:permalink_choices_limit). Added a "Last Updated" tag (WP
 *           post_modified — the latest time the post's Update button was pressed) with a
 *           date-format param. To power the dropdowns, the picker's gear form now renders
 *           select params (a param may be type=select with an ordered choices array of
 *           {value,label}); see framework/includes/dynamic-content/tags/links.php.
 * 2.10.26 - New "Site Converter" extension (Unyson+ -> Convert) — the admin home for the
 *           AI-generated-site -> WordPress importer (roadmap #2 of the conversion
 *           initiative). This first release ships the Media tool: fetch a source site's
 *           images into the Media Library, either by scanning a page URL (collects every
 *           img/srcset/CSS-url reference) or from a pasted URL list. Imports are de-duped by
 *           source URL (recorded as the `_unysonplus_source_url` postmeta), so re-running is
 *           safe and images referenced on several pages reuse one attachment. The reusable
 *           engine (FW_Site_Converter_Media: sideload / import_urls / scan_html / absolutize
 *           / rewrite) is static so a future "Convert bundle" importer and WP-CLI can share
 *           it. Presets import, menu import, and the one-shot bundle are the next slices.
 * 2.10.13 - Section family overhaul. (1) The Bleed Layout was extracted from the
 *           standard Section into its own "Bleed Section" element (a section-like
 *           shortcode that holds rows/columns; its content-side background is a
 *           full background-pro control, the image side bleeds to the viewport
 *           edge). The standard Section no longer carries the Bleed tab/markup.
 *           (2) The standard Section gains Min Height (Auto / 40 / 60 / 80 / 100vh)
 *           + Content Vertical Align, so it can do full-screen "hero" sections.
 *           (3) The "Hero Section" element was removed — the upgraded Section
 *           (background-pro image with Fixed = parallax, color/gradient/video,
 *           min-height + vertical-align) fully supersedes it. (4) Masonry Section's
 *           Background Color was upgraded to the full background-pro control.
 *           A shared emitter (sc_bg_pro_style / sc_bg_pro_video_attr in
 *           shortcode-styling-helper.php) backs the background-pro CSS across
 *           Section / Masonry / Bleed. (5) The image upload control now previews
 *           the FULL image (aspect-preserved 'large' size) filling the option
 *           container instead of a cropped 50x50 square, with a new
 *           `thumb_max_width` option attribute to cap it.
 *
 * 2.10.12 - Section background is now a single "Background" control (the
 *           background-pro option type) on the Layout tab: color, gradient,
 *           image (with position / size / repeat / attachment — Fixed gives a
 *           parallax effect) and looping video layers that stack as CSS (image
 *           over gradient over color) alongside the existing Formstone video
 *           player. It replaces the old separate Background Color / Image /
 *           Video fields and the Styling-tab preset Background Color. Existing
 *           sections are migrated automatically and losslessly: a shared mapper
 *           (section/includes/migration.php) synthesizes a background-pro value
 *           from the legacy atts, so old sections show their background
 *           pre-filled in the editor and render identically until re-saved (the
 *           stored legacy atts are left untouched). background-pro's
 *           _enqueue_static now also loads its gradient / upload / oembed /
 *           multi-inline child assets (plus wp_enqueue_media) and force-enqueues
 *           on post-edit screens, so the control is fully functional inside
 *           shortcode modals. Note: a migrated palette-preset colour becomes a
 *           static hex (it no longer tracks live palette changes). Hero and
 *           Masonry sections and the Section bleed layout are unchanged.
 *
 * 2.10.1 - Per-device spacing overrides + compact Margin/Padding control. The
 *          spacing option type gains a Phone / Tablet / Desktop switcher (synced
 *          to the page builder's global device toggle) so margin and padding can
 *          differ per breakpoint. The cascade is mobile-first and Bootstrap-
 *          native: the existing base value applies at all widths (values saved
 *          before this release are unchanged), while the Tablet (>= 768px) and
 *          Desktop (>= 992px) layers store breakpoint-infixed utility classes
 *          (m-md-3, pt-lg-2) now emitted by css-tokens.php inside min-width
 *          @media blocks. The control's tall plus-cross layout is replaced by a
 *          compact inline row per section with a link toggle (one "All" value vs
 *          four per-side values). A reusable fw-device-tabs component
 *          (framework/includes/device-tabs.php + framework/static/js/fw-device-
 *          tabs.js) backs the switcher so typography / background controls can
 *          adopt per-device editing later.
 *
 * 2.9.92 - New asset build pipeline (build/). A small Node toolchain (esbuild +
 *          PostCSS/autoprefixer/cssnano, run via `npm run build`) autoprefixes
 *          and minifies the core backend stylesheets, writing *.min.css siblings
 *          next to the readable source. New public helper
 *          fw_get_framework_asset_uri( $rel_path ) serves the .min build when it
 *          exists and SCRIPT_DEBUG is off, else falls back to the unminified
 *          source — so the plugin works with or without a build, and a missing
 *          .min never 404s. Wired for fw.css / backend-options.css /
 *          option-types.css (~20% smaller, and autoprefixer finishes the vendor-
 *          prefix normalization in the output). build/node_modules is dev-only
 *          and must be excluded from the uploaded zip (see build/README.md).
 *
 * 2.9.84 - New "Header & Footer Builder" extension. Adds two private, page-builder-
 *          authored post types (Header Preset / Footer Preset) modeled on the
 *          Snippets extension: each preset is built with the visual builder and
 *          rendered (auto-section-stripped) via fw_ext_hfbuilder_render() into the
 *          theme's <header>/<footer>. A header preset also carries a Type
 *          (standard-top / sidebar / off-canvas / fullscreen-overlay / mega) and a
 *          scroll Behavior, emitted as wrapper classes + data-attrs for the theme's
 *          CSS/JS. The builder palette on the preset edit screens is trimmed (via
 *          the fw_ext_shortcodes_disable_shortcodes filter, scoped to those CPTs)
 *          so header/footer-irrelevant elements (accordion, tabs, posts grid, …)
 *          are hidden; the list is filterable (fw_ext_hfbuilder_disabled_elements).
 *          Pairs with the new Header/Footer Elements shortcodes (2.9.83).
 *
 * 2.9.75 - Plugin version sync for the Breadcrumbs extension upgrade (1.0.18):
 *          schema.org BreadcrumbList structured data (Microdata / JSON-LD),
 *          new presentation + behavior options, a [breadcrumbs] shortcode, and
 *          an arguments array for fw_ext_breadcrumbs(). See the breadcrumbs
 *          extension manifest for details.
 *
 * 2.9.53 - Custom Fields: group-level Description (rendered as a note atop the
 *          meta box) and Display title (overrides the meta box heading); a
 *          per-group "Show in REST API" toggle that exposes the group's field
 *          values under `unysonplus_fields` on the targeted post types; and
 *          Import / Export of all field groups as JSON (Tools section — export
 *          downloads a file, import appends or replaces). Conditional logic and
 *          label/instruction placement are intentionally deferred (no native
 *          show_if in this fork; the former needs a dedicated JS engine).
 *
 * 2.9.52 - Custom Fields: new "Repeater" field type. Sub-fields are defined with
 *          a simple "name | Label | type" line list (types: text, textarea,
 *          wysiwyg, number, url, email, image, file, gallery, color, date,
 *          switch, checkbox). On the edit screen the repeater renders as an
 *          inline addable-box of rows; the saved value is an array of rows
 *          (each keyed by sub-field name), read with fw_get_field() and looped.
 *
 * 2.9.51 - Custom Fields: optional location refinements per group. Beyond the
 *          "Show on post types" target, a group can also narrow by "Page
 *          templates" and/or "Post statuses" — evaluated against the post being
 *          edited, so the group (and its hide-on-screen rules) only apply when
 *          the post matches. Empty refinements always match.
 *
 * 2.9.50 - Custom Fields: per-group "Hide on screen" setting. Checkboxes for
 *          Excerpt, Discussion, Comments, Revisions, Slug, Author, Format, Page
 *          Attributes, Featured Image, Categories, Tags and Send Trackbacks;
 *          when an active group targets a post type, the chosen core meta boxes
 *          are removed from that edit screen (late `add_meta_boxes` hook +
 *          remove_meta_box).
 *
 * 2.9.49 - Custom Fields: added width-variant text/select field types
 *          (medium-width text, short-width text, short-width select) and two
 *          field-group settings — an "Active" toggle (inactive groups are
 *          skipped, default on) and an "Order" number (groups with a lower
 *          number render first when several apply to the same post type). Each
 *          field type now also carries a per-field "Dynamic Content" checkbox
 *          (on by default) for the types that support it.
 *
 * 2.9.44 - Custom Fields extension is now functional (was a scaffold). The
 *          admin page (Unyson+ -> Custom Fields) is an addable-popup of Field
 *          Groups; each group has a title, a "show on post types" multi-select,
 *          a meta-box position, and an inline addable-box of fields. Each field
 *          has a label, a name (its meta key), a type from a curated list (text,
 *          textarea, WYSIWYG, number, url, email, image, file, gallery, select,
 *          radio, checkbox, checkboxes, switch, color, date), optional choices
 *          and instructions. On the fw_post_options filter each matching group
 *          becomes a `box`, so the existing meta-box engine renders the fields
 *          and saves to post meta. Read values with the new fw_get_field( $name
 *          [, $post_id [, $default ]] ) helper. Stored in the extension settings
 *          store under `field_groups`.
 *
 * 2.9.40 - New `medium-text` option type. A text input sized between
 *          `short-text` (~100px) and the full-width `text` — roughly half the
 *          regular width with a ~300px minimum (about 3x short-text). Useful for
 *          fields like labels that are cramped in short-text but don't need the
 *          whole column. Implemented as a FW_Option_Type_Text subclass that adds
 *          a `.fw-option-width-medium` wrapper class; registered in hooks.php and
 *          the option-types autoloader alongside the other simple types.
 *
 * 2.9.35 - New bundled extensions: "Post Types" and "Custom Fields", surfaced
 *          under the Unyson+ menu in the order Post Types, Custom Fields,
 *          Shortcodes, Component Presets. Post Types is a no-code custom post
 *          type creator: an admin page (Unyson+ -> Post Types) renders an
 *          addable-popup of definitions (singular/plural labels, key, supports,
 *          public, has_archive, hierarchical, REST, menu icon/position), stored
 *          in the extension settings store and registered via register_post_type()
 *          on `init`, with a deferred flush_rewrite_rules() after edits and an
 *          fw_ext_post_types_args filter. It also anchors the Unyson+ submenu
 *          order (admin_menu priority 999) via the fw_unysonplus_admin_submenu_order
 *          filter, because WordPress' position-key ordering is load-order
 *          dependent across independent extensions. Custom Fields ships as a
 *          scaffold for now (menu slot + planning page); the ACF-style Field
 *          Group builder that injects fields through the existing fw_post_options
 *          meta-box engine lands next. Both extensions must be activated under
 *          Unyson+ -> Extensions.
 *
 * 2.9.34 - Page Builder Settings styling fields are now OPT-OUT (unchecked by
 *          default, on by default): renamed `enqueue_bootstrap` → `disable_bootstrap`
 *          ("Dequeue Bootstrap 5 CSS") and the 2.9.31 `styling_presets` →
 *          `disable_styling_presets` ("Disable Styling Presets"). Reads inverted in
 *          bootstrap.php and unysonplus_styling_presets_enabled() (default false =
 *          enabled). Pre-launch, no migration.
 *
 * 2.9.31 - New "Styling Presets" master switch (Page Builder settings, beside
 *          "Bootstrap 5 Stylesheet"), default ON. It governs the whole styling
 *          layer as one coherent unit so it can be turned off for a bare,
 *          structure-only page builder — for developers who bring their own theme
 *          CSS and style via each element's CSS ID / Class (Advanced tab). When
 *          OFF: presets.css stops enqueuing (front end + admin + inline fallback);
 *          the per-shortcode Styling tab and the Button / Border / Table preset
 *          pickers are stripped from every shortcode via one central
 *          fw_shortcode_get_options filter; and the Component Presets editor page
 *          is hidden. The Animation tab is intentionally NOT affected — its
 *          Animate.css is conditionally enqueued (loads only when an element
 *          animates), so it's already zero-cost when ignored. Read via
 *          unysonplus_styling_presets_enabled() (page-builder ext setting
 *          `styling_presets`, default true). Note: bare mode unstyles preset-based
 *          classes and the Unyson+ theme depends on these tokens, so it's meant
 *          for non-Unyson themes (shortcodes 1.5.72).
 *
 * 2.9.18 - Discoverability: added a dedicated "Component Presets" item under the
 *          Unyson+ menu that opens the preset editor (Colors / Typography /
 *          Spacing / Buttons / Borders / Tables) directly. Previously the editor
 *          (the Shortcodes extension Settings form from 2.9.17) was only reachable
 *          by URL — the extension card's "Settings" link is intentionally pointed
 *          at the enable/disable page, so there was no menu path to the presets
 *          (shortcodes 1.5.60).
 *
 * 2.9.17 - Shortcode preset settings are now PLUGIN-owned and theme-independent.
 *          The preset libraries (Color Presets, Typography/font-sizes, Spacing,
 *          Gap, Button Presets, Button Sizes, Hover Animations, Border Presets,
 *          Table Presets) used to be defined by the Unyson+ theme and stored
 *          theme-scoped (fw_theme_settings_options:{theme}), with a stale
 *          duplicate fallback schema injected into Theme Settings on other themes
 *          (its old addable-box button template caused the
 *          "[Template Error] normal_text_color is not defined" under non-Unyson+
 *          themes). They now live in ONE place: the Shortcodes extension Settings
 *          form (framework/extensions/shortcodes/settings-options.php, schema in
 *          includes/components-options.php), rendered by Unyson core and stored in
 *          the theme-independent fw_ext_settings_options:shortcodes store. The
 *          getters in framework/includes/presets.php read via a new
 *          unysonplus_preset_store_get() seam, so shortcode styling (and the
 *          css-tokens.php pipeline) works identically under ANY active theme.
 *          The stale Theme-Settings injection (shortcode-options/) was removed and
 *          help-text links now point at Unyson+ → Extensions → Shortcodes →
 *          Settings. No data migration (pre-launch). Theme side: the preset option
 *          fragments + the Components tab were removed; the theme keeps its own
 *          base typography/layout and consumes presets via the plugin getters
 *          (shortcodes 1.5.59, theme 2.1.80).
 *
 * 2.9.16 - Table Presets — a third reusable component-preset library, mirroring
 *          Button Presets and Border Presets. A new `table-presets` option type
 *          (Theme Settings → Components → Tables) lets you build named table looks
 *          with a live mini-table preview; each produces a `.tbl-{name}` class. A
 *          preset carries SECTION skins (Header, Body, Striped, Row Hover, Footer,
 *          Caption) plus shared structure (cell padding, grid lines, outer frame,
 *          corner radius, shadow, cell font size), a transition, and a
 *          {{SELECTOR}} Custom CSS field; colors reference your Color Presets or a
 *          custom value. Pick one on a Table via the new `table-style-picker`
 *          field (Table Options → Table Preset), and optionally stack a Border
 *          Preset around it via the new Frame field (the table is wrapped in a
 *          `.colb-{slug}` box, so the two never conflict). Five built-ins ship
 *          (Clean Lines, Bordered Grid, Minimal, Striped, Dark Header) via
 *          framework/includes/presets.php; css-tokens.php emits the `.tbl-{slug}`
 *          rules into the cached presets stylesheet (schema bumped 9 → 10).
 *
 *          Reorg: Theme Settings gained a top-level "Components" tab and the
 *          existing Buttons + Borders preset panels moved there (next to Tables).
 *          Pure relocation — option keys (button_colors, button_sizes,
 *          button_animations, border_presets, table_presets) are unchanged, so
 *          saved presets are preserved (shortcodes 1.5.58, theme 2.1.79).
 *
 * 2.9.13 - Table shortcode rebuild (part 2 of 2): the tabular editor and front
 *          end gain the data-entry + presentation features. EDITOR: Import a table
 *          three ways — paste an HTML / Word / Google-Docs table (parsed, keeping
 *          bold/italic/links via an inline allowlist), upload a .csv (delimiter
 *          auto-detected), or paste a range straight from Excel / Sheets onto the
 *          grid (TSV expands cells in place). Export the table back out as CSV
 *          (download) or copy to clipboard (TSV). Cells can be merged / unmerged
 *          (Shift+click a range → Merge) with colspan/rowspan, and the editor keeps
 *          merges self-consistent through row/column edits. FRONT END: views/
 *          tabular.php is a full rewrite — real <thead> (N header rows) / <tbody> /
 *          <tfoot> (N footer rows), per-column alignment + width (<colgroup>), and
 *          colspan/rowspan output. A new "Table Options" tab adds display toggles
 *          (zebra / hover / bordered / compact / sticky header / caption + position)
 *          and visitor features (sorting, search/filter, pagination + rows-per-page,
 *          length selector, info line). The visitor features are powered by a small
 *          dependency-free enhancer (static/js/datatable.js) enqueued only when a
 *          table opts in and has no merged cells — no DataTables.net library is
 *          bundled (shortcodes 1.5.55).
 *
 * 2.9.12 - Table shortcode rebuild (part 1 of 2): the "tabular data" mode gets a
 *          brand-new spreadsheet editor, replacing the old cell-by-cell grid. It is
 *          a real <table> with inline-editable cells, a toolbar, drag-to-reorder
 *          rows, per-row/per-column popover menus (insert / duplicate / move / delete),
 *          per-column alignment, and Header-rows / Footer-rows counters. The editor is
 *          JSON-backed (a single hidden field) instead of one rendered option per cell,
 *          which is the groundwork for the upcoming HTML/Word + CSV import, Excel-paste,
 *          export, and DataTables (sort / search / paginate) features. Pricing-table
 *          mode is untouched (its legacy grid still embeds button / switch / pricing
 *          rows and serializes exactly as before), and tables saved previously keep
 *          working — FW_Option_Type_Table::get_value_from_json() reads the same
 *          {cols, rows, content} db shape and only adds optional align / colspan /
 *          rowspan / header-footer keys. Cell HTML is sanitized server-side with
 *          wp_kses (a safe inline allowlist) on save (shortcodes 1.5.54).
 * 2.9.5  - New `border-style-picker` option type: a popover-style dropdown that
 *          previews each Border Preset as a real bordered box next to its name
 *          (the .colb-{slug} CSS, already loaded in admin, paints itself). Mirrors
 *          the button-style-picker. The column's Styling → Border Preset field now
 *          uses it instead of a plain <select> (shortcodes 1.5.46). Saved value is
 *          unchanged (the .colb-{name} class), so rendering is unaffected.
 *
 * 2.9.0  - Border Presets: the per-preset Padding field is now the full `spacing`
 *          control (mode 'padding') — per-side (all / top / right / bottom / left)
 *          off your Spacing scale, for maximum customization, instead of a single
 *          all-sides value. css-tokens resolves each saved class (p-4 / pt-2 …) to a
 *          length via the spacing scale and still emits it WITHOUT !important, so the
 *          column's own Margin & Padding overrides it when set.
 *
 * 2.8.99 - Border Presets: each preset now also carries a Padding field, so a
 *          "card" preset isn't cramped out of the box (the four built-ins ship with
 *          sensible padding). It's emitted WITHOUT !important, so the column's own
 *          Margin & Padding (Styling tab) overrides the preset's padding whenever
 *          set — preset padding is the fallback, the column wins.
 *
 * 2.8.98 - New "Border Presets" feature for columns (mirrors Button Presets). A new
 *          `border-presets` option type (Theme Settings → General → Borders) lets you
 *          build reusable column "card" border styles — border (sides/style/width/
 *          color), corner radius and box-shadow, each with Default + Hover states and
 *          a {{SELECTOR}} Custom CSS field — with a live preview. Predefined presets
 *          (Card, Outline, Soft Shadow, Hover Lift) ship in framework/includes/
 *          presets.php; saved presets emit `.colb-{name}` + `.colb-{name}:hover` rules
 *          via css-tokens.php (folded into the cached presets stylesheet). Columns pick
 *          one in Styling → Border Preset (shortcodes 1.5.44 + theme 2.1.77).
 *
 * 2.8.97 - Popover option type: fixed the big gap before the help "?" icon — the
 *          input cell was full-width (width-type 'full') so the icon floated to the
 *          far right while the trigger was only 280px; it's now 'auto' (cell shrinks
 *          to the trigger) with a definite 260px trigger width. Plus column Mobile
 *          Order extended to 1–12 and Mobile Order / Position made short-selects
 *          (shortcodes 1.5.43, builder 1.2.48).
 *
 * 2.8.79 - Device preview toggle (Desktop / Tablet / Phone) for the page builder
 *          (see page-builder 1.6.19 + shortcodes 1.5.33): re-previews the canvas at
 *          each breakpoint so responsive column widths/offsets and masonry column
 *          counts are visible while editing. Plugin version bumped for the bundled-
 *          extension change.
 *
 * 2.8.75 - New "Masonry Section" Layout Element (see shortcodes 1.5.29 +
 *          page-builder 1.6.18): a section-like element that packs its columns
 *          into a left-to-right CSS-grid masonry so short items tuck up beside
 *          taller ones instead of leaving gaps. Plugin version bumped to ship the
 *          bundled-extension change.
 *
 * 2.8.71 - Plugin version sync for recent extension work + first page-builder
 *          modernization step. The editor canvas now lays out column rows with real
 *          flexbox (equal-height columns, clean wrapping) to match the flexbox
 *          frontend, gated by the existing Bootstrap-3 Legacy Mode setting — see
 *          page-builder 1.6.14. Also folds in the recent Column responsive layout /
 *          picker work (shortcodes 1.5.21–1.5.28). Going forward, the plugin version
 *          is bumped whenever a bundled extension changes, so it no longer lags behind.
 *
 * 2.8.70 - Popover option type: tabs. The `popover` type gains an optional `tabs`
 *          config (array of tab_key => array('label','options')) that organizes its
 *          hosted controls into Background-Pro-style tabs inside the panel. Tab
 *          grouping is visual only — all options share one name prefix, so the value
 *          stays a flat hash keyed by inner option id (ids must be unique across tabs).
 *          Single flat-option popovers keep their value passthrough; multi/tabbed ones
 *          keep a static trigger label (no single value to summarise). JS adds tab
 *          switching; CSS adds the tab bar styling.
 *
 * 2.8.66 - New `popover` option type. A reusable wrapper that collapses an inner option
 *          into a compact trigger field (a short read-only input showing the current
 *          selection); clicking it expands an in-flow panel hosting the real control —
 *          like the color picker's collapsed dropdown, but anchored inline (distinct
 *          from the modal `popup` type). With a single inner option it passes the value
 *          straight through, so it's a drop-in replacement for that option (same saved
 *          value); 2+ inner options yield a hash keyed by id (groundwork for a future
 *          tabs/array UX). The panel's inner HTML is rendered lazily into a
 *          data-options-template attribute and injected + re-`fw:options:init`'d on first
 *          open (the multi-picker trick), so heavy controls stay out of the DOM until
 *          opened. New files under framework/includes/option-types/popover/; registered
 *          in includes/hooks.php + autoload.php. First consumer: the Column element's
 *          width/offset pickers (shortcodes 1.5.21).
 *
 * 2.8.64 - Map option type: free OpenStreetMap picker fallback. When no Google Maps
 *          API key is configured, the location picker (the "Add/Edit Location" map used
 *          by the [map] shortcode and any other consumer of the `map` option type) now
 *          renders with Leaflet + OpenStreetMap instead of failing, with free Nominatim
 *          address search (geocode on Enter, reverse-geocode on pin drag) and a
 *          draggable marker. The saved value shape is identical to the Google picker
 *          (location/venue/address/city/state/country/zip + coordinates), so it is a
 *          true drop-in. When a key IS present the original Google picker is used
 *          unchanged. New file
 *          framework/includes/option-types/map/static/js/scripts-osm.js; selection logic
 *          lives in FW_Option_Type_Map::_enqueue_static(). Nominatim is called sparingly
 *          (only on Enter / field blur) to respect its ~1 req/sec usage policy. Pairs
 *          with the shortcodes extension's selectable Map Engine (shortcodes 1.5.4).
 *
 * 2.8.63 - Dynamic Content in the classic editor. The dynamic-tags picker, which until
 *          now appeared only next to Unyson option fields, is now also available on the
 *          native WordPress post editor: an "Insert Dynamic Content" button is added to
 *          the classic editor's media-buttons row (main "content" editor only — Rich
 *          Editor option fields keep their own relocated icon, no duplicate) and opens
 *          the exact same searchable popover, inserting the {{token}} into the active
 *          TinyMCE instance. To make tokens typed into the body actually work, {{tokens}}
 *          are now resolved on the frontend in post content and excerpts via new
 *          'the_content' / 'the_excerpt' filters (priority 9, before do_shortcode; gated
 *          to a literal '{{' so they no-op otherwise). New file
 *          framework/includes/dynamic-content/classic-editor.php (loaded from
 *          bootstrap.php); the classic-trigger wiring lives in the existing
 *          dynamic-content.js, reusing openPicker()/insertToken() with the editor's wrap
 *          element as a synthetic descriptor.
 *
 * 2.8.46 - Per-element Custom CSS + consolidated dynamic-CSS pipeline. Page-builder
 *          elements gain a "Custom CSS" field (Advanced tab) whose rules are scoped
 *          to the element via the `selector` keyword — so styling now travels inside
 *          the builder JSON and rides template export/import for free, rendering on
 *          every page the element appears on. A new per-page pipeline
 *          (framework/includes/dynamic-css.php) walks the builder JSON, scopes each
 *          element's CSS to its prefix-independent `.u{hash}` class, and writes one
 *          hashed `uploads/unysonplus/page-{id}-{hash}.css` (handle
 *          `unysonplus-dynamic`, inline fallback when uploads is read-only). It also
 *          folds the theme's page background + "Custom CSS (this page only)" in via
 *          the new `unysonplus_page_css` filter. The global layer (site-wide Custom
 *          CSS) folds into the existing presets file through the new
 *          `unysonplus_global_css` filter (css-tokens.php). Net result: view-source
 *          collapses from several inline <style> blocks to one global + one per-page
 *          stylesheet, both absorbed by the Asset Optimizer combiner.
 *
 * 2.8.41 - Dynamic Content. New Elementor-Pro-style "dynamic tags" capability:
 *          Text, Short Text, Textarea, and Rich Editor option fields now show a
 *          small picker icon (on by default; opt out per field with
 *          'dynamic_content' => false). Clicking it opens a searchable popover of
 *          grouped tags and inserts a token such as {{site_name}},
 *          {{current_year}}, {{post_meta|key=subtitle|fallback=N/A}}, or a
 *          WooCommerce product field. Tokens resolve to live values at render time
 *          inside the shortcode / page-builder path (filter
 *          'fw_shortcode_render_view:atts'), so the value inherits the view's
 *          existing wp_kses_post() / esc_attr() escaping. One registry is the
 *          single source of truth for both the picker and the resolver
 *          (framework/includes/dynamic-content/), and tags are registered on the
 *          'fw:dynamic-content:tags' filter — adding a provider later (ACF, Pods,
 *          Toolset) is one file, no core changes. v1 ships Core (Post / Site /
 *          Author / Date & Time) + an Unyson+ custom-field tag + WooCommerce
 *          (loaded only when WooCommerce is active). Unknown tokens are left
 *          literally (never fatal). See shortcodes 1.4.86.
 *
 * 2.8.38 - Page-Builder Templates: Import / Export. The existing Templates
 *          dropdown (next to "Sort Sections" in the page-builder header)
 *          now lets users export a saved template to a `.json` file and
 *          import a previously-exported file on another install, so
 *          templates can be shared between sites and teammates without a
 *          database export. Each of the three template kinds (Full,
 *          Sections, Columns) gets the same two affordances: a small
 *          download icon next to each saved-template row, and an
 *          "Import…" button above the saved list that opens an OS file
 *          picker. Exports wrap the saved option-row (`title` + `json` +
 *          `created`) in a small `_fw_template_export` envelope that
 *          records the kind, builder type, plugin version, and timestamp;
 *          imports validate that envelope before writing to `wp_options`
 *          and route a wrong-kind file to a clear error ("This is a
 *          Section template — open the Sections list to import it").
 *          File extension is plain `.json` so it's editable / readable.
 *          The deterministic option-key derivation (`md5($json)`) means
 *          re-importing the same file is idempotent — no duplicate rows.
 *          Implementation lives next to the existing save / load / delete
 *          AJAX handlers in each component class (the Full component is
 *          the only one that uses `check_ajax_referer`; Section / Column
 *          match their existing pattern of capability-check only). See
 *          builder ext 1.2.43, shortcodes 1.4.83.
 *
 * 2.8.26 - Drag-helper drift hunt, round 2. The user reported the 2.8.25
 *          diagnostic (disabling the three new section-sorter / section-
 *          like-factory page-builder admin assets) did NOT eliminate the
 *          drift — so those files are NOT the cause. RESTORED those enqueues
 *          in `class-fw-option-type-page-builder.php`. Then diffed every
 *          remaining drag-relevant file in CURRENT against OLD 2.7.40 and
 *          found two CSS-only additions that don't exist in OLD:
 *          (a) `builder/static/css/builder.css` — a new
 *              `.fw-option-type-builder .builder-items .builder-item
 *              .builder-item span { width: 100% }` rule;
 *          (b) `shortcodes/.../page-builder/static/css/styles.css` — new
 *              `border-radius: .25rem;` and `margin: .25rem;` declarations
 *              on `.fw-option-type-builder.fw-option-type-page-builder
 *              .builder-item-type`.
 *          Both are commented out behind TEMP diagnostic markers tagged
 *          with this version. The `.builder-item-type` margin is the prime
 *          suspect: that element is what jQuery UI clones as the drag
 *          helper, and a non-zero margin on the source shifts the cursor-
 *          to-helper offset each time the helper crosses into a connected
 *          sortable (each column's `.builder-items` is its own sortable —
 *          drift would accumulate per crossing exactly as the user
 *          describes). The `span { width: 100% }` rule is the secondary
 *          suspect because it changes the layout of nested column-item
 *          titles, which can shift items' offsets that jQuery UI sortable's
 *          intersect math reads each refresh. See builder 1.2.32,
 *          shortcodes 1.4.79, page-builder 1.6.10.
 *
 * 2.8.25 - TEMPORARY DIAGNOSTIC for the residual page-builder drag-helper
 *          drift (restored in 2.8.26 — was NOT the cause). After 2.8.24's
 *          revert of the override layer the user confirmed the drift is
 *          still present in CURRENT plugin but does NOT exist in an OLD
 *          2.7.40 copy, narrowing the regression to something added between
 *          OLD and CURRENT that is NOT in `builder.js` (the user copied
 *          OLD's builder.js in) or `initialize-builder.js` (reverted in
 *          2.8.24). The only delta in the page-builder static dir is three
 *          files I added earlier this session: `section-like-factory.js`,
 *          `section-sorter.js`, `section-sorter.css`. Their enqueues in
 *          `class-fw-option-type-page-builder.php` are commented out with
 *          TEMP markers so the user can confirm whether disabling them
 *          eliminates the drift. The PHP-side registry / base class are
 *          left in place. Restore (or replace with a real fix) next round
 *          once we know which side it's on. See shortcodes 1.4.78,
 *          page-builder 1.6.9.
 *
 * 2.8.24 - Page Builder drag helper drift — actually fixed by reverting the
 *          override layer we'd been iterating on. The user pulled down an OLD
 *          copy of unysonplus (2.7.40) and confirmed the drift bug does NOT
 *          exist there; jQuery UI's default positioning works fine in this
 *          DOM/CSS context. Copying OLD's builder.js into CURRENT didn't
 *          help either — proving the regression was in initialize-builder.js
 *          (where the thumbnail draggable lives) and in the additions to
 *          builder.js's sortable, not in the rearrange/FLIP logic. Removed:
 *          • `appendTo: '.fw-option-type-builder'` from both the draggable
 *            and the sortable
 *          • the `_fwHelperAnchor` cursor-vs-helper anchor recording in
 *            both `start` handlers
 *          • the `drag` callback (draggable) and `sort` callback (sortable)
 *            that re-pinned the helper every tick using that anchor
 *          • the anchor cleanup in both `stop` handlers
 *          The diagnostic overlay from 2.8.22 caught the actual flaw in the
 *          override (anchor drifting to `(-804, -269)` via repeated re-
 *          recording across nested-sortable start events). Rather than
 *          patch it further, dropping the entire override layer restores
 *          OLD's behavior — which works. Independently-correct
 *          improvements are KEPT: the simple→column hierarchy guard, the
 *          defensive FLIP-transform sweep at drag start, the
 *          `refreshPositions(true)` in the guard's early-return, and the
 *          `window.fwDragDebug` overlay (gated behind `_fwDragDebug`, off
 *          by default). See builder 1.2.31.
 *
 * 2.8.23 - Page Builder drag helper drift — actually fixed this time. The
 *          diagnostic overlay from 2.8.22 caught the root cause within one
 *          test drag: jQuery UI fires the SORTABLE's `start` event once per
 *          nested-sortable hand-off, not once per drag. The overlay showed
 *          `s.start: 15` for a 7-pass test (each column has its own nested
 *          sortable, and `connectToSortable` fires that sortable's
 *          `_mouseStart` the first time the cursor enters it). Our `start`
 *          handler was re-recording the cursor-vs-helper anchor on every
 *          fire — and at that moment jQuery UI has only partially
 *          initialized the sortable's offsets, so each re-record captured
 *          a slightly-wrong anchor. The overlay also showed the anchor
 *          drifting to nonsense values like `(-804, -269)` while the
 *          per-tick re-pin was correctly aligning the helper to that bad
 *          anchor — explaining the visible drift while every tick read
 *          delta ≈ 0. Fix: record the anchor only if it isn't already set,
 *          and clear it on `stop`. Each drag now uses a single anchor
 *          captured at the FIRST `start` (draggable's, or sortable's if
 *          it's an existing-item reorder). See builder 1.2.30.
 *
 * 2.8.22 - Debug-only: add a diagnostic overlay for page-builder drag-helper
 *          drift. OFF by default. Toggle by typing
 *          `window._fwDragDebug = true` in the browser console; the overlay
 *          appears in the top-right and shows real-time event counters
 *          (draggable.start/drag/stop, sortable.start/sort/stop), the
 *          cursor / helper / anchor coordinates, and the delta (cursor -
 *          anchor - helperOffset). The delta should stay at (0, 0); any
 *          non-zero value is the drift, in pixels and direction, with the
 *          overlay border / text turning amber the instant it appears so
 *          the offending tick is unambiguous. `window.fwDragDebug.reset()`
 *          zeroes the counters between drags. Set `_fwDragDebug = false`
 *          to remove the overlay (no other side-effects). See
 *          builder 1.2.29.
 *
 * 2.8.21 - Page Builder drag helper: residual drift across column crossings
 *          (visible in the user's 7-pass screenshot stack, helper moving
 *          progressively higher above the cursor) is now eliminated by
 *          treating the cursor as the sole source of truth for helper
 *          position every mousemove tick. The 2.8.19 / 2.8.20 rounds
 *          stabilized the helper's offsetParent, which knocked the drift
 *          down but couldn't reach the part coming from jQuery UI
 *          sortable's internal offset bookkeeping in nested-sortable
 *          hand-offs (each column has its own nested sortable; small
 *          recompute errors compounded per crossing). At drag start we now
 *          record where the cursor sits inside the helper, then on every
 *          `drag` (draggable) / `sort` (sortable) tick we forcibly re-set
 *          `style.left` / `style.top` so the cursor stays at that anchor.
 *          The override runs after jQuery UI's own _mouseDrag positioning,
 *          so it overwrites whatever it computed before the browser paints.
 *          See builder 1.2.28.
 *
 * 2.8.20 - Follow-up to 2.8.19: the previous fix appended the drag helper
 *          to `<body>` to stabilize its offsetParent, but that pulled the
 *          floating thumbnail outside the scoped CSS
 *          (`.fw-option-type-builder .builder-item-type { … }`) so the
 *          clone rendered without its border / box. Switch the appendTo
 *          target to `.fw-option-type-builder` instead — same stability
 *          benefit (only the inner `.builder-items-types` flips between
 *          static and fixed positioning, not the outer builder wrapper),
 *          and the helper stays inside the scoped CSS so it keeps its
 *          styling. The two companion fixes from 2.8.19 (defensive
 *          `.builder-item` transform sweep at drag start and
 *          `this.refreshPositions(true)` in the simple-only guard's
 *          early-return) are unchanged. See builder 1.2.27.
 *
 * 2.8.19 - Page Builder: the dragged element's visual helper (the floating
 *          clone that follows the cursor) no longer drifts away from the
 *          cursor across container crossings. Two contributing fixes:
 *          (1) the helper is now pinned to <body> for both the thumbnail
 *          draggable and the items sortable (`appendTo: 'body'`), so its
 *          offsetParent stays the document instead of the metabox tabs
 *          wrapper that the data-fixed-header logic flips between static
 *          and position:fixed on scroll; each previous flip shifted the
 *          helper relative to the cursor and the shifts accumulated.
 *          (2) On every drag start, all `.builder-item` elements have any
 *          residual inline `transform` / `transition` styles cleared
 *          defensively, so a previously-interrupted FLIP animation can't
 *          leave a stale visual offset that throws off
 *          getBoundingClientRect reads during the new drag. Also, the
 *          simple-only hierarchy guard's early-return now calls
 *          `this.refreshPositions(true)` to keep cached item rects in
 *          sync. See builder 1.2.26.
 *
 * 2.8.18 - Page Builder: dragging a Content/Media Elements thumbnail (e.g.
 *          Text Block) from the metabox-holder no longer makes the columns
 *          inside surrounding sections shuffle while the cursor passes over
 *          them. The simple→column hierarchy guard inside _rearrange (added
 *          1.2.21, re-scoped 1.2.24) read the dragged type only from
 *          `fw-source-item-type` stashed in `start` — which is set for
 *          existing-item reorders but skipped for thumbnail draggables. Now
 *          the guard also reads `data-builder-item-type` (the same attribute
 *          the receive handler uses) as a fallback, so the simple→column
 *          rule applies to first-drop too. Placeholder only commits when the
 *          cursor enters a column's .builder-items; while it's over section /
 *          root areas, it stays put — no column re-layout under the cursor.
 *          Layout Elements (Section/Column thumbnails) are unaffected. See
 *          builder 1.2.25.
 *
 * 2.8.17 - button-style-picker gained an `allow_none` config (default true). When
 *          false, the dropdown has no empty "— None —" row and an empty value is not
 *          accepted (falls back to the field default). Lets the Button shortcode's
 *          Style picker require a real preset; Size/other pickers keep their None /
 *          Normal row unchanged. Pairs with shortcodes 1.4.77.
 *
 * 2.8.16 - Fixed: custom hover animations had no effect (front end or admin preview)
 *          because the preset-CSS cache key (unysonplus_preset_css_hash) did not
 *          include the button_animations option — so a new/changed animation reused
 *          the existing presets-{hash}.css and the generated .btnfx-c-{slug} rules
 *          were never written. The hash now factors in the custom animations
 *          (schema bumped 6 to 7 to force one regeneration), so saving an animation
 *          produces a fresh stylesheet and the effect applies everywhere.
 *
 * 2.8.15 - Custom button hover animations: users can now add their own effects from
 *          Theme Settings to Buttons to Hover Animations (a repeatable Name + CSS
 *          editor) - no child stylesheet needed. presets.php gains the getter
 *          (unysonplus_get_custom_hover_animations, key button_animations), a slug
 *          map (id to slug, -2/-3 dedupe), and 5 seeded sample effects (Pulse Ring,
 *          Swing, Rubber Band, Squeeze, Raise and Glow) as editable references.
 *          css-tokens.php generates each into the preset stylesheet as
 *          `.btnfx-c-{slug}`: the CSS uses {{BTN}} (the button) and {{ANIM}} (a
 *          per-entry namespaced keyframes name) tokens, and is scrubbed of markup /
 *          @import / javascript: / expression() before output. Loads on the front
 *          end and in admin (rides the existing presets-*.css), so customs preview
 *          in the Hover Animation dropdown alongside the 22 built-ins. Pairs with
 *          shortcodes 1.4.71.
 *
 * 2.8.14 - New `button-hover-animation` option type: a gradient-style dropdown whose
 *          panel lists the hover effects in a 3-column grid of real buttons, each
 *          animating on hover; selecting writes the btnfx-* class. Replaces the
 *          button-style-picker reuse for the Button shortcode's Hover Animation
 *          field, which never animated: option-type instances are singletons, so the
 *          base enqueue_static() runs _enqueue_static() once per TYPE (with the first
 *          field's option). With three button-style-picker fields, the Hover field's
 *          per-field effect stylesheet was never enqueued. A dedicated type used by a
 *          single field always runs its own _enqueue_static, so it loads its `fx_css`
 *          reliably in the options modal. Registered in autoload.php + hooks.php.
 *          Also reverted the now-unused demo_hover / demo_css machinery from
 *          button-style-picker (kept its name-in-preview + uniform min-width).
 *          Pairs with shortcodes 1.4.69.
 *
 * 2.8.13 - button-style-picker rows/trigger now show the choice NAME inside the
 *          preview button itself (e.g. a Primary button labelled "Primary") instead
 *          of a fixed "Button" text with the name in a separate column — so the
 *          collapsed trigger reads the preset name, not "Button". Previews also get
 *          a uniform min-width and centered text for a tidy, aligned list. The None
 *          row reflects the choices[''] label when set (e.g. "Normal"/"None").
 *          Pairs with shortcodes 1.4.68.
 *
 * 2.8.12 - Fixed: button-style-picker hover-animation previews didn't animate in the
 *          options form because the effect CSS was only enqueued with the rendered
 *          shortcode (front end / canvas), not in the page-builder modal. Added a
 *          `demo_css` config (a stylesheet URL) that the picker now enqueues in its
 *          own _enqueue_static, so the preview effect classes load right where the
 *          options render. The Button shortcode passes its hover-fx.css URL. Pairs
 *          with shortcodes 1.4.67.
 *
 * 2.8.11 - button-style-picker gained an optional `demo_hover` mode: when set, its
 *          previews are allowed to animate and the JS plays each choice's hover
 *          effect (toggling `.is-hover` on the preview) while a row or the trigger
 *          is hovered/focused. Used by the Button shortcode's new Hover Animation
 *          picker so each option shows its motion live. Backward compatible -
 *          default off, so the Style/Size pickers still render static previews.
 *          Pairs with shortcodes 1.4.66.
 *
 * 2.8.10 - The default "Gradient" button preset now also sets a hover gradient: the
 *          hover state reverses the default (swapped stops, same 135deg) for a clear
 *          state change on hover. Because gradients cannot CSS-fade, a distinct flip
 *          reads better than a subtle shift. The $gradient default-preset helper now
 *          takes a hover gradient argument.
 *
 * 2.8.09 - Fixed: a blank Gradient V2 was auto-seeding itself to the starter
 *          (linear, 90deg, #2A7B9B to #EDDD53) instead of staying empty, so every
 *          button-preset state appeared to have a gradient by default. Cause: the
 *          panel pre-rendered the starter stop ROWS even when the value was empty;
 *          wpColorPicker/iris fired its (debounced) change callback on those during
 *          init, which ran updateStopColor to seedIfEmpty and committed the starter.
 *          Now the view renders NO stop rows and a blank preview when the value is
 *          empty (so no pickers exist to fire), an `initializing` guard blocks any
 *          init-time event from seeding (seedIfEmpty / updateStopColor are no-ops
 *          until the first paint finishes), and updateStopColor ignores no-op
 *          re-sets. The starter gradient is still one click away via "+ Add color
 *          stop" / clicking the preview / changing mode or angle (seed-on-
 *          interaction). NOTE: presets already SAVED with the bad starter keep it
 *          until cleared - clear a field with its x, or use Reset Tab Options on the
 *          Buttons tab.
 *
 * 2.8.08 - Added a default "Gradient" button preset (after Danger Outline) that
 *          showcases the new per-state Background Gradient: white text on a 135deg
 *          #667EEA to #764BA2 linear gradient, no solid bg or border. The gradient
 *          sits on the default state with hover left to inherit it (gradients cannot
 *          CSS-transition, so this avoids a hover snap). All other default presets
 *          remain gradient-free - the gradient field stays unset by default, so a
 *          freshly added preset has no gradient until one is chosen.
 *
 * 2.8.07 - Button Presets can now have a per-state Background Gradient (Theme
 *          Settings to Buttons to a preset). Each state tab (default/hover/active/
 *          focus/disabled) gains an optional Gradient V2 field; it is blank by
 *          default, so existing presets are unchanged. When set, the gradient is
 *          emitted as `background-image` LAYERED over the solid bg_color, which
 *          stays as a fallback (the emitter uses background-color, not the
 *          background shorthand, so the two coexist). Added to the option type's
 *          state_options()/state_option_rows() (parsing + asset enqueue are generic,
 *          so no extra wiring), emitted from css-tokens.php's shared $state_decls
 *          via FW_Option_Type_Gradient_V2::to_css() (so it lands on every state and
 *          on the front end, not just admin), and the in-box live preview JS was
 *          taught to read the gradient JSON and paint background-image too. Two
 *          intentional caveats: gradients do NOT fade on hover (CSS cannot
 *          transition background-image, and the preset transition is transition:all),
 *          and gradient stops are literal hex/rgba, so unlike a bg_color palette
 *          slug they do not re-color when a color preset is edited.
 *
 * 2.8.06 - Fixed: dragging a Gradient V2 preview stop marker updated the preview,
 *          the stop's position field, and the saved value, but not the read-out CSS
 *          text input — so the shown code lagged behind the gradient. The drag
 *          handler now writes the rebuilt CSS to the output field (and lastValidCSS)
 *          alongside the preview each move. It stays inline rather than calling
 *          render(), because render() rebuilds the markers and would kill the
 *          in-progress drag.
 *
 * 2.8.05 - Widened the Gradient V2 control 1.5x (min-width 320px to 480px) so the
 *          full generated gradient CSS string fits in the output field without
 *          truncation. The field grows with the container (flex), so this is the
 *          only knob needed; capped at 100% so it still fits narrow option columns.
 *
 * 2.8.04 - Gradient V2's CSS-output field is now EDITABLE: a developer can paste or
 *          type a gradient string and the editor parses it back, auto-adjusting the
 *          mode, angle, and color stops to match. This is the inverse of the
 *          existing builder, so the common "paste a basic 2-stop gradient, then
 *          tweak" flow finally works. A new JS parseGradient() handles
 *          linear/radial, both `Ndeg` and `to top|right|bottom|left` (+corner)
 *          directions, optional stop positions (auto-distributed CSS-style), and
 *          rgba() commas via a depth-aware top-level split. Any color the saved
 *          value cannot store (named like red, hsl(), 8-digit hex) is auto-resolved
 *          through the browser to rgb()/rgba(), which both the picker and the PHP
 *          sanitizer accept; valid 3/6-digit hex is preserved. Updates are live as
 *          you type (preview, markers, mode, angle) and the full stop-row list
 *          rebuilds on blur/Enter, so the color pickers are not destroyed on every
 *          keystroke. Unparseable text shows a red border and, on blur, reverts to
 *          the last valid gradient (empty text clears to no gradient). Pure
 *          addition - the hidden JSON stays the submitted value, so the saved shape
 *          and _get_value_from_input are unchanged.
 *
 * 2.8.03 - Gradient V2 now renders as a compact control: a single read-only input
 *          showing the generated CSS string (e.g. `linear-gradient(90deg, ...)`),
 *          which opens a dropdown panel holding the full editor (preview, mode,
 *          angle, stops). It is BLANK BY DEFAULT — the saved value is now an empty
 *          form (zero stops), and an empty value means "no gradient". A starter
 *          2-stop gradient is shown inside the panel for editing but is only
 *          committed to the value on first interaction (seed-on-interact), so a
 *          field left untouched stays blank. Clearing (the x on the input, or
 *          "Clear gradient" in the panel) returns it to empty. Added a canonical
 *          `FW_Option_Type_Gradient_V2::to_css($value)` static builder (returns ''
 *          when fewer than 2 stops) so consumers share one gradient-string source.
 *          Because of this, Background Pro drops its "Enable Gradient" switch: the
 *          gradient layer is on iff the gradient-v2 value compiles to a real
 *          gradient (>= 2 stops), checked via to_css(); its parser now sanitizes
 *          the sub-value through the gradient-v2 type. The initial paint no longer
 *          fires a change event, so loading a screen with a gradient field doesn't
 *          falsely mark the form dirty. Demo entries updated to show the blank
 *          default (demo.php) and a pre-set round-trip (demo-2.php) - theme 2.1.37.
 *
 * -----------------------------------------------------------------------------
 * 2.8.02 - New reusable `button-style-picker` option type: a custom dropdown
 *          whose trigger and every row render a REAL button preview (a
 *          `<span class="btn {value}">`), so picking a Button Style or Size
 *          shows the actual button instead of a plain text option. It stores the
 *          same class string a select did (e.g. `btn-primary`), so it's a
 *          drop-in — consuming views are unchanged. Works because the generated
 *          preset CSS is already enqueued in wp-admin (css-tokens.php), and the
 *          page-builder modal is an inline overlay, so the previews paint
 *          themselves with no extra admin-CSS emitter. Modeled on the compact
 *          color picker (trigger + listbox panel, hidden input, outside/Esc
 *          close, arrow-key a11y, fw:options:init). The Button shortcode's Style
 *          and Size fields now use it. Registered in autoload.php + hooks.php.
 *          Pairs with shortcodes 1.4.65.
 *
 * -----------------------------------------------------------------------------
 * 2.7.164 - Button preset CSS classes are now readable, name-based slugs instead
 *           of the opaque numeric id: a preset named "Primary" emits
 *           `.btn-primary` (and `.btn-outline-primary`), "Primary Outline" →
 *           `.btn-primary-outline`. Because these intentionally match Bootstrap's
 *           own class names and the generated rules carry !important, they
 *           override Bootstrap's button styles - the whole point of the presets.
 *           New helper unysonplus_button_preset_slug_map() (presets.php) is the
 *           single source of truth: name → lower/hyphenated slug, duplicate names
 *           get -2/-3, empty/symbol names fall back to the id. css-tokens.php
 *           (rule generation), sc_get_button_style_choices() (the Style dropdown's
 *           saved value) and the dropdown-preview emitter all consume it so the
 *           class, the saved value and the preview stay in lockstep. NOTE: not
 *           back-compatible - buttons saved before this reference the old
 *           .btn-{id} class and must have their Style re-picked. Pairs with
 *           shortcodes 1.4.64.
 *
 * -----------------------------------------------------------------------------
 * 2.7.163 - sc_needs_wrapper() fix: shortcodes that gate their wrapper on it
 *           (Text Block, Special Heading) were ALWAYS wrapping, because the
 *           Text/Background color compact-picker atts are always a non-empty
 *           array { predefined:'', custom:'' } even when nothing is picked, so
 *           the naive empty() check tripped. Now those colors run through
 *           sc_normalize_color_value() (set only if it yields a class or inline
 *           style), the remaining scalar styling keys keep the empty() test, and
 *           an Animations check was added (animation.enable === 'yes'). Result: a
 *           Text Block with no Styling/Animation/Advanced option set renders its
 *           raw editor content with NO wrapping <div>. Pairs with shortcodes 1.4.63.
 *
 * -----------------------------------------------------------------------------
 * 2.7.162 - Text Block shortcode: restored the Styling tab (Text/Background
 *           color, Font Size, Margin & Padding) that 2.7.157 wrongly removed.
 *           The intent was only to skip the wrapper when no styling is used -
 *           which the view already does via sc_needs_wrapper() (returns false
 *           when all styling/advanced atts are empty, so plain editor content
 *           renders unwrapped). No view change. Pairs with shortcodes 1.4.62.
 *
 * -----------------------------------------------------------------------------
 * 2.7.161 - Button shortcode: properly fixed icon vertical centering. The prior
 *           two attempts hinged on a `.btn:has(> i)` selector to switch the
 *           button to flex; when :has() did not apply the icon fell back to
 *           vertical-align and sat low - which is why those edits looked like
 *           they did nothing. The view now adds an explicit `has-icon` class to
 *           the button when an icon is present, and the CSS keys the flex
 *           centering off `.btn.has-icon` (icon is its own flex item, centered
 *           on the cross-axis; Dashicons reset to a 1em box that tracks
 *           font-size). Pairs with shortcodes 1.4.61.
 *
 * -----------------------------------------------------------------------------
 * 2.7.160 - Button shortcode: actually fixed the icon vertical centering. The
 *           2.7.159 attempt forced the icon to line-height:1, which shrank its
 *           line box below the label's and dropped the glyph low. Icons now
 *           inherit the button's line-height, so icon and label share an
 *           equal-height line box and their baselines coincide (icon fonts are
 *           baseline-designed like text) - clean centering across every pack.
 *           Pairs with shortcodes 1.4.60.
 *
 * -----------------------------------------------------------------------------
 * 2.7.159 - Button shortcode: fixed the slight vertical misalignment of button
 *           icons. Icon-font glyph boxes aren't centered on the text cap-height,
 *           so vertical-align:middle left them a touch low. When a button
 *           contains an icon it now lays out as an inline-flex row centered on
 *           the cross-axis, with gap for icon/label spacing (icons use
 *           line-height:1 so flex centering lands on the glyph). Pairs with
 *           shortcodes 1.4.59.
 *
 * -----------------------------------------------------------------------------
 * 2.7.158 - Button shortcode: scoped the Styling-tab spacing field to MARGIN
 *           only (mode => margin). Its padding picks emitted pt-/pb- padding
 *           classes onto the same <a> that carries the Size preset (.btn-xl), which
 *           already owns padding - the two collided. Padding now belongs solely
 *           to the Size preset; the instance controls margin (placement). The
 *           spacing option type's margin mode also resets the padding subtree on
 *           save, so re-saving an old button drops its stray p* picks. Pairs with
 *           shortcodes 1.4.58.
 *
 * -----------------------------------------------------------------------------
 * 2.7.157 - Text Block shortcode: removed its Styling tab (Text/Background color,
 *           Font Size, Margin & Padding). The block's content is authored in the
 *           WP editor, which carries its own formatting - a separate styling
 *           layer was redundant and could conflict with the editor output. The
 *           view already only wraps when a CSS ID/Class is set, so no render
 *           change was needed. Pairs with shortcodes 1.4.57.
 *
 * -----------------------------------------------------------------------------
 * 2.7.156 - Button icon + Style-dropdown fixes. (1) The button view now enqueues
 *           the icon-v2 pack CSS when an icon is used, so Linecons / Entypo /
 *           Linearicons / Typicons / Unycon render on the front end (they have no
 *           global handle, unlike Font Awesome / Dashicons - same enqueue the
 *           icon / icon-box views already do). (2) Button icons now scale with the
 *           button font-size and align on the baseline; Dashicons shipped a fixed
 *           20px box that ignored the size - the button stylesheet (now actually
 *           enqueued) resets i / i::before to inherit font-size + line-height. (3)
 *           The Button Style dropdown previews each preset again: its admin
 *           emitter read the old flat normal_*_color keys; rewritten to read the
 *           nested states.default compact values (predefined slug or custom hex),
 *           with outline/link presets previewing as colored text. Removed the
 *           dead sc-button-outline emitter branch. Pairs with shortcodes 1.4.56.
 *
 * -----------------------------------------------------------------------------
 * 2.7.155 - Default button presets: Primary is now the first preset (most-used,
 *           so it leads the Style dropdown) - only the array order changed, each
 *           preset keeps its id so saved buttons stay mapped. Added a "Link"
 *           text-button preset (Bootstrap .btn-link style): no background, no
 *           border, Primary-colored text that darkens to Indigo on hover - for
 *           tertiary / low-emphasis actions. One default; users duplicate it for
 *           other colors.
 *
 * -----------------------------------------------------------------------------
 * 2.7.154 - Button shortcode Styling tab tidied now that the Button Style preset
 *           owns colors per state. Removed the redundant Outline Style picker
 *           (outline presets already appear in the Style dropdown) and the three
 *           per-element color overrides (Link / Label / Icon Color) - icons
 *           inherit the preset text color via currentColor. Added a single
 *           Margin & Padding (spacing) field that auto-applies margin/padding
 *           utility classes to the button wrapper (same pattern as icon-box). Buttons
 *           saved with the old outline/color atts still render - those atts are
 *           simply ignored and color comes from the Style preset. Shared helpers
 *           (sc_color_field_compact / sc_extract_styling_atts) untouched - other
 *           shortcodes keep their color fields. Pairs with shortcodes 1.4.55.
 *
 * -----------------------------------------------------------------------------
 * 2.7.153 - Button width options. Size presets (Theme Settings → Buttons →
 *           Sizes) gained optional Min Width + Max Width unit-input fields,
 *           emitted as min-width / max-width on the .btn-{slug} rule by
 *           css-tokens.php (min-width keeps short-label buttons from looking
 *           tiny; max-width caps growth). The Button shortcode's binary "Full
 *           Width" switch became a "Button Width" select - Auto (fit content) /
 *           Full Width / Custom - with a Custom Width unit-input
 *           (px/%/rem/em/vw) applied as an inline width. Buttons saved before
 *           this still work: the view falls back to the legacy `block` value.
 *
 * -----------------------------------------------------------------------------
 * 2.7.152 - Button + color preset defaults refresh. Renamed the first solid
 *           button preset Default -> Secondary (bg + border = the Secondary
 *           color preset, white text); Primary now uses the Primary color preset
 *           for bg + border. Added six OUTLINE button presets (Secondary /
 *           Primary / Success / Info / Warning / Danger Outline): transparent
 *           background, text + border the same color, 2px solid border (2px reads
 *           as a deliberate outline, not a hairline), filling with the color on
 *           hover. Changed the Red color preset default from #d9534f to #dc3545
 *           (Bootstrap danger).
 *
 * -----------------------------------------------------------------------------
 * 2.7.151 - Buttons fixes + polish. (1) Fixed two errors in Theme Settings →
 *           Buttons → Sizes: the box-title template threw "[Template Error]
 *           padding is not defined" because it evaluated the unit-input
 *           composite (object) fields inside Underscore's with()-scope - the
 *           title now shows just the size name; the visual preview span is
 *           styled server-side by sc_emit_button_size_preview_saved_css(). (2)
 *           Hardened the core text / short-text option type against an array
 *           value (stale data from a key that became a composite type) - it no
 *           longer triggers "Array to string conversion" in simple.php, coercing
 *           non-scalars to ''. (3) Button Presets: gave Transition a sensible
 *           250ms default, and the whole preset header bar is now clickable to
 *           collapse/expand (drag handle + remove icon excluded).
 *
 * -----------------------------------------------------------------------------
 * 2.7.150 - Button Presets: the preset row TITLE is now the quick preview button
 *           itself (it carries the generated style + shows the name), removing
 *           the redundant separate header chip from 2.7.149. Also fixed the
 *           default button color presets, which still used the pre-tabs flat
 *           shape (normal_ / hover_ keys — and the theme copy used raw hex, which
 *           the migration turned into invalid picker values): both
 *           unysonplus_default_button_color_presets() (plugin) and
 *           unysonplus_option_button_color_defaults() (theme, now delegating to
 *           the plugin) emit the nested `states` skin shape with compact-picker
 *           { predefined: <color-preset-slug> } values, so Default/Primary/
 *           Success/Info/Warning/Danger paint correctly out of the box with
 *           sensible hover shifts; active/focus/disabled inherit default.
 *
 * -----------------------------------------------------------------------------
 * 2.7.149 - Buttons UI polish + a typography default fix. typography-v2's
 *           default letter-spacing was -1 (an odd, slightly-tightened default);
 *           changed to 0 so new typography/font fields start neutral. In the
 *           Button Presets editor: removed the stray full-width dividers between
 *           fields inside each state tab (the framework's per-option
 *           border-bottom) by suppressing it within the panels - the same effect
 *           as wrapping them in a borderless `group`, without restructuring the
 *           render. Restored a live mini preview button in each preset's header
 *           (the `.fw-bp-head-btn` chip) so the default-state look is visible
 *           even while the box is collapsed; it shares the generated `.uid` rule
 *           with the body preview and tracks the name + styling live.
 *
 * -----------------------------------------------------------------------------
 * 2.7.148 - Buttons: split the two axes cleanly so they stop overlapping -
 *           Button Presets = the SKIN (per-state colors, border color/style/
 *           width, box-shadow, font identity: family/weight/letter-spacing/
 *           style, text-transform, transition), and Sizes = the DIMENSIONS
 *           (font-size, line-height, padding Y/X, border-radius). Composed like
 *           Bootstrap: class="btn btn-primary btn-lg". Removed font-size, line-
 *           height, padding, margin and border-radius from the preset (radius
 *           now tracks size, per Bootstrap; border-width stays on the skin).
 *           Sizes font-size / padding Y+X / radius are now unit-input controls
 *           (px/em/rem, % for radius); border-width dropped from Sizes. The size
 *           preset data shape changed (padding{t,r,b,l} -> padding_y/padding_x;
 *           font_size/radius -> {value,unit}); css-tokens + the admin size-
 *           preview emitters read it via FW_Option_Type_Unit_Input::to_string
 *           with back-compat for the old string/4-side shapes, so existing DBs
 *           keep rendering. Added a remove-confirmation to button preset rows
 *           (the remove icon sits next to the collapse caret). Preset-CSS cache
 *           schema bumped to 6.
 *
 * -----------------------------------------------------------------------------
 * 2.7.147 - Button Presets polish: the live-preview button no longer stretches
 *           to the full height of the preview stage (it was a flex child picking
 *           up the stage's cross-axis stretch) - pinned with align-self:center +
 *           flex:0 0 auto. Border Width and Border Radius in each state tab now
 *           use the new `unit-input` control (px/em/rem, plus % for radius)
 *           instead of free-text, so values are picked as number + unit. The live
 *           preview JS and css-tokens generator both read the unit-input
 *           { value, unit } shape (via FW_Option_Type_Unit_Input::to_string) and
 *           still accept legacy plain-string values.
 *
 * -----------------------------------------------------------------------------
 * 2.7.146 - New reusable `unit-input` option type: a numeric field paired with a
 *           small, fully-configurable unit dropdown (defaults px / em / rem).
 *           Supply any `units` list - a sequential list or value=>label map - so
 *           it works for CSS lengths, physical measurements (inches/cm/m) or
 *           temperature (c/f). Optional `min`/`max`/`step` number attributes
 *           (step defaults to "any" for decimals). The saved value is always
 *           array{value,unit} for clean round-trips; a static helper
 *           FW_Option_Type_Unit_Input::to_string($v, $separate) compiles it -
 *           `separate=false` joins ("24px"), `separate=true` space-separates
 *           ("24 inches"). Registered in autoload.php + hooks.php. Theme demo
 *           files gained live examples for unit-input (x2), box-shadow and
 *           button-presets.
 *
 * -----------------------------------------------------------------------------
 * 2.7.145 - Button Presets + box-shadow UI polish. box-shadow (already its own
 *           reusable option type) now lays its preview out as a single 300px-wide
 *           row on top with the X/Y/blur/spread/color/inset controls beneath it.
 *           In each Button Preset state tab the fields are now grouped into tidy
 *           rows: Background Color | Text Color side by side, Border Color |
 *           Border Width on one row, Border Style | Border Radius on the next;
 *           Text transform became a short-select dropdown (was free text). Preset
 *           boxes now start collapsed, and expanding one refreshes its CodeMirror
 *           (custom CSS) + live preview so nothing renders zero-height after
 *           initialising while hidden. No saved-value shape change.
 *
 * 2.7.144 - Button Presets, take 2 - turned each preset into a polished button
 *           builder. Preset boxes are now collapsible (click the title or caret).
 *           The five interaction states became real Background-Pro-style content
 *           TABS (Default/Hover/Active/Focus/Disabled); each tab holds everything
 *           style-related for that state: Background/Text colors (compact picker),
 *           Text transform, Spacing (margin+padding), Border Color|Width and
 *           Border Style|Radius (two columns each), and a Box Shadow. The three
 *           typography text fields collapsed into one Font control (typography-v2
 *           with Script/subset + Color hidden). Transition stays a shared field;
 *           Custom CSS now uses the code-editor with a {{SELECTOR}} sample
 *           placeholder that clears on focus. Two new reusable option types back
 *           this: `box-shadow` (structured x/y/blur/spread/color/inset with a
 *           live CSS-string + shadow preview, reuses the rgba picker) and a
 *           `placeholder` config added to `code-editor`. css-tokens.php now
 *           compiles the nested per-state shape - resolving spacing utility
 *           names to lengths, typography, border, radius and box-shadow into
 *           .btn-{id} + :hover/:active/:focus/:disabled (+ .btn-outline-{id}) -
 *           and migrates pre-tab flat presets (normal_ / hover_ keys) into the
 *           new states shape on read. Registered box-shadow in autoload.php +
 *           hooks.php. CSS preset-cache schema bumped to 6.
 *
 * 2.7.143 - Button Presets: new `button-presets` option type (a specialised
 *           addable-box) powering Theme Settings → General → Buttons, replacing
 *           the old "Button Color Presets" addable-box. Each preset now has a
 *           live, auto-updating preview and a rich schema: typography, box
 *           geometry, Default/Hover/Active/Focus/Disabled colors (via the
 *           predefined-colors compact picker so a preset can reference a palette
 *           color OR a custom value), box-shadow, and a {{SELECTOR}}-aware custom
 *           CSS field. css-tokens.php was extended to emit the matching
 *           .btn-{id} / :hover / :active / :focus / :disabled / .btn-outline-{id}
 *           rules and now resolves the compact picker's {predefined,custom}
 *           shape (custom hex wins; otherwise slug→hex) while staying
 *           back-compatible with the previous slug-string values. Registered
 *           via autoload.php (require) and hooks.php (FW_Option_Type::register).
 *
 * 2.7.142 - Reset Tab Options: fixed the reset hitting the wrong tab (the open
 *           tab appeared untouched). Tab ids are NOT unique across the form - the
 *           theme reuses keys like "tab_layout" under General, Header and Footer -
 *           but the handler looked the id up globally with fw_collect_options(),
 *           which keeps the last match, so e.g. resetting General > Layout reset
 *           Footer > Layout instead. The client now sends the full open-tab path
 *           (e.g. "general_settings_container/tab_layout"), built from jQuery UI's
 *           .ui-state-active nav anchors, and _form_save() walks that path level by
 *           level - scoping each lookup to the parent's subtree - to land on the
 *           exact tab. If the open tab can't be determined the reset is refused
 *           rather than risking a wide wipe.
 *
 * 2.7.141 - Reset Tab Options: fixed the reset failing with a blank error modal
 *           after the "Resetting" popup. In _form_save() the tab lookup passed an
 *           inline assignment ($x = $this->get_options()) as the by-reference
 *           $options argument of fw_collect_options(); PHP emitted an "Only
 *           variables should be passed by reference" notice that printed into the
 *           AJAX response and corrupted the JSON, so the client treated a
 *           successful reset as an error. Now a plain variable is passed, and the
 *           container lookup also guards on the ['option']['options'] key.
 *
 * 2.7.140 - Reset Tab Options: the confirm dialog showed the tab name wrapped in
 *           &quot; instead of real quotes. The reset_tab_warning string was piped
 *           through esc_js(), which HTML-encodes " — wrong here because the text
 *           goes straight into a JS confirm(), not into markup. Now the warning
 *           (and the full-reset fallback) are emitted with wp_json_encode() as
 *           proper JS string literals, so the quotes render correctly.
 *
 * 2.7.139 - Theme Settings: split the dangerous "Reset Options" button. The
 *           header/footer button next to Save Changes now reads "Reset Tab
 *           Options" and only resets the innermost open tab to its defaults,
 *           leaving every other tab untouched — so a mis-click no longer wipes
 *           the whole configuration. A new POST flag (_fw_reset_tab_options)
 *           plus a hidden _fw_reset_tab_id field carry the active tab's
 *           container id; FW_Settings_Form::_form_save() locates that container
 *           with fw_collect_options(info_wrapper) and swaps only its option keys
 *           for the values from fw_get_options_values_from_input($sub, array()),
 *           merging over the saved blob. The change is scoped to side-tabs forms
 *           (the full-reset button still renders for tab-less forms), and the
 *           full "reset everything" action is relinquished to the theme, which
 *           now exposes it under Miscellaneous → Reset Settings (it reuses the
 *           untouched _fw_reset_options path + its existing confirm dialog).
 *
 * 2.7.138 - Icon Box: the "Icon Badge" control (Layout tab) is now a visual
 *           image-picker instead of a text dropdown — each of the seven choices
 *           shows an SVG thumbnail of the badge shape (None / Solid + Outline ×
 *           Square/Rounded/Circle) with a hover tooltip carrying the old text
 *           label. New SVGs under icon-box/static/img/badge/. The picker stores
 *           the same plain slug as the old select, so saved boxes keep their
 *           badge and the front-end view/CSS are unchanged. See shortcodes
 *           1.4.53.
 *
 * 2.7.137 - Every shortcode's Styling tab is now organized into option groups:
 *           a "Colors" group (text/background/per-element color presets + the
 *           Font Size preset) and a "Spacings" group (the Margin & Padding
 *           field; for Section, the padding + column-gap controls). Shortcodes
 *           with extra appearance fields that are neither colour nor spacing —
 *           button (style/outline/size/block/state), image-content
 *           (fit/radius/shadow), text-expander (toggle icon / initially open) —
 *           keep those in their own group alongside the Colors group. Groups
 *           are visual-only (`'type' => 'group'`); option keys stay flat in
 *           storage, so saved content and every view.php render unchanged.
 *           See shortcodes 1.4.52.
 *
 * 2.7.136 - Icon Box: renamed the "Icon Fill" feature to "Icon Badge"
 *           throughout — "fill" was misleading for the outline (ring) variants,
 *           which have no fill. Full rename: option keys (icon_fill →
 *           icon_badge, icon_fill_color → icon_badge_color), Layout/Styling tab
 *           labels + descriptions, emitted CSS modifier classes
 *           (icon-box__icon--has-fill → --has-badge, icon-box__icon--fill-* →
 *           --badge-*) and the matching selectors in the icon-box stylesheet,
 *           plus the internal view.php variable names. The seven choice values
 *           (solid-square … outline-circle) and the SVG `fill` presentation
 *           attributes in the custom-icon wp_kses allow-list were intentionally
 *           left alone. view.php carries a clearly-marked TEMP MIGRATION
 *           fallback that reads the legacy icon_fill / icon_fill_color values
 *           so icon boxes saved before the rename keep their badge shape +
 *           colour until re-saved; it is safe to delete later. See
 *           shortcodes 1.4.51.
 *
 * 2.7.135 - Icon Box shortcode gains Icon / Title / Content alignment controls
 *           (Layout tab). Each emits a Bootstrap text utility class
 *           (`text-start` / `text-center` / `text-end`); "Default" emits no
 *           class so existing boxes are untouched. Icon alignment only applies
 *           to the block layouts (Icon above title, Icon between title and
 *           content) — the inline / side layouts position the icon via flexbox.
 *           The top-title and between-title-content CSS dropped their
 *           `align-items: center` so full-width children honour the per-element
 *           text-* classes (default still centered via inherited text-align);
 *           the icon is wrapped in a full-width `.icon-box__icon-align` block
 *           (top-title) / aligned on `.icon-box__divider` (between layout) so
 *           the inline-flex icon responds to text-align. Scoped text-* fallback
 *           rules added to the icon-box stylesheet since the frontend grid CSS
 *           doesn't ship Bootstrap text utilities. See shortcodes 1.4.50.
 *
 * 2.7.134 - Fix (regression): columns could be dragged but not dropped in the
 *           page builder — not between columns, not into another section. The
 *           drag hierarchy guard in builder.js (added v2.7.81, generalized in
 *           v2.7.82) enforced `column → row`, but rows do NOT exist in the
 *           editor tree — the items-corrector synthesizes `[row]` wrappers only
 *           at shortcode-generation time, so in the editor a column's parent is
 *           the SECTION. The guard's `targetParentType === 'row'` check was
 *           therefore always false for columns, blocking every column drop.
 *           Fix: scope the guard to `simple` items only (the only type that
 *           needed it — keeps tall text-blocks from shifting sections and makes
 *           loose root simples snap into a column). Columns / sections /
 *           section-like items now drag with the framework's original freedom;
 *           their invalid drops remain rejected at `receive` via
 *           allowIncomingType / allowDestinationType. See builder 1.2.24.
 *
 * 2.7.133 - Coordinated bump with shortcodes 1.4.49: removed the
 *           `sc_get_styling_fields()` aggregator and inlined every
 *           shortcode's Styling tab (flat field list, no group wrappers),
 *           plus dropped `[special-heading]`'s wrapper Text Color. Saved
 *           values unchanged. See the shortcodes manifest for the full
 *           rationale and the per-shortcode list.
 *
 * 2.7.132 - Coordinated bump with shortcodes 1.4.48: bug fix for
 *           `[icon-box]` Icon Fill Color (Styling tab) emitting an inline
 *           `style="background-color:#…"` for preset picks instead of the
 *           themeable `bg-{slug}` utility class. See shortcodes manifest
 *           for the full explanation.
 *
 * 2.7.131 - Coordinated bump with the shortcodes extension's rollout of the
 *           `predefined-colors-color-picker-compact` option type to every
 *           remaining shortcode (see shortcodes manifest 1.4.47). No
 *           framework-side code changes — all the helper plumbing
 *           (`sc_color_field_compact`, `sc_normalize_color_value`,
 *           `sc_extract_styling_atts`) was already in place from 2.7.129
 *           and 2.7.130. This entry exists so the plugin header version
 *           stays in sync with the framework manifest while the
 *           shortcodes extension absorbs the bulk migration.
 *
 * 2.7.130 - Two changes that unblock the next shortcode (icon-box) on the
 *           compact-picker rollout.
 *           (A) New `sc_extract_styling_atts()` helper in
 *           `shortcode-styling-helper.php` — sibling of
 *           `sc_extract_styling_classes()` that returns BOTH a classes
 *           array AND an inline-styles array. Inner-element views
 *           (title, content, icon) need this so a custom-hex pick from
 *           the new compact picker actually paints the inner element,
 *           instead of being silently dropped the way the legacy
 *           extractor does. Kind ('text' | 'bg') for the style emission
 *           is inferred from the att key (any key containing `bg` gets
 *           `background: …`, everything else gets `color: …`). The
 *           legacy `sc_extract_styling_classes()` is unchanged so
 *           shortcodes that haven't migrated continue to work without
 *           any view edits.
 *           (B) `spacing` option type gains a deeper bottom padding
 *           (8px → 32px) so the trailing `desc` row no longer sits
 *           uncomfortably close to — or partially hidden behind — the
 *           page-builder shortcode modal's sticky Save/Reset footer.
 *           Symptom was the spacing field's "All Sides applies to every
 *           side at once…" description text getting clipped at the
 *           bottom of the modal when Spacing was the last field in the
 *           tab. Pure CSS bump in
 *           `framework/includes/option-types/spacing/static/css/styles.css`;
 *           no PHP changes, no behaviour change anywhere else.
 *
 * 2.7.129 - Begin rolling the shortcode Styling tab over to the new
 *           `predefined-colors-color-picker-compact` option type for
 *           Text Color / Background Color, in place of the legacy plain
 *           `<select>` produced by `sc_color_field()`. Three pieces land
 *           together so the rollout can proceed shortcode-by-shortcode:
 *           (1) New `sc_color_field_compact()` helper in
 *           `shortcode-styling-helper.php` — drop-in replacement for
 *           `sc_color_field()` with the same `kind` ('text'|'bg')
 *           semantics and the same `text-{slug}` / `bg-{slug}` saved
 *           class names. Choices are built from
 *           `unysonplus_color_preset_slug_map()` so the dropdown matches
 *           the live palette. (2) New `sc_normalize_color_value()`
 *           helper that resolves either the legacy plain-string saved
 *           value OR the new `{predefined, custom}` array shape into a
 *           class + style pair. `sc_apply_styling_classes()` (the
 *           `sc_build_wrapper_attr` filter callback) now funnels
 *           text_color / bg_color through this normaliser — preset picks
 *           still emit `class="text-red"` exactly as before; custom-color
 *           picks emit inline `style="color: …"` / `style="background: …"`
 *           appended to whatever the wrapper already carries.
 *           `sc_extract_styling_classes()` gets the same defensive
 *           treatment so view-side extractors don't WSOD when handed an
 *           array. (3) New `compact_colors` boolean on
 *           `sc_get_styling_fields()` — passing `true` swaps both
 *           Text/Background color fields to the compact picker for the
 *           shortcode opting in; default stays `false` so unmigrated
 *           shortcodes keep the legacy <select>. The compact option
 *           type itself gained a legacy-string back-compat shim in
 *           `_render()` and `_get_value_from_input()`: a saved value of
 *           `'text-red'` (string) from before the migration loads
 *           cleanly as `{predefined: 'text-red', custom: ''}` and
 *           normalises to the new array shape on next save. First
 *           consumer of the new flag: `[text-block]` shortcode.
 *
 * 2.7.128 - Renamed `framework/extensions/shortcodes/includes/shortccode-helpers.php`
 *           (double `c` — long-standing typo) to the correctly-spelled
 *           `shortcode-helpers.php`. The file defines `sc_get_option()`
 *           (and a deprecated `c_get_option()`) and is loaded by the
 *           extensions component's `include_extension_directory_all_locations()`
 *           — a `glob( '/includes/*.php' )` over the extension's
 *           directory at activation time. The glob doesn't care what
 *           individual files are named, so no `require_once` / autoload /
 *           manifest entry references the typo'd filename — verified with
 *           two repo-wide greps before renaming. Function definitions
 *           inside the file are byte-identical, so every caller of
 *           `sc_get_option()` (notably `shortcode-option-helpers.php` →
 *           `sc_option_color_palette()`, `sc_option_button_colors()`,
 *           etc.) keeps working unchanged. Pure cosmetic fix to remove
 *           a stumbling block for new contributors browsing the
 *           includes/ directory.
 *
 * 2.7.127 - Simplified the `predefined-colors-color-picker-compact` option
 *           type shipped in 2.7.126. The `display` attribute is gone —
 *           the two modes it gated (`'blocks'` swatch+neutral-label vs
 *           `'text'` colored-label-no-swatch) carried no actual
 *           information advantage over each other, and forced callers
 *           to make a per-context decision. Now there's ONE unified
 *           appearance: every option row shows both the colored swatch
 *           AND the preset's name rendered in that color. Callers that
 *           still pass `display => 'blocks'` / `'text'` have the key
 *           silently ignored — no fatal, no warning. Saved values are
 *           untouched.
 *           One UX hazard the unified design surfaces: near-white
 *           presets (White, Light Gray) become invisible when their
 *           label color matches the panel's white background. Fixed by
 *           detecting light colors at render via a new private
 *           `_color_is_light()` helper on the class (WCAG luminance,
 *           threshold 0.95 — matches the existing `sc_color_is_light()`
 *           convention from 2.7.70). Light options gain a
 *           `pccpc__option--light` class and a `data-light="1"` attr;
 *           the JS mirrors the class onto `.pccpc__trigger` when such a
 *           preset is picked or restored from saved state. CSS paints a
 *           subtle `#dbdbdb` rounded chip behind the label for the light
 *           class so the text stays legible. Cleanup: the
 *           `[data-display="text"]` selectors and the
 *           `data-display` wrapper attribute have been removed from
 *           styles.css and the class respectively. Existing wide
 *           `predefined-colors-color-picker` is untouched.
 *
 * 2.7.126 - New option type `predefined-colors-color-picker-compact` — a
 *           compact dropdown sibling of the existing wide
 *           `predefined-colors-color-picker`. Same composite intent
 *           (presets + a custom color picker, mutually-exclusive halves)
 *           but the preset half collapses to a single trigger button that
 *           opens an overlay panel of options on click, so a Styling tab
 *           with 10+ colour rows stays scannable. Two preview modes via
 *           a `display` option attribute: `'blocks'` (default — colored
 *           squares, for background contexts) or `'text'` (label rendered
 *           in the preset's color, for text-color contexts). Choices
 *           shape is richer than the wide hybrid because the new type
 *           needs three pieces per entry — the SAVED CLASS NAME, the
 *           HUMAN LABEL, and the PREVIEW COLOR — so callers pass
 *           `'class-name' => array( 'label' => '…', 'color' => '#hex' )`.
 *           Saved value is identical to the wide hybrid
 *           (`{ predefined, custom }`); the `predefined` half holds a CSS
 *           class name (e.g. `'text-red'`, `'bg-light-blue'`) that the
 *           consuming view emits VERBATIM as `class="…"`, and the
 *           `custom` half holds a hex/rgba the view emits as inline
 *           `style="color: …"` / `style="background: …"`. This output
 *           contract removes the slug-to-class mapping step that
 *           shortcode views otherwise need. Mutual exclusion mirrors the
 *           wide hybrid: picking a preset clears the custom picker;
 *           touching the custom picker clears the preset. Wired into
 *           the framework via `autoload.php` + `includes/hooks.php`
 *           alongside the existing hybrid. Additive — the wide hybrid
 *           and every existing Styling-tab `<select>` are untouched;
 *           migrating shortcodes to use the new type is a separate task.
 *
 * 2.7.125 - `[icon-box]` shortcode gains an Icon Fill option (Layout tab)
 *           and a paired Icon Fill Color picker (Styling tab). Seven
 *           variants — None plus Solid / Outline × Square / Rounded / Circle
 *           — cover the standard "icon on a coloured chip" look that
 *           features grids and tile sections need. The view resolves the
 *           picked colour preset slug to a hex via
 *           `unysonplus_color_preset_slug_map()` and emits it as an inline
 *           `background-color` (solid variants) or `border-color` + `color`
 *           (outline variants) on the `.icon-box__icon` span. The outline
 *           variant also sets `color` so a font icon or SVG with
 *           `stroke="currentColor"` reads in the same tone as the ring,
 *           rather than the default green that the icon-box's static.css
 *           sets when no Icon Color is picked. Shape geometry is shipped in
 *           the icon-box's own static.css under new `.icon-box__icon--has-
 *           fill` and `.icon-box__icon--fill-{variant}` modifier classes,
 *           so the layout works without any theme-side CSS. The Icon Fill
 *           Color picker uses the existing `sc_color_field( kind=bg )` so
 *           its dropdown options render with a coloured swatch background
 *           in the admin — matching the existing colour-picker pattern of
 *           Title / Content / Icon colours.
 *
 * 2.7.124 - Coupled the page-builder's Bootstrap-3-era "auto-split columns
 *           into separate rows" behaviour to the same setting that already
 *           controls the legacy stylesheet (Page Builder Settings →
 *           "Bootstrap 3 Legacy Mode"). Previously: dropping 8 quarter-width
 *           columns (`1/4` / `col-sm-3`) into a Section produced TWO `.fw-row`
 *           wrappers of 4 columns each — the right pattern for Bootstrap 3's
 *           floated grid, but wrong for Bootstrap 5's flex grid where
 *           `flex-wrap` handles the visual wrapping inside ONE `.fw-row` and
 *           `--bs-gutter-y` only spaces wrapped sub-rows of the same row.
 *           That mismatch is why the new Theme Settings → Default Gap Y (and
 *           per-section gap-y) wasn't taking effect on layouts with more than
 *           one row's worth of columns. The fix is a single short-circuit
 *           added to `Page_Builder_Items_Corrector_Row_Container::column_fits()`
 *           that reads `fw_get_db_ext_settings_option('page-builder',
 *           'load_bootstrap_3_legacy_css', false)` — when unchecked (the
 *           default), the fraction-split logic is skipped and all columns
 *           stay in one row. The existing static developer-only config flag
 *           `disable_columns_auto_wrap` still short-circuits first (kept for
 *           back-compat with any theme that already sets it). Going-forward
 *           only: existing posts whose page-builder data is already
 *           multi-row are not auto-migrated; the corrector only fires on
 *           save, so already-saved structures stay until the user re-edits
 *           that section.
 *
 * 2.7.123 - Follow-up to 2.7.122: the preset-CSS enqueue priority bump (1 → 20)
 *           wasn't late enough. Unyson's shortcodes extension hard-codes its
 *           own per-shortcode static enqueue at `wp_enqueue_scripts` priority
 *           30 (`framework/extensions/shortcodes/class-fw-extension-shortcodes.php`
 *           lines 58-69) so shortcode hooks can `wp_add_inline_style()`
 *           against theme handles — meaning every `fw-shortcode-{tag}-css`
 *           link was still printing AFTER `unysonplus-presets-css`. Real
 *           order observed: parent-style → plugin-extension CSS → presets →
 *           shortcode CSS. That left shortcode styles winning the cascade,
 *           so a saved preset (Default Gap, Section Variant, etc.) couldn't
 *           reliably override a shortcode's own stylesheet. Bumped to
 *           priority 35 so presets land AFTER the priority-30 shortcode
 *           enqueues but still leave a comfortable window (priority 40+)
 *           for child themes to override presets if they want. Inline
 *           fallback stays at `wp_head` priority 99 — already late enough.
 *
 * 2.7.122 - Plugin is now self-contained on Bootstrap 5 — no longer depends
 *           on the active theme shipping it. Three coordinated changes plus
 *           a couple of opportunistic cleanups:
 *           (1) Bundled `bootstrap.min.css` (5.3.3) at
 *           `framework/static/css/bootstrap.min.css` and added a new
 *           `framework/includes/bootstrap.php` that enqueues it at
 *           `wp_enqueue_scripts` priority 5. The enqueue is skipped when
 *           any other code has already registered or enqueued the
 *           `bootstrap` handle (third-party themes with their own
 *           Bootstrap build keep working), and also skipped when the user
 *           has unchecked the new "Bootstrap 5 Stylesheet" checkbox in
 *           Page Builder Settings (power users running Tailwind / custom
 *           CSS can opt out). Default state of the checkbox is on, so
 *           fresh installs and never-saved sites get Bootstrap
 *           automatically. (2) `unysonplus_enqueue_preset_css` priority
 *           bumped from 1 → 20 and the inline fallback from `wp_head`
 *           priority 2 → 99, so the generated `presets-{hash}.css` loads
 *           AFTER plugin shortcodes and the theme — Theme Settings →
 *           Default Gap (and every other preset override) now wins the
 *           cascade naturally on source order instead of having to rely on
 *           `:root` specificity boosts and `!important` declarations.
 *           (3) Stripped the `:root` prefix from every spacing utility
 *           rule (`.m-N` / `.p-N` / etc.) and from the gap site-default +
 *           per-section modifier rules. Spacing utilities keep
 *           `!important` (user-saved custom slugs need to beat any theme
 *           rule); gap rules drop it everywhere except per-row utilities
 *           (`.g-N` / `.gx-N` / `.gy-N`), which keep `!important` so they
 *           can override per-section gap modifiers that have higher
 *           specificity. Preset-CSS hash schema bumped 3 → 4. Pair with
 *           builder 1.2.23 (the `.fw-row` namespace alignment from
 *           `--fw-gutter-x/y` to `--bs-gutter-x/y`) and page-builder
 *           1.6.7 (settings-options.php gained the opt-out checkbox).
 *
 * 2.7.121 - Site-wide Default Gap rule (`:root .row, :root .fw-row`) now
 *           emits with `!important` on the `--bs-gutter-x` / `--bs-gutter-y`
 *           assignments. Specificity alone (0,2,0 vs Bootstrap's 0,1,0)
 *           should have been enough to override Bootstrap's stock 1.5rem
 *           gutter on real-world sites, but in practice we can't guarantee
 *           Bootstrap's `.row` rule isn't being re-emitted somewhere down
 *           the cascade — theme overrides, asset-optimizer concatenations,
 *           and page-builder admin previews can all reshuffle source order
 *           or even republish Bootstrap's rules with extra specificity
 *           boosts. Adding `!important` makes the override decisive
 *           regardless of load order. The per-row utility classes
 *           (`:root .g-N` / `.gx-N` / `.gy-N`) already used `!important`;
 *           per-section modifiers (`:root .section--gap-N .row`, etc.) don't
 *           need it because they win on specificity (0,3,0) against the
 *           site-default rule. Preset CSS hash schema bumped to 3 so the
 *           cached `presets-{hash}.css` file regenerates on every site.
 *
 * 2.7.120 - Added gap-scale preset machinery — a parallel system to
 *           `spacing_scale` but scoped to column-gap values (Bootstrap row
 *           gutters + CSS grid `gap`). Four new getters in
 *           `framework/includes/presets.php`:
 *           `unysonplus_default_gap_scale()` (Bootstrap 5's `$spacers`
 *           verbatim — 0, 0.25, 0.5, 1, 1.5, 3rem), `unysonplus_get_gap_scale()`
 *           (Theme Settings override → defaults, filterable via
 *           `unysonplus_gap_scale`), plus `unysonplus_get_default_gap()`,
 *           `unysonplus_get_default_gap_x()`, `unysonplus_get_default_gap_y()`
 *           for the site-wide Default Gap slug + axis overrides. The X/Y
 *           getters fall through to Default Gap when blank, so a user can
 *           set "horizontal-only" or "vertical-only" overrides without
 *           touching the other axis. `framework/includes/css-tokens.php`
 *           gained a new emit block that produces three layers of rules:
 *           (1) site-wide `:root .row, :root .fw-row` resolved to a single
 *           `{X, Y}` rule via effective-value resolution (no source-order
 *           collisions); (2) per-section modifiers
 *           `:root .section--gap-{slug} .row` (and -x-/-y- variants) used
 *           by the `[section]` shortcode's new Gap fields; (3) per-row
 *           utility classes `:root .g-{slug}` / `.gx-{slug}` / `.gy-{slug}`
 *           that override Bootstrap's stock `.g-N` utilities with our
 *           scale (same `:root` specificity-boost trick the spacing
 *           utilities use). All routed through `--bs-gutter-x` /
 *           `--bs-gutter-y` so Bootstrap's existing `.row > *` column
 *           padding rules pick them up automatically — we never touch
 *           column padding directly. The preset-CSS hash schema is
 *           bumped to 2 and now includes the gap scale + the three
 *           default-gap slugs so any change busts the file cache.
 *
 * 2.7.119 - Fix `spacing` option type silently resetting to default on every
 *           post Update. The page-builder's shortcode re-save path
 *           (`class-page-builder-simple-item.php::get_atts_after_create`)
 *           calls `fw_get_options_values_from_input( $options, array() )` —
 *           an empty input array — to re-run each option's
 *           `_get_value_from_input` on its already-saved value (so any
 *           auto-generated fields can refresh themselves, e.g. `unique`).
 *           FW relays that as `$input_value = null` for every option, with
 *           the previously-saved value pre-merged into `$option['value']`;
 *           composite option types are expected to return `$option['value']`
 *           verbatim when the input isn't an array. The spacing type was
 *           instead returning `$defaults['value']` (every slot empty), so
 *           every time the user hit Update WordPress would zero out the
 *           margin/padding picks on every shortcode in the post. Now
 *           matches the background-pro pattern: when `$input_value` isn't
 *           an array, return `$option['value']`. Picks now survive Update.
 *
 * 2.7.118 - Decouple the `spacing` option type from the shortcodes extension
 *           and the framework's preset getter so it's fully self-contained
 *           within `framework/includes/option-types/spacing/`. Previously
 *           the class called `sc_get_spacing_select_choices()` (lives in
 *           `framework/extensions/shortcodes/includes/shortcode-styling-helper.php`)
 *           and the README pointed users at `sc_styling_help_text()` —
 *           both cross-module reaches. The class now ships with its own
 *           built-in scale (Bootstrap 5's `$spacers` verbatim) and its own
 *           class-name sanitizer, generates choices internally, and exposes
 *           a single escape hatch: the `fw_option_type_spacing_scale`
 *           filter. The shortcodes extension hooks that filter to plug in
 *           `unysonplus_get_spacing_scale()` (Theme Settings → Spacing on
 *           unysonplus-theme, or plugin defaults from
 *           `framework/includes/presets.php` on any other theme) — so the
 *           previous "edit the scale in one place, see it everywhere"
 *           behaviour is preserved, but now via an explicit integration
 *           point instead of a hard-coded call out of the option-types
 *           folder. The README was rewritten to drop the
 *           `sc_styling_help_text` reference, document the default scale,
 *           and show how to override it via the filter.
 *
 * 2.7.117 - Follow-up to 2.7.116: the new composite `spacing` option type
 *           saves its value as one nested `$atts['spacing']` array, but the
 *           `[column]` shortcode was still extracting the legacy flat keys
 *           (`margin`, `margin_top`, `padding_bottom`, …) to push spacing
 *           classes onto its INNER div instead of the outer column wrapper.
 *           Those flat keys no longer exist, so column's inner div was
 *           silently losing its spacing. Added a mirror helper
 *           `sc_extract_spacing_classes( &$atts )` to shortcode-styling-helper.php
 *           that pulls the nested `spacing` att out, flattens it into a flat
 *           class list, and unsets `$atts['spacing']` so the
 *           `sc_apply_styling_classes` filter doesn't re-apply the same
 *           classes to the outer column. `[column]/views/view.php` updated
 *           to use the new helper alongside its existing
 *           `sc_extract_styling_classes()` call for `bg_color`. Other
 *           shortcodes that use `sc_get_styling_fields()` (text-block,
 *           media-image, icon-box, accordion, etc.) were already correct —
 *           they put styling-tab picks on the outer wrapper via the existing
 *           filter, which already knows about the nested shape.
 *
 * 2.7.116 - Added `spacing` composite option type at
 *           `includes/option-types/spacing/`. Replaces the legacy "10 separate
 *           short-select dropdowns inside two nested groups" UI with a single
 *           plus-cross widget: each section (Margin, Padding) has an All Sides
 *           select on top, then Top / Right / Bottom / Left arranged in a 3×3
 *           CSS grid so the position of each input matches the CSS axis it
 *           controls. Two columns side-by-side by default; stacks vertically
 *           below ~600px. A `mode` attribute (`'both'` | `'margin'` | `'padding'`)
 *           scopes the widget — picking a single mode hides the other column
 *           and force-resets its subtree to defaults on save, so a tampered
 *           POST can't smuggle values in via the hidden side. Saved value is
 *           a nested array of Bootstrap utility class names (`m-3`, `pt-2`,
 *           …); per-slot dropdown choices reuse the existing
 *           `sc_get_spacing_select_choices()` so the new widget and the legacy
 *           single-slot `sc_spacing_field()` helper share one source of truth
 *           for the live spacing scale. An empty `advanced` slot is reserved
 *           in the value tree for v2 (e.g. per-breakpoint values) — same
 *           pattern as background-pro. Ships with a per-folder `README.md`
 *           documenting the option-array spec and a copy-pasteable view-side
 *           flatten example, so third-party shortcode authors can pick the
 *           type up without reading the source. Loader entry added to
 *           `framework/bootstrap.php` right after the `background-pro` require
 *           — same eager-load pattern, since plugin-only composite types need
 *           their `FW_Option_Type::register()` call to fire before any
 *           options.php is processed and the case-sensitive autoload switch
 *           in `framework/autoload.php` wouldn't match anyway. The class is
 *           wrapped in a `class_exists` guard for safe theme overrides.
 *
 * 2.7.115 - Moved `predefined-colors` and `predefined-colors-color-picker`
 *           option types from unysonplus-theme into the plugin so any theme
 *           bundled with Unyson+ can use them, and so the `background-pro`
 *           Color tab works under generic themes without a registration gate.
 *           Both classes now live at `includes/option-types/<type>/`, are
 *           wired into the lazy class autoloader in `framework/autoload.php`,
 *           and are registered alongside the other option types in
 *           `framework/includes/hooks.php`. Each class is wrapped in a
 *           `class_exists` guard so a stale theme-side copy on a partially-
 *           upgraded deploy won't trigger a redeclare fatal. All sub-control
 *           CSS/JS enqueues inside the moved classes were rewritten from
 *           `get_template_directory_uri()` to `fw_get_framework_directory_uri()`
 *           — including the cross-dependency from
 *           predefined-colors-color-picker to predefined-colors. Also fixed
 *           a pre-existing `wp_enqueue_style` bug in the originals: the third
 *           argument was being passed the version string instead of the
 *           `$deps` array, so version cache-busting was silently disabled.
 *           Now passes `array()` as deps and the version string as the fourth
 *           argument. `background-pro` was simplified at the same time: the
 *           `file_exists()` enqueue guards and the `predefined-colors-color-
 *           picker` registration gate (with its rgba-color-picker fallback)
 *           were removed, since the dependency types are now always
 *           available. The theme-side directories and the theme's
 *           `inc/hooks.php` `require_once` lines for both types have been
 *           removed.
 *
 * 2.7.114 - `background-pro` polish pass after first real-world use. Four
 *           issues fixed: (1) the Video tab's MP4 / WebM uploaders rendered
 *           as image pickers because `upload` defaults to `images_only=true`
 *           — they now declare `images_only=false` with `files_ext` and
 *           `extra_mime_types` scoped to mp4/webm so the media modal filters
 *           to video files. (2) Added an `oembed` field "External Video URL"
 *           at the top of the Video tab. When set, sections should embed the
 *           remote video (YouTube/Vimeo/Dailymotion via WP's oEmbed) and
 *           ignore the self-hosted sources — saving server bandwidth. The
 *           value is stored under `value.video.external_url` and is sanitized
 *           with `esc_url_raw()` on save. (3) Sub-control descriptions were
 *           rendered with `wp_kses_post()`; on modern WordPress that allows
 *           `<video>` and similar media tags, which meant the Enable Video
 *           desc ("Renders a muted, looping HTML5 <video> element …")
 *           injected a real empty `<video>` element into the panel and ate
 *           vertical space. Descriptions now go through `esc_html()` so HTML
 *           tag names display literally. (4) Color tab now gates the
 *           `predefined-colors-color-picker` sub-control behind a registration
 *           check — that type ships only with unysonplus-theme, so on any
 *           other theme it would have fatalled. The fallback path renders a
 *           plain `rgba-color-picker` that writes to the same nested
 *           `color/value/custom` key, so saved values survive theme switches.
 *           Also added a sensible built-in default palette plus a new filter
 *           `fw_option_type_background_pro_color_palette` so any theme can
 *           supply its own preset colors without depending on the theme-
 *           specific `unysonplus_option_color_palette()` helper.
 *
 * 2.7.113 - Moved the `background-pro` option type from unysonplus-theme into
 *           the plugin. It originated in the theme (alongside fw-multi-inline,
 *           predefined-colors, and predefined-colors-color-picker) but is a
 *           generic, theme-agnostic control — splitting Color / Gradient /
 *           Image / Video into Avada-style sub-tabs that stack as CSS layers —
 *           so any theme bundled with Unyson+ should have access to it.
 *           The class lives at `includes/option-types/background-pro/` and is
 *           required unconditionally from bootstrap.php right after the
 *           fw-multi-inline conditional; the file is wrapped in a
 *           class_exists guard so a stale theme-side copy on a partially-
 *           upgraded deploy won't trigger a redeclare fatal. Two of the
 *           sub-controls (predefined-colors and predefined-colors-color-picker)
 *           still ship only in unysonplus-theme, so their CSS/JS enqueues
 *           remain theme-relative — but each one is now gated by file_exists()
 *           so generic themes silently skip them instead of 404ing on the
 *           asset URL. fw-multi-inline's stylesheet is now loaded from the
 *           plugin's own copy (canonical source) instead of the theme. The
 *           theme's `inc/hooks.php` loader entry for background-pro and the
 *           theme-side directory itself have been removed.
 *
 * 2.7.112 - Added `gradient-v2` option type. The legacy `gradient` only
 *           supported a two-stop HEX-only gradient with no mode, angle, or
 *           alpha control — fine for the simplest "fade A to B" use cases
 *           but inadequate for the rich gradients modern designers expect
 *           (think cssgradient.io). The v2 variant adds unlimited color
 *           stops with per-stop position, linear vs. radial mode, a 0-360°
 *           angle control with draggable knob, RGBA alpha via the existing
 *           wp-color-picker stack (reused from `rgba-color-picker` to avoid
 *           bundling a second picker library), a live preview bar with
 *           draggable stop markers, and click-to-add stop on the preview.
 *           State serializes as a single JSON hidden field
 *           (`{type, angle, stops:[{color, position}, ...]}`) for clean
 *           round-tripping. The legacy `gradient` type and its saved
 *           `{primary, secondary}` values are untouched — `gradient-v2` is
 *           opt-in via `'type' => 'gradient-v2'`. Sample entries added to
 *           the theme's `demo.php` (linear) and `demo-2.php` (radial) for
 *           visual smoke-testing.
 *
 * 2.7.111 - `[section]` shortcode Styling tab trimmed from 13 fields to 3.
 *           The generic `sc_get_styling_fields()` call (text color, bg color
 *           preset, font-size preset, all-sides margin + 4 per-side, all-sides
 *           padding + 4 per-side) was overkill for a page-level layout block —
 *           sections rarely need per-side margins, section-wide font scaling,
 *           or a section-wide text color (the Section Variant on the Layout
 *           tab already handles light/dark text). Replaced with exactly three
 *           fields under a renamed "Spacing & Style" tab: preset Background
 *           Color (`bg-{slug}` utility), Top Spacing (`pt-{n}`), Bottom Spacing
 *           (`pb-{n}`). Field keys stay as the standard `bg_color`,
 *           `padding_top`, `padding_bottom` so saved values on existing
 *           sections survive — only the removed fields' atts (text_color,
 *           font_size_preset, margin*, padding, padding_start, padding_end)
 *           become no-ops on the next save. The legacy Unyson `background_color`
 *           color-picker on the Layout tab is untouched (existing sites
 *           upgrading from stock Unyson keep their saved values) and gets a
 *           new `help` attribute clarifying its legacy role and pointing
 *           editors at the preset alternative.
 *
 * 2.7.110 - Default spacing scale extended from 6 stops (0-5) to 10 stops
 *           (0-9), adding 6: 3.5rem, 7: 4rem, 8: 4.5rem, 9: 5rem in half-rem
 *           steps for finer hero-section padding control. Every shortcode that
 *           uses `sc_spacing_field()` automatically picks up the new stops in
 *           its Margin/Padding dropdowns via `sc_get_spacing_select_choices()`
 *           reading the live scale. Theme Settings → General → Spacing also
 *           grows to 10 rows so users can override the new defaults. Sites
 *           with a saved custom scale are unaffected — their explicit override
 *           wins and the new defaults are only seen on first save / reset.
 *
 * 2.7.109 - `[section]` shortcode gains a Section Variant select at the top
 *           of the Layout tab (Default / Alt / Light / Dark). Picking a
 *           variant adds a `section--{slug}` class to the wrapper, with CSS
 *           defaults of `#f7f7f7` (alt off-white), `#ffffff` + dark text
 *           (light), and `#1a1a1a` + light text + readable blue links
 *           (dark). Each default is wrapped in a `var(--color-section-*, …)`
 *           lookup so theme authors can override the palette site-wide
 *           without touching shortcode CSS — set `--color-section-dark`,
 *           `--color-section-light-text`, etc. on `:root` in your theme.
 *           The Background Color picker still wins on top of any variant
 *           (later in the cascade, inline style attribute) so editors can
 *           pick "Dark" for the structural theme and override the bg for
 *           a one-off colour. Existing sections without a variant render
 *           exactly as before — back-compat is the `''` default value.
 *
 *           Also restructured `section/options.php` to wrap the Layout
 *           and Bleed Layout tabs in `group_*` containers (matching the
 *           Advanced tab's pattern) and switched to modern `[]` array
 *           syntax throughout. Purely cosmetic — saved instances unaffected.
 *
 * 2.7.108 - Default Color Presets gain four semantic entries at the TOP
 *           of the palette: Primary (#0d6efd), Secondary (#6c757d),
 *           Accent (#fd7e14), Muted (#adb5bd). Values are Bootstrap-derived
 *           so the names mean something familiar. Prepended, not replacing
 *           — the existing Black → Blue Gray palette continues unchanged
 *           below them. The four new slugs (`primary`, `secondary`,
 *           `accent`, `muted`) become available everywhere a Color Preset
 *           selector is used, surfaced as `text-primary` / `bg-primary` /
 *           etc. utility classes via the existing unysonplus-presets
 *           pipeline. Site owners customise these via Theme Settings →
 *           Color Presets — the framework's :root + !important rules
 *           override Bootstrap's defaults so a single change updates
 *           every shortcode using those classes.
 *
 * 2.7.107 - `[accordion]` cascade interval tightened from 500 ms to 200 ms
 *           per item. The original 0.5s offset felt sluggish for short
 *           item counts; 0.2s reads as a tight sequential reveal while
 *           still being distinctly perceptible as a cascade. Math now
 *           `base_delay + 0.2s × index` per item.
 *
 * 2.7.106 - `[accordion]` cascade fix: items were all animating at the
 *           same time instead of in sequence. Root cause was the delay
 *           mechanism — view.php was setting the `--animate-delay` CSS
 *           custom property inline on each item, but Animate.css v4
 *           only reads `var(--animate-delay)` from inside its
 *           `.animate__delay-Ns` utility classes (look for
 *           `.animate__animated.animate__delay-1s` in
 *           `framework/extensions/shortcodes/static/css/animate.min.css`).
 *           Plain `.animate__animated` does NOT pick up the variable,
 *           so the per-item delay was a no-op and every item started
 *           animating at intersection-time. Fixed by setting
 *           `animation-delay` (and `-webkit-animation-delay` for
 *           Safari) DIRECTLY in each item's inline style instead of
 *           relying on the CSS variable. Same cascade math (base +
 *           0.5s × index), now actually applied.
 *
 *           Also: removed the "Stagger Item Reveal" switch and its
 *           pure-CSS keyframe fallback (added in 2.7.99 / S). The
 *           cascade behaviour the editor wants happens automatically
 *           whenever the Animations tab is enabled; the standalone
 *           switch was redundant and added a confusing second axis.
 *           Saved instances that had `stagger_reveal=yes` simply have
 *           the att ignored — no fatal, no migration.
 *
 * 2.7.105 - `[accordion]` cascades the Animations-tab effect across items
 *           automatically. Before: picking an entry animation in the
 *           Animations tab animated the WHOLE wrapper as one block.
 *           Now: when the wrapper would have received `sc-anim-pending` +
 *           `data-sc-anim` (i.e., the Animations tab is enabled), the
 *           view captures those hooks off the wrapper and re-attaches
 *           them to each `.accordion-item` with a per-item
 *           `--animate-delay` of `(user-picked delay) + 0.5s × index`.
 *           Each item then triggers its own intersection-driven reveal
 *           via the existing `sc-animations.js`, producing a 500 ms
 *           cascade across the items. No JS changes required — the
 *           shared animations runtime already supports per-element
 *           triggers. The standalone "Stagger Item Reveal" switch
 *           (shipped 2.7.99) keeps its pure-CSS reveal behaviour but
 *           no longer fires alongside the cascade — when an Animations
 *           tab effect is in play, that cascade owns the reveal and
 *           the switch becomes a no-op. Updated the switch's editor-
 *           facing description to spell this out.
 *
 * 2.7.104 - `[column]` adds a "Full Height" switch in a new Layout tab.
 *           When enabled, the inner wrapper receives Bootstrap's `h-100`
 *           class so the styled content area stretches to the full row
 *           height — the standard equal-height-cards pattern. The class
 *           lands on the INNER wrapper (consistent with 2.7.103's routing)
 *           because Bootstrap flex columns already stretch to row height
 *           by default; the inner needs `h-100` to fill the column.
 *           When Full Height is on but no other Styling pick or Inner
 *           Wrapper Class is set, the inner div is force-created with
 *           just `h-100` so the option has something to attach to.
 *
 * 2.7.103 - `[column]` Styling-tab and custom-CSS classes now land on
 *           the INNER wrapper instead of the outer Bootstrap grid div.
 *           The outer column now carries only its `fw-col-*` width
 *           class (plus animation classes, responsive-hide, custom_attrs,
 *           and css_id which are layout-slot concerns and stay on the
 *           outer). Background, all margin presets
 *           (m/mt/me/mb/ms) and padding presets (p/pt/pe/pb/ps), plus the
 *           Advanced-tab CSS Class are now extracted via
 *           `sc_extract_styling_classes` and the user's css_class field
 *           and applied to an inner `<div>` together with the existing
 *           Inner Wrapper Class option. The inner div renders only when
 *           it has at least one class to carry — pure-layout columns
 *           with nothing styled still emit just `<div class="fw-col-12">`
 *           as before. Also reorganised the Advanced tab so Inner
 *           Wrapper Class sits directly below CSS Class inside the
 *           `group_css` block, matching the editor's mental model.
 *           Migration note for hand-written CSS: any selectors that
 *           targeted background / padding / margin directly on
 *           `.fw-col-*` will need to walk one level deeper
 *           (`.fw-col-* > div`) — but custom theme CSS on a grid column
 *           is uncommon, so the blast radius should be small.
 *
 * 2.7.102 - `[icon-box]` Styling tab cleanup. Dropped the default
 *           wrapper-level "Text Color" — per-element Title Color,
 *           Content Color, and Icon Color cover every visible text
 *           element, so the wrapper Text Color just competed with
 *           the per-element picks. Refreshed the field descriptions
 *           to drop the now-stale "Overrides the general Text Color"
 *           phrasing. Same pattern as the recent divider /
 *           call-to-action / accordion / icon cleanups.
 *
 * 2.7.101 - `[divider]` Styling tab cleanup. Dropped the default wrapper-
 *           level "Text Color" — per-element Line Color, Icon Color, and
 *           Divider Text Color cover every visible element, so the
 *           wrapper Text Color just competed with the per-element picks.
 *           Same pattern as the recent call-to-action / accordion /
 *           icon-box / icon cleanups.
 *
 * 2.7.100 - `[call-to-action]` Styling tab cleanup. Dropped the default
 *           wrapper-level "Text Color" field — the per-element Title
 *           Color and Content Color picks already cover both text
 *           elements, so the wrapper Text Color just competed with
 *           them. Also relabelled "Message Color" → "Content Color"
 *           so the Styling tab vocabulary matches the Content tab
 *           (where the body field is "Content"). The stored option
 *           key (`message_color`) is unchanged so saved instances
 *           carry over without migration; only the editor-facing
 *           label moves. Same pattern as the recent `[accordion]` /
 *           `[icon-box]` / `[icon]` cleanups.
 *
 * 2.7.99 - `[accordion]` option-additions batch — five new editor knobs:
 *          (A) Per-item "Open by Default" switch on each addable item.
 *              When ANY item carries the per-item flag, those picks fully
 *              override the shortcode-level Initially Open setting; in
 *              single-open mode (Multiple Open = No) only the FIRST flagged
 *              item wins so the cascade can't break the constraint.
 *          (B) URL Hash Deep-Linking switch (Behaviour tab, default Yes).
 *              On init, if the URL hash matches a header or panel id
 *              inside the accordion, that item is opened and scrolled
 *              into view. On user toggle, `history.replaceState` updates
 *              the hash so links stay shareable. Closing does NOT clear
 *              the hash. Single-open mode auto-closes any current open
 *              first when honouring an inbound hash.
 *          (D) "Show Expand / Collapse All" switch (Behaviour tab, default
 *              No). When on, two convenience buttons appear above the
 *              accordion that open or close every item at once. The
 *              buttons are aria-hidden so screen-reader users aren't
 *              presented with duplicate controls — the per-title path
 *              already handles a11y.
 *          (F) Title Alignment select (Layout tab) — Left (default) /
 *              Center / Right, controls flex justify-content on the
 *              title bar so the icon + number + text all shift together.
 *          (S) Stagger Item Reveal switch (Layout tab) — fades and
 *              slides each item in sequence on first paint, 500 ms apart,
 *              via a pure-CSS keyframe driven by a `--i` index custom
 *              property emitted on each `.accordion-item`. Pairs nicely
 *              with the existing Animations tab. Respects
 *              `prefers-reduced-motion` — all items appear instantly
 *              when the OS setting is on.
 *          Code-quality / a11y improvements (ARIA Accordion Pattern
 *          migration, vanilla-JS rewrite, FOUC bug fix, reduced-motion
 *          for the open/close slide, `.accordion-number` width fix)
 *          intentionally deferred to a separate pass.
 *
 *          Also dropped the default wrapper-level "Text Color" from
 *          `[icon]`'s Styling tab — the named Title Color + Icon Color
 *          fields already cover both inner elements, so a wrapper-level
 *          pick would just compete with the per-element ones. Matches
 *          the same cleanup applied to `[accordion]` and `[icon-box]`
 *          earlier.
 *
 * 2.7.98 - `[accordion]` Title Tag select added to the Layout tab.
 *          Editors can now pick the semantic heading level for every
 *          accordion item title (H2 through H6, defaulting to H3 which
 *          matches the old hardcoded value). The picked tag is whitelist-
 *          validated in view.php before being interpolated into the
 *          opening and closing tags. Use case: accordions placed inside
 *          a section whose own heading is H2 should expose item titles
 *          as H3; accordions inside a deeper card (H3) should drop to
 *          H4, etc. Keeping the title as a real heading element (rather
 *          than a div / span / p) preserves screen-reader heading
 *          navigation. Mirrors the `title_tag` UX already available on
 *          `icon-box` and `special-heading`.
 *
 * 2.7.97 - `[accordion]` Styling tab polish + icon-color fix.
 *          (1) Field labels: "Tab Title Color" → "Title Color",
 *          "Tab Content Color" → "Content Color". Stored option keys
 *          (`tab_title_color`, `tab_content_color`) are unchanged so
 *          existing saved instances are preserved — only the editor-
 *          facing strings move.
 *          (2) Dropped the default wrapper-level `text_color` AND
 *          `bg_color` from the Styling tab (a single wrapper colour
 *          would conflict with the per-element Title/Content picks and
 *          a single background behind both header and body looked wrong)
 *          and added two new background fields: `title_bg_color` (after
 *          Title Color) and `content_bg_color` (after Content Color),
 *          both using `sc_color_field( kind=bg )`. view.php applies them
 *          to the `<h3 class="accordion-title">` bar and the
 *          `<div class="accordion-content">` panel respectively.
 *          (3) Fix: `icon_closed_color` / `icon_open_color` had no
 *          visible effect on the built-in icon styles (plus-minus,
 *          plus-x, chevron, arrow) because those are drawn by CSS
 *          pseudo-elements on `.accordion-icon` using `background:
 *          currentColor` / `border-color: currentColor` — the previous
 *          implementation attached classes to state-spans that only exist
 *          in Custom mode, so the pseudo-elements never picked them up.
 *          New routing: view.php derives the color slug from the picked
 *          preset and sets `--ws-icon-closed-color` / `--ws-icon-open-color`
 *          as CSS variables on the wrapper. styles.css uses those
 *          variables as the `color` on `.accordion-icon` (closed) and
 *          `.accordion-title.ui-state-active .accordion-icon` (open),
 *          so `currentColor` in the pseudo-element rules now resolves
 *          to the picked preset. Works for every icon style; Custom-mode
 *          state spans inherit from `.accordion-icon` for free. Open
 *          color falls back to the Closed color when only the Closed
 *          pick is set.
 *
 * 2.7.96 - Fix: `[calendar]` Navigation Buttons Color (added in 2.7.90)
 *          had no visible effect. The `text-{slug}` preset class was being
 *          written onto the `.btn-group` wrapper, but `<button>` elements
 *          carry user-agent / theme color rules that don't inherit from
 *          a parent, so the cascade never reached the prev / today / next
 *          labels. Moved the class from `.btn-group` to each `<button>`
 *          directly so the rule attaches to the actual coloured element.
 *          `.btn-group` itself stays untouched.
 *
 * 2.7.95 - `[accordion]` items are now wrapped in their own
 *          `<div class="accordion-item">` and a new "Item Spacing" option
 *          (Layout tab) sets the vertical gap between them. The wrapper
 *          matches the Bootstrap 5 accordion convention and unlocks
 *          per-item styling (spacing, plus future per-item background /
 *          border / hover work) without relying on fragile adjacent-sibling
 *          CSS. Item Spacing uses `sc_spacing_field` with `prefix=mb`, so
 *          the dropdown emits a Bootstrap `mb-{slug}` class drawn from the
 *          theme's Spacing presets; the class is applied to every
 *          `.accordion-item` except the last so the final item doesn't
 *          carry a trailing margin. JS was migrated in lock-step: each of
 *          the four `.next('.accordion-content')` calls in scripts.js
 *          becomes `.closest('.accordion-item').find('.accordion-content')`,
 *          which is robust to extra DOM siblings ever appearing between
 *          title and panel. The CSS first-header rule moves from
 *          `.accordion-title:first-child` to
 *          `.accordion-item:first-child .accordion-title`. BREAKING
 *          CHANGE: any custom theme CSS that targeted the title/content
 *          adjacency with `.accordion-title + .accordion-content`
 *          (or `.accordion-content + .accordion-title`) will no longer
 *          match — rewrite those rules as descendant selectors under
 *          `.accordion-item`. README.md updated with the new structural
 *          example, the CSS-hook list, and a migration note.
 *
 * 2.7.94 - `[calendar]` Background Color now lands on the inner calendar
 *          grid (`#cal-day-box` / `.cal-week-box` / `.cal-month-box`)
 *          instead of the outer `.fw-shortcode-calendar-wrapper`. The
 *          old behaviour tinted everything including the page-header
 *          and nav button row above the grid, which read as a
 *          background slab rather than as the calendar's surface.
 *          view.php now extracts the `bg_color` styling pick via
 *          `sc_extract_styling_classes` (so the wrapper does NOT
 *          receive it) and passes the resulting `bg-{slug}` class
 *          through to scripts.js as `data-bg-class`. scripts.js applies
 *          the class to the active view's box inside `onAfterViewLoad`,
 *          which fires after every render — so the bg color survives
 *          prev/next/today navigation re-renders. Heading Color and
 *          Navigation Buttons Color (added in 2.7.90) are unchanged.
 *
 * 2.7.93 - Per-element color styling — second batch — covers five more
 *          shortcodes following the same `special-heading` pattern used
 *          by the 2.7.90 batch (`sc_get_styling_fields` `extras` +
 *          `sc_extract_styling_classes` in the view). Per shortcode:
 *            - `special-heading` : `title_color` is now an explicit field
 *                                  alongside `subtitle_color` (was: the
 *                                  default `text_color` was being routed
 *                                  to title; new behaviour keeps
 *                                  text_color on the wrapper as a base
 *                                  and uses title_color for the override).
 *            - `notification`   : `label_color`, `message_color`,
 *                                 `icon_color`. Applied across all three
 *                                 rendering paths (legacy inline, new
 *                                 inline with custom icon, stacked).
 *                                 Inline + legacy paths wrap the message
 *                                 in a `<span>` only when a message color
 *                                 is picked.
 *            - `tabs`           : `tab_title_color`, `tab_content_color`,
 *                                 applied across all addable tab items in
 *                                 both horizontal and vertical layouts.
 *            - `text-expander`  : `visible_color`, `hidden_color`,
 *                                 `btn_show_color`, `btn_hide_color`.
 *                                 Visible/hidden colors are injected into
 *                                 each parsed `<p>` token's class attr
 *                                 via a new `fw_text_expander_add_class`
 *                                 helper (handles existing class= attrs).
 *                                 Button colors are passed into the
 *                                 button-render closure. Native <details>
 *                                 mode now wraps hidden content in a
 *                                 `<div>` so the hidden color attaches.
 *                                 Existing free-form `btn_color` hex stays
 *                                 as a fallback below the per-button picks.
 *            - `testimonials`   : `title_color` (section heading) plus
 *                                 four per-card colors threaded into
 *                                 `sc_render_card` via the `$args` array
 *                                 (`quote_color_class`,
 *                                 `author_name_color_class`,
 *                                 `author_job_color_class`,
 *                                 `site_link_color_class`). Applied across
 *                                 all three layouts (grid, single,
 *                                 carousel).
 *          The wrapper-level "Text Color" stays as the general base; named
 *          picks override on their target elements. `map`, `media-image`,
 *          `media-video`, `posts`, `row`, `section`, `table`, `team-member`,
 *          `text-block`, `widget-area` are intentionally left alone.
 *
 * 2.7.92 - Hotfix for a fatal "Parse error: syntax error, unexpected token
 *          '<'" in `shortcodes/calendar/views/view.php` introduced by
 *          2.7.90's per-element-styling rollout. The new
 *          `sc_extract_styling_classes` block was wrapped in its own
 *          `<?php … ?>` pair AND immediately re-opened with `<?php`, but
 *          the file's opening `<?php` (line 1) was never closed before
 *          that point, so the inner `<?php` was illegal mid-PHP-block
 *          syntax. Removed the three stray tags so the existing PHP
 *          context flows uninterrupted down to the `?>` on line 33.
 *          Every page rendering `[calendar]`, and every page save whose
 *          content contained `[calendar]`, was WSOD-ing. Verified the
 *          other seven views touched by 2.7.90 (icon, icon-box,
 *          call-to-action, image-content, divider, button, accordion)
 *          do NOT have the same shape.
 *
 * 2.7.91 - Drop `text_color` and `font_size_preset` from the `[column]`
 *          shortcode's Styling tab. Column is purely a layout container;
 *          a wrapper-level typography pick would cascade to every nested
 *          shortcode and element inside the column, which surprised
 *          editors more often than it helped. Margins and Paddings remain.
 *          Child shortcodes that need typography control already own
 *          their own font-size / color knobs (see 2.7.90).
 *
 * 2.7.90 - Per-element color styling rolled out to eight shortcodes. Following
 *          the `special-heading` pattern (`sc_get_styling_fields` `extras` +
 *          `sc_extract_styling_classes` in the view), each shortcode now
 *          injects extra Color Preset selectors into the Styling tab and
 *          routes them to specific inner elements instead of the wrapper,
 *          so editors can color e.g. the title separately from the body.
 *          Per shortcode:
 *            - `icon`         → `title_color`, `icon_color`.
 *            - `icon-box`     → `title_color`, `content_color`, `icon_color`.
 *            - `call-to-action` → `title_color`, `message_color`.
 *            - `image-content`  → `content_color` (added to its existing
 *                                 custom Styling tab; no sc_get_styling_fields).
 *            - `divider`      → `line_color`, `icon_color`,
 *                               `divider_text_color`. `line_color` applies
 *                               to the wrapper so CSS using `currentColor`
 *                               for border-color recolors the line.
 *            - `calendar`     → `heading_color`, `buttons_color`.
 *            - `button`       → `link_color` (the whole `<a>`),
 *                               `label_color` (label `<span>`),
 *                               `icon_color` (icon `<i>`). The label is
 *                               wrapped in a `<span>` only when a label
 *                               color is picked, otherwise rendered inline
 *                               as before.
 *            - `accordion`    → `tab_title_color`, `tab_content_color`,
 *                               `icon_closed_color`, `icon_open_color`.
 *                               Applied across all addable items (per-item
 *                               override is a separate future enhancement).
 *                               The icon-state colors only fire in the
 *                               Custom icon mode (where the open/closed
 *                               state spans are emitted); built-in icon
 *                               styles still inherit the tab-title color.
 *          The wrapper-level "Text Color" from `sc_get_styling_fields` is
 *          kept where it existed (acts as the general base; named picks
 *          override on their target elements). `code-block`, `column`,
 *          and `hero_section` are intentionally untouched.
 *
 * 2.7.89 - Rearchitect `[word_scroll]` to a page-scroll model. The previous
 *          100vh internal scroll container fought the page-builder layout
 *          (sticky lead unable to anchor inside a column, items rendering
 *          stacked with a huge gap, wheel handler not firing). The wrapper
 *          is now a plain block element in the page flow; the lead phrase
 *          is `position: sticky` to the PAGE viewport, and the items list
 *          has `padding-block: calc(50vh - 0.5lh)` so the first and last
 *          items reach viewport centre as the user scrolls the PAGE.
 *          One-item-per-scroll is delivered by `scroll-snap-align: center`
 *          + `scroll-snap-stop: always` on each `<li>` combined with a
 *          page-level `scroll-snap-type: y mandatory` toggled on `<html>`
 *          via IntersectionObserver only while a `.word-scroll` section is
 *          50%+ visible. Items font-size / weight / family / line-height
 *          are copied from the lead's computed styles by scripts.js on
 *          load, resize, and after web-font load, so lead and items
 *          render at identical typography regardless of which heading tag
 *          the editor picks. Removed the `snap` and `start_index` options
 *          (both were tied to the dropped internal scroll container) and
 *          the `.word-scroll-foot` spacer. Existing saved instances
 *          continue to render — `snap` / `start_index` attributes are
 *          silently ignored.
 *
 * 2.7.88 - Three changes to `[word_scroll]`:
 *          (1) Drop the fluid `clamp()` font-size that previously forced
 *          every instance to a hardcoded scale. Lead phrase + items now
 *          inherit the theme's typography for their respective tags, so an
 *          editor switching the heading level from H2 to H1 sees the page's
 *          actual H1 size. The `.fluid` class is no longer added to the
 *          wrapper.
 *          (2) Strict one-item-per-scroll behaviour. CSS uses
 *          `scroll-snap-type: y mandatory` + `scroll-snap-stop: always`
 *          on each item so native touch / keyboard scrolling always lands
 *          on the next item. A vanilla wheel handler in scripts.js advances
 *          exactly one item per wheel tick with a smooth-scroll, throttles
 *          back-to-back ticks via a short lock window, and bubbles wheel
 *          events back to the page at the first/last boundary so the user
 *          can scroll out of the section normally. Also keeps an internal
 *          index in sync with native scroll position so touch/keyboard
 *          users continue from the right item on the next wheel.
 *          (3) Lead phrase now stops scrolling together with the last item.
 *          Restructured the HTML so `.word-scroll-inner` (the sticky
 *          containing block for the lead) ends right at the last item's
 *          centre, and the bottom padding that allows the last item to
 *          reach the viewport centre lives outside it in a new
 *          `.word-scroll-foot` spacer. Beyond that point the lead releases
 *          from sticky and scrolls out of view together with the last item.
 *
 * 2.7.87 - Refine the `[word_scroll]` shortcode UX. The "Lead Phrase
 *          Heading Level" select now includes H1 (page hero use case)
 *          and Paragraph (when the phrase is body copy), in addition to
 *          the existing H2–H6. The page-builder card preview now shows
 *          each scrolling item on its own line under the lead phrase so
 *          editors can see the full list without opening the popup.
 *          The two intimidating "Start Hue (0–360)" / "End Hue (0–360)"
 *          numeric inputs are gone; in their place is a single "Items
 *          Color" dropdown built from `sc_color_field()`, the same
 *          Color Preset selector used elsewhere. Lead phrase and the
 *          final scrolling item keep the theme default rendering; only
 *          the middle items take the picked preset color, and falling
 *          back to `currentColor` when no preset is picked. CSS drops
 *          the `--start / --end / --lightness / --base-chroma / --step`
 *          machinery and uses `color: var(--ws-items-color, currentColor)`
 *          on `li:not(:last-of-type)`. Saved instances of the previous
 *          version with `start_hue` / `end_hue` keys still render — the
 *          unknown keys are silently ignored.
 *
 * 2.7.86 - Hide the internal scrollbar on the `[word_scroll]` snap container.
 *          The wrapper becomes its own 100vh scroll viewport when `snap` is
 *          enabled, which made the browser paint a permanent vertical
 *          scrollbar on the right that conflicted with the design.
 *          Added `scrollbar-width: none` (Firefox), `-ms-overflow-style: none`
 *          (legacy Edge / IE), and a `::-webkit-scrollbar { display: none }`
 *          rule (Chromium / Safari) on `.word-scroll[data-snap="true"]`.
 *          Scrolling and snap behaviour are unchanged.
 *
 * 2.7.85 - Rewrote the `word-scroll-highlighter` shortcode. The previous
 *          implementation was a scroll-revealed `<mark>` highlight over a
 *          single paragraph and didn't match what the feature was meant
 *          to do. Replaced wholesale with a new `[word_scroll]` shortcode
 *          (new folder `shortcodes/word-scroll/`, old folder deleted) that
 *          pairs a sticky lead phrase with a scroll-driven list of items
 *          that animate via the modern view-timeline + an OKLCH hue sweep,
 *          following the danielhaim CodePen pattern. Each item is an
 *          entry in an addable-option list; switches expose snap /
 *          animate / start_hue / end_hue / heading_tag / start_index.
 *          CSS is fully scoped under `.word-scroll` so multiple instances
 *          on one page stay independent and nothing leaks to the page.
 *          JS is vanilla (no jQuery) and only runs when the wrapper is
 *          its own scroll container (data-snap="true"). Old
 *          `[word_scroll_highlighter]` tag is dropped — no migration
 *          since the previous code was effectively unusable before
 *          v2.7.84's hotfix.
 *
 * 2.7.84 - Hotfix for a WSOD when saving any page that contains
 *          `[word_scroll_highlighter]`. The shortcode's view called
 *          `fw_get_db_shortcode_option()`, a function name that was
 *          never defined in the framework, so every save hit
 *          "Call to undefined function" during the page-builder's
 *          post-save shortcode pass. Replaced the five calls with
 *          the standard `! empty( $atts['key'] ) ? … : default`
 *          pattern other shortcodes use; the two integer slider
 *          fields use `isset && !== ''` so a legitimate `0` from
 *          the slider's `min: 0` survives.
 *
 * 2.7.83 - Hotfix to v2.7.82: `Page_Builder_Section_Like_Item` (the abstract
 *          base for section-like variants) tried to register its type and
 *          add corrector filters inside `__construct()`, but the framework's
 *          `FW_Option_Type_Builder_Item::__construct()` is `final` —
 *          activating any plugin that defined a section-like variant
 *          (including the bundled `[hero_section]`) triggered a fatal
 *          "Cannot override final method" error and took the site down.
 *          Moved the registration logic into `_init()`, the framework's
 *          blessed extension point that `_call_init()` invokes on every
 *          item right after instantiation. Behavior is identical; the only
 *          difference is which lifecycle method runs the code.
 *
 * 2.7.82 - Section-like type framework. Unyson now supports registering
 *          multiple section-type shortcodes alongside the built-in `[section]`
 *          (e.g. `[hero_section]`, `[parallax_section]`, etc.), each with its
 *          own background effect and options. Introduces
 *          `FW_Section_Like_Registry` (filterable via `fw_section_like_types`)
 *          and an abstract `Page_Builder_Section_Like_Item` base class that
 *          registers itself in the registry and hooks the items-corrector's
 *          per-type `disable-builder-item-correction` / `manual-builder-item-correction`
 *          filters so its inner rows still get corrected. JS side ships a
 *          parallel `window.fwSectionLikeTypes` registry + `createSectionLikeItem`
 *          factory mirroring the canonical section view. Four previously
 *          hardcoded `'section'` checks were refactored to consult the
 *          registry: section's `allowIncomingType` (so new section types can't
 *          nest inside each other), items-corrector lines 70 + 172 (root
 *          recognition), builder.js `STRICT_HIERARCHY` (rows can land in any
 *          section-like container), and `section-sorter.js` (lists every
 *          section-like item, not only literal sections). Ships `[hero_section]`
 *          as the first working variant: parallax background image with
 *          configurable strength, overlay color, min-height, vertical
 *          alignment. New folder `framework/extensions/shortcodes/shortcodes/hero_section/`.
 *
 * 2.7.81 - Drag-reorder hierarchy lock in the page builder. Each item type can
 *          now only commit its placeholder into the correct parent container
 *          (simple → column, column → row, row → section, section → root) —
 *          enforced inside the custom `_rearrange` override in builder.js.
 *          Fixes two long-standing annoyances when working with tall items:
 *          dragging a long text-block inside a column no longer pushes the
 *          surrounding section down when the cursor briefly leaves the column,
 *          and dragging a root-level simple shortcode no longer reorders
 *          sections aside until the cursor actually enters a valid column.
 *          `start` handler stashes the dragged item type via
 *          `ui.item.data('fw-source-item-type', …)`; `_rearrange` reads it,
 *          resolves the target container's parent model via a DOM walk +
 *          `findItemRecursive`, and blocks cross-level commits before they
 *          start the existing 100ms settle timer. Thumbnail-panel drops are
 *          unaffected (start returns early for them so no data is stashed).
 *
 * 2.7.80 - Page Builder gains a "Sort Sections" dropdown in the builder header,
 *          left of the Templates button. Opens a qtip2 list of every root
 *          section with a drag handle, collapse toggle, and click-to-scroll —
 *          so reordering a section out of a 20+ section page no longer requires
 *          holding the mouse while the canvas auto-scrolls. Drag inside the
 *          dropdown calls the same `builder:change` path the canvas drag uses,
 *          so undo/redo and JSON serialization stay consistent. New JS+CSS
 *          live with the page-builder extension; the option-type-page-builder
 *          enqueue pipeline gained one script + one style + a localized l10n
 *          object.
 *
 * 2.7.79 - Add admin notice recommending the Classic Editor plugin
 *          (https://wordpress.org/plugins/classic-editor/) when it isn't
 *          active. With Gutenberg the Page Builder meta box sits below a
 *          second editor, which is confusing for authors. The notice offers
 *          a one-click Install or Activate button (depending on the plugin's
 *          current state), a "Learn more" link, and a per-user persistent
 *          dismiss. Shown only to users with `install_plugins` capability.
 *
 * 2.7.76 - Hotfix follow-up to v2.7.75: switching to unysonplus-theme on a
 *          site running framework v2.7.75 produced a fatal "Cannot redeclare
 *          class FW_Option_Type_FwMultiInline" — because the theme's own
 *          copy of the option type loads AFTER the plugin's bootstrap
 *          (post-`after_setup_theme`) and didn't have a class_exists guard.
 *
 *          The plugin now gates the include with `'unysonplus-theme' !==
 *          get_template()` in bootstrap.php — when the theme is active the
 *          plugin doesn't ship its own copy at all, letting the theme own
 *          the class declaration unambiguously. unysonplus-theme also got
 *          a class_exists guard added to its copy (theme v1.1.9) as a
 *          belt-and-suspenders fix.
 *
 * 2.7.75 - Bundle the `fw-multi-inline` option type into the framework. The
 *          Shortcode Options → Buttons → Sizes tab uses fw-multi-inline for
 *          its Top/Right/Bottom/Left padding control. Previously this type
 *          was only defined by unysonplus-theme, so on generic themes
 *          (twentytwentyfour etc.) users saw the warning
 *          "Undefined option type: fw-multi-inline" and Theme Settings →
 *          Shortcode Options → Buttons wouldn't render.
 *
 *          New files under framework/includes/option-types/fw-multi-inline/
 *          (class, view, CSS) — adapted from the theme's copy to use the
 *          plugin's URI. The class declaration is wrapped in
 *          `class_exists( 'FW_Option_Type_FwMultiInline' )` so it's a no-op
 *          on unysonplus-theme (theme declares it first; plugin's guard
 *          short-circuits) and active on every other theme.
 *
 * 2.7.74 - Theme Settings page renders the "Shortcode Options" tab vertically
 *          (side tabs) on generic themes, matching unysonplus-theme's layout.
 *          Added a new `fw_theme_config` filter in
 *          framework/core/components/theme.php (applied once per request to
 *          the resolved theme config array). Shortcodes extension hooks it
 *          to set `settings_form_side_tabs => true` whenever the plugin is
 *          the one supplying tabs (i.e. on a non-unysonplus theme). On
 *          unysonplus-theme the hook short-circuits — theme's own config
 *          wins.
 *
 * 2.7.73 - Fix follow-up to v2.7.72: on non-Unyson-aware themes (e.g.
 *          twentytwentyfour) the Theme Settings menu under Appearance was
 *          never registered, because the framework's
 *          `FW_Settings_Form_Theme::_action_admin_menu()` hard-bails when the
 *          active theme has no framework-customizations/theme/options/settings.php.
 *          The plugin's injected "Shortcode Options" tab had no host page.
 *          Added a new filter `fw_theme_settings_menu_register` in
 *          framework/core/components/backend/class-fw-settings-form-theme.php
 *          that lets plugins force the menu when they have options to inject.
 *          The shortcodes extension's loader.php hooks this filter to force
 *          registration whenever Shortcode Options would otherwise have
 *          nowhere to render. Generic themes now show Appearance → Theme
 *          Settings → Shortcode Options out of the box.
 *
 * 2.7.72 - Plugin now injects a "Shortcode Options" tab into the existing
 *          Appearance → Theme Settings page on any non-unysonplus theme,
 *          giving users the same UI for managing Color Presets, Font Size
 *          Presets, Spacing Scale, Button Color Presets, and Button Sizes
 *          regardless of which theme they run. Implementation: new
 *          extensions/shortcodes/includes/shortcode-options/{schema,loader}.php
 *          hooks the `fw_settings_options` filter and merges the schema into
 *          theme-provided options. When unysonplus-theme is the active theme
 *          the injection is suppressed (theme already provides equivalent
 *          tabs). Storage is unchanged — same option keys (theme_colors,
 *          font_sizes, spacing_scale, button_colors, button_sizes) saved via
 *          Theme Settings' built-in handler; getters in presets.php read the
 *          same place transparently. Help text in shortcode Styling tab
 *          tooltips now uses the "Add more in Theme Settings → …" wording on
 *          any theme — the GitHub fallback is only emitted if both
 *          sc_theme_provides_settings_ui() and sc_plugin_provides_settings_ui()
 *          return false (edge case: shortcodes extension disabled).
 *
 * 2.7.71 - Styling tab help text is now theme-aware. On unysonplus-theme the
 *          existing "Add more in Theme Settings → …" text stays unchanged.
 *          On any other theme, the same `help` icons now read "Install the
 *          Unyson+ Theme to manage … visually" and link to
 *          https://github.com/UnysonPlus/UnysonPlus-Theme. Detection via new
 *          `sc_theme_provides_settings_ui()` (filterable for third-party
 *          themes that re-implement the Theme Settings UI); copy centralised
 *          in `sc_styling_help_text( $context )`.
 *
 * 2.7.70 - Styling tab polish:
 *          • Luminance threshold for "needs contrast backdrop" tightened from
 *            0.85 → 0.95 in sc_color_is_light(). Yellow / Lime / Light Gray
 *            now render in their actual hue (no backdrop); only true whites
 *            (#fff, Bootstrap Light #f8f9fa) keep the contrast trick.
 *          • Backdrop colour softened from harsh #222 to mid-dark #444 across
 *            all three emitters (color-select, color-preset-select,
 *            button-style-select).
 *          • Removed the "Add more in Theme Settings → Spacing" help link
 *            from the 8 per-side spacing dropdowns (Top / Right / Bottom /
 *            Left × margins + paddings). Help stays on the two All-Sides
 *            fields, which is enough — repeating it on every per-side row
 *            was visual clutter.
 *
 * 2.7.69 - Shortcode Styling tab UX: every preset-picker field (Text Color,
 *          Background Color, Font Size Preset, Margin/Padding, Button Style,
 *          Outline Style, Button Size) now has a `help` link that opens the
 *          relevant Theme Settings tab in a new browser tab — so users can
 *          jump straight to where they can add more presets. Implemented via
 *          new `sc_theme_settings_url( $context )` helper with
 *          `sc_theme_settings_url` filter for non-unysonplus themes.
 *
 *          Visual fix: white / near-white preset options in the dropdowns no
 *          longer appear as blank rows. The `sc_emit_color_*_admin_css` and
 *          `sc_emit_button_style_select_admin_css` emitters now use perceived
 *          luminance (BT.601) to detect light colours and apply a dark
 *          contrast backdrop for the option label, while keeping the swatch
 *          colour. New helper `sc_color_is_light( $hex )`.
 *
 * 2.7.68 - WP.org submission readiness (Phase E.1 — Quick wins):
 *          • Replaced fw_rand_md5() weak randomness (rand/mt_rand/uniqid) with
 *            CSPRNG-backed random_bytes() in framework/helpers/general.php.
 *          • Escaped 6+ unescaped echoes in shortcode/portfolio views
 *            (portfolio/content.php, table/tabular, table/pricing,
 *            table/textarea-cell) using wp_kses_post / esc_html / esc_textarea
 *            per context. Fixed buggy `echo esc_html_e()` in about.php.
 *          • Replaced uniqid()-based DOM IDs with wp_unique_id() in accordion,
 *            tabs, and testimonials shortcode views.
 *          • Added missing 'fw' text domain on 11 _e()/__()/esc_html_e() calls
 *            in framework/views/about.php; routed plugin-install URL through
 *            esc_url() in the same file.
 *
 * 2.7.67 - Security hardening (Phase D — Builder fullscreen, multi-select
 *          autocomplete, Builder templates, Sidebars, Backend options):
 *          added wp_create_nonce + check_ajax_referer to all remaining
 *          mutating AJAX handlers. Each PHP handler is paired with its JS
 *          caller (localized nonce from wp_localize_script) in this release:
 *          • fw_builder_fullscreen_set/unset_storage_item
 *          • fw_option_type_multi_select_autocomplete
 *          • fw_builder_templates_render / fw_builder_templates_full_load /
 *            _save / _delete
 *          • 6 sidebar handlers (add_new / autocomplete / save_preset /
 *            remove_preset / delete / load_preset)
 *          • 3 backend-options handlers (render / get_values /
 *            get_values_json) — Theme Settings save, addable-box re-render,
 *            and reactive-options pipelines all now CSRF-protected.
 *
 * 2.7.66 - Security hardening (Phase D — Mailer): added CSRF nonce protection
 *          to the Mailer test-connection AJAX endpoint. PHP handler now calls
 *          check_ajax_referer( 'fw_ext_mailer_test_connection', '_nonce' );
 *          a fresh nonce is localized via wp_localize_script and posted by the
 *          option-type-mailer JS. First feature area of Phase D — handler /
 *          caller pair locked together in this release.
 *
 * 2.7.65 - Security hardening (Phase C): hardened the public calendar AJAX
 *          endpoint (wp_ajax_nopriv_shortcode_calendar_get_events). Added
 *          provider-slug whitelist via sanitize_key + isset check, is_callable
 *          guard on the resolved callback, and a new
 *          `fw_shortcode_calendar_provider_is_allowed` filter for per-site
 *          opt-out. Keeps the public endpoint functional for legitimate
 *          public-page calendars while preventing arbitrary callback invocation.
 *
 * 2.7.64 - Security hardening (Phase A + B):
 *          • Wrapped internal SQL identifiers through esc_sql() and converted
 *            "SHOW TABLES LIKE" to $wpdb->prepare() in framework/helpers/database.php
 *            and unysonplus.php (legacy fw_termmeta cleanup paths).
 *          • Blocked PHP object injection on legacy termmeta unserialize via
 *            allowed_classes=false.
 *          • Documented FW_Request output-escaping responsibilities in the
 *            class docblock (helpers/class-fw-request.php).
 *          • Escaped $atts in team-member, call-to-action, notification
 *            shortcode views and breadcrumbs view (esc_html / wp_kses_post /
 *            esc_url per context).
 */
