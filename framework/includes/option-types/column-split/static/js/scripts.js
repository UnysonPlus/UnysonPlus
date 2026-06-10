/**
 * Column Split option type — drag the divider to set the LEFT/RIGHT share.
 * The hidden input stores the left pane's integer column span (of `denominator`).
 */
(function ($, fwEvents) {

	function gcd(a, b) {
		a = Math.abs(a); b = Math.abs(b);
		while (b) { var t = b; b = a % b; a = t; }
		return a || 1;
	}

	function fraction(n, d) {
		n = parseInt(n, 10);
		if (isNaN(n) || n <= 0 || !d) { return ''; }
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
			min   = parseInt(cfg.min, 10) || 1,
			max   = parseInt(cfg.max, 10) || (denom - 1),
			showFraction = !!cfg.show_fraction;

		var $track   = $root.find('.fw-cs-track'),
			$left    = $root.find('.fw-cs-pane-left'),
			$right   = $root.find('.fw-cs-pane-right'),
			$divider = $root.find('.fw-cs-divider'),
			$input   = $root.find('.fw-cs-input');

		function paint(v, notify) {
			v = Math.max(min, Math.min(max, Math.round(v)));
			$input.val(v);
			$left.css('flex-grow', v);
			$right.css('flex-grow', denom - v);
			$divider.attr('aria-valuenow', v);
			if (showFraction) {
				$left.find('.fw-cs-pane-frac').text(fraction(v, denom));
				$right.find('.fw-cs-pane-frac').text(fraction(denom - v, denom));
			}
			if (notify) { $input.trigger('change'); }
		}

		function valueFromX(clientX) {
			var rect = $track.get(0).getBoundingClientRect();
			if (!rect.width) { return parseInt($input.val(), 10) || min; }
			var ratio = (clientX - rect.left) / rect.width;
			return ratio * denom;
		}

		var dragging = false;

		function onMove(ev) {
			if (!dragging) { return; }
			var x = (ev.type.indexOf('touch') === 0)
				? (ev.originalEvent.touches[0] || ev.originalEvent.changedTouches[0]).clientX
				: ev.clientX;
			paint(valueFromX(x), false);
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

		// Click anywhere on the track (not the divider) to jump there.
		$track.on('click', function (e) {
			if ($(e.target).closest('.fw-cs-divider').length) { return; }
			paint(valueFromX(e.clientX), true);
		});

		// Keyboard: arrows nudge the divider.
		$divider.on('keydown', function (e) {
			var v = parseInt($input.val(), 10) || min;
			if (e.which === 37 || e.which === 40) { paint(v - 1, true); e.preventDefault(); }
			else if (e.which === 39 || e.which === 38) { paint(v + 1, true); e.preventDefault(); }
			else if (e.which === 36) { paint(min, true); e.preventDefault(); }
			else if (e.which === 35) { paint(max, true); e.preventDefault(); }
		});

		// Initial paint (no notify) from the stored value.
		paint(parseInt($input.val(), 10) || Math.round(denom / 2), false);
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-column-split').each(function () {
			initSplit($(this));
		});
	});

})(jQuery, fwEvents);
