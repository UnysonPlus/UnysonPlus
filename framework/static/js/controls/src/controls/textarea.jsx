/**
 * `textarea` option type — React renderer.
 *
 * The plainest mapping in the set: the schema's value is a string and stays one.
 *
 * The PHP option type also declares `dynamic_content` (default true), which adds
 * the Dynamic Content picker beside the field in wp-admin. That picker is a
 * server-rendered jQuery UI, not a React component, so it is deliberately NOT
 * reproduced here — a block inspector gets a plain textarea. The stored value is
 * identical either way (dynamic content is a `{{ }}` token inside the same
 * string), so a value authored in the builder round-trips through this control
 * untouched, and vice versa.
 */

const { TextareaControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry (label, desc, attr…).
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Textarea( { option = {}, value = '', onChange } ) {
	const attr = option.attr || {};

	return (
		<TextareaControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ value ?? '' }
			placeholder={ attr.placeholder || undefined }
			rows={ attr.rows ? Number( attr.rows ) : undefined }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
}
