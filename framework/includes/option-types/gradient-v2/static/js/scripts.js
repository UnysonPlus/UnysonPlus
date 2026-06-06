(function ($) {
	'use strict';

	var L10n = (typeof _fw_option_type_gradient_v2_localized !== 'undefined')
		? _fw_option_type_gradient_v2_localized.l10n
		: { min_stops: 'A gradient must have at least 2 color stops.' };

	function buildCSS(state) {
		var sorted = state.stops.slice().sort(function (a, b) {
			return a.position - b.position;
		});
		var parts = sorted.map(function (s) {
			return s.color + ' ' + s.position + '%';
		});
		if (state.type === 'radial') {
			return 'radial-gradient(circle, ' + parts.join(', ') + ')';
		}
		return 'linear-gradient(' + state.angle + 'deg, ' + parts.join(', ') + ')';
	}

	var STARTER_STOPS = [
		{ color: '#2A7B9B', position: 0 },
		{ color: '#EDDD53', position: 100 }
	];

	var HEX_RE  = /^#([a-f0-9]{3}|[a-f0-9]{6})$/i;
	var RGBA_RE = /^rgba?\(/i;

	// "to <side(s)>" keyword → angle (deg), matching CSS linear-gradient semantics.
	var DIRECTION_ANGLES = {
		'to top': 0, 'to right': 90, 'to bottom': 180, 'to left': 270,
		'to top right': 45, 'to right top': 45,
		'to bottom right': 135, 'to right bottom': 135,
		'to bottom left': 225, 'to left bottom': 225,
		'to top left': 315, 'to left top': 315
	};

	// Resolve any CSS color token to a form the saved value can store.
	// - valid 3/6-digit #hex or rgb()/rgba() → returned as-is.
	// - anything else (named, hsl(), 8-digit hex, …) → resolved via the browser
	//   to rgb()/rgba(). Returns null when the browser can't make sense of it.
	var _colorProbe = null;
	function normalizeColor(token) {
		if (token == null) { return null; }
		var c = String(token).trim();
		if (!c) { return null; }
		if (HEX_RE.test(c) || RGBA_RE.test(c)) { return c; }

		// DOM resolution: assign and read back the computed color. A value the
		// browser rejects leaves the probe's color unchanged, so we sentinel it.
		if (!_colorProbe) {
			_colorProbe = document.createElement('span');
			_colorProbe.style.display = 'none';
			(document.body || document.documentElement).appendChild(_colorProbe);
		}
		_colorProbe.style.color = '';
		_colorProbe.style.color = '#010203'; // sentinel: a value unlikely to be the input
		var before = _colorProbe.style.color;
		_colorProbe.style.color = c;
		if (_colorProbe.style.color === '' || _colorProbe.style.color === before) {
			// Either rejected outright, or it genuinely *is* the sentinel — re-test
			// against a second sentinel to disambiguate.
			_colorProbe.style.color = '#040506';
			before = _colorProbe.style.color;
			_colorProbe.style.color = c;
			if (_colorProbe.style.color === '' || _colorProbe.style.color === before) {
				return null;
			}
		}
		var resolved = window.getComputedStyle(_colorProbe).color; // -> "rgb(r, g, b)" / "rgba(...)"
		return (resolved && RGBA_RE.test(resolved)) ? resolved : null;
	}

	// Split a gradient's inner text on TOP-LEVEL commas only, so the commas inside
	// rgb()/rgba()/hsl() don't tear a stop apart.
	function splitTopLevel(str) {
		var out = [], depth = 0, buf = '';
		for (var i = 0; i < str.length; i++) {
			var ch = str[i];
			if (ch === '(') { depth++; }
			else if (ch === ')') { depth--; }
			if (ch === ',' && depth === 0) { out.push(buf); buf = ''; }
			else { buf += ch; }
		}
		if (buf.trim() !== '' || out.length) { out.push(buf); }
		return out.map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
	}

	// Pull an optional trailing "<n>%" off a stop segment. Returns {color, position|null}.
	function splitStopSegment(seg) {
		var m = seg.match(/^(.*\S)\s+(-?\d+(?:\.\d+)?)%$/);
		if (m) {
			return { color: m[1].trim(), position: parseFloat(m[2]) };
		}
		return { color: seg.trim(), position: null };
	}

	// Parse a CSS gradient string into {type, angle, stops:[{color,position}]} or null.
	// Supports linear/radial, deg + "to <side>" directions, optional positions
	// (auto-distributed), and any browser-resolvable color (auto-converted to rgb/rgba).
	function parseGradient(input) {
		if (input == null) { return null; }
		var str = String(input).trim();
		if (!str) { return null; }

		// Tolerate a pasted declaration: "background: linear-gradient(...);"
		str = str.replace(/^(?:background(?:-image)?\s*:\s*)/i, '').replace(/;+\s*$/, '').trim();

		var m = str.match(/^(linear|radial)-gradient\s*\(([\s\S]*)\)$/i);
		if (!m) { return null; }

		var type  = m[1].toLowerCase();
		var parts = splitTopLevel(m[2]);
		if (!parts.length) { return null; }

		var angle = 90;
		// The first segment is config (direction/shape) iff it carries no color.
		var first = parts[0];
		var firstHasColor = normalizeColor(splitStopSegment(first).color) !== null;
		if (!firstHasColor) {
			if (type === 'linear') {
				var deg = first.match(/^(-?\d+(?:\.\d+)?)deg$/i);
				if (deg) {
					angle = parseFloat(deg[1]);
				} else {
					var key = first.toLowerCase().replace(/\s+/g, ' ').trim();
					if (DIRECTION_ANGLES.hasOwnProperty(key)) { angle = DIRECTION_ANGLES[key]; }
				}
			}
			// linear angle captured (or default); radial shape/size/position ignored.
			parts = parts.slice(1);
		}
		if (angle < 0)   { angle = 0; }
		if (angle > 360) { angle = 360; }

		// Remaining segments → stops.
		var raw = [];
		for (var i = 0; i < parts.length; i++) {
			var seg = splitStopSegment(parts[i]);
			var color = normalizeColor(seg.color);
			if (!color) { return null; } // any unresolvable stop invalidates the whole string
			raw.push({ color: color, position: seg.position });
		}
		if (raw.length < 2) { return null; }

		// Auto-distribute missing positions (CSS-like): ends pin to 0/100, interior
		// gaps interpolate linearly between the nearest explicit neighbors.
		if (raw[0].position === null)              { raw[0].position = 0; }
		if (raw[raw.length - 1].position === null) { raw[raw.length - 1].position = 100; }
		var i2 = 0;
		while (i2 < raw.length) {
			if (raw[i2].position !== null) { i2++; continue; }
			var start = i2 - 1;            // last known
			var end = i2;
			while (end < raw.length && raw[end].position === null) { end++; }
			var p0 = raw[start].position;
			var p1 = raw[end].position;
			var span = end - start;
			for (var k = i2; k < end; k++) {
				raw[k].position = p0 + (p1 - p0) * (k - start) / span;
			}
			i2 = end;
		}

		var stops = raw.map(function (s) {
			var p = s.position;
			if (p < 0)   { p = 0; }
			if (p > 100) { p = 100; }
			// Keep tidy integers when the value is whole.
			return { color: s.color, position: (p % 1 === 0) ? p : Math.round(p * 100) / 100 };
		});

		return { type: type, angle: angle, stops: stops };
	}

	// Stable per-instance id for namespacing document-level handlers.
	var uniqCounter = 0;
	function uniq( $root ) {
		var id = $root.data('gv2Uid');
		if (!id) { id = ++uniqCounter; $root.data('gv2Uid', id); }
		return id;
	}

	function GradientV2($root) {
		this.$root   = $root;
		this.$json   = $root.find('.fw-option-type-gradient-v2-json');
		this.$output = $root.find('.gv2-output');
		this.$clear  = $root.find('.gv2-clear');
		this.$panel  = $root.find('.gv2-panel');
		this.$prev   = $root.find('.gv2-preview');
		this.$stops  = $root.find('.gv2-preview-stops');
		this.$list   = $root.find('.gv2-stops-list');
		this.$angleInput = $root.find('.gv2-angle-input');
		this.$angleWrap  = $root.find('.gv2-angle');
		this.$angleDot   = $root.find('.gv2-angle-knob-dot');

		try {
			this.state = JSON.parse(this.$json.val());
		} catch (e) {
			this.state = { type: 'linear', angle: 90, stops: [] };
		}
		if (!this.state || typeof this.state !== 'object') {
			this.state = { type: 'linear', angle: 90, stops: [] };
		}
		if (!Array.isArray(this.state.stops)) { this.state.stops = []; }

		// "empty" = no gradient. The panel still shows starter stops to edit, but
		// the saved value stays empty until the user actually interacts (seed()).
		this.empty = this.state.stops.length < 2;

		// Editable-output state: while the user is typing in the .gv2-output field we
		// must not clobber it from render(); lastValidCSS is what we revert to.
		this.editing = false;
		this.lastValidCSS = this.empty ? '' : buildCSS(this.state);
		// True when the typed live-path changed state but hasn't rebuilt the stop
		// rows yet, so commitOutput knows whether a full rebuild is actually needed.
		this.rowsStale = false;

		// True until the first paint completes. Guards against init-time events
		// (e.g. wpColorPicker/iris firing its change callback, or fw broadcasting a
		// change across inputs) auto-seeding a blank gradient — see seedIfEmpty().
		this.initializing = true;

		this.initColorPickers();
		this.bindEvents();
		this.render({ initial: true }); // paint without firing change (don't dirty the form on load)

		this.initializing = false;
	}

	// Promote an empty picker to a real gradient on first interaction. The panel
	// already displays the starter stops; this commits them to the state so edits
	// have something to mutate. Returns true if it seeded (caller may re-render).
	GradientV2.prototype.seedIfEmpty = function () {
		if (this.initializing) { return false; } // never seed from init-time events
		if (!this.empty) { return false; }
		this.empty = false;
		if (this.state.stops.length < 2) {
			// Adopt whatever the panel is currently showing, else the starter.
			this.state.stops = readStopsFromList(this.$list);
			if (this.state.stops.length < 2) {
				this.state.stops = STARTER_STOPS.map(function (s) { return { color: s.color, position: s.position }; });
				this.rebuildStopsList();
			}
		}
		return true;
	};

	// Read the current stop rows out of the panel DOM (used when seeding).
	function readStopsFromList($list) {
		var out = [];
		$list.find('.gv2-stop').each(function () {
			var color = $(this).find('.gv2-stop-color').val();
			var pos   = parseFloat($(this).find('.gv2-stop-position').val());
			if (color && !isNaN(pos)) { out.push({ color: color, position: pos }); }
		});
		return out;
	}

	GradientV2.prototype.initColorPickers = function () {
		var self = this;
		this.$list.find('.gv2-stop-color').each(function () {
			self._initOnePicker($(this));
		});
	};

	GradientV2.prototype._initOnePicker = function ($input) {
		var self = this;
		if ($input.data('gv2-picker-init')) { return; }
		$input.data('gv2-picker-init', true);

		$input.wpColorPicker({
			change: function (e, ui) {
				var color = ui.color.toString();
				var index = parseInt($input.closest('.gv2-stop').attr('data-index'), 10);
				self.updateStopColor(index, color);
			},
			clear: function () {
				var index = parseInt($input.closest('.gv2-stop').attr('data-index'), 10);
				self.updateStopColor(index, '');
			}
		});
	};

	GradientV2.prototype.bindEvents = function () {
		var self = this;

		// --- Open / close the dropdown panel ---
		// The caret toggles. The output is an editable text field now, so focusing
		// it opens the panel (without preventDefault, so the caret can be placed).
		this.$root.on('click', '.gv2-trigger-caret', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self.togglePanel(self.$panel.prop('hidden'));
		});
		this.$output.on('focus', function () {
			self.editing = true;
			self.togglePanel(true);
		});

		// --- Editable output: parse typed/pasted CSS back into the editor ---
		var liveTimer = null;
		this.$output.on('input', function () {
			if (liveTimer) { clearTimeout(liveTimer); }
			liveTimer = setTimeout(function () { self.onOutputInput(); }, 150);
		});
		this.$output.on('blur', function () {
			if (liveTimer) { clearTimeout(liveTimer); liveTimer = null; }
			self.editing = false;
			self.commitOutput();
		});
		this.$output.on('keydown', function (e) {
			if (e.key === 'Enter' || e.keyCode === 13 || e.key === 'Escape' || e.keyCode === 27) {
				e.preventDefault();
				if (self.$output[0]) { self.$output[0].blur(); } // native blur → commit + $root closes
			}
		});

		// --- Clear (× on the input, and "Clear gradient" in the panel) ---
		this.$root.on('click', '.gv2-clear, .gv2-panel-clear', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self.clearGradient();
			if ($(this).hasClass('gv2-panel-clear')) {
				self.togglePanel(false);
			}
		});

		// Outside click closes.
		$(document).on('mousedown.gv2-' + uniq(this.$root), function (e) {
			if (!self.$root[0].contains(e.target)) {
				self.togglePanel(false);
			}
		});
		// Esc closes.
		this.$root.on('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				self.togglePanel(false);
			}
		});

		this.$root.on('click', '.gv2-mode-btn', function (e) {
			e.preventDefault();
			self.seedIfEmpty();
			var mode = $(this).attr('data-mode');
			self.state.type = mode;
			self.$root.find('.gv2-mode-btn').removeClass('is-active');
			$(this).addClass('is-active');
			self.$angleWrap.toggle(mode === 'linear');
			self.render();
		});

		this.$angleInput.on('input change', function () {
			self.seedIfEmpty();
			var v = parseInt($(this).val(), 10);
			if (isNaN(v)) { v = 0; }
			if (v < 0)    { v = 0; }
			if (v > 360)  { v = 360; }
			self.state.angle = v;
			self.render();
		});

		this._bindAngleKnob();

		this.$root.on('input change', '.gv2-stop-position', function () {
			self.seedIfEmpty();
			var index = parseInt($(this).closest('.gv2-stop').attr('data-index'), 10);
			var v = parseFloat($(this).val());
			if (isNaN(v)) { v = 0; }
			if (v < 0)    { v = 0; }
			if (v > 100)  { v = 100; }
			if (self.state.stops[index]) { self.state.stops[index].position = v; }
			self.render({ skipList: true });
		});

		this.$root.on('click', '.gv2-stop-remove', function (e) {
			e.preventDefault();
			if (self.state.stops.length <= 2) {
				window.alert(L10n.min_stops);
				return;
			}
			var index = parseInt($(this).closest('.gv2-stop').attr('data-index'), 10);
			self.state.stops.splice(index, 1);
			self.rebuildStopsList();
			self.render();
		});

		this.$root.on('click', '.gv2-add-stop', function (e) {
			e.preventDefault();
			if (self.seedIfEmpty()) { self.render(); return; } // seeding already gives 2 stops
			var stops = self.state.stops.slice().sort(function (a, b) { return a.position - b.position; });
			// Insert a new stop midway in the largest gap.
			var bestGap = -1, bestPos = 50, bestColor = '#888888';
			for (var i = 0; i < stops.length - 1; i++) {
				var gap = stops[i + 1].position - stops[i].position;
				if (gap > bestGap) {
					bestGap   = gap;
					bestPos   = stops[i].position + gap / 2;
					bestColor = stops[i].color;
				}
			}
			self.state.stops.push({ color: bestColor, position: Math.round(bestPos) });
			self.rebuildStopsList();
			self.render();
		});

		// Click on preview bar adds a stop at clicked position.
		this.$prev.on('click', function (e) {
			if ($(e.target).closest('.gv2-preview-stop-marker').length) { return; }
			if (self.seedIfEmpty()) { self.render(); return; }
			var offset = $(this).offset();
			var width  = $(this).outerWidth();
			var pct    = Math.max(0, Math.min(100, Math.round(((e.pageX - offset.left) / width) * 100)));
			// Sample color at that position from the current gradient (use nearest stop).
			var nearest = self.state.stops[0];
			for (var i = 0; i < self.state.stops.length; i++) {
				if (Math.abs(self.state.stops[i].position - pct) < Math.abs(nearest.position - pct)) {
					nearest = self.state.stops[i];
				}
			}
			self.state.stops.push({ color: nearest.color, position: pct });
			self.rebuildStopsList();
			self.render();
		});
	};

	GradientV2.prototype._bindAngleKnob = function () {
		var self = this;
		var $knob = this.$root.find('.gv2-angle-knob');
		if (!$knob.length) { return; }

		function setFromEvent(e) {
			var off = $knob.offset();
			var w = $knob.outerWidth();
			var h = $knob.outerHeight();
			var cx = off.left + w / 2;
			var cy = off.top  + h / 2;
			var dx = e.pageX - cx;
			var dy = e.pageY - cy;
			// 0deg points up, increase clockwise — match CSS rotate semantics.
			var deg = Math.round(Math.atan2(dx, -dy) * 180 / Math.PI);
			if (deg < 0) { deg += 360; }
			self.state.angle = deg;
			self.$angleInput.val(deg);
			self.render();
		}

		$knob.on('mousedown', function (e) {
			e.preventDefault();
			setFromEvent(e);
			$(document).on('mousemove.gv2knob', setFromEvent);
			$(document).on('mouseup.gv2knob', function () {
				$(document).off('mousemove.gv2knob mouseup.gv2knob');
			});
		});
	};

	GradientV2.prototype.updateStopColor = function (index, color) {
		// wpColorPicker/iris fires this (debounced) during init too; ignore until the
		// first paint is done so it can't seed a blank gradient or dirty the form.
		if (this.initializing) { return; }
		// Touching a color picker counts as interaction — seed an empty picker.
		this.seedIfEmpty();
		if (!this.state.stops[index]) { return; }
		if (this.state.stops[index].color === color) { return; } // no real change (e.g. picker re-set)
		this.state.stops[index].color = color;
		this.$list
			.find('.gv2-stop[data-index="' + index + '"] .gv2-stop-swatch')
			.css('background', color);
		this.render({ skipList: true });
	};

	// --- Dropdown open/close ---
	GradientV2.prototype.togglePanel = function (open) {
		this.$panel.prop('hidden', !open);
		this.$output.attr('aria-expanded', open ? 'true' : 'false');
		this.$root.toggleClass('gv2-open', !!open);
		if (open) {
			// CodeMirror-style: refresh color pickers' layout when revealed.
			this.$list.find('.gv2-stop-color').trigger('gv2:refresh');
		}
	};

	// --- Clear back to "no gradient" ---
	GradientV2.prototype.clearGradient = function () {
		this.empty = true;
		this.state.stops = [];
		this.render();
	};

	// Reflect state into the panel's mode buttons / angle controls (used by the
	// typed-input live path, which sets state directly without clicking controls).
	GradientV2.prototype.syncControlsFromState = function () {
		var self = this;
		this.$root.find('.gv2-mode-btn').each(function () {
			$(this).toggleClass('is-active', $(this).attr('data-mode') === self.state.type);
		});
		this.$angleWrap.toggle(this.state.type === 'linear');
		this.$angleInput.val(this.state.angle);
	};

	// Live (debounced) parse while the user types in the output field. Updates the
	// preview / markers / mode / angle, but neither rebuilds the stop rows nor
	// overwrites the field (render() guards on this.editing). Commit happens on blur.
	GradientV2.prototype.onOutputInput = function () {
		var val = this.$output.val();
		if (!val || !val.trim()) {
			this.$output.removeClass('is-invalid');
			this.empty = true;
			this.state.stops = [];
			this.rowsStale = true;
			this.syncControlsFromState();
			this.render({ typed: true });
			return;
		}
		var parsed = parseGradient(val);
		if (!parsed) {
			this.$output.addClass('is-invalid'); // unparseable so far — apply nothing
			return;
		}
		this.$output.removeClass('is-invalid');
		this.empty = false;
		this.state = parsed;
		this.rowsStale = true;
		this.syncControlsFromState();
		this.render(); // not empty → renders from state; field left alone (editing)
	};

	// Commit on blur / Enter: empty → clear; valid → full rebuild; invalid → revert.
	GradientV2.prototype.commitOutput = function () {
		var val = this.$output.val();
		this.$output.removeClass('is-invalid');

		if (!val || !val.trim()) {
			this.clearGradient();
			return;
		}
		var parsed = parseGradient(val);
		if (parsed) {
			if (this.rowsStale) {
				this.applyParsed(parsed); // rebuilds rows + reformats field to canonical CSS
			} else {
				// No typed change since the last rebuild (e.g. focus → click away) —
				// just reformat the field to canonical, no picker churn.
				this.render();
			}
			return;
		}

		// Invalid & non-empty: restore EVERYTHING (field, rows, preview) to the last
		// valid gradient, or blank if there wasn't one. Reparsing the canonical
		// lastValidCSS keeps state/rows/output consistent regardless of live edits.
		var prev = this.lastValidCSS ? parseGradient(this.lastValidCSS) : null;
		if (prev) {
			this.applyParsed(prev);
		} else {
			this.clearGradient();
		}
	};

	// Adopt a parsed gradient as the new value: full rebuild so each stop gets a
	// freshly-initialized color picker showing the right swatch.
	GradientV2.prototype.applyParsed = function (parsed) {
		this.empty = false;
		this.state = { type: parsed.type, angle: parsed.angle, stops: parsed.stops.slice() };
		this.syncControlsFromState();
		this.rebuildStopsList();
		this.render();
	};

	GradientV2.prototype.rebuildStopsList = function () {
		var self = this;
		this.rowsStale = false; // rows are about to match state again
		this.$list.find('.gv2-stop').each(function () {
			var $input = $(this).find('.gv2-stop-color');
			if ($input.data('gv2-picker-init')) {
				// wpColorPicker wraps the input — destroy its wrapper before remove.
				$input.closest('.wp-picker-container').remove();
			} else {
				$(this).remove();
			}
		});
		this.$list.empty();
		this.state.stops.forEach(function (stop, i) {
			var $row = $(
				'<div class="gv2-stop" data-index="' + i + '">' +
					'<span class="gv2-stop-swatch"></span>' +
					'<input type="text" class="gv2-stop-color fw-option-type-rgba-color-picker" data-alpha="true" />' +
					'<input type="number" class="gv2-stop-position" min="0" max="100" step="1" />' +
					'<span class="gv2-stop-unit">%</span>' +
					'<button type="button" class="gv2-stop-remove" title="Remove">&times;</button>' +
				'</div>'
			);
			$row.find('.gv2-stop-swatch').css('background', stop.color);
			$row.find('.gv2-stop-color').val(stop.color);
			$row.find('.gv2-stop-position').val(stop.position);
			self.$list.append($row);
			self._initOnePicker($row.find('.gv2-stop-color'));
		});
	};

	GradientV2.prototype.render = function (opts) {
		opts = opts || {};
		var self = this;

		// What the panel's preview bar + markers should reflect. When empty we
		// still show the panel's starter stops (read from the DOM list) so the
		// editor is usable, but the SAVED value remains empty. Exception: the typed
		// live-clear path passes {typed:true} so the preview goes blank, not stale.
		var displayStops = this.empty
			? (opts.typed ? [] : readStopsFromList(this.$list))
			: this.state.stops;
		var displayState = { type: this.state.type, angle: this.state.angle, stops: displayStops };

		this.$prev.css('background', displayStops.length >= 2 ? buildCSS(displayState) : '');

		// Re-render the draggable markers on top of the preview.
		this.$stops.empty();
		displayStops.forEach(function (stop, i) {
			var $m = $('<span class="gv2-preview-stop-marker"></span>');
			$m.css({ left: stop.position + '%', background: stop.color });
			$m.attr('data-index', i);
			self.$stops.append($m);
			self._makeMarkerDraggable($m);
		});

		// Keep stops list data-index attrs in sync with array order.
		if (!opts.skipList) {
			this.$list.find('.gv2-stop').each(function (i) {
				$(this).attr('data-index', i);
			});
		}

		this.$angleDot.css('transform', 'rotate(' + this.state.angle + 'deg)');

		// --- The output field + clear button + saved value ---
		// While the user is typing in the field (this.editing), never overwrite it.
		var json;
		if (this.empty) {
			// Saved value: explicit empty (no stops) = "no gradient".
			if (!this.editing) { this.$output.val(''); }
			this.$clear.prop('hidden', true);
			json = JSON.stringify({ type: this.state.type, angle: this.state.angle, stops: [] });
		} else {
			this.lastValidCSS = buildCSS(this.state);
			if (!this.editing) { this.$output.val(this.lastValidCSS); }
			this.$clear.prop('hidden', false);
			json = JSON.stringify(this.state);
		}
		this.$json.val(json);
		if (!opts.initial) { this.$json.trigger('change'); } // don't dirty the form on the first paint
	};

	GradientV2.prototype._makeMarkerDraggable = function ($marker) {
		var self = this;
		$marker.on('mousedown', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var index = parseInt($marker.attr('data-index'), 10);
			var $bar  = self.$prev;

			function move(ev) {
				var offset = $bar.offset();
				var width  = $bar.outerWidth();
				var pct    = Math.max(0, Math.min(100, Math.round(((ev.pageX - offset.left) / width) * 100)));
				self.seedIfEmpty();
				if (!self.state.stops[index]) { return; }
				self.state.stops[index].position = pct;
				$marker.css('left', pct + '%');
				self.$list
					.find('.gv2-stop[data-index="' + index + '"] .gv2-stop-position')
					.val(pct);
				// Keep the preview, the read-out text input, and the saved value all in
				// sync live (can't call render() here — it rebuilds the marker mid-drag).
				var css = buildCSS(self.state);
				self.$prev.css('background', css);
				self.$output.val(css);
				self.lastValidCSS = css;
				self.$json.val(JSON.stringify(self.state)).trigger('change');
			}

			$(document).on('mousemove.gv2marker', move);
			$(document).on('mouseup.gv2marker', function () {
				$(document).off('mousemove.gv2marker mouseup.gv2marker');
			});
		});
	};

	fwEvents.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option.fw-option-type-gradient-v2:not(.gv2-initialized)')
			.each(function () { new GradientV2($(this)); })
			.addClass('gv2-initialized');
	});

})(jQuery);
