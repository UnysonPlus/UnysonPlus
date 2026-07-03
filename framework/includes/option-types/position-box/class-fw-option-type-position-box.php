<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Position Offsets option type.
 *
 * A compact, Elementor-style inline row of four unit inputs — Top / Right /
 * Bottom / Left — for CSS position offsets. Each side is a nested `unit-input`
 * sub-control (a number field + a small unit dropdown), so the whole set reads
 * like the spacing control's four-side row. The unit list includes `auto`;
 * picking it makes that side compile to `auto` regardless of the number.
 *
 * Saved value (per side reuses unit-input's { value, unit } shape):
 *   array(
 *     'top'    => array( 'value' => '20', 'unit' => 'px'   ),
 *     'right'  => array( 'value' => '',   'unit' => 'auto' ),
 *     'bottom' => array( 'value' => '',   'unit' => 'px'   ),
 *     'left'   => array( 'value' => '',   'unit' => 'px'   ),
 *   )
 *
 * Compile to CSS with the static helper:
 *   FW_Option_Type_Position_Box::css_map( $saved ); // ['top'=>'20px','right'=>'auto','bottom'=>'','left'=>'']
 */
if ( ! class_exists( 'FW_Option_Type_Position_Box' ) ) :

class FW_Option_Type_Position_Box extends FW_Option_Type {

	const SIDES = array( 'top', 'right', 'bottom', 'left' );

	public function get_type() {
		return 'position-box';
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
	protected function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}

	/**
	 * Default unit list. `auto` is intentionally last — it's the odd one out
	 * (ignores the number) and the consumer maps it to the CSS keyword `auto`.
	 */
	public function default_units() {
		return array( 'px', '%', 'em', 'rem', 'vh', 'vw', 'auto' );
	}

	private function get_units( $option ) {
		return ( isset( $option['units'] ) && is_array( $option['units'] ) && $option['units'] )
			? array_values( $option['units'] )
			: $this->default_units();
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		$empty = array( 'value' => '', 'unit' => 'px' );
		return array(
			'value' => array( 'top' => $empty, 'right' => $empty, 'bottom' => $empty, 'left' => $empty ),
			'units' => $this->default_units(),
		);
	}

	/**
	 * Merge a saved value over the empty per-side shape so every side exists.
	 */
	private function normalize_value( $saved ) {
		$empty = array( 'value' => '', 'unit' => 'px' );
		$saved = is_array( $saved ) ? $saved : array();
		$out   = array();
		foreach ( self::SIDES as $side ) {
			$sv           = ( isset( $saved[ $side ] ) && is_array( $saved[ $side ] ) ) ? $saved[ $side ] : array();
			$out[ $side ] = array_merge( $empty, $sv );
		}
		return $out;
	}

	/**
	 * @internal
	 *
	 * Pull in the nested unit-input assets (each side is one) plus our own
	 * inline-row layout. Same explicit-enqueue pattern as the spacing type so
	 * the sub-control CSS/JS reaches AJAX-loaded builder modals.
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );
		$ver = fw()->manifest->get_version();

		fw()->backend->option_type( 'unit-input' )->enqueue_static();

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css',
			array( 'fw-option-unit-input' ),
			$ver
		);
	}

	/**
	 * Render one side by delegating to the unit-input option type. The sub-control
	 * submits under `{name_prefix}[{side}]`, so a save round-trips as our per-side map.
	 */
	public function render_side( $side, $side_value, $units, $id_prefix, $name_prefix ) {
		return fw()->backend->option_type( 'unit-input' )->render(
			$side,
			array(
				'type'  => 'unit-input',
				'label' => false,
				'desc'  => false,
				'value' => $side_value,
				'units' => $units,
			),
			array(
				'id_prefix'   => $id_prefix,
				'name_prefix' => $name_prefix,
				'value'       => $side_value,
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->get_type() . '/view.php' ),
			array(
				'id'          => $id,
				'option'      => $option,
				'value'       => $this->normalize_value( $data['value'] ),
				'units'       => $this->get_units( $option ),
				'id_prefix'   => $option['attr']['id'] . '-',
				'name_prefix' => $option['attr']['name'],
				'sides'       => self::SIDES,
				'type'        => $this,
			)
		);
	}

	/**
	 * @internal
	 *
	 * Sanitize each side's { value, unit } (mirrors unit-input): value must be
	 * blank or numeric, unit must be one of the configured units. When the option
	 * key is missing from the submitted form (FW passes non-array), keep the saved
	 * value so a re-save doesn't reset offsets.
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( ! is_array( $input_value ) ) {
			return is_array( $option['value'] ) ? $option['value'] : $this->_get_defaults()['value'];
		}
		$units    = $this->get_units( $option );
		$fallback = isset( $units[0] ) ? $units[0] : 'px';
		$out      = array();
		foreach ( self::SIDES as $side ) {
			$raw = isset( $input_value[ $side ] ) ? $input_value[ $side ] : null;
			if ( is_string( $raw ) ) {
				$decoded = json_decode( $raw, true );
				if ( is_array( $decoded ) ) { $raw = $decoded; }
			}
			$val = ( is_array( $raw ) && isset( $raw['value'] ) ) ? trim( (string) $raw['value'] ) : '';
			if ( $val !== '' && ! is_numeric( $val ) ) { $val = ''; }
			$unit = ( is_array( $raw ) && isset( $raw['unit'] ) ) ? (string) $raw['unit'] : $fallback;
			if ( ! in_array( $unit, $units, true ) ) { $unit = $fallback; }
			$out[ $side ] = array( 'value' => $val, 'unit' => $unit );
		}
		return $out;
	}

	/**
	 * Compile a saved value into a per-side CSS-length map. `auto` unit → the CSS
	 * keyword `auto`; a numeric value → number+unit; blank → '' (skip the side).
	 *
	 * @param array $value saved per-side map
	 * @return array [ 'top' => '20px'|'auto'|'', 'right' => …, 'bottom' => …, 'left' => … ]
	 */
	public static function css_map( $value ) {
		$out = array( 'top' => '', 'right' => '', 'bottom' => '', 'left' => '' );
		if ( ! is_array( $value ) ) {
			return $out;
		}
		foreach ( array_keys( $out ) as $side ) {
			$sv   = ( isset( $value[ $side ] ) && is_array( $value[ $side ] ) ) ? $value[ $side ] : array();
			$unit = isset( $sv['unit'] ) ? trim( (string) $sv['unit'] ) : '';
			$num  = isset( $sv['value'] ) ? trim( (string) $sv['value'] ) : '';
			if ( $unit === 'auto' ) {
				$out[ $side ] = 'auto';
			} elseif ( $num !== '' && is_numeric( $num ) ) {
				$out[ $side ] = $num . $unit;
			}
		}
		return $out;
	}
}

FW_Option_Type::register( 'FW_Option_Type_Position_Box' );

endif;
