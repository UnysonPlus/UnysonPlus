/**
 * Column Split option type — drag the divider to set the LEFT/RIGHT share.
 * The hidden input stores the LEFT pane's fraction as a lowest-terms "n/d" string
 * (e.g. "1/3", "2/5"). A legacy bare integer (left span out of `denominator`) is
 * still understood on load and re-saved as "n/d". The divider snaps to the ordered
 * `fractions` set from the option config (default = twelfths), so a set that includes
 * fifths lets it stop on 1/5, 2/5, 3/5, 4/5.
 */
(function ($, fwEvents) {

	function gcd(a, b) {
		a = Math.abs(a); b = Math.abs(b);
		while (b) { var t = b; b = a % b; a = t; }
		return a || 1;
	}

	// Lowest-terms "n/d" (or "1" when the pane fills the row).
	function fracStr(n, d) {
		if (!d || n <= 0) { return ''; }
		if (n >= d) { return '1'; }
		var g = gcd(n, d);
		return (n / g) + '/' + (d / g);
	}

	function initSplit($root) {
		if ($root.data('fwCsInit')) { return; }
		$root.data('fwCsInit', true);

		var cfg = {};
		try { cfg = JSON.parse($root.attr('data-fw-column-split') || '{}'); } catch (e) { cfg = {}; }

		var denom = parseInt(cfg.denominator, 10) || 12,
			showFraction = !!cfg.show_fraction;

		// Ordered allowed fractions [{n,d,val,key}]; fall back to twelfths.
		var list = (cfg.fractions && cfg.fractions.length)
			? cfg.fractions
			: ['1/12', '2/12', '3/12', '4/12', '5/12', '6/12', '7/12', '8/12', '9/12', '10/12', '11/12'];
		var fracs = [];
		list.forEach(function (s) {
			var p = String(s).split('/'), n = parseInt(p[0], 10), d = parseInt(p[1], 10);
			if (n > 0 && d > 0 && n < d) {
				var g = gcd(n, d); n = n / g; d = d / g;
				fracs.push({ n: n, d: d, val: n / d, key: n + '/' + d });
			}
		});
		fracs.sort(function (a, b) { return a.val - b.val; });
		var seen = {}, uniq = [];
		fracs.forEach(function (f) { if (!seen[f.key]) { seen[f.key] = 1; uniq.push(f); } });
		fracs = uniq;
		if (!fracs.length) { fracs = [{ n: 1, d: 2, val: 0.5, key: '1/2' }]; }

		var $track   = $root.find('.fw-cs-track'),
			$left    = $root.find('.fw-cs-pane-left'),
			$right   = $root.find('.fw-cs-pane-right'),
			$divider = $root.find('.fw-cs-divider'),
			$input   = $root.find('.fw-cs-input');

		// Parse the stored value → {n,d} lowest terms (tolerates a legacy int).
		function parseVal(v) {
			v = String(v);
			if (v.indexOf('/') > -1) {
				var p = v.split('/'), n = parseInt(p[0], 10), d = parseInt(p[1], 10);
				if (n > 0 && d > 0 && n < d) { var g = gcd(n, d); return { n: n / g, d: d / g }; }
			}
			var iv = parseInt(v, 10);
			if (iv > 0 && iv < denom) { var g2 = gcd(iv, denom); return { n: iv / g2, d: denom / g2 }; }
			return null;
		}

		function nearest(ratio) {
			var best = fracs[0], bd = Infinity;
			fracs.forEach(function (f) { var dd = Math.abs(f.val - ratio); if (dd < bd) { bd = dd; best = f; } });
			return best;
		}
		function indexOfKey(key) {
			for (var i = 0; i < fracs.length; i++) { if (fracs[i].key === key) { return i; } }
			return -1;
		}
		// Snap any {n,d} to a member of the allowed set.
		function toAllowed(r) {
			if (!r) { return nearest(0.5); }
			var idx = indexOfKey(r.n + '/' + r.d);
			return idx >= 0 ? fracs[idx] : nearest(r.n / r.d);
		}

		function paint(f, notify) {
			$input.val(f.key);
			$left.css('flex-grow', f.n);
			$right.css('flex-grow', f.d - f.n);
			$divider.attr('aria-valuenow', f.key);
			if (showFraction) {
				$left.find('.fw-cs-pane-frac').text(fracStr(f.n, f.d));
				$right.find('.fw-cs-pane-frac').text(fracStr(f.d - f.n, f.d));
			}
			if (notify) { $input.trigger('change'); }
		}

		function ratioFromX(clientX) {
			var rect = $track.get(0).getBoundingClientRect();
			if (!rect.width) { return null; }
			return (clientX - rect.left) / rect.width;
		}

		var dragging = false;

		function onMove(ev) {
			if (!dragging) { return; }
			var x = (ev.type.indexOf('touch') === 0)
				? (ev.originalEvent.touches[0] || ev.originalEvent.changedTouches[0]).clientX
				: ev.clientX;
			var r = ratioFromX(x);
			if (r != null) { paint(nearest(r), false); }
		}

		function onUp() {
			if (!dragging) { return; }
			dragging = false;
			$(document).off('.fwcs');
			$root.removeClass('fw-cs-dragging');
			$input.trigger('change');
		}

		$divider.on('mousedown touchstart', function (e) {
			e.preventDefault();
			dragging = true;
			$root.addClass('fw-cs-dragging');
			$(document)
				.on('mousemove.fwcs touchmove.fwcs', onMove)
				.on('mouseup.fwcs touchend.fwcs touchcancel.fwcs', onUp);
		});

		// Click anywhere on the track (not the divider) to jump to the nearest stop.
		$track.on('click', function (e) {
			if ($(e.target).closest('.fw-cs-divider').length) { return; }
			var r = ratioFromX(e.clientX);
			if (r != null) { paint(nearest(r), true); }
		});

		// Keyboard: arrows step through the allowed fractions.
		$divider.on('keydown', function (e) {
			var cur = toAllowed(parseVal($input.val()));
			var idx = indexOfKey(cur.key);
			if (idx < 0) { idx = 0; }
			if (e.which === 37 || e.which === 40) { if (idx > 0) { paint(fracs[idx - 1], true); } e.preventDefault(); }
			else if (e.which === 39 || e.which === 38) { if (idx < fracs.length - 1) { paint(fracs[idx + 1], true); } e.preventDefault(); }
			else if (e.which === 36) { paint(fracs[0], true); e.preventDefault(); }
			else if (e.which === 35) { paint(fracs[fracs.length - 1], true); e.preventDefault(); }
		});

		// Initial paint (no notify) from the stored value, snapped to an allowed stop.
		paint(toAllowed(parseVal($input.val())), false);
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-column-split').each(function () {
			initSplit($(this));
		});
	});

})(jQuery, fwEvents);
