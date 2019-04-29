<?php

/**
 * WC_Checkout_Braspag_Messages
 * Model for Braspag classes
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Messages
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Messages' ) ) {

    class WC_Checkout_Braspag_Messages {

        /**
         * Payment Status from Braspag
         * Message for Shop
         *
         * @since    1.0.0
         */
        public static function payment_status_note( $code ) {
            switch ( $code ) {
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_NOT_FINISHED:
                    return __( 'Braspag: payment is not finished.', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_AUTHORIZED:
                    return __( 'Braspag: payment method can be captured (credit/debit card) or paid (bank slip).', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PAYMENT_CONFIRMED:
                    return __( 'Braspag: payment confirmed.', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_DENIED:
                    return __( 'Braspag: payment denied (credit card and debit - eletronic transfer).', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_VOIDED:
                    return __( 'Braspag: payment canceled.', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_REFUNDED:
                    return __( 'Braspag: payment canceled/refund.', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PENDING:
                    return __( 'Braspag: waiting for bank (credit card and debit - eletronic transfer).', WCB_TEXTDOMAIN );
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_ABORTED:
                    return __( 'Braspag: payment aborted because processing failed.', WCB_TEXTDOMAIN );

                default:
                    break;
            }

            return '';
        }

        /**
         * Payment Reason Code from Braspag
         * Message for User
         *
         * Updated: 05-02-2019
         * @link https://braspag.github.io/manual/braspag-pagador#lista-de-reasoncode/reasonmessage
         *
         * @since    1.0.0
         */
        public static function payment_error_message( $reason_code, $is_credit_card = true ) {
            $card_type = $is_credit_card ? _x( 'credit card', 'To payment error messages.', WCB_TEXTDOMAIN ) : _x( 'debit card', 'To payment error messages.', WCB_TEXTDOMAIN );

            $message = '';
            switch ( $reason_code ) {
                case 7:
                    $message = __( 'Your payment was denied. Please try again with another method.', WCB_TEXTDOMAIN );
                    break;

                case 12:
                case 13:
                case 14:
                case 15:
                    // translators: card type (credit or debit)
                    $message = sprintf(
                        // translators: "debit card" or "credit card"
                        _x( 'There was a problem with your %s. Please try again with another one.', 'Credit or debit card.', WCB_TEXTDOMAIN ),
                        $card_type
                    );
                    break;

                case 18:
                    $message = __( 'There was a problem with your payment. Please try again.', WCB_TEXTDOMAIN );
                    break;

                case 21:
                    $message = sprintf(
                        // translators: "debit card" or "credit card"
                        _x( 'Your %s number is invalid. Please check it and try again.', 'Credit or debit card.', WCB_TEXTDOMAIN ),
                        $card_type
                    );
                    break;

                default:
                    $message = __( 'There was a problem with your payment. Please enter in contact or try again.', WCB_TEXTDOMAIN );
                    break;
            }

            return $message;
        }

    }

}
