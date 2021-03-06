<?php

function yardson_enqueue_styles() {

    $parent_style = 'storefront-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'yardson-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
    wp_enqueue_script('yardson-script', get_template_directory_uri() . '/../storefront-child/js/yardson.js', ['jquery'], wp_get_theme()->get('Version') );
}
add_action( 'wp_enqueue_scripts', 'yardson_enqueue_styles' );

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );

add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 12 );
add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 12 );
add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 12 );
add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 12 );
add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 12 );
add_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 11 );



if ( ! function_exists( 'storefront_site_security' ) ) {
    /**
     * Site branding wrapper and display
     *
     * @since  1.0.0
     * @return void
     */
    function storefront_site_security() {
        ?>
        <div class="site-security">
            <img src="<?php echo get_template_directory_uri() . '/../storefront-child/images/site-security.png'; ?>">
        </div>
        <?php
    }
}
add_action( 'storefront_header', 'storefront_site_security', 21 );

include 'nyhetsbrev/functions.php';