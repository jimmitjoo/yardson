<?php
/**
 * Created by PhpStorm.
 * User: jimmiejohansson
 * Date: 2017-06-07
 * Time: 10:55
 */

function yardson_nyhetsbrev_enqueue_styles()
{

    $templateStyle = 'storefront-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style('yardson-nyhetsbrev-style',
        get_stylesheet_directory_uri() . '/nyhetsbrev/style.css',
        array($templateStyle),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script( 'yardson-nyhetsbrev-display', get_template_directory_uri() . '/../storefront-child/nyhetsbrev/js/nyhetsbrev.js', array('yardson-script', 'jquery') );
    //wp_enqueue_script( 'yardson-nyhetsbrev-ajax-script', get_template_directory_uri() . '/../storefront-child/nyhetsbrev/js/ajax-script.js', array('jquery') );

    wp_localize_script( "yardson-nyhetsbrev-ajax-script",
        'yardson_nyhetsbrev',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), //url for php file that process ajax request to WP
            'nonce' => wp_create_nonce( "unique_id_nonce" ),// this is a unique token to prevent form hijacking
        )
    );
}

add_action('wp_enqueue_scripts', 'yardson_nyhetsbrev_enqueue_styles');
add_action('wp_enqueue_scripts', 'yardson_nyhetsbrev_enqueue_styles');

function yardson_nyhetsbrev_markup()
{
    echo '<div id="nlpop">';
    echo '<div class="newsletter-popup">';
    echo '<div class="close">Stäng</div>';
    echo '<div class="form">';
    echo '<h4>Skapa ett <strong>gratis konto</strong></h4>';
    echo '<h1>Få <strong>10% rabatt</strong></h1>';
    echo '<h4>på första köpet!</h4>';
    echo '<form id="nyhetsbrev_formular">';
    echo '<input id="nyhetsbrev_email" type="email" name="nyhetsbrev_email" placeholder="din@epost.se">';
    echo '<input id="nyhetsbrev_submit" class="button alt" type="submit" value="Skapa konto">';
    echo '</form>';
    echo '<p><small>Genom att skapa konto godkänner jag YARDsons <a href="/villkor">villkor</a>.</small></p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

}

add_action('wp_footer', 'yardson_nyhetsbrev_markup');

function yardson_nyhetsbrev_create_account()
{
    $email_address = $_POST['nyhetsbrev_email'];
    if (null == username_exists($email_address)) {

        // Generate the password and create the user
        $password = wp_generate_password(12, true);
        $user_id = wp_create_user($email_address, $password, $email_address);

        // Set the nickname
        wp_update_user(
            array(
                'ID' => $user_id,
                'nickname' => $email_address
            )
        );

        // Set the role
        $user = new WP_User($user_id);
        $user->set_role('customer');

        add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );

        // Email the user
        wp_mail(
            $email_address,
            'Välkommen till YARDson!',
            '<p>Ditt lösenord: ' . $password . '</p><p>Din rabattkod: konto-rabatt</p>'
        );

    } // end if

}
function wpdocs_set_html_mail_content_type() {
    return 'text/html';
}

add_action( 'wp_ajax_my_action', 'yardson_nyhetsbrev_create_account' );
add_action( 'wp_ajax_nopriv_my_action', 'yardson_nyhetsbrev_create_account' );
