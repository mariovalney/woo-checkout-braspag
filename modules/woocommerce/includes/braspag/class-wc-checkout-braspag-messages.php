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
         * Message for User
         *
         * @since    1.0.0
         */
        public static function payment_status( $code ) {
            $status = '';

            switch ( $code ) {
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_ABORTED:
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_NOT_FINISHED:
                    $status = __( 'Failed', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_AUTHORIZED:
                    $status = __( 'Processing', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PAYMENT_CONFIRMED:
                    $status = __( 'Paid', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_DENIED:
                    $status = __( 'Denied', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_VOIDED:
                    $status = __( 'Canceled', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_REFUNDED:
                    $status = __( 'Refunded', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PENDING:
                    $status = __( 'Waiting confirmation', WCB_TEXTDOMAIN );
                    break;

                default:
                    break;
            }

            /**
             * Filter payment status
             *
             * @param string $status
             * @param int $code
             */
            return apply_filters( 'wc_checkout_braspag_payment_status', $status, $code );
        }

        /**
         * Payment Status from Braspag
         * Message for Shop
         *
         * @since    1.0.0
         */
        public static function payment_status_note( $code ) {
            $note = '';

            switch ( $code ) {
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_NOT_FINISHED:
                    $note = __( 'Braspag: payment is not finished.', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_AUTHORIZED:
                    $note = __( 'Braspag: payment method can be captured (credit/debit card) or paid (bank slip).', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PAYMENT_CONFIRMED:
                    $note = __( 'Braspag: payment confirmed.', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_DENIED:
                    $note = __( 'Braspag: payment denied (credit card and debit - eletronic transfer).', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_VOIDED:
                    $note = __( 'Braspag: payment canceled.', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_REFUNDED:
                    $note = __( 'Braspag: payment canceled/refund.', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PENDING:
                    $note = __( 'Braspag: waiting for bank (credit card and debit - eletronic transfer).', WCB_TEXTDOMAIN );
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_ABORTED:
                    $note = __( 'Braspag: payment aborted because processing failed.', WCB_TEXTDOMAIN );
                    break;

                default:
                    break;
            }

            /**
             * Filter payment status note
             *
             * @param string $note
             * @param int $code
             */
            return apply_filters( 'wc_checkout_braspag_payment_status_note', $note, $code );
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
        public static function payment_error_message( $reason_code, $is_credit_card = true, $default_message = '' ) {
            $card_type = $is_credit_card ? _x( 'credit card', 'To payment error messages.', WCB_TEXTDOMAIN ) : _x( 'debit card', 'To payment error messages.', WCB_TEXTDOMAIN );

            $message = $default_message;
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

                case 105:
                    $message = __( 'Customer name is required.', WCB_TEXTDOMAIN );
                    break;

                case 117:
                    $message = __( 'Card holder is invalid.', WCB_TEXTDOMAIN );
                    break;

                case 118:
                case 127:
                case 128:
                    $message = __( 'Card number is required.', WCB_TEXTDOMAIN );
                    break;

                case 125:
                case 126:
                    $message = __( 'Card expiration date is invalid.', WCB_TEXTDOMAIN );
                    break;

                case 147:
                case 148:
                case 149:
                case 150:
                case 151:
                case 152:
                case 153:
                case 154:
                case 155:
                case 156:
                case 157:
                    $message = __( 'Customer data is invalid.', WCB_TEXTDOMAIN );
                    break;

                default:
                    if ( empty( $message ) ) {
                        $message = __( 'There was a problem with your payment. Please enter in contact or try again.', WCB_TEXTDOMAIN );
                    }
                    break;
            }

            /**
             * Filter the error message from payments
             *
             * @param string $message
             * @param int $code
             * @param boolean $is_credit_card
             * @param string $default_message
             */
            return apply_filters( 'wc_checkout_braspag_payment_error_message', $message, $reason_code, $is_credit_card, $default_message );
        }

    }

}
