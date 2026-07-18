<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Background Pattern presets — reusable CSS/HTML background patterns that get applied to
 * Sections / Containers / the site background as a scoped
 * `.pattern-{slug}` decorative layer. Loaded by ../presets.php. Stored THEME-SCOPED under
 * the `background_patterns` key (via the Components → Background Patterns addable-box).
 *
 * Each preset entry:
 *   array(
 *     'id'           => string,  // unique
 *     'pattern_name' => string,  // label -> .pattern-{slug}
 *     'root_class'   => string,  // outermost class of the pasted HTML (optional; auto-detected)
 *     'html'         => string,  // the pattern markup (may include an inline <svg><filter>)
 *     'css'          => string,  // the pattern CSS (class names kept as pasted; scoped per preset)
 *   )
 *
 * NOTE (scope): this file only defines the presets + slug map. The scope/namespace cleanup
 * transform and the `.pattern-{slug}` CSS generation (css-tokens.php) + the Section/Body
 * consumption are wired in a later step — the preset library + its live preview come first.
 */

if ( ! function_exists( 'unysonplus_default_pattern_presets' ) ) :
	function unysonplus_default_pattern_presets() {
		// 12 original, purely-CSS starter patterns (no external / third-party sources). Each is a
		// semi-transparent overlay (dark rgba marks) so it reads on any site/section background;
		// crank the rgba opacity in a preset to make it bolder. Built via a small helper that
		// wraps each into its `.pat-{id}` div + a full-size rule.
		$p = function ( $id, $name, $cls, $decls ) {
			return array(
				'id'           => $id,
				'pattern_name' => $name,
				'root_class'   => $cls,
				'html'         => '<div class="' . $cls . '"></div>',
				'css'          => '.' . $cls . '{width:100%;height:100%;' . $decls . '}',
			);
		};

		return array(
			$p( 'dots', __( 'Dots', 'fw' ), 'pat-dots',
				'background-image:radial-gradient(rgba(0,0,0,.18) 1.6px,transparent 1.7px);background-size:22px 22px' ),
			$p( 'grid', __( 'Grid', 'fw' ), 'pat-grid',
				'background-image:linear-gradient(rgba(0,0,0,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(0,0,0,.12) 1px,transparent 1px);background-size:26px 26px' ),
			$p( 'diagonal-stripes', __( 'Diagonal Stripes', 'fw' ), 'pat-diagonal',
				'background:repeating-linear-gradient(45deg,rgba(0,0,0,.07) 0 10px,transparent 10px 20px)' ),
			$p( 'vertical-stripes', __( 'Vertical Stripes', 'fw' ), 'pat-vertical',
				'background:repeating-linear-gradient(90deg,rgba(0,0,0,.07) 0 10px,transparent 10px 20px)' ),
			$p( 'horizontal-stripes', __( 'Horizontal Stripes', 'fw' ), 'pat-horizontal',
				'background:repeating-linear-gradient(0deg,rgba(0,0,0,.07) 0 10px,transparent 10px 20px)' ),
			$p( 'checkerboard', __( 'Checkerboard', 'fw' ), 'pat-checker',
				'background-image:linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%),linear-gradient(45deg,rgba(0,0,0,.1) 25%,transparent 25%,transparent 75%,rgba(0,0,0,.1) 75%);background-size:28px 28px;background-position:0 0,14px 14px' ),
			$p( 'crosshatch', __( 'Crosshatch', 'fw' ), 'pat-crosshatch',
				'background-image:repeating-linear-gradient(45deg,rgba(0,0,0,.08) 0 1px,transparent 1px 12px),repeating-linear-gradient(-45deg,rgba(0,0,0,.08) 0 1px,transparent 1px 12px)' ),
			$p( 'triangles', __( 'Triangles', 'fw' ), 'pat-triangles',
				'background-image:linear-gradient(45deg,rgba(0,0,0,.09) 25%,transparent 25%),linear-gradient(-45deg,rgba(0,0,0,.09) 25%,transparent 25%);background-size:20px 20px' ),
			$p( 'chevron', __( 'Chevron', 'fw' ), 'pat-chevron',
				'background:linear-gradient(135deg,rgba(0,0,0,.08) 25%,transparent 25%) -12px 0/24px 24px,linear-gradient(225deg,rgba(0,0,0,.08) 25%,transparent 25%) -12px 0/24px 24px,linear-gradient(315deg,rgba(0,0,0,.08) 25%,transparent 25%) 0 0/24px 24px,linear-gradient(45deg,rgba(0,0,0,.08) 25%,transparent 25%) 0 0/24px 24px' ),
			$p( 'circles', __( 'Circles', 'fw' ), 'pat-circles',
				'background-image:radial-gradient(circle at 50% 50%,transparent 5px,rgba(0,0,0,.09) 6px,transparent 7px);background-size:24px 24px' ),
			$p( 'scales', __( 'Scales', 'fw' ), 'pat-scales',
				'background-image:radial-gradient(circle at 50% 100%,transparent 9px,rgba(0,0,0,.08) 10px,transparent 11px);background-size:24px 12px' ),
			$p( 'confetti', __( 'Confetti', 'fw' ), 'pat-confetti',
				'background-image:radial-gradient(rgba(0,0,0,.15) 1.6px,transparent 1.7px),radial-gradient(rgba(0,0,0,.1) 1.6px,transparent 1.7px);background-size:30px 30px,30px 30px;background-position:0 0,15px 15px' ),
		);
	}
endif;

if ( ! function_exists( 'unysonplus_get_pattern_presets' ) ) :
	/** Saved patterns (theme-scoped) or the defaults when nothing is saved yet. */
	function unysonplus_get_pattern_presets() {
		$saved = function_exists( 'unysonplus_preset_store_get' )
			? unysonplus_preset_store_get( 'background_patterns', null )
			: null;
		if ( is_array( $saved ) ) {
			return $saved;
		}
		return unysonplus_default_pattern_presets();
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_preset_slug_map' ) ) :
	/**
	 * [ preset-id => css-slug ] so a pattern named "Dots" → `.pattern-dots`.
	 * Slug = lower-case, non-alphanumerics → '-', trimmed; collisions get a numeric suffix
	 * (-2, -3, …) in preset order; empty/symbol-only names fall back to the sanitized id. Single
	 * source of truth for the `.pattern-{slug}` class, shared by the (later) css-tokens generation
	 * and the Section/Body pattern picker.
	 */
	function unysonplus_pattern_preset_slug_map() {
		$map  = array();
		$seen = array();
		foreach ( unysonplus_get_pattern_presets() as $p ) {
			if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
			if ( $id === '' ) { continue; }
			$name = isset( $p['pattern_name'] ) ? (string) $p['pattern_name'] : '';
			$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) ), '-' );
			if ( $slug === '' ) { $slug = strtolower( $id ); }
			$base = $slug;
			$n    = 2;
			while ( isset( $seen[ $slug ] ) ) {
				$slug = $base . '-' . $n;
				$n++;
			}
			$seen[ $slug ] = true;
			$map[ $id ]    = $slug;
		}
		return $map;
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_select_choices' ) ) :
	/**
	 * Choices for a Background Pattern picker: `[ '' => 'None', <preset-id> => <name>, … ]`.
	 * The stored value is the preset ID (stable across renames); the render layer maps it to
	 * the current `.pattern-{slug}` via the slug map.
	 *
	 * @return array
	 */
	function unysonplus_pattern_select_choices() {
		$out = array( '' => __( 'None', 'fw' ) );
		foreach ( unysonplus_get_pattern_presets() as $p ) {
			if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
			if ( $id === '' ) { continue; }
			$name = ( isset( $p['pattern_name'] ) && $p['pattern_name'] !== '' ) ? (string) $p['pattern_name'] : $id;
			$out[ $id ] = $name;
		}
		return $out;
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_thumb_datauri' ) ) :
	/**
	 * A static thumbnail of a pattern as a `data:image/svg+xml` URI — the pattern's HTML + CSS
	 * embedded in an SVG `<foreignObject>` (the html-to-image technique). Renders inside an
	 * `<img>` (so the framework image-picker can use it): gradients / layout / colors show;
	 * CSS animations show frame 0 and SVG filters may not apply (fine for a swatch). Self-
	 * contained + inline, so no external fetch happens in the `<img>` sandbox.
	 */
	function unysonplus_pattern_thumb_datauri( $html, $css, $w = 200, $h = 120 ) {
		$inner = '<div xmlns="http://www.w3.org/1999/xhtml" style="width:' . (int) $w . 'px;height:' . (int) $h . 'px;overflow:hidden">'
			. '<style>' . (string) $css . '</style>' . (string) $html . '</div>';
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . (int) $w . '" height="' . (int) $h . '" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '">'
			. '<foreignObject width="100%" height="100%">' . $inner . '</foreignObject></svg>';
		return 'data:image/svg+xml,' . rawurlencode( $svg );
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_imagepicker_choices' ) ) :
	/**
	 * image-picker `choices` for a popover Background Pattern picker — a `none` choice plus one
	 * generated thumbnail per preset (keyed by preset id; the sentinel `none` = no pattern).
	 * Consumed by the Section / Container / Site pickers (multi-picker, popover).
	 *
	 * `none` MUST stay a real choice (not a deleted one): it's the default value, and it's the only
	 * way the <select> can reliably hold "no pattern" through the options modal's collect / re-render
	 * / lazy-tab cycles. Without it the picker falls back to its FIRST tile and any save silently
	 * stores that (the "Dots gets set when I change any option" bug). Its TILE is hidden from popover
	 * pickers by the image-picker option type (see its scripts.js) — so it never shows as a redundant
	 * pickable tile, but the value model stays intact and robust.
	 *
	 * @return array
	 */
	function unysonplus_pattern_imagepicker_choices() {
		$none = 'data:image/svg+xml,' . rawurlencode(
			'<svg xmlns="http://www.w3.org/2000/svg" width="200" height="120">'
			. '<rect width="200" height="120" fill="#f8fafc"/>'
			. '<line x1="0" y1="120" x2="200" y2="0" stroke="#e2e8f0" stroke-width="2"/>'
			. '<text x="100" y="67" text-anchor="middle" font-family="-apple-system,Segoe UI,sans-serif" font-size="15" fill="#94a3b8">None</text></svg>'
		);
		$choices = array(
			'none' => array(
				'small' => array( 'src' => $none, 'height' => 66 ),
				'large' => array( 'src' => $none, 'height' => 132 ),
				'label' => __( 'None', 'fw' ),
			),
		);
		foreach ( unysonplus_get_pattern_presets() as $p ) {
			if ( ! is_array( $p ) || empty( $p['id'] ) ) { continue; }
			$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $p['id'] );
			if ( $id === '' ) { continue; }
			$name = ( isset( $p['pattern_name'] ) && $p['pattern_name'] !== '' ) ? (string) $p['pattern_name'] : $id;
			$uri  = unysonplus_pattern_thumb_datauri(
				isset( $p['html'] ) ? (string) $p['html'] : '',
				isset( $p['css'] )  ? (string) $p['css']  : ''
			);
			$choices[ $id ] = array(
				'small' => array( 'src' => $uri, 'height' => 66 ),
				'large' => array( 'src' => $uri, 'height' => 132 ),
				'label' => $name,
			);
		}
		return $choices;
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_render_layer' ) ) :
	/**
	 * The decorative background-pattern LAYER for a preset id — an `aria-hidden`,
	 * non-interactive wrapper carrying the pattern's (scoped, id-namespaced) markup, meant to
	 * sit behind a host's content (`.upw-has-pattern > .pattern-layer` at z-index 0; content
	 * above). Returns '' for an empty/unknown id.
	 *
	 * The markup is admin-authored (Theme Settings, manage_options) so it is trusted, but any
	 * `<script>` is stripped since JavaScript patterns are unsupported. The filter ids are
	 * namespaced to match the generated `.pattern-{slug}` CSS (see unysonplus_pattern_scope()).
	 *
	 * @param string $id    preset id (the Background Pattern picker's saved value).
	 * @param bool   $fixed true → a FIXED full-viewport layer (site background) instead of the
	 *                      default absolute layer that fills its host (Section / Container).
	 * @return string
	 */
	function unysonplus_pattern_render_layer( $id, $fixed = false ) {
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $id );
		if ( $id === '' ) { return ''; }

		$map = unysonplus_pattern_preset_slug_map();
		if ( ! isset( $map[ $id ] ) ) { return ''; }
		$slug = $map[ $id ];

		$preset = null;
		foreach ( unysonplus_get_pattern_presets() as $p ) {
			if ( is_array( $p ) && isset( $p['id'] ) && (string) $p['id'] === $id ) { $preset = $p; break; }
		}
		if ( ! $preset ) { return ''; }

		$html = isset( $preset['html'] ) ? (string) $preset['html'] : '';
		$css  = isset( $preset['css'] )  ? (string) $preset['css']  : '';

		// Namespace the filter ids inside the markup to match the generated CSS.
		if ( function_exists( 'unysonplus_pattern_scope' ) ) {
			$scoped = unysonplus_pattern_scope( $html, $css, $slug );
			$html   = isset( $scoped['html'] ) ? $scoped['html'] : $html;
		}

		// JS patterns are unsupported — strip any <script> defensively.
		$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );

		$cls = 'pattern-layer pattern-' . $slug . ( $fixed ? ' pattern-layer--fixed' : '' );

		return '<div class="' . esc_attr( $cls ) . '" aria-hidden="true">'
			. $html
			. '</div>';
	}
endif;

if ( ! function_exists( 'unysonplus_get_site_background_pattern' ) ) :
	/** The pattern id chosen for the whole-site background (Theme Settings → Components →
	 *  Background Patterns → Site Background Pattern), or '' when none. Theme-scoped. */
	function unysonplus_get_site_background_pattern() {
		$v = '';
		if ( class_exists( 'FW_WP_Option' ) ) {
			$theme_id = function_exists( 'fw' ) ? fw()->theme->manifest->get_id() : 'default';
			$opt      = 'fw_theme_settings_options:' . $theme_id;
			$sentinel = '__upw_sbp_unset__';
			// The UnysonPlus theme nests the picker under its `general_layout` Layout container
			// (next to Site Background); other themes surface it at the TOP level via
			// Components → Background Patterns. Raw reads only — NOT fw_get_db_settings_option,
			// which would recurse + fire fw_option_types_init early (see presets/store.php).
			$v = FW_WP_Option::get( $opt, 'general_layout/site_background_pattern', $sentinel );
			if ( $v === $sentinel ) {
				$v = FW_WP_Option::get( $opt, 'site_background_pattern', '' );
			}
		}
		// Popover multi-picker stores { pattern: <id|'none'> }; tolerate a legacy scalar too.
		$id = is_array( $v ) ? ( isset( $v['pattern'] ) ? $v['pattern'] : '' ) : $v;
		$id = is_string( $id ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $id ) : '';
		return ( $id === 'none' ) ? '' : $id;
	}
endif;

if ( ! function_exists( 'unysonplus_render_site_background_pattern' ) ) :
	/**
	 * Front-end: draw the chosen site background pattern as a FIXED, full-viewport decorative
	 * layer behind the whole page (`.pattern-layer--fixed`, z-index -1). Shows through wherever
	 * the theme's content is transparent — same behaviour as any body background. Hooked on
	 * wp_footer; position:fixed makes the DOM position irrelevant.
	 *
	 * @internal
	 */
	function unysonplus_render_site_background_pattern() {
		if ( is_admin() || ! function_exists( 'unysonplus_pattern_render_layer' ) ) { return; }
		$id = unysonplus_get_site_background_pattern();
		if ( $id === '' ) { return; }
		echo unysonplus_pattern_render_layer( $id, true ); // phpcs:ignore WordPress.Security.EscapeOutput — admin-authored, scoped + script-stripped
	}
	add_action( 'wp_footer', 'unysonplus_render_site_background_pattern' );
endif;
