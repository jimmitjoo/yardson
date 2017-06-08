jQuery('document').ready(function () {
    if (get_cookie('shown_create_account') === null && yardson_nyhetsbrev_display) {
        jQuery('#nlpop').show();
        jQuery('#nlpop-open').hide();

        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop .close').click(function (event) {
            jQuery('#nlpop').hide();
            jQuery('#nlpop-open').show();
        });
    } else if (get_cookie('account_is_created') === null && yardson_nyhetsbrev_display) {
        jQuery('#nlpop-open').show();
    }

    jQuery('#nlpop-open').click(function () {
        jQuery('#nlpop').show();
    });
});



console.log({'Cookies':document.cookie});