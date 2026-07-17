<?php if (!defined('FW')) die('Forbidden');

// The canonical modern icon engine lives in the icon-v3 class. Load it before
// subclassing (autoload requires option-type files in folder order — `icon`
// before `icon-v3` — so the parent may not be defined yet; require_once is a
// no-op when autoload later requires the same file).
require_once dirname(dirname(__FILE__)) . '/icon-v3/class-fw-option-type-icon-v3.php';

/**
 * `icon` — the original Unyson stock font-icon type, now RECLAIMED to run the
 * canonical modern engine (the icon-v3 implementation): merged Icons/Custom
 * tabs, Emoji, Animated (Lottie), favorites, SVG upload.
 *
 * Keeping the `icon` id means its consumers upgrade automatically with no edits:
 *   - the `divider` shortcode (Select Icon),
 *   - the `post-types` extension's CPT menu-icon picker,
 *   - the theme demo option pages.
 *
 * Backward compatibility is 100%. The stock type stored a bare class STRING
 * (e.g. 'fa fa-star' / 'dashicons dashicons-admin-post'); the engine's
 * normalize_value() bridges that scalar to the canonical {type:'icon-font',
 * 'icon-class':…} array on load/render, and every consumer already reads either
 * shape — the frontend via sc_icon_render() (which also runs the FA4→FA6
 * migration), and post-types via resolve_menu_icon(). So old saved values keep
 * rendering unchanged.
 *
 * Only get_type() differs from the engine. Everything else (enqueue, render,
 * value handling, templates) is inherited, and the engine loads its assets from
 * the fixed icon-v3 folder under shared handles — so `icon`, `icon-v2` and
 * `icon-v3` all share ONE picker instance with no clash. The old stock assets
 * (static/js/backend.js, static/css/backend.css, the hardcoded FA4 set) are no
 * longer enqueued; they are left in place for reference / rollback.
 */
class FW_Option_Type_Icon extends FW_Option_Type_Icon_v3
{
    public function get_type(): string
    {
        return 'icon';
    }
}
