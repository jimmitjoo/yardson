<?php

namespace BrandsSync\Import;

use BrandsSync\Models\Remote_Category;
use BrandsSync\Models\Remote_Product;
use BrandsSync\Models\Remote_Variation;
use BrandsSync\Models\Sync_Status;
use BrandsSync\Plugin;
use WP_Error;

class Importer {

	private $plugin;
	private $logger;
	private $settings;
	private $category_structure;
	private $attribute_taxonomies;

	private $products = null;
	private $tags = null;

	/**
	 * Importer constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin               = $plugin;
		$this->logger               = $plugin->get_logger();
		$this->settings             = (array) get_option( 'brandssync' );
		$this->category_structure   = Tools::get_category_structure();
		$this->attribute_taxonomies = array();

		$plugin->get_loader()->add_action( 'before_delete_post', $this, 'on_product_delete', 10, 1 );
	}

	/**
	 * @param array $filter
	 *
	 * @return array
	 */
	public function get_products( $filter = array() ) {
		$reader      = new \XMLReader();
		$source_file = Tools::get_xml_source();
		if ( $source_file ) {
			$reader->open( $source_file );
		} else {
			return array();
		}
		$products   = array();
		$this->tags = array(
			Remote_Category::REWIX_CATEGORY_ID    => array(),
			Remote_Category::REWIX_SUBCATEGORY_ID => array(),
			Remote_Category::REWIX_GENDER_ID      => array(),
			Remote_Category::REWIX_BRAND_ID       => array(),
		);
		$lang       = 'default';

		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'item' ) {

				$product_xml = $this->get_product_xml( $reader );
				$product     = $this->populate_product( $product_xml, true, $lang );

				if ( $product && $this->apply_filter( $product, $filter ) ) {
					$products[] = $product;

					if ( ! in_array( $product['category'], $this->tags[ Remote_Category::REWIX_CATEGORY_ID ] ) && strlen( $product['category'] ) > 0 ) {
						$this->tags[ Remote_Category::REWIX_CATEGORY_ID ][] = $product['category'];
					}
					if ( ! in_array( $product['subcategory'], $this->tags[ Remote_Category::REWIX_SUBCATEGORY_ID ] ) && strlen( $product['subcategory'] ) > 0 ) {
						$this->tags[ Remote_Category::REWIX_SUBCATEGORY_ID ][] = $product['subcategory'];
					}
					if ( ! in_array( $product['gender'], $this->tags[ Remote_Category::REWIX_GENDER_ID ] ) && strlen( $product['gender'] ) > 0 ) {
						$this->tags[ Remote_Category::REWIX_GENDER_ID ][] = $product['gender'];
					}
					if ( ! in_array( $product['brand'], $this->tags[ Remote_Category::REWIX_BRAND_ID ] ) && strlen( $product['brand'] ) > 0 ) {
						$this->tags[ Remote_Category::REWIX_BRAND_ID ][] = $product['brand'];
					}
				}

				// Jump next to the next <item/>, the read is important
				$reader->read();
				$reader->next( 'item' );
			}
		}

		$reader->close();

		return $products;
	}

	/**
	 * @return array;
	 */
	public function get_products_categories() {
		if ( is_null($this->products) ) {
			$this->products = $this->get_products();
		}

		return $this->tags[ Remote_Category::REWIX_CATEGORY_ID ];
	}

	public function get_products_subcategories() {
		if ( is_null($this->products) ) {
			$this->products = $this->get_products();
		}

		return $this->tags[ Remote_Category::REWIX_SUBCATEGORY_ID ];
	}

	public function get_products_genders() {
		if ( is_null($this->products) ) {
			$this->products = $this->get_products();
		}

		return $this->tags[ Remote_Category::REWIX_GENDER_ID ];
	}
	
	public function get_products_brands() {
		if ( is_null($this->products) ) {
			$this->products = $this->get_products();
		}

		return $this->tags[ Remote_Category::REWIX_BRAND_ID ];
	}

	/**
	 * @param \XMLReader $reader
	 *
	 * @return \SimpleXMLElement
	 */
	private function get_product_xml( \XMLReader $reader ) {
		$doc = new \DOMDocument( '1.0', 'UTF-8' );
		$xml = simplexml_import_dom( $doc->importNode( $reader->expand(), true ) );

		return $xml;
	}

	/**
	 * @param \SimpleXMLElement $product_xml
	 * @param bool $check_imported
	 * @param string $lang
	 *
	 * @return array|bool
	 */
	private function populate_product( \SimpleXMLElement $product_xml, $check_imported = true, $lang = 'default' ) {
		$sku       = (string) $product_xml->code;
		$remote_id = (int) $product_xml->id;

		$tags = Tools::populate_tags( $product_xml->tags->tag );

		$conversion = isset( $this->settings['conversion'] ) ? $this->settings['conversion'] : 1;
		$api_url    = isset( $this->settings['api-url'] ) ? $this->settings['api-url'] : '';

		$name         = Tools::get_product_name( $tags, $product_xml->name, $lang );
		$price        = Tools::calculate_price( (float) $product_xml->bestTaxable, (float) $product_xml->taxable, (float) $product_xml->streetPrice );
		$best_taxable = ( (float) $product_xml->bestTaxable ) * $conversion;
		$taxable      = ( (float) $product_xml->taxable ) * $conversion;
		$street_price = ( (float) $product_xml->streetPrice ) * $conversion;
		$availability = (int) $product_xml->availability;
		$image_url    = $api_url . $product_xml->pictures->image->url;

		if ( $check_imported ) {
			global $wpdb;
			$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
			$result     = $wpdb->get_var( "SELECT imported FROM $table_name WHERE rewix_product_id = $remote_id" );
			$imported   = is_null( $result ) ? false : (bool) $result;
		} else {
			$imported = false;
		}

		$descriptions = Tools::get_descriptions( $product_xml );
		$description  = (string) $descriptions[ $lang ];

		$product = array(
			'ID'             => $remote_id,
			'imported'       => $imported,
			'remote-id'      => $remote_id,
			'name'           => $name,
			'base-name'      => $product_xml->name,
			'image'          => $image_url,
			'brand'          => $tags[ Remote_Category::REWIX_BRAND_ID ][ $lang ]['translation'],
			'category'       => $tags[ Remote_Category::REWIX_CATEGORY_ID ][ $lang ]['translation'],
			'subcategory'    => $tags[ Remote_Category::REWIX_SUBCATEGORY_ID ][ $lang ]['translation'],
			'gender'         => $tags[ Remote_Category::REWIX_GENDER_ID ][ $lang ]['translation'],
			'color'          => $tags[ Remote_Category::REWIX_COLOR_ID ][ $lang ]['translation'],
			'season'         => $tags[ Remote_Category::REWIX_SEASON_ID ][ $lang ]['translation'],
			'sku'            => $sku,
			'availability'   => $availability,
			'best-taxable'   => $best_taxable,
			'taxable'        => $taxable,
			'street-price'   => $street_price,
			'proposed-price' => $price,
			'description'    => $description,
			'tags'           => $tags,
			'descriptions'   => $descriptions
		);

		if ( ! empty( $product ) ) {
			return $product;
		}

		return false;
	}

	/**
	 * @param array $product_ids
	 */
	public function queue_products( $product_ids ) {
		global $wpdb;

		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;

		foreach ( $product_ids as $product_id ) {
			$query = "INSERT INTO $table_name (rewix_product_id, sync_status, reason) VALUES (%d, %s, '') ON DUPLICATE KEY UPDATE sync_status = %s, priority = priority + 1, reason = ''";
			$query = $wpdb->prepare( $query, $product_id, Sync_Status::QUEUED, Sync_Status::QUEUED );
			$wpdb->query( $query );
		}
	}

	public function queue_all_products() {
		global $wpdb;
		$reader = new \XMLReader();
		$reader->open( Tools::get_xml_source() );

		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$count = 0;
		
		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'item' ) {
				$product_xml = $this->get_product_xml( $reader );
				$product_id  = (int) $product_xml->id;

				$query = "INSERT INTO $table_name (rewix_product_id, sync_status, reason) VALUES (%d, %s, '') ON DUPLICATE KEY UPDATE sync_status = %s, priority = priority + 1, reason = ''";
				$query = $wpdb->prepare( $query, $product_id, Sync_Status::QUEUED, Sync_Status::QUEUED );
				$wpdb->query( $query );
				
				$count++;
				
				// Jump next to the next <item/>, the read is important
				$reader->read();
				$reader->next( 'item' );
			}
		}
		
		return $count;
	}

	public function dequeue_products( $product_ids ) {
		global $wpdb;

		$remote_products_table_name   = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$remote_variations_table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		foreach ( $product_ids as $product_id ) {
			$wpdb->query( "DELETE FROM $remote_products_table_name WHERE rewix_product_id = $product_id" );
			$wpdb->query( "DELETE FROM $remote_variations_table_name WHERE rewix_product_id = $product_id" );
		}
	}

	public function import_queued_products() {
		@ini_set( 'max_execution_time', 300 );
		wc_set_time_limit( 300 );

		global $wpdb;
		$lang = 'default';

		$products          = Remote_Product::get_products_by_status( Sync_Status::QUEUED, 0, 30 );
		$imported_products = array();

		$reader = new \XMLReader();
		$reader->open( Tools::get_xml_source() );

		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;

		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'item' ) {
				$product_xml = $this->get_product_xml( $reader );
				$remote_id   = (int) $product_xml->id;
				foreach ( $products as $product ) {

					if ( ( (int) $product['rewix_product_id'] ) == $remote_id ) {
						$imported_products[] = $remote_id;

						try {
							$product_id = $this->import_product( $product_xml, (int) $product['wc_product_id'], $lang );

							$wpdb->update( $table_name, array(
								'wc_product_id'  => $product_id,
								'sync_status'    => Sync_Status::IMPORTED,
								'imported'       => 1,
								'priority'       => 0,
								'last_sync_date' => current_time( 'mysql' )
							), array(
								'rewix_product_id' => $remote_id,
								'lang'             => $lang
							), array( '%d', '%s', '%d', '%d', '%s' ), array( '%d', '%s' ) );
						} catch ( \Exception $e ) {
							$this->logger->error( 'brandssync', print_r( $e, 1 ) );
							$wpdb->update( $table_name, array(
								'priority'       => (int) $product['priority'] + 1,
								'reason'         => (string) $e,
								'last_sync_date' => current_time( 'mysql' )
							), array(
								'rewix_product_id' => $remote_id,
								'lang'             => $lang
							), array( '%d', '%s', '%s' ), array( '%d', '%s' ) );
						}
					}
				}

				// Jump next to the next <item/>, the read is important
				$reader->read();
				$reader->next( 'item' );
			}
		}

		if ( count( $imported_products ) < 30 ) {
			foreach ( $products as $product ) {
				if ( ! in_array( (int) $product['rewix_product_id'], $imported_products ) ) {
					$wpdb->update( $table_name, array(
						'priority'       => (int) $product['priority'] + 1,
						'reason'         => (string) 'Not available upstream',
						'last_sync_date' => current_time( 'mysql' ),
						'sync_status'    => 'not-available'
					), array(
						'rewix_product_id' => $product['rewix_product_id'],
						'lang'             => $lang
					), array( '%d', '%s', '%s', '%s' ), array( '%d', '%s' ) );
				}
			}
		}
	}

	/**
	 * @param $product_xml
	 * @param int $wc_product_id
	 * @param string $lang
	 *
	 * @return int|WP_Error
	 * @throws \Exception
	 */
	private function import_product( $product_xml, $wc_product_id = 0, $lang = 'default' ) {
		global $sitepress, $wpdb;

		$product = $this->populate_product( $product_xml, false, $lang );
		$post    = array(
			'post_content' => $product['description'],
			'post_status'  => 'publish',
			'post_title'   => $product['name'],
			'post_type'    => 'product',
		);

		if ( $wc_product_id > 0 ) {
			$post_id = $wc_product_id;
		} else {
			//Create post
			$post_id = wp_insert_post( $post );
			if ( ! $post_id ) {
				die;
			}
		}

		if ( ! is_null( $sitepress ) ) {
			$def_trid = $sitepress->get_element_trid( $wc_product_id );
			$sitepress->set_element_language_details( $post_id, 'post_product', $def_trid, $lang );
		}

		$tags      = $product['tags'];
		$remote_id = (int) $product['remote-id'];

		foreach ( $this->category_structure as $category_tree ) {
			$category = 0;
			foreach ( $category_tree as $category_id ) {
				$category = Remote_Category::get_category( $category, $category_id,
					$tags[ $category_id ][ $lang ]['value'],
					$tags[ $category_id ][ $lang ]['translation'],
					$tags[ $category_id ][ $lang ]['url-key']
				);
				if ( $category ) {
					wp_set_object_terms( $post_id, $category, 'product_cat', true );
				} else {
					break;
				}
			}
		}

		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'no' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', "no" );
		update_post_meta( $post_id, '_weight', (float) $product_xml->weight );
		update_post_meta( $post_id, '_sku', (string) $product['sku'] );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'no' );
		update_post_meta( $post_id, '_backorders', 'no' );
		wp_set_object_terms( $post_id, array(
			$product['category'],
			$product['subcategory'],
			$product['color'],
			$product['gender'],
			$product['season'],
			$product['brand']
		), 'product_tag' );

		$product_attributes = array();

		$color_attribute_id = (int) $this->settings['color-attribute'];
		if ( $color_attribute_id > 0 ) {
			$product_attributes = $this->set_product_attribute( $post_id, $product_attributes, $color_attribute_id, $product['color'] );
		}

		$gender_attribute_id = (int) $this->settings['gender-attribute'];
		if ( $gender_attribute_id > 0 ) {
			$product_attributes = $this->set_product_attribute( $post_id, $product_attributes, $gender_attribute_id, $product['gender'] );
		}

		$season_attribute_id = (int) $this->settings['season-attribute'];
		if ( $season_attribute_id > 0 ) {
			$product_attributes = $this->set_product_attribute( $post_id, $product_attributes, $season_attribute_id, $product['season'] );
		}

		$brand_attribute_id = (int) $this->settings['brand-attribute'];
		if ( $brand_attribute_id > 0 ) {
			$product_attributes = $this->set_product_attribute( $post_id, $product_attributes, $brand_attribute_id, $product['brand'] );
		}

		$size_attribute_id                     = (int) $this->settings['size-attribute'];
		$size_attribute                        = $this->get_attribute_taxonomy_name( $size_attribute_id );
		$product_attributes[ $size_attribute ] = array(
			'name'         => $size_attribute,
			'value'        => '',
			'is_visible'   => '0',
			'is_variation' => '1',
			'is_taxonomy'  => '1'
		);
		update_post_meta( $post_id, '_product_attributes', $product_attributes );

		if ( self::is_simple_product( $product_xml ) ) {
			wp_set_object_terms( $post_id, 'simple', 'product_type' );

			$model_xml = $product_xml->models->model;
			update_post_meta( $post_id, '_barcode', (string) $model_xml->barcode );
			update_post_meta( $post_id, '_sku', (string) $model_xml->code );
			update_post_meta( $post_id, '_regular_price', (float) $product['street-price'] );
			update_post_meta( $post_id, '_sale_price', (float) $product['proposed-price'] );
			update_post_meta( $post_id, '_price', (float) $product['proposed-price'] );

			update_post_meta( $post_id, '_manage_stock', 'yes' );
			
			wc_update_product_stock_status($post_id, 'instock');
			self::set_stock( $post_id, (int) $product_xml->availability );

			$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
			$query      = "INSERT INTO $table_name (rewix_product_id, rewix_model_id, wc_model_id, wc_product_id) VALUES (%d, %d, %d, %d) " .
			              "ON DUPLICATE KEY UPDATE wc_product_id = %d, wc_model_id = %d";
			$query      = $wpdb->prepare( $query, $product['remote-id'], (int) $model_xml->id, $post_id, $post_id, $post_id, $post_id );
			$wpdb->query( $query );

		} else {
			wp_set_object_terms( $post_id, 'variable', 'product_type' );

			$attributes = array();
			foreach ( $product_xml->models->model as $model_xml ) {
				$attributes[] = (string) $model_xml->size;
			}
			foreach ( wp_get_object_terms( $post_id, $size_attribute, array('fields' => 'names')) as $existing_size){
				if (!in_array($existing_size, $attributes)){
					$attributes[] = $existing_size;
				}
			}
			wp_set_object_terms( $post_id, $attributes, $size_attribute );

			// create variations from size attribute
			$size       = '';
			$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
			foreach ( $product_xml->models->model as $model_xml ) {
				$model_id     = (int) $model_xml->id;
				$variation_id = (int) $wpdb->get_var( "SELECT wc_model_id FROM $table_name WHERE rewix_product_id = $remote_id AND rewix_model_id = $model_id" );

				if ( ! $this->import_product_model( $product, $post_id, $model_xml, $variation_id, $size_attribute ) ) {
					throw new \Exception( 'Error while importing model ' . $model_id );
				}
				$size = (string) $model_xml->size;
			}
			update_post_meta( $post_id, '_default_attributes', array( $size_attribute => sanitize_title_with_dashes( $size ) ) );
			\WC_Product_Variable::sync( $post_id );
			\WC_Product_Variable::sync_stock_status( $post_id );
			
			wc_delete_product_transients( $post_id );
		}

		if ( (boolean) $this->settings['import-images'] ) {
			$this->import_images( $post_id, $product_xml->pictures->image );
		}

		return $post_id;
	}

	private function import_product_model( $product, $wc_product_id, $model_xml, $model_id = 0, $size_attribute = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$size       = (string) $model_xml->size;
		if ( ! $size_attribute ) {
			$size_attribute_id = (int) $this->settings['size-attribute'];
			$size_attribute    = wc_attribute_taxonomy_name_by_id( $size_attribute_id );
		}

		if ( $model_id == 0 ) {
			$variation = array(
				'post_title'   => $product['name'] . ' ' . (string) $model_xml->size,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_parent'  => $wc_product_id,
				'post_type'    => 'product_variation'
			);
			$model_id  = wp_insert_post( $variation );
			if ( $model_id > 0 ) {
				$wpdb->insert( $table_name, array(
					'rewix_product_id' => $product['remote-id'],
					'rewix_model_id'   => (int) $model_xml->id,
					'wc_model_id'      => $model_id,
					'wc_product_id'    => $wc_product_id
				), array( '%d', '%d', '%d', '%d' ) );
			} else {
				return false;
			}
		}else if ('product_variation' !== get_post_type( $model_id )){ //Check this variation still exists
			$wpdb->delete( $table_name, array(
					'rewix_product_id' => $product['remote-id'],
					'rewix_model_id'   => (int) $model_xml->id), array( '%d', '%d') );
			return false;
		}

		// Regular Price ( you can set other data like sku and sale price here )
		update_post_meta( $model_id, '_barcode', (string) $model_xml->barcode );
		update_post_meta( $model_id, '_manage_stock', 'yes' );
		wc_update_product_stock_status($model_id, 'instock');
		update_post_meta( $model_id, '_sku', (string) $model_xml->code . '-' . $size );
		update_post_meta( $model_id, '_regular_price', (float) $product['street-price'] );
		update_post_meta( $model_id, '_sale_price', (float) $product['proposed-price'] );
		update_post_meta( $model_id, '_price', (float) $product['proposed-price'] );
		update_post_meta( $model_id, 'attribute_' . $size_attribute, sanitize_title_with_dashes( $size ) );
		self::set_stock( $model_id, (int) $model_xml->availability );

		return $model_id;
	}

	private function import_images( $wc_id, $images ) {
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$image_ids = array();

		foreach ( $images as $image ) {
			$image_url = $this->settings['api-url'] . $image->url . '?x=1300&y=1300&pad=1&fill=white';
			preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $image->url, $matches );
			$name = basename( $matches[0] );
			$slug = sanitize_title( $name );

			// Check if image is in DB
			$attachment_in_db = get_page_by_title( $slug, 'OBJECT', 'attachment' );
			// Is attachment already in DB?
			if ( $attachment_in_db ) {
				// push attachment ID
				$image_ids[] = $attachment_in_db->ID;
			} else {
				$get = wp_remote_get( $image_url, array( 'timeout' => 60 ) );
				if ( (int) wp_remote_retrieve_response_code( $get ) != 200 ) {
					// error
					continue;
				}

				$uploaded = wp_upload_bits( $name, null, wp_remote_retrieve_body( $get ) );
				if ( $uploaded['error'] ) {
					// error
					continue;
				}
				$attachment  = array(
					'guid'           => $uploaded['url'],
					'post_title'     => $slug,
					'post_mime_type' => 'image/jpeg'
				);
				$attach_id   = wp_insert_attachment( $attachment, $uploaded['file'], $wc_id );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				$image_ids[] = $attach_id;
			}
		}
		if ( count( $image_ids ) > 0 ) {
			set_post_thumbnail( $wc_id, $image_ids[0] );
			// Associate images as gallery
			update_post_meta( $wc_id, '_product_image_gallery', implode( ',', $image_ids ) );
		}
	}

	public function update_products_quantities() {
		global $wpdb;

		$table_name   = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$xml_products = array();

		$reader = new \XMLReader();
		$reader->open( Tools::get_xml_source() );
		while ( $reader->read() ) {
			if ( $reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'item' ) {
				$product_xml                = $this->get_product_xml( $reader );
				$remote_id                  = (int) $product_xml->id;
				$xml_products[ $remote_id ] = $product_xml;

				// Jump next to the next <item/>, the read is important
				$reader->read();
				$reader->next( 'item' );
			}
		}

		$status         = Sync_Status::QUEUED;
		$local_products = $wpdb->get_results( "SELECT wc_product_id, rewix_product_id FROM $table_name WHERE imported = 1 AND sync_status != '$status'", ARRAY_A );
		foreach ( $local_products as $local_product ) {
			if ( isset( $xml_products[ (int) $local_product['rewix_product_id'] ] ) ) {
				$this->update_product_quantity( $xml_products[ $local_product['rewix_product_id'] ], $local_product['rewix_product_id'], $local_product['wc_product_id'] );
			} else {
				$this->update_product_quantity( 0, $local_product['rewix_product_id'], $local_product['wc_product_id'] );
			}
		}
	}

	private function update_product_quantity( $product_xml, $remote_id, $wc_product_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$product    = null;

		if ( is_int( $product_xml ) ) {
			$variation_ids = $wpdb->get_col( "SELECT wc_model_id FROM $table_name WHERE rewix_product_id = $remote_id" );
			if ( count( $variation_ids ) > 0 ) {
				foreach ( $variation_ids as $variation_id ) {
					self::set_stock( (int) $variation_id, (int) 0 );
				}
				\WC_Product_Variable::sync_stock_status( (int) $wc_product_id );
			} else {
				self::set_stock( (int) $wc_product_id, (int) 0 );
			}
		} elseif ( self::is_simple_product( $product_xml ) ) {
			self::set_stock( (int) $wc_product_id, (int) $product_xml->availability );
		} else {
			$variation_ids = $wpdb->get_results( "SELECT rewix_model_id, wc_model_id FROM $table_name WHERE rewix_product_id = $remote_id" );
			foreach ( $variation_ids as $variation ) {
				$found = false;
				foreach ( $product_xml->models->model as $model_xml ) {
					if ($variation->rewix_model_id == (int) $model_xml->id ){
						self::set_stock( $variation->wc_model_id, (int) $model_xml->availability );
						$found = true;
						break;
					}
					/*
							if ( $product == null ) {
								$product = $this->populate_product( $product_xml );
							}
							if ( ! $this->import_product_model( $product, $wc_product_id, $model_xml, $variation_id ) ) {
								throw new \Exception( 'Error while importing model ' . $model_id );
							}
					*/
				}
				if (!$found){
					self::set_stock( $variation->wc_model_id, (int) 0 );
				}
			}
			\WC_Product_Variable::sync_stock_status( (int) $wc_product_id );
		}
	}

	static private function is_simple_product( $product_xml ) {
		if ( count( $product_xml->models->model ) == 1 ) {
			$model_xml = $product_xml->models->model[0];
			$size      = (string) $model_xml->size;
			if ( $size == 'NOSIZE' ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param $post_id
	 * @param float $quantity
	 */
	static private function set_stock( $variation_id, $quantity ) {
		$product = wc_get_product( $variation_id );
		if ($product == false){ //The variation has been deleted in woocommerce
			global $wpdb;
			$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
			$wpdb->delete( $table_name, array('wc_model_id' => $variation_id), array( '%d') );
		}else{
			wc_update_product_stock( $variation_id, (int) $quantity );
			if ( (float) $quantity > 0 ) {
				if (!$product->is_in_stock()){
					wc_update_product_stock_status($variation_id, 'instock');
				}
			} else {
				if ($product->is_in_stock()){
					wc_update_product_stock_status($variation_id, 'outofstock');
				}
			}
		}
	}

	private function set_product_attribute( $post_id, $product_attributes, $attribute_id, $value ) {
		$attribute                        = $this->get_attribute_taxonomy_name( $attribute_id );
		$product_attributes[ $attribute ] = array(
			'name'         => $attribute,
			'value'        => '',
			'is_visible'   => '1',
			'is_variation' => '0',
			'is_taxonomy'  => '1'
		);
		wp_set_object_terms( $post_id, array( $value ), $attribute );

		return $product_attributes;
	}

	public function on_product_delete( $post_id ) {
		// We check if the global post type isn't ours and just return
		global $post_type, $wpdb;
		if ( $post_type != 'product' ) {
			return;
		}
		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$rewix_id   = (int) $wpdb->get_var( "SELECT rewix_product_id FROM $table_name WHERE wc_product_id = $post_id" );
		$wpdb->delete( $table_name, array( 'rewix_product_id' => $rewix_id ), array( '%d' ) );
		$table_name = $wpdb->prefix . Remote_Variation::TABLE_NAME;
		$wpdb->delete( $table_name, array( 'rewix_product_id' => $rewix_id ), array( '%d' ) );
	}

	private function get_attribute_taxonomy_name( $attribute_id ) {
		if ( isset( $this->attribute_taxonomies[ $attribute_id ] ) ) {
			return $this->attribute_taxonomies[ $attribute_id ];
		}
		$attribute                                   = wc_attribute_taxonomy_name_by_id( $attribute_id );
		$this->attribute_taxonomies[ $attribute_id ] = $attribute;

		return $attribute;
	}

	/**
	 * @param $product
	 * @param $filter
	 *
	 * @return boolean
	 */
	private function apply_filter( $product, $filter ) {
		$result = true;
		if ( isset( $filter['category'] ) ) {
			$result &= $filter['category'] == $product['category'];
			if ( isset( $filter['subcategory'] ) ) {
				$result &= $filter['subcategory'] == $product['subcategory'];
			}
		} elseif ( isset( $filter['subcategory'] ) ) {
			$result &= $filter['subcategory'] == $product['subcategory'];
		}

		if ( isset( $filter['gender'] ) ) {
			$result &= $filter['gender'] == $product['gender'];
		}
		
		if ( isset( $filter['brand'] ) ) {
			$result &= $filter['brand'] == $product['brand'];
		}

		return $result;
	}
}
