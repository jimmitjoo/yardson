<?php

namespace BrandsSync\Admin;

use BrandsSync;
use BrandsSync\Plugin;

class Settings {

	public function __construct( Plugin $plugin ) {
		$this->loader = $plugin->get_loader();

		$this->loader->add_action( 'admin_menu', $this, 'setup_menu' );
		$this->loader->add_action( 'admin_init', $this, 'register_settings' );

		if ( $plugin->has_wpml_support() ) {
			include_once WP_CONTENT_DIR . '/plugins/sitepress-multilingual-cms/sitepress.php';
			$this->languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
		} else {
			$this->languages = array( array( 'code' => 'default', 'english_name' => 'Default' ) );
		}
	}

	public function setup_menu() {
		add_submenu_page( 'brandssync-import', __( 'Settings', 'brandssync' ), __( 'Settings', 'brandssync' ),
			'manage_options', 'brandssync-settings', array( $this, 'display' ) );
	}

	public function display() {
		require 'partials/settings.php';
	}

	public function register_settings() {
		// Add the section to reading settings so we can add our fields to it
		add_settings_section(
			'brandssync-settings-api-section',
			'API Configuration',
			array( $this, 'api_section_callback' ),
			'brandssync-settings'
		);

		// Add the field with the names and function to use for our new
		// settings, put it in our new section
		add_settings_field(
			'api-url',
			'API URL',
			array( $this, 'api_url_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		add_settings_field(
			'api-key',
			'API Key',
			array( $this, 'api_key_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		add_settings_field(
			'api-password',
			'API Password',
			array( $this, 'api_password_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		add_settings_field(
			'import-images',
			'Import Images',
			array( $this, 'import_images_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		$attributes = array( 'size', 'gender', 'color', 'season', 'brand' );
		foreach ( $attributes as $attribute ) {
			add_settings_field(
				$attribute . '-attribute',
				ucfirst( $attribute ),
				array( $this, 'attribute_callback' ),
				'brandssync-settings',
				'brandssync-settings-api-section',
				array( $attribute )
			);
		}

		add_settings_field(
			'category-structure',
			'Category Structure',
			array( $this, 'category_structure_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		add_settings_field(
			'locale',
			'Locale',
			array( $this, 'locale_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section',
			$this->languages
		);

		add_settings_field(
			'verbose-log',
			'Verbose Log',
			array( $this, 'verbose_log_callback' ),
			'brandssync-settings',
			'brandssync-settings-api-section'
		);

		add_settings_section(
			'brandssync-settings-price-section',
			'Price Configuration',
			array( $this, 'price_section_callback' ),
			'brandssync-settings'
		);

		add_settings_field(
			'price-base',
			'Price Base',
			array( $this, 'price_base_callback' ),
			'brandssync-settings',
			'brandssync-settings-price-section'
		);

		add_settings_field(
			'markup',
			'Markup (%)',
			array( $this, 'markup_callback' ),
			'brandssync-settings',
			'brandssync-settings-price-section'
		);

		add_settings_field(
			'conversion',
			'Currency coefficient',
			array( $this, 'conversion_callback' ),
			'brandssync-settings',
			'brandssync-settings-price-section'
		);

		add_settings_field(
			'round-price',
			'Round Price',
			array( $this, 'round_price_callback' ),
			'brandssync-settings',
			'brandssync-settings-price-section'
		);

		// Register all the settings in one option named 'brandssync'
		register_setting( 'brandssync-settings', 'brandssync', array( $this, 'validate_settings' ) );
	}

	public function validate_settings( $input ) {
		foreach ( $input as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$input[ $key ] = wp_filter_nohtml_kses( $value );
			}
		}

		return $input;
	}

	public function api_section_callback() {
		echo '<p>Set here the parameters for the connection</p>';
	}

	public function price_section_callback() {
		echo '<p>Set here the parameters for price configuration</p>';
	}

	public function api_url_callback() {
		echo self::get_field( 'api-url' );
	}

	public function api_key_callback() {
		echo self::get_field( 'api-key' );
	}

	public function api_password_callback() {
		echo self::get_field( 'api-password', '', 'password' );
	}

	public function import_images_callback() {
		echo self::get_checkbox( 'import-images', 'Import all images for products' );
	}

	public function attribute_callback( $attribute = array() ) {
		echo self::get_dropdown( $attribute[0] . '-attribute', '', $this->get_dropdown_attribute() );
	}

	public function category_structure_callback() {
		echo self::get_dropdown( 'category-structure', '', $this->get_category_structure_options() );
	}

	public function locale_callback( $languages ) {
		echo self::get_multidropdown( 'locale', '', $languages, $this->get_languages() );
	}

	public function verbose_log_callback() {
		echo self::get_checkbox( 'verbose-log', 'Show debug messages in logs' );
	}

	public function price_base_callback() {
		echo self::get_dropdown( 'price-base', '', $this->get_price_bases() );
	}

	public function markup_callback() {
		echo self::get_field( 'markup', '', 'text', 50 );
	}

	public function conversion_callback() {
		echo self::get_field( 'conversion', 'For example, the conversion EUR->GBP', 'text', 1 );
	}

	public function round_price_callback() {
		echo self::get_checkbox( 'round-price', 'Round prices: eg. 29.99€' );
	}

	static private function get_field( $field, $description = '', $type = 'text', $default = '' ) {
		$settings = (array) get_option( 'brandssync' );
		$value    = isset( $settings[ $field ] ) ? esc_attr( $settings[ $field ] ) : $default;

		$html = "<input name='brandssync[$field]' id='$field' type='$type' class='regular-text code' value='$value' />";
		if ( strlen( $description ) > 0 ) {
			$html .= "<p class='description'>$description</p>";
		}

		return $html;
	}

	static private function get_checkbox( $field, $description ) {
		$settings = (array) get_option( 'brandssync' );
		$value    = isset( $settings[ $field ] ) ? (bool) $settings[ $field ] : false;
		$checked  = $value ? 'checked' : '';

		$html = "<fieldset><label for='$field'>";
		$html .= "<input name='brandssync[$field]' id='$field' type='checkbox' value='true' $checked/>$description";
		$html .= '</label></<fieldset>';

		return $html;
	}

	static private function get_dropdown( $field, $description, $values ) {
		$settings = (array) get_option( 'brandssync' );
		$value    = (string) $settings[ $field ];

		$html = "<select name='brandssync[$field]' id='$field'>";
		foreach ( $values as $v ) {
			$select = $value == $v['id'] ? 'selected' : '';
			$html .= "<option value='{$v['id']}' $select>{$v['name']}</option>";
		}
		$html .= '</select>';
		$html .= $description;

		return $html;
	}


	static private function get_multidropdown( $field, $description, $keys, $values ) {
		$html = '';
		foreach ( $keys as $key ) {
			$lang_code = $key['code'];
			$settings  = (array) get_option( 'brandssync' );
			$value     = isset( $settings[ $field ] ) ? (string) $settings[ $field ][ $lang_code ] : $values[0]['id']; // default

			$html .= "<p><label class='locale' for='$field-$lang_code'>{$key['english_name']}: </label>";
			$html .= "<select name='brandssync[$field][$lang_code]' id='$field-$lang_code'>";
			foreach ( $values as $v ) {
				$select = $value == $v['id'] ? 'selected' : '';
				$html .= "<option value='{$v['id']}' $select>{$v['name']}</option>";
			}
			$html .= '</select></p>';
			$html .= $description;
		}

		return $html;
	}

	private function get_languages() {
		return array(
			array(
				'id'   => 'en_US',
				'name' => __( 'English', 'brandssync' ),
			),
			array(
				'id'   => 'it_IT',
				'name' => __( 'Italian', 'brandssync' ),
			),
			array(
				'id'   => 'fr_FR',
				'name' => __( 'French', 'brandssync' ),
			),
			array(
				'id'   => 'es_ES',
				'name' => __( 'Spanish', 'brandssync' ),
			),
			array(
				'id'   => 'de_DE',
				'name' => __( 'German', 'brandssync' ),
			),
		);
	}

	private function get_price_bases() {
		return array(
			array(
				'id'   => 'taxable',
				'name' => __( 'Taxable', 'brandssync' ),
			),
			array(
				'id'   => 'best-taxable',
				'name' => __( 'Best Taxable', 'brandssync' ),
			),
			array(
				'id'   => 'street-price',
				'name' => __( 'Street Price', 'brandssync' ),
			),
		);
	}

	private function get_dropdown_attribute() {
		$taxonomies = wc_get_attribute_taxonomies();
		$attributes = array(
			array(
				'id'   => 0,
				'name' => ''
			)
		);
		foreach ( $taxonomies as $taxonomy ) {
			$attributes[] = array(
				'id'   => (int) $taxonomy->attribute_id,
				'name' => $taxonomy->attribute_label
			);
		}

		return $attributes;
	}

	private function get_category_structure_options() {
		return array(
			array(
				'id'   => 0,
				'name' => __( 'Category > Subcategory', 'brandssync' ),
			),
			array(
				'id'   => 1,
				'name' => __( 'Gender > Category > Subcategory + Category > Subcategory', 'brandssync' ),
			),
			array(
				'id'   => 2,
				'name' => __( 'Gender > Category > Subcategory', 'brandssync' ),
			),
		);
	}
}
