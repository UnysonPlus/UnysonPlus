<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * @var string $id
 * @var array  $option
 * @var array  $data
 */

{
	$div_attr = $option['attr'];
	unset( $div_attr['value'], $div_attr['name'] );
}

$value = is_array( $data['value'] ) ? $data['value'] : array();
$value = array_merge( array( 'value' => '', 'unit' => 'px' ), $value );

$units = FW_Option_Type_Unit_Input::normalize_units( isset( $option['units'] ) ? $option['units'] : array() );

// Make sure the saved unit is selectable; else fall back to the first unit.
if ( ! isset( $units[ $value['unit'] ] ) ) {
	$unit_keys      = array_keys( $units );
	$value['unit']  = isset( $unit_keys[0] ) ? $unit_keys[0] : '';
}

$json_input_name = $data['name_prefix'] . '[' . $id . ']';

// Optional number-input attributes.
$num_attr = '';
foreach ( array( 'min', 'max' ) as $k ) {
	if ( isset( $option[ $k ] ) && $option[ $k ] !== '' ) {
		$num_attr .= ' ' . $k . '="' . esc_attr( $option[ $k ] ) . '"';
	}
}
$step = ( isset( $option['step'] ) && $option['step'] !== '' ) ? $option['step'] : 'any';
$num_attr .= ' step="' . esc_attr( $step ) . '"';
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="fw-unit-input-json"
		name="<?php echo esc_attr( $json_input_name ); ?>"
		value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>"
	/>

	<div class="fw-unit-input-row">
		<input type="number" class="fw-unit-input-value" value="<?php echo esc_attr( $value['value'] ); ?>"<?php echo $num_attr; ?> />
		<select class="fw-unit-input-unit">
			<?php foreach ( $units as $u_value => $u_label ) : ?>
				<option value="<?php echo esc_attr( $u_value ); ?>"<?php selected( (string) $value['unit'], (string) $u_value ); ?>><?php echo esc_html( $u_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>

</div>
