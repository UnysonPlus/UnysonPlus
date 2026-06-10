/**
 * fw-option-type-spacing
 *
 * Behaviour for the compact spacing control. Device-panel switching is handled
 * by the shared fw-device-tabs component; this file owns only the per-section
 * link toggle:
 *   - linked   → a single "All" select drives the whole section,
 *   - unlinked → four Top / Right / Bottom / Left selects.
 * On toggle we clear the now-hidden set so the saved value stays WYSIWYG (the
 * value model keeps `all` and the four sides as distinct slots; leaving a hidden
 * one populated would silently override on the frontend via source order).
 */
(function ($) {
	'use strict';

	$(document).on('click', '.fw-option-type-spacing .fw-sp-link', function (e) {
		e.preventDefault();

		var $row     = $(this).closest('.fw-sp-row');
		var nowLinked = !$row.hasClass('is-linked');

		$row.toggleClass('is-linked', nowLinked);

		if (nowLinked) {
			// Switching to "All" — drop the per-side picks.
			$row.find('.fw-sp-sides select').val('').trigger('change');
		} else {
			// Switching to per-side — drop the "All" pick.
			$row.find('.fw-sp-all select').val('').trigger('change');
		}
	});

})(jQuery);
