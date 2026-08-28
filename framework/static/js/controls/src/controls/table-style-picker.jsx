/**
 * `table-style-picker` option type — React renderer.
 *
 * The fourth of the preset-picker family, alongside `button-style-picker`,
 * `image-style-picker` and `button-hover-animation`. Same validator shape: a key
 * present in `choices`, plus `''` when `allow_none` is on, and the option default
 * for anything else.
 *
 * Like the other two that preview for real, the table preset CSS ships in
 * `unysonplus-presets` and is enqueued on `admin_enqueue_scripts` — so it is
 * present in the outer admin document a block sidebar renders into, and a sample
 * table wearing the preset class really is styled.
 *
 * A three-row sample rather than a swatch, because what a table preset changes
 * is the relationship between header, body and stripes. One row would show none
 * of it.
 */

const { BaseControl, Button } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    The selected preset key.
 * @param {Function} props.onChange Called with the next key.
 */
export default function TableStylePicker( { option = {}, value, onChange } ) {
	const choices = option.choices && typeof option.choices === 'object' ? option.choices : {};
	const allowNone = option.allow_none === undefined || option.allow_none;

	const current = typeof value === 'string' && choices[ value ] !== undefined ? value : '';

	const sample = ( key ) => (
		<table
			className={ key || undefined }
			style={ { width: '100%', borderCollapse: 'collapse', fontSize: '9px', pointerEvents: 'none' } }
			aria-hidden="true"
		>
			<thead>
				<tr>
					<th>A</th>
					<th>B</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>1</td>
					<td>2</td>
				</tr>
				<tr>
					<td>3</td>
					<td>4</td>
				</tr>
			</tbody>
		</table>
	);

	const row = ( key, label, selected ) => (
		<Button
			key={ key || '__none' }
			onClick={ () => onChange( key ) }
			aria-pressed={ selected }
			style={ {
				display: 'block',
				width: '100%',
				height: 'auto',
				padding: '6px',
				marginBottom: '6px',
				borderRadius: '4px',
				textAlign: 'left',
				boxShadow: selected
					? '0 0 0 2px var(--wp-admin-theme-color)'
					: 'inset 0 0 0 1px #ddd',
			} }
		>
			{ key ? (
				sample( key )
			) : (
				<span style={ { fontStyle: 'italic', opacity: 0.7 } }>
					{ option.placeholder || '— Select —' }
				</span>
			) }
			<span style={ { display: 'block', marginTop: '4px', fontSize: '11px' } }>{ label }</span>
		</Button>
	);

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			<div>
				{ allowNone && row( '', choices[ '' ] || 'None', current === '' ) }
				{ Object.keys( choices )
					.filter( ( key ) => key !== '' )
					.map( ( key ) => row( key, choices[ key ] || key, current === key ) ) }
			</div>
		</BaseControl>
	);
}
