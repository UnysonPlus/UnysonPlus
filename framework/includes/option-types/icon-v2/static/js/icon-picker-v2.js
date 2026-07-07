;(function($) {
	window.fwOptionTypeIconV2Picker = fw.Modal.extend({
		defaults: _.extend({}, fw.Modal.prototype.defaults, {
			title: 'Icon V2',
			size: 'small',
			modalCustomClass: 'fw-icon-v2-picker-modal',
			emptyHtmlOnClose: false,
			disableResetButton: true,
		}),

		ContentView: fw.Modal.prototype.ContentView.extend({
			events: {
				'input .fw-icon-v2-icons-library .fw-icon-v2-toolbar input':
					'onSearch',
				'click .fw-icon-v2-library-icon': 'markIconAsSelected',
				'click .fw-icon-v2-library-icon a': 'markIconAsFavorite',
				'click button.fw-icon-v2-custom-upload-perform':
					'performImageUpload',
				'input .fw-icon-v2-emoji-input': 'onEmojiInput',
				'input .fw-icon-v2-svg-input': 'onSvgInput',
				'click .fw-icon-v2-svg-upload': 'onSvgUploadClick',
				'change .fw-icon-v2-svg-file': 'onSvgFile',
				'input .fw-icon-v2-lucide-search': 'onLucideSearch',
				'click .fw-icon-v2-lucide-icon': 'onLucideSelect',
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

				// Lucide tiles share the .fw-icon-v2-library-icon grid class but
				// carry data-name (not data-fw-icon-v2) and are handled by
				// onLucideSelect — skip them here so this doesn't throw on
				// undefined.trim() and swallow the Lucide click.
				if (!$el.attr('data-fw-icon-v2')) {
					return
				}

				var type =
					$el.closest(
						'[data-fw-option-id="upload-custom-icon-recents"]'
					).length > 0
						? 'custom-upload'
						: 'icon-font'

				var result = $el.attr('data-fw-icon-v2').trim()

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
					.find('.fw-icon-v2-library-icon.selected')
					.removeClass('selected')

				if (this.model.result.type === 'icon-font') {
					var currentValue = this.model.result['icon-class']
				} else if (this.model.result.type === 'custom-upload') {
					var currentValue = this.model.result['attachment-id']
				}

				if (currentValue) {
					this.model.frame.$el
						.find('[data-fw-icon-v2$="' + currentValue + '"]')
						.addClass('selected')
				}
			},

			markIconAsFavorite: function markIconAsFavorite(e) {
				e.preventDefault()
				e.stopPropagation()

				var icon = $(e.currentTarget)
					.closest('.fw-icon-v2-library-icon')
					.attr('data-fw-icon-v2')

				this.model.markAsFavorite(icon)

				this.renderFavoritesAndRecentUploads()
				this.refreshFavorites()
			},

			refreshFavorites: function() {
				$('.fw-icon-v2-favorite').removeClass('fw-icon-v2-favorite')

				_.map(this.model.currentFavorites, function(favorite) {
					if (
						_.compose(
							_.negate(_.isNaN),
							_.partial(parseInt, _, 10)
						)(favorite)
					) {
						return
					}

					$('[data-fw-icon-v2="' + favorite + '"]').addClass(
						'fw-icon-v2-favorite'
					)
				})
			},

			renderFavoritesAndRecentUploads: function() {
				this.model.frame.$el
					.find('.fw-favorite-icons-wrapper')
					.replaceWith(this.model.getFavoritesHtml())

				this.model.frame.$el
					.find(
						'[data-fw-option-id="upload-custom-icon-recents"] .fw-option-html'
					)
					.html(this.model.getRecentIconsHtml())
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
					.closest('.fw-icon-v2-emoji-tab')
					.find('.fw-icon-v2-emoji-live')
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
					.closest('.fw-icon-v2-svg-tab')
					.find('.fw-icon-v2-svg-live')
					.html(isSvg ? markup : '')
			},

			// "Upload .svg file" → open the hidden file input.
			onSvgUploadClick: function(event) {
				event.preventDefault()
				$(event.currentTarget)
					.closest('.fw-icon-v2-toolbar')
					.find('.fw-icon-v2-svg-file')
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
						.find('.fw-icon-v2-svg-input')
						.val(markup)
					view.model.frame.$el
						.find('.fw-icon-v2-svg-live')
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

			// Lucide tab: search-as-you-type (debounced) against the bundled set.
			onLucideSearch: function(event) {
				this.debouncedLucideSearch($(event.currentTarget).val())
			},

			doLucideSearch: function(query) {
				var view = this

				$.post(ajaxurl, {
					action: 'fw_icon_v2_lucide_search',
					q: query || '',
				}).done(function(resp) {
					if (resp && resp.success) {
						view.renderLucideResults(resp.data)
					}
				})
			},

			renderLucideResults: function(items) {
				var view = this
				view.lucideResults = {}

				var $wrap = view.model.frame.$el.find(
						'.fw-icon-v2-lucide-results'
					)

				if (!items || !items.length) {
					$wrap.html(
						'<div class="fw-icon-v2-note"><h3>' +
							(window.fw_icon_v2_data &&
							fw_icon_v2_data.no_results
								? fw_icon_v2_data.no_results
								: 'No icons found') +
							'</h3></div>'
					)
					return
				}

				var currentId = view.model.result && view.model.result['svg-id']
				var html = '<ul class="fw-icon-v2-library-pack">'

				_.each(items, function(item) {
					view.lucideResults[item.name] = item

					html +=
						'<li class="fw-icon-v2-library-icon fw-icon-v2-lucide-icon ' +
						(currentId === item.id ? 'selected' : '') +
						'" data-name="' +
						_.escape(item.name) +
						'" title="' +
						_.escape(item.name) +
						'"><div class="fw-icon-inner">' +
						item.markup +
						'</div></li>'
				})

				html += '</ul>'
				$wrap.html(html)
			},

			onLucideSelect: function(event) {
				event.preventDefault()

				var $el = $(event.currentTarget)
				var item = this.lucideResults[$el.attr('data-name')]

				if (!item) {
					return
				}

				this.model.result = {
					type: 'svg',
					'svg-source': 'library',
					'svg-id': item.id,
					markup: item.markup,
				}

				$el.closest('.fw-icon-v2-library-pack')
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

			modal.frame.$el.find('.fw-icon-v2-toolbar select').selectize({
				plugins: ['hidden_textfield'],
				onChange: _.bind(modal.applyFilters, modal),
			})

			modal.frame.$el
				.find('.fw-options-tabs-wrapper')
				.off('tabsactivate.fwiconv2')
				.on('tabsactivate.fwiconv2', function(event, ui) {
					/**
					 * Every tab change should set a sensible default type on the
					 * modal (the concrete value is set when the user actually picks
					 * something in the tab). Detect the tab by a MARKER in its panel
					 * rather than its index, so adding tabs (Emoji / Custom SVG /
					 * Lucide) never breaks the existing icon-font / upload mapping.
					 * Favorites has no marker → keep the current type (the clicked
					 * favorite decides font-vs-upload).
					 */
					var $panel = ui.newPanel
					var kind =
						$panel.find('.fw-icon-v2-emoji-tab').length
							? 'emoji'
							: $panel.find('.fw-icon-v2-svg-tab').length
							? 'svg'
							: $panel.find('.fw-icon-v2-lucide-results').length
							? 'svg'
							: $panel.find(
									'[data-fw-option-id="upload-custom-icon-recents"]'
							  ).length
							? 'custom-upload'
							: $panel.find('.fw-icon-v2-icons-library').length
							? 'icon-font'
							: null

					if (kind) {
						modal.result.type = kind
					}

					// Lazy-load the Lucide grid the first time its tab is shown.
					var $lucide = $panel.find('.fw-icon-v2-lucide-results')
					if ($lucide.length && !$lucide.children().length) {
						modal.content.doLucideSearch('')
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
				'emoji': '.fw-icon-v2-emoji-tab',
				'svg': '.fw-icon-v2-svg-tab',
				'icon-font': '.fw-icon-v2-icons-library',
			}

			var wantSelector =
				typeSelectors[state.type] || typeSelectors['icon-font']
			// An SVG value has two possible tabs: a library pick opens Lucide,
			// pasted markup opens Custom SVG.
			if (state.type === 'svg') {
				wantSelector =
					state['svg-source'] === 'library'
						? '.fw-icon-v2-lucide-results'
						: '.fw-icon-v2-svg-tab'
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
				modal.frame.$el.find('.fw-icon-v2-emoji-input').val(state.char || '')
				modal.frame.$el.find('.fw-icon-v2-emoji-live').text(state.char || '')
			}
			if (state.type === 'svg') {
				modal.frame.$el.find('.fw-icon-v2-svg-input').val(state.markup || '')
				modal.frame.$el.find('.fw-icon-v2-svg-live').html(state.markup || '')
			}

			if (state.type === 'icon-font') {
				if (modal.result['icon-class']) {
					this.frame.$el
						.find(
							'.fw-icon-v2-icons-library .fw-icon-v2-toolbar input.fw-option-type-text'
						)
						.val('')

					var packForIcon = _.findWhere(
						_.values(this.getIconsData()),
						{
							css_class_prefix: this.result['icon-class'].split(
								' '
							)[0],
						}
					)

					var selectInput = modal.frame.$el.find(
						'.fw-icon-v2-icons-library .fw-icon-v2-toolbar select'
					)[0]

					if (selectInput && selectInput.value !== packForIcon) {
						this.frame.$el
							.find(
								'.fw-icon-v2-icons-library .fw-icon-v2-toolbar input.fw-option-type-text'
							)
							.val('')

						selectInput.selectize.setValue(packForIcon.name)
					}
				}
			}
		},

		applyFilters: function() {
			var packSelect = this.frame.$el.find(
				'.fw-icon-v2-icons-library .fw-icon-v2-toolbar select'
			)[0]

			var pack = packSelect
				? packSelect.value
				: _.keys(this.getIconsData())[0]

			var search = this.frame.$el
				.find(
					'.fw-icon-v2-icons-library .fw-icon-v2-toolbar input.fw-option-type-text'
				)
				.val()
				.trim()

			var packs = this.getFilteredPacks({
				pack: pack,
				search: search,
			})

			this.frame.$el
				.find(
					'[data-fw-option-id="icon-font"] .fw-icon-v2-library-pack-wrapper'
				)
				.html(
					wp.template('fw-icon-v2-packs')({
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
				action: 'fw_icon_v2_get_icons',
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
				action: 'fw_icon_v2_get_favorites',
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
				action: 'fw_icon_v2_update_favorites',
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
			return wp.template('fw-icon-v2-tabs')({
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

			return wp.template('fw-icon-v2-library')({
				packs: _.values(this.getIconsData()),
				pack_to_select: pack_to_select,
				current_state: this.result,
				favorites: this.currentFavorites,
			})
		},

		getFavoritesHtml: function() {
			return wp.template('fw-icon-v2-favorites')({
				favorites: this.currentFavorites || [],
				current_state: this.result,
			})
		},

		getRecentIconsHtml: function() {
			return wp.template('fw-icon-v2-recent-custom-icon-uploads')({
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
