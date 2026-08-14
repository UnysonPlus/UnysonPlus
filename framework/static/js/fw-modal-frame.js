/**
 * fw.ModalFrame — a Backbone-free replacement for wp.media.view.MediaFrame.
 *
 * The framework's options modal was built on WordPress's media frame, which is
 * Backbone. That made Backbone a hard dependency of the single most-used piece of
 * admin UI. This reimplements only the slice of the media frame the framework
 * actually used, in plain JavaScript + jQuery.
 *
 * ## Two contracts this file must not break
 *
 * 1. **The DOM contract.** The framework's stylesheets target the media-frame
 *    class names (`.media-modal`, `.media-modal-backdrop`, `.media-modal-content`,
 *    `.media-frame`, `.media-frame-title`, `.media-frame-content`,
 *    `.media-frame-toolbar`, `.media-modal-close`, `.media-toolbar-primary`), and
 *    so does the framework's own modal code — draggable, sizing, z-index stacking.
 *    The markup below reproduces that structure exactly. Changing a class name
 *    here silently restyles every options modal in the admin.
 *
 * 2. **The API contract.** 33 files reach into `modal.frame`. Everything they use
 *    is implemented here with the same shape: `$el`, `modal.$el`, `views.get()`,
 *    `content.set()`, `toolbar.set()`, `toolbar.selector`, `state()`, `open()`,
 *    `close()`, and `on/once/off/trigger` for `ready`, `open`, `close` and
 *    `content:create:main`.
 *
 * The one deliberate difference from WordPress: the `#wp-media-modal` id is not
 * reproduced. Nothing styles it, and modals stack — duplicate ids would be an
 * accessibility bug.
 *
 * @see fw-oo.js for fw.Events / fw.Class / fw.View
 * @since 2.16.11
 */
var fw = fw || {};

(function ($) {
	'use strict';

	/** Regions a frame exposes, in render order. */
	var TITLE_SELECTOR = '.media-frame-title';
	var CONTENT_SELECTOR = '.media-frame-content';
	var TOOLBAR_SELECTOR = '.media-frame-toolbar';

	/**
	 * Track open frames so the body class and Escape handling stay correct when
	 * modals are stacked (editing an element from inside another modal).
	 *
	 * @type {Array}
	 */
	var openFrames = [];

	/* ------------------------------------------------------------------ *
	 * Toolbar — replaces wp.media.view.Toolbar
	 * ------------------------------------------------------------------ */

	/**
	 * @param {Object}   options
	 * @param {Object[]} options.items Buttons: { text, style, priority, click }.
	 *                   priority < 0 renders left (secondary), >= 0 right (primary),
	 *                   ascending — matching the media toolbar's ordering so the
	 *                   framework's existing priority numbers keep their positions.
	 */
	fw.ModalToolbar = fw.View.extend({
		className: 'media-toolbar',

		initialize: function (options) {
			this.items = (options && options.items) || [];
			this.render();
		},

		render: function () {
			var $secondary = $('<div class="media-toolbar-secondary"></div>');
			var $primary = $('<div class="media-toolbar-primary"></div>');

			this.items
				.slice()
				.sort(function (a, b) {
					return (a.priority || 0) - (b.priority || 0);
				})
				.forEach(function (item) {
					var $button = $('<button type="button"></button>')
						.addClass('button media-button')
						.text(item.text || '');

					/**
					 * Size class, defaulting to 'large'.
					 *
					 * wp.media.view.Button defaults to `size: 'large'`, so every
					 * button the media toolbar rendered carried `button-large`.
					 * Code that injects extra buttons into this toolbar hardcodes
					 * that class to match — the page builder's "Save All" does —
					 * so omitting it here leaves the injected button visibly
					 * taller than Save / Apply / Reset beside it.
					 */
					$button.addClass('button-' + (item.size || 'large'));

					if (item.style) {
						$button.addClass('button-' + item.style);
					}

					if (item.className) {
						$button.addClass(item.className);
					}

					if (typeof item.click === 'function') {
						$button.on('click', function (e) {
							e.preventDefault();
							item.click.call(item);
						});
					}

					$button.appendTo((item.priority || 0) < 0 ? $secondary : $primary);
				});

			this.$el.empty().append($secondary).append($primary);

			return this;
		}
	});

	/* ------------------------------------------------------------------ *
	 * ModalFrame
	 * ------------------------------------------------------------------ */

	/**
	 * @param {Object} [options]
	 * @param {string} [options.title]
	 */
	function ModalFrame(options) {
		this.options = options || {};

		this._ready = false;
		this._views = {};
		this._previousFocus = null;

		this.build();
		this.bindEvents();
	}

	// Event emitter + the `ready`/`open`/`close` bus consumers bind to.
	for (var key in fw.Events) {
		if (Object.prototype.hasOwnProperty.call(fw.Events, key)) {
			ModalFrame.prototype[key] = fw.Events[key];
		}
	}

	/**
	 * Build the media-frame DOM, detached from view.
	 *
	 * Appended to <body> immediately but kept hidden: the framework's `ready`
	 * handler attaches jQuery UI draggable and must find a real element, while the
	 * comments there note it deliberately does NOT measure geometry yet (the box is
	 * still 0x0). Centring happens on `open`, when it is visible.
	 */
	ModalFrame.prototype.build = function () {
		this.$modalEl = $(
			'<div>' +
				'<div class="media-modal wp-core-ui" role="dialog" aria-modal="true">' +
					'<button type="button" class="media-modal-close">' +
						'<span class="media-modal-icon" aria-hidden="true"></span>' +
						'<span class="screen-reader-text">' + this.closeLabel() + '</span>' +
					'</button>' +
					'<div class="media-modal-content" role="document">' +
						'<div class="media-frame wp-core-ui hide-menu hide-router">' +
							'<div class="' + TITLE_SELECTOR.slice(1) + '"><h1></h1></div>' +
							'<div class="media-frame-router"></div>' +
							'<div class="' + CONTENT_SELECTOR.slice(1) + '"></div>' +
							'<div class="' + TOOLBAR_SELECTOR.slice(1) + '"></div>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="media-modal-backdrop"></div>' +
			'</div>'
		);

		this.$modalEl.hide().appendTo(document.body);

		// frame.$el is the .media-frame — what consumers add/remove classes on.
		this.$el = this.$modalEl.find('.media-frame');
		this.el = this.$el[0];

		// frame.modal.$el is the OUTER wrapper: the framework does
		// $modalWrapper.find('.media-modal') / .find('.media-modal-backdrop').
		var frame = this;

		this.modal = {
			$el: this.$modalEl,
			el: this.$modalEl[0],
			open: function () { frame.open(); },
			close: function () { frame.close(); }
		};

		// The title region is exposed as a view, because the framework reads
		// frame.views.get('.media-frame-title')[0].$el to write the title.
		this._views[TITLE_SELECTOR] = [ { $el: this.$modalEl.find(TITLE_SELECTOR + ' > h1') } ];

		// Per-instance registry lookup (the media frame exposes `views.get`).
		this.views = {
			get: function (selector) {
				return frame._views[selector] || [];
			},
			set: function (selector, view) {
				frame._views[selector] = [].concat(view);
				return view;
			}
		};

		this.buildRegions();
	};

	/**
	 * The `content` and `toolbar` regions, with the media frame's set()/get() API.
	 */
	ModalFrame.prototype.buildRegions = function () {
		this.content = this.makeRegion(CONTENT_SELECTOR);
		this.toolbar = this.makeRegion(TOOLBAR_SELECTOR);
	};

	/**
	 * @param {string} selector Region selector, also its `views` key.
	 * @return {Object} Region with set/get and the `selector` consumers read.
	 */
	ModalFrame.prototype.makeRegion = function (selector) {
		var frame = this;

		return {
			selector: selector,

			set: function (view) {
				var $region = frame.$el.find(selector);

				$region.empty();

				if (!view) {
					frame._views[selector] = [];
					return view;
				}

				$region.append(view.el || view.$el);

				if (typeof view.render === 'function') {
					view.render();
				}

				frame._views[selector] = [ view ];

				return view;
			},

			get: function () {
				return (frame._views[selector] || [])[0];
			}
		};
	};

	/** Close on the X, on the backdrop, and on Escape — as the media modal does. */
	ModalFrame.prototype.bindEvents = function () {
		var frame = this;

		this.$modalEl.on('click', '.media-modal-close', function (e) {
			e.preventDefault();
			frame.close();
		});

		this.$modalEl.on('click', '.media-modal-backdrop', function () {
			frame.close();
		});

		this.$modalEl.on('keydown', function (e) {
			if (e.keyCode === 27) { // Escape
				e.preventDefault();
				frame.trigger('escape');
				frame.close();
				return;
			}

			if (e.keyCode === 9) { // Tab — keep focus inside the dialog
				frame.constrainTab(e);
			}
		});
	};

	/**
	 * Minimal focus trap. wp.media.view.FocusManager did this for us; without it a
	 * Tab out of the modal lands on the page behind, which is both disorienting and
	 * an accessibility failure for a modal dialog.
	 *
	 * @param {jQuery.Event} e Keydown event.
	 */
	ModalFrame.prototype.constrainTab = function (e) {
		var $focusable = this.$modalEl
			.find('a[href], button, input, select, textarea, [tabindex]')
			.filter(':visible')
			.not('[disabled], [tabindex="-1"]');

		if (!$focusable.length) { return; }

		var first = $focusable[0];
		var last = $focusable[$focusable.length - 1];

		if (e.shiftKey && e.target === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && e.target === last) {
			e.preventDefault();
			first.focus();
		}
	};

	/** Localised close label, falling back when l10n is unavailable. */
	ModalFrame.prototype.closeLabel = function () {
		try {
			if (window._fw_localized && _fw_localized.l10n && _fw_localized.l10n.close) {
				return _fw_localized.l10n.close;
			}
		} catch (e) {}

		return 'Close dialog';
	};

	/**
	 * Open the modal.
	 *
	 * First open runs the deferred lifecycle in the order the framework expects:
	 * `content:create:main` (where the framework installs its content + toolbar
	 * views), then `ready` (where it configures the wrapper and attaches
	 * draggable, while still hidden), then the element is shown and `open` fires.
	 *
	 * That ordering matters: handlers are registered AFTER the frame is
	 * constructed, so neither event may fire during construction.
	 */
	ModalFrame.prototype.open = function () {
		if (!this._ready) {
			this._ready = true;
			this.trigger('content:create:main');
			this.trigger('ready');
		}

		this._previousFocus = document.activeElement;

		this.$modalEl.show();
		$(document.body).addClass('modal-open');

		if (openFrames.indexOf(this) === -1) {
			openFrames.push(this);
		}

		// Move focus into the dialog so keyboard users are not left behind it.
		var $first = this.$modalEl.find('.media-modal').attr('tabindex', '0');

		if ($first.length) { $first[0].focus(); }

		this.trigger('open');

		return this;
	};

	/** Close the modal and restore focus to whatever opened it. */
	ModalFrame.prototype.close = function () {
		if (!this.$modalEl.is(':visible')) { return this; }

		this.$modalEl.hide();

		var index = openFrames.indexOf(this);

		if (index !== -1) { openFrames.splice(index, 1); }

		if (!openFrames.length) {
			$(document.body).removeClass('modal-open');
		}

		this.trigger('close');

		if (this._previousFocus && typeof this._previousFocus.focus === 'function') {
			try { this._previousFocus.focus(); } catch (e) {}
		}

		return this;
	};

	/** Remove the frame from the DOM entirely. */
	ModalFrame.prototype.dispose = function () {
		this.close();
		this.off();
		this.$modalEl.remove();

		return this;
	};

	/**
	 * A truthy state object. The framework only ever tests `frame.state()` for
	 * truthiness (icon-picker gates on "are the frame's controls up yet"), so this
	 * is a stub rather than a port of the media frame's state machine.
	 *
	 * @return {Object}
	 */
	ModalFrame.prototype.state = function () {
		return this._state || (this._state = {
			get: function () { return undefined; },
			set: function () { return this; }
		});
	};

	fw.ModalFrame = ModalFrame;
})(jQuery);
