/**
 * Datetime Range option type — a SINGLE Air Datepicker input in range mode.
 * Replaces the legacy two-field xdsoft + moment.js implementation.
 *
 * The visible (readonly) input shows "from — to"; the real value lives in a
 * sibling hidden input as a JSON array [from, to] of formatted strings.
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

	var TOKEN_MAP = { Y: 'yyyy', y: 'yy', m: 'MM', n: 'M', d: 'dd', j: 'd', H: 'HH', G: 'H', h: 'hh', g: 'h', i: 'mm', s: 'ss', A: 'AA', a: 'aa' };
	var TIME_TOKEN = /[HGghisAa]/;

	function phpToAir(fmt) {
		return String(fmt).replace(/[A-Za-z]/g, function (c) { return TOKEN_MAP[c] || c; });
	}

	function splitFormat(fmt) {
		var parts = String(fmt).split(' ');
		return {
			date: parts.filter(function (p) { return !TIME_TOKEN.test(p); }).join(' '),
			time: parts.filter(function (p) { return TIME_TOKEN.test(p); }).join(' ')
		};
	}

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
		var $wrap = $(this),
			$display = $wrap.find('.fw-datetime-range-display'),
			$hidden = $wrap.find('.fw-datetime-range-value');

		if (!$display.length || typeof window.AirDatepicker !== 'function') { return; }

		var attrs = {};
		try { attrs = JSON.parse($wrap.attr('data-range-attr') || '{}'); } catch (e) { attrs = {}; }

		var phpFmt = attrs.format || 'Y/m/d';
		var sep = attrs.separator || ' — ';
		var hasTime = !!attrs.timepicker;
		var hasDate = attrs.datepicker !== false;
		var fmt = splitFormat(phpFmt);

		var config = {
			locale: localeEn,
			range: true,
			multipleDatesSeparator: sep,
			timepicker: hasTime,
			onlyTimepicker: !hasDate,
			autoClose: false, // a range needs two picks
			firstDay: 1,
			onSelect: function (o) {
				var vals = [];
				if (Array.isArray(o.formattedDate)) { vals = o.formattedDate.slice(0, 2); }
				else if (o.formattedDate) { vals = [o.formattedDate]; }
				$hidden.val(JSON.stringify(vals));
				fw.options.trigger.changeForEl(
					$wrap.closest('[data-fw-option-type="datetime-range"]'), { value: vals }
				);
			}
		};
		if (hasDate) { config.dateFormat = phpToAir(fmt.date || 'Y/m/d'); }
		if (hasTime) { config.timeFormat = phpToAir(fmt.time || 'H:i'); }
		if (attrs.minDate) { var mn = parseByFormat(attrs.minDate, phpFmt) || parseByFormat(attrs.minDate, 'Y/m/d'); if (mn) { config.minDate = mn; } }
		if (attrs.maxDate) { var mx = parseByFormat(attrs.maxDate, phpFmt) || parseByFormat(attrs.maxDate, 'Y/m/d'); if (mx) { config.maxDate = mx; } }

		// preselect the stored range
		var cur = [];
		try { cur = JSON.parse($hidden.val() || '[]'); } catch (e) { cur = []; }
		var dates = (cur || []).map(function (s) { return parseByFormat(s, phpFmt); }).filter(Boolean);
		if (dates.length) { config.selectedDates = dates; }

		var inst = new window.AirDatepicker($display.get(0), config);
		$wrap.data('air-dp', inst);
	};

	fw.options.register('datetime-range', {
		startListeningForChanges: $.noop,
		getValue: function (optionDescriptor) {
			var v = [];
			try { v = JSON.parse($(optionDescriptor.el).find('.fw-datetime-range-value').val() || '[]'); } catch (e) { v = []; }
			return { value: v, optionDescriptor: optionDescriptor };
		}
	});

	fwe.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option-type-datetime-range:not(.fw-option-initialized)').each(init)
			.addClass('fw-option-initialized');
	});

})(jQuery, fwEvents);
