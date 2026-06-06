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
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Api' ) ) {

    class WC_Checkout_Braspag_Api {

        /**
         * API Addresses
         * Starting with protocol and withou end slash
         *
         * @link https://braspag.github.io/manual/braspag-pagador#ambientes
         */
        const ENDPOINT_API               = 'https://api.braspag.com.br';
        const ENDPOINT_API_QUERY         = 'https://apiquery.braspag.com.br';
        const ENDPOINT_SANDBOX_API       = 'https://apisandbox.braspag.com.br';
        const ENDPOINT_SANDBOX_API_QUERY = 'https://apiquerysandbox.braspag.com.br';

        /**
         * HTTP Codes
         * Updated: 05-02-2019
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#lista-de-http-status-code
         */
        const STATUS_RESPONSE_OK      = 200;
        const STATUS_RESPONSE_CREATED = 201;

        /**
         * API Codes
         * Updated: 05-02-2019
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#c%C3%B3digos-de-erros-da-api
         */
        const ERROR_API_DUPLICATED = 302;

        /**
         * Transaction Status
         * Updated: 05-02-2019
         *
         * @link https://braspag.github.io/manual/braspag-pagador#lista-de-status-da-transa%C3%A7%C3%A3o
         */
        const TRANSACTION_STATUS_NOT_FINISHED      = 0;
        const TRANSACTION_STATUS_AUTHORIZED        = 1;
        const TRANSACTION_STATUS_PAYMENT_CONFIRMED = 2;
        const TRANSACTION_STATUS_DENIED            = 3;
        const TRANSACTION_STATUS_VOIDED            = 10;
        const TRANSACTION_STATUS_REFUNDED          = 11;
        const TRANSACTION_STATUS_PENDING           = 12;
        const TRANSACTION_STATUS_ABORTED           = 13;

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
         * The gateway
         * @var WC_Checkout_Braspag_Gateway
         */
        protected $gateway;

        /**
         * Set parameters and start API
         *
         * @return void
         */
        public function __construct( $merchant_id, $merchant_key, $gateway ) {
            $this->merchant_id  = $merchant_id;
            $this->merchant_key = $merchant_key;
            $this->gateway      = $gateway;

            // Endpoints
            $this->endpoint_api       = ( $this->gateway->is_sandbox ) ? self::ENDPOINT_SANDBOX_API : self::ENDPOINT_API;
            $this->endpoint_api_query = ( $this->gateway->is_sandbox ) ? self::ENDPOINT_SANDBOX_API_QUERY : self::ENDPOINT_API_QUERY;

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
         * @param string                      $method
         * @param WC_Order                    $order
         * @param WC_Checkout_Braspag_Gateway $gateway
         * @param array                       $data
         *
         * @return array
         */
        public function do_payment_request( $method, $order, $gateway, $data = array() ) {
            global $wccb_posted_data;

            if ( ! empty( $data ) ) {
                $wccb_posted_data = $data;
            }

            // Check Payment Method
            if ( empty( $method ) ) {
                return $this->return_error( __( 'Please, select a payment method.', WCB_TEXTDOMAIN ) );
            }

            // Search for request class
            $class = WC_Checkout_Braspag_Request::get_request_class( 'payment_' . $method );

            $this->gateway->log( sprintf( '[do_payment_request] method=%s class=%s', $method, $class ) );

            if ( ! class_exists( $class ) || ! method_exists( $class, 'do_request' ) ) {
                return $this->return_error( __( 'Please, select a valid payment method.', WCB_TEXTDOMAIN ) );
            }

            // Request
            $request = new $class( $order, $gateway );

            try {
                $response = $request->do_request();

                $this->gateway->log( sprintf( '[do_payment_request] raw response keys: %s', implode( ', ', array_keys( (array) $response ) ) ) );

                // Check for errors
                if ( ! empty( $response['errors'] ) ) {
                    $this->gateway->log( sprintf( '[do_payment_request] response errors: %s', implode( ' | ', (array) $response['errors'] ) ), 'error' );
                    return $this->return_error( $response['errors'] );
                }

                // It's a transaction ?
                if ( ! empty( $response['MerchantOrderId'] ) ) {
                    $this->gateway->log( sprintf( '[do_payment_request] transaction ok — MerchantOrderId=%s', $response['MerchantOrderId'] ) );
                    return $this->return_success( '', $response );
                }

                // We should redirect ?
                if ( ! empty( $response['url'] ) ) {
                    $this->gateway->log( sprintf( '[do_payment_request] redirect url=%s', $response['url'] ) );
                    $transaction = $response['transaction'] ?? array();
                    return $this->return_success( $response['url'], $transaction );
                }

                $this->gateway->log( '[do_payment_request] response did not match any expected shape', 'error' );
            } catch ( Exception $e ) {
                $message = $e->getMessage();

                $this->gateway->log( sprintf( '[do_payment_request] exception: %s', $message ), 'error' );

                if ( ! empty( $message ) ) {
                    return $this->return_error( $message );
                }
            }

            return $this->return_error( __( 'Ops... Some problem happened. Please, try again in a few seconds.', WCB_TEXTDOMAIN ) );
        }

        /**
         * Make a request
         *
         * @see wp_remote_request()
         */
        public function make_request( $url, $args = array() ) {
            // Default args
            $default = array(
                'method'      => 'GET',
                'timeout'     => apply_filters( 'wc_checkout_braspag_api_request_timeout', 30 ), // Filter timeout
                'blocking'    => true,
                'headers'     => array(),
                'httpversion' => '1.1',
            );

            if ( 'yes' === $this->gateway->override_user_agent ) {
                $default['user-agent'] = 'WooCheckoutBraspag/' . WCB_VERSION;
            }

            $args = wp_parse_args( $args, $default );

            // Required headers
            $args['headers']['MerchantId']  = $this->get_merchant_id();
            $args['headers']['MerchantKey'] = $this->get_merchant_key();

            // Make Request
            $result = wp_remote_request( $url, $args );

            $this->gateway->log(
                sprintf(
                    '[make_request] %s %s MerchantId=%s MerchantKey=%s body=%s',
                    $args['method'],
                    $url,
                    $this->get_merchant_id(),
                    $this->mask_credential( $this->get_merchant_key() ),
                    $this->sanitize_log( $args['body'] ?? '' )
                )
            );

            if ( ! is_wp_error( $result ) ) {
                $this->gateway->log(
                    sprintf(
                        '[make_request] response status=%s body=%s',
                        wp_json_encode( $result['response'] ?? '' ),
                        $this->sanitize_log( $result['body'] ?? '' )
                    )
                );

                return $result;
            }

            // Log request error
            $this->gateway->log( [ 'url' => $url, 'result' => $result ], 'error' ); // phpcs:ignore

            // Create a default connection message
            throw new Exception( __( 'Ops... We cannot connect to the server. Please, verify your internet connection.', WCB_TEXTDOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        /**
         * Make a PUT request
         *
         * @see wp_remote_request()
         */
        public function make_put_request( $url, $args = array() ) {
            if ( empty( $args['headers'] ) ) {
                $args['headers'] = array();
            }

            $args['method']                    = 'PUT';
            $args['headers']['Content-Length'] = 0;

            return $this->make_request( $url, $args );
        }

        /**
         * Mask sensitive fields in a JSON string before logging.
         */
        private function sanitize_log( $data ) {
            if ( ! is_string( $data ) ) {
                $data = wp_json_encode( $data );
            }
            $data = preg_replace( '/"CardNumber"\s*:\s*"(\d{6})\d+(\d{4})"/', '"CardNumber":"$1****$2"', $data );
            $data = preg_replace( '/"SecurityCode"\s*:\s*"\d+"/', '"SecurityCode":"***"', $data );
            $data = preg_replace( '/"Identity"\s*:\s*"(\d{3})\d+(\d{2})"/', '"Identity":"$1*****$2"', $data );
            $data = preg_replace( '/"MerchantKey"\s*:\s*"(\w{6})\w+(\w{4})"/', '"MerchantKey":"$1****$2"', $data );
            return $data;
        }

        /**
         * Mask a plain string credential: keep first 6 and last 4 chars.
         */
        private function mask_credential( $value ) {
            $len = strlen( $value );
            if ( $len <= 10 ) {
                return str_repeat( '*', $len );
            }
            return substr( $value, 0, 6 ) . '****' . substr( $value, -4 );
        }

        /**
         * Return a array with success
         * @see WC_Checkout_Braspag_Api::do_payment_request()
         *
         * @return array
         */
        private function return_success( $url, $transaction ) {
            return array(
                'url'         => $url,
                'transaction' => $transaction,
            );
        }

        /**
         * Return a array with errors
         * @see WC_Checkout_Braspag_Api::do_payment_request()
         *
         * @return array
         */
        private function return_error( $error ) {
            return array( 'errors' => (array) $error );
        }
    }

}
