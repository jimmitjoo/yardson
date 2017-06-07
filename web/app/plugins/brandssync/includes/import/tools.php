<?php

namespace BrandsSync\Import;

use BrandsSync\Models\Remote_Category;

class Tools {

	public static function get_xml_path() {
		global $brandssync;
		
		$dir      = $brandssync->get_data_directory();
		$settings = (array) get_option( 'brandssync' );
		$locale   = isset( $settings['locale'] ) ? $settings['locale'] : 'en_US';
		if ( is_array( $locale ) ) {
			$locale = implode( '-', $locale );
		}
		$path = $dir . DIRECTORY_SEPARATOR . 'brandssync-products-import-' . $locale . '.xml';

		return $path;
	}

	public static function get_xml_source( $time = 30 ) {
		global $brandssync;
		$path = self::get_xml_path();

		$filelength = @filesize( $path ); // returns false if file doesn't exist
		$filemtime = @filemtime( $path ); // returns false if file doesn't exist
		$life      = $time * 60; // life is in seconds

		if ( ! $filemtime || !$filelength || ( time() - $filemtime ) >= $life ) {
			$brandssync->get_logger()->info( 'brandssync', 'XML source file does not exists or it is too old.' );
			$path = self::download_xml_source( $path );
		}

		return $path;
	}

	private static function download_xml_source( $path ) {
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '1024M' );
		global $brandssync;

		$settings = (array) get_option( 'brandssync' );
		$locale   = isset( $settings['locale'] ) ? $settings['locale'] : 'en_US';
		if ( is_array( $locale ) ) {
			$locale = implode( ',', $locale );
		}
		$api_key      = isset( $settings['api-key'] ) ? $settings['api-key'] : '';
		$api_password = isset( $settings['api-password'] ) ? $settings['api-password'] : '';
		$api_url      = isset( $settings['api-url'] ) ? $settings['api-url'] : '';
		$url          = "{$api_url}/restful/export/api/products.xml?acceptedlocales={$locale}&addtags=true";
		$fp           = fopen( $path, 'w+' );

		$brandssync->get_logger()->debug( 'brandssync', 'Downloading XML Data: ' . $url );
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5000 );
		curl_setopt( $ch, CURLOPT_USERPWD, $api_key . ':' . $api_password );
		curl_exec( $ch );

		$http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_error = curl_error( $ch );
		curl_close( $ch );
		fclose( $fp );

		if ( $http_code == 401 ) {
			unlink($path);
			$brandssync->get_logger()->error( 'brandssync', 'Error loading XML Data: You are NOT authorized to access this service.' );

			return false;
		} elseif ( $http_code == 0 ) {
			unlink($path);
			$brandssync->get_logger()->error( 'brandssync',
				'Error loading XML Data: There has been an error executing the request. Code: ' . $http_code . ' Error: ' . $curl_error
			);

			return false;
		} elseif ( $http_code != 200 ) {
			unlink($path);
			$brandssync->get_logger()->error( 'brandssync',
				'Error loading XML Data: There has been an error executing the request. Code: ' . $http_code . ' Error: ' . $curl_error
			);

			return false;
		} else {
			$brandssync->get_logger()->debug( 'brandssync', 'XML source file has been downloaded successfully (' . $http_code . ')' );
		}

		return $path;
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public static function strip_tag_values( $value ) {
		return html_entity_decode( str_replace( '\n', '', trim( $value ) ) );
	}

	public static function calculate_price( $best_taxable, $taxable, $street_price ) {
		$settings = (array) get_option( 'brandssync' );

		$conversion = isset( $settings['conversion'] ) ? $settings['conversion'] : 1;
		$markup     = ( 1 + ( isset( $settings['markup'] ) ? $settings['markup'] : 0 ) / 100 ) * $conversion;
		$price_base = isset( $settings['price-base'] ) ? $settings['price-base'] : 'best-taxable';

		switch ( $price_base ) {
			case 'best-taxable':
				$price = $best_taxable * $markup;
				break;
			case 'taxable':
				$price = $taxable * $markup;
				break;
			case 'street-price':
				$price = $street_price * $markup;
				break;
			default:
				$price = $best_taxable;
		}

		if ( isset( $settings['round-price'] ) && (bool) $settings['round-price'] ) {
			$price = ceil( $price ) - 0.01;
		}

		return $price;
	}

	/**
	 * @param $tags_xml
	 *
	 * @return array
	 */
	public static function populate_tags( $tags_xml ) {
		$tag_ids = array(
			Remote_Category::REWIX_BRAND_ID,
			Remote_Category::REWIX_CATEGORY_ID,
			Remote_Category::REWIX_SUBCATEGORY_ID,
			Remote_Category::REWIX_GENDER_ID,
			Remote_Category::REWIX_COLOR_ID,
			Remote_Category::REWIX_SEASON_ID
		);

		$tags = array();
		//check for tag-ID:
		$settings = (array) get_option( 'brandssync' );
		$locales  = isset( $settings['locale'] ) ? $settings['locale'] : array( 'default' => 'en_US' );
		foreach ( $locales as $key => $locale ) {
			foreach ( $tags_xml as $tag ) {
				if ( in_array( (int) $tag->id, $tag_ids ) ) {
					$tags[ (int) $tag->id ][ $key ] = self::get_tag_values( $tag->value, $locale );
				}
			}
		}

		return $tags;
	}

	private static function get_tag_values( $tag, $locale ) {
		$value       = Tools::strip_tag_values( (string) $tag->value );
		$description = $url_key = $value;
		foreach ( $tag->translations->translation as $translation ) {
			if ( $locale == $translation->localecode ) {
				$description = Tools::strip_tag_values( (string) $translation->description );
				$url_key     = (string) $translation->urlKey;
			}
		}

		return array(
			'value'       => $value,
			'translation' => $description,
			'url-key'     => $url_key
		);
	}

	public static function get_product_name( $tags, $base_name, $lang ) {
		return $tags[ Remote_Category::REWIX_BRAND_ID ][ $lang ]['translation'] . ' - ' . (string) $base_name;
	}

	public static function get_descriptions( $description_xml ) {
		$descriptions = array();
		$settings     = (array) get_option( 'brandssync' );
		$locales      = isset( $settings['locale'] ) ? $settings['locale'] : array( 'default' => 'en_US' );
		foreach ( $locales as $key => $locale ) {
			foreach ( $description_xml->descriptions->description as $description ) {
				if ( $locale == (string) $description->localecode ) {
					$descriptions[ $key ] = (string) $description->description;
				}
			}
		}

		return $descriptions;
	}

	public static function get_category_structure() {
		$settings           = (array) get_option( 'brandssync' );
		$category_structure = isset( $settings['category-structure'] ) ? (int) $settings['category-structure'] : 0;

		switch ( $category_structure ) {
			case 0:
				return array(
					array(
						Remote_Category::REWIX_CATEGORY_ID,
						Remote_Category::REWIX_SUBCATEGORY_ID,
					),
				);
			case 2:
				return array(
					array(
						Remote_Category::REWIX_GENDER_ID,
						Remote_Category::REWIX_CATEGORY_ID,
						Remote_Category::REWIX_SUBCATEGORY_ID,
					),
				);
			case 1:
			default:
				return array(
					array(
						Remote_Category::REWIX_GENDER_ID,
						Remote_Category::REWIX_CATEGORY_ID,
						Remote_Category::REWIX_SUBCATEGORY_ID,
					),
					array(
						Remote_Category::REWIX_CATEGORY_ID,
						Remote_Category::REWIX_SUBCATEGORY_ID,
					),
				);
		}
	}
}
