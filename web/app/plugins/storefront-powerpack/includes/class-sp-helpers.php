<?php
/**
 * Storefront Powerpack Helpers Class
 *
 * @package  Storefront_Powerpack
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SP_Helpers' ) ) :

	/**
	 * The admin class
	 */
	class SP_Helpers {

		/**
		 * Construct is set to private so that it is not possible to instantiate
		 * this class.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		private function __construct() {}

		/**
		 * Converts a hex color to an rgba color
		 *
		 * @param  string $color the original hex color.
		 * @param  string $opacity the opacity value.
		 * @return string the hex color converted to an rgba color
		 * @since  1.0.0
		 */
		public static function hex_to_rgba( $color, $opacity = false ) {
			$default = 'rgb(0,0,0)';

			// Return default if no color provided.
			if ( empty( $color ) ) {
				return $default;
			}

			// Sanitize $color if "#" is provided.
			if ( $color[0] == '#' ) {
				$color = substr( $color, 1 );
			}

			// Check if color has 6 or 3 characters and get values.
			if ( strlen( $color ) == 6 ) {
				$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
			} elseif ( strlen( $color ) == 3 ) {
				$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
			} else {
				return $default;
			}

			// Convert hexadec to rgb.
			$rgb = array_map( 'hexdec', $hex );

			// Check if opacity is set(rgba or rgb).
			if ( $opacity ) {
				if ( abs( $opacity ) > 1 ) {
					$opacity = 1.0;
				}

				$output = 'rgba( ' . implode( ', ', $rgb ) . ',' . $opacity . ' )';
			} else {
				$output = 'rgb( ' . implode( ', ', $rgb ) . ' )';
			}

			// Return rgb(a) color string.
			return $output;
		}
	}

endif;