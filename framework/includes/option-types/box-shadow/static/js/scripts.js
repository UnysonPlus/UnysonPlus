/* global jQuery, fwEvents */
(function ($) {
	'use strict';

	function toCss(s) {
		var x = parseInt(s.x, 10) || 0;
		var y = parseInt(s.y, 10) || 0;
		var blur = parseInt(s.blur, 10) || 0;
		var spread = parseInt(s.spread, 10) || 0;
		if (x === 0 && y === 0 && blur === 0 && spread === 0) { return ''; }
		var color = s.color && ('' + s.color).trim() !== '' ? s.color : 'rgba(0,0,0,0.2)';
		return (s.inset ? 'inset ' : '') + x + 'px ' + y + 'px ' + blur + 'px ' + spread + 'px ' + color;
	}

	function BoxShadow($root) {
		this.$root = $root;
		this.$json = $root.find('.fw-box-shadow-json');
		this.$preview = $root.find('.bsh-preview');
		this.$output = $root.find('.bsh-output');
		this.$colorInput = $root.find('.bsh-color');

		try { this.state = JSON.parse(this.$json.val()); }
		catch (e) { this.state = {}; }
		if (!this.state || typeof this.state !== 'object') { this.state = {}; }
		this.state = $.extend({ x: 0, y: 0, blur: 0, spread: 0, color: '', inset: false }, this.state);

		this.initColorPicker();
		this.bind();
		this.render();
	}

	BoxShadow.prototype.initColorPicker = function () {
		var self = this;
		if (this.$colorInput.data('bsh-picker')) { return; }
		this.$colorInput.data('bsh-picker', true);
		this.$colorInput.wpColorPicker({
			change: function (e, ui) {
				self.state.color = ui.color ? ui.color.toString() : '';
				self.render();
			},
			clear: function () {
				self.state.color = '';
				self.render();
			}
		});
	};

	BoxShadow.prototype.bind = function () {
		var self = this;
		this.$root.on('input change', '.bsh-num', function () {
			self.state[$(this).attr('data-k')] = $(this).val();
			self.render();
		});
		this.$root.on('change', '.bsh-inset-cb', function () {
			self.state.inset = $(this).is(':checked');
			self.render();
		});
	};

	BoxShadow.prototype.render = function () {
		var css = toCss(this.state);
		this.$preview.attr('style', css !== '' ? 'box-shadow:' + css + ';' : '');
		this.$output.text(css !== '' ? css : 'none');
		this.$json.val(JSON.stringify(this.state)).trigger('change');
	};

	fwEvents.on('fw:options:init', function (data) {
		data.$elements
			.find('.fw-option-type-box-shadow:not(.bsh-initialized)')
			.each(function () { new BoxShadow($(this)); })
			.addClass('bsh-initialized');
	});

})(jQuery);
