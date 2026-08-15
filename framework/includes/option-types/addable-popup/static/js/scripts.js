(function ($, _, fwEvents, window) {
	var addablePopup = function () {
		var $this = $(this),
			$defaultItem = $this.find('.default-item:first'),
			// This column's item input-name template (e.g. fw_options[...][col_2][]).
			// Captured from the default item BEFORE it's removed, so it's known even
			// for an empty column. In connect mode a received item is re-keyed to this
			// so it serializes under THIS column (the form saves via $el.serialize()).
			itemName = ($defaultItem.find('input').attr('name') || ''),
			nodes = {
				$optionWrapper: $this,
				$addButton: $this.find('.add-new-item'),
				$itemsWrapper: $this.find('.items-wrapper'),
				getDefaultItem: function () {
					return $defaultItem.clone().removeClass('default-item').addClass('item');
				}
			},

			data = JSON.parse(
				JSON.parse(nodes.$optionWrapper.attr('data-for-js')).join('{{') // check option php class
			),

			utils = {
				modal: new fw.OptionsModal({
					title: data.title,
					options: data.options,
					size : data.size
				}),

				countItems: function () {
					return nodes.$itemsWrapper.find('> .item').length;
				},

				removeDefaultItem: function () {
					nodes.$optionWrapper.find('.default-item:first').remove();
				},

				toogleNodes : function(){
					utils.toogleItemsWrapper();
					utils.toogleAddButton();
					utils.toogleClone();
				},

				toogleItemsWrapper: function () {

					// In connect mode an empty column must stay visible so it remains a
					// drop target (a display:none list can't receive a dragged item).
					if (data.connect_group) {
						nodes.$itemsWrapper.show();
						return;
					}

					if (utils.countItems() === 0) {
						nodes.$itemsWrapper.hide();
					} else {
						nodes.$itemsWrapper.show();
					}

				},

				toogleAddButton: function(){
					if(data.limit !== 0 ){
						(utils.countItems() >= data.limit ) ?
							nodes.$addButton.hide() :
							nodes.$addButton.show();
					}
				},

				toogleClone: function(){
					if(data.limit !== 0 ){
						(utils.countItems() >= data.limit ) ?
							nodes.$itemsWrapper.addClass('hide-clone') :
							nodes.$itemsWrapper.removeClass('hide-clone');
					}
				},

				init: function () {
					utils.initItemsTemplates();
					utils.toogleNodes();
					utils.removeDefaultItem();
					utils.initSortable();
				},

				initSortable: function () {
					if (! nodes.$optionWrapper.hasClass('is-sortable')) {
						return false;
					}

					var sortableOpts = {
						items: '> .item',
						cursor: 'move',
						distance: 2,
						tolerance: 'pointer',
						axis: 'y',
						update: function(){
							nodes.$optionWrapper.trigger('change'); // for customizer
							fw.options.trigger.changeForEl(nodes.$optionWrapper);
						},
						start: function(e, ui){
							// Update the height of the placeholder to match the moving item.
							{
								var height = ui.item.outerHeight() - 1;

								ui.placeholder.height(height);
							}
						}
					};

					// Cross-list mode: link every list in the same connect_group so items
					// can be dragged between columns. Drop the y-axis lock (movement is now
					// sideways too), and on receive/remove re-sync toggles + fire change on
					// BOTH the giving and receiving lists so each re-collects by DOM order.
					if (data.connect_group) {
						delete sortableOpts.axis;
						sortableOpts.connectWith = '.fw-ap-connect-' + data.connect_group + ' .items-wrapper';
						sortableOpts.receive = function (e, ui) {
							// Re-key the received item's input NAME to THIS column so the
							// native form serialize() saves it here, not its origin column.
							// Without this the item visually moves but reverts on save.
							if (itemName) {
								ui.item.find('input').attr('name', itemName);
							}
							utils.toogleNodes();
							nodes.$optionWrapper.trigger('change');
							fw.options.trigger.changeForEl(nodes.$optionWrapper);
						};
						sortableOpts.remove = function () {
							utils.toogleNodes();
						};
					}

					nodes.$itemsWrapper.sortable(sortableOpts);
				},

				initItemsTemplates: function () {
					var $items = nodes.$itemsWrapper.find('> .item');

					if ($items.length > 0) {
						$items.each(function () {
							utils.editItem($(this), JSON.parse($(this).find('input').val()));
						});
					}
				},

				createItem: function (values) {
					var $clonedItem = nodes.getDefaultItem(),
						$clonedInput = $clonedItem.find('.input-wrapper');

					var $inputTemplate = $(
						( $clonedInput.html() || '' ).trim()
							.split(
								nodes.$addButton.attr('data-increment-placeholder')
							)
							.join(utils.countItems())
					);

					$inputTemplate.find('input').attr('value', JSON.stringify(values));

					$clonedInput.children().first().replaceWith($inputTemplate);

					var template = '';

					try {
						/**
						 * may throw error in in template is used an option id
						 * added after some items was already saved
						 */
						values._context = $clonedItem.find('.content');

						template = fw.template(
							( data.template || '' ).trim(),
							{
								evaluate: /\{\{([\s\S]+?)\}\}/g,
								interpolate: /\{\{=([\s\S]+?)\}\}/g,
								escape: /\{\{-([\s\S]+?)\}\}/g
							}
						)(values);
					} catch (e) {
						template = '[Template Error] '+ e.message;
					}

					$clonedItem.find('.content').html(template);

					return $clonedItem;
				},

				addNewItem: function (values) {
					nodes.$itemsWrapper.append(utils.createItem(values));
				},

				editItem: function (item, values) {
					item.replaceWith(utils.createItem(values));
				}
			};

		nodes.$itemsWrapper.on('click', '.delete-item', function (e) {
			e.stopPropagation();
			e.preventDefault();
			$(this).closest('.item').remove();
			utils.toogleNodes();
			nodes.$optionWrapper.trigger('change'); // for customizer
			fw.options.trigger.changeForEl(nodes.$optionWrapper);
		});

		nodes.$itemsWrapper.on('click', '.clone-item', function (e) {
			e.stopPropagation();
			var $item  = $(this).closest('.item');
			var $vals  = JSON.parse($($item).find('input').val());
			utils.addNewItem($vals);
			utils.toogleNodes();
			nodes.$optionWrapper.trigger('change'); // for customizer
			fw.options.trigger.changeForEl(nodes.$optionWrapper);
		});

		nodes.$itemsWrapper.on('click', '> .item', function (e) {
			e.preventDefault();

			var values = {};
			var $input = $(this).find('input');

			if ($input.length) {
				values = JSON.parse($input.val());
			}

			utils.modal.set('edit', true);
			utils.modal.set('values', values, {silent: true});
			utils.modal.set('itemRef', $(this));
			utils.modal.open();
		});

		nodes.$addButton.on('click', function () {
			utils.modal.set('edit', false);
			utils.modal.set('values', {}, {silent: true});
			utils.modal.open();
		});

		utils.modal.on('change:values', function (modal, values) {
			if (!modal.get('edit')) {
				utils.addNewItem(values);
				utils.toogleNodes();
			} else {
				utils.editItem(utils.modal.get('itemRef'), values);
			}

			nodes.$optionWrapper.trigger('change'); // for customizer
			fw.options.trigger.changeForEl(nodes.$optionWrapper);
		});

		[
			'open',
			'render',
			'close'
		].forEach(function (ev) {
			// Plain wrapper rather than a bind(): triggerEvent reads `this`
			// (the modal the event fired on), which bind() would replace.
			utils.modal.on(ev, function (modal) {
				triggerEvent.call(this, ev, modal);
			});

			function triggerEvent (eventName, modal) {
				eventName = 'fw:option-type:addable-popup:options-modal:' + eventName;
				fwEvents.trigger(eventName, { modal: this });
			}
		});

		$this.on('remove', function(){ // fixes https://github.com/ThemeFuse/Unyson/issues/2167
			utils.modal.frame.$el.closest('.fw-modal').remove(); // remove modal from DOM
			nodes = data = utils = undefined; // clear memory
		});

		utils.init();
	};

	fwEvents.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option-type-addable-popup:not(.fw-option-initialized)').each(addablePopup)
			.addClass('fw-option-initialized');
	});

	fw.options.register('addable-popup', {
		getValue: function (optionDescriptor) {
			var promise = $.Deferred();

			fw.whenAll(
				$(optionDescriptor.el).find(
					'> .fw-option-type-addable-popup > .items-wrapper'
				).first().find(
					'> .item.fw-backend-options-virtual-context'
				).toArray().map(fw.options.getContextValue)
			).then(function (valuesAsArray) {
				promise.resolve({
					// Was _.compose(JSON.parse, _.first, _.values, _.property('value')):
					// take the context's `value` hash, grab its first entry, parse it.
					value: valuesAsArray.map(function (singleContextValue) {
						return JSON.parse(
							Object.values(singleContextValue.value)[0]
						);
					}),

					optionDescriptor: optionDescriptor
				})
			});

			return promise;
		},

		startListeningForChanges: $.noop
	});

})(jQuery, _, fwEvents, window);
