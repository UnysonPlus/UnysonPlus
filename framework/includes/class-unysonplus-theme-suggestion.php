<?php if ( ! defined( 'ABSPATH' ) ) { die( 'Direct access forbidden.' ); }
/**
 * Suggestive (dismissible) admin notice recommending the Unyson+ parent theme.
 *
 * The mirror image of the theme's own installer (inc/classes/class-unysonplus-plugin-installer.php),
 * but inverted in TWO ways on purpose:
 *   - The theme REQUIRES the plugin  → a hard, non-dismissible error notice.
 *   - The plugin only SUGGESTS the theme → a friendly, info-styled, permanently
 *     dismissible notice (the plugin works with any theme; the theme just unlocks the
 *     full header/footer builder, presets and page templates).
 *
 * Shows only when the plugin is active AND the Unyson+ theme is NOT the active theme,
 * to a user who can switch/install themes, on a few relevant screens, and never again
 * once dismissed (per-user meta) or once the theme is activated. One-click action:
 * "Activate" if the theme is on disk, else "Install & activate" — downloaded from the
 * theme's GitHub archive through core's Theme_Upgrader, with a live progress UI (the
 * plain nonce'd link is the no-JS fallback).
 */
class UnysonPlus_Theme_Suggestion {

	/** Active-theme / on-disk folder slug of the Unyson+ parent theme. */
	const THEME_SLUG = 'unysonplus-theme';

	/** GitHub source archive (theme repo is source-only — no release asset). Unzips to
	 *  `UnysonPlus-Theme-master/`, which we rename to the slug on install. */
	const SOURCE_ZIP = 'https://github.com/UnysonPlus/UnysonPlus-Theme/archive/refs/heads/master.zip';

	/** admin-post / ajax action slug. */
	const ACTION = 'unysonplus_install_theme';

	/** Per-user meta flag: the suggestion was dismissed (never show again). */
	const DISMISS_META = 'unysonplus_theme_suggestion_dismissed';

	/** Transient carrying the install result across the no-JS redirect. */
	const RESULT_KEY = 'unysonplus_theme_install_result';

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'prompt_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'result_notice' ) );
		add_action( 'admin_footer', array( __CLASS__, 'print_assets' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_install' ) );        // no-JS fallback
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle_ajax_install' ) );      // progress UI
		add_action( 'wp_ajax_' . self::ACTION . '_dismiss', array( __CLASS__, 'handle_dismiss' ) );
	}

	/** The Unyson+ theme (or a child of it) is the active theme. */
	private static function is_active(): bool {
		return in_array( self::THEME_SLUG, array( get_template(), get_stylesheet() ), true );
	}

	/** The theme files exist on disk (installed, possibly inactive). */
	private static function is_installed(): bool {
		$theme = wp_get_theme( self::THEME_SLUG );
		return $theme->exists();
	}

	private static function is_dismissed(): bool {
		return (bool) get_user_meta( get_current_user_id(), self::DISMISS_META, true );
	}

	/** Keep the suggestion off every admin page — only a few relevant ones. */
	private static function on_relevant_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) { return false; }
		$screen = get_current_screen();
		if ( ! $screen || empty( $screen->id ) ) { return false; }
		if ( in_array( $screen->id, array( 'dashboard', 'plugins', 'themes' ), true ) ) { return true; }
		return ( strpos( $screen->id, 'fw-settings' ) !== false ); // any Unyson+ settings page
	}

	/** Whether the current user can act on the suggestion at all. */
	private static function user_can_act(): bool {
		return self::is_installed() ? current_user_can( 'switch_themes' ) : current_user_can( 'install_themes' );
	}

	private static function icon_url(): string {
		return function_exists( 'fw_get_framework_directory_uri' )
			? fw_get_framework_directory_uri( '/static/img/unysonplus-logo-green-bg.jpg' )
			: '';
	}

	/** Nonce'd URL for the no-JS install/activate fallback. */
	private static function action_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
	}

	/**
	 * The suggestion notice: info-styled, dismissible, with the smart action button.
	 */
	public static function prompt_notice(): void {
		if ( self::is_active() || self::is_dismissed() || ! self::user_can_act() || ! self::on_relevant_screen() ) {
			return;
		}

		$installed = self::is_installed();
		$label     = $installed ? __( 'Activate Unyson+ Theme', 'fw' ) : __( 'Install &amp; activate Unyson+ Theme', 'fw' );
		$busy      = $installed ? __( 'Activating…', 'fw' ) : __( 'Downloading &amp; installing from GitHub… this can take up to a minute.', 'fw' );
		$icon      = self::icon_url();
		?>
		<div class="notice notice-info is-dismissible" id="unysonplus-theme-suggestion"
			data-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( self::ACTION . '_dismiss' ) ); ?>">
			<div style="display:flex;align-items:center;gap:14px;padding:4px 0">
				<?php if ( $icon ) : ?>
					<img src="<?php echo esc_url( $icon ); ?>" alt="" width="44" height="44"
						style="flex:0 0 auto;border-radius:8px;display:block" />
				<?php endif; ?>
				<div style="flex:1 1 auto">
					<p style="margin:0 0 .35rem">
						<strong><?php esc_html_e( 'Get the most out of Unyson+', 'fw' ); ?></strong> &mdash;
						<?php esc_html_e( 'Unyson+ works with any theme, but the free Unyson+ Theme unlocks the full drag-and-drop Header / Footer builder, the preset library, and ready-made page templates. Recommended, not required.', 'fw' ); ?>
					</p>
					<p style="margin:0">
						<a class="button button-primary" id="unysonplus-theme-btn"
							style="vertical-align:baseline"
							href="<?php echo esc_url( self::action_url() ); ?>"
							data-action="<?php echo esc_attr( self::ACTION ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( self::ACTION ) ); ?>"
							data-busy="<?php echo esc_attr( $busy ); ?>"><?php echo esc_html( $label ); ?></a>
						<a href="#" class="button-link" id="unysonplus-theme-dismiss"
							style="margin-left:.5rem;color:#646970;text-decoration:none"><?php esc_html_e( 'No thanks', 'fw' ); ?></a>
					</p>
					<div id="unysonplus-theme-progress" style="display:none;margin:.5rem 0 .25rem">
						<div class="uts-track"><div class="uts-fill"></div></div>
						<p class="uts-status" style="margin:.4rem 0 0;color:#50575e"></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/** admin-post fallback: install (if needed) + activate, then redirect with a result. */
	public static function handle_install(): void {
		if ( ! self::user_can_act() ) {
			wp_die( esc_html__( 'You do not have permission to change themes on this site.', 'fw' ) );
		}
		check_admin_referer( self::ACTION );

		$result = self::install_and_activate();
		set_transient( self::RESULT_KEY, array(
			'status' => is_wp_error( $result ) ? 'error' : 'success',
			'msg'    => is_wp_error( $result ) ? $result->get_error_message() : '',
		), MINUTE_IN_SECONDS );

		wp_safe_redirect( self_admin_url( 'themes.php' ) );
		exit;
	}

	/** AJAX handler for the progress UI. */
	public static function handle_ajax_install(): void {
		if ( ! self::user_can_act() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to change themes on this site.', 'fw' ) ) );
		}
		check_ajax_referer( self::ACTION );

		$result = self::install_and_activate();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'redirect' => self_admin_url( 'themes.php' ) ) );
	}

	/** AJAX: persist the per-user dismissal so the suggestion never shows again. */
	public static function handle_dismiss(): void {
		check_ajax_referer( self::ACTION . '_dismiss' );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		}
		wp_send_json_success();
	}

	/**
	 * Download (if needed) + install + activate the Unyson+ theme.
	 *
	 * @return true|WP_Error
	 */
	private static function install_and_activate() {
		@set_time_limit( 300 );

		if ( ! self::is_installed() ) {
			if ( ! current_user_can( 'install_themes' ) ) {
				return new WP_Error( 'unysonplus_theme_cap', __( 'You do not have permission to install themes.', 'fw' ) );
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/theme.php';

			// The archive unzips to `UnysonPlus-Theme-master/`; rename it to the slug.
			$rename = array( __CLASS__, 'rename_source_to_slug' );
			add_filter( 'upgrader_source_selection', $rename, 10, 3 );

			$skin      = new WP_Ajax_Upgrader_Skin();
			$upgrader  = new Theme_Upgrader( $skin );
			$installed = $upgrader->install( self::SOURCE_ZIP );

			remove_filter( 'upgrader_source_selection', $rename, 10 );

			if ( is_wp_error( $installed ) ) { return $installed; }
			if ( is_wp_error( $skin->result ) ) { return $skin->result; }
			if ( method_exists( $skin, 'get_errors' ) && $skin->get_errors()->has_errors() ) { return $skin->get_errors(); }
			if ( true !== $installed ) {
				return new WP_Error( 'unysonplus_theme_install_failed', __( 'The Unyson+ Theme could not be installed from GitHub.', 'fw' ) );
			}
		}

		if ( ! current_user_can( 'switch_themes' ) ) {
			return new WP_Error( 'unysonplus_theme_cap', __( 'You do not have permission to switch themes.', 'fw' ) );
		}
		switch_theme( self::THEME_SLUG );
		return true;
	}

	/**
	 * `upgrader_source_selection`: rename the extracted `UnysonPlus-Theme-*` folder to
	 * the `unysonplus-theme` slug so the theme installs where WordPress expects it.
	 */
	public static function rename_source_to_slug( $source, $remote_source, $upgrader ) {
		$basename = basename( untrailingslashit( $source ) );
		if ( stripos( $basename, 'UnysonPlus-Theme' ) !== 0 ) {
			return $source;
		}
		global $wp_filesystem;
		$target = trailingslashit( $remote_source ) . self::THEME_SLUG . '/';
		if ( trailingslashit( $source ) === $target ) {
			return $source;
		}
		if ( $wp_filesystem && $wp_filesystem->move( $source, $target, true ) ) {
			return $target;
		}
		return new WP_Error( 'unysonplus_theme_rename_failed', __( 'Could not prepare the Unyson+ Theme folder.', 'fw' ) );
	}

	/** One-time result notice after the no-JS redirect. */
	public static function result_notice(): void {
		$res = get_transient( self::RESULT_KEY );
		if ( ! $res ) { return; }
		delete_transient( self::RESULT_KEY );

		if ( 'success' === $res['status'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>'
				. esc_html__( 'The Unyson+ Theme is now active — the full builder, presets and page templates are enabled.', 'fw' )
				. '</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p><strong>'
				. esc_html__( 'Unyson+ Theme install failed:', 'fw' ) . '</strong> '
				. esc_html( $res['msg'] ) . '</p></div>';
		}
	}

	/** Progress-bar styles + JS: AJAX install, live progress, and persist-the-dismiss. */
	public static function print_assets(): void {
		$notice = ( ! self::is_active() && ! self::is_dismissed() && self::user_can_act() && self::on_relevant_screen() );
		if ( ! $notice ) { return; }
		?>
		<style>
			#unysonplus-theme-progress .uts-track{position:relative;height:8px;border-radius:6px;background:#dcdcde;overflow:hidden;max-width:420px}
			#unysonplus-theme-progress .uts-fill{position:absolute;top:0;left:0;height:100%;width:40%;border-radius:6px;background:var(--fw-accent, #3858e9);animation:utsSlide 1.4s ease-in-out infinite}
			#unysonplus-theme-progress.uts-done .uts-fill{width:100%;animation:none;background:#46b450;transition:width .4s ease}
			#unysonplus-theme-progress.uts-error .uts-fill{width:100%;animation:none;background:#d63638}
			@keyframes utsSlide{0%{left:-40%}50%{left:30%}100%{left:100%}}
		</style>
		<script>
		(function(){
			var box = document.getElementById('unysonplus-theme-suggestion');
			if(!box || typeof ajaxurl === 'undefined') return;

			function dismiss(){
				var body = new FormData();
				body.append('action', <?php echo wp_json_encode( self::ACTION . '_dismiss' ); ?>);
				body.append('_ajax_nonce', box.dataset.dismissNonce);
				fetch(ajaxurl, {method:'POST', credentials:'same-origin', body:body, keepalive:true});
			}
			// Persist the native "×" dismiss and the "No thanks" link.
			box.addEventListener('click', function(e){
				if(e.target.closest('.notice-dismiss')){ dismiss(); return; }
				var no = e.target.closest('#unysonplus-theme-dismiss');
				if(no){ e.preventDefault(); dismiss(); box.parentNode && box.parentNode.removeChild(box); return; }
			});

			// AJAX install/activate with progress.
			box.addEventListener('click', function(e){
				var btn = e.target.closest('#unysonplus-theme-btn');
				if(!btn) return;
				e.preventDefault();
				if(btn.getAttribute('aria-busy') === 'true') return;
				btn.setAttribute('aria-busy','true');

				var prog = document.getElementById('unysonplus-theme-progress');
				var status = prog.querySelector('.uts-status');
				btn.style.pointerEvents='none'; btn.style.opacity='.6';
				prog.style.display='block'; prog.className='';

				var stages = [
					<?php echo "'" . esc_js( __( 'Connecting to GitHub…', 'fw' ) ) . "',"; ?>
					<?php echo "'" . esc_js( __( 'Downloading the Unyson+ Theme…', 'fw' ) ) . "',"; ?>
					<?php echo "'" . esc_js( __( 'Installing the theme…', 'fw' ) ) . "',"; ?>
					<?php echo "'" . esc_js( __( 'Activating…', 'fw' ) ) . "'"; ?>
				];
				var i=0; status.textContent = stages[0];
				var timer = setInterval(function(){ if(i < stages.length-1){ status.textContent = stages[++i]; } }, 6000);

				var body = new FormData();
				body.append('action', btn.dataset.action);
				body.append('_ajax_nonce', btn.dataset.nonce);

				fetch(ajaxurl, {method:'POST', credentials:'same-origin', body:body})
					.then(function(r){ return r.json(); })
					.then(function(res){
						clearInterval(timer);
						if(res && res.success){
							prog.className='uts-done';
							status.textContent = <?php echo "'" . esc_js( __( 'Done! Reloading…', 'fw' ) ) . "'"; ?>;
							setTimeout(function(){ window.location = (res.data && res.data.redirect) || window.location.href; }, 800);
						} else {
							prog.className='uts-error';
							status.textContent = (res && res.data && res.data.message) ? res.data.message : <?php echo "'" . esc_js( __( 'Something went wrong.', 'fw' ) ) . "'"; ?>;
							btn.style.pointerEvents=''; btn.style.opacity=''; btn.removeAttribute('aria-busy');
							btn.textContent = <?php echo "'" . esc_js( __( 'Retry', 'fw' ) ) . "'"; ?>;
						}
					})
					.catch(function(){
						clearInterval(timer);
						prog.className='uts-error';
						status.textContent = <?php echo "'" . esc_js( __( 'Network error. Please try again.', 'fw' ) ) . "'"; ?>;
						btn.style.pointerEvents=''; btn.style.opacity=''; btn.removeAttribute('aria-busy');
					});
			});
		})();
		</script>
		<?php
	}
}
