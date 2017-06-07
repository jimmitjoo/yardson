<?php

namespace BrandsSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/cron.php';

class Deactivator {
	public static function deactivate() {
		Cron::unschedule_events();
	}
}
