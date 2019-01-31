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
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Request' ) ) {

    class WC_Checkout_Braspag_Request extends WC_Checkout_Braspag_Model {

        /**
         * The gateway to be used
         */
        protected $gateway;

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
         * Send request to API
         *
         * @since    1.0.0
         *
         * @param    array  $data
         */
        public function do_request() {
            $errors = $this->validate();

            return array(
                'errors'    => $errors,
                'data'      => '',
                'url'       => '',
            );
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
            if ( ! $order instanceof WC_Order ) return;

            $this->MerchantOrderId  = $order->get_id();
            $this->Customer         = new WC_Checkout_Braspag_Customer( $order );
        }

        /**
         * Get a class name based on identifier type
         */
        public static function get_request_class( $identifier ) {
            $identifier = str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $identifier ) ) );

            return 'WC_Checkout_Braspag_Request_' . $identifier;
        }

    }

}
