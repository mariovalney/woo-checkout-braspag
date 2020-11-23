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
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

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
                'Provider'       => ( $this->gateway->is_sandbox ) ? WC_Checkout_Braspag_Providers::SANDBOX : $provider,
                'Type'           => $data['code'],
                'Amount'         => ( (float) $order->get_total() ) * 100,
                'Assignor'       => '',
                'Demonstrative'  => '',
                'Identification' => '',
                'Instructions'   => $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_bank_slip_instructions' ),
            ];

            $this->Payment = array_merge( ( empty( $this->Payment ) ? [] : $this->Payment ), $payment );

            /**
             * Filter bank slip number
             *
             * @param string $bank_slip_number
             * @param WC_Order $order
             * @param WC_Checkout_Braspag_Request_Payment_Bs $this
             *
             * @var string
             */
            $bank_slip_number = apply_filters( 'wc_checkout_braspag_bank_slip_number', '', $order, $this );
            if ( $bank_slip_number ) {
                $this->Payment['BoletoNumber'] = $this->sanitize_numbers( $this->Payment['BoletoNumber'] );
            }

            // Expiration Date
            $expiration_date = (int) $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_pay' );
            if ( $expiration_date ) {
                $expiration_date                 = ( $expiration_date === 1 ) ? '+ 1 day' : '+ ' . $expiration_date . ' days';
                $this->Payment['ExpirationDate'] = $this->sanitize_date( $expiration_date, 'Y-m-d' );
            }

            // Santander Data
            if ( $this->Payment['Provider'] === 'Santander2' ) {
                $this->Payment['NullifyDays'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_nullify_days' );
            }

            // Bradesco Data
            if ( $this->Payment['Provider'] === 'Bradesco2' ) {
                $this->Payment['DaysToFine']     = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_fine', 0 );
                $this->Payment['FineRate']       = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_fine_rate', 0 );
                $this->Payment['DaysToInterest'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_days_to_interest', 0 );
                $this->Payment['InterestRate']   = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_interest_rate', 0 );

                // Sanitization
                $this->Payment['DaysToFine']     = $this->sanitize_numbers( $this->Payment['DaysToFine'] );
                $this->Payment['DaysToInterest'] = $this->sanitize_numbers( $this->Payment['DaysToInterest'] );
                $this->Payment['FineRate']       = $this->sanitize_number( $this->Payment['FineRate'], 5 );
                $this->Payment['InterestRate']   = $this->sanitize_number( $this->Payment['InterestRate'], 5 );

                if ( empty( $this->Payment['FineRate'] ) ) {
                    $this->Payment['FineAmount'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_fine_amount', 0 );
                    $this->Payment['FineAmount'] = $this->sanitize_numbers( $this->Payment['FineAmount'] );
                }

                if ( empty( $this->Payment['InterestRate'] ) ) {
                    $this->Payment['InterestAmount'] = $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_interest_amount', 0 );
                    $this->Payment['InterestAmount'] = $this->sanitize_numbers( $this->Payment['InterestAmount'] );
                }
            }

            // Prefer company name
            $prefer_company = ( $this->gateway->get_option( 'method_' . $this::METHOD_CODE . '_prefer_company', 'no' ) === 'yes' );
            if ( $prefer_company && ! empty( $this->Customer ) && $this->Customer->IdentityType === 'CNPJ' ) {
                $this->Customer->Name = $order->get_billing_company() ?: $this->Customer->Name;
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
            if (
                (string) $payment_data['Type'] !== (string) $this->Payment['Type']
                || (string) $payment_data['Provider'] !== (string) $this->Payment['Provider']
                || (string) $payment_data['Amount'] !== (string) $this->Payment['Amount']
                || (string) $payment_data['BoletoNumber'] !== (string) $this->Payment['BoletoNumber']
            ) {
                return false;
            }

            return true;
        }

    }

}
