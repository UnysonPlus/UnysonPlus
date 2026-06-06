/**
 * Option Type Predefined Colors
 * @author Pavel Marhaunichy
 * @url http://likeaprothemes.com
 */
(function () {
	var optionTypeClass = 'fw-option-type-predefined-colors';

	jQuery(document).ready(function ($) {

		fwEvents.on('fw:options:init', function (data) {

			var $options = data.$elements.find('.' + optionTypeClass + ':not(.initialized)');

			$.each($options, function (i, option) { // for case when we have multiple options in tab
				var $option = $(option);
				//set default
				var $selectOptions = $option.find('option');
				$.each($selectOptions, function (i, el) {
					if (el.selected) {
						var value = el.value;
						$option.find('[data-option-type-predefined-colors="' + value + '"]').addClass('selected');
					}
				});

				$option.on('click', function (e) {
					if ($(e.target).hasClass(optionTypeClass + '-list__color')) {

						var value = e.target.getAttribute('data-option-type-predefined-colors');

						if ($(e.target).hasClass('selected')) {
							$(e.target).removeClass('selected');
							$option.find('option[value="' + value + '"]').attr('selected', false);
						} else {
							$option.find('.selected').removeClass('selected');

							$(e.target).addClass('selected');
							$option.find('option[value="' + value + '"]').attr('selected', true);
						}

					}
				});

				$option.addClass('initialized');
			});
		});
	});
})();
