/**
 * Option Type: Table Style Picker
 *
 * Custom dropdown whose trigger + rows are real mini-table previews. Behaviors:
 * - Trigger toggles the panel.
 * - Option click → write its data-value to the hidden input, repaint the trigger,
 *   mark selected, close.
 * - Outside click / Escape close. Arrow keys for listbox a11y.
 *
 * Mirrors the border-style-picker; the preview carries a .tbl-{slug} class.
 */
(function () {
	var OPTION_CLASS = 'fw-option-type-table-style-picker';
	var uniqCounter  = 0;

	jQuery( document ).ready( function ( $ ) {

		fwEvents.on( 'fw:options:init', function ( data ) {
			data.$elements.find( '.' + OPTION_CLASS + ':not(.initialized)' ).each( function () {
				initOption( $( this ) );
			} );
		} );

		function initOption( $option ) {
			$option.addClass( 'initialized' );

			var $trigger = $option.find( '.tsp__trigger' );
			var $panel   = $option.find( '.tsp__panel' );

			$trigger.on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				togglePanel( $option, ! panelIsOpen( $panel ) );
			} );

			$panel.on( 'click', '.tsp__option', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				selectOption( $option, $( this ) );
				togglePanel( $option, false );
				$trigger.focus();
			} );

			$( document ).on( 'mousedown.tsp-' + uniq( $option ), function ( e ) {
				if ( ! $option[0].contains( e.target ) ) {
					togglePanel( $option, false );
				}
			} );

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
			var $panel   = $option.find( '.tsp__panel' );
			var $trigger = $option.find( '.tsp__trigger' );
			$panel.prop( 'hidden', ! open );
			$trigger.attr( 'aria-expanded', open ? 'true' : 'false' );
		}

		function selectOption( $option, $opt ) {
			var val = $opt.attr( 'data-value' ) || '';
			$option.find( '.tsp__input' ).val( val ).trigger( 'change' );
			paintTrigger( $option, $opt, val );
			$option.find( '.tsp__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
			$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
		}

		// Rebuild the trigger to match the selected option: label + mini preview, or placeholder.
		function paintTrigger( $option, $opt, val ) {
			var $trigger = $option.find( '.tsp__trigger' );
			var $caret   = $trigger.find( '.tsp__caret' );

			$trigger.find( '.tsp__preview, .tsp__trigger-placeholder, .tsp__trigger-label' ).remove();

			if ( val !== '' ) {
				var label = ( $opt.find( '.tsp__option-label' ).text() || '' );
				$trigger.prepend( $opt.find( '.tsp__preview' ).first().clone() );
				$trigger.prepend( $( '<span class="tsp__trigger-label"></span>' ).text( label ) );
			} else {
				var phText = $trigger.data( 'placeholder' ) || '— Select —';
				$trigger.prepend( $( '<span class="tsp__trigger-placeholder"></span>' ).text( phText ) );
			}
			if ( $caret.length ) { $trigger.append( $caret ); }
		}

		function repaintFromSaved( $option ) {
			var $ph = $option.find( '.tsp__trigger-placeholder' );
			if ( $ph.length ) { $option.find( '.tsp__trigger' ).data( 'placeholder', $ph.text() ); }

			var val  = ( $option.find( '.tsp__input' ).val() || '' ).toString();
			var $opt = val !== ''
				? $option.find( '.tsp__option[data-value="' + cssEscape( val ) + '"]' ).first()
				: $option.find( '.tsp__option--empty' ).first();
			if ( $opt.length ) {
				$option.find( '.tsp__option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
				$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
			}
		}

		function focusOption( $panel, index ) {
			var $opts = $panel.find( '.tsp__option' );
			if ( $opts.length ) { $opts.eq( Math.max( 0, Math.min( index, $opts.length - 1 ) ) ).focus(); }
		}
		function moveFocus( $panel, dir ) {
			var $opts = $panel.find( '.tsp__option' );
			var idx   = $opts.index( $panel.find( '.tsp__option:focus' ) );
			focusOption( $panel, idx + dir );
		}

		function uniq( $option ) {
			var id = $option.data( 'tspUid' );
			if ( ! id ) { id = ++uniqCounter; $option.data( 'tspUid', id ); }
			return id;
		}
		function cssEscape( s ) {
			if ( window.CSS && window.CSS.escape ) { return window.CSS.escape( s ); }
			return String( s ).replace( /(["\\])/g, '\\$1' );
		}
	} );
})();
