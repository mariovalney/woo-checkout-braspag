<?php

/**
 * WC_Checkout_Braspag_Api
 * Class responsible to request Braspag
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Api
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Api' ) ) {

    class WC_Checkout_Braspag_Api {

        /**
         * API Addresses
         * Starting with protocol and withou end slash
         *
         * @link https://braspag.github.io/manual/braspag-pagador#ambientes
         */
        const ENDPOINT_API = 'https://api.braspag.com.br';
        const ENDPOINT_API_QUERY = 'https://apiquery.braspag.com.br';
        const ENDPOINT_SANDBOX_API = 'https://apisandbox.braspag.com.br';
        const ENDPOINT_SANDBOX_API_QUERY = 'https://apiquerysandbox.braspag.com.br';

        /**
         * HTTP Codes
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#lista-de-http-status-code
         */
        const STATUS_RESPONSE_OK = '200';
        const STATUS_RESPONSE_CREATED = '201';

        /**
         * API Codes
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#c%C3%B3digos-de-erros-da-api
         */
        const ERROR_API_DUPLICATED = '302';

        /**
         * Merchant ID
         */
        private $merchant_id;

        /**
         * Merchant Key
         */
        private $merchant_key;

        /**
         * Endpoint API
         */
        private $endpoint_api;

        /**
         * Endpoint API QUERY
         */
        private $endpoint_api_query;

        /**
         * Set parameters and start API
         *
         * @return void
         */
        public function __construct( $merchant_id, $merchant_key, $is_sandbox = false ) {
            $this->merchant_id = $merchant_id;
            $this->merchant_key = $merchant_key;
            $this->endpoint_api = ( $is_sandbox ) ? self::ENDPOINT_SANDBOX_API : self::ENDPOINT_API;
            $this->endpoint_api_query = ( $is_sandbox ) ? self::ENDPOINT_SANDBOX_API_QUERY : self::ENDPOINT_API_QUERY;

            /**
             * Filter allow developers to change API endpoint
             *
             * @param $endpoint_api string
             *
             * @return string
             */
            $this->endpoint_api = apply_filters( 'wc_checkout_braspag_endpoint_api_url', $this->endpoint_api );

            /**
             * Filter allow developers to change API QUERY endpoint
             *
             * @param $endpoint_api_query string
             *
             * @return string
             */
            $this->endpoint_api_query = apply_filters( 'wc_checkout_braspag_endpoint_api_query_url', $this->endpoint_api_query );
        }

        /**
         * Check API has the necessary to start requesting
         *
         * @return bool
         */
        public function is_valid() {
            $is_valid = ( ! empty( $this->merchant_id ) );

            /**
             * Filter allow developers to disable gateway
             *
             * @param $is_valid     bool
             * @param $merchant_id  string
             * @param $merchant_key string
             *
             * @return bool
             */
            return apply_filters( 'wc_checkout_braspag_api_is_valid', $is_valid, $this->merchant_id, $this->merchant_key );
        }

        /**
         * Get Merchant Id
         *
         * @return string
         */
        public function get_merchant_id() {
            return $this->merchant_id;
        }

        /**
         * Get Merchant Key
         *
         * @return string
         */
        public function get_merchant_key() {
            return $this->merchant_key;
        }

        /**
         * Get endpoint API
         *
         * @return string
         */
        public function get_endpoint_api() {
            return $this->endpoint_api;
        }

        /**
         * Get endpoint API QUERY
         *
         * @return string
         */
        public function get_endpoint_api_query() {
            return $this->endpoint_api_query;
        }

        /**
         * Try to do a payment request
         *
         * @return array
         */
        public function do_payment_request( $method, $order, $gateway ) {
            // Check Payment Method
            if ( empty( $method ) ) {
                return $this->return_error( __( 'Please, select a payment method.', WCB_VERSION ) );
            }

            // Search for request class
            $class = WC_Checkout_Braspag_Request::get_request_class( 'payment_' . $method );
            if ( ! is_callable( array( $class, 'do_request' ) ) ) {
                return $this->return_error( __( 'Please, select a valid payment method.', WCB_VERSION ) );
            }

            // Request
            $request = new $class( $order, $gateway );

            try {
                $response = $request->do_request();

                if ( ! empty( $response['errors'] ) ) {
                    return $this->return_error( $response['errors'] );
                }

                // return $this->return_success( $url, $data );
            } catch (Exception $e) {
                return $this->return_error( $e->getMessage() );
            }

            return $this->return_error( __( 'Ops... Some problem happened. Please, try again in a few seconds.', WCB_VERSION ) );
        }

        /**
         * Return a array with success
         * @see WC_Checkout_Braspag_Api::do_payment_request()
         *
         * @return array
         */
        private function return_success( $url, $data = [] ) {
            return [ 'url' => $url, 'data' => $data ];
        }

        /**
         * Return a array with errors
         * @see WC_Checkout_Braspag_Api::do_payment_request()
         *
         * @return array
         */
        private function return_error( $error ) {
            return [ 'errors' => (array) $error ];
        }

        /**
         * Make a request
         *
         * @see wp_remote_request()
         */
        public static function make_request( $url, $args ) {
            $result = wp_remote_request( $url, $args );

            if ( ! is_wp_error( $result ) ) return $result;

            error_log( $result->get_error_message() );
            throw new Exception( __( 'Ops... We cannot connect to the server. Please, verify your internet connection.' ) );
        }

    }

}
