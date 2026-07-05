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

	// The switcher (.fw-device-tabs) may be relocated OUT of its host — e.g. the
	// responsive option moves it into the option's label column. So resolve the
	// tab group by the host's id (fw_render_device_tabs stamps the group with
	// data-fw-device-tabs="{host id}"), falling back to a descendant lookup when
	// the switcher still lives inside the host (the spacing type, default case).
	function tabsFor($host) {
		var id = $host.attr('id');
		var $tabs = id ? $('.fw-device-tabs[data-fw-device-tabs="' + id + '"]') : $();
		return $tabs.length ? $tabs : $host.find('.fw-device-tabs');
	}

	function activate($host, key) {
		if (!KEY_TO_DEVICE[key]) { key = 'base'; }

		tabsFor($host).find('.fw-device-tab')
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

	// Relocate a device head (the .fw-device-head switcher wrapper) OUT of the option's
	// input column and INTO its label column, below the label text — so the switcher
	// sits by the label instead of reserving an empty band above the control. Opt-in:
	// only elements carrying .fw-device-head move. The per-device panels stay in the
	// input; the switcher keeps driving them by id (data-fw-device-tabs), and CSS
	// (fw-device-tabs.css) hides/reveals it on the option's own hover.
	function relocateHead(head) {
		var $head = $(head);
		if ($head.data('fwDeviceHeadMoved')) { return; }
		var $label = $head.closest('.fw-backend-option')
			.children('.fw-backend-option-label').children('.fw-inner').first();
		if (!$label.length) { return; }
		$label.append($head).addClass('fw-device-head-host');
		$head.addClass('fw-device-head-moved').data('fwDeviceHeadMoved', true);
	}

	function relocateHeads($scope) {
		($scope || $(document)).find('.fw-device-head').each(function () { relocateHead(this); });
	}

	// Nearest scrollable ancestor of an element (the options-modal body, usually),
	// so we can pin its scroll position across a panel switch.
	function scrollableAncestor(el) {
		el = el && el.parentNode;
		while (el && el !== document.body && el.nodeType === 1) {
			var oy = window.getComputedStyle(el).overflowY;
			if ((oy === 'auto' || oy === 'scroll') && el.scrollHeight > el.clientHeight) {
				return el;
			}
			el = el.parentNode;
		}
		return null;
	}

	$(document).on('click', '.fw-device-tab', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $tab  = $(this);
		// The switcher may sit inside its host (default) or be relocated elsewhere
		// (responsive → label column); resolve the host either way.
		var $host = $tab.closest('.fw-device-host');
		if (!$host.length) {
			var hid = $tab.closest('.fw-device-tabs').attr('data-fw-device-tabs');
			if (hid) { $host = $(document.getElementById(hid)); }
		}
		if (!$host.length) { return; }
		var key   = $tab.attr('data-fw-device-key') || 'base';

		// Pin the modal's scroll position. Showing a panel can make a freshly-visible
		// inner control (e.g. an image-picker) scroll its selected tile into view, which
		// yanks the whole modal to the top. Capture before, restore after — twice, since
		// that scroll can fire async on the next frame.
		var scroller = scrollableAncestor(this);
		var top = scroller ? scroller.scrollTop : null;

		// Local switch first for an instant response…
		activate($host, key);
		// …then nudge the global toggle so the canvas + sibling controls follow.
		syncGlobal(KEY_TO_DEVICE[key]);

		if (scroller && top !== null) {
			scroller.scrollTop = top;
			window.requestAnimationFrame(function () { scroller.scrollTop = top; });
		}
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
			relocateHeads($scope);
			$scope.find('.fw-device-host').each(function () { initHost(this); });
			$scope.filter('.fw-device-host').each(function () { initHost(this); });
		});
	}

	$(function () {
		relocateHeads($(document));
		$('.fw-device-host').each(function () { initHost(this); });
	});

})(jQuery, typeof fwEvents !== 'undefined' ? fwEvents : null);
