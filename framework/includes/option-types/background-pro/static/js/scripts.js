/**
 * Option Type: Background Pro
 * - Switches sub-tabs (Color / Gradient / Image / Video).
 * - Toggles a "has-value" dot on each tab whenever its layer becomes
 *   non-empty, so users notice hidden state in other tabs.
 */
(function () {
	jQuery(document).ready(function ($) {

		var ROOT_SEL = '.fw-option-type-background-pro';

		function initOption($option) {
			if ($option.data('bgProInit')) return;
			$option.data('bgProInit', true);

			// Tab click → switch active panel
			$option.on('click', '.bg-pro__tab', function () {
				var key = $(this).attr('data-bg-pro-tab');
				if (!key) return;

				$option.find('.bg-pro__tab').removeClass('is-active');
				$(this).addClass('is-active');

				$option.find('.bg-pro__panel').removeClass('is-active');
				$option.find('.bg-pro__panel[data-bg-pro-panel="' + key + '"]').addClass('is-active');
			});

			// Re-evaluate dots whenever anything inside a panel changes.
			// We listen broadly: native change/input plus a few Unyson custom
			// events that fire for upload + switch updates.
			$option.on(
				'change input fw:option-type:switch:change fw:upload:select fw:upload:remove',
				function () { updateDots($option); }
			);

			updateDots($option);
		}

		function updateDots($option) {
			$option.find('.bg-pro__tab').each(function () {
				var $tab   = $(this);
				var key    = $tab.attr('data-bg-pro-tab');
				var $panel = $option.find('.bg-pro__panel[data-bg-pro-panel="' + key + '"]');
				if (!$panel.length) return;

				var hasValue = false;

				if (key === 'color') {
					// predefined-colors-color-picker stores presets in a <select>
					// and the custom color in the color-picker's text input.
					var presetVal = ($panel.find('select[name$="[predefined]"]').val() || '').toString().trim();
					var customVal = ($panel.find('input[name$="[custom]"]').val()      || '').toString().trim();
					hasValue =
						presetVal !== '' ||
						(customVal !== '' && customVal !== 'transparent' && customVal !== 'rgba(0,0,0,0)');
				} else if (key === 'gradient' || key === 'video') {
					// Unyson switch is a checkbox with name = the option's name.
					// Checked state alone is the signal — value comparison is
					// unreliable because it's JSON-encoded ('"yes"' with quotes).
					var $enabled = $panel.find('input[type="checkbox"][name$="[enabled]"]').first();
					hasValue = $enabled.length > 0 && $enabled.is(':checked');
				} else if (key === 'image') {
					// Upload renders a single hidden input whose value is the
					// attachment_id (or empty). Our sub-key is 'src'.
					var $src = $panel.find('input[type="hidden"][name$="[src]"]').first();
					var srcVal = ($src.val() || '').toString().trim();
					hasValue = srcVal !== '' && srcVal !== '0';
				}

				$tab.toggleClass('has-value', !!hasValue);
			});
		}

		// Initial pass for any options already on the page
		$(ROOT_SEL).each(function () { initOption($(this)); });

		// Unyson re-fires this when new options are mounted (popup forms,
		// addable-popup items, etc.). Hook in so we catch dynamic instances.
		fwEvents.on('fw:options:init', function (data) {
			data.$elements.find(ROOT_SEL).each(function () { initOption($(this)); });
			// New options may have already-set values — refresh dots after init
			data.$elements.find(ROOT_SEL).each(function () { updateDots($(this)); });
		});
	});
})();
