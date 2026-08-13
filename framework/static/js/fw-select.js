/**
 * $.fn.fwSelect — the framework's enhanced select control, built on Tom Select.
 *
 * NAMING
 * Named for what it does, not for the library underneath — the same reasoning as
 * fw-tooltip. The engine is an implementation detail; swapping it should touch
 * this file and nothing else.
 *
 * WHY AN ADAPTER RATHER THAN DIRECT CALLS
 * Tom Select is a descendant of the previous control and implements nearly the
 * same instance API (setValue / getValue / addOption / removeOption /
 * refreshOptions / clear / focus / blur / destroy) under the same names, so the
 * four call sites needed almost no change. What differs is small and mechanical,
 * and is normalised here so the call sites stay readable:
 *
 *   - Tom Select exposes `wrapper` / `control` / `control_input` / `dropdown` as
 *     DOM elements. The option types were written against jQuery objects, so the
 *     `$wrapper` / `$control` / `$control_input` / `$dropdown` aliases are added.
 *   - `onDropdownOpen` is handed a DOM element upstream; the call sites expect a
 *     jQuery object, so it is wrapped.
 *   - The instance is published on the element as `el.fwSelect`, assigned
 *     SYNCHRONOUSLY during construction. The icon picker guards re-initialisation
 *     with `if (!el.fwSelect)`, so a late assignment would re-create the control
 *     on every call.
 *
 * CLASS NAMES
 * Tom Select's class names are configurable, so it is told to emit the framework
 * vocabulary directly (.fw-select, .fw-select-control, .fw-select-dropdown, …).
 * That keeps the stylesheets a plain selector rename rather than a re-structure.
 *
 * TWO PLUGINS THAT ARE NO LONGER NEEDED
 * The previous library required custom plugins for behaviour Tom Select has built
 * in, and both were dropped rather than ported:
 *
 *   - "hidden textfield" (icon picker) hand-rolled a non-editable control by
 *     hiding the text input offscreen. `controlInput: null` does this natively.
 *   - The sidebars plugin existed to stop the control blurring when the user
 *     clicked the "add new sidebar" form injected into the dropdown. Tom Select's
 *     own document-mousedown handler already ignores clicks inside the dropdown,
 *     so only the two event notifications remain, as ordinary delegated handlers
 *     at the call site.
 */
(function ($, window) {
	'use strict';

	if (!$) { return; }

	if (typeof window.TomSelect === 'undefined') {
		window.console && console.error(
			'fw-select: Tom Select is missing — the select control cannot initialise. ' +
			'Check that the tom-select script is enqueued before this file.'
		);
		return;
	}

	var TomSelect = window.TomSelect;

	/**
	 * The container classes carry the framework vocabulary. `item` and `option`
	 * are deliberately left at their defaults: they are generic words rather than
	 * vendor branding, and both the engine's own stylesheet and the framework's
	 * option-type CSS select on them (e.g. `.optgroup .option`). Renaming them
	 * silently detaches every one of those rules.
	 */
	var CLASS_NAMES = {
		wrapperClass:         'fw-select',
		controlClass:         'fw-select-control',
		dropdownClass:        'fw-select-dropdown',
		dropdownContentClass: 'fw-select-dropdown-content'
	};

	/**
	 * Add the jQuery aliases the option types were written against, plus a couple
	 * of helpers that used to come from custom plugins.
	 */
	function decorate(instance) {
		if (instance.__fwDecorated) { return instance; }
		instance.__fwDecorated = true;

		instance.$wrapper       = $(instance.wrapper);
		instance.$control       = $(instance.control);
		instance.$control_input = $(instance.control_input);
		instance.$dropdown      = $(instance.dropdown);

		/**
		 * Tom Select's destroy() restores the original element but knows nothing
		 * about the reference published on it. Without clearing that, the
		 * "already initialised" guard below would refuse to rebuild a control that
		 * had been destroyed — which multi-select does on every remove.
		 */
		var originalDestroy = instance.destroy;
		instance.destroy = function () {
			var el = this.input;
			var result = originalDestroy.apply(this, arguments);
			if (el) {
				try { delete el.fwSelect; } catch (e) { el.fwSelect = null; }
				$(el).removeData('fwSelect');
			}
			return result;
		};

		/**
		 * Select the first available option, or clear. Previously supplied by the
		 * sidebars plugin; kept because the sidebars code still calls it.
		 */
		if (typeof instance.setDefaultValue !== 'function') {
			instance.setDefaultValue = function () {
				var first = null;
				for (var key in this.options) {
					if (Object.prototype.hasOwnProperty.call(this.options, key)) {
						first = this.options[key];
						break;
					}
				}
				this.setValue(first ? first[this.settings.valueField] : '');
			};
		}

		return instance;
	}

	/**
	 * Normalise a settings object written for the old control into one Tom Select
	 * understands. Only the differences that actually occur in this codebase are
	 * handled; anything unrecognised is passed straight through.
	 */
	function normalise(options) {
		var settings = $.extend({}, options || {}, CLASS_NAMES);

		/**
		 * onInitialize fires from inside the constructor, before the instance is
		 * returned — so the jQuery aliases have to be attached here rather than
		 * after construction. The typography option type reads `this.$wrapper`
		 * inside its onInitialize and would otherwise get undefined.
		 */
		var originalInit = settings.onInitialize;
		settings.onInitialize = function () {
			decorate(this);
			if (typeof originalInit === 'function') {
				return originalInit.apply(this, arguments);
			}
		};

		// The dropdown callbacks are handed a DOM element upstream but a jQuery
		// object downstream. Wrap them rather than rewriting the call sites.
		$.each(['onDropdownOpen', 'onDropdownClose'], function (_, name) {
			if (typeof settings[name] === 'function') {
				var original = settings[name];
				settings[name] = function (dropdown) {
					return original.call(this, $(dropdown));
				};
			}
		});

		return settings;
	}

	$.fn.fwSelect = function (options) {
		// Command form is not used by the framework; the instance is reached via
		// el.fwSelect. Guard anyway so a stray string argument fails loudly.
		if (typeof options === 'string') {
			window.console && console.warn(
				'fw-select: .fwSelect("' + options + '") is not a supported command. ' +
				'Use the instance on the element: el.fwSelect'
			);
			return this;
		}

		return this.each(function () {
			if (this.fwSelect) { return; }   // already initialised

			var instance = new TomSelect(this, normalise(options));

			// Published synchronously — see the header note about the icon picker's
			// re-initialisation guard.
			this.fwSelect = decorate(instance);

			// Also published as jQuery data: the sidebars extension reaches its
			// controls with $(el).data('fwSelect') rather than through the element.
			$(this).data('fwSelect', instance);
		});
	};

	$.fn.fwSelect.version = '1.0.0 (Tom Select)';

})(window.jQuery, window);
