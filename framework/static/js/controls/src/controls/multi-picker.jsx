/**
 * `multi-picker` option type — React renderer.
 *
 * A picker plus the options that picker reveals: choose "Video" and you get the
 * video fields, choose "Image" and you get the image ones. It is the second
 * structural option type in the library after addable-popup, and it blocks more
 * elements than any other single type.
 *
 * ## The stored value
 *
 * ```
 * { <pickerKey>: 'video', video: { url: '…', autoplay: 'true' } }
 * ```
 *
 * — the picker's own value under the picker's key, and the SELECTED choice's
 * sub-values under a key named for that choice.
 *
 * Only the selected choice is stored. That is not an optimisation this control
 * invented: `_get_value_from_input()` prunes the others, and for good reason —
 * the Entrance picker's unpruned value carried a settings block for all ~56
 * Animate.css effects, around 70KB, which was enough to exhaust memory in the
 * page builder. This control keeps the other choices' values in the object while
 * you are switching back and forth, and PHP drops them on save; what survives a
 * round trip is what PHP decided to keep, which is the definition that matters.
 *
 * ## Why the child controls' WIRE format matters here
 *
 * Unlike addable-popup, this option type DOES run each child option's
 * `get_value_from_input()` — the picker's and the selected choice's alike. So the
 * children must emit what PHP expects to receive, not what it stores: `'true'`
 * for a switch, a delimited string for a multi-select. Rendering them through
 * the shared registry is what guarantees that, because those controls already
 * emit the wire format for the sidebar's top level.
 */

import { get as getControl } from '../registry.js';

const { Notice, BaseControl } = wp.components;
const { useMemo } = wp.element;

/**
 * Expand the `for` / `options` shared-block form in a choices map.
 *
 * Mirrors FW_Option_Type_Multi_Picker::prepare_choices(). A schema may declare
 * one block of options shared by several choices rather than repeating it, and
 * PHP expands that before it validates anything — so a control that skipped the
 * expansion would render a different set of fields from the ones the server
 * accepts.
 *
 * @param {Object} choices The declared choices map.
 * @return {Object} The expanded map.
 */
function prepareChoices( choices ) {
	const result = {};

	Object.keys( choices || {} ).forEach( ( key ) => {
		const settings = choices[ key ];

		if ( settings && settings.for && settings.options ) {
			const targets = Array.isArray( settings.for ) ? settings.for : [ settings.for ];
			const before = ( settings.location || 'before' ) === 'before';

			targets.forEach( ( name ) => {
				const existing = result[ name ] || choices[ name ] || {};

				result[ name ] = before
					? { ...settings.options, ...existing }
					: { ...existing, ...settings.options };
			} );

			return;
		}

		if ( result[ key ] === undefined ) {
			result[ key ] = settings;
		}
	} );

	return result;
}

/**
 * Flatten a declared options map, dropping containers but keeping ids.
 *
 * The PHP equivalent is fw_extract_only_options(): a choice's fields may be
 * wrapped in a box or group for layout, while the VALUE stays flat.
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
 * One field, rendered through the shared registry.
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
 * @param {Object}   props.value    Current value object.
 * @param {Function} props.onChange Called with the next value object.
 */
export default function MultiPicker( { option = {}, value, onChange } ) {
	const picker = option.picker || {};
	const pickerKey = Object.keys( picker )[ 0 ];
	const pickerOption = pickerKey ? picker[ pickerKey ] : null;

	const choices = useMemo( () => prepareChoices( option.choices ), [ option.choices ] );

	if ( ! pickerOption ) {
		// PHP raises E_USER_ERROR for a multi-picker with no picker. Say the same
		// thing without taking the sidebar down with it.
		return (
			<Notice status="error" isDismissible={ false }>
				This option is missing its picker and cannot be edited here.
			</Notice>
		);
	}

	const current = value && typeof value === 'object' ? value : {};
	const selected = current[ pickerKey ] ?? pickerOption.value;
	const revealed = choices[ selected ] ? flatten( choices[ selected ] ) : [];

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ /*
			  * `hide_picker` is honoured, but the revealed options are still shown.
			  * A schema hides the picker when the choice is decided elsewhere — not
			  * to hide the fields it controls.
			  */ }
			{ ! option.hide_picker && (
				<Field
					option={ pickerOption }
					value={ selected }
					onChange={ ( next ) => onChange( { ...current, [ pickerKey ]: next } ) }
				/>
			) }

			{ revealed.length > 0 && (
				<div
					style={ {
						marginTop: '12px',
						paddingLeft: '12px',
						borderLeft: '2px solid #ddd',
					} }
				>
					{ revealed.map( ( [ id, sub ] ) => (
						<Field
							key={ id }
							option={ sub }
							value={
								current[ selected ] ? current[ selected ][ id ] : undefined
							}
							onChange={ ( next ) =>
								onChange( {
									...current,
									[ selected ]: { ...( current[ selected ] || {} ), [ id ]: next },
								} )
							}
						/>
					) ) }
				</div>
			) }
		</BaseControl>
	);
}
