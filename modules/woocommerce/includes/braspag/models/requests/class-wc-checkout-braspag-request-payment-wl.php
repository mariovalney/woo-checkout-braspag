<?php

/**
 * WC_Checkout_Braspag_Request_Payment_Wl
 * Class responsible to request a Wallet Payment to API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Request_Payment_Wl
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request_Payment_Wl' ) ) {

    class WC_Checkout_Braspag_Request_Payment_Wl extends WC_Checkout_Braspag_Request {

        const METHOD_CODE = 'wl';

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
                'Provider'     => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $provider,
                'Capture'      => true,
                'Type'         => 'CreditCard',
                'Amount'       => ( (float) $order->get_total() ) * 100,
                'Installments' => (int) $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_installments', 0 ),
            ];

            $this->Payment = array_merge( ( empty( $this->Payment ) ? [] : $this->Payment ), $payment );

            // E-Wallet Data
            $valid_wallets = $data['providers'][ $provider ] ?? [];
            $valid_wallets = $valid_wallets['wallets'] ?? [];

            $wallet_code = $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_type' );
            if ( empty( $wallet_code ) || ! in_array( $wallet_code, $valid_wallets, true ) ) {
                throw new Exception( __( 'This e-wallet is not valid. Please try again.', WCB_TEXTDOMAIN ) );
            }

            $walletkey = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_' . strtolower( $wallet_code ) . '_walletkey' );
            $walletkey = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_walletkey', $walletkey, $wallet_code, $this->gateway );

            $this->Payment['Wallet'] = array(
                'Type'      => $wallet_code,
                'WalletKey' => $walletkey,
            );

            // Override WalletKey from Request
            $walletkey = $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_walletkey' );
            if ( ! empty( $walletkey ) ) {
                $this->Payment['Wallet']['WalletKey'] = $walletkey;
            }

            // Apple fields
            if ( $wallet_code === 'ApplePay' ) {
                $this->Payment['Wallet']['AdditionalData'] = array(
                    'EphemeralPublicKey' => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_token' ),
                );
            }

            // AndroidPay fields
            if ( $wallet_code === 'AndroidPay' ) {
                $this->Payment['Wallet']['AdditionalData'] = array(
                    'Signature' => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_token' ),
                );
            }

            // Masterpass fields
            if ( $wallet_code === 'Masterpass' ) {
                $this->Payment['Wallet']['AdditionalData'] = array(
                    'CaptureCode' => $this->sanitize_post_text_field( 'braspag_payment_' . $this::METHOD_CODE . '_token' ),
                );
            }

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

            // WalletKey
            if ( empty( $this->Payment['Wallet']['WalletKey'] ) ) {
                $errors[] = __( 'This e-wallet is not valid. Please try again.', WCB_TEXTDOMAIN );
            }

            $wallet_code = $this->Payment['Wallet']['Type'] ?? '';

            // Apple fields
            if ( $wallet_code === 'ApplePay' && empty( $this->Payment['Wallet']['AdditionalData']['EphemeralPublicKey'] ) ) {
                $errors[] = __( 'E-Wallet token is invalid.', WCB_TEXTDOMAIN );
            }

            // AndroidPay fields
            if ( $wallet_code === 'AndroidPay' && empty( $this->Payment['Wallet']['AdditionalData']['Signature'] ) ) {
                $errors[] = __( 'E-Wallet token is invalid.', WCB_TEXTDOMAIN );
            }

            // Masterpass fields
            if ( $wallet_code === 'Masterpass' && empty( $this->Payment['Wallet']['AdditionalData']['CaptureCode'] ) ) {
                $errors[] = __( 'E-Wallet token is invalid.', WCB_TEXTDOMAIN );
            }

            return $errors;
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
         *
         * @SuppressWarnings(PHPMD.CyclomaticComplexity)
         */
        public function is_equal_payment( $payment_data ) {
            if (
                (string) $payment_data['Type'] !== (string) $this->Payment['Type']
                || (string) $payment_data['Provider'] !== (string) $this->Payment['Provider']
                || (string) $payment_data['Amount'] !== (string) $this->Payment['Amount']
                || (string) $payment_data['Installments'] !== (string) $this->Payment['Installments']
                || (string) $payment_data['Wallet']['Type'] !== (string) $this->Payment['Wallet']['Type']
                || (string) $payment_data['Wallet']['WalletKey'] !== (string) $this->Payment['Wallet']['WalletKey']
            ) {
                return false;
            }

            if ( $payment_data['Wallet']['Type'] === 'ApplePay' ) {
                return (string) $payment_data['Wallet']['AdditionalData']['EphemeralPublicKey'] === (string) $this->Payment['Wallet']['AdditionalData']['EphemeralPublicKey'];
            }

            if ( $payment_data['Wallet']['Type'] === 'AndroidPay' ) {
                return (string) $payment_data['Wallet']['AdditionalData']['Signature'] === (string) $this->Payment['Wallet']['AdditionalData']['Signature'];
            }

            if ( $payment_data['Wallet']['Type'] === 'Masterpass' ) {
                return (string) $payment_data['Wallet']['AdditionalData']['CaptureCode'] === (string) $this->Payment['Wallet']['AdditionalData']['CaptureCode'];
            }

            return true;
        }

    }

}
