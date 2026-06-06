<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Border Presets option type — a slimmed Button-Presets clone for reusable
 * column "card" border styles. Each box is a named preset (-> .colb-<slug>) with
 * a live preview and Default / Hover state TABS holding the border skin (style,
 * width, color, box-shadow). Shared fields (sides, corner radius, transition,
 * custom CSS) sit outside the tabs.
 *
 * Saved value (one entry per preset):
 *   array(
 *     'id'            => string,   // unique
 *     'preset_name'   => string,   // label -> .colb-<slug>
 *     'border_sides'  => string,   // all|top|end|bottom|start
 *     'border_radius' => array,    // unit-input { value, unit }
 *     'padding'       => array,    // spacing value (mode 'padding'): per-side
 *                                  // Bootstrap-style classes { padding: { all,
 *                                  // top, right, bottom, left } }. Resolved to
 *                                  // CSS lengths (via the spacing scale) and
 *                                  // emitted WITHOUT !important so the column's
 *                                  // own Margin & Padding wins when set.
 *     'transition'    => string,   // ms (animates the hover change)
 *     'custom_css'    => string,   // {{SELECTOR}}-aware CSS
 *     'states'        => array(
 *        'default'|'hover' => array(
 *           'border_style',                 // scalar
 *           'border_width',                 // unit-input { value, unit }
 *           'border_color',                 // compact picker { predefined, custom }
 *           'box_shadow',                   // box-shadow value
 *        ),
 *     ),
 *   )
 */
class FW_Option_Type_Border_Presets extends FW_Option_Type {

	public function _get_backend_width_type() {
		return 'full';
	}

	public function get_type() {
		return 'border-presets';
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
				'label'           => __( 'Border Name', 'fw' ),
				'desc'            => __( 'e.g. Card, Outline, Soft Shadow. Produces the class .colb-<name>.', 'fw' ),
				'dynamic_content' => false,
			),
		);
	}

	/** Shared fields BELOW the state tabs (structure + transition + custom CSS). */
	public static function shared_bottom_options() {
		return array(
			'border_sides' => array(
				'type'    => 'short-select',
				'label'   => __( 'Border Sides', 'fw' ),
				'choices' => array(
					'all'    => __( 'All sides', 'fw' ),
					'top'    => __( 'Top', 'fw' ),
					'end'    => __( 'Right', 'fw' ),
					'bottom' => __( 'Bottom', 'fw' ),
					'start'  => __( 'Left', 'fw' ),
				),
				'value' => 'all',
			),
			'border_radius' => array(
				'type'  => 'unit-input',
				'label' => __( 'Corner Radius', 'fw' ),
				'units' => array( 'px', 'em', 'rem', '%' ),
				'min'   => 0,
			),
			'padding' => array(
				'type'  => 'spacing',
				'mode'  => 'padding',
				'label' => __( 'Padding', 'fw' ),
				'desc'  => __( 'Inner padding (per side) — so a card preset isn\'t cramped. Uses your Spacing scale. The column\'s own Margin & Padding (Styling tab) overrides this whenever it\'s set.', 'fw' ),
			),
			'transition' => array(
				'type'            => 'short-text',
				'label'           => __( 'Transition (ms)', 'fw' ),
				'value'           => '200',
				'desc'            => __( 'Animates the hover change. Milliseconds, e.g. 200.', 'fw' ),
				'dynamic_content' => false,
			),
			'custom_css' => array(
				'type'        => 'code-editor',
				'label'       => __( 'Custom CSS (advanced)', 'fw' ),
				'mode'        => 'css',
				'height'      => 140,
				'placeholder' => "{{SELECTOR}} {\n    /* your rules */\n}\n{{SELECTOR}}:hover {\n    /* hover rules */\n}",
				'desc'        => __( 'Use {{SELECTOR}} for this preset (becomes .colb-<name>).', 'fw' ),
			),
		);
	}

	/** Per-state fields (rendered once per state tab) — the border skin. */
	public static function state_options( $choices ) {
		return array(
			'border_style' => array(
				'type'    => 'select',
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
			$uri . '/static/css/border-presets.css',
			array( 'fw' ),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/static/js/border-presets.js',
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
