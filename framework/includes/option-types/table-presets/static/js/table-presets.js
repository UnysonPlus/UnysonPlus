/* global jQuery, fwEvents, fwUniqueIncrement */
(function ($) {
	'use strict';

	var uidCounter = 0;
	var PCCP      = '.fw-option-type-predefined-colors-color-picker-compact';
	var BSHADOW   = '.fw-option-type-box-shadow';
	var UNITINPUT = '.fw-option-type-unit-input';

	var SECTIONS = ['header', 'body', 'striped', 'hover', 'footer', 'caption'];

	function unitCss(v) {
		if (v && typeof v === 'object') {
			var num = (v.value == null) ? '' : ('' + v.value).trim();
			if (num === '') { return ''; }
			return num + (v.unit ? v.unit : '');
		}
		v = (v == null) ? '' : ('' + v).trim();
		if (v === '') { return ''; }
		return /^-?[0-9.]+$/.test(v) ? (parseFloat(v) || 0) + 'px' : v;
	}

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

	function borderShorthand(style, widthVal, color) {
		if (!style || style === '') { return ''; }
		var w = unitCss(widthVal) || '1px';
		return w + ' ' + style + ' ' + (color || 'currentColor');
	}

	function newSections() {
		var s = {};
		SECTIONS.forEach(function (k) { s[k] = {}; });
		return s;
	}

	/* read one box's full nested value from the DOM */
	function readBox($item) {
		var d = {
			preset_name: '', cell_padding_y: {}, cell_padding_x: {},
			grid_lines: 'horizontal', grid_style: '', grid_width: {}, grid_color: '',
			outer_border_style: '', outer_border_width: {}, outer_border_color: '',
			border_radius: {}, outer_shadow: {}, cell_font_size: '',
			transition: '', custom_css: '', sections: newSections()
		};

		function secOf(name) {
			var m = name.match(/\[sections\]\[([a-z]+)\]/);
			return (m && d.sections[m[1]] !== undefined) ? m[1] : null;
		}

		// compact color pickers
		$item.find(PCCP).each(function () {
			var $p = $(this);
			var name = ($p.find('.pccpc__preset-input').attr('name') || '');
			var color = pickerColor($p);
			var sm = name.match(/\[sections\]\[([a-z]+)\]\[([a-z_]+)\]\[predefined\]$/);
			if (sm) { if (d.sections[sm[1]] !== undefined) { d.sections[sm[1]][sm[2]] = color; } return; }
			var fm = name.match(/\[([a-z_]+)\]\[predefined\]$/);
			if (fm) { d[fm[1]] = color; }
		});

		// box-shadow (shared: outer_shadow)
		$item.find(BSHADOW).each(function () {
			var name = ($(this).find('.fw-box-shadow-json').attr('name') || '');
			if (!/\[outer_shadow\]$/.test(name)) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-box-shadow-json').val()); } catch (e) { val = {}; }
			d.outer_shadow = val;
		});

		// unit-inputs (shared + section border widths)
		$item.find(UNITINPUT).each(function () {
			var name = ($(this).find('.fw-unit-input-json').attr('name') || '');
			var val = {};
			try { val = JSON.parse($(this).find('.fw-unit-input-json').val()); } catch (e) { val = {}; }
			var sm = name.match(/\[sections\]\[([a-z]+)\]\[([a-z_]+)\]$/);
			if (sm) { if (d.sections[sm[1]] !== undefined) { d.sections[sm[1]][sm[2]] = val; } return; }
			var fm = name.match(/\[([a-z_]+)\]$/);
			if (fm && d[fm[1]] !== undefined) { d[fm[1]] = val; }
		});

		// scalars (select / switch / text / textarea) not inside a composite control
		$item.find('input, select, textarea').each(function () {
			var $el = $(this);
			if ($el.closest(PCCP).length || $el.closest(BSHADOW).length || $el.closest(UNITINPUT).length) { return; }
			var name = ($el.attr('name') || '');
			if (!name) { return; }

			var sm = name.match(/\[sections\]\[([a-z]+)\]\[([a-z_]+)\]$/);
			if (sm) {
				if (d.sections[sm[1]] !== undefined && typeof d.sections[sm[1]][sm[2]] === 'undefined') {
					d.sections[sm[1]][sm[2]] = $el.val();
				}
				return;
			}
			if (/\[preset_name\]$/.test(name))        { d.preset_name        = $el.val(); return; }
			if (/\[grid_lines\]$/.test(name))         { d.grid_lines         = $el.val(); return; }
			if (/\[grid_style\]$/.test(name))         { d.grid_style         = $el.val(); return; }
			if (/\[outer_border_style\]$/.test(name)) { d.outer_border_style = $el.val(); return; }
			if (/\[transition\]$/.test(name))         { d.transition         = $el.val(); return; }
			if (/\[custom_css\]$/.test(name))         { d.custom_css         = $el.val(); return; }
		});

		return d;
	}

	/* compose scoped preview CSS (sel = the .fw-tp-card uid) */
	function buildCss(sel, d) {
		function rule(sel2, parts) { parts = parts.filter(Boolean); return parts.length ? (sel2 + '{' + parts.join(';') + '}') : ''; }
		var css = '';
		var s = d.sections;

		// frame on the card
		var frame = [];
		var ob = borderShorthand(d.outer_border_style, d.outer_border_width, d.outer_border_color);
		if (ob) { frame.push('border:' + ob); }
		var radius = unitCss(d.border_radius);
		if (radius) { frame.push('border-radius:' + radius); frame.push('overflow:hidden'); }
		var sh = boxShadowCss(d.outer_shadow);
		if (sh) { frame.push('box-shadow:' + sh); }
		css += rule(sel, frame);

		// table
		var tbl = ['border-collapse:collapse', 'width:100%'];
		var fs = unitCss(d.cell_font_size);
		if (fs) { tbl.push('font-size:' + fs); }
		css += rule(sel + ' table', tbl);

		// cell base: padding + grid lines
		var cell = [];
		var py = unitCss(d.cell_padding_y), px = unitCss(d.cell_padding_x);
		if (py || px) { cell.push('padding:' + (py || '0') + ' ' + (px || '0')); }
		if (d.grid_lines && d.grid_lines !== 'none') {
			var gline = borderShorthand(d.grid_style || 'solid', d.grid_width, d.grid_color);
			if (gline) {
				if (d.grid_lines === 'horizontal' || d.grid_lines === 'both') { cell.push('border-bottom:' + gline); }
				if (d.grid_lines === 'vertical'   || d.grid_lines === 'both') { cell.push('border-right:' + gline); }
			}
		}
		css += rule(sel + ' th,' + sel + ' td', cell);

		// header
		var hd = [];
		if (s.header.bg_color)   { hd.push('background-color:' + s.header.bg_color); }
		if (s.header.text_color) { hd.push('color:' + s.header.text_color); }
		if (s.header.font_weight)    { hd.push('font-weight:' + s.header.font_weight); }
		if (s.header.text_transform) { hd.push('text-transform:' + s.header.text_transform); }
		var hb = borderShorthand(s.header.border_style, s.header.border_width, s.header.border_color);
		if (hb) { hd.push('border-bottom:' + hb); }
		css += rule(sel + ' thead th', hd);

		// body
		var bd = [];
		if (s.body.bg_color)   { bd.push('background-color:' + s.body.bg_color); }
		if (s.body.text_color) { bd.push('color:' + s.body.text_color); }
		if (d.transition) { var tv = /^[0-9.]+$/.test(('' + d.transition).trim()) ? d.transition + 'ms' : d.transition; bd.push('transition:background-color ' + tv + ' ease,color ' + tv + ' ease'); }
		css += rule(sel + ' tbody td', bd);

		// striped
		if (s.striped.enabled === 'yes' && s.striped.bg_color) {
			css += rule(sel + ' tbody tr:nth-child(2n) td', ['background-color:' + s.striped.bg_color]);
		}

		// hover
		var hv = [];
		if (s.hover.bg_color)   { hv.push('background-color:' + s.hover.bg_color); }
		if (s.hover.text_color) { hv.push('color:' + s.hover.text_color); }
		css += rule(sel + ' tbody tr:hover td', hv);

		// footer
		var ft = [];
		if (s.footer.bg_color)   { ft.push('background-color:' + s.footer.bg_color); }
		if (s.footer.text_color) { ft.push('color:' + s.footer.text_color); }
		if (s.footer.font_weight) { ft.push('font-weight:' + s.footer.font_weight); }
		var fb = borderShorthand(s.footer.border_style, s.footer.border_width, s.footer.border_color);
		if (fb) { ft.push('border-top:' + fb); }
		css += rule(sel + ' tfoot td', ft);

		if (d.custom_css) { css += ('' + d.custom_css).split('{{SELECTOR}}').join(sel); }

		return css;
	}

	function initItem($item) {
		if ($item.data('tp-init')) { return; }
		$item.data('tp-init', true);

		var uid     = 'tp-card-' + (++uidCounter);
		var $card   = $item.find('.fw-tp-card');
		var $style  = $item.find('.fw-tp-preview-style');
		var $header = $item.find('.fw-option-type-table-presets-item-title');
		$card.addClass(uid);

		function refresh() {
			var d = readBox($item);
			$style.html(buildCss('.' + uid, d));
			$header.text((d.preset_name && d.preset_name !== '') ? d.preset_name : 'Table Preset');
		}

		$item.on('input change', 'input, select, textarea', refresh);
		$item.on('fw:pccp:change', PCCP, refresh);

		// Section tabs
		$item.on('click', '.fw-tp-tab', function (e) {
			e.preventDefault();
			var section = $(this).attr('data-tp-tab');
			$item.find('.fw-tp-tab').removeClass('is-active');
			$(this).addClass('is-active');
			$item.find('.fw-tp-panel').removeClass('is-active')
				.filter('[data-tp-panel="' + section + '"]').addClass('is-active');
		});

		function onExpand() {
			if ($item.hasClass('is-collapsed')) { return; }
			$item.find('.CodeMirror').each(function () {
				if (this.CodeMirror) { this.CodeMirror.refresh(); }
			});
			refresh();
		}
		$item.on('click', '.fw-option-type-table-presets-item-header', function (e) {
			if ($(e.target).closest('.fw-option-type-table-presets-item-handle, .fw-option-type-table-presets-item-remove').length) {
				return;
			}
			e.preventDefault();
			$item.toggleClass('is-collapsed');
			onExpand();
		});

		refresh();
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-table-presets:not(.fw-tp-initialized)').each(function () {
			var $box = $(this).addClass('fw-tp-initialized');
			var $list = $box.children('.fw-option-type-table-presets-list');
			var template = $box.attr('data-list-item-template') || '';

			$list.sortable({
				handle: '.fw-option-type-table-presets-item-handle',
				items: '> .fw-option-type-table-presets-item',
				axis: 'y',
				tolerance: 'pointer',
				forcePlaceholderSize: true
			});

			$box.children('.fw-option-type-table-presets-controls')
				.find('.fw-option-type-table-presets-add')
				.on('click', function (e) {
					e.preventDefault();
					var idx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);
					var html = template.replace(/\{\{-\s*index\s*\}\}/g, idx);
					var $newBox = $($.parseHTML(html));
					$list.append($newBox);
					fwEvents.trigger('fw:options:init', { $elements: $newBox });
				});
		});

		var $items = data.$elements.find('.fw-option-type-table-presets-item');
		if (data.$elements.is('.fw-option-type-table-presets-item')) {
			$items = $items.add(data.$elements);
		}
		$items.each(function () { initItem($(this)); });
	});

	$(document).on('click', '.fw-option-type-table-presets-item-remove', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.fw-option-type-table-presets-item');
		var label = ($item.find('.fw-option-type-table-presets-item-title').first().text() || '').trim();
		var msg = label
			? 'Remove the "' + label + '" table preset? This cannot be undone.'
			: 'Remove this table preset? This cannot be undone.';
		if (window.confirm(msg)) { $item.remove(); }
	});

})(jQuery);
