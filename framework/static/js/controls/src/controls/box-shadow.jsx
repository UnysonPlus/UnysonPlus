/**
 * `box-shadow` option type — React renderer.
 *
 * The stored value is a fixed shape:
 *
 * ```
 * { x: 0, y: 4, blur: 12, spread: 0, color: 'rgba(0,0,0,.15)', inset: false }
 * ```
 *
 * The offsets are INTEGERS — `sanitize()` runs `(int) round( (float) $v )` on
 * each — while `color` is a free string and `inset` a real boolean. A control
 * emitting `'4'` where the option stores `4` would round-trip to a different
 * value than the page builder saves for the same shadow.
 *
 * `blur` and `spread` are clamped at a minimum of 0; negative offsets are
 * legitimate for `x` and `y`, which is why only two of the four have a floor.
 */

const { BaseControl, TextControl, ToggleControl, ColorPicker, Dropdown, Button } = wp.components;

/** Mirrors FW_Option_Type_Box_Shadow::_get_defaults(). */
const DEFAULTS = { x: 0, y: 0, blur: 0, spread: 0, color: '', inset: false };

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current shadow.
 * @param {Function} props.onChange Called with the next shadow.
 */
export default function BoxShadow( { option = {}, value, onChange } ) {
	const current = { ...DEFAULTS, ...( option.value || {} ), ...( value || {} ) };

	const setNumber = ( key, next, min ) => {
		const n = Math.round( parseFloat( next ) || 0 );

		onChange( { ...current, [ key ]: min !== undefined ? Math.max( min, n ) : n } );
	};

	const preview = `${ current.inset ? 'inset ' : '' }${ current.x }px ${ current.y }px ${ current.blur }px ${ current.spread }px ${ current.color || 'rgba(0,0,0,.2)' }`;

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ /*
			  * A sample box carrying the shadow itself. Four numbers describing a
			  * shadow are almost impossible to picture; one look at the result is
			  * worth more than any of them.
			  */ }
			<div
				style={ {
					height: '44px',
					margin: '0 6px 10px',
					borderRadius: '4px',
					background: '#fff',
					border: '1px solid #f0f0f0',
					boxShadow: preview,
				} }
				aria-hidden="true"
			/>

			<div style={ { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px' } }>
				<TextControl
					type="number"
					label="X"
					value={ String( current.x ) }
					onChange={ ( next ) => setNumber( 'x', next ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					type="number"
					label="Y"
					value={ String( current.y ) }
					onChange={ ( next ) => setNumber( 'y', next ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					type="number"
					label="Blur"
					min={ 0 }
					value={ String( current.blur ) }
					onChange={ ( next ) => setNumber( 'blur', next, 0 ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					type="number"
					label="Spread"
					min={ 0 }
					value={ String( current.spread ) }
					onChange={ ( next ) => setNumber( 'spread', next, 0 ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</div>

			<div style={ { marginTop: '8px' } }>
				<Dropdown
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							variant="secondary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
							__next40pxDefaultSize
						>
							<span
								style={ {
									display: 'inline-block',
									width: '14px',
									height: '14px',
									marginRight: '8px',
									borderRadius: '2px',
									border: '1px solid #ddd',
									background: current.color || 'transparent',
								} }
								aria-hidden="true"
							/>
							Shadow colour
						</Button>
					) }
					renderContent={ () => (
						<ColorPicker
							color={ current.color || undefined }
							enableAlpha
							// Alpha is the whole point of a shadow colour, so the value is
							// kept as the string the picker produces rather than normalised
							// to hex — this option type stores a free string, unlike the
							// colour pickers that accept hex only.
							onChange={ ( next ) => onChange( { ...current, color: next } ) }
						/>
					) }
				/>
			</div>

			<ToggleControl
				label="Inset"
				checked={ Boolean( current.inset ) }
				onChange={ ( next ) => onChange( { ...current, inset: next } ) }
				__nextHasNoMarginBottom
			/>
		</BaseControl>
	);
}
