<?php if ( ! defined( 'ABSPATH' ) ) { die( 'Direct access forbidden.' ); }
/**
 * Unyson+ → Connect AI: a one-click setup page that takes a fresh site from "plugin just activated" to
 * "paste this address into your AI app".
 *
 * Lives in core on purpose: the public release is core-only, so the AI Assistant extension (which serves
 * the MCP endpoint and its OAuth sign-in) does not exist yet when a beginner needs this page.
 *
 * The button runs five steps, each safe to run again (a step that is already done reports "already"):
 *   1. theme       — install (if missing) + activate the Unyson+ parent theme, unless it or a child is active.
 *                    Reuses UnysonPlus_Theme_Suggestion::install() (GitHub archive through Theme_Upgrader).
 *   2. extensions  — one request per extension: install it (and any missing parent / required extension)
 *                    through the Extensions manager's own install_extensions(), then activate_extensions(),
 *                    so parents, children and requirements behave exactly as on the Extensions screen.
 *                    The set is filterable: `fw_connect_ai_extensions`.
 *   3. permalinks  — Plain permalinks → /%postname%/ + a hard rewrite flush (the REST API lives under /wp-json/).
 *   4. mcp         — the AI Assistant's access mode option → write (set by name: the extension was only just
 *                    activated, so its classes are not loaded in this request).
 *   5. selftest    — runs in a later request, so the new extensions are loaded: the endpoint answering
 *                    tools/list internally, loopback requests to the endpoint + the OAuth discovery documents,
 *                    and an HTTPS / public-address check.
 *
 * The page drives the steps one AJAX request at a time (live ✓ / ✗, and no single request long enough to
 * hit a host's time limit). Without JavaScript, the form posts to admin-post.php and runs them all at once.
 */
class UnysonPlus_Connect_AI {

	const PAGE_SLUG   = 'fw-connect-ai';
	const CAPABILITY  = 'manage_options';
	const NONCE       = 'fw_connect_ai';
	const AJAX_STEP   = 'fw_connect_ai_step';
	const POST_ACTION = 'fw_connect_ai_run';

	/** The Unyson+ top-level menu (registered by the Extensions manager). */
	const PARENT_SLUG = 'fw-extensions';

	/** AI Assistant: MCP access option (FW_AI_MCP::OPTION_MODE), its REST namespace and its settings screen. */
	const MCP_OPTION  = 'upw_ai_mcp_mode';
	const MCP_NS      = 'unysonplus-ai/v1';
	const AI_PAGE     = 'fw-ai-assistant';

	/** Per-user meta: the "connect an AI app" notice was dismissed. */
	const DISMISS_META = 'unysonplus_connect_ai_notice_dismissed';

	/** Transient prefix carrying the no-JS run's results across the redirect. */
	const RESULT_KEY = 'fw_connect_ai_result_';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'wp_ajax_' . self::AJAX_STEP, array( __CLASS__, 'ajax_step' ) );
		add_action( 'admin_post_' . self::POST_ACTION, array( __CLASS__, 'handle_post' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'wp_ajax_fw_connect_ai_dismiss', array( __CLASS__, 'ajax_dismiss' ) );
	}

	/* ------------------------------------------------------------------ *
	 * Menu, URLs, the extension set
	 * ------------------------------------------------------------------ */

	public static function menu(): void {
		global $admin_page_hooks;
		// The Extensions manager can be told to put its menu elsewhere; fall back to Tools then.
		$parent = isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ? self::PARENT_SLUG : 'tools.php';
		add_submenu_page(
			$parent,
			__( 'Connect an AI app', 'fw' ),
			__( 'Connect AI', 'fw' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' ),
			1 // right under Extensions
		);
	}

	public static function page_url(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	private static function ai_page_url(): string {
		return admin_url( 'admin.php?page=' . self::AI_PAGE ) . '#upw-ai-outside';
	}

	private static function extensions_url(): string {
		return admin_url( 'admin.php?page=' . self::PARENT_SLUG );
	}

	/** The MCP endpoint address an AI app connects to. */
	private static function endpoint(): string {
		return rest_url( self::MCP_NS . '/mcp' );
	}

	/**
	 * The extensions an AI app needs to build a whole site, in install order (parents first).
	 *
	 * @return string[]
	 */
	public static function extensions(): array {
		$set = array( 'shortcodes', 'page-builder', 'ai-assistant', 'forms', 'mailer', 'crm', 'post-types', 'custom-fields', 'seo', 'theme-builder' );
		/** Filters the extensions the Connect AI page installs and activates (folder slugs, in install order). */
		$set = (array) apply_filters( 'fw_connect_ai_extensions', $set );
		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $set ) ) ) );
	}

	/** @return _FW_Extensions_Manager|null */
	private static function manager() {
		return ( function_exists( 'fw' ) && isset( fw()->extensions->manager ) ) ? fw()->extensions->manager : null;
	}

	private static function ext_title( string $name ): string {
		$m = self::manager();
		return $m ? (string) $m->get_extension_title( $name ) : $name;
	}

	/** On in the database (activated this request or earlier) or loaded. */
	private static function ext_is_on( string $name ): bool {
		if ( function_exists( 'fw_ext' ) && fw_ext( $name ) ) {
			return true;
		}
		$active = get_option( 'fw_active_extensions', array() );
		return is_array( $active ) && array_key_exists( $name, $active );
	}

	private static function theme_is_active(): bool {
		$slug = UnysonPlus_Theme_Suggestion::THEME_SLUG;
		return in_array( $slug, array( get_template(), get_stylesheet() ), true );
	}

	/** Whether this site has been set up already (the AI Assistant loaded and access set to read and write). */
	private static function is_ready(): bool {
		return function_exists( 'fw_ext' ) && fw_ext( 'ai-assistant' ) && get_option( self::MCP_OPTION ) === 'write';
	}

	/**
	 * A loopback / private address an AI app on the internet cannot reach.
	 */
	private static function is_private_host(): bool {
		$host = strtolower( trim( (string) wp_parse_url( home_url(), PHP_URL_HOST ), '[]' ) );
		if ( $host === 'localhost' || preg_match( '/\.(local|test|localhost|invalid|example)$/', $host ) ) {
			return true;
		}
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}
		return false;
	}

	/* ------------------------------------------------------------------ *
	 * Running the steps
	 * ------------------------------------------------------------------ */

	/**
	 * The step list the page walks through: theme, one entry per extension, permalinks, mcp, selftest.
	 *
	 * @return array[] { step, ext?, label }
	 */
	private static function plan(): array {
		$plan = array( array( 'step' => 'theme', 'label' => __( 'Unyson+ Theme', 'fw' ) ) );
		foreach ( self::extensions() as $ext ) {
			$plan[] = array( 'step' => 'extension', 'ext' => $ext, 'label' => self::ext_title( $ext ) );
		}
		$plan[] = array( 'step' => 'permalinks', 'label' => __( 'Web addresses (permalinks)', 'fw' ) );
		$plan[] = array( 'step' => 'mcp', 'label' => __( 'Let AI apps connect', 'fw' ) );
		$plan[] = array( 'step' => 'selftest', 'label' => __( 'Check the connection', 'fw' ) );
		return $plan;
	}

	/**
	 * @param string $step
	 * @param string $ext
	 * @return array { level: ok|warn|fail, text, fix?, items?: array[], endpoint? }
	 */
	private static function run_step( string $step, string $ext = '' ): array {
		@set_time_limit( 300 );
		switch ( $step ) {
			case 'theme':
				return self::step_theme();
			case 'extension':
				return in_array( $ext, self::extensions(), true )
					? self::step_extension( $ext )
					: self::fail( __( 'Unknown extension.', 'fw' ) );
			case 'permalinks':
				return self::step_permalinks();
			case 'mcp':
				return self::step_mcp();
			case 'selftest':
				return self::step_selftest();
		}
		return self::fail( __( 'Unknown step.', 'fw' ) );
	}

	private static function ok( string $text ): array {
		return array( 'level' => 'ok', 'text' => $text );
	}

	private static function warn( string $text, string $fix = '' ): array {
		return array( 'level' => 'warn', 'text' => $text, 'fix' => $fix );
	}

	private static function fail( string $text, string $fix = '' ): array {
		return array( 'level' => 'fail', 'text' => $text, 'fix' => $fix );
	}

	/** Step 1: the Unyson+ parent theme (or a child of it) is the active theme. */
	private static function step_theme(): array {
		if ( self::theme_is_active() ) {
			return self::ok( sprintf( __( 'Already active (%s).', 'fw' ), wp_get_theme()->get( 'Name' ) ) );
		}
		$manual = __( 'Download the Unyson+ Theme from GitHub (github.com/UnysonPlus/UnysonPlus-Theme, Code → Download ZIP), upload it under Appearance → Themes → Add New → Upload Theme, then press the button again.', 'fw' );
		if ( ! current_user_can( 'switch_themes' ) ) {
			return self::fail( __( 'Your account cannot change themes.', 'fw' ), __( 'Ask the site owner (an administrator) to press this button.', 'fw' ) );
		}
		$on_disk   = wp_get_theme( UnysonPlus_Theme_Suggestion::THEME_SLUG )->exists();
		$installed = UnysonPlus_Theme_Suggestion::install(); // no-op when the theme is already on disk
		if ( is_wp_error( $installed ) ) {
			return self::fail(
				sprintf( __( 'The theme could not be installed: %s', 'fw' ), wp_strip_all_tags( $installed->get_error_message() ) ),
				$manual
			);
		}
		$previous = wp_get_theme()->get( 'Name' );
		switch_theme( UnysonPlus_Theme_Suggestion::THEME_SLUG );
		if ( get_stylesheet() !== UnysonPlus_Theme_Suggestion::THEME_SLUG ) {
			return self::fail( __( 'The theme is installed but could not be switched on.', 'fw' ), __( 'Activate "Unyson+ Theme" under Appearance → Themes, then press the button again.', 'fw' ) );
		}
		return self::ok( sprintf(
			$on_disk ? __( 'Switched on (replacing %s).', 'fw' ) : __( 'Downloaded and switched on (replacing %s).', 'fw' ),
			$previous
		) );
	}

	/**
	 * Step 2 (once per extension): install what is missing — the extension, its parent, its required
	 * extensions — then activate it, all through the Extensions manager.
	 */
	private static function step_extension( string $name ): array {
		$m = self::manager();
		if ( ! $m ) {
			return self::fail( __( 'The Unyson+ Extensions manager is not available.', 'fw' ), __( 'Reload this page and try again.', 'fw' ) );
		}
		$title     = self::ext_title( $name );
		$was_on    = self::ext_is_on( $name );
		$installed = $m->get_installed_extensions( true );
		$added     = array();

		// Walk the extension, its parents and its requirements; install anything missing.
		$queue = array( $name );
		$seen  = array();
		while ( $queue ) {
			$x = array_shift( $queue );
			if ( isset( $seen[ $x ] ) ) {
				continue;
			}
			$seen[ $x ] = true;

			if ( ! isset( $installed[ $x ] ) ) {
				if ( ! $m->can_install() ) {
					return self::fail(
						sprintf( __( '%s is not installed, and your account cannot install extensions.', 'fw' ), self::ext_title( $x ) ),
						__( 'Ask the site owner (an administrator who can install plugins) to press this button.', 'fw' )
					);
				}
				if ( ! FW_WP_Filesystem::has_direct_access( fw_get_framework_directory( '/extensions' ) ) ) {
					return self::fail(
						sprintf( __( '%s could not be downloaded: WordPress needs FTP details to write files on this server.', 'fw' ), self::ext_title( $x ) ),
						sprintf( __( 'Install it from Unyson+ → Extensions (that screen asks for the FTP details): %s — then press the button here again.', 'fw' ), self::extensions_url() )
					);
				}
				$r = $m->install_extensions( array( $x => array() ), array( 'activate' => false, 'cancel_on_error' => true ) );
				if ( is_wp_error( $r ) ) {
					return self::download_failure( self::ext_title( $x ), $r );
				}
				$added[]   = self::ext_title( $x );
				$installed = $m->get_installed_extensions( true );
				if ( ! isset( $installed[ $x ] ) ) {
					return self::fail( sprintf( __( '%s was downloaded but did not appear in the extensions folder.', 'fw' ), self::ext_title( $x ) ), __( 'Press the button again. If it keeps failing, install it from Unyson+ → Extensions.', 'fw' ) );
				}
			}
			if ( ! empty( $installed[ $x ]['parent'] ) ) {
				$queue[] = $installed[ $x ]['parent'];
			}
			foreach ( array_keys( (array) fw_akg( 'requirements/extensions', $installed[ $x ]['manifest'], array() ) ) as $req ) {
				$queue[] = $req;
			}
		}

		if ( $was_on && ! $added ) {
			return self::ok( __( 'Already installed and on.', 'fw' ) );
		}

		$r = $m->activate_extensions( array( $name => array() ), true );
		if ( is_wp_error( $r ) ) {
			return self::fail(
				sprintf( __( '%1$s could not be switched on: %2$s', 'fw' ), $title, wp_strip_all_tags( $r->get_error_message() ) ),
				sprintf( __( 'Open Unyson+ → Extensions (%s), switch it on there and read what it says it needs.', 'fw' ), self::extensions_url() )
			);
		}
		$others = array_values( array_diff( $added, array( $title ) ) );
		if ( ! $added ) {
			return self::ok( __( 'Switched on.', 'fw' ) );
		}
		return self::ok( $others
			? sprintf( __( 'Downloaded and switched on (also downloaded what it needs: %s).', 'fw' ), implode( ', ', $others ) )
			: __( 'Downloaded and switched on.', 'fw' ) );
	}

	/** A failed download, in plain words — GitHub's per-hour limit gets its own explanation. */
	private static function download_failure( string $title, WP_Error $e ): array {
		$msg = wp_strip_all_tags( $e->get_error_message() );
		if ( preg_match( '/rate limit|Response code: (403|429)|\b429\b/i', $msg ) ) {
			return self::fail(
				sprintf( __( '%s could not be downloaded: GitHub is limiting downloads from your server right now.', 'fw' ), $title ),
				__( 'GitHub allows only so many downloads per hour from one server address, and shared hosting shares that allowance. Try again in an hour — the button carries on from where it stopped.', 'fw' )
			);
		}
		return self::fail(
			sprintf( __( '%1$s could not be downloaded: %2$s', 'fw' ), $title, $msg ),
			__( 'Your server has to reach github.com to download extensions. Press the button again in a few minutes; if it keeps failing, ask your host whether outgoing connections to github.com are blocked.', 'fw' )
		);
	}

	/** Step 3: pretty permalinks, so the REST API (and the AI connection) live under /wp-json/. */
	private static function step_permalinks(): array {
		if ( (string) get_option( 'permalink_structure' ) !== '' ) {
			return self::ok( __( 'Already using readable web addresses.', 'fw' ) );
		}
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules( true ); // hard: writes .htaccess on Apache

		if ( (string) get_option( 'permalink_structure' ) === '' ) {
			return self::fail( __( 'The permalink setting could not be saved.', 'fw' ), __( 'Open Settings → Permalinks, choose "Post name" and press Save Changes.', 'fw' ) );
		}
		global $is_apache;
		if ( $is_apache && function_exists( 'got_mod_rewrite' ) && got_mod_rewrite() ) {
			$htaccess = get_home_path() . '.htaccess';
			if ( ! is_file( $htaccess ) || strpos( (string) file_get_contents( $htaccess ), 'RewriteRule' ) === false ) {
				return self::warn(
					__( 'Switched to "Post name", but WordPress could not write the .htaccess file.', 'fw' ),
					__( 'Open Settings → Permalinks and press Save Changes; if WordPress shows rules to copy into .htaccess, ask your host to add them.', 'fw' )
				);
			}
		}
		return self::ok( __( 'Switched from "Plain" to "Post name" (/sample-page/).', 'fw' ) );
	}

	/** Step 4: let outside AI apps read and write (each app still has to be approved when it signs in). */
	private static function step_mcp(): array {
		if ( get_option( self::MCP_OPTION ) === 'write' ) {
			return self::ok( __( 'Already set to "Read and write".', 'fw' ) );
		}
		update_option( self::MCP_OPTION, 'write' );
		if ( get_option( self::MCP_OPTION ) !== 'write' ) {
			return self::fail( __( 'The setting could not be saved.', 'fw' ), sprintf( __( 'Set it by hand: Unyson+ → AI Assistant → Advanced → Outside AI programs → Read and write (%s).', 'fw' ), self::ai_page_url() ) );
		}
		return self::ok( __( 'Set to "Read and write". An app still has to be approved by you when it signs in.', 'fw' ) );
	}

	/**
	 * Step 5: does it work? Runs in its own request, so extensions switched on above are loaded here.
	 */
	private static function step_selftest(): array {
		$items = array();

		// Extensions that were switched on but did not load.
		$missing = array();
		foreach ( self::extensions() as $ext ) {
			if ( ! ( function_exists( 'fw_ext' ) && fw_ext( $ext ) ) ) {
				$missing[] = self::ext_title( $ext );
			}
		}
		if ( $missing ) {
			$items[] = self::warn(
				sprintf( __( 'Not running yet: %s.', 'fw' ), implode( ', ', $missing ) ),
				sprintf( __( 'Open Unyson+ → Extensions (%s) and switch them on; the screen says what each still needs.', 'fw' ), self::extensions_url() )
			);
		} else {
			$items[] = self::ok( __( 'All the needed extensions are running.', 'fw' ) );
		}

		// The AI Assistant itself: needs WordPress's Abilities API.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$items[] = self::fail( __( 'This WordPress is too old for the AI connection.', 'fw' ), __( 'Update WordPress under Dashboard → Updates, then press the button again.', 'fw' ) );
		}

		// 1) Internally, as you: the endpoint lists its tools.
		$count = self::internal_tools_count();
		if ( is_int( $count ) && $count > 0 ) {
			$items[] = self::ok( sprintf( _n( 'The AI connection answers: %d tool is ready.', 'The AI connection answers: %d tools are ready.', $count, 'fw' ), $count ) );
		} else {
			$items[] = self::fail(
				sprintf( __( 'The AI connection did not answer inside the site (%s).', 'fw' ), is_string( $count ) ? $count : __( 'no tools', 'fw' ) ),
				sprintf( __( 'Check that the AI Assistant is switched on under Unyson+ → Extensions (%s), then press the button again.', 'fw' ), self::extensions_url() )
			);
		}

		// 2) From outside (a loopback request, no sign-in): the address should answer "please sign in".
		$items[] = self::loopback_endpoint();

		// 3) The sign-in discovery documents AI apps look for.
		$items[] = self::loopback_discovery();

		// 4) Reachable from the internet over HTTPS?
		if ( self::is_private_host() ) {
			$items[] = self::warn(
				__( 'This site runs on your own computer (a local address), so AI apps on the internet cannot reach it.', 'fw' ),
				__( 'Run this on a site with a public https:// address — your live site or a staging copy at your host.', 'fw' )
			);
		} elseif ( wp_parse_url( home_url(), PHP_URL_SCHEME ) !== 'https' ) {
			$items[] = self::warn(
				__( 'This site does not use https://. AI app connectors need a public https:// address.', 'fw' ),
				__( 'Turn on SSL / HTTPS in your hosting control panel (most hosts offer a free certificate), switch the site address to https:// under Settings → General, then press the button again.', 'fw' )
			);
		} else {
			$items[] = self::ok( __( 'The site has a public https:// address.', 'fw' ) );
		}

		$level = 'ok';
		foreach ( $items as $i ) {
			if ( $i['level'] === 'fail' ) {
				$level = 'fail';
				break;
			}
			if ( $i['level'] === 'warn' ) {
				$level = 'warn';
			}
		}
		$text = array(
			'ok'   => __( 'Everything checks out.', 'fw' ),
			'warn' => __( 'Set up, with notes below.', 'fw' ),
			'fail' => __( 'Something needs fixing (see below).', 'fw' ),
		);
		return array(
			'level'    => $level,
			'text'     => $text[ $level ],
			'items'    => $items,
			'endpoint' => self::endpoint(),
			'ready'    => self::is_ready(),
		);
	}

	/**
	 * Call the endpoint's tools/list inside this request, as the current user.
	 *
	 * @return int|string Tool count, or a short reason.
	 */
	private static function internal_tools_count() {
		if ( ! function_exists( 'rest_do_request' ) ) {
			return 'REST API unavailable';
		}
		$req = new WP_REST_Request( 'POST', '/' . self::MCP_NS . '/mcp' );
		$req->set_header( 'Content-Type', 'application/json' );
		$req->set_body( wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => new stdClass() ) ) );
		$res  = rest_do_request( $req );
		$data = $res->get_data();
		if ( $res->get_status() !== 200 ) {
			return 'HTTP ' . $res->get_status() . ( is_array( $data ) && ! empty( $data['message'] ) ? ': ' . wp_strip_all_tags( (string) $data['message'] ) : '' );
		}
		return isset( $data['result']['tools'] ) && is_array( $data['result']['tools'] ) ? count( $data['result']['tools'] ) : 'unexpected answer';
	}

	private static function loopback_args(): array {
		return array(
			'timeout'     => 15,
			'redirection' => 3,
			/** Same filter Site Health uses for its loopback requests. */
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
			'headers'     => array( 'Accept' => 'application/json, text/event-stream', 'Content-Type' => 'application/json' ),
		);
	}

	/** The connector address, called from outside without signing in: 401 = reachable. */
	private static function loopback_endpoint(): array {
		$args         = self::loopback_args();
		$args['body'] = wp_json_encode( array( 'jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping' ) );
		$res          = wp_remote_post( self::endpoint(), $args );
		$unverified   = __( 'Your host may block a site from calling itself — that is common and usually harmless. Try connecting anyway.', 'fw' );

		if ( is_wp_error( $res ) ) {
			return self::warn( sprintf( __( 'Could not check the connector address from the server (%s).', 'fw' ), wp_strip_all_tags( $res->get_error_message() ) ), $unverified );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$err  = is_array( $body ) && isset( $body['code'] ) ? (string) $body['code'] : '';

		if ( $code === 401 && $err === 'upw_ai_mcp_auth' ) {
			$hint = (string) wp_remote_retrieve_header( $res, 'www-authenticate' );
			return self::ok( $hint !== ''
				? __( 'The connector address answers from outside and asks for sign-in, as it should.', 'fw' )
				: __( 'The connector address answers from outside and asks for sign-in.', 'fw' ) );
		}
		if ( $code === 200 || $code === 202 ) {
			return self::ok( __( 'The connector address answers from outside.', 'fw' ) );
		}
		if ( $code === 404 ) {
			return self::fail(
				__( 'The connector address was not found (404) when called from outside.', 'fw' ),
				__( 'Something is hiding the WordPress REST API (addresses under /wp-json/). Check security / firewall plugins (look for a "disable REST API" setting) and your host\'s firewall, then press the button again.', 'fw' )
			);
		}
		if ( in_array( $code, array( 401, 403 ), true ) ) {
			return self::fail(
				sprintf( __( 'The connector address is blocked from outside (HTTP %d%s).', 'fw' ), $code, $err !== '' ? ', ' . $err : '' ),
				__( 'A security plugin or firewall is blocking the WordPress REST API for visitors who are not signed in. Allow the address below (or everything under /wp-json/unysonplus-ai/) in that plugin, then press the button again.', 'fw' )
			);
		}
		if ( ! is_array( $body ) ) {
			return self::warn(
				sprintf( __( 'The connector address answered with a web page instead of data (HTTP %d).', 'fw' ), $code ),
				__( 'A firewall, a "coming soon" / maintenance plugin or a server cache may be answering instead of WordPress. Check those, or try connecting anyway.', 'fw' )
			);
		}
		return self::warn( sprintf( __( 'The connector address answered from outside with HTTP %d.', 'fw' ), $code ), $unverified );
	}

	/** The OAuth discovery documents (RFC 9728 / RFC 8414) the AI Assistant serves at the site root. */
	private static function loopback_discovery(): array {
		$fails = array();
		$any   = false;
		foreach ( array( 'oauth-protected-resource', 'oauth-authorization-server' ) as $doc ) {
			$res = wp_remote_get( home_url( '/.well-known/' . $doc ), self::loopback_args() );
			if ( is_wp_error( $res ) ) {
				return self::warn(
					sprintf( __( 'Could not check the sign-in details from the server (%s).', 'fw' ), wp_strip_all_tags( $res->get_error_message() ) ),
					__( 'Your host may block a site from calling itself — that is common and usually harmless. Try connecting anyway.', 'fw' )
				);
			}
			$code = (int) wp_remote_retrieve_response_code( $res );
			$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$key  = $doc === 'oauth-protected-resource' ? 'resource' : 'authorization_endpoint';
			if ( $code === 200 && is_array( $json ) && ! empty( $json[ $key ] ) ) {
				$any = true;
			} else {
				$fails[] = '/.well-known/' . $doc . ' (HTTP ' . $code . ')';
			}
		}
		if ( ! $fails ) {
			return self::ok( __( 'The sign-in details AI apps look for are published.', 'fw' ) );
		}
		return self::warn(
			sprintf( __( 'Some sign-in details AI apps look for could not be read: %s.', 'fw' ), implode( ', ', $fails ) ),
			$any
				? __( 'Try connecting anyway. If the app cannot find the sign-in page, ask your host to let /.well-known/ addresses through to WordPress.', 'fw' )
				: __( 'Your server answers /.well-known/ addresses itself instead of passing them to WordPress. Ask your host to let /.well-known/oauth-* through to WordPress, or try connecting anyway.', 'fw' )
		);
	}

	/* ------------------------------------------------------------------ *
	 * Request handlers
	 * ------------------------------------------------------------------ */

	public static function ajax_step(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to set up this site.', 'fw' ) ), 403 );
		}
		check_ajax_referer( self::NONCE );
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';
		$ext  = isset( $_POST['ext'] ) ? sanitize_key( wp_unslash( $_POST['ext'] ) ) : '';
		wp_send_json_success( self::run_step( $step, $ext ) );
	}

	/** No-JavaScript fallback: run every step in this request, then show the results on the page. */
	public static function handle_post(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to set up this site.', 'fw' ) );
		}
		check_admin_referer( self::NONCE );
		$results = array();
		foreach ( self::plan() as $i => $p ) {
			// The check runs on the page this redirects to: extensions switched on here load only from the next request.
			if ( $p['step'] !== 'selftest' ) {
				$results[ $i ] = self::run_step( $p['step'], $p['ext'] ?? '' );
			}
		}
		set_transient( self::RESULT_KEY . get_current_user_id(), $results, 10 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'ran', 1, self::page_url() ) );
		exit;
	}

	/* ------------------------------------------------------------------ *
	 * The admin notice pointing here
	 * ------------------------------------------------------------------ */

	public static function notice(): void {
		if ( ! current_user_can( self::CAPABILITY ) || ( function_exists( 'fw_ext' ) && fw_ext( 'ai-assistant' ) ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) || strpos( $screen->id, self::PAGE_SLUG ) !== false ) {
			return;
		}
		$relevant = in_array( $screen->id, array( 'dashboard', 'plugins' ), true )
			|| strpos( $screen->id, self::PARENT_SLUG ) !== false
			|| strpos( $screen->id, 'fw-settings' ) !== false;
		if ( ! $relevant ) {
			return;
		}
		$nonce = wp_create_nonce( 'fw_connect_ai_dismiss' );
		?>
		<div class="notice notice-info is-dismissible" id="fw-connect-ai-notice">
			<p>
				<strong><?php esc_html_e( 'Build this site with an AI app?', 'fw' ); ?></strong>
				<?php esc_html_e( 'Unyson+ can set this site up in one click so an AI app can build and edit it for you.', 'fw' ); ?>
				<a class="button button-primary" style="margin-left:.4rem;vertical-align:baseline" href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( 'Connect an AI app', 'fw' ); ?></a>
			</p>
		</div>
		<script>
		(function(){
			var n = document.getElementById('fw-connect-ai-notice');
			if (!n || typeof ajaxurl === 'undefined') return;
			n.addEventListener('click', function(e){
				if (!e.target.closest('.notice-dismiss')) return;
				var b = new FormData();
				b.append('action', 'fw_connect_ai_dismiss');
				b.append('_ajax_nonce', <?php echo wp_json_encode( $nonce ); ?>);
				fetch(ajaxurl, {method:'POST', credentials:'same-origin', body:b, keepalive:true});
			});
		})();
		</script>
		<?php
	}

	public static function ajax_dismiss(): void {
		check_ajax_referer( 'fw_connect_ai_dismiss' );
		update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		wp_send_json_success();
	}

	/* ------------------------------------------------------------------ *
	 * The page
	 * ------------------------------------------------------------------ */

	private static function icon( string $level ): string {
		$map = array(
			'ok'      => array( '✓', __( 'Done', 'fw' ) ),
			'warn'    => array( '!', __( 'Note', 'fw' ) ),
			'fail'    => array( '✗', __( 'Failed', 'fw' ) ),
			'pending' => array( '', __( 'Waiting', 'fw' ) ),
		);
		$m = $map[ $level ] ?? $map['pending'];
		return '<span class="fwcai-ico fwcai-' . esc_attr( $level ) . '" role="img" aria-label="' . esc_attr( $m[1] ) . '">' . esc_html( $m[0] ) . '</span>';
	}

	/** One result's detail lines (its text, its fix, and any sub-items). */
	private static function result_html( array $r ): string {
		$h = '<span class="fwcai-text">' . esc_html( $r['text'] ?? '' ) . '</span>';
		if ( ! empty( $r['fix'] ) ) {
			$h .= '<span class="fwcai-fix">' . esc_html( $r['fix'] ) . '</span>';
		}
		if ( ! empty( $r['items'] ) ) {
			$h .= '<ul class="fwcai-items">';
			foreach ( $r['items'] as $i ) {
				$h .= '<li>' . self::icon( $i['level'] ) . '<span><span class="fwcai-text">' . esc_html( $i['text'] ) . '</span>'
					. ( ! empty( $i['fix'] ) ? '<span class="fwcai-fix">' . esc_html( $i['fix'] ) . '</span>' : '' ) . '</span></li>';
			}
			$h .= '</ul>';
		}
		return $h;
	}

	public static function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$plan    = self::plan();
		$results = array();
		if ( ! empty( $_GET['ran'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- display only
			$results = (array) get_transient( self::RESULT_KEY . get_current_user_id() );
			delete_transient( self::RESULT_KEY . get_current_user_id() );
			if ( $results ) {
				$last             = count( $plan ) - 1; // selftest
				$results[ $last ] = self::run_step( 'selftest' );
			}
		}
		$ready    = self::is_ready();
		$endpoint = self::endpoint();
		$local    = self::is_private_host();
		$https    = wp_parse_url( home_url(), PHP_URL_SCHEME ) === 'https';
		?>
		<div class="wrap fwcai">
			<h1><?php esc_html_e( 'Connect an AI app', 'fw' ); ?></h1>
			<p class="fwcai-lead"><?php esc_html_e( 'Set this site up so an AI app can build and edit it for you. One button does the setup; then you paste one address into the AI app and sign in.', 'fw' ); ?></p>

			<div class="notice notice-warning inline"><p>
				<strong><?php esc_html_e( 'Use this on a fresh site or a staging copy.', 'fw' ); ?></strong>
				<?php esc_html_e( 'The button switches the site to the Unyson+ Theme (replacing the current theme) and turns on the extensions an AI app needs to build pages, forms and more.', 'fw' ); ?>
			</p></div>

			<?php if ( $local || ! $https ) : ?>
				<div class="notice notice-info inline"><p>
					<?php
					echo esc_html( $local
						? __( 'This site runs on your own computer (a local address). The setup still works, but AI apps on the internet cannot reach a local site — for that you need a site with a public https:// address.', 'fw' )
						: __( 'This site does not use https:// yet. AI app connectors need a public https:// address, so turn on SSL / HTTPS at your host first.', 'fw' ) );
					?>
				</p></div>
			<?php endif; ?>

			<div class="fwcai-card">
				<h2><?php esc_html_e( 'What the button does', 'fw' ); ?></h2>
				<ol class="fwcai-steps" id="fwcai-steps">
					<?php
					$rows = array(
						'theme'      => array( __( 'Switches on the Unyson+ Theme', 'fw' ), __( 'Downloads it first if it is not installed.', 'fw' ) ),
						'extensions' => array( __( 'Turns on the extensions an AI app needs', 'fw' ), __( 'Downloads any that are missing.', 'fw' ) ),
						'permalinks' => array( __( 'Uses readable web addresses', 'fw' ), __( 'Needed for the AI connection; only changes "Plain" permalinks.', 'fw' ) ),
						'mcp'        => array( __( 'Lets AI apps connect', 'fw' ), __( 'Read and write — each app still needs your approval when it signs in.', 'fw' ) ),
						'selftest'   => array( __( 'Checks the connection', 'fw' ), __( 'Makes sure the AI connection answers.', 'fw' ) ),
					);
					foreach ( $rows as $key => $row ) :
						// Results from a no-JS run, folded per row.
						$row_results = array();
						foreach ( $plan as $i => $p ) {
							$group = $p['step'] === 'extension' ? 'extensions' : $p['step'];
							if ( $group === $key && isset( $results[ $i ] ) ) {
								$row_results[ $i ] = $results[ $i ];
							}
						}
						$row_level = $row_results ? 'ok' : 'pending';
						foreach ( $row_results as $rr ) {
							if ( $rr['level'] === 'fail' ) { $row_level = 'fail'; break; }
							if ( $rr['level'] === 'warn' ) { $row_level = 'warn'; }
						}
						?>
						<li class="fwcai-step" data-group="<?php echo esc_attr( $key ); ?>">
							<?php echo self::icon( $row_level ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in icon() ?>
							<div class="fwcai-body">
								<strong><?php echo esc_html( $row[0] ); ?></strong>
								<span class="fwcai-sub"><?php echo esc_html( $row[1] ); ?></span>
								<div class="fwcai-result">
									<?php if ( $key === 'extensions' ) : ?>
										<ul class="fwcai-items">
											<?php foreach ( $plan as $i => $p ) : if ( $p['step'] !== 'extension' ) { continue; } ?>
												<li data-index="<?php echo (int) $i; ?>">
													<?php echo self::icon( isset( $results[ $i ] ) ? $results[ $i ]['level'] : 'pending' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
													<span><strong><?php echo esc_html( $p['label'] ); ?></strong> <span class="fwcai-r"><?php echo isset( $results[ $i ] ) ? self::result_html( $results[ $i ] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span></span>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php else : ?>
										<div class="fwcai-r"><?php foreach ( $row_results as $rr ) { echo self::result_html( $rr ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									<?php endif; ?>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fwcai-form">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::POST_ACTION ); ?>">
					<?php wp_nonce_field( self::NONCE ); ?>
					<p class="fwcai-actions">
						<button type="submit" class="button button-primary button-hero" id="fwcai-run"><?php esc_html_e( 'Set up this site for an AI app', 'fw' ); ?></button>
						<span class="fwcai-status" id="fwcai-status" aria-live="polite"></span>
					</p>
					<p class="description"><?php esc_html_e( 'Takes a minute or two while it downloads. Safe to press again: anything already done is left as it is, and a failed step can simply be retried.', 'fw' ); ?></p>
				</form>
			</div>

			<div class="fwcai-card fwcai-done" id="fwcai-done"<?php echo $ready ? '' : ' hidden'; ?>>
				<h2><?php esc_html_e( 'Your connector address', 'fw' ); ?></h2>
				<p><?php esc_html_e( 'Copy this address into your AI app:', 'fw' ); ?></p>
				<p class="fwcai-copy">
					<input type="text" readonly class="large-text code" id="fwcai-endpoint" value="<?php echo esc_attr( $endpoint ); ?>" onclick="this.select()">
					<button type="button" class="button button-primary" id="fwcai-copy" data-done="<?php esc_attr_e( 'Copied', 'fw' ); ?>"><?php esc_html_e( 'Copy address', 'fw' ); ?></button>
				</p>
				<h3><?php esc_html_e( 'Next steps', 'fw' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'In your AI app, add a custom connector and paste the address above.', 'fw' ); ?></li>
					<li><?php esc_html_e( 'Choose to sign in to your site. A page from this site opens — log in if it asks.', 'fw' ); ?></li>
					<li><?php esc_html_e( 'Choose "Read and write" and approve. Then ask the AI app to build your site.', 'fw' ); ?></li>
				</ol>
				<p class="description">
					<?php
					printf(
						/* translators: %s: link to the AI Assistant screen */
						esc_html__( 'To turn it off later: %s → Advanced → Outside AI programs. Choose "Off" to block every app, or use Sign out next to one app to disconnect just that one.', 'fw' ),
						'<a href="' . esc_url( self::ai_page_url() ) . '">' . esc_html__( 'Unyson+ → AI Assistant', 'fw' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>

		<style>
			.fwcai{max-width:860px}
			.fwcai .fwcai-lead{font-size:15px;max-width:720px}
			.fwcai .notice.inline{margin:12px 0}
			.fwcai-card{background:#fff;border:1px solid #c3c4c7;border-radius:6px;padding:4px 24px 20px;margin:20px 0;box-shadow:0 1px 1px rgba(0,0,0,.04)}
			.fwcai-steps{list-style:none;margin:0 0 16px;padding:0}
			.fwcai-step{display:flex;gap:12px;padding:12px 0;border-top:1px solid #f0f0f1}
			.fwcai-step:first-child{border-top:0}
			.fwcai-body{flex:1 1 auto;min-width:0}
			.fwcai-sub{display:block;color:#646970;margin-top:2px}
			.fwcai-ico{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;font-weight:700;font-size:13px;line-height:1;color:#fff;background:#dcdcde}
			.fwcai-ok{background:#00a32a}.fwcai-warn{background:#dba617}.fwcai-fail{background:#d63638}
			.fwcai-running{background:transparent;border:2px solid #dcdcde;border-top-color:#2271b1;animation:fwcaiSpin .8s linear infinite;box-sizing:border-box}
			@keyframes fwcaiSpin{to{transform:rotate(360deg)}}
			.fwcai-items{list-style:none;margin:8px 0 0;padding:0}
			.fwcai-items li{display:flex;gap:8px;align-items:flex-start;margin:6px 0}
			.fwcai-items .fwcai-ico{width:18px;height:18px;font-size:11px;margin-top:1px}
			.fwcai-text{display:inline}
			.fwcai-r .fwcai-text,.fwcai-items .fwcai-text{color:#1d2327}
			.fwcai-fix{display:block;margin-top:3px;padding:6px 10px;background:#fcf9e8;border-left:3px solid #dba617;color:#1d2327}
			.fwcai-actions{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
			.fwcai-status{color:#50575e}
			.fwcai-copy{display:flex;gap:8px;align-items:center}
			.fwcai-copy input{font-size:14px;padding:6px 8px}
			.fwcai-done{border-color:#00a32a}
			@media (prefers-reduced-motion:reduce){.fwcai-running{animation:none}}
		</style>
		<script>
		(function(){
			var form = document.getElementById('fwcai-form');
			if (!form || typeof ajaxurl === 'undefined' || !window.fetch) return; // no JS → the form posts normally
			var plan  = <?php echo wp_json_encode( $plan ); ?>;
			var nonce = form.querySelector('[name="_wpnonce"]').value;
			var btn   = document.getElementById('fwcai-run');
			var stat  = document.getElementById('fwcai-status');
			var L = <?php echo wp_json_encode( array(
				'ok' => __( 'Done', 'fw' ), 'warn' => __( 'Note', 'fw' ), 'fail' => __( 'Failed', 'fw' ), 'pending' => __( 'Waiting', 'fw' ), 'running' => __( 'Working', 'fw' ),
				'busy' => __( 'Working on: ', 'fw' ), 'again' => __( 'Run the setup again', 'fw' ),
				'allok' => __( 'All done — copy the address below.', 'fw' ), 'somefail' => __( 'Finished, but something needs fixing — see the red items above, then press the button again.', 'fw' ),
				'neterr' => __( 'The request did not finish (your server may have timed out). Press the button again — it carries on from where it stopped.', 'fw' ),
			) ); ?>;
			var glyph = {ok:'✓', warn:'!', fail:'✗', pending:'', running:''};

			function setIco(el, level){ el.className = 'fwcai-ico fwcai-' + level; el.textContent = glyph[level]; el.setAttribute('aria-label', L[level]); }
			function esc(s){ var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
			function resultHtml(r){
				var h = '<span class="fwcai-text">' + esc(r.text) + '</span>';
				if (r.fix) h += '<span class="fwcai-fix">' + esc(r.fix) + '</span>';
				if (r.items && r.items.length){
					h += '<ul class="fwcai-items">';
					r.items.forEach(function(i){
						h += '<li><span class="fwcai-ico fwcai-' + i.level + '" role="img" aria-label="' + esc(L[i.level]) + '">' + glyph[i.level] + '</span><span><span class="fwcai-text">' + esc(i.text) + '</span>' + (i.fix ? '<span class="fwcai-fix">' + esc(i.fix) + '</span>' : '') + '</span></li>';
					});
					h += '</ul>';
				}
				return h;
			}
			function row(group){ return document.querySelector('.fwcai-step[data-group="' + group + '"]'); }
			function worst(a, b){ var o = {pending:0, ok:1, warn:2, fail:3}; return (o[b] || 0) > (o[a] || 0) ? b : a; }

			function call(p){
				var b = new FormData();
				b.append('action', <?php echo wp_json_encode( self::AJAX_STEP ); ?>);
				b.append('_ajax_nonce', nonce);
				b.append('step', p.step);
				if (p.ext) b.append('ext', p.ext);
				return fetch(ajaxurl, {method:'POST', credentials:'same-origin', body:b})
					.then(function(r){ return r.json(); })
					.then(function(j){ if (!j || !j.success) throw new Error((j && j.data && j.data.message) || 'error'); return j.data; });
			}

			form.addEventListener('submit', function(e){
				e.preventDefault();
				if (btn.disabled) return;
				btn.disabled = true;
				document.querySelectorAll('.fwcai-step').forEach(function(s){
					setIco(s.querySelector('.fwcai-ico'), 'pending');
					s.querySelectorAll('.fwcai-r').forEach(function(r){ r.innerHTML = ''; });
					s.querySelectorAll('.fwcai-items li[data-index] > .fwcai-ico').forEach(function(i){ setIco(i, 'pending'); });
				});
				var levels = {}, anyFail = false, i = 0, last = null;

				function next(){
					if (i >= plan.length){ finish(); return; }
					var p = plan[i], idx = i, group = p.step === 'extension' ? 'extensions' : p.step, s = row(group);
					var sub = p.step === 'extension' ? s.querySelector('li[data-index="' + idx + '"]') : null;
					setIco(s.querySelector('.fwcai-ico'), 'running');
					if (sub) setIco(sub.querySelector('.fwcai-ico'), 'running');
					stat.textContent = L.busy + p.label;
					call(p).then(function(r){
						last = r;
						levels[group] = worst(levels[group] || 'pending', r.level);
						if (r.level === 'fail') anyFail = true;
						if (sub){ setIco(sub.querySelector('.fwcai-ico'), r.level); sub.querySelector('.fwcai-r').innerHTML = resultHtml(r); }
						else { s.querySelector('.fwcai-r').innerHTML = resultHtml(r); }
						var more = plan[idx + 1] && (plan[idx + 1].step === 'extension' ? 'extensions' : plan[idx + 1].step) === group;
						if (!more) setIco(s.querySelector('.fwcai-ico'), levels[group]);
						if (r.endpoint){
							document.getElementById('fwcai-endpoint').value = r.endpoint;
							if (r.ready) document.getElementById('fwcai-done').hidden = false;
						}
						i++; next();
					}).catch(function(){
						setIco(s.querySelector('.fwcai-ico'), 'fail');
						if (sub) setIco(sub.querySelector('.fwcai-ico'), 'fail');
						stat.textContent = L.neterr;
						btn.disabled = false; btn.textContent = L.again;
					});
				}
				function finish(){
					btn.disabled = false; btn.textContent = L.again;
					stat.textContent = anyFail ? L.somefail : L.allok;
					if (!anyFail && last && last.ready) document.getElementById('fwcai-done').scrollIntoView({behavior:'smooth', block:'start'});
				}
				next();
			});

			var copy = document.getElementById('fwcai-copy');
			copy && copy.addEventListener('click', function(){
				var input = document.getElementById('fwcai-endpoint'), label = copy.textContent;
				input.select();
				(navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.reject()).catch(function(){ document.execCommand('copy'); });
				copy.textContent = copy.dataset.done;
				setTimeout(function(){ copy.textContent = label; }, 1800);
			});
		})();
		</script>
		<?php
	}
}
