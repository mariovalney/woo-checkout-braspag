<?php
/**
 * Payment instructions.
 *
 * @version 2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

?>

<p class="woocommerce-thankyou-order-received-payment-data">
    <?php esc_html_e( 'Your payment data:', WCB_TEXTDOMAIN ); ?>
</p>

<ul class="woocommerce-thankyou-payment-details order_details">
    <li class="woocommerce-order-overview__payment-method-name method-name">
        <?php esc_html_e( 'Method name:', WCB_TEXTDOMAIN ); ?>
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

    <?php if ( ! empty( $payment['CreditCard'] ) ) : ?>
        <li class="woocommerce-order-overview__payment-card-number card-number">
            <?php esc_html_e( 'Credit Card:', WCB_TEXTDOMAIN ); ?>
            <strong>
            <?php
                esc_html( $payment['CreditCard']['CardNumber'] );

            if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
                esc_html( ' (' . $payment['CreditCard']['Brand'] . ')' );
            }
            ?>
            </strong>
        </li>
    <?php endif; ?>

    <li class="woocommerce-order-overview__payment-card-number card-number">
        <?php esc_html_e( 'Status:', WCB_TEXTDOMAIN ); ?>
        <strong><?php esc_html_e( wc_get_order_status_name( $status ) ); ?></strong>
        <br>
        <?php if ( $status === 'on-hold' || $status === 'pending' ) : ?>
            <?php esc_html_e( 'Your payment is pending for confirmation.', WCB_TEXTDOMAIN ); ?>
        <?php endif; ?>
    </li>

    <?php if ( ! empty( $payment['Url'] ) && ! empty( $payment['BoletoNumber'] ) ) : ?>
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
