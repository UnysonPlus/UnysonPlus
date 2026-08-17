/**
 * `checkbox` option type — React renderer.
 *
 * ## Two labels, not one
 *
 * This option type carries BOTH a `label` (the field's name, shown like every
 * other option's label) and a `text` (the caption printed beside the box itself,
 * defaulting to "Yes"). WordPress's CheckboxControl has only one label slot, and
 * it renders beside the box — so `text` maps there, and `label` is restored by
 * wrapping in a BaseControl when the two differ. Collapsing them would silently
 * drop whichever the author wrote second.
 *
 * ## The value is a real boolean
 *
 * `FW_Option_Type_Checkbox::_get_value_from_input()` ends in `(bool)`, so `true`
 * and `false` are what reach the database — not `'1'`, not `'yes'`. This control
 * therefore hands `onChange` a boolean, so a value saved from a block inspector
 * is byte-identical to one saved through the PHP renderer.
 *
 * Note this is the SINGLE checkbox. `checkboxes` (plural) is a different option
 * type with a different value shape — a map of choice keys to booleans — and has
 * its own renderer.
 */

const { CheckboxControl, BaseControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {boolean}  props.value    Current value.
 * @param {Function} props.onChange Called with the next boolean.
 */
export default function Checkbox( { option = {}, value, onChange } ) {
	// Values arriving from PHP-encoded JSON may be `false`, `0` or `''` for the
	// unchecked state; anything truthy means checked. The value handed BACK is
	// always a real boolean, which is what the PHP side stores.
	const checked = !! value;

	const caption = option.text || option.label || '';
	const heading = option.label && option.label !== caption ? option.label : '';

	const control = (
		<CheckboxControl
			label={ caption }
			help={ heading ? undefined : option.desc || undefined }
			checked={ checked }
			onChange={ ( next ) => onChange( !! next ) }
			__nextHasNoMarginBottom
		/>
	);

	if ( ! heading ) {
		return control;
	}

	return (
		<BaseControl
			label={ heading }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ control }
		</BaseControl>
	);
}
