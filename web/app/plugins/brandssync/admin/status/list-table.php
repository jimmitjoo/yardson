<?php

namespace BrandsSync\Admin\Status;

use BrandsSync\Models\Remote_Product;
use Brandssync\Models\Sync_Status;
use BrandsSync\Plugin;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {

	private $importer;
	private $sync_status = '';

	/** Class constructor
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {

		parent::__construct( [
			'singular' => __( 'Product', 'brandssync' ), //singular name of the listed records
			'plural'   => __( 'Products', 'brandssync' ), //plural name of the listed records
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
	public static function get_remote_products( $per_page = 30, $page_number = 1, $status ) {
		$offset = $per_page * ( $page_number - 1 );
		$limit  = $per_page;
		if ( $status && strlen( $status ) > 0 ) {
			$products = Remote_Product::get_products_by_status( $status, $offset, $limit );
		} else {
			$products = Remote_Product::get_products( $offset, $limit );
		}

		return $products;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return int
	 */
	public static function record_count( $status ) {
		if ( $status && strlen( $status ) > 0 ) {
			$count = Remote_Product::get_products_count_by_status( $status );
		} else {
			$count = Remote_Product::get_products_count();
		}

		return $count;
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		global $sync_status;
		if ( empty( $sync_status ) ) {
			_e( 'No products in queue or imported.', 'brandssync' );
		} else {
			_e( 'No products in the selected status', 'brandssync' );
		}
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
			case 'wc_product_id':
				return '<a href="' . get_edit_post_link( $item[ $column_name ] ) . '">' . $item[ $column_name ] . '</a>';
			case 'rewix_product_id':
			case 'sync_status':
			case 'last_sync_date':
			case 'priority':
				return $item[ $column_name ];
			case 'imported':
				if ( $item[ $column_name ] == 0 ) {
					return '<span class="dashicons dashicons-no"></span>';
				} else {
					return '<span class="dashicons dashicons-yes"></span>';
				}
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="rewix-products[]" value="%s" />', $item['rewix_product_id']
		);
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'               => '<input type="checkbox" />',
			'imported'         => '<span class="dashicons dashicons-editor-help"></span>',
			'rewix_product_id' => __( 'Remote ID', 'brandssync' ),
			'wc_product_id'    => __( 'WooCommerce ID', 'brandssync' ),
			'sync_status'      => __( 'Sync Status', 'brandssync' ),
			'last_sync_date'   => __( 'Last Sync', 'brandssync' ),
			'priority'         => __( 'Priority', 'brandssync' ),
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'        => array( 'name', true ),
			'category'    => array( 'category', false ),
			'subcategory' => array( 'subcategory', false ),
			'gender'      => array( 'gender', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-remove'   => __( 'Remove from queue', 'brandssync' ),
			'bulk-reimport' => __( 'Requeue products', 'brandssync' ),
		);

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		global $sync_status;
		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby = ! empty( $_GET["orderby"] ) ? mysql_real_escape_string( $_GET["orderby"] ) : 'ASC';
		$order   = ! empty( $_GET["order"] ) ? mysql_real_escape_string( $_GET["order"] ) : '';
		if ( ! empty( $orderby ) & ! empty( $order ) ) {

		}
		$sync_status = ! empty( $_REQUEST['sync_status'] ) ? $_REQUEST['sync_status'] : '';

		$per_page     = $this->get_items_per_page( 'products_per_page', 30 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count( $sync_status );
		$this->items  = self::get_remote_products( $per_page, $current_page, $sync_status );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
		) );
	}

	protected function get_views() {

	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'bulk-remove' === $this->current_action() ) {
			$this->importer->dequeue_products( $_POST['rewix-products'] );
		} elseif ( 'bulk-reimport' === $this->current_action() ) {
			$this->importer->queue_products( $_POST['rewix-products'] );
		}
	}

	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which && ! is_singular() ) {
				$this->status_dropdown( $this->screen->post_type );

				submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			}
			?>
		</div>
		<?php
	}

	private function status_dropdown( $post_type ) {
		global $sync_status;

		$statuses = array(
			array(
				'label' => __( 'All statuses', 'brandssync' ),
				'value' => '',
			),
			array(
				'label' => __( 'Queued', 'brandssync' ),
				'value' => Sync_Status::QUEUED
			),
			array(
				'label' => __( 'Imported', 'brandssync' ),
				'value' => Sync_Status::IMPORTED
			),
			array(
				'label' => __( 'Not available', 'brandssync' ),
				'value' => Sync_Status::NOT_AVAILABLE
			)
		);

		echo '<label class="screen-reader-text" for="filter-by-status">' . __( 'Filter by status' ) . '</label>';

		$output = "<select id='filter-by-status' name='sync_status'>\n";
		foreach ( $statuses as $status ) {
			$selected = selected( $sync_status, $status['value'], false );
			$output .= "\t<option value='{$status['value']}' $selected>{$status['label']}</option>\n";
		}
		$output .= "</select>\n";

		echo $output;
	}

}
