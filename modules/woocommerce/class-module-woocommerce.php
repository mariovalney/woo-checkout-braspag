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
         * Run
         *
         * @since    1.0.0
         * @param    Woo_Checkout_Braspag      $core   The Core object
         */
        public function run( Woo_Checkout_Braspag $core ) {
            $this->core = $core;

            if ( ! defined( 'WCB_WOOCOMMERCE_TEMPLATES' ) ) {
                define( 'WCB_WOOCOMMERCE_TEMPLATES', WCB_PLUGIN_PATH . '/modules/woocommerce/includes/templates/' );
            }

            if ( ! class_exists( 'WC_Payment_Gateway' ) || ! defined( 'WC_VERSION' ) || ! version_compare( WC_VERSION, '2.2', '>=' ) ) {
                $this->core->add_action( 'admin_notices', array( $this, 'dependencies_notices' ) );
                return;
            }

            $this->includes = array(
                'class-wc-checkout-braspag-gateway',
                'class-wc-checkout-braspag-api',
                'class-wc-checkout-braspag-providers',
                'braspag/class-wc-checkout-braspag-model',
                'braspag/class-wc-checkout-braspag-customer',
                'braspag/class-wc-checkout-braspag-address',
                'braspag/class-wc-checkout-braspag-query',
                'braspag/class-wc-checkout-braspag-request',
                'braspag/class-wc-checkout-braspag-request-payment-cc',
                'braspag/class-wc-checkout-braspag-request-payment-dc',
                'braspag/class-wc-checkout-braspag-request-payment-bs',
            );
        }

        /**
         * Define hooks
         *
         * @since    1.0.0
         * @param    Woo_Checkout_Braspag      $core   The Core object
         */
        public function define_hooks() {
            if ( ! class_exists( 'WC_Checkout_Braspag_Gateway' ) ) return;
            if ( ! class_exists( 'WC_Checkout_Braspag_Api' ) ) return;

            $this->core->add_filter( 'woocommerce_payment_gateways', array( $this, 'add_woocommerce_gateway' ) );
            $this->core->add_filter( 'plugin_action_links_' . WCB_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
        }

        /**
         * Action: 'admin_notices'
         * Missing something from WooCommerce
         */
        public function dependencies_notices() {
            include_once WCB_PLUGIN_PATH . '/modules/woocommerce/includes/views/html-notice-woocommerce-missing.php';
        }

        /**
         * Filter: 'woocommerce_payment_gateways'
         * Add gateway to WooCommerce
         */
        public function add_woocommerce_gateway( $methods ) {
            $methods[] = 'WC_Checkout_Braspag_Gateway';

            return $methods;
        }

        /**
         * Filter: 'plugin_action_links_{plugin_file}'
         * Add Settings link to plugins dashboard
         */
        public function plugin_action_links( $links ) {
            $url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_checkout_braspag_gateway' );
            $plugin_links = [ '<a href="' . esc_url( $url ) . '">' . __( 'Settings', WCB_TEXTDOMAIN ) . '</a>' ];

            return array_merge( $plugin_links, $links );
        }

    }
}