/**
 * Shared Coloris init for BOTH the `color-picker` and `rgba-color-picker` option types
 * (modern, vanilla, MIT — replaces Iris / wpColorPicker / wp-color-picker-alpha).
 *
 * The brand Color Presets are the global swatch grid (localized as window.fwColorisSwatches).
 * Per-type config is applied with scoped Coloris.setInstance() calls:
 *   - color-picker            -> hex, no opacity (default)
 *   - color-picker[data-alpha]-> hex + opacity (8-digit #rrggbbaa)
 *   - rgba-color-picker       -> rgb + opacity, so it emits `rgba(r,g,b,a)` (its stored shape)
 *
 * The value stays a plain colour string in the input, so the save path and every consumer
 * reading `$input.val()` keep working, and native change/input events drive live preview.
 *
 * Coloris binds a document-level focus listener to the `el` selector, so options rendered
 * LATER (page-builder modals) open the picker on focus with no re-init. We do NOT re-call
 * Coloris() on fw:options:init — re-processing inputs throws.
 */
( function () {
	var CP        = 'input.fw-option-type-color-picker[data-coloris]';
	var CP_PLAIN  = 'input.fw-option-type-color-picker[data-coloris]:not([data-alpha="1"])';
	var CP_ALPHA  = 'input.fw-option-type-color-picker[data-coloris][data-alpha="1"]';
	var RGBA      = 'input.fw-option-type-rgba-color-picker[data-coloris]';
	var ALL       = CP + ', ' + RGBA;

	function wantsAlpha( el ) {
		return el.classList.contains( 'fw-option-type-rgba-color-picker' ) || el.getAttribute( 'data-alpha' ) === '1';
	}

	function swatches() {
		return ( window.fwColorisSwatches && window.fwColorisSwatches.length ) ? window.fwColorisSwatches : [];
	}

	function isColorInput( el ) {
		return el && el.classList && ( el.classList.contains( 'fw-option-type-color-picker' ) || el.classList.contains( 'fw-option-type-rgba-color-picker' ) );
	}

	// Paint the input's swatch preview (background = its colour) — replaces Coloris's `wrap`
	// thumbnail, which we disable because wrapping detached builder-template inputs throws.
	function paint( el ) {
		var v = ( el.value || '' ).trim();
		var ok = /^#([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})$/i.test( v ) || /^rgba?\(/i.test( v );
		if ( ! ok ) { el.style.background = ''; el.style.color = ''; return; }
		el.style.background = v;
		// Readable text: luminance from hex (6-digit) or the rgb() channels.
		var r, g, b;
		if ( v[0] === '#' ) {
			var h = v.replace( '#', '' );
			if ( h.length === 3 ) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
			r = parseInt( h.substr( 0, 2 ), 16 ); g = parseInt( h.substr( 2, 2 ), 16 ); b = parseInt( h.substr( 4, 2 ), 16 );
		} else {
			var m = v.match( /(\d+)[,\s]+(\d+)[,\s]+(\d+)/ );
			if ( m ) { r = +m[1]; g = +m[2]; b = +m[3]; }
		}
		el.style.color = ( r === undefined ) ? '' : ( ( ( r * 299 + g * 587 + b * 114 ) / 1000 < 128 ) ? '#fff' : '#000' );
	}

	function boot() {
		if ( typeof window.Coloris === 'undefined' ) { return; }

		// wrap:false — Coloris never touches the input's DOM, so it can't choke on a detached
		// builder-template input. The picker still opens on focus (document delegation), which
		// also covers modal-added inputs.
		window.Coloris( {
			el: ALL,
			wrap: false,
			themeMode: 'light',
			format: 'hex',
			alpha: false,
			focusInput: true,
			selectInput: false,
			swatches: swatches(),
		} );

		// Plain color-picker → hex, NO opacity. Set explicitly (not just via the global
		// default) so Coloris applies alpha:false on open instead of leaking the alpha
		// slider left over from a previously-opened alpha picker.
		window.Coloris.setInstance( CP_PLAIN, { wrap: false, alpha: false, format: 'hex', swatches: swatches() } );

		// color-picker with opt-in opacity → 8-digit hex.
		window.Coloris.setInstance( CP_ALPHA, { wrap: false, alpha: true, format: 'hex', swatches: swatches() } );

		// rgba-color-picker → rgb format + opacity, so it emits `rgba(r,g,b,a)`.
		window.Coloris.setInstance( RGBA, { wrap: false, alpha: true, format: 'rgb', swatches: swatches() } );

		// Belt-and-suspenders for the alpha-slider leak: force the shared popup's slider to
		// match the focused input (Coloris keeps it visible after an alpha picker otherwise).
		document.addEventListener( 'focusin', function ( e ) {
			if ( ! isColorInput( e.target ) ) { return; }
			var show = wantsAlpha( e.target );
			window.requestAnimationFrame( function () {
				var a = document.querySelector( '.clr-alpha' );
				if ( a ) { a.style.display = show ? '' : 'none'; }
			} );
		}, true );

		document.addEventListener( 'input', function ( e ) {
			if ( isColorInput( e.target ) ) { paint( e.target ); }
		} );
		document.addEventListener( 'change', function ( e ) {
			if ( ! isColorInput( e.target ) ) { return; }
			paint( e.target );
			// Back-compat: the legacy `gradient` type live-previews off this jQuery event.
			if ( window.jQuery && e.target.classList.contains( 'fw-option-type-color-picker' ) ) {
				window.jQuery( e.target ).trigger( 'fw:color:picker:changed', { $element: window.jQuery( e.target ) } );
			}
		} );
		[].forEach.call( document.querySelectorAll( ALL ), paint );
	}

	// Run on window `load`, not DOMContentLoaded: Coloris builds its picker element on its
	// OWN DOM-ready pass, and configuring it earlier throws (f.className on undefined).
	if ( document.readyState === 'complete' ) {
		boot();
	} else {
		window.addEventListener( 'load', boot );
	}
} )();
