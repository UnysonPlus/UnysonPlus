<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Popover
 *
 * A compact wrapper that collapses one (or more) inner option(s) into a small
 * trigger field; clicking it reveals the real option control(s) in an in-flow
 * panel. Distinct from the modal `popup` type (this is an anchored, inline
 * disclosure — like the color picker's collapsed dropdown).
 *
 * Config:
 *  - 'inner-options'  array  Option definitions hosted inside the panel (flat).
 *  - 'tabs'           array  Optional. Group options into tabs (Background-Pro
 *                            style): array( tab_key => array('label'=>…, 'options'=>[…]) ).
 *  - 'summary'        array  Optional value => label map for the trigger text.
 *  - 'trigger_label'  string Fallback trigger text when nothing is selected.
 *
 * Value:
 *  - ONE flat inner option (no tabs) → the value is that option's value, passed
 *    straight through (a drop-in replacement for the wrapped option).
 *  - 2+ options and/or tabs → a hash keyed by inner option id (like `multi`).
 *    Tab grouping is visual only; option ids must be unique across all tabs.
 */
class FW_Option_Type_Popover extends FW_Option_Type {

	public function get_type() {
		return 'popover';
	}

	/** All hosted option definitions (flat `inner-options` + every tab's options). */
	private function collect_definitions( $option ) {
		$defs = isset( $option['inner-options'] ) && is_array( $option['inner-options'] ) ? $option['inner-options'] : array();
		if ( ! empty( $option['tabs'] ) && is_array( $option['tabs'] ) ) {
			foreach ( $option['tabs'] as $tab ) {
				if ( ! empty( $tab['options'] ) && is_array( $tab['options'] ) ) {
					$defs = array_merge( $defs, $tab['options'] );
				}
			}
		}
		return $defs;
	}

	private function inner_options( $option ) {
		return fw_extract_only_options( $this->collect_definitions( $option ) );
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		static $enqueue = true;

		if ( $enqueue ) {
			wp_enqueue_style(
				'fw-option-' . $this->get_type(),
				fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/css/styles.css' ),
				array(),
				fw()->manifest->get_version()
			);
			wp_enqueue_script(
				'fw-option-' . $this->get_type(),
				fw_get_framework_asset_uri( '/includes/option-types/' . $this->get_type() . '/static/js/scripts.js' ),
				array( 'jquery', 'fw-events' ),
				fw()->manifest->get_version(),
				true
			);

			$enqueue = false;
		}

		// Make sure the hosted option types' assets are present (they're injected
		// into the DOM only when the popover is first opened) — across tabs too.
		fw()->backend->enqueue_options_static( $this->collect_definitions( $option ) );

		return true;
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$inner    = $this->inner_options( $option );
		$has_tabs = ! empty( $option['tabs'] ) && is_array( $option['tabs'] );
		// Passthrough only when there's a single flat option and no tabs.
		$passthrough = ( ! $has_tabs && count( $inner ) === 1 );

		// Feed the current value into the inner option(s).
		if ( $passthrough ) {
			$inner_id     = key( $inner );
			$inner_values = array( $inner_id => $data['value'] );
			$current      = is_scalar( $data['value'] ) ? (string) $data['value'] : '';
		} else {
			$inner_values = is_array( $data['value'] ) ? $data['value'] : array();
			$current      = '';
		}

		$render_args = array(
			'id_prefix'   => $data['id_prefix'] . $id . '-',
			'name_prefix' => $data['name_prefix'] . '[' . $id . ']',
		);

		// Lazy: render the inner HTML into a data attribute. The JS injects it
		// (and re-fires fw:options:init) the first time the panel is opened, so
		// heavy controls stay out of the DOM until needed — like `multi-picker`.
		if ( $has_tabs ) {
			// Tabbed layout (Background-Pro style). All options share the same
			// name prefix — the tabs only group them visually; the value is flat.
			$tabs_html   = '<ul class="fw-popover-tabs">';
			$panels_html = '';
			$first       = true;
			foreach ( $option['tabs'] as $tab_key => $tab ) {
				$active    = $first ? ' is-active' : '';
				$tab_label = isset( $tab['label'] ) ? $tab['label'] : $tab_key;
				$tab_opts  = ( isset( $tab['options'] ) && is_array( $tab['options'] ) ) ? $tab['options'] : array();

				$tabs_html   .= '<li class="fw-popover-tab' . $active . '" data-fw-popover-tab="' . esc_attr( $tab_key ) . '">' . esc_html( $tab_label ) . '</li>';
				$panels_html .= '<div class="fw-popover-tab-panel' . $active . '" data-fw-popover-tab="' . esc_attr( $tab_key ) . '">'
					. fw()->backend->render_options( $tab_opts, $inner_values, $render_args )
					. '</div>';
				$first = false;
			}
			$tabs_html .= '</ul>';
			$inner_html = $tabs_html . '<div class="fw-popover-panels">' . $panels_html . '</div>';
		} else {
			$inner_html = fw()->backend->render_options(
				isset( $option['inner-options'] ) ? $option['inner-options'] : array(),
				$inner_values,
				$render_args
			);
		}

		$summary = ( isset( $option['summary'] ) && is_array( $option['summary'] ) ) ? $option['summary'] : array();
		$label   = isset( $summary[ $current ] ) ? $summary[ $current ] : ( '' !== $current ? $current : $option['trigger_label'] );

		$div_attr = $option['attr'];
		unset( $div_attr['name'], $div_attr['value'] );
		$div_attr['data-summary']   = json_encode( $summary );
		$div_attr['data-autoclose'] = $passthrough ? '1' : '0';

		$panel_attr = array(
			'class'                 => 'fw-popover-panel',
			'data-options-template' => $inner_html,
		);

		// Trigger is a styled element (not a readonly <input>, which WP admin
		// renders grey/disabled-looking).
		return '<div ' . fw_attr_to_html( $div_attr ) . '>'
			. '<div class="fw-popover-trigger" tabindex="0" role="button">'
				. '<span class="fw-popover-summary">' . esc_html( $label ) . '</span>'
				. '<span class="fw-popover-caret dashicons dashicons-arrow-down-alt2"></span>'
			. '</div>'
			. '<div ' . fw_attr_to_html( $panel_attr ) . '></div>'
		. '</div>';
	}

	public function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		$inner = $this->inner_options( $option );

		// Single inner option → pass its value straight through.
		if ( count( $inner ) === 1 ) {
			$inner_id     = key( $inner );
			$inner_option = $inner[ $inner_id ];

			return fw()->backend->option_type( $inner_option['type'] )->get_value_from_input(
				array_merge( $inner_option, array( 'value' => $option['value'] ) ),
				( is_array( $input_value ) && isset( $input_value[ $inner_id ] ) ) ? $input_value[ $inner_id ] : null
			);
		}

		// 2+ inner options → hash keyed by inner id (mirrors `multi`).
		$value = is_array( $option['value'] ) ? $option['value'] : array();
		foreach ( $inner as $inner_id => $inner_option ) {
			$value[ $inner_id ] = fw()->backend->option_type( $inner_option['type'] )->get_value_from_input(
				isset( $value[ $inner_id ] )
					? array_merge( $inner_option, array( 'value' => $value[ $inner_id ] ) )
					: $inner_option,
				( is_array( $input_value ) && isset( $input_value[ $inner_id ] ) ) ? $input_value[ $inner_id ] : null
			);
		}

		return $value;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		// 'full' so the input cell is full-width and the in-flow tabbed panel spans
		// the whole option width. ('auto' shrank the cell to the compact trigger,
		// which also shrank the panel.) The help "?" icon — which would otherwise
		// float to the far right of the full-width cell — is pinned just after the
		// trigger via CSS (.fw-backend-option-input-type-popover .fw-option-help-in-input).
		return 'full';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'inner-options' => array(),
			'tabs'          => array(),
			'summary'       => array(),
			'trigger_label' => __( 'Select', 'fw' ),
			'value'         => null,
		);
	}
}
