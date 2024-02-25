<?php

/**
 * WC_Checkout_Braspag_Customer
 * Class responsible to create a Customer
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Customer
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Customer' ) ) {

    class WC_Checkout_Braspag_Customer extends WC_Checkout_Braspag_Model {

        /**
         * @var mixed
         * @since PHP 8.2
         */
        public $Address;
        public $Birthdate;
        public $DeliveryAddress;
        public $Email;
        public $Identity;
        public $IdentityType;
        public $Name;

        /**
         * Populate data.
         *
         * @see WC_Order()
         * @since    1.0.0
         *
         * @param    WC_Order  $data
         *
         * phpcs:ignore
         */
        public function populate( $order ) {
            if ( ! $order instanceof WC_Order ) {
                return;
            }

            $this->Name            = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
            $this->Email           = $order->get_billing_email();
            $this->Birthdate       = '';
            $this->Identity        = '';
            $this->Address         = new WC_Checkout_Braspag_Address( $order, 'billing' );
            $this->DeliveryAddress = new WC_Checkout_Braspag_Address( $order, 'shipping' );

            // Data from Extra Fields (meta data)
            $person_type  = $this->sanitize_post_text_field( 'billing_persontype' );
            $billing_cpf  = $this->sanitize_post_text_field( 'billing_cpf' );
            $billing_cnpj = $this->sanitize_post_text_field( 'billing_cnpj' );

            // If "person type" is not selectable
            if ( empty( $person_type ) || ! in_array( $person_type, [ '1', '2' ], true ) ) {
                $person_type = ! empty( $billing_cpf ) ? '1' : '2';
            }

            // PF
            if ( (string) $person_type === '1' ) {
                $this->Identity     = $billing_cpf;
                $this->IdentityType = ( ! empty( $this->Identity ) ) ? 'CPF' : '';
            }

            // PJ
            if ( (string) $person_type === '2' ) {
                $this->Identity     = $billing_cnpj;
                $this->IdentityType = ( ! empty( $this->Identity ) ) ? 'CNPJ' : '';
            }

            // Sanitization
            $this->Birthdate = $this->sanitize_date( $this->Birthdate );
            $this->Identity  = $this->sanitize_numbers( $this->Identity );

            /**
             * Action allow developers to change Customer object
             *
             * @param obj  $this
             * @param obj  $order WC_Order
             */
            do_action( 'wc_checkout_braspag_populate_customer', $this, $order );
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
            $errors = [];

            $fields = array(
                'Name' => __( 'Please fill the customer name.', WCB_TEXTDOMAIN ),
            );

            foreach ( $fields as $field => $error ) {
                if ( ! empty( $this->$field ) ) {
                    continue;
                }

                $errors[] = $error;
            }

            return $errors;
        }

    }

}
