=== Pagador (Braspag) Checkout for WooCommerce ===
Contributors: mariovalney, vizir
Donate link: https://github.com/Vizir/woo-checkout-braspag
Tags: woocommerce, payment, braspag, vizir, mariovalney
Requires at least: 4.7
Tested up to: 5.4
Requires PHP: 7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add Braspag payment to your WooCommerce e-commerce!

== Description ==

Add Braspag gateway to WooCommerce.

[Braspag](https://www.braspag.com.br) is a Brazilian payment gateway.

### Development ###

This plugin was developer using the [official docs](https://braspag.github.io) of gateway, without any support.

None of developers have link with Braspag or WooCommerce.

### Payment Methods ###

- Bank Slip
- Credit Card

### Compatibility ###

We tested this plugin against version 4.1+ of WooCommerce.

This plugin do not require [Brazilian Market on WooCommerce](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) but suggest your use.

Braspag only requires customer name, but more fields are need for anti fraud or other gateway features.

### Configuration ###

After installing the plugin, activate the payment method and go to the configuration page.

- Activate the payment method.
- Fill the title and description for this payment method.
- Add the "Merchant ID" provided by Braspag.
- Check the option "Sandbox" if the store is not in Production (available for real sale).
- Add the "Secret Merchant Key" provided by Braspag (note that it is different for Sandbox).

After that, just activate the available payment methods.

All of them require a "Provider" provided by Braspag and some settings: read the tips (icon with the question mark) for more information.

= Translations =

You can [translate Pagador (Braspag) Checkout for WooCommerce](https://translate.wordpress.org/projects/wp-plugins/woo-checkout-braspag) to your language.

== Installation ==

* Install "Pagador (Braspag) Checkout for WooCommerce" by plugins dashboard.

Or

* Upload the entire `woo-checkout-braspag` folder to the `/wp-content/plugins/` directory.

Then

* Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Does it works with Gutenberg? =

Yes. WooCommerce supports WordPress 5+ and we too.

= Does it works for another e-commerce plugin? =

Nope. This is a WooCommerce extension.

= I cannot add a payment on order administration =

To create a payment on admin you should:

- Add a name to "billing address".
- Make sure order is not empty and not paid (needs payment).
- Transaction ID must be empty.

= Transaction ID? =

The transaction ID is the Braspag number on your order.

If you already have a Transaction ID (payment was done in Braspag) you must use the relative Order Action to get information from Braspag.

= My orders are not being updated automatically =

You should configure a URL to receive notification from Braspag.

It should be: "example.com/?wc-api=WC_Checkout_Braspag_Gateway"

Do not forget to change "example.com" to your home url.

= Which URL I should inform to receive Braspag POST Notifications? =

Check the previous FAQ.

= What is PHP? =

It is a programming language for web development. PHP as like any software it has versions. And we just support 7 (and above).

If you are using PHP in version below 7, please contact your host to update your environment.

= Who are the developers? =

* [Vizir](http://vizir.com.br/en) is a Brazilian software studio.
* [Mário Valney](https://mariovalney.com/me) is a Brazilian developer who works at Vizir Software Studio and integrates the [WordPress community](https://profiles.wordpress.org/mariovalney).

= Can I help you? =

Yes! Visit [GitHub repository](https://github.com/Vizir/woo-checkout-braspag).

== Screenshots ==

1. Screenshot 1
2. Screenshot 2
3. Screenshot 3

== Changelog ==

= 2.0.1 =

* Translation fix

= 2.0.0 =

* Improved payment info on order
* Added customer validation on checkout
* Allow developers skip payment method on checkout
* Allow create payment on order administration

= 1.4.0 =

* Added payment info on order
* Added autofind for credit card brands
* Removing Debit Card as it's not tested

= 1.3.3 =

* Added payment info on mails

= 1.3.2 =

* Support to empty Credentials if already configured on Braspag

= 1.3.1 =

* Support to Issuer

= 1.3.0 =

* Fix cents on order amount and improve order validation

= 1.2.0 =

* Support to Safra

= 1.1.0 =

* Best file organization
* Added methods to work with ExtraDataCollection on Payment info

= 1.0 =

* It's alive!
* Receive payments with Braspag!

== Upgrade Notice ==

= 2.0.0 =

It's a MAJOR update!
We do not found any break changes, but a entire feature was added to admin.
Make a backup of files and database before update.
