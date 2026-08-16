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

	/**
	 * Backbone read view properties through _.result(), so `tagName`,
	 * `className`, `id` and `attributes` may each be a value OR a function
	 * returning one. Nothing in the framework uses the function form today,
	 * but the contract is part of what consumers may rely on.
	 */
	function resultOf(obj, key) {
		return typeof obj[key] === 'function' ? obj[key]() : obj[key];
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

		/**
		 * listenTo() that fires at most once, then unbinds itself.
		 *
		 * Used by every form-builder item as
		 * `this.listenToOnce(this.labelInlineEditor, 'hide', …)`. Registered in
		 * _listeningTo like listenTo, so stopListening() still unwinds it if the
		 * view is torn down before the event ever fires.
		 */
		listenToOnce: function (obj, name, callback) {
			if (!obj) { return this; }

			var self = this;

			function wrapper() {
				obj.off(name, wrapper, self);
				callback.apply(self, arguments);
			}

			this._listeningTo = this._listeningTo || [];
			this._listeningTo.push({ obj: obj, name: name, callback: wrapper });

			obj.on(name, wrapper, this);

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

		// Before initialize(): subclasses expect get('_items') to already be a
		// collection inside their own initialize(), which is what
		// backbone-relational guaranteed.
		this._initNested();

		if (typeof this.initialize === 'function') {
			this.initialize(attributes, options);
		}

		/**
		 * Replay the constructor's attribute changes AFTER initialize().
		 *
		 * This looks odd and is load-bearing. backbone-relational defers the
		 * change events from the initial set until after initialize() has run,
		 * so a listener registered *inside* initialize() still receives them.
		 * The builder depends on exactly that: an item's view is created in the
		 * model's initialize() and binds
		 *
		 *     this.listenTo(this.model, 'change', this.render)
		 *
		 * and that deferred `change` is the ONLY thing that performs the item's
		 * first render. Item view subclasses override initialize() without
		 * calling render(), so without this the element is created, added to
		 * the canvas, and stays empty — the item silently vanishes.
		 *
		 * Scoped to classes declaring `nested` (the ones that replaced
		 * RelationalModel) so plain fw.Class users — fw.Modal and friends —
		 * keep Backbone.Model's ordinary "no events from the constructor"
		 * behaviour.
		 */
		if (this.nested && attributes) {
			for (var c in attributes) {
				if (Object.prototype.hasOwnProperty.call(attributes, c)) {
					this.trigger('change:' + c, this, this.attributes[c]);
				}
			}

			this.trigger('change', this);
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
				var value = this.attributes[key];

				// A nested collection serialises to its array form, so a tree
				// round-trips through JSON.stringify exactly as it was loaded.
				out[key] = (value && typeof value.toJSON === 'function' && value.models)
					? value.toJSON()
					: value;
			}
		}

		return out;
	};

	/**
	 * Build the declared nested collection, if this class has one.
	 *
	 * `nested` replaces the ONE backbone-relational relation the framework
	 * ever used — a self-referential HasMany. Declare it on the prototype:
	 *
	 *     nested: {
	 *         key:           '_items',   // attribute holding the children
	 *         collectionKey: '_item',    // back-reference set on the collection
	 *         collection:    function () { return Items; }  // lazy: the class
	 *     }                                                 // may not exist yet
	 *
	 * Deliberately not a relations engine. One relation, declared, because
	 * that is all the codebase has ever used — `relations` is overridden
	 * exactly once across every extension.
	 *
	 * Recursion is implicit: the collection instantiates its `model`, whose
	 * constructor runs this again for its own children.
	 */
	Class.prototype._initNested = function () {
		if (!this.nested || !this.nested.key) { return; }

		var key = this.nested.key,
			value = this.attributes[key],
			CollectionClass = typeof this.nested.collection === 'function'
				? this.nested.collection()
				: this.nested.collection;

		if (!CollectionClass) { return; }

		// Already a collection (e.g. re-initialised) — just re-link it.
		if (value && value.models) {
			if (this.nested.collectionKey) { value[this.nested.collectionKey] = this; }
			return;
		}

		// backbone-relational created the collection even when the attribute
		// was absent, and the saved format always carries `_items` — so an
		// empty array is the correct default, never `undefined`.
		var collection = new CollectionClass(value || []);

		if (this.nested.collectionKey) {
			collection[this.nested.collectionKey] = this;
		}

		this.attributes[key] = collection;
	};

	for (var e in Events) {
		if (Object.prototype.hasOwnProperty.call(Events, e)) {
			Class.prototype[e] = Events[e];
		}
	}

	Class.extend = extend;

	fw.Class = Class;

	/* ------------------------------------------------------------------ *
	 * fw.Collection — ordered set of fw.Class models
	 *                 (the Backbone.Collection replacement)
	 *
	 * Built for the builder canvas (step 6). The method set is not a guess:
	 * it is what the 23 files extending `builder.classes.*` actually call —
	 * add, remove, reset, each, get, at, where, findWhere, pluck, indexOf,
	 * toJSON — with Backbone's signatures, so those consumers need no edits.
	 *
	 * ## toJSON is the persistence format, not a convenience
	 *
	 * The builder saves with `JSON.stringify( builder.rootItems )`, so
	 * whatever this produces IS what lands in the database for every page
	 * ever built. It must stay an array of `model.toJSON()` results, in
	 * order. A mismatch here does not throw — it silently writes page data
	 * that cannot be read back.
	 *
	 * ## Events
	 *
	 * `add` (model, collection), `remove` (model, collection, {index}),
	 * `reset` (collection). Model events are forwarded to the collection so
	 * a view listening on the collection still sees `change:*` from any
	 * contained model — the behaviour the canvas redraws on.
	 * ------------------------------------------------------------------ */

	function Collection(models, options) {
		/**
		 * NO `cid` HERE — deliberately.
		 *
		 * Backbone.Collection has no cid; only Backbone.Model does. That
		 * asymmetry is load-bearing: builder.js's _fixItemsCollections()
		 * distinguishes a model from a collection with
		 *
		 *     if ( typeof collection.cid != 'undefined' ) { … it's a model … }
		 *
		 * because the same handler is bound to both `add` (model, collection)
		 * and `reset` (collection). Giving collections a cid made every reset
		 * look like a model, so the builder read the wrong argument and threw
		 * "collection.each is not a function" — the whole canvas failed to
		 * load and dropped items vanished.
		 *
		 * Duck-typing on cid is fragile, but it is the existing contract, and
		 * matching Backbone exactly is the job here.
		 */
		this.models = [];
		this.length = 0;

		options = options || {};

		if (options.model) { this.model = options.model; }

		if (typeof this.initialize === 'function') {
			this.initialize(models, options);
		}

		if (models) {
			this.reset(models, { silent: true });
		}
	}

	/**
	 * Turn a plain object into `this.model`, or pass an existing model
	 * through untouched. Mirrors Backbone's _prepareModel.
	 */
	Collection.prototype._prepareModel = function (attrs, options) {
		if (attrs instanceof Class) { return attrs; }

		var ModelClass = this.model || Class,
			opts = {};

		if (options) {
			for (var k in options) {
				if (Object.prototype.hasOwnProperty.call(options, k)) { opts[k] = options[k]; }
			}
		}

		// Backbone put the collection on options before constructing, so a
		// model's initialize() can already see it.
		opts.collection = this;

		/**
		 * `new` is deliberate even when `model` is a plain function rather
		 * than a class — the builder's Items.model is a FACTORY that inspects
		 * attrs.type and RETURNS an instance of the right subclass. Calling it
		 * with `new` returns that object (JS returns an explicitly returned
		 * object instead of `this`), which is exactly what Backbone did.
		 */
		return new ModelClass(attrs, opts);
	};

	/** Forward a contained model's events to the collection. */
	Collection.prototype._forward = function (model) {
		var self = this;

		if (!model || typeof model.on !== 'function') { return; }

		model._fwCollectionForward = function (name) {
			var args = Array.prototype.slice.call(arguments, 1);
			self.trigger.apply(self, [name].concat(args));
		};

		model.on('all', model._fwCollectionForward);
	};

	Collection.prototype._unforward = function (model) {
		if (model && model._fwCollectionForward) {
			model.off('all', model._fwCollectionForward);
			delete model._fwCollectionForward;
		}
	};

	/**
	 * add(modelOrAttrs | array, [options])
	 *
	 * @param {Object|Array} models
	 * @param {Object} [options] `{at: Number}` to insert, `{silent: true}`
	 */
	Collection.prototype.add = function (models, options) {
		options = options || {};

		var list = Object.prototype.toString.call(models) === '[object Array]' ? models : [models],
			added = [];

		for (var i = 0; i < list.length; i++) {
			var model = this._prepareModel(list[i], options);

			if (!model) { continue; }

			model.collection = this;

			if (options.at != null) {
				this.models.splice(options.at + i, 0, model);
			} else {
				this.models.push(model);
			}

			this._forward(model);
			added.push(model);
		}

		this.length = this.models.length;

		if (!options.silent) {
			for (var a = 0; a < added.length; a++) {
				this.trigger('add', added[a], this, options);
			}
		}

		return Object.prototype.toString.call(models) === '[object Array]' ? added : added[0];
	};

	/** remove(model | array, [options]) */
	Collection.prototype.remove = function (models, options) {
		options = options || {};

		var list = Object.prototype.toString.call(models) === '[object Array]' ? models : [models],
			removed = [];

		for (var i = 0; i < list.length; i++) {
			var index = this.models.indexOf(list[i]);

			if (index === -1) { continue; }

			this.models.splice(index, 1);
			this._unforward(list[i]);

			if (list[i].collection === this) { delete list[i].collection; }

			removed.push({ model: list[i], index: index });
		}

		this.length = this.models.length;

		if (!options.silent) {
			for (var r = 0; r < removed.length; r++) {
				this.trigger('remove', removed[r].model, this, { index: removed[r].index });
			}
		}

		return models;
	};

	/** Replace the whole contents. Fires a single `reset`. */
	Collection.prototype.reset = function (models, options) {
		options = options || {};

		for (var i = 0; i < this.models.length; i++) {
			this._unforward(this.models[i]);

			if (this.models[i].collection === this) { delete this.models[i].collection; }
		}

		this.models = [];
		this.length = 0;

		if (models) {
			this.add(models, { silent: true, at: null });
		}

		if (!options.silent) {
			this.trigger('reset', this, options);
		}

		return this;
	};

	/** Insert at the front — Backbone's add(model, {at: 0}). */
	Collection.prototype.unshift = function (model, options) {
		var opts = { at: 0 };

		if (options) {
			for (var k in options) {
				if (Object.prototype.hasOwnProperty.call(options, k)) { opts[k] = options[k]; }
			}
			opts.at = 0;
		}

		return this.add(model, opts);
	};

	Collection.prototype.at = function (index) {
		return this.models[index];
	};

	/** get(cid | model) — by cid, or the model itself if already contained. */
	Collection.prototype.get = function (obj) {
		if (obj == null) { return undefined; }

		for (var i = 0; i < this.models.length; i++) {
			if (this.models[i] === obj || this.models[i].cid === obj || this.models[i].cid === (obj && obj.cid)) {
				return this.models[i];
			}
		}

		return undefined;
	};

	Collection.prototype.each = function (callback, context) {
		for (var i = 0; i < this.models.length; i++) {
			callback.call(context, this.models[i], i, this.models);
		}

		return this;
	};

	Collection.prototype.map = function (callback, context) {
		var out = [];

		for (var i = 0; i < this.models.length; i++) {
			out.push(callback.call(context, this.models[i], i, this.models));
		}

		return out;
	};

	Collection.prototype.filter = function (callback, context) {
		var out = [];

		for (var i = 0; i < this.models.length; i++) {
			if (callback.call(context, this.models[i], i, this.models)) {
				out.push(this.models[i]);
			}
		}

		return out;
	};

	/** where({attr: value, …}) — all models matching every pair. */
	Collection.prototype.where = function (attrs, first) {
		var out = [];

		for (var i = 0; i < this.models.length; i++) {
			var match = true;

			for (var key in attrs) {
				if (Object.prototype.hasOwnProperty.call(attrs, key)) {
					if (this.models[i].get(key) !== attrs[key]) { match = false; break; }
				}
			}

			if (match) {
				if (first) { return this.models[i]; }
				out.push(this.models[i]);
			}
		}

		return first ? undefined : out;
	};

	Collection.prototype.findWhere = function (attrs) {
		return this.where(attrs, true);
	};

	Collection.prototype.pluck = function (key) {
		return this.map(function (model) { return model.get(key); });
	};

	Collection.prototype.indexOf = function (model) {
		return this.models.indexOf(model);
	};

	/**
	 * THE PERSISTENCE FORMAT — see the note at the top of this section.
	 * An array of model.toJSON(), in order. Nothing else.
	 */
	Collection.prototype.toJSON = function () {
		return this.map(function (model) { return model.toJSON(); });
	};

	for (var c in Events) {
		if (Object.prototype.hasOwnProperty.call(Events, c)) {
			Collection.prototype[c] = Events[c];
		}
	}

	Collection.extend = extend;

	fw.Collection = Collection;

	/* ------------------------------------------------------------------ *
	 * fw.View — minimal DOM view (the Backbone.View replacement)
	 * ------------------------------------------------------------------ */

	function View(options) {
		options = options || {};

		this.cid = uniqueId('v');

		/**
		 * Backbone's `viewOptions` verbatim, plus `controller` (used by
		 * fw-modal-frame). `id` and `events` are NOT optional extras: the
		 * builder constructs every item view as
		 * `new ItemView({ id: 'fw-builder-item-' + cid, model: … })`, and
		 * dropping either silently produces an element with no id — which
		 * breaks view lookup, CSS and drag-drop targeting without throwing.
		 */
		var keep = ['model', 'collection', 'el', 'id', 'attributes', 'className', 'tagName', 'events', 'controller'];

		for (var i = 0; i < keep.length; i++) {
			if (options[keep[i]] !== undefined) {
				this[keep[i]] = options[keep[i]];
			}
		}

		this.options = options;

		if (this.el) {
			this.setElement(this.el);
		} else {
			var el = document.createElement(resultOf(this, 'tagName') || 'div');
			var attributes = resultOf(this, 'attributes');
			var className = resultOf(this, 'className');
			var elId = resultOf(this, 'id');

			// Order matches Backbone's _ensureElement: `attributes` first, then
			// id/class, so an explicit id/className wins over the hash.
			if (attributes) {
				for (var a in attributes) {
					if (Object.prototype.hasOwnProperty.call(attributes, a)) {
						el.setAttribute(a, attributes[a]);
					}
				}
			}

			if (elId) { el.setAttribute('id', elId); }
			if (className) { el.className = className; }

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
	 * Scoped selector — `this.$(sel)` is `this.$el.find(sel)`.
	 *
	 * Backbone views use this constantly instead of a global jQuery lookup,
	 * because it keeps a view's queries inside its own element. It is used by
	 * the page-builder simple item, every form-builder item, the contact-form
	 * item and the email builder — all of which call it during render(), so a
	 * missing `$` does not throw somewhere harmless: the render dies and the
	 * item vanishes from the canvas.
	 *
	 * @param {String} selector
	 * @returns {jQuery}
	 */
	View.prototype.$ = function (selector) {
		return this.$el.find(selector);
	};

	/**
	 * Remove one delegated binding. Backbone's counterpart to delegate().
	 *
	 * @param {String} eventName
	 * @param {String} [selector]
	 * @param {Function} [listener]
	 */
	View.prototype.undelegate = function (eventName, selector, listener) {
		if (!this.$el) { return this; }

		this.$el.off(eventName + '.delegateEvents' + this.cid, selector, listener);

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
