/**
 * Button Set option type.
 *
 * The control is native radio/checkbox inputs styled via CSS `:checked` sibling
 * selectors, so selection, keyboard navigation and form serialization need no
 * JavaScript at all. This file exists for exactly one behaviour the platform
 * does not provide: deselecting an already-checked radio, for options that
 * opted in with 'allow_deselect' => true (the builder's "no override" case).
 */
jQuery( function ( $ ) {
	'use strict';

	var SELECTOR = '.fw-option-type-button-set[data-allow-deselect="true"]';

	/**
	 * A click on a <label> re-checks its radio before the click handler runs, so
	 * the checked state is useless by then. Record it on mousedown/keydown
	 * instead, while it still reflects what the user saw.
	 */
	$( document ).on( 'mousedown', SELECTOR + ' .fw-button-set-button', function () {
		var input = document.getElementById( $( this ).attr( 'for' ) );

		if ( input ) {
			$( input ).data( 'fwWasChecked', input.checked );
		}
	} );

	$( document ).on( 'click', SELECTOR + ' .fw-button-set-button', function ( e ) {
		var $label = $( this ),
			input  = document.getElementById( $label.attr( 'for' ) );

		if ( ! input || ! $( input ).data( 'fwWasChecked' ) ) {
			return;
		}

		// It was already the active choice: treat this click as "clear it".
		e.preventDefault();

		input.checked = false;
		$( input ).data( 'fwWasChecked', false );

		// Let the builder, conditional-logic ("show if") and any listening
		// option type react the same way they would to a normal selection.
		$( input ).trigger( 'change' );
	} );

	/**
	 * Space/Enter on a focused, already-checked radio should clear it too, so the
	 * keyboard path matches the mouse path.
	 */
	$( document ).on( 'keydown', SELECTOR + ' .fw-button-set-input', function ( e ) {
		if ( e.key !== ' ' && e.key !== 'Enter' && e.which !== 32 && e.which !== 13 ) {
			return;
		}

		if ( ! this.checked ) {
			return;
		}

		e.preventDefault();

		this.checked = false;

		$( this ).trigger( 'change' );
	} );
} );
