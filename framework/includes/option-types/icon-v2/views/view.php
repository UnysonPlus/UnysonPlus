<?php

if (! defined('FW')) { die('Forbidden'); }

/*
echo 'ID';
fw_print($id);
echo 'OPTION';
fw_print($option);
echo 'DATA';
fw_print($data);
echo 'JSON';
fw_print($json);
 */

$wrapper_attr = array(
	// `fw-icon-picker` is a STABLE hook class the picker JS binds to, so this
	// engine works no matter which type id rendered it ('icon-v2' or the
	// reclaimed 'icon'). The Unyson-added fw-option-type-<type> class differs
	// per id; this one is constant across both.
	'class' => $option['attr']['class'] . ' fw-icon-v2-preview-' . $option['preview_size'] . ' fw-icon-picker',
	'id' => $option['attr']['id'],
	'data-fw-modal-size' => $option['popup_size']
);

unset($option['attr']['class'], $option['attr']['id']);

?>

<div <?php echo fw_attr_to_html($wrapper_attr) ?>>
	<input <?php echo fw_attr_to_html($option['attr']) ?> type="hidden" />
</div>

