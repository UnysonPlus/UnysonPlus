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
$preview_text = isset( $option['preview_text'] ) && $option['preview_text'] !== '' ? (string) $option['preview_text'] : __( 'Button', 'fw' );
$preview_base = isset( $option['preview_base'] ) ? (string) $option['preview_base'] : 'btn';
$placeholder  = isset( $option['placeholder'] ) && $option['placeholder'] !== '' ? (string) $option['placeholder'] : __( '— Select —', 'fw' );
$allow_none   = ! isset( $option['allow_none'] ) || $option['allow_none'];

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

$selected_label = ( $value !== '' && isset( $choices[ $value ] ) ) ? $choices[ $value ] : '';
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="bsp__input"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>

	<button type="button" class="bsp__trigger" aria-haspopup="listbox" aria-expanded="false" onclick="return false;">
		<?php if ( $value !== '' ) : ?>
			<span class="bsp__preview <?php echo esc_attr( $preview_class( $value ) ); ?>"><?php echo esc_html( $selected_label !== '' ? $selected_label : $preview_text ); ?></span>
		<?php else : ?>
			<span class="bsp__trigger-placeholder"><?php echo esc_html( $placeholder ); ?></span>
		<?php endif; ?>
		<span class="bsp__caret" aria-hidden="true">▾</span>
	</button>

	<div class="bsp__panel" role="listbox" hidden>
		<?php if ( $allow_none ) : ?>
			<button
				type="button"
				class="bsp__option bsp__option--empty<?php echo $value === '' ? ' is-selected' : ''; ?>"
				data-value=""
				role="option"
				aria-selected="<?php echo $value === '' ? 'true' : 'false'; ?>"
			>
				<span class="bsp__option-label"><?php echo esc_html( isset( $choices[''] ) && $choices[''] !== '' ? $choices[''] : __( '— None —', 'fw' ) ); ?></span>
			</button>
		<?php endif; ?>

		<?php foreach ( $choices as $val => $label ) :
			if ( (string) $val === '' ) { continue; } // the None row is rendered above
			$is_sel = ( (string) $val === (string) $value );
			?>
			<button
				type="button"
				class="bsp__option<?php echo $is_sel ? ' is-selected' : ''; ?>"
				data-value="<?php echo esc_attr( $val ); ?>"
				role="option"
				aria-selected="<?php echo $is_sel ? 'true' : 'false'; ?>"
			>
				<span class="bsp__preview <?php echo esc_attr( $preview_class( $val ) ); ?>"><?php echo esc_html( $label ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>

</div>
