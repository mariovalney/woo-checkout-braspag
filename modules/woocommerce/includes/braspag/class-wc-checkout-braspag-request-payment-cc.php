<?php

/**
 * WC_Checkout_Braspag_Request_Payment_Cc
 * Class responsible to creat a request to Braspag API
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
            $data = $this->gateway->get_payment_method( 'cc' );

            $this->Payment = array(
                'Provider'          => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $this->gateway->get_option( 'method_cc_provider' ),
                'Type'              => $data['code'],
                'Amount'            => (int) $order->get_total() * 100,
                'ServiceTaxAmount'  => 0,
                'Installments'      => (int) ( $_POST['braspag_payment_installments'] ?? 0 ),
                'SoftDescriptor'    => $this->gateway->get_option( 'method_cc_soft_description' ),
                'Credentials'       => array(
                    'Code'  => $this->gateway->get_option( 'method_cc_credential_code' ),
                    'Key'   => $this->gateway->get_option( 'method_cc_credential_key' ),
                ),
            );

            // Getnet require Credentials Username and Password
            if ( $this->Payment['Provider'] === 'Getnet' ) {
                $this->Payment['Provider']['Credentials']['Username'] = $this->gateway->get_option( 'method_cc_credential_username' );
                $this->Payment['Provider']['Credentials']['Password'] = $this->gateway->get_option( 'method_cc_credential_password' );
            }

            // GlobalPayments require Credentials Signature
            if ( $this->Payment['Provider'] === 'GlobalPayments' ) {
                $this->Payment['Provider']['Credentials']['Signature'] = $this->gateway->get_option( 'method_cc_credential_signature' );
            }

            // CreditCard Data
            $this->CreditCard = array(
                'CardNumber'        => $_POST['braspag_payment_cc_number'] ?? '',
                'Holder'            => $_POST['braspag_payment_cc_holder'] ?? '',
                'ExpirationDate'    => $_POST['braspag_payment_cc_expiration_date'] ?? '',
                'SecurityCode'      => $_POST['braspag_payment_cc_security_code'] ?? '',
                'Brand'             => $_POST['braspag_payment_cc_brand'] ?? '',
            );

            /**
             * Action allow developers to change Address object
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment_cc', $this, $order );
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
                if ( ! empty( $this->CreditCard[ $field ] ) ) continue;

                $errors[] = $error;
            }

            return $errors;
        }

    }

}
