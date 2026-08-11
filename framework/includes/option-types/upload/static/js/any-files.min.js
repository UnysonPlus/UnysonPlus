(function($, fwe) {

	// --- Diagnostic mode -------------------------------------------------------------------------
	// Enable by adding ?fw_media_debug=1 to the admin URL, or `window.FW_MEDIA_DEBUG = true` before a
	// click. Logs every media-frame lifecycle step to the console with an [fw-upload] prefix.
	var FW_MEDIA_DEBUG = /[?&]fw_media_debug=1/.test(String(location.search || '')) || !!window.FW_MEDIA_DEBUG;
	function fwLog() {
		if (FW_MEDIA_DEBUG && window.console) {
			try { console.log.apply(console, ['[fw-upload]'].concat([].slice.call(arguments))); } catch (e) {}
		}
	}

	// A callable page-wide diagnostic: run `fwUploadDiag()` in the browser console to see whether the WP
	// media library is loaded and what every "any-files" upload field (incl. background-pro video/webm)
	// is configured with. Share its output if a picker still misbehaves.
	window.fwUploadDiag = function () {
		var report = {
			wp_media_available: !!(window.wp && wp.media),
			wp_media_settings:  !!(window.wp && wp.media && wp.media.view && wp.media.view.settings),
			wp_enqueue_media_l10n: !!window._wpMediaViewsL10n,
			underscore: typeof window._,
			fields: []
		};
		$('.fw-option-type-upload.any-files').each(function () {
			var $c = $(this);
			var details = $c.attr('data-files-details');
			report.fields.push({
				name: ($c.find('input[type="hidden"]').attr('name') || ''),
				initialized: $c.hasClass('fw-option-initialized'),
				buttons: $c.find('button').length,
				files_details: details ? JSON.parse(details) : null,
				value: $c.find('input[type="hidden"]').val()
			});
		});
		if (window.console) { console.log('[fw-upload] DIAGNOSTIC', report); }
		return report;
	};

	var init = function() {
		var $this = $(this),
			elements = {
				$container: $this,
				$input: $this.find('input[type="hidden"]'),
				$uploadButton: $this.find('button'),
				$deleteButton: $this.find('a'), // it 'clears' the input
				$textField: $this.find('em') // for the name of the attachment
			},
			l10n = {
				buttonAdd: elements.$container.attr('data-l10n-button-add'),
				buttonEdit: elements.$container.attr('data-l10n-button-edit')
			},
			frame;

		var haveFilesDetails = elements.$container.attr('data-files-details') !== undefined;
		var parsedFilesDetails = null;
		if (haveFilesDetails) {
			try { parsedFilesDetails = JSON.parse(elements.$container.attr('data-files-details')); }
			catch (e) { parsedFilesDetails = null; fwLog('bad data-files-details JSON', e); }
		}

		// The allowed library filter for this field: the field's declared mime types (e.g. ['video/mp4']
		// for a background video), or null = show everything. WP's `library.type` accepts full mime types
		// or a top-level bucket ('image'/'video'/'audio'). An empty/absent filter must NOT be passed as an
		// empty array — that yields an impossible query and an empty grid — so we omit it entirely.
		function libraryType() {
			if (parsedFilesDetails && Array.isArray(parsedFilesDetails.mime_types) && parsedFilesDetails.mime_types.length) {
				return parsedFilesDetails.mime_types;
			}
			return null;
		}

		// Build the frame with the STANDARD wp.media() call — a MediaFrame.Select that auto-creates its
		// Library (browse) + Upload states. The previous implementation hand-built a states controller,
		// tweaked plupload on `content:render`, and called `frame.reset()` on every open — any of which
		// could throw or wipe the content region, leaving a BLANK modal (and, because the throw happened
		// mid-createFrame, a half-built frame that only opened on the SECOND click). None of that is
		// needed: WP already allows video/mp4·webm·mov uploads, and the standard frame just works.
		var createFrame = function() {
			var type = libraryType();
			var opts = {
				title:    l10n.buttonAdd || undefined,
				button:   { text: (window._wpMediaViewsL10n && _wpMediaViewsL10n.select) || 'Select' },
				multiple: false,
				library:  type ? { type: type } : {},
				// Give the browse view an explicit filterable Library state — exactly like the image picker
				// (images-only.js). WITHOUT it the media toolbar's filter dropdown isn't rendered, leaving a
				// blank slot next to "Filter by date". This is the ONLY hand-built bit kept from the old code;
				// the modal-blanking culprits (frame.reset() + the content:render plupload poke) stay removed.
				states: new wp.media.controller.Library({
					library:    wp.media.query( type ? { type: type } : {} ),
					multiple:   false,
					title:      l10n.buttonAdd || undefined,
					filterable: 'uploaded',
					priority:   20
				})
			};

			fwLog('createFrame', opts);
			frame = wp.media(opts);

			frame.on('ready', function() {
				if (frame.modal) { frame.modal.$el.addClass('fw-option-type-upload'); }
				fwLog('frame ready');
			});

			// Preselect the currently-saved attachment so re-opening highlights it (no frame.reset()).
			frame.on('open', function() {
				var id = String(elements.$input.val() || '');
				if (id && id !== '0') {
					var att = wp.media.attachment(id);
					att.fetch();
					frame.state().get('selection').add([ att ]);
				}
				fwLog('frame open, preselect', id);
			});

			frame.on('select', function() {
				var attachment = frame.state().get('selection').first();
				if (!attachment) { return; }
				elements.$input.val(attachment.id).trigger('change'); // trigger Customizer update
				performSelection(attachment);
				fwLog('selected', attachment.id);
			});
		};

		elements.$uploadButton.on('click', function(e) {
			e.preventDefault();
			try {
				if (!frame) { createFrame(); }
				frame.open();
				fwLog('frame.open()');
			} catch (err) {
				if (window.console) { console.error('[fw-upload] media frame failed to open:', err); }
				if (FW_MEDIA_DEBUG) { window.alert('[fw-upload] media error: ' + (err && err.message ? err.message : err)); }
				frame = null; // let the next click rebuild cleanly instead of reusing a half-built frame
			}
		});

		elements.$deleteButton.on('click', function(e) {
			clearAttachment();
			elements
				.$input.val('')
				.trigger('change'); // trigger Customizer update
			e.preventDefault();
		});

		elements.$input.on('change', function () {
			if (! $(this).val()) {
				clearAttachment();
				return;
			}

			var attachment = wp.media.attachment($(this).val());

			if (! attachment.get('url')) {
				attachment.fetch().then(function (data) {
					performSelection(attachment);
				});

				return;
			}

			performSelection(attachment);
		})

		function clearAttachment () {
			elements.$textField.text('');
			elements.$uploadButton.text(l10n.buttonAdd);
			elements.$container.addClass('empty');

			fwe.trigger('fw:option-type:upload:clear', {$element: elements.$container});
			elements.$container.trigger('fw:option-type:upload:clear');

			fw.options.trigger.changeForEl(elements.$container, {
				value: {}
			});

			fw.options.trigger.scopedByType('clear', elements.$container, {
				value: {}
			});
		}

		function performSelection (attachment) {
			elements.$textField.text(attachment.get('filename'));
			elements.$uploadButton.text(l10n.buttonEdit);

			elements.$container.removeClass('empty');

			fwe.trigger('fw:option-type:upload:change', {
				$element: elements.$container,
				attachment: attachment
			});

			elements.$container.trigger('fw:option-type:upload:change', {
				attachment: attachment
			});

			fw.options.trigger.changeForEl(elements.$container, {
				value: {
					attachment_id: attachment.get('id'),
					url: attachment.get('url')
				}
			});
		}
	};

	fwe.on('fw:options:init', function(data) {
		data.$elements
			.find('.fw-option-type-upload.any-files:not(.fw-option-initialized)').each(init)
			.addClass('fw-option-initialized');
	});

})(jQuery, fwEvents);
