<div class="wrap">
	<h1>Settings</h1>
	<?php settings_errors(); ?>
	<fieldset>
		<form method="POST" action="<?php echo admin_url( 'options.php' ); ?>">
			<?php settings_fields( 'brandssync-settings' ); ?>
			<?php do_settings_sections( 'brandssync-settings' ); ?>

			<?php submit_button(); ?>
		</form>
	</fieldset>
</div>
