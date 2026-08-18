/**
 * `wp-editor` option type — React renderer.
 *
 * ## Why this is a code field and not a rich-text field
 *
 * The obvious choice is Gutenberg's `RichText`, and it is the wrong one here.
 *
 * `RichText` edits the contents of ONE block-level element. This option stores a
 * whole HTML fragment, which routinely contains several: `<p>a</p><p>b</p>`, a
 * `<ul>`, a heading. There is no lossless way to hand that to a single `RichText`
 * — you either strip the outer tag (which mangles anything with more than one
 * block) or nest tags that were not nested. Both silently corrupt content that a
 * user authored in the page builder, and neither failure is visible until someone
 * opens the page again.
 *
 * So the control edits the markup directly. It is plainer than a WYSIWYG, and it
 * is lossless — which is the right trade for a *second* authoring surface whose
 * whole justification is that it agrees with the first one. Rich editing stays in
 * the page builder, where the real editor lives.
 *
 * ## The save transform, and why it is safe
 *
 * With `wpautop` on (the default), `_get_value_from_input()` runs the value
 * through `wpautop()` and strips newlines — so typing plain text stores
 * `<p>plain text</p>`. Measured against the real option type, that transform is
 * IDEMPOTENT for every shape tested (plain text, blank-line paragraphs,
 * already-wrapped `<p>`, inline markup, lists, empty), so a value can be saved
 * repeatedly without drifting. That is what makes editing the markup directly
 * safe rather than a slow corruption.
 */

const { TextareaControl, BaseControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry.
 * @param {string}   props.value    Current HTML string.
 * @param {Function} props.onChange Called with the next HTML string.
 */
export default function WpEditor( { option = {}, value = '', onChange } ) {
	// `wpautop` defaults to true in the PHP option type, so say what will happen
	// to plain text rather than letting the user discover it after saving.
	const autop = false !== option.wpautop;

	const help =
		option.desc ||
		( autop
			? 'HTML is allowed. Plain text is wrapped in paragraphs when saved.'
			: 'HTML is allowed and stored exactly as written.' );

	return (
		<BaseControl __nextHasNoMarginBottom>
			<TextareaControl
				label={ option.label || '' }
				help={ help }
				value={ 'string' === typeof value ? value : '' }
				rows={ option.editor_height ? Math.max( 4, Math.round( option.editor_height / 24 ) ) : 6 }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		</BaseControl>
	);
}
