<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Code editor option type.
 *
 * Wraps WordPress core's bundled CodeMirror (available since WP 4.9 via
 * wp_enqueue_code_editor()) to provide syntax-highlighted editing for HTML,
 * CSS, JavaScript, PHP, JSON, XML and other languages.
 *
 * Storage shape is a plain string — identical to the 'textarea' option type —
 * so switching an existing textarea field to 'code-editor' is data-shape
 * compatible. Previously-saved values continue to render as-is in the editor
 * when the page is re-opened; re-saving stores the same string back to the
 * same key. No migration code, no DB rewrite required.
 *
 * Falls back gracefully to a plain textarea when the user has disabled syntax
 * highlighting in their WordPress profile (wp_enqueue_code_editor() returns
 * false in that case; our JS detects it and leaves the textarea untouched).
 *
 * Example option-config consumer:
 *
 *     'code' => array(
 *         'type'   => 'code-editor',
 *         'label'  => __( 'Code', 'fw' ),
 *         'mode'   => 'htmlmixed',
 *         'height' => 400,
 *     ),
 */
class FW_Option_Type_Code_Editor extends FW_Option_Type {

	public function get_type() {
		return 'code-editor';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'  => '',
			// Friendly mode names — mapped to MIME types in _enqueue_static().
			//   htmlmixed | html | javascript | js | css | php | json | xml
			'mode'   => 'htmlmixed',
			'height' => 300, // pixels
			// Optional greyed-out sample shown only while the editor is empty AND
			// unfocused; clears on focus/typing. Works for both CodeMirror and the
			// plain-textarea fallback. Does not affect the saved value.
			'placeholder' => '',
		);
	}

	/**
	 * @internal
	 */
	protected function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {

		// Friendly mode → MIME type for wp_enqueue_code_editor().
		// (CodeMirror itself takes the friendly mode strings; WP's wrapper takes MIME types.)
		$mime_map = array(
			'htmlmixed'               => 'text/html',
			'html'                    => 'text/html',
			'javascript'              => 'application/javascript',
			'js'                      => 'application/javascript',
			'css'                     => 'text/css',
			'php'                     => 'application/x-httpd-php',
			'application/x-httpd-php' => 'application/x-httpd-php',
			'json'                    => 'application/json',
			'xml'                     => 'application/xml',
		);
		$mode = isset( $option['mode'] ) ? (string) $option['mode'] : 'htmlmixed';
		$type = isset( $mime_map[ $mode ] ) ? $mime_map[ $mode ] : 'text/html';

		// Registers wp.codeEditor + the chosen syntax-mode bundle. Returns false
		// when the user disabled syntax highlighting — our JS handles that case
		// by leaving the textarea unmodified.
		wp_enqueue_code_editor( array( 'type' => $type ) );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
			array(),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'fw-events', 'code-editor' ),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 *
	 * Renders a wrapper <div class="fw-option-type-code-editor"> around the
	 * inner <textarea class="fw-option-code-editor"> — matches the structural
	 * pattern of the built-in `switch` option type (and most other Unyson
	 * option types), which is what the companion JS hooks rely on:
	 *
	 *     fwEvents.on('fw:options:init', function (data) {
	 *         data.$elements.find('.fw-option-type-code-editor:not(...)')
	 *     });
	 *
	 * Without the wrapper class, the JS could never discover the option
	 * instance and CodeMirror would never initialise (which is why a plain
	 * <textarea>-only render rendered correctly but stayed un-enhanced).
	 */
	protected function _render( $id, $option, $data ) {

		$value = isset( $data['value'] ) && is_string( $data['value'] ) ? $data['value'] : '';

		// Inner textarea — gets the form 'name', the editor data-* hints, and
		// our JS hook class.
		$placeholder = isset( $option['placeholder'] ) ? (string) $option['placeholder'] : '';

		$input_attr = array(
			'name'        => $option['attr']['name'],
			'id'          => $option['attr']['id'],
			'class'       => 'fw-option-code-editor',
			'data-mode'   => isset( $option['mode'] )   ? (string) $option['mode']    : 'htmlmixed',
			'data-height' => isset( $option['height'] ) ? (int) $option['height']     : 300,
			'rows'        => 12, // sensible fallback when CodeMirror is disabled
		);
		if ( $placeholder !== '' ) {
			// Native attr powers the plain-textarea fallback; data-attr powers the
			// CodeMirror overlay added by our JS.
			$input_attr['placeholder']      = $placeholder;
			$input_attr['data-placeholder'] = $placeholder;
		}

		// Wrapper div — picks up Unyson's framework-provided attrs (id/data-*/etc.)
		// minus the input-only attrs that shouldn't leak onto a container.
		unset(
			$option['attr']['name'],
			$option['attr']['value'],
			$option['attr']['type']
		);

		// Explicitly ensure the .fw-option-type-code-editor class is on the wrapper —
		// some Unyson versions auto-inject it via $option['attr']['class'], others
		// don't. Defensive append guarantees the JS hook always finds us.
		$wrapper_class             = 'fw-option-type-code-editor';
		$existing_wrapper_class    = isset( $option['attr']['class'] ) ? (string) $option['attr']['class'] : '';
		if ( strpos( $existing_wrapper_class, $wrapper_class ) === false ) {
			$option['attr']['class'] = trim( $existing_wrapper_class . ' ' . $wrapper_class );
		}

		return '<div ' . fw_attr_to_html( $option['attr'] ) . '>'
		     .   '<textarea ' . fw_attr_to_html( $input_attr ) . '>' . esc_textarea( $value ) . '</textarea>'
		     . '</div>';
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		// Storage is a plain string — same as the textarea option type.
		// is_null check matches Unyson's convention for "field absent in submission".
		return is_null( $input_value ) ? (string) $option['value'] : (string) $input_value;
	}
}
