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

			// Re-evaluate dots whenever anything inside a panel changes. Listen broadly —
			// native change/input/click (the click catches predefined-colors swatch picks,
			// which set a hidden <select> without bubbling a change) plus the Unyson upload
			// events — on a short timeout so the value is set before we read it.
			$option.on(
				'change input click fw:option-type:switch:change fw:option-type:upload:change fw:option-type:upload:clear',
				function () { setTimeout(function () { updateDots($option); }, 30); }
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
				} else if (key === 'gradient') {
					// The gradient layer has NO enable switch — it's "on" iff the
					// gradient-v2 control compiled a real gradient. Its read-only
					// output input (.gv2-output) holds that CSS string (empty = none).
					var gradCss = ($panel.find('.gv2-output').val() || '').toString().trim();
					hasValue = gradCss !== '';
				} else if (key === 'video') {
					// On as soon as a source is set (no enable toggle any more): an External
					// URL, or a self-hosted MP4 / WebM (upload hidden input = attachment id).
					var ext  = ($panel.find('.fw-oembed-input input, input[name$="[external_url]"]').first().val() || '').toString().trim();
					var mp4  = ($panel.find('input[type="hidden"][name$="[source_mp4]"]').first().val() || '').toString().trim();
					var webm = ($panel.find('input[type="hidden"][name$="[source_webm]"]').first().val() || '').toString().trim();
					hasValue = ext !== '' || (mp4 !== '' && mp4 !== '0') || (webm !== '' && webm !== '0');
				} else if (key === 'overlay') {
					// A tint colour (rgba text input) and/or a gradient (gradient-v2 output).
					var ovColor = ($panel.find('input[data-alpha], input[name$="[color]"]').first().val() || '').toString().trim();
					var ovGrad  = ($panel.find('.gv2-output').val() || '').toString().trim();
					hasValue = (ovColor !== '' && ovColor !== 'transparent' && ovColor !== 'rgba(0,0,0,0)') || ovGrad !== '';
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
