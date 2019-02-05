<?php
/**
 * The Template for Credit Card payment in Braspag
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/payment-methods/cc-form.php.
 *
 * HOWEVER, on occasion Woo Checkout Braspag will will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. Just like WooCommerce.
 *
 * @var $description                string  The gateway description
 * @var $methods                    array   Array of payment methods: { code => name }
 *
 * @version 1.0.0
 */
?>

<p class="form-row">
    <label><?php _e( 'Credit Card Number', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <input type="text" name="braspag_payment_cc_number" value="0000.0000.0000.0001">
    </span>
</p>

<p class="form-row">
    <label><?php _e( 'Holder Name', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <input type="text" name="braspag_payment_cc_holder" value="Card Holder Name">
    </span>
</p>

<p class="form-row">
    <label><?php _e( 'Expiration Date', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <input type="text" name="braspag_payment_cc_expiration_date" value="04/22">
    </span>
</p>

<p class="form-row">
    <label><?php _e( 'Security Code', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <input type="text" name="braspag_payment_cc_security_code" value="123">
    </span>
</p>

<p class="form-row">
    <label><?php _e( 'Brand', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <input type="text" name="braspag_payment_cc_brand" value="Visa">
    </span>
</p>

<p class="form-row">
    <label><?php _e( 'Installments', WCB_TEXTDOMAIN ); ?></label>
    <span class="woocommerce-input-wrapper">
        <select class="input-text" style="-webkit-appearance: menulist-button;" name="braspag_payment_cc_installments">
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
        </select>
    </span>
</p>

