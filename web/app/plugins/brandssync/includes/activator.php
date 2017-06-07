<?php

namespace BrandsSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/plugin.php';
require_once plugin_dir_path( __FILE__ ) . '/cron.php';

class Activator {
	public static function activate() {
		Plugin::activation_check();
		Plugin::create_database();
		Cron::schedule_events();
	}
}
