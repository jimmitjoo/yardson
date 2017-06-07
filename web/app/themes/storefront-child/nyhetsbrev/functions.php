<?php
/**
 * Created by PhpStorm.
 * User: jimmiejohansson
 * Date: 2017-06-07
 * Time: 10:55
 */

function yardson_nyhetsbrev_enqueue_styles() {

    $templateStyle = 'yardson-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( 'yardson-nyhetsbrev-style',
        get_stylesheet_directory_uri() . '/nyhetsbrev/style.css',
        array( $templateStyle ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'yardson_nyhetsbrev_enqueue_styles' );