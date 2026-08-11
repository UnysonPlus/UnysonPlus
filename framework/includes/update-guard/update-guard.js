/**
 * Update guard — intercepts a MANUAL update of the Unyson+ plugin and asks the
 * user to confirm (after reminding them to back up) before it proceeds.
 *
 * Two interception points cover every manual path:
 *   1) wp.updates.updatePlugin() is wrapped — the Plugins page "update now" link
 *      and the bulk "Update" action both funnel through it (AJAX in-place update).
 *   2) A capture-phase `submit` listener catches the Dashboard -> Updates
 *      ("Update Plugins") form POST when our plugin is among the checked rows.
 */
( function () {
	var cfg    = window.fwUpdateGuard || {};
	var PLUGIN = cfg.plugin;
	if ( ! PLUGIN ) { return; }
	var proceeding = false; // set while we replay a confirmed update, so the wrapper lets it through

	/** Build + show the confirm modal; calls onProceed() only if the user confirms. */
	function confirmUpdate( onProceed ) {
		var ov = document.createElement( 'div' );
		ov.className = 'fw-upg-overlay';
		ov.innerHTML =
			'<div class="fw-upg-modal" role="dialog" aria-modal="true" aria-labelledby="fw-upg-title">' +
				'<div class="fw-upg-modal__bar"></div>' +
				'<div class="fw-upg-modal__body">' +
					'<div class="fw-upg-modal__icon" aria-hidden="true"><span class="dashicons dashicons-backup"></span></div>' +
					'<h2 class="fw-upg-modal__title" id="fw-upg-title"></h2>' +
					'<p class="fw-upg-modal__msg"></p>' +
				'</div>' +
				'<div class="fw-upg-modal__actions">' +
					'<button type="button" class="button fw-upg-btn fw-upg-cancel"></button>' +
					'<button type="button" class="button button-primary fw-upg-btn fw-upg-proceed"></button>' +
				'</div>' +
			'</div>';
		ov.querySelector( '.fw-upg-modal__title' ).textContent = cfg.title || 'Update?';
		ov.querySelector( '.fw-upg-modal__msg' ).textContent   = cfg.message || '';
		var pBtn = ov.querySelector( '.fw-upg-proceed' ); pBtn.textContent = cfg.proceed || 'Proceed';
		var cBtn = ov.querySelector( '.fw-upg-cancel' );  cBtn.textContent = cfg.cancel || 'Cancel';
		document.body.appendChild( ov );

		function close() {
			if ( ov.parentNode ) { ov.parentNode.removeChild( ov ); }
			document.removeEventListener( 'keydown', onKey, true );
		}
		function onKey( e ) { if ( e.key === 'Escape' ) { e.preventDefault(); close(); } }

		cBtn.addEventListener( 'click', close );
		ov.addEventListener( 'mousedown', function ( e ) { if ( e.target === ov ) { close(); } } );
		pBtn.addEventListener( 'click', function () { close(); onProceed(); } );
		document.addEventListener( 'keydown', onKey, true );
		window.setTimeout( function () { pBtn.focus(); }, 30 );
	}

	// 1) Wrap wp.updates.updatePlugin — covers the "update now" link + bulk update.
	function wrapUpdatePlugin() {
		if ( ! window.wp || ! wp.updates || typeof wp.updates.updatePlugin !== 'function' ) { return; }
		if ( wp.updates.updatePlugin._fwWrapped ) { return; }
		var orig = wp.updates.updatePlugin;
		var wrapped = function ( args ) {
			if ( ! proceeding && args && args.plugin === PLUGIN ) {
				confirmUpdate( function () {
					proceeding = true;
					try { orig.call( wp.updates, args ); } finally { proceeding = false; }
				} );
				return; // block the update until the user confirms
			}
			return orig.apply( wp.updates, arguments );
		};
		wrapped._fwWrapped = true;
		wp.updates.updatePlugin = wrapped;
	}
	if ( document.readyState !== 'loading' ) { wrapUpdatePlugin(); }
	else { document.addEventListener( 'DOMContentLoaded', wrapUpdatePlugin ); }
	// wp.updates can finish wiring a touch later — one retry covers the race.
	window.setTimeout( wrapUpdatePlugin, 400 );

	// 2) Intercept the update-core.php "Update Plugins" form POST.
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! form || form._fwUpgOk || ! form.querySelectorAll ) { return; }
		var boxes = form.querySelectorAll( 'input[name="checked[]"]' ), cb = null, i;
		for ( i = 0; i < boxes.length; i++ ) {
			if ( boxes[ i ].value === PLUGIN ) { cb = boxes[ i ]; break; }
		}
		if ( ! cb || ! cb.checked ) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		confirmUpdate( function () { form._fwUpgOk = true; form.submit(); } );
	}, true );
} )();
