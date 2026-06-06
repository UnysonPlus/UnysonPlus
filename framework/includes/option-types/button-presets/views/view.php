<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var string $id
 * @var array  $option   (already has resolved 'box-options')
 * @var array  $data
 */

// Mirror addable-box nesting: inputs live at name_prefix[option_id][box_index][field].
// box.php appends the [box_index] segment, so include the option id here.
$list_item_data = array(
	'id_prefix'   => $data['id_prefix'] . $id . '-',
	'name_prefix' => $data['name_prefix'] . '[' . $id . ']',
);

$box_view = fw_get_framework_directory( '/includes/option-types/button-presets/views/box.php' );
?>
<div
	class="fw-option-type-button-presets"
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
	// is removed — mirrors addable-box. Without it the option key is absent and
	// the saved value falls back to the defaults. @see _get_value_from_input().
	echo fw()->backend->option_type( 'hidden' )->render( $id, array( 'value' => '~' ), array(
		'id_prefix'   => $data['id_prefix'],
		'name_prefix' => $data['name_prefix'],
	) );
	?>
	<div class="fw-option-type-button-presets-list">
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

	<div class="fw-option-type-button-presets-controls">
		<a href="#" class="fw-option-type-button-presets-add button button-primary dashicons-before dashicons-plus">
			<?php esc_html_e( 'Add Button Preset', 'fw' ); ?>
		</a>
	</div>
</div>
