<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Icon Badge Presets option type — a sibling of Border (Box) Presets, reshaped for
 * ICON badges: a fixed-size shaped tile with a centered glyph. Each badge is a named
 * preset (-> .iconb-<slug>) with a live preview and Default / Hover state TABS holding
 * the tile skin (background fill, icon color, border, box-shadow). Structural fields —
 * badge shape, badge size, icon size, corner radius, transition, hover FX, custom CSS —
 * sit outside the tabs.
 *
 * Where Box Presets model a content-fit card, an Icon Badge models the small shaped
 * container around an icon: it has a SET size, a SHAPE (circle / rounded / square /
 * hexagon), and a centered glyph with its own COLOR and SIZE — the icon-specific fields
 * a box preset doesn't carry.
 *
 * Saved value (one entry per preset):
 *   array(
 *     'id'            => string,   // unique
 *     'preset_name'   => string,   // label -> .iconb-<slug>
 *     'badge_shape'   => string,   // circle|rounded|square|hexagon
 *     'badge_size'    => array,    // unit-input { value, unit } — tile width & height
 *     'icon_size'     => array,    // unit-input { value, unit } — glyph size
 *     'border_radius' => array,    // unit-input { value, unit } — corner radius (rounded/square)
 *     'transition'    => string,   // ms (animates the hover change)
 *     'hover_fx'      => array,     // lift|glow|shine|pop
 *     'custom_css'    => string,   // {{SELECTOR}}-aware CSS
 *     'states'        => array(
 *        'default'|'hover' => array(
 *           'background',                   // background-pro (tile fill)
 *           'icon_color',                   // compact picker { predefined, custom } (glyph)
 *           'border_style',                 // scalar
 *           'border_width',                 // unit-input { value, unit }
 *           'border_color',                 // compact picker { predefined, custom }
 *           'box_shadow',                   // box-shadow value
 *        ),
 *     ),
 *   )
 */
class FW_Option_Type_Icon_Badge_Presets extends FW_Option_Type {

	public function _get_backend_width_type() {
		return 'full';
	}

	public function get_type() {
		return 'icon-badge-presets';
	}

	/** Interaction states, in tab order. */
	public static function states() {
		return array(
			'default' => __( 'Default', 'fw' ),
			'hover'   => __( 'Hover', 'fw' ),
		);
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'         => array(),
			// slug => array('label'=>..,'color'=>'#hex') for the compact pickers.
			'color-choices' => array(),
		);
	}

	/** Compact color picker wired to the supplied palette choices. */
	protected static function color_field( $label, $choices ) {
		return array(
			'type'    => 'predefined-colors-color-picker-compact',
			'label'   => $label,
			'choices' => is_array( $choices ) ? $choices : array(),
		);
	}

	/** Shared fields ABOVE the state tabs (identity). */
	public static function shared_top_options() {
		return array(
			'id' => array(
				'type' => 'unique',
			),
			'preset_name' => array(
				'type'            => 'text',
				'label'           => __( 'Badge Name', 'fw' ),
				'desc'            => __( 'e.g. Circle, Soft Tile, Outline. Produces the class .iconb-<name>.', 'fw' ),
				'dynamic_content' => false,
			),
		);
	}

	/** Shared fields BELOW the state tabs (geometry + transition + custom CSS). */
	public static function shared_bottom_options() {
		return array(
			'badge_shape' => array(
				'type'    => 'short-select',
				'label'   => __( 'Badge Shape', 'fw' ),
				'choices' => array(
					'circle'  => __( 'Circle', 'fw' ),
					'rounded' => __( 'Rounded', 'fw' ),
					'square'  => __( 'Square', 'fw' ),
					'hexagon' => __( 'Hexagon', 'fw' ),
				),
				'value' => 'circle',
				'desc'  => __( 'The tile outline. Rounded / Square honor the Corner Radius below; Hexagon is clipped.', 'fw' ),
			),
			'badge_size' => array(
				'type'  => 'unit-input',
				'label' => __( 'Badge Size', 'fw' ),
				'units' => array( 'px', 'em', 'rem' ),
				'min'   => 0,
				'value' => array( 'value' => '48', 'unit' => 'px' ),
				'desc'  => __( 'Width & height of the tile.', 'fw' ),
			),
			'icon_size' => array(
				'type'  => 'unit-input',
				'label' => __( 'Icon Size', 'fw' ),
				'units' => array( 'px', 'em', 'rem' ),
				'min'   => 0,
				'value' => array( 'value' => '24', 'unit' => 'px' ),
				'desc'  => __( 'The glyph inside the tile.', 'fw' ),
			),
			'border_radius' => array(
				'type'  => 'unit-input',
				'label' => __( 'Corner Radius', 'fw' ),
				'units' => array( 'px', 'em', 'rem', '%' ),
				'min'   => 0,
				'desc'  => __( 'Applies to the Rounded / Square shapes (Circle is always 50%).', 'fw' ),
			),
			'transition' => array(
				'type'            => 'short-text',
				'label'           => __( 'Transition (ms)', 'fw' ),
				'value'           => '200',
				'desc'            => __( 'Animates the hover change. Milliseconds, e.g. 200.', 'fw' ),
				'dynamic_content' => false,
			),
			'hover_fx' => array(
				'type'       => 'multi-select',
				'label'      => __( 'Hover Effects', 'fw' ),
				'desc'       => __( 'Special animated hover effects, layered on top of the Hover state above — combine freely. Lift raises the badge, Pop scales it up, Glow adds a colored halo, Shine sweeps a sheen across it. Honors reduced-motion.', 'fw' ),
				'population' => 'array',
				'choices'    => array(
					'lift'  => __( 'Lift', 'fw' ),
					'pop'   => __( 'Pop', 'fw' ),
					'glow'  => __( 'Glow', 'fw' ),
					'shine' => __( 'Shine', 'fw' ),
				),
				'value'      => array(),
			),
			'custom_css' => array(
				'type'        => 'code-editor',
				'label'       => __( 'Custom CSS (advanced)', 'fw' ),
				'mode'        => 'css',
				'height'      => 140,
				'placeholder' => "{{SELECTOR}} {\n    /* your rules */\n}\n{{SELECTOR}}:hover {\n    /* hover rules */\n}",
				'desc'        => __( 'Use {{SELECTOR}} for this preset (becomes .iconb-<name>).', 'fw' ),
			),
		);
	}

	/** Per-state fields (rendered once per state tab) — the tile skin (fill + icon + border). */
	public static function state_options( $choices ) {
		return array(
			'background' => array(
				'type'    => 'background-pro',
				'label'   => __( 'Tile Fill', 'fw' ),
				'desc'    => __( 'Fill behind the icon — color, gradient or image. Leave empty for a transparent (outline / ring) badge. Set per Default / Hover state.', 'fw' ),
				'disable' => 'video', // badge presets render as a CSS class, which can't host a <video>
			),
			'icon_color' => self::color_field( __( 'Icon Color', 'fw' ), $choices ),
			'border_style' => array(
				'type'    => 'short-select',
				'label'   => __( 'Border Style', 'fw' ),
				'choices' => array(
					''       => __( 'None', 'fw' ),
					'solid'  => __( 'Solid', 'fw' ),
					'dashed' => __( 'Dashed', 'fw' ),
					'dotted' => __( 'Dotted', 'fw' ),
					'double' => __( 'Double', 'fw' ),
				),
			),
			'border_width' => array(
				'type'  => 'unit-input',
				'label' => __( 'Border Width', 'fw' ),
				'units' => array( 'px', 'em', 'rem' ),
				'min'   => 0,
			),
			'border_color' => self::color_field( __( 'Border Color', 'fw' ), $choices ),
			'box_shadow' => array(
				'type'  => 'box-shadow',
				'label' => __( 'Box Shadow', 'fw' ),
			),
		);
	}

	/** Per-state fields grouped into display rows (same keys as state_options). */
	public static function state_option_rows( $choices ) {
		$o = self::state_options( $choices );
		return array(
			array( 'background'   => $o['background'] ),
			array( 'icon_color'   => $o['icon_color'] ),
			array( 'border_style' => $o['border_style'], 'border_width' => $o['border_width'] ),
			array( 'border_color' => $o['border_color'] ),
			array( 'box_shadow'   => $o['box_shadow'] ),
		);
	}

	/** Palette choices passed to the option config (theme color presets). */
	protected function choices( $option ) {
		return ( isset( $option['color-choices'] ) && is_array( $option['color-choices'] ) )
			? $option['color-choices']
			: array();
	}

	/** Every distinct sub-option, flattened — used for enqueue + parsing. */
	protected function all_sub_options( $option ) {
		$choices = $this->choices( $option );
		return array_merge(
			self::shared_top_options(),
			self::shared_bottom_options(),
			self::state_options( $choices )
		);
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		fw()->backend->enqueue_options_static( $this->all_sub_options( $option ) );

		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/static/css/icon-badge-presets.css',
			array( 'fw' ),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/static/js/icon-badge-presets.js',
			array( 'fw-events', 'jquery-ui-sortable', 'fw' ),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$choices = $this->choices( $option );

		$option['_bp'] = array(
			'shared_top'        => self::shared_top_options(),
			'shared_bottom'     => self::shared_bottom_options(),
			'state_options'     => self::state_options( $choices ),
			'state_option_rows' => self::state_option_rows( $choices ),
			'states'            => self::states(),
		);

		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->get_type() . '/views/view.php' ),
			array(
				'id'     => $id,
				'option' => $option,
				'data'   => $data,
			)
		);
	}

	/**
	 * @internal
	 */
	public function _get_value_from_input( $option, $input_value ) {
		// null = never submitted (defaults); '~' sentinel string = empty list.
		if ( is_null( $input_value ) ) {
			return $option['value'];
		}
		if ( ! is_array( $input_value ) ) {
			return array();
		}

		$choices     = $this->choices( $option );
		$shared_opts = fw_extract_only_options( array_merge( self::shared_top_options(), self::shared_bottom_options() ) );
		$state_opts  = fw_extract_only_options( self::state_options( $choices ) );
		$state_keys  = array_keys( self::states() );

		$values = array();

		foreach ( $input_value as $box_input ) {
			if ( ! is_array( $box_input ) ) {
				continue; // skip the '~' sentinel
			}

			$entry = array();

			foreach ( $shared_opts as $opt_id => $opt ) {
				$entry[ $opt_id ] = fw()->backend->option_type( $opt['type'] )->get_value_from_input(
					$opt,
					isset( $box_input[ $opt_id ] ) ? $box_input[ $opt_id ] : null
				);
			}

			$entry['states'] = array();
			$in_states = isset( $box_input['states'] ) && is_array( $box_input['states'] ) ? $box_input['states'] : array();
			foreach ( $state_keys as $state ) {
				$src       = isset( $in_states[ $state ] ) && is_array( $in_states[ $state ] ) ? $in_states[ $state ] : array();
				$state_val = array();
				foreach ( $state_opts as $opt_id => $opt ) {
					$state_val[ $opt_id ] = fw()->backend->option_type( $opt['type'] )->get_value_from_input(
						$opt,
						isset( $src[ $opt_id ] ) ? $src[ $opt_id ] : null
					);
				}
				$entry['states'][ $state ] = $state_val;
			}

			$values[] = $entry;
		}

		return $values;
	}
}
