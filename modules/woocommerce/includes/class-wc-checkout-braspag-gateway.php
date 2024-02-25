<?php

/**
 * WC_Checkout_Braspag_Gateway
 * Class responsible to manage all WooCommerce stuff
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Gateway
 * @since           1.0.0
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Gateway' ) ) {

    class WC_Checkout_Braspag_Gateway extends WC_Payment_Gateway {

        const EXTRA_FIELDS_PLUGIN_NAME  = 'Brazilian Market on WooCommerce';
        const EXTRA_FIELDS_PLUGIN_SLUG  = 'woocommerce-extra-checkout-fields-for-brazil';
        const EXTRA_FIELDS_PLUGIN_FILE  = 'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php';
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
         * @see init_payment_options()
         *
         * @var array
         */
        private $payment_methods = [];

        /**
         * True if we are processing payment
         *
         * @var bool
         */
        private $processing_payment = false;

        /**
         * @var WC_Checkout_Braspag_Api
         */
        public $api;

        /**
         * Gateway parameters
         *
         * @var string
         */
        public $sandbox;
        public $merchant_id;
        public $merchant_key;
        public $sandbox_merchant_key;
        public $debug;

        /**
         * @var boolean
         */
        public $is_sandbox;

        /**
         * The Constructor
         */
        public function __construct() {
            // Required infos
            $this->id                 = 'checkout-braspag';
            $this->icon               = apply_filters( 'wc_checkout_braspag_icon', WCB_PLUGIN_URL . '/modules/woocommerce/assets/images/braspag.png' );
            $this->method_title       = __( 'Checkout Braspag', WCB_TEXTDOMAIN );
            $this->method_description = __( 'Accept payments by credit card, debit card, eletronic transfer or bank slip using the Braspag Checkout.', WCB_TEXTDOMAIN );

            // Has fields on Checkout
            $this->has_fields = true;

            /**
             * Load Payment Options
             * NOTE: Should be called before init_form_fields
             */
            $this->init_payment_options();

            // Load the form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Options
            $this->enabled              = $this->get_option( 'enabled' );
            $this->title                = $this->get_option( 'title', 'Braspag' );
            $this->description          = $this->get_option( 'description' );
            $this->merchant_id          = $this->get_option( 'merchant_id' );
            $this->sandbox              = $this->get_option( 'sandbox' );
            $this->merchant_key         = $this->get_option( 'merchant_key' );
            $this->sandbox_merchant_key = $this->get_option( 'sandbox_merchant_key' );
            $this->debug                = $this->get_option( 'debug' );

            // Is Sandbox?
            $this->is_sandbox = ( 'yes' === $this->sandbox ) ? true : false;

            // Start API
            $merchant_key = ( $this->is_sandbox ) ? $this->sandbox_merchant_key : $this->merchant_key;
            $this->api    = new WC_Checkout_Braspag_Api( $this->merchant_id, $merchant_key, $this );

            // Register Hooks - WordPress
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );
            add_action( 'admin_notices', array( $this, 'add_notices' ) );

            // Register Hooks - WooCommerce
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
            add_action( 'woocommerce_api_wc_checkout_braspag_gateway', array( $this, 'wc_api_callback' ) );

            // Register Hooks - Custom Actions
            add_action( 'wc_checkout_braspag_print_bank_slip_description', array( $this, 'print_bank_slip_description' ) );
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         *
         * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
         */
        public function init_form_fields() {
            // Descriptions
            $merchant_id_description = sprintf(
                // translators: link to documentation (portuguese only)
                __( 'Please enter your Merchant ID. You can find it in %s.', WCB_TEXTDOMAIN ),
                '<a href="https://admin.braspag.com.br/Account/MyMerchants" target="_blank">' . __( 'Braspag Admin > My Merchants', WCB_TEXTDOMAIN ) . '</a>'
            );

            $merchant_key_description = sprintf(
                // translators: link to support email
                __( 'Please enter your Merchant Key. You received it after your register or you can enter in contact at %s.', WCB_TEXTDOMAIN ),
                '<a href="mailto:suporte@braspag.com.br" target="_blank">suporte@braspag.com.br</a>'
            );

            $auto_capture_description = sprintf(
                // translators: link to documentation (portuguese only)
                __( 'Ask your acquirer about this feature. If false we will try to capture the transaction right after authorization. Check %s for more details.', WCB_TEXTDOMAIN ),
                '<a href="https://braspag.github.io/manual/braspag-pagador#capturando-uma-transa%C3%A7%C3%A3o" target="_blank">Capturando uma transação</a>'
            );

            $save_card_description = sprintf(
                // translators: link to documentation (portuguese only)
                __( 'If true Braspag will return a token to be used in future. Check %s for more details.', WCB_TEXTDOMAIN ),
                '<a href="https://braspag.github.io/manual/braspag-pagador#salvando-e-reutilizando-cart%C3%B5es" target="_blank">Cartão Protegido</a>'
            );

            $use_extra_fields_description = sprintf(
                // translators: link to plugin
                __( 'The %s is a popular plugin to add customer fields and masks. If you do not want to add this fields or create your own implementation, unmark this and use or filters to add customer data.', WCB_TEXTDOMAIN ),
                '<a href="https://wordpress.org/plugins/' . self::EXTRA_FIELDS_PLUGIN_SLUG . '" target="_blank">' . self::EXTRA_FIELDS_PLUGIN_NAME . '</a>'
            );

            $wallet_key_description = sprintf(
                // translators: link to documentation (portuguese only)
                __( 'Encrypted key that identifies stores in wallets. Check %s for more details.', WCB_TEXTDOMAIN ),
                '<a href="https://braspag.github.io/manual/braspag-pagador#walletkey" target="_blank">Walletkey</a>'
            );

            $debug_description = sprintf(
                // translators: link to debug page
                __( 'Log Checkout Braspag events, such as API requests, you can check this log in %s.', WCB_TEXTDOMAIN ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '" target="_blank">' . __( 'System Status &gt; Logs', WCB_TEXTDOMAIN ) . '</a>'
            );

            // Form Fields (Before Payment Options)
            $this->form_fields = array(
                'enabled'              => array(
                    'type'    => 'checkbox',
                    'title'   => __( 'Enable/Disable', WCB_TEXTDOMAIN ),
                    'label'   => __( 'Enable Checkout Braspag', WCB_TEXTDOMAIN ),
                    'default' => 'no',
                ),
                'title'                => array(
                    'type'              => 'text',
                    'title'             => __( 'Title', WCB_TEXTDOMAIN ),
                    'description'       => __( 'Title of payment method to user.', WCB_TEXTDOMAIN ),
                    'default'           => __( 'Braspag', WCB_TEXTDOMAIN ),
                    'custom_attributes' => [ 'required' => 'required' ],
                ),
                'description'          => array(
                    'type'        => 'textarea',
                    'title'       => __( 'Description', WCB_TEXTDOMAIN ),
                    'description' => __( 'User will see this description during checkout.', WCB_TEXTDOMAIN ),
                    'default'     => __( 'Pay with credit card, debit card, eletronic transfer or bank slip.', WCB_TEXTDOMAIN ),
                ),
                'braspag_section'      => array(
                    'type'  => 'title',
                    'title' => __( 'Braspag Settings', WCB_TEXTDOMAIN ),
                ),
                'merchant_id'          => array(
                    'type'              => 'text',
                    'title'             => __( 'Merchant ID', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_id_description,
                    'default'           => '',
                    'custom_attributes' => [ 'required' => 'required' ],
                ),
                'sandbox'              => array(
                    'type'        => 'checkbox',
                    'title'       => __( 'Braspag Sandbox', WCB_TEXTDOMAIN ),
                    'label'       => __( 'Enable Braspag Sandbox', WCB_TEXTDOMAIN ),
                    'desc_tip'    => true,
                    'default'     => 'no',
                    'description' => __( 'You can use sandbox to test the payments (requires a sandbox Merchant ID).', WCB_TEXTDOMAIN ),
                ),
                'merchant_key'         => array(
                    'type'              => 'text',
                    'title'             => __( 'Merchant Key', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_key_description,
                    'custom_attributes' => [ 'data-condition' => '!woocommerce_checkout-braspag_sandbox' ],
                ),
                'sandbox_merchant_key' => array(
                    'type'              => 'text',
                    'title'             => __( 'Sandbox Merchant Key', WCB_TEXTDOMAIN ),
                    'description'       => $merchant_key_description,
                    'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_sandbox' ],
                ),
                'methods_section'      => array(
                    'type'  => 'title',
                    'title' => __( 'Payment Methods', WCB_TEXTDOMAIN ),
                ),
            );

            // Payment Methods Options
            foreach ( $this->payment_methods as $code => $data ) {
                $this->form_fields[ 'method_' . $code . '_enabled' ] = array(
                    'type'        => 'checkbox',
                    'title'       => $data['name'],
                    'label'       => sprintf(
                        // translators: payment method name (already translated and strtolower)
                        __( 'Enable payment using %s', WCB_TEXTDOMAIN ),
                        mb_strtolower( $data['name'] )
                    ),
                    'desc_tip'    => true,
                    'default'     => 'no',
                    'description' => __( 'It should be available to your merchant.', WCB_TEXTDOMAIN ),
                );

                // E-Wallet is still not implemented
                if ( $code === 'wl' ) {
                    $this->form_fields['method_wl_enabled']['desc_tip'] = false;
                    $this->form_fields['method_wl_enabled']['description'] = __( 'Still not fully implemented: will not show up on checkout page. Please, check FAQ for more information.', WCB_TEXTDOMAIN );
                }

                $sub_option_preffix = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

                // Providers
                if ( ! empty( $data['providers'] ) ) {
                    $providers_as_option = WC_Checkout_Braspag_Providers::get_provider_as_option( $data['providers'] );

                    $this->form_fields[ 'method_' . $code . '_provider' ] = array(
                        'type'              => 'select',
                        'title'             => $sub_option_preffix . __( 'Provider', WCB_TEXTDOMAIN ),
                        'description'       => sprintf(
                            // translators: provider name
                            __( 'Your %s provider', WCB_TEXTDOMAIN ),
                            mb_strtolower( $data['name'] )
                        ),
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                        'options'           => $providers_as_option,
                    );
                }

                // Cards Options
                if ( $code === 'cc' || $code === 'dc' ) {
                    $this->form_fields[ 'method_' . $code . '_soft_description' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Invoice Text', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Up to 13 characters.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields[ 'method_' . $code . '_auto_capture' ] = array(
                        'type'              => 'checkbox',
                        'title'             => $sub_option_preffix . __( 'Auto Capture', WCB_TEXTDOMAIN ),
                        'label'             => __( 'Enable Auto Capture', WCB_TEXTDOMAIN ),
                        'description'       => $auto_capture_description,
                        'desc_tip'          => false,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => 'no',
                    );

                    // Credit Card Only
                    if ( $code === 'cc' ) {
                        $this->form_fields[ 'method_' . $code . '_save_card' ] = array(
                            'type'              => 'checkbox',
                            'title'             => $sub_option_preffix . __( 'Save Card', WCB_TEXTDOMAIN ),
                            'label'             => __( 'Enable Save Card', WCB_TEXTDOMAIN ),
                            'description'       => $save_card_description,
                            'desc_tip'          => false,
                            'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                            'default'           => 'no',
                        );

                        $this->form_fields[ 'method_' . $code . '_find_brand' ] = array(
                            'type'              => 'checkbox',
                            'title'             => $sub_option_preffix . __( 'Find brand', WCB_TEXTDOMAIN ),
                            'label'             => __( 'Find brand by credit card number', WCB_TEXTDOMAIN ),
                            'description'       => __( "Will check credit card number to find brand if it's not presented", WCB_TEXTDOMAIN ),
                            'desc_tip'          => true,
                            'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                            'default'           => 'no',
                        );
                    }

                    $this->form_fields[ 'method_' . $code . '_interest' ] = array(
                        'type'              => 'select',
                        'title'             => $sub_option_preffix . __( 'Interest', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Installments Type: by merchant or by issuer.', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => 'ByMerchant',
                        'options'           => [
                            'ByMerchant' => __( 'By Merchant', WCB_TEXTDOMAIN ),
                            'ByIssuer'   => __( 'By Issuer', WCB_TEXTDOMAIN ),
                        ],
                    );

                    $this->form_fields[ 'method_' . $code . '_credential_code' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Code', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Affiliation generated by the acquirer (not required if configured on Braspag).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields[ 'method_' . $code . '_credential_key' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Key', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Affiliation Key/Token generated by the acquirer (not required if configured on Braspag).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => '',
                    );

                    // Getnet
                    $this->form_fields[ 'method_' . $code . '_credential_username' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Username', WCB_TEXTDOMAIN ),
                        'description'       => __( 'User generated by the acquirer (required for GetNet).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Getnet' ],
                        'default'           => '',
                    );

                    $this->form_fields[ 'method_' . $code . '_credential_password' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Password', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Password generated by the acquirer (required for GetNet).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Getnet',
                        ],
                        'default'           => '',
                    );

                    // GlobalPayments
                    $this->form_fields[ 'method_' . $code . '_credential_signature_for_global_payments' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Signature', WCB_TEXTDOMAIN ),
                        'description'       => __( 'TerminalID (required for Global Payments unless already configured on Braspag).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=GlobalPayments',
                        ],
                        'default'           => '',
                    );

                    // Safra
                    $this->form_fields[ 'method_' . $code . '_credential_signature_for_safra' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Signature', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Fill with "Safrapay;Cidade;Estado" (required for Safra unless already configured on Braspag).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Safra',
                        ],
                        'default'           => '',
                    );

                    $this->form_fields[ 'method_' . $code . '_credential_signature_for_safra2' ] = array(
                        'type'              => 'text',
                        'title'             => $sub_option_preffix . __( 'Credential Signature', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Fill with "Safrapay;Cidade;Estado" (required for Safra unless already configured on Braspag).', WCB_TEXTDOMAIN ),
                        'desc_tip'          => true,
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled|woocommerce_checkout-braspag_method_' . $code . '_provider=Safra2',
                        ],
                        'default'           => '',
                    );
                }

                // Bank Slip Options
                if ( $code === 'bs' ) {
                    $bs_description_default  = __( 'The order will be confirmed only after the payment approval. It can take 2 or 3 days.', WCB_TEXTDOMAIN );
                    $bs_description_default .= "\n\n" . __( 'After clicking "Proceed to payment" you will receive your bank slip and will be able to print and pay in your internet banking or in a lottery retailer.', WCB_TEXTDOMAIN );

                    $this->form_fields[ 'method_' . $code . '_description' ] = array(
                        'type'              => 'textarea',
                        'title'             => $sub_option_preffix . __( 'Description', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Text about payment using bank slip to display to your customer (accepts HTML).', WCB_TEXTDOMAIN ),
                        'css'               => 'min-height: 150px;',
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled' ],
                        'default'           => $bs_description_default,
                    );

                    $this->form_fields[ 'method_' . $code . '_bank_slip_instructions' ] = array(
                        'type'              => 'textarea',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Instructions', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Instructions displayed on bank slip. If not empty, will override the settings on Braspag.', WCB_TEXTDOMAIN ),
                        'css'               => 'min-height: 150px;',
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled' ],
                        'default'           => '',
                    );

                    $this->form_fields[ 'method_' . $code . '_days_to_pay' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Expiration Days', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Days from bank slip registration to customer pay. Will create expiration date. If not zero, will override the settings on Braspag.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    // Santander
                    $this->form_fields[ 'method_' . $code . '_nullify_days' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Nullify Days', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Days to cancel the bank slip. Only for Santander.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Santander2',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    // Bradesco
                    $this->form_fields[ 'method_' . $code . '_days_to_fine' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Days To Fine', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Days to fine the customer after expiration date. Only for Bradesco.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    $this->form_fields[ 'method_' . $code . '_fine_rate' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Fine Rate', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Fine amount (%). Only for Bradesco and allow 5 decimals.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 0.00001,
                        ],
                        'default'           => 0,
                    );

                    $this->form_fields[ 'method_' . $code . '_fine_amount' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Fine Amount', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Fine amount (in cents). Only for Bradesco and ignored if Fine Rate is not 0 or empty.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    $this->form_fields[ 'method_' . $code . '_days_to_interest' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Days To Interest', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Days to start charge interest after expiration date. Only for Bradesco.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    $this->form_fields[ 'method_' . $code . '_interest_rate' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Interest Rate', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Interest amount (monthly % - for example 30% will charge 1% by day). Only for Bradesco and allow 5 decimals.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 0.00001,
                        ],
                        'default'           => 0,
                    );

                    $this->form_fields[ 'method_' . $code . '_interest_amount' ] = array(
                        'type'              => 'number',
                        'title'             => $sub_option_preffix . __( 'Bank Slip Interest Amount', WCB_TEXTDOMAIN ),
                        'description'       => __( 'Interest amount (in cents). Only for Bradesco and ignored if Interest Rate is not 0 or empty.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [
                            'data-condition' => 'woocommerce_checkout-braspag_method_bs_enabled|woocommerce_checkout-braspag_method_bs_provider=Bradesco2',
                            'min'            => 0,
                            'step'           => 1,
                        ],
                        'default'           => 0,
                    );

                    // PF / PJ
                    $this->form_fields[ 'method_' . $code . '_prefer_company' ] = array(
                        'type'              => 'checkbox',
                        'title'             => $sub_option_preffix . __( 'Prefer Company', WCB_TEXTDOMAIN ),
                        'label'             => __( 'Use Company Name on Bank Slip data if Customer has CNPJ.', WCB_TEXTDOMAIN ),
                        'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_' . $code . '_enabled' ],
                        'default'           => 'no',
                    );
                }

                // E-Wallet Options
                if ( $code === 'wl' ) {
                    $wallets = array();
                    foreach ( WC_Checkout_Braspag_Providers::E_WALLET as $data ) {
                        $data = $data['wallets'] ?? [];
                        $wallets = array_merge( $wallets, $data );
                    }

                    $wallets = array_unique( $wallets );

                    foreach ( $wallets as $wallet ) {
                        $wallet_code = strtolower( $wallet );

                        $this->form_fields[ 'method_' . $code . '_' . $wallet_code . '_walletkey'  ] = array(
                            'type'              => 'textarea',
                            'title'             => $sub_option_preffix . sprintf( __( 'Key for %s', WCB_TEXTDOMAIN ), $wallet ), // phpcs:ignore
                            'description'       => $wallet_key_description,
                            'custom_attributes' => [ 'data-condition' => 'woocommerce_checkout-braspag_method_wl_enabled' ],
                        );
                    }
                }
            }

            // Options after Payment Methods
            $this->form_fields = array_merge(
                $this->form_fields,
                array(
                    'advanced_section' => array(
                        'type'  => 'title',
                        'title' => __( 'Advanced Settings', WCB_TEXTDOMAIN ),
                    ),
                    'use_extra_fields' => array(
                        'type'        => 'checkbox',
                        'title'       => __( 'Customer Fields', WCB_TEXTDOMAIN ),
                        'label'       => sprintf(
                            // translators: extra fields plugin name
                            __( 'Use "%s"', WCB_TEXTDOMAIN ),
                            self::EXTRA_FIELDS_PLUGIN_NAME
                        ),
                        'description' => $use_extra_fields_description,
                        'default'     => 'yes',
                    ),
                    'debug'            => array(
                        'type'        => 'checkbox',
                        'title'       => __( 'Debug Log', WCB_TEXTDOMAIN ),
                        'label'       => __( 'Enable logging', WCB_TEXTDOMAIN ),
                        'description' => $debug_description,
                        'default'     => 'no',
                    ),
                )
            );
        }

        /**
         * Check if the gateway is available for use.
         *
         * @return bool
         */
        public function is_available() {
            if ( $this->enabled !== 'yes' ) {
                return false;
            }

            if ( ! $this->api->is_valid() ) {
                return false;
            }

            return apply_filters( 'wc_checkout_braspag_using_supported_currency', ( get_woocommerce_currency() === 'BRL' ) );
        }

        /**
         * Get payment method data
         *
         * @return array
         */
        public function get_payment_method( $method ) {
            return $this->payment_methods[ $method ] ?? [];
        }

        /**
         * Get payment methods
         *
         * @return array
         */
        public function get_payment_methods() {
            return $this->payment_methods;
        }

        /**
         * Get payment methods
         *
         * @return array
         */
        public function get_frontend_payment_methods() {
            $methods = [];

            foreach ( $this->get_payment_methods() as $key => $value ) {
                if ( empty( $value['enabled'] ) || empty( $value['frontend'] ) ) {
                    continue;
                }

                $methods[ $key ] = $value;
            }

            return apply_filters( 'wc_checkout_braspag_frontend_payment_methods', $methods, $this );
        }

        /**
         * Get payment method data
         *
         * @return string
         */
        public function get_payment_method_by_code( $code ) {
            foreach ( $this->payment_methods as $method => $data ) {
                if ( $data['code'] === $code ) {
                    return $method;
                }
            }

            return '';
        }

        /**
         * Get API Return Url
         * @return string Api request URL to callback 'WC_Checkout_Braspag_Gateway'
         */
        public function get_api_return_url() {
            return WC()->api_request_url( 'WC_Checkout_Braspag_Gateway' );
        }

        /**
         * Payment fields.
         *
         */
        public function payment_fields() {
            global $braspag_gateway;
            $braspag_gateway = $this;

            $payment_methods = [];

            foreach ( $this->get_frontend_payment_methods() as $code => $data ) {
                // Ignore if has no provider selected (and we are not in sandbox)
                if ( ! $this->is_sandbox && empty( $this->get_option( 'method_' . $code . '_provider' ) ) ) {
                    continue;
                }

                $payment_methods[ $code ] = $data['name'];
            }

            $defaults = array(
                'description' => $this->description,
                'methods'     => $payment_methods,
            );

            /**
             * Filters the data passed to checkout template.
             *
             * We use wp_parse_args so you can filter a empty array to override defaults.
             */
            $override_args = apply_filters( 'wc_checkout_braspag_form_data', [] );
            $args          = wp_parse_args( $override_args, $defaults );

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
            $this->processing_payment = true;

            $order = wc_get_order( $order_id );

            $method = sanitize_text_field( $_POST['braspag_payment_method'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            /**
             * Filters the do_payment_request response.
             *
             * @param array $response
             * @param WC_Order $order
             * @param WC_Payment_Gateway $this
             */
            $response = apply_filters( 'wc_checkout_braspag_do_payment_request', $this->api->do_payment_request( $method, $order, $this ), $order, $this );

            // Update Order after gateway response
            if ( ! empty( $response['transaction'] ) ) {
                /**
                 * WooCommerce stuff
                 *
                 * @see WC_Order::payment_complete
                 */
                try {
                    // Our Stuff
                    $updated = $this->update_order_status( $response['transaction'] );
                    if ( empty( $updated ) ) {
                        throw new Exception( __( 'There was a problem updating your payment.', WCB_TEXTDOMAIN ) );
                    }

                    // WooCommerce Stuff (without order status)
                    do_action( 'woocommerce_pre_payment_complete', $order->get_id() );

                    if ( WC()->session ) {
                        WC()->session->set( 'order_awaiting_payment', false );
                    }

                    $order->save();

                    do_action( 'woocommerce_payment_complete', $order->get_id() );

                    // Redirect
                    return array(
                        'result'   => 'success',
                        'redirect' => ( ! empty( $response['url'] ) ) ? $response['url'] : $this->get_return_url( $order ),
                    );
                } catch ( Exception $e ) {
                    $response['errors'] = [ $e->getMessage() ];
                }
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
         * Update order status
         *
         * @link https://braspag.github.io/manual/braspag-pagador#resposta
         *
         * @param array $transaction API Request response from Braspag
         */
        public function update_order_status( $transaction ) {
            if ( empty( $transaction['MerchantOrderId'] ) || empty( $transaction['Payment'] ) ) {
                $this->log( 'Update Order Status failed: no MerchantOrderId and/or Payment was provided.' );
                return false;
            }

            // Get Order
            $order = wc_get_order( (int) $transaction['MerchantOrderId'] );
            if ( empty( $order ) ) {
                $this->log( 'Update Order Status failed: order is invalid for MerchantOrderId: ' . (int) $transaction['MerchantOrderId'] );
                return false;
            }

            // Update Meta Data
            $this->update_order_transaction_data( $order, $transaction );

            // Get Status
            $status = $transaction['Payment']['Status'] ?? '';

            // Add status and notes
            $order_status = '';

            switch ( $status ) {
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_NOT_FINISHED:
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_AUTHORIZED:
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PENDING:
                    $order_status = 'on-hold';
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_PAYMENT_CONFIRMED:
                    // Do not change status if it's already paid
                    $order_status = $order->is_paid() ? '' : 'processing';
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_DENIED:
                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_ABORTED:
                    $order_status = 'failed';
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_VOIDED:
                    $order_status = 'cancelled';
                    break;

                case WC_Checkout_Braspag_Api::TRANSACTION_STATUS_REFUNDED:
                    $order_status = 'refunded';
                    break;

                default:
                    $this->log( 'Braspag Status for order ' . $order->get_order_number() . ' is invalid: ' . $status );

                    /**
                     * Action allow developers to do something if status is invalid
                     *
                     * @param string  $status
                     * @param obj     $order  WC_Order
                     * @param array   $transaction
                     * @param obj     $this
                     */
                    do_action( 'wc_checkout_braspag_update_order_status_invalid', $status, $order, $transaction, $this );

                    return false;
            }

            // Log Status
            $this->log( 'Braspag Status for order ' . $order->get_order_number() . ' is: ' . $status );

            /**
             * Update Status
             *
             * WooCommerce already trigger 'woocommerce_order_status_{order_status}'
             * action on WC_Order::update_status() and it do what we need about stock.
             */
            if ( $order_status ) {
                $note = WC_Checkout_Braspag_Messages::payment_status_note( $status );
                $order->update_status( $order_status, $note );
            }

            /**
             * Action allow developers to do something after updated order status
             *
             * @param string  $status
             * @param obj     $order  WC_Order
             * @param array   $transaction
             * @param obj     $this
             */
            do_action( 'wc_checkout_braspag_update_order_status', $status, $order, $transaction, $this );

            return true;
        }

        /**
         * Update order status
         *
         * @link https://braspag.github.io/manual/braspag-pagador#resposta
         *
         * @param WC_Order  $order          WooCommerce Order
         * @param array     $transaction    API Request response from Braspag
         */
        public function update_order_transaction_data( WC_Order $order, $transaction ) {
            // Payment Data
            $payment_data = $transaction['Payment'] ?? [];
            $order->update_meta_data( '_wc_braspag_payment_data', $payment_data );
            if ( ! empty( $payment_data['CreditCard'] ) && ! empty( $payment_data['CreditCard']['CardToken'] ) ) {
                $card_token = array(
                    'Alias'     => $payment_data['CreditCard']['Alias'],
                    'CardToken' => $payment_data['CreditCard']['CardToken'],
                );

                $order->update_meta_data( '_wc_braspag_payment_card_token', $card_token );
            }

            // SavedCard
            $payment_method = $payment_data['Type'] ?? '';
            $payment_method = $this->get_payment_method_by_code( $payment_method );
            $order->update_meta_data( '_wc_braspag_payment_method', $payment_method );

            // Customer Data
            $customer_data = $transaction['Customer'] ?? [];
            $order->update_meta_data( '_wc_braspag_customer_data', $customer_data );

            // Payment ID
            $payment_id = $payment_data['PaymentId'] ?? '';
            $order->set_transaction_id( $payment_id );
            $order->update_meta_data( '_wc_braspag_payment_id', $payment_id ); // backward compatibility

            // Payment Method
            $payment_method = $payment_data['Type'] ?? '';
            $payment_method = $this->get_payment_method_by_code( $payment_method );
            $order->update_meta_data( '_wc_braspag_payment_method', $payment_method );

            $order->save();

            /**
             * Action allow developers to do something after updated order data
             *
             * @param obj     $order  WC_Order
             * @param array   $transaction
             * @param obj     $this
             */
            do_action( 'wc_checkout_braspag_update_order_transaction_data', $order, $transaction, $this );
        }

        /**
         * Update a order status and data from Braspag
         *
         * @param  string $payment_id
         * @return boolean
         * @throws Exception
         */
        public function update_order_from_payment( $payment_id ) {
            if ( empty( $payment_id ) ) {
                return false;
            }

            $api_query   = new WC_Checkout_Braspag_Query( $this );
            $transaction = $api_query->get_transaction( $payment_id );

            // Check payment
            $merchant_order_id = (int) ( $transaction['MerchantOrderId'] ?? 0 );

            // Check for Order
            $order = wc_get_order( $merchant_order_id );

            if ( empty( $merchant_order_id ) || empty( $order->get_id() ) ) {
                // Log
                $this->log( 'Error on checkout_braspag_gateway: Merchant Order Id (' . $merchant_order_id . ') has not a valid order.' );

                throw new Exception( __( 'There was a problem processing your payment: your order is invalid.', WCB_TEXTDOMAIN ) );
            }

            /**
             * Filters the transaction retrieved from payment_id
             *
             * @param array $transaction
             * @param WC_Order $order
             * @param WC_Checkout_Braspag_Query $api_query
             * @param WC_Payment_Gateway $this
             */
            $transaction = apply_filters( 'wc_checkout_braspag_update_order_from_payment_transaction', $transaction, $order, $api_query, $this );

            return $this->update_order_status( $transaction );
        }

        /**
         * Return true if we are processing payment
         *
         * @return bool
         */
        public function is_processing_payment() {
            return ! empty( $this->processing_payment );
        }

        /**
         * Create Log
         * Write to WC Logger with context
         *
         * @link https://woocommerce.wordpress.com/2017/01/26/improved-logging-in-woocommerce-2-7/
         *
         * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
         */
        public function log( $message, $level = 'debug', $source = '', array $context = [] ) {
            if ( 'yes' !== $this->debug ) {
                return;
            }

            $logger = wc_get_logger();

            if ( ! method_exists( $logger, $level ) ) {
                return;
            }

            $context['source'] = ( ! empty( $source ) ) ? $source : $this->id;

            // Format Message
            if ( ! is_string( $message ) ) {
                $message = print_r( $message, true ); // phpcs:ignore
            }

            // Call it
            call_user_func( array( $logger, $level ), $message, $context );
        }

        /**
         * Action 'admin_notices'
         * Enqueue scripts for gateway settings page.
         *
         * @return void
         */
        public function add_notices() {
            $using_extra_fields = ( $this->get_option( 'use_extra_fields', 'yes' ) === 'yes' );

            if ( $using_extra_fields && ! class_exists( self::EXTRA_FIELDS_PLUGIN_CLASS ) ) {
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
         * Action 'woocommerce_thankyou_{gateway}'
         * Run on receipt order page
         *
         * @return void
         */
        public function thankyou_page( $order_id ) {
            // Get Order
            $order = wc_get_order( $order_id );

            if ( empty( $order->get_id() ) ) {
                return;
            }

            // Add Payment Info
            $method = $order->get_meta( '_wc_braspag_payment_method' );

            $args = [
                'payment' => $order->get_meta( '_wc_braspag_payment_data' ),
                'method'  => $this->get_payment_method( $method ),
            ];

            wc_get_template( 'order-received.php', $args, 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );
        }

        /**
         * Action 'woocommerce_email_after_order_table'
         * Run on receipt order page
         *
         * @return void
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            // Add Payment Info
            $payment_data = $order->get_meta( '_wc_braspag_payment_data' );
            $method = $order->get_meta( '_wc_braspag_payment_method' );

            if ( empty( $payment_data ) ) {
                return;
            }

            $args = [
                'payment' => $payment_data,
                'method'  => $this->get_payment_method( $method ),
            ];

            $template = ( $plain_text ) ? 'emails/plain-instructions.php' : 'emails/html-instructions.php';
            wc_get_template( $template, $args, 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );
        }

        /**
         * WC Api Callback: 'WC_Checkout_Braspag_Gateway'
         * Process payments
         *
         * @return void
         *
         * @SuppressWarnings(PHPMD.ExitExpression)
         */
        public function wc_api_callback() {
            $error = esc_html__( 'Braspag Request Unauthorized', WCB_TEXTDOMAIN );

            // Body
            $body = file_get_contents( 'php://input' );
            $body = json_decode( $body, true );

            // Check for PaymentId
            $payment_id = $body['PaymentId'] ?? '';
            $payment_id = sanitize_text_field( $payment_id );

            if ( empty( $payment_id ) ) {
                wp_die( $error, 401 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            try {
                if ( $this->update_order_from_payment( $payment_id ) ) {
                    header( 'HTTP/1.1 200 OK' );
                    exit;
                }
            } catch ( Exception $e ) {
                $error = $e->getMessage();

                // Log
                $this->log( 'Error on wc_api_callback: ' . $error );
            }

            wp_die( $error, 401 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

            echo wpautop( esc_html( $description ) ); //phpcs:ignore
        }

        /**
         * Init payment methods data
         *
         * @return void
         */
        private function init_payment_options() {
            $this->payment_methods = [
                'cc' => [
                    'enabled'   => false,
                    'code'      => 'CreditCard',
                    'name'      => __( 'Credit Card', WCB_TEXTDOMAIN ),
                    'providers' => WC_Checkout_Braspag_Providers::CREDIT_CARD,
                    'frontend'  => true,
                ],
                // TODO: Still waiting Braspag Support
                // 'dc' => [
                //     'enabled'   => false,
                //     'code'      => 'DebitCard',
                //     'name'      => __( 'Debit Card', WCB_TEXTDOMAIN ),
                //     'providers' => WC_Checkout_Braspag_Providers::DEBIT_CARD,
                // ],
                'bs' => [
                    'enabled'   => false,
                    'code'      => 'Boleto',
                    'name'      => __( 'Bank Slip', WCB_TEXTDOMAIN ),
                    'providers' => WC_Checkout_Braspag_Providers::BANK_SLIP,
                    'frontend'  => true,
                ],
                'wl' => [
                    'enabled'   => false,
                    'code'      => 'EWallet',
                    'name'      => __( 'E-Wallet', WCB_TEXTDOMAIN ),
                    'providers' => WC_Checkout_Braspag_Providers::E_WALLET,
                    'frontend'  => false,
                ],
                // TODO: Still waiting Braspag Support
                // 'et' => [
                //     'enabled'   => false,
                //     'code'      => 'EletronicTransfer',
                //     'name'      => __( 'Eletronic Transfer', WCB_TEXTDOMAIN ),
                //     'providers' => WC_Checkout_Braspag_Providers::ELETRONIC_TRANSFER,
                // ],
            ];

            foreach ( array_keys( $this->payment_methods ) as $code ) {
                $enabled = ( $this->get_option( 'method_' . $code . '_enabled', 'no' ) === 'yes' );
                $enabled = apply_filters( 'wc_checkout_braspag_method_' . $code . '_enabled', $enabled );

                $this->payment_methods[ $code ]['enabled'] = $enabled;
            }
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

            $version = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? uniqid() : WCB_VERSION;

            $file_url  = WCB_PLUGIN_URL . '/modules/woocommerce/assets/' . $ext . '/' . $handle;
            $file_url .= ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.' . $ext : '.min.' . $ext;

            if ( $is_script ) {
                wp_enqueue_script( $this->id . '-' . $handle . '-script', $file_url, $dependencies, $version, true );
                return;
            }

            wp_enqueue_style( $this->id . '-' . $handle . '-style', $file_url, $dependencies, $version );
        }

    }

}
