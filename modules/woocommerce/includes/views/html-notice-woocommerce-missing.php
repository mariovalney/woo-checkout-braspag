<?php
/**
 * Admin View: Notice - WooCommerce missing.
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$plugin_slug = 'woocommerce';

if ( current_user_can( 'install_plugins' ) ) {
    $url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
} else {
    $url = 'http://wordpress.org/plugins/' . $plugin_slug;
}

?>

<div class="notice notice-error">
    <p><?php
        printf(
            __( '%s depends on the last version of %s to work!', WCB_TEXTDOMAIN ),
            '<strong>WooCommerce Checkout Braspag</strong>',
            '<a href="' . esc_url( $url ) . '">' . __( 'WooCommerce', WCB_TEXTDOMAIN ) . '</a>'
        );
    ?></p>
</div>
