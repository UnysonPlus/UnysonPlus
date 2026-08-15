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

		// Tear down any previous Lottie preview animation before resetting the
		// <i> — empty() removes the SVG but leaves the player's rAF loop running.
		var prevGlyph = $root.find('i')[0];
		if (prevGlyph && prevGlyph.__upwPreviewLottie) {
			try { prevGlyph.__upwPreviewLottie.destroy(); } catch (e) {}
			prevGlyph.__upwPreviewLottie = null;
		}
		if (prevGlyph && prevGlyph.__upwPreviewRive) {
			try { prevGlyph.__upwPreviewRive.cleanup(); } catch (e) {}
			prevGlyph.__upwPreviewRive = null;
		}

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
						(smallestSizeUrl(data['attachment-id']) ||
							wp.media.attachment(data['attachment-id']).get('url')) +
						'");'
					);
			}
		}

		if (data.type === 'lottie') {
			// The Animated kind previews as a small looping animation, using the
			// bundled lottie-web player enqueued alongside the picker.
			playLottiePreview($root, $root.find('i').addClass('fw-icon-v3-preview-lottie'), data['src']);
		}

		if (data.type === 'rive') {
			// Rive previews on a <canvas> via the bundled Rive runtime (only
			// enqueued when Rive is enabled).
			playRivePreview($root, $root.find('i').addClass('fw-icon-v3-preview-rive'), data['src']);
		}

		function hasIcon(data) {
			return data.type !== 'none';
		}
	}

	// Draw a Rive .riv into the option-swatch <i> on a <canvas>. Guarded so it is
	// inert when the Rive runtime isn't present (Rive not enabled).
	function playRivePreview($root, $i, src) {
		if (!src || !$i[0] || !window.rive) { return; }
		if (window.upwRiveWasm) { try { window.rive.RuntimeLoader.setWasmUrl(window.upwRiveWasm); } catch (e) {} }
		try {
			var canvas = document.createElement('canvas');
			canvas.className = 'upw-rive-canvas';
			$i[0].appendChild(canvas);
			var inst = new window.rive.Rive({
				src: src, canvas: canvas, autoplay: true,
				layout: new window.rive.Layout({ fit: window.rive.Fit.Contain, alignment: window.rive.Alignment.Center }),
				onLoad: function () { try { inst.resizeDrawingSurfaceToCanvas(); } catch (e) {} }
			});
			$i[0].__upwPreviewRive = inst;
		} catch (e) {}
	}

	// Load a looping Lottie animation into the option-swatch <i>. lottie-web is
	// enqueued in the footer, so on the very first paint window.lottie may not be
	// parsed yet — retry briefly, and bail if the value changed to another kind
	// while we waited (so a late load can't inject into a now-stale swatch).
	function playLottiePreview($root, $i, src) {
		if (!src || !$i[0]) {
			return;
		}

		var start = function () {
			if (getDataForRoot($root).type !== 'lottie' || $i[0].__upwPreviewLottie) {
				return;
			}
			try {
				$i[0].__upwPreviewLottie = window.lottie.loadAnimation({
					container: $i[0],
					renderer: 'svg',
					loop: true,
					autoplay: true,
					path: src
				});
			} catch (e) {}
		};

		if (window.lottie) {
			start();
			return;
		}

		var tries = 0;
		var iv = setInterval(function () {
			if (window.lottie) { clearInterval(iv); start(); }
			else if (++tries > 40) { clearInterval(iv); } // ~4s cap
		}, 100);
	}

	function getDataForRoot($root) {
		return JSON.parse($root.find('input').val());
	}

	/**
	 * URL of the narrowest registered size of an attachment (undefined when the
	 * attachment has no sizes yet), so the preview loads the lightest image.
	 */
	function smallestSizeUrl(attachmentId) {
		var sizes = Object.values(
			wp.media.attachment(attachmentId).get('sizes') || {}
		);

		var smallest = sizes.reduce(function (a, b) {
			return (a && a.width <= b.width) ? a : b;
		}, null);

		return smallest ? smallest.url : undefined;
	}

	function setDataForRoot($root, data) {
		var currentData = getDataForRoot($root);

		var actualValue = Object.assign({}, currentData, data);
		delete actualValue.attachment;

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
	['icon'].forEach(function(typeId) {
		fw.options.register(typeId, iconOptionHandler);
	});
})(jQuery);
