<div class="wrap">
	<h2>Remote Orders</h2>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
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
