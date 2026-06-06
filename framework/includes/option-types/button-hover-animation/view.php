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

$choices      = ( isset( $option['choices'] ) && is_array( $option['choices'] ) ) ? $option['choices'] : array();
$preview_base = isset( $option['preview_base'] ) ? (string) $option['preview_base'] : 'btn btn-primary';
$placeholder  = isset( $option['placeholder'] ) && $option['placeholder'] !== '' ? (string) $option['placeholder'] : __( 'None', 'fw' );

$value = is_string( $data['value'] ) ? $data['value'] : '';
// Keep only a value that's still a valid choice; else treat as unselected.
if ( $value !== '' && ! isset( $choices[ $value ] ) ) {
	$value = '';
}

$input_name = $data['name_prefix'] . '[' . $id . ']';

// Preview class string for a given choice value (base + the value class).
$preview_class = function ( $val ) use ( $preview_base ) {
	$cls = trim( $preview_base );
	if ( $val !== '' ) { $cls .= ' ' . $val; }
	return $cls;
};

// Trigger label: the selected choice's name, the '' (None) label, or the placeholder.
if ( $value !== '' && isset( $choices[ $value ] ) ) {
	$trigger_label = $choices[ $value ];
} elseif ( isset( $choices[''] ) && $choices[''] !== '' ) {
	$trigger_label = $choices[''];
} else {
	$trigger_label = $placeholder;
}

$none_label = ( isset( $choices[''] ) && $choices[''] !== '' ) ? $choices[''] : __( 'None', 'fw' );
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="bha__input"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>

	<button type="button" class="bha__trigger" aria-haspopup="listbox" aria-expanded="false" onclick="return false;">
		<span class="bha__trigger-label"><?php echo esc_html( $trigger_label ); ?></span>
		<span class="bha__caret" aria-hidden="true">▾</span>
	</button>

	<div class="bha__panel" role="listbox" hidden>
		<div class="bha__grid">

			<button
				type="button"
				class="bha__option bha__option--none<?php echo $value === '' ? ' is-selected' : ''; ?>"
				data-value=""
				role="option"
				aria-selected="<?php echo $value === '' ? 'true' : 'false'; ?>"
				title="<?php echo esc_attr( $none_label ); ?>"
			>
				<span class="bha__none"><?php echo esc_html( $none_label ); ?></span>
			</button>

			<?php foreach ( $choices as $val => $label ) :
				if ( (string) $val === '' ) { continue; } // None rendered above
				$is_sel = ( (string) $val === (string) $value );
				?>
				<button
					type="button"
					class="bha__option<?php echo $is_sel ? ' is-selected' : ''; ?>"
					data-value="<?php echo esc_attr( $val ); ?>"
					role="option"
					aria-selected="<?php echo $is_sel ? 'true' : 'false'; ?>"
					title="<?php echo esc_attr( $label ); ?>"
				>
					<span class="bha__preview <?php echo esc_attr( $preview_class( $val ) ); ?>"><?php echo esc_html( $label ); ?></span>
				</button>
			<?php endforeach; ?>

		</div>
	</div>

</div>
