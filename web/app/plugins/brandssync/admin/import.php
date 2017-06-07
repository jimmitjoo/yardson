<?php

namespace BrandsSync\Admin;

use BrandsSync\Admin\Import\List_Table;
use BrandsSync\Plugin;

class Import {
	private $plugin;
	private $loader;
	public $table;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		$this->loader = $plugin->get_loader();

		$this->loader->add_filter( 'set-screen-option', __CLASS__, 'set_screen', 10, 3 );
		$this->loader->add_action( 'admin_menu', $this, 'setup_menu' );

		$this->loader->add_action( 'woocommerce_product_after_variable_attributes', $this, 'variation_settings_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $this, 'save_variation_settings_fields', 10, 2 );
		$this->loader->add_action( 'admin_notices', $this, 'admin_notices' );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function setup_menu() {
		$hook = add_submenu_page( 'brandssync-import', __( 'Import', 'brandssync' ), __( 'Import', 'brandssync' ),
			'manage_options', 'brandssync-import', array( $this, 'display' ) );
		add_action( "load-$hook", [ $this, 'screen_option' ] );
	}

	public function variation_settings_fields( $loop, $variation_data, $variation ) {
		// Barcode Field
		woocommerce_wp_text_input(
			array(
				'id'          => '_barcode[' . $variation->ID . ']',
				'label'       => __( 'Barcode', 'woocommerce' ),
				'placeholder' => '80192348',
				'desc_tip'    => 'true',
				'description' => __( 'Enter the barcode value here.', 'woocommerce' ),
				'value'       => get_post_meta( $variation->ID, '_barcode', true )
			)
		);
	}

	public function save_variation_settings_fields( $post_id ) {
		// Barcode Field
		$barcode = $_POST['_barcode'][ $post_id ];
		if ( ! empty( $barcode ) ) {
			update_post_meta( $post_id, '_barcode', esc_attr( $barcode ) );
		}
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

	public function admin_notices() {
		global $pagenow;

		if ( $pagenow == 'admin.php' && isset( $_REQUEST['imported'] ) ) {
			$count   = (int) $_REQUEST['imported'];
			$message = sprintf(
				_n( 'Product has been successfully queued.', '%s products have been successfully queued.', $count ),
				number_format_i18n( $count )
			);
			echo "<div class='notice notice-success is-dismissible'><p>{$message}</p></div>";
		}
	}

	public function display() {
		require 'partials/import.php';
	}
}
