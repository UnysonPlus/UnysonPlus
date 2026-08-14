/**
 * `switch` option type — React renderer.
 *
 * The Unyson+ switch is not a boolean: it stores whichever of two arbitrary
 * values (`left-choice.value` / `right-choice.value`) is selected — those are
 * `false` / `true` by default, but an option can declare any pair, e.g.
 * 'hide' / 'show'. The React renderer must therefore round-trip the declared
 * values rather than coercing to a boolean, so a value saved here is byte-identical
 * to one saved through the PHP renderer.
 */

const { ToggleControl } = wp.components;

/** Default choices, mirroring FW_Option_Type_Switch::_get_defaults(). */
const LEFT = { value: false, label: 'No' };
const RIGHT = { value: true, label: 'Yes' };

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {*}        props.value    Current value — one of the two declared choices.
 * @param {Function} props.onChange Called with the next declared choice value.
 */
export default function Switch( { option = {}, value, onChange } ) {
	const left = option[ 'left-choice' ] || LEFT;
	const right = option[ 'right-choice' ] || RIGHT;

	// Compare loosely-but-safely: values arrive from PHP-encoded JSON, so a
	// declared `1` may reach us as `"1"`. Serializing both sides keeps
	// booleans, numbers and strings comparable without `==`.
	const same = ( a, b ) => JSON.stringify( a ) === JSON.stringify( b );
	const checked = same( value, right.value );

	return (
		<ToggleControl
			label={ option.label || '' }
			help={ option.desc || ( checked ? right.label : left.label ) }
			checked={ checked }
			onChange={ ( next ) => onChange( next ? right.value : left.value ) }
			__nextHasNoMarginBottom
		/>
	);
}
