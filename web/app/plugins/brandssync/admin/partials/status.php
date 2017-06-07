<div class="wrap brandssync-status-page">
	<h2>Status</h2>

	<div class="card">
		<h2><?php echo __( 'API Connection Status', 'brandssync' ) ?></h2>
		<p><strong>URL</strong> <?php echo $this->get_api_url() ?></p>
		<p><strong>API Key</strong> <?php echo $this->get_api_key() ?></p>
		<p><strong>Status</strong>
			<?php $api_status = $this->get_api_status(); ?>
			<?php if ( $api_status == 401 ): ?>
				<span
					class="text-danger"><?php echo __( 'Unauthorized. Your API Key is not enabled for this service.', 'brandssync' ) ?></span>
				<div><?php echo __('Please contact your dropshipping service provider for more information', 'brandssync') ?></div>
			<?php elseif ( $api_status == 0 ): ?>
				<span
					class="text-danger"><?php echo __( 'Connection error. Please check your configuration', 'brandssync' ) ?></span>
			<?php elseif ( $api_status != 200 ): ?>
				<span class="text-danger"><?php echo __( 'Request error', 'brandssync' ) ?></span>
			<?php else: ?>
				<span class="text-success"><?php echo __( 'Ok', 'brandssync' ) ?></span>
			<?php endif ?>
		</p>
	</div>

	<div class="card">
		<h2><?php echo __( 'Version', 'brandssync' ) ?></h2>
		<p><strong>BrandsSync version</strong> <?php echo $this->plugin->get_version() ?></p>
		<p><strong>Wordpress version</strong> <?php echo $GLOBALS['wp_version'] ?></p>
		<p><strong>WooCommerce version</strong> <?php echo $this->get_wc_version() ?></p>
	</div>

	<div class="card">
		<h2><?php echo __( 'Cron', 'brandssync' ) ?></h2>
		<p><?php echo __( 'For information about cronjob configuration, please contact your Wordpress hosting provider.', 'brandssync' ) ?></p>
		<?php $last_import = $this->get_last_import_timestamp();
		$last_update       = $this->get_last_update_timestamp(); ?>
		<?php if ( $last_import == 0 || $last_update == 0 ): ?>
			<p class="text-danger"><?php echo __( 'Cronjob hasn\'t run yet. Please check your configuration', 'brandssync' ) ?></p>
		<?php else: ?>
			<?php if ( $last_import < time() - 30 * 60 || $last_update < time() - 60 * 60 ): ?>
				<p class="text-warning"><?php echo __( 'Cronjobs are late for schedule. Please check your configuration', 'brandssync' ) ?></p>
			<?php endif ?>
			<p>
				<strong>Last import synchronization</strong>
				<?php if ( $last_import < time() - 30 * 60 ): ?>
					<span class="text-warning"> <?php echo date( 'M d, Y H:i:s T', $last_import ) ?></span>
				<?php else: ?>
					<span class="text-success"> <?php echo date( 'M d, Y H:i:s T', $last_import ) ?></span>
				<?php endif ?>
			</p>
			<p>
				<strong>Last quantity synchronization</strong>
				<?php if ( $last_update < time() - 60 * 60 ): ?>
					<span class="text-warning"> <?php echo date( 'M d, Y H:i:s T', $last_update ) ?></span>
				<?php else: ?>
					<span class="text-success"> <?php echo date( 'M d, Y H:i:s T', $last_update ) ?></span>
				<?php endif ?>
			</p>
		<?php endif ?>
	</div>

	<div class="card">
		<h2><?php echo __( 'Products status', 'brandssync' ) ?></h2>
		<p><strong>Queued products</strong> <?php echo $this->get_queued_products_count() ?></p>
		<p><strong>Imported products</strong> <?php echo $this->get_imported_products_count() ?></p>
	</div>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<input type="hidden" name="page" value="brandssync-status"/>
						<?php
						$this->table->prepare_items();
						$this->table->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
