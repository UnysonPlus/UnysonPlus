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

// Normalize current value against the empty default.
$value = is_array( $data['value'] ) ? $data['value'] : array();
$value = array_merge(
	array(
		'type'  => 'linear',
		'angle' => 90,
		'stops' => array(),
	),
	$value
);

// Is a real gradient set? (>= 2 stops). Empty = "no gradient".
$has_gradient = is_array( $value['stops'] ) && count( $value['stops'] ) >= 2;

// Render the actual stops only. When empty we render NO rows (and a blank preview)
// so the value truly reads as "no gradient" — and, crucially, no color pickers are
// initialised that could fire a change-on-init and auto-seed the value. The user
// starts a gradient via "+ Add color stop" / the preview / mode / angle (seed-on-
// interaction in JS), which fills in the starter stops on demand.
$editor_stops = $has_gradient ? $value['stops'] : array();

$json_input_name = $data['name_prefix'] . '[' . $id . ']';

// CSS string for the read-only output (blank when no gradient).
$output_css = FW_Option_Type_Gradient_V2::to_css( $value );

// Preview bar background — blank when there's no gradient.
$preview_css = '';
if ( $has_gradient ) {
	$css_stops = array();
	foreach ( $editor_stops as $stop ) {
		$css_stops[] = $stop['color'] . ' ' . floatval( $stop['position'] ) . '%';
	}
	$preview_css = $value['type'] === 'radial'
		? 'radial-gradient(circle, ' . implode( ', ', $css_stops ) . ')'
		: 'linear-gradient(' . intval( $value['angle'] ) . 'deg, ' . implode( ', ', $css_stops ) . ')';
}
?>
<div <?php echo fw_attr_to_html( $div_attr ); ?>>

	<input
		type="hidden"
		class="fw-option-type-gradient-v2-json"
		name="<?php echo esc_attr( $json_input_name ); ?>"
		value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>"
	/>

	<!-- Trigger row: read-only CSS output + clear button. Click opens the panel. -->
	<div class="gv2-trigger">
		<input
			type="text"
			class="gv2-output"
			value="<?php echo esc_attr( $output_css ); ?>"
			placeholder="<?php echo esc_attr__( 'Paste or type a CSS gradient — or click to build one', 'fw' ); ?>"
			spellcheck="false"
			autocomplete="off"
			autocapitalize="off"
			aria-haspopup="true"
			aria-expanded="false"
		/>
		<button type="button" class="gv2-clear" title="<?php echo esc_attr__( 'Clear gradient', 'fw' ); ?>"<?php echo $has_gradient ? '' : ' hidden'; ?>>&times;</button>
		<span class="gv2-trigger-caret" aria-hidden="true">▾</span>
	</div>

	<!-- Dropdown panel: the full editor (preview / mode / angle / stops). -->
	<div class="gv2-panel" hidden>

		<div class="gv2-preview" style="background: <?php echo esc_attr( $preview_css ); ?>;">
			<div class="gv2-preview-stops"><?php /* JS renders draggable stop markers here */ ?></div>
		</div>

		<div class="gv2-controls">
			<div class="gv2-mode" role="tablist">
				<button type="button" class="gv2-mode-btn <?php echo $value['type'] === 'linear' ? 'is-active' : ''; ?>" data-mode="linear">
					<?php echo esc_html__( 'Linear', 'fw' ); ?>
				</button>
				<button type="button" class="gv2-mode-btn <?php echo $value['type'] === 'radial' ? 'is-active' : ''; ?>" data-mode="radial">
					<?php echo esc_html__( 'Radial', 'fw' ); ?>
				</button>
			</div>

			<div class="gv2-angle" <?php if ( $value['type'] === 'radial' ) { echo 'style="display:none;"'; } ?>>
				<label class="gv2-angle-label"><?php echo esc_html__( 'Angle', 'fw' ); ?></label>
				<div class="gv2-angle-knob" title="<?php echo esc_attr__( 'Drag to rotate', 'fw' ); ?>">
					<div class="gv2-angle-knob-dot" style="transform: rotate(<?php echo intval( $value['angle'] ); ?>deg);"></div>
				</div>
				<input type="number" class="gv2-angle-input" min="0" max="360" step="1" value="<?php echo intval( $value['angle'] ); ?>" />
				<span class="gv2-angle-unit">&deg;</span>
			</div>
		</div>

		<div class="gv2-stops-panel">
			<div class="gv2-stops-header"><?php echo esc_html__( 'Stops', 'fw' ); ?></div>
			<div class="gv2-stops-list">
				<?php foreach ( $editor_stops as $i => $stop ) : ?>
					<div class="gv2-stop" data-index="<?php echo intval( $i ); ?>">
						<span class="gv2-stop-swatch" style="background: <?php echo esc_attr( $stop['color'] ); ?>;"></span>
						<input
							type="text"
							class="gv2-stop-color fw-option-type-rgba-color-picker"
							value="<?php echo esc_attr( $stop['color'] ); ?>"
							data-alpha="true"
						/>
						<input
							type="number"
							class="gv2-stop-position"
							min="0" max="100" step="1"
							value="<?php echo floatval( $stop['position'] ); ?>"
						/>
						<span class="gv2-stop-unit">%</span>
						<button type="button" class="gv2-stop-remove" title="<?php echo esc_attr__( 'Remove', 'fw' ); ?>">&times;</button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="gv2-add-stop"><?php echo esc_html__( '+ Add color stop', 'fw' ); ?></button>
		</div>

		<div class="gv2-panel-footer">
			<button type="button" class="gv2-panel-clear"><?php echo esc_html__( 'Clear gradient', 'fw' ); ?></button>
		</div>

	</div>

</div>
