<?php
/**
 * Shared Hover Animations library — contract test.
 *
 * ONE library (Theme Settings → Components → Hover Animations, key `hover_animations`)
 * feeds both the Button shortcode's Hover Animation picker and a Box Preset's Hover
 * Animation field. Proves, against the REAL theme-scoped preset store:
 *   - the library reads the shared key (and the legacy `button_animations` key as fallback),
 *   - the picker choices include a custom entry as `btnfx-c-{slug}`,
 *   - a Box Preset's `hover_animation` persists and is emitted onto `.boxp-{slug}` — a
 *     built-in cloned from hover-fx.css (rules + its @keyframes + a reduced-motion guard),
 *     a custom one re-rendered with {{SELECTOR}} = the box,
 *   - the button class (`.btnfx-c-{slug}`) is still emitted, and {{BTN}} still resolves.
 * Snapshots the three keys first and restores them in `finally`.
 *
 * Run:
 *   php D:/xampp/wp-cli.phar --path=D:/xampp/htdocs eval-file \
 *       "D:/xampp/htdocs/wp-content/plugins/unysonplus/framework/tests/hover-animations-test.php"
 * Exit 0 = all PASS, 1 = a FAIL.
 */
if ( ! function_exists( 'unysonplus_preset_store_get' ) || ! function_exists( 'unysonplus_build_presets_css_string' ) ) {
	fwrite( STDERR, "FAIL: preset store / css-tokens not loaded (run inside a WP install with the plugin active)\n" );
	exit( 1 );
}
$pass = 0; $fail = 0;
$ok = function ( $label, $cond, $got = '' ) use ( &$pass, &$fail ) {
	if ( $cond ) { $pass++; echo "  PASS  $label\n"; }
	else { $fail++; echo "  FAIL  $label" . ( $got !== '' ? "  (got: " . ( is_scalar( $got ) ? $got : substr( wp_json_encode( $got ), 0, 200 ) ) . ")" : '' ) . "\n"; }
};
$keys = array( 'border_presets', 'hover_animations', 'button_animations' );
$snap = array();
foreach ( $keys as $k ) { $snap[ $k ] = unysonplus_preset_store_get( $k, '__unset__' ); }
try {
	$bps = array_values( unysonplus_get_border_presets() );
	$map = unysonplus_border_preset_slug_map();
	$s0  = $map[ $bps[0]['id'] ] ?? ''; $s1 = $map[ $bps[1]['id'] ] ?? '';
	$bps[0]['hover_animation'] = 'btnfx-lift';
	$bps[1]['hover_animation'] = 'btnfx-c-pulse-ring';
	unysonplus_preset_store_set( 'border_presets', $bps );
	unysonplus_preset_store_set( 'hover_animations', array( array( 'id' => '0000020001', 'name' => 'Pulse Ring', 'css' => '{{SELECTOR}}:hover { animation: {{ANIM}} 1.1s ease infinite; } @keyframes {{ANIM}} { 0% { box-shadow: 0 0 0 0 rgba(0,0,0,.35); } 100% { box-shadow: 0 0 0 12px rgba(0,0,0,0); } }' ) ) );
	unysonplus_preset_store_set( 'button_animations', array() );

	echo "\n[1] Shared library + box preset emission\n";
	$lib = unysonplus_get_custom_hover_animations();
	$ok( "library reads the shared `hover_animations` key", count( $lib ) === 1 && $lib[0]['name'] === 'Pulse Ring', count( $lib ) );
	$ok( "picker choices include the custom entry as btnfx-c-pulse-ring", isset( sc_get_hover_animation_choices()['btnfx-c-pulse-ring'] ) );
	$re  = array_values( unysonplus_get_border_presets() );
	$ok( "box preset hover_animation persisted (btnfx-lift)", ( $re[0]['hover_animation'] ?? '' ) === 'btnfx-lift', $re[0]['hover_animation'] ?? '' );
	$css = unysonplus_build_presets_css_string( false );
	$ok( "'$s0' (Lift) → .boxp-$s0:hover carries the lift transform", (bool) preg_match( '/\.boxp-' . preg_quote( $s0, '/' ) . ':hover[^{]*\{[^}]*translateY\(-4px\)/', $css ) );
	$ok( "'$s0' (Lift) → the transition base is cloned too", (bool) preg_match( '/\.boxp-' . preg_quote( $s0, '/' ) . '\{[^}]*transition: transform \.25s/', $css ) );
	$ok( "'$s1' (custom) → .boxp-$s1:hover animates the custom keyframes", (bool) preg_match( '/\.boxp-' . preg_quote( $s1, '/' ) . ':hover\s*\{\s*animation: btnfxc-pulse-ring/', $css ) );
	$ok( "custom keyframes emitted", strpos( $css, '@keyframes btnfxc-pulse-ring' ) !== false );
	$ok( "button class for the custom entry still emitted", strpos( $css, '.btnfx-c-pulse-ring:hover' ) !== false );
	$ok( "no unresolved {{…}} tokens", strpos( $css, '{{' ) === false );
	$ok( "reduced-motion guard for the cloned box motion", strpos( $css, "@media (prefers-reduced-motion:reduce){.boxp-$s0,.boxp-$s0:hover" ) !== false );
	$ok( "cloner: unknown effect → empty", unysonplus_hover_fx_css_for( 'btnfx-nope', '.boxp-x' ) === '' );
	// The admin-head row-preview emitter must resolve EVERY token: an unreplaced {{SELECTOR}} once split a rule
	// into a bare `:hover{animation…}` — a universal rule that hover-animated the whole wp-admin.
	if ( function_exists( 'sc_emit_button_hover_animation_preview_css' ) ) {
		ob_start(); sc_emit_button_hover_animation_preview_css(); $prev = (string) ob_get_clean();
		$ok( "admin preview css: no unresolved {{…}} tokens", strpos( $prev, '{{' ) === false, substr( $prev, 0, 160 ) );
		$ok( "admin preview css: no BARE `:hover{` rule (would apply to every element)", ! preg_match( '/(^|[;}>])\s*:hover\s*\{/', preg_replace( '#<\/?style[^>]*>#', '', $prev ) ), substr( $prev, 0, 160 ) );
		$ok( "admin preview css: the row preview class is targeted", strpos( $prev, '.btnfx-preview-0000020001:hover' ) !== false, substr( $prev, 0, 160 ) );
	}
	$ok( "cloner: a keyframe effect (Heartbeat) brings its @keyframes, no stray .btnfx-", ( $h = unysonplus_hover_fx_css_for( 'btnfx-heartbeat', '.boxp-x' ) ) && strpos( $h, '@keyframes btnfx-heartbeat' ) !== false && strpos( $h, '.btnfx-' ) === false );

	echo "\n[2] Legacy fallback\n";
	unysonplus_preset_store_set( 'hover_animations', array() );
	unysonplus_preset_store_set( 'button_animations', array( array( 'id' => '0000020009', 'name' => 'Old Swing', 'css' => '{{BTN}}:hover { transform: rotate(3deg); }' ) ) );
	$lib2 = unysonplus_get_custom_hover_animations();
	$ok( "legacy `button_animations` read when the shared key is empty", count( $lib2 ) === 1 && $lib2[0]['name'] === 'Old Swing', array_map( function ( $a ) { return $a['name']; }, $lib2 ) );
	$css2 = unysonplus_build_presets_css_string( false );
	$ok( "legacy {{BTN}} token resolves for the button class", strpos( $css2, '.btnfx-c-old-swing:hover' ) !== false );
} finally {
	foreach ( $keys as $k ) { unysonplus_preset_store_set( $k, ( $snap[ $k ] === '__unset__' ) ? array() : $snap[ $k ] ); }
	echo "\n(restored the preset store)\n";
}
echo "\n========================================\nHOVER ANIMATIONS RESULT: " . ( $fail === 0 ? 'PASS' : 'FAIL' ) . "   ($pass passed, $fail failed)\n========================================\n";
exit( $fail === 0 ? 0 : 1 );
