/**
 * `multi-inline` (`fw-multi-inline`) option type — React renderer.
 *
 * Several small related fields on one row — a price's Monthly and Yearly, a
 * dimension's value and unit. The stored value is a flat map keyed by the child
 * ids declared in `fw_multi_options`:
 *
 * ```
 * { monthly: '29', yearly: '' }
 * ```
 *
 * `_get_value_from_input()` returns any array it is handed unchanged, so the
 * child values are stored exactly as the child controls produce them.
 *
 * One trap worth naming: the child schema uses **`title`** where nearly every
 * other option type uses `label`. That is the key the PHP view reads, so a
 * control passing the child schema straight to a shared control would render
 * every field unlabelled. They are mapped across below.
 */

import { get as getControl } from '../registry.js';

const { BaseControl, Notice } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current map of child id => value.
 * @param {Function} props.onChange Called with the next map.
 */
export default function MultiInline( { option = {}, value, onChange } ) {
	const children =
		option.fw_multi_options && typeof option.fw_multi_options === 'object'
			? option.fw_multi_options
			: {};

	const current = value && typeof value === 'object' ? value : {};
	const ids = Object.keys( children );

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div
				style={ {
					display: 'grid',
					/*
					 * `equal` asks for evenly-sized fields, and two or three fit a
					 * sidebar. Beyond that they stack: squeezing four text inputs into
					 * a 280px column produces fields too narrow to read what is in them.
					 */
					gridTemplateColumns:
						option.equal && ids.length <= 3 ? `repeat(${ ids.length }, 1fr)` : '1fr',
					gap: '8px',
				} }
			>
				{ ids.map( ( id ) => {
					const child = children[ id ];
					const Control = getControl( child.type );

					if ( ! Control ) {
						return (
							<Notice key={ id } status="warning" isDismissible={ false }>
								{ `No React control for "${ child.type }" yet.` }
							</Notice>
						);
					}

					return (
						<Control
							key={ id }
							option={ { ...child, label: child.title || child.label || id } }
							value={ current[ id ] }
							onChange={ ( next ) => onChange( { ...current, [ id ]: next } ) }
						/>
					);
				} ) }
			</div>
		</BaseControl>
	);
}
