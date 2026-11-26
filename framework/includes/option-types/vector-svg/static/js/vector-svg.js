/**
 * Vector SVG option type (roadmap 7g): the control (preview + Draw / edit + SVG code) and the vector editor.
 *
 * The editor is a full-screen panel on paper.js (loaded on first open): Select (V), Points (A), Pen (P),
 * Rectangle (R), Ellipse (E), Polygon (G) and Star (S) tools; boolean Union / Subtract / Intersect / Exclude on the
 * selection; fill, stroke and stroke width; bring forward / send backward; delete; undo / redo; and Save, which writes
 * clean SVG (a viewBox fitted to the drawing, rounded numbers) back into the field.
 */
( function ( $ ) {
	'use strict';
	var CFG = window._fwVectorSvg || {};
	var paperReady = null;

	function loadPaper() {
		if ( window.paper ) { return Promise.resolve( window.paper ); }
		if ( paperReady ) { return paperReady; }
		paperReady = new Promise( function ( resolve, reject ) {
			var s = document.createElement( 'script' );
			s.src = CFG.paper;
			s.onload = function () { window.paper ? resolve( window.paper ) : reject( new Error( 'paper.js did not load' ) ); };
			s.onerror = function () { paperReady = null; reject( new Error( 'paper.js did not load' ) ); };
			document.head.appendChild( s );
		} );
		return paperReady;
	}

	/* ------------------------------------------------------------------ the control */

	function initControl( $root ) {
		if ( $root.data( 'fwVsvgInit' ) ) { return; }
		$root.data( 'fwVsvgInit', 1 );
		var $code = $root.find( '.fw-vsvg__code' ), $prev = $root.find( '.fw-vsvg__preview' );
		var show = function () {
			var v = String( $code.val() || '' ).trim();
			$prev.html( /<svg[\s>]/i.test( v ) ? v.replace( /<script[\s\S]*?<\/script>/gi, '' ).replace( /\son\w+="[^"]*"/gi, '' ) : '<span class="fw-vsvg__empty">No shape yet</span>' );
		};
		$root.on( 'click', '.fw-vsvg__code-toggle', function ( e ) { e.preventDefault(); $root.toggleClass( 'is-code' ); } );
		$code.on( 'input change', show );
		$root.on( 'click', '.fw-vsvg__open', function ( e ) {
			e.preventDefault();
			var btn = this;
			btn.disabled = true;
			loadPaper().then( function ( P ) {
				btn.disabled = false;
				openEditor( P, String( $code.val() || '' ), function ( svg ) {
					$code.val( svg ).trigger( 'change' );
					show();
				} );
			} ).catch( function ( err ) { btn.disabled = false; window.alert( 'The vector editor could not load: ' + err.message ); } );
		} );
	}

	/* ------------------------------------------------------------------ the editor */

	var TOOLS = [
		[ 'select', 'Select', 'V' ], [ 'points', 'Points', 'A' ], [ 'pen', 'Pen', 'P' ],
		[ 'rect', 'Rectangle', 'R' ], [ 'ellipse', 'Ellipse', 'E' ], [ 'polygon', 'Polygon', 'G' ], [ 'star', 'Star', 'S' ]
	];
	var BOOLS = [ [ 'unite', 'Union' ], [ 'subtract', 'Subtract' ], [ 'intersect', 'Intersect' ], [ 'exclude', 'Exclude' ] ];

	function el( tag, cls, html ) { var e = document.createElement( tag ); if ( cls ) { e.className = cls; } if ( html != null ) { e.innerHTML = html; } return e; }

	function openEditor( P, svg, onSave ) {
		var wrap = el( 'div', 'fw-vsvg-ed' );
		wrap.setAttribute( 'role', 'dialog' );
		wrap.setAttribute( 'aria-label', 'Vector editor' );
		var bar = el( 'div', 'fw-vsvg-ed__bar' );
		var tools = el( 'div', 'fw-vsvg-ed__group' );
		TOOLS.forEach( function ( t ) {
			var b = el( 'button', 'fw-vsvg-ed__tool', t[ 1 ] + ' <kbd>' + t[ 2 ] + '</kbd>' );
			b.type = 'button'; b.setAttribute( 'data-tool', t[ 0 ] ); b.title = t[ 1 ] + ' (' + t[ 2 ] + ')';
			tools.appendChild( b );
		} );
		var sides = el( 'label', 'fw-vsvg-ed__field', 'Sides <input type="number" min="3" max="24" value="6" class="fw-vsvg-ed__sides">' );
		tools.appendChild( sides );
		var bools = el( 'div', 'fw-vsvg-ed__group' );
		BOOLS.forEach( function ( o ) { var b = el( 'button', 'fw-vsvg-ed__bool', o[ 1 ] ); b.type = 'button'; b.setAttribute( 'data-bool', o[ 0 ] ); b.title = o[ 1 ] + ' the selected shapes'; bools.appendChild( b ); } );
		var style = el( 'div', 'fw-vsvg-ed__group',
			'<label class="fw-vsvg-ed__field">Fill <input type="color" class="fw-vsvg-ed__fill" value="#00b295"> <input type="checkbox" class="fw-vsvg-ed__nofill" title="No fill"> none</label>' +
			'<label class="fw-vsvg-ed__field">Stroke <input type="color" class="fw-vsvg-ed__stroke" value="#1c1e22"></label>' +
			'<label class="fw-vsvg-ed__field">Width <input type="number" min="0" max="40" step="0.5" value="0" class="fw-vsvg-ed__sw"></label>' );
		var acts = el( 'div', 'fw-vsvg-ed__group',
			'<button type="button" data-act="forward" title="Bring forward">Forward</button>' +
			'<button type="button" data-act="backward" title="Send backward">Backward</button>' +
			'<button type="button" data-act="delete" title="Delete (Del)">Delete</button>' +
			'<button type="button" data-act="undo" title="Undo (Ctrl+Z)">Undo</button>' +
			'<button type="button" data-act="redo" title="Redo (Ctrl+Shift+Z)">Redo</button>' );
		var end = el( 'div', 'fw-vsvg-ed__group fw-vsvg-ed__end',
			'<button type="button" data-act="cancel">Cancel</button><button type="button" data-act="save" class="is-primary">Save shape</button>' );
		[ tools, bools, style, acts, end ].forEach( function ( g ) { bar.appendChild( g ); } );
		var hint = el( 'div', 'fw-vsvg-ed__hint' );
		var stage = el( 'div', 'fw-vsvg-ed__stage' );
		var canvas = el( 'canvas', 'fw-vsvg-ed__canvas' );
		canvas.setAttribute( 'tabindex', '0' );
		stage.appendChild( canvas );
		wrap.appendChild( bar ); wrap.appendChild( hint ); wrap.appendChild( stage );
		document.body.appendChild( wrap );
		document.documentElement.classList.add( 'fw-vsvg-open' );

		var scope = new P.PaperScope();
		canvas.width = stage.clientWidth; canvas.height = stage.clientHeight;
		scope.setup( canvas );
		var project = scope.project, view = scope.view;
		var art = new scope.Layer(); art.name = 'art';
		var ui = new scope.Layer(); ui.name = 'ui';
		art.activate();

		// Load the existing drawing, fitted into the view.
		if ( /<svg[\s>]/i.test( svg ) ) {
			try {
				var imp = project.importSVG( svg, { expandShapes: true, insert: false } );
				if ( imp ) {
					art.addChildren( imp.removeChildren ? flatten( imp ) : [ imp ] );
					fit();
				}
			} catch ( e ) { /* leave the canvas empty */ }
		}
		function flatten( g ) {
			var out = [];
			( g.children ? g.children.slice() : [] ).forEach( function ( c ) {
				if ( c.clipMask ) { return; } // the viewBox clip paper.js adds on import
				if ( c.className === 'Group' ) { out = out.concat( flatten( c ) ); }
				else { out.push( c ); }
			} );
			return out;
		}
		function fit() {
			var b = art.bounds;
			if ( ! b || ! b.width || ! b.height ) { return; }
			var s = Math.min( ( view.viewSize.width * 0.7 ) / b.width, ( view.viewSize.height * 0.7 ) / b.height );
			art.scale( s, b.center );
			art.position = view.center;
		}

		// History (whole drawing as JSON).
		var past = [], future = [];
		function snap() { past.push( art.exportJSON() ); if ( past.length > 80 ) { past.shift(); } future = []; }
		function restore( json ) { clearSel(); art.removeChildren(); art.importJSON( json ); }

		// Selection.
		var sel = [];
		function clearSel() { sel.forEach( function ( i ) { i.selected = false; i.fullySelected = false; } ); sel = []; syncStyle(); }
		function select( item, add ) {
			if ( ! add ) { clearSel(); }
			if ( item && sel.indexOf( item ) < 0 ) { sel.push( item ); }
			sel.forEach( function ( i ) { if ( tool === 'points' ) { i.fullySelected = true; } else { i.selected = true; } } );
			syncStyle();
		}
		function topItem( hit ) {
			var it = hit && hit.item;
			while ( it && it.parent && it.parent !== art ) { it = it.parent; }
			return it && it.layer === art ? it : null;
		}

		// Style controls.
		var $w = $( wrap );
		var fillIn = $w.find( '.fw-vsvg-ed__fill' )[ 0 ], noFill = $w.find( '.fw-vsvg-ed__nofill' )[ 0 ], strokeIn = $w.find( '.fw-vsvg-ed__stroke' )[ 0 ], swIn = $w.find( '.fw-vsvg-ed__sw' )[ 0 ];
		function hex( c ) { return c && c.toCSS ? c.toCSS( true ) : null; }
		function syncStyle() {
			if ( ! sel.length ) { return; }
			var i = sel[ 0 ];
			if ( i.fillColor ) { fillIn.value = hex( i.fillColor ); noFill.checked = false; } else { noFill.checked = true; }
			if ( i.strokeColor ) { strokeIn.value = hex( i.strokeColor ); }
			swIn.value = i.strokeColor ? ( i.strokeWidth || 0 ) : 0;
		}
		function styleNew( item ) {
			item.fillColor = noFill.checked ? null : fillIn.value;
			var sw = parseFloat( swIn.value ) || 0;
			item.strokeColor = sw > 0 ? strokeIn.value : null;
			item.strokeWidth = sw;
			item.strokeJoin = 'round'; item.strokeCap = 'round';
		}
		function applyStyle() {
			if ( ! sel.length ) { return; }
			snap();
			sel.forEach( function ( i ) { styleNew( i ); } );
		}
		[ fillIn, noFill, strokeIn, swIn ].forEach( function ( x ) { x.addEventListener( 'change', applyStyle ); } );

		// Tools.
		var tool = 'select', drag = null, penPath = null;
		var HINTS = {
			select: 'Click a shape to select it, Shift+click to add, drag to move. Arrow keys nudge (Shift for 10).',
			points: 'Drag a point or a handle to reshape. Double-click a point to make it sharp or smooth.',
			pen: 'Click to add corner points, drag to pull out curve handles. Click the first point to close the shape; Enter or Esc to finish it open.',
			rect: 'Drag to draw a rectangle (Shift for a square).', ellipse: 'Drag to draw an ellipse (Shift for a circle).',
			polygon: 'Drag from the centre to draw a polygon with the number of sides set next to the tools.',
			star: 'Drag from the centre to draw a star with that number of points.'
		};
		function setTool( t ) {
			finishPen();
			tool = t;
			$w.find( '.fw-vsvg-ed__tool' ).removeClass( 'is-on' ).filter( '[data-tool="' + t + '"]' ).addClass( 'is-on' );
			sel.forEach( function ( i ) { i.selected = false; i.fullySelected = false; if ( t === 'points' ) { i.fullySelected = true; } else { i.selected = true; } } );
			hint.textContent = HINTS[ t ] || '';
		}
		function finishPen( close ) {
			if ( ! penPath ) { return; }
			if ( close ) { penPath.closed = true; }
			if ( penPath.segments.length < 2 ) { penPath.remove(); } else { penPath.fullySelected = false; }
			penPath = null;
		}

		var t0 = new scope.Tool();
		t0.minDistance = 0;
		t0.onMouseDown = function ( e ) {
			canvas.focus();
			var hit = project.hitTest( e.point, { segments: true, handles: tool === 'points', fill: true, stroke: true, tolerance: 6 } );
			if ( tool === 'select' ) {
				var it = topItem( hit );
				if ( it ) {
					if ( sel.indexOf( it ) < 0 || ! e.modifiers.shift ) { select( it, e.modifiers.shift ); }
					snap(); drag = { kind: 'move' };
				} else { clearSel(); drag = null; }
			} else if ( tool === 'points' ) {
				if ( hit && hit.item && hit.item.layer === art && ( hit.type === 'segment' || hit.type === 'handle-in' || hit.type === 'handle-out' ) ) {
					snap(); drag = { kind: hit.type, seg: hit.segment };
				} else {
					var it2 = topItem( hit );
					clearSel(); if ( it2 ) { select( it2 ); }
					drag = null;
				}
			} else if ( tool === 'pen' ) {
				if ( penPath && penPath.segments.length > 2 && e.point.getDistance( penPath.firstSegment.point ) < 8 ) {
					snap(); finishPen( true ); return;
				}
				if ( ! penPath ) { snap(); penPath = new scope.Path(); styleNew( penPath ); if ( ! penPath.strokeColor && ! penPath.fillColor ) { penPath.strokeColor = strokeIn.value; penPath.strokeWidth = 2; } penPath.fullySelected = true; }
				penPath.add( e.point );
				drag = { kind: 'pen', seg: penPath.lastSegment };
			} else {
				snap();
				drag = { kind: 'shape', from: e.point, item: null };
			}
		};
		t0.onMouseDrag = function ( e ) {
			if ( ! drag ) { return; }
			if ( drag.kind === 'move' ) { sel.forEach( function ( i ) { i.position = i.position.add( e.delta ); } ); }
			else if ( drag.kind === 'segment' ) { drag.seg.point = drag.seg.point.add( e.delta ); }
			else if ( drag.kind === 'handle-in' ) { drag.seg.handleIn = drag.seg.handleIn.add( e.delta ); }
			else if ( drag.kind === 'handle-out' ) { drag.seg.handleOut = drag.seg.handleOut.add( e.delta ); }
			else if ( drag.kind === 'pen' ) { var d = e.point.subtract( drag.seg.point ); drag.seg.handleOut = d; drag.seg.handleIn = d.multiply( -1 ); }
			else if ( drag.kind === 'shape' ) {
				if ( drag.item ) { drag.item.remove(); }
				var a = drag.from, b = e.point, n = Math.max( 3, Math.min( 24, parseInt( $w.find( '.fw-vsvg-ed__sides' ).val(), 10 ) || 6 ) );
				if ( e.modifiers.shift && ( tool === 'rect' || tool === 'ellipse' ) ) { var m = Math.max( Math.abs( b.x - a.x ), Math.abs( b.y - a.y ) ); b = new scope.Point( a.x + m * Math.sign( b.x - a.x || 1 ), a.y + m * Math.sign( b.y - a.y || 1 ) ); }
				var r = a.getDistance( b ), it;
				if ( tool === 'rect' ) { it = new scope.Path.Rectangle( new scope.Rectangle( a, b ) ); }
				else if ( tool === 'ellipse' ) { it = new scope.Path.Ellipse( new scope.Rectangle( a, b ) ); }
				else if ( tool === 'polygon' ) { it = new scope.Path.RegularPolygon( a, n, r ); it.rotate( b.subtract( a ).angle + 90, a ); }
				else { it = new scope.Path.Star( a, n, r, r * 0.45 ); it.rotate( b.subtract( a ).angle + 90, a ); }
				styleNew( it ); if ( ! it.fillColor && ! it.strokeColor ) { it.strokeColor = strokeIn.value; it.strokeWidth = 2; }
				drag.item = it;
			}
		};
		t0.onMouseUp = function () {
			if ( drag && drag.kind === 'shape' && drag.item ) { select( drag.item ); }
			if ( drag && drag.kind === 'shape' && ! drag.item ) { past.pop(); }
			drag = null;
		};
		t0.onKeyDown = function ( e ) {
			var k = e.key, mod = e.modifiers.control || e.modifiers.meta;
			if ( mod && k === 'z' ) { e.modifiers.shift ? redo() : undo(); return false; }
			if ( k === 'enter' || k === 'escape' ) { if ( penPath ) { finishPen( false ); return false; } if ( k === 'escape' ) { clearSel(); } return false; }
			if ( ( k === 'delete' || k === 'backspace' ) && sel.length ) { del(); return false; }
			if ( sel.length && /^(up|down|left|right)$/.test( k ) ) {
				var s = e.modifiers.shift ? 10 : 1, d = { up: [ 0, -s ], down: [ 0, s ], left: [ -s, 0 ], right: [ s, 0 ] }[ k ];
				snap(); sel.forEach( function ( i ) { i.position = i.position.add( d ); } ); return false;
			}
			if ( ! mod ) { var map = { v: 'select', a: 'points', p: 'pen', r: 'rect', e: 'ellipse', g: 'polygon', s: 'star' }; if ( map[ k ] ) { setTool( map[ k ] ); return false; } }
		};
		view.onDoubleClick = function ( e ) {
			if ( tool !== 'points' ) { return; }
			var hit = project.hitTest( e.point, { segments: true, tolerance: 6 } );
			if ( hit && hit.segment && hit.item.layer === art ) {
				snap();
				var s = hit.segment;
				if ( s.handleIn.isZero() && s.handleOut.isZero() ) { s.smooth(); } else { s.handleIn = null; s.handleOut = null; }
			}
		};
		t0.activate();

		function del() { snap(); sel.forEach( function ( i ) { i.remove(); } ); sel = []; }
		// past holds the drawing as it was before each change; undo puts the current one aside for redo.
		function undo() { finishPen(); if ( ! past.length ) { return; } future.push( art.exportJSON() ); restore( past.pop() ); }
		function redo() { if ( ! future.length ) { return; } past.push( art.exportJSON() ); restore( future.pop() ); }
		function bool( op ) {
			var items = sel.filter( function ( i ) { return i[ op ]; } );
			if ( items.length < 2 ) { hint.textContent = 'Select two or more shapes first (Shift+click), then choose ' + op + '.'; return; }
			snap();
			// Order by stacking: the bottom shape is the one the others are cut from / joined to.
			items.sort( function ( a, b ) { return a.index - b.index; } );
			var res = items[ 0 ];
			for ( var i = 1; i < items.length; i++ ) { var r = res[ op ]( items[ i ], { insert: false } ); if ( res !== items[ 0 ] ) { res.remove(); } res = r; }
			res.style = items[ 0 ].style;
			art.insertChild( items[ 0 ].index, res );
			items.forEach( function ( i ) { i.remove(); } );
			sel = []; select( res );
		}

		$w.on( 'click', 'button', function ( e ) {
			e.preventDefault();
			var t = this.getAttribute( 'data-tool' ), b = this.getAttribute( 'data-bool' ), a = this.getAttribute( 'data-act' );
			if ( t ) { setTool( t ); }
			else if ( b ) { bool( b ); }
			else if ( a === 'delete' ) { if ( sel.length ) { del(); } }
			else if ( a === 'undo' ) { undo(); }
			else if ( a === 'redo' ) { redo(); }
			else if ( a === 'forward' ) { if ( sel.length ) { snap(); sel.forEach( function ( i ) { if ( i.nextSibling ) { i.insertAbove( i.nextSibling ); } } ); } }
			else if ( a === 'backward' ) { if ( sel.length ) { snap(); sel.forEach( function ( i ) { if ( i.previousSibling ) { i.insertBelow( i.previousSibling ); } } ); } }
			else if ( a === 'cancel' ) { close(); }
			else if ( a === 'save' ) { finishPen(); var out = exportSvg(); close(); onSave( out ); }
			canvas.focus();
		} );

		function exportSvg() {
			clearSel();
			if ( ! art.children.length ) { return ''; }
			var b = art.strokeBounds;
			var svgEl = document.createElementNS( 'http://www.w3.org/2000/svg', 'svg' );
			svgEl.setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
			var r = function ( v ) { return Math.round( v * 100 ) / 100; };
			svgEl.setAttribute( 'viewBox', [ r( b.x ), r( b.y ), r( b.width ), r( b.height ) ].join( ' ' ) );
			// Keep each shape's own colours: write every item's style onto its own element (paper.js puts shared
			// styles on the wrapping group, which is not kept).
			art.children.forEach( function ( item ) {
				var n = item.exportSVG( { asString: false, precision: 2 } );
				if ( ! n ) { return; }
				var fill = item.fillColor ? item.fillColor.toCSS( true ) : 'none';
				n.setAttribute( 'fill', fill );
				if ( item.strokeColor && item.strokeWidth > 0 ) {
					n.setAttribute( 'stroke', item.strokeColor.toCSS( true ) );
					n.setAttribute( 'stroke-width', Math.round( item.strokeWidth * 100 ) / 100 );
					n.setAttribute( 'stroke-linejoin', 'round' );
					n.setAttribute( 'stroke-linecap', 'round' );
				} else { [ 'stroke', 'stroke-width', 'stroke-linejoin', 'stroke-linecap' ].forEach( function ( x ) { n.removeAttribute( x ); } ); }
				if ( /^\s*mix-blend-mode:\s*normal;?\s*$/.test( n.getAttribute( 'style' ) || '' ) ) { n.removeAttribute( 'style' ); }
				if ( item.fillRule === 'evenodd' || item.className === 'CompoundPath' ) { n.setAttribute( 'fill-rule', item.fillRule || 'evenodd' ); }
				svgEl.appendChild( n );
			} );
			// Drop paper.js' extra attributes.
			Array.prototype.slice.call( svgEl.querySelectorAll( '*' ) ).forEach( function ( n ) {
				[ 'font-family', 'font-weight', 'font-size', 'text-anchor', 'mix-blend-mode', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset' ].forEach( function ( a ) { n.removeAttribute( a ); } );
				if ( n.getAttribute( 'fill-rule' ) === 'nonzero' ) { n.removeAttribute( 'fill-rule' ); }
				if ( n.getAttribute( 'stroke' ) === 'none' ) { n.removeAttribute( 'stroke' ); n.removeAttribute( 'stroke-width' ); n.removeAttribute( 'stroke-linecap' ); n.removeAttribute( 'stroke-linejoin' ); }
			} );
			return new XMLSerializer().serializeToString( svgEl );
		}

		function onResize() { view.viewSize = new scope.Size( stage.clientWidth, stage.clientHeight ); }
		window.addEventListener( 'resize', onResize );
		function close() {
			window.removeEventListener( 'resize', onResize );
			scope.remove();
			wrap.remove();
			document.documentElement.classList.remove( 'fw-vsvg-open' );
		}

		setTool( art.children.length ? 'select' : 'pen' );
		canvas.focus();
		wrap._fwVsvg = { scope: scope, setTool: setTool, select: select, bool: bool, exportSvg: exportSvg, art: art };
	}

	/* ------------------------------------------------------------------ init */

	function initAll( root ) { $( root || document ).find( '[data-fw-vsvg]' ).each( function () { initControl( $( this ) ); } ); }
	if ( window.fwEvents ) { fwEvents.on( 'fw:options:init', function ( data ) { initAll( data.$elements ); } ); }
	$( function () { initAll(); } );
	window.fwVectorSvg = { open: function ( svg, onSave ) { return loadPaper().then( function ( P ) { openEditor( P, svg || '', onSave ); } ); } };
}( jQuery ) );
