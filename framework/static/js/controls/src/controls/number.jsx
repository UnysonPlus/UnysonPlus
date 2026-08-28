/**
 * `number` option type — React renderer.
 *
 * The stored value is a NUMBER, not a string: `FW_Option_Type_Number::
 * _get_value_from_input()` ends in `intval()` or `floatval()` depending on the
 * option's `numeric_type`. So this control does the same cast before handing the
 * value on, rather than letting the `"12"` an `<input>` produces reach storage
 * where the PHP renderer would have put `12`.
 *
 * ## Why the field is allowed to hold a value the option cannot store
 *
 * Clamping is applied on BLUR, not on every keystroke. Clamping as you type
 * makes a min of 10 impossible to reach: typing `1` snaps to `10`, and the `2`
 * you meant to type next lands after it. So while the field has focus it holds
 * what you typed, and it is reconciled when you leave — which is also when PHP
 * would have clamped it, so the two agree on the value that is finally stored.
 *
 * An emptied field is left empty rather than snapped to 0, for the same reason:
 * clearing it is a step on the way to typing something else, not an instruction
 * to store zero. It resolves on blur to the declared default.
 */

const { TextControl } = wp.components;
const { useState, useEffect } = wp.element;

/**
 * Cast per the option's `numeric_type`, mirroring the PHP side.
 *
 * @param {*}      raw    The value to cast.
 * @param {Object} option The option schema entry.
 * @return {number} An integer or float.
 */
function cast( raw, option ) {
	const n = parseFloat( raw );

	if ( ! Number.isFinite( n ) ) {
		return option.numeric_type === 'integer' ? 0 : 0;
	}

	return option.numeric_type === 'integer' ? Math.trunc( n ) : n;
}

/**
 * Apply the declared min/max, exactly as _get_value_from_input() does.
 *
 * `null` and `''` both mean "no bound" in the schema defaults, so both are
 * skipped rather than compared against — `value < ''` is not the test intended.
 *
 * @param {number} value  A cast number.
 * @param {Object} option The option schema entry.
 * @return {number} The clamped number.
 */
function clamp( value, option ) {
	const { min, max } = option;

	if ( min !== null && min !== undefined && min !== '' && value < Number( min ) ) {
		return cast( min, option );
	}

	if ( max !== null && max !== undefined && max !== '' && value > Number( max ) ) {
		return cast( max, option );
	}

	return value;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry (label, min, max, step…).
 * @param {number}   props.value    Current value.
 * @param {Function} props.onChange Called with the next number.
 */
export default function Number_( { option = {}, value, onChange } ) {
	const stored = value ?? option.value ?? 0;
	const [ draft, setDraft ] = useState( String( stored ) );

	// Follow the stored value when it changes from outside this control (an
	// undo, or a value replaced wholesale), but not while the user is typing.
	useEffect( () => {
		setDraft( ( current ) =>
			cast( current, option ) === cast( stored, option ) ? current : String( stored )
		);
	}, [ stored ] );

	return (
		<TextControl
			type="number"
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ draft }
			min={ option.min ?? undefined }
			max={ option.max ?? undefined }
			step={ option.step ?? undefined }
			onChange={ ( next ) => {
				setDraft( next );

				// Push through only what is already a valid number, so a
				// half-typed "-" or "1." does not land in storage as 0.
				if ( next !== '' && Number.isFinite( parseFloat( next ) ) ) {
					onChange( cast( next, option ) );
				}
			} }
			onBlur={ () => {
				const next = clamp( cast( draft === '' ? option.value ?? 0 : draft, option ), option );
				setDraft( String( next ) );
				onChange( next );
			} }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
