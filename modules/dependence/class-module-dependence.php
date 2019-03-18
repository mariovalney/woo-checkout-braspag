<?php

/**
 * WCB_Module_Dependence
 * Module to notify about dependencies
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WCB_Module_Woocommerce
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WCB_Module_Dependence' ) ) {

    class WCB_Module_Dependence {

        /**
         * The plugin name to write in notices
         */
        const PLUGIN_NAME = 'WooCommerce Checkout Braspag';

        /**
         * List of dependencies to check
         * @var array
         */
        private $dependencies = [];

        /**
         * List of notices to show
         * @var array
         */
        private $notices = [];

        /**
         * Define Hooks Run
         *
         * @since    1.0.0
         * @return  void
         */
        public function define_hooks() {
            $this->core->add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }

        /**
         * After Run
         *
         * @since    1.0.0
         * @return  void
         */
        public function after_run() {
            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            // Check plugins
            include_once ABSPATH . 'wp-admin/includes/plugin.php';

            foreach ( $this->dependencies as $plugin ) {
                if ( is_plugin_active( $plugin->file ) ) {
                    continue;
                }

                if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin->file ) ) {
                    $notice = $this->create_activate_plugin_notice( $plugin );
                    $this->add_dependence_notice( $notice );
                    continue;
                }

                $notice = $this->create_install_plugin_notice( $plugin );
                $this->add_dependence_notice( $notice );
            }
        }

        /**
         * Add a plugin dependence
         *
         * @param string $plugin_file The plugin file like in is_plugin_active()
         * @param string $plugin_name The plugin name
         * @param string $plugin_slug The plugin slug (from repository)
         */
        public function add_dependence( $plugin_file, $plugin_name, $plugin_slug ) {
            $this->dependencies[] = (object) [
                'file' => $plugin_file,
                'name' => $plugin_name,
                'slug' => $plugin_slug,
            ];
        }

        /**
         * Add a notice
         *
         * @param string $notice The text
         * @param string $class  The HTML notice-$class
         */
        public function add_dependence_notice( $notice, $class = 'error' ) {
            $this->notices[] = [ $notice, $class ];
        }

        /**
         * Action: 'admin_notices'
         * Add notice about dependencies
         */
        public function admin_notices() {
            foreach ( $this->notices as $notice ) {
                $notice = $notice;
                include_once WCB_PLUGIN_PATH . '/modules/dependence/includes/views/html-notice.php';
            }
        }

        /**
         * Creates a notice to install a plugin
         *
         * @param  object $plugin A plugin data added with add_dependence
         * @return string
         */
        private function create_install_plugin_notice( $plugin ) {
            $url = wp_nonce_url(
                self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin->slug ),
                'install-plugin_' . $plugin->slug
            );

            return sprintf(
                __( '<strong>Assine JC</strong> depende do plugin %1$s para funcionar. Clique para %2$s.', WCB_TEXTDOMAIN ),
                $plugin->name,
                '<a href="' . esc_url( $url ) . '">' . __( 'instalar o plugin', WCB_TEXTDOMAIN ) . '</a>'
            );
        }

        /**
         * Creates a notice to activate a plugin
         *
         * @param  object $plugin A plugin data added with add_dependence
         * @return string
         */
        private function create_activate_plugin_notice( $plugin ) {
            $url = wp_nonce_url(
                self_admin_url( 'plugins.php?action=activate&plugin=' . $plugin->file ),
                'activate-plugin_' . $plugin->file
            );

            return sprintf(
                __( '<strong>Assine JC</strong> depende do plugin %1$s para funcionar. Clique para %2$s.', WCB_TEXTDOMAIN ),
                $plugin->name,
                '<a href="' . esc_url( $url ) . '">' . __( 'ativar o plugin', WCB_TEXTDOMAIN ) . '</a>'
            );
        }

    }
}
