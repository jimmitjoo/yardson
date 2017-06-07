<?php

namespace BrandsSync\Order;

use BrandsSync\Models\Remote_Order;
use BrandsSync\Models\Remote_Variation;
use BrandsSync\Plugin;

class Sync_Order {

	const SOLD_API_LOCK_OP = 'lock';
	const SOLD_API_SET_OP = 'set';
	const SOLD_API_UNLOCK_OP = 'unlock';

	private $plugin;
	private $logger;
	private $settings;
	private $pending_cache = null;
	private $processing_cache = null;

	public function __construct( Plugin $plugin ) {
		$this->plugin   = $plugin;
		$this->logger   = $this->plugin->get_logger();
		$this->settings = (array) get_option( 'brandssync' );

		$loader = $this->plugin->get_loader();
		$loader->add_action( 'woocommerce_after_checkout_validation', $this, 'on_checkout_process', 999 );
		$loader->add_action( 'woocommerce_order_status_processing', $this, 'on_order_processing', 10, 1 );
		$loader->add_action( 'before_delete_post', $this, 'on_order_delete', 10, 1 );
	}

	public function on_checkout_process() {
		//Do not reserve if there are already checkout errors
		if(isset( $_POST['woocommerce_checkout_update_totals'] ) || wc_notice_count( 'error' ) > 0){
			return;
		}
		
		$cart     = WC()->cart;
		$products = array();

		foreach ( $cart->cart_contents as $product ) {
			$item = array();
			if ( $product['data']->product_type == 'simple' ) {
				$item['model_id'] = $this->get_rewix_id($product['product_id']);
			} else { // variable product
				$item['model_id'] = $this->get_rewix_id($product['variation_id']);
			}
			$item['qty']  = (int) $product['quantity'];
			$item['type'] = self::SOLD_API_LOCK_OP;
			
			if ( $item['model_id'] ) {
				$products[]   = $item;
			}
		}

		$errors = $this->modify_growing_order( $products );
		if ( count( $errors ) > 0 ) {
			foreach ( $errors as $model_id => $qty ) {
				if ( is_cart() ) {
					wc_print_notice(
						sprintf( 'Error while placing order. Product %s is not available in quantity requested (%d).',
							$this->get_product_name_from_rewix_model_id( (int) $model_id ),
							$qty
						), 'error'
					);
				} else {
					wc_add_notice(
						sprintf( 'Error while placing order. Product %s is not available in quantity requested (%d).',
							$this->get_product_name_from_rewix_model_id( (int) $model_id ),
							$qty
						), 'error'
					);
				}
			}
		}
	}

	/**
	 * @param int $model_id
	 *
	 * @return string
	 */
	private function get_product_name_from_rewix_model_id( $model_id ) {
		global $wpdb;
		$table_name    = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$wc_product_id = (int) $wpdb->get_var( "SELECT wc_product_id FROM $table_name WHERE rewix_model_id = $model_id" );

		return get_the_title( $wc_product_id );
	}

	public function on_order_processing( $order_id ) {
		$this->send_dropshipping_order( wc_get_order( $order_id ) );

		return $order_id;
	}

	private function modify_growing_order( $operations ) {
		$xml            = new \SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root></root>' );
		$operation_lock = $xml->addChild( 'operation' );
		$operation_lock->addAttribute( 'type', self::SOLD_API_LOCK_OP );
		$operation_set = $xml->addChild( 'operation' );
		$operation_set->addAttribute( 'type', self::SOLD_API_SET_OP );
		$operation_unlock = $xml->addChild( 'operation' );
		$operation_unlock->addAttribute( 'type', self::SOLD_API_UNLOCK_OP );

		foreach ( $operations as $op ) {
			switch ( $op['type'] ) {
				case self::SOLD_API_LOCK_OP:
					$model = $operation_lock->addChild( 'model' );
					break;
				case self::SOLD_API_SET_OP:
					$model = $operation_set->addChild( 'model' );
					break;
				case self::SOLD_API_UNLOCK_OP:
					$model = $operation_unlock->addChild( 'model' );
					break;
			}
			if ( isset( $model ) ) {
				$model->addAttribute( 'stock_id', $op['model_id'] );
				$model->addAttribute( 'quantity', $op['qty'] );
				$this->logger->info( 'brandssync', "Model Ref.ID #{$op['model_id']}, qty: {$op['qty']}, operation type: {$op['type']}" );
			} else {
				$this->logger->info( 'brandssync', 'Invalid operation type: ' . $op['type'] );
			}
		}

		$xml_text = $xml->asXML();

		$username = $this->settings['api-key'];
		$password = $this->settings['api-password'];
		$url      = $this->settings['api-url'] . '/restful/ghost/orders/sold';
		$ch       = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, $username . ':' . $password );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/xml', 'Accept: application/xml' ) );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml_text );
		$data = curl_exec( $ch );

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		if ( ! $this->handle_curl_error( $http_code ) ) {
			return array( 'Error while booking products on remote platform' );
		}

		$reader = new \XMLReader();
		$reader->xml( $data );
		$reader->read();
		update_option( 'brandssync_growing_order_id', $reader->getAttribute( 'order_id' ) );

		$errors        = array();
		$growing_order = array();

		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'model' ) {
				$stock_id                   = $reader->getAttribute( 'stock_id' );
				$growing_order[ $stock_id ] = array(
					'stock_id'  => $stock_id,
					'locked'    => $reader->getAttribute( 'locked' ),
					'available' => $reader->getAttribute( 'available' ),
				);
			}
		}

		foreach ( $operations as $op ) {
			if ( isset( $growing_order[ $op['model_id'] ] ) ) {
				$product             = $growing_order[ $op['model_id'] ];
				$success             = true;
				$pending_quantity    = $this->get_pending_quantity_by_rewix_model( (int) $op['model_id'] );
				$processing_quantity = $this->get_processing_quantity_by_rewix_model( (int) $op['model_id'] );

				if ( $op['type'] == self::SOLD_API_LOCK_OP && $product['locked'] < ( $pending_quantity + $processing_quantity + $op['qty'] ) ) {
					$success = false;
				} else if ( $op['type'] == self::SOLD_API_UNLOCK_OP && $product['locked'] < ( $pending_quantity + $processing_quantity - $op['qty'] ) ) {
					$success = false;
				} else if ( $op['type'] == self::SOLD_API_SET_OP && $product['locked'] < $op['qty'] ) {
					$success = false;
				}

				if ( ! $success ) {
					$this->logger->error( 'brandssync', 'Model Ref.ID #' . $op['model_id'] . ', looked: ' . $product['locked'] . ', qty: ' . $op['qty'] . ', pending: ' . $this->get_pending_quantity_by_rewix_model( $stock_id ) . ', processing: ' . $this->get_pending_quantity_by_rewix_model( $stock_id ) . ', operation type: ' . $op['type'] . ' : OPERATION FAILED!' );
					$errors[ $op['model_id'] ] = $op['qty'];
				} else {
					$this->logger->info( 'brandssync', 'Model Ref.ID #' . $op['model_id'] . ', looked: ' . $product['locked'] . ', qty: ' . $op['qty'] . ', pending: ' . $this->get_pending_quantity_by_rewix_model( $stock_id ) . ', processing: ' . $this->get_pending_quantity_by_rewix_model( $stock_id ) . ', operation type: ' . $op['type'] );
				}
			} else {
				$errors[ $op['model_id'] ] = $op['qty'];
			}
		}

		return $errors;
	}

	private function handle_curl_error( $http_code ) {
		if ( $http_code == 401 ) {
			$this->logger->error( 'brandssync', 'UNAUTHORIZED!!' );
			if (function_exists('wc_print_notice')){
				wc_print_notice('You are NOT authorized to access this service. <br/> Please check your configuration in System -> Configuration or contact your supplier.');
			}
			return false;
		} else if ( $http_code == 0 ) {
			$this->logger->error( 'brandssync', 'HTTP Error 0!!' );
			if (function_exists('wc_print_notice')){
				wc_print_notice('There has been an error executing the request.<br/> Please check your configuration in System -> Configuration');
			}
			return false;
		} else if ( $http_code != 200 ) {
			$this->logger->error( 'brandssync', 'HTTP Error ' . $http_code . '!!' );
			if (function_exists('wc_print_notice')){
				wc_print_notice('There has been an error executing the request.<br/> HTTP Error Code: ' . $http_code);
			}
			return false;
		}

		return true;
	}

	private function get_pending_quantity_by_rewix_model( $rewix_model_id ) {
		list( $product_id, $variation_id ) = $this->get_wc_id( (int) $rewix_model_id );

		return $this->get_pending_quantity( $product_id, $variation_id );
	}

	private function get_processing_quantity_by_rewix_model( $rewix_model_id ) {
		list ( $product_id, $variation_id ) = $this->get_wc_id( (int) $rewix_model_id );

		return $this->get_processing_quantity( $product_id, $variation_id );
	}

	private function get_pending_quantity( $product_id, $variation_id ) {
		if ( is_null($this->pending_cache) ) {
			global $wpdb;
			$query               = 'SELECT wc_product_id, wc_model_id, sum(qty) AS quantity FROM
					(SELECT DISTINCT order_item_id,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_qty\')          AS qty,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_product_id\')   AS wc_product_id,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_variation_id\') AS wc_model_id
					FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im) orders
					WHERE
						orders.order_item_id IN (SELECT order_item_id FROM ' . $wpdb->prefix . 'woocommerce_order_items, ' . $wpdb->prefix . 'posts
													WHERE order_id = ID AND post_status in (\'wc-pending\',\'wc-on-hold\'))
						AND wc_product_id IS NOT NULL
					GROUP BY wc_product_id, wc_model_id';
			$this->pending_cache = $wpdb->get_results( $query, ARRAY_A );
		}
		foreach ( $this->pending_cache as $product ) {
			if ( $product['wc_product_id'] == $product_id && $product['wc_model_id'] = $variation_id ) {
				return (int) $product['quantity'];
			}
		}

		return 0;
	}

	private function get_processing_quantity( $product_id, $variation_id ) {
		if ( is_null($this->processing_cache) ) {
			global $wpdb;
			$query                  = 'SELECT wc_product_id, wc_model_id, sum(qty) AS quantity FROM
					(SELECT DISTINCT order_item_id,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_qty\')          AS qty,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_product_id\')   AS wc_product_id,
						(SELECT meta_value
							FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im2
							WHERE im2.order_item_id = im.order_item_id AND im2.meta_key = \'_variation_id\') AS wc_model_id
					FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta im) orders
					WHERE
						orders.order_item_id IN (SELECT order_item_id FROM ' . $wpdb->prefix . 'woocommerce_order_items, ' . $wpdb->prefix . 'posts
													WHERE order_id = ID AND post_status = \'wc-processing\'
													and not exists (
														SELECT * from ' . $wpdb->prefix . Remote_Order::TABLE_NAME . ' where order_id = wc_order_id
													))
						AND wc_product_id IS NOT NULL
					GROUP BY wc_product_id, wc_model_id';
			$this->processing_cache = $wpdb->get_results( $query, ARRAY_A );
		}
		foreach ( $this->processing_cache as $product ) {
			if ( $product['wc_product_id'] == $product_id && $product['wc_model_id'] = $variation_id ) {
				return (int) $product['quantity'];
			}
		}

		return 0;
	}

	/**
	 * @param int $rewix_model_id
	 *
	 * @return array
	 */
	private function get_wc_id( $rewix_model_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$result     = $wpdb->get_row( "SELECT wc_product_id, wc_model_id FROM $table_name WHERE rewix_model_id = $rewix_model_id" );
	
		return array( $result->wc_product_id, $result->wc_model_id );
	}

	/**
	 * @param int $wc_id
	 *
	 * @return int
	 */
	private function get_rewix_id( $wc_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$result     = $wpdb->get_var( "SELECT rewix_model_id FROM $table_name WHERE wc_model_id = $wc_id" );
	
		return (int) $result;
	}

	private function send_dropshipping_order( \WC_Order $order ) {
		global $wpdb;
		$items = $order->get_items();

		$xml        = new \SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root></root>' );
		$order_list = $xml->addChild( 'order_list' );
		$xml_order  = $order_list->addChild( 'order' );
		$item_list  = $xml_order->addChild( 'item_list' );
		$xml_order->addChild( 'key', $order->get_order_number() );
		$xml_order->addChild( 'date', str_replace( '-', '/', $order->order_date ) . ' +0000' );

		$remote_products = 0;
		$mixed = false;
		foreach ( $items as $item ) {
			$product_id   = (int) $item['product_id'];
			$variation_id = (int) $item['variation_id'];
			$rewix_id     = $this->get_rewix_id( $variation_id > 0 ? $variation_id : $product_id);

			if ( ! $rewix_id && $product_id > 0 ) {
				$mixed = true;
			}

			if ( $rewix_id ) {
				$remote_products ++;
				$item_node = $item_list->addChild( 'item' );
				$item_node->addChild( 'stock_id', $rewix_id );
				$item_node->addChild( 'quantity', (int) $item['qty'] );
				$this->logger->info( 'brandssync', 'Creating dropshipping order with model ID#' . $rewix_id . ' with quantity ' . $item['qty'] );
			}
		}
		if ( $remote_products == 0 ) {
			return false;
		}
		if ( $mixed ){
			$this->logger->error( 'brandssync', 'Order #' . $order->get_order_number() . ': Mixed Order!!!' );
			
			return false;
		}

		$recipient_details = $xml_order->addChild( 'recipient_details' );
		$recipient         = $recipient_details->addChild( 'recipient', $order->shipping_first_name . ' ' . $order->shipping_last_name );
		$careof            = $recipient_details->addChild( 'careof', $order->shipping_company );
		$phone             = $recipient_details->addChild( 'phone' );
		$phone_number      = $phone->addChild( 'number', $order->billing_phone );
		$address           = $recipient_details->addChild( 'address' );
		$street_name       = $address->addChild( 'street_name', $order->shipping_address_1 . ' ' . $order->shipping_address_2 );
		$zip               = $address->addChild( 'zip', $order->shipping_postcode );
		$city              = $address->addChild( 'city', $order->shipping_city );
		$province          = $address->addChild( 'province', $order->shipping_state );
		$country_code      = $address->addChild( 'countrycode', $order->shipping_country );

		$xml_text = $xml->asXML();

		$username = (string) $this->settings['api-key'];
		$password = (string) $this->settings['api-password'];
		$api_url  = (string) $this->settings['api-url'];
		$url      = $api_url . '/restful/ghost/orders/0/dropshipping';
		$ch       = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, $username . ':' . $password );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/xml', 'Accept: application/xml' ) );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml_text );
		$data   = curl_exec( $ch );

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		if ( ! $this->handle_curl_error( $http_code ) ) {
			return false;
		}

		$reader = new \XMLReader();
		$reader->xml( $data );
		
		$doc = new \DOMDocument( '1.0', 'UTF-8' );
		$reader->read();
		$rewix_order_id = $reader->getAttribute( 'order_id' );
		
		$this->logger->info( 'brandssync', 'Rewix order id: ' . $rewix_order_id . ' ' . $data );
		
		//TODO I will get all growing order content
		//may I do something with it??

		$url = $api_url . '/restful/ghost/clientorders/clientkey/' . $order->get_order_number();
		$ch  = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, $username . ':' . $password );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: application/xml' ) );
		$data = curl_exec( $ch );

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		if ( $http_code == 401 ) {
			$this->logger->err( 'brandssync', 'Send dropshipping order: UNAUTHORIZED!!' );

			return false;
		} else if ( $http_code == 500 ) {
			$this->logger->error( 'brandssync', 'Exception: Order #' . $order->get_order_number() . ' does not exists on rewix platform' );
			$this->logger->error( 'brandssync', 'Dropshipping operation for order #' . $order->get_order_number() . ' failed!!' );

			$remote_order_table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
			$wpdb->update(
				$remote_order_table_name,
				array( 'status' => 'failed' ), array( 'wc_order_id' => $order->get_order_number() ),
				array( '%s' ), array( '%d' )
			);

			return false;
		} else if ( $http_code != 200 ) {
			$this->logger->error( 'brandssync', 'Send dropshipping order: ERROR ' . $http_code . ' ' . curl_error( $ch ) );

			return false;
		}

		$reader = new \XMLReader();
		$reader->xml( $data );
		$doc = new \DOMDocument( '1.0', 'UTF-8' );

		$this->logger->info( 'brandssync', 'dropshipping order created successfully!!' );
		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'order' ) {
				$xml_order      = simplexml_import_dom( $doc->importNode( $reader->expand(), true ) );
				$rewix_order_id = (int) $xml_order->order_id;
				$status         = (int) $xml_order->status;
				$order_id       = (int) $order->get_order_number();


				$remote_order_table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
				$wpdb->insert(
					$remote_order_table_name,
					array(
						'wc_order_id'    => $order_id,
						'rewix_order_id' => $rewix_order_id,
						'status'         => $status
					)
				);
				$this->logger->info( 'brandssync', 'Entry (' . $rewix_order_id . ',' . $order_id . ') in association table created' );
				$this->logger->info( 'brandssync', 'Entries in association table created' );
				$this->logger->info( 'brandssync', 'Supplier order created successfully!!' );
			}
		}
	}

	public function update_order_statuses() {
		global $wpdb;
		$this->logger->info( 'brandssync', 'Order statuses update procedures STARTED!' );

		$remote_order_table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
		$status                  = Remote_Order::STATUS_DISPATCHED;
		$orders                  = $wpdb->get_results( "SELECT * FROM $remote_order_table_name WHERE status != $status" );
		$api_url                 = $this->settings['api-url'];
		$api_key                 = $this->settings['api-key'];
		$api_password            = $this->settings['api-password'];

		foreach ( $orders as $order ) {
			$wc_order = wc_get_order( (int) $order->wc_order_id );
			$url      = $api_url . '/restful/ghost/';
			// if dropshipping
			$url .= 'clientorders/clientkey/' . $wc_order->get_order_number();
			// TODO: add remote supplier

			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_USERPWD, $api_key . ':' . $api_password );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: application/xml' ) );
			$data = curl_exec( $ch );

			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_error = curl_error( $ch );
			curl_close( $ch );
			
			if ( $http_code == 401 ) {
				$this->logger->error( 'brandssync', 'UNAUTHORIZED!!' );

				return false;
			} else if ( $http_code == 500 ) {
				$this->logger->error( 'brandssync', 'Exception: Order #' . $wc_order->get_order_number() . ' does not exists on rewix platform' );
			} else if ( $http_code != 200 ) {
				$this->logger->error( 'brandssync', 'ERROR ' . $http_code . ' ' . $curl_error );
			}

			$reader = new \XMLReader();
			$reader->xml( $data );
			$doc = new \DOMDocument( '1.0', 'UTF-8' );

			while ( $reader->read() ) {
				if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'order' ) {
					$xml_order = simplexml_import_dom( $doc->importNode( $reader->expand(), true ) );
					$status    = (int) $xml_order->status;
					$order_id  = $xml_order->order_id;
					$this->logger->info( 'brandssync', 'Order_id: #' . $order_id . ' NEW Status:' . $status . ' OLD Status ' . $order->status );
					if ( (int) $order->status != $status ) {
						$wpdb->update(
							$remote_order_table_name,
							array(
								'status'         => $status,
								'rewix_order_id' => $order_id
							),
							array( 'id' => $order->id ),
							array( '%s', '%d' ), array( '%d' )
						);
						$this->logger->info( 'brandssync', 'Order status Update: WC ID #' . $order->wc_order_id . ' Rewix Order ID #' . $order_id . ': new status [' . $status . ']' );

						if ( $status == Remote_Order::STATUS_DISPATCHED ) {
							wc_get_order( $order->wc_order_id )->update_status( 'completed' );
							update_post_meta( $order->wc_order_id, 'tracking_url', $xml_order->tracking_url );
						}
					}
				}
			}
		}
		$this->logger->info( 'brandssync', 'Order statuses update procedures COMPLETED!' );
	}

	public function sync_with_supplier() {
		$this->sync_booked_products();
		$this->send_missing_orders(); // if dropshipper
	}

	private function sync_booked_products() {
		$booked_products = $this->get_growing_order_products();
		$rewix_products  = $this->get_local_rewix_products();

		$locked     = 0;
		$available  = 0;
		$operations = array();
		foreach ( $rewix_products as $rewix_product ) {
			$locked    = 0;
			$available = 0;
			if ( $booked_products ) {
				foreach ( $booked_products as $booked_product ) {
					if ( $booked_product['stock_id'] == $rewix_product['rewix_model_id'] ) {
						$locked    = $booked_product['locked'];
						$available = $booked_product['available'];
						break;
					}
				}
			}

			$processing_qty = $this->get_processing_quantity( (int) $rewix_product['wc_product_id'], (int) $rewix_product['wc_model_id'] );
			$pending_qty    = $this->get_pending_quantity( (int) $rewix_product['wc_product_id'], (int) $rewix_product['wc_model_id'] );

			if ( $processing_qty + $pending_qty != $locked ) {
				$operations[] = array(
					'type'     => self::SOLD_API_SET_OP,
					'model_id' => $rewix_product['rewix_model_id'],
					'qty'      => $processing_qty + $pending_qty,
				);
			}
		}
		if (count($operations) > 0){
			$this->modify_growing_order( $operations );
		}
	}

	private function send_missing_orders() {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
		$orders     = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}posts " .
		                              "WHERE post_status = 'wc-processing' AND ID NOT IN (SELECT wc_order_id FROM $table_name)" );
		foreach ( $orders as $order_id ) {
			$this->send_dropshipping_order( wc_get_order( $order_id ) );
		}
	}

	private function get_growing_order_products() {
		$api_url      = $this->settings['api-url'];
		$api_key      = $this->settings['api-key'];
		$api_password = $this->settings['api-password'];
		$url          = $api_url . '/restful/ghost/orders/dropshipping/locked/';

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERPWD, $api_key . ':' . $api_password );
		$data = curl_exec( $ch );

		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		if ( ! $this->handle_curl_error( $http_code ) ) {
			return false;
		}

		$reader = new \XMLReader();
		$reader->xml( $data );

		$doc = new \DOMDocument( '1.0', 'UTF-8' );
		$reader->read();
		update_option( 'brandssync_growing_order_id', $reader->getAttribute( 'order_id' ) );

		$products = array();

		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'model' ) {
				$product              = array();
				$product['stock_id']  = $reader->getAttribute( 'stock_id' );
				$product['locked']    = $reader->getAttribute( 'locked' );
				$product['available'] = $reader->getAttribute( 'available' );
				$products[]           = $product;
			}
		}

		return $products;
	}

	private function get_local_rewix_products() {
		global $wpdb;

		return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . Remote_Variation::TABLE_NAME, ARRAY_A );
	}

	public function on_order_delete( $post_id ) {
		// We check if the global post type isn't ours and just return
		global $post_type, $wpdb;
		if ( $post_type != 'shop_order' ) {
			return;
		}
		$table_name = $wpdb->prefix . Remote_Order::TABLE_NAME;
		$wpdb->delete( $table_name, array( 'wc_order_id' => $post_id ), array( '%d' ) );
	}
}
