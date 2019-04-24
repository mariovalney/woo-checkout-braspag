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

            $this->Name            = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $this->Email           = $order->get_billing_email();
            $this->Birthdate       = '';
            $this->Identity        = '';
            $this->Address         = new WC_Checkout_Braspag_Address( $order, 'billing' );
            $this->DeliveryAddress = new WC_Checkout_Braspag_Address( $order, 'shipping' );

            // Data from Extra Fields (meta data)
            if ( ! empty( $_POST['billing_persontype'] ) ) {
                if ( (string) $_POST['billing_persontype'] === '2' ) {
                    $this->Identity     = $_POST['billing_cnpj'] ?? '';
                    $this->IdentityType = ( ! empty( $this->Identity ) ) ? 'CNPJ' : '';
                } else {
                    $this->Identity     = $_POST['billing_cpf'] ?? '';
                    $this->IdentityType = ( ! empty( $this->Identity ) ) ? 'CPF' : '';
                }
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

    }

}
