<?php
// PHP Version: 7.4 or higher
if (!defined('FW')) {
    die('Forbidden');
}

class FW_Ext_Download_Source_Github extends FW_Ext_Download_Source {
	private $download_timeout = 300;

	public function get_type() {
		return 'github';
	}

	/**
	 * Download an extension from GitHub using BRANCH mode.
	 *
	 * No GitHub release/tag is required — the repository's default branch
	 * (master / main / ...) archive is downloaded, so pushing to that branch
	 * is enough to publish an extension or an extension update. No GitHub
	 * REST API call is made (see below).
	 *
	 * @param array $set {user_repo: 'UnysonPlus/UnysonPlus-Backups-Extension'}
	 * @param string $zip_path
	 *
	 * @return WP_Error|true
	 */
		public function download( array $set, $zip_path ) {
		$wp_error_id = 'fw_ext_github_download_source';

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$extension_title = $set['extension_title'];

		if ( empty( $set['user_repo'] ) ) {
			return new WP_Error(
				$wp_error_id,
				sprintf( __( '"%s" extension github source "user_repo" parameter is required', 'fw' ), $extension_title )
			);
		}

		{
			$transient_name = 'fw_ext_mngr_gh_dl';
			$transient_ttl  = HOUR_IN_SECONDS;

			$cache = get_site_transient( $transient_name );

			if ( $cache === false ) {
				$cache = [];
			}
		}

		if ( isset( $cache[ $set['user_repo'] ] ) ) {
			$download_link = $cache[ $set['user_repo'] ]['zipball_url'];
		} else {
			/**
			 * Resolve the branch to download. Defaults to the repository's
			 * GitHub default branch; override via the filter if needed.
			 */
			$branch = apply_filters( 'fw_ext_mngr_github_branch', '', $set['user_repo'] );

			/**
			 * No branch given: download the default branch as `archive/HEAD.zip`. That plain github.com
			 * address redirects to codeload and is NOT the REST API, so it does not count against the
			 * API's 60-requests-per-hour limit for unauthenticated callers (which shared hosts share per
			 * IP). It used to ask api.github.com for the default branch name first — one API call per
			 * extension, enough to exhaust the allowance when a whole set is installed at once.
			 */
			$download_link = empty( $branch )
				? 'https://github.com/' . $set['user_repo'] . '/archive/HEAD.zip'
				: 'https://github.com/' . $set['user_repo'] . '/archive/refs/heads/' . $branch . '.zip';

			$cache[ $set['user_repo'] ] = [ 'zipball_url' => $download_link ];

			set_site_transient( $transient_name, $cache, $transient_ttl );
		}

		// Download the zip
		$response = wp_remote_get(
			$download_link,
			[ 'timeout' => $this->download_timeout ]
		);

		$response_code = intval( wp_remote_retrieve_response_code( $response ) );

		if ( $response_code !== 200 ) {
			if ( $response_code ) {
				return new WP_Error(
					$wp_error_id,
					sprintf( __( 'Cannot download the "%s" extension zip. (Response code: %d)', 'fw' ),
						$extension_title, $response_code
					)
				);
			} elseif ( is_wp_error( $response ) ) {
				return new WP_Error(
					$wp_error_id,
					sprintf( __( 'Cannot download the "%s" extension zip. %s', 'fw' ),
						$extension_title,
						$response->get_error_message()
					)
				);
			} else {
				return new WP_Error(
					$wp_error_id,
					sprintf( __( 'Cannot download the "%s" extension zip.', 'fw' ),
						$extension_title
					)
				);
			}
		}

		$body = wp_remote_retrieve_body( $response );

		// save zip to file
		if ( ! $wp_filesystem->put_contents( $zip_path, $body ) ) {
			return new WP_Error(
				$wp_error_id,
				sprintf( __( 'Cannot save the "%s" extension zip.', 'fw' ), $extension_title )
			);
		}

		return true;
	}

}
