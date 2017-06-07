window.newsletterFormData = {};

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
jQuery('#nyhetsbrev_formular').ajax(options);
/*
jQuery('#nyhetsbrev_formular').on('submit', function (event) {
    event.preventDefault();

    window.newsletterFormData.nyhetsbrev_email = jQuery('#nyhetsbrev_email').val();


    jQuery.ajax(
        {
            type: "post",
            dataType: "json",
            url: yardson_nyhetsbrev.ajax_url,
            data: window.newsletterFormData,
            success: function (msg) {
                console.log(msg);
            }
        }
    );
});

*/