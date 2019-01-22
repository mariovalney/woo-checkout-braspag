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
         * @link https://braspag.github.io/manual/braspag-pagador
         */
        const ENDPOINT_API = 'https://api.braspag.com.br';
        const ENDPOINT_API_QUERY = 'https://apiquery.braspag.com.br';
        const ENDPOINT_SANDBOX_API = 'https://apisandbox.braspag.com.br';
        const ENDPOINT_SANDBOX_API_QUERY = 'https://apiquerysandbox.braspag.com.br';

        public function __construct( $merchant_id, $merchant_key, $is_sandbox = false ) {
            $this->merchant_id = $merchant_id;
            $this->merchant_key = $merchant_key;
            $this->endpoint_api = ( $is_sandbox ) ? self::ENDPOINT_SANDBOX_API : self::ENDPOINT_API;
            $this->endpoint_api_query = ( $is_sandbox ) ? self::ENDPOINT_SANDBOX_API_QUERY : self::ENDPOINT_API_QUERY;
        }

        /**
         * Check API has the necessary to start requesting
         *
         * @return bool
         */
        public function is_valid() {
            $is_valid = ( ! empty( $this->merchant_id ) && ! empty( $this->merchant_key ) );

            return apply_filters( 'wc_checkout_braspag_api_is_valid', $is_valid, $this->merchant_id, $this->merchant_key );
        }

    }

}
