var fw;

if (typeof Object['create'] != 'undefined') {
	/**
	 * create clean object
	 * autocomplete in console to show only defined methods, without other unnecesary methods from Object prototype
	 */
	fw = Object.create(null);
} else {
	fw = {};
}

/**
 * URI to framework directory
 */
fw.FW_URI = _fw_localized.FW_URI;

fw.SITE_URI = _fw_localized.SITE_URI;

fw.LOADER_URI = _fw_localized.LOADER_URI;

/**
 * Useful images
 */
fw.img = {
	loadingSpinner: fw.SITE_URI +'/wp-admin/images/spinner.gif',
	logoSvg: fw.LOADER_URI
};

/**
 * parseInt() alternative
 * Like intval() in php. Returns 0 on failure, not NaN
 * @param val
 */
fw.intval = function(val) {
	val = parseInt(val);

	return !isNaN(val) ? val : 0;
};

/**
 * Calculate md5 hash of the string
 * @param {String} string
 */
fw.md5 = (function(){
	"use strict";

	/*
	 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
	 * Digest Algorithm, as defined in RFC 1321.
	 * Copyright (C) Paul Johnston 1999 - 2000.
	 * Updated by Greg Holt 2000 - 2001.
	 * See http://pajhome.org.uk/site/legal.html for details.
	 */

	/*
	 * Convert a 32-bit number to a hex string with ls-byte first
	 */
	var hex_chr = "0123456789abcdef";
	function rhex(num)
	{
		var str = "", j;
		for(j = 0; j <= 3; j++)
			str += hex_chr.charAt((num >> (j * 8 + 4)) & 0x0F) +
			hex_chr.charAt((num >> (j * 8)) & 0x0F);
		return str;
	}

	/*
	 * Convert a string to a sequence of 16-word blocks, stored as an array.
	 * Append padding bits and the length, as described in the MD5 standard.
	 */
	function str2blks_MD5(str)
	{
		var nblk = ((str.length + 8) >> 6) + 1,
			blks = new Array(nblk * 16),
			i;
		for(i = 0; i < nblk * 16; i++) blks[i] = 0;
		for(i = 0; i < str.length; i++)
			blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
		blks[i >> 2] |= 0x80 << ((i % 4) * 8);
		blks[nblk * 16 - 2] = str.length * 8;
		return blks;
	}

	/*
	 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
	 * to work around bugs in some JS interpreters.
	 */
	function add(x, y)
	{
		var lsw = (x & 0xFFFF) + (y & 0xFFFF);
		var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
		return (msw << 16) | (lsw & 0xFFFF);
	}

	/*
	 * Bitwise rotate a 32-bit number to the left
	 */
	function rol(num, cnt)
	{
		return (num << cnt) | (num >>> (32 - cnt));
	}

	/*
	 * These functions implement the basic operation for each round of the
	 * algorithm.
	 */
	function cmn(q, a, b, x, s, t)
	{
		return add(rol(add(add(a, q), add(x, t)), s), b);
	}
	function ff(a, b, c, d, x, s, t)
	{
		return cmn((b & c) | ((~b) & d), a, b, x, s, t);
	}
	function gg(a, b, c, d, x, s, t)
	{
		return cmn((b & d) | (c & (~d)), a, b, x, s, t);
	}
	function hh(a, b, c, d, x, s, t)
	{
		return cmn(b ^ c ^ d, a, b, x, s, t);
	}
	function ii(a, b, c, d, x, s, t)
	{
		return cmn(c ^ (b | (~d)), a, b, x, s, t);
	}

	/*
	 * Take a string and return the hex representation of its MD5.
	 */
	function calcMD5(str)
	{
		var x = str2blks_MD5(str);
		var a =  1732584193;
		var b = -271733879;
		var c = -1732584194;
		var d =  271733878;

		var i, olda, oldb, oldc, oldd;

		for(i = 0; i < x.length; i += 16)
		{
			olda = a;
			oldb = b;
			oldc = c;
			oldd = d;

			a = ff(a, b, c, d, x[i+ 0], 7 , -680876936);
			d = ff(d, a, b, c, x[i+ 1], 12, -389564586);
			c = ff(c, d, a, b, x[i+ 2], 17,  606105819);
			b = ff(b, c, d, a, x[i+ 3], 22, -1044525330);
			a = ff(a, b, c, d, x[i+ 4], 7 , -176418897);
			d = ff(d, a, b, c, x[i+ 5], 12,  1200080426);
			c = ff(c, d, a, b, x[i+ 6], 17, -1473231341);
			b = ff(b, c, d, a, x[i+ 7], 22, -45705983);
			a = ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
			d = ff(d, a, b, c, x[i+ 9], 12, -1958414417);
			c = ff(c, d, a, b, x[i+10], 17, -42063);
			b = ff(b, c, d, a, x[i+11], 22, -1990404162);
			a = ff(a, b, c, d, x[i+12], 7 ,  1804603682);
			d = ff(d, a, b, c, x[i+13], 12, -40341101);
			c = ff(c, d, a, b, x[i+14], 17, -1502002290);
			b = ff(b, c, d, a, x[i+15], 22,  1236535329);

			a = gg(a, b, c, d, x[i+ 1], 5 , -165796510);
			d = gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
			c = gg(c, d, a, b, x[i+11], 14,  643717713);
			b = gg(b, c, d, a, x[i+ 0], 20, -373897302);
			a = gg(a, b, c, d, x[i+ 5], 5 , -701558691);
			d = gg(d, a, b, c, x[i+10], 9 ,  38016083);
			c = gg(c, d, a, b, x[i+15], 14, -660478335);
			b = gg(b, c, d, a, x[i+ 4], 20, -405537848);
			a = gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
			d = gg(d, a, b, c, x[i+14], 9 , -1019803690);
			c = gg(c, d, a, b, x[i+ 3], 14, -187363961);
			b = gg(b, c, d, a, x[i+ 8], 20,  1163531501);
			a = gg(a, b, c, d, x[i+13], 5 , -1444681467);
			d = gg(d, a, b, c, x[i+ 2], 9 , -51403784);
			c = gg(c, d, a, b, x[i+ 7], 14,  1735328473);
			b = gg(b, c, d, a, x[i+12], 20, -1926607734);

			a = hh(a, b, c, d, x[i+ 5], 4 , -378558);
			d = hh(d, a, b, c, x[i+ 8], 11, -2022574463);
			c = hh(c, d, a, b, x[i+11], 16,  1839030562);
			b = hh(b, c, d, a, x[i+14], 23, -35309556);
			a = hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
			d = hh(d, a, b, c, x[i+ 4], 11,  1272893353);
			c = hh(c, d, a, b, x[i+ 7], 16, -155497632);
			b = hh(b, c, d, a, x[i+10], 23, -1094730640);
			a = hh(a, b, c, d, x[i+13], 4 ,  681279174);
			d = hh(d, a, b, c, x[i+ 0], 11, -358537222);
			c = hh(c, d, a, b, x[i+ 3], 16, -722521979);
			b = hh(b, c, d, a, x[i+ 6], 23,  76029189);
			a = hh(a, b, c, d, x[i+ 9], 4 , -640364487);
			d = hh(d, a, b, c, x[i+12], 11, -421815835);
			c = hh(c, d, a, b, x[i+15], 16,  530742520);
			b = hh(b, c, d, a, x[i+ 2], 23, -995338651);

			a = ii(a, b, c, d, x[i+ 0], 6 , -198630844);
			d = ii(d, a, b, c, x[i+ 7], 10,  1126891415);
			c = ii(c, d, a, b, x[i+14], 15, -1416354905);
			b = ii(b, c, d, a, x[i+ 5], 21, -57434055);
			a = ii(a, b, c, d, x[i+12], 6 ,  1700485571);
			d = ii(d, a, b, c, x[i+ 3], 10, -1894986606);
			c = ii(c, d, a, b, x[i+10], 15, -1051523);
			b = ii(b, c, d, a, x[i+ 1], 21, -2054922799);
			a = ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
			d = ii(d, a, b, c, x[i+15], 10, -30611744);
			c = ii(c, d, a, b, x[i+ 6], 15, -1560198380);
			b = ii(b, c, d, a, x[i+13], 21,  1309151649);
			a = ii(a, b, c, d, x[i+ 4], 6 , -145523070);
			d = ii(d, a, b, c, x[i+11], 10, -1120210379);
			c = ii(c, d, a, b, x[i+ 2], 15,  718787259);
			b = ii(b, c, d, a, x[i+ 9], 21, -343485551);

			a = add(a, olda);
			b = add(b, oldb);
			c = add(c, oldc);
			d = add(d, oldd);
		}
		return rhex(a) + rhex(b) + rhex(c) + rhex(d);
	}

	return calcMD5;
})();

/**
 * fw.loading
 * Show/Hide loading on the page
 *
 * Usage:
 * - fw.loading.show('unique-id');
 * - fw.loading.hide('unique-id');
 */
(function($){
	var inst = {
		$el: null,
		queue: [],
		current: null,
		timeoutId: 0,
		pendingHide: false,
		$getEl: function(){
			if (this.$el === null) { // lazy init
				this.$el = $(
					'<div id="fw-loading" style="display: none;">'+
						'<table width="100%" height="100%"><tbody><tr><td valign="middle" align="center">'+
							'<img src="'+ fw.img.logoSvg +'"'+
								' width="30"'+
								' class="fw-animation-rotate-reverse-180"'+
								' alt="Loading"' +
								' onerror="this.onerror=null; this.src=\''+ fw.FW_URI +'/static/img/logo-100.png\';"'+
								' />'+
						'</td></tr></tbody></table>'+
					'</div>'
				);

				$(document.body).prepend(this.$el);
			}

			return this.$el;
		},
		removeFromQueue: function(id) {
			this.queue = _.filter(this.queue, function (item) {
				return item.id != id;
			});
		},
		show: function(id, opts) {
			if (typeof id != 'undefined') {
				this.removeFromQueue(id);

				{
					opts = jQuery.extend({
						autoClose: 30000
					}, opts || {});

					opts.id = id;

					/**
					 * @type {string} pending|opening|open|closing
					 */
					opts.state = 'pending';
				}

				this.queue.push(opts);

				return this.show();
			}

			if (this.current) {
				return false;
			}

			if (this.current && this.current.customClass !== null) {
				this.$modal.removeClass(this.current.customClass);
			}

			if (this.$modal && ! this.current.wrapWithTable) {
				this.wrapWithTable(this.$modal);
			}

			this.current = this.queue.shift();

			if (!this.current) {
				return false;
			}

			this.current.state = 'opening';

			{
				clearTimeout(this.timeoutId);

				this.timeoutId = setTimeout(_.bind(function(){
					if (
						!this.current
						||
						this.current.state != 'opening'
					) {
						return;
					}

					this.current.state = 'open';

					this.$getEl().removeClass('opening closing closed').addClass('open');

					if (this.current.autoClose) {
						clearTimeout(this.timeoutId);

						this.timeoutId = setTimeout(_.bind(function(){
							this.hide();
						}, this), this.current.autoClose);
					}

					if (this.pendingHide) {
						this.pendingHide = false;
						this.hide();
					}
				}, this), 300);
			}

			this.$getEl().removeClass('open closing closed').addClass('opening').show();

			return true;
		},
		hide: function(id) {
			if (typeof id != 'undefined') {
				if (
					this.current
					&&
					this.current.id == id
				) {
					// the script below will handle this
				} else {
					this.removeFromQueue(id);
					return true;
				}
			}

			if (!this.current) {
				return false;
			}

			var forceClose = false;

			if (this.current.state == 'opening') {
				if (this.current.id == id) {
					/**
					 * If the currently opening loading was requested to hide
					 * hide it immediately, do not wait full open.
					 * Maybe the script that started the loading was executed so quickly
					 * so the user don't event need to see the loading.
					 */
					// do nothing here, just allow the close script below to be executed
					forceClose = true;
				} else {
					this.pendingHide = true;
					return true;
				}
			} else {
				if (this.current.state != 'open') {
					return false;
				}
			}

			this.current.state = 'closing';

			{
				clearTimeout(this.timeoutId);

				this.timeoutId = setTimeout(_.bind(function () {
					if (
						!this.current
						||
						this.current.state != 'closing'
					) {
						return;
					}

					this.current.state = 'closed';

					this.$getEl().hide().removeClass('opening open closing').addClass('closed');

					if (this.$modal && this.current.customClass !== null) {
						this.$modal.removeClass(this.current.customClass);
					}

					if (this.$modal && ! this.current.wrapWithTable) {
						this.wrapWithTable(this.$modal);
					}

					this.current = null;

					this.show();
				}, this), 300);
			}

			if (forceClose) {
				this.$getEl().fadeOut('fast', _.bind(function(){
					this.$getEl().removeClass('force-closing').addClass('closed').removeAttr('style');
				}, this));

				this.$getEl().addClass('force-closing');
			}

			this.$getEl().removeClass('closed').addClass('closing');
		}
	};

	fw.loading = {
		show: function(id, opts) {
			/**
			 * Compatibility with old version of fw.loading.show()
			 * which didn't had the (id,opts) parameters
			 */
			if (typeof id == 'undefined') {
				id = 'main';
			}

			return inst.show(id, opts);
		},
		hide: function(id) {
			/**
			 * Compatibility with old version of fw.loading.hide()
			 * which didn't had the (id) parameter
			 */
			if (typeof id == 'undefined') {
				id = 'main';
			}

			return inst.hide(id);
		}
	};
})(jQuery);

/**
 * fw.notify
 * Non-blocking top-center toast notifications (a modern replacement for alert()).
 *
 * Usage:
 * - fw.notify('Saved.', 'success');
 * - fw.notify('Something went wrong', 'error', { id: 'my-error' });
 * - var t = fw.notify('Working…', 'info', { duration: 0 }); t.dismiss();
 *
 * @param {String} message  Message text (plain text; HTML is escaped)
 * @param {String} [type]   'info' (default) | 'success' | 'warning' | 'error'
 * @param {Object} [opts]   { duration:Number (ms, 0 = sticky), dismissible:Boolean, id:String }
 *                          - errors are sticky (duration 0) by default; others auto-hide after 6s
 *                          - reusing an `id` replaces the previous toast with that id
 *                            (so a retry loop won't stack identical messages)
 * @returns {Object} handle with a .dismiss() method and the toast jQuery element ($el)
 */
(function ($) {
	var $container = null;

	// Leading dashicon per type (dashicons are always loaded in wp-admin)
	var ICONS = {
		error:   'dismiss',
		success: 'yes-alt',
		warning: 'warning',
		info:    'info'
	};

	function getContainer() {
		// lazy init (and re-create if it was removed from the DOM)
		if ($container === null || !$container.parent().length) {
			$container = $(
				'<div id="fw-notify" class="fw-notify" aria-live="polite" aria-atomic="false"></div>'
			);
			$(document.body).append($container);
		}

		return $container;
	}

	function escapeHtml(str) {
		return $('<div></div>').text(str == null ? '' : String(str)).html();
	}

	function dismiss($toast) {
		if (!$toast || !$toast.length || $toast.data('fw-closing')) {
			return;
		}

		$toast
			.data('fw-closing', true)
			.removeClass('fw-notify-in')
			.addClass('fw-notify-out');

		setTimeout(function () {
			$toast.remove();
		}, 300); // must match the CSS transition duration
	}

	fw.notify = function (message, type, opts) {
		type = type || 'info';

		opts = $.extend({
			duration: type === 'error' ? 0 : 6000, // errors are sticky by default
			dismissible: true,
			id: null
		}, opts || {});

		var $wrap = getContainer();

		// De-dupe by id: replace any existing toast carrying the same id
		if (opts.id) {
			$wrap.children('[data-fw-notify-id="'+ String(opts.id).replace(/"/g, '') +'"]')
				.each(function () { dismiss($(this)); });
		}

		var $toast = $(
			'<div class="fw-notify-item fw-notify-'+ type +'"'+
				(type === 'error' ? ' role="alert"' : ' role="status"') +'>'+
				'<span class="fw-notify-icon dashicons dashicons-'+ (ICONS[type] || 'info') +'" aria-hidden="true"></span>'+
				'<span class="fw-notify-msg">'+ escapeHtml(message) +'</span>'+
				(opts.dismissible
					? '<button type="button" class="fw-notify-close" aria-label="Dismiss">&times;</button>'
					: '') +
			'</div>'
		);

		if (opts.id) {
			$toast.attr('data-fw-notify-id', opts.id);
		}

		$wrap.append($toast);

		// force reflow so the enter transition runs
		$toast[0].offsetHeight;
		$toast.addClass('fw-notify-in');

		var timer = 0;
		if (opts.duration > 0) {
			timer = setTimeout(function () { dismiss($toast); }, opts.duration);
		}

		$toast.on('click', '.fw-notify-close', function () {
			clearTimeout(timer);
			dismiss($toast);
		});

		return {
			dismiss: function () { clearTimeout(timer); dismiss($toast); },
			$el: $toast
		};
	};
})(jQuery);

/**
 * fw.validateOptionsForm — client-side inline validation for a backend options
 * <form> (Phase 3).
 *
 * Uses the browser Constraint Validation API, so any option whose input carries
 * standard HTML5 constraints validates automatically. Authors opt a field in via
 * the option's `attr`, e.g.:
 *
 *   'attr' => array( 'required' => 'required' )
 *   'attr' => array( 'type' => 'email' )
 *   'attr' => array( 'pattern' => '[A-Za-z]+', 'data-fw-error-message' => 'Letters only' )
 *
 * Invalid fields get a styled inline message next to the field (instead of the
 * browser's native bubble — the form is rendered with `novalidate`), the first
 * error's tab is revealed + scrolled into view, and a summary toast is shown.
 * Errors clear automatically as each field becomes valid again.
 *
 * @param {jQuery} $form
 * @returns {Boolean} true when the form is valid (safe to submit)
 */
(function ($) {
	function clearErrors($form) {
		$form.find('.fw-backend-option-error').remove();
		$form.find('.fw-backend-option.fw-backend-option-has-error')
			.removeClass('fw-backend-option-has-error');
	}

	// Inject a styled inline error next to a field (its closest .fw-backend-option)
	function showFieldError($field, message) {
		var $option = $field.closest('.fw-backend-option');
		if (!$option.length) { $option = $field.parent(); }

		var $target = $option.find('.fw-backend-option-input').first();
		if (!$target.length) { $target = $option; }

		$option.addClass('fw-backend-option-has-error');
		$target.append(
			$('<div class="fw-backend-option-error" role="alert"></div>').text(message)
		);
	}

	// Auto-clear a field's error once it's edited (and passes native validation,
	// if it has any). Shared by the client + server error paths.
	function ensureAutoClear($form) {
		$form
			.off('input.fwOptValidate change.fwOptValidate')
			.on('input.fwOptValidate change.fwOptValidate', function (e) {
				var el = e.target;
				if (!el) { return; }

				var $option = $(el).closest('.fw-backend-option.fw-backend-option-has-error');
				if (!$option.length) { return; }

				// Clear when the field passes native validation again, or when it
				// has no native constraints at all (server-only error → on edit).
				if (!el.willValidate || el.checkValidity()) {
					$option.removeClass('fw-backend-option-has-error')
						.find('.fw-backend-option-error').remove();
				}
			});
	}

	function notifyCount(n) {
		fw.notify(
			n === 1
				? 'Please fix the highlighted field.'
				: 'Please fix the ' + n + ' highlighted fields.',
			'warning',
			{ id: 'fw-options-modal:validation' }
		);
	}

	fw.validateOptionsForm = function ($form) {
		if (!$form || !$form.length) { return true; }

		clearErrors($form);

		var invalid = [];

		$.each($form[0].elements, function () {
			var el = this;

			// willValidate is false for hidden/disabled/buttons — skip those
			if (!el.willValidate) { return; }
			if ($(el).hasClass('hidden-submit')) { return; }
			if (el.checkValidity()) { return; }

			invalid.push(el);
		});

		if (!invalid.length) { return true; }

		$.each(invalid, function (i, el) {
			showFieldError(
				$(el),
				el.getAttribute('data-fw-error-message')
					|| el.validationMessage
					|| 'This field is invalid.'
			);
		});

		ensureAutoClear($form);
		fw.revealOptionField($(invalid[0]));
		notifyCount(invalid.length);

		return false;
	};

	/**
	 * Render server-side validation errors (Phase 3b) inline, reusing the exact
	 * same UI as the client-side validator. `errors` is the map returned by the
	 * save endpoint as response.data.errors: { option_id: 'message', ... }.
	 *
	 * @param {jQuery} $form
	 * @param {Object} errors  { option_id: 'message', ... }
	 * @returns {Number} how many errors were rendered
	 */
	fw.showOptionsErrors = function ($form, errors) {
		if (!$form || !$form.length || !errors) { return 0; }

		clearErrors($form);

		var $first = null, count = 0;

		$.each(errors, function (id, message) {
			var safeId = String(id).replace(/"/g, ''),
				$field = $form.find('[data-fw-option-id="'+ safeId +'"]').first();

			if (!$field.length) {
				// fall back to the input name used inside the options modal
				$field = $form.find('[name="fw_edit_options_modal['+ safeId +']"]').first();
			}
			if (!$field.length) { return; }

			showFieldError($field, message);
			if (!$first) { $first = $field; }
			count++;
		});

		ensureAutoClear($form);

		if ($first) { fw.revealOptionField($first); }
		if (count)  { notifyCount(count); }

		return count;
	};

	/**
	 * Reveal an option field that may be on an inactive tab: activate each
	 * ancestor option tab (outermost first), then scroll it into view + focus.
	 *
	 * @param {jQuery} $el a field input/element inside a backend options form
	 */
	fw.revealOptionField = function ($el) {
		if (!$el || !$el.length) { return; }

		var $form = $el.closest('form');

		// jQuery-UI option tabs: clicking the matching nav anchor switches tab
		$.each($el.parents('.fw-options-tab').get().reverse(), function (i, panel) {
			if (!panel.id) { return; }
			var $link = ($form.length ? $form : $(document))
				.find('.fw-options-tabs-list a[href="#'+ panel.id +'"]').first();
			if ($link.length) { $link.trigger('click'); }
		});

		try { $el[0].scrollIntoView({ block: 'center' }); }
		catch (e) { $el[0].scrollIntoView(); }

		try { $el.trigger('focus'); } catch (e) {}
	};
})(jQuery);

/**
 * Capitalizes the first letter of a string.
 */
fw.capitalizeFirstLetter = function(str) {
	str = str == null ? '' : String(str);
	return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Wait until an array of dynamically computed jQuery.Deferred() objects
 * get resolved.
 */
fw.whenAll = function(deferreds) {
	var deferred = new jQuery.Deferred();

	jQuery.when.apply(jQuery, deferreds).then(
		function() {
			deferred.resolve(Array.prototype.slice.call(arguments));
		},
		function() {
			deferred.fail(Array.prototype.slice.call(arguments));
		}
	);

	return deferred;
}

/**
 * Set nested property value
 *
 * Usage:
 * var obj = {foo: {}};
 * fw.ops('foo/bar', 'ok', obj); // {foo: {bar: 'ok'}}
 *
 * @param {String} properties 'a.b.c'
 * @param {*} value
 * @param {Object} obj
 * @param {String} [delimiter] Default '/'
 */
fw.ops = function(properties, value, obj, delimiter) {
	delimiter = delimiter || '/';

	if (typeof properties == 'string') {
		properties = properties.split(delimiter);
	} else {
		properties = [properties];
	}

	var property = properties.shift();

	if (properties.length) {
		properties = properties.join(delimiter);

		if (typeof obj[property] == 'undefined') {
			obj[property] = {};
		} else if (typeof obj[property] != 'object') {
			console.warn(
				'[fw.ops] Object property "'+ property +'" already has non object value:', obj[property],
				'It will be replaced with an empty object'
			);

			obj[property] = {};
		} else if (typeof obj[property] == 'object' && typeof obj[property].length != 'undefined') {
			// it's array, check if property is integer
			if ((/^[0-9]+$/).test(property)) {
				property = parseInt(property);
			} else {
				console.warn(
					'[fw.ops] Try to set non numeric property "'+ property +'" to array:', obj[property],
					'The array will be be replaced with an empty object'
				);

				obj[property] = {};
			}
		}

		fw.ops(properties, value, obj[property], delimiter);
	} else {
		obj[property] = value;
	}

	return obj;
};

/**
 * Get nested property value
 *
 * Usage:
 * var obj = {foo: {bar: 'ok'}};
 * fw.opg('foo/bar', obj); // 'ok'
 *
 * @param {String} properties 'a.b.c'
 * @param {Object} obj
 * @param {*} [defaultValue] If property will not exist
 * @param {String} [delimiter] Default '/'
 */
fw.opg = function(properties, obj, defaultValue, delimiter) {
	delimiter = delimiter || '/';

	if (typeof properties == 'string') {
		properties = properties.split(delimiter);
	} else {
		properties = [properties];
	}

	var property = properties.shift();

	if (typeof obj[property] == 'undefined') {
		return defaultValue;
	}

	if (properties.length) {
		properties = properties.join(delimiter);

		return fw.opg(properties, obj[property], defaultValue, delimiter);
	} else {
		return obj[property];
	}
};

/**
 * Unique random md5
 * @returns {String}
 */
fw.randomMD5 = function() {
	return String(
		fw.md5(
			Math.random() +'-'+ Math.random() +'-'+ Math.random()
		)
	);
};

/**
 * Return value from QueryString
 * @param name
 * @returns {string}
 */
fw.getQueryString = function(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		results = regex.exec(location.search);
	return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
};

(function(){
	/**
	 * Modal open/close animation duration. Single source of truth — keep in sync
	 * with the `.3s` fwGrowIn / fwGrowOut animations in fw.css. Used as the fallback
	 * timeout for the animationend-driven close (see below).
	 */
	var MODAL_ANIM_MS = 300;

	/**
	 * A stack-like structure to manage chains of modals
	 * (modals that are opened one from another)
	 */
	var modalsStack = {
		_stack: [],
		push: function(modal) {
			this._stack.push(modal);
		},
		pop: function() {
			return this._stack.pop();
		},
		peek: function() {
			return this._stack[this._stack.length - 1];
		},
		getSize: function() {
			return this._stack.length;
		}
	};

	/**
	 * Generic modal
	 *
	 * Usage:
	 * var modal = new fw.Modal();
	 *
	 * You can add a custom CSS class to your fw.Modal this way:
	 *
	 * var modal = new fw.Modal ({
	 *   modalCustomClass: 'your-custom-css-class'
	 * });
	 *
	 * modal.on('open|render|closing|close', function(){});
	 */
	fw.Modal = Backbone.Model.extend({
		defaults: {
			/* Modal title */
			title: 'Edit Options',
			headerElements: '',
			/**
			 * Content html
			 * @private
			 */
			html: '',
			modalCustomClass: '',
			emptyHtmlOnClose: true,
			disableLazyTabs: false,
			size: 'small' // small, medium, large
		},
		ContentView: Backbone.View.extend({
			tagName: 'form',
			attributes: {
				'onsubmit': 'return false;'
			},
			onSubmit: function(e) {
				e.preventDefault();

				this.model.trigger('submit', {
					$form: this.$el
				});
			},
			render: function() {
				this.$el.html(this.model.get('html'));

				if (this.model.get('html').length) {
					fwEvents.trigger('fw:options:init', {$elements: this.$el});

					this.model.trigger('render');

					this.afterHtmlReplaceFixes();
				}
			},
			renderSizeClass: function () {
				var $modalWrapper = this.model.frame.modal.$el;
				var allSizes = ['large', 'medium', 'small'];

				$modalWrapper.removeClass(
					_.map(allSizes, formSizeClass).join(' ')
				).addClass(formSizeClass(this.model.get('size')));

				function formSizeClass (size) { return 'fw-modal-' + size; }
			},
			initialize: function() {
				this.listenTo(this.model, 'change:html', this.render);
				this.listenTo(this.model, 'change:size', this.renderSizeClass);
			},
			/**
			 * Call this after html replace
			 * this.$el.html('...');
			 * this.afterHtmlReplaceFixes();
			 */
			afterHtmlReplaceFixes: function() {
				/* options fixes */
				{
					// hide last border
					this.$el.prepend('<div class="fw-backend-options-last-border-hider"></div>');

					// hide last border from tabs
					this.$el.find('.fw-options-tabs-contents > .fw-inner > .fw-options-tab')
						.append('<div class="fw-backend-options-last-border-hider"></div>');
				}

				this.$el.append('<input type="submit" class="fw-hidden hidden-submit" />');

				/**
				 * The user may want to completely disable lazy tabs for the
				 * current modal. It is VERY convenient sometimes.
				 */
				if (this.model.get('disableLazyTabs')) {
					fwEvents.trigger('fw:options:init:tabs', {
						$elements: this.model.frame.$el
					});
				}
			}
		}),
		/**
		 * Create and init this.frame
		 */
		initializeFrame: function(settings) {
			settings = settings || {};

			var modal = this;

			var ControllerMainState = wp.media.controller.State.extend({
				defaults: {
					id: 'main',
					content: 'main',
					menu: 'default',
					title: this.get('title'),
					headerElements: this.get('headerElements')
				},
				initialize: function() {
					this.listenTo(modal, 'change:title', function(){
						this.set('title', modal.get('title'));
					});
				},
				activate: function () {
					this.frame.once('ready', _.bind(function(){
						this.frame.views.get('.media-frame-title')[0].$el
							.text(this.get('title'))
							.append(this.get('headerElements') || '');
					}, this));
				}
			});

			this.frame = new wp.media.view.MediaFrame({
				state: 'main',
				states: [ new ControllerMainState ],
				uploader: false
			});

			var modal = this;

			this.frame.once('ready', function(){
				var $modalWrapper    = modal.frame.modal.$el,
					$backdrop        = $modalWrapper.find('.media-modal-backdrop'),
					size             = modal.get('size'),
					modalCustomClass = modal.get('modalCustomClass'),
					$close           = $modalWrapper.find('.media-modal-close');

				modal.frame.$el.addClass('hide-toolbar');

				$modalWrapper.addClass('fw-modal');

				if (modalCustomClass) {
					$modalWrapper.addClass(modalCustomClass);
				}

				if (_.indexOf(['large', 'medium', 'small'], size) !== -1) {
					$modalWrapper.addClass('fw-modal-' + size);
				} else {
					$modalWrapper.addClass('fw-modal-' + modal.defaults.size);
				}

				/**
				 * Draggable, non-blocking options panel. Drag the box by its header
				 * (.media-frame-title) so it can be moved off whatever you're editing
				 * (Divi/Elementor-style). Opens centred every time. jQuery UI draggable
				 * ships with WP admin.
				 *
				 * NB: this 'ready' block runs while the modal is still hidden (0×0), so
				 * we must NOT read/pin its geometry here — we only ATTACH draggable.
				 * Centring the CSS-centred fixed box to explicit top/left/size happens on
				 * the modal's `open` event (element is visible then → no jump, and jQuery
				 * UI reads the real origin).
				 */
				(function () {
					if (!jQuery.fn.draggable) { return; }
					var $box = $modalWrapper.find('.media-modal');
					if (!$box.length) { return; }

					// The panel may be parked mostly off any edge (so the canvas beneath
					// shows through), but a grabbable strip of the header must always stay
					// on-screen or the panel would be lost. Never allow the header to leave
					// the top; keep at least KEEP_X px horizontally and KEEP_BOTTOM px of the
					// header reachable at the bottom.
					var KEEP_X = 90, KEEP_BOTTOM = 44;
					var clampPos = function (top, left, w, h) {
						top  = Math.max(0, Math.min(top,  window.innerHeight - KEEP_BOTTOM));
						left = Math.max(KEEP_X - w, Math.min(left, window.innerWidth - KEEP_X));
						return { top: top, left: left };
					};

					var pin = function (top, left) {
						// Lock the box's CURRENT rendered size before repositioning. The
						// modal has no fixed CSS width — it stretches between left:30/right:30
						// (capped at max-width/height), so dropping right/bottom to `auto`
						// would collapse it to 0×0. Measure first, then apply explicit w/h.
						var w = $box.outerWidth(), h = $box.outerHeight();
						var c = clampPos(top, left, w, h);
						$box.css({ position: 'fixed', top: c.top + 'px', left: c.left + 'px', right: 'auto', bottom: 'auto', width: w + 'px', height: h + 'px', margin: 0 });
					};

					// Open the panel CENTRED in the viewport every time (center-center).
					// Runs on `open` (not the hidden `ready` above, and not in draggable's
					// start, which is too late — jQuery UI has already captured the origin),
					// so the box is visible and pin() reads a real size. A second panel
					// opened while the first is still up (modal-stack level > 0) cascades by a
					// small offset so it doesn't sit exactly on top of the one beneath it —
					// the primary (level 0) panel stays dead-centre.
					var stackLevel = function () {
						var m = ($modalWrapper.attr('class') || '').match(/fw-modal-level-(\d+)/);
						return m ? parseInt(m[1], 10) : 0;
					};
					var applyPosition = function () {
						var w = $box.outerWidth(), h = $box.outerHeight();
						var off  = stackLevel() * 32;
						var top  = Math.round((window.innerHeight - h) / 2) + off;
						var left = Math.round((window.innerWidth  - w) / 2) + off;
						pin(top, left); // pin() clamps to keep a grabbable strip on-screen
					};
					modal.on('open', function () {
						setTimeout(applyPosition, 60);
						// WP core locks page scroll (body.modal-open { overflow:hidden })
						// while any media modal is open. Our panel is non-blocking, so keep
						// the page editor scrollable — a body marker class (see CSS) undoes
						// the lock while a draggable panel is open.
						jQuery('body').addClass('fw-nonblocking-modal-open');
					});

					// Keep the panel reachable if the window is resized smaller — the
					// drag/open clamps only fire on those events, so re-clamp on resize too.
					jQuery(window).off('resize.fwModalDrag').on('resize.fwModalDrag', function () {
						if ($box.is(':visible')) {
							var r = $box[0].getBoundingClientRect();
							pin(r.top, r.left);
						}
					});
					modal.on('close', function () {
						jQuery(window).off('resize.fwModalDrag');
						// Restore the scroll lock only once NO draggable panel remains open
						// (this panel's fw-modal-open is already removed by the time close
						// fires). If a nested/second panel is still up, leave it unlocked.
						if (!jQuery('.fw-modal-draggable.fw-modal-open').length) {
							jQuery('body').removeClass('fw-nonblocking-modal-open');
						}
					});

					$modalWrapper.addClass('fw-modal-draggable');

					// Hover-driven focus backdrop: dim the page behind ONLY while the
					// cursor is inside the panel (see the matching CSS). Toggled here rather
					// than with a CSS :hover sibling selector so it doesn't depend on the
					// backdrop's DOM order relative to the box.
					$box.on('mouseenter.fwHover', function () { $modalWrapper.addClass('fw-modal-hover'); })
					    .on('mouseleave.fwHover', function () { $modalWrapper.removeClass('fw-modal-hover'); });

					$box.draggable({
						handle: '.media-frame-title',
						// No hard containment — allow parking mostly off-screen, but clamp
						// live so a grabbable strip of the header always stays visible.
						drag: function (e, ui) {
							var c = clampPos(ui.position.top, ui.position.left, $box.outerWidth(), $box.outerHeight());
							ui.position.top = c.top;
							ui.position.left = c.left;
						},
						cancel: '.media-modal-close, input, textarea, select, button, a, .fw-device-tabs'
					});
				})();

				/**
				 * Show effect on close
				 */
				(function(){
					var eventsNamespace = '.fwModalCloseEffect';
					var closing       = false;
					var fallbackTimer = 0;

					var finishClose = function(){
						if (!closing) { return; }
						closing = false;

						clearTimeout(fallbackTimer);
						$modalWrapper.off('animationend'+ eventsNamespace);

						// remove events that prevent original close
						$close.off(eventsNamespace);
						$backdrop.off(eventsNamespace);

						// fire original close process after animation effect finished
						$close.trigger('click');
						$backdrop.trigger('click');

						// remove animation class
						$modalWrapper.removeClass('fw-modal-closing');

						preventOriginalClose();
					};

					var closeEffect = function(){
						if (closing) { return; }
						closing = true;

						// begin css animation
						$modalWrapper.addClass('fw-modal-closing');

						/**
						 * Close once the fwGrowOut animation on .media-modal-content
						 * actually ends — instead of a hardcoded timer that had to be
						 * kept in sync with the CSS duration. A timed fallback still
						 * fires in case animationend never does (prefers-reduced-motion,
						 * the element being display:none, etc.) so the modal can never
						 * get stuck open. The animationName guard ignores animationend
						 * events bubbling up from descendant elements.
						 */
						$modalWrapper.on('animationend'+ eventsNamespace, function (e) {
							var ev = e.originalEvent || e;
							if (ev.animationName === 'fwGrowOut') {
								finishClose();
							}
						});

						fallbackTimer = setTimeout(finishClose, MODAL_ANIM_MS + 50);
					};

					function handleCloseClick(e) {
						e.stopPropagation();
						e.preventDefault();

						if (closing) {
							// do nothing while a close animation is already in progress
							return;
						}

						closeEffect();

						modal.trigger('closing');
					}

					// add events that prevent original close
					function preventOriginalClose() {
						$close.on('click'+ eventsNamespace, handleCloseClick);
						$backdrop.on('click'+ eventsNamespace, handleCloseClick);
					}

					preventOriginalClose();
				})();
			});

			this.frame.on('open', function() {
				var $modalWrapper = modal.frame.modal.$el,
					$modal = $modalWrapper.find('.media-modal'),
					$backdrop = $modalWrapper.find('.media-modal-backdrop');

				// Adjust modal level related properties
				{
					var stackSize = modalsStack.getSize();

					$modalWrapper
						.removeClass( // 'fw-modal-level-0 fw-modal-level-1 ...'
							Array.apply(null, {length: 10})
								.map(Number.call, Number)
								.map(function(i){ return 'fw-modal-level-'+ i; })
								.join(' ')
						)
						.addClass('fw-modal-level-'+ stackSize);

					if (stackSize) {
						$modal.css({
							border: (stackSize * 30) +'px solid transparent'
						});
					}

					// reset to initial css value
					// fixes https://github.com/ThemeFuse/Unyson/issues/2167
					$modal.css('z-index', '');

					/**
					 * Adjust the z-index for the new frame's backdrop and modal
					 */
					$backdrop.css('z-index',
						/**
						 * Use modal z-index because backdrop z-index in some cases can be too smaller
						 * and when there are 2+ modals open, first modal will cover the second backdrop
						 *
						 * For e.g.
						 *
						 * - second modal | z-index: 560003
						 * - second backdrop | z-index: 559902
						 *
						 * - first modal | z-index: 560002 (This will cover the above backdrop)
						 * - first backdrop | z-index: 559901
						 */
						parseInt($modal.css('z-index'))
						+ stackSize * 2 + 1
					);

					$modal.css('z-index',
						parseInt($modal.css('z-index'))
						+ stackSize * 2 + 2
					);
				}

				$modalWrapper.addClass('fw-modal-open');

				/**
				 * We probably don't need this class anymore due to the
				 * fact that we hacked CSS specificity away.
				 *
				 * I'll be keeping it here for integrity with fw.soleModal.
				 */
				$modalWrapper.addClass('fw-modal-opening');

				setTimeout(function () {
					$modalWrapper.removeClass('fw-modal-opening');
				}, MODAL_ANIM_MS);

				modalsStack.push($modalWrapper.find('.media-modal'));

				// Resize .fw-options-tabs-contents to fit entire window
				{
					modal.on('change:html', modal.resizeTabsContent);

					jQuery(window).on('resize.resizeTabsContent', function () {
						modal.resizeTabsContent();
					});
				}

				modal.trigger('open');
			});

			this.frame.on('close', function(){
				// Stop tracking modal HTML and window size
				{
					modal.off('change:html', modal.resizeTabsContent);

					jQuery(window).off('resize.resizeTabsContent');
				}

				/**
				 * clear html
				 * to prevent same ids in html when another modal with same options will be opened
				 */
				if (modal.get('emptyHtmlOnClose')) {
					modal.set('html', '');
				}

				modal.frame.modal.$el.removeClass('fw-modal-open');

				modalsStack.pop();

				modal.trigger('close');
			});

			this.frame.on('content:create:main', function () {
				modal.frame.content.set(
					modal.content
				);
			});
		},
		/**
		 * Create and init this.content
		 */
		initializeContent: function() {
			this.content = new this.ContentView({
				controller: this.frame,
				model: this
			});

			/**
			 * This allows to access from DOM the modal instance
			 */
			jQuery.data(this.content.el, 'modal', this);
		},
		initialize: function(attributes, settings) {
			this.initializeFrame(settings);
			this.initializeContent();
		},
		open: function() {
			this.frame.open();

			var modal = this;

			this.once('closing', function () {
				fwEvents.trigger(
					'fw:options:teardown',
					{ $elements: modal.content.$el, modal: modal }
				);
			});

			return this;
		},
		close: function() {
			this.frame.$el.closest('.media-modal').find('.media-modal-close:first').trigger('click');

			return this;
		},
		/**
		 * Resize .fw-options-tabs-contents to fit entire window
		 */
		resizeTabsContent: function () {
			var $content, $frame;

			$content = this.frame.$el.find('.fw-options-tabs-first-level > .fw-options-tabs-contents');
			if ($content.length == 0) {
				return;
			}

			$frame = $content.closest('.media-frame-content');

			// resize icon list to fit entire window
			$content.css('overflow-y', 'auto').height(1000000);
			$frame.scrollTop(1000000);

			// -1 is necessary for Linux and Windows
			// -2 is necessary for Mac OS
			// I don't know where this numbers come from but without this adjustment
			// vertical scroll bar appears.
			$content.height($content.height() - $frame.scrollTop() /* - 2  */);

			// This is another fix for vertical scroll bar issue
			$frame.css('overflow-y', 'hidden');
		}
	});
})();

/**
 * @param {String} [data] An object with two keys:
 *                        options: Your array with option types
 *                        data: a string that will contain correctly serialized data
 *                              Ex.: "parameter1=val1&par2=value"
 *
 * @returns {Promise} jQuery promise you can use in order to get your values
 *
 * modal.getValuesFromServer({options: your_options})
 *     .done(function (response, status, xhr) {
 *         console.log(response.data.values); // your values ready to be used
 *     })
 *     .fail(function (xhr, status, error) {
 *         // handle errors
 *         console.error(status + ': ' + error);
 *     });
 */
fw.getValuesFromServer = function (data) {
	var opts = _.extend({
		options: [],
		actualValues: ""
	}, data);

	if (opts.options.length === 0) { return {}; }

	var dataToSend = [
		'action=fw_backend_options_get_values',
		'_nonce='+ encodeURIComponent(typeof _fw_backend_options_localized !== 'undefined' ? _fw_backend_options_localized.nonce : ''),
		'options='+ encodeURIComponent(JSON.stringify(opts.options)),
		'name_prefix=fw_edit_options_modal'
	];

	if (opts.actualValues) {
		dataToSend.push(opts.actualValues);
	}

	return jQuery.ajax({
		url: ajaxurl,
		type: 'POST',
		data: dataToSend.join('&'),
		dataType: 'json'
	});
};

(function(){
	var fwLoadingId = 'fw-options-modal';

	/**
	 * Modal to edit backend options
	 *
	 * Usage:
	 * var modal = new fw.OptionsModal({
	 *  title: 'Custom Title',
	 *  options: [
	 *      {'test1': {
	 *          'type': 'text',
	 *          'label': 'Test1'
	 *      }},
	 *      {'test2': {
	 *          'type': 'textarea',
	 *          'label': 'Test2'
	 *      }}
	 *  ],
	 *  values: {
	 *      'test1': 'Default1',
	 *      'test2': 'Default2'
	 *  },
	 *  modalCustomClass: 'some-custom-class' // if you want to add some css class
	 *                                        // to your modal
	 * });
	 *
	 * // listen for values change
	 * modal.on('change:values', function(modal, values) {
	 *  // do something with values
	 * });
	 *
	 * // replace values
	 * modal.set('values', { ... });
	 *
	 * modal.open();
	 */
	fw.OptionsModal = fw.Modal.extend({
		ContentView: fw.Modal.prototype.ContentView.extend({
			/**
			 * novalidate: take over the browser's native constraint validation so
			 * we can render styled inline errors next to each field instead of the
			 * native popup bubble (see fw.validateOptionsForm).
			 */
			attributes: _.extend(
				{},
				fw.Modal.prototype.ContentView.prototype.attributes,
				{ novalidate: 'novalidate' }
			),
			events: {
				'submit': 'onSubmit'
			},
			onSubmit: function(e) {
				e.preventDefault();

				var loadingId = fwLoadingId +':submit',
					view = this;

				/**
				 * Init all Lazy Tabs to render all form inputs FIRST, so every
				 * input exists both for validation and for the serialize() below.
				 * Lazy Tabs script is listening the form 'submit' event
				 * but it's executed after this event.
				 */
				fwEvents.trigger('fw:options:init:tabs', {$elements: view.$el});

				/**
				 * Phase 3: client-side inline validation gate. If any field with
				 * HTML5 constraints is invalid, render inline errors and abort the
				 * save (don't send known-bad input to the server).
				 */
				if (!fw.validateOptionsForm(view.$el)) {
					return;
				}

				fw.loading.show(loadingId);

				// `_fw_validate=1` opts this save into the server-side validation
				// gate (Phase 3b). Other getValuesFromServer/reset callers omit it,
				// so their behaviour is unchanged.
				view.model.getValuesFromServer(view.$el.serialize() + '&_fw_validate=1')
					.done(function (response, status, xhr) {
						fw.loading.hide(loadingId);

						if (!response.success) {
							/**
							 * do not replace html here
							 * user completed the form with data and wants to submit data
							 * do not delete all his work
							 */

							// Phase 3b: server-side per-field validation errors —
							// render them inline next to each field (same UI as the
							// client-side validator) instead of a global toast.
							if (response.data && response.data.errors
								&& fw.showOptionsErrors(view.$el, response.data.errors)
							) {
								return;
							}

							fw.notify(response.data.message, 'error', {id: 'fw-options-modal:error'});
							return;
						}

						/**
						 * Make sure the second set() will trigger the 'change' event
						 * Fixes https://github.com/ThemeFuse/Unyson/issues/1998#issuecomment-248671721
						 */
						view.model.set('values', {}, {silent: true});
						view.model.set('values', response.data.values);

						/**
						 * Phase 4: pre-warm the render cache for the just-saved
						 * values so the next time this element is opened it's an
						 * instant cache hit (no AJAX spinner). Fire-and-forget; uses
						 * the canonical server render (NOT DOM reuse), so it can't
						 * introduce duplicate ids or half-initialized widgets.
						 */
						try {
							fw.options.fetchHtml(view.model.get('options'), response.data.values);
						} catch (e) {}

						if (! view.model.frame.$el.hasClass('fw-options-modal-no-close')) {
							// simulate click on close button to fire animations
							view.model.frame.modal.$el.find('.media-modal-close').trigger('click');
						}

						view.model.frame.$el.removeClass('fw-options-modal-no-close');
					})
					.fail(function (xhr, status, error) {
						fw.loading.hide(loadingId);

						/**
						 * do not replace html here
						 * user completed the form with data and wants to submit data
						 * do not delete all his work
						 */
						fw.notify(status +': '+ error.message, 'error', {id: 'fw-options-modal:error'});
					});
			},
			resetForm: function() {
				var loadingId = fwLoadingId +':reset';

				fw.loading.show(loadingId);

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: [
						'action=fw_backend_options_get_values',
						'_nonce='+ encodeURIComponent(typeof _fw_backend_options_localized !== 'undefined' ? _fw_backend_options_localized.nonce : ''),
						'options='+ encodeURIComponent(JSON.stringify(this.model.get('options'))),
						'name_prefix=fw_edit_options_modal'
					].join('&'),
					dataType: 'json',
					success: _.bind(function (response, status, xhr) {
						fw.loading.hide(loadingId);

						if (!response.success) {
							/**
							 * do not replace html here
							 * user completed the form with data and wants to submit data
							 * do not delete all his work
							 */
							fw.notify(response.data.message, 'error', {id: 'fw-options-modal:error'});
							return;
						}

						// make sure on the below open, the html 'change' will be fired
						this.model.set('html', '', {
							silent: true // right now we don't need modal reRender, only when the open below
						});

						this.model.open(response.data.values);
					}, this),
					error: function (xhr, status, error) {
						fw.loading.hide(loadingId);

						/**
						 * do not replace html here
						 * user completed the form with data and wants to submit data
						 * do not delete all his work
						 */
						fw.notify(status +': '+ String(error), 'error', {id: 'fw-options-modal:error'});
					}
				});
			}
		}),
		defaults: _.extend(
			/**
			 * Don't mutate original one!!!
			 */
			{},
			fw.Modal.prototype.defaults,
			{
				/**
				 * Will be transformed to array with json_decode($options, true)
				 * and sent to fw()->backend->render_options()
				 */
				options: [
					{'demo-text': {
						'type': 'text',
						'label': 'Demo Text'
					}},
					{'demo-textarea': {
						'type': 'textarea',
						'label': 'Demo Textarea'
					}}
				],
				/**
				 * Values of the options {'option-id': 'option value'}
				 * also used in fw()->backend->render_options()
				 */
				values: {},

				silentReceiveOfDefaultValues: true
			}
		),
		initialize: function () {
			fw.Modal.prototype.initialize.apply(this, arguments);

			fwEvents.trigger('fw:options-modal:after-initialize', {modal: this});

			// Forward events to fwEvents
			{
				/** @since 2.6.14 */
				this.on('open',  function () { fwEvents.trigger('fw:options-modal:open',  {modal: this}); });

				/** @since 2.6.14 */
				this.on('close', function () { fwEvents.trigger('fw:options-modal:close', {modal: this}); });
			}
		},
		initializeFrame: function(settings) {
			fw.Modal.prototype.initializeFrame.call(this, settings);

			settings = settings || {};

			var modal = this,
				buttons = [
					{
						style: 'primary',
						text: settings.saveText || _fw_localized.l10n.modal_save_btn,
						priority: 40,
						click: function () {
							if (settings.shouldSaveWithoutClose) {
								modal.frame.$el.addClass('fw-options-modal-no-close');
							}

							triggerSubmit();
						}
					}
				];

			if (!(
				typeof settings.disableResetButton === 'undefined'
					? _fw_localized.options_modal.default_reset_bnt_disabled
					: settings.disableResetButton
			)) {
				buttons = buttons.concat([{
					style: '',
					text: _fw_localized.l10n.reset,
					priority: -1,
					click: function () {
						modal.content.resetForm();
					}
				}]);
			}

			/**
			 * Apply button — saves changes and live re-renders the element on the
			 * canvas WITHOUT closing the modal (adds .fw-options-modal-no-close, which
			 * the save handler honours). Phase 1 of the draggable/non-blocking modal:
			 * shown by DEFAULT now (opt out with saveWithoutCloseButton:false) so every
			 * page-builder element edit gets the "apply & keep editing" workflow.
			 */
			if (settings.saveWithoutCloseButton !== false) {
				buttons = buttons.concat([{
					style: '',
					text: (_fw_localized.l10n && _fw_localized.l10n.apply) || 'Apply',
					priority: 35, // below Save (40) so Apply is LEFT of Save; Save stays rightmost
					click: function () {
						modal.frame.$el.addClass('fw-options-modal-no-close');
						triggerSubmit();
					}
				}]);
			}

			if (settings.buttons) {
				buttons = buttons.concat(settings.buttons);
			}

			this.frame.on('content:create:main', function () {
				modal.frame.toolbar.set(
					new wp.media.view.Toolbar({
						controller: modal.frame,
						items: buttons
					})
				);
			});

			this.frame.once('ready', _.bind(function() {
				this.frame.$el.removeClass('hide-toolbar');
				this.frame.modal.$el.addClass('fw-options-modal');
			}, this));

			function triggerSubmit () {
				/**
				 * Simulate form submit
				 * Important: Empty input[required] must not start form submit
				 *     and must show default browser warning popup "This field is required"
				 */
				modal.content.$el.find('input[type="submit"].hidden-submit').trigger('click');
			}
		},
		/**
		 * @param {Object} [values] Offer custom values for display. The user can reject them by closing the modal
		 */
		open: function(values) {
			fw.Modal.prototype.open.call(this);

			this.updateHtml(values);

			return this;
		},
		/**
		 * @param {String} [actualValues] A string containing correctly serialized
		 *                                data that will be sent to the server.
		 *                                Ex.: "parameter1=val1&par2=value"
		 *
		 * @returns {Promise} jQuery promise you can use in order to get your values
		 *
		 *
		 * modal.getValuesFromServer()
		 *     .done(function (response, status, xhr) {
		 *         console.log(response.data.values); // your values ready to be used
		 *     })
		 *     .fail(function (xhr, status, error) {
		 *         // handle errors
		 *         console.error(status + ': ' + error);
		 *     });
		 */
		getValuesFromServer: function (actualValues) {
			return fw.getValuesFromServer({
				options: this.get('options'),
				actualValues: actualValues
			});
		},
		/**
		 * @returns {Promise} jQuery promise
		 *
		 * Will work out just like getValuesFromServer() did, but it will
		 * also include values that are currently in the form.
		 */
		getActualValues: function () {
			return this.getValuesFromServer(this.content.$el.serialize());
		},

		updateHtml: function(values) {
			fw.loading.show(fwLoadingId);

			this.set('html', '');

			var modal = this;

			var promise = fw.options.fetchHtml(
				this.get('options'),
				typeof values == 'undefined' ? this.get('values') : values
			);

			promise.then(function (html, response) {
				if (response && _.isEmpty(modal.get('values'))) {
					// fixes https://github.com/ThemeFuse/Unyson/issues/1042#issuecomment-244364121
					modal.set(
						'values',
						response.data.default_values,
						{silent: modal.get('silentReceiveOfDefaultValues')}
					);
				}
			});

			promise.always(function (html) {
				fw.loading.hide(fwLoadingId);
				modal.set('html', html);
			});
		}
	});
})();

/*!
 * jquery.base64.js 0.0.3 - https://github.com/yckart/jquery.base64.js
 * Makes Base64 en & -decoding simpler as it is.
 *
 * Based upon: https://gist.github.com/Yaffle/1284012
 *
 * Copyright (c) 2012 Yannick Albert (http://yckart.com)
 * Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php).
 * 2013/02/10
 *
 * Modified
 **/
;(function($) {

	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
		a256 = '',
		r64 = [256],
		r256 = [256],
		i = 0;

	var UTF8 = {

		/**
		 * Encode multi-byte Unicode string into utf-8 multiple single-byte characters
		 * (BMP / basic multilingual plane only)
		 *
		 * Chars in range U+0080 - U+07FF are encoded in 2 chars, U+0800 - U+FFFF in 3 chars
		 *
		 * @param {String} strUni Unicode string to be encoded as UTF-8
		 * @returns {String} encoded string
		 */
		encode: function(strUni) {
			// use regular expressions & String.replace callback function for better efficiency
			// than procedural approaches
			var strUtf = strUni.replace(/[\u0080-\u07ff]/g, // U+0080 - U+07FF => 2 bytes 110yyyyy, 10zzzzzz
				function(c) {
					var cc = c.charCodeAt(0);
					return String.fromCharCode(0xc0 | cc >> 6, 0x80 | cc & 0x3f);
				})
				.replace(/[\u0800-\uffff]/g, // U+0800 - U+FFFF => 3 bytes 1110xxxx, 10yyyyyy, 10zzzzzz
				function(c) {
					var cc = c.charCodeAt(0);
					return String.fromCharCode(0xe0 | cc >> 12, 0x80 | cc >> 6 & 0x3F, 0x80 | cc & 0x3f);
				});
			return strUtf;
		},

		/**
		 * Decode utf-8 encoded string back into multi-byte Unicode characters
		 *
		 * @param {String} strUtf UTF-8 string to be decoded back to Unicode
		 * @returns {String} decoded string
		 */
		decode: function(strUtf) {
			// note: decode 3-byte chars first as decoded 2-byte strings could appear to be 3-byte char!
			var strUni = strUtf.replace(/[\u00e0-\u00ef][\u0080-\u00bf][\u0080-\u00bf]/g, // 3-byte chars
				function(c) { // (note parentheses for precence)
					var cc = ((c.charCodeAt(0) & 0x0f) << 12) | ((c.charCodeAt(1) & 0x3f) << 6) | (c.charCodeAt(2) & 0x3f);
					return String.fromCharCode(cc);
				})
				.replace(/[\u00c0-\u00df][\u0080-\u00bf]/g, // 2-byte chars
				function(c) { // (note parentheses for precence)
					var cc = (c.charCodeAt(0) & 0x1f) << 6 | c.charCodeAt(1) & 0x3f;
					return String.fromCharCode(cc);
				});
			return strUni;
		}
	};

	while(i < 256) {
		var c = String.fromCharCode(i);
		a256 += c;
		r256[i] = i;
		r64[i] = b64.indexOf(c);
		++i;
	}

	function code(s, discard, alpha, beta, w1, w2) {
		s = String(s);
		var buffer = 0,
			i = 0,
			length = s.length,
			result = '',
			bitsInBuffer = 0;

		while(i < length) {
			var c = s.charCodeAt(i);
			c = c < 256 ? alpha[c] : -1;

			buffer = (buffer << w1) + c;
			bitsInBuffer += w1;

			while(bitsInBuffer >= w2) {
				bitsInBuffer -= w2;
				var tmp = buffer >> bitsInBuffer;
				result += beta.charAt(tmp);
				buffer ^= tmp << bitsInBuffer;
			}
			++i;
		}
		if(!discard && bitsInBuffer > 0) result += beta.charAt(buffer << (w2 - bitsInBuffer));
		return result;
	}

	var Plugin = function(dir, input, encode) {
		return input ? Plugin[dir](input, encode) : dir ? null : this;
	};

	Plugin.btoa = Plugin.encode = function(plain, utf8encode) {
		plain = Plugin.raw === false || Plugin.utf8encode || utf8encode ? UTF8.encode(plain) : plain;
		plain = code(plain, false, r256, b64, 8, 6);
		return plain + '===='.slice((plain.length % 4) || 4);
	};

	Plugin.atob = Plugin.decode = function(coded, utf8decode) {
		coded = coded.replace(/[^A-Za-z0-9\+\/\=]/g, "");
		coded = String(coded).split('=');
		var i = coded.length;
		do {--i;
			coded[i] = code(coded[i], true, r64, a256, 6, 8);
		} while (i > 0);
		coded = coded.join('');
		return Plugin.raw === false || Plugin.utf8decode || utf8decode ? UTF8.decode(coded) : coded;
	};

	if (!window.btoa) window.btoa = Plugin.btoa;
	if (!window.atob) window.atob = Plugin.atob;
}(jQuery));

/**
 * fw.qtip($elements)
 */
(function($){
	/**
	 * Trigger custom event with delay when mouse left (i) and popup
	 * @param $i
	 */
	function initHide($i) {
		var api = $i.qtip('api');

		var hideTimeout = 0;
		var hideDelay = 200;

		var hide = function(){
			clearTimeout(hideTimeout);

			hideTimeout = setTimeout(function(){
				$i.trigger('fw-qtip:hide');
			}, hideDelay);
		};

		{
			api.elements.tooltip
				.on('mouseenter', function(){
					clearTimeout(hideTimeout);
				})
				.on('mouseleave', function(){
					hide();
				});

			$i
				.on('mouseenter', function(){
					clearTimeout(hideTimeout);
				})
				.on('mouseleave', function(){
					hide();
				});
		}
	};

	var idIncrement = 1;

	function initHelps($helps) {
		$helps.each(function(){
			var $i = $(this);

			var id = 'fw-qtip-'+ idIncrement++;

			var hideInitialized = false;

			$i.qtip({
				id: id,
				position: {
					viewport: $(document.body),
					at: $i.attr('data-custom-at') ? ( $i.attr('data-custom-at')) : 'top center',
					my: $i.attr('data-custom-my') ? ( $i.attr('data-custom-my')) : 'bottom center',
					adjust: {
						y: $i.attr('data-adjust') ? ( + $i.attr('data-adjust')) : 2
					}
				},
				style: {
					classes: $i.hasClass('dashicons-info')
						? 'qtip-fw fw-tip-info'
						: 'qtip-fw',
					tip: {
						width: 12,
						height: 5
					}
				},
				show: {
					solo: true,
					event: 'mouseover',
					effect: function(offset) {
						// fix tip position
						setTimeout(function(){
							offset.elements.tooltip.css('top',
								(parseInt(offset.elements.tooltip.css('top')) + 5) + 'px'
							);
						}, 12);

						if (!hideInitialized) {
							initHide($i);

							hideInitialized = true;
						}

						$(this).fadeIn(300);
					}
				},
				hide: {
					event: 'fw-qtip:hide',
					effect: function(offset) {
						$(this).fadeOut(300, function(){
							/**
							 * Reset tip content html.
							 * Needed for video tips, after hide the video should stop.
							 */
							api.elements.content.html($i.attr('title'))
						});
					}
				}
			});

			$i.on('remove', function(){
				api.hide();
			});

			var api = $i.qtip('api');
		});
	};

	fw.qtip = initHelps;
})(jQuery);

/**
 * Allow to select external links
 * jQuery('a:fw-external')
 */
jQuery.expr[':']['fw-external'] = function(obj){
	return !obj.href.match(/^mailto\:/)
		&& (obj.hostname != location.hostname)
		&& !obj.href.match(/^javascript\:/)
		&& !obj.href.match(/^$/);
};

/**
 * Check if an event fired from an element has listeners within specified container/parent element
 * @param $element
 * @param {String} event
 * @param $container
 * @return {Boolean}
 */
fw.elementEventHasListenerInContainer = function ($element, event, $container) {
	"use strict";

	var events, container = $container.get(0);

	/**
	 * Check if container element has delegated event that matches the element
	 */
	{
		var foundListener = false;

		if (
			(events = $container.data('events'))
			&&
			events[event]
		) {
			jQuery.each(events[event], function(i, eventData){
				if ($element.is(eventData.selector)) {
					foundListener = true;
					return false;
				}
			});
		}

		if (foundListener) {
			return true;
		}
	}

	/**
	 * Check every parent if has event listener
	 */
	{
		var $currentParent = $element;

		while ($currentParent.get(0) !== container) {
			if (
				(events = $currentParent.data('events'))
				&&
				events[event]
			) {
				return true;
			}

			if ($currentParent.attr('on'+ event)) {
				return true;
			}

			$currentParent = $currentParent.parent();

			if (!$currentParent.length) {
				/**
				 * The element doesn't exist in DOM
				 * This means that the event was processed, so it has listener
				 */
				return true;
			}
		}
	}

	return false;
};

/**
 * Simple modal
 * Meant to display success/error messages
 * Can be called multiple times,all calls will be pushed to queue and displayed one-by-one
 *
 * Usage:
 *
 * // open modal with close button and wait for user to close it
 * fw.soleModal.show('unique-id', 'Hello World!');
 *
 * // open modal with close button but auto hide it after 3 seconds
 * fw.soleModal.show('unique-id', 'Hello World!', {autoHide: 3000});
 *
 * fw.soleModal.hide('unique-id');
 */
fw.soleModal = (function(){
	var inst = {
		queue: [
			/*
			{
				id: 'hello'
				html: 'Hello <b>World</b>!'
				autoHide: 0000 // auto hide timeout in ms
				allowClose: true // useful when you make an ajax and must force the user to wait until it will finish
				showCloseButton: true // false will hide the button, but the user will still be able to click on backdrop to close it
				width: 350
				height: 200
				hidePrevious: false // just replace the modal content or hide the previous modal and open it again with new content
				updateIfCurrent: false // if current open modal has the same id as modal requested to show, update it without reopening
				backdrop: null // true - light, false - dark
				afterOpenStart: function(){} // before open animation starts
				afterOpen: function(){}
				afterCloseStart: function(){} // before close animation starts
				afterClose: function(){}
			}
			*/
		],
		/** @type {Object|null} */
		current: null,
		animationTime: 300,
		$modal: null,
		backdropOpacity: 0.7, // must be the same as in .fw-modal style
		currentMethod: '',
		currentMethodTimeoutId: 0,
		pendingMethod: '',
		lazyInit: function(){
			if (this.$modal) {
				return false;
			}

			this.$modal = jQuery(
				'<div class="fw-modal fw-sole-modal" style="display:none;">'+
				'    <div class="media-modal wp-core-ui" style="width: 350px; height: 200px;">'+
				'        <div class="media-modal-content" style="min-height: 200px;">' +
				'            <button type="button" class="button-link media-modal-close"><span class="media-modal-icon"></span></button>'+
				'            <table width="100%" height="100%"><tbody><tr>'+
				'                <td valign="middle" class="fw-sole-modal-content fw-text-center"><!-- modal content --></td>'+
				'            </tr><tbody></table>'+
				'        </div>'+
				'    </div>'+
				'    <div class="media-modal-backdrop"></div>'+
				'</div>'
			);

			( this.$getCloseButton().add(this.$getBackdrop()) ).on('click', _.bind(function(){
				if (this.current && !this.current.allowClose) {
					// manual close not is allowed
					return;
				}

				this.hide();
			}, this));

			jQuery(document.body).append(this.$modal);

			return true;
		},
		$getBackdrop: function() {
			this.lazyInit();

			return this.$modal.find('.media-modal-backdrop:first');
		},
		$getCloseButton: function() {
			this.lazyInit();

			return this.$modal.find('.media-modal-close:first');
		},
		$getContent: function() {
			return this.$modal.find('.fw-sole-modal-content:first');
		},
		wrapWithTable: function ($modal) {
			var temporaryContent = $modal.find('.fw-sole-modal-content').html();

			$modal.find('.fw-sole-modal-content').remove();

			var htmlTemplate =
				'<tbody>' +
					'<tr>' +
						'<td valign="middle" class="fw-sole-modal-content fw-text-center">' +
							temporaryContent +
						'</td>' +
					'</tr>' +
				'</tbody>';

			var $table = jQuery('<table>', {
				html: htmlTemplate
			}).attr({width: '100%', height: '100%'});

			$modal.find('.media-modal-content').append($table);
		},
		unwrapWithTable: function ($modal) {
			var temporaryContent = $modal.find('.fw-sole-modal-content').html();

			$modal.find('.fw-sole-modal-content')
				.closest('table')
				.remove();

			$modal.find('.media-modal-content')
				.append(jQuery('<div>', {
					class: 'fw-sole-modal-content',
					html: temporaryContent
				}));
		},
		setContent: function(html) {
			this.lazyInit();

			this.$getContent().html(html || '&nbsp;');
		},
		runPendingMethod: function() {
			if (this.currentMethod) {
				return false;
			}

			if (!this.pendingMethod) {
				if (this.queue.length) {
					// there are messages to display
					this.pendingMethod = 'show';
				} else {
					return false;
				}
			}

			var pendingMethod = this.pendingMethod;

			this.pendingMethod = '';

			if (pendingMethod == 'hide') {
				this.hide();
				return true;
			} else if (pendingMethod == 'show') {
				this.show();
				return true;
			} else {
				console.warn('Unknown pending method:', pendingMethod);
				this.hide();
				return false;
			}
		},
		setSize: function(width, height) {
			var $size = this.$modal.find('> .media-modal');
			var $modal = this.$modal;
			$modal.addClass('fw-modal-opening');

			if (
				$size.height() != height
				||
				$size.width() != width
			) {
				$size.animate({
					'height': height +'px',
					'width': width +'px'
				}, this.animationTime);
			}

			setTimeout(function () {
				$modal.removeClass('fw-modal-opening');
			}, this.animationTime);

			$size = undefined;
		},
		/**
		 * Show modal
		 * Call without arguments to display next from queue
		 * @param {String} [id]
		 * @param {String} [html]
		 * @param {Object} [opts]
		 * @returns {Boolean}
		 */
		show: function (id, html, opts) {
			if (typeof id != 'undefined') {
				// make sure to remove this id from queue (if was added previously)
				this.queue = _.filter(this.queue, function (item) { return item.id != id; });

				{
					opts = jQuery.extend({
						autoHide: 0,
						allowClose: true,
						showCloseButton: true,
						width: 350,
						height: 200,
						hidePrevious: false,
						updateIfCurrent: false,
						wrapWithTable: true,
						backdrop: null,
						customClass: null,
						afterOpen: function(){},
						afterOpenStart: function(){},
						afterClose: function(){},
						afterCloseStart: function(){}
					}, opts || {});

					// hide close button if close is not allowed
					opts.showCloseButton = opts.showCloseButton && opts.allowClose;

					opts.id = id;
					opts.html = html;
				}

				this.queue.push(opts);

				return this.show();
			}

			if (this.currentMethod) {
				return false;
			}

			if (this.current) {
				if (
					this.queue.length
					&&
					this.queue[0].id === this.current.id
					&&
					this.queue[0].updateIfCurrent
				) {
					if (this.$modal && this.current.customClass !== null) {
						this.$modal.removeClass(this.current.customClass);
					}

					if (this.$modal && ! this.current.wrapWithTable) {
						this.wrapWithTable(this.$modal);
					}

					this.current = this.queue.shift();

					if (this.$modal && this.current.customClass !== null) {
						this.$modal.addClass(this.current.customClass);
					}

					this.setContent(this.current.html);

					if (this.$modal && ! this.current.wrapWithTable) {
						this.unwrapWithTable(this.$modal);
					}

					return true;
				} else {
					return false;
				}
			}

			if (this.current && this.$modal && this.current.customClass !== null) {
				this.$modal.removeClass(this.current.customClass);
			}

			if (this.current && this.$modal && ! this.current.wrapWithTable) {
				this.unwrapWithTable(this.$modal);
			}

			this.currentMethod = '';
			this.current = this.queue.shift();

			if (!this.current) {
				this.hide();
				return false;
			}

			this.currentMethod = 'show';

			this.setContent(this.current.html);

			this.$getCloseButton().css('display', this.current.showCloseButton ? '' : 'none');

			{
				this.$modal.removeClass('fw-modal-backdrop-light fw-modal-backdrop-dark');

				if (this.current.backdrop !== null) {
					this.$modal.addClass('fw-modal-backdrop-'+ (this.current.backdrop ? 'light' : 'dark'));
				}
			}

			this.$modal.removeClass('fw-modal-closing');
			this.$modal.addClass('fw-modal-open');

			if (this.$modal && this.current.customClass !== null) {
				this.$modal.addClass(this.current.customClass);
			}

			if (this.$modal && ! this.current.wrapWithTable) {
				this.unwrapWithTable(this.$modal);
			}

			this.$modal.css('display', '');

			this.setSize(this.current.width, this.current.height);

			this.current.afterOpenStart(this.$modal);
			this.currentMethodTimeoutId = setTimeout(_.bind(function() {
				this.current.afterOpen();

				this.currentMethod = '';

				if (this.runPendingMethod()) {
					return;
				}

				if (this.current.autoHide > 0) {
					this.currentMethod = 'auto-hide';
					this.currentMethodTimeoutId = setTimeout(_.bind(function () {
						this.currentMethod = '';
						this.hide();
					}, this), this.current.autoHide);
				}
			}, this), this.animationTime * 2);

			return true;
		},
		/**
		 * @param {String} [id]
		 * @returns {boolean}
		 */
		hide: function(id) {
			if (typeof id != 'undefined') {
				if (this.current && this.current.id == id) {
					// this id is currently displayed, hide it
				} else {
					// remove id from queue
					this.queue = _.filter(this.queue, function (item) {
						return item.id != id;
					});
					return true;
				}
			}

			if (this.currentMethod) {
				if (this.currentMethod == 'hide') {
					return false;
				} else if (this.currentMethod == 'auto-hide') {
					clearTimeout(this.currentMethodTimeoutId);
				} else {
					this.pendingMethod = 'hide';
					return true;
				}
			}

			this.currentMethod = '';

			if (!this.current) {
				// nothing to hide
				return this.runPendingMethod();
			}

			this.currentMethod = 'hide';

			if (this.queue.length && !this.queue[0].hidePrevious) {
				// replace content
				this.current.afterCloseStart(this.$modal);
				this.$getContent().fadeOut('fast', _.bind(function(){
					this.current.afterClose();

					if (this.$modal && this.current.customClass !== null) {
						this.$modal.removeClass(this.current.customClass);
					}

					if (this.$modal && ! this.current.wrapWithTable) {
						this.wrapWithTable(this.$modal);
					}

					this.currentMethod = '';
					this.current = null;
					this.show();
					this.$getContent().fadeIn('fast');
				}, this));

				return true;
			}

			this.$modal.addClass('fw-modal-closing');

			this.current.afterCloseStart(this.$modal);
			this.currentMethodTimeoutId = setTimeout(_.bind(function(){
				this.current.afterClose();

				this.currentMethod = '';

				this.$modal.css('display', 'none');

				this.$modal.removeClass('fw-modal-open');
				this.$modal.removeClass('fw-modal-closing');

				if (this.$modal && this.current.customClass !== null) {
					this.$modal.removeClass(this.current.customClass);
				}

				if (this.$modal && ! this.current.wrapWithTable) {
					this.wrapWithTable(this.$modal);
				}

				this.setContent('');

				this.current = null;

				this.runPendingMethod();
			}, this), this.animationTime);
		}
	};

	return {
		show: function(id, html, opts) {
			inst.show(id, html, opts);
		},
		hide: function(id){
			inst.hide(id);
		},
		/**
		 * Generate flash messages html for soleModal content
		 */
		renderFlashMessages: function (flashMessages) {
			var html = [],
				typeHtml = [],
				typeMessageClass = '',
				typeIconClass = '',
				typeTitle = '';

			jQuery.each(flashMessages, function(type, messages){
				typeHtml = [];

				switch (type) {
					case 'error':
						typeMessageClass = 'fw-text-danger';
						typeIconClass = 'dashicons dashicons-dismiss';
						typeTitle = _fw_localized.l10n.ah_sorry;
						break;
					case 'warning':
						typeMessageClass = 'fw-text-warning';
						typeIconClass = 'dashicons dashicons-no-alt';
						typeTitle = _fw_localized.l10n.ah_sorry;
						break;
					case 'success':
						typeMessageClass = 'fw-text-success';
						typeIconClass = 'dashicons dashicons-star-filled';
						typeTitle = _fw_localized.l10n.done;
						break;
					case 'info':
						typeMessageClass = 'fw-text-info';
						typeIconClass = 'dashicons dashicons-info';
						typeTitle = _fw_localized.l10n.done;
						break;
					default:
						typeMessageClass = typeIconClass = typeTitle = '';
				}

				jQuery.each(messages, function(messageId, message){
					if (!typeTitle.length) {
						return;
					}

					typeHtml.push(
						'<li>'+
							'<h2 class="'+ typeMessageClass +'"><span class="'+ typeIconClass +'"></span> '+ typeTitle +'</h2>'+
							'<p class="fw-text-muted"><em>'+ message +'</em></p>'+
						'</li>'
					);
				});

				if (typeHtml.length) {
					html.push(
						'<ul>'+ typeHtml.join('</ul><ul>') +'</ul>'
					);
				}
			});

			return html.join('');
		}
	};
})();

/**
 * Simple mechanism of getting confirmations from the user.
 *
 * Usage:
 *
 * var confirm = fw.soleConfirm.create();
 * confirm.result.then(function (data) {
 *   // SUCCESS!!
 * });
 *
 * confirm.result.fail(function (data) {
 *   // FAIL!!
 * });
 *
 * confirm.show();
 *
 * Note: confirm.result is a full-featured jQuery.Deferred object, you can also
 * use methods like always, done, jQuery.when with it.
 *
 * Warning:
 *   You confirm object will be garbage collected after the user will pick an
 *   option, that is, it will become null. You should create one more confirm
 *   afterwards, if you need it.
 *
 * TODO:
 *  1. Maybe pass unknown options to fw.soleModal itself.
 */
fw.soleConfirm = (function ($) {
	var hashMap = {};

	function create (opts) {
		var confirm = new Confirm(opts);
		hashMap[confirm.id] = confirm;
		return hashMap[confirm.id];
	}

	function Confirm (opts) {
		this.result = jQuery.Deferred();
		this.id = fw.randomMD5();

		this.opts = _.extend({
			severity: 'info', // warning | info
			message: null,
			backdrop: null,
			renderFunction: null,
			shouldResolvePromise: function (confirm, el, action) { return true; },
			okHTML: _fw_localized.l10n.ok,
			cancelHTML: _fw_localized.l10n.cancel,
			customClass: ''
		}, opts);
	}

	Confirm.prototype.destroy = function () {
		hashMap[this.id] = null;
		delete hashMap[this.id];
	}

	/**
	 * Attached listeners on this.result will be lost after this operation.
	 * You'll have to add them once again.
	 */
	Confirm.prototype.reset = function () {
		if (hashMap[this.id]) {
			throw "You can't reset till your promise is not resolved! Do a .destroy() if you don't need Confirm anymore!";
		}

		if (this.result.isRejected() || this.result.isResolved()) {
			this.result = jQuery.Deferred();
		}

		hashMap[this.id] = this;
	};

	Confirm.prototype.show = function () {
		this._checkIsSet();

		fw.soleModal.show(this.id, this._getHtml(), {
			wrapWithTable: false,
			showCloseButton: false,
			allowClose: false, // a confirm window can't be closed on click of it's backdrop
			backdrop: this.opts.backdrop,
			customClass: 'fw-sole-confirm-modal fw-sole-confirm-' + this.opts.severity + ' ' + this.opts.customClass,
			updateIfCurrent: true,

			afterOpenStart: _.bind(this._fireEvents, this),
			afterCloseStart: _.bind(this._teardownEvents, this),

			onFireEvents: jQuery.noop,
			onTeardownEvents: jQuery.noop
		});
	};

	Confirm.prototype.hide = function (reason) {
		this._checkIsSet();

		fw.soleModal.hide(this.id);
	};

	//////////////////

	Confirm.prototype._fireEvents = function ($modal) {
		$modal.attr('data-fw-sole-confirm-id', this.id);

		$modal.find('.fw-sole-confirm-button')
			.add(
				$modal.find('.media-modal-backdrop')
			)
			.on('click.fw-sole-confirm', _.bind(this._handleClose, this));

		if (this.opts.onFireEvents) {
			this.opts.onFireEvents(this, $modal[0]);
		}
	};

	Confirm.prototype._teardownEvents = function ($modal) {
		$modal.find('.fw-sole-confirm-button')
			.add(
				$modal.find('.media-modal-backdrop')
			)
			.off('click.fw-sole-confirm');

		if (this.opts.onTeardownEvents) {
			this.opts.onTeardownEvents(this, $modal[0]);
		}
	};

	Confirm.prototype._checkIsSet = function () {
		if (! hashMap[this.id]) {
			throw "You can't do operations on fullfilled Confirm! Do a .reset() first.";
		}
	};

	Confirm.prototype._handleClose = function (event) {
        event.preventDefault();

		var $el = $(event.target);

		if ($el.hasClass('media-modal-backdrop')) {

			// do not do any transformation on $el here by intent

		} else if (! $el.hasClass('fw-sole-confirm-button')) {
			$el = $el.closest('.fw-sole-confirm-button');
		}

		var action = $el.attr('data-fw-sole-confirm-action') || 'reject';
		var id = $el.closest('.fw-sole-modal').attr('data-fw-sole-confirm-id');
		var confirm = hashMap[id];

		if (confirm) {
			var modal_container = $el.closest('.fw-sole-modal')[0];

			if (action === 'reject') {
				confirm.result.reject({
					confirm: confirm,
					modal_container: modal_container
				});
			} else {
				var shouldHideAfterResolve = confirm.opts.shouldResolvePromise(
					confirm, modal_container
				);

				if (! shouldHideAfterResolve) {
					return;
				}

				// probably keep this syntax for another actions in future
				_.contains(['resolve'], action) &&
					confirm.result[action]({
						confirm: confirm,
						modal_container: $el.closest('.fw-sole-modal')[0]
					});

			}

			confirm.hide();

			confirm.destroy();
			confirm = null;
		}
	};

	Confirm.prototype._getHtml = function () {
		if (this.opts.renderFunction) {
			return this.opts.renderFunction(this);
		}

		var topHtml = '';

		var iconClass = 'dashicons-' + this.opts.severity;
		var icon = '<span class="dashicons ' + iconClass + '"></span>';
		var heading = '<h1>' + fw.capitalizeFirstLetter(this.opts.severity) + '</h1>';
		var message = this.opts.message ? '<p>' + this.opts.message + '</p>' : '';

		topHtml = icon + heading + message;

		var cancelButton = $('<button>', {
			html: this.opts.cancelHTML
		}).attr({
			'data-fw-sole-confirm-action': 'reject',
			type: 'button',
		}).addClass('fw-sole-confirm-button button');

		var okButton = $('<button>', {
			html: this.opts.okHTML
		}).attr({
			'data-fw-sole-confirm-action': 'resolve',
			type: 'button',
		}).addClass('fw-sole-confirm-button button button-primary');

		// OK (primary) on the left, Cancel on the right — matches the Windows /
		// native-confirm() convention most users here expect.
		return topHtml + selfHtml(okButton) + selfHtml(cancelButton);

		function selfHtml (el) { return $('<div>').append(el).html(); }
	};

	return {
		create: create
	};
})(jQuery);

/**
 * fw.confirm — styled yes/no confirmation, a drop-in modern replacement for the
 * native blocking confirm(). Because the styled dialog is asynchronous, the
 * confirmed action runs in the onConfirm callback (not inline after the call).
 *
 * Usage:
 *   fw.confirm('Delete this item?', function () { … do it … });
 *   fw.confirm(msg, onYes, { severity: 'warning', onCancel: fn, okHTML: '…' });
 *
 * Built on fw.soleConfirm; falls back to native confirm() only if the styled
 * modal isn't available, so a destructive-action guard can never silently no-op.
 *
 * @param {String}   message
 * @param {Function} onConfirm  runs when the user accepts
 * @param {Object}   [opts]     { severity, okHTML, cancelHTML, customClass, onCancel }
 */
fw.confirm = function (message, onConfirm, opts) {
	opts = opts || {};

	if (fw.soleConfirm) {
		var createOpts = {
			severity: opts.severity || 'warning',
			message:  message
		};

		// only copy keys that are set (passing undefined would clobber defaults)
		if (opts.okHTML)      { createOpts.okHTML      = opts.okHTML; }
		if (opts.cancelHTML)  { createOpts.cancelHTML  = opts.cancelHTML; }
		if (opts.customClass) { createOpts.customClass = opts.customClass; }

		var confirm = fw.soleConfirm.create(createOpts);

		confirm.result.done(function () {
			if (typeof onConfirm === 'function') { onConfirm(); }
		});

		if (typeof opts.onCancel === 'function') {
			confirm.result.fail(opts.onCancel);
		}

		confirm.show();
	} else if (window.confirm(message)) {
		if (typeof onConfirm === 'function') { onConfirm(); }
	}
};

