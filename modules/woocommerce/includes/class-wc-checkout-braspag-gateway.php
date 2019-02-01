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

        const EXTRA_FIELDS_PLUGIN_NAME = 'WooCommerce Extra Checkout Fields for Brazil';
        const EXTRA_FIELDS_PLUGIN_SLUG = 'woocommerce-extra-checkout-fields-for-brazil';
        const EXTRA_FIELDS_PLUGIN_FILE = 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php';
        const EXTRA_FIELDS_PLUGIN_CLASS = 'Extra_Checkout_Fields_For_Brazil';

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
                'enabled'   => false,
                'name'      => 'Credit Card',
                'code'      => 'CreditCard',
                'providers' => WC_Checkout_Braspag_Providers::CREDIT_CARD,
            ),
            'dc' => array(
                'enabled'   => false,
                'name'      => 'Debit Card',
                'code'      => 'DebitCard',
                'providers' => WC_Checkout_Braspag_Providers::DEBIT_CARD,
            ),
            'bs' => array(
                'enabled'   => false,
                'name'      => 'Bank Slip',
                'code'      => 'Boleto',
                'providers' => WC_Checkout_Braspag_Providers::BANK_SLIP,
            ),
            // TODO: Still waiting Braspag Support
            // 'et' => array(
            //     'enabled'   => false,
            //     'name'      => 'Eletronic Transfer',
            //     'code'      => 'EletronicTransfer',
            //     'providers' => WC_Checkout_Braspag_Providers::ELETRONIC_TRANSFER,
            // ),
        );

        public function __construct() {
            // Required infos
            $this->id                   = 'checkout-braspag';
            $this->icon                 = apply_filters( 'wc_checkout_braspag_icon', WCB_PLUGIN_URL . '/modules/woocommerce/assets/images/braspag.png' );
            $this->method_title         = __( 'Checkout Braspag', WCB_TEXTDOMAIN );
            $this->method_description   = __( 'Accept payments by credit card, debit card, eletronic transfer or bank slip using the Braspag Checkout.', WCB_TEXTDOMAIN );

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
            $this->is_sandbox = ( 'yes' == $this->sandbox ) ? true : false;
            $merchant_key = ( $this->is_sandbox ) ? $this->sandbox_merchant_key : $this->merchant_key;

            $this->api = new WC_Checkout_Braspag_Api( $this->merchant_id, $merchant_key, $this->is_sandbox );

            // Register Hooks - WordPress
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script') );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script') );
            add_action( 'admin_notices', array( $this, 'add_notices' ) );

            // Register Hooks - WooCommerce
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Register Hooks - Custom Actions
            add_action( 'wc_checkout_braspag_print_bank_slip_description', array( $this, 'print_bank_slip_description' ) );
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

            $use_extra_fields_description = sprintf(
                __( 'The %s is a popular plugin to add customer fields and masks. If you do not want to add this fields or create your own implementation, unmark this and use or filters to add customer data.', WCB_TEXTDOMAIN ),
                '<a href="https://wordpress.org/plugins/' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_SLUG . '" target="_blank">' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_NAME . '</a>'
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
                    'default'     => __( 'Pay with credit card, debit card, eletronic transfer or bank slip.', WCB_TEXTDOMAIN ),
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

                $sub_option_preffix = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

                // Providers
                if ( ! empty( $data['providers'] ) ) {
                    $providers_as_option = WC_Checkout_Braspag_Providers::get_provider_as_option( $data['providers'] );

                    $this->form_fields['method_' . $code . '_provider'] = array(
                        'type'              => 'select',
                        'title'             => $sub_option_preffix . __( 'Provider', WCB_TEXTDOMAIN ),
                        'description'       => sprintf( __( 'Your %s provider', WCB_TEXTDOMAIN ), mb_strtolower( $data['name'] ) ),
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                        'options'           => $providers_as_option,
                    );
                }

                // Cards Options
                if ( $code == 'cc' || $code == 'dc' ) {
                    $this->form_fields['method_' . $code . '_soft_description'] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Invoice Text', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Up to 13 characters.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields['method_' . $code . '_auto_capture'] = array(
                        'type'              => 'checkbox',
                        'title'             => $sub_option_preffix . __( 'Auto Capture', WCB_TEXTDOMAIN ),
                        'label'             => __( 'Enable Auto Capture', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Check with your acquirer. If false we try to capture the transaction after authorization.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => 'no',
                    );

                    $this->form_fields['method_' . $code . '_credential_code'] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Code', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Affiliation generated by the acquirer.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields['method_' . $code . '_credential_key'] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Key', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Affiliation Key/Token generated by the acquirer.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields['method_' . $code . '_credential_username'] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Username', WCB_TEXTDOMAIN ),
                        'description'       => __( 'User generated by the acquirer (required for GetNet).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Getnet' ],
                        'default'           => '',
                    );

                    $this->form_fields['method_' . $code . '_credential_password'] = array(
                        'type'              => 'password',
                        'title'             => $sub_option_preffix . __( 'Credential Password', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Password generated by the acquirer (required for GetNet).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Getnet' ],
                        'default'           => '',
                    );

                    $this->form_fields['method_' . $code . '_credential_signature'] = array(
                        'type'              => 'password',
                        'title'             => $sub_option_preffix . __( 'Credential Signature', WCB_TEXTDOMAIN ),
                        'description'       => __( 'TerminalID (required for Global Payments).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=GlobalPayments' ],
                        'default'           => '',
                    );
                }

                // Bank Slip Options
                if ( $code == 'bs' ) {
                    $bs_description_default = __( 'The order will be confirmed only after the payment approval. It can take 2 or 3 days.', WCB_TEXTDOMAIN );
                    $bs_description_default .= "\n\n" . __( 'After clicking "Proceed to payment" you will receive your bank slip and will be able to print and pay in your internet banking or in a lottery retailer.', WCB_TEXTDOMAIN );

                    $this->form_fields['method_' . $code . '_description'] = array(
                        'type'              => 'textarea',
                        'title'             => $sub_option_preffix . __( 'Description', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Text about payment using bank slip to display to your customer (accepts HTML).', WCB_TEXTDOMAIN ),
                        'css'               => 'min-height: 150px;',
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled' ],
                        'default'           => $bs_description_default,
                    );
                }
            }

            // Options after Payment Methods
            $this->form_fields = array_merge( $this->form_fields, array(
                'advanced_section'  => array(
                    'type'  => 'title',
                    'title' => __( 'Advanced Settings', WCB_TEXTDOMAIN ),
                ),
                'use_extra_fields'  => array(
                    'type'          => 'checkbox',
                    'title'         => __( 'Customer Fields', WCB_TEXTDOMAIN ),
                    'label'         => sprintf( __( 'Use "%s"', WCB_TEXTDOMAIN ), WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_NAME ),
                    'description'   => $use_extra_fields_description,
                    'default'       => 'yes',
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
         * Get payment method data
         *
         * @return void
         */
        public function get_payment_method( $method ) {
            return $this->payment_methods[ $method ] ?? [];
        }

        /**
         * Payment fields.
         *
         */
        public function payment_fields() {
            $payment_methods = [];

            foreach ( $this->payment_methods as $code => $data ) {
                // Ignore if not enabled
                if ( empty( $data['enabled'] ) ) continue;

                // Ignore if has no provider selected (and we are not in sandbox)
                if ( ! $this->is_sandbox && empty( $this->get_option( 'method_' . $code . '_provider' ) ) ) continue;

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

            $method = ( ! empty( $_POST['braspag_payment_method'] ) ) ? $_POST['braspag_payment_method'] : '';
            $response = $this->api->do_payment_request( $method, $order, $this );

            // TODO: Update Order after gateway response
            if ( ! empty( $response['data'] ) ) {
                // $this->update_order( $response['data'] );
            }

            // Success if a URL is returned
            if ( ! empty( $response['url'] ) ) {
                return array(
                    'result'    => 'success',
                    'redirect'  => $response['url'],
                );
            }

            // If not success, add error notices
            $errors = ( ! empty( $response['errors'] ) ) ? $response['errors'] : [];
            foreach ( $errors as $error ) {
                wc_add_notice( $error, 'error' );
            }

            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }

        /**
         * Action 'admin_notices'
         * Enqueue scripts for gateway settings page.
         *
         * @return void
         */
        public function add_notices() {
            $using_extra_fields = ( $this->get_option( 'use_extra_fields', 'yes' ) == 'yes' );

            if ( $using_extra_fields && ! class_exists( WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_CLASS ) ) {
                include_once WCB_PLUGIN_PATH . '/modules/woocommerce/includes/views/html-notice-extra-fields-missing.php';
            }
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
         * Action 'wc_checkout_braspag_print_bank_slip_description'
         * Print description for Bank Slip payment method
         *
         * @return void
         */
        public function print_bank_slip_description() {
            $description = $this->get_option( 'method_bs_description' );
            $description = apply_filters( 'wc_checkout_braspag_bank_slip_description', $description );

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