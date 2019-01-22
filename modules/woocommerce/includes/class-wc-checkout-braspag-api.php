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

use Braspag\API\Merchant;
use Braspag\API\Environment;
use Braspag\API\Sale;
use Braspag\API\Braspag;
use Braspag\API\Payment;

if ( ! class_exists( 'WC_Checkout_Braspag_Api' ) ) {

    class WC_Checkout_Braspag_Api {

        /**
         * Merchant
         * @var Braspag\API\Merchant
         */
        private $merchant;

        /**
         * Environment
         * @var Braspag\API\Environment
         */
        private $environment;

        public function __construct( $merchant_id, $merchant_key, $is_sandbox = false ) {
            // Check we can use Braspag SDK
            if ( ! class_exists( 'Braspag\API\Merchant' ) ) return false;

            // Check we have necessary data
            if ( empty( $merchant_id ) || empty( $merchant_key ) ) return false;

            // SDK Configuration
            $this->merchant = new Merchant( $merchant_id, $merchant_key );
            $this->environment = ( $is_sandbox ) ? Environment::sandbox() : Environment::production();
        }

        /**
         * Check API has the necessary to start requesting
         *
         * @return bool
         */
        public function is_valid() {
            /**
             * Filter allow developers to disable gateway
             *
             * @param ! empty( $merchant )  bool
             * @param $merchant             Braspag\API\Merchant
             * @param $environment          Braspag\API\Environment
             *
             * @return bool
             */
            return apply_filters( 'wc_checkout_braspag_api_is_valid', ( ! empty( $this->merchant ) ), $this->merchant, $this->environment );
        }

    }

}
