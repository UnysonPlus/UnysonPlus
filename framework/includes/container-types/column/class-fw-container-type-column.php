<?php
/**
 * PHP Version: 7.4 or higher
 */
if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * "column" container type — lays options out side by side.
 *
 * Two or more `column` containers placed next to each other in an options array render as ONE row of
 * columns (the options renderer hands adjacent containers of the same type to one render call, the
 * same way it does for `box`). A single column on its own is just a full-width block.
 *
 *   'design_main' => array(
 *       'type'    => 'column',
 *       'width'   => '3/5',
 *       'options' => array( ... ),
 *   ),
 *   'design_side' => array(
 *       'type'    => 'column',
 *       'width'   => '2/5',
 *       'sticky'  => true,
 *       'options' => array( ... ),
 *   ),
 *
 * Config (per column):
 *   'width'   string  the column's share of the row as "n/d" ("3/5", "1/3"; the same fraction strings the
 *                     column-split option type stores). Columns without a width share the rest equally.
 *   'sticky'  bool    keep this column in view while the row scrolls (e.g. a live preview beside long
 *                     controls). Default false.
 *   'stack_first' bool when the row is stacked, put this column first (e.g. a preview that sits beside the
 *                     controls but should lead when there is no room for both). Default false.
 *   'attr'    array   extra HTML attributes for the column's wrapper (a `class` is appended).
 *
 * Row config (read from the FIRST column of the row):
 *   'stack_below' int  the row's width (px) under which the columns stack into one (default 960). It is
 *                      measured on the row itself (a CSS container query), so the row stacks in a narrow
 *                      modal or the Live Editor's side panel whatever the window size. Stacked, a sticky
 *                      column stays pinned only if it is also `stack_first` (it then sits at the top, so
 *                      pinning it is the natural reading); otherwise it would pin over the rest.
 *
 * Zero migration: like `group` / `box` / `tab`, a column renders and collects its children WITHOUT
 * namespacing — every option keeps saving under its own id, exactly as if the column weren't there. So
 * existing options can be wrapped in columns (or unwrapped) without touching saved values.
 */
class FW_Container_Type_Column extends FW_Container_Type {

	public function get_type() {
		return 'column';
	}

	protected function _get_defaults() {
		return array(
			'width'       => '',
			'sticky'      => false,
			'stack_first' => false,
			'stack_below' => 960,
		);
	}

	protected function _enqueue_static( $id, $option, $values, $data ) {
		// The styles live in backend-options.css, which every options screen already loads.
	}

	/** "3/5" → 0.6; anything else → 0 (meaning: share the rest equally). */
	private function fraction( $width ) {
		if ( is_string( $width ) && preg_match( '/^\s*(\d+)\s*\/\s*(\d+)\s*$/', $width, $m ) && (int) $m[2] > 0 ) {
			return max( 0, min( 1, (int) $m[1] / (int) $m[2] ) );
		}
		if ( is_numeric( $width ) && $width > 0 && $width <= 1 ) {
			return (float) $width;
		}
		return 0;
	}

	protected function _render( $containers, $values, $data ) {
		$cols  = '';
		$first = reset( $containers );
		reset( $containers );
		$stack = isset( $first['stack_below'] ) ? max( 0, (int) $first['stack_below'] ) : 960;

		foreach ( $containers as $id => &$column ) {
			$inner = ( isset( $column['options'] ) && is_array( $column['options'] ) )
				? fw()->backend->render_options( $column['options'], $values, $data )
				: '';

			$attr = isset( $column['attr'] ) && is_array( $column['attr'] ) ? $column['attr'] : array();
			unset( $attr['id'] );
			$attr['id']    = 'fw-backend-column-' . $id;
			$attr['class'] = 'fw-backend-column' . ( ! empty( $column['sticky'] ) ? ' fw-backend-column--sticky' : '' )
				. ( ! empty( $column['stack_first'] ) ? ' fw-backend-column--stack-first' : '' )
				. ( isset( $attr['class'] ) ? ' ' . $attr['class'] : '' );

			$share = $this->fraction( isset( $column['width'] ) ? $column['width'] : '' );
			// The share is applied only side by side (see the row rule below); stacked, every column is full width.
			$style = '--fw-col-flex: ' . ( $share > 0 ? '0 0 calc(' . round( $share * 100, 4 ) . '% - var(--fw-col-gap) * ' . round( 1 - $share, 4 ) . ')' : '1 1 0' ) . ';';
			$attr['style'] = $style . ( isset( $attr['style'] ) ? ' ' . $attr['style'] : '' );

			$cols .= '<div ' . fw_attr_to_html( $attr ) . '>' . $inner . '</div>';
		}
		unset( $column );

		// The row is stacked by default and turns into columns once the row ITSELF is at least
		// `stack_below` wide (a container query, so a narrow modal or side panel stays stacked). A
		// container query can't read a CSS variable, so each row carries its own small scoped rule.
		$uid = 'fwc-' . sanitize_html_class( (string) key( $containers ) );
		$css = '@container fw-columns (min-width: ' . $stack . 'px) {'
			. '[data-fw-columns="' . $uid . '"] > .fw-backend-columns__row { flex-direction: row; align-items: flex-start; }'
			. '[data-fw-columns="' . $uid . '"] > .fw-backend-columns__row > .fw-backend-column { flex: var(--fw-col-flex); min-width: 0; order: 0; }'
			. '[data-fw-columns="' . $uid . '"] > .fw-backend-columns__row > .fw-backend-column--sticky { position: sticky; top: 0; }'
			. '}';

		return '<div class="fw-backend-columns" data-fw-columns="' . esc_attr( $uid ) . '">'
			. '<style>' . $css . '</style>'
			. '<div class="fw-backend-columns__row">' . $cols . '</div>'
			. '</div>';
	}
}
