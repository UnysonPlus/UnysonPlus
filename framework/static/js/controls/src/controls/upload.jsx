/**
 * `upload` option type — React renderer.
 *
 * ## Value fidelity is the whole job here
 *
 * FW_Option_Type_Upload stores an attachment as:
 *
 *     { attachment_id: 123, url: '//example.com/wp-content/uploads/x.jpg' }
 *
 * Note the URL is **protocol-relative** — the PHP side strips `http(s):` (see
 * get_attachment_info()). The WordPress media modal hands back an absolute URL,
 * so it is normalised the same way before saving. Without that, a value written
 * through React would differ from the identical value written through the page
 * builder, and anything comparing them (presets, the converter, import
 * fingerprints) would see a spurious change.
 *
 * `wp.mediaUtils.MediaUpload` is used rather than the block editor's copy so the
 * control also works on plain React admin screens, not only inside the editor.
 */

const { Button, BaseControl, Flex, FlexItem } = wp.components;

/** The media picker, wherever this WordPress exposes it. */
function getMediaUpload() {
	if ( wp.mediaUtils && wp.mediaUtils.MediaUpload ) {
		return wp.mediaUtils.MediaUpload;
	}

	if ( wp.blockEditor && wp.blockEditor.MediaUpload ) {
		return wp.blockEditor.MediaUpload;
	}

	return null;
}

/** Match the PHP side: store URLs protocol-relative. */
function toProtocolRelative( url ) {
	return typeof url === 'string' ? url.replace( /^https?:\/\//, '//' ) : '';
}

/** Make a protocol-relative URL loadable in an <img>. */
function toDisplayUrl( url ) {
	return typeof url === 'string' && url.startsWith( '//' )
		? window.location.protocol + url
		: url;
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Object}   props.value    Current value — { attachment_id, url }.
 * @param {Function} props.onChange Called with the next value object.
 */
export default function Upload( { option = {}, value, onChange } ) {
	const MediaUpload = getMediaUpload();
	const current = value && value.url ? value : null;

	if ( ! MediaUpload ) {
		return (
			<BaseControl label={ option.label || '' } __nextHasNoMarginBottom>
				<p>
					{ 'The media library is not available on this screen.' }
				</p>
			</BaseControl>
		);
	}

	const onSelect = ( media ) => {
		onChange( {
			attachment_id: media.id,
			url: toProtocolRelative( media.url ),
		} );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ current && (
				<img
					src={ toDisplayUrl( current.url ) }
					alt=""
					style={ {
						display: 'block',
						maxWidth: '100%',
						height: 'auto',
						marginBottom: '8px',
						borderRadius: '2px',
					} }
				/>
			) }

			<MediaUpload
				onSelect={ onSelect }
				allowedTypes={ option.images_only === false ? undefined : [ 'image' ] }
				value={ current ? current.attachment_id : undefined }
				render={ ( { open } ) => (
					<Flex justify="flex-start" gap={ 2 }>
						<FlexItem>
							<Button variant="secondary" onClick={ open }>
								{ current ? 'Replace' : 'Select image' }
							</Button>
						</FlexItem>
						{ current && (
							<FlexItem>
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => onChange( '' ) }
								>
									{ 'Remove' }
								</Button>
							</FlexItem>
						) }
					</Flex>
				) }
			/>
		</BaseControl>
	);
}
