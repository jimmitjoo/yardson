<?php

namespace BrandsSync\Admin;

use BrandsSync\Admin\Orders\List_Table;
use BrandsSync\Models\Remote_Order;
use BrandsSync\Plugin;


class Orders {
	private $plugin;
	private $loader;

	private $table;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->loader = $plugin->get_loader();

		$this->loader->add_filter( 'set-screen-option', __CLASS__, 'set_screen', 10, 3 );
		$this->loader->add_action( 'admin_menu', $this, 'setup_menu' );
		
		$this->loader->add_filter( 'manage_shop_order_posts_columns', $this, 'shop_order_columns', 999, 1);
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $this, 'render_shop_order_columns', 999, 1 );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function setup_menu() {
		$hook = add_submenu_page( 'brandssync-import', __( 'Orders', 'brandssync' ), __( 'Orders', 'brandssync' ),
			'manage_options', 'brandssync-orders', array( $this, 'display' ) );
		add_action( "load-$hook", [ $this, 'screen_option' ] );
	}

	/**
	 * Screen options
	 */
	public function screen_option() {
		$option = 'per_page';
		$args   = [
			'label'   => 'Orders',
			'default' => 30,
			'option'  => 'orders_per_page'
		];

		add_screen_option( $option, $args );

		$this->table = new List_Table( $this->plugin );
	}

	public function display() {
		require 'partials/orders.php';
	}
	
	public function shop_order_columns( $columns ) {
		$new_columns = array();
		$new_columns['brandssync_status'] = '<span>' . __( 'Sync Status', 'brandssync' ) . '</span>';
		$columns = $this->array_insert($columns, $new_columns, 'order_status');
		return $columns;
	}
	
	public function render_shop_order_columns( $column ) {
		global $post;
		
		switch ( $column ) {
			case 'brandssync_status' :
				global $wpdb;
				$table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
				$query = 'SELECT * FROM '. $table_name . ' WHERE wc_order_id = %d';
				$query = $wpdb->prepare( $query, $post->ID );
				$products   = $wpdb->get_results( $query );
				if (sizeof($products) > 0){			
					switch ( $products[0]->status ) {
						case Remote_Order::STATUS_DISPATCHED:
							echo __( 'Dispatched', 'brandssync' );
							break;
						case Remote_Order::STATUS_BOOKED:
							echo __( 'Booked', 'brandssync' );
							break;
						case Remote_Order::STATUS_CONFIRMED:
							echo __( 'Confirmed', 'brandssync' );
							break;
						case Remote_Order::STATUS_READY:
							echo __( 'Ready', 'brandssync' );
							break;
						case Remote_Order::STATUS_NOAVAILABILITY:
							echo __( 'No Availability', 'brandssync' );
							break;
						case Remote_Order::STATUS_FAILED:
						default:
							echo __( 'Failed', 'brandssync' );
					}
				}
		}
	}
	
	/**
	 * Insert an array into another array before/after a certain key
	 *
	 * @param array $array The initial array
	 * @param array $pairs The array to insert
	 * @param string $key The certain key
	 * @param string $position Wether to insert the array before or after the key
	 * @return array
	 */
	private function array_insert( $array, $pairs, $key, $position = 'after' ) {
		$key_pos = array_search( $key, array_keys( $array ) );
		if ( 'after' == $position )
			$key_pos++;
			if ( false !== $key_pos ) {
				$result = array_slice( $array, 0, $key_pos );
				$result = array_merge( $result, $pairs );
				$result = array_merge( $result, array_slice( $array, $key_pos ) );
			}
			else {
				$result = array_merge( $array, $pairs );
			}
			return $result;
	}
}
