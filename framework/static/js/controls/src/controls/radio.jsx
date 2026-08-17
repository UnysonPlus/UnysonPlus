/**
 * `radio` option type — React renderer.
 *
 * Same schema shape as `select` — a `choices` map of value → label — rendered as
 * a radio group instead of a dropdown. Declaration order is preserved via
 * `Object.keys`, because the framework treats the order in `options.php` as
 * meaningful.
 *
 * The stored value is the choice KEY, never the label. Keys are strings by
 * convention (the PHP defaults warn against bool/int keys), so they are coerced
 * to strings for RadioControl and handed back unchanged.
 *
 * The schema's `inline` flag is not reproduced: RadioControl has no inline mode,
 * and a block inspector is a narrow column where stacked radios are the right
 * layout anyway. This is a presentation difference only — the value is unaffected.
 */

const { RadioControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current value — a key of `choices`.
 * @param {Function} props.onChange Called with the next choice key.
 */
export default function Radio( { option = {}, value, onChange } ) {
	const choices = option.choices || {};

	const options = Object.keys( choices ).map( ( key ) => {
		const choice = choices[ key ];

		return {
			value: key,
			// A choice may be a plain label string, or a { text|label } object —
			// matching the leniency the select control already applies.
			label:
				typeof choice === 'string'
					? choice
					: ( choice && ( choice.text || choice.label ) ) || key,
		};
	} );

	const selected = value ?? option.value ?? '';

	return (
		<RadioControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			selected={ String( selected ) }
			options={ options }
			onChange={ onChange }
		/>
	);
}
