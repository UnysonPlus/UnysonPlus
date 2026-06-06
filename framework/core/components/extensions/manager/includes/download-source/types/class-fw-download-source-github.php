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
	 * is enough to publish an extension or an extension update.
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

			if ( empty( $branch ) ) {
				$response = wp_remote_get(
					apply_filters( 'fw_github_api_url', 'https://api.github.com' )
					. '/repos/' . $set['user_repo'],
					[ 'timeout' => 25 ]
				);

				$response_code = intval( wp_remote_retrieve_response_code( $response ) );

				if ( $response_code !== 200 ) {
					if ( $response_code === 403 && ( $json_response = json_decode( wp_remote_retrieve_body( $response ), true ) ) ) {
						return new WP_Error(
							$wp_error_id,
							__( 'Github error:', 'fw' ) . ' ' . $json_response['message']
						);
					} elseif ( $response_code ) {
						return new WP_Error(
							$wp_error_id,
							sprintf(
								__( 'Failed to access Github repository "%s". (Response code: %d)', 'fw' ),
								$set['user_repo'], $response_code
							)
						);
					} elseif ( is_wp_error( $response ) ) {
						return new WP_Error(
							$wp_error_id,
							sprintf(
								__( 'Failed to access Github repository "%s". (%s)', 'fw' ),
								$set['user_repo'], $response->get_error_message()
							)
						);
					}

					return new WP_Error(
						$wp_error_id,
						sprintf( __( 'Failed to access Github repository "%s".', 'fw' ), $set['user_repo'] )
					);
				}

				$repo = json_decode( wp_remote_retrieve_body( $response ), true );

				$branch = ( ! empty( $repo['default_branch'] ) ) ? $repo['default_branch'] : 'master';
			}

			$download_link = 'https://github.com/' . $set['user_repo'] . '/archive/refs/heads/' . $branch . '.zip';

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
