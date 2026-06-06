<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * @var string $id
 * @var  array $option
 * @var  array $data
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

			$type = $option['fw_multi_options'][ $key ]['type'];
			$html = '';

			// short text
			if ( $type === 'short-text' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-text">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';

				$html .= fw()->backend->option_type( 'short-text' )->render(
					$key,
					array(
						'type'  => 'short-text',
						'value' => fw_akg( $key, $option['value'] ),
						'attr'  => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			// text
			if ( $type === 'text' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-text">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';

				$html .= fw()->backend->option_type( 'text' )->render(
					$key,
					array(
						'type'  => 'short-text',
						'value' => fw_akg( $key, $option['value'] ),
						'attr'  => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			// color
			if ( $type === 'color' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-text">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';

				$html .= fw()->backend->option_type( 'color-picker' )->render(
					$key,
					array(
						'type'  => 'color-picker',
						'value' => fw_akg( $key, $option['value'] ),
						'attr'  => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			// rgba color
			if ( $type === 'rgbacolor' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-text">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';

				$html .= fw()->backend->option_type( 'rgba-color-picker' )->render(
					$key,
					array(
						'type'  => 'rgba-color-picker',
						'value' => fw_akg( $key, $option['value'] ),
						'attr'  => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			// short select
			if ( $type === 'short-select' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-select">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';
				$html .= fw()->backend->option_type( 'short-select' )->render(
					$key,
					array(
						'type'    => 'short-select',
						'value'   => fw_akg( $key, $option['value'] ),
						'choices' => fw_akg( $key . '/choices', $option['fw_multi_options'] ),
						'attr'    => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			// select
			if ( $type === 'select' ) {

				$html .= '<div class="fw-multi-inline-holder fw-multi-holding-select">';
				$html .= '<span class="fw-multi-inline-title">' . $option['fw_multi_options'][ $key ]['title'] . '</span>';
				$html .= fw()->backend->option_type( 'select' )->render(
					$key,
					array(
						'type'    => 'select',
						'value'   => fw_akg( $key, $option['value'] ),
						'choices' => fw_akg( $key . '/choices', $option['fw_multi_options'] ),
						'attr'    => array(
							'data-fwmultioptions' => $group_name,
						),
					),
					array(
						'value'       => fw_akg( $key, $data['value'] ),
						'id_prefix'   => $option['attr']['id'] . '-',
						'name_prefix' => $option['attr']['name'],
					)
				);

				$html .= '</div>';
			}

			echo $html;
		}
		?>
	</div>
</div>
