/**
 * `border-style-picker` option type — React renderer.
 *
 * The stored value is a plain choice KEY — a border preset class name, or `''`
 * for "none" when `allow_none` is on (the default). `_get_value_from_input()`
 * accepts only keys present in `choices` (plus the empty string) and falls back
 * to the option default for anything else, so the control offers exactly the
 * declared set and nothing more.
 *
 * ## Why this renders as a dropdown, not the tile grid the page builder shows
 *
 * The PHP renderer draws a preview per choice by applying the preset's CLASS to a
 * sample box. Those classes come from the theme's compiled stylesheet, which is
 * not loaded in the block editor's sidebar — the sidebar is the outer document,
 * while theme styles are loaded into the canvas iframe. Drawing the tiles here
 * would produce a grid of identical, unstyled boxes: a preview that previews
 * nothing, and actively misleads.
 *
 * A labelled dropdown is honest about that. `preview_kind: 'badge'` choices carry
 * inline styles rather than classes, so where those are declared the swatch IS
 * meaningful and is rendered beside the label.
 */

const { SelectControl, BaseControl, Flex, FlexItem } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current choice key.
 * @param {Function} props.onChange Called with the next choice key.
 */
export default function BorderStylePicker( { option = {}, value, onChange } ) {
	const choices = option.choices || {};
	const allowNone = false !== option.allow_none;

	const options = ( allowNone
		? [ { value: '', label: option.placeholder || '— Select —' } ]
		: []
	).concat(
		Object.keys( choices ).map( ( key ) => {
			const choice = choices[ key ];

			return {
				value: key,
				label:
					'string' === typeof choice
						? choice
						: ( choice && ( choice.label || choice.text ) ) || key,
			};
		} )
	);

	const current = value ?? option.value ?? '';

	// Only badge-mode previews carry inline styles, which are the only ones that
	// survive outside the canvas iframe.
	const previews = ( 'badge' === option.preview_kind && option.previews ) || {};
	const swatch = previews[ current ] && previews[ current ].tile_style;

	const select = (
		<SelectControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			value={ String( current ) }
			options={ options }
			onChange={ onChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);

	if ( ! swatch ) {
		return select;
	}

	return (
		<BaseControl __nextHasNoMarginBottom>
			<Flex align="flex-end" gap={ 2 }>
				<FlexItem isBlock>{ select }</FlexItem>
				<FlexItem>
					{ /*
					  * `tile_style` is a raw inline-CSS string from the schema, so it is
					  * parsed into a style object rather than injected as an attribute —
					  * React would reject a string here, and building the object keeps
					  * the value out of any HTML-parsing path.
					  */ }
					<div
						aria-hidden="true"
						style={ String( swatch )
							.split( ';' )
							.reduce( ( acc, decl ) => {
								const [ prop, val ] = decl.split( ':' );
								if ( ! prop || ! val ) {
									return acc;
								}
								const key = prop
									.trim()
									.replace( /-([a-z])/g, ( _, c ) => c.toUpperCase() );
								return Object.assign( acc, { [ key ]: val.trim() } );
							}, { width: 34, height: 26 } ) }
					/>
				</FlexItem>
			</Flex>
		</BaseControl>
	);
}
