jQuery('document').ready(function () {
    if (get_cookie('shown_create_account') === null) {
        jQuery('#nlpop').show();
        jQuery('#nlpop-open').hide();

        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop .close').click(function (event) {
            jQuery('#nlpop').hide();
            jQuery('#nlpop-open').show();
        });
    } else if (get_cookie('account_is_created') === null) {
        jQuery('#nlpop-open').show();
    }
});

console.log({'Cookies':document.cookie});