<?php if (!defined('FW')) die('Forbidden');

class FW_Container_Type_Group extends FW_Container_Type {
	public function get_type() {
		return 'group';
	}

	protected function _get_defaults() {
		return array();
	}

	protected function _enqueue_static($id, $option, $values, $data) {
		//
	}

	protected function _render($containers, $values, $data) {
		$html = '';

		foreach ( $containers as $id => &$group ) {
			// prepare attributes
			{
				$attr = isset( $group['attr'] ) ? $group['attr'] : array();

				$attr['id'] = 'fw-backend-options-group-' . $id;

				if ( ! isset( $attr['class'] ) ) {
					$attr['class'] = 'fw-backend-options-group';
				} else {
					$attr['class'] = 'fw-backend-options-group ' . $attr['class'];
				}
			}

			$html .= '<div ' . fw_attr_to_html( $attr ) . '>';
			// Optional native heading + note for a group box, rendered only when the author set
			// them. `title` is a small uppercase "eyebrow" section label (deliberately NOT a
			// content <h4>, so it marks a section without competing with the field labels);
			// `desc` is a muted note allowing inline markup (e.g. <strong>). The 27px inset
			// matches the option rows' horizontal padding so the label lines up with the fields.
			//
			// IMPORTANT: FW_Container_Type::render() auto-fills `title` with fw_id_to_title($id)
			// for EVERY container when none is given, so an untitled group (or an FW-internal one
			// like a multi-picker's) would otherwise leak its humanised id (e.g. "Group Container").
			// Only render when the title differs from that auto value = an intentional label.
			$auto_title = function_exists( 'fw_id_to_title' ) ? fw_id_to_title( $id ) : '';
			if ( ! empty( $group['title'] ) && $group['title'] !== $auto_title ) {
				// Decode first so a title written with entities (e.g. "&amp;") isn't double-escaped.
				$html .= '<div class="fw-backend-options-group__title" style="margin:22px 0 2px;padding:0 27px;font-size:11px;font-weight:600;line-height:1.4;letter-spacing:.06em;text-transform:uppercase;color:#646970">'
					. esc_html( html_entity_decode( (string) $group['title'], ENT_QUOTES ) ) . '</div>';
			}
			if ( ! empty( $group['desc'] ) ) {
				$html .= '<p class="fw-backend-options-group__desc" style="margin:0 0 14px;padding:0 27px;color:#646970;font-size:12.5px;line-height:1.5">'
					. wp_kses_post( $group['desc'] ) . '</p>';
			}
			$html .= fw()->backend->render_options( $group['options'], $values, $data );
			$html .= '</div>';
		}

		return $html;
	}
}
