<?php if ( ! defined( 'ABSPATH' ) ) { die( 'Direct access forbidden.' ); }
/**
 * "Build this site with an AI app?" — a dismissible notice on the Dashboard, Plugins and Unyson+ screens
 * while the AI Assistant extension is not active, pointing at the Extensions screen where it is installed
 * in one click. Everything about connecting an AI app (access, connection passwords, the connection check)
 * lives in Unyson+ → AI Assistant once it is active.
 *
 * Lives in core because the public release is core-only: on a fresh site the AI Assistant is not there yet.
 * (This replaced the former one-click "Connect AI" setup page, whose theme switch and extension bundle were
 * more than most sites need — see the Design Decisions log.)
 */
class UnysonPlus_AI_Notice {

	const CAPABILITY   = 'manage_options';
	const PARENT_SLUG  = 'fw-extensions';
	/** Per-user meta: the notice was dismissed (same key as before, so a dismissal is remembered). */
	const DISMISS_META = 'unysonplus_connect_ai_notice_dismissed';

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'wp_ajax_fw_ai_notice_dismiss', array( __CLASS__, 'ajax_dismiss' ) );
	}

	public static function notice(): void {
		if ( ! current_user_can( self::CAPABILITY ) || ( function_exists( 'fw_ext' ) && fw_ext( 'ai-assistant' ) ) ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) ) {
			return;
		}
		$relevant = in_array( $screen->id, array( 'dashboard', 'plugins' ), true )
			|| strpos( $screen->id, self::PARENT_SLUG ) !== false
			|| strpos( $screen->id, 'fw-settings' ) !== false;
		if ( ! $relevant ) {
			return;
		}
		$nonce = wp_create_nonce( 'fw_ai_notice_dismiss' );
		?>
		<div class="notice notice-info is-dismissible" id="fw-ai-notice">
			<p>
				<strong><?php esc_html_e( 'Build this site with an AI app?', 'fw' ); ?></strong>
				<?php esc_html_e( 'Install the AI Assistant extension: it adds a chat that builds and edits pages, and lets the AI apps you use work on this site.', 'fw' ); ?>
				<a class="button button-primary" style="margin-left:.4rem;vertical-align:baseline" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PARENT_SLUG ) ); ?>"><?php esc_html_e( 'Open Extensions', 'fw' ); ?></a>
			</p>
		</div>
		<script>
		(function(){
			var n = document.getElementById('fw-ai-notice');
			if (!n || typeof ajaxurl === 'undefined') return;
			n.addEventListener('click', function(e){
				if (!e.target.closest('.notice-dismiss')) return;
				var b = new FormData();
				b.append('action', 'fw_ai_notice_dismiss');
				b.append('_ajax_nonce', <?php echo wp_json_encode( $nonce ); ?>);
				fetch(ajaxurl, {method:'POST', credentials:'same-origin', body:b, keepalive:true});
			});
		})();
		</script>
		<?php
	}

	public static function ajax_dismiss(): void {
		check_ajax_referer( 'fw_ai_notice_dismiss' );
		update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		wp_send_json_success();
	}
}
