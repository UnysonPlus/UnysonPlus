/**
 * `popover` option type — React renderer.
 *
 * A trigger that reveals one or more inner options. In the page builder it opens
 * a floating panel; here the inner options are rendered inline, for the same
 * reason the repeaters are — a floating panel launched from a narrow sidebar
 * covers the preview you are editing against.
 *
 * ## The stored value depends on how many inner options there are
 *
 * This is the detail that decides whether the control works at all:
 *
 * - **One** inner option — the popover's value IS that option's value, stored
 *   unwrapped. `[ 'fx' => … ]` produces `'left'`, not `[ 'fx' => 'left' ]`.
 * - **Two or more** — a hash keyed by inner id, like `multi`.
 *
 * `_get_value_from_input()` branches on exactly that count, so a control that
 * always wrapped (or never did) would produce a value the element's view cannot
 * read — and for the single-option case, which is most of them, the symptom is a
 * setting that silently reverts.
 *
 * Inner options may be declared under `inner-options`, under `tabs[].options`,
 * or both; `collect_definitions()` merges them in that order, and so does this.
 */

import { get as getControl } from '../registry.js';

const { BaseControl, Notice } = wp.components;
const { useMemo } = wp.element;

/**
 * Flatten a declared options map, dropping containers but keeping ids.
 *
 * Mirrors fw_extract_only_options(): a `group` or `box` is layout, and the value
 * stays flat regardless.
 *
 * @param {Object} options Declared options map.
 * @return {Array} Array of [ id, option ] pairs.
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
 * Collect the inner options from both declaration sites, in PHP's order.
 *
 * @param {Object} option The option schema entry.
 * @return {Array} Array of [ id, option ] pairs.
 */
function collect( option ) {
	let defs = { ...( option[ 'inner-options' ] || {} ) };

	if ( Array.isArray( option.tabs ) ) {
		option.tabs.forEach( ( tab ) => {
			if ( tab && tab.options ) {
				defs = { ...defs, ...tab.options };
			}
		} );
	}

	return flatten( defs );
}

/**
 * One inner field, rendered through the shared registry.
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
				{ `No React control for "${ option.type }" yet — edit this in the page builder.` }
			</Notice>
		);
	}

	return <Control option={ option } value={ value } onChange={ onChange } />;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {*}        props.value    Current value — shape depends on the inner count.
 * @param {Function} props.onChange Called with the next value.
 */
export default function Popover( { option = {}, value, onChange } ) {
	const inner = useMemo(
		() => collect( option ),
		[ option[ 'inner-options' ], option.tabs ]
	);

	if ( ! inner.length ) {
		return null;
	}

	// Single inner option: the popover's value is that option's value, unwrapped.
	if ( inner.length === 1 ) {
		const [ id, sub ] = inner[ 0 ];

		return (
			<Field
				// The popover usually carries the human label while its lone inner
				// option sets `label => false`, so borrow it rather than render a
				// field with no name.
				option={ {
					...sub,
					label: sub.label || option.label || id,
					desc: sub.desc || option.desc,
				} }
				value={ value !== undefined ? value : option.value }
				onChange={ onChange }
			/>
		);
	}

	const current = value && typeof value === 'object' ? value : {};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div style={ { paddingLeft: '12px', borderLeft: '2px solid #ddd' } }>
				{ inner.map( ( [ id, sub ] ) => (
					<Field
						key={ id }
						option={ sub }
						value={ current[ id ] }
						onChange={ ( next ) => onChange( { ...current, [ id ]: next } ) }
					/>
				) ) }
			</div>
		</BaseControl>
	);
}
