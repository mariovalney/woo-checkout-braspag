'use strict';

jQuery(document).ready(function($) {
    /**
     * Element: Payment Method Checkbox
     * Event: Show form on checked
     */
    $('body').on('change', '[name="braspag_payment_method"]', function(event) {
        var code = $(this).val();

        if ( ! $(this).prop('checked') || ! code ) return;

        $('.braspag-method-label, .braspag-method-form').removeClass('active');
        $('#payment-method-' + code + '-label, #braspag-payment-method-' + code + '-form' ).addClass('active');
    });
});
