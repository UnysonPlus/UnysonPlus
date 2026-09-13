/**
 * Preset preview contrast — auto dark header for a LIGHT preview button.
 *
 * Theme Settings → Components rows (Button Presets, Sizes, Hover Animations, …) show a live
 * preview `.btn` inside a light (#f6f7f7) row header. A light-filled button (white, cream, a
 * pale gradient, a translucent-white outline pill) then sits on a near-identical surface and
 * reads as nothing. This measures each preview's EFFECTIVE background — gradient stops or the
 * background-color composited over the header — falls back to the visible ink (text / border)
 * for a transparent outline button, and when the result is too close to the header surface it
 * flips that row header to a dark surface via `.is-light-preview` (CSS in
 * preset-preview-contrast.css). Re-evaluates on live edits (a colour picker change, a re-rendered
 * addable-box title) through one debounced MutationObserver.
 */
(function ($) {
	'use strict';

	var HEADER_BG = [246, 247, 247];      // #f6f7f7 — the light row header
	var CONTRAST_MIN = 1.45;              // below this ratio the preview vanishes into the header
	var LIGHT_LUM = 0.80;                 // or simply very light

	function chan(c) { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
	function lum(rgb) { return 0.2126 * chan(rgb[0]) + 0.7152 * chan(rgb[1]) + 0.0722 * chan(rgb[2]); }
	function ratio(a, b) { var l1 = Math.max(a, b), l2 = Math.min(a, b); return (l1 + 0.05) / (l2 + 0.05); }

	// "rgb(1, 2, 3)" / "rgba(1, 2, 3, .5)" / "#abc" / "#aabbcc" → [r,g,b,a] or null.
	function parseColor(s) {
		s = String(s || '').trim();
		var m = s.match(/^rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:[,\s\/]+([\d.]+%?))?\s*\)$/i);
		if (m) {
			var a = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
			return [+m[1], +m[2], +m[3], isNaN(a) ? 1 : a];
		}
		m = s.match(/^#([0-9a-f]{3,8})$/i);
		if (m) {
			var h = m[1];
			if (h.length === 3 || h.length === 4) { h = h.split('').map(function (x) { return x + x; }).join(''); }
			var r = parseInt(h.slice(0, 2), 16), g = parseInt(h.slice(2, 4), 16), b = parseInt(h.slice(4, 6), 16);
			var al = h.length === 8 ? parseInt(h.slice(6, 8), 16) / 255 : 1;
			return [r, g, b, al];
		}
		if (s === 'transparent') { return [0, 0, 0, 0]; }
		return null;
	}
	function over(c, base) { // composite [r,g,b,a] over an opaque base
		var a = c[3];
		return [Math.round(c[0] * a + base[0] * (1 - a)), Math.round(c[1] * a + base[1] * (1 - a)), Math.round(c[2] * a + base[2] * (1 - a))];
	}
	// Every colour token inside a gradient string (top-level commas are irrelevant here — we only need the stops).
	function gradientColors(img) {
		var out = [], re = /rgba?\([^)]*\)|#[0-9a-f]{3,8}/gi, m;
		while ((m = re.exec(img))) { var c = parseColor(m[0]); if (c) { out.push(c); } }
		return out;
	}

	// Effective surface luminance of a preview element, or null when it can't be judged.
	function surfaceLum(el) {
		var cs = window.getComputedStyle(el);
		var img = cs.backgroundImage || 'none';
		if (img !== 'none' && /gradient\(/i.test(img)) {
			var stops = gradientColors(img);
			if (stops.length) {
				var sum = 0;
				for (var i = 0; i < stops.length; i++) { sum += lum(over(stops[i], HEADER_BG)); }
				return sum / stops.length;
			}
		}
		var bg = parseColor(cs.backgroundColor);
		if (bg && bg[3] >= 0.12) { return lum(over(bg, HEADER_BG)); }
		// Transparent / near-transparent fill: the button IS its ink — text colour and border.
		var ink = parseColor(cs.color), bd = parseColor(cs.borderTopColor);
		var bw = parseFloat(cs.borderTopWidth) || 0;
		var samples = [];
		if (ink && ink[3] > 0.2) { samples.push(lum(over(ink, HEADER_BG))); }
		if (bd && bd[3] > 0.2 && bw > 0) { samples.push(lum(over(bd, HEADER_BG))); }
		if (!samples.length) { return null; }
		var s2 = 0; for (var j = 0; j < samples.length; j++) { s2 += samples[j]; }
		return s2 / samples.length;
	}

	function isLight(el) {
		var L = surfaceLum(el);
		if (L === null) { return false; }
		return L >= LIGHT_LUM || ratio(L, lum(HEADER_BG)) < CONTRAST_MIN;
	}

	// Row headers + the preview element inside each.
	function scan(root) {
		var $root = $(root || document);
		// Button Presets — the header title IS the preview.
		$root.find('.fw-option-type-button-presets-item-header').each(function () {
			var title = this.querySelector('.fw-option-type-button-presets-item-title');
			$(this).toggleClass('is-light-preview', !!title && isLight(title));
		});
		// Addable-box rows (Sizes, Hover Animations, …) whose title template renders a `.btn` preview.
		$root.find('.fw-option-type-addable-box .postbox-header, .fw-option-type-addable-box .hndle').each(function () {
			var $h = $(this).closest('.postbox-header'); if (!$h.length) { $h = $(this); }
			var btn = $h[0].querySelector('.btn');
			$h.toggleClass('is-light-preview', !!btn && isLight(btn));
		});
	}

	var timer = null;
	function schedule() { clearTimeout(timer); timer = setTimeout(function () { scan(); }, 120); }

	$(function () {
		scan();
		// Live edits: colour pickers, gradient fields, renamed rows, re-rendered titles.
		$(document).on('input change fw:pccp:change fw:options:init', schedule);
		if (window.MutationObserver) {
			new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
		}
		// Preset stylesheets can land after DOM-ready (the scoped preview <style> is rebuilt on input).
		$(window).on('load', schedule);
		setTimeout(schedule, 800);
	});
})(jQuery);
