<?php

namespace BrandsSync\Models;

class Remote_Category {
	const REWIX_BRAND_ID = 1;
	const REWIX_CATEGORY_ID = 4;
	const REWIX_SUBCATEGORY_ID = 5;
	const REWIX_COLOR_ID = 13;
	const REWIX_GENDER_ID = 26;
	const REWIX_SEASON_ID = 11;

	/**
	 * @param int $parent
	 * @param $tag_id
	 * @param $value
	 * @param $translation
	 *
	 * @return int category id
	 */
	public static function get_category( $parent, $tag_id, $value, $translation, $url_key ) {
		$slug = $tag_id . '-' . $url_key;

		global $brandssync;
		if ( $brandssync->has_wpml_support() ) {
			include_once WP_CONTENT_DIR . '/plugins/sitepress-multilingual-cms/sitepress.php';
			global $sitepress;
		}

		if ( is_null( $translation ) || strlen( $translation ) == 0 ) {
			return 0;
		}

		if ( $parent > 0 ) {
			$parent_slug = get_term_meta( $parent, 'brandssync_slug', true );
			if ( $parent_slug ) {
				$slug = $parent_slug . '-' . $slug;
			}

			$args = array(
					'hierarchical'     => 1,
					'show_option_none' => '',
					'hide_empty'       => 0,
					'meta_query' => array(
							array(
									'key'       => 'brandssync_slug',
									'value'     => $slug,
									'compare'   => '='
							)
					)
			);
			
			$subcats = get_terms( 'product_cat', $args );

			if (count($subcats) == 1) {
				return $subcats[0]->term_id;
			}

			$term = wp_insert_term(
				$translation, // the term
				'product_cat', // the taxonomy
				array(
					'description' => $translation,
					'slug'        => $url_key,
					'parent'      => $parent
				)
			);
			
			if (is_wp_error( $term )){
				$brandssync->get_logger()->error( 'brandssync', 'An error occurred creating category ' . $translation . ' ' . $term->get_error_message()  );
				return 0;
			}else{
				add_term_meta( $term['term_id'], 'brandssync_slug', $slug );
				return (int) $term['term_id'];
			}
		} else {
			$args = array(
					'hierarchical'     => 1,
					'show_option_none' => '',
					'hide_empty'       => 0,
					'meta_query' => array(
							array(
									'key'       => 'brandssync_slug',
									'value'     => $slug,
									'compare'   => '='
							)
					)
			);
			
			$categories = get_terms( 'product_cat', $args );

			if (count($categories) == 1) {
				return $categories[0]->term_id;
			}
			
			$term = wp_insert_term(
				$translation, // the term
				'product_cat', // the taxonomy
				array(
					'description' => $translation,
					'slug'        => $url_key
				)
			);
			
			if (is_wp_error( $term )){
				$brandssync->get_logger()->error( 'brandssync', 'An error occurred creating category ' . $translation . ' ' . $term->get_error_message() );
				return 0;
			}else{
				add_term_meta( $term['term_id'], 'brandssync_slug', $slug );
				return (int) $term['term_id'];
			}
		}
	}

}
