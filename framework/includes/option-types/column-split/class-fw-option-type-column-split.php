<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Column Split — a visual two-pane split control.
 *
 * Renders a long rounded bar divided into a LEFT and RIGHT pane by a draggable
 * divider; dragging sets how the row is shared between them. Each pane is labelled
 * (a dashicon / image + text) to show what it represents — e.g. "Image | Content"
 * for [image_content], "Content | Button" for a call-to-action.
 *
 * The stored value is the LEFT pane's fraction as a lowest-terms **"n/d" string**
 * (e.g. "1/3", "2/5"). This is SELF-IDENTIFYING: a legacy bare integer (the old
 * shape — the left span out of `denominator`, default 12) is still understood and
 * migrated on the fly (it has no "/"), so switching an existing control to the
 * fraction shape needs no data migration and never resets a saved split.
 *
 * The divider snaps to the ordered `fractions` set (default = twelfths 1/12…11/12).
 * Pass a richer set — e.g. twelfths + fifths — to let it stop on 1/5, 2/5, 3/5, 4/5.
 * Because consumers derive either flex-grow ratios OR grid classes from n/d, the set
 * can mix denominators freely.
 *
 * Config:
 *   'value'         string  left pane fraction "n/d" (default "1/2"); a legacy int is
 *                           accepted and read as int/denominator.
 *   'fractions'     array   ordered allowed left-pane fractions as "n/d" strings
 *                           (default = twelfths 1/12…11/12).
 *   'denominator'   int     ONLY used to interpret a legacy integer value (default 12).
 *   'show_fraction' bool    show each pane's lowest-form fraction (default true)
 *   'panes'         array   [ left, right ] — each: array('label' => '', 'icon' => '')
 *                           where `icon` is a dashicons-* class OR an image URL.
 */
class FW_Option_Type_Column_Split extends FW_Option_Type {

	public function _get_type() {
		return 'column-split';
	}

	/**
	 * The framework's abstract option-type identifier (mirrors the slider type:
	 * a public get_type() wrapping the internal _get_type()).
	 */
	public function get_type() {
		return $this->_get_type();
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

	/**
	 * Reduce n/d to lowest terms. Returns array( n, d ).
	 */
	private function reduce( $n, $d ) {
		$n = (int) $n;
		$d = (int) $d;
		$a = abs( $n );
		$b = abs( $d );
		while ( $b ) { $t = $b; $b = $a % $b; $a = $t; }
		$g = max( 1, $a );
		return array( (int) ( $n / $g ), (int) ( $d / $g ) );
	}

	/**
	 * Parse a stored/entered value into array( n, d ) lowest terms, or null.
	 *  - "n/d" string  → parsed directly (must be a proper fraction 0 < n < d).
	 *  - bare integer  → legacy left span out of $denominator.
	 */
	private function parse_fraction( $value, $denominator ) {
		if ( is_string( $value ) && strpos( $value, '/' ) !== false ) {
			$p = explode( '/', $value );
			$n = (int) $p[0];
			$d = isset( $p[1] ) ? (int) $p[1] : 0;
			if ( $n > 0 && $d > 0 && $n < $d ) {
				return $this->reduce( $n, $d );
			}
			return null;
		}
		$n = (int) $value;
		$d = max( 2, (int) $denominator );
		if ( $n > 0 && $n < $d ) {
			return $this->reduce( $n, $d );
		}
		return null;
	}

	/**
	 * Ordered list of allowed left-pane fractions as lowest-terms "n/d" strings.
	 * Defaults to twelfths (1/12…11/12) when the option supplies no `fractions`.
	 */
	private function allowed_fractions( $option ) {
		$raw = ( isset( $option['fractions'] ) && is_array( $option['fractions'] ) && $option['fractions'] )
			? $option['fractions']
			: null;
		if ( ! $raw ) {
			$raw = array();
			for ( $i = 1; $i <= 11; $i++ ) { $raw[] = $i . '/12'; }
		}
		$map = array();
		foreach ( $raw as $f ) {
			$r = $this->parse_fraction( $f, 12 );
			if ( $r ) { $map[ $r[0] . '/' . $r[1] ] = $r[0] / $r[1]; }
		}
		asort( $map );
		return array_keys( $map );
	}

	/**
	 * Normalise any incoming value to one of the allowed "n/d" fractions, snapping
	 * to the nearest allowed fraction by position when it isn't an exact member.
	 */
	private function normalize( $value, $option ) {
		$allowed     = $this->allowed_fractions( $option );
		$denominator = max( 2, (int) ( isset( $option['denominator'] ) ? $option['denominator'] : 12 ) );

		$r = $this->parse_fraction( $value, $denominator );
		if ( ! $r ) { $r = $this->parse_fraction( $option['value'], $denominator ); }
		if ( ! $r ) { $r = array( 1, 2 ); }

		$key = $r[0] . '/' . $r[1];
		if ( $allowed && ! in_array( $key, $allowed, true ) ) {
			$target = $r[0] / $r[1];
			$best   = $allowed[0];
			$bd     = INF;
			foreach ( $allowed as $a ) {
				list( $an, $ad ) = explode( '/', $a );
				$dd = abs( ( (int) $an / (int) $ad ) - $target );
				if ( $dd < $bd ) { $bd = $dd; $best = $a; }
			}
			$key = $best;
		}
		return $key;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$ver = fw()->manifest->get_version();

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'fw-option-' . $this->_get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->_get_type() . '/static/css/styles.css' ),
			array(),
			$ver
		);

		wp_enqueue_script(
			'fw-option-' . $this->_get_type(),
			fw_get_framework_asset_uri( '/includes/option-types/' . $this->_get_type() . '/static/js/scripts.js' ),
			array( 'jquery', 'fw-events' ),
			$ver,
			true
		);
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$allowed     = $this->allowed_fractions( $option );
		$denominator = max( 2, (int) ( isset( $option['denominator'] ) ? $option['denominator'] : 12 ) );

		$frac = $this->normalize( $data['value'], $option );
		list( $n, $d ) = array_map( 'intval', explode( '/', $frac ) );

		$panes = is_array( $option['panes'] ) ? array_values( $option['panes'] ) : array();
		$left  = isset( $panes[0] ) && is_array( $panes[0] ) ? $panes[0] : array();
		$right = isset( $panes[1] ) && is_array( $panes[1] ) ? $panes[1] : array();

		$cfg = array(
			'fractions'     => $allowed,
			'denominator'   => $denominator,
			'show_fraction' => ! empty( $option['show_fraction'] ),
		);

		$option['attr']['data-fw-column-split'] = json_encode( $cfg );
		$option['attr']['class'] = trim(
			( isset( $option['attr']['class'] ) ? $option['attr']['class'] : '' ) . ' fw-option-type-column-split'
		);

		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->_get_type() . '/view.php' ),
			array(
				'id'     => $id,
				'option' => $option,
				'data'   => $data,
				'value'  => $frac,   // "n/d" string (the hidden input's value)
				'n'      => $n,      // left pane numerator (flex-grow)
				'd'      => $d,      // denominator (right grow = d - n)
				'cfg'    => $cfg,
				'left'   => $left,
				'right'  => $right,
			)
		);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		if ( is_null( $input_value ) ) {
			$input_value = $option['value'];
		}
		return $this->normalize( $input_value, $option );
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'         => '1/2',
			'fractions'     => null, // null → twelfths 1/12…11/12
			'denominator'   => 12,   // legacy-integer interpretation only
			'show_fraction' => true,
			'panes'         => array(
				array( 'label' => __( 'Left', 'fw' ),  'icon' => 'dashicons-align-pull-left' ),
				array( 'label' => __( 'Right', 'fw' ), 'icon' => 'dashicons-align-pull-right' ),
			),
		);
	}
}
