/**
 * `column-split` option type — React renderer.
 *
 * Picks how two panes divide a row: `'1/2'`, `'1/3'`, `'5/12'`. The stored value
 * is that fraction string, always normalised to one of the allowed fractions.
 *
 * ## The control only offers allowed fractions, so normalisation never surprises
 *
 * `_get_value_from_input()` runs `normalize()` on everything: a fraction outside
 * the allowed set is SNAPPED to the nearest allowed one by ratio, not rejected.
 * That is sensible for a slider drag and unhelpful as a surprise — pick `2/5`
 * where the schema allows twelfths and you would silently get `5/12`.
 *
 * So this control renders exactly the allowed set as buttons. Nothing you can
 * choose here needs snapping, which means what the sidebar shows after a save is
 * what you clicked.
 *
 * The allowed set mirrors `allowed_fractions()`: the schema's `fractions` list
 * when given, otherwise twelfths `1/12`…`11/12`, sorted by ratio.
 */

const { BaseControl, Button } = wp.components;
const { useMemo } = wp.element;

/**
 * Greatest common divisor, for reducing to lowest terms.
 *
 * @param {number} a First number.
 * @param {number} b Second number.
 * @return {number} The GCD.
 */
function gcd( a, b ) {
	return b ? gcd( b, a % b ) : a;
}

/**
 * Parse "n/d" into a REDUCED [ n, d ], mirroring PHP's parse_fraction().
 *
 * The reduction is not tidiness — it is the stored shape. PHP reduces every
 * fraction to lowest terms, so the allowed set is `1/2`, `1/3`, `5/12`, never
 * `6/12` or `4/12`. A control that offered the unreduced forms would have every
 * such click silently rewritten on save: pick `6/12`, reopen, find `1/2`.
 *
 * That is exactly the surprise this control exists to avoid, and it is what the
 * parity suite caught when this function did not reduce.
 *
 * @param {*} raw The value to parse.
 * @return {Array|null} [ numerator, denominator ] in lowest terms, or null.
 */
function parseFraction( raw ) {
	const m = String( raw ?? '' ).match( /^\s*(\d+)\s*\/\s*(\d+)\s*$/ );

	if ( ! m ) {
		return null;
	}

	const n = parseInt( m[ 1 ], 10 );
	const d = parseInt( m[ 2 ], 10 );

	if ( ! ( d > 0 && n > 0 && n < d ) ) {
		return null;
	}

	const g = gcd( n, d );

	return [ n / g, d / g ];
}

/**
 * The allowed fractions, in ascending ratio order.
 *
 * @param {Object} option The option schema entry.
 * @return {Array} Fraction strings.
 */
function allowedFractions( option ) {
	const raw =
		Array.isArray( option.fractions ) && option.fractions.length
			? option.fractions
			: Array.from( { length: 11 }, ( _v, i ) => `${ i + 1 }/12` );

	const seen = new Map();

	raw.forEach( ( f ) => {
		const r = parseFraction( f );

		if ( r ) {
			seen.set( `${ r[ 0 ] }/${ r[ 1 ] }`, r[ 0 ] / r[ 1 ] );
		}
	} );

	return [ ...seen.entries() ].sort( ( a, b ) => a[ 1 ] - b[ 1 ] ).map( ( e ) => e[ 0 ] );
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current fraction, e.g. '1/2'.
 * @param {Function} props.onChange Called with the next fraction string.
 */
export default function ColumnSplit( { option = {}, value, onChange } ) {
	const allowed = useMemo( () => allowedFractions( option ), [ option.fractions ] );

	// Reduce the incoming value before matching: a value saved before the schema
	// changed, or written by hand, may be in unreduced form.
	const reduced = parseFraction( value );
	const key = reduced ? `${ reduced[ 0 ] }/${ reduced[ 1 ] }` : null;
	const current = key && allowed.includes( key ) ? key : option.value || '1/2';

	const panes = Array.isArray( option.panes ) ? option.panes : [];
	const leftLabel = panes[ 0 ] && panes[ 0 ].label ? panes[ 0 ].label : 'Left';
	const rightLabel = panes[ 1 ] && panes[ 1 ].label ? panes[ 1 ].label : 'Right';

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || `${ leftLabel } / ${ rightLabel }` }
			__nextHasNoMarginBottom
		>
			<div style={ { display: 'flex', flexWrap: 'wrap', gap: '4px' } }>
				{ allowed.map( ( frac ) => {
					const [ n, d ] = frac.split( '/' ).map( Number );
					const pct = ( n / d ) * 100;
					const selected = frac === current;

					return (
						<Button
							key={ frac }
							onClick={ () => onChange( frac ) }
							aria-pressed={ selected }
							label={ `${ leftLabel } ${ frac }` }
							showTooltip
							style={ {
								display: 'block',
								height: 'auto',
								padding: '4px',
								borderRadius: '4px',
								boxShadow: selected
									? '0 0 0 2px var(--wp-admin-theme-color)'
									: 'inset 0 0 0 1px #ddd',
							} }
						>
							{ /*
							  * A miniature of the split itself. Two bars in proportion say
							  * more at a glance than "5/12" does, and the fraction is still
							  * printed beneath for anyone matching it to a design.
							  */ }
							<span
								style={ {
									display: 'flex',
									gap: '2px',
									width: '46px',
									height: '14px',
								} }
								aria-hidden="true"
							>
								<span
									style={ {
										width: `${ pct }%`,
										background: 'var(--wp-admin-theme-color)',
										borderRadius: '2px',
										opacity: 0.85,
									} }
								/>
								<span
									style={ {
										width: `${ 100 - pct }%`,
										background: '#c3c4c7',
										borderRadius: '2px',
									} }
								/>
							</span>
							{ option.show_fraction !== false && (
								<span
									style={ {
										display: 'block',
										marginTop: '2px',
										fontSize: '10px',
										lineHeight: 1.2,
										textAlign: 'center',
									} }
								>
									{ frac }
								</span>
							) }
						</Button>
					);
				} ) }
			</div>
		</BaseControl>
	);
}
