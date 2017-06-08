<?php
/**
 * Storefront Powerpack Frontend Checkout Class
 *
 * @author   WooThemes
 * @package  Storefront_Powerpack
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SP_Frontend_Checkout' ) ) :

	/**
	 * The Frontend class.
	 */
	class SP_Frontend_Checkout extends SP_Frontend {

		/**
		 * Setup class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'wp',                 array( $this, 'distraction_free_checkout' ) );
			add_action( 'wp',                 array( $this, 'two_step_checkout' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'script' ), 99 );
			add_filter( 'body_class',         array( $this, 'body_class' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_customizer_css' ), 999 );
		}

		/**
		 * Create the distraction free checkout.
		 *
		 * @since 1.0.0
		 * @return  void
		 */
		public function distraction_free_checkout() {
			$distraction_free = get_theme_mod( 'sp_distraction_free_checkout', false );

			if ( is_checkout() && true === $distraction_free ) {
				// Remove the distractions.
				remove_action( 'storefront_header',         'storefront_secondary_navigation', 30 );
				remove_action( 'storefront_header',         'storefront_primary_navigation',   50 );
				remove_action( 'storefront_footer',         'storefront_footer_widgets',       10 );
				remove_action( 'storefront_footer',         'storefront_credit',               20 );
				remove_action( 'storefront_sidebar',        'storefront_get_sidebar',          10 );
				remove_action( 'storefront_content_top',    'woocommerce_breadcrumb',          10 );
				remove_action( 'storefront_header',         'storefront_product_search',       40 );
				remove_action( 'storefront_header',         'storefront_header_cart',          60 );
				remove_action( 'storefront_before_content', 'storefront_header_widget_region', 10 );
			}
		}

		/**
		 * Create the two step checkout.
		 *
		 * @since   1.0.0
		 * @return  void
		 */
		public function two_step_checkout() {
			$two_step_checkout = get_theme_mod( 'sp_two_step_checkout', false );

			if ( true === $two_step_checkout && is_checkout() ) {
				add_action( 'woocommerce_checkout_before_customer_details', 'sp_checkout_form_wrapper_div', 1 );
				add_action( 'woocommerce_checkout_before_customer_details', 'sp_checkout_form_wrapper',     2 );
				add_action( 'woocommerce_checkout_order_review',            'sp_close_div',                 30 );
				add_action( 'woocommerce_checkout_order_review',            'sp_close_ul',                  30 );
				add_action( 'woocommerce_checkout_before_customer_details', 'sp_address_wrapper',           5 );
				add_action( 'woocommerce_checkout_after_customer_details',  'sp_close_li' );
				add_action( 'wp_footer',                                    'sp_fire_flexslider' );
				add_action( 'woocommerce_checkout_before_order_review',     'sp_order_review_wrap',         1 );
				add_action( 'woocommerce_checkout_after_order_review',      'sp_close_li',                  40 );
			}
		}

		/**
		 * Enqueue styles and scripts.
		 *
		 * @since   1.0.0
		 * @return  void
		 */
		public function script() {
			$distraction_free    = get_theme_mod( 'sp_distraction_free_checkout', false );
			$checkout_layout     = get_theme_mod( 'sp_checkout_layout' );
			$two_step_checkout   = get_theme_mod( 'sp_two_step_checkout', false );
			$sticky_order_review = get_theme_mod( 'sp_sticky_order_review', false );

			/**
			 * Load the distraction free checkout styles if the setting is enabled and the checkout is the current page.
			 */
			if ( true === $distraction_free && is_checkout() ) {
				wp_enqueue_style( 'sp-distraction-free-checkout', plugins_url( 'assets/css/distraction-free.css', __FILE__ ), '', '1.0.0' );
				wp_style_add_data( 'sp-distraction-free-checkout', 'rtl', 'replace' );
			}

			/**
			 * Load the general checkout styles if the checkout is the current page.
			 */
			if ( 'default' !== $checkout_layout && is_checkout() ) {
				wp_enqueue_style( 'sp-checkout-layout', plugins_url( 'assets/css/layout.css', __FILE__ ), '', '1.0.0' );
				wp_style_add_data( 'sp-checkout-layout', 'rtl', 'replace' );

				// Disable the sticky payment javascript.
				wp_dequeue_script( 'storefront-sticky-payment' );
			}

			/**
			 * Load the two-step checkout styles and flexslider if the setting is enabled and the checkout is the current page.
			 */
			if ( true === $two_step_checkout && is_checkout() ) {
				wp_enqueue_style( 'sp-two-step-checkout', plugins_url( 'assets/css/two-step.css', __FILE__ ), '', '1.0.0' );
				wp_style_add_data( 'sp-two-step-checkout', 'rtl', 'replace' );
				wp_enqueue_script( 'flexslider', plugins_url( 'assets/js/jquery.flexslider.min.js', __FILE__ ), array( 'jquery' ), '2.5.0' );

				// Disable the sticky payment javascript.
				wp_dequeue_script( 'storefront-sticky-payment' );
			}

			/**
			 * Remove sticky order review from the checkout page.
			 */
			if ( false === $sticky_order_review && is_checkout() ) {
				wp_dequeue_script( 'storefront-sticky-payment' );
			}
		}

		/**
		 * Storefront Powerpack Body Class
		 *
		 * @param array $classes array of classes applied to the body tag.
		 * @see get_theme_mod()
		 */
		public function body_class( $classes ) {
			$checkout_layout  = get_theme_mod( 'sp_checkout_layout' );
			$distraction_free = get_theme_mod( 'sp_distraction_free_checkout', false );

			if ( true === $distraction_free ) {
				$classes[] = 'sp-distraction-free-checkout';
			}

			if ( 'default' !== $checkout_layout && is_checkout() ) {
				$classes[] = 'sp-' . $checkout_layout;
			}

			return $classes;
		}

		/**
		 * Add CSS in <head> for styles handled by the Customizer
		 *
		 * @return  void
		 * @since   1.2.1
		 */
		public function add_customizer_css() {
			$content_background_color = storefront_get_content_background_color();

			$checkout_style = '
				.checkout-slides .sp-checkout-control-nav li a:after {
					background-color:' . $content_background_color . ';
					border: 4px solid ' . storefront_adjust_color_brightness( $content_background_color, -40 ) . ';
				}

				.checkout-slides .sp-checkout-control-nav li:nth-child(2) a.flex-active:after {
					border: 4px solid ' . storefront_adjust_color_brightness( $content_background_color, -40 ) . ';
				}

				.checkout-slides .sp-checkout-control-nav li a:before,
				.checkout-slides .sp-checkout-control-nav li:nth-child(2) a.flex-active:before  {
					background-color:' . storefront_adjust_color_brightness( $content_background_color, -40 ) . ';
				}

				.checkout-slides .sp-checkout-control-nav li:nth-child(2) a:before {
					background-color:' . storefront_adjust_color_brightness( $content_background_color, -20 ) . ';
				}

				.checkout-slides .sp-checkout-control-nav li:nth-child(2) a:after {
					border: 4px solid ' . storefront_adjust_color_brightness( $content_background_color, -20 ) . ';
				}
			';

			wp_add_inline_style( 'storefront-style', $checkout_style );
		}
	}

endif;

return new SP_Frontend_Checkout();
