/**
 * fw-device-tabs — shared device switcher behaviour for responsive option controls.
 *
 * A host is any element with the `fw-device-host` class that contains:
 *   - one `.fw-device-tabs` group of `.fw-device-tab` buttons (rendered by the
 *     PHP helper fw_render_device_tabs()), each carrying:
 *       data-fw-device-key = "base" | "md" | "lg"   (the panel/breakpoint token)
 *       data-device        = "sm"   | "md" | "lg"   (the builder's global slug)
 *   - per-device panels marked `[data-fw-device-panel="{key}"]`.
 *
 * This module:
 *   - activates the matching tab + panel when a tab is clicked,
 *   - best-effort drives the page builder's global device toggle so the canvas
 *     follows (by clicking the toolbar device button, which runs its own apply()
 *     and re-broadcasts) — a no-op when that toolbar isn't present (e.g. the
 *     option modal), so it degrades gracefully,
 *   - listens to `fw:builder:device-preview` so the global toolbar drives the
 *     control too,
 *   - initialises each host's active tab from window.fwPbDevice, and re-inits
 *     freshly injected hosts on `fw:options:init`.
 *
 * Editor-only: nothing here touches the saved value or the live page.
 */
(function ($, fwe) {
	'use strict';

	var DEVICE_TO_KEY = { sm: 'base', md: 'md', lg: 'lg' };
	var KEY_TO_DEVICE = { base: 'sm', md: 'md', lg: 'lg' };

	// Resolve the key the builder is currently previewing (defaults to desktop,
	// matching device-preview.js's own 'lg' default).
	function globalKey() {
		var d = (typeof window.fwPbDevice === 'string') ? window.fwPbDevice : 'lg';
		return DEVICE_TO_KEY[d] || 'base';
	}

	function activate($host, key) {
		if (!KEY_TO_DEVICE[key]) { key = 'base'; }

		$host.find('.fw-device-tab')
			.removeClass('is-active')
			.filter('[data-fw-device-key="' + key + '"]').addClass('is-active');

		$host.find('[data-fw-device-panel]').removeClass('is-active');
		$host.find('[data-fw-device-panel="' + key + '"]').addClass('is-active');

		$host.attr('data-fw-device-active', key);
	}

	// Try to move the builder's global device toggle to match. The toolbar button's
	// own click handler runs apply() (sets window.fwPbDevice, reflows the canvas,
	// re-fires the event). Returns true if a toolbar button was found+clicked.
	function syncGlobal(device) {
		var $btn = $('.fw-device-preview .fw-device-btn[data-device="' + device + '"]');
		if (!$btn.length || $btn.hasClass('active')) { return false; }
		$btn.trigger('click');
		return true;
	}

	function initHost(host) {
		var $host = $(host);
		if ($host.data('fwDeviceTabsInit')) { return; }
		$host.data('fwDeviceTabsInit', true);
		activate($host, globalKey());
	}

	$(document).on('click', '.fw-device-host .fw-device-tab', function (e) {
		e.preventDefault();
		var $tab  = $(this);
		var $host = $tab.closest('.fw-device-host');
		var key   = $tab.attr('data-fw-device-key') || 'base';

		// Local switch first for an instant response…
		activate($host, key);
		// …then nudge the global toggle so the canvas + sibling controls follow.
		syncGlobal(KEY_TO_DEVICE[key]);
	});

	// Global toggle (or another control's sync) drives every host.
	if (fwe && typeof fwe.on === 'function') {
		fwe.on('fw:builder:device-preview', function (device) {
			var key = DEVICE_TO_KEY[device] || 'base';
			$('.fw-device-host').each(function () {
				activate($(this), key);
			});
		});

		// Re-init hosts that arrive with lazily-rendered options (modals, popovers).
		fwe.on('fw:options:init', function (data) {
			var $scope = (data && data.$elements) ? data.$elements : $(document);
			$scope.find('.fw-device-host').each(function () { initHost(this); });
			$scope.filter('.fw-device-host').each(function () { initHost(this); });
		});
	}

	$(function () {
		$('.fw-device-host').each(function () { initHost(this); });
	});

})(jQuery, typeof fwEvents !== 'undefined' ? fwEvents : null);
