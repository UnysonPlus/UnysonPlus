/**
 * `table` option type — React renderer.
 *
 * A tabular editor. The stored value:
 *
 * ```
 * {
 *   header_options: { table_purpose: 'tabular', header_rows: 1, footer_rows: 0 },
 *   cols:    [ { name, align, width }, … ],
 *   rows:    [ { name: 'heading-row' | 'default-row' }, … ],
 *   content: [ [ { textarea, colspan, rowspan, merged }, … ], … ]
 * }
 * ```
 *
 * ## This control emits the STORED shape, not the wire format
 *
 * The page builder's JS submits a JSON blob under `__json`, and that is the only
 * input `_get_value_from_input()` accepts for a tabular table — feed it the
 * stored shape and it falls through to the legacy pricing branch and returns
 * EMPTY CELLS.
 *
 * A block emits the stored shape anyway, for two reasons: nothing validates on
 * the block path, and the element's view reads that shape directly. The two
 * surfaces coexist because the builder's editor boots from the stored model and
 * re-serialises to `__json` when it saves — so a table authored in a block opens
 * and saves correctly there.
 *
 * What would break is anything feeding a stored value back through the
 * validator. That is asserted in the parity suite, so the asymmetry is visible
 * rather than folklore.
 *
 * Four parallel structures that have to agree: `content` is rows × columns,
 * `cols` must be exactly as long as every content row, and `rows` must be as
 * long as `content`. Adding a column means touching three of them. Every
 * mutation below rebuilds the whole value for that reason — patching one array
 * and forgetting another is how a table ends up with cells it cannot render.
 *
 * ## `rows[i].name` is derived, not chosen
 *
 * A row is `heading-row` when its index falls inside `header_rows`, and
 * `default-row` otherwise — `get_value_from_json()` derives it exactly that way
 * so the renderer gets a real `<thead>`. It is not an independent setting, so
 * this control derives it too rather than offering it.
 *
 * ## Merged cells are preserved, not edited
 *
 * `colspan`, `rowspan` and `merged` round-trip untouched: a cell object is
 * spread when its text changes, never rebuilt. Merging itself stays in the page
 * builder — choosing a span across a grid needs to be done on a grid, and a
 * sidebar column is not one. A table merged there and edited here keeps its
 * merges.
 */

const { BaseControl, Button, TextControl, TextareaControl, SelectControl } = wp.components;

const ALIGNS = [
	{ value: '', label: 'Default' },
	{ value: 'left', label: 'Left' },
	{ value: 'center', label: 'Center' },
	{ value: 'right', label: 'Right' },
];

/** A fresh cell, in the shape get_value_from_json() produces. */
const blankCell = () => ( { textarea: '', colspan: 1, rowspan: 1, merged: false } );

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current table value.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Table( { option = {}, value, onChange } ) {
	const v = value && typeof value === 'object' ? value : {};

	const header = {
		table_purpose: 'tabular',
		header_rows: 0,
		footer_rows: 0,
		...( v.header_options || {} ),
	};

	const cols = Array.isArray( v.cols ) && v.cols.length
		? v.cols
		: [ { name: 'default-col', align: '', width: '' } ];

	const content = Array.isArray( v.content ) && v.content.length
		? v.content
		: [ cols.map( blankCell ) ];

	/**
	 * Rebuild the whole value so the four structures cannot drift apart.
	 *
	 * @param {Array}  nextCols    Columns.
	 * @param {Array}  nextContent Rows of cells.
	 * @param {Object} nextHeader  Header options.
	 * @return {void}
	 */
	const commit = ( nextCols, nextContent, nextHeader = header ) => {
		const headerRows = Math.min( Math.max( 0, nextHeader.header_rows ), nextContent.length );
		const footerRows = Math.min(
			Math.max( 0, nextHeader.footer_rows ),
			Math.max( 0, nextContent.length - headerRows )
		);

		onChange( {
			header_options: {
				table_purpose: nextHeader.table_purpose === 'pricing' ? 'pricing' : 'tabular',
				header_rows: headerRows,
				footer_rows: footerRows,
			},
			cols: nextCols,
			// Derived from position, exactly as the PHP derives it.
			rows: nextContent.map( ( _row, i ) => ( {
				name: i < headerRows ? 'heading-row' : 'default-row',
			} ) ),
			content: nextContent.map( ( row ) =>
				nextCols.map( ( _c, ci ) => row[ ci ] || blankCell() )
			),
		} );
	};

	const setCell = ( ri, ci, text ) =>
		commit(
			cols,
			content.map( ( row, r ) =>
				r !== ri
					? row
					: row.map( ( cell, c ) =>
							// Spread, never rebuild: colspan / rowspan / merged survive.
							c === ci ? { ...( cell || blankCell() ), textarea: text } : cell
					  )
			)
		);

	const addRow = () => commit( cols, [ ...content, cols.map( blankCell ) ] );

	const removeRow = ( ri ) =>
		commit( cols, content.filter( ( _r, i ) => i !== ri ) );

	const addCol = () =>
		commit(
			[ ...cols, { name: 'default-col', align: '', width: '' } ],
			content.map( ( row ) => [ ...row, blankCell() ] )
		);

	const removeCol = ( ci ) =>
		commit(
			cols.filter( ( _c, i ) => i !== ci ),
			content.map( ( row ) => row.filter( ( _c, i ) => i !== ci ) )
		);

	const setCol = ( ci, patch ) =>
		commit( cols.map( ( c, i ) => ( i === ci ? { ...c, ...patch } : c ) ), content );

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div style={ { display: 'flex', gap: '8px' } }>
				<TextControl
					type="number"
					label="Header rows"
					min={ 0 }
					value={ String( header.header_rows ) }
					onChange={ ( next ) =>
						commit( cols, content, {
							...header,
							header_rows: parseInt( next, 10 ) || 0,
						} )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<TextControl
					type="number"
					label="Footer rows"
					min={ 0 }
					value={ String( header.footer_rows ) }
					onChange={ ( next ) =>
						commit( cols, content, {
							...header,
							footer_rows: parseInt( next, 10 ) || 0,
						} )
					}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</div>

			{ cols.map( ( col, ci ) => (
				<div
					key={ ci }
					style={ {
						display: 'flex',
						gap: '6px',
						alignItems: 'flex-end',
						marginTop: '8px',
					} }
				>
					<SelectControl
						label={ `Column ${ ci + 1 }` }
						value={ col.align || '' }
						options={ ALIGNS }
						onChange={ ( next ) => setCol( ci, { align: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label="Width"
						value={ col.width || '' }
						onChange={ ( next ) => setCol( ci, { width: next } ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ cols.length > 1 && (
						<Button
							icon="trash"
							size="small"
							isDestructive
							label={ `Remove column ${ ci + 1 }` }
							onClick={ () => removeCol( ci ) }
						/>
					) }
				</div>
			) ) }

			<div style={ { marginTop: '12px' } }>
				{ content.map( ( row, ri ) => (
					<div key={ ri } style={ { marginBottom: '10px' } }>
						<div
							style={ {
								display: 'flex',
								justifyContent: 'space-between',
								alignItems: 'center',
								fontSize: '11px',
								opacity: 0.75,
							} }
						>
							<span>
								{ ri < header.header_rows
									? `Header row ${ ri + 1 }`
									: `Row ${ ri + 1 }` }
							</span>
							{ content.length > 1 && (
								<Button
									icon="trash"
									size="small"
									isDestructive
									label={ `Remove row ${ ri + 1 }` }
									onClick={ () => removeRow( ri ) }
								/>
							) }
						</div>

						{ cols.map( ( _col, ci ) => {
							const cell = row[ ci ] || blankCell();

							// A merged cell is rendered by its origin; editing it here would
							// write text nothing displays.
							if ( cell.merged ) {
								return (
									<p
										key={ ci }
										style={ { margin: '2px 0', fontSize: '11px', fontStyle: 'italic', opacity: 0.6 } }
									>
										{ `Column ${ ci + 1 }: merged` }
									</p>
								);
							}

							return (
								<TextareaControl
									key={ ci }
									label={ `Column ${ ci + 1 }` }
									value={ cell.textarea || '' }
									rows={ 2 }
									onChange={ ( next ) => setCell( ri, ci, next ) }
									__nextHasNoMarginBottom
								/>
							);
						} ) }
					</div>
				) ) }
			</div>

			<div style={ { display: 'flex', gap: '6px' } }>
				<Button variant="secondary" onClick={ addRow } __next40pxDefaultSize>
					Add row
				</Button>
				<Button variant="secondary" onClick={ addCol } __next40pxDefaultSize>
					Add column
				</Button>
			</div>
		</BaseControl>
	);
}
