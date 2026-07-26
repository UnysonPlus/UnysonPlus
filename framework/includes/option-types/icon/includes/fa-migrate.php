<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Font Awesome 4 → Font Awesome 6 class migration.
 *
 * FA4 put every icon in one font under the bare `fa` root (`fa fa-twitter`).
 * FA6 splits the library into styles with their own class — solid (`fa-solid`
 * / `fas`), regular (`fa-regular` / `far`) and brands (`fa-brands` / `fab`) —
 * and renamed many icons (`fa-home` → `fa-house`, `fa-close` → `fa-xmark`).
 *
 * fw_fa4_to_fa6() rewrites a legacy class string to its FA6 equivalent using
 * the bundled data/fa4-migrate.json map (FA's own shims + the brands list).
 * Anything already FA6 (or not FA-shaped) is returned untouched, sizing /
 * animation modifiers (fa-2x, fa-spin, fa-fw, …) are preserved, and unknown
 * names default to solid. It is a best-effort cleanup — the bundled FA6 CSS
 * also ships the official v4-shims, so any class this misses still renders.
 */
if ( ! function_exists( 'fw_fa4_to_fa6' ) ) :
	function fw_fa4_to_fa6( $class ) {
		$class = trim( (string) $class );
		if ( $class === '' ) { return $class; }

		$tokens = preg_split( '/\s+/', $class );

		// Already an FA6 (or FA5+) class — it names a style — so leave it alone.
		$fa6_styles = array(
			'fas', 'far', 'fab', 'fal', 'fad', 'fat', 'fass', 'fasr', 'fasl',
			'fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin',
			'fa-duotone', 'fa-sharp',
		);
		foreach ( $tokens as $t ) {
			if ( in_array( $t, $fa6_styles, true ) ) { return $class; }
		}

		// Only touch genuine FA4 classes, which carry the bare `fa` root.
		if ( ! in_array( 'fa', $tokens, true ) ) { return $class; }

		static $map = null;
		if ( $map === null ) {
			$file = dirname( __FILE__ ) . '/../data/fa4-migrate.json';
			$map  = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
			if ( ! is_array( $map ) ) { $map = array(); }
		}

		// FA4 sizing / layout / animation modifiers — kept, not treated as the icon.
		static $modifiers = array(
			'lg' => 1, 'sm' => 1, 'xs' => 1, 'fw' => 1, 'ul' => 1, 'li' => 1,
			'border' => 1, 'pull-left' => 1, 'pull-right' => 1, 'spin' => 1,
			'pulse' => 1, 'rotate-90' => 1, 'rotate-180' => 1, 'rotate-270' => 1,
			'flip-horizontal' => 1, 'flip-vertical' => 1, 'stack' => 1,
			'stack-1x' => 1, 'stack-2x' => 1, 'inverse' => 1,
			'1x' => 1, '2x' => 1, '3x' => 1, '4x' => 1, '5x' => 1,
			'6x' => 1, '7x' => 1, '8x' => 1, '9x' => 1, '10x' => 1,
		);

		$icon_name = '';
		$rest      = array();
		foreach ( $tokens as $t ) {
			if ( $t === 'fa' ) { continue; } // drop the FA4 root
			if ( $icon_name === '' && strpos( $t, 'fa-' ) === 0 ) {
				$suffix = substr( $t, 3 );
				if ( ! isset( $modifiers[ $suffix ] ) ) { $icon_name = $suffix; continue; }
			}
			$rest[] = $t; // modifiers (and anything unexpected) survive unchanged
		}

		if ( $icon_name === '' ) { return $class; }

		$fa6 = isset( $map[ $icon_name ] ) ? $map[ $icon_name ] : ( 'fas fa-' . $icon_name );

		return trim( $fa6 . ( $rest ? ' ' . implode( ' ', $rest ) : '' ) );
	}
endif;
