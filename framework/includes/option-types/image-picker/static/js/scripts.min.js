(function(){
	var optionTypeClass = 'fw-option-type-image-picker';
	var eventNamePrefix = 'fw:option-type:image-picker:';

	fw.options.register('image-picker', {
		startListeningForChanges: jQuery.noop,
		getValue: function (optionDescriptor) {
			var select = optionDescriptor.el.querySelector('select');

			if (select && select.multiple) {
				var hidden = optionDescriptor.el.querySelector('input.fw-image-picker-multiple-value');
				var val = [];

				if (hidden) {
					try { val = JSON.parse(hidden.value) || []; } catch (e) { val = []; }
				}

				return {
					value: val,
					optionDescriptor: optionDescriptor
				}
			}

			return {
				value: select.value,
				optionDescriptor: optionDescriptor
			}
		}
	});

	jQuery(document).ready(function ($) {
		// Live filter for searchable image-pickers: show only tiles whose label matches, and hide a
		// category (optgroup) header when none of its tiles remain visible.
		function filterImagePicker($wrap, q) {
			q = (q || '').toLowerCase().replace(/\s+/g, ' ').replace(/^ | $/g, '');
			var $sel = $wrap.find('.image_picker_selector');
			$sel.find('li').each(function () {
				var $li = $(this);
				if (!$li.children('.thumbnail').length) { return; } // skip group wrappers / titles
				var label = $li.find('.thumbnail p').text().toLowerCase();
				this.style.display = (!q || label.indexOf(q) > -1) ? '' : 'none';
			});
			$sel.find('li.group_title').each(function () {
				var $ul = $(this).parent(), $wrapLi = $ul.parent();
				var vis = $ul.children('li').filter(function () {
					return $(this).children('.thumbnail').length && this.style.display !== 'none';
				}).length > 0;
				if ($wrapLi.is('li')) { $wrapLi[0].style.display = vis ? '' : 'none'; }
			});
		}

		// Tabs layout: build a tab bar (All + one per category) from the rendered groups.
		function buildTabs($wrap) {
			var $sel = $wrap.find('.image_picker_selector');
			if ($wrap.children('.fw-image-picker-tabs').length) { return; }
			var cats = [];
			$sel.find('li.group_title').each(function () {
				var c = $(this).text().replace(/\s+/g, ' ').replace(/^ | $/g, '');
				if (c && cats.indexOf(c) < 0) { cats.push(c); }
			});
			if (!cats.length) { return; }
			var $bar = $('<div class="fw-image-picker-tabs"></div>');
			// First category is the default active tab (not "All").
			cats.forEach(function (c, i) {
				$bar.append($('<button type="button" class="fw-ip-tab' + (i === 0 ? ' is-active' : '') + '"></button>').attr('data-cat', c).text(c));
			});
			// "All" always sits at the end.
			$bar.append($('<button type="button" class="fw-ip-tab" data-cat="__all__"></button>').text('All'));
			var $anchor = $wrap.find('.fw-image-picker-search');
			if ($anchor.length) { $anchor.after($bar); } else { $sel.before($bar); }
			$wrap.attr('data-active-cat', cats[0]);
			$bar.on('click', '.fw-ip-tab', function () {
				$bar.find('.fw-ip-tab').removeClass('is-active');
				$(this).addClass('is-active');
				$wrap.attr('data-active-cat', $(this).attr('data-cat'));
				applyTabsView($wrap);
			});
		}

		// Show only the active category (unless "All"), further filtered by the search box. In "All"
		// the groups stay stacked with headers; a single category hides its header (the tab names it).
		function applyTabsView($wrap) {
			var $sel = $wrap.find('.image_picker_selector');
			var cat  = $wrap.attr('data-active-cat') || '__all__';
			var q = ($wrap.find('.fw-image-picker-search').val() || '').toLowerCase().replace(/\s+/g, ' ').replace(/^ | $/g, '');
			// While searching, look across ALL categories (grouped with headers); when the box is
			// cleared, fall back to the active category tab.
			var isAll = (cat === '__all__') || (q !== '');
			function matches(li) {
				var label = $(li).find('.thumbnail p').text().toLowerCase();
				return !q || label.indexOf(q) > -1;
			}
			$sel.find('li.group_title').each(function () {
				var groupCat = $(this).text().replace(/\s+/g, ' ').replace(/^ | $/g, '');
				var $ul = $(this).parent(), $wrapLi = $ul.parent();
				var showGroup = isAll || groupCat === cat;
				var anyVisible = false;
				$ul.children('li').each(function () {
					if (!$(this).children('.thumbnail').length) { return; }
					var vis = showGroup && matches(this);
					this.style.display = vis ? '' : 'none';
					if (vis) { anyVisible = true; }
				});
				this.style.display = (isAll && anyVisible) ? '' : 'none'; // header shown only in "All"
				if ($wrapLi.is('li')) { $wrapLi[0].style.display = anyVisible ? '' : 'none'; }
			});
			$sel.children('li').each(function () {
				if (!$(this).children('.thumbnail').length) { return; }
				this.style.display = (isAll && matches(this)) ? '' : 'none';
			});
		}

		// Delegated so it works no matter when/where the picker is rendered (e.g. inside a
		// multi-picker popover panel that inits after this script, or re-rendered dynamically).
		$(document).on('input', '.fw-image-picker--searchable .fw-image-picker-search', function () {
			var $wrap = $(this).closest('.fw-image-picker--searchable');
			if ($wrap.hasClass('fw-image-picker--tabs')) { applyTabsView($wrap); }
			else { filterImagePicker($wrap, this.value); }
		});

		/** Init image_picker options */
		fwEvents.on('fw:options:init', function (data) {
			var $elements = data.$elements.find('.'+ optionTypeClass +':not(.fw-option-initialized)');

			if (!$elements.length) {
				return;
			}

			$elements.each(function () {
				var $wrap = $(this);
				// Searchable / tabbed pickers show tile labels (so name search + category tabs read
				// well); every other image-picker keeps its exact previous behaviour (no labels).
				var searchable = $wrap.hasClass('fw-image-picker--searchable');
				var tabbed     = $wrap.hasClass('fw-image-picker--tabs');
				var labeled    = $wrap.hasClass('fw-image-picker--labeled');

				$wrap.find('select').imagepicker({
					show_label: searchable || tabbed || labeled,
					clicked: function(options) {
						var $this = $(this);
						var value = $this.val();
						var data  = $this.find('option[value="'+ value +'"]').data('extra-data');

						$this.closest('.'+ optionTypeClass).trigger(eventNamePrefix +'clicked', {
							options : options,
							value   : value,
							data    : (typeof data !== 'undefined') ? data : false
						});
					},
					changed: function (oldValues, newValues) {
						var $this = $(this);
						var isMultiple = $this.attr('multiple');
						var cleanValues = $.grep(newValues || [], function (v) {
							return v !== '' && v != null;
						});

						if (isMultiple) {
							// Keep the hidden input (the value that submits) in sync.
							$this.closest('.' + optionTypeClass)
								.find('input.fw-image-picker-multiple-value')
								.val(JSON.stringify(cleanValues));
						}

						fw.options.trigger.changeForEl($this[0], {
							value: isMultiple ? cleanValues : newValues[0]
						});

						$this.closest('.'+ optionTypeClass).trigger(eventNamePrefix +'changed', {
							oldValues : oldValues,
							newValues : newValues
						});
					}
				});

				// Hide the value-holder "off" tile (the 1×1 transparent image emitted by _render to keep
				// the <select> value when the selection has no tile). CSS :has() also hides it; this is
				// the fallback for older browsers.
				$wrap.find('.image_picker_selector img[src^="data:image/gif;base64,R0lGOD"]').closest('li').hide();

				// Click-again-to-deselect (popover-hosted image-pickers). The image-picker library
				// only auto-clears on re-click when the <select> carries an *implicit blank* option,
				// which our pickers don't emit — so wire the natural toggle here. Scoped to a popover
				// context (the multi-picker popover `.fw-mp-pop` or the `popover` option type), and
				// single-select only (a `multiple` picker already toggles per tile). Clearing prefers
				// the picker's own "none"/"off" choice when it has one, else '' — either of which
				// existing renderers already treat as "no effect".
				if (this.addEventListener && !$wrap.attr('multiple')
					&& $wrap.closest('.fw-mp-pop, .fw-option-type-popover, .fw-backend-option-type-popover').length) {
					var $ipSelect = $wrap.find('select').first();
					if ($ipSelect.length && !$ipSelect.attr('multiple')) {
						// Hide the redundant "none"/"off" TILE in popover pickers. That choice STAYS a
						// real <option> in the value model — it's the default value and the target
						// click-again-to-deselect clears back to — it just shouldn't show as a pickable
						// tile now that deselect is the way to clear. Keeping it a real choice (rather
						// than a synthetic off-tile) is what makes the picker reliably hold "none" across
						// the modal's collect / re-render / lazy-tab cycles: without it the <select>
						// falls back to the FIRST tile and any save silently stores that (e.g. a Section
						// storing "dots" the moment any option changed). Group-agnostic: walk the
						// library's own option objects (works with tabbed / optgroup layouts too).
						var _picker = $ipSelect.data('picker');
						if (_picker && _picker.picker_options) {
							jQuery.each(_picker.picker_options, function (_i, o) {
								var v = (o && o.option && o.option.val) ? o.option.val() : null;
								if ((v === 'none' || v === 'off') && o.node) { jQuery(o.node).hide(); }
							});
						}
						var clearedValue = function () {
							if ($ipSelect.find('option[value="none"]').length) { return 'none'; }
							if ($ipSelect.find('option[value="off"]').length)  { return 'off'; }
							return '';
						};
						var ipEl = this;
						// Capture phase: read the tile's selected state BEFORE the library's own
						// thumbnail handler re-affirms it, so a re-click of the ACTIVE tile (→ deselect)
						// is distinguishable from the first click of a new tile (→ normal select).
						ipEl.addEventListener('click', function (e) {
							var thumb = (e.target && e.target.closest) ? e.target.closest('.thumbnail') : null;
							if (!thumb || !ipEl.contains(thumb)) { return; }
							if (thumb.className.indexOf('selected') === -1) { return; } // new tile → select
							setTimeout(function () {
								var val = clearedValue();
								var picker = $ipSelect.data('picker');
								$ipSelect.val(val);
								if (picker && typeof picker.sync_picker_with_select === 'function') {
									picker.sync_picker_with_select();
								}
								if (fw.options && fw.options.trigger && fw.options.trigger.changeForEl) {
									fw.options.trigger.changeForEl($ipSelect[0], { value: val });
								}
								$ipSelect.trigger('change'); // drives multi-picker reveal / popover summary
							}, 0);
						}, true);
					}
				}

				$wrap.find('.image_picker_selector .image_picker_image').each(function(){
					var $this = $(this);
					var largeImageAttr = $this.data('large-img-attr');

					if (largeImageAttr) {
						$this.qtip({
							content: $('<div></div>').append(
								$('<img/>').attr(largeImageAttr).addClass(optionTypeClass +'-large-image')
							).html(),
							position: {
								at: 'top center',
								my: 'bottom center',
								viewport: $('body'),
								adjust: {
									y: -5
								}
							},
							style: {
								classes: 'qtip-fw',
								tip: {
									width: 12,
									height: 5
								}
							},
							show: {
								effect: function(offset) {
									$(this).fadeIn(300);

									// fix tip position
									setTimeout(function(){
										offset.elements.tooltip.css('top',
											(parseInt(offset.elements.tooltip.css('top')) + 5) + 'px'
										);
									}, 12);
								}
							},
							hide: {
								effect: function() {
									$(this).fadeOut(300);
								}
							}
						});
					}
				});

				if (tabbed) { buildTabs($wrap); applyTabsView($wrap); }
			});

			$elements.addClass('fw-option-initialized');
		});
	});
})();
