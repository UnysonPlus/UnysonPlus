/**
 * fw.Events / fw.Class — the Backbone-shaped primitives the framework actually used.
 *
 * The admin only ever needed two things from Backbone: an event emitter with
 * attribute storage (Backbone.Model) and a thin DOM view (Backbone.View). This
 * file provides both in plain ES5-compatible JavaScript, with the same call
 * signatures, so existing code keeps working unchanged.
 *
 * ## Compatibility notes
 *
 * - `on/off/once/trigger` follow Backbone's signatures, including the
 *   `off(name, callback, context)` form used to remove a specific binding.
 * - `listenTo/stopListening` are implemented over our own bookkeeping. They are
 *   NOT interoperable with Backbone's internals (Backbone tracks listeners in a
 *   module-private registry), so a Backbone view calling `listenTo()` on one of
 *   OUR objects would bind but never auto-unbind. Nothing does that any more —
 *   the one place that did (a wp.media state listening to fw.Modal) was inverted
 *   to push instead of listen. Keep it that way.
 * - `set()` fires `change:<key>` then `change`, and skips silently when the value
 *   is unchanged — the behaviours option-type code relies on.
 *
 * @since 2.16.11
 */
var fw = fw || {};

(function () {
	'use strict';

	var slice = Array.prototype.slice;
	var idCounter = 0;

	function uniqueId(prefix) {
		idCounter += 1;
		return (prefix || '') + idCounter;
	}

	/* ------------------------------------------------------------------ *
	 * fw.Events — Backbone.Events-compatible emitter
	 * ------------------------------------------------------------------ */

	/**
	 * Normalise Backbone's event-map form.
	 *
	 * Backbone accepts `on({event: handler, ...}, context)` — and `listenTo` passes
	 * that straight through — so callers write:
	 *
	 *     this.listenTo(this.modal, { 'open': fn, 'close': fn });
	 *
	 * Eight places in the framework bind that way, including every page-builder
	 * item. When a map arrives, the CALLBACK position holds the context instead.
	 *
	 * @param {Object}   map     Event name → handler.
	 * @param {*}        callback Second argument as passed by the caller.
	 * @param {*}        context  Third argument as passed by the caller.
	 * @param {Function} bind     Called as (name, handler, context) per entry.
	 * @param {Object}   self     Returned for chaining.
	 */
	function applyEventMap(map, callback, context, bind, self) {
		var ctx = context === undefined ? callback : context;

		for (var key in map) {
			if (Object.prototype.hasOwnProperty.call(map, key)) {
				bind(key, map[key], ctx);
			}
		}

		return self;
	}

	var Events = {
		on: function (name, callback, context) {
			if (!name) { return this; }

			// Event-map form: on({event: handler}, context)
			if (typeof name === 'object') {
				var self = this;
				return applyEventMap(name, callback, context, function (k, cb, ctx) {
					self.on(k, cb, ctx);
				}, this);
			}

			if (!callback) { return this; }

			// Space-separated names: 'open close'
			if (name.indexOf(' ') !== -1) {
				var names = name.split(/\s+/);
				for (var i = 0; i < names.length; i++) {
					this.on(names[i], callback, context);
				}
				return this;
			}

			this._events = this._events || {};
			(this._events[name] = this._events[name] || []).push({
				callback: callback,
				context: context,
				ctx: context || this
			});

			return this;
		},

		once: function (name, callback, context) {
			if (name && typeof name === 'object') {
				var target = this;
				return applyEventMap(name, callback, context, function (k, cb, ctx) {
					target.once(k, cb, ctx);
				}, this);
			}

			var self = this;
			var fired = false;

			function wrapper() {
				if (fired) { return; }
				fired = true;
				self.off(name, wrapper);
				callback.apply(this, arguments);
			}

			// Keep a handle on the original so off(name, original) still works.
			wrapper._callback = callback;

			return this.on(name, wrapper, context);
		},

		off: function (name, callback, context) {
			if (!this._events) { return this; }

			// Event-map form — the shape stopListening() unwinds.
			if (name && typeof name === 'object') {
				var self = this;
				return applyEventMap(name, callback, context, function (k, cb, ctx) {
					self.off(k, cb, ctx);
				}, this);
			}

			// off() — drop everything
			if (!name && !callback && !context) {
				this._events = {};
				return this;
			}

			var names = name ? [name] : Object.keys(this._events);

			for (var i = 0; i < names.length; i++) {
				var key = names[i];
				var handlers = this._events[key];

				if (!handlers) { continue; }

				if (!callback && !context) {
					delete this._events[key];
					continue;
				}

				var remaining = [];

				for (var j = 0; j < handlers.length; j++) {
					var handler = handlers[j];
					var callbackMatches = !callback
						|| callback === handler.callback
						|| callback === handler.callback._callback;
					var contextMatches = !context || context === handler.context;

					if (!(callbackMatches && contextMatches)) {
						remaining.push(handler);
					}
				}

				if (remaining.length) {
					this._events[key] = remaining;
				} else {
					delete this._events[key];
				}
			}

			return this;
		},

		trigger: function (name) {
			if (!this._events || !name) { return this; }

			var args = slice.call(arguments, 1);

			if (name.indexOf(' ') !== -1) {
				var names = name.split(/\s+/);
				for (var n = 0; n < names.length; n++) {
					this.trigger.apply(this, [names[n]].concat(args));
				}
				return this;
			}

			// Copy: a handler may unbind itself (once) while we iterate.
			var handlers = (this._events[name] || []).slice();

			for (var i = 0; i < handlers.length; i++) {
				handlers[i].callback.apply(handlers[i].ctx, args);
			}

			// Backbone's catch-all: 'all' handlers receive the event name first.
			var all = (this._events.all || []).slice();

			for (var a = 0; a < all.length; a++) {
				all[a].callback.apply(all[a].ctx, [name].concat(args));
			}

			return this;
		},

		listenTo: function (obj, name, callback) {
			if (!obj) { return this; }

			this._listeningTo = this._listeningTo || [];
			this._listeningTo.push({ obj: obj, name: name, callback: callback });

			obj.on(name, callback, this);

			return this;
		},

		stopListening: function (obj) {
			if (!this._listeningTo) { return this; }

			var remaining = [];

			for (var i = 0; i < this._listeningTo.length; i++) {
				var entry = this._listeningTo[i];

				if (!obj || obj === entry.obj) {
					entry.obj.off(entry.name, entry.callback, this);
				} else {
					remaining.push(entry);
				}
			}

			this._listeningTo = remaining;

			return this;
		}
	};

	fw.Events = Events;

	/* ------------------------------------------------------------------ *
	 * Backbone-style subclassing: Klass.extend({...}, {...})
	 * ------------------------------------------------------------------ */

	function extend(protoProps, staticProps) {
		var Parent = this;
		var Child;

		if (protoProps && Object.prototype.hasOwnProperty.call(protoProps, 'constructor')) {
			Child = protoProps.constructor;
		} else {
			Child = function () { return Parent.apply(this, arguments); };
		}

		// Static inheritance
		for (var key in Parent) {
			if (Object.prototype.hasOwnProperty.call(Parent, key)) {
				Child[key] = Parent[key];
			}
		}

		if (staticProps) {
			for (var s in staticProps) {
				if (Object.prototype.hasOwnProperty.call(staticProps, s)) {
					Child[s] = staticProps[s];
				}
			}
		}

		Child.prototype = Object.create(Parent.prototype);
		Child.prototype.constructor = Child;

		if (protoProps) {
			for (var p in protoProps) {
				if (Object.prototype.hasOwnProperty.call(protoProps, p)) {
					Child.prototype[p] = protoProps[p];
				}
			}
		}

		Child.__super__ = Parent.prototype;

		return Child;
	}

	fw.extendClass = extend;

	/* ------------------------------------------------------------------ *
	 * fw.Class — attribute bag + events (the Backbone.Model replacement)
	 * ------------------------------------------------------------------ */

	function Class(attributes, options) {
		this.cid = uniqueId('c');
		this.attributes = {};

		var defaults = typeof this.defaults === 'function' ? this.defaults() : this.defaults;

		if (defaults) {
			for (var key in defaults) {
				if (Object.prototype.hasOwnProperty.call(defaults, key)) {
					this.attributes[key] = defaults[key];
				}
			}
		}

		if (attributes) {
			for (var a in attributes) {
				if (Object.prototype.hasOwnProperty.call(attributes, a)) {
					this.attributes[a] = attributes[a];
				}
			}
		}

		if (typeof this.initialize === 'function') {
			this.initialize(attributes, options);
		}
	}

	Class.prototype.get = function (key) {
		return this.attributes[key];
	};

	Class.prototype.has = function (key) {
		return this.attributes[key] != null;
	};

	/**
	 * set(key, value) or set({key: value})
	 *
	 * Fires `change:<key>` for each changed key, then a single `change`.
	 * Pass `{silent: true}` to suppress both.
	 */
	Class.prototype.set = function (key, value, options) {
		var attrs;

		if (key == null) { return this; }

		if (typeof key === 'object') {
			attrs = key;
			options = value;
		} else {
			attrs = {};
			attrs[key] = value;
		}

		options = options || {};

		var changed = [];

		for (var k in attrs) {
			if (!Object.prototype.hasOwnProperty.call(attrs, k)) { continue; }
			if (this.attributes[k] === attrs[k]) { continue; }

			this.attributes[k] = attrs[k];
			changed.push(k);
		}

		if (!options.silent) {
			for (var i = 0; i < changed.length; i++) {
				this.trigger('change:' + changed[i], this, this.attributes[changed[i]], options);
			}

			if (changed.length) {
				this.trigger('change', this, options);
			}
		}

		return this;
	};

	Class.prototype.toJSON = function () {
		var out = {};

		for (var key in this.attributes) {
			if (Object.prototype.hasOwnProperty.call(this.attributes, key)) {
				out[key] = this.attributes[key];
			}
		}

		return out;
	};

	for (var e in Events) {
		if (Object.prototype.hasOwnProperty.call(Events, e)) {
			Class.prototype[e] = Events[e];
		}
	}

	Class.extend = extend;

	fw.Class = Class;

	/* ------------------------------------------------------------------ *
	 * fw.View — minimal DOM view (the Backbone.View replacement)
	 * ------------------------------------------------------------------ */

	function View(options) {
		options = options || {};

		this.cid = uniqueId('v');

		// Copy the options Backbone.View copied onto the instance.
		var keep = ['model', 'collection', 'el', 'attributes', 'className', 'tagName', 'controller'];

		for (var i = 0; i < keep.length; i++) {
			if (options[keep[i]] !== undefined) {
				this[keep[i]] = options[keep[i]];
			}
		}

		this.options = options;

		if (this.el) {
			this.setElement(this.el);
		} else {
			var el = document.createElement(this.tagName || 'div');
			var attributes = typeof this.attributes === 'function' ? this.attributes() : this.attributes;

			if (this.className) { el.className = this.className; }

			if (attributes) {
				for (var a in attributes) {
					if (Object.prototype.hasOwnProperty.call(attributes, a)) {
						el.setAttribute(a, attributes[a]);
					}
				}
			}

			this.setElement(el);
		}

		if (typeof this.initialize === 'function') {
			this.initialize(options);
		}
	}

	View.prototype.tagName = 'div';

	/** Backbone's 'event selector' key splitter, e.g. 'click .js-save'. */
	var eventSplitter = /^(\S+)\s*(.*)$/;

	View.prototype.setElement = function (el) {
		this.undelegateEvents();

		this.$el = jQuery(el);
		this.el = this.$el[0];

		this.delegateEvents();

		return this;
	};

	/**
	 * Bind the view's `events` hash, exactly as Backbone.View does.
	 *
	 * This is not optional sugar: views declare things like
	 * `events: { 'submit': 'onSubmit' }` and never wire the handler themselves —
	 * the base class is expected to do it. The options modal's Save button works by
	 * triggering a hidden submit input, so without this the whole save path is
	 * silently dead.
	 *
	 * Values may be a method name on the view or a function. Keys are
	 * 'event' or 'event selector' (the latter binding a delegated handler).
	 *
	 * @param {Object} [events] Defaults to this.events.
	 */
	View.prototype.delegateEvents = function (events) {
		events = events || (typeof this.events === 'function' ? this.events() : this.events);

		if (!events || !this.$el) { return this; }

		this.undelegateEvents();

		for (var key in events) {
			if (!Object.prototype.hasOwnProperty.call(events, key)) { continue; }

			var method = events[key];

			if (typeof method !== 'function') { method = this[method]; }
			if (!method) { continue; }

			var match = key.match(eventSplitter);

			this.delegate(match[1], match[2], method.bind(this));
		}

		return this;
	};

	/**
	 * @param {string}   eventName DOM event, e.g. 'submit'.
	 * @param {string}   selector  Optional delegation selector.
	 * @param {Function} listener  Bound handler.
	 */
	View.prototype.delegate = function (eventName, selector, listener) {
		this.$el.on(eventName + '.delegateEvents' + this.cid, selector || null, listener);

		return this;
	};

	View.prototype.undelegateEvents = function () {
		if (this.$el) {
			this.$el.off('.delegateEvents' + this.cid);
		}

		return this;
	};

	View.prototype.render = function () {
		return this;
	};

	View.prototype.remove = function () {
		this.stopListening();

		if (this.$el) { this.$el.remove(); }

		return this;
	};

	for (var v in Events) {
		if (Object.prototype.hasOwnProperty.call(Events, v)) {
			View.prototype[v] = Events[v];
		}
	}

	View.extend = extend;

	fw.View = View;
})();
