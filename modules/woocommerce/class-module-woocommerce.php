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
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WCB_Module_Woocommerce' ) ) {

    class WCB_Module_Woocommerce {

        /**
         * The core
         * @var Woo_Checkout_Braspag
         */
        public $core;

        /**
         * Include files
         *
         * @var array
         */
        public $includes = [];

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

            $this->includes = [
                'class-wc-checkout-braspag-gateway',
                'class-wc-checkout-braspag-api',
                'class-wc-checkout-braspag-providers',
                'class-wc-checkout-braspag-credit-card-brand',
                'braspag/class-wc-checkout-braspag-model',
                'braspag/class-wc-checkout-braspag-messages',
                'braspag/traits/class-wc-checkout-braspag-extradata',
                'braspag/models/class-wc-checkout-braspag-customer',
                'braspag/models/class-wc-checkout-braspag-address',
                'braspag/models/class-wc-checkout-braspag-query',
                'braspag/models/class-wc-checkout-braspag-request',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-bs',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-cc',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-dc',
                'braspag/models/requests/class-wc-checkout-braspag-request-payment-wl',
            ];
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

            $this->core->add_filter( 'woocommerce_order_actions', array( $this, 'woocommerce_order_actions' ), 10, 2 );
            $this->core->add_action( 'woocommerce_order_action_checkout_braspag_update', array( $this, 'checkout_braspag_update' ) );

            $this->core->add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
            $this->core->add_action( 'woocommerce_process_shop_order_meta', array( $this, 'woocommerce_process_shop_order_meta' ), 99, 2 );

            $this->core->add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'woocommerce_admin_order_data_after_shipping_address' ) );
            $this->core->add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'woocommerce_admin_order_data_after_billing_address' ) );

            $this->core->add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
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
        public function woocommerce_order_actions( $actions, $order ) {
            $gateway = $this->get_gateway_object();

            if ( ! empty( $gateway ) && ! empty( $order ) && $order->get_payment_method() === $gateway->id ) {
                $actions['checkout_braspag_update'] = __( 'Update payment info from Braspag', WCB_TEXTDOMAIN );
            }

            return $actions;
        }

        /**
         * Action 'woocommerce_order_action_checkout_braspag_update'
         * Process the action order on dashboard
         *
         * @return void
         */
        public function checkout_braspag_update( $order ) {
            $gateway = $this->get_gateway_object();

            if ( empty( $gateway ) || $order->get_payment_method() !== $gateway->id ) {
                return;
            }

            $payment = $order->get_meta( '_wc_braspag_payment_data' );
            if ( empty( $payment ) || empty( $payment['PaymentId'] ) ) {
                $order->add_order_note( __( 'Payment info updated failed: not able to find "PaymentId".', WCB_TEXTDOMAIN ) );
                return;
            }

            try {
                // Update Order from Payment
                if ( $gateway->update_order_from_payment( $payment['PaymentId'] ) ) {
                    $order->add_order_note( __( 'Braspag: updating payment info.', WCB_TEXTDOMAIN ), 0, get_current_user_id() );
                }
            } catch ( Exception $e ) {
                $order->add_order_note( sprintf( __( 'Braspag: updating payment info error (%s).', WCB_TEXTDOMAIN ), $e->getMessage() ), 0, get_current_user_id() );
            }
        }

        /**
         * Action: 'admin_enqueue_scripts'
         * Scripts for administration
         *
         * @return void
         */
        public function admin_enqueue_scripts( $page ) {
            if ( $page !== 'post-new.php' && $page !== 'post.php' ) {
                return;
            }

            $post_type = '';
            if ( ! empty( $_GET['post_type'] ) ) {
                $post_type = sanitize_text_field( $_GET['post_type'] );
            }

            if ( ! $post_type && ! empty( $_GET['post'] ) ) {
                $post = sanitize_text_field( $_GET['post'] );
                $post = get_post( $post );
                $post_type = $post ? $post->post_type : '';
            }

            if ( $post_type !== 'shop_order' ) {
                return;
            }

            // Shop Order JS
            $version = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? uniqid() : WCB_VERSION;

            $file_url  = WCB_PLUGIN_URL . '/modules/woocommerce/assets/js/shop-order.';
            $extension = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'js' : 'min.js';
            wp_enqueue_script( 'wc-checkout-braspag-shop-order-script', $file_url . $extension, [ 'jquery' ], $version, true );

            $file_url  = WCB_PLUGIN_URL . '/modules/woocommerce/assets/css/shop-order.';
            $extension = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'css' : 'min.css';
            wp_enqueue_style( 'wc-checkout-braspag-shop-order-style', $file_url . $extension, [], $version );
        }

        /**
         * Action: 'woocommerce_admin_order_data_after_billing_address'
         * Payment data on dashboard
         *
         * @return void
         */
        public function woocommerce_admin_order_data_after_billing_address( $order ) {
            /**
             * If we have Extra_Checkout_Fields_For_Brazil_Order we are going to add to right side
             * @see woocommerce_admin_order_data_after_shipping_address
             */
            if ( class_exists( 'Extra_Checkout_Fields_For_Brazil_Order' ) ) {
                return;
            }

            $this->woocommerce_admin_order_payment_data( $order );
        }

        /**
         * Action: 'woocommerce_admin_order_data_after_shipping_address'
         * Payment data on dashboard
         *
         * @return void
         */
        public function woocommerce_admin_order_data_after_shipping_address( $order ) {

            /**
             * If we haven't Extra_Checkout_Fields_For_Brazil_Order we already added to left side
             * @see woocommerce_admin_order_data_after_billing_address
             */
            if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil_Order' ) ) {
                return;
            }

            $this->woocommerce_admin_order_payment_data( $order );
        }

        /**
         * Action: 'add_meta_boxes'
         * Add metaboxed
         *
         * @return void
         */
        public function add_meta_boxes( $post_type, $post ) {
            if ( $post_type !== 'shop_order' || ! $this->can_create_payment_on_admin( $post ) ) {
                return;
            }

            add_meta_box( 'braspag-create-payment-meta-box', __( 'Pay with Braspag', WCB_TEXTDOMAIN ), array( $this, 'render_create_payment_meta_box' ), 'shop_order', 'side' );
        }

        /**
         * Action: 'woocommerce_process_shop_order_meta'
         * After saved all metaboxes
         *
         * @see WC_Admin_Meta_Boxes::__construct()
         * @see WC_Admin_Meta_Boxes::save_meta_boxes
         *
         * @return void
         */
        public function woocommerce_process_shop_order_meta( $post_id, $post ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ( empty( $_POST['braspag_payment_method'] ) || $_POST['braspag_payment_method'] === 'none' ) {
                return;
            }

            if ( ! $this->can_create_payment_on_admin( $post ) ) {
                return;
            }

            /**
             * Let's fake the
             */
            if ( ! function_exists( 'wc_add_notice' ) ) {
                function wc_add_notice( $message, $notice_type = 'success', $data = array() ) {
                    /* translators: %s is the message passed to notice. */
                    $message = sprintf( __( 'There was a problem creating your payment: %s.', WCB_TEXTDOMAIN ), $message );
                    WC_Admin_Meta_Boxes::add_error( $message );
                }
            }

            $order = wc_get_order( $post );

            try {
                $gateway = $this->get_gateway_object();
                $gateway->process_payment( $order->get_id() );

                $order->save();
            } catch ( Exception $e ) {
                error_log( '[WCB Create Payment Error] ' . $e->getMessage() );
            }
        }

        /**
         * Check we can create payment on Admin
         *
         * @param  WP_Post $post
         * @return boolean
         */
        private function can_create_payment_on_admin( $post ) {
            $order = wc_get_order( $post );
            $gateway = $this->get_gateway_object();
            if ( ! $order->needs_payment() || $order->get_payment_method() !== $gateway->id ) {
                return false;
            }

            $braspag_customer = new WC_Checkout_Braspag_Customer( $order );
            if ( ! empty( $braspag_customer->validate() ) ) {
                return false;
            }

            return true;
        }

        /**
         * Callback for create payment metabox
         *
         * @param  WP_Post $post
         * @return void
         *
         * @SuppressWarnings(PHPMD.UnusedLocalVariable)
         */
        public function render_create_payment_meta_box( $post ) {
            global $braspag_gateway;

            if ( empty( $braspag_gateway ) ) {
                $braspag_gateway = $this->get_gateway_object();
            }

            $order = wc_get_order( $post );

            require WCB_PLUGIN_PATH . '/modules/woocommerce/includes/views/meta-box/create-payment.php';
        }

        /**
         * Payment data on dashboard
         *
         * @return void
         */
        private function woocommerce_admin_order_payment_data( $order ) {
            $gateway = $this->get_gateway_object();

            if ( empty( $gateway ) || $order->get_payment_method() !== $gateway->id ) {
                return;
            }

            echo '<div class="clear"></div>';
            echo '<h3>' . esc_html__( 'Payment', WCB_TEXTDOMAIN ) . '</h3>';
            echo '<div class="braspag-payment"><p>';

            // Payment Info
            $payment = $order->get_meta( '_wc_braspag_payment_data' );

            if ( empty( $payment ) ) {
                esc_html_e( 'No payment info.', WCB_TEXTDOMAIN );
                echo '</p></div>';

                return;
            }

            /**
             * Filter payment info on dashboard
             *
             * @var array
             */
            $fields = apply_filters( 'wc_checkout_braspag_admin_order_payment_data', $this->get_payment_info( $order ), $payment, $order );
            foreach ( $fields as $field ) {
                echo '<strong>' . esc_html( $field['label'] ) . '</strong>: ' . $field['value'] . '<br>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo '</p></div>';
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

        /**
         * Get payment info from braspag data
         *
         * @param  WC_Order $order
         * @return array
         */
        private function get_payment_info( $order ) {
            $payment = $order->get_meta( '_wc_braspag_payment_data' );

            // Payment Type
            $methods = array();
            $gateway = $this->get_gateway_object();
            if ( ! empty( $gateway ) ) {
                $methods = $gateway->get_payment_methods();
            }

            $payment_type = $payment['Type'] ?? '-';
            foreach ( $methods as $method ) {
                if ( ( $method['code'] ?? '' ) !== $payment_type ) {
                    continue;
                }

                $payment_type = $method['name'];

                if ( ( $method['code'] ?? '' ) === 'CreditCard' && ! empty( $payment['Wallet'] ) ) {
                    $wallet = __( 'e-wallet', WCB_TEXTDOMAIN );

                    switch ( $payment['Wallet']['Type'] ?? '' ) {
                        case 'ApplePay':
                            $wallet = 'Apple Pay';
                            break;
                        case 'SamsungPay':
                            $wallet = 'Samsung Pay';
                            break;
                        case 'AndroidPay':
                            $wallet = 'Android Pay';
                            break;
                        case 'VisaCheckout':
                            $wallet = 'Visa Checkout';
                            break;
                        case 'Masterpass':
                            $wallet = 'Masterpass';
                            break;
                        default:
                            break;
                    }

                    $payment_type = sprintf( __( 'Credit Card (%s)', WCB_TEXTDOMAIN ), $wallet );
                }

                break;
            }

            // Payment Fields
            $fields = $this->payment_fields( $payment );

            // Card Token
            $card_token = $order->get_meta( '_wc_braspag_payment_card_token' );
            if ( ! empty( $card_token ) ) {
                $fields[] = array(
                    'label' => __( 'Card Token', WCB_TEXTDOMAIN ),
                    'value' => esc_html( $card_token['CardToken'] ),
                );
            }

            return array_merge(
                array(
                    array(
                        'label' => __( 'Type', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $payment_type ),
                    ),
                ),
                $fields
            );
        }

        /**
         * Get payment fields info from braspag data
         *
         * @param  array $payment
         * @return array
         */
        private function payment_fields( $payment ) {
            $type = $payment['Type'] ?? '';

            if ( $type === 'Boleto' ) {
                return array(
                    array(
                        'label' => __( 'Number', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $payment['BoletoNumber'] ?? '-' ),
                    ),
                    array(
                        'label' => __( 'Print bank slip', WCB_TEXTDOMAIN ),
                        'value' => '<a href="' . esc_url( $payment['Url'] ?? '#' ) . '" target="_blank">' . __( 'Link', WCB_TEXTDOMAIN ) . '</a>',
                    ),
                );
            }

            if ( $type === 'CreditCard' ) {
                $creditcard = $payment['CreditCard'] ?? [];

                return array(
                    array(
                        'label' => __( 'Installments', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $payment['Installments'] ?? '-' ),
                    ),
                    array(
                        'label' => __( 'Card Number', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $creditcard['CardNumber'] ?? '-' ),
                    ),
                    array(
                        'label' => __( 'Card Holder', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $creditcard['Holder'] ?? '-' ),
                    ),
                    array(
                        'label' => __( 'Card Expiration Date', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $creditcard['ExpirationDate'] ?? '-' ),
                    ),
                    array(
                        'label' => __( 'Card Brand', WCB_TEXTDOMAIN ),
                        'value' => esc_html( $creditcard['Brand'] ?? '-' ),
                    ),
                );
            }

            return array();
        }

    }

}
