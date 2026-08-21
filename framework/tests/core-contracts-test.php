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

$checkboxes_schema = array(
	'type'    => 'checkboxes',
	'choices' => array( 'comments' => 'Comments', 'author' => 'Author', 'date' => 'Date' ),
);
$button_style_schema = array(
	'type'    => 'button-style-picker',
	'value'   => 'btn-primary',
	'choices' => array( 'btn-primary' => 'Primary', 'btn-ghost' => 'Ghost' ),
);
$button_style_strict = array_merge( $button_style_schema, array( 'allow_none' => false ) );

$table_schema = array( 'type' => 'table' );

$responsive_schema = array(
	'type'  => 'responsive',
	'inner' => array( 'type' => 'short-select', 'choices' => array( 'left' => 'Left', 'right' => 'Right' ) ),
);
$responsive_blank = array(
	'type'  => 'responsive',
	'inner' => array( 'type' => 'short-select',
		'choices' => array( '' => 'Inherit', 'left' => 'Left', 'right' => 'Right' ) ),
);
$grad_schema = array( 'type' => 'gradient-v2' );
$rgba_schema = array( 'type' => 'rgba-color-picker', 'value' => '' );
$bg_schema   = array( 'type' => 'background-pro' );

$shadow_schema = array( 'type' => 'box-shadow' );

$popover_one = array(
	'type'          => 'popover',
	'value'         => 'left',
	'inner-options' => array( 'fx' => array(
		'type'    => 'select',
		'value'   => 'left',
		'choices' => array( 'left' => 'Left', 'up' => 'Up' ),
	) ),
);
$popover_many = array(
	'type'          => 'popover',
	'value'         => array(),
	'inner-options' => array(
		'a' => array( 'type' => 'text' ),
		'b' => array( 'type' => 'text' ),
	),
);

$split_schema = array( 'type' => 'split-slider', 'min_width' => 10 );

$multi_upload_schema = array( 'type' => 'multi-upload' );
$addable_box_schema  = array(
	'type'        => 'addable-box',
	'box-options' => array( 'label' => array( 'type' => 'text' ) ),
);
$date_schema      = array( 'type' => 'date-picker', 'value' => '' );
$multi_inline_sch = array(
	'type'             => 'multi-inline',
	'value'            => array( 'monthly' => '29', 'yearly' => '' ),
	'fw_multi_options' => array(
		'monthly' => array( 'type' => 'text', 'title' => 'Monthly' ),
		'yearly'  => array( 'type' => 'text', 'title' => 'Yearly' ),
	),
);

$col_split_schema = array( 'type' => 'column-split', 'value' => '1/2' );
$col_split_thirds = array( 'type' => 'column-split', 'value' => '1/2',
	'fractions' => array( '1/3', '1/2', '2/3' ) );
$code_schema     = array( 'type' => 'code-editor', 'mode' => 'htmlmixed' );
$dt_schema       = array( 'type' => 'datetime-picker', 'value' => '' );

$bha_schema = array(
	'type'    => 'button-hover-animation',
	'value'   => '',
	'choices' => array( 'btnfx-lift' => 'Lift', 'btnfx-sweep' => 'Sweep' ),
);

$img_style_schema = array(
	'type'    => 'image-style-picker',
	'value'   => '',
	'choices' => array( 'rounded' => 'Rounded', 'duotone' => 'Duotone' ),
);
$img_style_schema_strict = array_merge( $img_style_schema, array(
	'allow_none' => false,
	'value'      => 'rounded',
) );

$multi_picker_schema = array(
	'type'   => 'multi-picker',
	'picker' => array( 'kind' => array(
		'type'    => 'select',
		'value'   => 'image',
		'choices' => array( 'image' => 'Image', 'video' => 'Video' ),
	) ),
	'choices' => array(
		'image' => array( 'alt'      => array( 'type' => 'text' ) ),
		'video' => array( 'url'      => array( 'type' => 'text' ),
		                  'autoplay' => array( 'type' => 'switch' ) ),
	),
);

$addable_schema = array(
	'type'          => 'addable-popup',
	'popup-options' => array( 'title' => array( 'type' => 'text' ) ),
	'template'      => '{{= title }}',
);
$addable_limited = array_merge( $addable_schema, array( 'limit' => 2 ) );
$addable_full    = array_merge( $addable_schema, array( 'type' => 'addable-popup-full' ) );

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
	 * `number` stores an int OR a float depending on `numeric_type`, and clamps to
	 * min/max. The React control casts and clamps the same way, so a value that
	 * looks settled in the sidebar is the value that gets stored — the failure
	 * this guards against is the quiet one, where PHP silently rewrites what the
	 * editor showed and the user never learns which number won.
	 */
	array( 'number: float default',  array( 'type' => 'number' ),                              12.5,  12.5 ),
	array( 'number: integer type',   array( 'type' => 'number', 'numeric_type' => 'integer' ), 12,    12 ),
	array( 'number: zero',           array( 'type' => 'number' ),                              0,     0.0 ),
	array( 'number: clamped to min', array( 'type' => 'number', 'min' => 5 ),                  5.0,   5.0 ),
	array( 'number: clamped to max', array( 'type' => 'number', 'max' => 10 ),                 10.0,  10.0 ),

	/**
	 * `short-text` and `medium-text` are FW_Option_Type_Text subclasses that change
	 * only the input's width, and share the React text control for that reason.
	 * These assert the shared control is not merely a plausible substitute: the
	 * value semantics really are identical.
	 */
	array( 'short-text: plain',    array( 'type' => 'short-text' ),                            'abc',        'abc' ),
	array( 'medium-text: plain',   array( 'type' => 'medium-text' ),                           'abc',        'abc' ),
	array( 'short-text: empty',    array( 'type' => 'short-text' ),                            '',           '' ),

	/**
	 * addable-popup stores an ARRAY OF OBJECTS. Its _get_value_from_input()
	 * accepts either real arrays or JSON strings, because the jQuery renderer
	 * submits each item through a hidden input; the React control emits arrays.
	 *
	 * The `limit` case is the one worth having: PHP truncates on save, so a
	 * control that let you add past the cap would show work that is silently
	 * discarded. The control hides its Add button at the cap for that reason,
	 * and this asserts the cap it is hiding at is the real one.
	 */
	array( 'addable-popup: items', $addable_schema,
		array( array( 'title' => 'One' ), array( 'title' => 'Two' ) ),
		array( array( 'title' => 'One' ), array( 'title' => 'Two' ) ) ),
	array( 'addable-popup: empty', $addable_schema, array(), array() ),
	array( 'addable-popup: json wire', $addable_schema,
		array( '{"title":"One"}' ),
		array( array( 'title' => 'One' ) ) ),
	array( 'addable-popup: limit truncates', $addable_limited,
		array( array( 'title' => 'a' ), array( 'title' => 'b' ), array( 'title' => 'c' ) ),
		array( array( 'title' => 'a' ), array( 'title' => 'b' ) ) ),
	array( 'addable-popup-full: same shape', $addable_full,
		array( array( 'title' => 'One' ) ),
		array( array( 'title' => 'One' ) ) ),

	/**
	 * multi-picker stores the pick plus ONLY the selected choice's sub-values.
	 * The pruning is the point: an unpruned Entrance picker carried a settings
	 * block for all ~56 Animate.css effects and exhausted memory in the page
	 * builder. These cases pin that behaviour down so a future change to the
	 * React control cannot quietly reintroduce the collect-everything shape.
	 *
	 * Note the switch arrives as the STRING 'true' and is stored as a boolean:
	 * unlike addable-popup, this type runs each child's _get_value_from_input(),
	 * so children must emit the wire format, not the stored one.
	 */
	array( 'multi-picker: selected only', $multi_picker_schema,
		array( 'kind' => 'video', 'video' => array( 'url' => 'a.mp4', 'autoplay' => 'true' ),
		       'image' => array( 'alt' => 'dropped' ) ),
		array( 'kind' => 'video', 'video' => array( 'url' => 'a.mp4', 'autoplay' => true ) ) ),
	array( 'multi-picker: other branch', $multi_picker_schema,
		array( 'kind' => 'image', 'image' => array( 'alt' => 'A cat' ) ),
		array( 'kind' => 'image', 'image' => array( 'alt' => 'A cat' ) ) ),

	/**
	 * image-style-picker stores a preset KEY. Its validator accepts a declared
	 * choice, plus '' when allow_none is on, and silently substitutes the option
	 * default for anything else — so an out-of-date key does not error, it just
	 * quietly becomes something else. The React control treats an unknown key as
	 * unselected for that reason, rather than showing a selection the next save
	 * would refuse.
	 */
	array( 'image-style: declared key', $img_style_schema,          'duotone', 'duotone' ),
	array( 'image-style: none allowed', $img_style_schema,          '',        '' ),
	array( 'image-style: none refused', $img_style_schema_strict,   '',        'rounded' ),
	array( 'image-style: unknown key',  $img_style_schema,          'nope',    '' ),

	/**
	 * checkboxes stores ONLY the checked choices, each as `true`.
	 *
	 * The case that matters is 'unchecked sent as false'. _get_value_from_input()
	 * keeps every entry whose value is not the EMPTY STRING and stores it as true —
	 * it never tests truthiness — so `false` would come back CHECKED. The React
	 * control omits unchecked keys entirely, and this pins that down against a
	 * future tidy-up that "simplifies" it into sending both.
	 */
	array( 'checkboxes: two checked', $checkboxes_schema,
		array( 'comments' => true, 'author' => true ),
		array( 'comments' => true, 'author' => true ) ),
	array( 'checkboxes: none checked', $checkboxes_schema, array(), array() ),
	array( 'checkboxes: unknown key dropped', $checkboxes_schema,
		array( 'comments' => true, 'gone' => true ),
		array( 'comments' => true ) ),
	// Documents the trap rather than the control: false does NOT mean unchecked.
	array( 'checkboxes: false is not unchecked', $checkboxes_schema,
		array( 'author' => false ),
		array( 'author' => true ) ),

	/**
	 * button-style-picker stores the CLASS STRING, with the same accept-or-default
	 * rule as image-style-picker. `allow_none => false` is used where a button must
	 * always carry a real preset.
	 */
	array( 'button-style: declared key', $button_style_schema, 'btn-ghost', 'btn-ghost' ),
	array( 'button-style: none allowed', $button_style_schema, '',          '' ),
	array( 'button-style: none refused', $button_style_strict, '',          'btn-primary' ),
	array( 'button-style: unknown key',  $button_style_schema, 'btn-nope',  'btn-primary' ),

	/**
	 * button-hover-animation accepts '' UNCONDITIONALLY — unlike its two cousins it
	 * has no `allow_none` config, because "no hover effect" is valid for every
	 * button. Asserted so the shared-looking trio does not get "unified" into one
	 * control that forbids the empty value somewhere it is legal.
	 */
	array( 'hover-fx: declared key', $bha_schema, 'btnfx-sweep', 'btnfx-sweep' ),
	array( 'hover-fx: none always ok', $bha_schema, '', '' ),
	array( 'hover-fx: unknown key', $bha_schema, 'btnfx-nope', '' ),

	/**
	 * column-split stores an 'n/d' fraction, and _get_value_from_input() SNAPS an
	 * out-of-set value to the nearest allowed one by ratio rather than rejecting
	 * it. The React control only offers allowed fractions for exactly that reason —
	 * the snap is helpful for a drag and confusing as a surprise.
	 */
	array( 'column-split: allowed',  $col_split_schema, '5/12', '5/12' ),
	// Fractions are stored in LOWEST TERMS: the twelfths list yields 1/2, not 6/12.
	// The React control reduces before offering a tile for exactly this reason —
	// offering '6/12' would mean every click on it was silently rewritten on save.
	array( 'column-split: reduced',  $col_split_schema, '1/2',  '1/2' ),
	array( 'column-split: unreduced input reduces', $col_split_schema, '6/12', '1/2' ),
	array( 'column-split: snaps',    $col_split_thirds, '5/12', '1/2' ),

	// code-editor stores the string as given — no transform, unlike wp-editor.
	array( 'code-editor: markup',    $code_schema, '<p>a</p>', '<p>a</p>' ),
	array( 'code-editor: empty',     $code_schema, '',         '' ),

	/**
	 * datetime-picker validates the FORMATTING, not just the date: the validator
	 * re-formats what it parsed and compares it back, so a valid date in the wrong
	 * shape is discarded for the option default. An ISO string is the exact mistake
	 * a React control is likely to make, so it is asserted as a failure case.
	 */
	array( 'datetime: declared format', $dt_schema, '2026/09/01 14:30', '2026/09/01 14:30' ),
	array( 'datetime: ISO is REJECTED', $dt_schema, '2026-09-01T14:30', '' ),
	array( 'datetime: empty',           $dt_schema, '',                 '' ),

	/**
	 * addable-box is addable-popup with its children under `box-options`, and it
	 * DOES run each child's validator. The React control re-uses the repeater
	 * component with the key remapped, so this asserts the remap actually reaches
	 * the same stored shape.
	 */
	array( 'addable-box: items', $addable_box_schema,
		array( array( 'label' => 'One' ), array( 'label' => 'Two' ) ),
		array( array( 'label' => 'One' ), array( 'label' => 'Two' ) ) ),
	array( 'addable-box: empty', $addable_box_schema, array(), array() ),

	/**
	 * date-picker stores dd-MM-yyyy, and its validator only casts to string — it
	 * accepts ANY shape. That is what makes it dangerous: an ISO date saves fine
	 * and is then misread, because PHP's strtotime() treats a dash-separated date
	 * as d-m-Y. These assert the stored shape rather than the validator, since the
	 * validator has no opinion.
	 */
	array( 'date-picker: dd-MM-yyyy', $date_schema, '01-09-2026', '01-09-2026' ),
	array( 'date-picker: empty',      $date_schema, '',           '' ),

	// multi-inline returns whatever array it is handed; the child ids are the keys.
	array( 'multi-inline: pair', $multi_inline_sch,
		array( 'monthly' => '29', 'yearly' => '290' ),
		array( 'monthly' => '29', 'yearly' => '290' ) ),

	/**
	 * split-slider normalises widths to sum 100, and an EMPTY array is a real
	 * value meaning AUTO (equal columns) rather than "unset" — the validator
	 * returns array() for empty input and never expands it into explicit halves.
	 * The React control normalises as you type so both paths hold the same shape.
	 */
	array( 'split-slider: sums to 100', $split_schema,
		array( array( 'w' => 60, 'name' => '' ), array( 'w' => 40, 'name' => '' ) ),
		array( array( 'w' => 60, 'name' => '' ), array( 'w' => 40, 'name' => '' ) ) ),
	array( 'split-slider: empty is AUTO', $split_schema, array(), array() ),

	/**
	 * popover's stored shape depends on how many inner options it declares:
	 * ONE is stored unwrapped (the inner option's own value), two or more as a
	 * hash keyed by inner id. _get_value_from_input() branches on that count.
	 *
	 * The single case is the common one, and a control that wrapped it anyway
	 * would produce a value the element cannot read — a setting that silently
	 * reverts. Both shapes are asserted so the branch cannot be "tidied" away.
	 */
	array( 'popover: one inner is unwrapped', $popover_one, 'up', 'up' ),
	array( 'popover: many inner is a hash',   $popover_many,
		array( 'a' => 'x', 'b' => 'y' ), array( 'a' => 'x', 'b' => 'y' ) ),

	/**
	 * box-shadow stores INTEGER offsets — sanitize() casts each with
	 * (int) round( (float) $v ) — alongside a free-string colour and a real
	 * boolean. A control emitting '4' where the option stores 4 would round-trip
	 * to a different value than the page builder saves for the same shadow.
	 */
	array( 'box-shadow: ints', $shadow_schema,
		array( 'x' => 0, 'y' => 4, 'blur' => 12, 'spread' => 0, 'color' => 'rgba(0,0,0,.15)', 'inset' => false ),
		array( 'x' => 0, 'y' => 4, 'blur' => 12, 'spread' => 0, 'color' => 'rgba(0,0,0,.15)', 'inset' => false ) ),
	array( 'box-shadow: inset true', $shadow_schema,
		array( 'x' => -2, 'y' => -2, 'blur' => 6, 'spread' => 1, 'color' => '#000', 'inset' => true ),
		array( 'x' => -2, 'y' => -2, 'blur' => 6, 'spread' => 1, 'color' => '#000', 'inset' => true ) ),

	/**
	 * responsive always stores the FULL { base, md, lg } shape. An empty string
	 * means "inherit the smaller width" — not "unset" — so a control that omitted
	 * untouched devices would drop keys the element reads.
	 */
	/*
	 * Whether a blank device SURVIVES depends on the inner type. A select with no
	 * blank entry in its choices rejects '' and falls through to the first choice —
	 * so 'md' => '' comes back as 'left' here. Real schemas that want an inheriting
	 * device declare a blank/default choice, and then it round-trips intact (the
	 * second case). Both are asserted because the difference is invisible until a
	 * tablet quietly picks up a value nobody set.
	 */
	array( 'responsive: blank needs a blank choice', $responsive_schema,
		array( 'base' => 'left', 'md' => '', 'lg' => 'right' ),
		array( 'base' => 'left', 'md' => 'left', 'lg' => 'right' ) ),
	array( 'responsive: blank survives when declared', $responsive_blank,
		array( 'base' => 'left', 'md' => '', 'lg' => 'right' ),
		array( 'base' => 'left', 'md' => '', 'lg' => 'right' ) ),
	array( 'responsive: base only', $responsive_schema,
		array( 'base' => 'left' ),
		array( 'base' => 'left', 'md' => '', 'lg' => '' ) ),

	/**
	 * gradient-v2: FEWER THAN TWO STOPS IS NO GRADIENT. The validator discards a
	 * lone stop and stores the empty form rather than restoring a default, and
	 * that empty array is what turns the layer off — there is no enable switch.
	 * The React control clears a gradient rather than leaving one stop behind.
	 */
	array( 'gradient: two stops', $grad_schema,
		array( 'type' => 'linear', 'angle' => 90, 'stops' => array(
			array( 'color' => '#3858e9', 'position' => 0 ),
			array( 'color' => '#7f54b3', 'position' => 100 ) ) ),
		array( 'type' => 'linear', 'angle' => 90, 'stops' => array(
			array( 'color' => '#3858e9', 'position' => 0.0 ),
			array( 'color' => '#7f54b3', 'position' => 100.0 ) ) ) ),
	array( 'gradient: one stop is none', $grad_schema,
		array( 'type' => 'linear', 'angle' => 90, 'stops' => array( array( 'color' => '#000', 'position' => 0 ) ) ),
		array( 'type' => 'linear', 'angle' => 90, 'stops' => array() ) ),
	array( 'gradient: angle clamps', $grad_schema,
		array( 'type' => 'radial', 'angle' => 999, 'stops' => array() ),
		array( 'type' => 'radial', 'angle' => 360, 'stops' => array() ) ),

	/**
	 * rgba-color-picker is LOOSER than color-picker: hex 3/4/6/8 or rgb()/rgba().
	 * Normalising to hex here — as the colour-picker control must — would be a
	 * quiet downgrade, since this type exists where alpha is the point.
	 */
	array( 'rgba: rgba string', $rgba_schema, 'rgba(0,0,0,0.5)', 'rgba(0,0,0,0.5)' ),
	array( 'rgba: hex',         $rgba_schema, '#2f74e6',         '#2f74e6' ),
	array( 'rgba: empty is a value', $rgba_schema, '',            '' ),

	/**
	 * table stores four parallel structures that must agree: `content` is rows x
	 * columns, `cols` is as long as every content row, `rows` is as long as
	 * `content`, and each row's `name` is DERIVED from whether its index falls
	 * inside header_rows. The React control rebuilds the whole value on every
	 * mutation for that reason.
	 *
	 * The WIRE format is a JSON blob under `__json` — what the page builder's JS
	 * submits — and this asserts that path, because it is the one that exists. The
	 * stored shape is NOT accepted as input: feeding it back returns empty cells,
	 * since the parser falls through to the legacy pricing branch.
	 *
	 * That asymmetry is survivable only because the builder's editor BOOTS from the
	 * stored model and re-serialises to `__json` on save. A block emits the stored
	 * shape directly (nothing validates on that path, and the element's view reads
	 * that shape), so both surfaces work — but anything that fed a stored value
	 * back through the validator would silently empty the table.
	 */
	array( 'table: wire json to stored shape', $table_schema,
		array(
			'header_options' => array( 'table_purpose' => 'tabular' ),
			'__json' => "{\"header_options\": {\"table_purpose\": \"tabular\", \"header_rows\": 1, \"footer_rows\": 0}, \"cols\": [{\"name\": \"default-col\", \"align\": \"left\", \"width\": \"\"}], \"content\": [[{\"textarea\": \"Plan\", \"colspan\": 1, \"rowspan\": 1, \"merged\": false}]]}",
		),
		array(
			'header_options' => array( 'table_purpose' => 'tabular', 'header_rows' => 1, 'footer_rows' => 0 ),
			'cols'    => array( array( 'name' => 'default-col', 'align' => 'left', 'width' => '' ) ),
			'rows'    => array( array( 'name' => 'heading-row' ) ),
			'content' => array( array( array( 'textarea' => 'Plan', 'colspan' => 1, 'rowspan' => 1, 'merged' => false ) ) ),
		) ),

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

/**
 * The compact predefined-colours picker is the SHARED colour field across the
 * shortcode set — 73 shortcodes declare it through sc_color_field_compact(). Its
 * value is a pair, and the server keeps BOTH halves: choosing a preset does not
 * clear a custom colour, which is what makes switching between them
 * non-destructive.
 *
 * Note it does NOT validate the custom value the way the plain `color-picker`
 * option type does — it is cast to a string and stored verbatim, so `rgba()` is
 * legitimate here and must not be normalised to hex.
 */
$pc_schema = array( 'p' => array(
	'type'    => 'predefined-colors-color-picker-compact',
	'choices' => array(
		'text-red'  => array( 'label' => 'Red', 'color' => '#ff0000' ),
		'text-blue' => array( 'label' => 'Blue', 'color' => '#0000ff' ),
	),
) );

$pc_both = fw_get_options_values_from_input( $pc_schema, array( 'p' => array( 'predefined' => 'text-blue', 'custom' => '#123456' ) ) );
ct_eq( 'compact colour: both halves are kept', array( 'predefined' => 'text-blue', 'custom' => '#123456' ), $pc_both['p'] ?? null );

$pc_legacy = fw_get_options_values_from_input( $pc_schema, array( 'p' => 'text-red' ) );
ct_eq( 'compact colour: legacy bare string becomes a pair', array( 'predefined' => 'text-red', 'custom' => '' ), $pc_legacy['p'] ?? null );

$pc_rgba = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'predefined-colors-color-picker-compact', 'picker' => 'rgba-color-picker' ) ),
	array( 'p' => array( 'predefined' => '', 'custom' => 'rgba(1,2,3,0.5)' ) )
);
ct_eq( 'compact colour: rgba custom stored verbatim (unlike color-picker)', 'rgba(1,2,3,0.5)', $pc_rgba['p']['custom'] ?? null );

/**
 * `wp-editor` is the only option type whose save TRANSFORMS the value: with
 * `wpautop` on (the default) it wraps plain text in paragraphs and strips
 * newlines. A transforming save is the shape most likely to corrupt content a
 * little more on each edit, so idempotence is asserted explicitly — it is what
 * makes editing the markup directly safe.
 */
$we_auto = array( 'p' => array( 'type' => 'wp-editor' ) );

$we_plain = fw_get_options_values_from_input( $we_auto, array( 'p' => 'Hello' ) );
ct_eq( 'wp-editor: wpautop wraps plain text', '<p>Hello</p>', $we_plain['p'] ?? null );

$we_wrapped = fw_get_options_values_from_input( $we_auto, array( 'p' => '<p>Hi</p>' ) );
ct_eq( 'wp-editor: already-wrapped html passes through', '<p>Hi</p>', $we_wrapped['p'] ?? null );

$we_once  = fw_get_options_values_from_input( $we_auto, array( 'p' => "First\n\nSecond" ) );
$we_twice = fw_get_options_values_from_input( $we_auto, array( 'p' => $we_once['p'] ) );
ct_eq( 'wp-editor: the wpautop transform is idempotent', $we_once['p'] ?? null, $we_twice['p'] ?? null );

$we_raw = fw_get_options_values_from_input(
	array( 'p' => array( 'type' => 'wp-editor', 'wpautop' => false ) ),
	array( 'p' => "a\n\nb" )
);
ct_eq( 'wp-editor: wpautop=false stores verbatim', "a\n\nb", $we_raw['p'] ?? null );

/**
 * `border-style-picker` stores a choice KEY and rejects anything outside the
 * declared set — including the empty string when `allow_none` is off, which is
 * why the control must not offer a blank entry in that case.
 */
$bsp = array( 'p' => array(
	'type'    => 'border-style-picker',
	'value'   => '',
	'choices' => array( 'b-solid' => 'Solid', 'b-dashed' => 'Dashed' ),
) );

$bsp_ok = fw_get_options_values_from_input( $bsp, array( 'p' => 'b-dashed' ) );
ct_eq( 'border picker: a declared choice is kept', 'b-dashed', $bsp_ok['p'] ?? null );

$bsp_bad = fw_get_options_values_from_input( $bsp, array( 'p' => 'not-a-preset' ) );
ct_eq( 'border picker: an unknown key falls back to the default', '', $bsp_bad['p'] ?? null );

$bsp_req = fw_get_options_values_from_input(
	array( 'p' => array(
		'type' => 'border-style-picker', 'value' => 'b-solid', 'allow_none' => false,
		'choices' => array( 'b-solid' => 'Solid' ),
	) ),
	array( 'p' => '' )
);
ct_eq( 'border picker: empty is rejected when allow_none is false', 'b-solid', $bsp_req['p'] ?? null );

/* --------------------------------------------------------------------- *
 * 2b. Builder item-type registration
 * --------------------------------------------------------------------- */
echo "\n== builder item-type registration ==\n";

/**
 * Each builder TYPE must fire its own :register_items action.
 *
 * FW_Option_Type_Builder::get_item_types() guards that action with a static
 * flag. A plain `static $did_action = false` is shared by every instance AND
 * every subclass, so the first builder type to resolve its items silenced all
 * the others: they skipped their own action and reported zero item types.
 *
 * The symptom was remote from the cause. The Gutenberg bridge resolves the FORM
 * builder's item types during init to build a field editor; that left the PAGE
 * builder with no item types, so every item in a saved page rendered as a
 * generic "Default View" box — with no PHP error and nothing in the log.
 *
 * Tested with two throwaway builder types rather than the real ones: the real
 * ones register behind an is_admin() gate and may already have been resolved
 * earlier in the request, either of which would make this pass or fail for
 * reasons unrelated to the guard.
 */
if ( class_exists( 'FW_Option_Type_Builder' ) && ! class_exists( 'CT_Builder_A' ) ) {
	eval(
		'class CT_Builder_A extends FW_Option_Type_Builder {'
		. ' public function get_type() { return "ct-builder-a"; }'
		. ' protected function _render($id,$o,$d){ return ""; }'
		. ' protected function _get_defaults(){ return array("value"=>array("json"=>"[]")); }'
		. '}'
		. 'class CT_Builder_B extends CT_Builder_A {'
		. ' public function get_type() { return "ct-builder-b"; }'
		. '}'
	);
}

if ( class_exists( 'CT_Builder_A' ) ) {
	$ct_fired = array();

	foreach ( array( 'ct-builder-a', 'ct-builder-b' ) as $ct_bt ) {
		add_action(
			'fw_option_type_builder:' . $ct_bt . ':register_items',
			function () use ( $ct_bt, &$ct_fired ) {
				$ct_fired[ $ct_bt ] = true;
			}
		);
	}

	$ct_items = new ReflectionMethod( 'FW_Option_Type_Builder', 'get_item_types' );
	$ct_items->setAccessible( true );

	$ct_a = new CT_Builder_A();
	$ct_b = new CT_Builder_B();

	$ct_items->invoke( $ct_a );
	$ct_items->invoke( $ct_b );

	ct(
		'first builder type fires its register_items action',
		isset( $ct_fired['ct-builder-a'] ),
		$ct_fired ? implode( ', ', array_keys( $ct_fired ) ) : 'nothing fired'
	);

	ct(
		'a SECOND builder type still fires its own action',
		isset( $ct_fired['ct-builder-b'] ),
		$ct_fired ? implode( ', ', array_keys( $ct_fired ) ) : 'nothing fired'
	);
}

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
