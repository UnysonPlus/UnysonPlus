jQuery(function ($) {
	fw.qtip( $('.fw-extensions-list .fw-extensions-list-item .fw-extension-tip') );
});

/**
 * Install/Remove/... via popup if has direct filesystem access (no ftp credentials required)
 */
jQuery(function($){
	var inst = {
		isBusy: false,
		eventNamespace: '.fw-extension',
		$wrapper: $('.wrap'),
		listenSubmit: function() {
			this.$wrapper.on('submit'+ this.eventNamespace, 'form.fw-extension-ajax-form', this.onSubmit);
		},
		stopListeningSubmit: function() {
			this.$wrapper.off('submit'+ this.eventNamespace, 'form.fw-extension-ajax-form');
		},
		onSubmit: function(e) {
			e.preventDefault();

			if (inst.isBusy) {
				fw.notify('Working... Please try again later', 'warning');
				return;
			}

			var $form = $(this);

			var confirmMessage = $form.attr('data-confirm-message'),
			    action         = $form.attr('data-extension-action'),
				action         = action === 'uninstall' ? 'delete' : action,
				nonceName      = '_nonce_fw_extensions_' + action;

			inst.isBusy = true;
			inst.loading($form, true);

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'fw_extensions_check_direct_fs_access',
					[nonceName]: $form.find('#' + nonceName).val(),
					extAction: action
				},
				dataType: 'json'
			}).done(function(data){
				if (data.success) {
					var proceed = function () {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'fw_extensions_' + (action === 'delete' ? 'uninstall' : action),
							extension: $form.attr('data-extension-name'),
							[nonceName]: $form.find('#' + nonceName).val()
						},
						dataType: 'json'
					}).done(function(r) {
						if (r.success) {
							window.location.reload();
						} else {
							var error = r.data ? r.data.pop().message : 'Error';

							fw.soleModal.show(
								'fw-extension-install-error',
								'<p class="fw-text-danger">Error: '+ error +'</p>'
							);
						}
					}).fail(function(jqXHR, textStatus, errorThrown){
						fw.soleModal.show(
							'fw-extension-install-error',
							'<p class="fw-text-danger">Error: '+ String(errorThrown) +'</p>'
						);
						inst.isBusy = false;
						inst.loading($form, false);
					});
					};

					if (confirmMessage) {
						// Styled confirm. Only proceed on accept — on Cancel we reset
						// the busy/loading state and stop. (The old native confirm()
						// fell through and ran the uninstall even on Cancel.)
						fw.confirm(confirmMessage, proceed, {
							onCancel: function () {
								inst.isBusy = false;
								inst.loading($form, false);
							}
						});
					} else {
						proceed();
					}
				} else {
					inst.stopListeningSubmit();
					$form.submit();
				}
			}).fail(function(jqXHR, textStatus, errorThrown){
				inst.stopListeningSubmit();
				$form.submit();
			});
		},
		loading: function($form, show) {
			var $item = $form.closest('.fw-extensions-list-item');
			var $loadingContainer = $item.find('.fw-extensions-list-item-title').first();
			var $loading = $loadingContainer.find('.ajax-form-loading');

			if (!$loading.length) {
				$loadingContainer.append(
					'<span class="ajax-form-loading fw-text-center fw-hidden">'+
						'<img src="'+ fw.img.loadingSpinner +'" />'+
					'</span>'
				);
				$loading = $loadingContainer.find('.ajax-form-loading');
			}

			// Indeterminate line progress bar pinned to the bottom of the card,
			// shown alongside the spinner. The install/uninstall is a single
			// black-box AJAX request (no per-task callbacks), so a true done/total
			// bar isn't available — this honestly signals "working" until it
			// returns. Visibility is driven by the .fw-ext-installing class.
			var $inner = $item.find('.inner').first();
			if ($inner.length && !$inner.children('.fw-ext-install-bar').length) {
				$inner.append(
					'<span class="fw-ext-install-bar" aria-hidden="true">'+
						'<span class="fw-ext-install-bar__inner"></span>'+
					'</span>'
				);
			}

			if (show) {
				$loading.removeClass('fw-hidden');
				$item.addClass('fw-ext-installing');
			} else {
				$loading.addClass('fw-hidden');
				$item.removeClass('fw-ext-installing');
			}
		}
	};

	inst.listenSubmit();
});