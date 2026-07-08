<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * @var string $id
 * @var array  $option
 * @var array  $data
 * @var array  $value        segments: array of array('w'=>int,'name'=>string)
 * @var array  $cfg          min / max / step / min_width / allow_names
 * @var bool   $allow_names
 */

$wrapper_attr = $option['attr'];
unset( $wrapper_attr['value'], $wrapper_attr['name'] );

// AUTO keeps the saved input empty; widths are only written once the user sets them.
$json  = ! empty( $is_auto ) ? '' : ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $value ) : json_encode( $value ) );
$count = count( $value );

// Grid mode (denominator > 0): show each pane as a reduced N/denominator fraction
// (e.g. 6/12 → 1/2) instead of a percentage; the JS mirrors this exactly.
$denom       = isset( $cfg['denominator'] ) ? (int) $cfg['denominator'] : 0;
$fw_ss_label = function ( $w ) use ( $denom ) {
	if ( $denom > 0 ) {
		$u   = max( 1, (int) round( $w / 100 * $denom ) );
		$gcd = function ( $a, $b ) use ( &$gcd ) { return $b ? $gcd( $b, $a % $b ) : $a; };
		$d   = max( 1, $gcd( $u, $denom ) );
		return ( $u / $d ) . '/' . ( $denom / $d );
	}
	return $w . '%';
};
?>
<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
	<div class="fw-ss-track">
		<?php foreach ( $value as $i => $seg ) :
			$w    = (int) $seg['w'];
			$name = isset( $seg['name'] ) ? (string) $seg['name'] : '';
			?>
			<?php if ( $i > 0 ) : ?>
			<div class="fw-ss-divider" tabindex="0" role="slider"
				aria-valuemin="<?php echo esc_attr( $cfg['min_width'] ); ?>"
				aria-valuemax="100"
				aria-label="<?php esc_attr_e( 'Drag to resize columns', 'fw' ); ?>"
				data-i="<?php echo (int) $i; ?>"><span class="fw-ss-grip"></span></div>
			<?php endif; ?>
			<div class="fw-ss-pane" style="flex-grow:<?php echo esc_attr( $w ); ?>" data-i="<?php echo (int) $i; ?>">
				<?php if ( $allow_names ) : ?>
				<input type="text" class="fw-ss-name" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $i + 1 ); ?>" />
				<?php else : ?>
				<span class="fw-ss-pane-label"><?php echo esc_html( '' !== $name ? $name : ( $i + 1 ) ); ?></span>
				<?php endif; ?>
				<span class="fw-ss-pane-pct"><?php echo esc_html( $fw_ss_label( $w ) ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="fw-ss-toolbar">
		<button type="button" class="fw-ss-remove button" title="<?php esc_attr_e( 'Remove last column', 'fw' ); ?>">&minus;</button>
		<span class="fw-ss-count"><?php echo (int) $count; ?></span>
		<button type="button" class="fw-ss-add button" title="<?php esc_attr_e( 'Add column', 'fw' ); ?>">+</button>
		<button type="button" class="fw-ss-reset button"><?php esc_html_e( 'Reset to defaults', 'fw' ); ?></button>
		<span class="fw-ss-hint"><?php echo ! empty( $is_auto ) ? esc_html__( 'equal columns', 'fw' ) : esc_html__( 'custom widths', 'fw' ); ?></span>
	</div>
	<input type="hidden" class="fw-ss-input" name="<?php echo esc_attr( $option['attr']['name'] ); ?>" value="<?php echo esc_attr( $json ); ?>" />
</div>
