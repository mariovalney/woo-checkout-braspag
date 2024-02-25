<?php

/**
 * WC_Checkout_Braspag_Address
 * Class responsible to create a Customer
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Address
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Address' ) ) {

    class WC_Checkout_Braspag_Address extends WC_Checkout_Braspag_Model {

        /**
         * The order to be hooked
         */
        protected $order;

        /**
         * @var mixed
         * @since PHP 8.2
         */
        public $City;
        public $Complement;
        public $Country;
        public $District;
        public $Number;
        public $State;
        public $Street;
        public $ZipCode;

        /**
         * Constructor
         *
         * @see WC_Order
         * @since    1.0.0
         *
         * @param    array  $data
         */
        public function __construct( $order, $address_type ) {
            $this->order = $order;

            $data = $this->order->get_data();
            $data = ( empty( $data[ $address_type ] ) ) ? [] : $data[ $address_type ];

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
        public function populate( $address_data ) {
            $this->Street     = $address_data['address_1'] ?? '';
            $this->Complement = $address_data['address_2'] ?? '';
            $this->ZipCode    = $address_data['postcode'] ?? '';
            $this->City       = $address_data['city'] ?? '';
            $this->State      = $address_data['state'] ?? '';
            $this->Country    = $address_data['country'] ?? '';

            // Data from Extra Fields (meta data)
            $this->Number   = $this->sanitize_post_text_field( 'billing_number' );
            $this->District = $this->sanitize_post_text_field( 'billing_neighborhood' );

            // Sanitization
            $this->ZipCode = $this->sanitize_numbers( $this->ZipCode );

            /**
             * Action allow developers to change Address object
             *
             * @param obj  $this
             * @param obj  $this->order  WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_address', $this, $this->order );
        }

    }

}
