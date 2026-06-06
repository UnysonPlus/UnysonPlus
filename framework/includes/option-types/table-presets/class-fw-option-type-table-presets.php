<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Table Presets option type — a Border-Presets sibling for reusable table looks.
 * Each box is a named preset (-> .tbl-<slug>) with a live preview and SECTION tabs
 * (Header / Body / Striped / Hover / Footer / Caption) holding that section's skin.
 * Shared structure (padding, grid lines, outer frame, radius, shadow, font-size,
 * transition, custom CSS) sits outside the tabs.
 *
 * Saved value (one entry per preset):
 *   array(
 *     'id'                 => string,   // unique
 *     'preset_name'        => string,   // label -> .tbl-<slug>
 *     'cell_padding_y'     => array,    // unit-input
 *     'cell_padding_x'     => array,    // unit-input
 *     'grid_lines'         => string,   // none|horizontal|vertical|both
 *     'grid_style'         => string,   // solid|dashed|dotted
 *     'grid_width'         => array,    // unit-input
 *     'grid_color'         => array,    // compact picker { predefined, custom }
 *     'outer_border_style' => string,
 *     'outer_border_width' => array,    // unit-input
 *     'outer_border_color' => array,    // compact picker
 *     'border_radius'      => array,    // unit-input
 *     'outer_shadow'       => array,    // box-shadow value
 *     'cell_font_size'     => array,    // unit-input
 *     'transition'         => string,   // ms
 *     'custom_css'         => string,   // {{SELECTOR}}-aware
 *     'sections'           => array(
 *        'header'  => array( bg_color, text_color, font_weight, text_transform, border_style, border_width, border_color ),
 *        'body'    => array( bg_color, text_color ),
 *        'striped' => array( enabled, bg_color ),
 *        'hover'   => array( bg_color, text_color ),
 *        'footer'  => array( bg_color, text_color, font_weight, border_style, border_width, border_color ),
 *        'caption' => array( color, font_size, font_style ),
 *     ),
 *   )
 */
class FW_Option_Type_Table_Presets extends FW_Option_Type {

	public function _get_backend_width_type() {
		return 'full';
	}

	public function get_type() {
		return 'table-presets';
	}

	/** Section tabs, in order. */
	public static function sections() {
		return array(
			'header'  => __( 'Header', 'fw' ),
			'body'    => __( 'Body', 'fw' ),
			'striped' => __( 'Striped', 'fw' ),
			'hover'   => __( 'Row Hover', 'fw' ),
			'footer'  => __( 'Footer', 'fw' ),
			'caption' => __( 'Caption', 'fw' ),
		);
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'         => array(),
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

	protected static function unit_field( $label, $units = array( 'px', 'em', 'rem' ) ) {
		return array(
			'type'  => 'unit-input',
			'label' => $label,
			'units' => $units,
			'min'   => 0,
		);
	}

	protected static function border_style_field( $label ) {
		return array(
			'type'    => 'select',
			'label'   => $label,
			'choices' => array(
				''       => __( 'None', 'fw' ),
				'solid'  => __( 'Solid', 'fw' ),
				'dashed' => __( 'Dashed', 'fw' ),
				'dotted' => __( 'Dotted', 'fw' ),
				'double' => __( 'Double', 'fw' ),
			),
		);
	}

	protected static function font_weight_field() {
		return array(
			'type'    => 'select',
			'label'   => __( 'Font Weight', 'fw' ),
			'choices' => array(
				''       => __( 'Default', 'fw' ),
				'normal' => __( 'Normal', 'fw' ),
				'500'    => __( 'Medium (500)', 'fw' ),
				'600'    => __( 'Semibold (600)', 'fw' ),
				'700'    => __( 'Bold (700)', 'fw' ),
			),
		);
	}

	protected static function text_transform_field() {
		return array(
			'type'    => 'select',
			'label'   => __( 'Text Transform', 'fw' ),
			'choices' => array(
				''           => __( 'None', 'fw' ),
				'uppercase'  => __( 'UPPERCASE', 'fw' ),
				'capitalize' => __( 'Capitalize', 'fw' ),
				'lowercase'  => __( 'lowercase', 'fw' ),
			),
		);
	}

	/** Shared fields ABOVE the section tabs (identity). */
	public static function shared_top_options() {
		return array(
			'id' => array(
				'type' => 'unique',
			),
			'preset_name' => array(
				'type'            => 'text',
				'label'           => __( 'Table Style Name', 'fw' ),
				'desc'            => __( 'e.g. Striped, Bordered Grid, Minimal. Produces the class .tbl-<name>.', 'fw' ),
				'dynamic_content' => false,
			),
		);
	}

	/** Shared fields BELOW the section tabs (structure + transition + custom CSS). */
	public static function shared_bottom_options( $choices = array() ) {
		return array(
			'cell_padding_y' => self::unit_field( __( 'Cell Padding Y (top / bottom)', 'fw' ) ),
			'cell_padding_x' => self::unit_field( __( 'Cell Padding X (left / right)', 'fw' ) ),
			'grid_lines' => array(
				'type'    => 'short-select',
				'label'   => __( 'Grid Lines', 'fw' ),
				'choices' => array(
					'none'       => __( 'None', 'fw' ),
					'horizontal' => __( 'Horizontal (rows)', 'fw' ),
					'vertical'   => __( 'Vertical (columns)', 'fw' ),
					'both'       => __( 'Both', 'fw' ),
				),
				'value' => 'horizontal',
			),
			'grid_style' => self::border_style_field( __( 'Grid Line Style', 'fw' ) ),
			'grid_width' => self::unit_field( __( 'Grid Line Width', 'fw' ) ),
			'grid_color' => self::color_field( __( 'Grid Line Color', 'fw' ), $choices ),
			'outer_border_style' => self::border_style_field( __( 'Outer Border Style', 'fw' ) ),
			'outer_border_width' => self::unit_field( __( 'Outer Border Width', 'fw' ) ),
			'outer_border_color' => self::color_field( __( 'Outer Border Color', 'fw' ), $choices ),
			'border_radius' => self::unit_field( __( 'Corner Radius', 'fw' ), array( 'px', 'em', 'rem', '%' ) ),
			'outer_shadow' => array(
				'type'  => 'box-shadow',
				'label' => __( 'Outer Shadow', 'fw' ),
			),
			'cell_font_size' => self::unit_field( __( 'Cell Font Size', 'fw' ) ),
			'transition' => array(
				'type'            => 'short-text',
				'label'           => __( 'Hover Transition (ms)', 'fw' ),
				'value'           => '150',
				'desc'            => __( 'Animates the row hover change. Milliseconds, e.g. 150.', 'fw' ),
				'dynamic_content' => false,
			),
			'custom_css' => array(
				'type'        => 'code-editor',
				'label'       => __( 'Custom CSS (advanced)', 'fw' ),
				'mode'        => 'css',
				'height'      => 140,
				'placeholder' => "{{SELECTOR}} > table { /* … */ }\n{{SELECTOR}} thead th { /* … */ }",
				'desc'        => __( 'Use {{SELECTOR}} for this preset (becomes .tbl-<name>).', 'fw' ),
			),
		);
	}

	/** Per-section fields (each section tab renders its own set). */
	public static function section_options( $section, $choices ) {
		switch ( $section ) {
			case 'header':
				return array(
					'bg_color'       => self::color_field( __( 'Background', 'fw' ), $choices ),
					'text_color'     => self::color_field( __( 'Text Color', 'fw' ), $choices ),
					'font_weight'    => self::font_weight_field(),
					'text_transform' => self::text_transform_field(),
					'border_style'   => self::border_style_field( __( 'Bottom Border Style', 'fw' ) ),
					'border_width'   => self::unit_field( __( 'Bottom Border Width', 'fw' ) ),
					'border_color'   => self::color_field( __( 'Bottom Border Color', 'fw' ), $choices ),
				);
			case 'body':
				return array(
					'bg_color'   => self::color_field( __( 'Background', 'fw' ), $choices ),
					'text_color' => self::color_field( __( 'Text Color', 'fw' ), $choices ),
				);
			case 'striped':
				return array(
					'enabled' => array(
						'type'         => 'switch',
						'label'        => __( 'Zebra Stripes', 'fw' ),
						'right-choice' => array( 'value' => 'yes', 'label' => __( 'On', 'fw' ) ),
						'left-choice'  => array( 'value' => 'no', 'label' => __( 'Off', 'fw' ) ),
						'value'        => 'no',
					),
					'bg_color' => self::color_field( __( 'Stripe Background (every other row)', 'fw' ), $choices ),
				);
			case 'hover':
				return array(
					'bg_color'   => self::color_field( __( 'Hover Background', 'fw' ), $choices ),
					'text_color' => self::color_field( __( 'Hover Text Color', 'fw' ), $choices ),
				);
			case 'footer':
				return array(
					'bg_color'     => self::color_field( __( 'Background', 'fw' ), $choices ),
					'text_color'   => self::color_field( __( 'Text Color', 'fw' ), $choices ),
					'font_weight'  => self::font_weight_field(),
					'border_style' => self::border_style_field( __( 'Top Border Style', 'fw' ) ),
					'border_width' => self::unit_field( __( 'Top Border Width', 'fw' ) ),
					'border_color' => self::color_field( __( 'Top Border Color', 'fw' ), $choices ),
				);
			case 'caption':
				return array(
					'color'      => self::color_field( __( 'Caption Color', 'fw' ), $choices ),
					'font_size'  => self::unit_field( __( 'Caption Font Size', 'fw' ) ),
					'font_style' => array(
						'type'    => 'select',
						'label'   => __( 'Caption Style', 'fw' ),
						'choices' => array(
							'normal' => __( 'Normal', 'fw' ),
							'italic' => __( 'Italic', 'fw' ),
						),
						'value'   => 'normal',
					),
				);
		}
		return array();
	}

	/** Per-section fields grouped into display rows (same keys as section_options). */
	public static function section_rows( $section, $choices ) {
		$o = self::section_options( $section, $choices );
		switch ( $section ) {
			case 'header':
				return array(
					array( 'bg_color' => $o['bg_color'], 'text_color' => $o['text_color'] ),
					array( 'font_weight' => $o['font_weight'], 'text_transform' => $o['text_transform'] ),
					array( 'border_style' => $o['border_style'], 'border_width' => $o['border_width'] ),
					array( 'border_color' => $o['border_color'] ),
				);
			case 'footer':
				return array(
					array( 'bg_color' => $o['bg_color'], 'text_color' => $o['text_color'] ),
					array( 'font_weight' => $o['font_weight'] ),
					array( 'border_style' => $o['border_style'], 'border_width' => $o['border_width'] ),
					array( 'border_color' => $o['border_color'] ),
				);
			case 'striped':
				return array(
					array( 'enabled' => $o['enabled'] ),
					array( 'bg_color' => $o['bg_color'] ),
				);
			case 'caption':
				return array(
					array( 'color' => $o['color'], 'font_size' => $o['font_size'] ),
					array( 'font_style' => $o['font_style'] ),
				);
			default: // body, hover
				return array(
					array( 'bg_color' => $o['bg_color'], 'text_color' => $o['text_color'] ),
				);
		}
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
		$all = array_merge( self::shared_top_options(), self::shared_bottom_options( $choices ) );
		foreach ( array_keys( self::sections() ) as $section ) {
			$all = array_merge( $all, self::section_options( $section, $choices ) );
		}
		return $all;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		fw()->backend->enqueue_options_static( $this->all_sub_options( $option ) );

		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/static/css/table-presets.css',
			array( 'fw' ),
			fw()->manifest->get_version()
		);

		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/static/js/table-presets.js',
			array( 'fw-events', 'jquery-ui-sortable', 'fw' ),
			fw()->manifest->get_version(),
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$choices  = $this->choices( $option );
		$sections = self::sections();

		$section_rows = array();
		foreach ( array_keys( $sections ) as $section ) {
			$section_rows[ $section ] = self::section_rows( $section, $choices );
		}

		$option['_tp'] = array(
			'shared_top'    => self::shared_top_options(),
			'shared_bottom' => self::shared_bottom_options( $choices ),
			'sections'      => $sections,
			'section_rows'  => $section_rows,
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

		$choices      = $this->choices( $option );
		$shared_opts  = fw_extract_only_options( array_merge( self::shared_top_options(), self::shared_bottom_options( $choices ) ) );
		$section_keys = array_keys( self::sections() );

		$section_opts = array();
		foreach ( $section_keys as $section ) {
			$section_opts[ $section ] = fw_extract_only_options( self::section_options( $section, $choices ) );
		}

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

			$entry['sections'] = array();
			$in_sections = isset( $box_input['sections'] ) && is_array( $box_input['sections'] ) ? $box_input['sections'] : array();
			foreach ( $section_keys as $section ) {
				$src     = isset( $in_sections[ $section ] ) && is_array( $in_sections[ $section ] ) ? $in_sections[ $section ] : array();
				$sec_val = array();
				foreach ( $section_opts[ $section ] as $opt_id => $opt ) {
					$sec_val[ $opt_id ] = fw()->backend->option_type( $opt['type'] )->get_value_from_input(
						$opt,
						isset( $src[ $opt_id ] ) ? $src[ $opt_id ] : null
					);
				}
				$entry['sections'][ $section ] = $sec_val;
			}

			$values[] = $entry;
		}

		return $values;
	}
}
