<?php

/**
 * WC_Checkout_Braspag_Gateway
 * Class responsible to manage all WooCommerce stuff
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Gateway
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Gateway' ) ) {

    class WC_Checkout_Braspag_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            // Required infos
            $this->id                   = 'checkout-braspag';
            $this->icon                 = apply_filters( 'wc_checkout_braspag_icon', WCB_PLUGIN_URL . '/modules/woocommerce/assets/images/braspag.png' );
            $this->method_title         = __( 'Checkout Braspag', WCB_TEXTDOMAIN );
            $this->method_description   = __( 'Accept payments by credit card, debit card, online debit or banking billet using the Checkout Braspag.', WCB_TEXTDOMAIN );

            // Has fields
            $this->has_fields = true;

            // Load the form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Options
            $this->title           = $this->get_option( 'title' );
            // $this->description     = $this->get_option( 'description' );
            // $this->merchant_id     = $this->get_option( 'merchant_id' );
            // $this->antifraud       = $this->get_option( 'antifraud' );
            // $this->cc_authorized   = $this->get_option( 'cc_authorized' );
            // $this->send_only_total = $this->get_option( 'send_only_total' );
            // $this->debug           = $this->get_option( 'debug' );

            // Hooks
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled'   => array(
                    'type'    => 'checkbox',
                    'title'   => __( 'Enable/Disable', WCB_TEXTDOMAIN ),
                    'label'   => __( 'Enable Checkout Braspag', WCB_TEXTDOMAIN ),
                    'default' => 'no',
                ),
                'title'     => array(
                    'type'        => 'text',
                    'title'       => __( 'Title', WCB_TEXTDOMAIN ),
                    'description' => __( 'Title of payment method to user.', WCB_TEXTDOMAIN ),
                    'default'     => __( 'Braspag', WCB_TEXTDOMAIN ),
                ),
                'description' => array(
                    'type'        => 'textarea',
                    'title'       => __( 'Description', WCB_TEXTDOMAIN ),
                    'description' => __( 'User will see this description during checkout.', WCB_TEXTDOMAIN ),
                    'default'     => __( 'Pay with credit card, debit card, online debit or banking billet.', WCB_TEXTDOMAIN ),
                ),
            );
        }

    }

}