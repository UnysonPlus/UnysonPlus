<?php if (!defined('FW')) die('Forbidden');
/**
 * "Install Extension" (3rd-party) page.
 *
 * @var string $back_link  URL back to the extensions list.
 * @var bool   $direct_fs  Whether the extensions dir is directly writable.
 */
?>
<div class="wrap fw-ext-install-custom">

	<h1>
		<?php esc_html_e('Install Extension', 'fw'); ?>
		<a href="<?php echo esc_url($back_link); ?>" class="page-title-action"><?php esc_html_e('Back to Extensions', 'fw'); ?></a>
	</h1>

	<p class="description">
		<?php esc_html_e('Install a third-party extension that is not in the list above — upload a .zip or point to a public GitHub repository.', 'fw'); ?>
	</p>

	<div class="notice notice-warning inline fw-ext-install-custom__trust">
		<p>
			<strong><?php esc_html_e('Heads up:', 'fw'); ?></strong>
			<?php esc_html_e('An extension is executable PHP code. Only install extensions from sources you trust — exactly as you would for a WordPress plugin.', 'fw'); ?>
		</p>
		<p>
			<?php esc_html_e('Installed extensions live in the plugin folder and are preserved across normal WordPress-driven updates. If you ever update the plugin by manually deleting and re-uploading its folder over FTP, simply install the extension again afterwards.', 'fw'); ?>
		</p>
	</div>

	<?php if (!$direct_fs) : ?>
		<div class="notice notice-error inline">
			<p><?php esc_html_e('The extensions directory is not directly writable on this server, so installing from here is unavailable. Upload the extension folder via SFTP/FTP into the plugin\'s framework/extensions/ directory, or ask your host to enable direct filesystem access.', 'fw'); ?></p>
		</div>
	<?php endif; ?>

	<div class="fw-ext-install-custom__cards"<?php echo $direct_fs ? '' : ' style="opacity:.5;pointer-events:none;"'; ?>>

		<div class="fw-ext-install-custom__card">
			<h2><?php esc_html_e('Upload a .zip', 'fw'); ?></h2>
			<p class="description"><?php esc_html_e('A zipped extension folder containing manifest.php.', 'fw'); ?></p>
			<input type="file" id="fw-ext-zip" accept=".zip" />
			<p>
				<button type="button" class="button button-primary" id="fw-ext-install-zip"><?php esc_html_e('Upload &amp; install', 'fw'); ?></button>
			</p>
		</div>

		<div class="fw-ext-install-custom__card">
			<h2><?php esc_html_e('From GitHub', 'fw'); ?></h2>
			<p class="description"><?php esc_html_e('A public repository URL, e.g. https://github.com/owner/repo', 'fw'); ?></p>
			<input type="url" id="fw-ext-github" class="regular-text" placeholder="https://github.com/owner/repo" />
			<p>
				<button type="button" class="button button-primary" id="fw-ext-install-github"><?php esc_html_e('Download &amp; install', 'fw'); ?></button>
			</p>
		</div>

	</div>

	<div id="fw-ext-install-custom-notice" class="fw-ext-install-custom__notice" style="display:none;"></div>

</div>
