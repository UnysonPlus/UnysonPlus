/**
 * Option Type Predefined Colors Color Picker
 * @author Pavel Marhaunichy
 * @url http://likeaprothemes.com
 */
(function () {
	var optionTypeClass = 'fw-option-type-predefined-colors-color-picker';

	jQuery(document).ready(function ($) {

		fwEvents.on('fw:options:init', function (data) {

			var $options = data.$elements.find('.' + optionTypeClass + ':not(.initialized)');

			$.each($options, function (i, option) { // for case when we have multiple options in tab
				var $option = $(option),
					$radio = $option.find('.' + optionTypeClass + '__radio'),
					$text = $option.find('.' + optionTypeClass + '__text');

				//set defaults for picker
				if ($text.attr('value') !== '') $radio.attr('checked', true);

				$option.on('click', function (e) {

					if ($(e.target).hasClass(optionTypeClass + '__text')) {
						//unckeck predefined
						$option.find('select :first-child').attr('selected', true);
						$option.find('.selected')
							.removeClass('selected')
							.attr('selected', false);

						$radio.attr('checked', true);
					} else if ($(e.target).hasClass(optionTypeClass + '__radio')) {
						//unckeck predefined
						$option.find('select :first-child').attr('selected', true);
						$option.find('.selected')
							.removeClass('selected')
							.attr('selected', false);

						$text.focus();
					} else if ($(e.target).hasClass('fw-option-type-predefined-colors-list__color')) {
						$text
							.attr('value', '')
							.css('background-color', '');
						$radio.attr('checked', false);
					}

				});

				$option.addClass('initialized');

			});
		});
	});
})();
