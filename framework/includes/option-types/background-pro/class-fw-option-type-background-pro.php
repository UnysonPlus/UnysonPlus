<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Background Pro
 *
 * Composite background option modeled after Avada / Elementor builders.
 * Four sub-tabs (Color / Gradient / Image / Video) whose values stack as
 * CSS layers when rendered. An empty `advanced` slot is reserved for v2
 * (overlays, filters, parallax, blend modes, etc.) — adding it later does
 * not require a schema migration.
 *
 * Originally lived in unysonplus-theme/inc/includes/option-types/; moved
 * into the plugin so any theme that ships with Unyson+ can use the type.
 * The class_exists guard means a stale theme-side copy on an upgraded
 * deploy won't fatal — the first declaration wins.
 */

if ( ! class_exists( 'Fw_Option_Type_Background_Pro' ) ) :

class Fw_Option_Type_Background_Pro extends FW_Option_Type {

	public function get_type() {
		return 'background-pro';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => array(
				'color' => array(
					// predefined-colors-color-picker shape: presets + custom picker
					'value' => array(
						'predefined' => '',
						'custom'     => '',
					),
				),
				'gradient' => array(
					// Blank by default: zero stops = no gradient layer (the gradient-v2
					// value being non-empty is what turns the layer on — no enable switch).
					'data' => array(
						'type'  => 'linear',
						'angle' => 90,
						'stops' => array(),
					),
				),
				'image' => array(
					'src'        => array(),
					'position'   => 'center center',
					'size'       => array(
						'selected' => 'cover',
						'custom'   => '',
					),
					'repeat'     => 'no-repeat',
					'attachment' => 'scroll',
				),
				'video' => array(
					'enabled'      => 'no',
					'external_url' => '',
					'source_mp4'   => array(),
					'source_webm'  => array(),
					'poster'       => array(),
					'fallback'     => array(),
					'loop'         => 'yes',
					'autoplay'     => 'yes',
					'mute'         => 'yes',
					'playsinline'  => 'yes',
					'allow_interaction' => 'no',
				),
				// A tint layered OVER the image (and gradient/color) — a semi-transparent colour
				// and/or a gradient, both rendered on top so text stays legible on hero images.
				// 'color' is an rgba string; 'gradient' is a gradient-v2 data set.
				'overlay' => array(
					'color'    => '',
					'gradient' => array( 'type' => 'linear', 'angle' => 90, 'stops' => array() ),
				),
				'advanced' => array(), // reserved for v2
			),
			// Layers to hide for a given usage. String ('video') or array ('video','gradient').
			// e.g. a box-preset fill renders as CSS so it disables 'video' (no DOM hook).
			'disable' => array(),
		);
	}

	/**
	 * @internal
	 *
	 * Pull in every sub-control's CSS/JS plus our own tab styling.
	 * Mirrors the pattern in predefined-colors-color-picker: builder /
	 * customizer contexts do not auto-enqueue child types, so we do it
	 * explicitly to be safe everywhere. All paths resolve against the
	 * plugin's framework directory now that every sub-control type lives
	 * here — the option type works under any theme.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$fw_uri = fw_get_framework_directory_uri();
		$uri    = $fw_uri . '/includes/option-types/' . $this->get_type() . '/static';
		$ver    = fw()->manifest->get_version();

		// Predefined colors (Color tab — preset swatches)
		wp_enqueue_style(
			'fw-option-predefined-colors',
			$fw_uri . '/includes/option-types/predefined-colors/static/css/styles.css',
			array(), $ver
		);
		wp_enqueue_script(
			'fw-option-predefined-colors',
			$fw_uri . '/includes/option-types/predefined-colors/static/js/scripts.js',
			array( 'fw-events', 'jquery' ),
			$ver,
			true
		);

		// Standard color picker (the 'custom' half of predefined-colors-color-picker)
		wp_enqueue_style(
			'fw-option-color-picker',
			$fw_uri . '/includes/option-types/color-picker/static/css/styles.css',
			array(), $ver
		);
		wp_enqueue_script(
			'fw-option-color-picker',
			$fw_uri . '/includes/option-types/color-picker/static/js/scripts.js',
			array( 'jquery', 'fw-events', 'wp-color-picker' ),
			$ver,
			true
		);
		wp_localize_script(
			'fw-option-color-picker',
			'_fw_option_type_color_picker_localized',
			array( 'l10n' => array(
				'reset_to_default' => __( 'Reset', 'fw' ),
				'reset_to_initial' => __( 'Reset', 'fw' ),
			) )
		);

		// predefined-colors-color-picker glue (mutual-exclusion JS)
		wp_enqueue_style(
			'fw-option-predefined-colors-color-picker',
			$fw_uri . '/includes/option-types/predefined-colors-color-picker/static/css/styles.css',
			array(), $ver
		);
		wp_enqueue_script(
			'fw-option-predefined-colors-color-picker',
			$fw_uri . '/includes/option-types/predefined-colors-color-picker/static/js/scripts.js',
			array( 'fw-events', 'jquery' ),
			$ver,
			true
		);

		// fw-multi-inline (Image tab → size)
		wp_enqueue_style(
			'fw-option-fw-multi-inline',
			$fw_uri . '/includes/option-types/fw-multi-inline/static/css/styles.css',
			array(), $ver
		);

		// The Gradient / Image / Video panels compose further child option types
		// (gradient-v2, upload, oembed, fw-multi-inline). Delegate to each one's
		// own enqueue so their CSS/JS load in every context — including shortcode
		// edit modals, where the page-load options-walk can miss nested custom
		// types (the same gap that left the spacing control unstyled in modals).
		foreach ( array( 'gradient-v2', 'oembed', 'upload', 'fw-multi-inline', 'rgba-color-picker' ) as $child ) {
			fw()->backend->option_type( $child )->enqueue_static();
		}
		if ( function_exists( 'wp_enqueue_media' ) ) {
			wp_enqueue_media(); // the image / video / poster upload sub-controls
		}

		// our own tab UI
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array(), $ver
		);
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/js/scripts.js',
			array( 'jquery', 'fw-events' ),
			$ver,
			true
		);
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );
		$wrapper_attr['class'] = trim( ( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' ) . ' fw-option-type-background-pro' );

		// Use the SAVED value ($data['value']) — NOT $option['value'] (the option-def default).
		// render_options passes the stored value via $data; reading $option['value'] here made the
		// modal re-render the defaults every time, so saved color / gradient / image / video layers
		// looked empty on reopen (the frontend was unaffected — it reads the stored atts directly).
		$value        = ( isset( $data['value'] ) && is_array( $data['value'] ) ) ? $data['value'] : $option['value'];
		$id_prefix    = $option['attr']['id'] . '-';
		$name_prefix  = $option['attr']['name'];

		$tabs = array(
			'color'    => __( 'Color',    'unysonplus' ),
			'gradient' => __( 'Gradient', 'unysonplus' ),
			'image'    => __( 'Image',    'unysonplus' ),
			'overlay'  => __( 'Overlay',  'unysonplus' ),
			'video'    => __( 'Video',    'unysonplus' ),
		);

		// Hide any layers named in the option's `disable` ('video', or array('video','image')).
		// Used e.g. by box-preset fills, which render as CSS and so can't host a video layer.
		// The remaining layers stay; the first becomes the initially-active tab/panel.
		$disable = isset( $option['disable'] ) ? $option['disable'] : array();
		$disable = is_array( $disable ) ? $disable : array_filter( array_map( 'trim', explode( ',', (string) $disable ) ) );
		foreach ( $disable as $d ) { unset( $tabs[ $d ] ); }
		$active_tab = (string) key( $tabs );

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<ul class="bg-pro__tabs" role="tablist">
				<?php foreach ( $tabs as $key => $label ) :
					$has_value = $this->_layer_has_value( $key, fw_akg( $key, $value, array() ) );
					?>
					<li class="bg-pro__tab<?php echo $key === $active_tab ? ' is-active' : ''; ?><?php echo $has_value ? ' has-value' : ''; ?>"
					    data-bg-pro-tab="<?php echo esc_attr( $key ); ?>"
					    role="tab">
						<span class="bg-pro__tab-dot" aria-hidden="true"></span>
						<span class="bg-pro__tab-label"><?php echo esc_html( $label ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="bg-pro__panels">

				<?php /* ---- COLOR TAB ---- */ ?>
				<div class="bg-pro__panel<?php echo $active_tab === 'color' ? ' is-active' : ''; ?>" data-bg-pro-panel="color">
					<?php
					/**
					 * Color palette source. unysonplus-theme exposes one via
					 * `unysonplus_option_color_palette()`; other themes can supply
					 * theirs through the `fw_option_type_background_pro_color_palette`
					 * filter. A sensible default ships built-in so the picker is
					 * never empty regardless of theme.
					 *
					 * @param array $palette { 'Label' => '#hex', ... }
					 */
					$palette = function_exists( 'unysonplus_option_color_palette' )
						? unysonplus_option_color_palette()
						: array(
							__( 'Black',      'fw' ) => '#000000',
							__( 'White',      'fw' ) => '#ffffff',
							__( 'Gray',       'fw' ) => '#636c72',
							__( 'Light Gray', 'fw' ) => '#bdbdbd',
							__( 'Red',        'fw' ) => '#d9534f',
							__( 'Blue',       'fw' ) => '#3f51b5',
							__( 'Green',      'fw' ) => '#4caf50',
							__( 'Orange',     'fw' ) => '#ff9800',
						);
					$palette = apply_filters( 'fw_option_type_background_pro_color_palette', $palette );

					$this->_render_sub( 'color/value', array(
						'type'   => 'predefined-colors-color-picker',
						'label'  => __( 'Background Color', 'unysonplus' ),
						'desc'   => __( 'Pick a preset or a custom color. Setting a custom color clears the preset selection (and vice versa). Sits underneath all other layers.', 'unysonplus' ),
						'help'   => __( 'Manage your preset palette under Theme Settings → General → Colors.', 'unysonplus' ),
						'value'  => fw_akg( 'color/value', $value, array( 'predefined' => '', 'custom' => '' ) ),
						'colors' => array(
							'predefined' => array(
								'type'    => 'predefined',
								'choices' => $palette,
							),
							'custom' => array(
								'type'   => 'custom',
								'picker' => 'color-picker',
							),
						),
					), $id_prefix, $name_prefix );
					?>
				</div>

				<?php /* ---- GRADIENT TAB ---- */ ?>
				<div class="bg-pro__panel<?php echo $active_tab === 'gradient' ? ' is-active' : ''; ?>" data-bg-pro-panel="gradient">
					<?php
					$this->_render_sub( 'gradient/data', array(
						'type'  => 'gradient-v2',
						'label' => __( 'Gradient', 'unysonplus' ),
						'desc'  => __( 'Linear or radial, unlimited color stops, RGBA, live preview. Leave blank for no gradient; setting one stacks it as a background-image above the solid color.', 'unysonplus' ),
						'value' => fw_akg( 'gradient/data', $value, array() ),
					), $id_prefix, $name_prefix );
					?>
				</div>

				<?php /* ---- IMAGE TAB ---- */ ?>
				<div class="bg-pro__panel<?php echo $active_tab === 'image' ? ' is-active' : ''; ?>" data-bg-pro-panel="image">
					<?php
					$this->_render_sub( 'image/src', array(
						'type'        => 'upload',
						'label'       => __( 'Background Image', 'unysonplus' ),
						'desc'        => __( 'Stacks above gradient and color.', 'unysonplus' ),
						'images_only' => true,
						'value'       => fw_akg( 'image/src', $value, array() ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'image/position', array(
						'type'    => 'select',
						'label'   => __( 'Position', 'unysonplus' ),
						'desc'    => __( 'Where to anchor the image inside the container.', 'unysonplus' ),
						'value'   => fw_akg( 'image/position', $value, 'center center' ),
						'choices' => array(
							'top left'      => __( 'Top Left',      'unysonplus' ),
							'top center'    => __( 'Top Center',    'unysonplus' ),
							'top right'     => __( 'Top Right',     'unysonplus' ),
							'center left'   => __( 'Center Left',   'unysonplus' ),
							'center center' => __( 'Center Center', 'unysonplus' ),
							'center right'  => __( 'Center Right',  'unysonplus' ),
							'bottom left'   => __( 'Bottom Left',   'unysonplus' ),
							'bottom center' => __( 'Bottom Center', 'unysonplus' ),
							'bottom right'  => __( 'Bottom Right',  'unysonplus' ),
						),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'image/size', array(
						'type'  => 'fw-multi-inline',
						'label' => __( 'Size', 'unysonplus' ),
						'desc'  => __( 'Auto = natural size · Cover = fill, may crop · Contain = fit fully · Custom = e.g. "400px" or "100% 50%".', 'unysonplus' ),
						'value' => fw_akg( 'image/size', $value, array( 'selected' => 'cover', 'custom' => '' ) ),
						'fw_multi_options' => array(
							'selected' => array(
								'title'   => false,
								'type'    => 'select',
								'choices' => array(
									'auto'    => __( 'Auto',         'unysonplus' ),
									'cover'   => __( 'Cover',        'unysonplus' ),
									'contain' => __( 'Contain',      'unysonplus' ),
									'custom'  => __( 'Custom Value', 'unysonplus' ),
								),
							),
							'custom' => array(
								'type'  => 'short-text',
								'title' => false,
							),
						),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'image/repeat', array(
						'type'    => 'select',
						'label'   => __( 'Repeat', 'unysonplus' ),
						'desc'    => __( 'How the image tiles to fill space.', 'unysonplus' ),
						'value'   => fw_akg( 'image/repeat', $value, 'no-repeat' ),
						'choices' => array(
							'no-repeat' => __( 'No Repeat',                       'unysonplus' ),
							'repeat'    => __( 'Repeat (Tile)',                   'unysonplus' ),
							'repeat-x'  => __( 'Repeat Horizontally',             'unysonplus' ),
							'repeat-y'  => __( 'Repeat Vertically',               'unysonplus' ),
							'space'     => __( 'Space (No Crop)',                 'unysonplus' ),
							'round'     => __( 'Round (Stretch to Whole Tiles)',  'unysonplus' ),
						),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'image/attachment', array(
						'type'    => 'select',
						'label'   => __( 'Attachment', 'unysonplus' ),
						'desc'    => __( 'Scroll = moves with page. Fixed = stays in place (parallax effect). Local = scrolls with the element\'s own scrollbar.', 'unysonplus' ),
						'value'   => fw_akg( 'image/attachment', $value, 'scroll' ),
						'choices' => array(
							'scroll' => __( 'Scroll', 'unysonplus' ),
							'fixed'  => __( 'Fixed (Parallax)', 'unysonplus' ),
							'local'  => __( 'Local', 'unysonplus' ),
						),
					), $id_prefix, $name_prefix );
					?>
				</div>

				<?php /* ---- OVERLAY TAB ---- */ ?>
				<div class="bg-pro__panel<?php echo $active_tab === 'overlay' ? ' is-active' : ''; ?>" data-bg-pro-panel="overlay">
					<?php
					$this->_render_sub( 'overlay/color', array(
						'type'  => 'rgba-color-picker',
						'label' => __( 'Overlay Color', 'unysonplus' ),
						'desc'  => __( 'A semi-transparent colour laid OVER the image (and gradient / color). Use the alpha slider for tint strength — e.g. black at ~40% for legible text on a hero image.', 'unysonplus' ),
						'value' => fw_akg( 'overlay/color', $value, '' ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'overlay/gradient', array(
						'type'  => 'gradient-v2',
						'label' => __( 'Overlay Gradient', 'unysonplus' ),
						'desc'  => __( 'Optional gradient overlay (use RGBA stops for transparency) — e.g. transparent → dark, top to bottom. Stacks above the overlay colour.', 'unysonplus' ),
						'value' => fw_akg( 'overlay/gradient', $value, array() ),
					), $id_prefix, $name_prefix );
					?>
				</div>

				<?php /* ---- VIDEO TAB ---- */ ?>
				<div class="bg-pro__panel<?php echo $active_tab === 'video' ? ' is-active' : ''; ?>" data-bg-pro-panel="video">
					<?php
					// No enable toggle — the video background turns on automatically as soon as a
					// source is set (External URL, or a self-hosted MP4 / WebM). It renders as a
					// muted, looping layer on top of the image / gradient / color; the poster shows
					// while it buffers and the fallback covers browsers that can't autoplay.
					$this->_render_sub( 'video/external_url', array(
						'type'  => 'oembed',
						'label' => __( 'External Video URL', 'unysonplus' ),
						'desc'  => __( 'Paste a YouTube, Vimeo, or Dailymotion URL. When set, the external video is used and the self-hosted sources below are ignored — saving server bandwidth.', 'unysonplus' ),
						'help'  => __( 'Any URL that WordPress oEmbed recognises will work (YouTube, Vimeo, Dailymotion, TED, etc.). External videos can\'t be muted/looped from the option panel — those flags only apply to self-hosted sources.', 'unysonplus' ),
						'value' => fw_akg( 'video/external_url', $value, '' ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'video/source_mp4', array(
						'type'             => 'upload',
						'label'            => __( 'Video Source (MP4)', 'unysonplus' ),
						'desc'             => __( 'Primary self-hosted video file. H.264 MP4 has the broadest browser support — recommended for the main source. Leave empty if using an External Video URL above.', 'unysonplus' ),
						'help'             => __( 'Keep files small: aim for under 5 MB and 10-20 seconds for hero loops. Long or large videos slow page load and burn mobile data.', 'unysonplus' ),
						'images_only'      => false,
						'files_ext'        => array( 'mp4', 'm4v' ),
						'extra_mime_types' => array( 'video/mp4' ),
						'value'            => fw_akg( 'video/source_mp4', $value, array() ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'video/source_webm', array(
						'type'             => 'upload',
						'label'            => __( 'Video Source (WebM)', 'unysonplus' ),
						'desc'             => __( 'Optional secondary self-hosted source. Some browsers prefer WebM for smaller file size; if provided, it will be offered alongside the MP4.', 'unysonplus' ),
						'help'             => __( 'Both sources should contain the same content. The browser picks whichever format it prefers — you don\'t need WebM, but providing it can reduce bandwidth.', 'unysonplus' ),
						'images_only'      => false,
						'files_ext'        => array( 'webm' ),
						'extra_mime_types' => array( 'video/webm' ),
						'value'            => fw_akg( 'video/source_webm', $value, array() ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'video/poster', array(
						'type'        => 'upload',
						'label'       => __( 'Poster Image', 'unysonplus' ),
						'desc'        => __( 'Shown while the video is loading (and on mobile data savers).', 'unysonplus' ),
						'help'        => __( 'Use a still frame from the video so the transition is seamless. Same aspect ratio as the video, ideally compressed under 200 KB.', 'unysonplus' ),
						'images_only' => true,
						'value'       => fw_akg( 'video/poster', $value, array() ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'video/fallback', array(
						'type'        => 'upload',
						'label'       => __( 'Fallback Image', 'unysonplus' ),
						'desc'        => __( 'Shown when the browser cannot play the video at all (e.g. very old browsers, or both sources fail).', 'unysonplus' ),
						'help'        => __( 'Different from the Poster: the poster is a preview while loading; the fallback is permanent for users who never get the video.', 'unysonplus' ),
						'images_only' => true,
						'value'       => fw_akg( 'video/fallback', $value, array() ),
					), $id_prefix, $name_prefix );

					// Playback behaviour. A background video always AUTOPLAYS MUTED inline — that's
					// what makes it a background, and browsers only autoplay muted video (un-muted
					// is blocked for real visitors), so there is intentionally NO Sound/Mute option:
					// it would silently break autoplay for everyone but the site owner. Looping and
					// click-to-pause are the real visitor-facing choices. (Autoplay + mute + inline
					// are forced on at render time in sc_bg_pro_video_attr.)
					$this->_render_sub( 'video/loop', array(
						'type'  => 'switch',
						'label' => __( 'Loop video', 'unysonplus' ),
						'desc'  => __( 'Restart the video automatically when it reaches the end.', 'unysonplus' ),
						'value' => fw_akg( 'video/loop', $value, 'yes' ),
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No',  'unysonplus' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'unysonplus' ) ),
					), $id_prefix, $name_prefix );

					$this->_render_sub( 'video/allow_interaction', array(
						'type'  => 'switch',
						'label' => __( 'Allow pause', 'unysonplus' ),
						'desc'  => __( 'By default the video is decorative — it ignores clicks so no play/pause icon appears. Turn this on to let visitors click the video to pause and resume it.', 'unysonplus' ),
						'value' => fw_akg( 'video/allow_interaction', $value, 'no' ),
						'left-choice'  => array( 'value' => 'no',  'label' => __( 'No',  'unysonplus' ) ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'Yes', 'unysonplus' ) ),
					), $id_prefix, $name_prefix );
					?>
				</div>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render one nested sub-option wrapped in a labeled row.
	 *
	 * Unyson's stock settings-form renderer would normally add the label /
	 * description / help wrapper around each control. We bypass that pipeline
	 * by calling option_type->render() directly, so we emit our own row
	 * structure here. Label/desc/help come from the $option array, matching
	 * the conventions of native option definitions.
	 *
	 * $path is "group/field" — e.g. "image/position". The group becomes a
	 * nested key in the saved value, the field is the actual control's $id.
	 */
	private function _render_sub( $path, $option, $id_prefix, $name_prefix ) {
		$parts = explode( '/', $path );
		$group = $parts[0];
		$field = isset( $parts[1] ) ? $parts[1] : null;

		$nested_id_prefix   = $id_prefix . $group . '-';
		$nested_name_prefix = $name_prefix . '[' . $group . ']';

		$label = isset( $option['label'] ) ? $option['label'] : '';
		$desc  = isset( $option['desc'] )  ? $option['desc']  : '';
		$help  = isset( $option['help'] )  ? $option['help']  : '';

		?>
		<div class="bg-pro__row bg-pro__row--<?php echo esc_attr( $group ); ?><?php echo $field ? '-' . esc_attr( $field ) : ''; ?>">
			<?php if ( $label ) : ?>
				<div class="bg-pro__row-label">
					<label><?php echo esc_html( $label ); ?></label>
					<?php if ( $help ) : ?>
						<span class="bg-pro__row-help" tabindex="0" title="<?php echo esc_attr( wp_strip_all_tags( $help ) ); ?>">?</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="bg-pro__row-control">
				<?php
				echo fw()->backend->option_type( $option['type'] )->render(
					$field,
					$option,
					array(
						'id_prefix'   => $nested_id_prefix,
						'name_prefix' => $nested_name_prefix,
						'value'       => $option['value'],
					)
				);
				?>
				<?php if ( $desc ) : ?>
					<p class="bg-pro__row-desc"><?php
						/**
						 * Escape descriptions instead of running them through
						 * wp_kses_post(). Descriptions often mention HTML element
						 * names (e.g. "<video> tag"), and wp_kses_post on modern
						 * WordPress allows <video> — which would inject a real
						 * empty video element into the description and eat
						 * vertical space. esc_html keeps the text literal.
						 */
						echo esc_html( $desc );
					?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Decide whether a given layer should show its "has value" dot on the tab.
	 * Cheap heuristics — false positives are fine; false negatives are not.
	 */
	private function _layer_has_value( $tab, $layer_value ) {
		if ( empty( $layer_value ) || ! is_array( $layer_value ) ) {
			return false;
		}
		switch ( $tab ) {
			case 'color':
				// predefined-colors-color-picker stores nested {predefined, custom}
				$v = isset( $layer_value['value'] ) ? $layer_value['value'] : array();
				if ( ! is_array( $v ) ) {
					$v = (string) $v;
					return $v !== '' && $v !== 'rgba(0,0,0,0)' && $v !== 'transparent';
				}
				$preset = isset( $v['predefined'] ) ? trim( (string) $v['predefined'] ) : '';
				$custom = isset( $v['custom'] )     ? trim( (string) $v['custom'] )     : '';
				return $preset !== '' || ( $custom !== '' && $custom !== 'rgba(0,0,0,0)' && $custom !== 'transparent' );
			case 'gradient':
				// On iff the gradient-v2 value compiles to a real gradient (>= 2 stops).
				$data = isset( $layer_value['data'] ) ? $layer_value['data'] : array();
				return FW_Option_Type_Gradient_V2::to_css( $data ) !== '';
			case 'image':
				return ! empty( $layer_value['src'] ) && ! empty( $layer_value['src']['url'] );
			case 'video':
				// On as soon as a source is set (no enable toggle any more).
				return ! empty( $layer_value['external_url'] )
					|| ( ! empty( $layer_value['source_mp4']['url'] ) )
					|| ( ! empty( $layer_value['source_webm']['url'] ) );
			case 'overlay':
				// On iff a non-transparent overlay colour OR a real gradient is set.
				$c = isset( $layer_value['color'] ) ? trim( (string) $layer_value['color'] ) : '';
				$g = isset( $layer_value['gradient'] ) ? $layer_value['gradient'] : array();
				return ( $c !== '' && $c !== 'rgba(0,0,0,0)' && $c !== 'transparent' )
					|| ( class_exists( 'FW_Option_Type_Gradient_V2' ) && FW_Option_Type_Gradient_V2::to_css( $g ) !== '' );
		}
		return false;
	}

	/**
	 * @internal
	 *
	 * Per-sub-control value extraction. We delegate to each child option_type
	 * for the actual parsing so e.g. uploads remain valid upload arrays.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		$defaults = $this->_get_defaults();
		$out      = $defaults['value'];

		// Color (predefined-colors-color-picker)
		if ( isset( $input_value['color']['value'] ) && is_array( $input_value['color']['value'] ) ) {
			$out['color']['value'] = array(
				'predefined' => isset( $input_value['color']['value']['predefined'] ) ? (string) $input_value['color']['value']['predefined'] : '',
				'custom'     => isset( $input_value['color']['value']['custom'] )     ? (string) $input_value['color']['value']['custom']     : '',
			);
		}

		// Gradient
		if ( isset( $input_value['gradient']['data'] ) ) {
			// No enable switch; a non-empty gradient-v2 value (>= 2 stops) turns the
			// layer on. Parse through the child type so the stops/angle are sanitized.
			$out['gradient']['data'] = fw()->backend->option_type( 'gradient-v2' )->get_value_from_input(
				array( 'value' => $defaults['value']['gradient']['data'] ),
				$input_value['gradient']['data']
			);
		}

		// Image. The `upload` sub-control's hidden input submits a SCALAR attachment id
		// (not an array), so delegate to the upload type — it resolves the id to the
		// { attachment_id, url } array (and passes an already-resolved array through
		// unchanged on re-save). The old is_array() gate never matched the scalar, so the
		// background image was silently dropped on every save.
		if ( isset( $input_value['image']['src'] ) ) {
			$out['image']['src'] = fw()->backend->option_type( 'upload' )->get_value_from_input(
				array( 'type' => 'upload', 'value' => array(), 'images_only' => true ),
				$input_value['image']['src']
			);
		}
		foreach ( array( 'position', 'repeat', 'attachment' ) as $k ) {
			if ( isset( $input_value['image'][ $k ] ) ) {
				$out['image'][ $k ] = (string) $input_value['image'][ $k ];
			}
		}
		if ( isset( $input_value['image']['size'] ) && is_array( $input_value['image']['size'] ) ) {
			$out['image']['size'] = array_merge( $out['image']['size'], $input_value['image']['size'] );
		}

		// Video
		if ( isset( $input_value['video']['enabled'] ) ) {
			$out['video']['enabled'] = $input_value['video']['enabled'] === 'yes' ? 'yes' : 'no';
		}
		if ( isset( $input_value['video']['external_url'] ) ) {
			// esc_url_raw, not esc_url — we're storing, not rendering.
			$out['video']['external_url'] = esc_url_raw( (string) $input_value['video']['external_url'] );
		}
		// Video sources / poster / fallback are `upload` sub-controls too — same scalar-id
		// submit as the image above, so delegate to the upload type (was dropped by is_array()).
		foreach ( array( 'source_mp4', 'source_webm', 'poster', 'fallback' ) as $k ) {
			if ( isset( $input_value['video'][ $k ] ) ) {
				$out['video'][ $k ] = fw()->backend->option_type( 'upload' )->get_value_from_input(
					array( 'type' => 'upload', 'value' => array(), 'images_only' => ( 'poster' === $k || 'fallback' === $k ) ),
					$input_value['video'][ $k ]
				);
			}
		}
		foreach ( array( 'loop', 'autoplay', 'mute', 'playsinline', 'allow_interaction' ) as $k ) {
			if ( isset( $input_value['video'][ $k ] ) ) {
				$out['video'][ $k ] = $input_value['video'][ $k ] === 'yes' ? 'yes' : 'no';
			}
		}

		// Overlay — an rgba colour + a gradient-v2, both parsed through their child types.
		if ( isset( $input_value['overlay']['color'] ) ) {
			$out['overlay']['color'] = fw()->backend->option_type( 'rgba-color-picker' )->get_value_from_input(
				array( 'type' => 'rgba-color-picker', 'value' => '' ),
				$input_value['overlay']['color']
			);
		}
		if ( isset( $input_value['overlay']['gradient'] ) ) {
			$out['overlay']['gradient'] = fw()->backend->option_type( 'gradient-v2' )->get_value_from_input(
				array( 'type' => 'gradient-v2', 'value' => $defaults['value']['overlay']['gradient'] ),
				$input_value['overlay']['gradient']
			);
		}

		return $out;
	}
}

FW_Option_Type::register( 'Fw_Option_Type_Background_Pro' );

/**
 * Force-load the background-pro assets on post-edit screens.
 *
 * background-pro ships its own stylesheet/script plus a stack of child controls
 * (predefined-colors, color-picker, gradient-v2, upload, oembed, fw-multi-inline).
 * When it's used in a shortcode (e.g. the Section's Background control), the
 * page-builder modal loads options via an AJAX walk that does not reliably reach
 * nested custom option types — so the control could render unstyled / inert.
 * Enqueuing here on every post-edit screen (where the builder + shortcode modals
 * live) guarantees the assets are present before any modal opens. enqueue_static
 * dedupes by handle, so this is a safe no-op when something already loaded them.
 */
if ( ! function_exists( 'fw_option_type_background_pro_force_admin_enqueue' ) ) {
	function fw_option_type_background_pro_force_admin_enqueue() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && $screen->base === 'post' ) {
			fw()->backend->option_type( 'background-pro' )->enqueue_static();
		}
	}
	add_action( 'admin_enqueue_scripts', 'fw_option_type_background_pro_force_admin_enqueue', 20 );
}

endif; // class_exists guard
