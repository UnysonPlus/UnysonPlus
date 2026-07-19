(function($){
	fwEvents.on('fw:options:init', function (data) {
		data.$elements.find('.fw-option.fw-option-type-gradient:not(.initialized)').each(function(){
			var $option = $(this);

			// Convenience: when the primary color changes and the secondary is
			// still empty, default the secondary to the primary. Once the
			// secondary has a value we leave it alone.
			//
			// The sub-pickers are Coloris (not Iris) — the color-picker fires
			// `fw:color:picker:changed` with { $element }, and Coloris repaints
			// its swatch from a native 'input'/'change' event, so we just set the
			// value and dispatch those (the old `.iris('hide')` call threw because
			// nothing here is Iris-initialised anymore).
			$option.on('fw:color:picker:changed', '.fw-option-type-color-picker.primary', function (event, evData) {
				var $secondary = $option.find('.fw-option-type-color-picker.secondary:first');

				if ($secondary.val()) {
					return; // don't overwrite a chosen secondary color
				}

				var primaryVal = (evData && evData.$element) ? evData.$element.val() : $(this).val();

				$secondary.val(primaryVal).trigger('input').trigger('change');
			});
		}).addClass('initialized');
	});
})(jQuery);
