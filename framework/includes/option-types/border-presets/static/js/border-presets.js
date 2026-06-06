/* global jQuery, fwEvents */
(function ($) {
	'use strict';

	var uidCounter = 0;
	var PCCP      = '.fw-option-type-predefined-colors-color-picker-compact';
	var BSHADOW   = '.fw-option-type-box-shadow';
	var UNITINPUT = '.fw-option-type-unit-input';

	var STATES = ['default', 'hover'];

	function cssLen(v) {
		v = (v == null) ? '' : ('' + v).trim();
		if (v === '') { return ''; }
		return /^-?[0-9.]+$/.test(v) ? (parseFloat(v) || 0) + 'px' : v;
	}

	/* a unit-input value {value,unit} (or a legacy string) -> css length */
	function unitCss(v) {
		if (v && typeof v === 'object') {
			var num = (v.value == null) ? '' : ('' + v.value).trim();
			if (num === '') { return ''; }
			return num + (v.unit ? v.unit : '');
		}
		return cssLen(v);
	}

	/* resolve a compact picker root to its css color */
	function pickerColor($p) {
		var custom = ($p.find('input[name$="[custom]"]').val() || '').toString().trim();
		if (custom !== '' && custom !== 'transparent' && custom !== 'rgba(0,0,0,0)') { return custom; }
		var slug = ($p.find('.pccpc__preset-input').val() || '').toString().trim();
		if (slug !== '') {
			var $opt = $p.find('.pccpc__option[data-class="' + slug.replace(/"/g, '\\"') + '"]').first();
			return $opt.length ? ($opt.attr('data-color') || '') : '';
		}
		return '';
	}

	function boxShadowCss(s) {
		if (!s || typeof s !== 'object') { return ''; }
		var x = parseInt(s.x, 10) || 0, y = parseInt(s.y, 10) || 0,
			blur = parseInt(s.blur, 10) || 0, spread = parseInt(s.spread, 10) || 0;
		if (x === 0 && y === 0 && blur === 0 && spread === 0) { return ''; }
		var color = (s.color && ('' + s.color).trim() !== '') ? s.color : 'rgba(0,0,0,0.2)';
		return (s.inset ? 'inset ' : '') + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
	}

	/* read one box's full nested value from the DOM */
	function readBox($item) {
		var d = { preset_name: '', border_sides: 'all', border_radius: {}, padding: {}, transition: '', custom_css: '', states: {} };
		STATES.forEach(function (s) { d.states[s] = {}; });

		function stateOf(name) {
			var m = name.match(/\[states\]\[([a-z]+)\]/);
			return (m && d.states[m[1]] !== undefined) ? m[1] : null;
		}

		// compact color pickers (border_color per state)
		$item.find(PCCP).each(function () {
			var $p = $(this);
			var name = ($p.find('.pccpc__preset-input').attr('name') || '');
			var fm = name.match(/\[([a-z_]+)\]\[predefined\]$/);
			if (!fm) { return; }
			var st = stateOf(name);
			if (st) { d.states[st][fm[1]] = pickerColor($p); }
		});

		// box-shadow (hidden JSON per state)
		$item.find(BSHADOW).each(function () {
			var name = ($(this).find('.fw-box-shadow-json').attr('name') || '');
			var st = stateOf(name);
			if (!st) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-box-shadow-json').val()); } catch (e) { val = {}; }
			d.states[st].box_shadow = val;
		});

		// unit-input (border_width per state, OR shared border_radius)
		$item.find(UNITINPUT).each(function () {
			var name = ($(this).find('.fw-unit-input-json').attr('name') || '');
			var fm = name.match(/\[([a-z_]+)\]$/);
			if (!fm) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-unit-input-json').val()); } catch (e) { val = {}; }
			var st = stateOf(name);
			if (st) { d.states[st][fm[1]] = val; }
			else if (fm[1] === 'border_radius') { d.border_radius = val; }
		});

		// padding: a spacing widget (mode=padding). Each slot is a <select> whose
		// selected option text is "name (size)" — parse the size so the preview
		// matches the generated CSS (both resolve via the same spacing scale).
		$item.find('.fw-option-type-spacing select').each(function () {
			var nm = ($(this).attr('name') || '');
			var m = nm.match(/\[padding\]\[(all|top|right|bottom|left)\]$/);
			if (!m) { return; }
			var txt = ($(this).find('option:selected').text() || '');
			var sm = txt.match(/\(([^)]+)\)/);
			if (sm && sm[1].trim() !== '') { d.padding[m[1]] = sm[1].trim(); }
		});

		// scalars NOT inside a composite control above
		$item.find('input, select, textarea').each(function () {
			var $el = $(this);
			if ($el.closest(PCCP).length || $el.closest(BSHADOW).length || $el.closest(UNITINPUT).length) { return; }
			var name = ($el.attr('name') || '');
			if (!name) { return; }
			if (/\[preset_name\]$/.test(name))  { d.preset_name  = $el.val(); return; }
			if (/\[border_sides\]$/.test(name)) { d.border_sides = $el.val(); return; }
			if (/\[transition\]$/.test(name))   { d.transition   = $el.val(); return; }
			if (/\[custom_css\]$/.test(name))   { d.custom_css   = $el.val(); return; }
			var sm = name.match(/\[states\]\[([a-z]+)\]\[(border_style)\]$/);
			if (sm && d.states[sm[1]] !== undefined) {
				if (typeof d.states[sm[1]][sm[2]] === 'undefined') { d.states[sm[1]][sm[2]] = $el.val(); }
			}
		});

		return d;
	}

	function sideProp(sides) {
		switch (sides) {
			case 'top':    return 'border-top';
			case 'end':    return 'border-right';
			case 'bottom': return 'border-bottom';
			case 'start':  return 'border-left';
			default:       return 'border';
		}
	}

	/* declarations for one state */
	function stateDecls(st, sides) {
		var p = [];
		if (!st) { return p; }
		var prop  = sideProp(sides);
		var style = st.border_style;
		var bw    = unitCss(st.border_width);
		var color = st.border_color;

		if (style && style !== '') {
			p.push(prop + ':' + (bw || '1px') + ' ' + style + ' ' + (color || 'currentColor'));
		} else {
			if (bw)    { p.push(prop + '-width:' + bw); }
			if (color) { p.push(prop + '-color:' + color); }
		}

		var sh = boxShadowCss(st.box_shadow);
		if (sh) { p.push('box-shadow:' + sh); }
		return p;
	}

	/* compose full scoped preview CSS */
	function buildCss(sel, d) {
		function rule(parts) { return parts.filter(Boolean).join(';'); }

		var base = [];
		var r = unitCss(d.border_radius);
		if (r) { base.push('border-radius:' + r); }
		// padding: 'all' (shorthand) first, then per-side longhands override it.
		var padProp = { all: 'padding', top: 'padding-top', right: 'padding-right', bottom: 'padding-bottom', left: 'padding-left' };
		['all', 'top', 'right', 'bottom', 'left'].forEach(function (slot) {
			if (d.padding && d.padding[slot]) { base.push(padProp[slot] + ':' + d.padding[slot]); }
		});
		if (d.transition) { base.push('transition:all ' + (parseInt(d.transition, 10) || 0) + 'ms ease'); }
		base = base.concat(stateDecls(d.states.default, d.border_sides));

		var css = sel + '{' + rule(base) + '}';

		var hov = stateDecls(d.states.hover, d.border_sides);
		if (hov.length) { css += sel + ':hover,' + sel + '.is-hover{' + rule(hov) + '}'; }

		if (d.custom_css) { css += ('' + d.custom_css).split('{{SELECTOR}}').join(sel); }

		return css;
	}

	function initItem($item) {
		if ($item.data('bdp-init')) { return; }
		$item.data('bdp-init', true);

		var uid    = 'bdp-' + (++uidCounter);
		var $card  = $item.find('.fw-bd-card');
		var $style = $item.find('.fw-bp-preview-style');
		var $stage = $item.find('.fw-bp-preview-stage');
		var $header = $item.find('.fw-option-type-border-presets-item-title');
		$card.addClass(uid);

		function refresh() {
			var d = readBox($item);
			$style.html(buildCss('.' + uid, d));
			$header.text((d.preset_name && d.preset_name !== '') ? d.preset_name : 'Border Preset');
		}

		$item.on('input change', 'input, select, textarea', refresh);
		$item.on('fw:pccp:change', PCCP, refresh);

		// State tabs: switch the visible panel AND the previewed pseudo-state.
		$item.on('click', '.fw-bp-tab', function (e) {
			e.preventDefault();
			var state = $(this).attr('data-bp-tab');
			$item.find('.fw-bp-tab').removeClass('is-active');
			$(this).addClass('is-active');
			$item.find('.fw-bp-panel').removeClass('is-active')
				.filter('[data-bp-panel="' + state + '"]').addClass('is-active');
			$card.removeClass('is-hover');
			if (state !== 'default') { $card.addClass('is-' + state); }
		});

		// Light / dark preview stage
		$item.on('click', '.fw-bp-swatch', function (e) {
			e.preventDefault();
			var mode = $(this).attr('data-swatch');
			$item.find('.fw-bp-swatch').removeClass('is-active');
			$(this).addClass('is-active');
			$stage.toggleClass('is-light', mode === 'light').toggleClass('is-dark', mode === 'dark');
		});

		function onExpand() {
			if ($item.hasClass('is-collapsed')) { return; }
			$item.find('.CodeMirror').each(function () {
				if (this.CodeMirror) { this.CodeMirror.refresh(); }
			});
			refresh();
		}
		$item.on('click', '.fw-option-type-border-presets-item-header', function (e) {
			if ($(e.target).closest('.fw-option-type-border-presets-item-handle, .fw-option-type-border-presets-item-remove').length) {
				return;
			}
			e.preventDefault();
			$item.toggleClass('is-collapsed');
			onExpand();
		});

		refresh();
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-border-presets:not(.fw-bdp-initialized)').each(function () {
			var $box = $(this).addClass('fw-bdp-initialized');
			var $list = $box.children('.fw-option-type-border-presets-list');
			var template = $box.attr('data-list-item-template') || '';

			$list.sortable({
				handle: '.fw-option-type-border-presets-item-handle',
				items: '> .fw-option-type-border-presets-item',
				axis: 'y',
				tolerance: 'pointer',
				forcePlaceholderSize: true
			});

			$box.children('.fw-option-type-border-presets-controls')
				.find('.fw-option-type-border-presets-add')
				.on('click', function (e) {
					e.preventDefault();
					var idx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);
					var html = template.replace(/\{\{-\s*index\s*\}\}/g, idx);
					var $newBox = $($.parseHTML(html));
					$list.append($newBox);
					fwEvents.trigger('fw:options:init', { $elements: $newBox });
				});
		});

		var $items = data.$elements.find('.fw-option-type-border-presets-item');
		if (data.$elements.is('.fw-option-type-border-presets-item')) {
			$items = $items.add(data.$elements);
		}
		$items.each(function () { initItem($(this)); });
	});

	$(document).on('click', '.fw-option-type-border-presets-item-remove', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.fw-option-type-border-presets-item');
		var label = ($item.find('.fw-option-type-border-presets-item-title').first().text() || '').trim();
		var msg = label
			? 'Remove the "' + label + '" border preset? This cannot be undone.'
			: 'Remove this border preset? This cannot be undone.';
		if (window.confirm(msg)) { $item.remove(); }
	});

})(jQuery);
