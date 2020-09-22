<?php
/**
 * Admin View: Meta Box for Payment Form
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( empty( $order ) ) {
    return;
}

if ( $order->get_transaction_id() ) {
    echo '<p>' . esc_html( __( 'You cannot create a payment because this order already have a Transaction ID.', WCB_TEXTDOMAIN ) ) . '</p>';

    $order_action = '<strong>' . __( 'Update payment info from Braspag', WCB_TEXTDOMAIN ) . '</strong>';

    /* translators: order action on bold */
    echo '<p>' . sprintf( __( 'Use the "%s" order action to get payment info from Braspag or remove the Transaction ID.', WCB_TEXTDOMAIN ), $order_action ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    return;
}

$options = array(
    'none' => __( 'Choose a payment method', WCB_TEXTDOMAIN ),
);
foreach ( $braspag_gateway->get_frontend_payment_methods() as $code => $data ) {
    $options[ $code ] = $data['name'];
}

echo '<div id="wc-braspag-meta-box-create-payment-form">';

$args = array(
    'id'      => 'braspag_payment_method',
    'label'   => __( 'Payment Method', WCB_TEXTDOMAIN ),
    'value'   => 'none',
    'options' => $options,
);
woocommerce_wp_select( $args );

echo '<div class="payment-method-wrapper payment-method-bs">';
echo '<p>' . esc_html( __( 'When saving the order we will try to create your payment.', WCB_TEXTDOMAIN ) ) . '</p>';
echo '</div>';

echo '<div class="payment-method-wrapper payment-method-cc">';
require_once WCB_PLUGIN_PATH . '/modules/woocommerce/includes/templates/payment-methods/cc-form.php';
echo '</div>';

echo '</div>';
