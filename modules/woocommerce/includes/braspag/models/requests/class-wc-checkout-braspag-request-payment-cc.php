<?php

/**
 * WC_Checkout_Braspag_Request_Payment_Cc
 * Class responsible to request a Credit Card Payment to API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Request_Payment_Cc
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request_Payment_Cc' ) ) {

    class WC_Checkout_Braspag_Request_Payment_Cc extends WC_Checkout_Braspag_Request {

        const METHOD_CODE = 'cc';

        /**
         * Card Node to be populated in JSON
         * @var string
         */
        protected $card_node = 'CreditCard';

        /**
         * Populate data.
         *
         * @see WC_Order()
         * @since    1.0.0
         *
         * @param    WC_Order  $data
         *
         * @SuppressWarnings(PHPMD.NPathComplexity)
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         */
        public function populate( $order ) {
            parent::populate( $order );

            // Check for order
            if ( empty( $order->get_id() ) ) {
                throw new Exception( __( 'There was a problem with your payment. Please try again.', WCB_TEXTDOMAIN ) );
            }

            // Payment Data
            $data = $this->gateway->get_payment_method( $this::METHOD_CODE );

            $provider = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_provider' );
            $provider = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_provider', $provider, $this->gateway );

            $payment = [
                'Provider'         => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $provider,
                'Type'             => $data['code'],
                'Amount'           => ( (float) $order->get_total() ) * 100,
                'ServiceTaxAmount' => 0,
                'Installments'     => (int) $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_installments', 0 ),
                'SoftDescriptor'   => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_soft_description' ),
                'Capture'          => ( $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_auto_capture', 'no' ) === 'yes' ),
                'Interest'         => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_interest' ),
                'Credentials'      => [
                    'Code' => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_code' ),
                    'Key'  => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_key' ),
                ],
            ];

            $this->Payment = array_merge( ( empty( $this->Payment ) ? [] : $this->Payment ), $payment );

            // Make Interest ByMerchant default and validate
            if ( $this->Payment['Interest'] !== 'ByIssuer' ) {
                $this->Payment['Interest'] = 'ByMerchant';
            }

            // Getnet require Credentials Username and Password
            if ( $this->Payment['Provider'] === 'Getnet' ) {
                $this->Payment['Credentials']['Username'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_username' );
                $this->Payment['Credentials']['Password'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_password' );
            }

            // GlobalPayments require Credentials Signature
            if ( $this->Payment['Provider'] === 'GlobalPayments' ) {
                $this->Payment['Credentials']['Signature'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_signature_for_global_payments' );
            }

            // Safra require Credentials Signature
            if ( $this->Payment['Provider'] === 'Safra' ) {
                $this->Payment['Credentials']['Signature'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_signature_for_safra' );
            }

            if ( $this->Payment['Provider'] === 'Safra2' ) {
                $this->Payment['Credentials']['Signature'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_credential_signature_for_safra2' );
            }

            // Braspag accept empty credentials if it's configured on merchant
            if ( empty( $this->Payment['Credentials']['Code'] ) && empty( $this->Payment['Credentials']['Key'] ) ) {
                unset( $this->Payment['Credentials'] );
            }

            // CreditCard Data
            $this->Payment[ $this->card_node ] = array(
                'CardNumber'     => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_number' ),
                'Holder'         => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_holder' ),
                'ExpirationDate' => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_expiration_date' ),
                'SecurityCode'   => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_security_code' ),
                'Brand'          => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_brand' ),
            );

            // Try to find Brand
            if ( empty( $this->Payment[ $this->card_node ]['Brand'] ) && $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_find_brand' ) === 'yes' ) {
                $this->Payment[ $this->card_node ]['Brand'] = $this->find_brand_by_card_number( $this->Payment[ $this->card_node ]['CardNumber'] );
            }

            // Save card
            if ( $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_save_card', 'no' ) === 'yes' ) {
                $alias = 'saved-on-order-' . $order->get_id();
                $alias = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_save_card_alias', $alias, $order, $this );

                $this->Payment[ $this->card_node ]['SaveCard'] = true;
                $this->Payment[ $this->card_node ]['Alias'] = $alias;
            }

            // Try to convert any month/year format to Y-m-d before to try sanitize
            $this->Payment[ $this->card_node ]['ExpirationDate'] = explode( '/', $this->Payment[ $this->card_node ]['ExpirationDate'] );
            $this->Payment[ $this->card_node ]['ExpirationDate'] = ( $this->Payment[ $this->card_node ]['ExpirationDate'][1] ?? '' ) . '-' . $this->Payment[ $this->card_node ]['ExpirationDate'][0] . '-01';

            // Sanitization
            $this->Payment[ $this->card_node ]['CardNumber']     = $this->sanitize_numbers( $this->Payment[ $this->card_node ]['CardNumber'] );
            $this->Payment[ $this->card_node ]['SecurityCode']   = $this->sanitize_numbers( $this->Payment[ $this->card_node ]['SecurityCode'] );
            $this->Payment[ $this->card_node ]['ExpirationDate'] = $this->sanitize_date( $this->Payment[ $this->card_node ]['ExpirationDate'], 'm/Y' );

            /**
             * Action allow developers to change request data
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment_' . $this::METHOD_CODE, $this, $order );
        }

        /**
         * Validate data.
         * Return errors
         *
         * @since    1.0.0
         *
         * @param    array  $errors
         */
        public function validate() {
            $errors = parent::validate();

            // Installments
            if ( empty( $this->Payment['Installments'] ) ) {
                $errors[] = __( 'Please, choose your installments.', WCB_TEXTDOMAIN );
            }

            // Card Data
            $card_data = array(
                'CardNumber'     => __( 'Please fill the card number.', WCB_TEXTDOMAIN ),
                'Holder'         => __( 'Please fill the card holder name.', WCB_TEXTDOMAIN ),
                'ExpirationDate' => __( 'Please fill the card expiration date.', WCB_TEXTDOMAIN ),
                'SecurityCode'   => __( 'Please fill the card security code.', WCB_TEXTDOMAIN ),
                'Brand'          => __( 'Please fill the card brand.', WCB_TEXTDOMAIN ),
            );

            foreach ( $card_data as $field => $error ) {
                if ( ! empty( $this->Payment[ $this->card_node ][ $field ] ) ) {
                    continue;
                }

                $errors[] = $error;
            }

            return $errors;
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

            // If captured, let's continue
            $payment = $transaction['Payment'] ?? [];

            if ( ! empty( $payment['CapturedDate'] ) ) {
                return $transaction;
            }

            // Finish the request
            return $this->finish_request( $transaction );
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

            // Authorized should capture
            if ( (int) $status === WC_Checkout_Braspag_Api::TRANSACTION_STATUS_AUTHORIZED ) {
                // Capture
                $response = $this->capture_transaction( $transaction );

                // Log Capture
                $payment_id = $transaction['Payment']['PaymentId'];
                $this->gateway->log( 'Payment ' . $payment_id . ' was captured.' );

                // Update Payment from Capture
                $response = json_decode( $response );
                foreach ( $response as $key => $value ) {
                    if ( isset( $transaction['Payment'][ $key ] ) ) {
                        $transaction['Payment'][ $key ] = $value;
                    }
                }

                return $transaction;
            }

            // Pending should not be captured or be a error
            if ( (int) $status === WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PENDING ) {
                return $transaction;
            }

            // Other cases we throw to create a notice
            $reason_code = $payment['ReasonCode'] ?? '';
            $message     = WC_Checkout_Braspag_Messages::payment_error_message( $reason_code, true );

            throw new Exception( $message );
        }

        /**
         * Capture the transaction
         *
         * @link https://braspag.github.io/manual/braspag-pagador?shell#requisi%C3%A7%C3%A3o13
         * @since    1.0.0
         *
         * @param    array  $data
         * @return   void
         */
        public function capture_transaction( $transaction ) {
            if ( empty( $transaction['Payment'] ) ) {
                throw new Exception();
            }

            // Get PaymentId
            $payment_id = $transaction['Payment']['PaymentId'];

            /**
             * Filter endpoint to capture a transaction
             * You can use it to add 'Amount' or 'ServiceTaxAmount' to URL
             *
             * @var string  $endpoint
             */
            $endpoint = $this->gateway->api->get_endpoint_api() . $this::TRANSACTION_ENDPOINT . $payment_id . '/capture';
            $endpoint = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_capture_transaction_endpoint', $endpoint );

            // PUT Request
            $result = $this->gateway->api->make_put_request( $endpoint );

            // If Captured, return
            $response_code = (int) $result['response']['code'];
            if ( $response_code === WC_Checkout_Braspag_Api::STATUS_RESPONSE_OK ) {
                return $result['body'];
            }

            // Log
            $error = $response_code . ' ' . ( $result['response']['message'] ?? '' );
            $error = 'Payment ' . $payment_id . ' was not captured: ' . $error;
            $this->gateway->log( $error );

            /**
             * Action after try to capture the transaction
             *
             * Note: It do not trigger if Auto Capture is true.
             */
            $not_captured_action = 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_not_captured';
            do_action( $not_captured_action, $transaction );

            // If there's no action, we throw the standard message
            if ( has_action( $not_captured_action ) ) {
                throw new Exception( $error );
            }
        }

        /**
         * Cancel the transaction
         *
         * @link https://braspag.github.io/manual/braspag-pagador?shell#requisi%C3%A7%C3%A3o26
         *
         * @param  string $payment_id
         * @param  string $amount
         * @return bool If cancelled.
         */
        public function cancel_transaction( $payment_id, $amount ) {
            /**
             * Filter endpoint to cancel a transaction
             *
             * @var string  $endpoint
             */
            $endpoint = $this->gateway->api->get_endpoint_api() . $this::TRANSACTION_ENDPOINT . $payment_id . '/void?amount=' . $amount;
            $endpoint = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_cancel_transaction_endpoint', $endpoint );

            // PUT Request
            $result = $this->gateway->api->make_put_request( $endpoint );

            return ( (int) $result['response']['code'] === WC_Checkout_Braspag_Api::STATUS_RESPONSE_OK );
        }

        /**
         * Check a $transaction->Payment node is equal to the current request
         *
         * @param    array  $payment_data
         */
        public function is_equal_payment( $payment_data ) {
            if (
                (string) $payment_data['Type'] !== (string) $this->Payment['Type']
                || (string) $payment_data['Provider'] !== (string) $this->Payment['Provider']
                || (string) $payment_data['Amount'] !== (string) $this->Payment['Amount']
                || (string) $payment_data['Installments'] !== (string) $this->Payment['Installments']
            ) {
                return false;
            }

            $card_number      = $payment_data[ $this->card_node ]['CardNumber'];
            $this_card_number = $this->Payment[ $this->card_node ]['CardNumber'];

            if ( substr( $card_number, 0, 4 ) !== substr( $this_card_number, 0, 4 ) ) {
                return false;
            }
            if ( substr( $card_number, -3 ) !== substr( $this_card_number, -3 ) ) {
                return false;
            }

            return true;
        }

        /**
         * Finds a brand by card number.
         *
         * @param      string  $number
         * @return     string
         */
        public function find_brand_by_card_number( $number ) {
            $brand = WC_Checkout_Braspag_Credit_Card_Brand::find_brand( $number );

            if ( empty( $brand ) && $this->gateway->is_sandbox ) {
                $brand = WC_Checkout_Braspag_Credit_Card_Brand::SANDBOX;
            }

            return $brand;
        }

    }

}
