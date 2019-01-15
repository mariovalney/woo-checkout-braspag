<?php

/**
 * WCB_Module_Woocommerce
 * Class responsible to manage all WooCommerce stuff
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WCB_Module_Woocommerce
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'WCB_Module_Woocommerce' ) ) {

    class WCB_Module_Woocommerce {

        /**
         * Define the core functionalities into plugin.
         *
         * @since    1.0.0
         * @param    Woo_Checkout_Braspag      $core   The Core object
         */
        public function run( Woo_Checkout_Braspag $core ) {
            $this->core = $core;

            if ( ! class_exists( 'WC_Payment_Gateway' ) || ! defined( 'WC_VERSION' ) || ! version_compare( WC_VERSION, '2.2', '>=' ) ) {
                $this->core->add_action( 'admin_notices', array( $this, 'dependencies_notices' ) );
                return;
            }

            $this->includes = array(
                'class-wc-checkout-braspag-gateway',
            );
        }

        /**
         * Action: 'admin_notices'
         * Missing something
         */
        public function dependencies_notices() {
            include_once 'includes/views/html-notice-woocommerce-missing.php';
        }

    }
}
