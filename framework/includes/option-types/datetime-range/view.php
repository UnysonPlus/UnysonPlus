<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * @var string $id
 * @var array  $option   already has 'format' resolved, plus timepicker/datepicker/min-date/max-date
 * @var array  $data
 */

$attr = $option['attr'];
$name = isset( $attr['name'] ) ? $attr['name'] : '';
unset( $attr['name'], $attr['value'], $attr['id'] );

$value = is_array( $option['value'] ) ? array_values( $option['value'] ) : array();
$from  = isset( $value[0] ) ? (string) $value[0] : '';
$to    = isset( $value[1] ) ? (string) $value[1] : '';

$sep     = FW_Option_Type_Datetime_Range::SEPARATOR;
$display = ( $from !== '' && $to !== '' ) ? ( $from . $sep . $to ) : '';

$cfg = array(
	'format'     => $option['format'],
	'separator'  => $sep,
	'timepicker' => ! empty( $option['timepicker'] ),
	'datepicker' => ( $option['datepicker'] !== false ),
	'minDate'    => isset( $option['min-date'] ) ? $option['min-date'] : null,
	'maxDate'    => isset( $option['max-date'] ) ? $option['max-date'] : null,
);

$attr['data-range-attr'] = json_encode( $cfg );
?>
<div <?php echo fw_attr_to_html( $attr ) ?>>
	<input type="text"
	       class="fw-datetime-range-display fw-option-type-text"
	       readonly
	       autocomplete="off"
	       value="<?php echo esc_attr( $display ) ?>"
	       placeholder="<?php echo esc_attr__( 'Select a range…', 'fw' ) ?>" />
	<input type="hidden"
	       class="fw-datetime-range-value"
	       name="<?php echo esc_attr( $name ) ?>"
	       value="<?php echo esc_attr( json_encode( array( $from, $to ) ) ) ?>" />
</div>
