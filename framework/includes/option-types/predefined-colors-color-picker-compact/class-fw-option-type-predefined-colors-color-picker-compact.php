<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Predefined Colors Color Picker (Compact)
 *
 * Compact sibling of `predefined-colors-color-picker`. The wide hybrid lays
 * out every swatch inline — fine for a single Theme Settings color field but
 * visually deafening for a shortcode Styling tab with 10+ color rows. This
 * variant collapses the preset half to a single dropdown trigger that opens
 * an overlay panel of options, paired with the same custom color picker.
 *
 * Each option row shows BOTH a colored swatch and the preset's name painted
 * in that color — one unified design (no `display` variant; previous
 * `'blocks'` / `'text'` modes were superseded in plugin 2.7.127). Light
 * colors (luminance > 0.95) get a `#dbdbdb` chip behind the label so
 * white-on-white doesn't disappear.
 *
 * Saved value is identical to the wide hybrid:
 *     array( 'predefined' => 'class-name', 'custom' => '#hex' )
 * — mutually exclusive halves. The `predefined` half stores a CSS class name
 * directly (e.g. `'text-red'`, `'bg-light-blue'`) so the consuming shortcode
 * can emit it verbatim as `class="..."` without a mapping step. The `custom`
 * half stores a hex / rgba string the consumer emits as inline `style=...`.
 *
 * Choices shape (richer than the wide hybrid because we need three pieces
 * per entry — the saved class, the human label, and the preview color):
 *
 *     'choices' => array(
 *         'text-red'  => array( 'label' => 'Red',  'color' => '#d9534f' ),
 *         'text-blue' => array( 'label' => 'Blue', 'color' => '#3f51b5' ),
 *         // ...
 *     )
 */

if ( ! class_exists( 'FW_Option_Type_Predefined_Colors_Color_Picker_Compact' ) ) :

class FW_Option_Type_Predefined_Colors_Color_Picker_Compact extends FW_Option_Type {

	public function get_type() {
		return 'predefined-colors-color-picker-compact';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'picker'  => 'color-picker',    // or 'rgba-color-picker' for alpha
			'value'   => array(
				'predefined' => '',
				'custom'     => '',
			),
			'choices' => array(
				// 'class-name' => array( 'label' => 'Human Label', 'color' => '#hex' )
			),
		);
	}

	/**
	 * Perceived-luminance check. Returns true when $hex is light enough
	 * that a colored label rendered ON THE PANEL'S WHITE BACKGROUND would
	 * be hard to read — i.e. the label needs a darker chip backdrop.
	 *
	 * Threshold 0.95 matches the plugin's existing `sc_color_is_light()`
	 * convention (Styling-helper changelog 2.7.70). Accepts `#RGB` or
	 * `#RRGGBB`; returns false for anything else (rgba(), named colors,
	 * empty, etc.) so unknown formats default to "no chip" — same look
	 * as today.
	 */
	private static function _color_is_light( $hex ) {
		if ( ! is_string( $hex ) ) {
			return false;
		}
		$hex = ltrim( trim( $hex ), '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
			return false;
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		$luminance = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b ) / 255;
		return $luminance > 0.95;
	}

	/**
	 * @internal
	 *
	 * Mirrors the wide hybrid's enqueue block — every dependency loaded
	 * explicitly because builder / popup contexts don't auto-enqueue
	 * children the way Theme Settings does.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$fw_uri = fw_get_framework_directory_uri();
		$uri    = $fw_uri . '/includes/option-types/' . $this->get_type() . '/static';
		$ver    = fw()->manifest->get_version();

		// The 'custom' half embeds either a color-picker or an rgba-color-picker — both are
		// now Coloris. Delegate to the option types' OWN enqueue so the correct Coloris assets
		// + shared init + preset swatches load. Replaces the old direct enqueue of the Iris /
		// wp-color-picker-alpha scripts (whose init wpColorPicker-ified every rgba input).
		fw()->backend->option_type( 'color-picker' )->enqueue_static();
		fw()->backend->option_type( 'rgba-color-picker' )->enqueue_static();

		// This option type's own assets
		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array(),
			$ver
		);
		wp_enqueue_script(
			'fw-option-' . $this->get_type(),
			$uri . '/js/scripts.js',
			array( 'fw-events', 'jquery' ),
			$ver,
			true
		);
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'auto'; // auto|fixed|full
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {

		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );

		$picker = isset( $option['picker'] ) && $option['picker'] === 'rgba-color-picker'
			? 'rgba-color-picker'
			: 'color-picker';

		$parent_name = $option['attr']['name'];
		$parent_id   = $option['attr']['id'];

		// Back-compat: when a shortcode that previously used a plain <select>
		// (`sc_color_field()`) is migrated to this compact picker, existing
		// saved instances still hold a flat string like 'text-red' or
		// 'bg-light-blue' instead of the new {predefined, custom} array. Treat
		// the legacy string as the `predefined` half so the trigger paints
		// with the right preset on first load. The next save normalises it
		// to the array shape via `_get_value_from_input()`.
		$saved = isset( $data['value'] ) ? $data['value'] : array();
		if ( is_string( $saved ) ) {
			$saved = array( 'predefined' => $saved, 'custom' => '' );
		}

		// Resolve the current saved values
		$current_predefined = isset( $saved['predefined'] ) ? (string) $saved['predefined'] : '';
		$current_custom     = isset( $saved['custom'] )     ? (string) $saved['custom']     : '';

		// Locate the matching choice (if any) so the trigger renders with
		// the right label + preview color on first paint
		$choices       = is_array( $option['choices'] ) ? $option['choices'] : array();
		$picked_label  = '';
		$picked_color  = '';
		if ( $current_predefined !== '' && isset( $choices[ $current_predefined ] ) ) {
			$picked_label = (string) ( $choices[ $current_predefined ]['label'] ?? '' );
			$picked_color = (string) ( $choices[ $current_predefined ]['color'] ?? '' );
		}
		$trigger_label_html = $picked_label !== ''
			? esc_html( $picked_label )
			: esc_html__( '— Select —', 'fw' );
		$trigger_color_css  = $picked_color !== ''
			? esc_attr( $picked_color )
			: 'transparent';
		$trigger_is_light   = $picked_color !== '' && self::_color_is_light( $picked_color );
		$trigger_class      = 'pccpc__trigger' . ( $trigger_is_light ? ' pccpc__trigger--light' : '' );

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<div class="pccpc__row">
				<input
					type="hidden"
					class="pccpc__preset-input"
					name="<?php echo esc_attr( $parent_name . '[predefined]' ); ?>"
					value="<?php echo esc_attr( $current_predefined ); ?>"
				/>

				<button
					type="button"
					class="<?php echo esc_attr( $trigger_class ); ?>"
					aria-haspopup="listbox"
					aria-expanded="false"
					style="--pccpc-color: <?php echo $trigger_color_css; ?>;"
				>
					<span class="pccpc__trigger-swatch" aria-hidden="true"></span>
					<span class="pccpc__trigger-label"><?php echo $trigger_label_html; ?></span>
					<span class="pccpc__caret" aria-hidden="true">▾</span>
				</button>

				<input
					type="radio"
					class="pccpc__radio"
					aria-label="<?php echo esc_attr__( 'Use custom color', 'fw' ); ?>"
					<?php echo $current_custom !== '' ? 'checked' : ''; ?>
				/>

				<div class="pccpc__custom">
					<?php
					echo fw()->backend->option_type( $picker )->render(
						'custom',
						array(
							'type'     => $picker,
							'value'    => $current_custom,
							'palettes' => '',
							'attr'     => array(
								'class'        => 'pccpc__custom-input',
								'autocomplete' => 'off',
							),
						),
						array(
							'value'       => $current_custom,
							'id_prefix'   => $parent_id . '-',
							'name_prefix' => $parent_name,
						)
					);
					?>
				</div>
			</div>

			<div class="pccpc__panel" role="listbox" hidden>
				<button
					type="button"
					class="pccpc__option pccpc__option--empty<?php echo $current_predefined === '' ? ' is-selected' : ''; ?>"
					data-class=""
					data-color=""
					data-label=""
					data-light="0"
					role="option"
					aria-selected="<?php echo $current_predefined === '' ? 'true' : 'false'; ?>"
				>
					<?php echo esc_html__( '— None —', 'fw' ); ?>
				</button>
				<?php foreach ( $choices as $class_name => $entry ) :
					$label     = isset( $entry['label'] ) ? (string) $entry['label'] : (string) $class_name;
					$color     = isset( $entry['color'] ) ? (string) $entry['color'] : '';
					$is_picked = ( $current_predefined !== '' && $current_predefined === (string) $class_name );
					$is_light  = $color !== '' && self::_color_is_light( $color );
					$opt_class = 'pccpc__option'
						. ( $is_picked ? ' is-selected' : '' )
						. ( $is_light  ? ' pccpc__option--light' : '' );
				?>
					<button
						type="button"
						class="<?php echo esc_attr( $opt_class ); ?>"
						data-class="<?php echo esc_attr( $class_name ); ?>"
						data-color="<?php echo esc_attr( $color ); ?>"
						data-label="<?php echo esc_attr( $label ); ?>"
						data-light="<?php echo $is_light ? '1' : '0'; ?>"
						role="option"
						aria-selected="<?php echo $is_picked ? 'true' : 'false'; ?>"
						style="--pccpc-color: <?php echo esc_attr( $color !== '' ? $color : 'transparent' ); ?>;"
					>
						<span class="pccpc__option-swatch" aria-hidden="true"></span>
						<span class="pccpc__option-label"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		// Legacy-string back-compat: same shape rescue as in _render().
		// A shortcode migrating from `sc_color_field()` may carry a plain
		// string default like `'text-red'`; coerce it to the new array
		// shape on first save so subsequent loads round-trip cleanly.
		if ( is_string( $input_value ) ) {
			return array( 'predefined' => $input_value, 'custom' => '' );
		}
		if ( ! is_array( $input_value ) ) {
			$fallback = isset( $option['value'] ) ? $option['value'] : array();
			if ( is_string( $fallback ) ) {
				$fallback = array( 'predefined' => $fallback, 'custom' => '' );
			}
			return $fallback;
		}
		return array(
			'predefined' => isset( $input_value['predefined'] ) ? (string) $input_value['predefined'] : '',
			'custom'     => isset( $input_value['custom'] )     ? (string) $input_value['custom']     : '',
		);
	}
}

endif; // class_exists guard
