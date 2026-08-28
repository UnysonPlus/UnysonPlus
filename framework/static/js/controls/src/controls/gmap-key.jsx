/**
 * `gmap-key` option type — React renderer.
 *
 * Read-only, deliberately. This control does not let you edit the key, and that
 * is the correct behaviour rather than an unfinished one.
 *
 * The Google Maps key is a SITE-WIDE setting: the option type declares
 * `'fw-storage' => array( 'type' => 'wp-option', … )`, so saving it through the
 * page builder writes a `wp_option`, not a value on the element.
 *
 * A block's attributes never pass through that storage layer — they are written
 * straight into post content. An editable field here would therefore write the
 * key into this one block's attributes, where nothing reads it: the map would go
 * on using the site-wide key, the field would look saved, and the two would
 * disagree silently and permanently. Someone would eventually conclude the key
 * "doesn't work".
 *
 * Saying where the setting actually lives is more useful than a field that lies.
 */

const { BaseControl, Notice } = wp.components;

/**
 * @param {Object} props
 * @param {Object} props.option The option schema entry.
 * @param {string} props.value  The key, as it reaches the editor.
 */
export default function GmapKey( { option = {}, value } ) {
	const set = typeof value === 'string' && value.trim() !== '';

	return (
		<BaseControl label={ option.label || 'Google Maps API key' } __nextHasNoMarginBottom>
			<Notice status={ set ? 'success' : 'warning' } isDismissible={ false }>
				{ set
					? 'A site-wide key is set. It is stored for the whole site rather than per block — change it in the page builder or Theme Settings.'
					: 'No site-wide key is set, and a Google map will not load without one. Add it in the page builder or Theme Settings.' }
			</Notice>
		</BaseControl>
	);
}
