window.newsletterFormData = {};
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
        jQuery('#nyhetsbrev_formular').html(
            "<style type='text/css'>@-webkit-keyframes uil-default-anim { 0% { opacity: 1} 100% {opacity: 0} }@keyframes uil-default-anim { 0% { opacity: 1} 100% {opacity: 0} }.uil-default-css > div:nth-of-type(1){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -1s;animation-delay: -1s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(2){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.9166666666666666s;animation-delay: -0.9166666666666666s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(3){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.8333333333333334s;animation-delay: -0.8333333333333334s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(4){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.75s;animation-delay: -0.75s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(5){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.6666666666666666s;animation-delay: -0.6666666666666666s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(6){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.5833333333333334s;animation-delay: -0.5833333333333334s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(7){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.5s;animation-delay: -0.5s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(8){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.4166666666666667s;animation-delay: -0.4166666666666667s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(9){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.3333333333333333s;animation-delay: -0.3333333333333333s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(10){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.25s;animation-delay: -0.25s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(11){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.16666666666666666s;animation-delay: -0.16666666666666666s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}.uil-default-css > div:nth-of-type(12){-webkit-animation: uil-default-anim 1s linear infinite;animation: uil-default-anim 1s linear infinite;-webkit-animation-delay: -0.08333333333333333s;animation-delay: -0.08333333333333333s;}.uil-default-css { position: relative;background:none;width:200px;height:200px;}</style><div class='uil-default-css' style='transform:scale(0.6);'><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(0deg) translate(0,-60px);transform:rotate(0deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(30deg) translate(0,-60px);transform:rotate(30deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(60deg) translate(0,-60px);transform:rotate(60deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(90deg) translate(0,-60px);transform:rotate(90deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(120deg) translate(0,-60px);transform:rotate(120deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(150deg) translate(0,-60px);transform:rotate(150deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(180deg) translate(0,-60px);transform:rotate(180deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(210deg) translate(0,-60px);transform:rotate(210deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(240deg) translate(0,-60px);transform:rotate(240deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(270deg) translate(0,-60px);transform:rotate(270deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(300deg) translate(0,-60px);transform:rotate(300deg) translate(0,-60px);border-radius:10px;position:absolute;'></div><div style='top:80px;left:98px;width:4px;height:40px;background:#333333;-webkit-transform:rotate(330deg) translate(0,-60px);transform:rotate(330deg) translate(0,-60px);border-radius:10px;position:absolute;'></div></div>"
        );
        window.newsletterFormData.nyhetsbrev_email = jQuery('#nyhetsbrev_email').val();
        jQuery.ajax({
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