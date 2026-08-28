/**
 * `responsive` option type — React renderer.
 *
 * Wraps another option type in three breakpoints. The stored value is always the
 * full shape, whichever devices were touched:
 *
 * ```
 * { base: 'left', md: '', lg: 'center' }
 * ```
 *
 * An empty string means "inherit from the device below" — it is NOT the same as
 * unset, and it is why `_get_value_from_input()` starts from
 * `{ base: '', md: '', lg: '' }` and fills in only what was submitted. A control
 * that omitted untouched keys would produce a value missing the very fields the
 * element reads.
 *
 * ## Whether blank SURVIVES depends on the inner type
 *
 * Each device's value is validated by the inner option type, and a `select` with
 * no blank entry in its `choices` does not accept `''` — it falls through to the
 * first choice. So on the page-builder path, a blank Tablet on such an option
 * comes back as the first choice rather than as "inherit".
 *
 * Real schemas handle this by declaring a blank/default choice (the column's
 * alignment pickers use a `default` entry), and then blank round-trips intact.
 *
 * The consequence for this control is small but worth getting right: it promises
 * "leave blank to inherit" only when the inner type can actually represent
 * blank. Promising it otherwise would be describing behaviour the save undoes.
 *
 * ## Per-device values can be scalars OR objects
 *
 * The inner option can be anything — a `short-select` storing `'left'`, or a
 * `unit-input` storing `{ value, unit }`. Nothing here inspects the shape; each
 * device's value is handed to the inner control and taken back unexamined, which
 * is the only way this stays correct as inner types are added.
 *
 * ## The tabs are devices, not a picker
 *
 * Switching tabs does not change the value — it changes which device you are
 * editing. That distinction is worth the visual weight: the alternative, three
 * stacked copies of the same field, makes it far too easy to set a desktop value
 * while believing you set the mobile one.
 */

import { get as getControl } from '../registry.js';

const { BaseControl, Button, Notice } = wp.components;
const { useState } = wp.element;

/** Mirrors FW_Option_Type_Responsive::DEVICES, in ascending width order. */
const DEVICES = [
	{ key: 'base', label: 'Mobile', icon: 'smartphone' },
	{ key: 'md', label: 'Tablet', icon: 'tablet' },
	{ key: 'lg', label: 'Desktop', icon: 'desktop' },
];

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current { base, md, lg }.
 * @param {Function} props.onChange Called with the next full shape.
 */
export default function Responsive( { option = {}, value, onChange } ) {
	const [ device, setDevice ] = useState( 'base' );

	const inner = option.inner || { type: 'short-select', choices: {} };
	const Control = getControl( inner.type );

	// Always start from the complete shape: the element reads all three keys, and
	// PHP fills the untouched ones with '' rather than dropping them.
	const current = {
		base: '',
		md: '',
		lg: '',
		...( option.value && typeof option.value === 'object' ? option.value : {} ),
		...( value && typeof value === 'object' ? value : {} ),
	};

	/*
	 * Can the inner type represent "blank"? For a choice-based inner that means a
	 * declared empty choice; anything else (a text field, a unit-input) can hold
	 * an empty value natively.
	 */
	const choices = inner.choices;
	const canBeBlank =
		! choices ||
		typeof choices !== 'object' ||
		Object.prototype.hasOwnProperty.call( choices, '' );

	if ( ! Control ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ `No React control for "${ inner.type }" yet — edit this in the page builder.` }
			</Notice>
		);
	}

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div style={ { display: 'flex', gap: '2px', marginBottom: '8px' } }>
				{ DEVICES.map( ( d ) => {
					const isSet = current[ d.key ] !== '' && current[ d.key ] !== undefined;

					return (
						<Button
							key={ d.key }
							icon={ d.icon }
							label={ isSet ? `${ d.label } (set)` : d.label }
							showTooltip
							isPressed={ device === d.key }
							onClick={ () => setDevice( d.key ) }
							style={ {
								/*
								 * A dot marks a device that carries its own value, so the
								 * inherited ones are visible at a glance. Without it there is
								 * no way to tell a device you have set from one you have not
								 * without clicking each in turn.
								 */
								position: 'relative',
								boxShadow: isSet
									? 'inset 0 -2px 0 var(--wp-admin-theme-color)'
									: undefined,
							} }
						/>
					);
				} ) }
			</div>

			<Control
				option={ {
					...inner,
					label: DEVICES.find( ( d ) => d.key === device ).label,
					desc:
						device === 'base'
							? 'Applies to every width unless a larger one overrides it.'
							: canBeBlank
								? 'Leave blank to inherit the smaller width.'
								: undefined,
				} }
				value={ current[ device ] }
				onChange={ ( next ) => onChange( { ...current, [ device ]: next } ) }
			/>
		</BaseControl>
	);
}
