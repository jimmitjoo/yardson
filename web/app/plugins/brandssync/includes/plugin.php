<?php

namespace BrandsSync;

use BrandsSync\Admin\Import;
use BrandsSync\Admin\Orders;
use BrandsSync\Admin\Settings;
use BrandsSync\Admin\Status;
use BrandsSync\Import\Importer;
use BrandsSync\Order\Sync_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once ABSPATH . 'wp-includes/pluggable.php';

class Plugin {

	private $loader;
	private $logger;

	private $importer;
	private $sync_order;

	const PLUGIN_NAME = 'brandssync';
	const VERSION = '0.1.1';
	const DB_VERSION = 7;

	public function __construct() {
		$this->plugin_name = self::PLUGIN_NAME;
		$this->version     = self::VERSION;

		$settings    = (array) get_option( 'brandssync' );
		$verbose_log = isset( $settings['verbose-log'] ) ? (bool) $settings['verbose-log'] : false;

		$this->logger = new Logger( $verbose_log ? Logger::DEBUG : Logger::WARNING );
		$this->loader = new Loader();

		$this->load_dependencies();
	}

	public function run() {
		if ( is_admin() ) {
			$this->loader->add_action( 'admin_menu', $this, 'setup_menu', 0 );
			$this->loader->add_action( 'admin_init', $this, 'check_version' );
			$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_style' );
		}
		$this->loader->add_filter( 'plugin_action_links', $this, 'setup_action_links', 10, 2 );

		// this can't be lazy loaded
		add_filter( 'cron_schedules', '\BrandsSync\Cron::cron_schedules' );

		$this->loader->run();
	}

	private function load_dependencies() {
		// Init services
		$this->importer   = new Importer( $this );
		$this->sync_order = new Sync_Order( $this );

		if ( is_admin() ) {
			new Import( $this );
			new Orders( $this );
			new Status( $this );
			new Settings( $this );
		} elseif ( defined( 'DOING_CRON' ) || isset( $_GET['doing_wp_cron'] ) ) {
			new Cron( $this );
		}
	}

	public function setup_menu() {
		add_menu_page( 'BrandsSync Plugin', 'BrandsSync', 'manage_options', 'brandssync-import' );
	}

	public function setup_action_links( $actions, $file ) {
		if ( basename( $file, '.php' ) == $this->get_plugin_name() ) {
			$documentation = array( 'documentation' => '<a href="https://wordpress.brandssync.rewixecommerce.com" target="_blank">Documentation</a>' );
			$settings      = array( 'settings' => '<a href="admin.php?page=brandssync-settings">Settings</a>' );

			$actions = array_merge( $documentation, $actions );
			$actions = array_merge( $settings, $actions );
		}

		return $actions;
	}

	public function enqueue_style() {
		wp_register_style( 'brandssync_admin_css', plugins_url( 'brandssync/admin/partials/style.css' ), false, '1.0.0' );
		wp_enqueue_style( 'brandssync_admin_css' );
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

	// The primary sanity check, automatically disable the plugin on activation if it doesn't meet minimum requirements.
	static function activation_check() {
		if ( ! self::compatible_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'BrandsSync requires WordPress 4.4 and WooCommerce 5.5 or higher!', 'brandssync' ) );
		}
	}

	static function create_database() {
		global $wpdb;

		$installed_ver   = get_option( 'brandssync_db_version', 0 );
		$charset_collate = $wpdb->get_charset_collate();

		if ( $installed_ver < self::DB_VERSION ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$remote_products_table_name = $wpdb->prefix . 'brandssync_remote_products';
			$sql                        = "CREATE TABLE $remote_products_table_name (
				`id` INT(10) UNSIGNED AUTO_INCREMENT,
				`rewix_product_id` INT(10) UNSIGNED NOT NULL UNIQUE,
				`wc_product_id` INT(10) UNSIGNED NOT NULL,
				`lang` VARCHAR(10) DEFAULT 'default',
				`sync_status` VARCHAR(128) NOT NULL,
				`reason` TEXT,
				`last_sync_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`priority` INT(10) NOT NULL DEFAULT '0',
				`imported` BOOLEAN NOT NULL DEFAULT FALSE,
				PRIMARY KEY (`id`),
				UNIQUE (`rewix_product_id`),
				INDEX (`wc_product_id`)
			) $charset_collate;";
			dbDelta( $sql );

			$remote_combination_table_name = $wpdb->prefix . 'brandssync_remote_variations';
			$sql                           = "CREATE TABLE $remote_combination_table_name (
				`id` INT(10) UNSIGNED AUTO_INCREMENT,
				`rewix_product_id` INT(10) UNSIGNED NOT NULL,
				`rewix_model_id` INT(10) UNSIGNED NOT NULL UNIQUE,
				`wc_model_id` INT(10) UNSIGNED NOT NULL,
				`wc_product_id` INT(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE (`rewix_model_id`, `wc_model_id`),
				INDEX (`wc_model_id`),
				FOREIGN KEY(`rewix_product_id`) REFERENCES `$remote_products_table_name`(`rewix_product_id`)
					ON DELETE CASCADE
			) $charset_collate;";
			dbDelta( $sql );

			$remote_order_table_name = $wpdb->prefix . 'brandssync_remote_orders';
			$sql                     = "CREATE TABLE $remote_order_table_name (
				`id` INT(10) UNSIGNED AUTO_INCREMENT,
				`rewix_order_id` INT(10) UNSIGNED NOT NULL,
				`wc_order_id` INT(10) UNSIGNED NOT NULL,
				`status` VARCHAR(128) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE (`wc_order_id`, `rewix_order_id`)
			) $charset_collate";
			dbDelta( $sql );

			update_option( 'brandssync_db_version', self::DB_VERSION );
		}
	}

	public function get_data_directory() {
		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'brandssync';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		$htaccess = $dir . DIRECTORY_SEPARATOR . ".htaccess";
		if ( ! file_exists($htaccess)){
			if ( $file_handle = @fopen( $htaccess, 'w' ) ) {
				fwrite( $file_handle, 'deny from all' );
				fclose( $file_handle );
			}
		}
	
		return $dir;
	}
	
	// The backup sanity check, in case the plugin is activated in a weird way, or the versions change after activation.
	function check_version() {
		if ( ! self::compatible_version() ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	function disabled_notice() {
		echo '<strong>' . esc_html__( 'BrandsSync requires WordPress 4.4 and WooCommerce 2.5 or higher!', 'brandssync' ) . '</strong>';
	}

	static function compatible_version() {
		if ( version_compare( $GLOBALS['wp_version'], '4.4', '<' ) ) {
			return false;
		}

		// Check if WooCommerce is active
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * @return Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * @return bool
	 */
	public function has_wpml_support() {
		return in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * @return Importer
	 */
	public function get_importer() {
		return $this->importer;
	}

	/**
	 * @return Sync_Order
	 */
	public function get_sync_order() {
		return $this->sync_order;
	}
}
