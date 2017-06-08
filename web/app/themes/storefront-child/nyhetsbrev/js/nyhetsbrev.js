if ( get_cookie( 'shown_create_account' ) === null ) {
    jQuery('document').ready(function() {
        jQuery('#nlpop').slideUp();
        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop .close').click(function(event) {
            jQuery('#nlpop').slideDown();
        });

    });
}