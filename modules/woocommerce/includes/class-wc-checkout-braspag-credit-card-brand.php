<?php

/**
 * WC_Checkout_Braspag_Credit_Card_Brand
 * Class responsible to help credit card search
 *
 * @link https://en.wikipedia.org/wiki/Payment_card_number
 * @link https://github.com/erikhenrique/bin-cc
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Credit_Card_Brand
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Credit_Card_Brand' ) ) {

    class WC_Checkout_Braspag_Credit_Card_Brand {

        /**
         * The brand for SANDBOX provider
         *
         * @var        string
         */
        const SANDBOX = 'Visa';

        /**
         * Get Credit Card provider as option
         *
         * @return array
         */
        public static function find_brand( $number ) {
            return 'Visa';
        }

    }

}

