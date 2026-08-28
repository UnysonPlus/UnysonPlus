/**
 * `addable-popup` option type — React renderer.
 *
 * A repeater: a list of items, each item a small set of options of its own. It
 * is the single most common structural option in the element library — slides,
 * tabs, steps, timeline entries, opening hours, hotspots and social links are
 * all this one type — so a block cannot expose most multi-part elements without
 * it.
 *
 * ## The stored value
 *
 * An ARRAY OF OBJECTS, each object mapping the popup's option ids to their
 * values. `_get_value_from_input()` accepts either real arrays or JSON strings
 * (the jQuery renderer submits strings through a hidden input); this control
 * emits arrays, which that method takes as-is.
 *
 * Two details of the PHP are mirrored here rather than assumed:
 *
 * - `limit`, when set, truncates the list on save. A control that let you add a
 *   seventh item to a list capped at six would be showing you work that is
 *   discarded the moment it is stored, so the Add button disappears at the cap.
 * - the sub-options' own `_get_value_from_input()` methods are NOT run on the
 *   item objects — PHP stores what it is given. So the values these child
 *   controls produce are the values that get stored, which is exactly why they
 *   are rendered through the same registry the rest of the sidebar uses instead
 *   of being re-implemented here.
 *
 * ## Why items expand inline instead of opening a modal
 *
 * The name says popup, and the jQuery renderer does open a modal — it has a
 * whole options panel's width to work with. A block inspector is a narrow
 * column, and a modal launched from it covers the very preview you are editing
 * against. Expanding in place keeps the canvas visible while you type, which is
 * the entire reason to edit in the block editor rather than the page builder.
 *
 * The stored value is identical either way; this is a presentation choice, and
 * it is the only one of the two that fits the space.
 */

import { get as getControl } from '../registry.js';

const { Button, Card, CardBody, Notice, BaseControl } = wp.components;
const { useState, useMemo } = wp.element;

/**
 * Flatten a declared options map, dropping container levels but keeping ids.
 *
 * `popup-options` is usually a flat map, but nothing stops an author nesting a
 * `group` or `box` in it, and the item VALUE is flat regardless — PHP stores
 * `{ title: …, url: … }` whether or not the schema wrapped those in a box. So
 * the renderer flattens to match the value, rather than rendering a shape the
 * value does not have.
 *
 * @param {Object} options Declared options map.
 * @return {Array} Array of [ id, option ] pairs, in declaration order.
 */
function flatten( options ) {
	const out = [];

	Object.keys( options || {} ).forEach( ( id ) => {
		const option = options[ id ];

		if ( ! option || typeof option !== 'object' ) {
			return;
		}

		if ( option.options ) {
			out.push( ...flatten( option.options ) );
			return;
		}

		if ( option.type ) {
			out.push( [ id, option ] );
		}
	} );

	return out;
}

/**
 * Compile an item-label template into a function.
 *
 * The schema declares labels as Underscore templates — `{{= title || "Track" }}`
 * — because that is what the jQuery renderer has always evaluated. Rather than
 * pull in a template library to read a handful of interpolations, the
 * expressions are compiled straight into a function body, which is what
 * `_.template` does anyway.
 *
 * This evaluates code from the option schema, i.e. from a theme's or plugin's
 * own PHP. That is the same trust boundary the PHP renderer already sits behind
 * — it is not, and must never become, a path for values a VISITOR supplied.
 *
 * A template that throws yields an empty label rather than breaking the
 * sidebar: a repeater you cannot use is a worse outcome than a row labelled
 * "Item 3".
 *
 * @param {string} template The template string.
 * @return {Function|null} (item) => string, or null when there is nothing to compile.
 */
function compileTemplate( template ) {
	if ( ! template || typeof template !== 'string' ) {
		return null;
	}

	const parts = [];
	const pattern = /\{\{=?([\s\S]+?)\}\}/g;
	let last = 0;
	let match;

	while ( ( match = pattern.exec( template ) ) !== null ) {
		if ( match.index > last ) {
			parts.push( JSON.stringify( template.slice( last, match.index ) ) );
		}
		parts.push( '(' + match[ 1 ] + ')' );
		last = pattern.lastIndex;
	}

	if ( last < template.length ) {
		parts.push( JSON.stringify( template.slice( last ) ) );
	}

	if ( ! parts.length ) {
		return null;
	}

	try {
		// eslint-disable-next-line no-new-func
		const fn = new Function(
			'item',
			'with ( item ) { return [' + parts.join( ',' ) + '].join( "" ); }'
		);

		return ( item ) => {
			try {
				return String( fn( item || {} ) );
			} catch ( e ) {
				return '';
			}
		};
	} catch ( e ) {
		return null;
	}
}

/**
 * The default value for a new item: every sub-option's declared default.
 *
 * @param {Array} fields Array of [ id, option ] pairs.
 * @return {Object} A fresh item.
 */
function blankItem( fields ) {
	const item = {};

	fields.forEach( ( [ id, option ] ) => {
		item[ id ] = option.value !== undefined ? option.value : '';
	} );

	return item;
}

/**
 * One field inside an expanded item.
 *
 * Mirrors index.jsx's Option, but reads the registry directly — importing that
 * component would make index.jsx and this file import each other.
 *
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {*}        props.value    Current value.
 * @param {Function} props.onChange Called with the next value.
 */
function Field( { option, value, onChange } ) {
	const Control = getControl( option.type );

	if ( ! Control ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ `No React control for "${ option.type }" — edit this item in the page builder.` }
			</Notice>
		);
	}

	return <Control option={ option } value={ value } onChange={ onChange } />;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Array}    props.value    Current array of item objects.
 * @param {Function} props.onChange Called with the next array.
 */
export default function AddablePopup( { option = {}, value, onChange } ) {
	const [ openIndex, setOpenIndex ] = useState( null );

	const fields = useMemo(
		() => flatten( option[ 'popup-options' ] ),
		[ option[ 'popup-options' ] ]
	);
	const label = useMemo( () => compileTemplate( option.template ), [ option.template ] );

	// A value may be absent, or arrive as an object keyed 0,1,2 after a JSON
	// round trip; normalise to an array without rewriting what is stored.
	const items = Array.isArray( value )
		? value
		: value && typeof value === 'object'
			? Object.values( value )
			: [];

	const limit = parseInt( option.limit, 10 ) || 0;
	const atLimit = limit > 0 && items.length >= limit;

	const replace = ( next ) => onChange( next );

	const update = ( index, id, next ) =>
		replace(
			items.map( ( item, i ) => ( i === index ? { ...item, [ id ]: next } : item ) )
		);

	const move = ( index, delta ) => {
		const target = index + delta;

		if ( target < 0 || target >= items.length ) {
			return;
		}

		const next = items.slice();
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		replace( next );
		setOpenIndex( openIndex === index ? target : openIndex );
	};

	const remove = ( index ) => {
		replace( items.filter( ( _item, i ) => i !== index ) );
		setOpenIndex( null );
	};

	const duplicate = ( index ) => {
		if ( atLimit ) {
			return;
		}

		const next = items.slice();
		next.splice( index + 1, 0, { ...items[ index ] } );
		replace( next );
	};

	const add = () => {
		replace( [ ...items, blankItem( fields ) ] );
		setOpenIndex( items.length );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div className="fw-addable-popup">
				{ items.map( ( item, index ) => {
					const text = ( label && label( item ).trim() ) || `Item ${ index + 1 }`;
					const isOpen = openIndex === index;

					return (
						<Card key={ index } size="small" style={ { marginBottom: '8px' } }>
							<CardBody style={ { padding: '8px' } }>
								<div
									style={ {
										display: 'flex',
										alignItems: 'center',
										gap: '2px',
									} }
								>
									<Button
										variant="tertiary"
										onClick={ () => setOpenIndex( isOpen ? null : index ) }
										style={ {
											flex: '1 1 auto',
											justifyContent: 'flex-start',
											minWidth: 0,
											overflow: 'hidden',
											textOverflow: 'ellipsis',
										} }
										aria-expanded={ isOpen }
									>
										{ text }
									</Button>
									<Button
										icon="arrow-up-alt2"
										size="small"
										label="Move up"
										disabled={ index === 0 }
										onClick={ () => move( index, -1 ) }
									/>
									<Button
										icon="arrow-down-alt2"
										size="small"
										label="Move down"
										disabled={ index === items.length - 1 }
										onClick={ () => move( index, 1 ) }
									/>
									<Button
										icon="admin-page"
										size="small"
										label="Duplicate"
										disabled={ atLimit }
										onClick={ () => duplicate( index ) }
									/>
									<Button
										icon="trash"
										size="small"
										isDestructive
										label="Remove"
										onClick={ () => remove( index ) }
									/>
								</div>

								{ isOpen && (
									<div style={ { marginTop: '12px' } }>
										{ fields.map( ( [ id, sub ] ) => (
											<Field
												key={ id }
												option={ sub }
												value={ item ? item[ id ] : undefined }
												onChange={ ( next ) => update( index, id, next ) }
											/>
										) ) }
									</div>
								) }
							</CardBody>
						</Card>
					);
				} ) }

				<Button
					variant="secondary"
					onClick={ add }
					disabled={ atLimit }
					__next40pxDefaultSize
				>
					{ option[ 'add-button-text' ] || 'Add' }
				</Button>

				{ atLimit && (
					<p style={ { margin: '8px 0 0', fontStyle: 'italic' } }>
						{ `Limit of ${ limit } reached.` }
					</p>
				) }
			</div>
		</BaseControl>
	);
}
