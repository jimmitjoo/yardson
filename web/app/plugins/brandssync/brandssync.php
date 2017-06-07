<?php
/**
 * Plugin Name: BrandsSync
 * Plugin URI: https://rewix.zero11.it
 * Description: Sync with your favorite B2B Dropshipment service
 * Version: 0.1.1
 * Author: Zero11
 * Author URI: http://www.zero11.it
 * License: GPL
 */

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

spl_autoload_register( 'brandssync_autoload' ); // Register autoloader
function brandssync_autoload( $classname ) {
	$prefix = 'BrandsSync\\';
	// does the class use the namespace prefix?
	if ( strpos( $classname, $prefix ) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}

	$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '_', '-', strtolower( $classname ) ) );

	// create the actual filepath
	$filePath = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . $class . '.php';

	// check if the file exists
	if ( file_exists( $filePath ) ) {
		// require once on the file
		require_once $filePath;
	} else {
		$classname = $prefix . 'includes/' . str_replace( $prefix, '', $classname );
		$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '_', '-', strtolower( $classname ) ) );
		$filePath = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . $class . '.php';
		if ( file_exists( $filePath ) ) {
			// require once on the file
			require_once $filePath;
		}
	}
}

function activate_brandssync() {
	BrandsSync\Activator::activate();
}

function deactivate_brandssync() {
	BrandsSync\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_brandssync' );
register_deactivation_hook( __FILE__, 'deactivate_brandssync' );

function init_brandssync() {
	global $brandssync;
	$brandssync = new BrandsSync\Plugin();
	$brandssync->run();
}

global $brandssync;
init_brandssync();

