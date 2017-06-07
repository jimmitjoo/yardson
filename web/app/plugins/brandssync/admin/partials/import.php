<div class="wrap">
	<h2>Import Products</h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<?php $this->table->prepare_items(); ?>
				<div class="meta-box-sortables ui-sortable">
					<form method="get">
						<input type="hidden" name="page" value="brandssync-import"/>
						<?php $this->table->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
