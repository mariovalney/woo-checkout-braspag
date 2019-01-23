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
                $active = ( $first_method ) ? ' active' : '';
                $first_method = false;
        ?>

            <li>
                <label id="payment-method-<?php echo $code; ?>-label" class="braspag-method-label<?php echo $active; ?>">
                    <input id="braspag-payment-method-<?php echo $code; ?>" type="radio" name="braspag_payment_method" value="<?php echo $code; ?>" <?php echo $checked; ?>>
                    <span><?php echo $name; ?></span>
                </label>
            </li>

        <?php endforeach; ?>
    </ul>

    <?php
        $first_method = true;

        foreach ( $methods as $code => $name ) {
            $code = esc_attr( $code );

            $active = ( $first_method ) ? ' active' : '';
            $first_method = false;

            echo '<div id="braspag-payment-method-' . $code . '-form" class="braspag-method-form' . $active . '">';
            wc_get_template( 'payment-methods/' . $code . '-form.php', [], 'woocommerce/braspag/', WCB_WOOCOMMERCE_TEMPLATES );
            echo '</div>';
        }
    ?>
</fieldset>

