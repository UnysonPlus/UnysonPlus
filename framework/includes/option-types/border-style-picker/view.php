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
$preview_text = isset( $option['preview_text'] ) && $option['preview_text'] !== '' ? (string) $option['preview_text'] : __( 'Border', 'fw' );
$placeholder  = isset( $option['placeholder'] ) && $option['placeholder'] !== '' ? (string) $option['placeholder'] : __( '— Select —', 'fw' );
$allow_none   = ! isset( $option['allow_none'] ) || $option['allow_none'];

$value = is_string( $data['value'] ) ? $data['value'] : '';
// Keep only a value that's still a valid choice; else treat as unselected.
if ( $value !== '' && ! isset( $choices[ $value ] ) ) {
	$value = '';
}

$input_name = $data['name_prefix'] . '[' . $id . ']';

$selected_label = ( $value !== '' && isset( $choices[ $value ] ) ) ? $choices[ $value ] : '';

// Badge preview mode — draw a real inline-styled mini tile + name beside it.
$preview_kind = isset( $option['preview_kind'] ) ? (string) $option['preview_kind'] : '';
$previews     = ( isset( $option['previews'] ) && is_array( $option['previews'] ) ) ? $option['previews'] : array();
$badge_svg    = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2l2.9 6.2 6.8.6-5.1 4.5 1.5 6.6L12 17l-6 3.5 1.5-6.6-5.1-4.5 6.8-.6z"/></svg>';

/**
 * Render the inner `.bsp__preview` markup for a choice. Badge mode → a mini tile
 * (inline-styled from $previews) + the name; otherwise the classic class-driven box.
 */
$render_preview = function ( $val, $label ) use ( $preview_kind, $previews, $badge_svg, $preview_text ) {
	$val   = (string) $val;
	$label = (string) $label;
	if ( $preview_kind === 'badge' ) {
		$p    = isset( $previews[ $val ] ) && is_array( $previews[ $val ] ) ? $previews[ $val ] : array();
		$tile = isset( $p['tile_style'] ) ? (string) $p['tile_style'] : '';
		$ico  = isset( $p['icon_style'] ) ? (string) $p['icon_style'] : '';
		return '<span class="bsp__preview bsp__preview--badge">'
			. '<span class="bsp__badge"' . ( $tile !== '' ? ' style="' . esc_attr( $tile ) . '"' : '' ) . '>'
			. '<span class="bsp__badge-glyph"' . ( $ico !== '' ? ' style="' . esc_attr( $ico ) . '"' : '' ) . '>' . $badge_svg . '</span>'
			. '</span>'
			. '<span class="bsp__badge-name">' . esc_html( $label !== '' ? $label : $preview_text ) . '</span>'
			. '</span>';
	}
	// Classic: the choice value is a CSS class (.boxp-{slug}) that styles the span.
	return '<span class="bsp__preview ' . esc_attr( $val ) . '">' . esc_html( $label !== '' ? $label : $preview_text ) . '</span>';
};
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
			<?php echo $render_preview( $value, $selected_label ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
				<?php echo $render_preview( $val, $label ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		<?php endforeach; ?>
	</div>

</div>
