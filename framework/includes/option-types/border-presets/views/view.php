<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var string $id
 * @var array  $option
 * @var array  $data
 */

$list_item_data = array(
	'id_prefix'   => $data['id_prefix'] . $id . '-',
	'name_prefix' => $data['name_prefix'] . '[' . $id . ']',
);

$box_view = fw_get_framework_directory( '/includes/option-types/border-presets/views/box.php' );
?>
<div
	class="fw-option-type-border-presets"
	<?php echo fw_attr_to_html( $option['attr'] ); ?>
	data-list-item-template="<?php echo fw_htmlspecialchars( fw_render_view( $box_view, array(
		'id'        => '{{- index }}',
		'option'    => $option,
		'box_value' => null,
		'data'      => $list_item_data,
	) ) ); ?>"
>
	<?php
	// Sentinel so the option still submits (as an empty list) when every preset
	// is removed — without it the saved value would fall back to the defaults.
	echo fw()->backend->option_type( 'hidden' )->render( $id, array( 'value' => '~' ), array(
		'id_prefix'   => $data['id_prefix'],
		'name_prefix' => $data['name_prefix'],
	) );
	?>
	<div class="fw-option-type-border-presets-list">
		<?php
		foreach ( $data['value'] as $list_item_value ) {
			echo fw_render_view( $box_view, array(
				'id'        => fw_unique_increment(),
				'option'    => $option,
				'box_value' => $list_item_value,
				'data'      => $list_item_data,
			) );
		}
		?>
	</div>

	<div class="fw-option-type-border-presets-controls">
		<a href="#" class="fw-option-type-border-presets-add button button-primary dashicons-before dashicons-plus">
			<?php esc_html_e( 'Add Box Preset', 'fw' ); ?>
		</a>
	</div>
</div>
