/**
 * Split Slider option type — an N-pane, 100%-rule width control.
 * The hidden input stores a JSON array of segments: [{w:Int, name:String}, …]
 * whose widths always sum to 100. An EMPTY input = AUTO (equal columns); a
 * "Reset to auto" button returns to that state.
 */
(function ($, fwEvents) {

	function equalState(n) {
		var state = [], each = Math.floor(100 / n), i;
		for (i = 0; i < n; i++) { state.push({ w: each, name: '' }); }
		state[0].w += 100 - each * n;
		return state;
	}

	function gcd(a, b) { return b ? gcd(b, a % b) : a; }

	// Pane label: a reduced N/denominator fraction in grid mode (e.g. 6/12 → 1/2),
	// otherwise a rounded percentage. Mirrors the PHP view exactly.
	function paneLabel(w, cfg) {
		if (cfg && cfg.denominator > 0) {
			var u = Math.max(1, Math.round(w / 100 * cfg.denominator));
			var g = gcd(u, cfg.denominator) || 1;
			return (u / g) + '/' + (cfg.denominator / g);
		}
		return Math.round(w) + '%';
	}

	function normalizeTo100(state, minw, denom) {
		var n = state.length, sum = 0, i;
		for (i = 0; i < n; i++) { sum += Math.max(0, state[i].w); }
		if (sum <= 0) {
			var each = 100 / n;
			for (i = 0; i < n; i++) { state[i].w = each; }
			sum = 100;
		}
		// Grid mode: distribute `denom` whole units (largest-remainder) → exact
		// percentages, so equal splits stay perfectly equal. Mirrors the PHP normalize.
		if (denom > 0) {
			var floor = [], rem = [], ftot = 0;
			for (i = 0; i < n; i++) {
				var e = Math.max(0, state[i].w) / sum * denom;
				var f = Math.max(1, Math.floor(e));
				floor.push(f); rem.push(e - Math.floor(e)); ftot += f;
			}
			var need = Math.round(denom - ftot);
			if (need > 0) {
				var ord = rem.map(function (r, idx) { return { r: r, idx: idx }; }).sort(function (a, b) { return b.r - a.r; });
				for (var k = 0; k < need; k++) { floor[ord[k % n].idx]++; }
			} else if (need < 0) {
				for (var j = 0; j < -need; j++) {
					var bi2 = 0, bv2 = -1;
					for (i = 0; i < n; i++) { if (floor[i] > bv2) { bv2 = floor[i]; bi2 = i; } }
					if (floor[bi2] > 1) { floor[bi2]--; }
				}
			}
			for (i = 0; i < n; i++) { state[i].w = floor[i] / denom * 100; }
			return;
		}
		for (i = 0; i < n; i++) { state[i].w = Math.max(minw, Math.round(state[i].w)); }
		var t = 0; for (i = 0; i < n; i++) { t += state[i].w; }
		var diff = 100 - t;
		if (diff !== 0) {
			var bi = 0, bv = -1;
			for (i = 0; i < n; i++) { if (state[i].w > bv) { bv = state[i].w; bi = i; } }
			state[bi].w = Math.max(minw, state[bi].w + diff);
		}
	}

	function renderTrack($root, state, cfg) {
		var $track = $root.find('.fw-ss-track').empty();
		state.forEach(function (seg, i) {
			if (i > 0) {
				$track.append(
					'<div class="fw-ss-divider" tabindex="0" role="slider" aria-valuemin="' + cfg.min_width +
					'" aria-valuemax="100" aria-valuenow="' + seg.w + '" data-i="' + i + '"><span class="fw-ss-grip"></span></div>'
				);
			}
			var $pane = $('<div class="fw-ss-pane"></div>').css('flex-grow', seg.w).attr('data-i', i);
			if (cfg.allow_names) {
				$('<input type="text" class="fw-ss-name">').val(seg.name || '').attr('placeholder', i + 1).appendTo($pane);
			} else {
				$('<span class="fw-ss-pane-label"></span>').text(seg.name || (i + 1)).appendTo($pane);
			}
			$('<span class="fw-ss-pane-pct"></span>').text(paneLabel(seg.w, cfg)).appendTo($pane);
			$track.append($pane);
		});
		$root.find('.fw-ss-count').text(state.length);
		$root.find('.fw-ss-remove').prop('disabled', state.length <= cfg.min);
		$root.find('.fw-ss-add').prop('disabled', state.length >= cfg.max);
	}

	function applySizes($root, state, cfg) {
		$root.find('.fw-ss-track .fw-ss-pane').each(function (i) {
			if (!state[i]) { return; }
			$(this).css('flex-grow', state[i].w).find('.fw-ss-pane-pct').text(paneLabel(state[i].w, cfg || {}));
		});
		$root.find('.fw-ss-track .fw-ss-divider').each(function (i) {
			if (state[i + 1]) { $(this).attr('aria-valuenow', state[i + 1].w); }
		});
	}

	function init($root) {
		if ($root.data('fwSsInit')) { return; }
		$root.data('fwSsInit', true);

		var cfg = {};
		try { cfg = JSON.parse($root.attr('data-fw-split-slider') || '{}'); } catch (e) { cfg = {}; }
		cfg.min = parseInt(cfg.min, 10) || 1;
		cfg.max = parseInt(cfg.max, 10) || 5;
		cfg.step = parseInt(cfg.step, 10) || 5;
		cfg.min_width = parseInt(cfg.min_width, 10) || 10;
		cfg.allow_names = !!cfg.allow_names;
		cfg.auto_count = parseInt(cfg.auto_count, 10) || Math.min(cfg.max, 3);
		cfg.denominator = parseInt(cfg.denominator, 10) || 0;

		var raw = ($root.find('.fw-ss-input').val() || '').trim();
		var auto = !raw;
		var state;
		if (auto) {
			state = equalState(Math.max(cfg.min, Math.min(cfg.max, cfg.auto_count)));
		} else {
			try { state = JSON.parse(raw); } catch (e) { state = []; }
			if (!state.length) { state = equalState(Math.max(cfg.min, Math.min(cfg.max, cfg.auto_count))); auto = true; }
			state = state.map(function (s) { return { w: parseFloat(s.w) || 0, name: (s.name || '').toString() }; });
			normalizeTo100(state, cfg.min_width, cfg.denominator);
		}

		function setAutoUI() {
			$root.toggleClass('fw-ss-is-auto', auto);
			$root.find('.fw-ss-hint').text(auto ? 'equal columns' : 'custom widths');
		}
		function commit(notify) {
			$root.find('.fw-ss-input').val(auto ? '' : JSON.stringify(state));
			if (notify) { $root.find('.fw-ss-input').trigger('change'); }
		}
		function activate() { if (auto) { auto = false; setAutoUI(); } }

		renderTrack($root, state, cfg);
		setAutoUI();
		commit(false);

		var $track = $root.find('.fw-ss-track');
		var dragging = null;

		function onMove(ev) {
			if (!dragging) { return; }
			var x = (ev.type.indexOf('touch') === 0)
				? (ev.originalEvent.touches[0] || ev.originalEvent.changedTouches[0]).clientX
				: ev.clientX;
			var rect = dragging.rect;
			if (!rect.width) { return; }
			var pos = Math.max(0, Math.min(100, (x - rect.left) / rect.width * 100));
			var i = dragging.i, k, before = 0;
			for (k = 0; k < i - 1; k++) { before += state[k].w; }
			var pair = state[i - 1].w + state[i].w;
			if (cfg.denominator > 0) {
				// Snap to the grid: work in whole grid units (e.g. twelfths).
				var unit = 100 / cfg.denominator;
				var pairU = Math.round(pair / unit);
				var leftU = Math.max(1, Math.min(pairU - 1, Math.round((pos - before) / unit)));
				state[i - 1].w = leftU * unit;
				state[i].w = (pairU - leftU) * unit;
			} else {
				var left = Math.round((pos - before) / cfg.step) * cfg.step;
				left = Math.max(cfg.min_width, Math.min(pair - cfg.min_width, left));
				state[i - 1].w = left;
				state[i].w = pair - left;
			}
			applySizes($root, state, cfg);
			commit(false);
		}
		function onUp() {
			if (!dragging) { return; }
			dragging = null;
			$root.removeClass('fw-ss-dragging');
			$(document).off('.fwss');
			commit(true);
		}

		$root.on('mousedown touchstart', '.fw-ss-divider', function (e) {
			e.preventDefault();
			activate();
			dragging = { i: parseInt($(this).attr('data-i'), 10), rect: $track.get(0).getBoundingClientRect() };
			$root.addClass('fw-ss-dragging');
			$(document).on('mousemove.fwss touchmove.fwss', onMove).on('mouseup.fwss touchend.fwss touchcancel.fwss', onUp);
		});

		$root.on('click', '.fw-ss-add', function () {
			if (state.length >= cfg.max) { return; }
			activate();
			var bi = 0, bv = -1;
			state.forEach(function (s, i) { if (s.w > bv) { bv = s.w; bi = i; } });
			var half = state[bi].w / 2;
			state[bi].w = half;
			state.splice(bi + 1, 0, { w: half, name: '' });
			normalizeTo100(state, cfg.min_width, cfg.denominator);
			renderTrack($root, state, cfg);
			commit(true);
		});

		$root.on('click', '.fw-ss-remove', function () {
			if (state.length <= cfg.min) { return; }
			activate();
			var last = state.pop();
			state[state.length - 1].w += last.w;
			normalizeTo100(state, cfg.min_width, cfg.denominator);
			renderTrack($root, state, cfg);
			commit(true);
		});

		$root.on('click', '.fw-ss-reset', function () {
			// Restore the configured default widths (the preset's widths); fall
			// back to equal columns only when no default is configured.
			if (cfg.default && cfg.default.length) {
				auto  = false;
				state = cfg.default.map(function (s) { return { w: parseFloat(s.w) || 0, name: (s.name || '').toString() }; });
				normalizeTo100(state, cfg.min_width, cfg.denominator);
			} else {
				auto  = true;
				state = equalState(Math.max(cfg.min, Math.min(cfg.max, cfg.auto_count)));
			}
			renderTrack($root, state, cfg);
			setAutoUI();
			commit(true);
		});

		$root.on('input', '.fw-ss-name', function () {
			var i = parseInt($(this).closest('.fw-ss-pane').attr('data-i'), 10);
			if (state[i]) { activate(); state[i].name = $(this).val(); commit(true); }
		});

		$root.on('keydown', '.fw-ss-divider', function (e) {
			var i = parseInt($(this).attr('data-i'), 10);
			if (!state[i]) { return; }
			var pair = state[i - 1].w + state[i].w, v = state[i - 1].w;
			var kstep = (cfg.denominator > 0) ? (100 / cfg.denominator) : cfg.step;
			if (e.which === 37 || e.which === 40) { v -= kstep; }
			else if (e.which === 39 || e.which === 38) { v += kstep; }
			else { return; }
			e.preventDefault();
			activate();
			if (cfg.denominator > 0) {
				var unit = 100 / cfg.denominator;
				var pairU = Math.round(pair / unit);
				var leftU = Math.max(1, Math.min(pairU - 1, Math.round(v / unit)));
				state[i - 1].w = leftU * unit;
				state[i].w = (pairU - leftU) * unit;
			} else {
				v = Math.max(cfg.min_width, Math.min(pair - cfg.min_width, v));
				state[i - 1].w = v;
				state[i].w = pair - v;
			}
			applySizes($root, state, cfg);
			commit(true);
		});
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-split-slider').each(function () {
			init($(this));
		});
	});

})(jQuery, fwEvents);
