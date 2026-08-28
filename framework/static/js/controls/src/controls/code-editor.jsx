/**
 * `code-editor` option type — React renderer.
 *
 * A monospace textarea. The stored value is the code, as a plain string.
 *
 * ## No CodeMirror here, deliberately
 *
 * The PHP renderer loads CodeMirror for syntax highlighting, and this control
 * does not. Two reasons, in order of how much they matter:
 *
 * 1. WordPress's bundled editor (`wp.codeEditor`) initialises against a real
 *    textarea and manages its own DOM. Wrapping that in a React component means
 *    reconciling two things that both believe they own the node — the class of
 *    integration that works until an unrelated re-render wipes the buffer, which
 *    for a code field means losing the user's work.
 * 2. A sidebar column is the wrong shape for editing code anyway. Highlighting a
 *    line that wraps four times buys very little.
 *
 * A plain monospace field is honest about what it is, and it cannot eat what you
 * typed. Real code editing stays in the page builder, where the editor has room
 * and a stable host.
 *
 * `spellCheck` is off and autocapitalise/autocorrect are disabled: browsers
 * "helpfully" capitalising a variable name is a genuine hazard in a code field,
 * not a cosmetic one.
 */

const { TextareaControl } = wp.components;

/**
 * @param {Object}   props
 * @param {Object}   props.option   The option schema entry (mode, height, placeholder).
 * @param {string}   props.value    Current code.
 * @param {Function} props.onChange Called with the next string.
 */
export default function CodeEditor( { option = {}, value = '', onChange } ) {
	// `height` is declared in pixels for the CodeMirror instance; reuse it so a
	// field the schema wanted tall is tall here too, within reason for a sidebar.
	const height = Math.max( 120, Math.min( 400, parseInt( option.height, 10 ) || 300 ) );

	const help = option.desc
		? option.desc
		: option.mode
			? `${ option.mode } — edited as plain text here; use the page builder for syntax highlighting.`
			: undefined;

	return (
		<TextareaControl
			label={ option.label || '' }
			help={ help }
			value={ value ?? '' }
			placeholder={ option.placeholder || undefined }
			onChange={ onChange }
			rows={ Math.round( height / 20 ) }
			spellCheck={ false }
			autoCapitalize="off"
			autoCorrect="off"
			autoComplete="off"
			style={ { fontFamily: 'Menlo, Consolas, monospace', fontSize: '12px' } }
			__nextHasNoMarginBottom
		/>
	);
}
