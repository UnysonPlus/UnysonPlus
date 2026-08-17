/**
 * `image-picker` option type — React renderer.
 *
 * A grid of image tiles. Simple in appearance, and the schema behind it has three
 * separate axes that all have to be honoured or the saved value changes shape:
 *
 * **1. Choices can be nested.** A choice whose value is an object carrying its own
 * `choices` key is a category GROUP, not a tile. PHP flattens these recursively
 * (`_flatten_choice_keys`) to decide which keys are valid, so the control must
 * recurse the same way — a flat read would treat a group as a selectable tile and
 * offer a key the server then rejects.
 *
 * **2. A tile's image is declared three ways.** Either a plain `'src'` string, or
 * `{ small, large, data }`, or `{ small: { src, alt } }`. All three appear in the
 * wild; all three are read here.
 *
 * **3. `multiple` changes the value TYPE.** Off, the value is a single key string.
 * On, it is an array of keys. Those are different storage shapes from the same
 * option type, so the control switches on the flag rather than guessing from the
 * current value — a `multiple` picker with one selection must still save an array.
 *
 * `blank` decides whether clicking the selected tile clears it. When it is off,
 * PHP refuses an unknown/empty key and restores the default, so the control does
 * not offer a deselect that the server would undo.
 */

const { BaseControl, Button } = wp.components;

/**
 * Walk the choice tree, returning flat [{ key, label, src, alt }] leaves.
 *
 * Mirrors _flatten_choice_keys(): a node with a nested `choices` array is a group
 * and contributes no key of its own.
 *
 * @param {Object} choices Declared choices, possibly grouped.
 * @return {Array} Flat list of selectable tiles.
 */
function flattenChoices( choices ) {
	const out = [];

	Object.keys( choices || {} ).forEach( ( key ) => {
		const choice = choices[ key ];

		if ( choice && typeof choice === 'object' && choice.choices && typeof choice.choices === 'object' ) {
			out.push( ...flattenChoices( choice.choices ) );
			return;
		}

		let src = '';
		let alt = key;

		if ( typeof choice === 'string' ) {
			src = choice;
		} else if ( choice && typeof choice === 'object' ) {
			const small = choice.small;

			if ( typeof small === 'string' ) {
				src = small;
			} else if ( small && typeof small === 'object' ) {
				src = small.src || '';
				alt = small.alt || key;
			}

			if ( ! src && typeof choice.large === 'string' ) {
				src = choice.large;
			}
		}

		out.push( { key, label: alt, src } );
	} );

	return out;
}

/**
 * @param {Object}       props
 * @param {Object}       props.option   The option schema entry.
 * @param {string|Array} props.value    Current value — key, or array of keys when `multiple`.
 * @param {Function}     props.onChange Called with the next key or array of keys.
 */
export default function ImagePicker( { option = {}, value, onChange } ) {
	const tiles = flattenChoices( option.choices );
	const multiple = !! option.multiple;
	const blank = !! option.blank;

	const selected = multiple
		? ( Array.isArray( value ) ? value : [] )
		: ( value ?? option.value ?? '' );

	const isOn = ( key ) => ( multiple ? selected.includes( key ) : selected === key );

	const toggle = ( key ) => {
		if ( multiple ) {
			// Preserve declaration order rather than click order, matching the way
			// PHP filters the submitted list against its valid-key map.
			const next = selected.includes( key )
				? selected.filter( ( k ) => k !== key )
				: tiles.map( ( t ) => t.key ).filter( ( k ) => k === key || selected.includes( k ) );

			onChange( next );
			return;
		}

		// Single: only offer deselect when the schema allows a blank value, since
		// PHP would otherwise restore the default and the click would appear to do
		// nothing.
		onChange( blank && selected === key ? '' : key );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div style={ { display: 'flex', flexWrap: 'wrap', gap: 8 } }>
				{ tiles.map( ( tile ) => (
					<Button
						key={ tile.key }
						onClick={ () => toggle( tile.key ) }
						aria-pressed={ isOn( tile.key ) }
						label={ tile.label }
						showTooltip
						style={ {
							padding: 2,
							height: 'auto',
							border: isOn( tile.key ) ? '2px solid var(--wp-admin-theme-color, #2271b1)' : '2px solid transparent',
							borderRadius: 4,
							outline: isOn( tile.key ) ? 'none' : '1px solid #ddd',
						} }
					>
						{ tile.src ? (
							<img src={ tile.src } alt={ tile.label } style={ { display: 'block', maxWidth: 72, height: 'auto' } } />
						) : (
							<span style={ { display: 'block', padding: '8px 10px', fontSize: 12 } }>{ tile.label }</span>
						) }
					</Button>
				) ) }
			</div>
		</BaseControl>
	);
}
