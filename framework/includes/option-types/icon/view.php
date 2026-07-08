<?php if (!defined('FW')) die('Forbidden');

/**
 * @var array $option
 * @var array $data
 * @var string $id
 * @var array $set
 */

$wrapper_attr = array(
	'class' => $option['attr']['class'],
	'id'    => $option['attr']['id'],
);
unset($option['attr']['class'], $option['attr']['id']);

// Guard: this type keys $set['icons'] by a plain class string, so a value that
// arrived as an array (a richer icon shape) must never be used as an offset -
// it would throw "Illegal offset type" and take down the whole options page.
if (is_array($data['value'])) {
	$data['value'] = isset($data['value']['icon-class']) && is_string($data['value']['icon-class'])
		? $data['value']['icon-class']
		: (isset($data['value']['icon']) && is_string($data['value']['icon']) ? $data['value']['icon'] : '');
}

$icons = &$set['icons'];

// build $groups array based on $icons
$groups = array();
foreach ($icons as $icon_tab) {
	$group_id = $icon_tab['group'];
	$groups[$group_id] = $set['groups'][$group_id];
}

ksort($icons);
ksort($groups);

?>
<div <?php echo fw_attr_to_html($wrapper_attr) ?>>

	<input <?php echo fw_attr_to_html($option['attr']) ?> type="hidden" />

	<div class="js-option-type-icon-container">

		<?php if (count($groups) > 1): ?>
		<div class="fw-backend-option-fixed-width">
			<select class="js-option-type-icon-dropdown">
				<?php
					echo fw_html_tag('option', array('value' => 'all'), htmlspecialchars(__('All Categories', 'fw')));
					foreach ($groups as $group_id => $group_title) {
						$selected = (isset($set['icons'][$data['value']]['group']) && $set['icons'][$data['value']]['group'] === $group_id);
						echo fw_html_tag('option', array('value' => $group_id, 'selected' => $selected), htmlspecialchars($group_title));
					}
				?>
			</select>
		</div>
		<?php endif; ?>

		<div class="option-type-icon-list js-option-type-icon-list <?php echo esc_attr($set['container-class']) ?>">
			<?php
				foreach ($icons as $icon_id => $icon_tab) {
					$active = ($data['value'] == $icon_id) ? 'active' : '';
					echo fw_html_tag('i', array(
						'class' => "$icon_id js-option-type-icon-item $active",
						'data-value' => $icon_id,
						'data-group' => $icon_tab['group']
					), true);
				}
			?>
		</div>

	</div>

</div>
