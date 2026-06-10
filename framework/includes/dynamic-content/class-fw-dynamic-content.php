<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Dynamic Content — registry + resolver.
 *
 * Single source of truth shared by:
 *   - the admin picker (icon + popover next to Text/Short Text/Textarea/Rich Editor fields), and
 *   - the frontend resolver (turns {{tokens}} into live values at shortcode render time).
 *
 * A "tag" is a named dynamic value (post_title, site_name, current_year, …). Tags are registered
 * on the `fw:dynamic-content:tags` filter, so adding a provider later (ACF, Pods, Toolset) is just
 * a new file that hooks that one filter — no changes to this class, the picker, or the resolver.
 *
 * Token syntax (inserted by the picker, resolved on the frontend):
 *   {{tag_id}}                                  simple
 *   {{tag_id|param=value|fallback=Some text}}   parameterized, with optional fallback
 *
 * Double braces + pipe-delimited params are collision-resistant and survive wp_kses_post() /
 * esc_attr() untouched, so the consuming view's existing escaping still applies to the resolved
 * value (we never echo HTML here — only return plain strings).
 */
final class FW_Dynamic_Content {

	/** @var FW_Dynamic_Content */
	private static $instance;

	/**
	 * Built tag registry: id => array('id','label','group','params','resolve').
	 * Null until first built; then cached for the request.
	 * @var array|null
	 */
	private $tags = null;

	private function __construct() {}

	/**
	 * @return FW_Dynamic_Content
	 */
	public static function _get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Markup for the picker trigger appended next to supported fields.
	 * The JS reads data-fw-option-type / data-fw-option-id from the surrounding
	 * `.fw-backend-option-descriptor` wrapper, so the button itself stays generic.
	 *
	 * @return string
	 */
	public static function trigger_html() {
		return '<a href="#" class="fw-dynamic-content-trigger dashicons dashicons-database" '
			. 'title="' . esc_attr__( 'Insert Dynamic Content', 'fw' ) . '" '
			. 'aria-label="' . esc_attr__( 'Insert Dynamic Content', 'fw' ) . '"></a>';
	}

	/**
	 * Load the bundled tag definition files once. Done lazily (on first get_tags())
	 * so the WooCommerce class_exists() check inside woocommerce.php runs after
	 * plugins/theme are loaded.
	 */
	private function load_bundled_tags() {
		static $loaded = false;

		if ( $loaded ) {
			return;
		}
		$loaded = true;

		$dir = dirname( __FILE__ ) . '/tags';

		require_once $dir . '/core.php';
		require_once $dir . '/links.php';
		require_once $dir . '/unysonplus.php';
		require_once $dir . '/woocommerce.php';
	}

	/**
	 * Build (once) and return the full tag registry.
	 *
	 * @return array id => array('id','label','group','params','resolve')
	 */
	public function get_tags() {
		if ( null !== $this->tags ) {
			return $this->tags;
		}

		$this->load_bundled_tags();

		/**
		 * Register Dynamic Content tags.
		 *
		 * @param array $tags  Keyed by tag id. Each value is an array:
		 *   'label'   => (string)   label shown in the picker
		 *   'group'   => (string)   picker group heading
		 *   'params'  => (array)    optional param descriptors, each:
		 *                              array('id'=>'key','label'=>'Field key','type'=>'text','default'=>'')
		 *   'resolve' => (callable) function( array $params, array $context ) : scalar
		 */
		$tags = apply_filters( 'fw:dynamic-content:tags', array() );

		$normalized = array();

		foreach ( (array) $tags as $id => $tag ) {
			if ( ! is_array( $tag ) || ! isset( $tag['resolve'] ) || ! is_callable( $tag['resolve'] ) ) {
				continue;
			}

			$normalized[ $id ] = array_merge(
				array(
					'id'     => $id,
					'label'  => $id,
					'group'  => __( 'General', 'fw' ),
					'params' => array(),
				),
				$tag
			);
		}

		return $this->tags = $normalized;
	}

	/**
	 * Resolve every {{tag}} token in a single string.
	 *
	 * @param string $text
	 * @param array  $context  e.g. array('post_id' => 123). Falls back to the global post.
	 * @return string
	 */
	public function resolve( $text, array $context = array() ) {
		if ( ! is_string( $text ) || false === strpos( $text, '{{' ) ) {
			return $text;
		}

		$tags = $this->get_tags();

		return preg_replace_callback(
			'/\{\{\s*([a-z0-9_]+)\s*((?:\|[^}]*)?)\}\}/i',
			function ( $m ) use ( $tags, $context ) {
				$id = strtolower( $m[1] );

				if ( ! isset( $tags[ $id ] ) ) {
					return $m[0]; // unknown tag: leave the token literally, never fatal
				}

				$params   = $this->parse_params( isset( $m[2] ) ? $m[2] : '' );
				$fallback = isset( $params['fallback'] ) ? $params['fallback'] : '';

				$value = call_user_func( $tags[ $id ]['resolve'], $params, $context );
				$value = is_scalar( $value ) ? (string) $value : '';

				return ( '' === $value ) ? $fallback : $value;
			},
			$text
		);
	}

	/**
	 * Resolve tokens in a value of any shape — strings, or nested arrays of strings.
	 * Non-string scalars and structure are left intact.
	 *
	 * @param mixed $value
	 * @param array $context
	 * @return mixed
	 */
	public function resolve_recursive( $value, array $context = array() ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->resolve_recursive( $v, $context );
			}

			return $value;
		}

		return $this->resolve( $value, $context );
	}

	/**
	 * Parse the pipe-delimited param section of a token: "|key=city|fallback=N/A".
	 *
	 * @param string $raw
	 * @return array
	 */
	private function parse_params( $raw ) {
		$params = array();

		$raw = trim( $raw );
		if ( '' === $raw ) {
			return $params;
		}

		$raw = ltrim( $raw, '|' );

		foreach ( explode( '|', $raw ) as $pair ) {
			if ( false === strpos( $pair, '=' ) ) {
				continue;
			}

			list( $k, $v ) = explode( '=', $pair, 2 );

			$k = trim( $k );
			if ( '' !== $k ) {
				$params[ $k ] = trim( $v );
			}
		}

		return $params;
	}

	/**
	 * Trimmed, grouped list for the admin picker (callbacks stripped — JSON-safe).
	 *
	 * @return array group label => array of array('id','label','params')
	 */
	public function get_tags_for_js() {
		$groups = array();

		foreach ( $this->get_tags() as $id => $tag ) {
			$group = $tag['group'];

			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = array();
			}

			$groups[ $group ][] = array(
				'id'     => $id,
				'label'  => $tag['label'],
				'params' => array_values( (array) $tag['params'] ),
			);
		}

		return $groups;
	}
}

if ( ! function_exists( 'fw_dynamic_content' ) ) {
	/**
	 * Dynamic Content registry/resolver accessor.
	 *
	 * @return FW_Dynamic_Content
	 */
	function fw_dynamic_content() {
		return FW_Dynamic_Content::_get_instance();
	}
}
