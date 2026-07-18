<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * One preset box.
 *
 * @var string $id        box index (a number, or "{{- index }}" in the template)
 * @var array  $option    (has resolved '_bp' schema)
 * @var array  $box_value
 * @var array  $data      ['id_prefix' => option-level, 'name_prefix' => option-level]
 * @var bool   $defer     when true, the heavy body is stashed in data-bp-body and the
 *                        JS hydrates it (and inits its widgets) only on first expand.
 */

$bp            = $option['_bp'];
$shared_top    = $bp['shared_top'];
$shared_bottom = $bp['shared_bottom'];
$state_rows    = $bp['state_option_rows'];
$states        = $bp['states'];

$values = is_array( $box_value ) ? $box_value : array();

$box_name_prefix = $data['name_prefix'] . '[' . $id . ']';
$box_id_prefix   = $data['id_prefix'] . $id . '-';

$preview_label = ( isset( $values['color_name'] ) && $values['color_name'] !== '' )
	? $values['color_name']
	: __( 'Button', 'fw' );

$state_values = isset( $values['states'] ) && is_array( $values['states'] ) ? $values['states'] : array();

/**
 * DEFER the heavy body? Each preset body renders a typography-v2 font picker, a
 * CodeMirror code-editor and (× 5 state tabs) a gradient-v2 + box-shadow + compact
 * color pickers. Initializing every preset's widgets at once is what made opening
 * the Buttons tab take several seconds. When $defer is true we emit the body HTML
 * into data-bp-body instead of live DOM; the JS hydrates + initializes it only when
 * the box is first expanded, so the tab opens instantly. The collapsed header still
 * previews correctly (the JS parses data-bp-body without initializing widgets), and
 * any never-expanded body is materialized on form submit so it always saves. The
 * "Add Button Preset" template renders NON-deferred (one new box is cheap, and the
 * user expands it to edit anyway).
 */
$defer = ! empty( $defer );

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

// Build the (heavy) body markup once, into a buffer, so it can be emitted live OR
// stashed in data-bp-body depending on $defer.
ob_start();
?>
	<!-- Shared: name + font -->
	<div class="fw-bp-shared fw-bp-shared-top">
		<?php echo $render( $shared_top, $values, $box_name_prefix, $box_id_prefix ); ?>
	</div>

	<!-- Live preview stage. The scoped <style> is NOT here — it lives in the header
	     (below) so the collapsed header title previews correctly even before the body
	     is hydrated. -->
	<div class="fw-bp-preview">
		<div class="fw-bp-preview-toolbar">
			<div class="fw-bp-swatch-toggle">
				<button type="button" class="fw-bp-swatch is-active" data-swatch="light" title="<?php echo esc_attr__( 'Light', 'fw' ); ?>"></button>
				<button type="button" class="fw-bp-swatch fw-bp-swatch-dark" data-swatch="dark" title="<?php echo esc_attr__( 'Dark', 'fw' ); ?>"></button>
			</div>
		</div>
		<div class="fw-bp-preview-stage is-light">
			<a href="#" class="fw-bp-btn btn" onclick="return false;"><?php echo esc_html( $preview_label ); ?></a>
		</div>
	</div>

	<!-- State tabs (Background-Pro style) -->
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

	<!-- Shared: transition + custom CSS -->
	<div class="fw-bp-shared fw-bp-shared-bottom">
		<?php echo $render( $shared_bottom, $values, $box_name_prefix, $box_id_prefix ); ?>
	</div>
<?php
$body_html = ob_get_clean();
?>
<div class="fw-option-type-button-presets-item is-collapsed<?php echo $defer ? ' fw-bp-deferred' : ''; ?>" data-bp-index="<?php echo esc_attr( $id ); ?>"<?php echo $defer ? ' data-bp-body="' . esc_attr( $body_html ) . '"' : ''; ?>>
	<div class="fw-option-type-button-presets-item-header">
		<span class="fw-option-type-button-presets-item-handle" title="<?php echo esc_attr__( 'Drag to reorder', 'fw' ); ?>"></span>
		<span class="fw-option-type-button-presets-item-title btn"><?php echo esc_html( $preview_label ); ?></span>
		<a href="#" class="fw-option-type-button-presets-item-duplicate dashicons-before dashicons-admin-page" title="<?php echo esc_attr__( 'Duplicate', 'fw' ); ?>"></a>
		<a href="#" class="fw-option-type-button-presets-item-remove dashicons-before dashicons-no-alt" title="<?php echo esc_attr__( 'Remove', 'fw' ); ?>"></a>
		<a href="#" class="fw-option-type-button-presets-item-toggle dashicons-before dashicons-arrow-up-alt2" title="<?php echo esc_attr__( 'Collapse / expand', 'fw' ); ?>"></a>
	</div>

	<!-- Scoped preview <style> — kept OUTSIDE the (deferrable) body so it applies to
	     the collapsed header title before the body is hydrated. -->
	<style class="fw-bp-preview-style"></style>

	<div class="fw-option-type-button-presets-item-body"><?php echo $defer ? '' : $body_html; // phpcs:ignore ?></div>
</div>
