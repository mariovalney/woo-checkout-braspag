=== Pagador (Braspag) Checkout for WooCommerce ===
Contributors: mariovalney, vizir
Donate link: https://github.com/Vizir/woo-checkout-braspag
Tags: woocommerce, payment, braspag, vizir, mariovalney
Requires at least: 4.7
Tested up to: 5.2
Requires PHP: 7.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add Braspag payment to your WooCommerce e-commerce!

== Description ==

Add Braspag gateway to WooCommerce.

[Braspag](https://www.braspag.com.br) is a Brazilian payment gateway so we will focus on pt_BR documentation.

### Desenvolvimento ###

Este plugin foi desenvolvido a partir da [documentação oficial](https://braspag.github.io) do Pagador, sem nenhum apoio oficial.

Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

### Formas de Pagamento ###

Até o momento estão disponíveis:

- Boleto Bancário
- Cartão de Crédito
- Cartão de Débito (ainda em testes)

### Compatibilidade ###

Esse plugin foi desenvolvido e testado na versão 3.8+ do WooCommerce.

Este plugin é compatível com o [Brazilian Market on WooCommerce](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

### Configuração ###

Após instalar o plugin, ative a forma de pagamento normalmente e vá para a página de configuração.

- Ative a forma de pagamento.
- Dê um título e descrição para essa forma de pagamento.
- Adicione o "Merchant ID" fornecido pela Braspag.
- Marque a opção "Sandbox" se a loja não estiver em Produção (disponível para venda real).
- Adicione a "Secret Merchant Key" fornecida pela Braspag (observe que ela é diferente para Sandbox).

Após isso basta ativar as formas de pagamento que deseja disponibilizar.
Todas elas necessitam de um "Provider" fornecido pela Braspag, bem como algumas configurações particulares: leia as dicas (ícone com a interrogação) para mais informações.

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

= 1.3.0 =

Update to the new version!
