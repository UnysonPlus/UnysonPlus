/**
 * `checkboxes` option type — React renderer.
 *
 * A list of independent toggles. The stored value is a map of the CHECKED
 * choices only:
 *
 * ```
 * { comments: true, author: true }
 * ```
 *
 * ## The trap this control exists to avoid
 *
 * `_get_value_from_input()` keeps every submitted entry whose value is not the
 * empty string, and stores it as `true`. It does not test for truthiness — so a
 * control that sent `{ author: false }` for an unchecked box would have that box
 * come back CHECKED, because `false !== ''`.
 *
 * Unchecked choices are therefore OMITTED entirely rather than sent as false.
 * That is asserted in the parity suite rather than left to a comment, because it
 * is the kind of thing a later "tidy-up" would helpfully break.
 *
 * Choices not present in the schema are dropped server-side, so a stale key in a
 * saved value simply disappears on the next save; the control ignores it too
 * rather than rendering a box for something the server will refuse.
 */

const { CheckboxControl, BaseControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Map of checked choice id => true.
 * @param {Function} props.onChange Called with the next map.
 */
export default function Checkboxes( { option = {}, value, onChange } ) {
	const choices = option.choices && typeof option.choices === 'object' ? option.choices : {};
	const current = value && typeof value === 'object' ? value : {};

	const toggle = ( key, checked ) => {
		const next = {};

		// Rebuild from the declared choices so the order is the schema's, and so a
		// key that is no longer a choice does not survive an unrelated toggle.
		Object.keys( choices ).forEach( ( id ) => {
			const on = id === key ? checked : Boolean( current[ id ] );

			if ( on ) {
				next[ id ] = true;
			}
		} );

		onChange( next );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div
				style={
					option.inline
						? { display: 'flex', flexWrap: 'wrap', gap: '4px 16px' }
						: undefined
				}
			>
				{ Object.keys( choices ).map( ( key ) => (
					<CheckboxControl
						key={ key }
						label={ choices[ key ] }
						checked={ Boolean( current[ key ] ) }
						onChange={ ( checked ) => toggle( key, checked ) }
						__nextHasNoMarginBottom
					/>
				) ) }
			</div>
		</BaseControl>
	);
}
