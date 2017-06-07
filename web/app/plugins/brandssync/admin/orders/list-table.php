<?php

namespace BrandsSync\Admin\Orders;

use BrandsSync\Models\Remote_Order;
use BrandsSync\Plugin;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {

	private $importer;

	/** Class constructor */
	public function __construct( Plugin $plugin ) {

		parent::__construct( [
			'singular' => __( 'Remote Order', 'brandssync' ), //singular name of the listed records
			'plural'   => __( 'Remote Orders', 'brandssync' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

		$this->importer = $plugin->get_importer();
	}

	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return array
	 */
	public static function get_remote_products( $per_page = 30, $page_number = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'brandssync_remote_orders';
		$offset     = $per_page * ( $page_number - 1 );
		$limit      = $per_page;
		$products   = $wpdb->get_results( "SELECT * FROM $table_name LIMIT $limit OFFSET $offset", ARRAY_A );

		return $products;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return int
	 */
	public static function record_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'brandssync_remote_orders';
		$count      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		return $count;
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No order has been added.', 'brandssync' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'ID':
			case 'rewix_order_id':
			case 'wc_order_id':
				return $item[ $column_name ];
			case 'status':
				switch ( $item[ $column_name ] ) {
					case Remote_Order::STATUS_DISPATCHED:
						return __( 'Dispatched', 'brandssync' );
					case Remote_Order::STATUS_BOOKED:
						return __( 'Booked', 'brandssync' );
					case Remote_Order::STATUS_CONFIRMED:
						return __( 'Confirmed', 'brandssync' );
					case Remote_Order::STATUS_READY:
						return __( 'Ready', 'brandssync' );
					case Remote_Order::STATUS_NOAVAILABILITY:
						return __( 'No Availability', 'brandssync' );
					case Remote_Order::STATUS_FAILED:
					default:
						return __( 'Failed', 'brandssync' );
				}
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_wc_order_id( $item ) {

		$import_nonce = wp_create_nonce( 'brandssync_orders' );

		$title = $item['wc_order_id'];

		$actions = array(
			'show-order' => sprintf( '<a href="%s">See order details</a>', admin_url( 'post.php?post=' . absint( $title ) . '&action=edit' ) )
		);

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'rewix_order_id' => __( 'Remote ID', 'brandssync' ),
			'wc_order_id'    => __( 'WooCommerce ID', 'brandssync' ),
			'status'         => __( 'Sync Status', 'brandssync' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array();

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();


		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page     = $this->get_items_per_page( 'orders_per_page', 30 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();
		$this->items  = self::get_remote_products( $per_page, $current_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
		) );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'bulk-remove' === $this->current_action() ) {
		}
	}

}
