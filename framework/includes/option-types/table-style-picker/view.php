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

$choices     = ( isset( $option['choices'] ) && is_array( $option['choices'] ) ) ? $option['choices'] : array();
$placeholder = isset( $option['placeholder'] ) && $option['placeholder'] !== '' ? (string) $option['placeholder'] : __( '— Select —', 'fw' );
$allow_none  = ! isset( $option['allow_none'] ) || $option['allow_none'];

$value = is_string( $data['value'] ) ? $data['value'] : '';
if ( $value !== '' && ! isset( $choices[ $value ] ) ) {
	$value = '';
}

$input_name = $data['name_prefix'] . '[' . $id . ']';

// A small, representative table that the .tbl-{slug} CSS paints.
// $class arrives as the full 'tbl-{slug}' value; wrap it verbatim.
$mini = function ( $class ) {
	$cls = $class !== '' ? ' ' . esc_attr( $class ) : '';
	ob_start(); ?>
	<span class="tsp__preview<?php echo $cls; ?>">
		<table>
			<thead><tr><th>Name</th><th>Plan</th></tr></thead>
			<tbody>
				<tr><td>Alpha</td><td>Pro</td></tr>
				<tr><td>Bravo</td><td>Lite</td></tr>
			</tbody>
		</table>
	</span>
	<?php
	return ob_get_clean();
};

$selected_label = ( $value !== '' && isset( $choices[ $value ] ) ) ? $choices[ $value ] : '';
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="tsp__input"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>

	<button type="button" class="tsp__trigger" aria-haspopup="listbox" aria-expanded="false" onclick="return false;">
		<?php if ( $value !== '' ) : ?>
			<span class="tsp__trigger-label"><?php echo esc_html( $selected_label ); ?></span>
			<?php echo $mini( $value ); // mini-table preview ?>
		<?php else : ?>
			<span class="tsp__trigger-placeholder"><?php echo esc_html( $placeholder ); ?></span>
		<?php endif; ?>
		<span class="tsp__caret" aria-hidden="true">▾</span>
	</button>

	<div class="tsp__panel" role="listbox" hidden>
		<?php if ( $allow_none ) : ?>
			<button
				type="button"
				class="tsp__option tsp__option--empty<?php echo $value === '' ? ' is-selected' : ''; ?>"
				data-value=""
				role="option"
				aria-selected="<?php echo $value === '' ? 'true' : 'false'; ?>"
			>
				<span class="tsp__option-label"><?php echo esc_html( isset( $choices[''] ) && $choices[''] !== '' ? $choices[''] : __( '— None —', 'fw' ) ); ?></span>
			</button>
		<?php endif; ?>

		<?php foreach ( $choices as $val => $label ) :
			if ( (string) $val === '' ) { continue; }
			$is_sel = ( (string) $val === (string) $value );
			?>
			<button
				type="button"
				class="tsp__option<?php echo $is_sel ? ' is-selected' : ''; ?>"
				data-value="<?php echo esc_attr( $val ); ?>"
				role="option"
				aria-selected="<?php echo $is_sel ? 'true' : 'false'; ?>"
			>
				<span class="tsp__option-label"><?php echo esc_html( $label ); ?></span>
				<?php echo $mini( (string) $val ); // mini-table preview ?>
			</button>
		<?php endforeach; ?>
	</div>

</div>
