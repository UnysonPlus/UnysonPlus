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
$placeholder = isset( $option['placeholder'] ) && $option['placeholder'] !== '' ? (string) $option['placeholder'] : __( '— Select an image style —', 'fw' );
$allow_none  = ! isset( $option['allow_none'] ) || $option['allow_none'];

$value = is_string( $data['value'] ) ? $data['value'] : '';
// Keep only a value that's still a valid choice; else treat as unselected.
if ( $value !== '' && ! isset( $choices[ $value ] ) ) {
	$value = '';
}

$input_name = $data['name_prefix'] . '[' . $id . ']';

// Self-contained "photo" stand-in — a small landscape (sky gradient + sun + hills) so
// filters (grayscale / duotone / contrast), the scrim, and the corner/mask shapes all
// read clearly at swatch size. No external request (CSP-safe). Write `url(#id)` and
// `#hex` literally — rawurlencode() encodes each `#` once (a pre-encoded `%23` would be
// double-encoded to `%2523` and break the reference; that was the "invisible photo" bug).
$svg    = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 60" preserveAspectRatio="xMidYMid slice">'
	. '<defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#79add9"/><stop offset="1" stop-color="#f3d2a6"/></linearGradient></defs>'
	. '<rect width="80" height="60" fill="url(#sky)"/>'
	. '<circle cx="57" cy="19" r="9" fill="#ffd769"/>'
	. '<path d="M0 43 Q20 30 40 41 T80 39 V60 H0 Z" fill="#3f8f7d"/>'
	. '<path d="M0 51 Q26 40 52 49 T80 49 V60 H0 Z" fill="#2b6a62"/>'
	. '</svg>';
$sample = 'data:image/svg+xml,' . rawurlencode( $svg );

/**
 * Render one image-treatment preview: a sample photo wrapped in `.imgs-wrap {class}`
 * so the live Image-Style CSS applies, plus the style's name.
 */
$isp_preview = function ( $class, $label ) use ( $sample ) {
	return '<span class="isp__preview">'
		. '<span class="isp__swatch imgs-wrap ' . esc_attr( $class ) . '">'
		. '<img src="' . esc_attr( $sample ) . '" alt="" />'
		. '</span>'
		. '<span class="isp__name">' . esc_html( $label ) . '</span>'
		. '</span>';
};

$selected_label = ( $value !== '' && isset( $choices[ $value ] ) ) ? $choices[ $value ] : '';
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="isp__input"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>

	<button type="button" class="isp__trigger" aria-haspopup="listbox" aria-expanded="false" onclick="return false;">
		<?php if ( $value !== '' ) : ?>
			<?php echo $isp_preview( $value, $selected_label !== '' ? $selected_label : $value ); ?>
		<?php else : ?>
			<span class="isp__trigger-placeholder"><?php echo esc_html( $placeholder ); ?></span>
		<?php endif; ?>
		<span class="isp__caret" aria-hidden="true">▾</span>
	</button>

	<div class="isp__panel" role="listbox" hidden>
		<?php if ( $allow_none ) : ?>
			<button
				type="button"
				class="isp__option isp__option--empty<?php echo $value === '' ? ' is-selected' : ''; ?>"
				data-value=""
				role="option"
				aria-selected="<?php echo $value === '' ? 'true' : 'false'; ?>"
			>
				<span class="isp__option-label"><?php echo esc_html( isset( $choices[''] ) && $choices[''] !== '' ? $choices[''] : __( '— None —', 'fw' ) ); ?></span>
			</button>
		<?php endif; ?>

		<?php foreach ( $choices as $val => $label ) :
			if ( (string) $val === '' ) { continue; } // the None row is rendered above
			$is_sel = ( (string) $val === (string) $value );
			?>
			<button
				type="button"
				class="isp__option<?php echo $is_sel ? ' is-selected' : ''; ?>"
				data-value="<?php echo esc_attr( $val ); ?>"
				role="option"
				aria-selected="<?php echo $is_sel ? 'true' : 'false'; ?>"
			>
				<?php echo $isp_preview( $val, $label ); ?>
			</button>
		<?php endforeach; ?>
	</div>

</div>
