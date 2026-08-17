<?php
/**
 * Regression guard for the framework's silent contracts.
 *
 * ## Why these three things, and not "coverage"
 *
 * Chasing a coverage percentage across 230k lines of a mature plugin is not a good use
 * of anyone's time and will not get finished. This file targets the contracts that fail
 * SILENTLY — where a mistake does not throw, does not warn, and shows up later as
 * corrupted saved data or a page that renders wrong:
 *
 *  1. fw_akg() / fw_aks() — nested path access, used everywhere for option values.
 *     A change in how a missing key resolves silently swaps real values for defaults.
 *
 *  2. Option value shapes — _get_value_from_input() decides what is written to the
 *     database. Change a shape and every previously saved value becomes unreadable,
 *     with no error at the point of failure.
 *
 *  3. The page-builder JSON round trip — json_encode( rootItems ) IS the storage format
 *     for every page ever built. It has to survive load → mutate → save byte-identically
 *     for content that was not edited.
 *
 * The Backbone removal needed 161 hand-written parity assertions to land safely, and
 * three of the bugs it surfaced were invisible in the code being replaced. Those
 * assertions were written under pressure and then thrown away. This is the small,
 * permanent version of that.
 *
 * ## Run
 *
 *   php D:/xampp/wp-cli.phar --path=D:/xampp/htdocs/core-upgrade eval-file \
 *       "D:/xampp/htdocs/core-upgrade/wp-content/plugins/unysonplus/framework/tests/core-contracts-test.php"
 *
 * Exit code 0 = all PASS, 1 = at least one FAIL (CI-friendly).
 */

if ( ! function_exists( 'fw_akg' ) ) {
	fwrite( STDERR, "FAIL: framework not loaded (run inside a WP install with the plugin active)\n" );
	exit( 1 );
}

/* --------------------------------------------------------------------- *
 * Tiny assertion harness (same shape as the site-converter golden tests)
 * --------------------------------------------------------------------- */
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function ct( $label, $cond, $got = null ) {
	if ( $cond ) {
		$GLOBALS['__pass']++;
		echo "  PASS  $label\n";
	} else {
		$GLOBALS['__fail']++;
		echo "  FAIL  $label" . ( null !== $got ? "  (got: " . ( is_scalar( $got ) ? var_export( $got, true ) : wp_json_encode( $got ) ) . ")" : "" ) . "\n";
	}
}

function ct_eq( $label, $expected, $actual ) {
	ct( $label, $expected === $actual, $actual );
}

/* --------------------------------------------------------------------- *
 * 1. fw_akg / fw_aks — nested path access
 * --------------------------------------------------------------------- */
echo "\n== fw_akg / fw_aks ==\n";

$src = array(
	'a' => array( 'b' => array( 'c' => 'deep' ) ),
	'zero'  => 0,
	'empty' => '',
	'null'  => null,
	'false' => false,
);

ct_eq( 'reads a nested path', 'deep', fw_akg( 'a/b/c', $src ) );
ct_eq( 'missing path returns the default', 'fallback', fw_akg( 'a/b/nope', $src, 'fallback' ) );
ct_eq( 'missing path defaults to null', null, fw_akg( 'no/such/path', $src ) );
ct_eq( 'accepts an array of keys', 'deep', fw_akg( array( 'a', 'b', 'c' ), $src ) );
ct_eq( 'honours a custom delimiter', 'deep', fw_akg( 'a.b.c', $src, null, '.' ) );

/**
 * Falsy values are the whole reason this helper exists rather than a chain of isset().
 * If any of these start returning the default, every option whose value is 0, '' or
 * false silently reverts to its default on read — the exact failure that produces
 * "my setting will not stay off".
 */
ct_eq( 'integer 0 is a VALUE, not missing', 0, fw_akg( 'zero', $src, 'DEFAULT' ) );
ct_eq( 'empty string is a VALUE, not missing', '', fw_akg( 'empty', $src, 'DEFAULT' ) );
ct_eq( 'false is a VALUE, not missing', false, fw_akg( 'false', $src, 'DEFAULT' ) );
ct_eq( 'explicit null returns null, not the default', null, fw_akg( 'null', $src, 'DEFAULT' ) );

// Objects take the same path syntax as arrays.
$obj = json_decode( '{"x":{"y":"objdeep"}}' );
ct_eq( 'reads through an object', 'objdeep', fw_akg( 'x/y', $obj ) );

// fw_aks writes by path, creating intermediates.
$dst = array();
fw_aks( 'p/q/r', 'written', $dst );
ct_eq( 'fw_aks creates intermediate levels', 'written', fw_akg( 'p/q/r', $dst ) );
fw_aks( 'p/q/r', 'overwritten', $dst );
ct_eq( 'fw_aks overwrites in place', 'overwritten', fw_akg( 'p/q/r', $dst ) );
fw_aks( 'p/other', 'sibling', $dst );
ct_eq( 'fw_aks preserves siblings', 'overwritten', fw_akg( 'p/q/r', $dst ) );

// Round trip: what fw_aks writes, fw_akg reads back unchanged — including falsy values.
foreach ( array( 0, '', false, null, '0', 'text', array( 'nested' => 1 ) ) as $i => $val ) {
	$rt = array();
	fw_aks( 'round/trip', $val, $rt );
	ct( "fw_aks -> fw_akg round trip preserves value #$i", fw_akg( 'round/trip', $rt, '__MISSING__' ) === $val, fw_akg( 'round/trip', $rt, '__MISSING__' ) );
}

/* --------------------------------------------------------------------- *
 * 2. Option value shapes
 * --------------------------------------------------------------------- */
echo "\n== option value shapes ==\n";

/**
 * The contract is NOT "null input returns the declared default".
 *
 * That was the obvious assertion to write, and it is wrong — `switch` declares
 * 'value' => null while _get_value_from_input() deliberately resolves a null to the
 * LEFT choice, because a switch must be on or off and null is neither. Asserting the
 * naive version reports a passing option type as broken.
 *
 * The contract that actually holds for every type, and that matters, is STABILITY:
 * resolving an absent value must produce the same result every time. That is what runs
 * on a fresh install and for every option added to an existing site, and a type that
 * drifts here changes saved data on an unrelated save.
 */
$shape_types = array( 'text', 'textarea', 'switch', 'select', 'checkbox', 'radio' );

foreach ( $shape_types as $type ) {
	$ot = fw()->backend->option_type( $type );

	if ( ! $ot instanceof FW_Option_Type ) {
		ct( "option type '$type' is registered", false );
		continue;
	}

	$schema = array( 'probe' => array( 'type' => $type ) );

	$first  = fw_get_options_values_from_input( $schema, array() );
	$second = fw_get_options_values_from_input( $schema, array() );

	ct( "'$type': resolves an absent value", array_key_exists( 'probe', $first ), $first );
	ct_eq( "'$type': absent-value resolution is stable", $first['probe'] ?? '__ABSENT__', $second['probe'] ?? '__ABSENT__' );
}

/**
 * A switch specifically must land on one of its two declared choices — never null,
 * never a label. This is the assertion the naive version above was reaching for.
 */
$sw       = fw()->backend->option_type( 'switch' );
$sw_def   = $sw->get_defaults();
$resolved = fw_get_options_values_from_input( array( 'p' => array( 'type' => 'switch' ) ), array() );
ct(
	"'switch': absent value resolves to a declared choice, not null",
	in_array( $resolved['p'] ?? null, array( $sw_def['left-choice']['value'], $sw_def['right-choice']['value'] ), true ),
	$resolved['p'] ?? '__ABSENT__'
);

/**
 * The switch's wire format, pinned. The control submits STRINGS; the option type stores
 * booleans. Documented here because the asymmetry is invisible at the call site and is
 * the thing most likely to be "tidied" into a bug by someone normalising the two.
 */
$sw_schema = array( 'p' => array( 'type' => 'switch' ) );
foreach ( array( 'true' => true, 'false' => false ) as $wire => $stored ) {
	$got = fw_get_options_values_from_input( $sw_schema, array( 'p' => $wire ) );
	ct_eq( "'switch': wire '$wire' stores " . var_export( $stored, true ), $stored, $got['p'] ?? '__ABSENT__' );
}

/**
 * Idempotence: feeding a type's own output back in must not change it. A type that
 * transforms on every pass corrupts a value a little more each save — invisible in a
 * single test, obvious after a week of edits.
 *
 * Values here must be what the control actually SUBMITS, which for a switch is the
 * STRING 'true' / 'false' — not the boolean it decodes to, and not the 'Yes' / 'No'
 * labels. Feeding it a PHP boolean makes the option vanish from the result entirely
 * (invalid input is dropped rather than defaulted), which looks like a bug in the
 * option type and is really a bug in the test. Measured wire values:
 *
 *   'true'  -> true        'false' -> false
 *   true    -> dropped     '1'/'0' -> dropped
 */
/**
 * NOTE: `switch` is deliberately absent from this list, and that is a finding worth
 * keeping rather than a gap. Its wire format and its storage format differ — it accepts
 * the string 'true' and stores the boolean true — so its own output is not valid as its
 * own input, and feeding true back in drops the value.
 *
 * That is not a defect. The rendered control always resupplies 'true' on the next
 * submit, so the stored value is stable across the cycle that actually happens. But it
 * means idempotence is the wrong property to assert for it, and asserting it anyway
 * reports working code as broken. The switch's real contract — that an absent value
 * resolves to a declared choice, and that 'true'/'false' map correctly — is covered above.
 */
$idempotent_cases = array(
	array( 'text',     'hello' ),
	array( 'textarea', "line one\nline two" ),
	array( 'text',     '' ),
	array( 'text',     '0' ),
	array( 'text',     'quotes " and \' and backslash \\' ),
	array( 'textarea', 'unicode: em—dash · ✓ 中文' ),
);

foreach ( $idempotent_cases as $case ) {
	list( $type, $value ) = $case;

	$ot = fw()->backend->option_type( $type );
	if ( ! $ot instanceof FW_Option_Type ) { continue; }

	$schema = array( 'p' => array( 'type' => $type ) );
	$once   = fw_get_options_values_from_input( $schema, array( 'p' => $value ) );
	$twice  = fw_get_options_values_from_input( $schema, array( 'p' => $once['p'] ?? null ) );

	$label = "'$type' (" . var_export( $value, true ) . ')';
	ct( "$label: value survives one save", array_key_exists( 'p', $once ), $once );
	ct_eq( "$label: value is unchanged by a second save", $once['p'] ?? '__ABSENT__', $twice['p'] ?? '__ABSENT__' );
}

/* --------------------------------------------------------------------- *
 * 2b. React control layer — value-shape parity with PHP
 * --------------------------------------------------------------------- */
echo "\n== React control parity ==\n";

/**
 * The React controls in framework/static/js/controls/ are a SECOND renderer for the
 * same option schema. The whole arrangement only holds if a value saved from a block
 * inspector is indistinguishable from one saved through the PHP renderer.
 *
 * This cannot run the JSX. What it can do — and what actually protects the contract —
 * is pin the exact value each React control is documented to emit and assert PHP still
 * accepts it unchanged. If someone "tidies" an option type's input handling, this fails
 * here rather than silently rewriting values on a block-edited page.
 *
 * Update these cases whenever a React control's emitted shape changes.
 */
$radio_schema = array(
	'type'    => 'radio',
	'choices' => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
);

/**
 * multi-select's delimiter, built rather than written literally: the sequence
 * contains an asterisk-slash pair, which would close this docblock early if it
 * appeared verbatim anywhere above.
 */
if ( ! defined( 'MULTI_SELECT_DELIM' ) ) {
	define( 'MULTI_SELECT_DELIM', '/' . '*' . '/' );
}

/**
 * Build a complete `spacing` value. The React control always emits the whole
 * structure — the server rebuilds it from defaults on every save, so emitting it
 * whole is what keeps a React-saved value byte-identical to a builder-saved one.
 *
 * @param array $margin  Base margin slots.
 * @param array $padding Base padding slots.
 * @param array $md      md overrides, as array( 'margin' => …, 'padding' => … ).
 * @param array $lg      lg overrides, same shape.
 * @return array
 */
function fw_test_spacing( $margin = array(), $padding = array(), $md = array(), $lg = array() ) {
	$slots = function ( $a ) {
		return array_merge(
			array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
			is_array( $a ) ? $a : array()
		);
	};

	$device = function ( $d ) use ( $slots ) {
		return array(
			'margin'  => $slots( isset( $d['margin'] ) ? $d['margin'] : array() ),
			'padding' => $slots( isset( $d['padding'] ) ? $d['padding'] : array() ),
		);
	};

	return array(
		'margin'   => $slots( $margin ),
		'padding'  => $slots( $padding ),
		'advanced' => array( 'md' => $device( $md ), 'lg' => $device( $lg ) ),
	);
}

/**
 * `mode` narrows which sections the server will read. A padding edit submitted to
 * a margin-only option must be DROPPED, not stored — otherwise the React control
 * could offer edits that vanish on save with no feedback.
 */
$mode_in  = fw_test_spacing( array( 'top' => '3' ), array( 'top' => '4' ) );
$mode_out = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'spacing', 'mode' => 'margin' ) ),
	array( 'p' => $mode_in )
);

ct(
	'spacing: mode=margin keeps margin and drops padding',
	isset( $mode_out['p'] )
		&& '3' === $mode_out['p']['margin']['top']
		&& '' === $mode_out['p']['padding']['top'],
	isset( $mode_out['p'] ) ? $mode_out['p']['padding'] : '__ABSENT__'
);

$parity_cases = array(
	// label,                      schema,                                    react emits,     php must store
	array( 'textarea: plain',      array( 'type' => 'textarea' ),             'hello world',   'hello world' ),
	array( 'textarea: multiline',  array( 'type' => 'textarea' ),             "multi\nline",   "multi\nline" ),
	array( 'textarea: empty',      array( 'type' => 'textarea' ),             '',              '' ),
	array( 'radio: choice key',    $radio_schema,                             'right',         'right' ),
	array( 'radio: first choice',  $radio_schema,                             'left',          'left' ),
	// The checkbox emits a real boolean, not '1'/'yes' — _get_value_from_input casts
	// with (bool), so anything else would round-trip to a different stored value.
	array( 'checkbox: true',       array( 'type' => 'checkbox', 'text' => 'Show' ), true,  true ),
	array( 'checkbox: false',      array( 'type' => 'checkbox', 'text' => 'Show' ), false, false ),
	// The switch's React renderer round-trips the DECLARED choice values rather than
	// coercing to a boolean, which is what keeps custom choice pairs intact.
	array( 'switch: wire true',    array( 'type' => 'switch' ),               'true',          true ),
	array( 'switch: wire false',   array( 'type' => 'switch' ),               'false',         false ),
	array( 'text: plain',          array( 'type' => 'text' ),                 'hello',         'hello' ),

	/**
	 * Colour is HEX, never rgba(). The PHP validator accepts only 3/4/6/8-digit
	 * hex and silently falls back to the option default on anything else — so a
	 * control emitting `rgba(47,116,230,0.5)` would blank the user's colour with
	 * no error anywhere. That is not hypothetical: it is what the React
	 * color-picker did before its hex normaliser existed, and this case is what
	 * caught it. Alpha is expressed as 8-digit hex.
	 */
	array( 'color: 6-digit hex',   array( 'type' => 'color-picker' ),                          '#2f74e6',    '#2f74e6' ),
	array( 'color: 3-digit hex',   array( 'type' => 'color-picker' ),                          '#abc',       '#abc' ),
	array( 'color: empty',         array( 'type' => 'color-picker' ),                          '',           '' ),
	array( 'color: 8-digit alpha', array( 'type' => 'color-picker', 'alpha' => true ),         '#2f74e680',  '#2f74e680' ),

	// Slider stores a float — floatval() in the option type.
	array( 'slider: number',       array( 'type' => 'slider' ),                                65,           65.0 ),
	array( 'slider: zero',         array( 'type' => 'slider' ),                                0,            0.0 ),

	/**
	 * unit-input stores a { value, unit } pair where the NUMBER HALF IS A STRING,
	 * so '' (unset) stays distinguishable from '0'. Units may be declared as a
	 * sequential list or a value => label map; both must round-trip.
	 */
	array( 'unit-input: list units', array( 'type' => 'unit-input', 'units' => array( 'px', '%', 'rem' ) ),
		array( 'value' => '320', 'unit' => 'rem' ), array( 'value' => '320', 'unit' => 'rem' ) ),
	array( 'unit-input: map units',  array( 'type' => 'unit-input', 'units' => array( 'px' => 'PX', 'vw' => 'VW' ) ),
		array( 'value' => '50', 'unit' => 'vw' ),   array( 'value' => '50', 'unit' => 'vw' ) ),
	array( 'unit-input: blank value', array( 'type' => 'unit-input', 'units' => array( 'px', 'em' ) ),
		array( 'value' => '', 'unit' => 'px' ),     array( 'value' => '', 'unit' => 'px' ) ),

	/**
	 * multi-select's WIRE format is a '/*' . '/'-delimited string, while its STORED
	 * value is an array. _get_value_from_input() calls explode() on the input, so
	 * handing it a real array raises a TypeError on PHP 8 rather than degrading —
	 * which is why the React control joins before emitting.
	 */
	array( 'multi-select: two keys', array( 'type' => 'multi-select', 'population' => 'array',
		'choices' => array( 'a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma' ) ),
		'a' . MULTI_SELECT_DELIM . 'c', array( 'a', 'c' ) ),
	array( 'multi-select: empty', array( 'type' => 'multi-select', 'population' => 'array',
		'choices' => array( 'a' => 'Alpha' ) ), '', array() ),

	/**
	 * image-picker changes value TYPE with `multiple`: a key string when off, an
	 * array of keys when on. Grouped choices (a choice carrying its own `choices`)
	 * are flattened before validation, so a leaf inside a group is a valid key.
	 */
	array( 'image-picker: single key', array( 'type' => 'image-picker', 'value' => 'one',
		'choices' => array( 'one' => '/a.png', 'two' => '/b.png' ) ), 'two', 'two' ),
	array( 'image-picker: grouped leaf', array( 'type' => 'image-picker', 'value' => 'x1',
		'choices' => array( 'grp' => array( 'label' => 'Group', 'choices' => array(
			'x1' => '/x1.png',
			'x2' => array( 'small' => array( 'src' => '/x2.png', 'alt' => 'X2' ) ),
		) ) ) ), 'x2', 'x2' ),
	array( 'image-picker: multiple', array( 'type' => 'image-picker', 'multiple' => true, 'value' => array(),
		'choices' => array( 'p' => '/p.png', 'q' => '/q.png', 'r' => '/r.png' ) ),
		array( 'p', 'r' ), array( 'p', 'r' ) ),
	array( 'image-picker: blank clears', array( 'type' => 'image-picker', 'blank' => true, 'value' => 'one',
		'choices' => array( 'one' => '/a.png', 'two' => '/b.png' ) ), '', '' ),

	/**
	 * spacing is a three-level structure — margin/padding at the base layer, then
	 * per-device overrides under advanced.md / advanced.lg, each with five slots.
	 * Leaves are Bootstrap token NAMES ('0'-'5'), not CSS lengths: the server
	 * strips everything outside [a-zA-Z0-9_-], so '12%' would silently become '12'.
	 */
	array( 'spacing: empty', array( 'type' => 'spacing' ), fw_test_spacing(), fw_test_spacing() ),
	array( 'spacing: base + both devices', array( 'type' => 'spacing' ),
		fw_test_spacing( array( 'all' => '1' ), array( 'all' => '2' ),
			array( 'margin' => array( 'all' => '3' ) ), array( 'padding' => array( 'left' => '5' ) ) ),
		fw_test_spacing( array( 'all' => '1' ), array( 'all' => '2' ),
			array( 'margin' => array( 'all' => '3' ) ), array( 'padding' => array( 'left' => '5' ) ) ) ),
);

foreach ( $parity_cases as $case ) {
	list( $label, $schema, $emitted, $expected ) = $case;

	$got    = fw_get_options_values_from_input( array( 'p' => $schema ), array( 'p' => $emitted ) );
	$actual = array_key_exists( 'p', $got ) ? $got['p'] : '__ABSENT__';

	ct( "$label: React value survives the PHP path", $actual === $expected, $actual );
}

/**
 * Typography derives most of its value rather than storing what was submitted, so
 * it is checked by property instead of by equality.
 */
$typo_out = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography' ) ),
	array( 'p' => array(
		'family' => 'Georgia',
		'size'   => array( 'value' => '24', 'unit' => 'px' ),
		'color'  => '#112233',
		'weight' => '700',
		'style'  => 'italic',
	) )
);
$typo = isset( $typo_out['p'] ) ? $typo_out['p'] : array();

ct_eq( 'typography: family stored', 'Georgia', isset( $typo['family'] ) ? $typo['family'] : null );
ct_eq( 'typography: size kept as { value, unit }', array( 'value' => '24', 'unit' => 'px' ), isset( $typo['size'] ) ? $typo['size'] : null );
ct_eq( 'typography: 6-digit hex kept', '#112233', isset( $typo['color'] ) ? $typo['color'] : null );
ct_eq( 'typography: weight kept', '700', isset( $typo['weight'] ) ? $typo['weight'] : null );

/**
 * These three are DERIVED from `family` — the server sets them regardless of what
 * was submitted. A React control must not guess at them.
 */
ct_eq( 'typography: google_font derived from family', false, isset( $typo['google_font'] ) ? $typo['google_font'] : null );
ct_eq( 'typography: subset derived from family', false, isset( $typo['subset'] ) ? $typo['subset'] : null );

/**
 * Colour here is STRICTER than the color-picker option type: 3 or 6 digits only.
 * An 8-digit alpha hex — which color-picker accepts — is rejected and replaced
 * with the default. That is why the typography control strips alpha before
 * emitting rather than reusing the color-picker's normaliser.
 */
$typo_alpha = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography' ) ),
	array( 'p' => array( 'color' => '#11223344' ) )
);
ct(
	'typography: 8-digit alpha hex is rejected (unlike color-picker)',
	isset( $typo_alpha['p']['color'] ) && '#11223344' !== $typo_alpha['p']['color'],
	isset( $typo_alpha['p']['color'] ) ? $typo_alpha['p']['color'] : '__ABSENT__'
);

// A disabled component is stored as false, not omitted.
$typo_gated = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography', 'components' => array( 'color' => false ) ) ),
	array( 'p' => array( 'color' => '#ff0000' ) )
);
ct_eq( 'typography: disabled component becomes false', false, isset( $typo_gated['p']['color'] ) ? $typo_gated['p']['color'] : null );

// size_format 'number' stores a bare number, not the { value, unit } pair.
$typo_num = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography', 'size_format' => 'number' ) ),
	array( 'p' => array( 'size' => 18 ) )
);
ct_eq( 'typography: number format keeps a bare number', 18, isset( $typo_num['p']['size'] ) ? $typo_num['p']['size'] : null );

// typography-v2 is a pure alias — same value shape, so one React control serves both.
$typo_v2 = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography-v2' ) ),
	array( 'p' => array( 'family' => 'Georgia', 'weight' => '700' ) )
);
$typo_v1 = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'typography' ) ),
	array( 'p' => array( 'family' => 'Georgia', 'weight' => '700' ) )
);
ct_eq( 'typography-v2 alias produces an identical shape', $typo_v1['p'], $typo_v2['p'] );

// Idempotence: the stored value fed back must not change.
$typo_again = fw_get_options_values_from_input( array( 'p' => array( 'type' => 'typography' ) ), array( 'p' => $typo ) );
ct_eq( 'typography: stored value survives a second save', $typo, isset( $typo_again['p'] ) ? $typo_again['p'] : null );

/**
 * `icon` is discriminated by `type` and has a LEGACY bare-string shape still
 * present in saved values. Both must round-trip, and the pack metadata for a font
 * icon is derived server-side from the class — it depends on which packs are
 * installed, so a React control cannot and must not guess at it.
 */
$icon_schema = array( 'p' => array( 'type' => 'icon' ) );

$icon_font = fw_get_options_values_from_input( $icon_schema, array( 'p' => array( 'type' => 'icon-font', 'icon-class' => 'fa fa-star' ) ) );
ct_eq( 'icon: icon-font type kept', 'icon-font', $icon_font['p']['type'] ?? null );
ct_eq( 'icon: icon-class kept', 'fa fa-star', $icon_font['p']['icon-class'] ?? null );
ct( 'icon: pack-name derived by the server', array_key_exists( 'pack-name', $icon_font['p'] ?? array() ), array_keys( $icon_font['p'] ?? array() ) );

$icon_legacy = fw_get_options_values_from_input( $icon_schema, array( 'p' => 'fa fa-linux' ) );
ct_eq( 'icon: legacy bare string becomes icon-font', 'icon-font', $icon_legacy['p']['type'] ?? null );
ct_eq( 'icon: legacy bare string keeps its class', 'fa fa-linux', $icon_legacy['p']['icon-class'] ?? null );

$icon_empty = fw_get_options_values_from_input( $icon_schema, array( 'p' => '' ) );
ct_eq( 'icon: legacy empty string becomes none', 'none', $icon_empty['p']['type'] ?? null );

$icon_upload = fw_get_options_values_from_input( $icon_schema, array( 'p' => array(
	'type' => 'custom-upload', 'attachment-id' => 12, 'url' => 'http://x/y.png',
) ) );
/**
 * NOTE the difference from the `upload` option type, which stores its URL
 * protocol-relative. `icon` keeps whatever it was given, so a control must NOT
 * apply the same normalisation here.
 */
ct_eq( 'icon: custom-upload url kept verbatim, not protocol-relative', 'http://x/y.png', $icon_upload['p']['url'] ?? null );

$icon_none = fw_get_options_values_from_input( $icon_schema, array( 'p' => array( 'type' => 'none' ) ) );
ct( 'icon: switching to none drops icon-class', ! array_key_exists( 'icon-class', $icon_none['p'] ?? array() ), array_keys( $icon_none['p'] ?? array() ) );

$icon_again = fw_get_options_values_from_input( $icon_schema, array( 'p' => $icon_font['p'] ) );
ct_eq( 'icon: stored value survives a second save', $icon_font['p'], $icon_again['p'] ?? null );

/* --------------------------------------------------------------------- *
 * 3. Page-builder JSON round trip
 * --------------------------------------------------------------------- */
echo "\n== page-builder JSON round trip ==\n";

/**
 * json_encode( rootItems ) IS the storage format for every page ever built. The
 * property that matters is not "it parses" but "content nobody edited comes back
 * byte-identical" — anything less rewrites saved pages on an unrelated save.
 */
$builder_json = wp_json_encode( array(
	array(
		'type'   => 'section',
		'_items' => array(
			array(
				'type'   => 'column',
				'width'  => '1_1',
				'_items' => array(
					array(
						'type'      => 'simple',
						'shortcode' => 'text_block',
						'atts'      => array(
							'text'    => 'Quotes " and \' and backslash \\ and <b>markup</b>',
							'unicode' => 'em—dash · ✓ 中文',
							'empty'   => '',
							'zero'    => 0,
							'nested'  => array( 'a' => array( 'b' => 'c' ) ),
						),
					),
				),
			),
		),
	),
) );

$decoded  = json_decode( $builder_json, true );
$reencoded = wp_json_encode( $decoded );

ct( 'builder JSON decodes without error', null !== $decoded, json_last_error_msg() );
ct_eq( 'decode -> encode is byte-identical', $builder_json, $reencoded );

// The awkward values specifically, since these are what break naive escaping.
$atts = $decoded[0]['_items'][0]['_items'][0]['atts'];
ct( 'quotes and backslashes survive', false !== strpos( $atts['text'], '\\' ) && false !== strpos( $atts['text'], '"' ), $atts['text'] );
ct( 'unicode survives', false !== strpos( $atts['unicode'], '中文' ), $atts['unicode'] );
ct_eq( 'empty string stays a string', '', $atts['empty'] );
ct_eq( 'integer zero stays an integer', 0, $atts['zero'] );
ct_eq( 'nested atts survive', 'c', fw_akg( 'nested/a/b', $atts ) );

/**
 * The slashing hazard. Meta writes pass through wp_unslash(), so a value containing a
 * backslash must be wp_slash()ed on the way in or it arrives corrupted. This is the
 * bug shape that silently eats every backslash in saved builder content.
 */
$slashed   = wp_slash( $builder_json );
$unslashed = wp_unslash( $slashed );
ct_eq( 'wp_slash -> wp_unslash round trip is lossless', $builder_json, $unslashed );

/* --------------------------------------------------------------------- *
 * Result
 * --------------------------------------------------------------------- */
$pass = (int) $GLOBALS['__pass'];
$fail = (int) $GLOBALS['__fail'];

echo "\n----------------------------------------\n";
echo "  PASS: $pass    FAIL: $fail\n";
echo "----------------------------------------\n";

exit( $fail > 0 ? 1 : 0 );
