<?php

/**
 * WC_Checkout_Braspag_Model
 * Model for Braspag classes
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Model
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Model' ) ) {

    class WC_Checkout_Braspag_Model implements JsonSerializable {

        /**
         * Constructor
         *
         * @since    1.0.0
         *
         * @param    array  $data
         */
        public function __construct( $data ) {
            $this->populate( $data );
        }

        /**
         * Populate data.
         * Override to create your objects
         *
         * @since    1.0.0
         *
         * @param    array  $data
         */
        public function populate( $data ) {
            return $data;
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
            return [];
        }

        /**
         * Json
         *
         * @since    1.0.0
         */
        public function jsonSerialize() {
            return $this;
        }

        /**
         * Sanitize: numbers only
         *
         * @since    1.0.0
         */
        public function sanitize_numbers( $string ) {
            return preg_replace( '/\D*/', '', $string );
        }

        /**
         * Sanitize: number (with format)
         *
         * @since    1.0.0
         */
        public function sanitize_number( $string, $decimals = 0 ) {
            $number = preg_replace('/[^\d.]*/', '', $string);
            $number = (float) $string;

            return number_format( $number, (int) $decimals, '.', '' );
        }

        /**
         * Sanitize: data to format
         *
         * @since    1.0.0
         */
        public function sanitize_date( $string, $format = 'Y-m-d' ) {
            if ( empty( $string ) ) return $string;

            return date( $format, strtotime( $string ) );
        }

    }

}
