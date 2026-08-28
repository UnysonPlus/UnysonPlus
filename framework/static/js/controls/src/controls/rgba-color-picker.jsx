/**
 * `rgba-color-picker` option type — React renderer.
 *
 * Stores a colour STRING, and its validator is looser than the plain
 * `color-picker`'s: hex of 3, 4, 6 or 8 digits, **or** `rgb()` / `rgba()`. Both
 * are valid CSS, and the PHP renderer's picker emits `rgb()` when a colour is
 * fully opaque and `rgba()` when it carries alpha.
 *
 * That difference matters. `color-picker` accepts hex ONLY and silently
 * substitutes the option default for anything else — which is why its React
 * control normalises to hex. Doing the same here would be a quiet downgrade:
 * this type exists precisely where alpha is the point (an overlay tint over a
 * hero image), and the empty string is a legitimate value meaning "no colour".
 *
 * So the string the picker produces is stored as given.
 */

const { BaseControl, Button, ColorPicker, Dropdown } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current colour string.
 * @param {Function} props.onChange Called with the next string.
 */
export default function RgbaColorPicker( { option = {}, value, onChange } ) {
	const current = typeof value === 'string' ? value : option.value || '';

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div style={ { display: 'flex', gap: '6px', alignItems: 'center' } }>
				<Dropdown
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							variant="secondary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
							__next40pxDefaultSize
						>
							<span
								style={ {
									display: 'inline-block',
									width: '16px',
									height: '16px',
									marginRight: '8px',
									borderRadius: '3px',
									border: '1px solid #ddd',
									// A chequerboard behind the swatch, so a transparent or
									// semi-transparent colour reads as transparent rather than
									// as white.
									backgroundImage:
										'repeating-linear-gradient(45deg,#e0e0e0 0 4px,#fff 4px 8px)',
								} }
								aria-hidden="true"
							>
								<span
									style={ {
										display: 'block',
										width: '100%',
										height: '100%',
										background: current || 'transparent',
									} }
								/>
							</span>
							{ current || 'None' }
						</Button>
					) }
					renderContent={ () => (
						<ColorPicker color={ current || undefined } enableAlpha onChange={ onChange } />
					) }
				/>

				{ current !== '' && (
					<Button
						icon="no-alt"
						size="small"
						label="Clear"
						// '' is a real value here — "no colour" — not a missing one.
						onClick={ () => onChange( '' ) }
					/>
				) }
			</div>
		</BaseControl>
	);
}
