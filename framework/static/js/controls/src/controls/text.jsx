/**
 * `text` option type — React renderer.
 *
 * Wraps WordPress's own TextControl so the result is visually identical to
 * every other control in a block inspector. The Unyson+ option schema is mapped
 * onto it here; the component itself stays presentational.
 */

const { TextControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry (label, desc, attr…).
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Text( { option = {}, value = '', onChange } ) {
	return (
		<TextControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ value ?? '' }
			placeholder={ ( option.attr && option.attr.placeholder ) || undefined }
			onChange={ onChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
