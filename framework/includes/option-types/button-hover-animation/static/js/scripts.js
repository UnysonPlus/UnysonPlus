/**
 * Option Type: Button Hover Animation
 *
 * Select-like trigger that opens a panel with the effects in a 3-column grid of
 * real buttons. Behaviors:
 * - Trigger toggles the panel.
 * - Each grid cell is a real button preview; hovering it plays the effect (genuine
 *   :hover). Clicking a cell writes its data-value to the hidden input, updates the
 *   trigger label, marks selected, and closes.
 * - Outside click / Escape close. Repaints the trigger label from the saved value
 *   on init.
 */
(function () {
	var OPTION_CLASS = 'fw-option-type-button-hover-animation';
	var uniqCounter  = 0;

	jQuery( document ).ready( function ( $ ) {

		fwEvents.on( 'fw:options:init', function ( data ) {
			data.$elements.find( '.' + OPTION_CLASS + ':not(.initialized)' ).each( function () {
				initOption( $( this ) );
			} );
		} );

		function initOption( $option ) {
			$option.addClass( 'initialized' );

			var $trigger = $option.find( '.bha__trigger' );
			var $panel   = $option.find( '.bha__panel' );

			$trigger.on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				togglePanel( $option, $panel.prop( 'hidden' ) );
			} );

			$panel.on( 'click', '.bha__option', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				selectOption( $option, $( this ) );
				togglePanel( $option, false );
				$trigger.focus();
			} );

			// Outside click closes.
			$( document ).on( 'mousedown.bha-' + uniq( $option ), function ( e ) {
				if ( ! $option[0].contains( e.target ) ) {
					togglePanel( $option, false );
				}
			} );

			// Esc closes.
			$option.on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' || e.keyCode === 27 ) {
					togglePanel( $option, false );
					$trigger.focus();
				}
			} );

			repaintFromSaved( $option );
		}

		function togglePanel( $option, open ) {
			var $panel = $option.find( '.bha__panel' );
			$panel.prop( 'hidden', ! open );
			$option.find( '.bha__trigger' ).attr( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) {
				positionPanel( $option );
				var reposition = function () { positionPanel( $option ); };
				$option.data( 'fwPickerReposition', reposition );
				window.addEventListener( 'scroll', reposition, true );
				window.addEventListener( 'resize', reposition );
			} else {
				var rep = $option.data( 'fwPickerReposition' );
				if ( rep ) {
					window.removeEventListener( 'scroll', rep, true );
					window.removeEventListener( 'resize', rep );
					$option.removeData( 'fwPickerReposition' );
				}
				$panel.css( { position: '', top: '', left: '' } );
			}
		}

		// Anchor the panel to its trigger as position:fixed so an ancestor with
		// overflow:hidden (a settings box / scroll container) can't clip it. Flips
		// above when there's no room below; clamps to the viewport.
		function positionPanel( $option ) {
			var trg    = $option.find( '.bha__trigger' ).get( 0 );
			var $panel = $option.find( '.bha__panel' );
			var pnl    = $panel.get( 0 );
			if ( ! trg || ! pnl ) { return; }
			$panel.css( { position: 'fixed', top: '0px', left: '0px' } );
			var rect = trg.getBoundingClientRect();
			var pw = pnl.offsetWidth, ph = pnl.offsetHeight, vw = window.innerWidth, vh = window.innerHeight;
			var left = rect.left;
			if ( left + pw > vw - 8 ) { left = Math.max( 8, vw - pw - 8 ); }
			var top = rect.bottom + 4;
			if ( top + ph > vh - 8 && rect.top - ph - 4 > 8 ) { top = rect.top - ph - 4; }
			$panel.css( { left: Math.round( left ) + 'px', top: Math.round( top ) + 'px' } );
		}

		function selectOption( $option, $opt ) {
			var val   = $opt.attr( 'data-value' ) || '';
			var label = ( $opt.attr( 'title' ) || '' ).toString();

			$option.find( '.bha__input' ).val( val ).trigger( 'change' );
			$option.find( '.bha__trigger-label' ).text( label );

			$option.find( '.bha__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
			$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
		}

		function repaintFromSaved( $option ) {
			var val  = ( $option.find( '.bha__input' ).val() || '' ).toString();
			var $opt = val !== ''
				? $option.find( '.bha__option[data-value="' + cssEscape( val ) + '"]' ).first()
				: $option.find( '.bha__option--none' ).first();
			if ( $opt.length ) {
				$option.find( '.bha__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
				$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
				$option.find( '.bha__trigger-label' ).text( ( $opt.attr( 'title' ) || '' ).toString() );
			}
		}

		// --- utils ---
		function uniq( $option ) {
			var id = $option.data( 'bhaUid' );
			if ( ! id ) { id = ++uniqCounter; $option.data( 'bhaUid', id ); }
			return id;
		}
		function cssEscape( s ) {
			if ( window.CSS && window.CSS.escape ) { return window.CSS.escape( s ); }
			return String( s ).replace( /(["\\])/g, '\\$1' );
		}
	} );
} )();
