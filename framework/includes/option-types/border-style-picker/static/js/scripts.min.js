/**
 * Option Type: Border Style Picker
 *
 * Custom dropdown whose trigger + rows are real bordered-box previews. Behaviors:
 * - Trigger toggles the panel.
 * - Option click → write its data-value to the hidden input, repaint the
 *   trigger preview, mark selected, close.
 * - Outside click / Escape close. Arrow keys + Enter for listbox a11y.
 * - Repaint trigger from the saved value on init.
 *
 * Mirrors the button-style-picker; the preview just carries a .colb-{slug} class.
 */
(function () {
	var OPTION_CLASS = 'fw-option-type-border-style-picker';
	var uniqCounter  = 0;

	jQuery( document ).ready( function ( $ ) {

		fwEvents.on( 'fw:options:init', function ( data ) {
			data.$elements.find( '.' + OPTION_CLASS + ':not(.initialized)' ).each( function () {
				initOption( $( this ) );
			} );
		} );

		function initOption( $option ) {
			$option.addClass( 'initialized' );

			var $trigger = $option.find( '.bsp__trigger' );
			var $panel   = $option.find( '.bsp__panel' );

			$trigger.on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				togglePanel( $option, ! panelIsOpen( $panel ) );
			} );

			$panel.on( 'click', '.bsp__option', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				selectOption( $option, $( this ) );
				togglePanel( $option, false );
				$trigger.focus();
			} );

			// Outside click closes.
			$( document ).on( 'mousedown.bdsp-' + uniq( $option ), function ( e ) {
				if ( ! $option[0].contains( e.target ) ) {
					togglePanel( $option, false );
				}
			} );

			// Keyboard: Esc closes; ↑/↓ move; Enter/Space on the trigger opens.
			$option.on( 'keydown', function ( e ) {
				var key = e.key;
				if ( key === 'Escape' || e.keyCode === 27 ) {
					togglePanel( $option, false );
					$trigger.focus();
					return;
				}
				if ( ! panelIsOpen( $panel ) ) {
					if ( ( key === 'Enter' || key === ' ' || key === 'ArrowDown' ) && $( e.target ).is( $trigger ) ) {
						e.preventDefault();
						togglePanel( $option, true );
						focusOption( $panel, 0 );
					}
					return;
				}
				if ( key === 'ArrowDown' || key === 'ArrowUp' ) {
					e.preventDefault();
					moveFocus( $panel, key === 'ArrowDown' ? 1 : -1 );
				}
			} );

			repaintFromSaved( $option );
		}

		function panelIsOpen( $panel ) {
			return $panel.length > 0 && ! $panel.prop( 'hidden' );
		}

		function togglePanel( $option, open ) {
			var $panel   = $option.find( '.bsp__panel' );
			var $trigger = $option.find( '.bsp__trigger' );
			$panel.prop( 'hidden', ! open );
			$trigger.attr( 'aria-expanded', open ? 'true' : 'false' );
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
			var trg    = $option.find( '.bsp__trigger' ).get( 0 );
			var $panel = $option.find( '.bsp__panel' );
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
			var val = $opt.attr( 'data-value' ) || '';

			$option.find( '.bsp__input' ).val( val ).trigger( 'change' );

			// Repaint the trigger to mirror the chosen row.
			paintTrigger( $option, $opt, val );

			$option.find( '.bsp__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
			$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
		}

		// Rebuild the trigger's inner markup to match the selected option: a real
		// preview box when a value is set, else the placeholder text.
		function paintTrigger( $option, $opt, val ) {
			var $trigger = $option.find( '.bsp__trigger' );
			var $caret   = $trigger.find( '.bsp__caret' );

			$trigger.find( '.bsp__preview, .bsp__trigger-placeholder' ).remove();

			if ( val !== '' ) {
				var $srcPreview = $opt.find( '.bsp__preview' );
				var $clone = $srcPreview.clone();
				$trigger.prepend( $clone );
			} else {
				var phText = $trigger.data( 'placeholder' ) || '— Select —';
				$trigger.prepend( $( '<span class="bsp__trigger-placeholder"></span>' ).text( phText ) );
			}
			// Keep caret last.
			if ( $caret.length ) { $trigger.append( $caret ); }
		}

		function repaintFromSaved( $option ) {
			var val = ( $option.find( '.bsp__input' ).val() || '' ).toString();
			// Remember the placeholder text for later repaints to empty.
			var $ph = $option.find( '.bsp__trigger-placeholder' );
			if ( $ph.length ) { $option.find( '.bsp__trigger' ).data( 'placeholder', $ph.text() ); }

			var $opt = val !== ''
				? $option.find( '.bsp__option[data-value="' + cssEscape( val ) + '"]' ).first()
				: $option.find( '.bsp__option--empty' ).first();
			if ( $opt.length ) {
				$option.find( '.bsp__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
				$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
			}
		}

		// --- focus helpers for arrow-key nav ---
		function focusOption( $panel, index ) {
			var $opts = $panel.find( '.bsp__option' );
			if ( $opts.length ) { $opts.eq( Math.max( 0, Math.min( index, $opts.length - 1 ) ) ).focus(); }
		}
		function moveFocus( $panel, dir ) {
			var $opts = $panel.find( '.bsp__option' );
			var idx   = $opts.index( $panel.find( '.bsp__option:focus' ) );
			focusOption( $panel, idx + dir );
		}

		// --- utils ---
		function uniq( $option ) {
			var id = $option.data( 'bdspUid' );
			if ( ! id ) { id = ++uniqCounter; $option.data( 'bdspUid', id ); }
			return id;
		}
		function cssEscape( s ) {
			if ( window.CSS && window.CSS.escape ) { return window.CSS.escape( s ); }
			return String( s ).replace( /(["\\])/g, '\\$1' );
		}
	} );
} )();
