<?php

add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 12 );
add_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 12 );
add_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 12 );
add_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 12 );
add_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 12 );
add_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 11 );