(function($) {
	// Bind via a stable hook class rendered by the shared view.php, so the
	// picker works for every id this engine is registered under — 'icon-v3'
	// and the reclaimed 'icon'. (Previously '.fw-option-type-icon-v3', which
	// only matched the 'icon-v3' id.)
	var $rootClass = '.fw-icon-v3-picker';

	/**
	 * We'll have this HTML structure
	 *
	 * <div class="fw-icon-v3-preview-wrapper>
	 *   <div class="fw-icon-v3-preview">
	 *     <i></i>
	 *     <button class="fw-icon-v3-remove-icon"></button>
	 *   </div>
	 *
	 *   <button class="fw-icon-v3-trigger-modal">Add Icon</div>
	 * </div>
	 */

	fwEvents.on('fw:options:init', function(data) {
		data.$elements.find($rootClass).toArray().map(renderSinglePreview);
	});

	$(document).on('click', $rootClass + ' .fw-icon-v3-remove-icon', removeIcon);
	$(document).on('click', $rootClass + ' .fw-icon-v3-trigger-modal', getNewIcon);
	$(document).on('click', $rootClass + ' .fw-icon-v3-preview', getNewIcon);

	/**
	 * For debugging purposes
	 */
	function refreshEachIcon() {
		$($rootClass).toArray().map(refreshSinglePreview);
	}

	function getNewIcon(event) {
		event.preventDefault();

		var $root = $(this).closest($rootClass);
		var modalSize = $root.attr('data-fw-modal-size');

		/**
		 * fw.OptionsModal should execute it's change:values callbacks
		 * only if the picker was changed. That's why we introduce unique-id
		 * for each picker.
		 */
		if (!$root.data('unique-id')) {
			$root.data('unique-id', fw.randomMD5());
		}

		fwOptionTypeIconV2Instance.set('size', modalSize);

		fwOptionTypeIconV2Instance
			.open(getDataForRoot($root))
			.then(function(data) {
				setDataForRoot($root, data);
			})
			.fail(function() {
				// modal closed without save
			});
	}

	function removeIcon(event) {
		event.preventDefault();
		event.stopPropagation();

		setDataForRoot($(this).closest($rootClass), {
			type: 'none',
			'icon-class': '',
			'url': '',
			'attachment-id': ''
		});
	}

	function renderSinglePreview($root) {
		$root = $($root);

		/**
		* Skip element if it's already activated
		*/
		if ($root.hasClass('fw-activated')) {
			return;
		}

		$root.addClass('fw-activated');

		var $wrapper = $('<div>', {
			class: 'fw-icon-v3-preview-wrapper',
			'data-icon-type': getDataForRoot($root)['type'],
		});

		var $preview = $('<div>', {
			class: 'fw-icon-v3-preview',
		})
			.append($('<i>'))
			.append(
				$('<a>', {
					class: 'fw-icon-v3-remove-icon dashicons fw-x',
					html: '',
				})
			);

		$wrapper.append($preview).append(
			$('<button>', {
				class: 'fw-icon-v3-trigger-modal button-secondary button-large',
				type: 'button',
				html: fw_icon_v3_data.add_icon_label,
			})
		);

		$wrapper.appendTo($root);

		if (getDataForRoot($root)['type'] === 'custom-upload') {
			var media = wp.media.attachment(
				getDataForRoot($root)['attachment-id']
			);

			if (! media.get('url')) {
				media.fetch().then(function () {
					refreshSinglePreview($root);
				});
			}
		}

		refreshSinglePreview($root);
	}

	function refreshSinglePreview($root) {
		$root = $($root);

		var data = getDataForRoot($root);

		$root
			.find('.fw-icon-v3-trigger-modal')
			.text(
				fw_icon_v3_data[
					hasIcon(data) ? 'edit_icon_label' : 'add_icon_label'
				]
			);

		$root
			.find('.fw-icon-v3-preview-wrapper')
			.removeClass('fw-has-icon')
			.addClass(hasIcon(data) ? 'fw-has-icon' : '');

		$root
			.find('.fw-icon-v3-preview-wrapper')
			.attr('data-icon-type', data['type']);

		// Reset the preview glyph before re-rendering it for the current kind.
		$root.find('i').attr('class', '').attr('style', '').empty();

		if (data.type === 'icon-font') {
			$root.find('i').attr('class', data['icon-class']);
		}

		if (data.type === 'emoji') {
			$root.find('i').addClass('fw-icon-v3-preview-emoji').text(data['char'] || '');
		}

		if (data.type === 'svg') {
			// markup is what the picker stored; server sanitises on save/render.
			$root.find('i').addClass('fw-icon-v3-preview-svg').html(data['markup'] || '');
		}

		if (data.type === 'custom-upload') {
			if (hasIcon(data)) {
				$root
					.find('i')
					.attr(
						'style',
						'background-image: url("' +
						// Insert the smallest possible image in the preview
						(_.min(
							_.values(wp.media.attachment(
								data['attachment-id']
							).get('sizes')),
							function (size) {return size.width}
						).url || wp.media.attachment(data['attachment-id']).get('url')) +
						'");'
					);
			}
		}

		function hasIcon(data) {
			return data.type !== 'none';
		}
	}

	function getDataForRoot($root) {
		return JSON.parse($root.find('input').val());
	}

	function setDataForRoot($root, data) {
		var currentData = getDataForRoot($root);

		var actualValue = _.omit(_.extend({}, currentData, data), 'attachment');

		if (actualValue.type === 'icon-font') {
			if ((actualValue['icon-class'] || "").trim() === '') {
				actualValue.type = 'none';
			}
		}

		if (actualValue.type === 'custom-upload') {
			if (! actualValue['attachment-id']) {
				actualValue.type = 'none';
			}
		}

		if (actualValue.type === 'emoji') {
			if ((actualValue['char'] || '').trim() === '') {
				actualValue.type = 'none';
			}
		}

		if (actualValue.type === 'svg') {
			if (
				! (actualValue['markup'] || '') &&
				! (actualValue['url'] || '') &&
				! (actualValue['svg-id'] || '')
			) {
				actualValue.type = 'none';
			}
		}

		$root.find('input').val(JSON.stringify(actualValue)).trigger('change');

		fw.options.trigger.changeForEl($root, {
			value: actualValue,
		});

		refreshSinglePreview($root);
	}

	var iconOptionHandler = {
		startListeningForChanges: $.noop,
		getValue: function(optionDescriptor) {
			var raw = $(optionDescriptor.el).find('input').val();
			var parsed;
			try {
				parsed = JSON.parse(raw);
			} catch (e) {
				// Defensive: a legacy `icon` scalar ('fa fa-star') is not JSON.
				// PHP _render normalizes before printing, so this should not
				// happen, but tolerate it rather than throwing in the builder.
				parsed = raw ? { type: 'icon-font', 'icon-class': raw } : { type: 'none' };
			}
			return {
				value: parsed,

				optionDescriptor: optionDescriptor,
			};
		},
	};

	// This one engine now backs several option-type ids: the reclaimed 'icon'
	// (divider, post-types CPT menu icon, theme demos), 'icon-v2' (the production
	// type used by ~23 shortcodes + megamenu), and 'icon-v3' (the test type).
	// FW_Option_Type_Icon and FW_Option_Type_Icon_v2 subclass this engine and no
	// longer enqueue their own picker JS, so the stock backend.js that used to
	// register 'icon' is retired — registering it here is now the sole
	// registration. Register the SAME handler under each id. The script is
	// enqueued once (shared handle), so each id is registered exactly once (no
	// "Can't re-register an option type again").
	['icon', 'icon-v2', 'icon-v3'].forEach(function(typeId) {
		fw.options.register(typeId, iconOptionHandler);
	});
})(jQuery);
