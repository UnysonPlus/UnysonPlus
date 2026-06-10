/**
 * Dynamic Content — admin picker.
 *
 * Injects behavior for the `.fw-dynamic-content-trigger` icon that the PHP layer
 * appends next to Text / Short Text / Textarea / Rich Editor option fields. Clicking
 * the icon opens a searchable popover of grouped tags; selecting one inserts a
 * `{{token}}` into the field (at the caret for inputs/textareas, into the active
 * TinyMCE instance for the Rich Editor).
 *
 * Tag data is provided by PHP via wp_localize_script as `_fw_dynamic_content`.
 */
(function ($, fwEvents) {
	'use strict';

	if (typeof window._fw_dynamic_content === 'undefined') {
		return;
	}

	var DATA = window._fw_dynamic_content;
	var L10N = DATA.l10n || {};

	var $openPopover = null; // the single currently-open popover, if any

	/**
	 * Insert text at the caret of an input/textarea (append as fallback).
	 */
	function insertAtCaret(el, text) {
		if (!el) {
			return;
		}

		el.focus();

		if (typeof el.selectionStart === 'number') {
			var start = el.selectionStart;
			var end = el.selectionEnd;
			el.value = el.value.slice(0, start) + text + el.value.slice(end);
			var caret = start + text.length;
			el.selectionStart = el.selectionEnd = caret;
		} else {
			el.value += text;
		}
	}

	/**
	 * Insert a token into whichever field the descriptor wraps.
	 */
	function insertToken($descriptor, type, token) {
		if (type === 'wp-editor') {
			var $ta = $descriptor.find('textarea.wp-editor-area').first();
			var edId = $ta.attr('id');
			var ed = (window.tinymce && edId) ? window.tinymce.get(edId) : null;

			if (ed && !ed.isHidden()) {
				ed.execCommand('mceInsertContent', false, token);
				ed.fire('change');
			} else if ($ta.length) {
				insertAtCaret($ta.get(0), token);
				$ta.trigger('change');
			}
			return;
		}

		var $field = $descriptor.find('input[type="text"], textarea').first();
		if (!$field.length) {
			return;
		}

		insertAtCaret($field.get(0), token);
		$field.trigger('change').trigger('keyup');

		if (window.fw && fw.options && fw.options.trigger && fw.options.trigger.changeForEl) {
			fw.options.trigger.changeForEl($descriptor, { value: $field.val() });
		}
	}

	/**
	 * Strip characters that would corrupt the {{tag|key=value}} grammar.
	 */
	function sanitizeParamValue(v) {
		return String(v == null ? '' : v).replace(/[{}|]/g, '').trim();
	}

	/**
	 * Build a token string from a tag definition + entered param values.
	 */
	function buildToken(tag, values) {
		var parts = [];

		(tag.params || []).forEach(function (p) {
			var v = sanitizeParamValue(values[p.id]);
			if (v.length) {
				parts.push(p.id + '=' + v);
			}
		});

		var fallback = sanitizeParamValue(values.fallback);
		if (fallback.length) {
			parts.push('fallback=' + fallback);
		}

		return '{{' + tag.id + (parts.length ? '|' + parts.join('|') : '') + '}}';
	}

	function closePopover() {
		if ($openPopover) {
			var reposition = $openPopover.data('fwDcReposition');
			if (reposition) {
				window.removeEventListener('scroll', reposition, true);
				window.removeEventListener('resize', reposition);
			}
			$openPopover.remove();
			$openPopover = null;
			$(document).off('.fwDc');
		}
	}

	/**
	 * Anchor the (position:fixed) popover to the trigger's current viewport
	 * position, so it stays glued to the button while the modal/page scrolls.
	 */
	function positionPopover($popover, $trigger) {
		var el = $trigger.get(0);
		if (!el) {
			return;
		}

		var rect = el.getBoundingClientRect();
		var pw = $popover.outerWidth();
		var ph = $popover.outerHeight();
		var vw = window.innerWidth;
		var vh = window.innerHeight;

		var top = rect.bottom + 4;
		var left = rect.left;

		// Keep within the viewport horizontally.
		if (left + pw > vw - 12) {
			left = Math.max(12, vw - pw - 12);
		}

		// Flip above the trigger when there isn't room below.
		if (top + ph > vh - 12 && rect.top - ph - 4 > 12) {
			top = rect.top - ph - 4;
		}

		$popover.css({ top: Math.round(top) + 'px', left: Math.round(left) + 'px' });
	}

	/**
	 * Render the param form for a tag that needs input before insertion.
	 */
	function renderParamForm($body, $descriptor, type, tag) {
		$body.empty();

		var $form = $('<div class="fw-dc-param-form"></div>');
		$form.append('<div class="fw-dc-param-title">' + escapeHtml(tag.label) + '</div>');

		var $inputs = {};

		(tag.params || []).forEach(function (p) {
			var $row = $('<label class="fw-dc-param-row"></label>');
			$row.append('<span>' + escapeHtml(p.label || p.id) + '</span>');
			var $inp;
			if (p.type === 'select' && p.choices && p.choices.length) {
				// Dropdown param (e.g. a Page permalink picker). Choices arrive as an
				// ordered array of { value, label } so server-side ordering survives.
				$inp = $('<select class="fw-dc-param-input fw-dc-param-select"></select>');
				$inp.append($('<option></option>').attr('value', '').text(L10N.select || '— Select —'));
				p.choices.forEach(function (c) {
					$('<option></option>').attr('value', c.value).text(c.label).appendTo($inp);
				});
				if (p.default) {
					$inp.val(p.default);
				}
			} else {
				$inp = $('<input type="text" class="fw-dc-param-input" />').val(p.default || '');
			}
			$row.append($inp);
			if (p.help) {
				$row.append('<span class="fw-dc-param-help">' + escapeHtml(p.help) + '</span>');
			}
			$form.append($row);
			$inputs[p.id] = $inp;
		});

		// Optional fallback for every parameterized tag.
		var $fbRow = $('<label class="fw-dc-param-row"></label>');
		$fbRow.append('<span>' + escapeHtml(L10N.fallback || 'Fallback') + '</span>');
		var $fb = $('<input type="text" class="fw-dc-param-input" />');
		$fbRow.append($fb);
		$form.append($fbRow);
		$inputs.fallback = $fb;

		var $actions = $('<div class="fw-dc-param-actions"></div>');
		var $insert = $('<button type="button" class="button button-primary fw-dc-insert"></button>')
			.text(L10N.insert || 'Insert');
		var $back = $('<button type="button" class="button fw-dc-back"></button>')
			.text(L10N.back || 'Back');
		$actions.append($insert).append($back);
		$form.append($actions);

		$insert.on('click', function () {
			var values = {};
			$.each($inputs, function (id, $el) {
				values[id] = $el.val();
			});
			insertToken($descriptor, type, buildToken(tag, values));
			closePopover();
		});

		$back.on('click', function () {
			renderTagList($body, $descriptor, type);
		});

		$body.append($form);
		(tag.params && tag.params.length ? $inputs[tag.params[0].id] : $fb).focus();
	}

	/**
	 * Render the grouped, searchable list of tags.
	 */
	function renderTagList($body, $descriptor, type, filter) {
		$body.empty();
		filter = (filter || '').toLowerCase();

		var groups = DATA.tags || {};
		var any = false;

		Object.keys(groups).forEach(function (groupLabel) {
			var items = groups[groupLabel].filter(function (tag) {
				if (!filter) {
					return true;
				}
				return (tag.label + ' ' + tag.id).toLowerCase().indexOf(filter) !== -1;
			});

			if (!items.length) {
				return;
			}
			any = true;

			$body.append('<div class="fw-dc-group">' + escapeHtml(groupLabel) + '</div>');

			items.forEach(function (tag) {
				var $item = $('<a href="#" class="fw-dc-item"></a>')
					.text(tag.label)
					.attr('data-tag-id', tag.id);

				if (tag.params && tag.params.length) {
					$item.append('<span class="fw-dc-item-params dashicons dashicons-admin-generic"></span>');
				}

				$item.on('click', function (e) {
					e.preventDefault();
					if (tag.params && tag.params.length) {
						renderParamForm($body, $descriptor, type, tag);
					} else {
						insertToken($descriptor, type, buildToken(tag, {}));
						closePopover();
					}
				});

				$body.append($item);
			});
		});

		if (!any) {
			$body.append('<div class="fw-dc-empty">' + escapeHtml(L10N.no_results || 'No tags found') + '</div>');
		}
	}

	function openPicker($descriptor, type, $trigger) {
		closePopover();

		var $popover = $(
			'<div class="fw-dc-popover">' +
				'<div class="fw-dc-search"><input type="text" placeholder="' +
				escapeHtml(L10N.search || 'Search…') + '" /></div>' +
				'<div class="fw-dc-body"></div>' +
			'</div>'
		);

		$('body').append($popover);
		$openPopover = $popover;

		var $body = $popover.find('.fw-dc-body');
		renderTagList($body, $descriptor, type);

		// Anchor to the trigger, and keep it anchored as the modal/page scrolls or
		// resizes (capture phase catches scrolling inside the modal's content area).
		positionPopover($popover, $trigger);
		var reposition = function () {
			positionPopover($popover, $trigger);
		};
		window.addEventListener('scroll', reposition, true);
		window.addEventListener('resize', reposition);
		$popover.data('fwDcReposition', reposition);

		var $search = $popover.find('.fw-dc-search input');
		$search.on('input', function () {
			renderTagList($body, $descriptor, type, $(this).val());
		}).focus();

		// Close on outside click / Esc.
		setTimeout(function () {
			$(document).on('mousedown.fwDc', function (e) {
				if (!$(e.target).closest('.fw-dc-popover').length &&
					!$(e.target).closest('.fw-dynamic-content-trigger').length) {
					closePopover();
				}
			});
			$(document).on('keydown.fwDc', function (e) {
				if (e.keyCode === 27) {
					closePopover();
				}
			});
		}, 0);
	}

	function escapeHtml(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function initDescriptor() {
		var $descriptor = $(this);
		var $trigger = $descriptor.find('> .fw-dynamic-content-trigger, .fw-inner-option > .fw-dynamic-content-trigger, .fw-dynamic-content-trigger').first();

		if (!$trigger.length || $trigger.data('fwDcInit')) {
			return;
		}
		$trigger.data('fwDcInit', true);

		var type = $descriptor.attr('data-fw-option-type');

		// Rich Editor: relocate the icon into the editor's media-buttons row,
		// next to "Add Media", styled as a WP button (icon + label).
		if (type === 'wp-editor') {
			var $mediaButtons = $descriptor.find('.wp-media-buttons').first();
			if ($mediaButtons.length) {
				$trigger
					.removeClass('dashicons dashicons-database')
					.addClass('button fw-dc-editor-button')
					.html(
						'<span class="dashicons dashicons-database"></span>' +
						'<span class="fw-dc-label">' +
						escapeHtml(L10N.editor_button || 'Dynamic Content') +
						'</span>'
					);
				$mediaButtons.append($trigger);
			}
		}

		$trigger.on('click', function (e) {
			e.preventDefault();
			openPicker($descriptor, type, $trigger);
		});
	}

	fwEvents.on('fw:options:init', function (data) {
		if (!data || !data.$elements) {
			return;
		}
		data.$elements
			.find('.fw-backend-option-descriptor')
			.has('.fw-dynamic-content-trigger')
			.each(initDescriptor);
	});

	/**
	 * Classic (TinyMCE) editor button.
	 *
	 * The button is printed into the editor's media-buttons row by classic-editor.php
	 * (class .fw-dc-classic-trigger, data-editor=<editor id>). It reuses the same picker
	 * as the option fields by handing openPicker() a synthetic descriptor: the editor's
	 * wrap element, which contains the `textarea.wp-editor-area` insertToken() targets.
	 */
	function initClassicTrigger() {
		var $trigger = $(this);
		if ($trigger.data('fwDcInit')) {
			return;
		}
		$trigger.data('fwDcInit', true);

		var editorId = $trigger.attr('data-editor') || 'content';

		$trigger.on('click', function (e) {
			e.preventDefault();

			var $descriptor = $('#wp-' + editorId + '-wrap');
			if (!$descriptor.length) {
				$descriptor = $('#' + editorId).closest('.wp-editor-wrap');
			}
			if (!$descriptor.length) {
				return;
			}

			openPicker($descriptor, 'wp-editor', $trigger);
		});
	}

	$(function () {
		$('.fw-dc-classic-trigger').each(initClassicTrigger);
	});
})(jQuery, window.fwEvents);
