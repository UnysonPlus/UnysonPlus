/**
 * `gradient-v2` option type — React renderer.
 *
 * Stored value:
 *
 * ```
 * { type: 'linear', angle: 90, stops: [ { color: '#000', position: 0 }, … ] }
 * ```
 *
 * ## Fewer than two stops means NO gradient
 *
 * `_get_value_from_input()` drops the stops array entirely when fewer than two
 * survive validation, and stores the empty form rather than restoring a default.
 * That empty array is what turns the layer off — there is no enable switch.
 *
 * So this control never leaves a single stop behind: removing the second-to-last
 * clears the gradient outright, which is what the server would have done anyway.
 * The alternative is a picker showing one stop while the page renders nothing.
 *
 * ## What the validator accepts
 *
 * - `type` — `linear` or `radial`; anything else becomes `linear`.
 * - `angle` — integer, clamped 0–360.
 * - stop colours — hex (3 or 6) **or** `rgb()` / `rgba()`. A stop whose colour
 *   matches neither is dropped silently, so the control only ever emits those
 *   forms.
 * - stop positions — float, clamped 0–100.
 */

const { BaseControl, Button, ColorPicker, Dropdown, RangeControl, SelectControl } = wp.components;

/** Offered when a blank gradient is switched on — two stops, so it is valid. */
const STARTER = [
	{ color: '#3858e9', position: 0 },
	{ color: '#7f54b3', position: 100 },
];

/**
 * Build the CSS for a preview swatch.
 *
 * @param {Object} value The gradient value.
 * @return {string} A CSS gradient, or '' when there is none.
 */
function toCss( value ) {
	const stops = Array.isArray( value.stops ) ? value.stops : [];

	if ( stops.length < 2 ) {
		return '';
	}

	const list = [ ...stops ]
		.sort( ( a, b ) => a.position - b.position )
		.map( ( s ) => `${ s.color } ${ s.position }%` )
		.join( ', ' );

	return value.type === 'radial'
		? `radial-gradient(circle, ${ list })`
		: `linear-gradient(${ value.angle }deg, ${ list })`;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current gradient value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function GradientV2( { option = {}, value, onChange } ) {
	const current = {
		type: 'linear',
		angle: 90,
		stops: [],
		...( option.value && typeof option.value === 'object' ? option.value : {} ),
		...( value && typeof value === 'object' ? value : {} ),
	};

	const stops = Array.isArray( current.stops ) ? current.stops : [];
	const on = stops.length >= 2;

	const set = ( next ) => onChange( { ...current, ...next } );

	const setStop = ( index, patch ) =>
		set( { stops: stops.map( ( s, i ) => ( i === index ? { ...s, ...patch } : s ) ) } );

	const removeStop = ( index ) => {
		const next = stops.filter( ( _s, i ) => i !== index );

		// One stop is not a gradient. PHP would discard it on save, so discard it
		// here rather than showing a picker whose page renders nothing.
		set( { stops: next.length < 2 ? [] : next } );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div
				style={ {
					height: '32px',
					borderRadius: '4px',
					border: '1px solid #ddd',
					marginBottom: '8px',
					background: on
						? toCss( current )
						: 'repeating-linear-gradient(45deg,#f0f0f0 0 6px,#fff 6px 12px)',
				} }
				aria-hidden="true"
			/>

			{ ! on ? (
				<Button
					variant="secondary"
					onClick={ () => set( { stops: STARTER } ) }
					__next40pxDefaultSize
				>
					Add a gradient
				</Button>
			) : (
				<>
					<SelectControl
						label="Type"
						value={ current.type }
						options={ [
							{ label: 'Linear', value: 'linear' },
							{ label: 'Radial', value: 'radial' },
						] }
						onChange={ ( next ) => set( { type: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ current.type === 'linear' && (
						<RangeControl
							label="Angle"
							value={ current.angle }
							min={ 0 }
							max={ 360 }
							onChange={ ( next ) => set( { angle: Math.round( next ?? 90 ) } ) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }

					{ stops.map( ( stop, index ) => (
						<div
							key={ index }
							style={ {
								display: 'flex',
								alignItems: 'center',
								gap: '6px',
								marginTop: '8px',
							} }
						>
							<Dropdown
								renderToggle={ ( { onToggle } ) => (
									<Button
										onClick={ onToggle }
										label={ `Stop ${ index + 1 } colour` }
										showTooltip
										style={ {
											width: '28px',
											height: '28px',
											padding: 0,
											borderRadius: '3px',
											border: '1px solid #ddd',
											background: stop.color,
										} }
									/>
								) }
								renderContent={ () => (
									<ColorPicker
										color={ stop.color }
										enableAlpha
										onChange={ ( next ) => setStop( index, { color: next } ) }
									/>
								) }
							/>
							<div style={ { flex: '1 1 auto' } }>
								<RangeControl
									value={ stop.position }
									min={ 0 }
									max={ 100 }
									onChange={ ( next ) =>
										setStop( index, { position: Math.max( 0, Math.min( 100, next ?? 0 ) ) } )
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
							</div>
							<Button
								icon="trash"
								size="small"
								isDestructive
								label="Remove stop"
								onClick={ () => removeStop( index ) }
							/>
						</div>
					) ) }

					<Button
						variant="tertiary"
						onClick={ () =>
							set( {
								stops: [ ...stops, { color: '#ffffff', position: 100 } ],
							} )
						}
						style={ { marginTop: '8px' } }
					>
						Add stop
					</Button>
				</>
			) }
		</BaseControl>
	);
}
