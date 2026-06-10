<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * @var string $id
 * @var array  $option
 * @var array  $data
 * @var int    $value   left pane span
 * @var array  $cfg     denominator / min / max / show_fraction
 * @var array  $left    left pane: array('label','icon')
 * @var array  $right   right pane: array('label','icon')
 */

$wrapper_attr = $option['attr'];
unset( $wrapper_attr['value'], $wrapper_attr['name'] );

$denominator = (int) $cfg['denominator'];
$right_value = $denominator - $value;

// Build one pane's inner markup (icon + label + fraction badge). Icon is a
// dashicons-* class, or an image URL when it looks like a path/URL.
$pane_inner = function ( $pane ) {
	$out  = '';
	$icon = isset( $pane['icon'] ) ? (string) $pane['icon'] : '';
	if ( $icon !== '' ) {
		$is_img = ( strpos( $icon, '/' ) !== false ) || preg_match( '/\.(png|jpe?g|gif|webp|svg)$/i', $icon );
		if ( $is_img ) {
			$out .= '<img class="fw-cs-pane-img" src="' . esc_url( $icon ) . '" alt="" />';
		} else {
			$out .= '<span class="fw-cs-pane-icon dashicons ' . esc_attr( $icon ) . '"></span>';
		}
	}
	$label = isset( $pane['label'] ) ? (string) $pane['label'] : '';
	if ( $label !== '' ) {
		$out .= '<span class="fw-cs-pane-label">' . esc_html( $label ) . '</span>';
	}
	$out .= '<span class="fw-cs-pane-frac"></span>';
	return $out;
};
?>
<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
	<div class="fw-cs-track">
		<div class="fw-cs-pane fw-cs-pane-left" style="flex-grow:<?php echo esc_attr( $value ); ?>">
			<?php echo $pane_inner( $left ); // phpcs:ignore — built from escaped parts ?>
		</div>
		<div class="fw-cs-divider" tabindex="0" role="slider"
			aria-valuemin="<?php echo esc_attr( $cfg['min'] ); ?>"
			aria-valuemax="<?php echo esc_attr( $cfg['max'] ); ?>"
			aria-valuenow="<?php echo esc_attr( $value ); ?>"
			aria-label="<?php esc_attr_e( 'Drag to set the split', 'fw' ); ?>"
		><span class="fw-cs-grip"></span></div>
		<div class="fw-cs-pane fw-cs-pane-right" style="flex-grow:<?php echo esc_attr( $right_value ); ?>">
			<?php echo $pane_inner( $right ); // phpcs:ignore — built from escaped parts ?>
		</div>
	</div>
	<input type="hidden" class="fw-cs-input" name="<?php echo esc_attr( $option['attr']['name'] ); ?>" value="<?php echo esc_attr( $value ); ?>" />
</div>
