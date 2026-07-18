<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Typography (v2) — DEPRECATION ALIAS.
 *
 * The rich typography control was promoted to the clean `typography` name
 * (see FW_Option_Type_Typography). `typography-v2` is kept only so existing
 * schemas, saved shortcode items, child themes and extensions that still
 * declare `'type' => 'typography-v2'` keep working unchanged.
 *
 * It is a thin subclass: it overrides ONLY get_type(). Everything else —
 * rendering, the editor JS/CSS, google-fonts, value parsing, defaults — is
 * inherited from FW_Option_Type_Typography, whose enqueue + view are pinned to
 * the `typography` asset folder (self::ASSET_BASE), so this alias serves the
 * SAME assets and produces the SAME markup and value shape.
 *
 * Prefer `'type' => 'typography'` in new code.
 */
class FW_Option_Type_Typography_v2 extends FW_Option_Type_Typography {

	public function get_type() {
		return 'typography-v2';
	}

}
