/**
 * `slider` option type — React renderer.
 *
 * The stored value is a NUMBER — `FW_Option_Type_Slider::_get_value_from_input()`
 * ends in `floatval()`. So this hands `onChange` a number, never the string an
 * `<input>` would otherwise give: storing `"40"` where the PHP renderer stores
 * `40` is exactly the kind of shape drift that makes a value compare unequal to
 * itself across renderers.
 *
 * Range configuration lives under `properties`, mirroring ion.rangeSlider's
 * settings (the library the PHP renderer uses): `min`, `max`, `step`. Those keys
 * are read from there rather than from the option root, because that is where an
 * existing options.php already declares them.
 */

const { RangeControl } = wp.components;

/** Mirrors FW_Option_Type_Slider::default_properties(). */
const DEFAULTS = { min: 0, max: 100, step: 1 };

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {number}   props.value    Current value.
 * @param {Function} props.onChange Called with the next number.
 */
export default function Slider( { option = {}, value, onChange } ) {
	const props = option.properties || {};

	const min = Number( props.min ?? DEFAULTS.min );
	const max = Number( props.max ?? DEFAULTS.max );
	const step = Number( props.step ?? DEFAULTS.step );

	// A value may arrive as a JSON-encoded string from PHP; normalise for display
	// without changing what is stored until the user actually moves the slider.
	const current = Number( value ?? option.value ?? min );

	return (
		<RangeControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ Number.isFinite( current ) ? current : min }
			min={ min }
			max={ max }
			step={ step }
			// RangeControl yields undefined when reset; fall back to the declared
			// default rather than writing undefined into the option value.
			onChange={ ( next ) => onChange( Number( next ?? option.value ?? min ) ) }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
