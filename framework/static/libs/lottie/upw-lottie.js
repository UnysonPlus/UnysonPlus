/**
 * UnysonPlus Lottie icon runtime.
 *
 * Hydrates every <span class="upw-lottie" data-src="…"> on the page into a Lottie
 * animation using the bundled lottie-web (SVG light build). The element carries:
 *   data-src     — URL of the Lottie JSON
 *   data-trigger — loop | once | hover | click   (default: loop)
 *   data-speed   — playback multiplier            (default: 1)
 *
 * Loads lazily: only enqueued when a Lottie icon is actually rendered. Re-exposed
 * as window.upwLottieInit(root) so the builder preview and AJAX-injected content
 * can hydrate freshly-added elements.
 */
( function () {
	'use strict';

	function initEl( el ) {
		if ( el.__upwLottie || ! window.lottie ) { return; }
		var src = el.getAttribute( 'data-src' );
		if ( ! src ) { return; }

		var trigger  = el.getAttribute( 'data-trigger' ) || 'loop';
		var speed    = parseFloat( el.getAttribute( 'data-speed' ) ) || 1;
		var autoplay = ( trigger === 'loop' || trigger === 'once' );

		var anim;
		try {
			anim = window.lottie.loadAnimation( {
				container: el,
				renderer: 'svg',
				loop: trigger === 'loop',
				autoplay: autoplay,
				path: src,
				rendererSettings: { progressiveLoad: true }
			} );
		} catch ( e ) { return; }

		anim.setSpeed( speed );
		el.__upwLottie = anim;

		if ( trigger === 'hover' ) {
			var host = el.closest( '.upw-lottie-hover-host' ) || el;
			host.addEventListener( 'mouseenter', function () { anim.setDirection( 1 ); anim.goToAndPlay( 0, true ); } );
			host.addEventListener( 'mouseleave', function () { anim.stop(); } );
		} else if ( trigger === 'click' ) {
			el.addEventListener( 'click', function () { anim.goToAndPlay( 0, true ); } );
		}
	}

	function initAll( root ) {
		var scope = root && root.querySelectorAll ? root : document;
		var els = scope.querySelectorAll( '.upw-lottie[data-src]' );
		for ( var i = 0; i < els.length; i++ ) { initEl( els[ i ] ); }
	}

	// Public hook for dynamically-injected content (builder preview, AJAX, etc.).
	window.upwLottieInit = initAll;

	if ( document.readyState !== 'loading' ) { initAll(); }
	else { document.addEventListener( 'DOMContentLoaded', function () { initAll(); } ); }
} )();
