<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Reusable device-tabs component (Phone / Tablet / Desktop).
 *
 * Renders the three-icon device switcher used by responsive option controls
 * (spacing first; typography-v2 / background-pro can adopt it later). The
 * matching JS lives in framework/static/js/fw-device-tabs.js and keeps the
 * tabs in sync with the page builder's global device toggle
 * (window.fwPbDevice + the `fw:builder:device-preview` event from
 * device-preview.js).
 *
 * Convention an option type must follow to use this:
 *   - add the `fw-device-host` class to the option's outer wrapper,
 *   - drop fw_render_device_tabs( $id ) into its header,
 *   - give each per-device panel `data-fw-device-panel="{key}"`.
 * The component toggles `.is-active` on the active tab and panel.
 *
 * Keys are mobile-first and Bootstrap-native:
 *   base → no breakpoint infix, applies at all widths (the "phone"/base layer)
 *   md   → @media (min-width:768px)   (tablet override)
 *   lg   → @media (min-width:992px)   (desktop override)
 * The global device slugs (lg|md|sm) map to keys via sm→base, md→md, lg→lg.
 */

if ( ! function_exists( 'fw_device_tabs_definition' ) ) :
	/**
	 * The three device tabs, displayed Desktop → Tablet → Phone (the familiar
	 * Elementor order). This is display order only — the cascade stays mobile-
	 * first / Bootstrap-native: `key` 'base' (Phone) is the min-width:0 layer that
	 * applies at all widths, 'md'/'lg' are the ≥768 / ≥992 overrides. `device` is
	 * the builder's global device slug this tab maps to.
	 *
	 * @return array
	 */
	function fw_device_tabs_definition() {
		return array(
			array( 'key' => 'lg',   'device' => 'lg', 'icon' => 'desktop',    'label' => __( 'Desktop (≥ 992px)', 'unysonplus' ) ),
			array( 'key' => 'md',   'device' => 'md', 'icon' => 'tablet',     'label' => __( 'Tablet (≥ 768px)',  'unysonplus' ) ),
			array( 'key' => 'base', 'device' => 'sm', 'icon' => 'smartphone', 'label' => __( 'Phone (base — applies to all unless overridden)', 'unysonplus' ) ),
		);
	}
endif;

if ( ! function_exists( 'fw_render_device_tabs' ) ) :
	/**
	 * Markup for the device switcher. The base (phone) tab is marked active by
	 * default; fw-device-tabs.js re-syncs the active tab to window.fwPbDevice on
	 * init and on every `fw:builder:device-preview` event.
	 *
	 * @param string $id A unique-ish id for the host (used only for data-attr).
	 * @return string
	 */
	function fw_render_device_tabs( $id = '' ) {
		$html = '<div class="fw-device-tabs" data-fw-device-tabs="' . esc_attr( $id ) . '">';
		foreach ( fw_device_tabs_definition() as $tab ) {
			$active = ( $tab['key'] === 'base' ) ? ' is-active' : '';
			$html  .= '<button type="button" class="fw-device-tab' . $active . '"'
				. ' data-fw-device-key="' . esc_attr( $tab['key'] ) . '"'
				. ' data-device="' . esc_attr( $tab['device'] ) . '"'
				. ' title="' . esc_attr( $tab['label'] ) . '"'
				. ' aria-label="' . esc_attr( $tab['label'] ) . '">'
				. '<span class="dashicons dashicons-' . esc_attr( $tab['icon'] ) . '"></span>'
				. '</button>';
		}
		$html .= '</div>';
		return $html;
	}
endif;
