/**
 * "Install Extension" (3rd-party) page.
 * Uploads a .zip or downloads a GitHub repo, then installs it as a UnysonPlus
 * extension (lands installed-but-inactive on the Extensions list).
 */
(function ($) {
	'use strict';

	var data = window._fw_ext_install_custom || {};
	var l10n = data.l10n || {};

	function notice(type, msg) {
		var $n = $('#fw-ext-install-custom-notice');
		$n.attr('class', 'fw-ext-install-custom__notice notice inline notice-' + (type === 'success' ? 'success' : 'error'))
			.html('<p>' + msg + '</p>')
			.show();
	}

	function busy($btn, on) {
		if (on) {
			$btn.data('label', $btn.text()).prop('disabled', true).text(l10n.installing || 'Installing…');
		} else {
			$btn.prop('disabled', false).text($btn.data('label'));
		}
	}

	function onDone($btn, res) {
		busy($btn, false);
		if (res && res.success) {
			var name = (res.data && res.data.name) ? res.data.name : '';
			var link = data.link
				? ' <a href="' + data.link + '">' + (l10n.go_to_list || 'Go to Extensions') + '</a>'
				: '';
			notice('success', (l10n.done || 'Installed.') + (name ? ' (' + name + ')' : '') + link);
		} else {
			var msg = (res && res.data && res.data.message) ? res.data.message : (l10n.failed || 'Install failed.');
			notice('error', msg);
		}
	}

	function onFail($btn) {
		busy($btn, false);
		notice('error', l10n.failed || 'Install failed.');
	}

	$(function () {
		// ZIP upload
		$('#fw-ext-install-zip').on('click', function () {
			var $btn = $(this);
			var file = ($('#fw-ext-zip')[0].files || [])[0];
			if (!file) {
				notice('error', l10n.no_file || 'Choose a .zip file first.');
				return;
			}

			var fd = new FormData();
			fd.append('action', 'fw_ext_install_custom_zip');
			fd.append('nonce', data.nonce);
			fd.append('extension_zip', file);

			busy($btn, true);
			$.ajax({
				url: data.ajaxurl,
				method: 'POST',
				data: fd,
				processData: false,
				contentType: false
			}).done(function (res) { onDone($btn, res); }).fail(function () { onFail($btn); });
		});

		// GitHub URL
		$('#fw-ext-install-github').on('click', function () {
			var $btn = $(this);
			var url = $.trim($('#fw-ext-github').val() || '');
			if (!url) {
				notice('error', l10n.no_url || 'Enter a GitHub repository URL.');
				return;
			}

			busy($btn, true);
			$.post(data.ajaxurl, {
				action: 'fw_ext_install_custom_github',
				nonce: data.nonce,
				github_url: url
			}).done(function (res) { onDone($btn, res); }).fail(function () { onFail($btn); });
		});
	});
})(jQuery);
