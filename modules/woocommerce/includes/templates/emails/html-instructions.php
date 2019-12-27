<?php
/**
 * The Template for emails order details (html)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/emails/html-instructions.php.
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

$data = [];

// Method Name
if ( ! empty( $method['name'] ) ) {
    $data[] = [
        __( 'Payment method:', WCB_TEXTDOMAIN ),
        $method['name'],
    ];
}

// Installments
if ( ! empty( $payment['Installments'] ) ) {
    $installments = $payment['Installments'];

    if ( ! empty( $payment['Amount'] ) ) {
        $installment = $payment['Amount'] / $payment['Installments'] / 100;
        $installment = number_format( $installment, 2, ',', '' );

        // translators: First is installments count and second is amount by installment
        $installments = sprintf( __( '%1$s x R$%2$s' ), $payment['Installments'], $installment );
    }

    $data[] = [
        __( 'Installments:', WCB_TEXTDOMAIN ),
        $installments,
    ];
}

// Status
if ( ! empty( $payment['Status'] ) ) {
    $data[] = [
        __( 'Status:', WCB_TEXTDOMAIN ),
        WC_Checkout_Braspag_Messages::payment_status( $payment['Status'] ),
    ];
}

// Credit Card
if ( ! empty( $payment['CreditCard'] ) ) {
    $card_number = $payment['CreditCard']['CardNumber'] ?? '';

    if ( ! empty( $payment['CreditCard']['Brand'] ) ) {
        $card_number .= ' (' . $payment['CreditCard']['Brand'] . ')';
    }

    $data[] = [
        __( 'Credit Card:', WCB_TEXTDOMAIN ),
        $card_number,
    ];
}

// Bank Slip
if ( ! empty( $payment['Url'] ) && ! empty( $payment['BoletoNumber'] ) && ( empty( $payment['Status'] ) || (string) $payment['Status'] !== '2' ) ) {
    $data[] = [
        __( 'Bank Slip:', WCB_TEXTDOMAIN ),
        $payment['Url'],
    ];

    if ( ! empty( $payment['DigitableLine'] ) ) {
        $data[] = [
            __( 'Digitable Line:', WCB_TEXTDOMAIN ),
            $payment['DigitableLine'],
        ];
    }
}

?>

<h2>
    <?php esc_html_e( 'Payment data', WCB_TEXTDOMAIN ); ?>
</h2>

<div style="margin-bottom: 40px;">
    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
        <tbody>
            <?php foreach ( $data as $row ) : ?>
                <tr>
                    <td class="td" style="text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                        <strong><?php echo esc_html( $row[0] ); ?></strong>
                    </td>
                    <td class="td" style="text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
                        <?php echo esc_html( $row[1] ); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
