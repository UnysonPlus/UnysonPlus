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

	/* read a background-pro control -> {color, gradient, image} (video isn't previewable) */
	function bgProValue($bg) {
		if (!$bg || !$bg.length) { return null; }
		var custom = ($bg.find('[name$="[color][value][custom]"]').val() || '').toString().trim();
		var pre    = ($bg.find('[name$="[color][value][predefined]"]').val() || '').toString().trim();
		var imgRaw = ($bg.find('[name$="[image][src]"]').val() || '').toString().trim();
		var img = '';
		if (imgRaw) {
			try { var pj = JSON.parse(imgRaw); img = (pj && (pj.url || (pj[0] && pj[0].url))) || ''; }
			catch (e) { img = /^(https?:|\/)/.test(imgRaw) ? imgRaw : ''; }
		}
		return {
			color:    (custom && custom !== 'transparent' && custom !== 'rgba(0,0,0,0)') ? custom : pre,
			gradient: ($bg.find('.gv2-output').val() || '').toString().trim(),
			image:    img
		};
	}

	/* background-pro value -> css declarations array (color, then image over gradient) */
	function bgDecls(bg) {
		var p = [];
		if (!bg) { return p; }
		if (bg.color) { p.push('background-color:' + bg.color); }
		var imgs = [];
		if (bg.image)    { imgs.push('url(' + bg.image + ')'); }
		if (bg.gradient) { imgs.push(bg.gradient); }
		if (imgs.length) {
			p.push('background-image:' + imgs.join(', '));
			if (bg.image) { p.push('background-size:cover'); p.push('background-position:center'); }
		}
		return p;
	}

	/* read one badge's full nested value from the DOM */
	function readBox($item) {
		var d = { preset_name: '', badge_shape: 'circle', badge_size: {}, icon_size: {}, border_radius: {}, transition: '', hover_fx: [], custom_css: '', states: {} };
		STATES.forEach(function (s) { d.states[s] = {}; });

		function stateOf(name) {
			var m = name.match(/\[states\]\[([a-z]+)\]/);
			return (m && d.states[m[1]] !== undefined) ? m[1] : null;
		}

		// compact color pickers (icon_color / border_color per state)
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

		// unit-input (border_width per state, OR shared badge_size / icon_size / border_radius)
		$item.find(UNITINPUT).each(function () {
			var name = ($(this).find('.fw-unit-input-json').attr('name') || '');
			var fm = name.match(/\[([a-z_]+)\]$/);
			if (!fm) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-unit-input-json').val()); } catch (e) { val = {}; }
			var st = stateOf(name);
			if (st) { d.states[st][fm[1]] = val; }
			else if (fm[1] === 'badge_size' || fm[1] === 'icon_size' || fm[1] === 'border_radius') { d[fm[1]] = val; }
		});

		// scalars NOT inside a composite control above
		$item.find('input, select, textarea').each(function () {
			var $el = $(this);
			if ($el.closest(PCCP).length || $el.closest(BSHADOW).length || $el.closest(UNITINPUT).length) { return; }
			var name = ($el.attr('name') || '');
			if (!name) { return; }
			if (/\[preset_name\]$/.test(name))  { d.preset_name  = $el.val(); return; }
			if (/\[badge_shape\]$/.test(name))  { d.badge_shape  = $el.val(); return; }
			if (/\[transition\]$/.test(name))   { d.transition   = $el.val(); return; }
			if (/\[custom_css\]$/.test(name))   { d.custom_css   = $el.val(); return; }
			var sm = name.match(/\[states\]\[([a-z]+)\]\[(border_style)\]$/);
			if (sm && d.states[sm[1]] !== undefined) {
				if (typeof d.states[sm[1]][sm[2]] === 'undefined') { d.states[sm[1]][sm[2]] = $el.val(); }
			}
		});

		// Hover Effects (multi-select) — read the selected options.
		d.hover_fx = [];
		$item.find('[name*="[hover_fx]"] option:selected').each(function () {
			var v = $(this).val();
			if (v) { d.hover_fx.push(v); }
		});

		// Tile fill (background-pro) PER STATE.
		$item.find('.fw-bp-panel[data-bp-panel]').each(function () {
			var state = $(this).attr('data-bp-panel');
			if (!d.states[state]) { return; }
			var $bg = $(this).find('.fw-option-type-background-pro').first();
			if ($bg.length) { d.states[state].background = bgProValue($bg); }
		});

		return d;
	}

	/* border declarations for one state (the tile always borders on all sides) */
	function stateDecls(st) {
		var p = [];
		if (!st) { return p; }
		var style = st.border_style;
		var bw    = unitCss(st.border_width);
		var color = st.border_color;

		if (style && style !== '') {
			p.push('border:' + (bw || '1px') + ' ' + style + ' ' + (color || 'currentColor'));
		} else {
			if (bw)    { p.push('border-width:' + bw); }
			if (color) { p.push('border-color:' + color); }
		}

		var sh = boxShadowCss(st.box_shadow);
		if (sh) { p.push('box-shadow:' + sh); }
		return p;
	}

	/* shape -> border-radius / clip-path base decls */
	function shapeDecls(shape, cornerCss) {
		if (shape === 'hexagon') {
			return ['clip-path:polygon(50% 0,100% 25%,100% 75%,50% 100%,0 75%,0 25%)'];
		}
		if (shape === 'circle') { return ['border-radius:50%']; }
		if (shape === 'square') { return cornerCss ? ['border-radius:' + cornerCss] : ['border-radius:0']; }
		// rounded
		return ['border-radius:' + (cornerCss || '25%')];
	}

	/* compose full scoped preview CSS */
	function buildCss(sel, d) {
		function rule(parts) { return parts.filter(Boolean).join(';'); }

		var size = unitCss(d.badge_size) || '48px';
		var icon = unitCss(d.icon_size) || '24px';
		var corner = unitCss(d.border_radius);

		var base = [
			'display:inline-flex', 'align-items:center', 'justify-content:center',
			'box-sizing:border-box', 'flex:0 0 auto',
			'width:' + size, 'height:' + size
		];
		base = base.concat(shapeDecls(d.badge_shape, corner));
		if (d.transition) { base.push('transition:all ' + (parseInt(d.transition, 10) || 0) + 'ms ease'); }

		// Pop / Shine need a clipping-safe positioning context (Shine only).
		var fx = d.hover_fx || [];
		var has = function (k) { return fx.indexOf(k) > -1; };
		if (has('shine')) { base.push('position:relative'); base.push('overflow:hidden'); }

		// Default state: border skin + tile fill + icon color.
		base = base.concat(stateDecls(d.states.default));
		base = base.concat(bgDecls(d.states.default && d.states.default.background));
		var dcolor = d.states.default && d.states.default.icon_color;
		if (dcolor) { base.push('color:' + dcolor); }

		var css = sel + '{' + rule(base) + '}';
		// Icon sizing (font glyph + inline SVG).
		css += sel + ' svg{width:' + icon + ';height:' + icon + ';fill:currentColor}';
		css += sel + ' i,' + sel + ' [class*="fa-"]{font-size:' + icon + ';line-height:1}';

		// Hover state: border + fill + icon color diffs.
		var hov = stateDecls(d.states.hover)
			.concat(bgDecls(d.states.hover && d.states.hover.background));
		var hcolor = d.states.hover && d.states.hover.icon_color;
		if (hcolor) { hov.push('color:' + hcolor); }

		// Lift + Pop compose into one transform; Glow merges into the hover shadow.
		var tf = [];
		if (has('lift')) { tf.push('translateY(-4px)'); }
		if (has('pop'))  { tf.push('scale(1.12)'); }
		if (tf.length) { hov.push('transform:' + tf.join(' ')); }
		if (has('glow')) {
			var gc = (d.states.hover && d.states.hover.background && d.states.hover.background.color)
				|| (d.states.default && d.states.default.background && d.states.default.background.color)
				|| (d.states.default && d.states.default.icon_color) || 'rgba(47,116,230,0.45)';
			var glow = '0 0 20px ' + gc, merged = false, i;
			for (i = 0; i < hov.length; i++) {
				if (hov[i].indexOf('box-shadow:') === 0) { hov[i] += ', ' + glow; merged = true; break; }
			}
			if (!merged) {
				var ds = boxShadowCss(d.states.default && d.states.default.box_shadow);
				hov.push('box-shadow:' + (ds ? ds + ', ' : '') + glow);
			}
		}
		if (hov.length) { css += sel + ':hover,' + sel + '.is-hover{' + rule(hov) + '}'; }

		// Shine — a diagonal sheen swept via a ::before pseudo.
		if (has('shine')) {
			css += sel + '::before{content:"";position:absolute;top:0;left:-75%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-20deg);pointer-events:none;transition:left .6s ease;z-index:2}';
			css += sel + ':hover::before,' + sel + '.is-hover::before{left:125%}';
		}

		if (d.custom_css) { css += ('' + d.custom_css).split('{{SELECTOR}}').join(sel); }

		return css;
	}

	function initItem($item) {
		if ($item.data('ibp-init')) { return; }
		$item.data('ibp-init', true);

		var uid    = 'ibp-' + (++uidCounter);
		var $badge  = $item.find('.fw-ib-badge');
		var $style = $item.find('.fw-bp-preview-style');
		var $stage = $item.find('.fw-bp-preview-stage');
		var $header = $item.find('.fw-option-type-icon-badge-presets-item-title');
		$badge.addClass(uid);

		function refresh() {
			var d = readBox($item);
			$style.html(buildCss('.' + uid, d));
			$header.text((d.preset_name && d.preset_name !== '') ? d.preset_name : 'Icon Badge');
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
			$badge.removeClass('is-hover');
			if (state !== 'default') { $badge.addClass('is-' + state); }
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
		$item.on('click', '.fw-option-type-icon-badge-presets-item-header', function (e) {
			if ($(e.target).closest('.fw-option-type-icon-badge-presets-item-handle, .fw-option-type-icon-badge-presets-item-duplicate, .fw-option-type-icon-badge-presets-item-remove').length) {
				return;
			}
			e.preventDefault();
			$item.toggleClass('is-collapsed');
			onExpand();
		});

		refresh();
	}

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-icon-badge-presets:not(.fw-ibp-initialized)').each(function () {
			var $box = $(this).addClass('fw-ibp-initialized');
			var $list = $box.children('.fw-option-type-icon-badge-presets-list');
			var template = $box.attr('data-list-item-template') || '';

			$list.sortable({
				handle: '.fw-option-type-icon-badge-presets-item-handle',
				items: '> .fw-option-type-icon-badge-presets-item',
				axis: 'y',
				tolerance: 'pointer',
				forcePlaceholderSize: true
			});

			$box.children('.fw-option-type-icon-badge-presets-controls')
				.find('.fw-option-type-icon-badge-presets-add')
				.on('click', function (e) {
					e.preventDefault();
					var idx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);
					var html = template.replace(/\{\{-\s*index\s*\}\}/g, idx);
					var $newBox = $($.parseHTML(html));
					$list.append($newBox);
					fwEvents.trigger('fw:options:init', { $elements: $newBox });
				});
		});

		var $items = data.$elements.find('.fw-option-type-icon-badge-presets-item');
		if (data.$elements.is('.fw-option-type-icon-badge-presets-item')) {
			$items = $items.add(data.$elements);
		}
		$items.each(function () { initItem($(this)); });
	});

	$(document).on('click', '.fw-option-type-icon-badge-presets-item-remove', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.fw-option-type-icon-badge-presets-item');
		var label = ($item.find('.fw-option-type-icon-badge-presets-item-title').first().text() || '').trim();
		var msg = label
			? 'Remove the "' + label + '" icon badge preset? This cannot be undone.'
			: 'Remove this icon badge preset? This cannot be undone.';
		fw.confirm(msg, function () { $item.remove(); });
	});

	// --- Duplicate a preset (clone as a NEW preset, fresh index, live values) ---
	$(document).on('click', '.fw-option-type-icon-badge-presets-item-duplicate', function (e) {
		e.preventDefault();
		duplicateItem($(this).closest('.fw-option-type-icon-badge-presets-item'));
	});

	function duplicateItem($item) {
		var oldIdx = String($item.attr('data-bp-index') || '');
		var newIdx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);
		$item.find('.CodeMirror').each(function () { if (this.CodeMirror) { this.CodeMirror.save(); } });
		syncValuesToAttrs($item);
		var $clone = $item.clone();
		$clone.find('.CodeMirror').remove();
		$clone.removeData('ibp-init').removeAttr('data-ibp-init');
		$clone.find('.initialized').removeClass('initialized');
		reindex($clone, oldIdx, newIdx);
		$clone.attr('data-bp-index', newIdx).addClass('is-collapsed');
		$item.after($clone);
		fwEvents.trigger('fw:options:init', { $elements: $clone });
	}

	function syncValuesToAttrs($scope) {
		$scope.find('input, textarea, select').each(function () {
			if (this.tagName === 'TEXTAREA') {
				this.textContent = this.value;
			} else if (this.tagName === 'SELECT') {
				$(this).find('option').each(function () {
					if (this.selected) { this.setAttribute('selected', 'selected'); } else { this.removeAttribute('selected'); }
				});
			} else if (this.type === 'checkbox' || this.type === 'radio') {
				if (this.checked) { this.setAttribute('checked', 'checked'); } else { this.removeAttribute('checked'); }
			} else {
				this.setAttribute('value', this.value);
			}
		});
	}

	function reindex($scope, oldIdx, newIdx) {
		if (oldIdx === '') { return; }
		var esc = oldIdx.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		var re  = new RegExp('([\\[\\-])' + esc + '([\\]\\-])');
		$scope.find('[name], [id], [for]').each(function () {
			var el = this;
			['name', 'id', 'for'].forEach(function (a) {
				var v = el.getAttribute(a);
				if (v && re.test(v)) { el.setAttribute(a, v.replace(re, '$1' + newIdx + '$2')); }
			});
		});
	}

})(jQuery);
