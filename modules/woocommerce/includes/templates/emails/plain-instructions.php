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

esc_html_e( 'Payment data:', WCB_TEXTDOMAIN );

// Method Name
echo "\n\n";
esc_html_e( 'Payment method:', WCB_TEXTDOMAIN );
echo "\n" . esc_html( $method['name'] );

// Installments
if ( ! empty( $payment['Installments'] ) ) {
    echo "\n\n";
    esc_html_e( 'Installments:', WCB_TEXTDOMAIN );
    echo "\n";

    if ( ! empty( $payment['Amount'] ) ) {
        $installment = $payment['Amount'] / $payment['Installments'] / 100;
        $installment = number_format( $installment, 2, ',', '' );

        // translators: First is installments count and second is amount by installment
        printf( esc_html__( '%1$s x R$%2$s' ), esc_html( $payment['Installments'] ), esc_html( $installment ) );
    } else {
        echo esc_html( $payment['Installments'] );
    }
}

// Status
if ( ! empty( $payment['Status'] ) ) {
    echo "\n\n";
    esc_html_e( 'Status:', WCB_TEXTDOMAIN );
    echo "\n" . esc_html( WC_Checkout_Braspag_Messages::payment_status( $payment['Status'] ) );
}

// Credit Card
if ( ! empty( $payment['CreditCard'] ) ) {
    echo "\n\n";
    esc_html_e( 'Credit Card:', WCB_TEXTDOMAIN );
    echo "\n" . esc_html( $payment['CreditCard']['CardNumber'] );

    if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
        echo ' (' . esc_html( $payment['CreditCard']['Brand'] ) . ')';
    }
}

// Bank Slip
if ( ! empty( $payment['Url'] ) && ! empty( $payment['BoletoNumber'] ) && ( empty( $payment['Status'] ) || (string) $payment['Status'] !== '2' ) ) {
    echo "\n\n";
    esc_html_e( 'Bank Slip:', WCB_TEXTDOMAIN );
    echo "\n" . esc_html( $payment['Url'] );

    if ( ! empty( $payment['DigitableLine'] ) ) {
        echo "\n\n";
        esc_html_e( 'Digitable Line:', WCB_TEXTDOMAIN );
        echo "\n" . esc_html( $payment['DigitableLine'] );
    }
}

// End
echo "\n\n";
