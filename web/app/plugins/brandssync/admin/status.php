<?php

namespace BrandsSync\Admin;

use BrandsSync\Admin\Status\List_Table;
use BrandsSync\Models\Sync_Status;
use BrandsSync\Plugin;

class Status {
	private $plugin;
	private $loader;

	private $table;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->loader = $plugin->get_loader();
		$this->settings = (array) get_option('brandssync');

		$this->loader->add_filter( 'set-screen-option', __CLASS__, 'set_screen', 10, 3 );
		$this->loader->add_action( 'admin_menu', $this, 'setup_menu' );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function setup_menu() {
		$hook = add_submenu_page( 'brandssync-import', __( 'Status', 'brandssync' ), __( 'Status', 'brandssync' ),
			'manage_options', 'brandssync-status', array( $this, 'display' ) );
		add_action( "load-$hook", [ $this, 'screen_option' ] );
	}

	/**
	 * Screen options
	 */
	public function screen_option() {
		$option = 'per_page';
		$args   = [
			'label'   => 'Products',
			'default' => 30,
			'option'  => 'products_per_page'
		];

		add_screen_option( $option, $args );

		$this->table = new List_Table( $this->plugin );
	}

	public function display() {
		require 'partials/status.php';
	}

	private function get_api_url() {
		return $this->settings['api-url'];
	}

	private function get_api_key() {
		return $this->settings['api-key'];
	}

	private function get_api_status() {
		$username = $this->settings['api-key'];
		$password = $this->settings['api-password'];
		$url = $this->settings['api-url'] . '/restful/ghost/orders/dropshipping/locked';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_code;
	}

	private function get_wc_version() {
		global $woocommerce;
		return $woocommerce->version;
	}

	private function get_last_import_timestamp() {
		return get_option('brandssync-last-import-sync', 0);
	}

	private function get_last_update_timestamp() {
		return get_option('brandssync-last-quantity-sync', 0);
	}

	private function get_queued_products_count() {
		global $wpdb;
		$status = Sync_Status::QUEUED;
		return (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}brandssync_remote_products WHERE sync_status = '$status'");
	}

	private function get_imported_products_count() {
		global $wpdb;
		$status = Sync_Status::IMPORTED;
		return (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}brandssync_remote_products WHERE imported = 1");
	}
}
