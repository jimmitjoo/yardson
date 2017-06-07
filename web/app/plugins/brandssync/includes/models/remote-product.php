<?php

namespace BrandsSync\Models;

class Remote_Product {
	const TABLE_NAME = 'brandssync_remote_products';

	public static function get_products_by_status( $status, $offset = 0, $limit = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$query      = "SELECT * FROM $table_name WHERE sync_status = '$status'";
		$query .= ' ORDER BY priority asc, rewix_product_id asc';
		if ( $limit > 0 ) {
			$query .= ' LIMIT ' . $limit;
		}
		if ( $offset > 0 ) {
			$query .= ' OFFSET ' . $offset;
		}
		$products = $wpdb->get_results( $query, ARRAY_A );

		return $products;
	}

	public static function get_products( $offset = 0, $limit = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$query      = 'SELECT * FROM ' . $table_name;
		$query .= ' ORDER BY priority asc, rewix_product_id asc';
		if ( $limit > 0 ) {
			$query .= ' LIMIT ' . $limit;
		}
		if ( $offset > 0 ) {
			$query .= ' OFFSET ' . $offset;
		}
		$products = $wpdb->get_results( $query, ARRAY_A );

		return $products;
	}

	public static function get_products_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$query      = "SELECT COUNT(*) FROM $table_name";

		return (int) $wpdb->get_var( $query );
	}

	public static function get_products_count_by_status( $status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . Remote_Product::TABLE_NAME;
		$query      = "SELECT COUNT(*) FROM $table_name WHERE sync_status = '$status'";

		return (int) $wpdb->get_var( $query );
	}
}
