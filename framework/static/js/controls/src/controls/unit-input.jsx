/**
 * `unit-input` option type — React renderer.
 *
 * ## The value is a structured pair, and its number is a STRING
 *
 * Stored shape is `{ value: '12', unit: 'px' }`. The number half is deliberately a
 * string, not a number: `_get_value_from_input()` keeps `''` for "unset" and only
 * accepts blank-or-numeric, so `''` and `0` stay distinguishable. Emitting a real
 * number here would make an empty field save as `0` and silently apply a `0px`
 * where the option meant "inherit".
 *
 * ## Units may be declared two ways
 *
 * `units` is either a sequential list (`['px','em']`) or a map of value → label
 * (`['px' => 'PX']`). PHP normalises both through `normalize_units()`; the same
 * normalisation is reproduced here so the dropdown offers exactly the units the
 * server will accept. A unit outside that set is rejected server-side and replaced
 * with the first configured one, so offering a wider list would silently rewrite
 * the user's choice on save.
 */

const { __experimentalNumberControl: NumberControl, SelectControl, BaseControl, Flex, FlexItem } = wp.components;

/** Mirrors the PHP fallback in FW_Option_Type_Unit_Input::normalize_units(). */
const DEFAULT_UNITS = [ 'px', 'em', 'rem' ];

/**
 * Normalise the schema's `units` into [{ value, label }], matching normalize_units().
 *
 * @param {Array|Object} units Declared units.
 * @return {Array} Normalised unit options.
 */
function normalizeUnits( units ) {
	const source =
		units && ( Array.isArray( units ) ? units.length : Object.keys( units ).length )
			? units
			: DEFAULT_UNITS;

	const out = [];

	if ( Array.isArray( source ) ) {
		source.forEach( ( entry ) => {
			const value = String( entry ).trim();
			if ( value ) {
				out.push( { value, label: value } );
			}
		} );
	} else {
		Object.keys( source ).forEach( ( key ) => {
			const value = String( key ).trim();
			if ( value ) {
				const label = String( source[ key ] ?? '' ).trim();
				out.push( { value, label: label || value } );
			}
		} );
	}

	return out.length ? out : DEFAULT_UNITS.map( ( u ) => ( { value: u, label: u } ) );
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current value — { value, unit }.
 * @param {Function} props.onChange Called with the next { value, unit } pair.
 */
export default function UnitInput( { option = {}, value, onChange } ) {
	const units = normalizeUnits( option.units );

	const fallback =
		option.value && typeof option.value === 'object'
			? option.value
			: { value: '', unit: units[ 0 ].value };

	const current = value && typeof value === 'object' ? value : fallback;

	// Keep the number as a string, and only accept blank-or-numeric — the same
	// rule the server applies, so what is typed is what is stored.
	const num = current.value === undefined || current.value === null ? '' : String( current.value );

	// A unit outside the configured set would be rewritten server-side; show the
	// one that will actually be saved.
	const unit = units.some( ( u ) => u.value === current.unit ) ? current.unit : units[ 0 ].value;

	const emit = ( nextNum, nextUnit ) => onChange( { value: nextNum, unit: nextUnit } );

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<Flex align="flex-end" gap={ 2 }>
				<FlexItem isBlock>
					<NumberControl
						value={ num }
						min={ option.min !== '' && option.min !== undefined ? Number( option.min ) : undefined }
						max={ option.max !== '' && option.max !== undefined ? Number( option.max ) : undefined }
						step={ option.step !== '' && option.step !== undefined ? Number( option.step ) : undefined }
						onChange={ ( next ) => {
							const raw = next === undefined || next === null ? '' : String( next ).trim();
							emit( raw === '' || ! isNaN( Number( raw ) ) ? raw : '', unit );
						} }
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem>
					<SelectControl
						value={ unit }
						options={ units }
						onChange={ ( nextUnit ) => emit( num, nextUnit ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</FlexItem>
			</Flex>
		</BaseControl>
	);
}
