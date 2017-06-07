window.newsletterFormData = {};

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

