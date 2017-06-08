<?php
/**
 * Storefront Powerpack Integrations Class
 *
 * @author   WooThemes
 * @package  Storefront_Powerpack
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SP_Integrations' ) ) :

	/**
	 * The integrations class
	 */
	class SP_Integrations {

		/**
		 * Setup class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			/**
			 * Composite Products integration
			 */
			if ( class_exists( 'WooCommerce' ) && class_exists( 'WC_Composite_Products' ) ) {
				global $woocommerce_composite_products;

				if ( isset( $woocommerce_composite_products->version ) && version_compare( $woocommerce_composite_products->version, '3.0', '>=' ) ) {

					// Filter component options loop columns.
					add_filter( 'woocommerce_composite_component_loop_columns', 		array( $this, 'cp_component_options_loop_columns' ), 5 );

					// Filter max component options per page.
					add_filter( 'woocommerce_component_options_per_page', 				array( $this, 'cp_component_options_per_page' ), 5 );

					// Filter max component columns in review/summary.
					add_filter( 'woocommerce_composite_component_summary_max_columns', 	array( $this, 'cp_summary_max_columns' ), 5 );

					// Filter toggle-box view.
					add_filter( 'woocommerce_composite_component_toggled', 				array( $this, 'cp_component_toggled' ), 5, 3 );

					// Register additional customizer section to configure these parameters.
					add_action( 'customize_register', 									array( $this, 'cp_customize_register' ) );
					add_filter( 'sp_setting_defaults',                                 array( $this, 'cp_setting_defaults' ), 99 );
				}
			}
		}

		/**
		 * Filters the Storefront Powerpack default settings to include those
		 * required for the Composite Products integration.
		 *
		 * @param array $args the default args filtered to account for the Composite Products integration.
		 * @return array
		 */
		public function cp_setting_defaults( $args ) {
			$args['sp_cp_component_options_loop_columns'] = '3';
			$args['sp_cp_component_options_per_page']     = '6';
			$args['sp_cp_summary_max_columns']            = '6';
			$args['sp_cp_component_toggled']              = 'progressive';

			return $args;
		}

		/**
		 * Number of component option columns when the Product Thumbnails setting is active
		 *
		 * @param  integer $cols product columns.
		 * @return integer
		 */
		public function cp_component_options_loop_columns( $cols ) {
			$cols = get_theme_mod( 'sp_cp_component_options_loop_columns' );
			return $cols;
		}

		/**
		 * Number of component options per page when the Product Thumbnails setting is active
		 *
		 * @param  integer $num_per_page products per page.
		 * @return integer
		 */
		public function cp_component_options_per_page( $num_per_page ) {
			$num_per_page = get_theme_mod( 'sp_cp_component_options_per_page' );
			return $num_per_page;
		}

		/**
		 * Max number of Review/Summary columns when a Multi-page layout is active
		 *
		 * @param  integer $max_cols maximum columns.
		 * @return integer
		 */
		public function cp_summary_max_columns( $max_cols ) {
			$max_cols = get_theme_mod( 'sp_cp_summary_max_columns' );
			return $max_cols;
		}

		/**
		 * Enable/disable the toggle-box component view when a Single-page layout is active
		 *
		 * @param boolean $show_toggle toggle boolean.
		 * @param string  $component_id the component id.
		 * @param array   $product the product.
		 * @return boolean
		 */
		public function cp_component_toggled( $show_toggle, $component_id, $product ) {
			$show_toggle = get_theme_mod( 'sp_cp_component_toggled' );

			$style = $product->get_composite_layout_style();

			if ( $style === $show_toggle || 'both' === $show_toggle ) {
				return true;
			}

			return false;
		}

		/**
		 * Customizer Composite Products settings
		 *
		 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
		 */
		public function cp_customize_register( $wp_customize ) {

			/**
		     * Composite Products section
		     */
	        $wp_customize->add_section( 'sp_cp_section' , array(
				'title'       => __( 'Composite Products', 'storefront-powerpack' ),
				'description' => __( 'Customise the look & feel of Composite product pages', 'storefront-powerpack' ),
				'priority'    => 59,
			) );

	        /**
	         * Component Options (Product) Columns
	         */
		    $wp_customize->add_setting( 'sp_cp_component_options_loop_columns', array(
		        'default'           => '3',
		    ) );

		    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sp_cp_component_options_loop_columns', array(
				'label'       => __( 'Component options columns', 'storefront-powerpack' ),
				'description' => sprintf( __( 'In effect when the %sProduct Thumbnails%s options style is active', 'storefront-powerpack' ), '<strong>', '</strong>' ),
				'section'     => 'sp_cp_section',
				'settings'    => 'sp_cp_component_options_loop_columns',
				'type'        => 'select',
				'priority'    => 1,
				'choices'     => array(
					'1'           => '1',
					'2'           => '2',
					'3'           => '3',
					'4'           => '4',
					'5'           => '5',
				),
	        ) ) );

	        /**
	         * Component Options per Page
	         */
		    $wp_customize->add_setting( 'sp_cp_component_options_per_page', array(
		        'default'           => '6',
		    ) );

		    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sp_cp_component_options_per_page', array(
				'label'       => __( 'Component options per page', 'storefront-powerpack' ),
				'description' => sprintf( __( 'In effect when the %sProduct Thumbnails%s options style is active', 'storefront-powerpack' ), '<strong>', '</strong>' ),
				'section'     => 'sp_cp_section',
				'settings'    => 'sp_cp_component_options_per_page',
				'type'        => 'select',
				'priority'    => 2,
				'choices'     => array(
					'1'           => '1',
					'2'           => '2',
					'3'           => '3',
					'4'           => '4',
					'5'           => '5',
					'6'           => '6',
					'7'           => '7',
					'8'           => '8',
					'9'           => '9',
					'10'          => '10',
					'11'          => '11',
					'12'          => '12',
					'13'          => '13',
					'14'          => '14',
					'15'          => '15',
					'16'          => '16',
					'17'          => '17',
					'18'          => '18',
					'19'          => '19',
					'20'          => '20',
					'21'          => '21',
					'22'          => '22',
					'23'          => '23',
					'24'          => '24',
				),
	        ) ) );

	        /**
	         * Max columns in Summary/Review section
	         */
		    $wp_customize->add_setting( 'sp_cp_summary_max_columns', array(
		        'default'           => '6',
		    ) );

		    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sp_cp_summary_max_columns', array(
				'label'       => __( 'Max columns in Summary', 'storefront-powerpack' ),
				'description' => sprintf( __( 'In effect when using the %1$sStepped%2$s or %1$sComponentized%2$s layout', 'storefront-powerpack' ), '<strong>', '</strong>' ),
				'section'     => 'sp_cp_section',
				'settings'    => 'sp_cp_summary_max_columns',
				'type'        => 'select',
				'priority'    => 3,
				'choices'     => array(
					'1'           => '1',
					'2'           => '2',
					'3'           => '3',
					'4'           => '4',
					'5'           => '5',
					'6'           => '6',
					'7'           => '7',
					'8'           => '8',
				),
	        ) ) );

	        /**
	         * Toggle Box
	         */
		    $wp_customize->add_setting( 'sp_cp_component_toggled', array(
		        'default'           => 'progressive',
		    ) );

		    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'sp_cp_component_toggled', array(
				'label'       => __( 'Toggle-box view', 'storefront-powerpack' ),
				'description' => __( 'Apply the toggle-box Component view to the following layout(s)', 'storefront-powerpack' ),
				'section'     => 'sp_cp_section',
				'settings'    => 'sp_cp_component_toggled',
				'type'        => 'select',
				'priority'    => 5,
				'choices'     => array(
					'single'      => 'Stacked',
					'progressive' => 'Progressive',
					'both'        => 'Both',
					'none'        => 'None',
				),
	        ) ) );
		}
	}

endif;

return new SP_Integrations();
