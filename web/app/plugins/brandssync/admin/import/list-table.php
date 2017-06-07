<?php

namespace BrandsSync\Admin\Import;

use BrandsSync\Plugin;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class List_Table extends \WP_List_Table {

	private $importer;
	private $products;

	/** Class constructor
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {

		parent::__construct( array(
			'singular' => __( 'Product', 'brandssync' ), //singular name of the listed records
			'plural'   => __( 'Products', 'brandssync' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		) );

		$this->importer = $plugin->get_importer();
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No products available.', 'brandssync' );
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
			case 'imported':
				if ( $item[ $column_name ] == 0 ) {
					return '<span class="dashicons dashicons-no"></span>';
				} else {
					return '<span class="dashicons dashicons-yes"></span>';
				}
			case 'image':
				return "<img src='$item[$column_name]?x=150&y=150&q=95&c=600&p=white' style='width: 100%; max-width: 150px'>";
			case 'remote-id':
			case 'name':
			case 'sku':
			case 'category':
			case 'subcategory':
			case 'gender':
			case 'brand':
			case 'season':
			case 'availability':
			case 'best-taxable':
			case 'taxable':
			case 'street-price':
			case 'proposed-price':
				return $item[ $column_name ];
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
			'<input type="checkbox" name="rewix-products[]" value="%s" />', $item['ID']
		);
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'imported'       => '<span class="dashicons dashicons-editor-help"></span>',
			'remote-id'      => __( 'Remote ID', 'brandssync' ),
			'image'          => __( 'Image', 'brandssync' ),
			'name'           => __( 'Name', 'brandssync' ),
			'sku'            => __( 'SKU', 'brandssync' ),
			'category'       => __( 'Category', 'brandssync' ),
			'subcategory'    => __( 'Subcategory', 'brandssync' ),
			'subcategory'    => __( 'Brand', 'brandssync' ),
			'gender'         => __( 'Gender', 'brandssync' ),
			'season'         => __( 'Season', 'brandssync' ),
			'availability'   => __( 'Availability', 'brandssync' ),
			'best-taxable'   => __( 'Best Taxable', 'brandssync' ),
			'taxable'        => __( 'Taxable', 'brandssync' ),
			'street-price'   => __( 'Street Price', 'brandssync' ),
			'proposed-price' => __( 'Proposed Price', 'brandssync' ),
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'imported'    => array( 'imported', false ),
			'name'        => array( 'name', false ),
			'category'    => array( 'category', false ),
			'subcategory' => array( 'subcategory', false ),
			'brand'       => array( 'brand', false ),
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
			'bulk-import' => __( 'Import', 'brandssync' )
		);

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		global $selected_category, $selected_subcategory, $selected_gender, $selected_brand;

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Avoid long URI requests
		// FIXME: set specific query_arg
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer', 'imported' ), $_SERVER['REQUEST_URI'] );

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$selected_category    = ! empty( $_REQUEST['category'] ) ? $_REQUEST['category'] : '';
		$selected_subcategory = ! empty( $_REQUEST['subcategory'] ) ? $_REQUEST['subcategory'] : '';
		$selected_gender      = ! empty( $_REQUEST['gender'] ) ? $_REQUEST['gender'] : '';
		$selected_brand      = ! empty( $_REQUEST['brand'] ) ? $_REQUEST['brand'] : '';

		$per_page     = $this->get_items_per_page( 'products_per_page', 30 );
		$current_page = $this->get_pagenum();
		$this->items  = $this->get_products( $per_page, $current_page );
		$total_items  = count( $this->products );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
		) );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'bulk-import' === $this->current_action() ) {
			$this->importer->queue_products( $_REQUEST['rewix-products'] );

			wp_redirect( add_query_arg(
				'imported', count( $_REQUEST['rewix-products'] ),
				remove_query_arg( array(
					'_wp_http_referer',
					'_wpnonce',
					'rewix-products',
					'import-all',
					'action',
					'action2'
				), stripslashes( $_SERVER['REQUEST_URI'] ) ) ) );

			exit();

		} elseif ( 'import-all' === $this->current_action() ) {
			$count = $this->importer->queue_all_products();

			wp_redirect( add_query_arg(
				'imported', $count,
				remove_query_arg( array(
					'_wp_http_referer',
					'_wpnonce',
					'rewix-products',
					'import-all',
					'action',
					'action2'
				), stripslashes( $_SERVER['REQUEST_URI'] ) ) ) );

			exit();
		}

	}

	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which && ! is_singular() ) {
				$this->category_dropdown( $this->screen->post_type );
				$this->subcategory_dropdown( $this->screen->post_type );
				$this->gender_dropdown( $this->screen->post_type );
				$this->brand_dropdown( $this->screen->post_type );

				submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			}
			?>
			<?php
			submit_button( __( 'Import all products' ), 'apply', 'import-all', false );
			?>
		</div>
		<?php
	}

	public function current_action() {
		if ( isset( $_REQUEST['import-all'] ) ) {
			return 'import-all';
		}

		return parent::current_action();
	}

	private function category_dropdown( $post_type ) {
		global $selected_category;
		echo '<label class="screen-reader-text" for="filter-by-category">' . __( 'Filter by category' ) . '</label>';

		$categories = $this->importer->get_products_categories();
		sort( $categories );

		$output = '<select id="filter-by-category" name="category">';
		$output .= '<option value="">Filter by category</option>';
		foreach ( $categories as $category ) {
			$selected = selected( $selected_category, $category, false );
			$output .= "<option value='{$category}' $selected>{$category}</option>";
		}
		$output .= '</select>';

		echo $output;
	}

	private function subcategory_dropdown( $post_type ) {
		global $selected_subcategory;
		echo '<label class="screen-reader-text" for="filter-by-subcategory">' . __( 'Filter by subcategory' ) . '</label>';

		$subcategories = $this->importer->get_products_subcategories();
		sort( $subcategories );

		$output = '<select id="filter-by-subcategory" name="subcategory">';
		$output .= '<option value="">Filter by subcategory</option>';
		foreach ( $subcategories as $subcategory ) {
			$selected = selected( $selected_subcategory, $subcategory, false );
			$output .= "\t<option value='{$subcategory}' $selected>{$subcategory}</option>\n";
		}
		$output .= '</select>';

		echo $output;
	}

	private function gender_dropdown( $post_type ) {
		global $selected_gender;
		echo '<label class="screen-reader-text" for="filter-by-gender">' . __( 'Filter by gender' ) . '</label>';

		$subcategories = $this->importer->get_products_genders();
		sort( $subcategories );

		$output = '<select id="filter-by-gender" name="gender">';
		$output .= '<option value="">Filter by gender</option>';
		foreach ( $subcategories as $gender ) {
			$selected = selected( $selected_gender, $gender, false );
			$output .= "\t<option value='{$gender}' $selected>{$gender}</option>\n";
		}
		$output .= '</select>';

		echo $output;
	}
	
	private function brand_dropdown( $post_type ) {
		global $selected_brand;
		echo '<label class="screen-reader-text" for="filter-by-brand">' . __( 'Filter by brand' ) . '</label>';

		$brands = $this->importer->get_products_brands();
		sort( $brands );

		$output = '<select id="filter-by-brand" name="brand">';
		$output .= '<option value="">Filter by brand</option>';
		foreach ( $brands as $brand ) {
			$selected = selected( $selected_brand, $brand, false );
			$output .= "\t<option value='{$brand}' $selected>{$brand}</option>\n";
		}
		$output .= '</select>';

		echo $output;
	}

	private function get_products( $per_page, $current_page ) {
		global $selected_category, $selected_subcategory, $selected_gender, $selected_brand;
		$filters = array();
		if ( ! is_null( $selected_category ) && strlen( $selected_category ) > 0 ) {
			$filters['category'] = $selected_category;
		}
		if ( ! is_null( $selected_subcategory ) && strlen( $selected_subcategory ) > 0 ) {
			$filters['subcategory'] = $selected_subcategory;
		}
		if ( ! is_null( $selected_gender ) && strlen( $selected_gender ) > 0 ) {
			$filters['gender'] = $selected_gender;
		}
		if ( ! is_null( $selected_brand ) && strlen( $selected_brand ) > 0 ) {
			$filters['brand'] = $selected_brand;
		}
		$this->products = $this->importer->get_products( $filters );

		return array_slice( $this->products, ( $current_page - 1 ) * $per_page, $per_page );
	}
}
