/**
 * `button-hover-animation` option type — React renderer.
 *
 * Picks a hover effect for a button. The stored value is the effect's CLASS
 * (`'btnfx-lift'`), and `_get_value_from_input()` accepts a declared choice or
 * the empty string, substituting the option default for anything else.
 *
 * Note the difference from its two cousins: this one accepts `''` **always**,
 * with no `allow_none` config, because "no hover effect" is a legitimate answer
 * for every button. `button-style-picker` and `image-style-picker` both let a
 * schema forbid the empty value; this one does not, so the None row is
 * unconditional.
 *
 * ## `fx_css` is a URL, and the preview is dead without it
 *
 * The `.btnfx-*` classes are NOT part of the admin preset CSS — they live in a
 * separate stylesheet the option type points at through `fx_css`, and the PHP
 * renderer enqueues it in `_enqueue_static()`. Nothing enqueues it for a block
 * sidebar, so this control links it itself.
 *
 * Without that, every row would render an identical unstyled button: a preview
 * that previews nothing, which is worse than a plain list because it looks like
 * the effects are all the same.
 *
 * ## Why the effect only shows on hover, and that is fine
 *
 * A hover effect has no resting appearance — that is what makes it a hover
 * effect. So the rows are previewed by hovering them, exactly as in the page
 * builder. The name is always visible, so the list is still usable by keyboard
 * and by anyone who never hovers.
 */

const { BaseControl, Button } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    The selected effect class.
 * @param {Function} props.onChange Called with the next class.
 */
export default function ButtonHoverAnimation( { option = {}, value, onChange } ) {
	const choices = option.choices && typeof option.choices === 'object' ? option.choices : {};
	const base = option.preview_base || 'btn btn-primary';
	const fxCss = option.fx_css || '';

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
				<span className={ `${ base } ${ key }` } aria-hidden="true">
					{ 'Button' }
				</span>
			) : (
				<span style={ { fontStyle: 'italic', opacity: 0.7 } }>
					{ option.placeholder || 'None' }
				</span>
			) }
			<span style={ { fontSize: '11px', opacity: 0.75 } }>{ label }</span>
		</Button>
	);

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || 'Hover a row to see the effect.' }
			__nextHasNoMarginBottom
		>
			{ /*
			  * Linked here rather than enqueued: nothing runs the option type's
			  * _enqueue_static() for a block sidebar. React de-duplicates nothing
			  * here, but the browser does — the same href is fetched once however
			  * many of these controls a sidebar happens to contain.
			  */ }
			{ fxCss && <link rel="stylesheet" href={ fxCss } /> }

			<div>
				{ row( '', choices[ '' ] || '', current === '' ) }
				{ Object.keys( choices )
					.filter( ( key ) => key !== '' )
					.map( ( key ) => row( key, choices[ key ] || key, current === key ) ) }
			</div>
		</BaseControl>
	);
}
