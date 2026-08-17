/**
 * `typography` option type — React renderer. Also serves `typography-v2`, which
 * is a pure deprecation alias (it overrides only get_type()).
 *
 * ## Most of this value is DERIVED, not submitted
 *
 * `_get_value_from_input()` merges defaults + the option's own value + the input,
 * and then rewrites several keys itself:
 *
 *   - `family === false`  -> google_font / style / weight / subset / variation all false
 *   - family is a Google font -> google_font = true, style = false, weight = false
 *   - otherwise           -> google_font = false, subset = false, variation = false
 *
 * So this control edits the fields a user actually sets and lets the server derive
 * the rest. Emitting a guess for `google_font` would simply be overwritten — and
 * guessing wrong in the other direction (claiming a Google font that is not one)
 * would be silently corrected, which is worse than not guessing.
 *
 * ## Two format switches change field TYPES
 *
 *   - `size_format: 'unit'` (default) stores `{ value, unit }`; `'number'` stores a
 *     bare pixel number. A consumer that feeds size into JS needs the number form,
 *     so the two are not interchangeable.
 *   - `color_format: 'picker'` stores a hex string.
 *
 * ## Colour here is stricter than the color-picker option type
 *
 * Typography validates with `/^#([a-f0-9]{3}){1,2}$/i` — 3 or 6 digits ONLY. The
 * 4- and 8-digit alpha forms that `color-picker` accepts are rejected here and
 * replaced with the option default, so this control never offers alpha.
 *
 * ## `components` gates which fields exist
 *
 * A component switched off is stored as `false`, not omitted. Rendering a field
 * the schema disabled would offer an edit the server discards.
 */

const { BaseControl, TextControl, SelectControl, ColorPicker, Button, Flex, FlexItem,
	__experimentalNumberControl: NumberControl } = wp.components;
const { useState } = wp.element;

/** Mirrors the PHP defaults' `components` map. */
const ALL_COMPONENTS = [ 'family', 'size', 'line-height', 'letter-spacing', 'color', 'weight', 'style', 'variation', 'subset' ];

const WEIGHTS = [ '100', '200', '300', '400', '500', '600', '700', '800', '900' ];
const STYLES = [ 'normal', 'italic', 'oblique' ];
const SIZE_UNITS = [ 'px', 'rem', 'em' ];

/** Web-safe families, offered as suggestions. Any string is valid — see the docs. */
const COMMON_FAMILIES = [
	'Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Courier New',
	'Verdana', 'Tahoma', 'Trebuchet MS', 'Palatino', 'Garamond',
];

/** Typography accepts 3- or 6-digit hex only — no alpha. */
function toPlainHex( raw ) {
	const input = String( raw ?? '' ).trim();

	if ( ! input ) {
		return '';
	}

	if ( /^#([a-f0-9]{3}|[a-f0-9]{6})$/i.test( input ) ) {
		return input;
	}

	// Drop an alpha channel rather than emit a value the server will reject.
	const eight = input.match( /^#([a-f0-9]{6})[a-f0-9]{2}$/i );
	if ( eight ) {
		return '#' + eight[ 1 ];
	}

	const m = input.match( /^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i );
	if ( ! m ) {
		return '';
	}

	const hx = ( n ) =>
		Math.max( 0, Math.min( 255, Math.round( Number( n ) ) ) ).toString( 16 ).padStart( 2, '0' );

	return `#${ hx( m[ 1 ] ) }${ hx( m[ 2 ] ) }${ hx( m[ 3 ] ) }`;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Typography( { option = {}, value, onChange } ) {
	const [ pickerOpen, setPickerOpen ] = useState( false );

	const components = Object.assign(
		ALL_COMPONENTS.reduce( ( acc, k ) => Object.assign( acc, { [ k ]: true } ), {} ),
		option.components || {}
	);

	const sizeFormat = 'number' === option.size_format ? 'number' : 'unit';
	const current = value && typeof value === 'object' ? value : ( option.value || {} );

	const on = ( key ) => !! components[ key ];

	/** Emit the whole value with one key replaced; the server derives the rest. */
	const set = ( key, next ) => onChange( Object.assign( {}, current, { [ key ]: next } ) );

	// Size is either { value, unit } or a bare number, depending on size_format.
	const size = current.size;
	const sizeNum =
		size && typeof size === 'object' ? String( size.value ?? '' ) : String( size ?? '' );
	const sizeUnit = size && typeof size === 'object' ? size.unit || 'px' : 'px';

	const setSize = ( num, unit ) =>
		set( 'size', 'unit' === sizeFormat ? { value: String( num ?? '' ), unit } : Number( num ) || 0 );

	const colorValue = 'string' === typeof current.color ? current.color : '';

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ on( 'family' ) && (
				<>
					<TextControl
						label="Font family"
						value={ 'string' === typeof current.family ? current.family : '' }
						list="fw-typography-families"
						onChange={ ( next ) => set( 'family', next ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<datalist id="fw-typography-families">
						{ COMMON_FAMILIES.map( ( f ) => (
							<option key={ f } value={ f } />
						) ) }
					</datalist>
				</>
			) }

			{ on( 'size' ) && (
				<Flex align="flex-end" gap={ 2 } style={ { marginTop: 8 } }>
					<FlexItem isBlock>
						<NumberControl
							label="Size"
							value={ sizeNum }
							onChange={ ( next ) => setSize( next, sizeUnit ) }
							__next40pxDefaultSize
						/>
					</FlexItem>
					{ 'unit' === sizeFormat && (
						<FlexItem>
							<SelectControl
								value={ sizeUnit }
								options={ SIZE_UNITS.map( ( u ) => ( { value: u, label: u } ) ) }
								onChange={ ( next ) => setSize( sizeNum, next ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</FlexItem>
					) }
				</Flex>
			) }

			{ on( 'line-height' ) && (
				<NumberControl
					label="Line height"
					value={ String( current[ 'line-height' ] ?? '' ) }
					onChange={ ( next ) => set( 'line-height', Number( next ) || 0 ) }
					__next40pxDefaultSize
				/>
			) }

			{ on( 'letter-spacing' ) && (
				<NumberControl
					label="Letter spacing"
					value={ String( current[ 'letter-spacing' ] ?? '' ) }
					onChange={ ( next ) => set( 'letter-spacing', Number( next ) || 0 ) }
					__next40pxDefaultSize
				/>
			) }

			{ on( 'weight' ) && false !== current.weight && (
				<SelectControl
					label="Weight"
					value={ String( current.weight ?? '400' ) }
					options={ WEIGHTS.map( ( w ) => ( { value: w, label: w } ) ) }
					onChange={ ( next ) => set( 'weight', next ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ on( 'style' ) && false !== current.style && (
				<SelectControl
					label="Style"
					value={ String( current.style ?? 'normal' ) }
					options={ STYLES.map( ( s ) => ( { value: s, label: s } ) ) }
					onChange={ ( next ) => set( 'style', next ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ on( 'color' ) && (
				<div style={ { marginTop: 8 } }>
					<Flex justify="flex-start" align="center" gap={ 2 }>
						<FlexItem>
							<Button
								variant="secondary"
								onClick={ () => setPickerOpen( ! pickerOpen ) }
								style={ {
									background: colorValue || 'transparent',
									border: '1px solid #949494',
									minWidth: 40,
									height: 30,
								} }
							>
								{ colorValue ? '' : '—' }
							</Button>
						</FlexItem>
						<FlexItem>
							<code>{ colorValue || 'unset' }</code>
						</FlexItem>
					</Flex>
					{ pickerOpen && (
						<ColorPicker
							color={ colorValue || undefined }
							enableAlpha={ false }
							onChange={ ( next ) => set( 'color', toPlainHex( next ) ) }
						/>
					) }
				</div>
			) }
		</BaseControl>
	);
}
