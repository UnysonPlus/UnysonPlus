<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Per-page dynamic CSS pipeline — collapses the page's dynamic styles into a
 * single hashed `page-{id}-{hash}.css` under wp-content/uploads/unysonplus/,
 * enqueued under the handle `unysonplus-dynamic` so the Asset Optimizer absorbs
 * it into the combined site CSS (same strategy as presets-{hash}.css in
 * css-tokens.php). Falls back to an inline <style id="unysonplus-dynamic-css">
 * when the uploads dir is not writable.
 *
 * Two sources are concatenated per page:
 *
 *   1. Per-element Custom CSS — every page-builder element's `custom_css`
 *      field, read straight from the builder JSON (post meta), scoped to the
 *      element's prefix-independent `.u{hash}` class (sc_element_scope_class()).
 *      Because the CSS lives in the builder JSON it travels with template
 *      export/import for free and renders on every page the element is placed.
 *
 *   2. Page-level CSS — contributed by the theme through the
 *      `unysonplus_page_css` filter (page background + the page's own
 *      "Custom CSS (this page only)" meta). Keeping it behind a filter keeps
 *      the plugin/theme decoupled and lets a child theme override.
 *
 * The companion global layer (presets + site-wide Custom CSS via the
 * `unysonplus_global_css` filter) is handled in css-tokens.php. Net view-source:
 * one global <link> + one per-page <link>, which the combiner merges into one.
 */

if ( ! function_exists( 'unysonplus_scrub_element_css' ) ) :
	/**
	 * Defense-in-depth scrub for author-provided element CSS. Mirrors the
	 * custom hover-animation scrub in css-tokens.php: no markup, no
	 * script/style tags, no @import / javascript: / expression() tricks.
	 *
	 * @param string $css Raw CSS from the element's custom_css field.
	 * @return string Scrubbed CSS.
	 */
	function unysonplus_scrub_element_css( $css ) {
		$css = (string) $css;
		if ( trim( $css ) === '' ) { return ''; }
		$css = preg_replace( '#</?(style|script)[^>]*>#i', '', $css );
		$css = str_replace( array( '<', '>' ), '', $css );
		$css = preg_replace( '/@import\b/i', '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		return $css;
	}
endif;

if ( ! function_exists( 'unysonplus_collect_element_css' ) ) :
	/**
	 * Recursively walk builder JSON nodes, collecting each element's scoped
	 * Custom CSS into $parts. A node is `{ type, atts:{ …optionValues }, _items }`
	 * — the page-builder item layer stores every option value (including
	 * `custom_css` and `unique_id`) under `atts` (see class-page-builder-item.php).
	 * We read from `atts` and fall back to the node top level for any
	 * flattened / legacy shape.
	 *
	 * @param array $nodes Builder items (decoded JSON).
	 * @param array $parts Accumulator passed by reference.
	 */
	function unysonplus_collect_element_css( $nodes, &$parts ) {
		if ( ! is_array( $nodes ) || ! function_exists( 'sc_element_scope_class' ) ) {
			return;
		}
		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) ) { continue; }

			$vals = ( isset( $node['atts'] ) && is_array( $node['atts'] ) ) ? $node['atts'] : $node;

			if ( ! empty( $vals['custom_css'] ) && ! empty( $vals['unique_id'] ) ) {
				$scope = sc_element_scope_class( $vals );
				$raw   = unysonplus_scrub_element_css( (string) $vals['custom_css'] );
				if ( $scope !== '' && trim( $raw ) !== '' ) {
					// `selector` (standalone keyword) -> this element's scope class.
					$parts[] = preg_replace( '/\bselector\b/', '.' . $scope, $raw );
				}
			}

			if ( ! empty( $node['_items'] ) && is_array( $node['_items'] ) ) {
				unysonplus_collect_element_css( $node['_items'], $parts );
			}
		}
	}
endif;

if ( ! function_exists( 'unysonplus_build_page_css_string' ) ) :
	/**
	 * Build the consolidated per-page CSS body (no <style> tag).
	 *
	 * @param int  $post_id
	 * @param bool $pretty  When true, newline-separate the blocks. Defaults to WP_DEBUG.
	 * @return string CSS body, or '' when the page has no dynamic CSS.
	 */
	function unysonplus_build_page_css_string( $post_id, $pretty = null ) {
		if ( $pretty === null ) {
			$pretty = defined( 'WP_DEBUG' ) && WP_DEBUG;
		}
		$post_id = (int) $post_id;
		$glue    = $pretty ? "\n" : '';
		$parts   = array();

		// 1. Per-element Custom CSS from the builder JSON.
		if ( $post_id && function_exists( 'fw_get_db_post_option' ) ) {
			$builder = fw_get_db_post_option( $post_id, 'page-builder' );
			if ( is_array( $builder ) && ! empty( $builder['builder_active'] ) && ! empty( $builder['json'] ) ) {
				$items = json_decode( $builder['json'], true );
				if ( is_array( $items ) ) {
					unysonplus_collect_element_css( $items, $parts );
				}
			}
		}

		$css = implode( $glue, $parts );

		// 2. Page-level CSS contributed by the theme (page bg + page_custom_css).
		$page_extra = trim( (string) apply_filters( 'unysonplus_page_css', '', $post_id ) );
		if ( $page_extra !== '' ) {
			$css .= ( $css !== '' ? $glue : '' ) . $page_extra;
		}

		return $css;
	}
endif;

if ( ! function_exists( 'unysonplus_ensure_page_css_file' ) ) :
	/**
	 * Ensure `uploads/unysonplus/page-{id}-{hash}.css` exists for the current
	 * page CSS. Hash is taken over the built string so the URL changes whenever
	 * the page's dynamic CSS does (auto cache-bust). Stale generations for the
	 * same post are purged best-effort.
	 *
	 * @param int $post_id
	 * @return array|false ['url','path','hash'] or false to fall back to inline.
	 */
	function unysonplus_ensure_page_css_file( $post_id ) {
		$css = unysonplus_build_page_css_string( $post_id );
		if ( $css === '' ) {
			return false;
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$hash = substr( md5( $css ), 0, 12 );
		$dir  = trailingslashit( $upload['basedir'] ) . 'unysonplus';
		$file = 'page-' . (int) $post_id . '-' . $hash . '.css';
		$path = $dir . '/' . $file;
		$url  = trailingslashit( $upload['baseurl'] ) . 'unysonplus/' . $file;

		if ( file_exists( $path ) ) {
			return array( 'url' => $url, 'path' => $path, 'hash' => $hash );
		}

		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		$header = "/* unysonplus page {$post_id} dynamic CSS - auto-generated, do not edit. hash={$hash} */\n";

		// Self-heal: drop other page-{id}-*.css generations for this post.
		$old_files = glob( $dir . '/page-' . (int) $post_id . '-*.css' );
		if ( is_array( $old_files ) ) {
			foreach ( $old_files as $old ) {
				if ( $old !== $path ) { @unlink( $old ); }
			}
		}

		if ( @file_put_contents( $path, $header . $css, LOCK_EX ) === false ) {
			return false;
		}

		return array( 'url' => $url, 'path' => $path, 'hash' => $hash );
	}
endif;

if ( ! function_exists( 'unysonplus_enqueue_page_css' ) ) :
	/**
	 * Enqueue the generated per-page CSS file under `unysonplus-dynamic`.
	 * Priority 36 keeps it right after presets (35), so it wins source order
	 * over them and the Asset Optimizer absorbs the handle at its later pass.
	 */
	function unysonplus_enqueue_page_css() {
		if ( is_admin() || ! is_singular() ) { return; }
		$post_id = get_queried_object_id();
		if ( ! $post_id ) { return; }

		$file = unysonplus_ensure_page_css_file( $post_id );
		if ( $file === false ) { return; }

		// Depend on the preset stylesheet ONLY when it's actually enqueued — in
		// bare mode (Styling Presets off) `unysonplus-presets` isn't registered, so
		// listing it as a dependency would trigger a "dependencies not registered"
		// notice. The dependency is purely for source-order; harmless to drop here.
		$deps = ( function_exists( 'unysonplus_styling_presets_enabled' ) && ! unysonplus_styling_presets_enabled() )
			? array()
			: array( 'unysonplus-presets' );

		// null $ver: hash is already in the filename.
		wp_enqueue_style( 'unysonplus-dynamic', $file['url'], $deps, null );
	}
endif;

if ( ! function_exists( 'unysonplus_inline_page_css_fallback' ) ) :
	/**
	 * Inline <style id="unysonplus-dynamic-css"> fallback, fired only when the
	 * file-based enqueue didn't take (read-only uploads dir, etc.).
	 */
	function unysonplus_inline_page_css_fallback() {
		if ( is_admin() || ! is_singular() ) { return; }
		if ( wp_style_is( 'unysonplus-dynamic', 'enqueued' ) ) { return; }

		$post_id = get_queried_object_id();
		if ( ! $post_id ) { return; }

		$css = unysonplus_build_page_css_string( $post_id );
		if ( $css === '' ) { return; }

		echo '<style id="unysonplus-dynamic-css">' . $css . '</style>';
	}
endif;

add_action( 'wp_enqueue_scripts', 'unysonplus_enqueue_page_css', 36 );
add_action( 'wp_head',           'unysonplus_inline_page_css_fallback', 100 );
