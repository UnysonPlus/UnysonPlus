/**
 * Slider + Range Slider — powered by noUiSlider (vanilla, no jQuery/moment).
 * Replaces the legacy jQuery Ion.RangeSlider. One shared adapter drives both
 * the single `slider` and the double `range-slider` option types.
 *
 * The PHP still emits the Ion-shaped `data-fw-irs-options` config and a
 * `.fw-irs-range-slider` <input> whose value carries the saved data:
 *   - slider:        a single number
 *   - range-slider:  "from;to"
 * This adapter reads that config, renders noUiSlider, and writes the value back
 * into that input on every change — so the PHP render/save contract is unchanged
 * (including the `values` map and the `fw_fraction_of` fraction labels).
 */
(function ($, fwEvents) {

	function gcd(a, b) { a = Math.abs(a); b = Math.abs(b); while (b) { var t = b; b = a % b; a = t; } return a || 1; }

	// Lowest-form fraction of value/denom, e.g. (6,12) -> "1/2".
	function fractionLabel(value, denom) {
		var n = parseInt(value, 10);
		if (isNaN(n) || n <= 0 || !denom) { return String(value); }
		if (n >= denom) { return '1'; }
		var g = gcd(n, denom);
		return (n / g) + '/' + (denom / g);
	}

	function decimalsOf(step) {
		step = parseFloat(step);
		if (isNaN(step)) { return 0; }
		var s = String(step), i = s.indexOf('.');
		return i < 0 ? 0 : (s.length - i - 1);
	}

	function initOne(el, isRange) {
		var $wrap = $(el);
		if ($wrap.hasClass('initialized') || typeof window.noUiSlider === 'undefined') { return; }

		var opts = {};
		try { opts = JSON.parse($wrap.attr('data-fw-irs-options') || '{}'); } catch (e) { opts = {}; }

		var $input = $wrap.find('.fw-irs-range-slider');
		if (!$input.length) { $wrap.addClass('initialized'); return; }

		var values = (opts.values && opts.values.length) ? opts.values : null;
		var denom  = opts.fw_fraction_of ? parseInt(opts.fw_fraction_of, 10) : 0;
		var step   = values ? 1 : (parseFloat(opts.step) || 1);
		var dec    = values ? 0 : decimalsOf(step);
		var range  = values
			? { min: 0, max: Math.max(0, values.length - 1) }
			: { min: parseFloat(opts.min) || 0, max: (opts.max != null ? parseFloat(opts.max) : 100) };

		var from = (opts.from != null && opts.from !== '') ? parseFloat(opts.from) : range.min;
		var to   = (opts.to   != null && opts.to   !== '') ? parseFloat(opts.to)   : range.max;
		var start = isRange ? [from, to] : [from];

		// Map a raw slider number to its DISPLAYED label (tooltip / pips).
		function display(rawNum) {
			var v = values ? values[Math.round(rawNum)] : rawNum;
			if (denom) { return fractionLabel(v, denom); }
			var num = parseFloat(v);
			if (isNaN(num)) { return String(v); }
			return dec ? String(num.toFixed(dec)) : String(Math.round(num));
		}
		// Map a raw slider number to its STORED value (goes into the input).
		function stored(rawNum) {
			if (values) { return values[Math.round(rawNum)]; }
			return dec ? parseFloat(rawNum).toFixed(dec) : String(Math.round(parseFloat(rawNum)));
		}

		var tip = { to: function (v) { return display(v); }, from: function (s) { return parseFloat(s); } };

		var config = {
			start: start,
			connect: isRange ? true : [true, false],
			step: step,
			range: range,
			snap: !!opts.grid_snap,
			tooltips: isRange ? [tip, tip] : [tip]
		};

		if (opts.grid !== false) {
			config.pips = {
				mode: 'positions',
				values: [0, 25, 50, 75, 100],
				density: 10,
				format: { to: function (v) { return display(v); } }
			};
		}

		// Render noUiSlider next to the (now hidden) value input.
		var sliderEl = document.createElement('div');
		sliderEl.className = 'fw-noui';
		$input.addClass('fw-noui-value-input').after(sliderEl);

		window.noUiSlider.create(sliderEl, config);
		$wrap.data('noui', sliderEl.noUiSlider);

		function writeInput(vals, handle, unencoded) {
			if (isRange) {
				$input.val(stored(unencoded[0]) + ';' + stored(unencoded[1]));
			} else {
				$input.val(stored(unencoded[0]));
			}
		}

		sliderEl.noUiSlider.on('update', writeInput);

		// Announce on release (parity with Ion's onFinish / throttled change).
		sliderEl.noUiSlider.on('change', function (vals, handle, unencoded) {
			var payload = isRange
				? { from: stored(unencoded[0]), to: stored(unencoded[1]) }
				: stored(unencoded[0]);
			if (window.fw && fw.options && fw.options.trigger && fw.options.trigger.changeForEl) {
				fw.options.trigger.changeForEl($wrap[0], { value: payload });
			}
			$input.trigger('change');
		});

		$wrap.addClass('initialized');
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-slider:not(.initialized)').each(function () { initOne(this, false); });
		data.$elements.find('.fw-option-type-range-slider:not(.initialized)').each(function () { initOne(this, true); });
	});

	// range-slider registers its value getter (from;to -> {from,to}); slider's
	// value is collected straight from its input.
	function getRangeValue(el) {
		var parts = ($(el).find('.fw-irs-range-slider').val() || '').split(';');
		return { from: parts[0], to: parts[1] };
	}
	fw.options.register('range-slider', {
		startListeningForChanges: $.noop,
		getValue: function (optionDescriptor) {
			return { value: getRangeValue(optionDescriptor.el), optionDescriptor: optionDescriptor };
		}
	});

})(jQuery, fwEvents);
