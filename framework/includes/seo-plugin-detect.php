<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Is another SEO plugin handling this site's meta tags?
 *
 * Lives in core rather than in the SEO extension because two things that run
 * BEFORE the extension need the answer: the default-extension seed (which must
 * not switch SEO on for a site already running Yoast) and the conflict notice.
 *
 * The parent theme carries its own copy of this check — it has to, since the
 * theme must work with no plugin at all. Keep the two lists in step.
 */
if ( ! function_exists( 'fw_upw_seo_plugin_active' ) ) :
	/**
	 * @return string The detected plugin's display name, or '' when none is active.
	 */
	function fw_upw_seo_plugin_active() {
		// Checked by constant/class rather than by plugin file, because that is
		// what survives a renamed folder, a mu-plugin install, or a fork.
		$known = array(
			'Yoast SEO'      => function () {
				return defined( 'WPSEO_VERSION' );
			},
			'Rank Math'      => function () {
				return class_exists( 'RankMath' );
			},
			'SEOPress'       => function () {
				return defined( 'SEOPRESS_VERSION' );
			},
			'All in One SEO' => function () {
				return defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' );
			},
			'The SEO Framework' => function () {
				return defined( 'THE_SEO_FRAMEWORK_VERSION' );
			},
			'Slim SEO'       => function () {
				return defined( 'SLIM_SEO_VER' );
			},
		);

		foreach ( $known as $label => $test ) {
			if ( $test() ) {
				/** Filters the name of the detected third-party SEO plugin ('' = none). */
				return (string) apply_filters( 'fw_upw_seo_plugin_active', $label );
			}
		}

		return (string) apply_filters( 'fw_upw_seo_plugin_active', '' );
	}
endif;
