<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * One border preset box.
 *
 * @var string $id
 * @var array  $option    (has resolved '_bp' schema)
 * @var array  $box_value
 * @var array  $data
 */

$bp            = $option['_bp'];
$shared_top    = $bp['shared_top'];
$shared_bottom = $bp['shared_bottom'];
$state_rows    = $bp['state_option_rows'];
$states        = $bp['states'];

$values = is_array( $box_value ) ? $box_value : array();

$box_name_prefix = $data['name_prefix'] . '[' . $id . ']';
$box_id_prefix   = $data['id_prefix'] . $id . '-';

$preview_label = ( isset( $values['preset_name'] ) && $values['preset_name'] !== '' )
	? $values['preset_name']
	: __( 'Border Preset', 'fw' );

$state_values = isset( $values['states'] ) && is_array( $values['states'] ) ? $values['states'] : array();

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
<div class="fw-option-type-border-presets-item is-collapsed">
	<div class="fw-option-type-border-presets-item-header">
		<span class="fw-option-type-border-presets-item-handle" title="<?php echo esc_attr__( 'Drag to reorder', 'fw' ); ?>"></span>
		<span class="fw-option-type-border-presets-item-title"><?php echo esc_html( $preview_label ); ?></span>
		<a href="#" class="fw-option-type-border-presets-item-toggle dashicons-before dashicons-arrow-up-alt2" title="<?php echo esc_attr__( 'Collapse / expand', 'fw' ); ?>"></a>
		<a href="#" class="fw-option-type-border-presets-item-remove dashicons-before dashicons-no-alt" title="<?php echo esc_attr__( 'Remove', 'fw' ); ?>"></a>
	</div>

	<div class="fw-option-type-border-presets-item-body">

		<!-- Shared: name -->
		<div class="fw-bp-shared fw-bp-shared-top">
			<?php echo $render( $shared_top, $values, $box_name_prefix, $box_id_prefix ); ?>
		</div>

		<!-- Live preview -->
		<div class="fw-bp-preview">
			<div class="fw-bp-preview-toolbar">
				<div class="fw-bp-swatch-toggle">
					<button type="button" class="fw-bp-swatch is-active" data-swatch="light" title="<?php echo esc_attr__( 'Light', 'fw' ); ?>"></button>
					<button type="button" class="fw-bp-swatch fw-bp-swatch-dark" data-swatch="dark" title="<?php echo esc_attr__( 'Dark', 'fw' ); ?>"></button>
				</div>
			</div>
			<div class="fw-bp-preview-stage is-light">
				<div class="fw-bd-card"><?php esc_html_e( 'Card content', 'fw' ); ?></div>
			</div>
			<style class="fw-bp-preview-style"></style>
		</div>

		<!-- State tabs (Default / Hover) -->
		<div class="fw-bp-states">
			<ul class="fw-bp-tabs" role="tablist">
				<?php $first = true; foreach ( $states as $state => $label ) : ?>
					<li class="fw-bp-tab<?php echo $first ? ' is-active' : ''; ?>" data-bp-tab="<?php echo esc_attr( $state ); ?>" role="tab">
						<span class="fw-bp-tab-label"><?php echo esc_html( $label ); ?></span>
					</li>
				<?php $first = false; endforeach; ?>
			</ul>
			<div class="fw-bp-panels">
				<?php $first = true; foreach ( $states as $state => $label ) :
					$sv          = isset( $state_values[ $state ] ) ? $state_values[ $state ] : array();
					$name_prefix = $box_name_prefix . '[states][' . $state . ']';
					$id_prefix   = $box_id_prefix . 'states-' . $state . '-';
					?>
					<div class="fw-bp-panel<?php echo $first ? ' is-active' : ''; ?>" data-bp-panel="<?php echo esc_attr( $state ); ?>">
						<?php foreach ( $state_rows as $row_opts ) : ?>
							<div class="fw-bp-row fw-bp-row-<?php echo count( $row_opts ); ?>">
								<?php echo $render( $row_opts, $sv, $name_prefix, $id_prefix ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php $first = false; endforeach; ?>
			</div>
		</div>

		<!-- Shared: sides + radius + transition + custom CSS -->
		<div class="fw-bp-shared fw-bp-shared-bottom">
			<?php echo $render( $shared_bottom, $values, $box_name_prefix, $box_id_prefix ); ?>
		</div>

	</div>
</div>
