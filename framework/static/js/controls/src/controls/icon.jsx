/**
 * `icon` option type — React renderer.
 *
 * ## One option type, several value shapes
 *
 * The stored value is an object discriminated by `type`:
 *
 *   { type: 'none' }
 *   { type: 'icon-font',     'icon-class': 'fa fa-star', … }
 *   { type: 'emoji',         char: '★' }
 *   { type: 'custom-upload', 'attachment-id': 12, url: '…' }
 *   { type: 'svg',           'svg-source': 'library'|'upload'|'inline', … }
 *
 * A **legacy bare string** is also accepted — `normalize_value()` turns
 * `'fa fa-star'` into `{ type: 'icon-font', 'icon-class': 'fa fa-star' }` and
 * `''` into `{ type: 'none' }`. That is the older shape still sitting in saved
 * values, so this control reads it and writes the canonical object form.
 *
 * ## Three keys are DERIVED, like typography
 *
 * For `icon-font`, the server resolves `icon-class-without-root`, `pack-name` and
 * `pack-css-uri` from the class via its packs loader. The control emits only
 * `icon-class` and lets the server derive the rest — those values depend on which
 * packs are installed, which is not knowable client-side.
 *
 * ## `svg` values are preserved, not edited
 *
 * The SVG library browser is server-side (icon packs, uploads, pasted markup with
 * sanitisation on the way in). Rather than offer a partial editor that could drop
 * `svg-id` or unsanitised markup, an existing `svg` value is shown read-only with
 * a pointer to the page builder — and left completely untouched. Destroying a
 * value the control cannot fully represent would be the worst outcome here.
 */

const { BaseControl, SelectControl, TextControl, Button, Flex, FlexItem, Notice } = wp.components;

/** The media picker, wherever this WordPress exposes it (matches upload.jsx). */
function getMediaUpload() {
	if ( wp.mediaUtils && wp.mediaUtils.MediaUpload ) {
		return wp.mediaUtils.MediaUpload;
	}

	if ( wp.blockEditor && wp.blockEditor.MediaUpload ) {
		return wp.blockEditor.MediaUpload;
	}

	return null;
}

/** Mirrors FW_Option_Type_Icon::normalize_value(). */
function normalize( value ) {
	if ( 'string' === typeof value ) {
		return '' === value ? { type: 'none' } : { type: 'icon-font', 'icon-class': value };
	}

	if ( ! value || 'object' !== typeof value || ! value.type ) {
		return { type: 'none' };
	}

	return value;
}

/** Types this control can edit. `svg` is read-only — see the file docblock. */
const EDITABLE_TYPES = [
	{ value: 'none', label: 'None' },
	{ value: 'icon-font', label: 'Icon font' },
	{ value: 'emoji', label: 'Emoji' },
	{ value: 'custom-upload', label: 'Custom image' },
];

/**
 * @param {Object}          props
 * @param {Object}          props.option   The option schema entry.
 * @param {Object|string}   props.value    Current value — object, or legacy string.
 * @param {Function}        props.onChange Called with the next value object.
 */
export default function Icon( { option = {}, value, onChange } ) {
	const current = normalize( value );
	const MediaUpload = getMediaUpload();

	if ( 'svg' === current.type ) {
		return (
			<BaseControl label={ option.label || '' } help={ option.desc || undefined } __nextHasNoMarginBottom>
				<Notice status="info" isDismissible={ false }>
					{ 'This icon uses an SVG. Edit it in the page builder — the SVG library, uploads and pasted markup are handled on the server.' }
				</Notice>
			</BaseControl>
		);
	}

	// Switching type starts a clean value: carrying stale keys across would leave
	// e.g. an `icon-class` on a `custom-upload` value, which the server keeps.
	const setType = ( type ) => onChange( { type } );

	return (
		<BaseControl label={ option.label || '' } help={ option.desc || undefined } __nextHasNoMarginBottom>
			<SelectControl
				label="Icon type"
				value={ current.type }
				options={ EDITABLE_TYPES }
				onChange={ setType }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			{ 'icon-font' === current.type && (
				<TextControl
					label="Icon class"
					help="e.g. fa fa-star — the icon pack browser lives in the page builder."
					value={ current[ 'icon-class' ] || '' }
					onChange={ ( next ) => onChange( { type: 'icon-font', 'icon-class': next } ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ 'emoji' === current.type && (
				<TextControl
					label="Emoji"
					value={ current.char || '' }
					onChange={ ( next ) => onChange( { type: 'emoji', char: next } ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			) }

			{ 'custom-upload' === current.type && (
				<Flex align="center" gap={ 2 } style={ { marginTop: 8 } }>
					{ current.url && (
						<FlexItem>
							<img src={ current.url } alt="" style={ { maxWidth: 48, height: 'auto', display: 'block' } } />
						</FlexItem>
					) }
					<FlexItem>
						{ MediaUpload ? (
							<MediaUpload
								allowedTypes={ [ 'image' ] }
								value={ current[ 'attachment-id' ] || undefined }
								onSelect={ ( media ) =>
									onChange( {
										type: 'custom-upload',
										'attachment-id': media.id,
										url: media.url,
									} )
								}
								render={ ( { open } ) => (
									<Button variant="secondary" onClick={ open }>
										{ current.url ? 'Replace image' : 'Select image' }
									</Button>
								) }
							/>
						) : (
							<Notice status="warning" isDismissible={ false }>
								{ 'The media picker is unavailable on this screen.' }
							</Notice>
						) }
					</FlexItem>
					{ current.url && (
						<FlexItem>
							<Button variant="tertiary" onClick={ () => onChange( { type: 'none' } ) }>
								{ 'Remove' }
							</Button>
						</FlexItem>
					) }
				</Flex>
			) }
		</BaseControl>
	);
}
