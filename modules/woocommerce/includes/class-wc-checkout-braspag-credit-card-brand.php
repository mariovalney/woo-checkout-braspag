<?php

/**
 * WC_Checkout_Braspag_Credit_Card_Brand
 * Class responsible to help credit card search
 *
 * @link https://en.wikipedia.org/wiki/Payment_card_number
 * @link https://github.com/erikhenrique/bin-cc
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Credit_Card_Brand
 * @since           1.0.0
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Credit_Card_Brand' ) ) {

    class WC_Checkout_Braspag_Credit_Card_Brand {

        /**
         * The brand for SANDBOX provider
         *
         * @var string
         */
        const SANDBOX = 'Visa';

        /**
         * The credit card number
         *
         * @var string
         */
        private $number = '';

        /**
         * The constructor
         *
         * @param string
         */
        public function __construct( $number ) {
            $this->number = preg_replace( '/\D*/', '', $number );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_amex() {
            return $this->iin_is_between( [ 34, 37 ] );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_aura() {
            return $this->iin_is_between( 5078 );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_diners() {
            return $this->iin_is_between( 36 ) || $this->iin_is_between( 38, 39 )
                   || $this->iin_is_between( 300, 305 ) || $this->iin_is_between( 3095 );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_discover() {
            return $this->iin_is_between( [ 64, 65, 6011 ] ) || $this->iin_is_between( 622126, 622925 )
                   || $this->iin_is_between( 624000, 626999 ) || $this->iin_is_between( 628200, 628899 );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_elo() {
            $ranges = array(
                4011,
                431274,
                438935,
                451416,
                457393,
                4576,
                457631,
                457632,
                504175,
                627780,
                636297,
                636368,
                636369,
                array( 506699, 506778 ),
                array( 509000, 509999 ),
                array( 650031, 650033 ),
                array( 650035, 650051 ),
                array( 650405, 650439 ),
                array( 650485, 650538 ),
                array( 650541, 650598 ),
                array( 650700, 650718 ),
                array( 650720, 650727 ),
                array( 650901, 650920 ),
                array( 651652, 651679 ),
                array( 655000, 655019 ),
                array( 655021, 655058 ),
            );

            foreach ( $ranges as $range ) {
                $range = (array) $range;

                if ( $this->iin_is_between( $range[0], $range[1] ?? null ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_hipercard() {
            return $this->iin_is_between( [ 384100, 384140, 384160, 606282, 637095, 637568, 637599, 637609, 637612 ] );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_jcb() {
            return $this->iin_is_between( 35 );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_maestro() {
            return $this->iin_is_between( 50 ) || $this->iin_is_between( 56, 69 );
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_master() {
            return $this->iin_is_between( 51, 55 ) || $this->iin_is_between( 222100, 272099 ) || $this->is_maestro();
        }

        /**
         * Check is a visa card
         *
         * @return boolean
         */
        public function is_visa() {
            return $this->iin_is_between( 4 );
        }

        /**
         *
         * @return
         */
        private function iin_is_between( $start, $end = null, $size = 6 ) {
            if ( is_array( $start ) ) {
                foreach ( $start as $value ) {
                    if ( $this->iin_is_between( $value, $end, $size ) ) {
                        return true;
                    }
                }

                return false;
            }

            $iin = (int) substr( $this->number, 0, $size );

            $end = (int) str_pad( $end ?: $start, $size, '9' );
            $start = (int) str_pad( $start, $size, '0' );

            return $start <= $iin && $iin <= $end;
        }

        /**
         * Get Credit Card provider as option
         *
         * @return array
         */
        public static function find_brand( $number ) {
            $rules = new self( $number );

            $brands = array(
                'Amex',
                'Aura',
                'Diners',
                'Discover',
                'Elo',
                'Hipercard',
                'Jcb',
                'Master',
                'Visa',
            );

            foreach ( $brands as $brand ) {
                $method = 'is_' . strtolower( $brand );
                if ( ! method_exists( $rules, $method ) ) {
                    continue;
                }

                if ( call_user_func( array( $rules, $method ) ) ) {
                    return $brand;
                }
            }

            return '';
        }

    }

}

