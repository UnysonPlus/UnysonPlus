;(function($) {

	window.fwOptionTypeIconV2Picker = fw.Modal.extend({
		defaults: _.extend({}, fw.Modal.prototype.defaults, {
			title: 'Icon V2',
			size: 'small',
			modalCustomClass: 'fw-icon-v3-picker-modal',
			emptyHtmlOnClose: false,
			disableResetButton: true,
		}),

		ContentView: fw.Modal.prototype.ContentView.extend({
			events: {
				// One search box for the merged Icons tab; onIconsSearch routes to
				// the font filter or the SVG AJAX search by the current mode.
				'input .fw-icon-v3-icons-library .fw-icon-v3-toolbar input':
					'onIconsSearch',
				'click .fw-icon-v3-library-icon': 'markIconAsSelected',
				'click .fw-icon-v3-library-icon a': 'markIconAsFavorite',
				'click button.fw-icon-v3-custom-upload-perform':
					'performImageUpload',
				'input .fw-icon-v3-emoji-input': 'onEmojiInput',
				'input .fw-icon-v3-svg-input': 'onSvgInput',
				'click .fw-icon-v3-svg-upload': 'onSvgUploadClick',
				'change .fw-icon-v3-svg-file': 'onSvgFile',
				'input .fw-icon-v3-lottie-url': 'onLottieChange',
				'change .fw-icon-v3-lottie-trigger': 'onLottieChange',
				'input .fw-icon-v3-lottie-speed': 'onLottieChange',
				'click .fw-icon-v3-lottie-upload': 'onLottieUploadClick',
				'change .fw-icon-v3-lottie-file': 'onLottieFile',
				'click .fw-icon-v3-lucide-icon': 'onLucideSelect',
				submit: 'onSubmit',
			},

			initialize: function() {
				fw.Modal.prototype.ContentView.prototype.initialize.call(this)

				// keep track of current searches for better performance
				this.previousSearch = ''

				this.throttledApplyFilters = _.throttle(
					_.bind(this.model.applyFilters, this.model),
					200
				)

				// Lucide search: name → last-fetched item map, plus a debounced
				// AJAX search so typing doesn't fire a request per keystroke.
				this.lucideResults = {}
				this.debouncedLucideSearch = _.debounce(
					_.bind(this.doLucideSearch, this),
					250
				)

				// Merged search: query EVERY SVG pack at once (pack='all').
				this.debouncedSvgAll = _.debounce(
					_.bind(function(q) {
						this.doLucideSearch(q, 'all')
					}, this),
					250
				)
			},

			onSubmit: function(e) {
				this.model.resolveResult()

				var content = this

				e.preventDefault()

				setTimeout(function() {
					content.model.frame.modal.$el
						.find('.media-modal-close')
						.trigger('click')
				}, 0)
			},

			performImageUpload: function() {
				var vm = this

				var uploadFrame = wp.media({
					library: {
						type: 'image',
					},

					states: new wp.media.controller.Library({
						library: wp.media.query({type: 'image'}),
						multiple: true,
						filterable: 'uploaded',
						content: 'upload',
						title: 'Select Image',
						priority: 20,
					}),
				})

				uploadFrame.on('ready', function() {
					uploadFrame.modal.$el.addClass('fw-option-type-upload')
				})

				uploadFrame.off('select')

				uploadFrame.on('select', function() {
					var attachments = uploadFrame
						.state()
						.get('selection')
						.toArray()

					attachments.map(function(attachment) {
						if (
							!_.contains(
								vm.model.currentFavorites,
								attachment.id.toString()
							)
						) {
							vm.model.markAsFavorite(attachment.id.toString())
						}
					})

					vm.renderFavoritesAndRecentUploads()

					uploadFrame.detach()
				})

				uploadFrame.open()
			},

			markIconAsSelected: function markIconAsSelected(e) {
				e.preventDefault()

				var $el = $(e.currentTarget)

				// Lucide tiles share the .fw-icon-v3-library-icon grid class but
				// carry data-name (not data-fw-icon-v3) and are handled by
				// onLucideSelect — skip them here so this doesn't throw on
				// undefined.trim() and swallow the Lucide click.
				if (!$el.attr('data-fw-icon-v3')) {
					return
				}

				var type =
					$el.closest(
						'[data-fw-option-id="upload-custom-icon-recents"]'
					).length > 0
						? 'custom-upload'
						: 'icon-font'

				var result = $el.attr('data-fw-icon-v3').trim()

				// Set the value TYPE from what was clicked. The merged Custom tab's
				// tabsactivate deliberately doesn't set a type (the tab holds both an
				// SVG section and the image uploader), so without this a picked image
				// keeps the previous type and never becomes a 'custom-upload' value.
				this.model.result.type = type

				this.model.result[
					type === 'custom-upload' ? 'attachment-id' : 'icon-class'
				] = result

				if (type === 'custom-upload') {
					this.model.result.url = wp.media
						.attachment(result)
						.get('url')
				}

				this.refreshSelectedIcon()
			},

			refreshSelectedIcon: function refreshSelectedIcon() {
				this.model.frame.$el
					.find('.fw-icon-v3-library-icon.selected')
					.removeClass('selected')

				if (this.model.result.type === 'icon-font') {
					var currentValue = this.model.result['icon-class']
				} else if (this.model.result.type === 'custom-upload') {
					var currentValue = this.model.result['attachment-id']
				}

				if (currentValue) {
					this.model.frame.$el
						.find('[data-fw-icon-v3$="' + currentValue + '"]')
						.addClass('selected')
				}
			},

			markIconAsFavorite: function markIconAsFavorite(e) {
				e.preventDefault()
				e.stopPropagation()

				var icon = $(e.currentTarget)
					.closest('.fw-icon-v3-library-icon')
					.attr('data-fw-icon-v3')

				this.model.markAsFavorite(icon)

				this.renderFavoritesAndRecentUploads()
				this.refreshFavorites()
			},

			refreshFavorites: function() {
				$('.fw-icon-v3-favorite').removeClass('fw-icon-v3-favorite')

				_.map(this.model.currentFavorites, function(favorite) {
					if (
						_.compose(
							_.negate(_.isNaN),
							_.partial(parseInt, _, 10)
						)(favorite)
					) {
						return
					}

					$('[data-fw-icon-v3="' + favorite + '"]').addClass(
						'fw-icon-v3-favorite'
					)
				})
			},

			renderFavoritesAndRecentUploads: function() {
				this.model.frame.$el
					.find('.fw-favorite-icons-wrapper')
					.replaceWith(this.model.getFavoritesHtml())

				// The recents list is injected DIRECTLY into the upload-section div
				// (templates.php puts {{{data.recently_used_custom_uploads_html}}}
				// straight inside it) — there is NO .fw-option-html child here, so
				// target the section element itself. Using a .fw-option-html child
				// selector matched nothing, which is why picking/uploading a raster
				// image appeared to do nothing.
				var $recents = this.model.frame.$el.find(
					'[data-fw-option-id="upload-custom-icon-recents"]'
				)

				$recents.html(this.model.getRecentIconsHtml())
			},

			onSearch: function(event) {
				var $el = $(event.currentTarget)

				if (
					this.previousSearch.trim().length === 0 &&
					$el.val().trim().length === 0
				) {
					return
				}

				if ($el.val().trim().length === 0) {
					this.throttledApplyFilters()
				}

				if ($el.val().trim().length > 2) {
					this.throttledApplyFilters()
				}

				this.previousSearch = $el.val()
			},

			// Emoji tab: a typed/pasted emoji becomes the whole value.
			onEmojiInput: function(event) {
				var char = $(event.currentTarget).val()

				this.model.result = char
					? { type: 'emoji', char: char }
					: { type: 'none' }

				$(event.currentTarget)
					.closest('.fw-icon-v3-emoji-tab')
					.find('.fw-icon-v3-emoji-live')
					.text(char || '')
			},

			// Custom SVG tab: pasted inline markup. Real sanitisation happens
			// server-side on save AND render; this preview is just what's typed.
			onSvgInput: function(event) {
				var markup = $(event.currentTarget).val()
				var isSvg = markup.toLowerCase().indexOf('<svg') !== -1

				// 'svg-id': '' clears any leftover Lucide id from a prior pick so
				// it can't win over this markup when the value is merged/saved.
				this.model.result = isSvg
					? { type: 'svg', 'svg-source': 'inline', markup: markup, 'svg-id': '' }
					: { type: 'none' }

				$(event.currentTarget)
					.closest('.fw-icon-v3-svg-tab')
					.find('.fw-icon-v3-svg-live')
					.html(isSvg ? markup : '')
			},

			// "Upload .svg file" → open the hidden file input.
			onSvgUploadClick: function(event) {
				event.preventDefault()
				// The button + hidden file input live in .fw-icon-v3-custom-head
				// (the merged Custom tab), NOT a .fw-icon-v3-toolbar — so scope the
				// lookup to their shared parent, else the file dialog never opens.
				$(event.currentTarget)
					.closest('.fw-icon-v3-custom-head')
					.find('.fw-icon-v3-svg-file')
					.trigger('click')
			},

			// Read the chosen .svg client-side (no media upload → no SVG-mime
			// restriction) into the textarea + value, same shape as a paste.
			onSvgFile: function(event) {
				var input = event.currentTarget
				var file = input.files && input.files[0]
				if (!file) {
					return
				}

				var view = this
				var reader = new FileReader()

				reader.onload = function(e) {
					var markup = String((e.target && e.target.result) || '')
					var isSvg = markup.toLowerCase().indexOf('<svg') !== -1

					view.model.frame.$el
						.find('.fw-icon-v3-svg-input')
						.val(markup)
					view.model.frame.$el
						.find('.fw-icon-v3-svg-live')
						.html(isSvg ? markup : '')

					// 'svg-id': '' clears any leftover Lucide id from a prior pick.
					view.model.result = isSvg
						? {
								type: 'svg',
								'svg-source': 'upload',
								markup: markup,
								'svg-id': '',
						  }
						: { type: 'none' }
				}

				reader.readAsText(file)
				input.value = '' // allow re-selecting the same file
			},

			// --- Animated (Lottie) tab -----------------------------------------
			// Any of the three controls (URL / trigger / speed) re-reads the tab and
			// updates the value + the live preview.
			onLottieChange: function(event) {
				this.syncLottie($(event.currentTarget).closest('.fw-icon-v3-lottie-tab'))
			},

			// Read the tab's inputs into the picked value + (re)play the preview.
			syncLottie: function($tab) {
				var src     = $.trim($tab.find('.fw-icon-v3-lottie-url').val() || '')
				var trigger = $tab.find('.fw-icon-v3-lottie-trigger').val() || 'loop'
				var speed   = parseFloat($tab.find('.fw-icon-v3-lottie-speed').val()) || 1

				this.model.result = src
					? { type: 'lottie', src: src, trigger: trigger, speed: speed }
					: { type: 'none' }

				var $live = $tab.find('.fw-icon-v3-lottie-live')
				if ($live[0] && $live[0].__upwLottie) { try { $live[0].__upwLottie.destroy() } catch (e) {} $live[0].__upwLottie = null }
				$live.empty()
				if (src && window.lottie) {
					try {
						var anim = window.lottie.loadAnimation({
							container: $live[0], renderer: 'svg', loop: true, autoplay: true, path: src
						})
						anim.setSpeed(speed)
						$live[0].__upwLottie = anim
					} catch (e) {}
				}
			},

			onLottieUploadClick: function(event) {
				event.preventDefault()
				$(event.currentTarget).closest('.fw-icon-v3-lottie-tab').find('.fw-icon-v3-lottie-file').trigger('click')
			},

			// Upload the chosen .json to the server (stored under uploads); on success
			// drop the returned URL into the URL field and sync.
			onLottieFile: function(event) {
				var input = event.currentTarget
				var file  = input.files && input.files[0]
				if (!file) { return }

				var view  = this
				var $tab  = $(input).closest('.fw-icon-v3-lottie-tab')
				var $msg  = $tab.find('.fw-icon-v3-lottie-msg').removeClass('fw-icon-v3-error').text(
					(window.fwIconV3 && fwIconV3.i18n && fwIconV3.i18n.uploading) || 'Uploading…')

				var fd = new FormData()
				fd.append('action', 'fw_icon_lottie_upload')
				fd.append('nonce', (window.fwIconV3 && fwIconV3.lottieNonce) || '')
				fd.append('lottie_file', file)

				$.ajax({ url: (window.ajaxurl || (window.fwIconV3 && fwIconV3.ajaxUrl)), method: 'POST', data: fd, processData: false, contentType: false })
					.done(function(res) {
						if (res && res.success && res.data && res.data.url) {
							$tab.find('.fw-icon-v3-lottie-url').val(res.data.url)
							$msg.text('')
							view.syncLottie($tab)
						} else {
							$msg.addClass('fw-icon-v3-error').text((res && res.data && res.data.message) || 'Upload failed.')
						}
					})
					.fail(function() { $msg.addClass('fw-icon-v3-error').text('Upload failed.') })
				input.value = ''
			},

			// Lucide tab: search-as-you-type (debounced) against the bundled set.
			onLucideSearch: function(event) {
				this.debouncedLucideSearch($(event.currentTarget).val())
			},

			doLucideSearch: function(query, packOverride) {
				var view = this

				// Which pack to search: an explicit override ('all' for the merged
				// search), else the pack chosen in the unified dropdown.
				var pack =
					packOverride ||
					view.model.frame.$el.find('.fw-icon-v3-pack-select').val() ||
					'lucide'

				$.post(ajaxurl, {
					action: 'fw_icon_v3_svg_search',
					pack: pack,
					q: query || '',
				}).done(function(resp) {
					if (resp && resp.success) {
						view.renderLucideResults(resp.data)
					}
				})
			},

			// Unified Icons-tab search box. Empty query → browse the selected pack
			// (one pane). A query → MERGED search: font packs (client-side) AND
			// every SVG pack (AJAX) at once, both panes shown.
			onIconsSearch: function(event) {
				var q = $(event.currentTarget).val()
				var $lib = this.model.frame.$el.find('.fw-icon-v3-icons-library')

				if (q.trim().length === 0) {
					this.renderIconsTab()
					return
				}

				// Leaving browse mode → stop the infinite-scroll pagination.
				if (this.svgBrowse) { this.svgBrowse.active = false }

				$lib.find('.fw-icon-v3-font-mode').show()
				$lib.find('.fw-icon-v3-svg-mode').show()
				this.throttledApplyFilters()
				this.debouncedSvgAll(q)
			},

			// Render the Icons tab for whichever pack is selected: show the right
			// pane and drive the matching render (client-side font filter vs. SVG
			// AJAX search). Called on open, on pack change, and on tab activate.
			renderIconsTab: function() {
				var $lib = this.model.frame.$el.find('.fw-icon-v3-icons-library')
				if (!$lib.length) { return }

				var pack = $lib.find('.fw-icon-v3-pack-select').val() || ''
				var type = (this.packTypes && this.packTypes[pack]) || 'font'
				this.iconsMode = type

				var $search = $lib.find(
					'.fw-icon-v3-toolbar input.fw-option-type-text'
				)

				if (type === 'svg') {
					$lib.find('.fw-icon-v3-font-mode').hide()
					$lib.find('.fw-icon-v3-svg-mode').show()
					// Browse the whole pack with lazy-load / infinite scroll.
					this.startSvgBrowse(pack)
				} else {
					$lib.find('.fw-icon-v3-svg-mode').hide()
					$lib.find('.fw-icon-v3-font-mode').show()
					if (this.svgBrowse) { this.svgBrowse.active = false }
					this.model.applyFilters()
				}
			},

			// Start browsing a single SVG pack: load the first batch, then the
			// scroll handler (bound in prepareForPick) pulls further batches as the
			// user scrolls — so "select Lucide and keep scrolling" loads them all.
			SVG_BATCH: 120,
			startSvgBrowse: function(pack) {
				var view = this
				view.svgBrowse = { pack: pack, offset: 0, loading: true, done: false, active: true }

				$.post(ajaxurl, {
					action: 'fw_icon_v3_svg_search',
					pack: pack,
					q: '',
					offset: 0,
				}).done(function(resp) {
					if (!view.svgBrowse || view.svgBrowse.pack !== pack) { return }
					var data = (resp && resp.success) ? resp.data : {}
					var items = (data && data.items) || []
					view.renderLucideResults(items)
					view.svgBrowse.offset = items.length
					view.svgBrowse.loading = false
					view.svgBrowse.done = ! ( data && data.has_more )
				})
			},

			// Fetch + APPEND the next batch when scrolling near the bottom.
			loadMoreSvg: function() {
				var view = this
				var s = view.svgBrowse
				if ( ! s || ! s.active || s.loading || s.done ) { return }
				s.loading = true

				$.post(ajaxurl, {
					action: 'fw_icon_v3_svg_search',
					pack: s.pack,
					q: '',
					offset: s.offset,
				}).done(function(resp) {
					if ( ! view.svgBrowse || view.svgBrowse.pack !== s.pack ) { return }
					var data = (resp && resp.success) ? resp.data : {}
					var items = (data && data.items) || []
					view.appendSvgResults(items)
					s.offset += items.length
					s.loading = false
					if ( ! ( data && data.has_more ) || ! items.length ) { s.done = true }
				})
			},

			// Append more SVG tiles to the existing browse grid (before the ghost
			// fillers), keeping the left-aligned last row intact.
			appendSvgResults: function(items) {
				if ( ! items || ! items.length ) { return }
				var view = this
				var currentId = view.model.result && view.model.result['svg-id']
				var $ul = view.model.frame.$el
					.find('.fw-icon-v3-lucide-results .fw-icon-v3-library-pack')
					.first()
				if ( ! $ul.length ) { return }

				var html = ''
				_.each(items, function(item) {
					view.lucideResults[item.id] = item
					html +=
						'<li class="fw-icon-v3-library-icon fw-icon-v3-lucide-icon ' +
						(currentId === item.id ? 'selected' : '') +
						'" data-svg-id="' + _.escape(item.id) +
						'" data-name="' + _.escape(item.name) +
						'" title="' + _.escape(item.name) +
						'"><div class="fw-icon-inner">' + item.markup + '</div></li>'
				})
				$ul.find('.fw-ghost-item').remove()
				$ul.append(html)
				$ul.append(new Array(12).join('<li class="fw-ghost-item"></li>'))
			},

			// Which pack a stored value belongs to, so the dropdown pre-selects it
			// when re-opening the picker. Null → keep the server default.
			packForState: function(state) {
				if (!state) { return null }

				if (
					state.type === 'svg' &&
					state['svg-source'] === 'library' &&
					state['svg-id']
				) {
					var sp = String(state['svg-id']).split('/')[0]
					if (this.packTypes && this.packTypes[sp]) { return sp }
				}

				if (state.type === 'icon-font' && state['icon-class']) {
					var first = String(state['icon-class']).trim().split(/\s+/)[0]
					var found = null
					_.each(this.model.getIconsData(), function(pk, id) {
						if (found) { return }
						var prefixes = pk.match_prefixes || [pk.css_class_prefix]
						if (_.contains(prefixes, first)) { found = id }
					})
					if (found) { return found }
				}

				return null
			},

			renderLucideResults: function(items) {
				var view = this
				view.lucideResults = {}

				var $wrap = view.model.frame.$el.find(
						'.fw-icon-v3-lucide-results'
					)

				if (!items || !items.length) {
					$wrap.html(
						'<div class="fw-icon-v3-note"><h3>' +
							(window.fw_icon_v3_data &&
							fw_icon_v3_data.no_results
								? fw_icon_v3_data.no_results
								: 'No icons found') +
							'</h3></div>'
					)
					return
				}

				var currentId = view.model.result && view.model.result['svg-id']

				// Group by pack (items carry .pack) so a merged multi-pack search
				// shows a heading per library, matching the font results. Key the
				// lookup by full id ('<pack>/<name>') so same-named icons in
				// different packs don't collide.
				var order = []
				var byPack = {}
				_.each(items, function(item) {
					var pk = item.pack || String(item.id).split('/')[0]
					if (!byPack[pk]) {
						byPack[pk] = []
						order.push(pk)
					}
					byPack[pk].push(item)
					view.lucideResults[item.id] = item
				})
				var multi = order.length > 1

				var html = ''
				_.each(order, function(pk) {
					if (multi) {
						var title =
							(view.packTitles && view.packTitles[pk]) || pk
						html += '<h2><span>' + _.escape(title) + '</span></h2>'
					}
					html += '<ul class="fw-icon-v3-library-pack">'
					_.each(byPack[pk], function(item) {
						html +=
							'<li class="fw-icon-v3-library-icon fw-icon-v3-lucide-icon ' +
							(currentId === item.id ? 'selected' : '') +
							'" data-svg-id="' +
							_.escape(item.id) +
							'" data-name="' +
							_.escape(item.name) +
							'" title="' +
							_.escape(item.name) +
							'"><div class="fw-icon-inner">' +
							item.markup +
							'</div></li>'
					})
					// Ghost fillers keep the last row LEFT-aligned (matching the
					// font grid) instead of the growable tiles centering/spreading.
					html += new Array(12).join('<li class="fw-ghost-item"></li>')
					html += '</ul>'
				})
				$wrap.html(html)
			},

			onLucideSelect: function(event) {
				event.preventDefault()

				var $el = $(event.currentTarget)
				var item = this.lucideResults[$el.attr('data-svg-id')]

				if (!item) {
					return
				}

				this.model.result = {
					type: 'svg',
					'svg-source': 'library',
					'svg-id': item.id,
					markup: item.markup,
				}

				$el.closest('.fw-icon-v3-library-pack')
					.find('.selected')
					.removeClass('selected')
				$el.addClass('selected')
			},
		}),

		initialize: function(attributes, settings) {
			fw.Modal.prototype.initialize.call(this, attributes, {
				disableResetButton: true,
			})

			var modal = this

			this.currentFavorites = null

			this.result = {}

			jQuery.when(this.loadIconsData()).then(
				_.bind(function() {
					this.set('html', this.getTabsHtml())
				}, this)
			)

			jQuery.when(this.loadLatestFavorites()).then(
				_.bind(function() {
					this.content.renderFavoritesAndRecentUploads()
					this.content.refreshFavorites()
				}, this)
			)

			this.frame.on('close', _.bind(this.rejectResultAndResetIt, this))
		},

		resolveResult: function() {
			if (this.promise) {
				this.promise.resolve(this.result)
			}

			this.promise = null
		},

		rejectResultAndResetIt: function() {
			if (this.promise) {
				this.promise.reject(this.result)
			}

			this.promise = null
		},

		initializeFrame: function(settings) {
			fw.OptionsModal.prototype.initializeFrame.call(this, settings)
		},

		open: function(values) {
			this.promise = jQuery.Deferred()

			this.get('controls_ready') &&
				this.set('controls_ready', !!this.frame.state())

			values = values || {
				type: 'icon-font',
				'icon-class': '',
			}

			if (values.type === 'none') {
				values.type = 'icon-font'
			}

			this.set('current_state', values)
			this.result = this.get('current_state')

			if (this.frame.state()) {
				this.prepareForPick()
			}

			this.frame.open()

			/**
			 * On first open, modal is prepared here.
			 */
			if (!this.get('controls_ready')) {
				this.prepareForPick()
			}

			return this.promise
		},

		close: function() {
			fw.Modal.prototype.close.call(this)
		},

		prepareForPick: function() {
			var modal = this

			// Unified Icons-tab dropdown (Icon Fonts + SVG Icons optgroups). A
			// pack-id → type map lets the JS switch between the client-side font
			// filter and the SVG AJAX search when the selection changes.
			// Selectize the unified dropdown ONCE (openIcon may call
			// prepareForPick twice). selectize does NOT read DOM <optgroup>s on
			// its own, so pass the options + optgroups explicitly, or the dropdown
			// comes up empty.
			var $packSelect = modal.frame.$el.find('.fw-icon-v3-pack-select')
			var packSelectEl = $packSelect[0]

			if (packSelectEl && !packSelectEl.selectize) {
				var packTypes = {}
				var packTitles = {}
				var szOptions = []
				var szGroups = []
				var defaultPack = packSelectEl.value || ''

				$packSelect.find('optgroup').each(function() {
					var groupLabel = this.getAttribute('label') || ''
					szGroups.push({ value: groupLabel, label: groupLabel })
					$(this)
						.find('option')
						.each(function() {
							var val = this.value
							var txt = (this.textContent || val).trim()
							packTypes[val] = this.getAttribute('data-type') || 'font'
							packTitles[val] = txt
							szOptions.push({ value: val, text: txt, group: groupLabel })
							if (this.selected) { defaultPack = val }
						})
				})

				modal.content.packTypes = packTypes
				modal.content.packTitles = packTitles

				$packSelect.selectize({
					plugins: ['hidden_textfield'],
					options: szOptions,
					optgroups: szGroups,
					optgroupField: 'group',
					optgroupValueField: 'value',
					optgroupLabelField: 'label',
					labelField: 'text',
					valueField: 'value',
					searchField: ['text', 'value'],
					items: defaultPack ? [defaultPack] : [],
					onChange: _.bind(modal.content.renderIconsTab, modal.content),
				})
				packSelectEl = $packSelect[0]
			}

			// Pre-select the pack for the stored value (its font pack or SVG pack)
			// so re-opening lands on the right library; else the server default
			// (Font Awesome). Render silently now so the grid is ready on open.
			var initPack = modal.content.packForState(modal.get('current_state'))
			if (initPack && packSelectEl && packSelectEl.selectize) {
				packSelectEl.selectize.setValue(initPack, true)
			}
			modal.content.renderIconsTab()

			// Infinite scroll for SVG browse: pull the next batch as the shared
			// results container nears the bottom. Throttled; loadMoreSvg() no-ops
			// unless a single SVG pack is being browsed.
			modal.frame.$el
				.find('.fw-icon-v3-results')
				.off('scroll.fwiconv3svg')
				.on('scroll.fwiconv3svg', _.throttle(function() {
					var el = this
					if (el.scrollTop + el.clientHeight >= el.scrollHeight - 240) {
						modal.content.loadMoreSvg()
					}
				}, 150))

			modal.frame.$el
				.find('.fw-options-tabs-wrapper')
				.off('tabsactivate.fwiconv3')
				.on('tabsactivate.fwiconv3', function(event, ui) {
					/**
					 * Set a sensible default type on the modal when a tab is shown
					 * (the concrete value is set when the user picks something).
					 * Detect the tab by a MARKER in its panel, never by index.
					 */
					var $panel = ui.newPanel

					// Merged Icons tab: render whichever mode its dropdown is on,
					// and default the type to that mode.
					if ($panel.find('.fw-icon-v3-icons-library').length) {
						modal.content.renderIconsTab()
						modal.result.type =
							modal.content.iconsMode === 'svg' ? 'svg' : 'icon-font'
						return
					}

					// Merged Custom tab holds BOTH an SVG section and an image
					// uploader, so the type is decided by what the user does (paste
					// SVG vs pick an image) — don't override the current/stored type
					// on activate (that would clobber a stored upload value).
					if ($panel.find('.fw-icon-v3-custom-tab').length) {
						return
					}

					var kind = $panel.find('.fw-icon-v3-emoji-tab').length
						? 'emoji'
						: null

					if (kind) {
						modal.result.type = kind
					}
				})

			this.content.renderFavoritesAndRecentUploads()
			this.content.refreshFavorites()

			var $tabs = modal.frame.$el.find('.ui-tabs')
			var state = modal.get('current_state')

			// Open the modal on the tab matching the current value's type, found
			// by a MARKER in each tab's panel — never by a fixed index — so the
			// tabs can be reordered freely without breaking this.
			var $panels = $tabs.find('.ui-tabs-panel')
			if (!$panels.length) {
				$panels = $tabs.find('[role="tabpanel"]')
			}

			var typeSelectors = {
				'custom-upload': '[data-fw-option-id="upload-custom-icon-recents"]',
				'emoji': '.fw-icon-v3-emoji-tab',
				'svg': '.fw-icon-v3-svg-tab',
				'lottie': '.fw-icon-v3-lottie-tab',
				'icon-font': '.fw-icon-v3-icons-library',
			}

			var wantSelector =
				typeSelectors[state.type] || typeSelectors['icon-font']
			// An SVG value has two possible tabs: a library pick opens Lucide,
			// pasted markup opens Custom SVG.
			if (state.type === 'svg') {
				wantSelector =
					state['svg-source'] === 'library'
						? '.fw-icon-v3-lucide-results'
						: '.fw-icon-v3-svg-tab'
			}
			var wantIndex = -1
			$panels.each(function(i) {
				if ($(this).find(wantSelector).length) {
					wantIndex = i
					return false
				}
			})
			if (wantIndex >= 0 && $tabs.tabs('option', 'active') !== wantIndex) {
				$tabs.tabs({active: wantIndex})
			}

			// Pre-fill the Emoji / Custom SVG inputs when editing such a value.
			if (state.type === 'emoji') {
				modal.frame.$el.find('.fw-icon-v3-emoji-input').val(state.char || '')
				modal.frame.$el.find('.fw-icon-v3-emoji-live').text(state.char || '')
			}
			if (state.type === 'svg') {
				modal.frame.$el.find('.fw-icon-v3-svg-input').val(state.markup || '')
				modal.frame.$el.find('.fw-icon-v3-svg-live').html(state.markup || '')
			}
			if (state.type === 'lottie') {
				var $ltab = modal.frame.$el.find('.fw-icon-v3-lottie-tab')
				$ltab.find('.fw-icon-v3-lottie-url').val(state.src || '')
				$ltab.find('.fw-icon-v3-lottie-trigger').val(state.trigger || 'loop')
				$ltab.find('.fw-icon-v3-lottie-speed').val(state.speed || 1)
				if (modal.content && modal.content.syncLottie) { modal.content.syncLottie($ltab) }
			}

			// The stored icon-font value's pack is already pre-selected above via
			// packForState() (which honours FA6's fas/far/fab prefixes), and its
			// tile is highlighted by refreshSelectedIcon() after the grid renders.
		},

		applyFilters: function() {
			var packSelect = this.frame.$el.find(
				'.fw-icon-v3-icons-library .fw-icon-v3-toolbar select'
			)[0]

			var pack = packSelect
				? packSelect.value
				: _.keys(this.getIconsData())[0]

			var search = this.frame.$el
				.find(
					'.fw-icon-v3-icons-library .fw-icon-v3-toolbar input.fw-option-type-text'
				)
				.val()
				.trim()

			var packs = this.getFilteredPacks({
				pack: pack,
				search: search,
			})

			this.frame.$el
				.find(
					'[data-fw-option-id="icon-font"] .fw-icon-v3-library-pack-wrapper'
				)
				.html(
					wp.template('fw-icon-v3-packs')({
						packs: packs,
						current_state: this.result,
						should_have_headings: search.trim().length > 0,
						favorites: this.currentFavorites,
					})
				)

			this.content.refreshSelectedIcon()
		},

		getFilteredPacks: function(filters) {
			var self = this

			filters = _.extend(
				{},
				{
					search: '',
					pack: '',
				},
				filters
			)

			var packs = []

			/*
			if (filters.pack.trim() === '' || filters.pack === 'all') {
				packs = [ _.first(_.values(this.getIconsData())) ];
			} else {
				packs = [this.getIconsData()[filters.pack]];
			}
			*/

			if (filters.search.trim() === '') {
				packs = [this.getIconsData()[filters.pack]]
			} else {
				packs = _.values(this.getIconsData())
			}

			packs = _.map(packs, function(pack) {
				var newPack = _.extend({}, pack)

				newPack.icons = _.filter(pack.icons, function(icon) {
					return self.fuzzyConsecutive(filters.search, icon)
				})

				return newPack
			})

			return _.reject(packs, function(pack) {
				return _.isEmpty(pack.icons)
			})
		},

		loadIconsData: function() {
			if (this.iconsDataPromise) {
				return this.iconsDataPromise
			}

			this.iconsDataPromise = jQuery.post(ajaxurl, {
				action: 'fw_icon_v3_get_icons',
			})

			this.iconsDataPromise.then(_.bind(this.preloadFonts, this))

			return this.iconsDataPromise
		},

		getIconsData: function() {
			this.loadIconsData()

			if (this.iconsDataPromise.state() === 'resolved') {
				if (this.iconsDataPromise.responseJSON.success) {
					return this.iconsDataPromise.responseJSON.data
				}
			}

			return null
		},

		loadLatestFavorites: function() {
			var modal = this

			if (modal.favoritesPromise) {
				return modal.favoritesPromise
			}

			modal.favoritesPromise = $.Deferred()

			var ajaxPromise = $.post(ajaxurl, {
				action: 'fw_icon_v3_get_favorites',
			})

			ajaxPromise.then(function() {
				if (ajaxPromise.state() === 'resolved') {
					modal.currentFavorites = _.uniq(ajaxPromise.responseJSON)
				}

				var recent_uploads = _.filter(
					ajaxPromise.responseJSON,
					_.compose(_.negate(_.isNaN), _.partial(parseInt, _, 10))
				)

				if (recent_uploads.length === 0) {
					modal.favoritesPromise.resolve()
					return
				}

				wp.media
					.query({post__in: recent_uploads, perPage: 200})
					.more()
					.then(function() {
						var oldLength = modal.currentFavorites.length

						recent_uploads.map(function(id) {
							if (!wp.media.attachment(id).get('url')) {
								modal.currentFavorites = _.without(
									modal.currentFavorites,
									id
								)
							}
						})

						if (oldLength !== modal.currentFavorites.length) {
							modal.syncFavoritesToServer()
						}

						modal.favoritesPromise.resolve()
					})
			})

			return modal.favoritesPromise
		},

		syncFavoritesToServer: function() {
			jQuery.post(ajaxurl, {
				action: 'fw_icon_v3_update_favorites',
				favorites: JSON.stringify(_.uniq(this.currentFavorites)),
			})
		},

		markAsFavorite: function(icon) {
			icon = icon.trim()

			var modal = this

			var isFavorite = _.contains(modal.currentFavorites, icon)

			if (isFavorite) {
				modal.currentFavorites = _.uniq(
					_.reject(modal.currentFavorites, function(favorite) {
						return favorite == icon
					})
				)
			} else {
				modal.currentFavorites.push(icon)
			}

			this.syncFavoritesToServer()
		},

		preloadFonts: function() {
			_.map(this.getIconsData(), preloadFont)

			function preloadFont(pack) {
				var $el = jQuery(
					'<i class="' +
						pack.css_class_prefix +
						' ' +
						pack.icons[0] +
						'" style="opacity: 0;">'
				)

				jQuery('body').append($el)

				setTimeout(function() {
					$el.remove()
				}, 200)
			}
		},

		getTabsHtml: function() {
			return wp.template('fw-icon-v3-tabs')({
				icons_library_html: this.getLibraryHtml(),
				favorites_list_html: this.getFavoritesHtml(),
				recently_used_custom_uploads_html: this.getRecentIconsHtml(),
				current_state: this.result,
				favorites: this.currentFavorites,
			})
		},

		getLibraryHtml: function() {
			var packs = _.values(this.getIconsData())
			var pack_to_select = [_.first(packs)]

			return wp.template('fw-icon-v3-library')({
				packs: _.values(this.getIconsData()),
				pack_to_select: pack_to_select,
				current_state: this.result,
				favorites: this.currentFavorites,
			})
		},

		getFavoritesHtml: function() {
			return wp.template('fw-icon-v3-favorites')({
				favorites: this.currentFavorites || [],
				current_state: this.result,
			})
		},

		getRecentIconsHtml: function() {
			return wp.template('fw-icon-v3-recent-custom-icon-uploads')({
				favorites: this.currentFavorites || [],
				current_state: this.result,
			})
		},

		fuzzyConsecutive: function fuzzyConsecutive(query, search) {
			if (query.trim() === '') return true

			return (
				search
					.toLowerCase()
					.trim()
					.indexOf(query.toLowerCase()) > -1
			)
		},
	})

	$(function() {
		fwOptionTypeIconV2Instance = new fwOptionTypeIconV2Picker()
	})

	Selectize.define('hidden_textfield', function(options) {
		var self = this

		this.showInput = function() {
			this.$control.css({cursor: 'pointer'})

			this.$control_input.css({
				opacity: 0,
				position: 'relative',
				left: self.rtl ? 10000 : -10000,
			})

			this.isInputHidden = false
		}

		this.setup_original = this.setup

		this.setup = function() {
			self.setup_original()
			this.$control_input.prop('disabled', 'disabled')
		}
	})
})(jQuery)
