<?php

/**
 * Shared helpers for Pagador (Braspag) Checkout.
 *
 * @package Woo_Checkout_Braspag
 * @since   5.1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Resolve a WooCommerce order ID from a Braspag MerchantOrderId.
 *
 * Numeric MerchantOrderId values map to themselves by default. Non-numeric
 * values resolve to 0 unless a filter returns a valid order ID.
 *
 * @since 5.1.0
 *
 * @param string|int $merchant_order_id Raw MerchantOrderId from Braspag.
 * @return int
 */
function wc_checkout_braspag_get_order_id_from_merchant_order_id( $merchant_order_id ) {
	$order_id = is_numeric( $merchant_order_id ) ? absint( $merchant_order_id ) : 0;

	/**
	 * Filters the WooCommerce order ID resolved from a Braspag MerchantOrderId.
	 *
	 * @since 5.1.0
	 *
	 * @param int          $order_id           Default numeric cast (0 if non-numeric).
	 * @param string|int   $merchant_order_id  Raw MerchantOrderId from Braspag.
	 */
	return absint( apply_filters( 'wc_checkout_braspag_merchant_order_id_to_order_id', $order_id, $merchant_order_id ) );
}

/**
 * Get a WooCommerce order from a Braspag MerchantOrderId.
 *
 * @since 5.1.0
 *
 * @param string|int $merchant_order_id Raw MerchantOrderId from Braspag.
 * @return WC_Order|false
 */
function wc_checkout_braspag_get_order_by_merchant_order_id( $merchant_order_id ) {
	$order_id = wc_checkout_braspag_get_order_id_from_merchant_order_id( $merchant_order_id );

	return $order_id ? wc_get_order( $order_id ) : false;
}
