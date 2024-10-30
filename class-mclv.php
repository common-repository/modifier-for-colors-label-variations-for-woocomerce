<?php
/**
 * Main class
 *
 */

if ( !defined( 'MCLV' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'MCLV' ) ) {
    /**
     *
     * @since 1.0.0
     */
    class MCLV {

        /**
         * Plugin object
         *
         * @var string
         * @since 1.0.0
         */
        public $obj = null;

        /**
         * Constructor
         *
         * @return mixed|MCLV_Admin|MCLV_Frontend
         * @since 1.0.0
         */
        public function __construct() {

            // actions
            add_action( 'init', array( $this, 'init' ) );

            if( is_admin() ) {
                $this->obj = new MCLV_Admin( MCLV_VERSION );
            }  else {
                $this->obj = new MCLV_Frontend( MCLV_VERSION );
            }

            // add new attribute types
            add_filter( 'product_attributes_type_selector', array( $this, 'attribute_types' ), 10, 1 );

        }

        /**
         * Add new attribute types to standard WooCommerce
         *
         */
        public function attribute_types( $default_type ){
            $custom = mclv_get_custom_tax_types();
            return is_array( $custom ) ? array_merge( $default_type, $custom ) : $default_type;
        }



        /**
         * Init method
         *
         * @access public
         * @since 1.0.0
         */
        public function init() {}

    }
}