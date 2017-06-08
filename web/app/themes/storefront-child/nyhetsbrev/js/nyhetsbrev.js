jQuery('document').ready(function () {
    if (get_cookie('shown_create_account') === null && yardson_nyhetsbrev_display) {
        jQuery('#nlpop').show();
        jQuery('#nlpop-open').hide();

        create_cookie('shown_create_account', 'yes', 21);
    } else if (get_cookie('account_is_created') === null && yardson_nyhetsbrev_display) {
        jQuery('#nlpop-open').show();
    }

    jQuery('#nlpop-open').click(function () {
        jQuery('#nlpop').show();
        jQuery('#nlpop-open').hide();
    });

    jQuery('#nlpop .close').click(function () {
        jQuery('#nlpop').hide();
        jQuery('#nlpop-open').show();
    });
});



console.log({'Cookies':document.cookie});