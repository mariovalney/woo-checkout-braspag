<?php

/**
 * WCB_Module_Woocommerce
 * Class responsible to manage all WooCommerce stuff
 *
 * Depends: dependence
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WCB_Module_Woocommerce
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WCB_Module_Woocommerce' ) ) {

    class WCB_Module_Woocommerce {

        /**
         * Run
         *
         * @since    1.0.0
         */
        public function run() {
            $module = $this->core->get_module( 'dependence' );

            // Checking Dependences
            $module->add_dependence( 'woocommerce/woocommerce.php', 'WooCommerce', 'woocommerce' );

            if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '<' ) ) {
                $notice = __( 'Please update <strong>WooCommerce</strong>. The minimum supported version is 2.2.', WCB_TEXTDOMAIN );
                $module->add_dependence_notice( $notice );
            }

            // Return if WooCommerce is not found
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

            if ( ! defined( 'WCB_WOOCOMMERCE_TEMPLATES' ) ) {
                define( 'WCB_WOOCOMMERCE_TEMPLATES', WCB_PLUGIN_PATH . '/modules/woocommerce/includes/templates/' );
            }

            $this->includes = array(
                'class-wc-checkout-braspag-gateway',
                'class-wc-checkout-braspag-api',
                'class-wc-checkout-braspag-providers',
                'braspag/class-wc-checkout-braspag-model',
                'braspag/class-wc-checkout-braspag-messages',
                'braspag/traits/class-wc-checkout-braspag-extradata',
                'braspag/models/class-wc-checkout-braspag-customer',
                'braspag/models/class-wc-checkout-braspag-address',
                'braspag/models/class-wc-checkout-braspag-query',
                'braspag/models/class-wc-checkout-braspag-request',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-cc',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-dc',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-bs',
            );
        }

        /**
         * Define hooks
         *
         * @since    1.0.0
         * @param    Woo_Checkout_Braspag      $core   The Core object
         */
        public function define_hooks() {
            if ( ! class_exists( 'WC_Checkout_Braspag_Gateway' ) ) {
                return;
            }
            if ( ! class_exists( 'WC_Checkout_Braspag_Api' ) ) {
                return;
            }

            $this->core->add_filter( 'woocommerce_payment_gateways', array( $this, 'add_woocommerce_gateway' ) );
            $this->core->add_filter( 'plugin_action_links_' . WCB_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
            $this->core->add_filter( 'woocommerce_order_actions', array( $this, 'woocommerce_order_actions' ) );
            $this->core->add_action( 'woocommerce_order_action_checkout_braspag_update', array( $this, 'checkout_braspag_update' ) );
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
            $url          = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_checkout_braspag_gateway' );
            $plugin_links = [ '<a href="' . esc_url( $url ) . '">' . __( 'Settings', WCB_TEXTDOMAIN ) . '</a>' ];

            return array_merge( $plugin_links, $links );
        }

        /**
         * Filter 'woocommerce_order_actions'
         * Add actions to order dashboard
         *
         * @return array
         */
        public function woocommerce_order_actions( $actions ) {
            global $theorder;

            $gateway = $this->get_gateway_object();

            if ( ! empty( $gateway ) && ! empty( $theorder ) && $theorder->get_payment_method() === $gateway->id ) {
                $actions['checkout_braspag_update'] = __( 'Update payment info from Braspag', WCB_TEXTDOMAIN );
            }

            return $actions;
        }

        /**
         * Action 'woocommerce_order_action_checkout_braspag_update'
         * Process the action order on dashboard
         *
         * @return array
         */
        public function checkout_braspag_update( $order ) {
            $gateway = $this->get_gateway_object();

            if ( empty( $gateway ) || $order->get_payment_method() !== $gateway->id ) {
                return;
            }

            $payment = $order->get_meta( '_wc_braspag_payment_data' );

            if ( empty( $payment['PaymentId'] ) ) {
                return;
            }

            // Update Order from Payment
            $gateway->update_order_from_payment( $payment['PaymentId'] );
        }

        /**
         * Return the 'WC_Checkout_Braspag_Gateway' object if available
         *
         * @return WC_Checkout_Braspag_Gateway|false
         */
        private function get_gateway_object() {
            $gateways = WC()->payment_gateways();

            foreach ( $gateways->get_available_payment_gateways() as $available_gateway ) {
                if ( ! is_a( $available_gateway, 'WC_Checkout_Braspag_Gateway' ) ) {
                    continue;
                }

                return $available_gateway;
            }

            return false;
        }

    }

}
