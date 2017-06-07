<?php
/**
 * Created by PhpStorm.
 * User: jimmiejohansson
 * Date: 2017-06-07
 * Time: 10:55
 */

function yardson_nyhetsbrev_enqueue_styles()
{

    $templateStyle = 'yardson-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style('yardson-nyhetsbrev-style',
        get_stylesheet_directory_uri() . '/nyhetsbrev/style.css',
        array($templateStyle),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script( 'yardson-nyhetsbrev-ajax-script', get_template_directory_uri() . '/nyhetsbrev/js/ajax-script.js', array('jquery') );

    wp_localize_script( 'yardson-nyhetsbrev-ajax-script', 'yardson_nyhetsbrev',
        array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action('wp_enqueue_scripts', 'yardson_nyhetsbrev_enqueue_styles');

function yardson_nyhetsbrev_markup()
{
    echo '<div class="newsletter-popup">';
    echo '<div class="form">';
    echo '<h4>Skapa ett gratis konto</h4>';
    echo '<h1>Få 10% rabatt</h1>';
    echo '<form id="nyhetsbrev_formular">';
    echo '<input id="nyhetsbrev_email" type="email" name="nyhetsbrev_email" placeholder="din@epost.se">';
    echo '<input id="nyhetsbrev_submit" type="submit" value="Skapa konto">';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    echo wp_localize_script(admin_url( 'admin-ajax.php' ));
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

        // Email the user
        wp_mail($email_address, 'Välkommen!', 'Ditt lösenord: ' . $password);

    } // end if

}

add_action( 'wp_ajax_nopriv_my_action', 'yardson_nyhetsbrev_create_account' );
