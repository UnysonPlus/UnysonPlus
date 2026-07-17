<?php
/**
 * PHP Version: 8.2 or higher
 */

if (!defined('FW')) { die('Forbidden'); }

// The canonical modern icon engine now lives in the icon-v3 class. Load it
// before subclassing (autoload requires the option-type files in folder order —
// icon-v2 before icon-v3 — so the parent may not be defined yet; require_once is
// a no-op when autoload later requires the same file).
require_once dirname(dirname(__FILE__)) . '/icon-v3/class-fw-option-type-icon-v3.php';

/**
 * icon-v2 — the production icon option type used by ~23 shortcodes + the
 * megamenu. It now RUNS the canonical modern engine (the icon-v3
 * implementation): merged Icons/Custom tabs, Emoji, Animated (Lottie),
 * favorites, SVG upload, and the raster image-upload fixes.
 *
 * The type id stays 'icon-v2' so every consumer keeps working with no shortcode
 * edits. Backward compatibility is 100%: icon-v2's stored value shape is a
 * strict subset of the engine's ({type:'none'|'icon-font'|'custom-upload'|
 * 'emoji'|'svg', …} — the engine only ADDS the 'lottie' type), so existing saved
 * values need no migration. Legacy scalar values are still bridged by the
 * engine's normalize_value().
 *
 * Only get_type() differs from the engine. Everything else (enqueue, render,
 * value handling, templates) is inherited — and the engine deliberately loads
 * its assets from the fixed icon-v3 folder under shared handles, so this
 * subclass and the icon-v3 test type share ONE picker instance with no clash.
 *
 * (The old icon-v2 picker assets in this folder — icon-picker-v2.js,
 * render-icon-previews.js, css/picker.css, views/* — are no longer enqueued.
 * They are left in place for reference / rollback and can be removed later.)
 */
class FW_Option_Type_Icon_v2 extends FW_Option_Type_Icon_v3
{
    public function get_type(): string
    {
        return 'icon-v2';
    }
}
