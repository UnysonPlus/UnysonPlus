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
$value = array_merge(
	array( 'x' => 0, 'y' => 0, 'blur' => 0, 'spread' => 0, 'color' => '', 'inset' => false ),
	$value
);

$json_input_name = $data['name_prefix'] . '[' . $id . ']';
$css_preview     = FW_Option_Type_Box_Shadow::to_css( $value );
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="fw-box-shadow-json"
		name="<?php echo esc_attr( $json_input_name ); ?>"
		value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>"
	/>

	<!-- Preview: its own row on top -->
	<div class="bsh-preview-wrap">
		<span class="bsh-preview" style="<?php echo $css_preview !== '' ? 'box-shadow:' . esc_attr( $css_preview ) . ';' : ''; ?>"></span>
	</div>

	<!-- Controls: below the preview -->
	<div class="bsh-controls">
		<label class="bsh-field"><span><?php echo esc_html__( 'X', 'fw' ); ?></span>
			<input type="number" class="bsh-num" data-k="x" value="<?php echo (int) $value['x']; ?>" /></label>
		<label class="bsh-field"><span><?php echo esc_html__( 'Y', 'fw' ); ?></span>
			<input type="number" class="bsh-num" data-k="y" value="<?php echo (int) $value['y']; ?>" /></label>
		<label class="bsh-field"><span><?php echo esc_html__( 'Blur', 'fw' ); ?></span>
			<input type="number" class="bsh-num" data-k="blur" min="0" value="<?php echo (int) $value['blur']; ?>" /></label>
		<label class="bsh-field"><span><?php echo esc_html__( 'Spread', 'fw' ); ?></span>
			<input type="number" class="bsh-num" data-k="spread" value="<?php echo (int) $value['spread']; ?>" /></label>
	</div>

	<div class="bsh-row bsh-row-2">
		<label class="bsh-field bsh-color-field"><span><?php echo esc_html__( 'Color', 'fw' ); ?></span>
			<input type="text" class="bsh-color" value="<?php echo esc_attr( $value['color'] ); ?>" data-alpha="true" /></label>
		<label class="bsh-inset"><input type="checkbox" class="bsh-inset-cb" <?php checked( ! empty( $value['inset'] ) ); ?> /> <?php echo esc_html__( 'Inset', 'fw' ); ?></label>
	</div>

	<div class="bsh-output-row">
		<span class="bsh-output-label"><?php echo esc_html__( 'CSS', 'fw' ); ?></span>
		<code class="bsh-output"><?php echo esc_html( $css_preview !== '' ? $css_preview : 'none' ); ?></code>
	</div>

</div>
