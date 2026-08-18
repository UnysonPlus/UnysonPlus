/**
 * `predefined-colors-color-picker-compact` option type — React renderer.
 *
 * This is the shared colour field across the shortcode set — **73 shortcodes**
 * declare it through `sc_color_field_compact()`. Without a React control, colour
 * could not be edited in any block sidebar, which is why it is worth more than
 * any single block.
 *
 * ## The value is a PAIR, and both halves are kept
 *
 *     { predefined: 'text-red', custom: '#ff0000' }
 *
 * `predefined` is a class name from the theme's Color Presets; `custom` is a raw
 * CSS colour. `_get_value_from_input()` stores both, casting each to a string —
 * it never clears one because the other is set. So this control does not treat
 * them as mutually exclusive either: choosing a preset leaves any custom colour
 * intact underneath, which is what makes switching back and forth non-destructive.
 *
 * ## Legacy bare strings
 *
 * A shortcode migrated from the older `sc_color_field()` may carry a plain string
 * default like `'text-red'`. PHP rescues that to `{ predefined: 'text-red',
 * custom: '' }` on first save; this control reads the same shape so a value that
 * has not been re-saved yet still renders correctly.
 *
 * ## `picker` decides whether alpha is offered
 *
 * `'color-picker'` (default) or `'rgba-color-picker'`. Unlike the plain
 * `color-picker` option type, this one does not validate the custom value — it is
 * cast to a string and stored verbatim — so `rgba()` is legitimate here when the
 * schema asks for it, and must NOT be normalised to hex.
 */

const { BaseControl, SelectControl, ColorPicker, Button, Flex, FlexItem } = wp.components;
const { useState } = wp.element;

/** Mirrors the PHP rescue of a legacy string value. */
function normalize( value ) {
	if ( 'string' === typeof value ) {
		return { predefined: value, custom: '' };
	}

	if ( ! value || 'object' !== typeof value ) {
		return { predefined: '', custom: '' };
	}

	return {
		predefined: 'string' === typeof value.predefined ? value.predefined : '',
		custom: 'string' === typeof value.custom ? value.custom : '',
	};
}

/**
 * @param {Object}        props
 * @param {Object}        props.option   The option schema entry.
 * @param {Object|string} props.value    Current value — { predefined, custom } or legacy string.
 * @param {Function}      props.onChange Called with the next { predefined, custom }.
 */
export default function PredefinedColorsCompact( { option = {}, value, onChange } ) {
	const [ open, setOpen ] = useState( false );

	const current = normalize( value );
	const choices = option.choices || {};
	const alpha = 'rgba-color-picker' === option.picker;

	const presetOptions = [ { value: '', label: '— Select —' } ].concat(
		Object.keys( choices ).map( ( key ) => ( {
			value: key,
			label: ( choices[ key ] && choices[ key ].label ) || key,
		} ) )
	);

	const presetColor =
		current.predefined && choices[ current.predefined ]
			? choices[ current.predefined ].color || ''
			: '';

	// What the element will actually show: a preset wins over a custom colour,
	// matching how the shortcode's CSS applies the class.
	const effective = presetColor || current.custom || '';

	const emit = ( next ) => onChange( Object.assign( {}, current, next ) );

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<SelectControl
				label="Preset"
				value={ current.predefined }
				options={ presetOptions }
				onChange={ ( next ) => emit( { predefined: next } ) }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			<Flex justify="flex-start" align="center" gap={ 2 } style={ { marginTop: 8 } }>
				<FlexItem>
					<Button
						variant="secondary"
						onClick={ () => setOpen( ! open ) }
						aria-expanded={ open }
						style={ {
							background: effective || 'transparent',
							border: '1px solid #949494',
							minWidth: 40,
							height: 30,
						} }
					>
						{ effective ? '' : '—' }
					</Button>
				</FlexItem>
				<FlexItem>
					<code>{ current.custom || ( presetColor ? `${ current.predefined } (preset)` : 'unset' ) }</code>
				</FlexItem>
				{ current.custom && (
					<FlexItem>
						<Button variant="tertiary" onClick={ () => emit( { custom: '' } ) }>
							{ 'Clear custom' }
						</Button>
					</FlexItem>
				) }
			</Flex>

			{ open && (
				<ColorPicker
					color={ current.custom || undefined }
					enableAlpha={ alpha }
					/*
					 * Stored verbatim — this option type casts to string without
					 * validating, so an rgba() value is legitimate when the schema
					 * asks for it. Do NOT reuse the plain color-picker's hex
					 * normaliser here; it would discard alpha the schema allows.
					 */
					onChange={ ( next ) => emit( { custom: String( next ?? '' ) } ) }
				/>
			) }
		</BaseControl>
	);
}
