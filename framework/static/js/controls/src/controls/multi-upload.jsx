/**
 * `multi-upload` option type — React renderer.
 *
 * A list of media attachments — a gallery's images, an image sequence's frames.
 * The stored value is an array of objects:
 *
 * ```
 * [ { attachment_id: 12, url: '//example.com/wp-content/uploads/a.jpg' }, … ]
 * ```
 *
 * ## Two details taken from the PHP rather than guessed
 *
 * - The URL is stored **protocol-relative**. `get_attachments_info()` runs
 *   `preg_replace( '/^https?:\/\//', '//', $url )` on every one, so a value
 *   carrying `https://…` differs from what the page builder saves, and a site
 *   served over both schemes would pin the wrong one.
 * - The key is `attachment_id`, not `id`. The element views read that name.
 *
 * This control emits the STORED shape rather than the wire shape (a list of bare
 * ids), because a block's attributes go straight to the element's view without
 * passing through `_get_value_from_input()` — nothing would expand ids into
 * objects on that path. See the note in class-fw-extension-gutenberg.php.
 */

const { BaseControl, Button } = wp.components;
const { MediaUpload, MediaUploadCheck } = wp.blockEditor;

/**
 * Normalise a WordPress media object into the option type's stored shape.
 *
 * @param {Object} media A media object from MediaUpload.
 * @return {Object} { attachment_id, url }.
 */
function toStored( media ) {
	return {
		attachment_id: media.id,
		url: String( media.url || '' ).replace( /^https?:\/\//, '//' ),
	};
}

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {Array}    props.value    Current array of attachment objects.
 * @param {Function} props.onChange Called with the next array.
 */
export default function MultiUpload( { option = {}, value, onChange } ) {
	const items = Array.isArray( value ) ? value : [];

	const ids = items
		.map( ( item ) => ( item && item.attachment_id ? parseInt( item.attachment_id, 10 ) : null ) )
		.filter( Boolean );

	const remove = ( index ) => onChange( items.filter( ( _item, i ) => i !== index ) );

	const move = ( index, delta ) => {
		const target = index + delta;

		if ( target < 0 || target >= items.length ) {
			return;
		}

		const next = items.slice();
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
	};

	return (
		<BaseControl
			label={ option.label || '' }
			help={ option.desc || undefined }
			__nextHasNoMarginBottom
		>
			{ items.length > 0 && (
				<div
					style={ {
						display: 'grid',
						gridTemplateColumns: 'repeat(3, 1fr)',
						gap: '6px',
						marginBottom: '8px',
					} }
				>
					{ items.map( ( item, index ) => (
						<div
							key={ `${ item.attachment_id }-${ index }` }
							style={ {
								position: 'relative',
								border: '1px solid #ddd',
								borderRadius: '4px',
								overflow: 'hidden',
							} }
						>
							{ /*
							  * A protocol-relative src resolves against the editor's own
							  * scheme, which is exactly what it will do on the front end.
							  */ }
							<img
								src={ item.url }
								alt=""
								style={ {
									display: 'block',
									width: '100%',
									height: '54px',
									objectFit: 'cover',
								} }
							/>
							<div
								style={ {
									display: 'flex',
									justifyContent: 'center',
									gap: '1px',
									padding: '2px 0',
								} }
							>
								<Button
									icon="arrow-left-alt2"
									size="small"
									label="Move earlier"
									disabled={ index === 0 }
									onClick={ () => move( index, -1 ) }
								/>
								<Button
									icon="arrow-right-alt2"
									size="small"
									label="Move later"
									disabled={ index === items.length - 1 }
									onClick={ () => move( index, 1 ) }
								/>
								<Button
									icon="trash"
									size="small"
									isDestructive
									label="Remove"
									onClick={ () => remove( index ) }
								/>
							</div>
						</div>
					) ) }
				</div>
			) }

			<MediaUploadCheck>
				<MediaUpload
					multiple
					gallery
					addToGallery
					allowedTypes={ option.images_only === false ? undefined : [ 'image' ] }
					value={ ids }
					onSelect={ ( media ) =>
						onChange( ( Array.isArray( media ) ? media : [ media ] ).map( toStored ) )
					}
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open } __next40pxDefaultSize>
							{ items.length ? 'Edit selection' : 'Add media' }
						</Button>
					) }
				/>
			</MediaUploadCheck>
		</BaseControl>
	);
}
