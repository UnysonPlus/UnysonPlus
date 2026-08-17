<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Shared rate limiter for unauthenticated endpoints.
 *
 * ## Why this exists
 *
 * The framework registers a handful of `wp_ajax_nopriv_*` actions so that
 * front-end features work for logged-out visitors — load-more, filtering,
 * newsletter signup, WooCommerce quick view. Each of those handlers is written
 * carefully: they verify a nonce, sanitize input and validate it.
 *
 * A nonce is not a secret on a public page. It is printed into the HTML that
 * every visitor receives, so anyone can read it and replay the endpoint as fast
 * as they can open connections. Nonce verification proves the request came from
 * a page we rendered; it says nothing about how many times.
 *
 * That matters most where a request costs the site something. The newsletter
 * handler sends an email through wp_mail() on every successful call — an
 * unthrottled loop against it is an outbound mail flood, an unusable inbox and,
 * on a shared host, a damaged sending reputation. The query-backed endpoints are
 * cheaper per call but still run unbounded WP_Query work.
 *
 * ## What this is not
 *
 * This is a courtesy limiter, not a DDoS defence. It runs inside PHP, which
 * means WordPress has already booted by the time it says no. Anything at that
 * scale belongs in front of the application — a CDN, a WAF, fail2ban. The goal
 * here is narrower and worth having: stop one bored visitor with a loop from
 * flooding a mailbox or hammering the database.
 *
 * @since 2.16.20
 */

if ( ! function_exists( 'fw_rate_limit_id' ) ) :
	/**
	 * The identity a limit is counted against.
	 *
	 * The client IP, hashed. Hashed because this lands in the options table on
	 * sites with no persistent object cache, and a plugin should not quietly
	 * accumulate a log of visitor IP addresses to solve a throttling problem.
	 * A salted hash counts just as well and is not personal data at rest.
	 *
	 * REMOTE_ADDR only, deliberately. `X-Forwarded-For` is attacker-controlled
	 * unless a known proxy sets it, so trusting it by default would let anyone
	 * bypass every limit by varying one header. Sites genuinely behind a proxy
	 * can supply the real address through the filter.
	 *
	 * @return string
	 */
	function fw_rate_limit_id() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';

		/**
		 * Filter the raw client address used for rate limiting.
		 *
		 * Use this behind a load balancer or CDN, and validate the proxy before
		 * trusting its header.
		 *
		 * @param string $ip Raw address from REMOTE_ADDR.
		 */
		$ip = (string) apply_filters( 'fw_rate_limit_client_ip', $ip );

		return md5( 'fw-rl|' . $ip . '|' . wp_salt( 'nonce' ) );
	}
endif;

if ( ! function_exists( 'fw_rate_limit_exceeded' ) ) :
	/**
	 * Count one hit against `$action` and report whether the caller is over budget.
	 *
	 * Sliding-ish window: the first hit starts a counter that expires after
	 * `$window` seconds, so a burst is measured from its own beginning rather
	 * than against wall-clock buckets an attacker could straddle.
	 *
	 * Users who can edit posts are exempt. They are authenticated, already
	 * trusted with far more damaging capabilities, and are the people most
	 * likely to trip a limit while legitimately testing a form.
	 *
	 * Fails OPEN. If the transient layer misbehaves, visitors keep working and
	 * the site loses throttling — the opposite choice would let a cache problem
	 * take the front end down, which is a worse failure than the one being
	 * prevented.
	 *
	 * @param string $action Identifier for the endpoint being limited.
	 * @param int    $limit  Allowed hits per window.
	 * @param int    $window Window length in seconds.
	 * @return bool True when the request should be refused.
	 */
	function fw_rate_limit_exceeded( $action, $limit = 30, $window = 60 ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return false;
		}

		/**
		 * Filter a limit before it is applied, or disable one entirely.
		 *
		 * Return 0 (or less) for `$limit` to switch the limiter off for an action.
		 *
		 * @param int    $limit
		 * @param string $action
		 * @param int    $window
		 */
		$limit = (int) apply_filters( 'fw_rate_limit', (int) $limit, $action, (int) $window );

		if ( $limit <= 0 ) {
			return false;
		}

		$key    = 'fw_rl_' . md5( $action . '|' . fw_rate_limit_id() );
		$window = max( 1, (int) $window );
		$hits   = get_transient( $key );

		if ( false === $hits ) {
			set_transient( $key, 1, $window );

			return false;
		}

		$hits = (int) $hits + 1;

		/**
		 * Re-set with the window's REMAINING time, not the full window.
		 *
		 * set_transient() has no "update value, keep expiry" mode, and passing 0
		 * as the expiry does not mean "leave it alone" — it means NEVER EXPIRE.
		 * Writing 0 here would turn the counter permanent and lock a visitor out
		 * of the endpoint for the life of the site.
		 *
		 * Carrying the remaining time forward keeps the window fixed from its
		 * first hit, so a burst is measured from its own beginning and always
		 * drains. `_transient_timeout_*` is absent when a persistent object cache
		 * is handling transients; there the fall back to the full window makes it
		 * a sliding window instead — slightly stricter, still self-draining, which
		 * is the right way to be wrong.
		 */
		$timeout   = (int) get_option( '_transient_timeout_' . $key );
		$remaining = $timeout > 0 ? $timeout - time() : $window;

		if ( $remaining < 1 ) {
			$remaining = $window;
		}

		set_transient( $key, $hits, $remaining );

		return $hits > $limit;
	}
endif;

if ( ! function_exists( 'fw_rate_limit_ajax' ) ) :
	/**
	 * Rate-limit guard for an AJAX handler.
	 *
	 * Ends the request with a 429 and a JSON error when the limit is passed, so a
	 * caller can use it as a one-line guard directly after its nonce check.
	 *
	 * 429 (not 403) because the request was well-formed and authorized — it was
	 * simply too frequent, and a client that reads status codes should know the
	 * difference between "never do this" and "not so fast".
	 *
	 * @param string $action Identifier for the endpoint being limited.
	 * @param int    $limit  Allowed hits per window.
	 * @param int    $window Window length in seconds.
	 */
	function fw_rate_limit_ajax( $action, $limit = 30, $window = 60 ) {
		if ( ! fw_rate_limit_exceeded( $action, $limit, $window ) ) {
			return;
		}

		wp_send_json_error(
			array(
				'message'    => __( 'Too many requests. Please wait a moment and try again.', 'fw' ),
				'rate_limit' => true,
			),
			429
		);
	}
endif;
