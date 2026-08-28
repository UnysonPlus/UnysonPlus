/**
 * `image-style-picker` option type — React renderer.
 *
 * Picks one of the theme's Image Style presets (a treatment: rounded, duotone,
 * scrim, masked, and so on). The stored value is the preset's KEY, a plain
 * string — `_get_value_from_input()` accepts a key present in `choices`, plus
 * the empty string when `allow_none` is on, and falls back to the option default
 * for anything else.
 *
 * ## Why this one draws real previews and border-style-picker does not
 *
 * Both are "pick a preset", and the two controls deliberately look different.
 *
 * `border-style-picker` previews a choice by applying the preset's class to a
 * sample box, and those classes live in the THEME's compiled stylesheet — which
 * loads into the editor's canvas iframe, not into the sidebar. Drawing tiles
 * there would produce a grid of identical unstyled boxes, so that control is a
 * dropdown.
 *
 * The Image Style presets are different: they ship in `unysonplus-presets`,
 * which is enqueued on `admin_enqueue_scripts` and is therefore present in the
 * OUTER admin document — the one the block sidebar renders into. So a swatch
 * wrapped in `.imgs-wrap .{key}` really is styled here, and a picture of the
 * treatment beats its name.
 *
 * The layout around the swatches is inline-styled rather than reusing the PHP
 * view's `isp__*` classes, because that stylesheet is enqueued by the option
 * type's own `_enqueue_static()` when PHP renders it, and nothing renders it in
 * a block sidebar. Borrowing class names whose CSS is not loaded is how a
 * control ends up looking broken in one place and fine in another.
 */

const { BaseControl, Button } = wp.components;

/**
 * The stand-in "photo" the swatches treat.
 *
 * Mirrors the PHP view's sample: a small landscape, so filters (grayscale,
 * duotone, contrast), the scrim and the corner/mask shapes all read at swatch
 * size. Inlined as a data URI, so it costs no request and no CSP exception.
 */
const SAMPLE = `data:image/svg+xml,${ encodeURIComponent(
	'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 60" preserveAspectRatio="xMidYMid slice">' +
		'<defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">' +
		'<stop offset="0" stop-color="#79add9"/><stop offset="1" stop-color="#f3d2a6"/>' +
		'</linearGradient></defs>' +
		'<rect width="80" height="60" fill="url(#sky)"/>' +
		'<circle cx="57" cy="19" r="9" fill="#ffd769"/>' +
		'<path d="M0 43 Q20 30 40 41 T80 39 V60 H0 Z" fill="#3f8f7d"/>' +
		'<path d="M0 51 Q26 40 52 49 T80 49 V60 H0 Z" fill="#2b6a62"/>' +
		'</svg>'
) }`;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    The selected preset key.
 * @param {Function} props.onChange Called with the next key.
 */
export default function ImageStylePicker( { option = {}, value, onChange } ) {
	const choices = option.choices && typeof option.choices === 'object' ? option.choices : {};
	const allowNone = option.allow_none === undefined || option.allow_none;

	// A stored key that is no longer a declared choice reads as unselected, the
	// same way the PHP view treats it — rather than showing a selection the
	// server would refuse on the next save.
	const current = typeof value === 'string' && choices[ value ] !== undefined ? value : '';

	const keys = Object.keys( choices ).filter( ( key ) => key !== '' );

	const tile = ( key, label, selected ) => (
		<Button
			key={ key || '__none' }
			onClick={ () => onChange( key ) }
			label={ label }
			showTooltip
			style={ {
				display: 'block',
				height: 'auto',
				padding: '4px',
				borderRadius: '4px',
				boxShadow: selected ? '0 0 0 2px var(--wp-admin-theme-color)' : 'inset 0 0 0 1px #ddd',
			} }
			aria-pressed={ selected }
		>
			<span
				className={ key ? `imgs-wrap ${ key }` : undefined }
				style={ { display: 'block', overflow: 'hidden', lineHeight: 0 } }
			>
				<img
					src={ SAMPLE }
					alt=""
					style={ { display: 'block', width: '100%', height: '42px', objectFit: 'cover' } }
				/>
			</span>
			<span
				style={ {
					display: 'block',
					marginTop: '4px',
					fontSize: '11px',
					lineHeight: 1.3,
					whiteSpace: 'normal',
					textAlign: 'center',
				} }
			>
				{ label }
			</span>
		</Button>
	);

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div
				style={ {
					display: 'grid',
					gridTemplateColumns: 'repeat(3, 1fr)',
					gap: '6px',
				} }
			>
				{ /*
				  * The None tile carries no swatch: there is no treatment to show, and a
				  * blank sample photo would read as "this preset does nothing visible"
				  * rather than "no preset".
				  */ }
				{ allowNone && (
					<Button
						onClick={ () => onChange( '' ) }
						style={ {
							display: 'block',
							height: 'auto',
							minHeight: '68px',
							padding: '4px',
							borderRadius: '4px',
							fontSize: '11px',
							whiteSpace: 'normal',
							boxShadow:
								current === ''
									? '0 0 0 2px var(--wp-admin-theme-color)'
									: 'inset 0 0 0 1px #ddd',
						} }
						aria-pressed={ current === '' }
					>
						{ choices[ '' ] || '— None —' }
					</Button>
				) }

				{ keys.map( ( key ) => tile( key, choices[ key ] || key, current === key ) ) }
			</div>
		</BaseControl>
	);
}
