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
    <?php _e( 'Your payment data:', VZR_TEXTDOMAIN ); ?>
</p>

<ul class="woocommerce-thankyou-payment-details order_details">
    <li class="woocommerce-order-overview__payment-method-name method-name">
        <?php _e( 'Method name:', WCB_TEXTDOMAIN ); ?>
        <strong><?php echo $method['name']; ?></strong>
    </li>

    <?php if ( ! empty( $payment['Installments'] ) ): ?>
        <li class="woocommerce-order-overview__payment-method-name method-name">
            <?php _e( 'Installments:', WCB_TEXTDOMAIN ); ?>
            <strong>
                <?php
                    if ( ! empty( $payment['Amount'] ) ) {
                        $installment = $payment['Amount'] / $payment['Installments'] / 100;
                        $installment = number_format( $installment, 2, ',', '' );

                        printf( __( '%s x R$%s' ), $payment['Installments'], $installment );
                    } else {
                        echo $payment['Installments'];
                    }
                ?>
            </strong>
        </li>
    <?php endif; ?>

    <?php if ( ! empty( $payment['CreditCard'] ) ): ?>
        <li class="woocommerce-order-overview__payment-card-number card-number">
            <?php _e( 'Credit Card:', WCB_TEXTDOMAIN ); ?>
            <strong>
                <?php
                    echo $payment['CreditCard']['CardNumber'];

                    if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
                        echo ' (' . $payment['CreditCard']['Brand'] . ')';
                    }
                ?>
            </strong>
        </li>
    <?php endif; ?>

    <li class="woocommerce-order-overview__payment-card-number card-number">
        <?php _e( 'Status:', WCB_TEXTDOMAIN ); ?>
        <strong><?php echo wc_get_order_status_name($status); ?></strong>
        <br>
        <?php if ( $status === 'pending' ): ?>
            <div class="woocommerce-message">
                Seu pagamento está aguardando a confirmação da operadora do cartão de crédito.
            <span>
        <?php endif; ?>
    </li>
</ul>
