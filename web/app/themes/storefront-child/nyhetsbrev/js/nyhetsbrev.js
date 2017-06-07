if ( get_cookie( 'shown_create_account' ) === null ) {
    jQuery('document').ready(function() {
        jQuery('#nlpop').show();
        create_cookie('shown_create_account', 'yes', 21);

        jQuery('#nlpop .close').click(function(event) {
            jQuery('#nlpop').hide();
        });

    });
}

var options = {
    url: yardson_nyhetsbrev.ajax_url,  // this is part of the JS object you pass in from wp_localize_scripts.
    type: 'post',        // 'get' or 'post', override for form's 'method' attribute
    dataType: 'json',
    success : function(responseText, statusText, xhr, $form) {
        $('#nyhetsbrev_formular').html('<p>Ditt konto är nu skapat, kolla din e-post!</p>');
    },
    // use beforeSubmit to add your nonce to the form data before submitting.
    beforeSubmit : function(arr, $form, options){
        arr.push( { "name" : "nonce", "value" : yardson_nyhetsbrev.nonce });
    },

};

// you should probably use an id more unique than "form"
$('#nyhetsbrev_formular').ajaxForm(options);