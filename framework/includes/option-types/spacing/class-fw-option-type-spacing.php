<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Spacing
 *
 * Composite spacing option (Margin + Padding) with a plus-cross layout.
 * Each section has an "all sides" select plus a 4-quadrant cross of
 * Top / Right / Bottom / Left selects positioned to match their CSS axis.
 *
 * `mode` attribute scopes the widget:
 *   'both'    (default) — both margin and padding columns
 *   'margin'  — margin column only (padding subtree stays at defaults on save)
 *   'padding' — padding column only
 *
 * The saved value is a nested array of Bootstrap utility class names
 * (e.g. 'm-3', 'pt-2'). Choices are generated from the type's own scale,
 * which defaults to Bootstrap 5's $spacers. Theme / plugin code can replace
 * the scale via the `fw_option_type_spacing_scale` filter — letting the
 * type stay completely self-contained (no calls into the shortcodes
 * extension or any theme-specific preset getter) while still picking up
 * a site-wide custom scale wherever one is wired in.
 *
 * An empty `advanced` slot in the value tree is reserved for v2
 * (e.g. per-breakpoint values) — adding it later does not require a
 * schema migration. Same pattern as background-pro.
 */

if ( ! class_exists( 'Fw_Option_Type_Spacing' ) ) :

class Fw_Option_Type_Spacing extends FW_Option_Type {

	const SLOTS = array( 'all', 'top', 'right', 'bottom', 'left' );

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
	 * Slot choices keyed by Bootstrap utility class name.
	 * Returns: [ '' => 'Default', '{prefix}-{slug}' => '{name} ({size})', ... ]
	 */
	public function get_choices( $section, $slot ) {
		$prefix = $this->get_prefix( $section, $slot );
		$out    = array( '' => __( 'Default', 'unysonplus' ) );

		foreach ( $this->get_scale() as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['name'] ) ) { continue; }
			$slug = strtolower( $this->sanitize_class( $entry['name'] ) );
			if ( $slug === '' ) { continue; }
			$size  = isset( $entry['size'] ) ? $entry['size'] : '';
			$label = $entry['name'] . ( $size !== '' ? ' (' . $size . ')' : '' );
			$out[ $prefix . '-' . $slug ] = $label;
		}
		return $out;
	}

	/**
	 * Render one slot's <select> by delegating to the short-select option type.
	 * Mirrors the sub-control pattern background-pro uses (no FW label wrapper —
	 * the view supplies its own).
	 */
	public function render_slot( $section, $slot, $value, $id_prefix, $name_prefix ) {
		$nested_id_prefix   = $id_prefix . $section . '-';
		$nested_name_prefix = $name_prefix . '[' . $section . ']';
		$slot_value         = isset( $value[ $section ][ $slot ] ) ? (string) $value[ $section ][ $slot ] : '';

		return fw()->backend->option_type( 'short-select' )->render(
			$slot,
			array(
				'type'    => 'short-select',
				'label'   => false,
				'desc'    => false,
				'value'   => $slot_value,
				'choices' => $this->get_choices( $section, $slot ),
				'attr'    => array( 'class' => 'sc-spacing fw-option-spacing-slot' ),
			),
			array(
				'id_prefix'   => $nested_id_prefix,
				'name_prefix' => $nested_name_prefix,
				'value'       => $slot_value,
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'mode'  => 'both',
			'value' => array(
				'margin'   => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
				'padding'  => array( 'all' => '', 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' ),
				'advanced' => array(), // reserved for v2 (e.g. per-breakpoint values)
			),
		);
	}

	/**
	 * @internal
	 *
	 * Pull in short-select's assets (we render 5 or 10 of them depending on mode)
	 * plus our own grid styling. Same explicit-enqueue pattern as background-pro
	 * so builder / customizer contexts don't miss the sub-control assets.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );
		$ver = fw()->manifest->get_version();

		fw()->backend->option_type( 'short-select' )->enqueue_static();

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
	 *
	 * Inline ob_start pattern (matches background-pro) — the markup is short
	 * enough that pulling it into a separate view.php buys us no readability,
	 * and keeps the option type's behaviour observable from one file.
	 */
	protected function _render( $id, $option, $data ) {
		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );

		// Resolve current value. prepare() guarantees $data['value'] is set —
		// either the saved value or the option's default. Merge defaults
		// section-by-section so a partial save still has every slot key.
		$defaults = $this->_get_defaults();
		$saved    = is_array( $data['value'] ) ? $data['value'] : array();
		$value    = array();
		foreach ( array( 'margin', 'padding' ) as $section ) {
			$section_saved      = isset( $saved[ $section ] ) && is_array( $saved[ $section ] ) ? $saved[ $section ] : array();
			$value[ $section ] = array_merge( $defaults['value'][ $section ], $section_saved );
		}

		$mode = isset( $option['mode'] ) ? (string) $option['mode'] : 'both';
		if ( ! in_array( $mode, array( 'both', 'margin', 'padding' ), true ) ) {
			$mode = 'both';
		}

		$wrapper_attr['class'] = trim(
			( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' )
			. ' fw-option-type-spacing fw-option-type-spacing--mode-' . $mode
		);

		$id_prefix   = $option['attr']['id'] . '-';
		$name_prefix = $option['attr']['name'];

		$sections = array(
			'margin'  => __( 'Margin',  'unysonplus' ),
			'padding' => __( 'Padding', 'unysonplus' ),
		);
		$slot_labels = array(
			'top'    => __( 'Top',    'unysonplus' ),
			'right'  => __( 'Right',  'unysonplus' ),
			'bottom' => __( 'Bottom', 'unysonplus' ),
			'left'   => __( 'Left',   'unysonplus' ),
		);

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<div class="fw-option-spacing-cols">
				<?php foreach ( $sections as $section => $section_label ) :
					if ( $mode === 'margin'  && $section !== 'margin' )  { continue; }
					if ( $mode === 'padding' && $section !== 'padding' ) { continue; }
					?>
					<div class="fw-option-spacing-col fw-option-spacing-col--<?php echo esc_attr( $section ); ?>">
						<div class="fw-option-spacing-col-title"><?php echo esc_html( $section_label ); ?></div>

						<div class="fw-option-spacing-all">
							<div class="fw-option-spacing-slot-label">
								<?php esc_html_e( 'All Sides', 'unysonplus' ); ?>
							</div>
							<div class="fw-option-spacing-slot-control">
								<?php echo $this->render_slot( $section, 'all', $value, $id_prefix, $name_prefix ); ?>
							</div>
							<p class="fw-option-spacing-all-hint">
								<?php esc_html_e( 'Applies to all sides; overridden by per-side values when set.', 'unysonplus' ); ?>
							</p>
						</div>

						<div class="fw-option-spacing-cross">
							<?php foreach ( $slot_labels as $slot => $slot_label ) : ?>
								<div class="fw-option-spacing-slot fw-option-spacing-slot--<?php echo esc_attr( $slot ); ?>">
									<div class="fw-option-spacing-slot-label"><?php echo esc_html( $slot_label ); ?></div>
									<div class="fw-option-spacing-slot-control">
										<?php echo $this->render_slot( $section, $slot, $value, $id_prefix, $name_prefix ); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @internal
	 *
	 * Per-slot value extraction. Honours `mode` — when set to 'margin' or
	 * 'padding', the inactive subtree is force-reset to defaults so a stale
	 * value can't leak in via a tampered POST.
	 *
	 * When `$input_value` is not an array (FW passes `null` whenever the
	 * option key is missing from the submitted form — notably the
	 * page-builder's "re-save existing atts" path in
	 * `class-page-builder-simple-item.php::get_atts_after_create` which
	 * calls `fw_get_options_values_from_input( $options, array() )`),
	 * fall back to `$option['value']`. FW's `get_value_from_input` has
	 * already merged the previously-saved value into `$option['value']`
	 * by that point, so returning it preserves the existing pick across
	 * post Update / re-save cycles. Returning `$defaults['value']` here
	 * would silently reset every shortcode's spacing to empty whenever
	 * the post is re-saved.
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

		foreach ( $sections as $section ) {
			if ( ! isset( $input_value[ $section ] ) || ! is_array( $input_value[ $section ] ) ) {
				continue;
			}
			foreach ( self::SLOTS as $slot ) {
				if ( ! isset( $input_value[ $section ][ $slot ] ) ) { continue; }
				// Defensive class-name sanitization: only [A-Za-z0-9_-] survives.
				// The legit values are Bootstrap utility classes (e.g. 'm-3'),
				// so any other character is a tampered submission.
				$val = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $input_value[ $section ][ $slot ] );
				$out[ $section ][ $slot ] = $val;
			}
		}

		return $out;
	}
}

FW_Option_Type::register( 'Fw_Option_Type_Spacing' );

endif; // class_exists guard
