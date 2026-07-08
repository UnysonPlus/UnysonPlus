<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Split Slider — a visual N-pane width control (1–5+ segments, 100%-rule).
 *
 * Renders a 100%-wide bar divided into N panes by (N-1) draggable dividers; drag
 * a divider to redistribute width between its two adjacent panes (the total is
 * always 100). + / − buttons add or remove a pane (within `min`/`max`); each pane
 * carries an optional editable name (falls back to its 1-based index).
 *
 * Generalises `column-split` (which is the fixed 2-pane / single-integer case)
 * into an arbitrary number of named, percentage-width columns.
 *
 * Stored value — an array of segments, widths always summing to 100:
 *   array(
 *     array( 'w' => 20, 'name' => 'Logo' ),
 *     array( 'w' => 55, 'name' => 'Details' ),
 *     array( 'w' => 25, 'name' => 'CTA' ),
 *   )
 *
 * Config:
 *   'value'       array  default segments (see above)
 *   'min'         int    fewest segments (default 1)
 *   'max'         int    most segments (default 5)
 *   'step'        int    drag/keyboard snap, in % (default 5)
 *   'min_width'   int    smallest a segment may become, in % (default 10)
 *   'allow_names' bool   show the editable per-segment name input (default true)
 */
class FW_Option_Type_Split_Slider extends FW_Option_Type {

	public function _get_type() {
		return 'split-slider';
	}

	/**
	 * Public alias of the internal type id (mirrors the slider / column-split types).
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
	 * Coerce a raw value (array or JSON string) into a clean segment list whose
	 * widths sum to exactly 100, with the count clamped to [min, max].
	 *
	 * @return array list( array $segments, int $min, int $max, int $min_width )
	 */
	private function normalize( $value, $option ) {
		$min  = max( 1, (int) $option['min'] );
		$max  = max( $min, (int) $option['max'] );
		$minw = max( 1, (int) $option['min_width'] );

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			$value   = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$segs = array();
		foreach ( $value as $seg ) {
			if ( is_array( $seg ) ) {
				$w    = isset( $seg['w'] ) ? (float) $seg['w'] : 0;
				$name = isset( $seg['name'] ) ? (string) $seg['name'] : '';
			} else {
				$w    = (float) $seg;
				$name = '';
			}
			$segs[] = array( 'w' => $w, 'name' => $name );
		}

		// Clamp the segment count.
		while ( count( $segs ) < $min ) {
			$segs[] = array( 'w' => 0, 'name' => '' );
		}
		if ( count( $segs ) > $max ) {
			$segs = array_slice( $segs, 0, $max );
		}
		$n = count( $segs );

		// Proportionally normalise widths to sum 100.
		$sum = 0;
		foreach ( $segs as $s ) {
			$sum += max( 0, $s['w'] );
		}
		if ( $sum <= 0 ) {
			$each = floor( 100 / $n );
			for ( $i = 0; $i < $n; $i++ ) {
				$segs[ $i ]['w'] = $each;
			}
		} else {
			for ( $i = 0; $i < $n; $i++ ) {
				$segs[ $i ]['w'] = max( 0, $segs[ $i ]['w'] ) / $sum * 100;
			}
		}
		// Grid mode: distribute $denom WHOLE grid units across the segments with the
		// largest-remainder method, then express each as an exact percentage. This keeps
		// widths perfectly proportional (e.g. 6 columns → 2 units = 16.6667% each, so an
		// equal split renders truly equal — no fat first column from integer rounding).
		$denom = max( 0, (int) $option['denominator'] );
		if ( $denom > 0 ) {
			$floor = array();
			$rem   = array();
			$ftot  = 0;
			for ( $i = 0; $i < $n; $i++ ) {
				$e            = $segs[ $i ]['w'] / 100 * $denom; // $segs already sum to 100
				$f            = max( 1, (int) floor( $e ) );
				$floor[ $i ]  = $f;
				$rem[ $i ]    = $e - floor( $e );
				$ftot        += $f;
			}
			$need = $denom - $ftot;
			if ( $need > 0 ) {
				$order = range( 0, $n - 1 );
				usort( $order, function ( $a, $b ) use ( $rem ) { return $rem[ $b ] <=> $rem[ $a ]; } );
				for ( $k = 0; $k < $need; $k++ ) { $floor[ $order[ $k % $n ] ]++; }
			} elseif ( $need < 0 ) {
				for ( $k = 0; $k < -$need; $k++ ) {
					$bi = 0; $bv = -1;
					for ( $i = 0; $i < $n; $i++ ) { if ( $floor[ $i ] > $bv ) { $bv = $floor[ $i ]; $bi = $i; } }
					if ( $floor[ $bi ] > 1 ) { $floor[ $bi ]--; }
				}
			}
			for ( $i = 0; $i < $n; $i++ ) { $segs[ $i ]['w'] = $floor[ $i ] / $denom * 100; }
			return array( array_values( $segs ), $min, $max, $minw );
		}

		// Round, enforce the per-segment minimum, then fix any rounding drift on
		// the largest segment so the total is exactly 100.
		for ( $i = 0; $i < $n; $i++ ) {
			$segs[ $i ]['w'] = max( $minw, (int) round( $segs[ $i ]['w'] ) );
		}
		$sum = 0;
		foreach ( $segs as $s ) {
			$sum += $s['w'];
		}
		$diff = 100 - $sum;
		if ( 0 !== $diff ) {
			$idx  = 0;
			$best = -1;
			for ( $i = 0; $i < $n; $i++ ) {
				if ( $segs[ $i ]['w'] > $best ) {
					$best = $segs[ $i ]['w'];
					$idx  = $i;
				}
			}
			$segs[ $idx ]['w'] = max( $minw, $segs[ $idx ]['w'] + $diff );
		}

		return array( array_values( $segs ), $min, $max, $minw );
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		$ver = fw()->manifest->get_version();

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
		$min  = max( 1, (int) $option['min'] );
		$max  = max( $min, (int) $option['max'] );
		$minw = max( 1, (int) $option['min_width'] );
		$auto_count = max( $min, min( $max, (int) $option['auto_count'] ) );

		// An empty value = AUTO (equal columns). We render a preview of equal
		// panes but keep the saved input empty until the user sets widths.
		$raw = $data['value'];
		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			$raw     = is_array( $decoded ) ? $decoded : array();
		}
		$is_auto = ! is_array( $raw ) || 0 === count( $raw );

		if ( $is_auto ) {
			$segments = array();
			$each     = (int) floor( 100 / $auto_count );
			for ( $i = 0; $i < $auto_count; $i++ ) {
				$segments[] = array( 'w' => $each, 'name' => '' );
			}
			$segments[0]['w'] += 100 - ( $each * $auto_count );
		} else {
			list( $segments ) = $this->normalize( $raw, $option );
		}

		$locked = ! empty( $option['locked'] );

		// Configured default widths — used by the "Reset to defaults" button.
		$default_raw = $option['value'];
		if ( is_string( $default_raw ) ) {
			$decoded_def = json_decode( $default_raw, true );
			$default_raw = is_array( $decoded_def ) ? $decoded_def : array();
		}
		$default_segs = ( is_array( $default_raw ) && count( $default_raw ) ) ? $this->normalize( $default_raw, $option )[0] : array();

		$cfg = array(
			'min'         => $min,
			'max'         => $max,
			'step'        => max( 1, (int) $option['step'] ),
			'min_width'   => $minw,
			'allow_names' => ! empty( $option['allow_names'] ),
			'auto_count'  => $auto_count,
			'is_auto'     => $is_auto,
			'locked'      => $locked,
			'denominator' => max( 0, (int) $option['denominator'] ),
			'default'     => $default_segs,
		);

		$option['attr']['data-fw-split-slider'] = json_encode( $cfg );
		$option['attr']['class']                = trim(
			( isset( $option['attr']['class'] ) ? $option['attr']['class'] : '' ) . ' fw-option-type-split-slider' . ( $is_auto ? ' fw-ss-is-auto' : '' ) . ( $locked ? ' fw-ss-locked' : '' )
		);

		return fw_render_view(
			fw_get_framework_directory( '/includes/option-types/' . $this->_get_type() . '/view.php' ),
			array(
				'id'          => $id,
				'option'      => $option,
				'data'        => $data,
				'value'       => $segments,
				'cfg'         => $cfg,
				'allow_names' => $cfg['allow_names'],
				'is_auto'     => $is_auto,
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
		if ( is_string( $input_value ) ) {
			$decoded     = json_decode( $input_value, true );
			$input_value = is_array( $decoded ) ? $decoded : array();
		}
		// Empty = AUTO (equal columns) — stored as an empty array.
		if ( ! is_array( $input_value ) || 0 === count( $input_value ) ) {
			return array();
		}
		list( $segments ) = $this->normalize( $input_value, $option );
		return $segments;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'value'       => array(
				array( 'w' => 50, 'name' => '' ),
				array( 'w' => 50, 'name' => '' ),
			),
			'min'         => 1,
			'max'         => 5,
			'step'        => 5,
			'min_width'   => 10,
			'allow_names' => true,
			'auto_count'  => 3,     // panes shown when value is empty (AUTO)
			'locked'      => false, // hide add/remove (fixed column count)
			'denominator' => 0,     // >0 snaps widths to a grid (e.g. 12) + shows N/denominator fractions instead of %
		);
	}
}
