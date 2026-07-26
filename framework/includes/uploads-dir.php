<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Single home for everything UnysonPlus writes under wp-content/uploads.
 *
 * All plugin-created upload folders live under ONE parent — wp-content/uploads/
 * unysonplus/ — as sub-dirs (icon-packs, lottie, rive, templates, designs,
 * shortcodes, asset-optimizer, css, …) instead of scattering sibling
 * uploads/unysonplus-* folders across the uploads root.
 *
 * NEW CODE that needs an uploads folder MUST route through fw_upw_uploads_dir()
 * so the convention is enforced (and future folders auto-organize) in one place.
 */
if ( ! function_exists( 'fw_upw_uploads_dir' ) ) :
	/**
	 * { path, url } of uploads/unysonplus[/<subdir>] (NO trailing slash on the
	 * returned path/url). Sub-dirs are created lazily by the caller (wp_mkdir_p).
	 *
	 * @param string $subdir Sub-directory under the unysonplus parent, e.g. 'lottie'.
	 * @return array{path:string,url:string}
	 */
	function fw_upw_uploads_dir( $subdir = '' ) {
		$up  = wp_upload_dir();
		$rel = 'unysonplus' . ( $subdir !== '' ? '/' . trim( $subdir, '/' ) : '' );
		return array(
			'path' => wp_normalize_path( trailingslashit( $up['basedir'] ) . $rel ),
			'url'  => trailingslashit( $up['baseurl'] ) . $rel,
		);
	}
endif;

if ( ! function_exists( 'fw_upw_normalize_legacy_upload_url' ) ) :
	/**
	 * Rewrite a legacy `/uploads/unysonplus-<x>/…` URL to the consolidated
	 * `/uploads/unysonplus/<x>/…` location, so URLs stored in the DB before the
	 * consolidation (Lottie / Rive `src` values) still resolve after the folder
	 * move. Applied on read (render + editor prefill). A non-matching URL is
	 * returned unchanged.
	 */
	function fw_upw_normalize_legacy_upload_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) { return $url; }
		return preg_replace(
			'#/uploads/unysonplus-(icon-packs|lottie|rive|templates|designs|shortcodes|asset-optimizer)/#',
			'/uploads/unysonplus/$1/',
			$url
		);
	}
endif;

/**
 * One-time migration: consolidate the legacy sibling uploads/unysonplus-* folders
 * — and the loose preset/page CSS that used to sit directly in uploads/unysonplus/
 * — into uploads/unysonplus/<subdir>/. Guarded by an option flag; runs once on
 * admin_init. Best-effort @rename: a folder that can't move is left in place and
 * the new code simply writes fresh files at the new location (most of these are
 * regenerable caches or re-downloadable content).
 */
if ( ! function_exists( 'fw_upw_migrate_uploads' ) ) :
	function fw_upw_migrate_uploads() {
		if ( get_option( 'unysonplus_uploads_consolidated_v1' ) ) { return; }

		$up = wp_upload_dir();
		if ( ! empty( $up['error'] ) ) { return; }

		$base   = trailingslashit( $up['basedir'] );
		$parent = $base . 'unysonplus';

		// The loose *.css that lived directly in uploads/unysonplus/ must move to
		// uploads/unysonplus/css/ FIRST — before the folder becomes the parent of
		// the moved sibling sub-dirs (order avoids globbing the sub-dirs' files).
		if ( is_dir( $parent ) ) {
			$css_dir = $parent . '/css';
			$loose   = (array) glob( $parent . '/*.css' );
			if ( $loose ) {
				wp_mkdir_p( $css_dir );
				foreach ( $loose as $f ) { @rename( $f, $css_dir . '/' . basename( $f ) ); }
			}
		}

		wp_mkdir_p( $parent );

		// Legacy sibling folder → new sub-dir name.
		$moves = array(
			'unysonplus-icon-packs'      => 'icon-packs',
			'unysonplus-lottie'          => 'lottie',
			'unysonplus-rive'            => 'rive',
			'unysonplus-templates'       => 'templates',
			'unysonplus-designs'         => 'designs',
			'unysonplus-shortcodes'      => 'shortcodes',
			'unysonplus-asset-optimizer' => 'asset-optimizer',
			'unysonplus-presets'         => 'presets', // theme Preset Library
		);
		foreach ( $moves as $old => $sub ) {
			$from = $base . $old;
			$to   = $parent . '/' . $sub;
			if ( is_dir( $from ) && ! is_dir( $to ) ) {
				@rename( $from, $to );
			}
		}

		update_option( 'unysonplus_uploads_consolidated_v1', 1, true );
	}
	add_action( 'admin_init', 'fw_upw_migrate_uploads' );
endif;
