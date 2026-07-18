<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * @var string $id
 * @var  array $option
 * @var  array $data
 *
 * Each child control renders on one horizontal row, with its caption BELOW the
 * control (muted italic) — matching the typography-v2 option's layout for
 * cross-control consistency. Stacks vertically on mobile (see styles.css).
 */

{
	$div_attr = $option['attr'];

	unset(
		$div_attr['value'],
		$div_attr['name']
	);
}
$group_name = null;
if ( ! empty( $option['groupname'] ) ) {
	$group_name = $option['groupname'];
}
?>
<div <?php echo fw_attr_to_html( $div_attr ) ?>>
	<div class="fw-multi-inline-group">
		<?php foreach ( $option['value'] as $key => $options_group ) {

			$cfg   = $option['fw_multi_options'][ $key ];
			$type  = $cfg['type'];
			$title = isset( $cfg['title'] ) ? $cfg['title'] : '';

			$field        = '';       // the rendered control markup
			$holder_class = 'fw-multi-holding-text';

			$data_for_child = array(
				'value'       => fw_akg( $key, $data['value'] ),
				'id_prefix'   => $option['attr']['id'] . '-',
				'name_prefix' => $option['attr']['name'],
			);
			$mp_attr = array( 'data-fwmultioptions' => $group_name );

			// short text
			if ( $type === 'short-text' ) {
				$field = fw()->backend->option_type( 'short-text' )->render( $key, array(
					'type'  => 'short-text',
					'value' => fw_akg( $key, $option['value'] ),
					'attr'  => $mp_attr,
				), $data_for_child );
			}

			// text
			elseif ( $type === 'text' ) {
				$field = fw()->backend->option_type( 'text' )->render( $key, array(
					'type'  => 'short-text',
					'value' => fw_akg( $key, $option['value'] ),
					'attr'  => $mp_attr,
				), $data_for_child );
			}

			// color
			elseif ( $type === 'color' ) {
				$field = fw()->backend->option_type( 'color-picker' )->render( $key, array(
					'type'  => 'color-picker',
					'value' => fw_akg( $key, $option['value'] ),
					'attr'  => $mp_attr,
				), $data_for_child );
			}

			// rgba color
			elseif ( $type === 'rgbacolor' ) {
				$field = fw()->backend->option_type( 'rgba-color-picker' )->render( $key, array(
					'type'  => 'rgba-color-picker',
					'value' => fw_akg( $key, $option['value'] ),
					'attr'  => $mp_attr,
				), $data_for_child );
			}

			// short select
			elseif ( $type === 'short-select' ) {
				$holder_class = 'fw-multi-holding-select';
				$field = fw()->backend->option_type( 'short-select' )->render( $key, array(
					'type'    => 'short-select',
					'value'   => fw_akg( $key, $option['value'] ),
					'choices' => fw_akg( $key . '/choices', $option['fw_multi_options'] ),
					'attr'    => $mp_attr,
				), $data_for_child );
			}

			// select
			elseif ( $type === 'select' ) {
				$holder_class = 'fw-multi-holding-select';
				$field = fw()->backend->option_type( 'select' )->render( $key, array(
					'type'    => 'select',
					'value'   => fw_akg( $key, $option['value'] ),
					'choices' => fw_akg( $key . '/choices', $option['fw_multi_options'] ),
					'attr'    => $mp_attr,
				), $data_for_child );
			}

			// unit-input (numeric field + unit dropdown). Passes the child's
			// units/separate/min/max/step config through so a border-width or
			// size row behaves exactly like a standalone unit-input.
			elseif ( $type === 'unit-input' ) {
				$holder_class = 'fw-multi-holding-unit';
				$field = fw()->backend->option_type( 'unit-input' )->render( $key, array(
					'type'     => 'unit-input',
					'value'    => fw_akg( $key, $option['value'] ),
					'units'    => isset( $cfg['units'] ) ? $cfg['units'] : array( 'px', 'em', 'rem' ),
					'separate' => isset( $cfg['separate'] ) ? $cfg['separate'] : false,
					'min'      => isset( $cfg['min'] ) ? $cfg['min'] : '',
					'max'      => isset( $cfg['max'] ) ? $cfg['max'] : '',
					'step'     => isset( $cfg['step'] ) ? $cfg['step'] : '',
					'attr'     => $mp_attr,
				), $data_for_child );
			}

			// predefined-colors-color-picker-compact (palette preset dropdown +
			// inline custom picker). Passes the child's picker + choices config
			// through so a border-color row stays palette-linked like a
			// standalone sc_color_field_compact() field.
			elseif ( $type === 'predefined-colors-color-picker-compact' || $type === 'compact-color' ) {
				$holder_class = 'fw-multi-holding-color';
				$field = fw()->backend->option_type( 'predefined-colors-color-picker-compact' )->render( $key, array(
					'type'    => 'predefined-colors-color-picker-compact',
					'value'   => fw_akg( $key, $option['value'] ),
					'picker'  => isset( $cfg['picker'] ) ? $cfg['picker'] : 'color-picker',
					'choices' => isset( $cfg['choices'] ) ? $cfg['choices'] : array(),
					'attr'    => $mp_attr,
				), $data_for_child );
			}

			// icon-v2 (icon picker button — opens a modal to choose a
			// font / SVG / emoji / uploaded icon). The value + enqueue paths
			// are already generic (child_type passes 'icon-v2' through), so
			// this render branch is all that was missing for icon rows.
			elseif ( $type === 'icon-v2' || $type === 'icon' ) {
				$holder_class = 'fw-multi-holding-icon';
				$field = fw()->backend->option_type( 'icon-v2' )->render( $key, array(
					'type'  => 'icon-v2',
					'value' => fw_akg( $key, $option['value'] ),
					'attr'  => $mp_attr,
				), $data_for_child );
			}

			if ( $field === '' ) {
				continue;
			}

			// Control first, caption BELOW (muted italic) — typography-v2 layout.
			echo '<div class="fw-multi-inline-holder ' . esc_attr( $holder_class ) . '">'
				. $field
				. ( $title !== '' && $title !== false ? '<div class="fw-multi-inline-title">' . $title . '</div>' : '' )
				. '</div>';
		} ?>
	</div>
</div>
