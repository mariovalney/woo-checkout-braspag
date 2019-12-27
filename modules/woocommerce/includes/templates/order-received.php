<?php
/**
 * The Template for thank you page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/order-received.php.
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

?>

<p class="woocommerce-thankyou-order-received-payment-data">
    <?php esc_html_e( 'Your payment data:', WCB_TEXTDOMAIN ); ?>
</p>

<ul class="woocommerce-thankyou-payment-details order_details">
    <li class="woocommerce-order-overview__payment-method-name method-name">
        <?php esc_html_e( 'Payment method:', WCB_TEXTDOMAIN ); ?>
        <strong><?php echo esc_html( $method['name'] ); ?></strong>
    </li>

    <?php if ( ! empty( $payment['Installments'] ) ) : ?>
        <li class="woocommerce-order-overview__payment-method-name method-name">
            <?php esc_html_e( 'Installments:', WCB_TEXTDOMAIN ); ?>
            <strong>
            <?php
                if ( ! empty( $payment['Amount'] ) ) {
                    $installment = $payment['Amount'] / $payment['Installments'] / 100;
                    $installment = number_format( $installment, 2, ',', '' );

                    // translators: First is installments count and second is amount by installment
                    echo esc_html( sprintf( __( '%1$s x R$%2$s' ), $payment['Installments'], $installment ) );
                } else {
                    echo esc_html( $payment['Installments'] );
                }
            ?>
            </strong>
        </li>
    <?php endif; ?>

    <?php if ( ! empty( $payment['Status'] ) ) : ?>
        <li class="woocommerce-order-overview__payment-card-number card-number">
            <?php esc_html_e( 'Status:', WCB_TEXTDOMAIN ); ?>
            <strong><?php esc_html_e( WC_Checkout_Braspag_Messages::payment_status( $payment['Status'] ) ); ?></strong>
        </li>
    <?php endif; ?>

    <?php if ( ! empty( $payment['CreditCard'] ) ) : ?>
        <li class="woocommerce-order-overview__payment-card-number card-number">
            <?php esc_html_e( 'Credit Card:', WCB_TEXTDOMAIN ); ?>
            <strong>
            <?php
                echo esc_html( $payment['CreditCard']['CardNumber'] );

                if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
                    echo esc_html( ' (' . $payment['CreditCard']['Brand'] . ')' );
                }
            ?>
            </strong>
        </li>
    <?php endif; ?>

    <?php if ( ! empty( $payment['Url'] ) && ! empty( $payment['BoletoNumber'] ) && ( empty( $payment['Status'] ) || (string) $payment['Status'] !== '2' ) ) : ?>
        <li class="woocommerce-order-overview__payment-bank-slip-url bank-slip-url">
            <?php esc_html_e( 'Bank Slip:', WCB_TEXTDOMAIN ); ?>
            <br>
            <a href="<?php echo esc_url( $payment['Url'] ); ?>" class="woocommerce-button button" target="_blank" rel="noopener noreferrer">
                <?php esc_html_e( 'Print bank slip', WCB_TEXTDOMAIN ); ?>
            </a>
            <?php if ( ! empty( $payment['DigitableLine'] ) ) : ?>
                <span class="digitable-line">
                    <?php esc_html_e( 'Digitable Line:', WCB_TEXTDOMAIN ); ?>
                    <input type="text" class="selectable-content" value="<?php echo esc_html( $payment['DigitableLine'] ); ?>"
                        title="<?php esc_html_e( 'Click to copy', WCB_TEXTDOMAIN ); ?>" readonly>
                </span>
            <?php endif; ?>
        </li>
    <?php endif; ?>
</ul>
