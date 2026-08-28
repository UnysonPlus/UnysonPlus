/**
 * `mailer` option type — React renderer.
 *
 * Read-only, for the same reason `gmap-key` is: it is a SITE-WIDE setting.
 *
 * The option type declares
 * `'fw-storage' => array( 'type' => 'wp-option', 'wp-option' => 'fw_ext_settings_options:mailer' )`,
 * so saving it through the page builder writes the site's mail configuration —
 * SMTP host, credentials, send method — not a value on the element.
 *
 * A block's attributes never pass through that storage layer. An editable field
 * here would write mail settings into one block's attributes, where nothing
 * reads them: mail would keep using the site configuration, the fields would
 * look saved, and the two would disagree permanently. On a contact form, the
 * cost of that confusion is mail that silently does not arrive.
 *
 * Saying where the setting lives is more useful than a field that lies.
 */

const { BaseControl, Notice } = wp.components;

/**
 * @param {Object} props
 * @param {Object} props.option The option schema entry.
 * @param {Object} props.value  The stored value, if any reaches the editor.
 */
export default function Mailer( { option = {}, value } ) {
	const configured = value && typeof value === 'object' && Object.keys( value ).length > 0;

	return (
		<BaseControl label={ option.label || 'Mailer' } __nextHasNoMarginBottom>
			<Notice status={ configured ? 'success' : 'warning' } isDismissible={ false }>
				{ configured
					? 'Mail delivery is configured for the whole site. Change it under Unyson+ → Settings → Mailer, not per form.'
					: 'Mail delivery is not configured yet. Set it up under Unyson+ → Settings → Mailer — a form cannot send without it.' }
			</Notice>
		</BaseControl>
	);
}
