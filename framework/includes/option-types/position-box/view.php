<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * @var string                      $id
 * @var array                       $option
 * @var array                       $value        normalized per-side { value, unit } map
 * @var array                       $units
 * @var string                      $id_prefix
 * @var string                      $name_prefix
 * @var array                       $sides
 * @var FW_Option_Type_Position_Box $type
 */

$div_attr = $option['attr'];
unset( $div_attr['value'], $div_attr['name'] );

$labels = array(
	'top'    => __( 'Top', 'unysonplus' ),
	'right'  => __( 'Right', 'unysonplus' ),
	'bottom' => __( 'Bottom', 'unysonplus' ),
	'left'   => __( 'Left', 'unysonplus' ),
);
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>
	<div class="fw-pos-box">
		<?php foreach ( $sides as $side ) : ?>
			<div class="fw-pos-slot fw-pos-slot--<?php echo esc_attr( $side ); ?>">
				<span class="fw-pos-slot-label"><?php echo esc_html( isset( $labels[ $side ] ) ? $labels[ $side ] : $side ); ?></span>
				<?php echo $type->render_side( $side, $value[ $side ], $units, $id_prefix, $name_prefix ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
