/* global jQuery, fwEvents */
(function ($) {
	'use strict';

	var uidCounter = 0;
	var PCCP   = '.fw-option-type-predefined-colors-color-picker-compact';
	var BSHADOW = '.fw-option-type-box-shadow';
	var UNITINPUT = '.fw-option-type-unit-input';
	var GRADIENT = '.fw-option-type-gradient-v2';

	var STATES = ['default', 'hover', 'active', 'focus', 'disabled'];

	function cssLen(v) {
		v = (v == null) ? '' : ('' + v).trim();
		if (v === '') { return ''; }
		return /^-?[0-9.]+$/.test(v) ? (parseFloat(v) || 0) + 'px' : v;
	}

	/* ---- a unit-input value {value,unit} (or a legacy string) -> css length ---- */
	function unitCss(v) {
		if (v && typeof v === 'object') {
			var num = (v.value == null) ? '' : ('' + v.value).trim();
			if (num === '') { return ''; }
			return num + (v.unit ? v.unit : '');
		}
		return cssLen(v);
	}

	/* ---- resolve a compact picker root to its css color ---- */
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

	/* ---- read one box's full nested value from the DOM ---- */
	function readBox($item) {
		var d = { color_name: '', transition: '', custom_css: '', font: {}, states: {} };
		STATES.forEach(function (s) { d.states[s] = {}; });

		function stateOf(name) {
			var m = name.match(/\[states\]\[([a-z]+)\]/);
			return (m && d.states[m[1]] !== undefined) ? m[1] : null;
		}

		// compact color pickers (bg/text/border per state)
		$item.find(PCCP).each(function () {
			var $p = $(this);
			var name = ($p.find('.pccpc__preset-input').attr('name') || '');
			var fm = name.match(/\[([a-z_]+)\]\[predefined\]$/);
			if (!fm) { return; }
			var field = fm[1];
			var st = stateOf(name);
			var color = pickerColor($p);
			if (st) { d.states[st][field] = color; }
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

		// gradient-v2 (background gradient, hidden JSON per state)
		$item.find(GRADIENT).each(function () {
			var name = ($(this).find('.fw-option-type-gradient-v2-json').attr('name') || '');
			var st = stateOf(name);
			if (!st) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-option-type-gradient-v2-json').val()); } catch (e) { val = {}; }
			d.states[st].gradient = val;
		});

		// unit-input (border_width): hidden JSON holds { value, unit }
		$item.find(UNITINPUT).each(function () {
			var name = ($(this).find('.fw-unit-input-json').attr('name') || '');
			var st = stateOf(name);
			var fm = name.match(/\[([a-z_]+)\]$/);
			if (!st || !fm) { return; }
			var val = {};
			try { val = JSON.parse($(this).find('.fw-unit-input-json').val()); } catch (e) { val = {}; }
			d.states[st][fm[1]] = val;
		});

		// scalar inputs/selects/textarea NOT inside the composite controls above
		$item.find('input, select, textarea').each(function () {
			var $el = $(this);
			if ($el.closest(PCCP).length || $el.closest(BSHADOW).length || $el.closest(UNITINPUT).length || $el.closest(GRADIENT).length) { return; }
			var name = ($el.attr('name') || '');
			if (!name) { return; }

			// shared font: [font][<key>]
			var fm = name.match(/\[font\]\[([a-z-]+)\]$/);
			if (fm) { d.font[fm[1]] = $el.val(); return; }

			// shared scalars
			if (/\[color_name\]$/.test(name)) { d.color_name = $el.val(); return; }
			if (/\[transition\]$/.test(name)) { d.transition = $el.val(); return; }
			if (/\[custom_css\]$/.test(name)) { d.custom_css = $el.val(); return; }

			// per-state scalars (border_width is a unit-input composite, handled above)
			var sm = name.match(/\[states\]\[([a-z]+)\]\[(text_transform|border_style)\]$/);
			if (sm && d.states[sm[1]] !== undefined) {
				if (typeof d.states[sm[1]][sm[2]] === 'undefined') { d.states[sm[1]][sm[2]] = $el.val(); }
			}
		});

		return d;
	}

	function boxShadowCss(s) {
		if (!s || typeof s !== 'object') { return ''; }
		var x = parseInt(s.x, 10) || 0, y = parseInt(s.y, 10) || 0,
			blur = parseInt(s.blur, 10) || 0, spread = parseInt(s.spread, 10) || 0;
		if (x === 0 && y === 0 && blur === 0 && spread === 0) { return ''; }
		var color = (s.color && ('' + s.color).trim() !== '') ? s.color : 'rgba(0,0,0,0.2)';
		return (s.inset ? 'inset ' : '') + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
	}

	/* ---- a gradient-v2 value {type,angle,stops} -> css gradient (mirrors to_css) ---- */
	function gradientCss(v) {
		if (!v || typeof v !== 'object' || !v.stops || v.stops.length < 2) { return ''; }
		var stops = v.stops.slice().sort(function (a, b) { return a.position - b.position; });
		var parts = stops.map(function (s) { return s.color + ' ' + s.position + '%'; });
		if (v.type === 'radial') { return 'radial-gradient(circle, ' + parts.join(', ') + ')'; }
		return 'linear-gradient(' + (v.angle == null ? 90 : v.angle) + 'deg, ' + parts.join(', ') + ')';
	}

	/* ---- declarations for one state ---- */
	function stateDecls(st) {
		var p = [];
		if (!st) { return p; }
		if (st.text_color)   { p.push('color:' + st.text_color); }
		if (st.bg_color)     { p.push('background-color:' + st.bg_color); }
		var g = gradientCss(st.gradient);   // layers over the bg color (fallback)
		if (g)               { p.push('background-image:' + g); }
		if (st.text_transform) { p.push('text-transform:' + st.text_transform); }

		var bStyle = st.border_style;
		var bw = unitCss(st.border_width);   // border_width is unit-input {value,unit}
		if (bStyle && bStyle !== 'none') {
			p.push('border:' + (bw || '1px') + ' ' + bStyle + ' ' + (st.border_color || 'currentColor'));
		} else if (bStyle === 'none') {
			p.push('border:0 solid transparent');
		} else {
			if (bw) { p.push('border-width:' + bw); }
			if (st.border_color) { p.push('border-color:' + st.border_color); }
		}

		var sh = boxShadowCss(st.box_shadow);
		if (sh) { p.push('box-shadow:' + sh); }

		return p;
	}

	/* ---- compose full scoped preview CSS ---- */
	function buildCss(sel, d) {
		function rule(parts) { return parts.filter(Boolean).join(';'); }

		// base = shared font IDENTITY (no size/line-height — that's the size axis)
		// + transition + default state
		var base = [];
		var f = d.font || {};
		if (f.family)            { base.push('font-family:' + f.family); }
		if (f.weight)            { base.push('font-weight:' + f.weight); }
		if (f['letter-spacing'] !== undefined && f['letter-spacing'] !== '') { base.push('letter-spacing:' + cssLen(f['letter-spacing'])); }
		if (f.style)             { base.push('font-style:' + f.style); }
		if (d.transition)        { base.push('transition:all ' + (parseInt(d.transition, 10) || 0) + 'ms ease'); }
		base = base.concat(stateDecls(d.states.default));

		var css = sel + '{' + rule(base) + '}';

		var map = {
			hover:    sel + ':hover,' + sel + '.is-hover',
			active:   sel + ':active,' + sel + '.is-active',
			focus:    sel + ':focus,' + sel + '.is-focus',
			disabled: sel + '.is-disabled,' + sel + ':disabled'
		};
		Object.keys(map).forEach(function (state) {
			var decls = stateDecls(d.states[state]);
			if (decls.length) { css += map[state] + '{' + rule(decls) + '}'; }
		});

		if (d.custom_css) { css += ('' + d.custom_css).split('{{SELECTOR}}').join(sel); }

		return css;
	}

	// Hydrate a DEFERRED box: move its stashed body (data-bp-body) into live DOM. With
	// withInit !== false, also fire fw:options:init so the body's heavy widgets
	// (typography-v2, code-editor, gradient-v2, box-shadow, pickers) initialize — this
	// is the cost we deferred, now paid for ONE box on expand instead of all at once.
	// On submit we hydrate WITHOUT init (withInit === false): the server-rendered
	// inputs already carry their values, so the preset saves without re-initializing.
	function hydrateBody($item, withInit) {
		if (!$item.hasClass('fw-bp-deferred') || $item.data('bp-hydrated')) { return; }
		$item.data('bp-hydrated', true);
		var html = $item.attr('data-bp-body') || '';
		$item.removeAttr('data-bp-body').removeClass('fw-bp-deferred');
		var $body = $item.find('.fw-option-type-button-presets-item-body').html(html);
		if (withInit !== false) {
			fwEvents.trigger('fw:options:init', { $elements: $body });
		}
	}

	function initItem($item) {
		if ($item.data('bp-init')) { return; }
		$item.data('bp-init', true);

		var uid = 'bpb-' + (++uidCounter);
		// The scoped <style> lives in the header (outside the deferrable body) so it
		// styles the header title even while the body is not yet hydrated.
		var $style = $item.find('.fw-bp-preview-style').first();
		// The header title IS the quick preview: it carries the generated `.uid`
		// rule, so it shows the preset's default-state look (and its name) even
		// while the box is collapsed.
		var $header = $item.find('.fw-option-type-button-presets-item-title');
		$header.addClass(uid);

		// Read this preset's value — from the live body once hydrated, otherwise by
		// parsing the stashed data-bp-body template into a DETACHED fragment (no widget
		// init) so the collapsed header preview is correct without paying the init cost.
		function readValue() {
			if (!$item.hasClass('fw-bp-deferred') || $item.data('bp-hydrated')) {
				return readBox($item);
			}
			return readBox($('<div>').html($item.attr('data-bp-body') || ''));
		}

		function refresh() {
			var d = readValue();
			$style.html(buildCss('.' + uid, d));
			var label = (d.color_name && d.color_name !== '') ? d.color_name : 'Button';
			// The preview button is inside the (possibly not-yet-hydrated) body — style
			// + label it when present; a no-op while deferred.
			$item.find('.fw-bp-btn').addClass(uid).text(label);
			$header.text(label);
		}

		$item.on('input change', 'input, select, textarea', refresh);
		$item.on('fw:pccp:change', PCCP, refresh);

		// State tabs: switch the visible panel AND the previewed pseudo-state.
		// Query the preview button fresh (it may have been hydrated after init).
		$item.on('click', '.fw-bp-tab', function (e) {
			e.preventDefault();
			var state = $(this).attr('data-bp-tab');
			$item.find('.fw-bp-tab').removeClass('is-active');
			$(this).addClass('is-active');
			$item.find('.fw-bp-panel').removeClass('is-active')
				.filter('[data-bp-panel="' + state + '"]').addClass('is-active');
			var $btn = $item.find('.fw-bp-btn');
			$btn.removeClass('is-hover is-active is-focus is-disabled');
			if (state !== 'default') { $btn.addClass('is-' + state); }
		});

		// Light / dark preview stage
		$item.on('click', '.fw-bp-swatch', function (e) {
			e.preventDefault();
			var mode = $(this).attr('data-swatch');
			$item.find('.fw-bp-swatch').removeClass('is-active');
			$(this).addClass('is-active');
			$item.find('.fw-bp-preview-stage')
				.toggleClass('is-light', mode === 'light').toggleClass('is-dark', mode === 'dark');
		});

		// Collapse / expand. On first expand, hydrate the deferred body (+ init its
		// widgets), then refresh the preview from the now-live inputs.
		function onExpand() {
			if ($item.hasClass('is-collapsed')) { return; }
			hydrateBody($item, true);
			$item.find('.CodeMirror').each(function () {
				if (this.CodeMirror) { this.CodeMirror.refresh(); }
			});
			refresh();
		}
		// The whole header toggles collapse/expand — clicking the title button,
		// the toggle caret, or any empty header space all work. The drag handle
		// and the remove icon are excluded so they keep their own behavior.
		$item.on('click', '.fw-option-type-button-presets-item-header', function (e) {
			if ($(e.target).closest('.fw-option-type-button-presets-item-handle, .fw-option-type-button-presets-item-duplicate, .fw-option-type-button-presets-item-remove').length) {
				return;
			}
			e.preventDefault();
			$item.toggleClass('is-collapsed');
			onExpand();
		});

		// Initial collapsed preview (parsed from data-bp-body when deferred — cheap).
		refresh();
	}

	// Safety net: before the settings form submits, materialize any deferred preset
	// body that was never expanded so its inputs are present in the DOM and save.
	// No widget init — the server-rendered inputs carry their values. Mirrors
	// Unyson's own lazy-tab "init all on submit" hook (backend-options.js).
	$(document).on('submit', 'form', function () {
		$(this).find('.fw-option-type-button-presets-item.fw-bp-deferred').each(function () {
			hydrateBody($(this), false);
		});
	});

	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option-type-button-presets:not(.fw-bp-initialized)').each(function () {
			var $box = $(this).addClass('fw-bp-initialized');
			var $list = $box.children('.fw-option-type-button-presets-list');
			var template = $box.attr('data-list-item-template') || '';

			$list.sortable({
				handle: '.fw-option-type-button-presets-item-handle',
				items: '> .fw-option-type-button-presets-item',
				axis: 'y',
				tolerance: 'pointer',
				forcePlaceholderSize: true
			});

			$box.children('.fw-option-type-button-presets-controls')
				.find('.fw-option-type-button-presets-add')
				.on('click', function (e) {
					e.preventDefault();
					var idx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);
					var html = template.replace(/\{\{-\s*index\s*\}\}/g, idx);
					var $newBox = $($.parseHTML(html));
					$list.append($newBox);
					fwEvents.trigger('fw:options:init', { $elements: $newBox });
				});
		});

		var $items = data.$elements.find('.fw-option-type-button-presets-item');
		if (data.$elements.is('.fw-option-type-button-presets-item')) {
			$items = $items.add(data.$elements);
		}
		$items.each(function () { initItem($(this)); });
	});

	$(document).on('click', '.fw-option-type-button-presets-item-remove', function (e) {
		e.preventDefault();
		// Confirm before removing — the remove icon sits right next to the
		// collapse caret, so it's easy to hit by accident.
		var $item = $(this).closest('.fw-option-type-button-presets-item');
		var label = ($item.find('.fw-option-type-button-presets-item-title').first().text() || '').trim();
		var msg = label
			? 'Remove the "' + label + '" button preset? This cannot be undone.'
			: 'Remove this button preset? This cannot be undone.';
		fw.confirm(msg, function () {
			$item.remove();
		});
	});

	// --- Duplicate a preset ------------------------------------------------
	// Clone the item as a NEW preset (fresh unique index) so it saves separately.
	// A plain DOM clone doesn't carry live form values, so we push each input's
	// current value into its attribute first, then re-point the box index in every
	// name / id / for to a new unique one.
	$(document).on('click', '.fw-option-type-button-presets-item-duplicate', function (e) {
		e.preventDefault();
		duplicateItem($(this).closest('.fw-option-type-button-presets-item'));
	});

	function duplicateItem($item) {
		// If the source is still deferred, hydrate it (with widget init) first so its
		// body inputs are live DOM — otherwise reindex() can't re-point the clone's
		// body field names/ids (they'd live inside data-bp-body) and the duplicate
		// would collide with the original on save.
		hydrateBody($item, true);

		var oldIdx = String($item.attr('data-bp-index') || '');
		var newIdx = (typeof fwUniqueIncrement === 'function') ? fwUniqueIncrement() : ('' + (+new Date()) + uidCounter);

		// Flush CodeMirror (custom CSS) back into its textarea, then sync every
		// live form value into the DOM so the clone keeps it.
		$item.find('.CodeMirror').each(function () { if (this.CodeMirror) { this.CodeMirror.save(); } });
		syncValuesToAttrs($item);

		var $clone = $item.clone();

		// Drop the rendered CodeMirror widget + any "initialized" markers so the
		// clone's editors / pickers re-initialise cleanly.
		$clone.find('.CodeMirror').remove();
		$clone.removeData('bp-init').removeAttr('data-bp-init');
		$clone.find('.initialized').removeClass('initialized');

		reindex($clone, oldIdx, newIdx);
		$clone.attr('data-bp-index', newIdx).addClass('is-collapsed');

		$item.after($clone);
		fwEvents.trigger('fw:options:init', { $elements: $clone });
	}

	// Push live values into DOM attributes so .clone() carries them.
	function syncValuesToAttrs($scope) {
		$scope.find('input, textarea, select').each(function () {
			if (this.tagName === 'TEXTAREA') {
				this.textContent = this.value;
			} else if (this.tagName === 'SELECT') {
				$(this).find('option').each(function () {
					if (this.selected) { this.setAttribute('selected', 'selected'); }
					else { this.removeAttribute('selected'); }
				});
			} else if (this.type === 'checkbox' || this.type === 'radio') {
				if (this.checked) { this.setAttribute('checked', 'checked'); }
				else { this.removeAttribute('checked'); }
			} else {
				this.setAttribute('value', this.value);
			}
		});
	}

	// Re-point the box index where it appears bounded by [ ] (names) or - - (ids),
	// first occurrence only — that's always the box-level segment.
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
