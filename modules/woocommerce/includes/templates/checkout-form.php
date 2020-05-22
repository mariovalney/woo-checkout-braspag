<?php
/**
 * The Template for checkout form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/braspag/checkout-form.php.
 *
 * HOWEVER, on occasion Woo Checkout Braspag will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. Just like WooCommerce.
 *
 * @var $description                string  The gateway description
 * @var $methods                    array   Array of payment methods: { code => name }
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

echo wpautop( esc_html( $description ) ); // phpcs:ignore

$methods = ( is_array( $methods ) ) ? $methods : [];

?>

<fieldset id="braspag-payment-form">

    <?php
        /**
         * Action before payment methods list
         */
        do_action( 'wc_checkout_braspag_checkout_form_before_payment_methods' );
    ?>

    <ul id="braspag-payment-methods">
        <?php
            $first_method = true;

            foreach ( $methods as $code => $name ) :
                $code = esc_attr( $code );

                if ( apply_filters( 'wc_checkout_braspag_checkout_form_skip_' . $code, false ) ) {
                    continue;
                }

                $name = esc_html( $name );

                $checked      = checked( true, $first_method, false );
                $active       = ( $first_method ) ? ' active' : '';
                $first_method = false;
        ?>

            <li>
                <label id="payment-method-<?php echo esc_attr( $code ); ?>-label" class="braspag-method-label<?php echo esc_attr( $active ); ?>">
                    <input id="braspag-payment-method-<?php echo esc_attr( $code ); ?>" type="radio" name="braspag_payment_method" value="<?php echo esc_attr( $code ); ?>" <?php echo esc_html( $checked ); ?>>
                    <span><?php echo esc_html( $name ); ?></span>
                </label>
            </li>

        <?php endforeach; ?>
    </ul>

    <?php
        /**
         * Action before payment methods list
         */
        do_action( 'wc_checkout_braspag_checkout_form_after_payment_methods' );
    ?>

<?php
    $first_method = true;

    foreach ( $methods as $code => $name ) {
        $code = esc_attr( $code );

        if ( apply_filters( 'wc_checkout_braspag_checkout_form_skip_' . $code, false ) ) {
            continue;
        }

        $active       = ( $first_method ) ? ' active' : '';
        $first_method = false;


        echo '<div id="braspag-payment-method-' . esc_attr( $code ) . '-form" class="braspag-method-form' . esc_attr( $active ) . '">';

        // Payment Method Template
        wc_get_template( 'payment-methods/' . $code . '-form.php', [], 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );

        echo '</div>';
    }
?>
</fieldset>
