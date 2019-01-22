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

            // Has fields on Checkout ?
            $this->has_fields = false;

            // Load the form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // All Options
            $this->title                = $this->get_option( 'title' );
            $this->description          = $this->get_option( 'description' );
            $this->merchant_id          = $this->get_option( 'merchant_id' );
            $this->sandbox              = $this->get_option( 'sandbox' );
            $this->merchant_key         = $this->get_option( 'merchant_key' );
            $this->sandbox_merchant_key = $this->get_option( 'sandbox_merchant_key' );
            $this->debug                = $this->get_option( 'debug' );

            // Active Logs
            $this->log = ( 'yes' == $this->debug ) ? new WC_Logger() : false;

            // Start API
            $is_sandbox = ( 'yes' == $this->sandbox ) ? true : false;
            $merchant_key = ( $is_sandbox ) ? $this->sandbox_merchant_key : $this->merchant_key;

            $this->api = new WC_Checkout_Braspag_Api( $this->merchant_id, $merchant_key, $is_sandbox );

            // Register Hooks
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         */
        public function init_form_fields() {
            $merchant_id_description = sprintf(
                __( 'Please enter your Merchant ID. You can find it in %s.', WCB_TEXTDOMAIN ),
                '<a href="https://admin.braspag.com.br/Account/MyMerchants" target="_blank">' . __( 'Braspag Admin > My Merchants', WCB_TEXTDOMAIN ) . '</a>'
            );

            $merchant_key_description = sprintf(
                __( 'Please enter your Merchant Key. You received it after your register or you can enter in contact at %s.', WCB_TEXTDOMAIN ),
                '<a href="mailto:suporte@braspag.com.br">suporte@braspag.com.br</a>'
            );

            $debug_description = sprintf(
                __( 'Log Checkout Braspag events, such as API requests, you can check this log in %s.', WCB_TEXTDOMAIN ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', WCB_TEXTDOMAIN ) . '</a>'
            );

            $this->form_fields = array(
                'enabled'               => array(
                    'type'    => 'checkbox',
                    'title'   => __( 'Enable/Disable', WCB_TEXTDOMAIN ),
                    'label'   => __( 'Enable Checkout Braspag', WCB_TEXTDOMAIN ),
                    'default' => 'no',
                ),
                'title'                 => array(
                    'type'        => 'text',
                    'title'       => __( 'Title', WCB_TEXTDOMAIN ),
                    'description' => __( 'Title of payment method to user.', WCB_TEXTDOMAIN ),
                    'default'     => __( 'Braspag', WCB_TEXTDOMAIN ),
                ),
                'description'           => array(
                    'type'        => 'textarea',
                    'title'       => __( 'Description', WCB_TEXTDOMAIN ),
                    'description' => __( 'User will see this description during checkout.', WCB_TEXTDOMAIN ),
                    'default'     => __( 'Pay with credit card, debit card, online debit or banking billet.', WCB_TEXTDOMAIN ),
                ),
                'braspag_section'       => array(
                    'type'        => 'title',
                    'title'       => __( 'Braspag Settings', WCB_TEXTDOMAIN ),
                ),
                'merchant_id'           => array(
                    'type'              => 'text',
                    'title'             => __( 'Merchant ID', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_id_description,
                    'default'           => '',
                    'custom_attributes' => [ 'required' => 'required' ],
                ),
                'sandbox'               => array(
                    'type'              => 'checkbox',
                    'title'             => __( 'Braspag Sandbox', WCB_TEXTDOMAIN ),
                    'label'             => __( 'Enable Braspag Sandbox', WCB_TEXTDOMAIN ),
                    'desc_tip'          => true,
                    'default'           => 'no',
                    'description'       => __( 'You can use sandbox to test the payments (requires a sandbox Merchant ID).', WCB_TEXTDOMAIN ),
                ),
                'merchant_key'          => array(
                    'type'              => 'text',
                    'title'             => __( 'Merchant Key', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_key_description,
                    'custom_attributes' => [ 'data-condition' => '!woocommerce_checkout-braspag_sandbox' ],
                ),
                'sandbox_merchant_key'  => array(
                    'type'              => 'text',
                    'title'             => __( 'Sandbox Merchant Key', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_key_description,
                    'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_sandbox' ],
                ),
                'debug_section'         => array(
                    'type'  => 'title',
                    'title' => __( 'Log Settings', WCB_TEXTDOMAIN ),
                ),
                'debug'                 => array(
                    'type'          => 'checkbox',
                    'title'         => __( 'Debug Log', WCB_TEXTDOMAIN ),
                    'label'         => __( 'Enable logging', WCB_TEXTDOMAIN ),
                    'description'   => $debug_description,
                    'default'       => 'no',
                ),
            );
        }

        /**
         * Action 'admin_enqueue_scripts'
         * Enqueue scripts for gateway settings page.
         *
         * No need to validate admin page as WooCommerce says:
         * "Gateways are only loaded when needed, such as during checkout and on the settings page in admin"
         *
         * @link https://docs.woocommerce.com/document/payment-gateway-api/#section-8
         */
        public function enqueue_scripts() {
            $script_url = WCB_PLUGIN_URL . '/modules/woocommerce/assets/js/scripts';
            $script_url .= ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js';

            wp_enqueue_script( $this->id . '-script', $script_url, [ 'jquery', 'underscore' ], WCB_VERSION, true );
        }

    }

}
