<?php
/**
 * Regression guard: a retired option-type ID must stay RESOLVABLE, not just removed.
 *
 * The icon option type was consolidated into one engine and the old `icon-v2` / `icon-v3` ids were retired.
 * Inside this plugin that was safe — nothing here declares them any more. Outside it, it was not.
 *
 * Downloaded extensions live in `framework-customizations/`, which a plugin update deliberately NEVER
 * touches. A site that installed one before the consolidation therefore keeps, for ever, a copy that still
 * declares `'type' => 'icon-v2'`. Against a core that no longer registers that id Unyson resolves it to
 * FW_Option_Type_Undefined, prints "Undefined option type: icon-v2" at the top of every admin screen, and
 * the option does not render. Child themes and third-party code that used the documented id break the same
 * way. It was reported from a clean install of the release zip, which is how little it takes to hit.
 *
 * The lesson generalises past icons: retiring a PUBLIC option-type id is a breaking change for code this
 * plugin does not ship and cannot update, so the id has to keep resolving even when its folder is gone.
 *
 * Run:
 *   php D:/xampp/wp-cli.phar --path=D:/xampp/htdocs eval-file \
 *       "D:/xampp/htdocs/wp-content/plugins/unysonplus/framework/tests/retired-option-type-ids-test.php"
 *
 * Exit code 0 = all PASS, 1 = at least one FAIL.
 */

if ( ! function_exists( 'fw' ) || ! fw()->backend ) {
	fwrite( STDERR, "FAIL: Unyson framework not loaded\n" );
	exit( 1 );
}

$fails = 0;
$ok    = function ( $cond, $msg ) use ( &$fails ) {
	if ( $cond ) { echo "  \xe2\x9c\x93 $msg\n"; } else { $fails++; echo "  \xe2\x9c\x97 FAIL: $msg\n"; }
};

$resolves = function ( $id ) {
	$ot = fw()->backend->option_type( $id );
	if ( ! is_object( $ot ) ) { return false; }
	return 'FW_Option_Type_Undefined' !== get_class( $ot );
};

echo "\n== The one icon type is registered\n";

$ok( $resolves( 'icon' ), '`icon` resolves' );

echo "\n== ...and the retired ids still resolve, for code we cannot update\n";

foreach ( array( 'icon-v2', 'icon-v3' ) as $id ) {
	$ot  = fw()->backend->option_type( $id );
	$cls = is_object( $ot ) ? get_class( $ot ) : 'NULL';
	$ok( $resolves( $id ), "`$id` resolves instead of falling through to Undefined (got $cls)" );
	$ok( $ot instanceof FW_Option_Type_Icon,
		"...through the SAME engine, so it renders and stores the identical value shape" );
	$ok( is_object( $ot ) && $id === $ot->get_type(),
		"...while still reporting its own id, which is what the stored option refers to" );
}

echo "\n== An option declared the old way actually renders\n";

// The symptom users see is the message in the admin page, so assert on rendered output, not just the lookup.
$html = (string) fw()->backend->render_options(
	array( 'legacy_icon' => array( 'type' => 'icon-v2', 'label' => 'Icon' ) ),
	array()
);
$ok( false === stripos( $html, 'Undefined option type' ),
	'rendering a legacy `icon-v2` option emits no "Undefined option type" notice' );
$ok( '' !== trim( $html ), '...and produces actual markup rather than nothing' );

echo "\n== NEGATIVE: a genuinely unknown id is still reported\n";

// The aliases must not turn into a catch-all that hides a real typo.
$ok( ! $resolves( 'icon-v9-not-a-real-type' ),
	'NEGATIVE: an id that never existed still resolves to Undefined, so typos stay visible' );

echo $fails ? "\n\xe2\x9c\x97 $fails FAILED\n" : "\n\xe2\x9c\x93 ALL PASS - retired ids keep working for code we never update\n";
exit( $fails ? 1 : 0 );
