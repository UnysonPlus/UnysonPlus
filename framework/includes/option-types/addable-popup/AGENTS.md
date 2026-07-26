# `addable-popup` option type — how to USE it (+ cross-list drag-and-drop)

A repeatable list where each row is edited in a **modal** (the popup), rendered from a template.
The header/footer column builders are the canonical consumers. This note covers the normal contract
and the **`connect_group`** cross-list drag-and-drop feature, which has one non-obvious gotcha.

## What it is
A vertical, sortable list of items. Each item's data is a hash edited via `fw.OptionsModal` using
`popup-options`; the row's visible label comes from the underscore `template`. `sortable => true`
(default) makes items drag-reorderable within the list.

## Saved value shape
An **array of row hashes** — `[ { …popup-option values… }, … ]`. Each item's value lives in a hidden
`<input>` inside the row; **the form saves via native `$el.serialize()`**, and every item's input
name is `fw_options[…][<option_id>][]` (array-append — that's how multiple rows survive one name).

## How to consume
Read the saved array from `fw_get_db_settings_option( '<id>' )` (or the parent container's value).
Config keys: `popup-options` (the modal fields), `template` (row label, `{{= … }}`), `size`
(`small`/`medium`/`large`), `limit` (0 = unlimited), `sortable`, and `connect_group` (below).

## Cross-list drag-and-drop — `connect_group` (opt-in)

Set the **same non-empty `connect_group`** on two or more `addable-popup`s and their items can be
dragged **between** them (jQuery-UI `connectWith`). Empty (default) = self-contained, vertical-only.

```php
// Each column of ONE header/footer bar shares a group id → items drag across columns.
'main_footer_col_1' => array( 'type' => 'addable-popup', /* … */ 'connect_group' => 'main_footer_2' ),
'main_footer_col_2' => array( 'type' => 'addable-popup', /* … */ 'connect_group' => 'main_footer_2' ),
```

Scope the id per logical group (a row + its column count, a header bar) so **unrelated**
addable-popups on the same page don't interlink. The id is sanitized to a CSS-class-safe token and
emitted as `.fw-ap-connect-<group>` on the wrapper; `connectWith` targets `.fw-ap-connect-<group>
.items-wrapper`.

### The gotcha (why a naive connectWith reverts on save)
Because the form saves by **input name** (`serialize()`), a dragged item keeps its **origin
column's** name and would save back there — the item moves visually but **reverts on reload**. The
fix (already in `scripts.js`): on jQuery-UI `receive`, **re-key the moved item's input `name`** to
the receiving column. The receiving name template is captured from that column's `.default-item`
*before* it's removed, so it's known even for an **empty** column. Empty connected columns are also
kept visible (a "Drag an item here" drop target) since a `display:none` list can't receive a drop.

**If you add a new connected list and moves don't persist**, the receive-time name re-key is the
layer to check — value/DOM order alone is not enough; the input name is what serialize() reads.

## Canonical snippet
See `unysonplus_footer_column()` / `unysonplus_header_column()` in the theme
(`inc/includes/header-footer-option-helpers.php`): they pass `connect_group` = the bar/row id so all
columns of one bar interlink, and nothing else does.
