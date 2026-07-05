<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Responsive
 *
 * A generic per-device WRAPPER around a single inner control (image-picker,
 * select, short-select, …). It renders that control once per device layer —
 * Phone (base) / Tablet (md) / Desktop (lg) — behind the shared device-tabs
 * switcher, and stores one value per layer:
 *
 *     array( 'base' => <val>, 'md' => <val|''>, 'lg' => <val|''> )
 *
 * Mobile-first / Bootstrap-native, exactly like the `spacing` option type this
 * is modelled on: `base` applies at all widths; an empty `md`/`lg` means
 * "inherit the smaller layer", so nothing is emitted for it. The consumer
 * (a shortcode view) reads the three raw tokens and maps them to whatever
 * breakpoint-infixed utility class fits its context (e.g. justify-content-md-*
 * vs align-items-md-*), which is why this type stays token-agnostic — it never
 * bakes a class name, it just holds a value per device.
 *
 * Usage (in an options array):
 *
 *     'content_h' => array(
 *         'type'  => 'responsive',
 *         'label' => __( 'Content Alignment', 'fw' ),
 *         'value' => array( 'base' => 'default', 'md' => '', 'lg' => '' ),
 *         'inner' => array(
 *             'type'    => 'image-picker',
 *             'choices' => $halign_choices,
 *         ),
 *     ),
 *
 * Migration note: converting an existing single-value option to this type is a
 * value-shape change. `normalize_value()` tolerates a legacy scalar (folds it
 * into `base`) so a pre-existing saved value never triggers a PHP error or a
 * blank modal, and the first save persists the new `{base,md,lg}` shape.
 *
 * The device switcher reuses framework/includes/device-tabs.php +
 * framework/static/js/fw-device-tabs.js (synced to the builder's global device
 * toggle). Panel show/hide is handled generically by fw-device-tabs.css, so this
 * type ships almost no CSS of its own.
 */

if ( ! class_exists( 'Fw_Option_Type_Responsive' ) ) :

class Fw_Option_Type_Responsive extends FW_Option_Type {

	/**
	 * Per-device layers. key = device-tabs panel token; the Bootstrap infix a
	 * consumer would use is '' (base), 'md', 'lg'.
	 */
	const DEVICES = array( 'base', 'md', 'lg' );

	public function get_type() {
		return 'responsive';
	}

	/**
	 * Resolve the inner control config from the option definition, forcing off
	 * the inner label/desc (the wrapper supplies the option label; the inner is
	 * just the control repeated per device).
	 */
	private function inner_option( $option ) {
		$inner = ( isset( $option['inner'] ) && is_array( $option['inner'] ) )
			? $option['inner']
			: array( 'type' => 'short-select', 'choices' => array() );

		if ( empty( $inner['type'] ) ) {
			$inner['type'] = 'short-select';
		}
		$inner['label'] = false;
		$inner['desc']  = false;

		return $inner;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value' => array( 'base' => '', 'md' => '', 'lg' => '' ),
			'inner' => array( 'type' => 'short-select', 'choices' => array() ),
		);
	}

	/**
	 * Merge a saved value over the {base,md,lg} shape. Tolerates a legacy scalar
	 * (folds it into `base`) so converting an existing option to this type never
	 * errors on a pre-existing value.
	 */
	private function normalize_value( $saved ) {
		$out = array( 'base' => '', 'md' => '', 'lg' => '' );

		if ( is_array( $saved ) ) {
			foreach ( self::DEVICES as $k ) {
				if ( isset( $saved[ $k ] ) && ! is_array( $saved[ $k ] ) ) {
					$out[ $k ] = $saved[ $k ];
				}
			}
		} elseif ( is_string( $saved ) && $saved !== '' ) {
			$out['base'] = $saved; // legacy scalar → base layer
		}

		return $out;
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );

		$value = $this->normalize_value( $data['value'] );
		$inner = $this->inner_option( $option );

		$wrapper_attr['class'] = trim(
			( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' )
			. ' fw-option-type-responsive fw-device-host'
		);

		$id_prefix   = $option['attr']['id'] . '-';
		$name_prefix = $option['attr']['name'];

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<div class="fw-option-responsive-head fw-device-head">
				<?php echo fw_render_device_tabs( $option['attr']['id'] ); ?>
			</div>
			<div class="fw-option-responsive-panels">
				<?php foreach ( self::DEVICES as $key ) :
					$panel_val = isset( $value[ $key ] ) ? $value[ $key ] : '';
					?>
					<div class="fw-option-responsive-panel<?php echo ( $key === 'base' ) ? ' is-active' : ''; ?>" data-fw-device-panel="<?php echo esc_attr( $key ); ?>">
						<?php echo fw()->backend->option_type( $inner['type'] )->render(
							$key,
							array_merge( $inner, array( 'value' => $panel_val ) ),
							array(
								'id_prefix'   => $id_prefix,
								'name_prefix' => $name_prefix,
								'value'       => $panel_val,
							)
						); ?>
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
	 * Parse the base / md / lg layers, each through the inner control's own
	 * validation. Mirrors the spacing type's null-input guard: when FW passes a
	 * non-array `$input_value` (the option key is absent from the submitted form
	 * — notably the page-builder's "re-save existing atts" path), fall back to
	 * `$option['value']` (which FW has already merged the saved value into) so a
	 * re-save preserves the pick instead of silently resetting it to defaults.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( ! is_array( $input_value ) ) {
			return isset( $option['value'] ) ? $option['value'] : $this->normalize_value( null );
		}

		$inner     = $this->inner_option( $option );
		$inner_opt = array_merge( $inner, array( 'value' => '' ) );
		$out       = array( 'base' => '', 'md' => '', 'lg' => '' );

		foreach ( self::DEVICES as $k ) {
			if ( isset( $input_value[ $k ] ) ) {
				$out[ $k ] = fw()->backend->option_type( $inner['type'] )
					->get_value_from_input( $inner_opt, $input_value[ $k ] );
			}
		}

		return $out;
	}

	/**
	 * @internal
	 *
	 * Same explicit-enqueue pattern as spacing / background-pro: pull the shared
	 * device-tabs assets, the inner control's assets, and our (tiny) layout CSS.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$fw_uri = fw_get_framework_directory_uri( '/static' );
		$uri    = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );
		$ver    = fw()->manifest->get_version();

		wp_enqueue_style( 'dashicons' );

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

		// The inner control's own assets (image-picker, select, …).
		$inner = $this->inner_option( $option );
		fw()->backend->option_type( $inner['type'] )->enqueue_static();

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array( 'fw-device-tabs' ), $ver
		);
		// The switcher's relocation into the label column is handled generically by
		// fw-device-tabs.js (the shared .fw-device-head mechanism) — no per-type JS.
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}
}

FW_Option_Type::register( 'Fw_Option_Type_Responsive' );

/**
 * Force-load the device-tabs + responsive assets on post-edit screens.
 *
 * Same reasoning as the spacing type: generic shortcodes load their option HTML
 * into the page-builder modal via AJAX, and the page-load enqueue walk that is
 * supposed to cover them does not reliably reach a nested custom option type —
 * so the device switcher could be unstyled/inert in those modals. Enqueuing on
 * every post-edit screen guarantees the assets are present before any modal
 * opens; wp_enqueue_* dedupes by handle, so this is a no-op when already loaded.
 */
if ( ! function_exists( 'fw_option_type_responsive_force_admin_enqueue' ) ) {
	function fw_option_type_responsive_force_admin_enqueue() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && $screen->base === 'post' ) {
			fw()->backend->option_type( 'responsive' )->enqueue_static();
		}
	}
	add_action( 'admin_enqueue_scripts', 'fw_option_type_responsive_force_admin_enqueue', 20 );
}

endif; // class_exists guard
