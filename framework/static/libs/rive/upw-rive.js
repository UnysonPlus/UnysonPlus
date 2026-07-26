/**
 * UnysonPlus Rive icon runtime.
 *
 * Hydrates every <span class="upw-rive" data-src="…"> on the page into a Rive
 * animation drawn on a <canvas>, using the bundled @rive-app/canvas runtime
 * (WASM). The element carries:
 *   data-src     — URL of the .riv file
 *   data-trigger — loop | once | hover | click   (default: loop)
 *
 * Loads lazily: only enqueued when a Rive icon is actually rendered. The WASM
 * URL is pinned to our bundled copy (window.upwRiveWasm) BEFORE any instance is
 * created, so the runtime never reaches out to a CDN. Re-exposed as
 * window.upwRiveInit(root) so the builder preview + AJAX-injected content can
 * hydrate freshly-added elements.
 */
( function () {
	'use strict';

	var wasmSet = false;

	// Point the runtime at the bundled rive.wasm. MUST run before the first
	// Rive instance is created, or the loader falls back to the unpkg CDN.
	function ensureWasm() {
		if ( wasmSet || ! window.rive || ! window.upwRiveWasm ) { return; }
		try { window.rive.RuntimeLoader.setWasmUrl( window.upwRiveWasm ); wasmSet = true; } catch ( e ) {}
	}

	function initEl( el ) {
		if ( el.__upwRive || ! window.rive ) { return; }
		var src = el.getAttribute( 'data-src' );
		if ( ! src ) { return; }

		ensureWasm();

		var trigger = el.getAttribute( 'data-trigger' ) || 'loop';

		var canvas = el.querySelector( 'canvas.upw-rive-canvas' );
		if ( ! canvas ) {
			canvas = document.createElement( 'canvas' );
			canvas.className = 'upw-rive-canvas';
			el.appendChild( canvas );
		}

		var inst;
		try {
			inst = new window.rive.Rive( {
				src: src,
				canvas: canvas,
				autoplay: ( trigger === 'loop' || trigger === 'once' ),
				layout: new window.rive.Layout( {
					fit: window.rive.Fit.Contain,
					alignment: window.rive.Alignment.Center
				} ),
				onLoad: function () { try { inst.resizeDrawingSurfaceToCanvas(); } catch ( e ) {} }
			} );
		} catch ( e ) { return; }

		el.__upwRive = inst;

		if ( trigger === 'hover' ) {
			var host = el.closest( '.upw-rive-hover-host' ) || el;
			host.addEventListener( 'mouseenter', function () { try { inst.play(); } catch ( e ) {} } );
			host.addEventListener( 'mouseleave', function () { try { inst.pause(); } catch ( e ) {} } );
		} else if ( trigger === 'click' ) {
			el.addEventListener( 'click', function () { try { inst.play(); } catch ( e ) {} } );
		}
	}

	function initAll( root ) {
		ensureWasm();
		var scope = root && root.querySelectorAll ? root : document;
		var els = scope.querySelectorAll( '.upw-rive[data-src]' );
		for ( var i = 0; i < els.length; i++ ) { initEl( els[ i ] ); }
	}

	// Public hook for dynamically-injected content (builder preview, AJAX, etc.).
	window.upwRiveInit = initAll;

	if ( document.readyState !== 'loading' ) { initAll(); }
	else { document.addEventListener( 'DOMContentLoaded', function () { initAll(); } ); }
} )();
