<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
/**
 * @var  FW_Option_Type_Typography_v2 $typography_v2
 * @var  string $id
 * @var  array $option
 * @var  array $data
 * @var array $defaults
 */

{
	$wrapper_attr = $option['attr'];

	unset(
		$wrapper_attr['value'],
		$wrapper_attr['name']
	);
}

{
	$option['value'] = array_merge( $defaults['value'], (array) $option['value'] );
	$data['value']   = array_merge( $option['value'], is_array($data['value']) ? $data['value'] : array() );
	$google_font     = $typography_v2->get_google_font( $data['value']['family'] );

}

$components = (isset($option['components']) && is_array($option['components']))
	? array_merge($defaults['components'], $option['components'])
	: $defaults['components'];

// Size field control: 'unit' (default) renders a unit-input (px/rem/em); 'number'
// keeps the legacy bare-number input. A legacy scalar size is normalized to a
// { value, unit } array here so the unit-input shows it (the unit-input view drops a
// non-array value), which also means NO editor-load migration is needed.
$size_format = isset( $option['size_format'] ) ? $option['size_format'] : ( isset( $defaults['size_format'] ) ? $defaults['size_format'] : 'unit' );
if ( $size_format === 'unit' ) {
	$sz = $data['value']['size'];
	if ( is_string( $sz ) ) {
		$sz_t = trim( $sz );
		if ( $sz_t !== '' && $sz_t[0] === '{' ) {
			$sz_d = json_decode( $sz_t, true );
			$sz   = is_array( $sz_d ) ? $sz_d : $sz;
		}
	}
	if ( ! is_array( $sz ) ) {
		$sz = ( $sz === '' || $sz === false || $sz === null )
			? array( 'value' => '', 'unit' => 'px' )
			: array( 'value' => (string) $sz, 'unit' => 'px' );
	}
	$sz = array_merge( array( 'value' => '', 'unit' => 'px' ), $sz );
	$size_unit_value = $sz;
}
?>
<div <?php echo fw_attr_to_html( $wrapper_attr ) ?>>
	<?php if ( $components['family'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-family fw-border-box-sizing fw-col-sm-5">
			<select data-type="family" data-value="<?php echo esc_attr($data['value']['family']); ?>"
			        name="<?php echo esc_attr( $option['attr']['name'] ) ?>[family]"
			        class="fw-option-typography-v2-option-family-input">
			</select>

			<div class="fw-inner"><?php _e('Font face', 'fw'); ?></div>
		</div>

		<?php if ( $components['style'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-style fw-border-box-sizing fw-col-sm-3"
		     style="display: <?php echo ( $google_font ) ? 'none' : 'inline-block'; ?>;">
			<select data-type="style" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[style]"
			        class="fw-option-typography-v2-option-style-input">
				<?php foreach (
					array(
						'normal'  => __('Normal', 'fw'),
						'italic'  => __('Italic', 'fw'),
						'oblique' => __('Oblique', 'fw')
					)
					as $key => $style
				): ?>
					<option value="<?php echo esc_attr( $key ) ?>"
					        <?php if ($data['value']['style'] === $key): ?>selected="selected"<?php endif; ?>><?php echo fw_htmlspecialchars( $style ) ?></option>
				<?php endforeach; ?>
			</select>

			<div class="fw-inner"><?php _e( 'Style', 'fw' ); ?></div>
		</div>
		<?php endif; ?>

		<?php if ( $components['weight'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-weight fw-border-box-sizing fw-col-sm-3"
		     style="display: <?php echo ( $google_font ) ? 'none' : 'inline-block'; ?>;">
			<select data-type="weight" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[weight]"
			        class="fw-option-typography-v2-option-weight-input">
				<?php foreach (
					array(
						100 => 100,
						200 => 200,
						300 => 300,
						400 => 400,
						500 => 500,
						600 => 600,
						700 => 700,
						800 => 800,
						900 => 900
					)
					as $key => $style
				): ?>
					<option value="<?php echo esc_attr( $key ) ?>"
					        <?php if ($data['value']['weight'] == $key): ?>selected="selected"<?php endif; ?>><?php echo fw_htmlspecialchars( $style ) ?></option>
				<?php endforeach; ?>
			</select>

			<div class="fw-inner"><?php _e( 'Weight', 'fw' ); ?></div>
		</div>
		<?php endif; ?>

		<?php if ( ! isset( $components['subset'] ) || $components['subset'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-subset fw-border-box-sizing fw-col-sm-2"
		     style="display: <?php echo ( $google_font ) ? 'inline-block' : 'none'; ?>;">
			<select data-type="subset" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[subset]"
			        class="fw-option-typography-v2-option-subset">
				<?php if ( $google_font ) {
					foreach ( $google_font['subsets'] as $subset ) { ?>
						<option value="<?php echo esc_attr( $subset ) ?>"
						        <?php if ($data['value']['subset'] === $subset): ?>selected="selected"<?php endif; ?>><?php echo fw_htmlspecialchars( $subset ); ?></option>
					<?php }
				}
				?>
			</select>

			<div class="fw-inner"><?php _e( 'Script', 'fw' ); ?></div>
		</div>
		<?php endif; ?>


		<?php if ( $components['variation'] ) : ?>
		<div
			class="fw-option-typography-v2-option fw-option-typography-v2-option-variation fw-border-box-sizing fw-col-sm-2"
			style="display: <?php echo ( $google_font ) ? 'inline-block' : 'none'; ?>;">
			<select data-type="variation" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[variation]"
			        class="fw-option-typography-v2-option-variation">
				<?php if ( $google_font ) {
					foreach ( $google_font['variants'] as $variant ) { ?>
						<option value="<?php echo esc_attr( $variant ) ?>"
						        <?php if ($data['value']['variation'] == $variant): ?>selected="selected"<?php endif; ?>><?php echo fw_htmlspecialchars( $variant ); ?></option>
					<?php }
				}
				?>
			</select>

			<div class="fw-inner"><?php esc_html_e( 'Style', 'fw' ); ?></div>
		</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $components['size'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-size fw-border-box-sizing fw-col-sm-2"<?php if ( $size_format === 'unit' ) echo ' data-size-format="unit"'; ?>>
			<?php if ( $size_format === 'unit' ) : ?>
				<?php
				// Unit-input (px/rem/em). Rendered as a sub-control like the colour picker:
				// its name resolves to this option's [size], and it submits a JSON string
				// that _get_value_from_input decodes back to { value, unit }.
				echo fw()->backend->option_type( 'unit-input' )->render(
					'size',
					array(
						'type'  => 'unit-input',
						'label' => false,
						'desc'  => false,
						'units' => array( 'px', 'rem', 'em' ),
						'min'   => 0,
						'value' => $size_unit_value,
					),
					array(
						'value'       => $size_unit_value,
						'id_prefix'   => 'fw-option-' . $id . '-typography-v2-option-',
						'name_prefix' => $data['name_prefix'] . '[' . $id . ']',
					)
				);
				?>
			<?php else : ?>
				<input data-type="size" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[size]"
				       class="fw-option-typography-v2-option-size-input" type="text"
				       value="<?php echo esc_attr( is_array( $data['value']['size'] ) ? '' : $data['value']['size'] ); ?>">
			<?php endif; ?>

			<div class="fw-inner"><?php esc_html_e( 'Size', 'fw' ); ?></div>
		</div>
	<?php endif; ?>

	<?php if ( $components['line-height'] ) : ?>
		<div
			class="fw-option-typography-v2-option fw-option-typography-v2-option-line-height fw-border-box-sizing fw-col-sm-2">
			<input data-type="line-height" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[line-height]"
			       value="<?php echo esc_attr($data['value']['line-height']); ?>"
			       class="fw-option-typography-v2-option-line-height-input" type="text">

			<div class="fw-inner"><?php esc_html_e( 'Line height', 'fw' ); ?></div>
		</div>
	<?php endif; ?>

	<?php if ( $components['letter-spacing'] ) : ?>
		<div
			class="fw-option-typography-v2-option fw-option-typography-v2-option-letter-spacing fw-border-box-sizing fw-col-sm-2">
			<input data-type="letter-spacing" name="<?php echo esc_attr( $option['attr']['name'] ) ?>[letter-spacing]"
			       value="<?php echo esc_attr($data['value']['letter-spacing']); ?>"
			       class="fw-option-typography-v2-option-letter-spacing-input" type="text">

			<div class="fw-inner"><?php esc_html_e( 'Spacing', 'fw' ); ?></div>
		</div>
	<?php endif; ?>

	<?php if ( $components['color'] ) : ?>
		<div class="fw-option-typography-v2-option fw-option-typography-v2-option-color fw-border-box-sizing fw-col-sm-2"
		     data-type="color">
			<?php
			echo fw()->backend->option_type( 'color-picker' )->render(
				'color',
				array(
					'label' => false,
					'desc'  => false,
					'type'  => 'color-picker',
					'value' => $option['value']['color']
				),
				array(
					'value'       => $data['value']['color'],
					'id_prefix'   => 'fw-option-' . $id . '-typography-v2-option-',
					'name_prefix' => $data['name_prefix'] . '[' . $id . ']',
				)
			)
			?>
			<div class="fw-inner"><?php esc_html_e( 'Color', 'fw' ); ?></div>
		</div>
	<?php endif; ?>

</div>
