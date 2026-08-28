/**
 * `split-slider` option type — React renderer.
 *
 * Divides a row into panes. The stored value is an array of segments whose
 * widths sum to 100:
 *
 * ```
 * [ { w: 60, name: '' }, { w: 40, name: '' } ]
 * ```
 *
 * …or an **empty array**, which is not "no value" but a meaningful one: AUTO,
 * meaning equal columns. `_get_value_from_input()` returns `array()` for an
 * empty input and never normalises it into explicit halves, so the control must
 * keep the distinction rather than helpfully filling it in.
 *
 * ## The control normalises, because the server would
 *
 * `normalize()` proportionally scales the widths to sum 100, clamps each to
 * `min_width`, and clamps the segment count to `min`/`max`. On the page-builder
 * path that happens on save; on the block path nothing runs at all.
 *
 * So this control normalises as you type. Both paths then hold the same shape,
 * and — more usefully — the numbers in the sidebar are the numbers that render,
 * rather than a set of inputs whose relationship to the result is a mystery
 * until the page reloads.
 *
 * The drag-handle UI the page builder offers is not reproduced here: a
 * three-handle splitter inside a 280px column is fiddlier than typing a number,
 * and dragging in a block sidebar competes with the editor's own gestures.
 */

const { BaseControl, Button, TextControl, ToggleControl } = wp.components;

/**
 * Scale widths to sum 100 and honour `min_width`, mirroring PHP's normalize().
 *
 * @param {Array}  segs   Segments, each { w, name }.
 * @param {Object} option The option schema entry.
 * @return {Array} Normalised segments.
 */
function normalize( segs, option ) {
	const minWidth = Math.max( 1, parseInt( option.min_width, 10 ) || 10 );
	const n = segs.length;

	if ( ! n ) {
		return [];
	}

	let sum = segs.reduce( ( acc, s ) => acc + Math.max( 0, Number( s.w ) || 0 ), 0 );

	// All-zero input divides evenly rather than dividing by zero.
	if ( sum <= 0 ) {
		const each = Math.floor( 100 / n );

		return segs.map( ( s, i ) => ( {
			...s,
			w: i === n - 1 ? 100 - each * ( n - 1 ) : each,
		} ) );
	}

	const scaled = segs.map( ( s ) => ( {
		...s,
		w: Math.max( minWidth, Math.round( ( Math.max( 0, Number( s.w ) || 0 ) / sum ) * 100 ) ),
	} ) );

	// Rounding and the min_width floor both push the total off 100; settle the
	// remainder on the widest segment, where a point or two is least visible.
	const total = scaled.reduce( ( acc, s ) => acc + s.w, 0 );
	const drift = 100 - total;

	if ( drift !== 0 ) {
		let widest = 0;

		scaled.forEach( ( s, i ) => {
			if ( s.w > scaled[ widest ].w ) {
				widest = i;
			}
		} );

		scaled[ widest ] = {
			...scaled[ widest ],
			w: Math.max( minWidth, scaled[ widest ].w + drift ),
		};
	}

	return scaled;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Array}    props.value    Current segments, or [] for AUTO.
 * @param {Function} props.onChange Called with the next segments.
 */
export default function SplitSlider( { option = {}, value, onChange } ) {
	const segs = Array.isArray( value ) ? value : [];
	const isAuto = segs.length === 0;

	const min = Math.max( 1, parseInt( option.min, 10 ) || 1 );
	const max = Math.max( min, parseInt( option.max, 10 ) || 5 );
	const autoCount = Math.min( max, Math.max( min, parseInt( option.auto_count, 10 ) || 3 ) );

	const set = ( next ) => onChange( normalize( next, option ) );

	const toggleAuto = ( auto ) => {
		if ( auto ) {
			onChange( [] );
			return;
		}

		// Leaving AUTO materialises the equal split it was showing, so the first
		// explicit value matches what was on screen a moment earlier.
		set( Array.from( { length: autoCount }, () => ( { w: 0, name: '' } ) ) );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<ToggleControl
				label="Equal columns"
				checked={ isAuto }
				onChange={ toggleAuto }
				__nextHasNoMarginBottom
			/>

			{ ! isAuto && (
				<>
					<div
						style={ {
							display: 'flex',
							gap: '2px',
							height: '14px',
							margin: '8px 0',
						} }
						aria-hidden="true"
					>
						{ segs.map( ( s, i ) => (
							<span
								key={ i }
								style={ {
									width: `${ s.w }%`,
									background:
										i % 2 ? '#c3c4c7' : 'var(--wp-admin-theme-color)',
									borderRadius: '2px',
								} }
							/>
						) ) }
					</div>

					{ segs.map( ( s, i ) => (
						<div
							key={ i }
							style={ { display: 'flex', gap: '6px', alignItems: 'flex-end' } }
						>
							<TextControl
								type="number"
								label={ `Pane ${ i + 1 } (%)` }
								value={ String( s.w ) }
								onChange={ ( next ) =>
									set(
										segs.map( ( seg, j ) =>
											j === i ? { ...seg, w: parseFloat( next ) || 0 } : seg
										)
									)
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							{ option.allow_names !== false && (
								<TextControl
									label="Name"
									value={ s.name || '' }
									onChange={ ( next ) =>
										onChange(
											segs.map( ( seg, j ) =>
												j === i ? { ...seg, name: next } : seg
											)
										)
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
							) }
							{ ! option.locked && segs.length > min && (
								<Button
									icon="trash"
									size="small"
									isDestructive
									label="Remove pane"
									onClick={ () => set( segs.filter( ( _s, j ) => j !== i ) ) }
								/>
							) }
						</div>
					) ) }

					{ ! option.locked && segs.length < max && (
						<Button
							variant="secondary"
							onClick={ () => set( [ ...segs, { w: 0, name: '' } ] ) }
							style={ { marginTop: '8px' } }
							__next40pxDefaultSize
						>
							Add pane
						</Button>
					) }
				</>
			) }
		</BaseControl>
	);
}
