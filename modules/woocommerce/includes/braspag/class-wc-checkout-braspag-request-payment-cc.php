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
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request_Payment_Cc' ) ) {

    class WC_Checkout_Braspag_Request_Payment_Cc extends WC_Checkout_Braspag_Request {

        const METHOD_CODE = 'cc';

        const TRANSACTION_ENDPOINT = '/v2/sales/';

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
            $data = $this->gateway->get_payment_method( self::METHOD_CODE );

            $this->Payment = array(
                'Provider'          => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_provider' ),
                'Type'              => $data['code'],
                'Amount'            => (int) $order->get_total() * 100,
                'ServiceTaxAmount'  => 0,
                'Installments'      => (int) ( $_POST['braspag_payment_' . self::METHOD_CODE . '_installments'] ?? 0 ),
                'SoftDescriptor'    => $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_soft_description' ),
                'Capture'           => ( $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_auto_capture', 'no' ) === 'yes' ),
                'Credentials'       => array(
                    'Code'  => $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_credential_code' ),
                    'Key'   => $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_credential_key' ),
                ),
            );

            // Getnet require Credentials Username and Password
            if ( $this->Payment['Provider'] === 'Getnet' ) {
                $this->Payment['Provider']['Credentials']['Username'] = $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_credential_username' );
                $this->Payment['Provider']['Credentials']['Password'] = $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_credential_password' );
            }

            // GlobalPayments require Credentials Signature
            if ( $this->Payment['Provider'] === 'GlobalPayments' ) {
                $this->Payment['Provider']['Credentials']['Signature'] = $this->gateway->get_option( 'method_' . self::METHOD_CODE . '_credential_signature' );
            }

            // CreditCard Data
            $this->Payment['CreditCard'] = array(
                'CardNumber'        => $_POST['braspag_payment_' . self::METHOD_CODE . '_number'] ?? '',
                'Holder'            => $_POST['braspag_payment_' . self::METHOD_CODE . '_holder'] ?? '',
                'ExpirationDate'    => $_POST['braspag_payment_' . self::METHOD_CODE . '_expiration_date'] ?? '',
                'SecurityCode'      => $_POST['braspag_payment_' . self::METHOD_CODE . '_security_code'] ?? '',
                'Brand'             => $_POST['braspag_payment_' . self::METHOD_CODE . '_brand'] ?? '',
            );

            // Try to convert any month/year format to Y-m-d before to try sanitize
            $this->Payment['CreditCard']['ExpirationDate'] = explode( '/', $this->Payment['CreditCard']['ExpirationDate'] );
            $this->Payment['CreditCard']['ExpirationDate'] = ( $this->Payment['CreditCard']['ExpirationDate'][1] ?? '' ) . '-' . $this->Payment['CreditCard']['ExpirationDate'][0] . '-01';

            // Sanitization
            $this->Payment['CreditCard']['CardNumber'] = $this->sanitize_numbers( $this->Payment['CreditCard']['CardNumber'] );
            $this->Payment['CreditCard']['SecurityCode'] = $this->sanitize_numbers( $this->Payment['CreditCard']['SecurityCode'] );
            $this->Payment['CreditCard']['ExpirationDate'] = $this->sanitize_date( $this->Payment['CreditCard']['ExpirationDate'], 'm/Y' );

            /**
             * Action allow developers to change Address object
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment_' . self::METHOD_CODE, $this, $order );
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
            $errors = [];

            // Installments
            if ( empty( $this->Payment['Installments'] ) ) {
                $errors[] = __( 'Please, choose your installments.', WCB_TEXTDOMAIN );
            }

            // Card Data
            $card_data = array(
                'CardNumber'        => __( 'Please fill the card number.', WCB_TEXTDOMAIN ),
                'Holder'            => __( 'Please fill the card holder name.', WCB_TEXTDOMAIN ),
                'ExpirationDate'    => __( 'Please fill the card expiration date.', WCB_TEXTDOMAIN ),
                'SecurityCode'      => __( 'Please fill the card security code.', WCB_TEXTDOMAIN ),
                'Brand'             => __( 'Please fill the card brand.', WCB_TEXTDOMAIN ),
            );

            foreach ( $card_data as $field => $error ) {
                if ( ! empty( $this->Payment['CreditCard'][ $field ] ) ) continue;

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
         */
        public function do_request() {
            $transaction = parent::do_request();

            print_r( $transaction );
            exit(2);
        }

        /**
         * Cancel the transaction
         *
         * @since    1.0.0
         *
         * @param    array  $data
         */
        public function cancel_transaction( $payment_id, $amount ) {
            /**
             * Filter endpoint to cancel a transaction
             *
             * @var string  $endpoint
             */
            $endpoint = $this->gateway->api->get_endpoint_api() . $this::TRANSACTION_ENDPOINT . $payment_id . '/void?amount=' . $amount;
            $endpoint = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_cancel_transaction_endpoint', $endpoint );

            // Create WP Request
            $request = array(
                'method'    => 'PUT',
                'timeout'   => 30,
                'blocking'  => true,
                'headers'   => array(
                    'Content-Length'    => 0,
                    'MerchantId'        => $this->gateway->api->get_merchant_id(),
                    'MerchantKey'       => $this->gateway->api->get_merchant_key(),
                ),
            );

            $result = WC_Checkout_Braspag_Api::make_request( $endpoint, $request );

            return ( $result['response']['code'] === WC_Checkout_Braspag_Api::STATUS_RESPONSE_OK );
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
            if ( $payment_data['Installments'] != $this->Payment['Installments'] ) return false;

            $card_number = $payment_data['CreditCard']['CardNumber'];
            $this_card_number = $this->Payment['CreditCard']['CardNumber'];

            if ( substr( $card_number, 0, 4 ) != substr( $this_card_number, 0, 4 ) ) return false;
            if ( substr( $card_number, -3 ) != substr( $this_card_number, -3 ) ) return false;

            return true;
        }

    }

}
