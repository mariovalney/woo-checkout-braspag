<?php

/**
 * WC_Checkout_Braspag_Request_Payment_Dc
 * Class responsible to request a Debit Card Payment to API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Request_Payment_Dc
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request_Payment_Dc' ) ) {

    class WC_Checkout_Braspag_Request_Payment_Dc extends WC_Checkout_Braspag_Request_Payment_Cc {

        const METHOD_CODE = 'dc';

        /**
         * Card Node to be populated in JSON
         * @var string
         */
        protected $card_node = 'DebitCard';

        /**
         * Populate data.
         *
         * @see WC_Order()
         * @since    1.0.0
         *
         * @param    WC_Order  $data
         */
        public function populate( $order ) {
            parent::populate( $order );

            if ( empty( $this->Payment ) ) {
                throw new Exception( __( 'There was a problem with your payment. Please try again.', WCB_TEXTDOMAIN ) );
            }

            // Return URL
            $this->Payment['ReturnUrl'] = $this->gateway->get_api_return_url();

            /**
             * Action allow developers to change request data
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment_' . $this::METHOD_CODE, $this, $order );
        }

        /**
         * Finish payment request to API
         * We slice the method to allow Debit Card reuse.
         *
         * @since    1.0.0
         *
         * @param    array  $transaction A Braspag transaction.
         * @return   array  $transaction A Braspag transaction.
         */
        public function finish_request( $transaction ) {
            $payment = $transaction['Payment'] ?? [];
            $status  = $payment['Status'] ?? '';

            // Authorized and not finished should redirect to payment
            if ( (int) $status === WC_Checkout_Braspag_Api::TRANSACTION_STATUS_NOT_FINISHED ) {

                // Redirect URL
                if ( empty( $payment['AuthenticationUrl'] ) ) {
                    throw new Exception( __( 'There was a problem with your payment. Please try again.', WCB_TEXTDOMAIN ) );
                }

                // Log Redirect
                $payment_id = $transaction['Payment']['PaymentId'];
                $this->gateway->log( 'Payment ' . $payment_id . ' was authorized and user was sent to authentication.' );

                return [
                    'url'         => $payment['AuthenticationUrl'],
                    'transaction' => $transaction,
                ];
            }

            // Other cases we throw to create a notice
            $reason_code = $payment['ReasonCode'] ?? '';
            $message     = WC_Checkout_Braspag_Messages::payment_error_message( $reason_code, false );

            throw new Exception( $message );
        }

    }

}
