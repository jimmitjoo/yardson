<?php

namespace BrandsSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Cron {
	private $loader;
	private $plugin;
	private $importer;
	private $sync_order;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->loader = $plugin->get_loader();

		$this->loader->add_action( 'brandssync_import_products_event', $this, 'import_products' );
		$this->loader->add_action( 'brandssync_sync_quantities_event', $this, 'sync_quantities' );
		$this->loader->add_action( 'brandssync_sync_orders_event', $this, 'sync_orders' );
	}

	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'brandssync_import_products_event' ) ) {
			wp_schedule_event( time(), '5min', 'brandssync_import_products_event' );
		}
		if ( ! wp_next_scheduled( 'brandssync_sync_quantities_event' ) ) {
			wp_schedule_event( time(), '30min', 'brandssync_sync_quantities_event' );
		}
		if ( ! wp_next_scheduled( 'brandssync_sync_orders_event' ) ) {
			wp_schedule_event( time(), '30min', 'brandssync_sync_orders_event' );
		}
	}

	public static function unschedule_events() {
		wp_clear_scheduled_hook( 'brandssync_import_products_event' );
		wp_clear_scheduled_hook( 'brandssync_sync_quantities_event' );
		wp_clear_scheduled_hook( 'brandssync_sync_orders_event' );
	}

	public static function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['1min'] ) ) {
			$schedules['1min'] = array(
				'interval' => 60,
				'display'  => __( 'Every minute' )
			);
		}
		if ( ! isset( $schedules['5min'] ) ) {
			$schedules['5min'] = array(
				'interval' => 5 * 60,
				'display'  => __( 'Every 5 minutes' )
			);
		}
		if ( ! isset( $schedules['30min'] ) ) {
			$schedules['30min'] = array(
				'interval' => 30 * 60,
				'display'  => __( 'Every 30 minutes' )
			);
		}

		return $schedules;
	}

	public function import_products() {
		$lock = $this->get_lock();

		if ( $lock ) {
			@ini_set( 'max_execution_time', 300 );
			wc_set_time_limit( 300 );

			$this->plugin->get_logger()->debug( 'brandssync', 'Starting import products procedure' );
			$this->plugin->get_importer()->import_queued_products();
			$this->plugin->get_logger()->debug( 'brandssync', 'Completed import products procedure' );
			update_option( 'brandssync-last-import-sync', (int) time() );
			fclose( $lock );
		} else {
			$this->plugin->get_logger()->error( 'brandssync', 'IMPORT PRODUCTS: Cannot get lock file' );
		}
	}

	public function sync_quantities() {
		$lock = $this->get_lock();

		if ( $lock ) {
			$this->plugin->get_logger()->debug( 'brandssync', 'Starting sync quantities procedure' );
			$this->plugin->get_importer()->update_products_quantities();
			$this->plugin->get_logger()->debug( 'brandssync', 'Completed sync quantities procedure' );
			update_option( 'brandssync-last-quantity-sync', (int) time() );
			fclose( $lock );
		} else {
			$this->plugin->get_logger()->error( 'brandssync', 'SYNC QUANTITIES: Cannot get lock file' );
		}
	}

	public function sync_orders() {
		$lock = $this->get_lock();

		if ( $lock ) {
			$this->plugin->get_logger()->debug( 'brandssync', 'Starting sync orders procedure' );
			$this->plugin->get_sync_order()->update_order_statuses();
			$this->plugin->get_sync_order()->sync_with_supplier();
			$this->plugin->get_logger()->debug( 'brandssync', 'Completed sync orders procedure' );
			fclose( $lock );
		} else {
			$this->plugin->get_logger()->error( 'brandssync', 'SYNC ORDERS: Cannot get lock file' );
		}
	}

	private function get_lock() {
		//get XML path and use 'lock':
		$lock = $this->get_lock_file_path();

		$lockFile = @fopen( $lock, 'w+' );
		if ( flock( $lockFile, LOCK_EX ) ) {
			return $lockFile;
		} else {
			fclose( $lockFile );

			return false;
		}
	}

	private function get_lock_file_path() {
		global $brandssync;
		
		return trailingslashit( $brandssync->get_data_directory() ) . '.lock';
	}
}
