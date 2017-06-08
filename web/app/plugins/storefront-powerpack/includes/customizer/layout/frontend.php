<?php
/**
 * Storefront Powerpack Frontend Layout Class
 *
 * @author   WooThemes
 * @package  Storefront_Powerpack
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SP_Frontend_Layout' ) ) :

	/**
	 * The Frontend class.
	 */
	class SP_Frontend_Layout extends SP_Frontend {
		/**
		 * Setup class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'body_class', array( $this, 'body_class' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'script' ), 99 );
			add_action( 'wp_enqueue_scripts', array( $this, 'add_customizer_css' ), 999 );
		}

		/**
		 * Storefront Powerpack Body Class
		 *
		 * @param array $classes array of classes applied to the body tag.
		 * @see get_theme_mod()
		 */
		public function body_class( $classes ) {
			if ( 'max-width' === get_theme_mod( 'sp_max_width' ) ) {
				$classes[] = 'sp-max-width';
			}

			if ( 'frame' === get_theme_mod( 'sp_content_frame' ) ) {
				$classes[] = 'sp-fixed-width';
			}

			return $classes;
		}

		/**
		 * Enqueue styles and scripts.
		 *
		 * @since   1.0.0
		 * @return  void
		 */
		public function script() {
			if ( 'max-width' === get_theme_mod( 'sp_max_width' ) || 'frame' === get_theme_mod( 'sp_content_frame' ) ) {
				wp_enqueue_style( 'sp-layout', plugins_url( 'assets/css/layout.css', __FILE__ ), '', storefront_powerpack()->version );
			}
		}

		/**
		 * Add CSS in <head> for styles handled by the Customizer
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function add_customizer_css() {
			$content_background_color = get_theme_mod( 'sp_content_frame_background' );

			$wc_style = '
				.sp-fixed-width .site {
					background-color:' . $content_background_color . ';
				}
			';

			wp_add_inline_style( 'storefront-style', $wc_style );
		}
	}

endif;

return new SP_Frontend_Layout();