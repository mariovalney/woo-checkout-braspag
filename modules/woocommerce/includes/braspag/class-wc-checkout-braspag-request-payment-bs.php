<?php

/**
 * WC_Checkout_Braspag_Request_Payment_Bs
 * Class responsible to request a Bank Slip Payment to API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Request_Payment_Bs
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request_Payment_Bs' ) ) {

    class WC_Checkout_Braspag_Request_Payment_Bs extends WC_Checkout_Braspag_Request {

        const METHOD_CODE = 'bs';

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

            if ( empty( $this->MerchantOrderId ) ) return;

            // Payment Data
            $data = $this->gateway->get_payment_method( $this::METHOD_CODE );

            $this->Payment = array(
                'Provider'          => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_provider' ),
                'Type'              => $data['code'],
                'Amount'            => (int) $order->get_total() * 100,
                'BoletoNumber'      => $order->get_order_number(),
                'Assignor'          => '',
                'Demonstrative'     => '',
                'Identification'    => '',
                'Instructions'      => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_bank_slip_instructions' ),
            );


            // Sanitization
            $this->Payment['BoletoNumber'] = $this->sanitize_numbers( $this->Payment['BoletoNumber'] );

            // Expiration Date
            $expiration_date = (int) $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_pay' );
            if ( $expiration_date ) {
                $expiration_date = ( $expiration_date === 1 ) ? '+ 1 day' : '+ ' . $expiration_date . ' days';
                $this->Payment['ExpirationDate'] = $this->sanitize_date( $expiration_date, 'Y-m-d' );
            }

            // Santander Data
            if ( $this->Payment['Provider'] === 'Santander2' ) {
                $this->Payment['NullifyDays'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_nullify_days' );
            }

            // Santander Data
            if ( $this->Payment['Provider'] === 'Bradesco2' ) {
                $this->Payment['DaysToFine'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_fine', 0 );
                $this->Payment['FineRate'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_fine_rate', 0 );
                $this->Payment['DaysToInterest'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_interest', 0 );
                $this->Payment['InterestRate'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_interest_rate', 0 );

                // Sanitization
                $this->Payment['DaysToFine'] = $this->sanitize_numbers( $this->Payment['DaysToFine'] );
                $this->Payment['DaysToInterest'] = $this->sanitize_numbers( $this->Payment['DaysToInterest'] );
                $this->Payment['FineRate'] = $this->sanitize_number( $this->Payment['FineRate'], 5 );
                $this->Payment['InterestRate'] = $this->sanitize_number( $this->Payment['InterestRate'], 5 );

                if ( empty( $this->Payment['FineRate'] ) ) {
                    $this->Payment['FineAmount'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_fine_amount', 0 );
                    $this->Payment['FineAmount'] = $this->sanitize_numbers( $this->Payment['FineAmount'] );
                }

                if ( empty( $this->Payment['InterestRate'] ) ) {
                    $this->Payment['InterestAmount'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_interest_amount', 0 );
                    $this->Payment['InterestAmount'] = $this->sanitize_numbers( $this->Payment['InterestAmount'] );
                }
            }

            // DEBUG
            $this->Payment['Assignor'] = "Empresa Teste";
            $this->Payment['Demonstrative'] = "Desmonstrative Teste";
            $this->Payment['ExpirationDate'] = "2019-02-25";

            /**
             * Action allow developers to change Address object
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment_' . $this::METHOD_CODE, $this, $order );
        }

        /**
         * Send payment request to API
         *
         * @since    1.0.0
         *
         * @param    array  $data
         * @return   array  $transaction A array with 'errors' key if some problem
         *                               happend or a "transaction" from Braspag if success.
         */
        public function do_request() {
            $transaction = parent::do_request();

            // If errors, return
            if ( ! empty( $transaction['errors'] ) ) {
                return $transaction;
            }

            // Add notice about error
            $payment = $transaction['Payment'] ?? [];

            $reason_code = $payment['ReasonCode'] ?? '';
            $message = WC_Checkout_Braspag_Messages::payment_error_message( $reason_code, true );

            throw new Exception( $message );
        }

        /**
         * Cancel the transaction
         *
         * @param  string $payment_id
         * @param  string $amount
         * @return bool If cancelled.
         */
        public function cancel_transaction( $payment_id, $amount ) {
            return false;
        }

        /**
         * Check a $transaction->Payment node is equal to the current request
         *
         * @param    array  $payment_data
         */
        public function is_equal_payment( $payment_data ) {
            if ( $payment_data['Type'] != $this->Payment['Type'] ) return false;
            if ( $payment_data['Provider'] != $this->Payment['Provider'] ) return false;
            if ( $payment_data['Amount'] != $this->Payment['Amount'] ) return false;
            if ( $payment_data['BoletoNumber'] != $this->Payment['BoletoNumber'] ) return false;

            return true;
        }

    }

}