/**
 * `form-builder` option type — React renderer.
 *
 * The form's fields. The stored value is the base builder's shape — a JSON
 * STRING under a single key:
 *
 * ```
 * { json: '[{"type":"text","shortcode":"text_a1b2c3d","width":"","options":{"label":"Name", … }}]' }
 * ```
 *
 * Note where the field's settings live: under an **`options`** key on the item,
 * NOT flat on it. The item view reads `$item['options']['label']`, and a flat
 * item renders a field with no label and warns on every access. That is not
 * guessable from the option schema — it comes from the item views and from the
 * default form the contact-form element seeds — and rendering one was the only
 * way to find it.
 *
 * Note the double encoding: the value is an object whose `json` member is a
 * string. `_get_value_from_input()` decodes it, validates each item through its
 * type class, and re-encodes. Blocks never reach that, so this control does the
 * encoding itself — and everything below exists to make what it encodes the same
 * thing PHP would have produced.
 *
 * ## Every item needs a UNIQUE `shortcode`
 *
 * This is the part that would silently lose data if it were wrong.
 *
 * `shortcode` is the field's identifier: submitted values are keyed by it.
 * `get_value_from_items()` regenerates it whenever it is missing OR duplicated,
 * as `sanitize_key( type_with_underscores . '_' . 7 hex )`. On the block path
 * nothing regenerates anything, so two fields sharing a shortcode would collide
 * and one field's submissions would vanish — with no error, on a form, which is
 * the worst place for a silent failure.
 *
 * So new fields are minted with a unique shortcode in the same format, and the
 * control refuses to duplicate one when a field is cloned.
 *
 * ## Item schemas come from PHP
 *
 * Each item type's options live in a PHP class, not in the option array, so the
 * bridge attaches them as `item_types` — see enrich_option(). The per-field
 * editor renders whichever of them exist through the shared control registry, so
 * a text field offers its constraints and a select offers its choices, without
 * this control knowing anything about either.
 *
 * ## What stays in the page builder
 *
 * Field WIDTHS and ordering-by-drag. The builder lays fields out on a grid; a
 * sidebar column is not a grid, and a width picker there would be guesswork.
 * Widths already set are preserved untouched — the item object is spread, never
 * rebuilt.
 */

import { get as getControl } from '../registry.js';

const { BaseControl, Button, Card, CardBody, Notice, SelectControl } = wp.components;
const { useState } = wp.element;

/**
 * Mint a unique field identifier in PHP's format.
 *
 * `sanitize_key( str_replace( '-', '_', $type ) . '_' . substr( fw_rand_md5(), 0, 7 ) )`
 *
 * @param {string} type     The item type.
 * @param {Array}  existing Items already in the form.
 * @return {string} A shortcode not already in use.
 */
function mintShortcode( type, existing ) {
	const used = new Set( existing.map( ( i ) => i && i.shortcode ).filter( Boolean ) );
	const base = String( type ).replace( /-/g, '_' ).toLowerCase();

	for ( let attempt = 0; attempt < 50; attempt++ ) {
		const suffix = Math.random().toString( 16 ).slice( 2, 9 ).padEnd( 7, '0' );
		const candidate = `${ base }_${ suffix }`;

		if ( ! used.has( candidate ) ) {
			return candidate;
		}
	}

	// Fifty collisions on 7 hex characters does not happen; falling back to a
	// length-based suffix keeps this total rather than returning a duplicate.
	return `${ base }_${ existing.length }`;
}

/**
 * Decode the stored `{ json: '…' }` into an array of items.
 *
 * @param {*} value The option value.
 * @return {Array} Items.
 */
function decode( value ) {
	if ( ! value || typeof value !== 'object' ) {
		return [];
	}

	if ( Array.isArray( value.json ) ) {
		return value.json;
	}

	if ( typeof value.json !== 'string' ) {
		return [];
	}

	try {
		const parsed = JSON.parse( value.json );

		return Array.isArray( parsed ) ? parsed : [];
	} catch ( e ) {
		// A value we cannot parse is not ours to discard — returning [] here would
		// render an empty form editor whose first edit wipes the real form.
		return null;
	}
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current { json } value.
 * @param {Function} props.onChange Called with the next { json } value.
 */
export default function FormBuilder( { option = {}, value, onChange } ) {
	const [ openIndex, setOpenIndex ] = useState( null );

	const items = decode( value );
	const itemTypes = option.item_types && typeof option.item_types === 'object'
		? option.item_types
		: {};

	if ( items === null ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				This form could not be read here — edit it in the page builder. Nothing has been
				changed.
			</Notice>
		);
	}

	const commit = ( next ) => onChange( { ...( value || {} ), json: JSON.stringify( next ) } );

	const addField = ( type ) => {
		if ( ! type ) {
			return;
		}

		const declared = itemTypes[ type ] ? itemTypes[ type ].options || {} : {};
		const options = {};

		// Seed the item with its type's declared defaults, so a new field arrives
		// labelled rather than blank.
		Object.keys( declared ).forEach( ( id ) => {
			if ( declared[ id ].value !== undefined ) {
				options[ id ] = declared[ id ].value;
			}
		} );

		commit( [
			...items,
			{
				type,
				shortcode: mintShortcode( type, items ),
				// The page builder gives every item a width from its grid; '' is the
				// element's own "auto" and is what the seeded default form uses.
				width: '',
				options,
			},
		] );
		setOpenIndex( items.length );
	};

	const setAttr = ( index, id, next ) =>
		commit(
			items.map( ( item, i ) =>
				i === index
					? { ...item, options: { ...( item.options || {} ), [ id ]: next } }
					: item
			)
		);

	const move = ( index, delta ) => {
		const target = index + delta;

		if ( target < 0 || target >= items.length ) {
			return;
		}

		const next = items.slice();
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		commit( next );
		setOpenIndex( openIndex === index ? target : openIndex );
	};

	const remove = ( index ) => {
		commit( items.filter( ( _item, i ) => i !== index ) );
		setOpenIndex( null );
	};

	const duplicate = ( index ) => {
		const copy = { ...items[ index ], shortcode: mintShortcode( items[ index ].type, items ) };
		const next = items.slice();

		next.splice( index + 1, 0, copy );
		commit( next );
	};

	return (
		<BaseControl
			label={ option.label || 'Form fields' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ items.map( ( item, index ) => {
				const known = itemTypes[ item.type ];
				const fields = known ? known.options || {} : {};
				const isOpen = openIndex === index;
				const attrs = ( item && item.options ) || {};
				const title =
					( attrs.label && String( attrs.label ).trim() ) ||
					( known && known.title ) ||
					item.type;

				return (
					<Card key={ item.shortcode || index } size="small" style={ { marginBottom: '8px' } }>
						<CardBody style={ { padding: '8px' } }>
							<div style={ { display: 'flex', alignItems: 'center', gap: '2px' } }>
								<Button
									variant="tertiary"
									onClick={ () => setOpenIndex( isOpen ? null : index ) }
									aria-expanded={ isOpen }
									style={ {
										flex: '1 1 auto',
										justifyContent: 'flex-start',
										minWidth: 0,
										overflow: 'hidden',
										textOverflow: 'ellipsis',
									} }
								>
									{ title }
									{ attrs.required ? ' *' : '' }
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
									{ ! known && (
										<Notice status="warning" isDismissible={ false }>
											{ `Unknown field type "${ item.type }" — edit it in the page builder.` }
										</Notice>
									) }

									{ Object.keys( fields ).map( ( id ) => {
										const sub = fields[ id ];
										const Control = getControl( sub.type );

										if ( ! Control ) {
											return (
												<Notice key={ id } status="warning" isDismissible={ false }>
													{ `No React control for "${ sub.type }" yet.` }
												</Notice>
											);
										}

										return (
											<Control
												key={ id }
												option={ sub }
												value={ attrs[ id ] }
												onChange={ ( next ) => setAttr( index, id, next ) }
											/>
										);
									} ) }

									<p style={ { margin: '8px 0 0', fontSize: '11px', opacity: 0.7 } }>
										{ `Field name: ${ item.shortcode }` }
									</p>
								</div>
							) }
						</CardBody>
					</Card>
				);
			} ) }

			<SelectControl
				label="Add a field"
				value=""
				options={ [
					{ label: '— Choose a field type —', value: '' },
					...Object.keys( itemTypes ).map( ( id ) => ( {
						value: id,
						label: itemTypes[ id ].title || id,
					} ) ),
				] }
				onChange={ addField }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</BaseControl>
	);
}
