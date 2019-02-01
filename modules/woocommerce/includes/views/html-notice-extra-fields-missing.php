<?php
/**
 * Admin View: Notice - Extra Fields plugin is missing.
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$is_installed = false;

if ( function_exists( 'get_plugins' ) ) {
    $plugins = get_plugins();
    $is_installed = ( ! empty( $plugins[ WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_FILE ] ) );
}

if ( $is_installed ) {
    $url = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_FILE . '&plugin_status=active' ), 'activate-plugin_' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_FILE );
    $action = 'Activate the plugin';
} else {
    $url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_SLUG ), 'install-plugin_' . WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_SLUG );
    $action = 'Install the plugin';
}
?>

<div class="notice notice-error">
    <p><?php
        printf(
            __( '%s depends on the last version of %s to work!', WCB_TEXTDOMAIN ),
            '<strong>WooCommerce Checkout Braspag</strong>',
            WC_Checkout_Braspag_Gateway::EXTRA_FIELDS_PLUGIN_NAME
        );
    ?></p>

    <?php if ( current_user_can( 'install_plugins' ) ) : ?>
        <p>
            <a href="<?php echo esc_url( $url ); ?>" class="button button-primary">
                <?php _e( $action, WCB_TEXTDOMAIN ); ?>
            </a>
        </p>
    <?php endif; ?>
</div>