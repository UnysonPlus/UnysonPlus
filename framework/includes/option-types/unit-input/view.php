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

$value = $data['value'];
// A JSON-string value (multi-inline nesting) → decode. A LEGACY SCALAR length (an option promoted from
// `text`/`number` to `unit-input`, e.g. a Text Style size stored as "96" or "0.5px") → split its trailing
// unit into { value, unit } so the migrated field displays instead of showing blank. Anything else → defaults.
if ( is_string( $value ) ) {
	$sv = trim( $value );
	if ( isset( $sv[0] ) && $sv[0] === '{' ) {
		$decoded = json_decode( $sv, true );
		$value   = is_array( $decoded ) ? $decoded : array();
	} elseif ( $sv !== '' && preg_match( '/^(-?[0-9.]+)\s*(px|rem|em|%|vw|vh|ch|pt)?$/i', $sv, $m ) ) {
		$value = array( 'value' => $m[1], 'unit' => ( ! empty( $m[2] ) ? strtolower( $m[2] ) : '' ) );
	} else {
		$value = array();
	}
} elseif ( is_numeric( $value ) ) {
	$value = array( 'value' => (string) $value, 'unit' => '' );
} elseif ( ! is_array( $value ) ) {
	$value = array();
}
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
