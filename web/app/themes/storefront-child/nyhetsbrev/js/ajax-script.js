window.newsletterFormData = {};
window.newsletterFormData.nyhetsbrev_email = jQuery('#nyhetsbrev_email').val();
window.newsletterFormData.action = 'yardson_nyhetsbrev_create_account';

var options = {
    url: yardson_nyhetsbrev.ajax_url,  // this is part of the JS object you pass in from wp_localize_scripts.
    type: 'post',        // 'get' or 'post', override for form's 'method' attribute
    dataType: 'json',
    data: window.newsletterFormData,
    success: function (responseText, statusText, xhr, $form) {
        jQuery('#nyhetsbrev_formular').html('<p>Ditt konto är nu skapat, kolla din e-post!</p>');
    },
    // use beforeSubmit to add your nonce to the form data before submitting.
    beforeSubmit: function (arr, $form, options) {
        arr.push({"name": "nonce", "value": yardson_nyhetsbrev.nonce});
    },

};
jQuery('document').ready(function () {
    console.log('doc ready');

    jQuery('#nyhetsbrev_formular').submit(function (e) {
        e.preventDefault();
        console.log('submitted');
        jQuery.ajax({
            url: yardson_nyhetsbrev.ajax_url,  // this is part of the JS object you pass in from wp_localize_scripts.
            type: 'post',        // 'get' or 'post', override for form's 'method' attribute
            dataType: 'json',
            data: window.newsletterFormData,
            success: function (responseText, statusText, xhr, $form) {
                console.log(responseText);
                console.log(statusText);
                jQuery('#nyhetsbrev_formular').html('<p>Ditt konto är nu skapat, kolla din e-post!</p>');
            },
            // use beforeSubmit to add your nonce to the form data before submitting.
            beforeSubmit: function (arr, $form, options) {
                arr.push({"name": "nonce", "value": yardson_nyhetsbrev.nonce});
            }
        });

        return false;
    });
});
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