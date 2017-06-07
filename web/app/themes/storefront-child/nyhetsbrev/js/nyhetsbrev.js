if ( get_cookie( 'shown_create_account' ) === null ) {
    jQuery('document').ready(function() {
        jQuery('#nlpop').show();
        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop').click(function() {
            jQuery(this).hide();
        });

    });
}