jQuery(document).ready(function ($) {
	var optionClass = '.fw-option-type-addable-option';

	function initSortable ($options) {
		try {
			$options.sortable('destroy');
		} catch (e) {
			// happens when sortable was not initialized before
		}

		var $opt = $options.first().closest(optionClass);
		if (! $opt.hasClass('is-sortable')) {
			return false;
		}

		var isMobile = $(document.body).hasClass('mobile');

		var sortableOpts = {
			items: '> tbody > tr',
			handle: 'td:first',
			cursor: 'move',
			placeholder: 'sortable-placeholder',
			delay: ( isMobile ? 200 : 0 ),
			distance: 2,
			tolerance: 'pointer',
			forcePlaceholderSize: true,
			axis: 'y',
			start: function(e, ui){
				// Update the height of the placeholder to match the moving item.
				{
					var height = ui.item.outerHeight();

					ui.placeholder.height(height);
				}
			},
			update: function(){
				$(this).closest(optionClass).trigger('change'); // for customizer
				fw.options.trigger.changeForEl($(this).closest(optionClass));
			}
		};

		// Cross-list mode: link every list in the same connect_group so rows can be
		// dragged between them. Drop the y-axis lock, and on receive re-key the moved
		// row's input name to THIS list (names are indexed fw_options[id][n], and the
		// form saves by native serialize(), so a moved row must adopt this list's
		// prefix + a fresh non-colliding index or it reverts on save).
		var group = $opt.attr('data-connect-group');
		if (group) {
			delete sortableOpts.axis;
			sortableOpts.connectWith = '.fw-ap-connect-' + group + ' table.fw-option-type-addable-option-options';
			sortableOpts.receive = function (e, ui) {
				var $inputs = ui.item.find('input, select, textarea');
				if ($inputs.length) {
					var $btn  = $opt.find('.fw-option-type-addable-option-add').first();
					var idx   = parseInt($btn.attr('data-increment'), 10) || 1;
					$btn.attr('data-increment', idx + 1);
					var ph    = $btn.attr('data-increment-placeholder');
					// Target name prefix (fw_options[thisId][n]) from THIS list's template.
					var tplName = $('<div>').html(
						$opt.find('.default-addable-option-template').first().attr('data-template') || ''
					).find('input, select, textarea').first().attr('name') || '';
					var pm = tplName.match(/^([^\]]*\][^\]]*\])/);
					var oldName = $inputs.first().attr('name') || '';
					var om = oldName.match(/^([^\]]*\][^\]]*\])/);
					if (pm && om) {
						var newPrefix = pm[1].split(ph).join(String(idx)); // fw_options[thisId][idx]
						var oldPrefix = om[1];
						$inputs.each(function () {
							var n = $(this).attr('name');
							if (n) { $(this).attr('name', n.replace(oldPrefix, newPrefix)); }
						});
					}
				}
				$opt.trigger('change');
				fw.options.trigger.changeForEl($opt);
			};
		}

		$options.sortable(sortableOpts);
	}

	var methods = {
		/** Make full/prefixed event name from short name */
		makeEventName: function (shortName) {
			return 'fw:option-type:addable-option:' + shortName;
		}
	};

	fwEvents.on('fw:options:init', function (data) {
		var $elements = data.$elements.find(optionClass +':not(.fw-option-initialized)');

		$elements.toArray().map(function (el) {
			// Trigger change when one of the underlying contexts change
			fw.options.on.change(function (data) {
				if (! $(data.context).is(
					'[data-fw-option-type="addable-option"] tr.fw-option-type-addable-option-option'
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
		$elements.on('click', optionClass +'-add', function(){
			var $button   = $(this);
			var $option   = $button.closest(optionClass);
			var $options  = $option.find(optionClass +'-options:first');
			var increment = parseInt($button.attr('data-increment'));

			var $newOption = $(
				$option.find('.default-addable-option-template:first').attr('data-template')
					.split( $button.attr('data-increment-placeholder') ).join( String(increment) )
			);

			// animation
			{
				$newOption.addClass('fw-animation-zoom-in');

				setTimeout(function(){
					$newOption.removeClass('fw-animation-zoom-in');
				}, 300);
			}

			$button.attr('data-increment', increment + 1);

			$options.append($newOption);

			// Re-render wp-editor
			if (
				window.fwWpEditorRefreshIds
				&&
				$newOption.find('.fw-option-type-wp-editor:first').length
			) {
				fwWpEditorRefreshIds(
					$newOption.find('.fw-option-type-wp-editor textarea:first').attr('id'),
					$newOption
				);
			}

			// remove focus form "Add" button to prevent pressing space/enter to add easy many options
			$newOption.find('input,select,textarea').first().focus();

			fwEvents.trigger('fw:options:init', {$elements: $newOption});

			$option.trigger(methods.makeEventName('option:init'), {$option: $newOption});
			fw.options.trigger.changeForEl($option);
		});

		/** Init Remove button */
		$elements.on('click', optionClass +'-remove', function(){
			fw.options.trigger.changeForEl($(this).closest(
				'[data-fw-option-type="addable-option"]'
			));

			$(this).closest(optionClass +'-option').remove();
		});

		$elements.each(function(){
			// Scope to THIS addable-option's own table (was $elements.find(':first'),
			// which only ever initialized the first list on a page with several — and
			// read the wrong element's connect_group). Per-element init is required for
			// cross-list connect mode to wire connectWith on every participating list.
			initSortable($(this).find(optionClass +'-options:first'));
		});

		$elements.addClass('fw-option-initialized');
	});

	fw.options.register('addable-option', {
		startListeningForChanges: $.noop,
		getValue: function (optionDescriptor) {
			var promise = $.Deferred();

			fw.whenAll(
				$(optionDescriptor.el).find(
					'table.fw-option-type-addable-option-options'
				).first().find(
					'> tbody > .fw-backend-options-virtual-context'
				).toArray().map(fw.options.getContextValue)
			).then(function (valuesAsArray) {
				promise.resolve({
					value: valuesAsArray.map(function (singleContextValue) {
						return Object.values(singleContextValue.value)[0];
					}),

					optionDescriptor: optionDescriptor
				})
			});

			return promise;
		}
	})
});
