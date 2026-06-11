jQuery(document).ready(function ($) {
	var optionTypeClass = '.fw-option-type-addable-box';

	var methods = {
		/** Make full/prefixed event name from short name */
		makeEventName: function(shortName) {
			return 'fw:option-type:addable-box:'+ shortName;
		},
		/** Create object with useful data about box for event data */
		getBoxDataForEvent: function($box) {
			var data = {};

			data.$box       = $box;
			data.$controls  = $box.find('.fw-option-box-controls:first');
			data.$options   = $box.find('.fw-option-box-options:first');

			data.$box       = $box.find('.fw-postbox:first');
			data.$title     = data.$box.find('> .hndle:first');
			data.$titleText = data.$title.find('> span:first');

			return data;
		},
		/** Make boxes to be sortable */
		reInitSortable: function ($boxes) {
			try {
				$boxes.sortable('destroy');
			} catch (e) {
				// happens when sortable was not initialized before
			}

			if (!$boxes.first().closest(optionTypeClass).hasClass('is-sortable')) {
				return false;
			}

			var isMobile = $(document.body).hasClass('mobile');
			var twoCol   = $boxes.closest('.fw-preset-2col').length > 0;

			$boxes.sortable({
				items: '> .fw-option-box',
				handle: '.hndle:first',
				cursor: 'move',
				placeholder: 'sortable-placeholder',
				delay: ( isMobile ? 200 : 0 ),
				distance: 2,
				tolerance: 'pointer',
				forcePlaceholderSize: true,
				axis: twoCol ? false : 'y',
				start: function(e, ui){
					// Update the height of the placeholder to match the moving item.
					{
						var height = ui.item.outerHeight();

						height -= 2; // Subtract 2 for borders

						ui.placeholder.height(height);
					}
				},
				update: function(){
					var optionType = $(this).closest(optionTypeClass);

					optionType.trigger('change'); // for customizer

					fw.options.trigger.changeForEl(optionType);
				}
			});
		},

		/** Init boxes controls */
		initControls: function ($boxes) {

			$boxes.find('.fw-option-box-control').on('mouseover', function () {
				$(this).off('click');
			})

			$boxes
				.find('.fw-option-box-controls:not(.initialized)')
				.on('click', '.fw-option-box-control', function(e){

					e.preventDefault();
					e.stopPropagation(); // prevent open/close of the box (when the link is in box title bar)

					var $control  = $(this);
					var controlId = $control.attr('data-control-id');

					switch (controlId) {
						case 'duplicate':
							methods.duplicateBox($control.closest('.fw-option-box'));
							break;
						case 'delete':
							var $option = $control.closest(optionTypeClass);

							$control.closest('.fw-option-box').remove();

							methods.checkLimit($option);
							methods.updateHasBoxesClass($option);
							methods.relayoutPresetGrid($option.find('.fw-option-boxes').get(0));

							fw.options.trigger.changeForEl($option);

							break;
						default:
							// custom control. trigger event for others to handle this
							$control.closest(optionTypeClass).trigger(
								methods.makeEventName('control:click'), {
									controlId: controlId,
									$control: $control,
									box: methods.getBoxDataForEvent($control.closest('.fw-option-box'))
								}
							);
					}
				})
				.addClass('initialized')
				.find('.fw-option-box-control').off('click'); // remove e.stopPropagation() added by /wp-admin/js/postbox.min.js
		},
		checkLimit: function($option) {
			var $button = $option.find('> .fw-option-boxes-controls .fw-option-boxes-add-button');
			var limit = fw.intval($button.attr('data-limit'));

			if (limit > 0 && $option.find('> .fw-option-boxes > .fw-option-box').length >= limit) {
				$button.addClass('fw-hidden');
			} else {
				$button.removeClass('fw-hidden');
			}
		},
		updateHasBoxesClass: function($option) {
			$option[
				$option.find('> .fw-option-boxes > .fw-option-box:first').length
				? 'addClass' : 'removeClass'
			]('has-boxes');
		},

		/**
		 * Duplicate a box: clone it as a NEW entry (fresh increment index) carrying
		 * its current values. A plain clone doesn't copy live form values, so we sync
		 * each input's value into its attribute first; names are then re-pointed by
		 * prefix (clean) and ids/for by the per-box index.
		 */
		duplicateBox: function ($box) {
			var $option   = $box.closest(optionTypeClass);
			var $boxes    = $option.find('.fw-option-boxes:first');
			var $button   = $option.find('> .fw-option-boxes-controls .fw-option-boxes-add-button');
			var increment = parseInt($button.attr('data-increment'));
			$button.attr('data-increment', increment + 1);

			var oldPrefix = $box.attr('data-name-prefix') || '';
			var lastIdx   = oldPrefix.match(/\[([^\[\]]+)\]\s*$/);
			var oldIdx    = lastIdx ? lastIdx[1] : '';
			var newIdx    = String(increment);
			var newPrefix = oldPrefix.replace(/\[[^\[\]]+\]\s*$/, '[' + newIdx + ']');

			methods.syncValues($box);

			var $clone = $box.clone();
			$clone.find('.CodeMirror').remove();
			$clone.removeClass('fw-option-initialized initialized').removeAttr('data-values');
			$clone.find('.initialized').removeClass('initialized');

			if (oldPrefix) {
				$clone.attr('data-name-prefix', newPrefix);
				$clone.find('[name]').each(function () {
					var n = this.getAttribute('name');
					if (n && n.indexOf(oldPrefix) === 0) { this.setAttribute('name', newPrefix + n.slice(oldPrefix.length)); }
				});
			}
			if (oldIdx) {
				var esc = oldIdx.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
				var re  = new RegExp('([\\[\\-])' + esc + '([\\]\\-])');
				$clone.find('[id], [for]').each(function () {
					var el = this;
					['id', 'for'].forEach(function (a) {
						var v = el.getAttribute(a);
						if (v && re.test(v)) { el.setAttribute(a, v.replace(re, '$1' + newIdx + '$2')); }
					});
				});
			}

			$box.after($clone);
			methods.initControls($clone);
			if ($option.hasClass('is-sortable')) { methods.reInitSortable($boxes); }
			fwEvents.trigger('fw:options:init', { $elements: $clone });
			methods.checkLimit($option);
			methods.updateHasBoxesClass($option);
			methods.relayoutPresetGrid($boxes.get(0));
			fw.options.trigger.changeForEl($boxes);
		},

		/** Push live form values into DOM attributes so .clone() carries them. */
		syncValues: function ($scope) {
			$scope.find('input, textarea, select').each(function () {
				if (this.tagName === 'TEXTAREA') { this.textContent = this.value; }
				else if (this.tagName === 'SELECT') { $(this).find('option').each(function () { if (this.selected) { this.setAttribute('selected', 'selected'); } else { this.removeAttribute('selected'); } }); }
				else if (this.type === 'checkbox' || this.type === 'radio') { if (this.checked) { this.setAttribute('checked', 'checked'); } else { this.removeAttribute('checked'); } }
				else { this.setAttribute('value', this.value); }
			});
		},

		/**
		 * Column-major (top-to-bottom) layout for the .fw-preset-2col preset lists.
		 * The CSS base is a row-major grid fallback; here we upgrade it to fill each
		 * column top-to-bottom by giving the grid an explicit column + row count and
		 * grid-auto-flow: column. (We can't do this in pure CSS because the column
		 * count is responsive and the row count depends on the box count.) Grid is
		 * used instead of CSS multi-column so the absolutely-positioned expanded
		 * panel isn't fragmented/clipped at a column break.
		 */
		relayoutPresetGrid: function (el) {
			if (!el) { return; }
			var $g = $(el);
			if (!$g.closest('.fw-preset-2col').length) { return; }

			var n    = $g.children('.fw-option-box').length;
			var w    = el.clientWidth;
			var cols = (w > 0 && n > 1) ? Math.min(n, Math.max(1, Math.floor(w / 360))) : 1;

			// No-op guard (also stops the ResizeObserver from looping on the
			// height change our own relayout causes): re-run only when the
			// effective layout (columns x box-count) actually changes.
			var key = cols + 'x' + n;
			if ($g.attr('data-fw-grid') === key) { return; }
			$g.attr('data-fw-grid', key);

			if (cols < 2) {
				$g.css({ gridAutoFlow: '', gridTemplateColumns: '', gridTemplateRows: '' });
			} else {
				$g.css({
					gridAutoFlow: 'column',
					gridTemplateColumns: 'repeat(' + cols + ', minmax(0, 428px))',
					gridTemplateRows: 'repeat(' + Math.ceil(n / cols) + ', auto)'
				});
			}
		},

		/** Watch a preset grid so it re-lays out on resize / tab-show. */
		observePresetGrid: function (el) {
			if (!el || !$(el).closest('.fw-preset-2col').length) { return; }
			if (typeof ResizeObserver !== 'undefined' && !el.fwPresetRO) {
				el.fwPresetRO = new ResizeObserver(function () { methods.relayoutPresetGrid(el); });
				el.fwPresetRO.observe(el);
			}
			methods.relayoutPresetGrid(el);
		}
	};

	/**
	 * Update box title using the 'template' option parameter and box option values
	 */
	var titleUpdater = {
		pendingClass: 'fw-option-type-addable-box-pending-title-update',
		isBusy: false,
		template: function(template, vars) {
			try {
				return _.template(
					$.trim(template),
					undefined,
					{
						evaluate: /\{\{([\s\S]+?)\}\}/g,
						interpolate: /\{\{=([\s\S]+?)\}\}/g,
						escape: /\{\{-([\s\S]+?)\}\}/g
					}
				)(vars);
			} catch (e) {
				return '[Template Error] '+ e.message;
			}
		},
		/**
		 * Update the given box title, or find a pending box
		 * @public
		 */
		update: function($box) {
			if (this.isBusy) {
				return;
			}

			if (typeof $box == 'undefined') {
				$box = $(optionTypeClass +' .'+ this.pendingClass +':first');
			}

			if (!$box.length) {
				return;
			}

			var data = JSON.parse(
				$box.closest(optionTypeClass).attr('data-for-js')
			);

			data.template = $.trim(data.template);

			if (!data.template.length) {
				delete data;
				return;
			}

			var $dataWrapper = $box.closest('.fw-option-box');

			var values = $dataWrapper.attr('data-values');

			if (values) {
				// box after refresh
				$dataWrapper.removeAttr('data-values');

				$box.removeClass(titleUpdater.pendingClass);

				var jsonParsedValues = JSON.parse(values) || {};

				$box.find( '.postbox-header > .hndle span:not([class])' ).first().html(
					this.template(data.template, $.extend({}, {o: jsonParsedValues}, jsonParsedValues))
				);

				delete data;
				delete jsonParsedValues;
				this.update();
				return;
			}

			this.isBusy = true;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: [
					'action=fw_backend_options_get_values',
					'_nonce='+ encodeURIComponent(typeof _fw_backend_options_localized !== 'undefined' ? _fw_backend_options_localized.nonce : ''),
					'options='+ encodeURIComponent(JSON.stringify(data.options)),
					'name_prefix='+ encodeURIComponent($dataWrapper.attr('data-name-prefix')),
					$box.find('> .inside > .fw-option-box-options').find('input, select, textarea').serialize()
				].join('&'),
				dataType: 'json'
			}).done(_.bind(function (response, status, xhr) {
				this.isBusy = false;
				$box.removeClass(titleUpdater.pendingClass);

				var template = '';

				if (response.success) {
					template = this.template(data.template, $.extend({}, {o: response.data.values}, response.data.values));
				} else {
					template = '[Ajax Error] '+ response.data.message
				}

				$box.find( '.postbox-header > .hndle span:not([class])' ).first().html( template );

				delete data;

				this.update();
			}, this)).fail(_.bind(function (xhr, status, error) {
				this.isBusy = false;
				$box.removeClass(titleUpdater.pendingClass);

				$box.find( '.postbox-header > .hndle span:not([class])' ).first().text( '[Server Error] ' + status + ': ' + error.message );

				delete data;

				this.update();
			}, this));
		}
	};

	fwEvents.on('fw:options:init', function (data) {
		var $elements = data.$elements.find(optionTypeClass +':not(.fw-option-initialized)');

		$elements.toArray().map(function (el) {
			fw.options.on.change(function (data) {
				if (! $(data.context).is(
					'[data-fw-option-type="addable-box"] .fw-option-boxes > .fw-option-box'
				)) {
					return;
				}

				// Listen to just its own virtual contexts
				if (! el.contains(data.context)) {
					return;
				}

				fw.options.trigger.changeForEl(el);
			});
		});

		/** Init Add button */
		$elements.on('click', '> .fw-option-boxes-controls > .fw-option-boxes-add-button', function(){
			var $button   = $(this);
			var $option   = $button.closest(optionTypeClass);
			var $boxes    = $option.find('.fw-option-boxes:first');
			var increment = parseInt($button.attr('data-increment'));

			var $newBox = $(
				$option.find('> .default-box-template').attr('data-template')
					.split( $button.attr('data-increment-placeholder') ).join( String(increment) )
			);

			$button.attr('data-increment', increment + 1);

			// animation
			{
				$newBox.addClass('fw-animation-zoom-in');

				setTimeout(function(){
					$newBox.removeClass('fw-animation-zoom-in');
				}, 300);
			}

			$boxes.append($newBox);

			// Re-render wp-editor
			if (
				window.fwWpEditorRefreshIds
				&&
				$newBox.find('.fw-option-type-wp-editor:first').length
			) {
				$newBox.find(
					'.fw-option-type-wp-editor textarea'
				).toArray().map(function (textarea) {
					fwWpEditorRefreshIds(
						$(textarea).attr('id'),
						$newBox
					);
				});
			}

			methods.initControls($newBox);

			if ($option.hasClass('is-sortable')) {
				methods.reInitSortable($boxes);
			}

			// remove focus form "Add" button to prevent pressing space/enter to add easy many boxes
			$newBox.find('input,select,textarea').first().focus();

			fwEvents.trigger('fw:options:init', {$elements: $newBox});

			var box = methods.getBoxDataForEvent($newBox);

			$option.trigger(methods.makeEventName('box:init'), {box: box});

			methods.checkLimit($option);
			methods.updateHasBoxesClass($option);
			methods.relayoutPresetGrid($boxes.get(0));

			fw.options.trigger.changeForEl($boxes);
		});

		// close postboxes and attach event listener
		$elements.find('> .fw-option-boxes > .fw-option-box > .fw-postbox').addClass('closed');

		$elements.on('fw:box:close', '> .fw-option-boxes > .fw-option-box > .fw-postbox', function(){
			// later a script will pick it by this class and will update the title via ajax
			$(this).addClass(titleUpdater.pendingClass);

			/*
			$(this).find('> .hndle span:not([class])').first().html(
				$('<img>').attr('src', fw.img.loadingSpinner)
			);
			*/

			titleUpdater.update($(this));
		});

		// Accordion for preset lists (.fw-preset-2col): an expanded box shows a
		// floating panel that overlaps the boxes beneath it, so opening one box
		// closes any other open box in the SAME list — panels never stack.
		$elements.on('fw:box:open', '> .fw-option-boxes > .fw-option-box > .fw-postbox', function(){
			var $postbox = $(this);

			if (!$postbox.closest('.fw-preset-2col').length) {
				return;
			}

			$postbox.closest('.fw-option-boxes')
				.find('> .fw-option-box > .fw-postbox')
				.not(this)
				.filter(':not(.closed)')
				.addClass('closed')
				.trigger('fw:box:close');
		});

		methods.initControls($elements);

		$elements.each(function(){
			methods.checkLimit($(this));
		});

		// Column-major (top-to-bottom) grid layout for .fw-preset-2col lists.
		$elements.find('> .fw-option-boxes').each(function () {
			methods.observePresetGrid(this);
		});

		$elements.addClass('fw-option-initialized');

		setTimeout(function(){
			// executed later, after .sortable('destroy') from backend-options.js
			methods.reInitSortable($elements.find('.fw-option-boxes'));

			// execute box:init event for existing boxes
			$elements.each(function(){
				var $option = $(this);

				$option.find('> .fw-option-boxes > .fw-option-box').each(function(){
					$option.trigger(methods.makeEventName('box:init'), {
						box: methods.getBoxDataForEvent($(this))
					});
				})
			});
		}, 100);

		titleUpdater.update();
	});

	fw.options.register('addable-box', {
		startListeningForChanges: $.noop,
		getValue: function (optionDescriptor) {
			var promise = $.Deferred();

			fw.whenAll(
				$(optionDescriptor.el).find(
					'.fw-option-boxes'
				).first().find(
					'> .fw-option-box.fw-backend-options-virtual-context'
				).toArray().map(fw.options.getContextValue)
			).then(function (valuesAsArray) {
				promise.resolve({
					value: valuesAsArray.map(function (singleContextValue) {
						return singleContextValue.value;
					}),

					optionDescriptor: optionDescriptor
				})
			});

			return promise;
		}
	})
});
