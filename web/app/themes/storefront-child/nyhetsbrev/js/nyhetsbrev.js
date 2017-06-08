if ( get_cookie( 'shown_create_account' ) === null ) {
    jQuery('document').ready(function() {
        console.log('show nlpop');
        jQuery('#nlpop').show();
        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop .close').click(function(event) {
            jQuery('#nlpop').slideDown();
            console.log('close nlpop');
        });

    });
}