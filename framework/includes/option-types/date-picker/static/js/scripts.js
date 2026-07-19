/**
 * Date Picker option type — powered by Air Datepicker (vanilla, no jQuery/moment).
 * Replaces the legacy jQuery bootstrap-datepicker. Stored value format stays
 * "dd-mm-yyyy" so existing saved values keep working with no migration.
 */

"use strict";

var FW_AD_LOCALE_EN = {
	days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
	months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
	monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	today: 'Today', clear: 'Clear', dateFormat: 'dd-MM-yyyy', timeFormat: 'HH:mm', firstDay: 1
};

/** Parse a "dd-mm-yyyy" string into a Date (or null). */
function fw_date_picker_parse(value) {
	if (!value) { return null; }
	var m = /^(\d{2})-(\d{2})-(\d{4})/.exec(value);
	if (!m) { return null; }
	var d = new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
	return isNaN(d.getTime()) ? null : d;
}

function fw_option_type_date_picker_initialize(el) {
	var AD = window.AirDatepicker;
	if (typeof AD !== 'function') { return; }

	var opts = {};
	try { opts = JSON.parse(el.getAttribute('data-fw-option-date-picker-opts') || '{}'); } catch (e) { opts = {}; }

	var config = {
		locale: FW_AD_LOCALE_EN,
		dateFormat: 'dd-MM-yyyy',
		autoClose: true,
		firstDay: (opts.weekStart != null) ? parseInt(opts.weekStart, 10) : 1
	};

	if (opts.minDate) { var mn = fw_date_picker_parse(opts.minDate); if (mn) { config.minDate = mn; } }
	if (opts.maxDate) { var mx = fw_date_picker_parse(opts.maxDate); if (mx) { config.maxDate = mx; } }

	// preselect the stored value so the calendar opens on / highlights it
	if (el.value) { var cur = fw_date_picker_parse(el.value); if (cur) { config.selectedDates = [cur]; } }

	new AD(el, config);
}

jQuery(document).ready(function ($) {
	fwEvents.on('fw:options:init', function (data) {
		var $obj = data.$elements.find('.fw-option-type-date-picker:not(.initialized)');

		if (!$obj.length) {
			return;
		}

		$obj.each(function () {
			fw_option_type_date_picker_initialize(this);
		});

		$obj.addClass('initialized');
	});
});
