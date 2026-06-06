/**
 * Code-editor option type — initialises wp.codeEditor (WordPress core's
 * CodeMirror bundle) on each .fw-option-type-code-editor option instance.
 *
 * Hooks the Unyson 'fw:options:init' event via the fwEvents bus (NOT jQuery's
 * DOM event system — Unyson's event doesn't bubble through the DOM). This
 * matches the proven pattern used by the built-in switch option type.
 *
 * Falls back gracefully: if wp.codeEditor isn't available (user disabled
 * syntax highlighting in their profile, or the script enqueue didn't reach
 * the page) we log a console.warn and leave the textarea unmodified.
 */
jQuery( document ).ready( function ( $ ) {
	'use strict';

	var optionTypeClass = 'fw-option-type-code-editor';

	function buildSettings ( textarea ) {
		var mode   = textarea.dataset.mode   || 'htmlmixed';
		var height = parseInt( textarea.dataset.height, 10 ) || 300;

		var base = ( window.wp && wp.codeEditor && wp.codeEditor.defaultSettings )
				? $.extend( true, {}, wp.codeEditor.defaultSettings )
				: {};

		base.codemirror = $.extend( {}, base.codemirror || {}, {
			mode             : mode,
			lineNumbers      : true,
			lineWrapping     : true,
			indentUnit       : 2,
			tabSize          : 2,
			autoCloseTags    : true,
			autoCloseBrackets: true,
			matchBrackets    : true
		} );

		return { settings: base, height: height };
	}

	function initOne ( $option ) {
		if ( $option.hasClass( 'fw-option-initialized' ) ) { return; }
		$option.addClass( 'fw-option-initialized' );

		var textarea = $option.find( 'textarea.fw-option-code-editor' ).get( 0 );
		if ( ! textarea ) { return; }

		// Bail gracefully when wp.codeEditor isn't loaded.
		if ( ! ( window.wp && wp.codeEditor && typeof wp.codeEditor.initialize === 'function' ) ) {
			if ( window.console && console.warn ) {
				console.warn(
					'[fw-option-type-code-editor] wp.codeEditor is not loaded — falling back to plain textarea. ' +
					'Check that wp_enqueue_code_editor() ran in the admin page that hosts this option.'
				);
			}
			return;
		}

		var conf   = buildSettings( textarea );
		var editor = wp.codeEditor.initialize( textarea, conf.settings );

		if ( editor && editor.codemirror ) {
			editor.codemirror.setSize( '100%', conf.height );

			// Mirror CodeMirror → textarea on every change so Unyson's form
			// serializer picks up the latest value when the popup is saved.
			editor.codemirror.on( 'change', function ( cm ) {
				textarea.value = cm.getValue();
				$( textarea ).trigger( 'change' );
			} );

			// CodeMirror needs a kick when it's instantiated in an initially
			// hidden container (Unyson popup). Refresh once on the next tick.
			setTimeout( function () { editor.codemirror.refresh(); }, 50 );

			initPlaceholder( $option, textarea, editor.codemirror );
		}
	}

	/**
	 * Greyed-out sample shown only while the document is empty AND the editor is
	 * unfocused. Clears as soon as the user focuses or types. CodeMirror has no
	 * native placeholder in WP's bundle, so we overlay one on the wrapper.
	 */
	function initPlaceholder ( $option, textarea, cm ) {
		var text = textarea.getAttribute( 'data-placeholder' ) || '';
		if ( ! text ) { return; }

		var $wrap = $( cm.getWrapperElement() );
		$wrap.css( 'position', 'relative' );

		var $ph = $( '<pre class="fw-code-editor-placeholder"></pre>' ).text( text );
		$wrap.append( $ph );

		function sync () {
			var empty = cm.getValue().length === 0;
			$ph.toggle( empty && ! cm.hasFocus() );
		}

		cm.on( 'focus', function () { $ph.hide(); } );
		cm.on( 'blur', sync );
		cm.on( 'change', sync );
		// Clicking the overlay should drop into the editor.
		$ph.on( 'mousedown', function () { cm.focus(); } );

		sync();
	}

	fwEvents.on( 'fw:options:init', function ( data ) {
		data.$elements.find( '.' + optionTypeClass + ':not(.fw-option-initialized)' ).each( function () {
			initOne( $( this ) );
		} );
	} );

} );
