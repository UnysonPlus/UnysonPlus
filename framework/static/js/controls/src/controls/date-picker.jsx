/**
 * `date-picker` option type — React renderer.
 *
 * The stored value is a date STRING in `dd-MM-yyyy` — `01-09-2026`. That is not
 * a guess: the option type's own script sets `dateFormat: 'dd-MM-yyyy'`, and its
 * `fw_date_picker_parse()` reads values back with `/^(\d{2})-(\d{2})-(\d{4})/`.
 *
 * ## Why this one needed care
 *
 * `_get_value_from_input()` only casts to string. It accepts anything — which
 * makes this among the more dangerous option types to write a control for,
 * because a wrong format is not rejected, it is *misread later*.
 *
 * Emit ISO (`2026-09-01`) and it saves happily. Then PHP reads it: `strtotime()`
 * treats a dash-separated date as **d-m-Y**, so `2026-09-01` is parsed as day
 * 2026 of month 09. The symptom is an event on a nonsensical date, months from
 * where it belongs, with nothing anywhere reporting an error.
 *
 * So this control emits `dd-MM-yyyy`, reads it back the same way, and emits
 * nothing at all for input it cannot format.
 *
 * `min-date` / `max-date` are declared in that same format and are converted for
 * the native input, so the browser enforces the range the schema intends.
 */

const { BaseControl } = wp.components;

/**
 * `dd-MM-yyyy` → `yyyy-MM-dd`, for the native input.
 *
 * @param {string} stored The stored value.
 * @return {string} An input value, or ''.
 */
function toInput( stored ) {
	const m = String( stored ?? '' ).match( /^(\d{2})-(\d{2})-(\d{4})$/ );

	return m ? `${ m[ 3 ] }-${ m[ 2 ] }-${ m[ 1 ] }` : '';
}

/**
 * `yyyy-MM-dd` → `dd-MM-yyyy`, for storage.
 *
 * @param {string} input The input value.
 * @return {string} The stored form, or ''.
 */
function toStored( input ) {
	const m = String( input ?? '' ).match( /^(\d{4})-(\d{2})-(\d{2})$/ );

	return m ? `${ m[ 3 ] }-${ m[ 2 ] }-${ m[ 1 ] }` : '';
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current date string, dd-MM-yyyy.
 * @param {Function} props.onChange Called with the next dd-MM-yyyy string.
 */
export default function DatePicker( { option = {}, value, onChange } ) {
	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<input
				type="date"
				value={ toInput( value ) }
				min={ toInput( option[ 'min-date' ] ) || undefined }
				max={ toInput( option[ 'max-date' ] ) || undefined }
				onChange={ ( e ) => onChange( toStored( e.target.value ) ) }
				style={ {
					width: '100%',
					padding: '6px 8px',
					border: '1px solid #949494',
					borderRadius: '2px',
				} }
			/>
		</BaseControl>
	);
}
