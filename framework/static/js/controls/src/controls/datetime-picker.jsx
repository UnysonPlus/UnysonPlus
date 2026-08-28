/**
 * `datetime-picker` option type — React renderer.
 *
 * The stored value is a STRING formatted to the schema's `format` (a PHP
 * `date()` format, default `'Y/m/d H:i'`). That is the whole difficulty: the
 * server does not parse a date, it *validates the formatting* —
 * `_fw_validate_date_format()` re-formats the parsed value and compares it back
 * to the input, so a string that is a perfectly valid date in the wrong shape is
 * rejected and replaced by the option default.
 *
 * A control emitting an ISO string (`2026-09-01T14:30`) into a `Y/m/d H:i` field
 * would therefore look like it worked and silently save nothing. So this control
 * formats to the declared format, and refuses to guess when it cannot.
 *
 * ## The fallback is the point
 *
 * `formatWith()` supports the tokens that appear in this codebase's schemas
 * (`Y y m n d j H G i s`). Meet a token it does not implement and the control
 * degrades to a PLAIN TEXT field showing the expected format, rather than
 * emitting a string built from a partial understanding of the format.
 *
 * A text field that says `Y/m/d H:i` is mildly annoying. A date picker that
 * writes an unparseable string into a countdown, which then silently falls back
 * to its default date, is a bug someone debugs on the day of a launch.
 *
 * Min/max are also enforced server-side; they are passed to the input so the
 * browser refuses out-of-range values rather than letting the server discard
 * them without saying so.
 */

const { TextControl, BaseControl } = wp.components;

/** Tokens this control knows how to render. Anything else triggers the fallback. */
const SUPPORTED = 'YymndjHGis';

const pad = ( n ) => String( n ).padStart( 2, '0' );

/**
 * Format a Date with a PHP date() format string.
 *
 * @param {Date}   date   The date.
 * @param {string} format PHP date() format.
 * @return {string} The formatted string.
 */
function formatWith( date, format ) {
	let out = '';

	for ( let i = 0; i < format.length; i++ ) {
		const ch = format[ i ];

		// PHP escapes a literal character with a backslash; carry that through so a
		// format like 'Y \a\t H:i' does not render the letters as tokens.
		if ( ch === '\\' ) {
			out += format[ ++i ] ?? '';
			continue;
		}

		switch ( ch ) {
			case 'Y': out += date.getFullYear(); break;
			case 'y': out += pad( date.getFullYear() % 100 ); break;
			case 'm': out += pad( date.getMonth() + 1 ); break;
			case 'n': out += date.getMonth() + 1; break;
			case 'd': out += pad( date.getDate() ); break;
			case 'j': out += date.getDate(); break;
			case 'H': out += pad( date.getHours() ); break;
			case 'G': out += date.getHours(); break;
			case 'i': out += pad( date.getMinutes() ); break;
			case 's': out += pad( date.getSeconds() ); break;
			default: out += ch;
		}
	}

	return out;
}

/**
 * Whether every alphabetic token in the format is one we can render.
 *
 * @param {string} format PHP date() format.
 * @return {boolean} True when the format is fully supported.
 */
function isSupported( format ) {
	for ( let i = 0; i < format.length; i++ ) {
		if ( format[ i ] === '\\' ) {
			i++;
			continue;
		}

		if ( /[A-Za-z]/.test( format[ i ] ) && ! SUPPORTED.includes( format[ i ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Parse a stored string back into the `<input>`'s own value format.
 *
 * Only the shapes this control emits need to round-trip; anything else leaves
 * the input blank rather than showing a date it may have misread.
 *
 * @param {string}  stored     The saved string.
 * @param {boolean} withTime   Whether the input carries a time.
 * @return {string} A value for datetime-local / date / time, or ''.
 */
function toInputValue( stored, withTime ) {
	const m = String( stored ?? '' ).match(
		/^(\d{4})\D(\d{1,2})\D(\d{1,2})(?:\D+(\d{1,2}):(\d{2}))?/
	);

	if ( ! m ) {
		// A time-only value ('14:30') for a timepicker-only field.
		const t = String( stored ?? '' ).match( /^(\d{1,2}):(\d{2})$/ );
		return t ? `${ pad( t[ 1 ] ) }:${ t[ 2 ] }` : '';
	}

	const date = `${ m[ 1 ] }-${ pad( m[ 2 ] ) }-${ pad( m[ 3 ] ) }`;

	return withTime ? `${ date }T${ pad( m[ 4 ] || 0 ) }:${ m[ 5 ] || '00' }` : date;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current formatted string.
 * @param {Function} props.onChange Called with the next formatted string.
 */
export default function DatetimePicker( { option = {}, value, onChange } ) {
	const config = option[ 'datetime-picker' ] || {};
	const format = config.format || 'Y/m/d H:i';
	const hasDate = config.datepicker !== false;
	const hasTime = config.timepicker !== false;

	// Unsupported format → a plain text field that says what shape is expected.
	if ( ! isSupported( format ) ) {
		return (
			<TextControl
				label={ option.label || '' }
				help={ `${ option.desc ? option.desc + ' ' : '' }Format: ${ format }` }
				value={ value ?? '' }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		);
	}

	const type = hasDate && hasTime ? 'datetime-local' : hasDate ? 'date' : 'time';

	const handle = ( next ) => {
		if ( ! next ) {
			onChange( '' );
			return;
		}

		// A time-only input has no date to build a Date from; anchor it to today so
		// the formatter has something valid, then emit only what the format asks for.
		const parsed = type === 'time' ? new Date( `1970-01-01T${ next }` ) : new Date( next );

		onChange( Number.isNaN( parsed.getTime() ) ? '' : formatWith( parsed, format ) );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<input
				type={ type }
				value={ toInputValue( value, hasDate && hasTime ) }
				min={ config.minDate || undefined }
				max={ config.maxDate || undefined }
				onChange={ ( e ) => handle( e.target.value ) }
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
