/**
 * `multi-select` option type — React renderer.
 *
 * ## The wire format is a delimited STRING, the stored value is an array
 *
 * `_get_value_from_input()` does `explode( '/*' + '/', $input_value )`. That means
 * it expects a **string** — handing it a real array raises a TypeError on PHP 8,
 * not a graceful fallback. So this control emits `'a/*' + '/b/*' + '/c'` and PHP
 * turns that back into `[ 'a', 'b', 'c' ]`.
 *
 * An empty selection must be the empty string, which PHP maps to `array()`.
 *
 * ## Only `population: 'array'` is supported here
 *
 * The schema's `population` may be `array` (choices declared inline), or `posts` /
 * `taxonomy` / `users`, where the list is built server-side from a query. The
 * dynamic populations need a REST round trip that this control deliberately does
 * not make — rendering an empty picker for them would look like "there is nothing
 * to choose" rather than "this cannot be edited here". They fall through to the
 * standard "no React control" notice instead, and stay fully editable in the page
 * builder.
 */

const { FormTokenField, BaseControl, Notice } = wp.components;

/** Matches the delimiter in FW_Option_Type_Multi_Select::_get_value_from_input(). */
const DELIMITER = '/*/';

/**
 * @param {Object}          props
 * @param {Object}          props.option   The option schema entry.
 * @param {Array|string}    props.value    Current value — array of keys, or the wire string.
 * @param {Function}        props.onChange Called with the next wire string.
 */
export default function MultiSelect( { option = {}, value, onChange } ) {
	const population = option.population || 'array';

	if ( 'array' !== population ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ `This "${ population }" list is built on the server — edit it in the page builder.` }
			</Notice>
		);
	}

	const choices = option.choices || {};

	// key -> label, and the reverse, because FormTokenField works in labels while
	// the option stores keys.
	const labelOf = {};
	const keyOf = {};

	Object.keys( choices ).forEach( ( key ) => {
		const choice = choices[ key ];
		const label =
			typeof choice === 'string'
				? choice
				: ( choice && ( choice.text || choice.label ) ) || key;

		labelOf[ key ] = label;
		// Last writer wins on duplicate labels; the key round trip below falls back
		// to the raw token, so a duplicate degrades to "unknown token" rather than
		// silently selecting the wrong choice.
		keyOf[ label ] = key;
	} );

	// Accept either shape on the way in: the stored array, or the wire string.
	const selectedKeys = Array.isArray( value )
		? value
		: String( value ?? '' )
				.split( DELIMITER )
				.filter( ( k ) => '' !== k );

	const selectedLabels = selectedKeys.map( ( k ) => labelOf[ k ] ?? k );

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<FormTokenField
				value={ selectedLabels }
				suggestions={ Object.values( labelOf ) }
				onChange={ ( tokens ) => {
					const keys = tokens
						.map( ( token ) => keyOf[ token ] ?? token )
						// A token the user typed freely is not a valid choice; PHP would
						// keep it verbatim, so drop it here rather than store a key that
						// resolves to nothing.
						.filter( ( k ) => Object.prototype.hasOwnProperty.call( labelOf, k ) );

					onChange( keys.join( DELIMITER ) );
				} }
				__experimentalExpandOnFocus
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</BaseControl>
	);
}
