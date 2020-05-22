<?php
/**
 * Admin View: Add Payment Info
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );


echo '<div class="clear"></div>';
echo '<h3>' . esc_html__( 'Payment', WCB_TEXTDOMAIN ) . '</h3>';
echo '<div class="braspag-payment"><p>';

if ( empty( $payment ) ) {
    esc_html_e( 'No payment info.', WCB_TEXTDOMAIN );
    echo '</p></div>';

    return;
}

/**
 * Filter payment info on dashboard
 *
 * @var array
 */
$fields = apply_filters( 'wc_checkout_braspag_admin_order_payment_data', $this->get_payment_info( $payment ), $payment );
foreach ( $fields as $field ) {
    echo '<strong>' . esc_html( $field['label'] ) . '</strong>: ' . $field['value'] . '<br>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

echo '</p></div>';
