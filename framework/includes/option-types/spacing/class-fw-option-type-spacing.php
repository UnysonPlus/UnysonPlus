<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Spacing
 *
 * Composite spacing option (Margin + Padding) with a compact, Elementor-style
 * layout: one inline row per section with a link toggle (linked = a single
 * "All" select; unlinked = four Top / Right / Bottom / Left selects), plus a
 * per-device switcher (Phone / Tablet / Desktop) in the header.
 *
 * `mode` attribute scopes the widget:
 *   'both'    (default) — both margin and padding rows
 *   'margin'  — margin row only (padding subtree stays at defaults on save)
 *   'padding' — padding row only
 *
 * The saved value is a nested array of Bootstrap utility class names. The base
 * (phone) layer lives under `margin` / `padding` (e.g. 'm-3', 'pt-2') and, being
 * min-width:0 utilities, applies at every width — so values saved before the
 * per-device feature keep rendering identically. Tablet / Desktop overrides live
 * under `advanced.md` / `advanced.lg` and carry Bootstrap's responsive infix
 * (e.g. 'm-md-3', 'pt-lg-2'), emitted as min-width media-query utilities by
 * css-tokens.php. Mobile-first, Bootstrap-native — bigger screens inherit the
 * smaller layer unless explicitly overridden.
 *
 * Choices are generated from the type's own scale, which defaults to Bootstrap
 * 5's $spacers. Theme / plugin code can replace the scale via the
 * `fw_option_type_spacing_scale` filter — letting the type stay self-contained
 * (no calls into the shortcodes extension or any theme-specific preset getter)
 * while still picking up a site-wide custom scale wherever one is wired in.
 *
 * The device switcher reuses the shared component in framework/includes/device-tabs.php
 * + framework/static/js/fw-device-tabs.js, which syncs to the page builder's
 * global device toggle (window.fwPbDevice / the `fw:builder:device-preview` event).
 */

if ( ! class_exists( 'Fw_Option_Type_Spacing' ) ) :

class Fw_Option_Type_Spacing extends FW_Option_Type {

	const SLOTS = array( 'all', 'top', 'right', 'bottom', 'left' );

	/**
	 * Per-device layers. key = panel/breakpoint token used by the device-tabs
	 * component; bp = Bootstrap responsive infix ('' = base / no media query).
	 */
	const DEVICES = array(
		'base' => '',
		'md'   => 'md',
		'lg'   => 'lg',
	);

	public function get_type() {
		return 'spacing';
	}

	/**
	 * Bootstrap utility-class prefix for a section/slot pair.
	 * e.g. ('margin','top') → 'mt'.
	 */
	public function get_prefix( $section, $slot ) {
		$map = array(
			'margin'  => array( 'all' => 'm',  'top' => 'mt', 'right' => 'me', 'bottom' => 'mb', 'left' => 'ms' ),
			'padding' => array( 'all' => 'p',  'top' => 'pt', 'right' => 'pe', 'bottom' => 'pb', 'left' => 'ps' ),
		);
		return isset( $map[ $section ][ $slot ] ) ? $map[ $section ][ $slot ] : 'm';
	}

	/**
	 * Build the full Bootstrap utility class for a section/slot/breakpoint/size.
	 * Base (`$bp === ''`) → 'mt-3'; tablet ('md') → 'mt-md-3'; desktop ('lg') → 'mt-lg-3'.
	 */
	public function class_name( $section, $slot, $bp, $slug ) {
		$prefix = $this->get_prefix( $section, $slot );
		$infix  = ( $bp === '' ) ? '' : '-' . $bp;
		return $prefix . $infix . '-' . $slug;
	}

	/**
	 * Built-in default spacing scale — Bootstrap 5's $spacers verbatim.
	 * Used when nothing hooks the `fw_option_type_spacing_scale` filter,
	 * so the type produces a sensible dropdown out of the box in any theme.
	 *
	 * Each entry: array( 'name' => slug, 'size' => CSS length ).
	 *
	 * @return array
	 */
	public function default_scale() {
		return array(
			array( 'name' => '0', 'size' => '0'       ),
			array( 'name' => '1', 'size' => '0.25rem' ),
			array( 'name' => '2', 'size' => '0.5rem'  ),
			array( 'name' => '3', 'size' => '1rem'    ),
			array( 'name' => '4', 'size' => '1.5rem'  ),
			array( 'name' => '5', 'size' => '3rem'    ),
		);
	}

	/**
	 * Resolved spacing scale. Apply the `fw_option_type_spacing_scale`
	 * filter so a theme / extension can swap in its own (e.g. one read
	 * from Theme Settings). Falls back to the built-in default if the
	 * filter returns anything unusable.
	 *
	 * @return array
	 */
	public function get_scale() {
		$scale = apply_filters( 'fw_option_type_spacing_scale', $this->default_scale() );
		return ( is_array( $scale ) && ! empty( $scale ) ) ? $scale : $this->default_scale();
	}

	/**
	 * Local class-name sanitizer — keeps the option type independent of
	 * the shortcodes extension's `sc_sanitize_class()` helper.
	 */
	private function sanitize_class( $value ) {
		return preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( (string) $value ) );
	}

	/**
	 * Slot choices keyed by Bootstrap utility class name, for a given breakpoint.
	 * Returns: [ '' => 'Default', '{prefix}{-bp}-{slug}' => '{name} ({size})', ... ]
	 */
	public function get_choices( $section, $slot, $bp = '' ) {
		$out = array( '' => __( 'Default', 'unysonplus' ) );

		foreach ( $this->get_scale() as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['name'] ) ) { continue; }
			$slug = strtolower( $this->sanitize_class( $entry['name'] ) );
			if ( $slug === '' ) { continue; }
			$size  = isset( $entry['size'] ) ? $entry['size'] : '';
			$label = $entry['name'] . ( $size !== '' ? ' (' . $size . ')' : '' );
			$out[ $this->class_name( $section, $slot, $bp, $slug ) ] = $label;
		}
		return $out;
	}

	/**
	 * Render one slot's <select> by delegating to the short-select option type.
	 * Mirrors the sub-control pattern background-pro uses (no FW label wrapper —
	 * the view supplies its own). `$sec_id_prefix` / `$sec_name_prefix` are the
	 * section-level prefixes under which short-select appends `[{slot}]`.
	 */
	public function render_slot( $section, $slot, $slot_value, $sec_id_prefix, $sec_name_prefix, $bp = '' ) {
		return fw()->backend->option_type( 'short-select' )->render(
			$slot,
			array(
				'type'    => 'short-select',
				'label'   => false,
				'desc'    => false,
				'value'   => $slot_value,
				'choices' => $this->get_choices( $section, $slot, $bp ),
				'attr'    => array( 'class' => 'sc-spacing fw-option-spacing-slot' ),
			),
			array(
				'id_prefix'   => $sec_id_prefix,
				'name_prefix' => $sec_name_prefix,
				'value'       => $slot_value,
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		$empty = array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' );
		return array(
			'mode'  => 'both',
			'value' => array(
				// Base / phone layer — Bootstrap min-width:0 utilities (all widths).
				'margin'   => $empty,
				'padding'  => $empty,
				// Per-device overrides (mobile-first): md ≥768px, lg ≥992px.
				'advanced' => array(
					'md' => array( 'margin' => $empty, 'padding' => $empty ),
					'lg' => array( 'margin' => $empty, 'padding' => $empty ),
				),
			),
		);
	}

	/**
	 * Merge a saved value over defaults so every device/section/slot key exists,
	 * even after a partial save or when reading a value stored before the
	 * per-device feature (its `advanced` was an empty array).
	 */
	private function normalize_value( $saved ) {
		$defaults = $this->_get_defaults();
		$saved    = is_array( $saved ) ? $saved : array();
		$value    = $defaults['value'];

		foreach ( array( 'margin', 'padding' ) as $section ) {
			if ( isset( $saved[ $section ] ) && is_array( $saved[ $section ] ) ) {
				$value[ $section ] = array_merge( $value[ $section ], $saved[ $section ] );
			}
		}

		$saved_adv = ( isset( $saved['advanced'] ) && is_array( $saved['advanced'] ) ) ? $saved['advanced'] : array();
		foreach ( array( 'md', 'lg' ) as $dev ) {
			foreach ( array( 'margin', 'padding' ) as $section ) {
				if ( isset( $saved_adv[ $dev ][ $section ] ) && is_array( $saved_adv[ $dev ][ $section ] ) ) {
					$value['advanced'][ $dev ][ $section ] = array_merge(
						$value['advanced'][ $dev ][ $section ],
						$saved_adv[ $dev ][ $section ]
					);
				}
			}
		}

		return $value;
	}

	/**
	 * @internal
	 *
	 * Pull in short-select's assets (we render several per device) plus the
	 * shared device-tabs component and our own layout styling. Same explicit-
	 * enqueue pattern as background-pro so builder / customizer contexts don't
	 * miss the sub-control assets.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$uri    = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );
		$fw_uri = fw_get_framework_directory_uri( '/static' );
		$ver    = fw()->manifest->get_version();

		wp_enqueue_style( 'dashicons' );
		fw()->backend->option_type( 'short-select' )->enqueue_static();

		// Shared device switcher (Phone / Tablet / Desktop), synced to the
		// page builder's global device toggle.
		wp_enqueue_style(
			'fw-device-tabs',
			$fw_uri . '/css/fw-device-tabs.css',
			array(), $ver
		);
		wp_enqueue_script(
			'fw-device-tabs',
			$fw_uri . '/js/fw-device-tabs.js',
			array( 'jquery', 'fw-events' ),
			$ver,
			true
		);

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array( 'fw-device-tabs' ), $ver
		);
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/js/scripts.js',
			array( 'jquery', 'fw-events', 'fw-device-tabs' ),
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
	 * Render one section row (Margin or Padding) for a single device layer:
	 * a header (title + link toggle) and the field area (an "All" select plus
	 * the four side selects). CSS shows the "All" select when linked and the
	 * four sides when unlinked; the link toggle (scripts.js) flips the state.
	 */
	private function render_section_row( $section, $section_label, $section_value, $bp, $sec_id_prefix, $sec_name_prefix ) {
		$slot_labels = array(
			'top'    => __( 'Top',    'unysonplus' ),
			'right'  => __( 'Right',  'unysonplus' ),
			'bottom' => __( 'Bottom', 'unysonplus' ),
			'left'   => __( 'Left',   'unysonplus' ),
		);

		// Default to "linked" unless any per-side value is set — that's the
		// signal the user previously chose per-side editing.
		$has_sides = false;
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $slot ) {
			if ( ! empty( $section_value[ $slot ] ) ) { $has_sides = true; break; }
		}
		$linked_class = $has_sides ? '' : ' is-linked';

		ob_start();
		?>
		<div class="fw-sp-row fw-sp-row--<?php echo esc_attr( $section ); ?><?php echo $linked_class; ?>">
			<div class="fw-sp-row-head">
				<span class="fw-sp-row-title"><?php echo esc_html( $section_label ); ?></span>
				<button type="button" class="fw-sp-link" title="<?php esc_attr_e( 'Link sides (apply one value to all)', 'unysonplus' ); ?>" aria-label="<?php esc_attr_e( 'Link sides', 'unysonplus' ); ?>">
					<span class="dashicons dashicons-admin-links fw-sp-ico-linked"></span>
					<span class="dashicons dashicons-editor-unlink fw-sp-ico-unlinked"></span>
				</button>
			</div>
			<div class="fw-sp-fields">
				<div class="fw-sp-all">
					<span class="fw-sp-slot-label"><?php esc_html_e( 'All', 'unysonplus' ); ?></span>
					<?php echo $this->render_slot( $section, 'all', isset( $section_value['all'] ) ? $section_value['all'] : '', $sec_id_prefix, $sec_name_prefix, $bp ); ?>
				</div>
				<div class="fw-sp-sides">
					<?php foreach ( $slot_labels as $slot => $slot_label ) : ?>
						<div class="fw-sp-slot fw-sp-slot--<?php echo esc_attr( $slot ); ?>">
							<span class="fw-sp-slot-label"><?php echo esc_html( $slot_label ); ?></span>
							<?php echo $this->render_slot( $section, $slot, isset( $section_value[ $slot ] ) ? $section_value[ $slot ] : '', $sec_id_prefix, $sec_name_prefix, $bp ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @internal
	 *
	 * Inline ob_start pattern (matches background-pro). Header carries the device
	 * switcher; one panel per device holds the Margin / Padding rows. All panels
	 * stay in the DOM (only the active one is shown) so every device's selects
	 * submit on save.
	 */
	protected function _render( $id, $option, $data ) {
		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );

		$value = $this->normalize_value( $data['value'] );

		$mode = isset( $option['mode'] ) ? (string) $option['mode'] : 'both';
		if ( ! in_array( $mode, array( 'both', 'margin', 'padding' ), true ) ) {
			$mode = 'both';
		}

		$wrapper_attr['class'] = trim(
			( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' )
			. ' fw-option-type-spacing fw-device-host fw-option-type-spacing--mode-' . $mode
		);

		$id_prefix   = $option['attr']['id'] . '-';
		$name_prefix = $option['attr']['name'];

		$sections = array(
			'margin'  => __( 'Margin',  'unysonplus' ),
			'padding' => __( 'Padding', 'unysonplus' ),
		);

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<div class="fw-option-spacing-head fw-device-head">
				<?php echo fw_render_device_tabs( $option['attr']['id'] ); ?>
			</div>
			<div class="fw-option-spacing-panels">
				<?php foreach ( self::DEVICES as $key => $bp ) :
					// Resolve this device layer's section values + prefixes.
					if ( $key === 'base' ) {
						$layer_values     = $value;                          // margin / padding at top level
						$sec_name_segment = '';                              // {name}[{section}]
						$sec_id_segment   = '';                              // {id}-{section}-
					} else {
						$layer_values     = isset( $value['advanced'][ $key ] ) ? $value['advanced'][ $key ] : array();
						$sec_name_segment = '[advanced][' . $key . ']';      // {name}[advanced][md][{section}]
						$sec_id_segment   = 'advanced-' . $key . '-';        // {id}-advanced-md-{section}-
					}
					?>
					<div class="fw-option-spacing-panel<?php echo ( $key === 'base' ) ? ' is-active' : ''; ?>" data-fw-device-panel="<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $sections as $section => $section_label ) :
							if ( $mode === 'margin'  && $section !== 'margin' )  { continue; }
							if ( $mode === 'padding' && $section !== 'padding' ) { continue; }

							$section_value   = isset( $layer_values[ $section ] ) && is_array( $layer_values[ $section ] ) ? $layer_values[ $section ] : array();
							$sec_name_prefix = $name_prefix . $sec_name_segment . '[' . $section . ']';
							$sec_id_prefix   = $id_prefix . $sec_id_segment . $section . '-';

							echo $this->render_section_row( $section, $section_label, $section_value, $bp, $sec_id_prefix, $sec_name_prefix );
						endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize one section's submitted slot map. Only [A-Za-z0-9_-] survives —
	 * legit values are Bootstrap utility classes ('m-3', 'm-md-3'), so any other
	 * character is a tampered submission.
	 */
	private function parse_section( $input_section ) {
		$out = array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' );
		if ( ! is_array( $input_section ) ) { return $out; }
		foreach ( self::SLOTS as $slot ) {
			if ( ! isset( $input_section[ $slot ] ) ) { continue; }
			$out[ $slot ] = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $input_section[ $slot ] );
		}
		return $out;
	}

	/**
	 * @internal
	 *
	 * Per-slot value extraction across the base layer and the md / lg overrides.
	 * Honours `mode` — when set to 'margin' or 'padding', the inactive subtree is
	 * force-reset to defaults so a stale value can't leak in via a tampered POST.
	 *
	 * When `$input_value` is not an array (FW passes `null` whenever the option
	 * key is missing from the submitted form — notably the page-builder's
	 * "re-save existing atts" path which calls
	 * `fw_get_options_values_from_input( $options, array() )`), fall back to
	 * `$option['value']`. FW's `get_value_from_input` has already merged the
	 * previously-saved value into `$option['value']` by that point, so returning
	 * it preserves the existing pick (including the advanced overrides) across
	 * post Update / re-save cycles. Returning `$defaults['value']` here would
	 * silently reset every shortcode's spacing whenever the post is re-saved.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		$defaults = $this->_get_defaults();
		$out      = $defaults['value'];

		$mode = isset( $option['mode'] ) ? (string) $option['mode'] : 'both';
		if ( $mode === 'margin' )       { $sections = array( 'margin' ); }
		elseif ( $mode === 'padding' )  { $sections = array( 'padding' ); }
		else                            { $sections = array( 'margin', 'padding' ); }

		// Base / phone layer.
		foreach ( $sections as $section ) {
			if ( isset( $input_value[ $section ] ) ) {
				$out[ $section ] = $this->parse_section( $input_value[ $section ] );
			}
		}

		// Per-device overrides (md / lg).
		$input_adv = ( isset( $input_value['advanced'] ) && is_array( $input_value['advanced'] ) ) ? $input_value['advanced'] : array();
		foreach ( array( 'md', 'lg' ) as $dev ) {
			foreach ( $sections as $section ) {
				if ( isset( $input_adv[ $dev ][ $section ] ) ) {
					$out['advanced'][ $dev ][ $section ] = $this->parse_section( $input_adv[ $dev ][ $section ] );
				}
			}
		}

		return $out;
	}
}

FW_Option_Type::register( 'Fw_Option_Type_Spacing' );

/**
 * Force-load the spacing assets on post-edit screens.
 *
 * Unlike base option types — whose styles live in the always-loaded
 * option-types.css — the spacing control ships its OWN stylesheet/script (link
 * toggle, device switcher, …). Generic shortcodes (text-block, button, …) load
 * their option HTML into the page-builder modal via AJAX, and the page-load
 * enqueue walk that is supposed to cover them does not reliably reach this
 * nested custom option type — so its CSS/JS could be missing in those modals
 * (symptom: both link-toggle icons showing, an unstyled grid). Enqueuing here on
 * every post-edit screen (where the builder and shortcode modals live) guarantees
 * the assets are on the page before any modal opens. The option type's own
 * `static_enqueued` guard makes this a no-op when something already enqueued it.
 */
if ( ! function_exists( 'fw_option_type_spacing_force_admin_enqueue' ) ) {
	function fw_option_type_spacing_force_admin_enqueue() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && $screen->base === 'post' ) {
			fw()->backend->option_type( 'spacing' )->enqueue_static();
		}
	}
	add_action( 'admin_enqueue_scripts', 'fw_option_type_spacing_force_admin_enqueue', 20 );
}

endif; // class_exists guard
