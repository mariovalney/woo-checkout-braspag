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

$methods = ( is_array( $methods ) ) ? $methods : [];

?>

<fieldset id="braspag-payment-form">
    <ul id="braspag-payment-methods">
        <?php
            $first_method = true;

            foreach ( $methods as $code => $name ) :
                $code = esc_attr( $code );
                $name = esc_html( $name );

                $checked = checked( true, $first_method, false );
                $first_method = false;
        ?>

            <li class="payment-method-<?php echo $code; ?>">
                <label>
                    <input id="braspag-payment-method-<?php echo esc_attr( $code ); ?>" type="radio" name="braspag_payment_method" value="<?php echo esc_attr( $code ); ?>" <?php echo $checked; ?>>
                    <?php echo $name; ?>
                </label>
            </li>

        <?php endforeach; ?>
    </ul>

    <?php
        foreach ( $methods as $code => $name ) {
            $code = esc_attr( $code );

            echo '<div id="braspag-' . $code . '-form" class="braspag-method-form">';
            wc_get_template( 'payment-methods/' . $code . '-form.php', [], 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );
            echo '</div>';
        }
    ?>
</fieldset>

