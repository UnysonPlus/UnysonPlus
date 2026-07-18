(function ($, fwEvents) {
	var defaults = {
		grid: true
	};

	function gcd(a, b) {
		a = Math.abs(a);
		b = Math.abs(b);
		while (b) { var t = b; b = a % b; a = t; }
		return a || 1;
	}

	// Lowest-form fraction of value/denominator, e.g. (6, 12) -> "1/2", (4, 12) -> "1/3".
	function fractionLabel(value, denom) {
		var n = parseInt(value, 10);
		if (isNaN(n) || n <= 0 || !denom) { return String(value); }
		if (n >= denom) { return '1'; }
		var g = gcd(n, denom);
		return (n / g) + '/' + (denom / g);
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-slider:not(.initialized)').each(function () {
			var $opt = $(this);
			var options = JSON.parse($opt.attr('data-fw-irs-options'));

			// Optional display-only formatter: when `fw_fraction_of` is set, render the
			// value as the lowest-form fraction of value/denominator. The STORED value
			// stays the integer — this only changes the labels. Used by Image Content's
			// "Image / Content Split" slider (6 -> "1/2", 4 -> "1/3", 3 -> "1/4", ...).
			if (options.fw_fraction_of) {
				var denom = parseInt(options.fw_fraction_of, 10);
				options.prettify = function (value) { return fractionLabel(value, denom); };
			}

			// Announce value changes through the framework's reactive-options API so live consumers
			// (e.g. an in-modal preview) can react — the same mechanism containers/multi-picker use.
			// Fires on RELEASE (onFinish), not every drag frame. Purely additive: it only broadcasts,
			// nothing here depends on a listener existing. Defensive fallback mirrors easing-picker.
			var announce = function (irs) {
				if (window.fw && fw.options && fw.options.trigger && fw.options.trigger.changeForEl) {
					fw.options.trigger.changeForEl($opt[0], { value: irs.from });
				} else {
					$opt.find('.fw-irs-range-slider').trigger('change');
				}
			};
			var prevFinish = options.onFinish;
			options.onFinish = function (irs) {
				if (typeof prevFinish === 'function') { prevFinish(irs); }
				announce(irs);
			};

			var slider = $opt.find('.fw-irs-range-slider').ionRangeSlider(_.defaults(options, defaults));
		}).addClass('initialized');
	});

})(jQuery, fwEvents);
