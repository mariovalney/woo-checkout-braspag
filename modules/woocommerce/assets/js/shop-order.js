'use strict';

jQuery( document ).ready( function($) {
    $('body').on('change', '#braspag_payment_method', function(event) {
        event.preventDefault();

        var method = $(this).val();
        $('#wc-braspag-meta-box-create-payment-form .payment-method-wrapper').hide();
        $('#wc-braspag-meta-box-create-payment-form .payment-method-wrapper.payment-method-' + method).show();
    });
} );
