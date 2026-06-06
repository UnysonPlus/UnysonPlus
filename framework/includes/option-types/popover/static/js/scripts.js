/* Popover option type. Toggles an in-flow panel that lazily hosts the inner
 * option(s); reflects the inner control's value into the compact trigger. */
(function ($, fwe) {

	// Read the inner control's current value and show its label in the trigger.
	function reflect($po) {
		var summary = {};
		try { summary = JSON.parse($po.attr('data-summary') || '{}'); } catch (e) {}

		var $panel = $po.find('.fw-popover-panel');
		// image-picker / select / radio expose a <select> or input; read the first.
		var $ctrl = $panel.find('select').first();
		if (!$ctrl.length) {
			$ctrl = $panel.find('input:not([type=hidden]), textarea').first();
		}
		var val = $ctrl.length ? $ctrl.val() : '';

		var label = ( val != null && summary[val] != null ) ? summary[val] : ( val || '' );
		$po.find('.fw-popover-summary').text(label);
	}

	function openPanel($po) {
		var $panel = $po.find('.fw-popover-panel');
		var tpl = $panel.attr('data-options-template');

		// First open: inject the inner-option HTML and wire it up.
		if (tpl != null) {
			$panel.html(tpl).removeAttr('data-options-template');
			fwe.trigger('fw:options:init', { $elements: $panel });
			// Only single-option (passthrough) popovers reflect a value into the
			// trigger; multi/tabbed ones keep their static trigger label.
			if ($po.attr('data-autoclose') === '1') {
				reflect($po);
			}
		}

		$po.addClass('is-open');
	}

	function init($po) {
		$po.addClass('initialized');

		$po.on('click', '.fw-popover-trigger', function (e) {
			e.preventDefault();
			if ($po.hasClass('is-open')) {
				$po.removeClass('is-open');
			} else {
				openPanel($po);
			}
		});

		// Keyboard: Enter/Space toggles the panel.
		$po.on('keydown', '.fw-popover-trigger', function (e) {
			if (e.which === 13 || e.which === 32) {
				e.preventDefault();
				if ($po.hasClass('is-open')) { $po.removeClass('is-open'); } else { openPanel($po); }
			}
		});

		// Single-option (passthrough) popover: reflect the value into the trigger
		// and auto-close so it behaves like a normal picker. Multi/tabbed popovers
		// keep their static label and stay open while editing.
		if ($po.attr('data-autoclose') === '1') {
			$po.on('change', '.fw-popover-panel select, .fw-popover-panel input, .fw-popover-panel textarea', function () {
				reflect($po);
				$po.removeClass('is-open');
			});
		}

		// Tabbed popover: switch the active tab/panel.
		$po.on('click', '.fw-popover-tab', function () {
			var key = $(this).attr('data-fw-popover-tab');
			var $panel = $po.find('.fw-popover-panel');
			$panel.find('.fw-popover-tab').removeClass('is-active');
			$(this).addClass('is-active');
			$panel.find('.fw-popover-tab-panel').removeClass('is-active');
			$panel.find('.fw-popover-tab-panel[data-fw-popover-tab="' + key + '"]').addClass('is-active');
		});
	}

	// Close any open popover when clicking outside it.
	$(document).on('mousedown.fwPopover', function (e) {
		$('.fw-option-type-popover.is-open').each(function () {
			if (!$.contains(this, e.target)) {
				$(this).removeClass('is-open');
			}
		});
	});

	fwe.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-popover:not(.initialized)').each(function () {
			init($(this));
		});
	});

}(jQuery, fwEvents));
