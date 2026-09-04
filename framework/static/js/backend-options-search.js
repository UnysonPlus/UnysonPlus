/**
 * Theme Settings — option search.
 *
 * Renders a search box above the side navigation, filters the PHP-built index as
 * you type, and jumps to the chosen option.
 *
 * JUMPING TO AN OPTION
 * --------------------
 * The index carries an option's ID, not a DOM id — rendered row ids are derived
 * from the full name path and are not reliably reconstructable server-side. The
 * dependable handle is the control's `name`, which always contains `[<id>]`.
 * That is the same hook the framework itself uses elsewhere (the column's
 * Max Width rule matches `[name*="[max_width]"]`).
 *
 * Tabs are lazy: opening one injects its `data-fw-tab-html`. So a jump has to
 * activate the tab FIRST, then wait for the row to exist before scrolling — hence
 * the short poll rather than an immediate query.
 */
( function ( $ ) {
	'use strict';

	var cfg = window._fw_options_search || {};
	var INDEX = cfg.index || [];
	var L10N = cfg.l10n || {};

	if ( ! INDEX.length ) {
		return;
	}

	var MAX_RESULTS = 12;

	/** Case-insensitive match across label, description and path. */
	function search( q ) {
		q = String( q || '' ).trim().toLowerCase();
		if ( q.length < 2 ) {
			return [];
		}

		var scored = [];
		for ( var i = 0; i < INDEX.length; i++ ) {
			var it = INDEX[ i ];
			var label = ( it.label || '' ).toLowerCase();
			var desc = ( it.desc || '' ).toLowerCase();
			var path = ( it.path || '' ).toLowerCase();

			var score = -1;
			if ( label.indexOf( q ) === 0 ) {
				score = 0;                       // label starts with the query
			} else if ( label.indexOf( q ) > -1 ) {
				score = 1;                       // label contains it
			} else if ( path.indexOf( q ) > -1 ) {
				score = 2;                       // the tab/section name matches
			} else if ( desc.indexOf( q ) > -1 ) {
				score = 3;                       // only the description matches
			}

			if ( score > -1 ) {
				scored.push( { item: it, score: score } );
			}
		}

		scored.sort( function ( a, b ) {
			return a.score - b.score || a.item.label.length - b.item.label.length;
		} );

		return scored.slice( 0, MAX_RESULTS ).map( function ( s ) { return s.item; } );
	}

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/** Wrap the matched run so the user can see WHY a row matched. */
	function mark( text, q ) {
		var t = String( text || '' );
		var i = t.toLowerCase().indexOf( String( q ).toLowerCase() );
		if ( i < 0 || ! q ) {
			return esc( t );
		}
		return esc( t.slice( 0, i ) ) + '<mark>' + esc( t.slice( i, i + q.length ) ) +
			'</mark>' + esc( t.slice( i + q.length ) );
	}

	/**
	 * Open every tab along a chain, in order, then run `done`.
	 *
	 * Panes are lazy at EVERY level, so this cannot be a single click: opening
	 * "Header" injects its HTML, but an option nested under Header → Layout →
	 * Navigation still does not exist until those inner tabs are opened as well.
	 * Each step waits for the next link to appear (it only exists once its parent's
	 * HTML is injected) before clicking it.
	 *
	 * Ids that are boxes or groups rather than tabs simply have no link and are
	 * skipped, so the chain can be recorded without knowing which level is a tab.
	 */
	function openChain( ids, done, i, tries, scope ) {
		ids = ids || [];
		i = i || 0;
		tries = tries || 0;
		scope = scope || document;

		if ( i >= ids.length ) {
			done( scope );
			return;
		}

		// SCOPED to the pane opened by the previous step. Tab ids are NOT unique across
		// the page -- `tab_layout` exists under both General and Header -- so a global
		// lookup silently opened the wrong one and the chain stalled. Within a parent
		// pane the id is unique, and we only ever descend.
		var $link = $( scope ).find( 'a[href="#fw-options-tab-' + ids[ i ] + '"]' ).first();

		if ( ! $link.length ) {
			// Either not a tab (a box or group id), or its parent has not painted yet.
			if ( tries < 12 ) {
				window.setTimeout( function () { openChain( ids, done, i, tries + 1, scope ); }, 90 );
			} else {
				openChain( ids, done, i + 1, 0, scope );
			}
			return;
		}

		var alreadyOpen = $link.closest( 'li' ).hasClass( 'ui-tabs-active' );
		if ( ! alreadyOpen ) {
			$link.get( 0 ).click();          // native click: the widget listens for a real event
		}

		window.setTimeout( function () {
			// Descend into this tab's pane, again resolved within the current scope so a
			// duplicate id elsewhere on the page cannot win.
			var pane = $( scope ).find( '[id="fw-options-tab-' + ids[ i ] + '"]' ).get( 0 ) || scope;
			openChain( ids, done, i + 1, 0, pane );
		}, alreadyOpen ? 30 : 240 );
	}

	/**
	 * Find the row for an option id, polling because the tab's HTML is injected
	 * asynchronously when the tab is activated.
	 */
	function reveal( id, tries, scope ) {
		tries = tries || 0;
		var sel = '[name*="[' + id + ']"]';

		// Prefer the pane the chain landed in: option ids can repeat across tabs
		// (Header and Footer both have a `logo`, for instance).
		var $ctrl = scope ? $( scope ).find( sel ).filter( ':visible' ).first() : $();
		if ( ! $ctrl.length ) { $ctrl = $( sel ).filter( ':visible' ).first(); }
		if ( ! $ctrl.length ) { $ctrl = $( sel ).first(); }

		if ( ! $ctrl.length ) {
			if ( tries < 30 ) {                      // ~3s
				window.setTimeout( function () { reveal( id, tries + 1, scope ); }, 100 );
			}
			return;
		}

		var $row = $ctrl.closest( '.fw-backend-option' );
		if ( ! $row.length ) {
			return;
		}

		var el = $row.get( 0 );
		if ( el && el.scrollIntoView ) {
			el.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		}
		$row.addClass( 'fw-option-search-hit' );
		window.setTimeout( function () { $row.removeClass( 'fw-option-search-hit' ); }, 2000 );
	}

	function build() {
		var $nav = $( '.fw-backend-side-tabs .fw-options-tabs-first-level > .fw-options-tabs-list' ).first();
		if ( ! $nav.length || $( '.fw-options-search' ).length ) {
			return false;
		}

		var $box = $(
			'<div class="fw-options-search">' +
				'<span class="fw-options-search__icon" aria-hidden="true"></span>' +
				'<input type="search" class="fw-options-search__input" autocomplete="off" spellcheck="false"' +
					' placeholder="' + esc( L10N.placeholder || 'Search settings…' ) + '"' +
					' aria-label="' + esc( L10N.placeholder || 'Search settings' ) + '" />' +
				'<button type="button" class="fw-options-search__clear" aria-label="' +
					esc( L10N.clear || 'Clear search' ) + '" hidden>&times;</button>' +
				'<div class="fw-options-search__results" role="listbox" hidden></div>' +
			'</div>'
		);
		$nav.before( $box );

		var $input = $box.find( '.fw-options-search__input' );
		var $out = $box.find( '.fw-options-search__results' );
		var $clear = $box.find( '.fw-options-search__clear' );

		function render( q ) {
			var hits = search( q );
			$clear.prop( 'hidden', ! q );

			if ( ! q || q.trim().length < 2 ) {
				$out.prop( 'hidden', true ).empty();
				return;
			}

			if ( ! hits.length ) {
				$out.prop( 'hidden', false ).html(
					'<p class="fw-options-search__empty">' + esc( L10N.noResults || 'No matching settings' ) + '</p>'
				);
				return;
			}

			var html = hits.map( function ( it ) {
				return '<button type="button" class="fw-options-search__hit" role="option"' +
						' data-id="' + esc( it.id ) + '" data-tab="' + esc( it.tab ) + '"' +
						' data-tabs="' + esc( ( it.tabs || [] ).join( ',' ) ) + '">' +
						'<span class="fw-options-search__label">' + mark( it.label, q ) + '</span>' +
						( it.path ? '<span class="fw-options-search__path">' + mark( it.path, q ) + '</span>' : '' ) +
					'</button>';
			} ).join( '' );

			$out.prop( 'hidden', false ).html( html );
		}

		var t = null;
		$input.on( 'input', function () {
			var v = this.value;
			window.clearTimeout( t );
			t = window.setTimeout( function () { render( v ); }, 120 );
		} );

		$input.on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				this.value = '';
				render( '' );
				this.blur();
			}
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				$out.find( '.fw-options-search__hit' ).first().trigger( 'click' );
			}
		} );

		$clear.on( 'click', function () {
			$input.val( '' ).trigger( 'focus' );
			render( '' );
		} );

		$out.on( 'click', '.fw-options-search__hit', function () {
			var id = $( this ).data( 'id' );
			var tab = $( this ).data( 'tab' );
			var chain = $( this ).data( 'tabs' );
			chain = chain ? String( chain ).split( ',' ).filter( Boolean ) : ( tab ? [ tab ] : [] );
			$out.prop( 'hidden', true );
			openChain( chain, function ( scope ) { reveal( id, 0, scope ); } );
		} );

		$( document ).on( 'click', function ( e ) {
			if ( ! $( e.target ).closest( '.fw-options-search' ).length ) {
				$out.prop( 'hidden', true );
			}
		} );

		return true;
	}

	// The side nav is present on load, but options render through the framework's
	// own init — retry briefly rather than assume ordering.
	$( function () {
		if ( build() ) {
			return;
		}
		var tries = 0;
		var iv = window.setInterval( function () {
			if ( build() || ++tries > 40 ) {
				window.clearInterval( iv );
			}
		}, 150 );
	} );
} )( jQuery );
