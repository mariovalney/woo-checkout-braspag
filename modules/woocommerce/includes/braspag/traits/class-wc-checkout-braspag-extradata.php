<?php

/**
 * WC_Checkout_Braspag_Traits_Extradata
 * Class responsible to creat a request to Braspag API
 *
 * @package         Woo_Checkout_Braspag
 * @subpackage      WC_Checkout_Braspag_Traits_Extradata
 * @since           1.0.0
 *
 */

// If this file is called directly, call the cops.
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'WC_Checkout_Braspag_Traits_Extradata' ) ) {

    trait WC_Checkout_Braspag_Traits_Extradata {
        /**
         * Get a value from data
         *
         * @param string  $name
         * @param boolean  $single
         * @return mixed  The item if single or array of items. If not found returns false or empty array.
         */
        public function get_extradata( $name, $single = false ) {
            $items = ( $single ) ? false : [];

            foreach ( $this->get_extradata_collection() as $item ) {
                if ( empty( $item->Name ) || (string) $item->Name !== (string) $name ) {
                    continue;
                }

                if ( $single ) {
                    return $item;
                }

                $items[] = $item;
            }

            return $items;
        }

        /**
         * Add data to collection
         *
         * @param string  $name
         * @param mixed  $value
         * @param boolean $unique
         */
        public function add_extradata( $name, $value, $unique = false ) {
            if ( $unique ) {
                $this->delete_extradata( $name, $value );
            }

            $collection = $this->get_extradata_collection();

            // Add item
            $collection[] = (object) [
                'Name'  => $name,
                'Value' => $value,
            ];

            $this->set_extradata_collection( $collection );
        }

        /**
         * Update a value (add a unique one)
         *
         * @param string  $name
         * @param mixed  $value
         */
        public function update_extradata( $name, $value ) {
            $this->add_extradata( $name, $value, true );
        }

        /**
         * Remove all values with a name
         *
         * @param string  $name
         */
        public function delete_extradata( $name ) {
            $collection = $this->get_extradata_collection();

            $collection = array_filter(
                $collection,
                function( $item ) use ( $name ) {
                    if ( empty( $item->Name ) ) {
                        return false;
                    }

                    return (string) $item->Name !== (string) $name;
                }
            );

            $this->set_extradata_collection( $collection );
        }

        /**
         * Return the collection
         *
         * @return array
         */
        public function get_extradata_collection() {
            if ( empty( $this->Payment ) || empty( $this->Payment['ExtraDataCollection'] ) || ! is_array( $this->Payment['ExtraDataCollection'] ) ) {
                return [];
            }

            return $this->Payment['ExtraDataCollection'];
        }

        /**
         * Set the collection
         *
         * @param array
         */
        public function set_extradata_collection( $collection ) {
            if ( empty( $this->Payment ) ) {
                $this->Payment = [];
            }

            $this->Payment['ExtraDataCollection'] = (array) $collection;
        }

    }

}
