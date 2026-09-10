/**
 * Option Type: Tabs
 * - Switches panels when a tab is clicked (or activated by keyboard).
 * - When the option opts into dots, marks a tab "customized" as soon as
 *   anything inside its panel changes, so hidden state in other tabs is
 *   noticeable. The initial dot state is rendered server-side (value vs
 *   default); this only turns dots ON as the user edits — a light, generic
 *   signal that doesn't need to know each inner type's notion of "empty".
 */
(function () {
	jQuery(document).ready(function ($) {

		var ROOT_SEL = '.fw-option-type-tabs';

		function activate($option, key) {
			if (!key) return;
			$option.find('> .fw-tabs__tabs > .fw-tabs__tab').removeClass('is-active');
			$option.find('> .fw-tabs__tabs > .fw-tabs__tab[data-fw-tab="' + key + '"]').addClass('is-active');
			$option.find('> .fw-tabs__panels > .fw-tabs__panel').removeClass('is-active');
			$option.find('> .fw-tabs__panels > .fw-tabs__panel[data-fw-panel="' + key + '"]').addClass('is-active');
		}

		function initOption($option) {
			if ($option.data('fwTabsInit')) return;
			$option.data('fwTabsInit', true);

			// Tab click / keyboard → switch active panel. Scope to THIS option's
			// own tab bar so nested tabs options don't cross-fire.
			$option.on('click', '> .fw-tabs__tabs > .fw-tabs__tab', function () {
				activate($option, $(this).attr('data-fw-tab'));
			});
			$option.on('keydown', '> .fw-tabs__tabs > .fw-tabs__tab', function (e) {
				if (e.which === 13 || e.which === 32) { // Enter / Space
					e.preventDefault();
					activate($option, $(this).attr('data-fw-tab'));
				}
			});

			// Dots: when a panel's controls change, light that tab's dot.
			$option.on('change input', '> .fw-tabs__panels > .fw-tabs__panel', function () {
				var key = $(this).attr('data-fw-panel');
				$option.find('> .fw-tabs__tabs > .fw-tabs__tab[data-fw-tab="' + key + '"]').addClass('has-value');
			});
		}

		// Initial pass for any options already on the page
		$(ROOT_SEL).each(function () { initOption($(this)); });

		// Unyson re-fires this when new options mount (popups, addable items, etc.).
		fwEvents.on('fw:options:init', function (data) {
			data.$elements.find(ROOT_SEL).each(function () { initOption($(this)); });
		});
	});
})();
