/**
 * Option Type: Predefined Colors Color Picker (Compact)
 *
 * Behaviors:
 * - Toggle the dropdown panel open / closed on trigger click.
 * - Click an option → write its data-class to the hidden preset input,
 *   update the trigger's swatch + label, mark it selected, close the panel,
 *   and CLEAR the custom picker (mutual exclusion).
 * - Touching the custom picker → clear the preset selection.
 * - Click outside / Escape → close the panel.
 * - On init, paint the trigger to match whatever's already saved.
 */

(function () {
	var OPTION_CLASS = 'fw-option-type-predefined-colors-color-picker-compact';

	jQuery( document ).ready( function ( $ ) {

		fwEvents.on( 'fw:options:init', function ( data ) {
			var $options = data.$elements.find( '.' + OPTION_CLASS + ':not(.initialized)' );
			$options.each( function () {
				initOption( $( this ) );
			} );
		} );

		function initOption( $option ) {
			$option.addClass( 'initialized' );

			var $trigger     = $option.find( '.pccpc__trigger' );
			var $panel       = $option.find( '.pccpc__panel' );
			var $presetInput = $option.find( '.pccpc__preset-input' );
			var $radio       = $option.find( '.pccpc__radio' );
			var $customInput = $option.find( '.pccpc__custom .pccpc__custom-input' );

			// --- Trigger toggles the panel ---
			$trigger.on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				togglePanel( $option, !panelIsOpen( $panel ) );
			} );

			// --- Option click → pick preset ---
			$panel.on( 'click', '.pccpc__option', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				selectPreset( $option, $( this ) );
				togglePanel( $option, false );
			} );

			// --- Custom picker focus → clear preset ---
			// Use focusin so it bubbles from any input inside .pccpc__custom
			// (the wp-color-picker renders multiple form fields).
			$option.find( '.pccpc__custom' ).on( 'focusin click', function () {
				clearPreset( $option );
			} );
			$radio.on( 'click', function () {
				clearPreset( $option );
			} );

			// --- Outside click closes the panel ---
			$( document ).on( 'mousedown.pccpc-' + uniq( $option ), function ( e ) {
				if ( ! $option[0].contains( e.target ) ) {
					togglePanel( $option, false );
				}
			} );

			// --- Escape closes ---
			$option.on( 'keydown', function ( e ) {
				if ( e.key === 'Escape' || e.keyCode === 27 ) {
					togglePanel( $option, false );
					$trigger.focus();
				}
			} );

			// --- Initial paint from saved value ---
			repaintFromSaved( $option );
		}

		function panelIsOpen( $panel ) {
			return $panel.length > 0 && ! $panel.prop( 'hidden' );
		}

		function togglePanel( $option, open ) {
			var $panel   = $option.find( '.pccpc__panel' );
			var $trigger = $option.find( '.pccpc__trigger' );
			if ( open ) {
				$panel.prop( 'hidden', false );
				$trigger.attr( 'aria-expanded', 'true' );
				positionPanel( $option );
				// Keep the panel glued to its trigger as the modal / page scrolls or
				// resizes (capture phase catches scrolling inside the modal body).
				var reposition = function () { positionPanel( $option ); };
				$option.data( 'pccpcReposition', reposition );
				window.addEventListener( 'scroll', reposition, true );
				window.addEventListener( 'resize', reposition );
			} else {
				$panel.prop( 'hidden', true );
				$trigger.attr( 'aria-expanded', 'false' );
				var rep = $option.data( 'pccpcReposition' );
				if ( rep ) {
					window.removeEventListener( 'scroll', rep, true );
					window.removeEventListener( 'resize', rep );
					$option.removeData( 'pccpcReposition' );
				}
				// Reset inline positioning so the CSS default governs while hidden.
				$panel.css( { position: '', top: '', left: '' } );
			}
		}

		// Anchor the panel to its trigger as position:fixed so it escapes any
		// ancestor with overflow:hidden (a settings box / scroll container) that
		// would otherwise clip it. Flips above the trigger when there is no room
		// below, and clamps to the viewport.
		function positionPanel( $option ) {
			var trg    = $option.find( '.pccpc__trigger' ).get( 0 );
			var $panel = $option.find( '.pccpc__panel' );
			var pnl    = $panel.get( 0 );
			if ( ! trg || ! pnl ) { return; }

			$panel.css( { position: 'fixed', top: '0px', left: '0px' } ); // fix + reset before measuring

			var rect = trg.getBoundingClientRect();
			var pw   = pnl.offsetWidth;
			var ph   = pnl.offsetHeight;
			var vw   = window.innerWidth;
			var vh   = window.innerHeight;

			var left = rect.left;
			if ( left + pw > vw - 8 ) { left = Math.max( 8, vw - pw - 8 ); }

			var top = rect.bottom + 4;
			if ( top + ph > vh - 8 && rect.top - ph - 4 > 8 ) {
				top = rect.top - ph - 4; // flip above
			}

			$panel.css( { left: Math.round( left ) + 'px', top: Math.round( top ) + 'px' } );
		}

		function selectPreset( $option, $opt ) {
			var classVal = $opt.attr( 'data-class' ) || '';
			var colorVal = $opt.attr( 'data-color' ) || '';
			var labelVal = $opt.attr( 'data-label' ) || '';
			var isLight  = $opt.attr( 'data-light' ) === '1';

			// Write the saved value
			$option.find( '.pccpc__preset-input' )
				.val( classVal )
				.trigger( 'change' );

			// Repaint the trigger
			var $trigger      = $option.find( '.pccpc__trigger' );
			var $triggerLabel = $trigger.find( '.pccpc__trigger-label' );
			$trigger.css( '--pccpc-color', colorVal !== '' ? colorVal : 'transparent' );
			// Inline-style attr keeps the CSS-var in sync AND ensures the
			// [style*="--pccpc-color: transparent"] selector in our CSS keeps
			// working (the .css() call doesn't always update the inline-style
			// attribute in older jQuery).
			$trigger.attr( 'style', '--pccpc-color: ' + ( colorVal !== '' ? colorVal : 'transparent' ) + ';' );
			$triggerLabel.text( labelVal !== '' ? labelVal : '— Select —' );

			// Mirror the per-option 'light' chip onto the trigger so the
			// picked color stays readable when it's a near-white.
			$trigger.toggleClass( 'pccpc__trigger--light', isLight );

			// Mark selected in the panel
			$option.find( '.pccpc__option' )
				.removeClass( 'is-selected' )
				.attr( 'aria-selected', 'false' );
			$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );

			// Clear the custom picker (mutual exclusion)
			clearCustomPicker( $option );
		}

		function clearPreset( $option ) {
			// Don't clear if the panel just opened from the trigger — the
			// trigger isn't part of .pccpc__custom anyway, so focusin on
			// the custom side genuinely means the user is touching the
			// picker.
			$option.find( '.pccpc__preset-input' )
				.val( '' )
				.trigger( 'change' );

			var $trigger      = $option.find( '.pccpc__trigger' );
			var $triggerLabel = $trigger.find( '.pccpc__trigger-label' );
			$trigger.attr( 'style', '--pccpc-color: transparent;' );
			$trigger.removeClass( 'pccpc__trigger--light' );
			$triggerLabel.text( '— Select —' );

			$option.find( '.pccpc__option' )
				.removeClass( 'is-selected' )
				.attr( 'aria-selected', 'false' );
			$option.find( '.pccpc__option--empty' )
				.addClass( 'is-selected' )
				.attr( 'aria-selected', 'true' );
		}

		function clearCustomPicker( $option ) {
			// The wp-color-picker / rgba-color-picker exposes a hidden input
			// holding the actual saved value (e.g. '#d9534f' or
			// 'rgba(0,0,0,0)'). Find any input under .pccpc__custom whose
			// name ends in '[custom]' and blank it. Also clear the visible
			// text field so the user sees the picker reset.
			var $custom = $option.find( '.pccpc__custom' );
			$custom.find( 'input[name$="[custom]"]' ).val( '' );
			$custom.find( 'input.pccpc__custom-input, input.fw-option-color-picker, input.fw-option-rgba-color-picker' )
				.val( '' )
				.trigger( 'change' );
			$option.find( '.pccpc__radio' ).prop( 'checked', false );
		}

		function repaintFromSaved( $option ) {
			var saved = ( $option.find( '.pccpc__preset-input' ).val() || '' ).toString();
			if ( saved === '' ) {
				// No preset; if the custom picker has a value, mark the radio.
				var $customVal = $option.find( '.pccpc__custom input[name$="[custom]"]' ).val() || '';
				if ( $customVal !== '' ) {
					$option.find( '.pccpc__radio' ).prop( 'checked', true );
				}
				return;
			}
			var $opt = $option.find( '.pccpc__option[data-class="' + cssEscape( saved ) + '"]' ).first();
			if ( $opt.length ) {
				// Reuse selectPreset paint logic, but without firing the
				// custom-picker clear (we're just repainting from saved).
				var colorVal = $opt.attr( 'data-color' ) || '';
				var labelVal = $opt.attr( 'data-label' ) || '';
				var isLight  = $opt.attr( 'data-light' ) === '1';
				var $trigger = $option.find( '.pccpc__trigger' );
				$trigger.attr( 'style', '--pccpc-color: ' + ( colorVal !== '' ? colorVal : 'transparent' ) + ';' );
				$trigger.find( '.pccpc__trigger-label' ).text( labelVal !== '' ? labelVal : '— Select —' );
				$trigger.toggleClass( 'pccpc__trigger--light', isLight );
				$option.find( '.pccpc__option' )
					.removeClass( 'is-selected' )
					.attr( 'aria-selected', 'false' );
				$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
			}
		}

		// --- Tiny utilities ---
		var uniqCounter = 0;
		function uniq( $option ) {
			var id = $option.data( 'pccpcUid' );
			if ( ! id ) {
				id = ++uniqCounter;
				$option.data( 'pccpcUid', id );
			}
			return id;
		}

		// CSS.escape polyfill light enough to inline
		function cssEscape( s ) {
			if ( window.CSS && window.CSS.escape ) { return window.CSS.escape( s ); }
			return String( s ).replace( /(["\\])/g, '\\$1' );
		}
	} );
} )();
