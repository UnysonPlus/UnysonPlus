/**
 * Theme Settings -> Icons -> Icon Library: the unified icon-pack panel.
 *
 * One surface for every library:
 *   - Bundled webfonts (Font Awesome, Dashicons, …): an On/Off toggle only.
 *   - Bundled SVG (Lucide, Tabler): On/Off toggle only.
 *   - Installed SVG (downloaded): On/Off toggle + Remove.
 *   - Available SVG (in the remote catalog): an Install button.
 *
 * Toggle/Install/Remove hit the capability+nonce-gated `fw_icon_pack_manage` AJAX
 * and update the card in place. The On/Off toggle folds in the old "Enabled
 * libraries" checklist — disabling a library only hides it when picking NEW icons.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.upwIconPacks || null;

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function isEnabled( slug ) { return cfg.enabled.indexOf( slug ) !== -1; }
	function isInstalled( slug ) { return cfg.installed.indexOf( slug ) !== -1; }

	// Resolve a pack's LIVE state from the current installed/enabled arrays (which
	// mutate as the user acts), not the server-rendered snapshot.
	function liveState( p ) {
		if ( p.type === 'font' ) { return 'font'; }
		if ( p.state === 'bundled' ) { return 'bundled'; }
		return isInstalled( p.slug ) ? 'installed' : 'available';
	}

	function countLabel( n ) {
		if ( !n ) { return ''; }
		return n === 1 ? '1 icon' : ( n.toLocaleString() + ' icons' );
	}

	function toggleHtml( p ) {
		var on = isEnabled( p.slug );
		return '' +
			'<label class="upw-ipk__switch" title="' + esc( on ? cfg.i18n.on : cfg.i18n.off ) + '">' +
				'<input type="checkbox" data-act="toggle" data-slug="' + esc( p.slug ) + '"' + ( on ? ' checked' : '' ) + '>' +
				'<span class="upw-ipk__switch-track"><span class="upw-ipk__switch-knob"></span></span>' +
			'</label>';
	}

	function metaLabel( p, state ) {
		var n = p.count || 0;
		if ( state === 'font' ) {
			return n ? ( n.toLocaleString() + ' ' + cfg.i18n.fontIcons ) : cfg.i18n.font;
		}
		// Every other state is an SVG set (bundled Lucide/Tabler, installed, available).
		return n ? ( n.toLocaleString() + ' ' + cfg.i18n.svgIcons ) : cfg.i18n.bundled;
	}

	function cardHtml( p ) {
		var state = liveState( p );
		var actions;

		if ( state === 'available' ) {
			actions = '<button type="button" class="button button-secondary button-small" data-act="install" data-slug="' + esc( p.slug ) + '">' + esc( cfg.i18n.install ) + '</button>';
		} else {
			actions = toggleHtml( p );
			if ( state === 'installed' ) {
				actions += '<button type="button" class="button button-small upw-ipk__remove" data-act="uninstall" data-slug="' + esc( p.slug ) + '">' + esc( cfg.i18n.remove ) + '</button>';
			}
		}

		var dim = ( state !== 'available' && !isEnabled( p.slug ) ) ? ' upw-ipk__card--off' : '';
		var tag = ( p.origin === 'custom' && state === 'installed' )
			? ' <span class="upw-ipk__tag">' + esc( cfg.i18n.custom ) + '</span>' : '';

		// Preview strip of sample glyphs (available packs only). The SVG is
		// sanitized server-side, so it is injected as-is (escaping would break it).
		var preview = ( state === 'available' && p.preview )
			? '<div class="upw-ipk__preview">' + p.preview + '</div>' : '';

		return '' +
			'<div class="upw-ipk__card' + dim + '" data-slug="' + esc( p.slug ) + '">' +
				preview +
				'<span class="upw-ipk__card-title">' + esc( p.title ) + tag + '</span>' +
				'<span class="upw-ipk__card-meta">' + esc( metaLabel( p, state ) ) + '</span>' +
				'<div class="upw-ipk__card-actions">' + actions + '</div>' +
			'</div>';
	}

	function uploadHtml() {
		return '' +
			'<div class="upw-ipk__upload">' +
				'<p class="upw-ipk__upload-desc">' + esc( cfg.i18n.uploadDesc ) + '</p>' +
				'<div class="upw-ipk__upload-row">' +
					'<label class="upw-ipk__upload-field">' +
						'<span>' + esc( cfg.i18n.uploadNameLabel ) + '</span>' +
						'<input type="text" class="upw-ipk__upload-name" placeholder="' + esc( cfg.i18n.uploadNamePlaceholder ) + '">' +
					'</label>' +
					'<label class="upw-ipk__upload-field">' +
						'<span>' + esc( cfg.i18n.uploadFileLabel ) + '</span>' +
						'<input type="file" class="upw-ipk__upload-file" accept=".json,application/json">' +
					'</label>' +
					'<button type="button" class="button button-secondary" data-act="upload">' + esc( cfg.i18n.uploadButton ) + '</button>' +
				'</div>' +
				'<p class="upw-ipk__upload-msg" aria-live="polite"></p>' +
			'</div>';
	}

	// One container per sub-tab (Library / Browse / Upload).
	var IDS = { library: 'upw-ipk-library', browse: 'upw-ipk-browse', upload: 'upw-ipk-upload' };

	function buildLists() {
		var present = [], available = [];
		( cfg.packs || [] ).forEach( function ( p ) {
			( liveState( p ) === 'available' ? available : present ).push( p );
		} );
		var byTitle = function ( a, b ) { return a.title.localeCompare( b.title ); };
		return { present: present.sort( byTitle ), available: available.sort( byTitle ) };
	}

	function renderLibrary( $el ) {
		var present = buildLists().present;
		$el.html(
			'<p class="upw-ipk__intro">' + esc( cfg.i18n.libraryIntro ) + '</p>' +
			'<div class="upw-ipk__grid">' + present.map( cardHtml ).join( '' ) + '</div>' +
			'<p class="upw-ipk__hint">' + esc( cfg.i18n.toggleHint ) + '</p>'
		);
	}

	function renderBrowse( $el ) {
		var available = buildLists().available;
		var html =
			'<div class="upw-ipk__head">' +
				'<p class="upw-ipk__intro">' + esc( cfg.i18n.browseIntro ) + '</p>' +
				'<button type="button" class="button button-small upw-ipk__refresh" data-act="refresh">' + esc( cfg.i18n.refresh ) + '</button>' +
			'</div>';
		if ( !cfg.catalogOk ) {
			html += '<div class="upw-ipk__notice">' + esc( cfg.i18n.catalogUnavailable ) + '</div>';
		} else {
			html += available.length
				? '<div class="upw-ipk__grid">' + available.map( cardHtml ).join( '' ) + '</div>'
				: '<div class="upw-ipk__empty">' + esc( cfg.i18n.allInstalled ) + '</div>';
		}
		$el.html( html );
	}

	function renderUpload( $el ) {
		$el.html( uploadHtml() );
	}

	function renderKind( kind, $el ) {
		if ( kind === 'library' ) { renderLibrary( $el ); }
		else if ( kind === 'browse' ) { renderBrowse( $el ); }
		else { renderUpload( $el ); }
	}

	// Re-render every container currently in the DOM (a lazy sub-tab may not be
	// injected yet). Used after any state change so Library and Browse stay in sync
	// — e.g. installing in Browse moves the pack into Library.
	function renderAll() {
		Object.keys( IDS ).forEach( function ( kind ) {
			var el = document.getElementById( IDS[ kind ] );
			if ( el ) { renderKind( kind, $( el ) ); }
		} );
	}

	function ajax( packAction, slug ) {
		return $.post( cfg.ajaxUrl, {
			action: 'fw_icon_pack_manage',
			pack_action: packAction,
			slug: slug || '',
			nonce: cfg.nonce
		} );
	}

	function applyState( res ) {
		if ( res && res.success && res.data ) {
			if ( res.data.installed ) { cfg.installed = res.data.installed; }
			if ( res.data.enabled )   { cfg.enabled   = res.data.enabled; }
			// Fresh, authoritative pack list — includes packs added since page load
			// (e.g. an uploaded custom pack the client had never seen).
			if ( res.data.packs )     { cfg.packs     = res.data.packs; }
			return true;
		}
		return false;
	}

	function fail( $card, msg ) {
		$card.removeClass( 'upw-ipk__card--busy' );
		$card.find( '.upw-ipk__card-actions' ).after( '<p class="upw-ipk__error">' + esc( msg ) + '</p>' );
		window.setTimeout( function () { $card.find( '.upw-ipk__error' ).fadeOut( 400, function () { $( this ).remove(); } ); }, 4000 );
	}

	function handleUpload( $btn ) {
		var $wrap = $btn.closest( '.upw-ipk__upload' ),
			name  = $.trim( $wrap.find( '.upw-ipk__upload-name' ).val() || '' ),
			input = $wrap.find( '.upw-ipk__upload-file' )[ 0 ],
			$msg  = $wrap.find( '.upw-ipk__upload-msg' ).removeClass( 'upw-ipk__error' ).text( '' );

		if ( !name ) { $msg.addClass( 'upw-ipk__error' ).text( cfg.i18n.uploadNeedName ); return; }
		if ( !input || !input.files || !input.files.length ) { $msg.addClass( 'upw-ipk__error' ).text( cfg.i18n.uploadNeedFile ); return; }

		var fd = new FormData();
		fd.append( 'action', 'fw_icon_pack_manage' );
		fd.append( 'pack_action', 'upload' );
		fd.append( 'nonce', cfg.nonce );
		fd.append( 'title', name );
		fd.append( 'pack_file', input.files[ 0 ] );

		$btn.prop( 'disabled', true ).text( cfg.i18n.uploading );

		$.ajax( { url: cfg.ajaxUrl, method: 'POST', data: fd, processData: false, contentType: false } )
			.done( function ( res ) {
				if ( applyState( res ) ) {
					$msg.text( cfg.i18n.uploadDone || '' );
					renderAll(); // the new custom pack now appears under Library
				} else {
					$msg.addClass( 'upw-ipk__error' ).text( ( res && res.data && res.data.message ) || cfg.i18n.genericError );
				}
				$btn.prop( 'disabled', false ).text( cfg.i18n.uploadButton );
			} )
			.fail( function () {
				$msg.addClass( 'upw-ipk__error' ).text( cfg.i18n.genericError );
				$btn.prop( 'disabled', false ).text( cfg.i18n.uploadButton );
			} );
	}

	// Delegated on the document once, so it works across all three lazy sub-tab
	// containers regardless of when their HTML is injected.
	var bound = false;
	function bindGlobal() {
		if ( bound ) { return; }
		bound = true;

		$( document ).on( 'click', '.upw-ipk button[data-act]', function () {
			var $btn = $( this ),
				act  = $btn.data( 'act' ),
				slug = $btn.data( 'slug' ),
				$card = $btn.closest( '.upw-ipk__card' );

			if ( act === 'upload' ) { handleUpload( $btn ); return; }

			if ( act === 'refresh' ) {
				$btn.prop( 'disabled', true ).html( '<span class="upw-ipk__spinner"></span>' );
				ajax( 'refresh' ).always( function () { window.location.reload(); } );
				return;
			}

			if ( act === 'uninstall' && !window.confirm( cfg.i18n.confirmRemove.replace( '%s', slug ) ) ) {
				return;
			}

			$card.addClass( 'upw-ipk__card--busy' ).find( '.upw-ipk__card-actions' )
				.html( '<span class="upw-ipk__spinner"></span> ' + esc( act === 'install' ? cfg.i18n.installing : cfg.i18n.removing ) );

			ajax( act, slug )
				.done( function ( res ) { applyState( res ) ? renderAll() : fail( $card, ( res && res.data && res.data.message ) || cfg.i18n.genericError ); } )
				.fail( function () { fail( $card, cfg.i18n.genericError ); } );
		} );

		$( document ).on( 'change', '.upw-ipk input[data-act="toggle"]', function () {
			var $cb   = $( this ),
				slug  = $cb.data( 'slug' ),
				on    = $cb.is( ':checked' ),
				$card = $cb.closest( '.upw-ipk__card' );

			$card.toggleClass( 'upw-ipk__card--off', !on );
			$cb.prop( 'disabled', true );

			ajax( on ? 'enable' : 'disable', slug )
				.done( function ( res ) {
					if ( !applyState( res ) ) {
						$cb.prop( 'checked', !on );
						$card.toggleClass( 'upw-ipk__card--off', on );
					}
				} )
				.fail( function () {
					$cb.prop( 'checked', !on );
					$card.toggleClass( 'upw-ipk__card--off', on );
				} )
				.always( function () { $cb.prop( 'disabled', false ); } );
		} );
	}

	// Render any container that has appeared but isn't rendered yet (each sub-tab's
	// HTML is injected only when it's first shown).
	function initContainers() {
		bindGlobal();
		Object.keys( IDS ).forEach( function ( kind ) {
			var el = document.getElementById( IDS[ kind ] );
			if ( !el || $( el ).data( 'upw-inited' ) ) { return; }
			$( el ).data( 'upw-inited', 1 );
			renderKind( kind, $( el ) );
		} );
	}

	$( function () {
		if ( !cfg ) { return; }

		initContainers();

		// Sub-tabs are lazy — Browse/Upload inject only when opened. Watch for them.
		if ( window.MutationObserver ) {
			var mo = new MutationObserver( function () { initContainers(); } );
			mo.observe( document.body, { childList: true, subtree: true } );
		}
		if ( window.fwEvents && typeof window.fwEvents.on === 'function' ) {
			window.fwEvents.on( 'fw:options:init', function () { initContainers(); } );
		}
	} );

} )( jQuery );
