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

        /**
         * Payment Methods
         *
         * Array of payment methods 'code' => array of data {
         *   name    string  Name of Payment Method
         *   type    string  Type sent to API
         *   enabled string  If we can use it
         * }
         *
         * This is the only data you need to change to manage payment options.
         *
         * @var array
         */
        private $payment_methods = array(
            'cc' => array(
                'name'      => 'Credit Card',
                'code'      => 'CreditCard',
                'enabled'   => false,
            ),
            'dc' => array(
                'name'      => 'Debit Card',
                'code'      => 'DebitCard',
                'enabled'   => false,
            ),
            'bt' => array(
                'name'      => 'Bank Ticket',
                'code'      => 'Boleto',
                'enabled'   => false,
            ),
            'et' => array(
                'name'      => 'Eletronic Transfer',
                'code'      => 'EletronicTransfer',
                'enabled'   => false,
            ),
        );

        public function __construct() {
            // Required infos
            $this->id                   = 'checkout-braspag';
            $this->icon                 = apply_filters( 'wc_checkout_braspag_icon', WCB_PLUGIN_URL . '/modules/woocommerce/assets/images/braspag.png' );
            $this->method_title         = __( 'Checkout Braspag', WCB_TEXTDOMAIN );
            $this->method_description   = __( 'Accept payments by credit card, debit card, online debit or banking billet using the Checkout Braspag.', WCB_TEXTDOMAIN );

            // Has fields on Checkout
            $this->has_fields = true;

            // Load the form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Load Payment Options
            $this->init_payment_options();

            // Options
            $this->enabled              = $this->get_option( 'enabled' );
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

            // Register Hooks - Script
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script') );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script') );

            // Register Hooks - Gateway
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Register Hooks - Custom Actions
            add_action( 'wc_checkout_braspag_print_bank_ticket_description', array( $this, 'print_bank_ticket_description' ) );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         */
        public function init_form_fields() {
            // Descriptions
            $merchant_id_description = sprintf(
                __( 'Please enter your Merchant ID. You can find it in %s.', WCB_TEXTDOMAIN ),
                '<a href="https://admin.braspag.com.br/Account/MyMerchants" target="_blank">' . __( 'Braspag Admin > My Merchants', WCB_TEXTDOMAIN ) . '</a>'
            );

            $merchant_key_description = sprintf(
                __( 'Please enter your Merchant Key. You received it after your register or you can enter in contact at %s.', WCB_TEXTDOMAIN ),
                '<a href="mailto:suporte@braspag.com.br" target="_blank">suporte@braspag.com.br</a>'
            );

            $debug_description = sprintf(
                __( 'Log Checkout Braspag events, such as API requests, you can check this log in %s.', WCB_TEXTDOMAIN ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '" target="_blank">' . __( 'System Status &gt; Logs', WCB_TEXTDOMAIN ) . '</a>'
            );

            // Form Fields (Before Payment Options)
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
                'methods_section'       => array(
                    'type'  => 'title',
                    'title' => __( 'Payment Methods', WCB_TEXTDOMAIN ),
                ),
            );

            // Payment Methods Options
            foreach ( $this->payment_methods as $code => $data ) {
                $this->form_fields['method_' . $code . '_enabled'] = array(
                    'type'              => 'checkbox',
                    'title'             => $data['name'],
                    'label'             => sprintf( __( 'Enable payment using %s', WCB_TEXTDOMAIN ), mb_strtolower( $data['name'] ) ),
                    'desc_tip'          => true,
                    'default'           => 'no',
                    'description'       => __( 'It should be available to your merchant.', WCB_TEXTDOMAIN ),
                );

                if ( $code == 'bt' ) {
                    $bt_description_default = __( 'The order will be confirmed only after the payment approval. It can take 2 or 3 days.', WCB_TEXTDOMAIN );
                    $bt_description_default .= "\n\n" . __( 'After clicking "Proceed to payment" you will receive your bank ticket and will be able to print and pay in your internet banking or in a lottery retailer.', WCB_TEXTDOMAIN );

                    $this->form_fields['method_' . $code . '_description'] = array(
                        'type'              => 'textarea',
                        'title'             => __( 'Description', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Text about payment using bank ticket to display to your customer (accepts HTML).', WCB_TEXTDOMAIN ),
                        'css'               => 'min-height: 150px;',
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_bt_enabled' ],
                        'default'           => $bt_description_default,
                    );
                }
            }

            // Options after Payment Methods
            $this->form_fields = array_merge( $this->form_fields, array(
                'bt_description'    => array(
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
            ) );
        }

        /**
         * Init payment methods data
         *
         * @return void
         */
        public function init_payment_options() {
            foreach ( $this->payment_methods as $code => $data ) {
                $enabled = ( $this->get_option( 'method_' . $code . '_enabled', 'no' ) === 'yes' );
                $enabled = apply_filters( 'wc_checkout_braspag_method_' . $code . '_enabled', $enabled );

                $this->payment_methods[ $code ]['name'] = __( $data['name'], WCB_TEXTDOMAIN );
                $this->payment_methods[ $code ]['enabled'] = $enabled;
            }
        }

        /**
         * Check if the gateway is available for use.
         *
         * @return bool
         */
        public function is_available() {
            if ( $this->enabled != 'yes' ) return false;
            if ( ! $this->api->is_valid() ) return false;

            return apply_filters( 'wc_checkout_braspag_using_supported_currency', ( get_woocommerce_currency() == 'BRL' ) );
        }

        /**
         * Payment fields.
         *
         */
        public function payment_fields() {
            $payment_methods = [];

            foreach ( $this->payment_methods as $code => $data ) {
                if ( empty( $data['enabled'] ) ) continue;

                $payment_methods[ $code ] = $data['name'];
            }

            $defaults = array(
                'description'   => $this->description,
                'methods'       => $payment_methods,
            );

            /**
             * Filters the data passed to checkout template.
             *
             * We use wp_parse_args so you can filter a empty array to override defaults.
             */
            $override_args = apply_filters( 'wc_checkout_braspag_form_data', [] );
            $args = wp_parse_args( $override_args, $defaults );

            wc_get_template( 'checkout-form.php', $args, 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );
        }

        /**
         * Process the payment and return the result.
         *
         * @param  int $order_id
         *
         * @return array
         */
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            return array(
                'result'    => 'success',
                'redirect'  => $order->get_checkout_payment_url( true ),
            );
        }

        /**
         * Action 'admin_enqueue_scripts'
         * Enqueue scripts for gateway settings page.
         *
         * @return void
         */
        public function enqueue_admin_script() {
            $this->enqueue_asset( 'admin', [ 'jquery', 'underscore' ] );
        }

        /**
         * Action 'wp_enqueue_script'
         * Enqueue scripts for checkout page.
         *
         * @return void
         */
        public function enqueue_frontend_script() {
            $this->enqueue_asset( 'frontend', [ 'jquery' ] );
            $this->enqueue_asset( 'frontend', [], false );
        }

        /**
         * Action 'wc_checkout_braspag_print_bank_ticket_description'
         * Print description for Bank Ticket payment method
         *
         * @return void
         */
        public function print_bank_ticket_description() {
            $description = $this->get_option( 'method_bt_description' );
            $description = apply_filters( 'wc_checkout_braspag_bank_ticket_description', $description );

            echo wpautop( $description );
        }

        /**
         * Enqueue scripts or styles for gateway settings page.
         *
         * We do not validate page into actions because WooCommerce says:
         * "Gateways are only loaded when needed, such as during checkout and on the settings page in admin"
         *
         * @link https://docs.woocommerce.com/document/payment-gateway-api/#section-8
         *
         * @return void
         */
        private function enqueue_asset( $handle, $dependencies = [], $is_script = true ) {
            $ext = ( $is_script ) ? 'js' : 'css';

            $file_url = WCB_PLUGIN_URL . '/modules/woocommerce/assets/' . $ext . '/' . $handle;
            $file_url .= ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.' . $ext : '.min.' . $ext;

            if ( $is_script ) {
                wp_enqueue_script( $this->id . '-' . $handle . '-script', $file_url, $dependencies, WCB_VERSION, true );
                return;
            }

            wp_enqueue_style( $this->id . '-' . $handle . '-style', $file_url, $dependencies, WCB_VERSION );
        }

    }

}
