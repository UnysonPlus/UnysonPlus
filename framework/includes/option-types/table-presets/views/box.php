<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * One table preset box.
 *
 * @var string $id
 * @var array  $option    (has resolved '_tp' schema)
 * @var array  $box_value
 * @var array  $data
 */

$tp            = $option['_tp'];
$shared_top    = $tp['shared_top'];
$shared_bottom = $tp['shared_bottom'];
$sections      = $tp['sections'];
$section_rows  = $tp['section_rows'];

$values = is_array( $box_value ) ? $box_value : array();

$box_name_prefix = $data['name_prefix'] . '[' . $id . ']';
$box_id_prefix   = $data['id_prefix'] . $id . '-';

$preview_label = ( isset( $values['preset_name'] ) && $values['preset_name'] !== '' )
	? $values['preset_name']
	: __( 'Table Preset', 'fw' );

$section_values = isset( $values['sections'] ) && is_array( $values['sections'] ) ? $values['sections'] : array();

$render = function ( $opts, $vals, $name_prefix, $id_prefix ) {
	return fw()->backend->render_options(
		$opts,
		is_array( $vals ) ? $vals : array(),
		array(
			'id_prefix'   => $id_prefix,
			'name_prefix' => $name_prefix,
		)
	);
};
?>
<div class="fw-option-type-table-presets-item is-collapsed" data-bp-index="<?php echo esc_attr( $id ); ?>">
	<div class="fw-option-type-table-presets-item-header">
		<span class="fw-option-type-table-presets-item-handle" title="<?php echo esc_attr__( 'Drag to reorder', 'fw' ); ?>"></span>
		<span class="fw-option-type-table-presets-item-title"><?php echo esc_html( $preview_label ); ?></span>
		<a href="#" class="fw-option-type-table-presets-item-duplicate dashicons-before dashicons-admin-page" title="<?php echo esc_attr__( 'Duplicate', 'fw' ); ?>"></a>
		<a href="#" class="fw-option-type-table-presets-item-remove dashicons-before dashicons-no-alt" title="<?php echo esc_attr__( 'Remove', 'fw' ); ?>"></a>
		<a href="#" class="fw-option-type-table-presets-item-toggle dashicons-before dashicons-arrow-up-alt2" title="<?php echo esc_attr__( 'Collapse / expand', 'fw' ); ?>"></a>
	</div>

	<div class="fw-option-type-table-presets-item-body">

		<!-- Shared: name -->
		<div class="fw-tp-shared fw-tp-shared-top">
			<?php echo $render( $shared_top, $values, $box_name_prefix, $box_id_prefix ); ?>
		</div>

		<!-- Live preview -->
		<div class="fw-tp-preview">
			<div class="fw-tp-preview-stage">
				<div class="fw-tp-card">
					<table>
						<thead><tr><th>Name</th><th>Plan</th><th>Score</th></tr></thead>
						<tbody>
							<tr><td>Alpha</td><td>Pro</td><td>9.4</td></tr>
							<tr><td>Bravo</td><td>Lite</td><td>8.1</td></tr>
							<tr><td>Cosmo</td><td>Pro</td><td>7.8</td></tr>
						</tbody>
						<tfoot><tr><td>Total</td><td>3</td><td>25.3</td></tr></tfoot>
					</table>
				</div>
			</div>
			<style class="fw-tp-preview-style"></style>
		</div>

		<!-- Section tabs -->
		<div class="fw-tp-sections">
			<ul class="fw-tp-tabs" role="tablist">
				<?php $first = true; foreach ( $sections as $section => $label ) : ?>
					<li class="fw-tp-tab<?php echo $first ? ' is-active' : ''; ?>" data-tp-tab="<?php echo esc_attr( $section ); ?>" role="tab">
						<span class="fw-tp-tab-label"><?php echo esc_html( $label ); ?></span>
					</li>
				<?php $first = false; endforeach; ?>
			</ul>
			<div class="fw-tp-panels">
				<?php $first = true; foreach ( $sections as $section => $label ) :
					$sv          = isset( $section_values[ $section ] ) ? $section_values[ $section ] : array();
					$name_prefix = $box_name_prefix . '[sections][' . $section . ']';
					$id_prefix   = $box_id_prefix . 'sections-' . $section . '-';
					?>
					<div class="fw-tp-panel<?php echo $first ? ' is-active' : ''; ?>" data-tp-panel="<?php echo esc_attr( $section ); ?>">
						<?php foreach ( $section_rows[ $section ] as $row_opts ) : ?>
							<div class="fw-tp-row fw-tp-row-<?php echo count( $row_opts ); ?>">
								<?php echo $render( $row_opts, $sv, $name_prefix, $id_prefix ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php $first = false; endforeach; ?>
			</div>
		</div>

		<!-- Shared: structure + transition + custom CSS -->
		<div class="fw-tp-shared fw-tp-shared-bottom">
			<?php echo $render( $shared_bottom, $values, $box_name_prefix, $box_id_prefix ); ?>
		</div>

	</div>
</div>
