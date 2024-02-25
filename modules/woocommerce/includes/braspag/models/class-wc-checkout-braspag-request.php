<?php

/**
 * WC_Checkout_Braspag_Request
 * Class responsible to creat a request to Braspag API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Request
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request' ) ) {

    class WC_Checkout_Braspag_Request extends WC_Checkout_Braspag_Model {
        use WC_Checkout_Braspag_Traits_Extradata;

        /**
         * Method Code
         * Classes should override
         */
        const METHOD_CODE = '';

        /**
         * Endpoint to create a transation
         * Classes can override
         *
         * @link https://braspag.github.io/manual/braspag-pagador
         */
        const TRANSACTION_ENDPOINT = '/v2/sales/';

        /**
         * The gateway
         * @var WC_Checkout_Braspag_Gateway
         */
        protected $gateway;

        /**
         * @var mixed
         * @since PHP 8.2
         */
        public $Customer;
        public $MerchantOrderId;
        public $Payment;

        /**
         * Constructor
         *
         * @since    1.0.0
         *
         * @param    array      $data
         * @param    string     $gateway
         */
        public function __construct( $data, $gateway ) {
            $this->gateway = $gateway;
            $this->populate( $data );
        }

        /**
         * Populate data.
         *
         * @see WC_Order()
         * @since    1.0.0
         *
         * @param    WC_Order  $data
         */
        public function populate( $order ) {
            if ( ! $order instanceof WC_Order ) {
                throw new Exception( __( 'There was a problem with your payment. Please try again.', WCB_TEXTDOMAIN ) );
            }

            $this->MerchantOrderId = $order->get_id();
            $this->Customer        = new WC_Checkout_Braspag_Customer( $order );

            /**
             * Action allow developers to change request data
             *
             * @param obj  $this
             * @param obj  $order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_payment', $this, $order );
        }

        /**
         * Validate data.
         * If child override validation, we do not need to matter with constants.
         *
         * @since    1.0.0
         *
         * @param    array  $errors
         */
        public function validate() {
            if ( empty( $this::METHOD_CODE ) || empty( $this::TRANSACTION_ENDPOINT ) ) {
                return [ __( 'Invalid payment method.', WCB_TEXTDOMAIN ) ];
            }

            if ( empty( $this->Payment['Amount'] ) ) {
                return [ __( 'Invalid amount: your order is empty.', WCB_TEXTDOMAIN ) ];
            }

            // Validate Customer
            return $this->Customer->validate();
        }

        /**
         * Check a $transaction->Payment node is equal to the current request
         *
         * @param    array  $payment_data
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function is_equal_payment( $payment_data ) {
            die( 'All payment methods should override this method' );
        }

        /**
         * Send payment request to API
         * Expect exceptions: use inside try catch
         *
         * @since    1.0.0
         *
         * @param    array  $data
         * @return   array  $transaction A array with 'errors' key if some problem
         *                               happend or a "transaction" from Braspag if success.
         */
        public function do_request() {
            $errors = $this->validate();

            if ( ! empty( $errors ) ) {
                return [ 'errors' => $errors ];
            }

            return $this->post_transaction();
        }

        /**
         * Cancel transaction
         * For methods it don't accept canceling, return false
         *
         * @param  string $payment_id
         * @param  string $amount
         * @return bool If cancelled.
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function cancel_transaction( $payment_id, $amount ) {
            die( 'All payment methods should override this method' );
        }

        /**
         * Send request to API
         *
         * @since    1.0.0
         *
         * @param    array  $data
         */
        private function post_transaction() {
            /**
             * Filter endpoint to create a transaction
             *
             * @var string  $endpoint
             */
            $endpoint = $this->gateway->api->get_endpoint_api() . $this::TRANSACTION_ENDPOINT;
            $endpoint = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_transaction_endpoint', $endpoint );

            // Create WP Request
            $request = array(
                'method'  => 'POST',
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $this ),
            );

            /**
             * Filter request
             *
             * @var string  $request
             * @var obj     $this
             */
            $request = apply_filters( 'wc_checkout_braspag_request_payment_' . $this::METHOD_CODE . '_transaction_request', $request, $this );

            // Send the request
            $result = $this->gateway->api->make_request( $endpoint, $request );

            // Check for success
            $response = $result['response'] ?? [];
            $body     = json_decode( ( $result['body'] ?? '' ), true );

            // If the payment WAS CREATED
            if ( (int) $response['code'] === WC_Checkout_Braspag_Api::STATUS_RESPONSE_CREATED ) {
                return $body;
            }

            /**
             * If the payment WAS NOT CREATED
             * Braspag returns a array with a object
             */
            $body = (array) $body;
            $body = array_shift( $body );

            $code    = $body['Code'] ?? '';
            $message = $body['Message'] ?? '';

            /**
             * Check for duplicated code to treat the error
             *
             * Some Merchants do not allow duplicated payments to MerchantOrderId
             * (WC_Order->id) so we should cancel it before try again.
             */
            if ( (int) $code === WC_Checkout_Braspag_Api::ERROR_API_DUPLICATED ) {
                return $this->process_duplicated_payment();
            }

            /**
             * Check valid payment error message
             */
            $message = WC_Checkout_Braspag_Messages::payment_error_message( $code, ( $this::METHOD_CODE === 'cc' ), __( $message ) );

            /**
             * To be catched
             * @see WC_Checkout_Braspag_Api::do_payment_request()
             */
            throw new Exception( $message );
        }

        /**
         * Try to process a duplicated payment:
         * - If the payment is equal, try to continue
         * - If it's not equal, try to cancel
         *
         * @return   array  $transaction if it's the same or
         */
        private function process_duplicated_payment() {
            $default_error = __( 'You already tried to pay this order and we were not able to cancel the previous attempt. Please, try again or contact us.', WCB_TEXTDOMAIN );

            /**
             * Try to find all payments
             *
             * The duplicated (and successully created) should be the last
             * we can ignore others because they are not authorized
             */
            $api_query = new WC_Checkout_Braspag_Query( $this->gateway );
            $payments  = $api_query->get_sale_by_MerchantOrderId( $this->MerchantOrderId );

            // phpcs:disable
            usort( $payments, function( $payment_a, $payment_b ) {
                $date_a = strtotime( $payment_a['ReceveidDate'] );
                $date_b = strtotime( $payment_b['ReceveidDate'] );

                return $date_b - $date_a;
            } );
            // phpcs:enable

            // If not find any payment, alert error
            if ( empty( $payments[0]['PaymentId'] ) ) {
                throw new Exception( $default_error );
            }

            // Get transaction
            $transaction = $api_query->get_transaction( $payments[0]['PaymentId'] );

            if ( empty( $transaction['Payment'] ) ) {
                throw new Exception( $default_error );
            }

            // If it's the same payment, return transaction
            if ( $this->is_equal_payment( $transaction['Payment'] ) ) {
                return $transaction;
            }

            // Try to cancel
            if ( $this->cancel_transaction( $transaction['Payment']['PaymentId'], $transaction['Payment']['Amount'] ) ) {
                throw new Exception( __( 'You already tried to pay this order. We are canceling this attempt so you can retry.', WCB_TEXTDOMAIN ) );
            }

            throw new Exception( $default_error );
        }

        /**
         * Get a class name based on identifier type
         *
         * @param  string
         * @return  string
         */
        public static function get_request_class( $identifier ) {
            $identifier = str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $identifier ) ) );

            return 'WC_Checkout_Braspag_Request_' . $identifier;
        }

    }

}
