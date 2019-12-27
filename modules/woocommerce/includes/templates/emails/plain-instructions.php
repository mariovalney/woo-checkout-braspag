<?php
/**
 * The Template for emails order details (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/emails/plain-instructions.php.
 *
 * HOWEVER, on occasion Woo Checkout Braspag will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. Just like WooCommerce.
 *
 * @var $payment    array  The payment data
 * @var $method     array  Payment Method data
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

_e( 'Payment data:', WCB_TEXTDOMAIN );

// Method Name
echo "\n\n";
_e( 'Payment method:', WCB_TEXTDOMAIN );
echo "\n" . $method['name'];

// Installments
if ( ! empty( $payment['Installments'] ) ) {
    echo "\n\n";
    _e( 'Installments:', WCB_TEXTDOMAIN );
    echo "\n";

    if ( ! empty( $payment['Amount'] ) ) {
        $installment = $payment['Amount'] / $payment['Installments'] / 100;
        $installment = number_format( $installment, 2, ',', '' );

        // translators: First is installments count and second is amount by installment
        printf( __( '%1$s x R$%2$s' ), $payment['Installments'], $installment );
    } else {
        echo $payment['Installments'];
    }
}

// Status
if ( ! empty( $payment['Status'] ) ) {
    echo "\n\n";
    _e( 'Status:', WCB_TEXTDOMAIN );
    echo "\n" . WC_Checkout_Braspag_Messages::payment_status( $payment['Status'] );
}

// Credit Card
if ( ! empty( $payment['CreditCard'] ) ) {
    echo "\n\n";
    _e( 'Credit Card:', WCB_TEXTDOMAIN );
    echo "\n" . $payment['CreditCard']['CardNumber'];

    if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
        echo ' (' . $payment['CreditCard']['Brand'] . ')';
    }
}

// Bank Slip
if ( ! empty( $payment['Url'] ) && ! empty( $payment['BoletoNumber'] ) && ( empty( $payment['Status'] ) || (string) $payment['Status'] !== '2' ) ) {
    echo "\n\n";
    _e( 'Bank Slip:', WCB_TEXTDOMAIN );
    echo "\n" . $payment['Url'];

    if ( ! empty( $payment['DigitableLine'] ) ) {
        echo "\n\n";
        _e( 'Digitable Line:', WCB_TEXTDOMAIN );
        echo "\n" . $payment['DigitableLine'];
    }
}

// End
echo "\n\n";
