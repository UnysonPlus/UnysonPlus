<?php if ( ! defined( 'ABSPATH' ) ) die( 'Direct access forbidden.' );

/**
 * Inline "multi" option type — renders N child fields side-by-side on a single
 * row. Used by the Spacing/Padding T/R/B/L control, the Custom Styling border
 * row and similar compact composites.
 *
 * Supported child types (a superset of the legacy fw-multi-inline):
 *   short-text, text, color-picker, rgba-color-picker (rgbacolor),
 *   short-select, select, unit-input, predefined-colors-color-picker-compact,
 *   icon-v2 (icon picker button).
 * unit-input passes through units/separate/min/max/step; the compact color
 * preset passes through picker/choices — so a border row can be
 * width(unit-input) + style(select) + color(compact preset) on one line, or
 * a pair of icon pickers (e.g. open + close) side by side.
 *
 * This is the canonical, correctly-named implementation. It supersedes the
 * legacy `fw-multi-inline` type (same behavior, off-convention `fw-` prefix +
 * `FwMultiInline` class casing). New options should use `'type' => 'multi-inline'`.
 * Existing `fw-multi-inline` call sites are being migrated over one-by-one; once
 * none remain, the old type will become a thin deprecated alias that extends
 * this class.
 *
 * Standalone by design (does NOT extend FW_Option_Type_FwMultiInline): the
 * plugin skips its own fw-multi-inline copy whenever unysonplus-theme is active
 * (the theme declares that class itself), so extending it would be hostage to
 * load order. A self-contained copy works on any theme with zero coupling.
 *
 * The class registration is wrapped in a class_exists guard so a stale
 * theme-side copy on a partially-upgraded deploy won't fatal.
 */
if ( ! class_exists( 'FW_Option_Type_Multi_Inline' ) ) :
class FW_Option_Type_Multi_Inline extends FW_Option_Type
{
	public function get_type()
	{
		return 'multi-inline';
	}

	/**
	 * Map a `fw_multi_options` child 'type' to the real option-type name. Mirrors the
	 * aliases the view.php render switch understands (text→short-text, color→color-picker,
	 * rgbacolor→rgba-color-picker, compact-color→predefined-colors-color-picker-compact);
	 * every other type passes through unchanged.
	 */
	private function child_type( $type )
	{
		$map = array(
			'text'          => 'short-text',
			'color'         => 'color-picker',
			'rgbacolor'     => 'rgba-color-picker',
			'compact-color' => 'predefined-colors-color-picker-compact',
		);
		return isset( $map[ $type ] ) ? $map[ $type ] : $type;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data )
	{
		$uri = fw_get_framework_directory_uri( '/includes/option-types/' . $this->get_type() . '/static' );

		wp_enqueue_style(
			'fw-option-' . $this->get_type(),
			$uri . '/css/styles.css'
		);

		// Enqueue each child control's OWN static (JS/CSS). Without this the nested
		// unit-input's script never loads on a page that has no standalone unit-input,
		// so its number / unit never sync into the hidden JSON field and the typed value
		// is lost on save (the "border width won't save" bug). The compact color picker
		// has the same dependency. This is the exact pattern the `responsive` option type
		// uses to load its inner control's assets; enqueues dedupe by handle, so it's safe.
		if ( ! empty( $option['fw_multi_options'] ) && is_array( $option['fw_multi_options'] ) ) {
			$done = array();
			foreach ( $option['fw_multi_options'] as $cfg ) {
				if ( empty( $cfg['type'] ) ) { continue; }
				$child_type = $this->child_type( $cfg['type'] );
				if ( isset( $done[ $child_type ] ) ) { continue; }
				$done[ $child_type ] = true;
				$child = fw()->backend->option_type( $child_type );
				if ( $child ) { $child->enqueue_static(); }
			}
		}
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type()
	{
		return 'auto';
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data )
	{
		return fw_render_view( dirname( __FILE__ ) . '/view.php', array(
			'id'     => $id,
			'option' => $option,
			'data'   => $data,
		) );
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input( $option, $input_value )
	{
		if ( ! is_array( $input_value ) ) {
			return $option['value'];
		}

		// Run each child through its OWN option type's get_value_from_input, so a nested
		// unit-input's submitted JSON is decoded to { value, unit } (instead of stored as
		// a raw JSON string), a select is validated against its choices, the compact
		// colour parses to { predefined, custom }, etc. Previously this returned the raw
		// $input_value, which stored the width as a JSON string and broke consumers.
		$out = is_array( $option['value'] ) ? $option['value'] : array();

		if ( ! empty( $option['fw_multi_options'] ) && is_array( $option['fw_multi_options'] ) ) {
			foreach ( $option['fw_multi_options'] as $key => $cfg ) {
				if ( empty( $cfg['type'] ) ) { continue; }
				$child_type = $this->child_type( $cfg['type'] );
				$child      = fw()->backend->option_type( $child_type );
				$sub_input  = array_key_exists( $key, $input_value ) ? $input_value[ $key ] : null;

				if ( $child ) {
					$child_opt   = array_merge( $cfg, array(
						'type'  => $child_type,
						'value' => fw_akg( $key, $option['value'] ),
					) );
					$out[ $key ] = $child->get_value_from_input( $child_opt, $sub_input );
				} elseif ( $sub_input !== null ) {
					$out[ $key ] = $sub_input;
				}
			}
		}

		return $out;
	}

	/**
	 * @internal
	 */
	protected function _get_defaults()
	{
		return array(
			'value' => array(
				'firstoption' => 'select2',
			),
			'fw_multi_options' => array(
				'firstoption' => array(
					'type'    => 'select',
					'title'   => __( 'Title', 'fw' ),
					'choices' => array(
						'select1' => __( 'select1', 'fw' ),
						'select2' => __( 'select2', 'fw' ),
						'select3' => __( 'select3', 'fw' ),
					),
				),
			),
			'groupname' => '',
		);
	}
}

FW_Option_Type::register( 'FW_Option_Type_Multi_Inline' );
endif;
