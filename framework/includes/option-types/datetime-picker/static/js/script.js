/**
 * Datetime Picker option type — powered by Air Datepicker (vanilla, no jQuery/moment).
 * Replaces the legacy xdsoft jquery.datetimepicker + moment.js stack.
 * The stored value format is preserved (default "Y/m/d H:i") — no migration:
 * the PHP format tokens are mapped to Air Datepicker's tokens at runtime.
 *
 * Modes (driven by the option's data-datetime-attr):
 *   timepicker:true,  datepicker:true   -> date + time
 *   timepicker:true,  datepicker:false  -> time only  (onlyTimepicker)
 *   timepicker:false, datepicker:true   -> date only
 */
(function ($, fwe) {

	var localeEn = {
		days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		today: 'Today', clear: 'Clear', dateFormat: 'yyyy/MM/dd', timeFormat: 'HH:mm', firstDay: 1
	};

	// PHP date() tokens -> Air Datepicker format tokens.
	var TOKEN_MAP = { Y: 'yyyy', y: 'yy', m: 'MM', n: 'M', d: 'dd', j: 'd', H: 'HH', G: 'H', h: 'hh', g: 'h', i: 'mm', s: 'ss', A: 'AA', a: 'aa' };
	var TIME_TOKEN = /[HGghisAa]/;

	function phpToAir(fmt) {
		return String(fmt).replace(/[A-Za-z]/g, function (c) { return TOKEN_MAP[c] || c; });
	}

	// Split a PHP format like "Y/m/d H:i" into its date part ("Y/m/d") and time
	// part ("H:i"). Air Datepicker appends the time itself (timeFormat) when the
	// timepicker is on, so date and time must live in SEPARATE format options —
	// putting time tokens in dateFormat too would render the time twice.
	function splitFormat(fmt) {
		var parts = String(fmt).split(' ');
		return {
			date: parts.filter(function (p) { return !TIME_TOKEN.test(p); }).join(' '),
			time: parts.filter(function (p) { return TIME_TOKEN.test(p); }).join(' ')
		};
	}

	// Parse a value string according to a PHP-style format into a Date (or null).
	function parseByFormat(value, fmt) {
		if (!value) { return null; }
		var groups = [], re = '';
		var caps = { Y: ['(\\d{4})', 'Y'], y: ['(\\d{2})', 'y'], m: ['(\\d{1,2})', 'm'], n: ['(\\d{1,2})', 'm'], d: ['(\\d{1,2})', 'd'], j: ['(\\d{1,2})', 'd'], H: ['(\\d{1,2})', 'H'], G: ['(\\d{1,2})', 'H'], i: ['(\\d{1,2})', 'i'], s: ['(\\d{1,2})', 's'] };
		for (var k = 0; k < fmt.length; k++) {
			var c = fmt[k];
			if (caps[c]) { re += caps[c][0]; groups.push(caps[c][1]); }
			else { re += c.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
		}
		var m = new RegExp('^' + re).exec(value);
		if (!m) { return null; }
		var p = { Y: 1970, m: 1, d: 1, H: 0, i: 0, s: 0 };
		for (var g = 0; g < groups.length; g++) {
			var name = groups[g], val = parseInt(m[g + 1], 10);
			if (name === 'y') { p.Y = 2000 + val; } else { p[name] = val; }
		}
		var dt = new Date(p.Y, p.m - 1, p.d, p.H, p.i, p.s);
		return isNaN(dt.getTime()) ? null : dt;
	}

	var init = function () {
		var $container = $(this),
			$input = $container.find('input.fw-option-type-text'),
			attrs = $container.data('datetime-attr') || {};

		if (!$input.length) { $input = $container.find('input').first(); }
		if (!$input.length || typeof window.AirDatepicker !== 'function') { return; }

		var el = $input.get(0);
		var phpFmt = attrs.format || 'Y/m/d H:i';
		var hasTime = attrs.timepicker !== false;
		var hasDate = attrs.datepicker !== false;
		var fmt = splitFormat(phpFmt);

		var config = {
			locale: localeEn,
			timepicker: hasTime,
			onlyTimepicker: !hasDate,
			autoClose: !hasTime, // date-only closes on pick; time modes stay open to adjust
			firstDay: 1
		};
		// Date and time formats live in SEPARATE options (see splitFormat).
		if (hasDate) { config.dateFormat = phpToAir(fmt.date || 'Y/m/d'); }
		if (hasTime) { config.timeFormat = phpToAir(fmt.time || 'H:i'); }

		if (attrs.minDate) { var mn = parseByFormat(attrs.minDate, phpFmt) || parseByFormat(attrs.minDate, 'Y/m/d'); if (mn) { config.minDate = mn; } }
		if (attrs.maxDate) { var mx = parseByFormat(attrs.maxDate, phpFmt) || parseByFormat(attrs.maxDate, 'Y/m/d'); if (mx) { config.maxDate = mx; } }

		if (el.value) { var cur = parseByFormat(el.value, phpFmt); if (cur) { config.selectedDates = [cur]; } }

		// Air Datepicker does not fire the input's native change event; do it in
		// onSelect so both Unyson's change tracking and the range linker react.
		config.onSelect = function () { $(el).trigger('change'); };

		var inst = new window.AirDatepicker(el, config);
		$container.data('air-dp', inst);

		$input.on('change', function (e) {
			fw.options.trigger.changeForEl(
				$(e.target).closest('[data-fw-option-type="datetime-picker"], [data-fw-option-type="time-picker"]'), {
					value: e.target.value
				}
			);
		});
	};

	// The same picker powers both 'datetime-picker' and its thin 'time-picker'
	// subclass (time-only), so register/initialise both under one script.
	var optionApi = {
		startListeningForChanges: $.noop,
		getValue: function (optionDescriptor) {
			return {
				value: $(optionDescriptor.el).find(
					'[data-fw-option-type="text"]'
				).find('> input').val(),
				optionDescriptor: optionDescriptor
			};
		}
	};
	fw.options.register('datetime-picker', optionApi);
	fw.options.register('time-picker', optionApi);

	fwe.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option-type-datetime-picker:not(.fw-option-initialized), .fw-option-type-time-picker:not(.fw-option-initialized)').each(init)
			.addClass('fw-option-initialized');
	});

})(jQuery, fwEvents);
