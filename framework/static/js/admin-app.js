/**
 * The admin app shell (FW_Admin_App).
 *
 * Two things only: remember whether the WordPress menu is hidden (per user, so
 * it survives a reload), and open the sidebar over the page at narrow widths
 * where it is off-canvas. Everything inside the content column is the app's
 * own, and this script never touches it.
 */
( function () {
	'use strict';

	var app = document.getElementById( 'fw-app' );

	if ( ! app ) {
		return;
	}

	var focusBtn = app.querySelector( '.fw-app-focus-toggle' );

	if ( focusBtn ) {
		focusBtn.addEventListener( 'click', function () {
			var on = ! document.body.classList.contains( 'fw-app-focus' );

			document.body.classList.toggle( 'fw-app-focus', on );
			focusBtn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			focusBtn.querySelector( '.fw-app-side__text' ).textContent = on ? focusBtn.dataset.on : focusBtn.dataset.off;

			var body = new FormData();

			body.append( 'action', app.dataset.focusAction );
			body.append( '_ajax_nonce', app.dataset.focusNonce );
			body.append( 'on', on ? '1' : '0' );

			// Remembering the choice is a convenience; a failure must not stop
			// the menu from having moved.
			fetch( app.dataset.ajax, { method: 'POST', body: body, credentials: 'same-origin' } ).catch( function () {} );
		} );
	}

	var menuBtn = app.querySelector( '.fw-app-top__menu' );

	function setMenu( open ) {
		app.classList.toggle( 'is-menu-open', open );

		if ( menuBtn ) {
			menuBtn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
	}

	if ( menuBtn ) {
		menuBtn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			setMenu( ! app.classList.contains( 'is-menu-open' ) );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( app.classList.contains( 'is-menu-open' ) && ! e.target.closest( '.fw-app-side' ) ) {
			setMenu( false );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			setMenu( false );
		}
	} );
}() );
