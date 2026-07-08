<?php

// Unified "Icons" tab = webfont packs (Font Awesome, Dashicons, …) + inline-SVG
// libraries (Lucide, Tabler, …) behind ONE optgrouped dropdown. Each pack is
// gated by the Theme Settings -> Icons checklist. Font packs come from the
// icon-v3 packs loader; SVG packs from the multi-pack registry (auto-listed as
// new ones are bundled). Picking a font pack drives a client-side filter over
// the localised icon list; picking an SVG pack drives an AJAX search — the JS
// switches mode by the option's data-type. Emoji stays its own tab.
$font_packs = array();
if ( function_exists( 'unysonplus_font_icon_pack_ids' ) && function_exists( 'fw' ) ) {
	$icon_ot      = fw()->backend->option_type( 'icon-v3' );
	$loader_packs = ( $icon_ot && isset( $icon_ot->packs_loader ) ) ? $icon_ot->packs_loader->get_packs_unfiltered() : array();
	foreach ( unysonplus_font_icon_pack_ids() as $font_pid ) {
		if ( function_exists( 'unysonplus_icon_pack_enabled' ) && ! unysonplus_icon_pack_enabled( $font_pid ) ) {
			continue;
		}
		$font_packs[ $font_pid ] = isset( $loader_packs[ $font_pid ]['title'] ) ? $loader_packs[ $font_pid ]['title'] : ucfirst( $font_pid );
	}
}

$svg_packs = array();
if ( function_exists( 'unysonplus_svg_icon_pack_ids' ) ) {
	$svg_reg = function_exists( 'fw_icon_svg_pack_registry' ) ? fw_icon_svg_pack_registry() : array();
	foreach ( unysonplus_svg_icon_pack_ids() as $svg_pid ) {
		if ( function_exists( 'unysonplus_icon_pack_enabled' ) && ! unysonplus_icon_pack_enabled( $svg_pid ) ) {
			continue;
		}
		$svg_packs[ $svg_pid ] = isset( $svg_reg[ $svg_pid ]['title'] ) ? $svg_reg[ $svg_pid ]['title'] : ucfirst( $svg_pid );
	}
}

// Default selected pack: Font Awesome if present, else first font pack, else
// first SVG pack.
$default_pack = isset( $font_packs['font-awesome'] ) ? 'font-awesome'
	: ( $font_packs ? key( $font_packs ) : ( $svg_packs ? key( $svg_packs ) : '' ) );

$icons_select = '<select class="fw-icon-v3-pack-select">';
if ( $font_packs ) {
	$icons_select .= '<optgroup label="' . esc_attr__( 'Icon Fonts', 'fw' ) . '">';
	foreach ( $font_packs as $fpid => $fptitle ) {
		$icons_select .= '<option value="' . esc_attr( $fpid ) . '" data-type="font"'
			. ( $fpid === $default_pack ? ' selected' : '' ) . '>' . esc_html( $fptitle ) . '</option>';
	}
	$icons_select .= '</optgroup>';
}
if ( $svg_packs ) {
	$icons_select .= '<optgroup label="' . esc_attr__( 'SVG Icons', 'fw' ) . '">';
	foreach ( $svg_packs as $spid => $sptitle ) {
		$icons_select .= '<option value="' . esc_attr( $spid ) . '" data-type="svg"'
			. ( $spid === $default_pack ? ' selected' : '' ) . '>' . esc_html( $sptitle ) . '</option>';
	}
	$icons_select .= '</optgroup>';
}
$icons_select .= '</select>';

// Toolbar (dropdown + search) sits first; two result panes below (font grid /
// SVG grid), toggled by mode. The font pane keeps data-fw-option-id="icon-font"
// so applyFilters() renders into it unchanged.
$icons_tab_html =
	'<div class="fw-icon-v3-toolbar">'
	. $icons_select
	. '<input type="text" class="fw-icon-v3-icons-search fw-option fw-option-type-text" placeholder="'
		. esc_attr__( 'Search icons…', 'fw' ) . '" autocomplete="off" />'
	. '</div>'
	// Both result panes share ONE scroll container so that during a merged
	// search the font results and the SVG results stack and scroll together
	// (otherwise each pane is full-height and the SVG one sits below the fold).
	. '<div class="fw-icon-v3-results">'
		. '<div class="fw-icon-v3-font-mode" data-fw-option-id="icon-font">'
			. '<div class="fw-icon-v3-library-pack-wrapper fw-search-icons-wrapper"></div>'
		. '</div>'
		. '<div class="fw-icon-v3-svg-mode" style="display:none">'
			. '<div class="fw-icon-v3-library-pack-wrapper fw-search-icons-wrapper fw-icon-v3-lucide-results"></div>'
		. '</div>'
	. '</div>';

$tab_config = array(
		'icons' => array(
			'type' => 'tab',
			'title' => __('Icons', 'fw'),
			'lazy_tabs' => false,
			'options' => array(
				'icons-picker' => array(
					'type' => 'html-full',
					'attr' => array('class' => 'fw-icon-v3-icons-library'),
					'label' => false,
					'html' => $icons_tab_html,
				)
			)
		),

		// Tab order is purely presentational: both tab→type detection AND the
		// open-on-matching-tab logic (prepareForPick) are marker-based, so tabs
		// can be arranged in any order. Current order: Icons, Emoji, Custom,
		// Favorites.
		'emoji' => array(
			'type' => 'tab',
			'lazy_tabs' => false,
			'title' => __('Emoji', 'fw'),
			'options' => array(
				'emoji-picker' => array(
					'type' => 'html-full',
					'label' => false,
					'html' =>
						// Toolbar is a sibling (first child), matching the Icon
						// Fonts tab, so the heading aligns; the emoji-tab wrapper
						// holds only the content below it.
						'<div class="fw-icon-v3-toolbar"><h3>' . esc_html__( 'Emoji', 'fw' ) . '</h3></div>'
						. '<div class="fw-icon-v3-emoji-tab">'
						. '<p class="fw-icon-v3-hint">'
						. esc_html__( 'Type or paste any emoji. Windows: press Win + . — macOS: Ctrl + Cmd + Space.', 'fw' )
						. '</p>'
						. '<input type="text" class="fw-icon-v3-emoji-input" maxlength="16" placeholder="' . esc_attr__( 'e.g. star, rocket, heart emoji', 'fw' ) . '" autocomplete="off" />'
						. '<div class="fw-icon-v3-emoji-live" aria-hidden="true"></div>'
						. '</div>'
				)
			)
		),

		// Custom = bring-your-own icon: paste/upload an inline SVG (top) OR upload
		// an image via the WP media library (bottom). One tab, two sections, each
		// with its own preview. Both value types (svg-inline / custom-upload) open
		// this tab; the JS handlers keep their original selectors, so the merge is
		// purely presentational.
		'custom' => array(
			'type' => 'tab',
			'lazy_tabs' => false,
			'title' => __('Custom', 'fw'),
			'options' => array(
				'custom-picker' => array(
					'type' => 'html-full',
					'label' => false,
					'html' =>
						'<div class="fw-icon-v3-toolbar"><h3>' . esc_html__( 'Custom', 'fw' ) . '</h3></div>'
						. '<div class="fw-icon-v3-custom-tab">'
						// --- SVG section: paste inline markup or upload an .svg ---
						. '<div class="fw-icon-v3-svg-tab">'
						. '<div class="fw-icon-v3-custom-head">'
						. '<h4 class="fw-icon-v3-section-label">' . esc_html__( 'SVG code', 'fw' ) . '</h4>'
						. '<button type="button" class="fw-icon-v3-svg-upload button button-secondary">' . esc_html__( 'Upload SVG file', 'fw' ) . '</button>'
						. '<input type="file" class="fw-icon-v3-svg-file" accept=".svg,image/svg+xml" style="display:none" />'
						. '</div>'
						. '<p class="fw-icon-v3-hint">'
						. esc_html__( 'Paste inline <svg>…</svg> markup, or upload an .svg file. Scripts, event handlers and external references are stripped for safety. Use fill="currentColor" to inherit the element colour.', 'fw' )
						. '</p>'
						. '<textarea class="fw-icon-v3-svg-input" rows="6" spellcheck="false" placeholder="&lt;svg viewBox=&quot;0 0 24 24&quot;&gt;…&lt;/svg&gt;"></textarea>'
						. '<div class="fw-icon-v3-svg-live" aria-hidden="true"></div>'
						. '</div>'
						// --- Image section: WP media upload (its own preview grid) ---
						// Keep the data-fw-option-id marker the JS relies on to (a)
						// tag a clicked tile as a custom-upload and (b) open this tab
						// for a stored upload value.
						. '<div class="fw-icon-v3-upload-section" data-fw-option-id="upload-custom-icon-recents">'
						. '{{{data.recently_used_custom_uploads_html}}}'
						. '</div>'
						. '</div>'
				)
			)
		),

		'favorites' => array(
			'type' => 'tab',
			'attr' => array('class' => 'fw-icon-v3-favorites'),
			'title' => __('Favorites', 'fw'),
			'lazy_tabs' => false,
			'options' => array(
				'icon-font-favorites' => array(
					'type' => 'html-full',
					'label' => false,
					'html' => '{{{data.favorites_list_html}}}'
				)
			)
		)
	)
;

// Gate the unified Icons tab by the Theme Settings -> Icons selection: hide it
// only when NO font pack AND no SVG pack is enabled. Emoji, Custom and
// Favorites always show. Helpers come from the core pack-settings.php
// (function_exists guards keep this safe if it's absent).
if ( empty( $font_packs ) && empty( $svg_packs ) ) {
	unset( $tab_config['icons'] );
}

$tabs = fw()->backend->render_options(
	$tab_config,
	array(),
	array(
		'id_prefix' => 'fw-option-type-iconv3-',
		'name_prefix' => 'fw_option_type_iconv3'
	)
);

?>

<script type="text/html" id="tmpl-fw-icon-v3-tabs">

<?php echo $tabs; ?>

</script>

<script type="text/html" id="tmpl-fw-icon-v3-library">

<div class="fw-icon-v3-toolbar">
	<# if (data.packs.length > 1) { #>
		<select class="fw-selectize">
			<# _.each(data.packs, function (pack, index) { #>
				<option {{ index === 0 ? 'selected' : '' }} value="{{pack.name}}">
					{{pack.title}}
				</option>
			<# }) #>
		</select>
	<# } #>

	<input
		type="text"
		placeholder="<?php echo __('Search Icon', 'fw'); ?>"
		class="fw-option fw-option-type-text">
</div>

<div class="fw-icon-v3-library-pack-wrapper fw-search-icons-wrapper">
	<# if (data.packs.length > 0) { #>
		<# var template = wp.template('fw-icon-v3-packs'); #>
		<# data.packs = data.pack_to_select #>
		<# data.should_have_headings = false #>

		{{{ template(data) }}}
	<# } #>

</div>

</script>

<script type="text/html" id="tmpl-fw-icon-v3-packs">
	<# _.each(data.packs, function (pack) { #>
		<# if (pack.icons.length === 0) { return; } #>

		<# if (data.should_have_headings) { #>
			<h2>
				<span>{{pack.title}}</span>
			</h2>
		<# } #>

		{{{
			wp.template('fw-icon-v3-icons-collection')(
				_.extend({}, pack, {
					current_state: data.current_state,
					favorites: data.favorites
				})
			)
		}}}
	<# }) #>

	<# if (data.packs.length === 0) { #>
		<div class="fw-icon-v3-note">
			<h3><?php echo __('Sorry, but no results have been found', 'fw'); ?></h3>
			<p><?php echo __('You can try something like: wordpress, twitter, heart, cat e.t.c ', 'fw'); ?></p>
		</div>
	<# } #>
</script>

<script type="text/html" id="tmpl-fw-icon-v3-favorites">

<div class="fw-icon-v3-library-pack-wrapper fw-favorite-icons-wrapper">
	<# var favorites = _.filter(data.favorites, _.compose(_.isNaN, _.partial(parseInt, _, 10))) #>

	<# if (favorites.length === 0) { #>

		<div class="fw-icon-v3-note">
			<!-- <i class="fw-icon-v3-info dashicons dashicons-star-filled"></i> -->
			<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 20 20">
				<path d="M10 1l3 6 6 .75-4.12 4.62L16 19l-6-3-6 3 1.13-6.63L1 7.75 7 7z"/>
			</svg>

			<h3><?php echo __('You have no favorite icons yet', 'fw'); ?></h3>

			<p>
				<?php echo __("To add icons here, simply click on the star button that's on top right corner of each icon.", 'fw'); ?>
			</p>
		</div>

	<# } else { #>

		{{{
			wp.template('fw-icon-v3-icons-collection')(
				_.extend({}, {icons: favorites, current_state: data.current_state})
			)
		}}}

	<# } #>
</div>

</script>

<script type="text/html" id="tmpl-fw-icon-v3-recent-custom-icon-uploads">
<# var recent_uploads = _.filter(data.favorites, _.compose(_.negate(_.isNaN), _.partial(parseInt, _, 10))) #>

<div class="fw-icon-v3-toolbar fw-icon-v3-custom-head">
	<h4 class="fw-icon-v3-section-label"><?php echo __('Image', 'fw'); ?></h4>

	<button type="button" class="fw-icon-v3-custom-upload-perform button button-secondary">
		<?php echo __('Upload image', 'fw'); ?>
	</button>
</div>

<# if (recent_uploads.length === 0) { #>

	<div class="fw-icon-v3-library-pack-wrapper">
		<div class="fw-icon-v3-note">

			<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 20 20">
				<path d="M8 14V8H5l5-6 5 6h-3v6H8zm-2 2v-6H4v8h12.01v-8H14v6H6z"></path>
			</svg>

			<h3>
				<?php echo __('You have no uploaded icons yet', 'fw'); ?>
			</h3>

			<p>
				<?php echo __('To add new icons simply click on the Upload button.', 'fw'); ?>
			</p>
		</div>
	</div>

<# } else { #>

	<div class="fw-icon-v3-library-pack-wrapper">
		<ul class="fw-icon-v3-library-pack">

		<# _.each(recent_uploads, function (attachment_id) { #>
			<# var selectedClass = data.current_state['attachment-id'] === attachment_id ? 'selected' : ''; #>
			<# url = (_.min(_.values(wp.media.attachment(attachment_id).get('sizes')), function (size) {
				return size.width;
			}).url || wp.media.attachment(attachment_id).get('url')); #>

			<li
				data-fw-icon-v3="{{ attachment_id }}"
				class="fw-icon-v3-library-icon {{selectedClass}}">

				<div class="fw-icon-inner">
					<img src="{{ url }}" style="max-width: 100%" alt="">

					<a
						title="<?php esc_html_e( 'Delete item', 'fw' ); ?>"
						class="fw-icon-v3-favorite dashicons dashicons-no">
					</a>
				</div>
			</li>

		<# }) #>

			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>

		</ul>
	</div>

<# } #>

</script>

<script type="text/html" id="tmpl-fw-icon-v3-icons-collection">

	<# if (data.icons.length > 0) { #>
		<ul class="fw-icon-v3-library-pack">

		<# _.each(data.icons, function (icon) { #>
			<# var iconClass = (data.css_class_prefix && data.apply_root_class) ? data.css_class_prefix + ' ' + icon : icon; #>
			<# var selectedClass = data.current_state['icon-class'] === iconClass ? 'selected' : ''; #>
			<# var favoriteClass = _.contains(data.favorites, iconClass) ? 'fw-icon-v3-favorite' : '' #>

			<li
				data-fw-icon-v3="{{iconClass}}"
				class="fw-icon-v3-library-icon {{selectedClass}} {{favoriteClass}}">

				<div class="fw-icon-inner">
					<i class="{{iconClass}}"></i>

					<a
						title="<?php echo __('Add to Favorites', 'fw') ?>"
						class="fw-icon-v3-favorite dashicons dashicons-star-filled">
					</a>
				</div>
			</li>

		<# }) #>

			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>
			<li class="fw-ghost-item"></li>

		</ul>
	<# } #>

</script>

