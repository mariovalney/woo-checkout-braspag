<?php

/**
 * WC_Checkout_Braspag_Providers
 * Class responsible to manage Braspag Providers
 *
 * @link https://braspag.github.io/manual/braspag-pagador?json#lista-de-providers
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Providers
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Providers' ) ) {

    class WC_Checkout_Braspag_Providers {

        /**
         * Sandbox Provider
         */
        const SANDBOX = 'Simulado';

        /**
         * Credit Card Providers
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#providers-para-cr%C3%A9dito
         * @version 2.2.1 23/09/2020
         */
        const CREDIT_CARD = array(
            'Cielo'          => array(
                'brands'      => [ 'Visa', 'Master', 'Amex', 'Elo', 'Aura', 'Jcb', 'Diners', 'Discover' ],
                'description' => 'Plataforma legado Cielo 1.5',
            ),
            'Cielo30'        => array(
                'brands'      => [ 'Visa', 'Master', 'Amex', 'Elo', 'Aura', 'Jcb', 'Diners', 'Discover', 'Hipercard', 'Hiper' ],
                'description' => 'Plataforma de e-commerce Cielo 3.0',
            ),
            'Redecard'       => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Hiper', 'Diners' ],
                'description' => 'Plataforma legado Rede Komerci',
            ),
            'Rede'           => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Hiper', 'Diners', 'Elo', 'Amex' ],
                'description' => 'Plataforma de e-commerce Rede e-Rede na versão SOAP',
            ),
            'Rede2'          => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Hiper', 'Diners', 'Elo', 'Amex' ],
                'description' => 'Plataforma de e-commerce Rede e-Rede na versão REST',
            ),
            'Getnet'         => array(
                'brands'      => [ 'Visa', 'Master', 'Elo', 'Amex' ],
                'description' => 'Plataforma de e-commerce GetNet',
            ),
            'GlobalPayments' => array(
                'brands'      => [ 'Visa', 'Master' ],
                'description' => 'Plataforma de e-commerce Global Payments',
            ),
            'Stone'          => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Elo' ],
                'description' => 'Plataforma de e-commerce Stone',
            ),
            'Safra'          => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Elo', 'Amex' ],
                'description' => 'Provider para transações na plataforma de e-commerce Safra (legado)',
            ),
            'Safra2'          => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Elo', 'Amex' ],
                'description' => 'Provider para transações na plataforma de e-commerce Safra',
            ),
            'FirstData'      => array(
                'brands'      => [ 'Visa', 'Master', 'Cabal' ],
                'description' => 'Plataforma de e-commerce First Data: transações em guaranis (PYG), pesos argentinos (ARG) e reais (BRL)',
            ),
            'Sub1'           => array(
                'brands'      => [ 'Visa', 'Master', 'Diners', 'Amex', 'Discover', 'Cabal', 'Naranja', 'Nevada' ],
                'description' => 'Plataforma legado Sub1 First Data: transações em pesos argentinos (ARG)',
            ),
            'Banorte'        => array(
                'brands'      => [ 'Visa', 'Master', 'Carnet' ],
                'description' => 'Plataforma de e-commerce Banorte: transações em pesos mexicanos (MXN)',
            ),
            'Credibanco'     => array(
                'brands'      => [ 'Visa', 'Master', 'Diners', 'Amex', 'Credential' ],
                'description' => 'Plataforma de e-commerce Credibanco: transações em pesos colombianos (COP)',
            ),
            'Transbank'      => array(
                'brands'      => [ 'Visa', 'Master', 'Diners', 'Amex' ],
                'description' => 'Plataforma de e-commerce Transbank: transações em pesos chilenos (CLP)',
            ),
            'RedeSitef'      => array(
                'brands'      => [ 'Visa', 'Master', 'Hipercard', 'Diners' ],
                'description' => 'Plataforma Rede com tecnologia TEF',
            ),
            'CieloSitef'     => array(
                'brands'      => [ 'Visa', 'Master', 'Amex', 'Elo', 'Aura', 'Jcb', 'Diners', 'Discover' ],
                'description' => 'Plataforma Cielo com tecnologia TEF',
            ),
            'SantanderSitef' => array(
                'brands'      => [ 'Visa', 'Master' ],
                'description' => 'Plataforma GetNet com tecnologia TEF',
            ),
            'Banese'         => array(
                'brands'      => [ 'Banese' ],
                'description' => 'Provider para transações com a bandeira BaneseCard',
            ),
            'BrasilCard'     => array(
                'brands'      => [ 'BrasilCard' ],
                'description' => 'Provider para transações com a bandeira BrasilCard',
            ),
            'Credsystem'     => array(
                'brands'      => [],
                'description' => 'Sistema de cartões em regime de bandeira privativa (Private Label Brand)',
            ),
            'Credz'          => array(
                'brands'      => [ 'Credz' ],
                'description' => 'Sistema de cartões em regime de bandeira privativa (Private Label Brand)',
            ),
            'DMCard'         => array(
                'brands'      => [],
                'description' => 'Sistema de cartões em regime de bandeira privativa (Private Label Brand)',
            ),
        );

        /**
         * Debit Card Providers
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#providers-pra-d%C3%A9bito
         * @version 2.2.1 23/09/2020
         */
        const DEBIT_CARD = array(
            'Cielo'   => array(
                'brands'      => [ 'Visa', 'Master' ],
                'description' => 'Débito na plataforma legado Cielo 1.5',
            ),
            'Cielo30' => array(
                'brands'      => [ 'Visa', 'Master' ],
                'description' => 'Débito na plataforma de e-commerce Cielo 3.0',
            ),
            'Getnet'            => array(
                'brands'        => [ 'Visa', 'Master' ],
                'description'   => 'Débito na plataforma de e-commerce GetNet',
            ),
            'Rede2'             => array(
                'brands'        => [ 'Visa', 'Master' ],
                'description'   => 'Débito na plataforma de e-commerce Rede',
            ),
            'Safra2'            => array(
                'brands'        => [ 'Visa', 'Master' ],
                'description'   => 'Débito na plataforma de e-commerce Safra',
            ),
            'FirstData'         => array(
                'brands'        => [ 'Visa', 'Master' ],
                'description'   => 'Débito na plataforma de e-commerce First Data',
            ),
            'GlobalPayments'    => array(
                'brands'        => [ 'Visa', 'Master' ],
                'description'   => 'Débito na plataforma de e-commerce Global Payments',
            ),
        );

        /**
         * Bank Slip Providers
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#providers-para-boleto-com-registro
         * @version 2.2.1 23/09/2020
         */
        const BANK_SLIP = array(
            'Bradesco2'      => array(
                'description' => 'Boleto registrado do Bradesco',
            ),
            'BancoDoBrasil2' => array(
                'description' => 'Boleto registrado do Banco do Brasil',
            ),
            'ItauShopline'   => array(
                'description' => 'Boleto registrado do Itaú (Shopline)',
            ),
            'Itau2'          => array(
                'description' => 'Boleto registrado do Itaú',
            ),
            'Santander2'     => array(
                'description' => 'Boleto registrado do Santander',
            ),
            'Caixa2'         => array(
                'description' => 'Boleto registrado da Caixa Econômica',
            ),
            'CitiBank2'      => array(
                'description' => 'Boleto registrado do Citi Bank',
            ),
            'BankOfAmerica'  => array(
                'description' => 'Boleto registrado do Bank of America',
            ),
        );

        /**
         * E-Wallet Providers
         *
         * @link https://braspag.github.io/manual/braspag-pagador#requisi%C3%A7%C3%A3o-padr%C3%A3o
         * @version 1.0.0 14/08/2020
         */
        const E_WALLET = array(
            'Cielo'          => array(
                'description' => 'Plataforma legado Cielo 1.5',
                'wallets'     => [ 'ApplePay', 'SamsungPay', 'AndroidPay', 'VisaCheckout', 'Masterpass' ],
            ),
            'Cielo30'        => array(
                'description' => 'Plataforma de e-commerce Cielo 3.0',
                'wallets'     => [ 'ApplePay', 'SamsungPay', 'AndroidPay', 'VisaCheckout', 'Masterpass' ],
            ),
        );

        /**
         * Eletronic Transfer Providers
         *
         * @link https://braspag.github.io/manual/braspag-pagador?json#providers-para-transfer%C3%AAncia-eletronica-(d%C3%A9bito-online)
         * @version 2.2.1 23/09/2020
         */
        const ELETRONIC_TRANSFER = array(
            'Bradesco'               => array(
                'description' => 'Débito online no Bradesco',
            ),
            'BancoDoBrasil'          => array(
                'description' => 'Débito online no Banco do Brasil',
            ),
            'SafetyPay'              => array(
                'description' => 'Débito online no Safety Pay',
            ),
            'Itau'                   => array(
                'description' => 'Débito online no Itaú',
            ),
            'PayMeeRedirectCheckout' => array(
                'description' => 'Débito online no PayMee Redirect Checkout',
            ),
            'PayMeeSemiTransparent'  => array(
                'description' => 'Débito online no PayMee Semi Transparent',
            ),
        );

        public function __construct() {}

        /**
         * Get Credit Card provider as option
         *
         * @return array
         */
        public static function get_provider_as_option( $providers ) {
            $options = array(
                '' => __( '-- Choose your provider', WCB_TEXTDOMAIN ),
            );

            ksort( $providers );

            foreach ( $providers as $key => $value ) {
                $options[ $key ] = sprintf( '%s - %s', $key, __( $value['description'], WCB_TEXTDOMAIN ) ); // phpcs:ignore
            }

            /**
             * Filter provider option fomat
             *
             * DO NOT change $key of option as we validate it from constants to create request
             * Use to change any description or remove options.
             */
            return apply_filters( 'wc_checkout_braspag_providers_as_option', $options, $providers );
        }

    }

}
