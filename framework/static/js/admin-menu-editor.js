/**
 * Unyson+ → Workspaces → Menus: the per-role admin menu editor.
 *
 * The page is the model: rows are dragged, renamed, given icons and switched
 * off in place, and on submit the whole layout is read back from the DOM into
 * one JSON field (so a host's max_input_vars cannot cut it short). The preview
 * is redrawn from the same rows after every change.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.fwMenuEditor || { icons: [], i18n: {} };

	$( function () {
		var $root = $( '#fw-me' );

		if ( ! $root.length ) {
			return;
		}

		var $preview = $( '#fw-me-preview' );
		var $picker = null;
		var $pickerRow = null;

		function iconOf( $row ) {
			return $row.attr( 'data-new-icon' ) || $row.attr( 'data-icon' ) || 'dashicons-admin-generic';
		}

		function nameOf( $row ) {
			var v = $.trim( $row.find( '> .fw-me-line .fw-me-label' ).val() || '' );
			return v || $row.attr( 'data-title' );
		}

		function preview() {
			$preview.empty();

			$root.find( '.fw-me-list' ).each( function () {
				$( this ).toggleClass( 'is-empty', ! $( this ).children( '.fw-me-row' ).length );
			} );

			$root.find( '.fw-me-group' ).each( function () {
				var $group = $( this );
				var $rows = $group.find( '.fw-me-row' ).not( '.is-hidden' );

				if ( ! $rows.length ) {
					return;
				}

				var $name = $group.find( '.fw-me-group-name' );
				if ( $name.length ) {
					$( '<li class="fw-me-pv-group"></li>' ).text( $.trim( $name.val() ) || $name.attr( 'placeholder' ) ).appendTo( $preview );
				}

				$rows.each( function () {
					var $row = $( this );

					if ( $row.hasClass( 'is-sep' ) ) {
						$( '<li class="fw-me-pv-sep"></li>' ).appendTo( $preview );
						return;
					}

					$( '<li class="fw-me-pv-item"></li>' )
						.append( $( '<span class="dashicons" aria-hidden="true"></span>' ).addClass( iconOf( $row ) ) )
						.append( $( '<span></span>' ).text( nameOf( $row ) ) )
						.appendTo( $preview );
				} );
			} );
		}

		function closePicker() {
			if ( $picker ) {
				$picker.remove();
				$picker = null;
			}
			if ( $pickerRow ) {
				$pickerRow.find( '> .fw-me-line .fw-me-icon' ).attr( 'aria-expanded', 'false' );
				$pickerRow = null;
			}
		}

		function setIcon( $row, icon ) {
			$row.attr( 'data-new-icon', icon );
			$row.find( '> .fw-me-line .fw-me-icon .dashicons' ).attr( 'class', 'dashicons ' + iconOf( $row ) );
			preview();
		}

		function openPicker( $row, $btn ) {
			closePicker();
			$pickerRow = $row;
			$btn.attr( 'aria-expanded', 'true' );

			$picker = $( '<div class="fw-me-picker" role="dialog"></div>' ).attr( 'aria-label', cfg.i18n.icon || 'Change icon' );
			$( '<button type="button" class="fw-me-picker-reset"></button>' ).text( cfg.i18n[ 'default' ] || 'Default icon' ).on( 'click', function () {
				setIcon( $row, '' );
				closePicker();
				$btn.trigger( 'focus' );
			} ).appendTo( $picker );

			var $grid = $( '<div class="fw-me-picker-grid"></div>' ).appendTo( $picker );
			$.each( cfg.icons, function ( i, icon ) {
				$( '<button type="button"></button>' )
					.attr( { title: icon.replace( 'dashicons-', '' ), 'aria-label': icon.replace( 'dashicons-', '' ) } )
					.toggleClass( 'is-current', icon === iconOf( $row ) )
					.append( $( '<span class="dashicons" aria-hidden="true"></span>' ).addClass( icon ) )
					.on( 'click', function () {
						setIcon( $row, icon );
						closePicker();
						$btn.trigger( 'focus' );
					} )
					.appendTo( $grid );
			} );

			$picker.insertAfter( $row.find( '> .fw-me-line' ) );
			( $grid.find( 'button.is-current' ).first().length ? $grid.find( 'button.is-current' ).first() : $grid.find( 'button' ).first() ).trigger( 'focus' );
		}

		function refuse( $list ) {
			$list.addClass( 'is-refused' );
			window.setTimeout( function () {
				$list.removeClass( 'is-refused' );
			}, 900 );
			if ( window.wp && wp.a11y && cfg.i18n.noDrop ) {
				wp.a11y.speak( cfg.i18n.noDrop );
			}
		}

		function bindSubs( $subs ) {
			// Drag from anywhere on a sub-item; only its checkbox keeps its own click.
			$subs.sortable( {
				cancel: 'input',
				distance: 4,
				items: '> li:not(.is-pinned):not(.is-locked):not(.is-lifted)',
				connectWith: '.fw-me-list',
				placeholder: 'fw-me-placeholder fw-me-subplaceholder',
				forcePlaceholderSize: true,
				tolerance: 'pointer',
				start: function () {
					closePicker();
					$root.addClass( 'is-dragging-sub' );
				},
				stop: function () {
					$root.removeClass( 'is-dragging-sub' );
					preview();
				},
				// A top-level row dropped here: only a moved sub-item, back into its own menu.
				receive: function ( e, ui ) {
					var $row = ui.item;
					var $parent = $( this ).closest( '.fw-me-row' );

					if ( ! $row.is( '.fw-me-row' ) || $row.attr( 'data-lift-parent' ) !== $parent.attr( 'data-slug' ) ) {
						$( ui.sender ).sortable( 'cancel' );
						refuse( $( this ) );
						return;
					}

					var $sub = $row.data( 'sub' );
					( $sub && $sub.length ? $sub.removeClass( 'is-lifted' ).prop( 'hidden', false ) : subRow( $row ) ).insertBefore( $row );
					$row.remove();
					preview();
				}
			} );
		}

		// Drag from anywhere on an item's header, like a postbox. A press that does not
		// move (distance) stays a click, which opens the item; the fields keep theirs.
		$root.find( '.fw-me-list' ).sortable( {
			handle: '> .fw-me-line',
			cancel: 'input, button, select, textarea, .fw-me-toggle',
			distance: 4,
			connectWith: '.fw-me-list, .fw-me-subs',
			items: '> .fw-me-row',
			placeholder: 'fw-me-placeholder',
			forcePlaceholderSize: true,
			tolerance: 'pointer',
			start: closePicker,
			update: preview,
			// A sub-item dropped here becomes a top-level row where it was dropped.
			receive: function ( e, ui ) {
				var $sub = ui.item;

				if ( $sub.is( '.fw-me-row' ) ) {
					return; // a row moving between groups
				}

				var $sender = $( ui.sender );
				var $row = liftedRow( $sub.attr( 'data-title' ), $sub.attr( 'data-key' ), $sub.attr( 'data-url' ), $sender.closest( '.fw-me-row' ).attr( 'data-slug' ), $sub.attr( 'data-slug' ), $sub.attr( 'data-icon' ) );

				$row.insertBefore( $sub );
				$row.data( 'sub', $sub );
				$sub.addClass( 'is-lifted' ).prop( 'hidden', true ).appendTo( $sender );
				preview();
			}
		} );

		$root.find( '.fw-me-subs' ).each( function () {
			bindSubs( $( this ) );
		} );

		$root.on( 'click', '.fw-me-icon', function () {
			var $row = $( this ).closest( '.fw-me-row' );
			if ( $pickerRow && $pickerRow[ 0 ] === $row[ 0 ] ) {
				closePicker();
				return;
			}
			openPicker( $row, $( this ) );
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && $picker ) {
				var $btn = $pickerRow.find( '> .fw-me-line .fw-me-icon' );
				closePicker();
				$btn.trigger( 'focus' );
			}
		} );

		function toggleSubs( $row ) {
			var $subs = $row.find( '> .fw-me-subs' );
			var $btn = $row.find( '> .fw-me-line .fw-me-more' );

			if ( ! $subs.length || ! $btn.length ) {
				return;
			}

			var open = $subs.prop( 'hidden' );
			$subs.prop( 'hidden', ! open );
			$btn.attr( 'aria-expanded', open ? 'true' : 'false' );
			$row.toggleClass( 'is-open', open );
		}

		// The arrow opens an item, and so does a click on the item's empty space,
		// the way the WordPress Menus screen opens a menu item.
		$root.on( 'click', '.fw-me-more', function () {
			toggleSubs( $( this ).closest( '.fw-me-row' ) );
		} );

		$root.on( 'click', '.fw-me-line', function ( e ) {
			if ( $( e.target ).closest( 'button, input, label' ).length ) {
				return;
			}
			toggleSubs( $( this ).closest( '.fw-me-row' ) );
		} );

		// A sub-item moved to the top level becomes a row of its own, right after
		// its old parent, until it is moved back.
		function liftedRow( title, key, url, parentSlug, slug, icon ) {
			icon = icon || 'dashicons-admin-generic';
			var $row = $( '<li class="fw-me-row"></li>' ).attr( {
				'data-key': key,
				'data-slug': url,
				'data-icon': icon,
				'data-new-icon': '',
				'data-title': title,
				'data-lift-parent': parentSlug,
				'data-lift-slug': slug
			} );
			var $line = $( '<div class="fw-me-line"></div>' ).appendTo( $row );

			$( '<span class="fw-me-handle dashicons dashicons-menu" aria-hidden="true"></span>' ).appendTo( $line );
			$( '<button type="button" class="fw-me-icon" aria-expanded="false"></button>' )
				.attr( 'aria-label', cfg.i18n.icon || 'Change icon' )
				.append( $( '<span class="dashicons" aria-hidden="true"></span>' ).addClass( icon ) )
				.appendTo( $line );
			$( '<input type="text" class="fw-me-label" />' ).attr( { placeholder: title, 'aria-label': title } ).appendTo( $line );
			$( '<label class="fw-me-toggle"><input type="checkbox" class="fw-me-shown" checked /> <span></span></label>' )
				.find( 'span' ).text( cfg.i18n.shown || 'Shown' ).end()
				.appendTo( $line );
			$( '<span class="fw-me-more-space" aria-hidden="true"></span>' ).appendTo( $line );

			return $row;
		}

		function subRow( $row ) {
			return $( '<li class="fw-me-sub-row"></li>' ).attr( {
				'data-slug': $row.attr( 'data-lift-slug' ),
				'data-title': $row.attr( 'data-title' ),
				'data-key': $row.attr( 'data-key' ),
				'data-url': $row.attr( 'data-slug' ),
				'data-icon': $row.attr( 'data-icon' )
			} )
				.append( '<span class="fw-me-subhandle dashicons dashicons-menu" aria-hidden="true"></span>' )
				.append( $( '<label></label>' ).append( $( '<input type="checkbox" class="fw-me-sub" checked />' ).val( $row.attr( 'data-lift-slug' ) ) ).append( ' ' + $row.attr( 'data-title' ) ) );
		}

		// Turn a sub-item into a top-level row at $at (a sibling to insert before),
		// or right after its parent when $at is not given.
		function lift( $sub, $at ) {
			var $parent = $sub.closest( '.fw-me-row' );
			var $row = liftedRow( $sub.attr( 'data-title' ), $sub.attr( 'data-key' ), $sub.attr( 'data-url' ), $parent.attr( 'data-slug' ), $sub.attr( 'data-slug' ), $sub.attr( 'data-icon' ) );

			$row.data( 'sub', $sub );
			if ( $at ) {
				$row.insertBefore( $at );
			} else {
				$row.insertAfter( $parent );
			}
			$sub.addClass( 'is-lifted' ).prop( 'hidden', true );
			return $row;
		}

		function parentOf( $row ) {
			return $root.find( '.fw-me-row' ).filter( function () {
				return $( this ).attr( 'data-slug' ) === $row.attr( 'data-lift-parent' );
			} ).first();
		}

		// Keyboard equivalents of dragging a sub-item out (Alt + Left) and back in
		// (Alt + Right); a mouse just drags.
		$root.on( 'keydown', '.fw-me-sub-row', function ( e ) {
			if ( ! e.altKey || 'ArrowLeft' !== e.key || $( this ).hasClass( 'is-pinned' ) || $( this ).hasClass( 'is-locked' ) ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			lift( $( this ) ).find( '.fw-me-label' ).trigger( 'focus' );
			preview();
		} );

		$root.on( 'keydown', '.fw-me-row[data-lift-parent]', function ( e ) {
			if ( ! e.altKey || 'ArrowRight' !== e.key ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			moveBack( $( this ) );
		} );

		function moveBack( $row ) {
			var $sub = $row.data( 'sub' );
			var $parent = parentOf( $row );

			if ( $sub ) {
				$sub.removeClass( 'is-lifted' ).prop( 'hidden', false );
			} else if ( $parent.length ) {
				// Lifted before this page loaded: put it back among its parent's sub-items.
				var $subs = $parent.find( '> .fw-me-subs' );
				if ( ! $subs.length ) {
					$subs = $( '<ul class="fw-me-subs" hidden></ul>' ).appendTo( $parent );
					bindSubs( $subs );
				}
				subRow( $row ).appendTo( $subs );
			}

			( $parent.length ? $parent.find( '> .fw-me-line .fw-me-label' ) : $() ).trigger( 'focus' );
			$row.remove();
			preview();
		}

		$root.on( 'change', '.fw-me-shown', function () {
			$( this ).closest( '.fw-me-row' ).toggleClass( 'is-hidden', ! this.checked );
			preview();
		} );

		$root.on( 'input', '.fw-me-label, .fw-me-group-name', preview );

		// Keyboard reordering: Alt + Arrow Up / Down from anywhere in a row.
		$root.on( 'keydown', '.fw-me-sub-row', function ( e ) {
			if ( ! e.altKey || ( 'ArrowUp' !== e.key && 'ArrowDown' !== e.key ) || $( this ).hasClass( 'is-pinned' ) || $( this ).hasClass( 'is-locked' ) ) {
				return;
			}
			var $sub = $( this );
			var $prev = $sub.prevAll( '.fw-me-sub-row:not(.is-pinned):not(.is-locked):not(.is-lifted)' ).first();
			var $next = $sub.nextAll( '.fw-me-sub-row:not(.is-lifted)' ).first();
			e.preventDefault();
			e.stopPropagation();
			if ( 'ArrowUp' === e.key && $prev.length ) {
				$sub.insertBefore( $prev );
			} else if ( 'ArrowDown' === e.key && $next.length ) {
				$sub.insertAfter( $next );
			}
			$( e.target ).trigger( 'focus' );
		} );

		$root.on( 'keydown', '.fw-me-row', function ( e ) {
			if ( ! e.altKey || ( 'ArrowUp' !== e.key && 'ArrowDown' !== e.key ) ) {
				return;
			}
			var $row = $( this );
			e.preventDefault();
			e.stopPropagation();
			if ( 'ArrowUp' === e.key && $row.prev( '.fw-me-row' ).length ) {
				$row.insertBefore( $row.prev( '.fw-me-row' ) );
			} else if ( 'ArrowDown' === e.key && $row.next( '.fw-me-row' ).length ) {
				$row.insertAfter( $row.next( '.fw-me-row' ) );
			}
			$( e.target ).trigger( 'focus' );
			preview();
		} );

		$( '#fw-me-form' ).on( 'submit', function ( e ) {
			var submitter = e.originalEvent && e.originalEvent.submitter;
			if ( submitter && 'reset' === submitter.name ) {
				return;
			}

			var layout = { order: [], hidden: [], labels: {}, icons: {}, sub_hidden: {}, sub_order: {}, map: {}, group_labels: {}, slugs: {}, lifted: [] };

			$root.find( '.fw-me-group' ).each( function () {
				var group = $( this ).attr( 'data-group' );
				var $name = $( this ).find( '.fw-me-group-name' );

				if ( $name.length && $.trim( $name.val() ) ) {
					layout.group_labels[ group ] = $.trim( $name.val() );
				}

				$( this ).find( '.fw-me-row' ).each( function () {
					var $row = $( this );
					var key = $row.attr( 'data-key' );
					var label = $.trim( $row.find( '> .fw-me-line .fw-me-label' ).val() || '' );

					layout.order.push( key );
					layout.slugs[ key ] = $row.attr( 'data-slug' );

					if ( $row.attr( 'data-lift-parent' ) ) {
						layout.lifted.push( { parent: $row.attr( 'data-lift-parent' ), slug: $row.attr( 'data-lift-slug' ) } );
					}

					if ( group ) {
						layout.map[ key ] = group;
					}
					if ( $row.hasClass( 'is-hidden' ) ) {
						layout.hidden.push( key );
					}
					if ( label ) {
						layout.labels[ key ] = label;
					}
					if ( $row.attr( 'data-new-icon' ) ) {
						layout.icons[ key ] = $row.attr( 'data-new-icon' );
					}

					var subs = $row.find( '> .fw-me-subs > li' ).not( '.is-lifted' ).map( function () {
						return $( this ).attr( 'data-slug' );
					} ).get();
					if ( subs.length > 1 ) {
						layout.sub_order[ key ] = subs;
					}

					var off = $row.find( '.fw-me-subs > li' ).not( '.is-lifted' ).find( '.fw-me-sub' ).not( ':checked' ).map( function () {
						return this.value;
					} ).get();
					if ( off.length ) {
						layout.sub_hidden[ key ] = off;
					}
				} );
			} );

			$( '#fw-me-layout' ).val( JSON.stringify( layout ) );
		} );

		preview();
	} );
}( window.jQuery ) );
