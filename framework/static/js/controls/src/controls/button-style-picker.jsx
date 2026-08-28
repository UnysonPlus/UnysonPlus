/**
 * `button-style-picker` option type — React renderer.
 *
 * Picks a Button Preset or Size. The stored value is the CLASS STRING itself
 * (`'btn-primary'`, `'btn-lg'`), which is why the PHP renderer is a drop-in
 * replacement for the plain `<select>` it grew out of.
 *
 * `_get_value_from_input()` accepts a key present in `choices`, plus `''` when
 * `allow_none` is on, and substitutes the option default for anything else. The
 * control mirrors that: an unrecognised saved value shows as unselected rather
 * than as a selection the next save would silently rewrite.
 *
 * ## Real buttons, like image-style-picker and unlike border-style-picker
 *
 * Each row renders `<span class="btn {value}">` so the preset paints itself. That
 * works here for the same reason it works for the Image Style picker: the
 * generated preset CSS ships in `unysonplus-presets`, enqueued on
 * `admin_enqueue_scripts`, so it is present in the OUTER admin document that a
 * block sidebar renders into.
 *
 * `border-style-picker` is the counter-example — its preset classes live in the
 * theme's compiled stylesheet, which loads into the canvas iframe only, so its
 * control is a plain dropdown. The difference is not stylistic; it is which
 * document the CSS is in.
 *
 * Layout is inline-styled rather than reusing the PHP view's own classes, whose
 * stylesheet is enqueued by `_enqueue_static()` and so is absent here.
 */

const { BaseControl, Button } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    The selected class string.
 * @param {Function} props.onChange Called with the next class string.
 */
export default function ButtonStylePicker( { option = {}, value, onChange } ) {
	const choices = option.choices && typeof option.choices === 'object' ? option.choices : {};
	const allowNone = option.allow_none === undefined || option.allow_none;
	const base = option.preview_base || 'btn';
	const text = option.preview_text || 'Button';

	const current = typeof value === 'string' && choices[ value ] !== undefined ? value : '';

	const row = ( key, label, selected ) => (
		<Button
			key={ key || '__none' }
			onClick={ () => onChange( key ) }
			aria-pressed={ selected }
			style={ {
				display: 'flex',
				alignItems: 'center',
				gap: '10px',
				width: '100%',
				height: 'auto',
				padding: '6px 8px',
				marginBottom: '4px',
				borderRadius: '4px',
				textAlign: 'left',
				boxShadow: selected
					? '0 0 0 2px var(--wp-admin-theme-color)'
					: 'inset 0 0 0 1px #ddd',
			} }
		>
			{ key ? (
				// The preview is decorative — the real control is the row itself, and a
				// nested interactive element would be a second tab stop that does nothing.
				<span className={ `${ base } ${ key }` } aria-hidden="true">
					{ text }
				</span>
			) : (
				<span style={ { fontStyle: 'italic', opacity: 0.7 } }>
					{ option.placeholder || '— Select —' }
				</span>
			) }
			<span style={ { fontSize: '11px', opacity: 0.75 } }>{ label }</span>
		</Button>
	);

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div>
				{ allowNone && row( '', choices[ '' ] || '', current === '' ) }
				{ Object.keys( choices )
					.filter( ( key ) => key !== '' )
					.map( ( key ) => row( key, choices[ key ] || key, current === key ) ) }
			</div>
		</BaseControl>
	);
}
