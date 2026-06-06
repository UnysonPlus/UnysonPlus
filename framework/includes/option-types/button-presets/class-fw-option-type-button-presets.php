<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Button Presets option type.
 *
 * A specialised addable-box: each box is a reusable button preset with a live,
 * auto-updating preview, a collapsible header, and a Background-Pro-style set of
 * interaction-state TABS (Default / Hover / Active / Focus / Disabled). Each
 * state tab holds everything style-related (background, text, transform,
 * spacing, border, radius, box-shadow); shared fields (name, font, transition,
 * custom CSS) sit outside the tabs.
 *
 * Colors use the predefined-colors compact picker (palette OR custom value);
 * the box-shadow uses the reusable box-shadow option type.
 *
 * Saved value (one entry per preset):
 *   array(
 *     'id'         => string,     // class suffix -> .btn-<id>
 *     'color_name' => string,     // label, shown in the Styling dropdown
 *     'font'       => array,      // typography-v2 value (IDENTITY only: family/
 *                                 // weight/letter-spacing/style — size + line-
 *                                 // height live on the Size axis, .btn-<slug>)
 *     'transition' => string,     // ms (shared, animates all states)
 *     'custom_css' => string,     // {{SELECTOR}}-aware CSS
 *     'states'     => array(      // SKIN per state — no padding/font-size/radius
 *       'default'|'hover'|'active'|'focus'|'disabled' => array(
 *          'bg_color', 'text_color', 'border_color'  // compact picker {predefined,custom}
 *          'text_transform', 'border_style',         // scalars
 *          'border_width', // unit-input {value,unit}
 *          'box_shadow',   // box-shadow value
 *       ),
 *     ),
 *   )
 *
 * Back-compat: presets saved before tabs (flat normal_ / hover_ keys, no
 * 'states') are migrated into states.default / states.hover on read.
 */
class FW_Option_Type_Button_Presets extends FW_Option_Type {

	public function _get_backend_width_type() {
		return 'full';
	}

	public function get_type() {
		return 'button-presets';
	}

	/** Interaction states, in tab order. */
	public static function states() {
		return array(
			'default'  => __( 'Default', 'fw' ),
			'hover'    => __( 'Hover', 'fw' ),
			'active'   => __( 'Active', 'fw' ),
			'focus'    => __( 'Focus', 'fw' ),
			'disabled' => __( 'Disabled', 'fw' ),
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

	/** Compact color picker field wired to the supplied palette choices. */
	protected static function color_field( $label, $choices ) {
		return array(
			'type'    => 'predefined-colors-color-picker-compact',
			'label'   => $label,
			'choices' => is_array( $choices ) ? $choices : array(),
		);
	}

	/** Shared fields shown ABOVE the state tabs (identity + font). */
	public static function shared_top_options() {
		return array(
			'id' => array(
				'type' => 'unique',
			),
			'color_name' => array(
				'type'            => 'text',
				'label'           => __( 'Button Name', 'fw' ),
				'desc'            => __( 'e.g. Primary, Secondary, Ghost. Produces the class .btn-<id>.', 'fw' ),
				'dynamic_content' => false,
			),
			'font' => array(
				'type'       => 'typography-v2',
				'label'      => __( 'Font', 'fw' ),
				// Identity only — family / weight / letter-spacing / style.
				// size + line-height belong to the SIZE axis (Theme Settings →
				// Buttons → Sizes), so they're hidden here; Color and
				// Script/subset (variation) are hidden per design.
				'components' => array(
					'family'         => true,
					'size'           => false,
					'line-height'    => false,
					'letter-spacing' => true,
					'weight'         => true,
					'style'          => true,
					'color'          => false,
					'variation'      => false,
				),
			),
		);
	}

	/** Shared fields shown BELOW the state tabs (transition + custom CSS). */
	public static function shared_bottom_options() {
		return array(
			'transition' => array(
				'type'            => 'short-text',
				'label'           => __( 'Transition (ms)', 'fw' ),
				'value'           => '250',
				'desc'            => __( 'Animates all state changes (hover/active/focus). Milliseconds, e.g. 250.', 'fw' ),
				'dynamic_content' => false,
			),
			'custom_css' => array(
				'type'        => 'code-editor',
				'label'       => __( 'Custom CSS (advanced)', 'fw' ),
				'mode'        => 'css',
				'height'      => 140,
				'placeholder' => "{{SELECTOR}} {\n    /* your rules */\n}\n{{SELECTOR}}:hover {\n    letter-spacing: 1px;\n}",
				'desc'        => __( 'Use {{SELECTOR}} for this preset (becomes .btn-<id>).', 'fw' ),
			),
		);
	}

	/**
	 * Per-state fields (rendered once per state tab). These are the SKIN
	 * properties only — colors, border, shadow, transform. Dimensional
	 * properties (padding, font-size, border-radius) live on the SIZE axis
	 * (Theme Settings → Buttons → Sizes), composed as `.btn .btn-primary .btn-lg`.
	 */
	public static function state_options( $choices ) {
		return array(
			'bg_color'       => self::color_field( __( 'Background Color', 'fw' ), $choices ),
			'text_color'     => self::color_field( __( 'Text Color', 'fw' ), $choices ),
			'gradient'       => array(
				'type'  => 'gradient-v2',
				'label' => __( 'Background Gradient', 'fw' ),
				'desc'  => __( 'Optional. Layers over the background color (which stays as a fallback). Leave blank for none. Note: gradients do not fade on hover.', 'fw' ),
			),
			'text_transform' => array(
				'type'    => 'short-select',
				'label'   => __( 'Text transform', 'fw' ),
				'choices' => array(
					''           => __( 'Default', 'fw' ),
					'none'       => __( 'None', 'fw' ),
					'uppercase'  => 'UPPERCASE',
					'lowercase'  => 'lowercase',
					'capitalize' => 'Capitalize',
				),
			),
			'border_color' => self::color_field( __( 'Border Color', 'fw' ), $choices ),
			'border_width' => array(
				'type'  => 'unit-input',
				'label' => __( 'Border Width', 'fw' ),
				'units' => array( 'px', 'em', 'rem' ),
				'min'   => 0,
			),
			'border_style' => array(
				'type'    => 'select',
				'label'   => __( 'Border Style', 'fw' ),
				'choices' => array(
					''       => __( 'Default', 'fw' ),
					'none'   => __( 'None', 'fw' ),
					'solid'  => __( 'Solid', 'fw' ),
					'dashed' => __( 'Dashed', 'fw' ),
					'dotted' => __( 'Dotted', 'fw' ),
				),
			),
			'box_shadow' => array(
				'type'  => 'box-shadow',
				'label' => __( 'Box Shadow', 'fw' ),
			),
		);
	}

	/**
	 * Per-state fields grouped into display rows. Each inner array is one row;
	 * a 2-field row renders as two side-by-side columns. Same field keys/objects
	 * as state_options() so name prefixes and parsing are unaffected.
	 */
	public static function state_option_rows( $choices ) {
		$o = self::state_options( $choices );
		return array(
			array( 'bg_color'     => $o['bg_color'],     'text_color'   => $o['text_color'] ),
			array( 'gradient'     => $o['gradient'] ),
			array( 'text_transform' => $o['text_transform'], 'border_style' => $o['border_style'] ),
			array( 'border_color' => $o['border_color'], 'border_width' => $o['border_width'] ),
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
		// Enqueue every nested sub-option's static once (handles dedupe by handle),
		// so compact pickers, typography-v2, spacing, code-editor, box-shadow, etc.
		// initialise inside our boxes — including dynamically added ones.
		fw()->backend->enqueue_options_static( $this->all_sub_options( $option ) );

		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/static/css/button-presets.css',
			array( 'fw' ),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/static/js/button-presets.js',
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

		// Stash resolved schema on the option for the views.
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

		$choices       = $this->choices( $option );
		$shared_opts   = fw_extract_only_options( array_merge( self::shared_top_options(), self::shared_bottom_options() ) );
		$state_opts    = fw_extract_only_options( self::state_options( $choices ) );
		$state_keys    = array_keys( self::states() );

		$values = array();

		foreach ( $input_value as $box_input ) {
			if ( ! is_array( $box_input ) ) {
				continue; // skip the '~' sentinel
			}

			$box_input = self::maybe_migrate_flat( $box_input, $state_keys );

			$entry = array();

			// shared fields
			foreach ( $shared_opts as $opt_id => $opt ) {
				$entry[ $opt_id ] = fw()->backend->option_type( $opt['type'] )->get_value_from_input(
					$opt,
					isset( $box_input[ $opt_id ] ) ? $box_input[ $opt_id ] : null
				);
			}

			// per-state fields
			$entry['states'] = array();
			$in_states = isset( $box_input['states'] ) && is_array( $box_input['states'] ) ? $box_input['states'] : array();
			foreach ( $state_keys as $state ) {
				$src = isset( $in_states[ $state ] ) && is_array( $in_states[ $state ] ) ? $in_states[ $state ] : array();
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

	/**
	 * Migrate a legacy flat preset (normal_ / hover_ keys, no 'states') into the
	 * nested 'states' shape so old saves keep working on first load.
	 */
	public static function maybe_migrate_flat( $box, $state_keys = array( 'default', 'hover', 'active', 'focus', 'disabled' ) ) {
		if ( isset( $box['states'] ) && is_array( $box['states'] ) ) {
			return $box; // already new shape
		}

		$map = array(
			'default'  => 'normal',
			'hover'    => 'hover',
			'active'   => 'active',
			'focus'    => 'focus',
			'disabled' => 'disabled',
		);

		$has_flat = false;
		foreach ( $map as $prefix ) {
			if ( isset( $box[ $prefix . '_text_color' ] ) || isset( $box[ $prefix . '_bg_color' ] ) || isset( $box[ $prefix . '_border_color' ] ) ) {
				$has_flat = true;
				break;
			}
		}
		if ( ! $has_flat ) {
			return $box;
		}

		$box['states'] = array();
		foreach ( $map as $state => $prefix ) {
			$box['states'][ $state ] = array(
				'text_color'   => isset( $box[ $prefix . '_text_color' ] )   ? $box[ $prefix . '_text_color' ]   : '',
				'bg_color'     => isset( $box[ $prefix . '_bg_color' ] )     ? $box[ $prefix . '_bg_color' ]     : '',
				'border_color' => isset( $box[ $prefix . '_border_color' ] ) ? $box[ $prefix . '_border_color' ] : '',
			);
		}
		return $box;
	}
}
