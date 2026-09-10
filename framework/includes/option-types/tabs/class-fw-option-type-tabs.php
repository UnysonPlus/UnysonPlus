<?php if ( ! defined( 'FW' ) ) die( 'Forbidden' );

/**
 * Class Option Type Tabs
 *
 * A compact, value-holding tab strip that groups nested options into panels —
 * the in-option tab UI first built for `background-pro` (Color / Gradient /
 * Image / …), extracted here so any option can reuse it: Scroll Keyframes'
 * Start / Middle / End states, normal / hover pairs, per-breakpoint sets, etc.
 *
 * How it differs from the `tab` CONTAINER type: the container organizes the
 * options TREE at the modal's top level and holds no value. This is a real
 * option type — it stores a value ({ tabId: { innerId: val } }) and can live
 * anywhere an option can, including inside a multi-picker choice's group,
 * where the container type cannot.
 *
 * Definition shape:
 *   array(
 *     'type'  => 'tabs',
 *     'value' => array(),            // { tabId => { innerId => value } }
 *     'dots'  => true,               // show a "customized" dot per tab (optional)
 *     'tabs'  => array(
 *        'start' => array(
 *           'title'   => __( 'Start', 'fw' ),
 *           'options' => array(       // a standard Unyson options array
 *              'x' => array( 'type' => 'slider', ... ),
 *              'y' => array( 'type' => 'slider', ... ),
 *           ),
 *        ),
 *        'end' => array( 'title' => __( 'End', 'fw' ), 'options' => array( ... ) ),
 *     ),
 *   )
 *
 * The class_exists guard mirrors background-pro: a stale duplicate on a
 * partially-upgraded deploy won't fatal — the first declaration wins.
 */

if ( ! class_exists( 'FW_Option_Type_Tabs' ) ) :

class FW_Option_Type_Tabs extends FW_Option_Type {

	public function get_type() {
		return 'tabs';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults() {
		return array(
			'tabs'  => array(), // tabId => array( 'title' => ..., 'options' => array( ... ) )
			'value' => array(), // tabId => array( innerId => value )
			'dots'  => false,   // show a per-tab "customized" dot
		);
	}

	/**
	 * All inner options across every tab, flattened — for enqueue + width.
	 */
	private function _all_inner_options( $option ) {
		$all = array();
		if ( ! empty( $option['tabs'] ) && is_array( $option['tabs'] ) ) {
			foreach ( $option['tabs'] as $tab ) {
				if ( ! empty( $tab['options'] ) && is_array( $tab['options'] ) ) {
					$all = array_merge( $all, $tab['options'] );
				}
			}
		}
		return $all;
	}

	/**
	 * @internal
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		static $enqueue = true;

		if ( $enqueue ) {
			$rel = '/includes/option-types/' . $this->get_type() . '/static';
			$uri = fw_get_framework_directory_uri( $rel );
			$dir = fw_get_framework_directory( $rel );
			// Version the admin CSS/JS by file mtime (on top of the framework version), so an
			// edit to these files busts the browser cache immediately — a static version string
			// leaves stale admin CSS/JS cached until a hard refresh, which is easy to miss.
			$base   = fw()->manifest->get_version();
			$cssver = $base . '.' . ( @filemtime( $dir . '/css/styles.css' ) ?: '0' );
			$jsver  = $base . '.' . ( @filemtime( $dir . '/js/scripts.js' ) ?: '0' );
			wp_enqueue_style( 'fw-option-' . $this->get_type(), $uri . '/css/styles.css', array(), $cssver );
			wp_enqueue_script( 'fw-option-' . $this->get_type(), $uri . '/js/scripts.js', array( 'jquery', 'fw-events' ), $jsver, true );
			$enqueue = false;
		}

		// Pull in every nested control's own CSS/JS so panels render live in any context.
		fw()->backend->enqueue_options_static( $this->_all_inner_options( $option ) );

		return true;
	}

	/**
	 * @internal
	 */
	public function _get_backend_width_type() {
		return 'full';
	}

	/**
	 * @internal
	 */
	protected function _render( $id, $option, $data ) {
		$tabs = ( ! empty( $option['tabs'] ) && is_array( $option['tabs'] ) ) ? $option['tabs'] : array();
		if ( empty( $tabs ) ) {
			return '';
		}

		$value = ( isset( $data['value'] ) && is_array( $data['value'] ) ) ? $data['value'] : array();
		$dots  = ! empty( $option['dots'] );

		$wrapper_attr = $option['attr'];
		unset( $wrapper_attr['value'], $wrapper_attr['name'] );
		$wrapper_attr['class'] = trim( ( isset( $wrapper_attr['class'] ) ? $wrapper_attr['class'] : '' ) . ' fw-option-type-tabs' );

		$active_tab = (string) key( $tabs );

		ob_start();
		?>
		<div <?php echo fw_attr_to_html( $wrapper_attr ); ?>>
			<ul class="fw-tabs__tabs" role="tablist">
				<?php foreach ( $tabs as $tab_id => $tab ) :
					$title     = isset( $tab['title'] ) ? $tab['title'] : $tab_id;
					$has_value = $dots && $this->_tab_customized( $tab, fw_akg( $tab_id, $value, array() ) );
					?>
					<li class="fw-tabs__tab<?php echo $tab_id === $active_tab ? ' is-active' : ''; ?><?php echo $has_value ? ' has-value' : ''; ?>"
					    data-fw-tab="<?php echo esc_attr( $tab_id ); ?>"
					    role="tab" tabindex="0">
						<?php if ( $dots ) : ?><span class="fw-tabs__dot" aria-hidden="true"></span><?php endif; ?>
						<span class="fw-tabs__label"><?php echo esc_html( $title ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="fw-tabs__panels">
				<?php foreach ( $tabs as $tab_id => $tab ) :
					$inner = ( ! empty( $tab['options'] ) && is_array( $tab['options'] ) ) ? $tab['options'] : array();
					?>
					<div class="fw-tabs__panel<?php echo $tab_id === $active_tab ? ' is-active' : ''; ?>" data-fw-panel="<?php echo esc_attr( $tab_id ); ?>">
						<?php
						echo fw()->backend->render_options(
							$inner,
							fw_akg( $tab_id, $value, array() ),
							array(
								'id_prefix'   => $data['id_prefix'] . $id . '-' . $tab_id . '-',
								'name_prefix' => $data['name_prefix'] . '[' . $id . '][' . $tab_id . ']',
							)
						);
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Whether a tab's saved value differs from its inner options' defaults —
	 * drives the optional "customized" dot. Cheap deep compare; false positives
	 * are harmless (a dot shows), false negatives just hide a dot.
	 */
	private function _tab_customized( $tab, $tab_value ) {
		if ( empty( $tab['options'] ) || ! is_array( $tab['options'] ) ) {
			return false;
		}
		if ( empty( $tab_value ) || ! is_array( $tab_value ) ) {
			return false;
		}
		foreach ( fw_extract_only_options( $tab['options'] ) as $inner_id => $inner_option ) {
			$default = isset( $inner_option['value'] ) ? $inner_option['value'] : null;
			$current = isset( $tab_value[ $inner_id ] ) ? $tab_value[ $inner_id ] : null;
			if ( $current !== null && $current !== $default ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @internal
	 *
	 * Per-tab, per-inner value extraction — delegate to each child option type
	 * so uploads stay upload arrays, sliders stay clamped, etc. (mirrors `multi`).
	 */
	protected function _get_value_from_input( $option, $input_value ) {
		$value = ( is_array( $input_value ) || empty( $option['value'] ) ) ? array() : $option['value'];

		if ( empty( $option['tabs'] ) || ! is_array( $option['tabs'] ) ) {
			return $value;
		}

		foreach ( $option['tabs'] as $tab_id => $tab ) {
			$inner_opts = ( ! empty( $tab['options'] ) && is_array( $tab['options'] ) ) ? $tab['options'] : array();
			if ( ! isset( $value[ $tab_id ] ) || ! is_array( $value[ $tab_id ] ) ) {
				$value[ $tab_id ] = array();
			}
			$tab_input = ( is_array( $input_value ) && isset( $input_value[ $tab_id ] ) ) ? $input_value[ $tab_id ] : null;

			foreach ( fw_extract_only_options( $inner_opts ) as $inner_id => $inner_option ) {
				$value[ $tab_id ][ $inner_id ] = fw()->backend->option_type( $inner_option['type'] )->get_value_from_input(
					isset( $value[ $tab_id ][ $inner_id ] )
						? array_merge( $inner_option, array( 'value' => $value[ $tab_id ][ $inner_id ] ) )
						: $inner_option,
					( is_array( $tab_input ) && isset( $tab_input[ $inner_id ] ) ) ? $tab_input[ $inner_id ] : null
				);
			}
		}

		return $value;
	}

	/**
	 * @internal
	 * No auto-generated label row around the whole control (mirrors `multi`) —
	 * each inner option renders its own label.
	 */
	public function _get_data_for_js( $id, $option, $data = array() ) {
		return false;
	}
}

FW_Option_Type::register( 'FW_Option_Type_Tabs' );

endif; // class_exists guard
