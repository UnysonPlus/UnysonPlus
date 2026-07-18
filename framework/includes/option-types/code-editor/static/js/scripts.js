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

			// CodeMirror measures its gutter + viewport from the container size at init
			// time. When created inside a hidden/collapsed box — or before its grid column
			// has laid out — that measurement is wrong and the text is clipped to the LEFT
			// under the line-number gutter. Refresh it (a) after layout settles (double rAF
			// + a fallback) and (b) the first time it actually becomes visible — covering a
			// new row added open AND a collapsed row later expanded.
			var kick = function () {
				try {
					editor.codemirror.refresh();
					if ( typeof editor.codemirror._fwSyncPlaceholder === 'function' ) {
						editor.codemirror._fwSyncPlaceholder();
					}
				} catch ( e ) {}
			};
			requestAnimationFrame( function () { requestAnimationFrame( kick ); } );
			setTimeout( kick, 150 );
			if ( window.IntersectionObserver ) {
				var io = new IntersectionObserver( function ( entries ) {
					for ( var i = 0; i < entries.length; i++ ) {
						if ( entries[ i ].isIntersecting ) { kick(); }
					}
				} );
				io.observe( editor.codemirror.getWrapperElement() );
			}

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

		// Sit the overlay PAST the gutter, aligned with line 1's content, so the absolutely
		// positioned line-number column never covers its first characters. Re-aligned on every
		// CodeMirror refresh (the gutter width changes once the editor is properly measured).
		function place () {
			var gutter = cm.getGutterElement();
			$ph.css( 'left', ( ( gutter ? gutter.offsetWidth : 0 ) + 4 ) + 'px' );
		}

		function sync () {
			var empty = cm.getValue().length === 0;
			$ph.toggle( empty && ! cm.hasFocus() );
		}

		// Exposed so the refresh `kick` (initOne) can re-place + re-sync after a measure.
		cm._fwSyncPlaceholder = function () { place(); sync(); };

		cm.on( 'focus', function () { $ph.hide(); } );
		cm.on( 'blur', function () { place(); sync(); } );
		cm.on( 'change', sync );

		place();
		sync();
	}

	fwEvents.on( 'fw:options:init', function ( data ) {
		data.$elements.find( '.' + optionTypeClass + ':not(.fw-option-initialized)' ).each( function () {
			initOne( $( this ) );
		} );
	} );

	// CodeMirror measures its gutter + viewport from the container size at init time.
	// Inside a COLLAPSED addable-box row (postboxes start `.closed`) that measurement is
	// wrong, so once the row is expanded the text is clipped to the LEFT under the
	// line-number gutter until the editor is refreshed. The one-shot refresh in initOne()
	// runs while the box is still hidden, and Unyson fires no box-open event — so refresh
	// every CodeMirror inside a postbox whenever it is expanded (the header toggle removes
	// `.closed`). Delegated + guarded, so it's a no-op on pages without CodeMirror.
	$( document ).on( 'click', '.fw-postbox .postbox-header, .fw-postbox > .hndle, .fw-postbox .handlediv', function () {
		var $box = $( this ).closest( '.fw-postbox' );
		setTimeout( function () {
			if ( $box.hasClass( 'closed' ) ) { return; } // only when it ended up OPEN
			$box.find( '.CodeMirror' ).each( function () {
				if ( this.CodeMirror && typeof this.CodeMirror.refresh === 'function' ) {
					this.CodeMirror.refresh();
				}
			} );
		}, 60 );
	} );

} );
