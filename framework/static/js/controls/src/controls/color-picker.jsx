/**
 * `color-picker` option type — React renderer.
 *
 * ## The stored value is HEX, always — never rgba()
 *
 * `_get_value_from_input()` validates against
 * `/^#([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i` and falls back to the
 * option default on anything else. So alpha in this framework is expressed as
 * **8-digit hex** (`#rrggbbaa`), not as `rgba(...)`.
 *
 * That matters because WordPress's ColorPicker will happily hand back an `rgba()`
 * string once `enableAlpha` is on — which PHP silently rejects, replacing the
 * user's colour with the default. Everything this control emits is therefore
 * normalised to hex first. The parity test in framework/tests/ pins this: it was
 * an actual rgba-rejected-to-empty failure before the normaliser existed.
 *
 * `alpha` stays opt-in (PHP default `false`) so the common case keeps producing
 * plain 6-digit hex for CSS consumers that expect it.
 */

const { ColorPicker, BaseControl, Button, Flex, FlexItem } = wp.components;
const { useState } = wp.element;

/**
 * Coerce whatever ColorPicker produces into the hex form PHP accepts.
 *
 * Passes valid hex straight through. Converts `rgb()` / `rgba()` to 6-digit hex,
 * or 8-digit when the alpha channel is meaningful. Returns '' for anything it
 * cannot represent, which is the schema's "unset" and is accepted by PHP —
 * better than emitting a string that would be silently swapped for the default.
 *
 * @param {string} raw Colour string from the picker.
 * @return {string} Hex colour, or ''.
 */
function toHex( raw ) {
	const input = String( raw ?? '' ).trim();

	if ( ! input ) {
		return '';
	}

	if ( /^#([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/i.test( input ) ) {
		return input;
	}

	const m = input.match(
		/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/i
	);

	if ( ! m ) {
		return '';
	}

	const hx = ( n ) =>
		Math.max( 0, Math.min( 255, Math.round( Number( n ) ) ) )
			.toString( 16 )
			.padStart( 2, '0' );

	const rgb = `#${ hx( m[ 1 ] ) }${ hx( m[ 2 ] ) }${ hx( m[ 3 ] ) }`;
	const alpha = m[ 4 ] === undefined ? 1 : Number( m[ 4 ] );

	if ( ! Number.isFinite( alpha ) || alpha >= 1 ) {
		return rgb;
	}

	return rgb + hx( Math.max( 0, Math.min( 1, alpha ) ) * 255 );
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current colour string.
 * @param {Function} props.onChange Called with the next colour string.
 */
export default function ColorPickerControl( { option = {}, value = '', onChange } ) {
	const [ open, setOpen ] = useState( false );
	const current = value ?? '';

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<Flex justify="flex-start" align="center" gap={ 2 }>
				<FlexItem>
					<Button
						variant="secondary"
						onClick={ () => setOpen( ! open ) }
						aria-expanded={ open }
						style={ {
							// A swatch the user can actually see, including "unset".
							background: current || 'transparent',
							border: '1px solid #949494',
							minWidth: 40,
							height: 30,
						} }
					>
						{ current ? '' : '—' }
					</Button>
				</FlexItem>
				<FlexItem>
					<code>{ current || 'unset' }</code>
				</FlexItem>
				{ current && (
					<FlexItem>
						<Button variant="tertiary" onClick={ () => onChange( '' ) }>
							{ 'Clear' }
						</Button>
					</FlexItem>
				) }
			</Flex>

			{ open && (
				<ColorPicker
					color={ current || undefined }
					enableAlpha={ !! option.alpha }
					onChange={ ( next ) => onChange( toHex( next ) ) }
				/>
			) }
		</BaseControl>
	);
}
