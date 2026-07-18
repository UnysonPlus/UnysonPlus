<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Background Pattern — cleanup / scope transform.
 *
 * Turns a pasted CSS/HTML pattern into a safe, reusable, collision-free unit keyed to a
 * single `.pattern-{slug}` wrapper, WITHOUT the author having to rename anything:
 *
 *   1. Every CSS selector is prefixed with `.pattern-{slug}` (so `.press .sheet` becomes
 *      `.pattern-{slug} .press .sheet`), and page-global selectors (`html` / `body` /
 *      `:root`) are rewritten to the wrapper so a pattern can never restyle the whole page.
 *   2. `@keyframes NAME` is namespaced to `{slug}-NAME` (and every `animation` /
 *      `animation-name` reference rewritten) so two patterns can't collide on a `fall` /
 *      `print` / `pulse-stream` name.
 *   3. Any SVG-filter id referenced by `url(#id)` is namespaced in BOTH the CSS and the
 *      HTML (`<filter id="id">` → `id="{ns}-id"`), so multiple filter patterns coexist.
 *   4. `@media` / `@supports` blocks are preserved, their inner selectors scoped; `@import`
 *      is dropped (never pull a remote stylesheet from a pasted pattern).
 *
 * Deterministic + dependency-free (a small brace-aware tokenizer, not a full CSS parser) —
 * built for the flat selector + simple at-rule shape real-world CSS/HTML patterns use.
 *
 * Loaded by ../presets.php. The css-tokens generator + the Section/Body render layer
 * consume these (wired in a later step).
 */

if ( ! function_exists( 'unysonplus_pattern_detect_root_class' ) ) :
	/**
	 * The outermost element's first CSS class in a pasted HTML snippet — used as the
	 * pattern's "root class" when the author didn't name it. Returns '' if none.
	 *
	 * @param string $html
	 * @return string
	 */
	function unysonplus_pattern_detect_root_class( $html ) {
		if ( ! is_string( $html ) || $html === '' ) { return ''; }
		// First opening tag that carries a class attribute.
		if ( preg_match( '/<([a-zA-Z][\w-]*)\b[^>]*?\bclass\s*=\s*("|\')(.*?)\2/s', $html, $m ) ) {
			$classes = preg_split( '/\s+/', trim( $m[3] ) );
			foreach ( $classes as $c ) {
				$c = trim( $c );
				if ( $c !== '' ) { return $c; }
			}
		}
		return '';
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_namespace' ) ) :
	/** The per-pattern namespace token (keyframes + filter ids), e.g. `pattern-neon`. */
	function unysonplus_pattern_namespace( $slug ) {
		return 'pattern-' . preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $slug ) );
	}
endif;

if ( ! function_exists( '_upw_pat_split_blocks' ) ) :
	/**
	 * Brace-aware split of a CSS string into top-level blocks. Returns a list of
	 * array( 'sel' => <selector-or-at-rule-prelude>, 'body' => <inside the braces> ).
	 * Text with no following `{` (stray) is ignored. Comments are stripped first.
	 */
	function _upw_pat_split_blocks( $css ) {
		$css    = preg_replace( '#/\*.*?\*/#s', '', (string) $css ); // strip comments
		$blocks = array();
		$len    = strlen( $css );
		$i      = 0;
		$buf    = '';
		while ( $i < $len ) {
			$ch = $css[ $i ];
			if ( $ch === '{' ) {
				$depth = 1;
				$j     = $i + 1;
				$body  = '';
				while ( $j < $len && $depth > 0 ) {
					$cj = $css[ $j ];
					if ( $cj === '{' ) { $depth++; } elseif ( $cj === '}' ) {
						$depth--;
						if ( $depth === 0 ) { break; }
					}
					$body .= $cj;
					$j++;
				}
				$blocks[] = array( 'sel' => trim( $buf ), 'body' => $body );
				$buf      = '';
				$i        = $j + 1;
			} else {
				$buf .= $ch;
				$i++;
			}
		}
		return $blocks;
	}
endif;

if ( ! function_exists( '_upw_pat_scope_selector_list' ) ) :
	/**
	 * Prefix each comma-separated selector with `$prefix` (`.pattern-{slug}`), rewriting the
	 * page-global selectors (html / body / :root) to the wrapper and the bare universal
	 * selector to a descendant. Commas inside `()` (e.g. `:nth-child(2n+1)`) are respected.
	 */
	function _upw_pat_scope_selector_list( $sel, $prefix ) {
		$parts = array();
		$depth = 0;
		$cur   = '';
		$len   = strlen( $sel );
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $sel[ $i ];
			if ( $c === '(' ) { $depth++; } elseif ( $c === ')' ) { $depth--; }
			if ( $c === ',' && $depth <= 0 ) {
				$parts[] = $cur;
				$cur     = '';
			} else {
				$cur .= $c;
			}
		}
		$parts[] = $cur;

		$out = array();
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( $p === '' ) { continue; }
			// Page-global → the wrapper itself (a pattern must not restyle the whole page).
			$p2 = preg_replace( '/^(?:html|body|:root)\b/i', $prefix, $p, 1, $cnt );
			if ( $cnt ) {
				$out[] = trim( $p2 );
				continue;
			}
			if ( $p === '*' ) {
				$out[] = $prefix . ' *';
				continue;
			}
			$out[] = $prefix . ' ' . $p;
		}
		return implode( ', ', $out );
	}
endif;

if ( ! function_exists( 'unysonplus_pattern_scope' ) ) :
	/**
	 * Scope a pattern's HTML + CSS to `.pattern-{slug}`. Returns
	 * array( 'html' => <html with filter ids namespaced>, 'css' => <scoped css> ).
	 *
	 * @param string $html
	 * @param string $css
	 * @param string $slug  the pattern's css slug (see unysonplus_pattern_preset_slug_map()).
	 * @return array
	 */
	function unysonplus_pattern_scope( $html, $css, $slug ) {
		$html   = (string) $html;
		$css    = (string) $css;
		$slug   = preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $slug ) );
		$prefix = '.pattern-' . $slug;
		$ns     = unysonplus_pattern_namespace( $slug );

		// --- 1. Collect keyframe names + filter ids referenced in the CSS. ---
		$kf_names = array();
		if ( preg_match_all( '/@(?:-webkit-)?keyframes\s+([A-Za-z_][\w-]*)/', $css, $mk ) ) {
			$kf_names = array_unique( $mk[1] );
		}
		$filter_ids = array();
		if ( preg_match_all( '/url\(\s*#([A-Za-z_][\w:-]*)\s*\)/', $css, $mf ) ) {
			$filter_ids = array_unique( $mf[1] );
		}

		// --- 2. Namespace filter ids in the CSS (url(#id) → url(#ns-id)). ---
		foreach ( $filter_ids as $fid ) {
			$css = preg_replace(
				'/url\(\s*#' . preg_quote( $fid, '/' ) . '\s*\)/',
				'url(#' . $ns . '-' . $fid . ')',
				$css
			);
		}

		// --- 3. Tokenize + scope block-by-block. ---
		$css = _upw_pat_scope_css_blocks( $css, $prefix, $ns, $kf_names );

		// --- 4. Rewrite keyframe REFERENCES to match the namespaced headers — but ONLY inside
		//        `animation` / `animation-name` declaration values, so we never touch the
		//        already-renamed `@keyframes` header (avoids double-namespacing) or an
		//        unrelated token elsewhere (e.g. an `@media print` prelude). ---
		if ( ! empty( $kf_names ) ) {
			$css = preg_replace_callback(
				'/(animation(?:-name)?\s*:\s*)([^;{}]*)/i',
				function ( $m ) use ( $kf_names, $ns ) {
					$val = $m[2];
					foreach ( $kf_names as $name ) {
						$val = preg_replace( '/\b' . preg_quote( $name, '/' ) . '\b/', $ns . '-' . $name, $val );
					}
					return $m[1] . $val;
				},
				$css
			);
		}

		// --- 5. Mirror the filter-id namespacing into the HTML (<filter id="id"> + url(#id)). ---
		foreach ( $filter_ids as $fid ) {
			$html = preg_replace(
				'/\bid\s*=\s*("|\')' . preg_quote( $fid, '/' ) . '\1/',
				'id=$1' . $ns . '-' . $fid . '$1',
				$html
			);
			$html = preg_replace(
				'/url\(\s*#' . preg_quote( $fid, '/' ) . '\s*\)/',
				'url(#' . $ns . '-' . $fid . ')',
				$html
			);
		}

		return array( 'html' => $html, 'css' => $css );
	}
endif;

if ( ! function_exists( '_upw_pat_scope_css_blocks' ) ) :
	/**
	 * Recursively scope the blocks of a CSS string. Normal rules get their selector list
	 * prefixed; @keyframes get their name namespaced (body untouched); @media / @supports
	 * keep their prelude and have their inner rules scoped; @font-face is kept as-is;
	 * @import is dropped.
	 *
	 * @param string   $css
	 * @param string   $prefix   `.pattern-{slug}`
	 * @param string   $ns       `pattern-{slug}`
	 * @param string[] $kf_names collected keyframe names (headers namespaced here)
	 * @return string
	 */
	function _upw_pat_scope_css_blocks( $css, $prefix, $ns, $kf_names ) {
		$out = '';
		foreach ( _upw_pat_split_blocks( $css ) as $b ) {
			$sel  = $b['sel'];
			$body = $b['body'];

			if ( $sel === '' ) { continue; }

			// @keyframes — namespace the name, keep the body (0%/50%/100% frames) verbatim.
			if ( preg_match( '/^@(-webkit-)?keyframes\s+([A-Za-z_][\w-]*)/', $sel, $m ) ) {
				$at   = '@' . ( $m[1] ? $m[1] : '' ) . 'keyframes';
				$out .= $at . ' ' . $ns . '-' . $m[2] . " {" . $body . "}\n";
				continue;
			}

			// @media / @supports — keep the prelude, scope the inner rules.
			if ( preg_match( '/^@(media|supports|container)\b/i', $sel ) ) {
				$out .= $sel . " {\n" . _upw_pat_scope_css_blocks( $body, $prefix, $ns, $kf_names ) . "}\n";
				continue;
			}

			// @font-face / @page — no selector to scope; keep as-is (global by nature).
			if ( preg_match( '/^@(font-face|page)\b/i', $sel ) ) {
				$out .= $sel . " {" . $body . "}\n";
				continue;
			}

			// @import / other unknown at-rules — drop (never fetch a remote sheet from a paste).
			if ( $sel[0] === '@' ) {
				continue;
			}

			// Normal style rule.
			$out .= _upw_pat_scope_selector_list( $sel, $prefix ) . " {" . $body . "}\n";
		}
		return $out;
	}
endif;
