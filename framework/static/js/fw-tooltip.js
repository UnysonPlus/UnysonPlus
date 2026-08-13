/**
 * $.fn.fwTooltip — the framework's tooltip, built on Floating UI.
 *
 * NAMING
 * Deliberately named for what it does, not for the library underneath. The
 * previous implementation exposed its vendor in the public surface (class names,
 * script handle, plugin method), so when that vendor stopped being maintained the
 * naming advertised it. The engine is an implementation detail; if Floating UI is
 * ever replaced, only this file changes and nothing downstream notices.
 *
 * WHAT IT SUPPORTS
 * Only the subset the framework actually uses. Anything else warns to the console
 * rather than silently doing nothing — an unhandled option should be loud in
 * development, not a mystery bug later.
 *
 *   position: { at, my, viewport, adjust: { x, y } }
 *   style:    { classes, tip: { width, height }, width }
 *   content:  { text: String|jQuery|Function } — or a bare String/jQuery
 *   show:     'click' | { event, solo, effect, ready }
 *   hide:     'unfocus' | { event, effect }
 *   events:   { show, hide }
 *   id, suppress
 *
 *   .fwTooltip('api')               -> api
 *   .fwTooltip('destroy'[, keep])   -> destroy
 *   api.show() / api.hide() / api.toggle() / api.destroy(keepData)
 *   api.set('content.text', v)
 *   api.elements.tooltip / api.elements.content   (jQuery objects)
 *
 * THE TITLE-ATTRIBUTE CONTRACT (the subtle part)
 * $.fn.attr is hooked so that reading `.attr('title')` on a tooltipped element
 * transparently returns the stashed copy, and writing it updates live content.
 * Two places depend on this and would break without it:
 *   - fw.js resets tip content on hide via `$i.attr('title')` (stops videos).
 *   - sidebars' showTip() sets `.attr('title', text)` then fires 'show_tip',
 *     expecting the NEW text to render.
 *
 * DOM SHAPE
 *   <div id="fw-tooltip-ID" class="fw-tooltip fw-tooltip-default ..." role="tooltip">
 *     <div id="fw-tooltip-ID-content" class="fw-tooltip-content"></div>
 *     <div class="fw-tooltip-arrow"></div>
 *   </div>
 */
(function ($, window, document) {
	'use strict';

	if (!$) { return; }

	if (!window.FloatingUIDOM) {
		window.console && console.error(
			'fw-tooltip: Floating UI is missing — the tooltip shim cannot position anything. ' +
			'Check that the floating-ui scripts are enqueued before this file.'
		);
		return;
	}

	var FUI = window.FloatingUIDOM;

	var NAMESPACE = 'fwTooltip';
	var ATTR_HAS  = 'data-fw-tooltip';
	var OLDTITLE  = 'data-fw-tooltip-title';

	var idCounter = 0;
	var instances = [];       // every live instance, for solo/unfocus handling

	/**
	 * Only these keys are understood. Anything else gets a one-time console warning
	 * so a missed option surfaces during development instead of failing silently.
	 */
	var KNOWN_OPTIONS = {
		position: 1, style: 1, content: 1, show: 1, hide: 1,
		events: 1, id: 1, suppress: 1, overwrite: 1, prerender: 1
	};

	var warned = {};
	function warnOnce(key, message) {
		if (warned[key]) { return; }
		warned[key] = true;
		window.console && console.warn('fw-tooltip: ' + message);
	}

	// ---------------------------------------------------------------------
	// Corner mapping
	// ---------------------------------------------------------------------

	/**
	 * Placement is described with two corners: `at` is the point on the TARGET,
	 * `my` is the point on the TOOLTIP that meets it. Floating UI wants a single
	 * placement string. The side comes from `at`; the alignment comes from its
	 * secondary token.
	 *
	 *   at:'top center'    my:'bottom center'  -> 'top'
	 *   at:'bottom center' my:'top center'     -> 'bottom'
	 *   at:'center right'  my:'center left'    -> 'right'
	 */
	function cornersToPlacement(at, my) {
		at = String(at || 'top center').toLowerCase().split(/\s+/);
		my = String(my || 'bottom center').toLowerCase().split(/\s+/);

		var side, align = '';

		if (at[0] === 'top' || at[0] === 'bottom') {
			side = at[0];
			// secondary token is horizontal
			if (at[1] === 'left')  { align = '-start'; }
			if (at[1] === 'right') { align = '-end'; }
		} else if (at[1] === 'left' || at[1] === 'right') {
			side = at[1];
			if (at[0] === 'top')    { align = '-start'; }
			if (at[0] === 'bottom') { align = '-end'; }
		} else {
			// 'center center' and anything unrecognised: infer from `my` instead
			side = (my[0] === 'top') ? 'bottom' : 'top';
		}

		return side + align;
	}

	// ---------------------------------------------------------------------
	// Instance
	// ---------------------------------------------------------------------

	function FwTooltip($target, options) {
		this.$target  = $target;
		this.target   = $target[0];
		this.options  = options;
		this.id       = options.id || ('fw-tooltip-' + (++idCounter));
		this.visible  = false;
		this.rendered = false;
		this.destroyed = false;
		this.cleanupAuto = null;
		this.boundEvents = [];

		// True when content came from the title attribute,
		// which is what makes a later .attr('title', x) update the live content.
		this.contentFromAttr = false;

		this.render();
		this.bind();
	}

	FwTooltip.prototype.render = function () {
		var o = this.options;
		var style = o.style || {};

		var $tooltip = $('<div/>', {
			id: 'fw-tooltip-' + this.id,
			role: 'tooltip',
			'class': 'fw-tooltip fw-tooltip-pos-' + cornersToPlacement(
				(o.position || {}).at, (o.position || {}).my
			).replace('-', ' fw-tooltip-align-') + ' ' + (style.classes || '')
		}).css({
			position: 'absolute',
			left: '-28000px',
			top: '-28000px',
			display: 'none'
		});

		var $content = $('<div/>', {
			id: 'fw-tooltip-' + this.id + '-content',
			'class': 'fw-tooltip-content'
		});

		var $tip = $('<div/>', { 'class': 'fw-tooltip-arrow' });

		if (style.width) {
			$tooltip.css('width', typeof style.width === 'number' ? style.width + 'px' : style.width);
		}

		// A CSS triangle renders the arrow; dimensions come from style.tip at
		// these sizes and avoids shipping the drawing code. Dimensions come from
		// style.tip so the existing 12x5 callers look unchanged.
		var tip = style.tip || {};
		var tw = parseInt(tip.width, 10)  || 12;
		var th = parseInt(tip.height, 10) || 5;
		$tip.css({ width: tw + 'px', height: th + 'px', position: 'absolute' });
		this.tipSize = { width: tw, height: th };

		$tooltip.append($content).append($tip);
		$('body').append($tooltip);

		this.elements = {
			tooltip: $tooltip,
			content: $content,
			tip: $tip,
			target: this.$target
		};

		this.$target.attr(ATTR_HAS, this.id);

		this.applyContent();
		this.rendered = true;
	};

	/**
	 * Resolve the configured content into the content element. Falls back to the
	 * target's (stashed) title attribute, which is how the help icons work.
	 */
	FwTooltip.prototype.applyContent = function () {
		var o = this.options;
		var raw = o.content;
		var text;

		if (raw && typeof raw === 'object' && !raw.jquery && 'text' in raw) {
			text = raw.text;
		} else if (raw !== undefined && raw !== null && raw !== '') {
			text = raw;                     // bare string / jQuery (image-picker does this)
		}

		if (text === undefined || text === null || text === '' || text === true) {
			text = this.getStashedTitle();
			this.contentFromAttr = true;
		}

		this.setContent(text);
	};

	FwTooltip.prototype.setContent = function (text) {
		var $c = this.elements.content;
		if (!$c) { return; }

		if (typeof text === 'function') {
			text = text.call(this.target, null, this);
		}

		if (text && text.jquery) {
			// Move the live node in rather than copying markup — the builder's template
			// and section-sorter panels rely on keeping their bound event handlers.
			$c.empty().append(text);
		} else {
			$c.html(text == null ? '' : String(text));
		}
	};

	FwTooltip.prototype.getStashedTitle = function () {
		var t = this.target.getAttribute(OLDTITLE);
		if (t === null) { t = this.target.getAttribute('title'); }
		return t || '';
	};

	// ---------------------------------------------------------------------
	// Positioning
	// ---------------------------------------------------------------------

	FwTooltip.prototype.reposition = function () {
		if (this.destroyed || !this.rendered) { return; }

		var self = this;
		var o = this.options;
		var pos = o.position || {};
		var placement = cornersToPlacement(pos.at, pos.my);

		var boundary = undefined;
		if (pos.viewport && pos.viewport.jquery && pos.viewport.length) {
			boundary = pos.viewport[0];
		} else if (pos.viewport && pos.viewport.nodeType) {
			boundary = pos.viewport;
		}

		var detectOpts = boundary && boundary !== document.body ? { boundary: boundary } : {};

		var middleware = [
			FUI.offset(this.tipSize.height),
			FUI.flip(detectOpts),
			FUI.shift($.extend({ padding: 5 }, detectOpts)),
			FUI.arrow({ element: this.elements.tip[0], padding: 6 })
		];

		return FUI.computePosition(this.target, this.elements.tooltip[0], {
			placement: placement,
			strategy: 'absolute',
			middleware: middleware
		}).then(function (data) {
			if (self.destroyed) { return; }

			var adjust = (o.position && o.position.adjust) || {};
			var x = data.x + (parseInt(adjust.x, 10) || 0);
			var y = data.y + (parseInt(adjust.y, 10) || 0);

			// Positioned with top/left rather than transform on purpose: fw.js and
			// the image-picker nudge the tooltip afterwards by reading and writing
			// .css('top'), which a transform-based position would silently ignore.
			self.elements.tooltip.css({ left: x + 'px', top: y + 'px' });

			self.placeTip(data);
		});
	};

	FwTooltip.prototype.placeTip = function (data) {
		var arrow = data.middlewareData && data.middlewareData.arrow;
		if (!arrow) { return; }

		var side = data.placement.split('-')[0];
		var opposite = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' }[side];
		var w = this.tipSize.width, h = this.tipSize.height;

		var colour = this.elements.tooltip.css('border-top-color') ||
		             this.elements.tooltip.css('background-color') || '#333';

		var css = {
			left: arrow.x != null ? arrow.x + 'px' : '',
			top:  arrow.y != null ? arrow.y + 'px' : '',
			right: '', bottom: '',
			width: 0, height: 0,
			borderStyle: 'solid',
			borderColor: 'transparent'
		};

		// The triangle points back at the target, so it hangs off the opposite edge.
		if (side === 'top') {
			css.bottom = (-h) + 'px';
			css.borderWidth = h + 'px ' + (w / 2) + 'px 0 ' + (w / 2) + 'px';
			css.borderTopColor = colour;
		} else if (side === 'bottom') {
			css.top = (-h) + 'px';
			css.borderWidth = '0 ' + (w / 2) + 'px ' + h + 'px ' + (w / 2) + 'px';
			css.borderBottomColor = colour;
		} else if (side === 'left') {
			css.right = (-h) + 'px';
			css.borderWidth = (w / 2) + 'px 0 ' + (w / 2) + 'px ' + h + 'px';
			css.borderLeftColor = colour;
		} else {
			css.left = (-h) + 'px';
			css.borderWidth = (w / 2) + 'px ' + h + 'px ' + (w / 2) + 'px 0';
			css.borderRightColor = colour;
		}

		css['border-' + opposite + '-width'] = '0';
		this.elements.tip.css(css);
	};

	// ---------------------------------------------------------------------
	// Show / hide
	// ---------------------------------------------------------------------

	FwTooltip.prototype.show = function () {
		if (this.destroyed || this.visible) { return this; }

		var self = this;
		var o = this.options;
		var show = normaliseShow(o.show);

		if (this.contentFromAttr) {
			// Re-read on every show: sidebars rewrites the title before firing its
			// custom show event, and expects the new text.
			this.setContent(this.getStashedTitle());
		}

		if (show.solo) {
			$.each(instances.slice(), function (_, inst) {
				if (inst !== self && inst.visible) { inst.hide(); }
			});
		}

		this.visible = true;
		this.elements.tooltip.addClass('fw-tooltip-open');

		// Make it measurable before Floating UI reads its size, but keep it
		// invisible so there is no flash at the pre-position coordinates.
		this.elements.tooltip.css({ display: 'block', visibility: 'hidden' });

		this.reposition().then(function () {
			if (self.destroyed || !self.visible) { return; }

			self.elements.tooltip.css('visibility', '');

			if (typeof show.effect === 'function') {
				// The effect is called with `this` = the tooltip element and the api as
				// its argument; fw.js and image-picker both rely on that signature.
				self.elements.tooltip.css('display', 'none');
				show.effect.call(self.elements.tooltip[0], self);
			} else {
				self.elements.tooltip.css('display', 'block');
			}

			self.startAutoUpdate();
			self.fire('show');
		});

		return this;
	};

	FwTooltip.prototype.hide = function () {
		if (this.destroyed || !this.visible) { return this; }

		var self = this;
		var hide = normaliseHide(this.options.hide);

		this.visible = false;
		this.elements.tooltip.removeClass('fw-tooltip-open');
		this.stopAutoUpdate();

		if (typeof hide.effect === 'function') {
			hide.effect.call(this.elements.tooltip[0], this);
		} else {
			this.elements.tooltip.css('display', 'none');
		}

		this.fire('hide');
		return this;
	};

	FwTooltip.prototype.toggle = function (state) {
		var want = (state === undefined) ? !this.visible : !!state;
		return want ? this.show() : this.hide();
	};

	FwTooltip.prototype.fire = function (name) {
		var events = this.options.events || {};
		if (typeof events[name] === 'function') {
			events[name].call(this.elements.tooltip[0], $.Event('tooltip' + name), this);
		}
	};

	/**
	 * Keep the tooltip glued to its target while visible. Matters most in the
	 * builder, where panels scroll inside their own container.
	 */
	FwTooltip.prototype.startAutoUpdate = function () {
		var self = this;
		this.stopAutoUpdate();
		if (FUI.autoUpdate) {
			this.cleanupAuto = FUI.autoUpdate(this.target, this.elements.tooltip[0], function () {
				self.reposition();
			});
		}
	};

	FwTooltip.prototype.stopAutoUpdate = function () {
		if (this.cleanupAuto) { this.cleanupAuto(); this.cleanupAuto = null; }
	};

	// ---------------------------------------------------------------------
	// Events
	// ---------------------------------------------------------------------

	function normaliseShow(show) {
		if (typeof show === 'string') { return { event: show }; }
		return $.extend({ event: 'mouseenter' }, show || {});
	}

	function normaliseHide(hide) {
		if (typeof hide === 'string') {
			return hide === 'unfocus' ? { unfocus: true } : { event: hide };
		}
		if (hide && hide.event === 'unfocus') { return $.extend({}, hide, { unfocus: true }); }
		return $.extend({ event: 'mouseleave' }, hide || {});
	}

	FwTooltip.prototype.on = function ($el, events, handler) {
		$el.on(events, handler);
		this.boundEvents.push({ $el: $el, events: events, handler: handler });
	};

	FwTooltip.prototype.bind = function () {
		var self = this;
		var show = normaliseShow(this.options.show);
		var hide = normaliseHide(this.options.hide);
		var ns = '.fw-tooltip-' + this.id;

		if (show.event === 'click') {
			this.on(this.$target, 'click' + ns, function (e) {
				e.preventDefault();
				self.toggle();
			});
		} else if (show.event) {
			$.each(show.event.split(/\s+/), function (_, ev) {
				self.on(self.$target, ev + ns, function () { self.show(); });
			});
		}

		if (hide.unfocus) {
			// Deferred so the click that opened it doesn't immediately close it.
			this.on($(document), 'mousedown' + ns, function (e) {
				if (!self.visible) { return; }
				if (self.target === e.target || self.$target.has(e.target).length) { return; }
				if (self.elements.tooltip[0] === e.target ||
				    self.elements.tooltip.has(e.target).length) { return; }
				self.hide();
			});
		} else if (hide.event && hide.event !== 'unfocus') {
			$.each(hide.event.split(/\s+/), function (_, ev) {
				self.on(self.$target, ev + ns, function () { self.hide(); });
			});
		}

		// Tear down when the target leaves the DOM; fw.js leans on this.
		this.on(this.$target, 'remove' + ns + ' removetooltip' + ns, function () {
			self.destroy();
		});
	};

	// ---------------------------------------------------------------------
	// Destroy
	// ---------------------------------------------------------------------

	FwTooltip.prototype.destroy = function (keepData) {
		if (this.destroyed) { return; }
		this.destroyed = true;
		this.visible = false;

		this.stopAutoUpdate();

		$.each(this.boundEvents, function (_, b) { b.$el.off(b.events, b.handler); });
		this.boundEvents = [];

		this.elements.tooltip.remove();

		this.$target.removeAttr(ATTR_HAS);
		this.$target.removeData(NAMESPACE);

		// Restore the native title unless the caller asked to keep our data.
		if (!keepData) {
			var stashed = this.target.getAttribute(OLDTITLE);
			if (stashed !== null) {
				this.target.setAttribute('title', stashed);
				this.target.removeAttribute(OLDTITLE);
			}
		}

		var i = $.inArray(this, instances);
		if (i > -1) { instances.splice(i, 1); }
	};

	/**
	 * Only the content.text path is used by the framework.
	 */
	FwTooltip.prototype.set = function (path, value) {
		if (path === 'content.text') {
			this.setContent(value);
			if (this.visible) { this.reposition(); }
			return this;
		}
		warnOnce('set:' + path, 'api.set("' + path + '") is not implemented.');
		return this;
	};

	// ---------------------------------------------------------------------
	// $.fn.fwTooltip
	// ---------------------------------------------------------------------

	$.fn.fwTooltip = function (options, arg1) {
		// Command form: .fwTooltip('api'), .fwTooltip('destroy', true), .fwTooltip('show') ...
		if (typeof options === 'string') {
			var api = this.length ? $(this[0]).data(NAMESPACE) : null;

			switch (options) {
				case 'api':
					return api;
				case 'destroy':
					this.each(function () {
						var inst = $(this).data(NAMESPACE);
						if (inst) { inst.destroy(arg1); }
					});
					return this;
				case 'show':
				case 'hide':
				case 'toggle':
					this.each(function () {
						var inst = $(this).data(NAMESPACE);
						if (inst) { inst[options](arg1); }
					});
					return this;
				case 'option':
					return api ? api.options : undefined;
				default:
					warnOnce('cmd:' + options, 'unsupported command .fwTooltip("' + options + '").');
					return this;
			}
		}

		var opts = $.extend(true, {}, options || {});

		$.each(opts, function (key) {
			if (!KNOWN_OPTIONS[key]) {
				warnOnce('opt:' + key, 'option "' + key + '" is not implemented and will be ignored.');
			}
		});

		return this.each(function () {
			var $el = $(this);

			// Re-initialising replaces by default.
			var existing = $el.data(NAMESPACE);
			if (existing) {
				if (opts.overwrite === false) { return; }
				existing.destroy(true);
			}

			// Stash the title so the browser's native tooltip doesn't double up.
			// The $.fn.attr hook below keeps .attr('title') reading this copy.
			if (opts.suppress !== false) {
				var title = this.getAttribute('title');
				if (title) {
					this.setAttribute(OLDTITLE, title);
					this.setAttribute('title', '');
				}
			}

			var inst = new FwTooltip($el, opts);
			$el.data(NAMESPACE, inst);
			instances.push(inst);

			var show = normaliseShow(opts.show);
			if (show.ready) { inst.show(); }
		});
	};

	$.fn.fwTooltip.shim = true;
	$.fn.fwTooltip.version = '1.0.0 (Floating UI)';

	// ---------------------------------------------------------------------
	// $.fn.attr hook — see the header note. Without this, fw.js blanks its tip
	// content on hide and the sidebars error popups show stale text.
	// ---------------------------------------------------------------------

	var originalAttr = $.fn.attr;

	$.fn.attr = function (name, value) {
		if (this.length && name === 'title') {
			var el = this[0];
			var inst = $.data(el, NAMESPACE);

			if (inst && !inst.destroyed && inst.options.suppress !== false) {
				if (arguments.length < 2) {
					return el.getAttribute(OLDTITLE);
				}

				if (inst.contentFromAttr) { inst.set('content.text', value); }

				el.setAttribute(OLDTITLE, value == null ? '' : String(value));
				return this;
			}
		}

		return originalAttr.apply(this, arguments);
	};

})(window.jQuery, window, document);
