<?php

/**
 * The Template for checkout form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/checkout-form.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. Just like WooCommerce.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

echo wpautop( $description );
