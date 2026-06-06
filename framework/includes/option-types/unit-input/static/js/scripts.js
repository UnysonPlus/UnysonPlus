/* global jQuery, fwEvents */
(function ($) {
	'use strict';

	function UnitInput($root) {
		this.$root = $root;
		this.$json = $root.find('.fw-unit-input-json');
		this.$value = $root.find('.fw-unit-input-value');
		this.$unit = $root.find('.fw-unit-input-unit');

		try { this.state = JSON.parse(this.$json.val()); }
		catch (e) { this.state = {}; }
		if (!this.state || typeof this.state !== 'object') { this.state = {}; }
		this.state = $.extend({ value: '', unit: 'px' }, this.state);

		this.bind();
		this.sync();
	}

	UnitInput.prototype.bind = function () {
		var self = this;
		this.$value.on('input change', function () {
			self.state.value = $(this).val();
			self.sync();
		});
		this.$unit.on('change', function () {
			self.state.unit = $(this).val();
			self.sync();
		});
	};

	UnitInput.prototype.sync = function () {
		this.$json.val(JSON.stringify(this.state)).trigger('change');
	};

	fwEvents.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option-type-unit-input:not(.fw-unit-input-initialized)')
			.each(function () { new UnitInput($(this)); })
			.addClass('fw-unit-input-initialized');
	});

})(jQuery);
