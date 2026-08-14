/**
 * `select` option type — React renderer.
 *
 * The Unyson+ schema declares `choices` as a plain value → label map, which is
 * the reverse shape of what WordPress's SelectControl wants, so it is mapped
 * here. Insertion order is preserved (the framework relies on declaration order
 * for things like design registries), so `Object.keys` is used rather than any
 * sort.
 */

const { SelectControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Select( { option = {}, value, onChange } ) {
	const choices = option.choices || {};

	const options = Object.keys( choices ).map( ( key ) => {
		const choice = choices[ key ];

		return {
			value: key,
			// A choice may be a plain label string, or a { text|label } object
			// (some option types decorate choices with extra metadata).
			label:
				typeof choice === 'string'
					? choice
					: ( choice && ( choice.text || choice.label ) ) || key,
		};
	} );

	return (
		<SelectControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ value ?? option.value ?? '' }
			options={ options }
			onChange={ onChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
