<?php
/**
 * Frontend class
 */

if ( !defined( 'MCLV' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'MCLV_Frontend' ) ) {
    /**
     * Frontend class.
     */
    class MCLV_Frontend {

        public $version;

        /**
         * Constructor
         *
         */
        public function __construct( $version ) {
            $this->version = $version;

            //Actions
            add_action('init', array( $this, 'init'));
            add_action('wp_enqueue_scripts', array( $this, 'enqueue_static'));

            //Override default WooCommerce add-to-cart/variable.php template
            add_action('template_redirect', array( $this, 'override'));

            //Loaded
            do_action('mclv_loaded');

            add_action('woocommerce_single_variation', array($this, 'single_variation'));
        }


        /**
         * Init method 
         *
         */
        public function init() {}


        /**
         * Override default template 
         */
        public function override() {
            remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
            add_action( 'woocommerce_variable_add_to_cart', array( $this, 'variable_add_to_cart' ), 30 );
        }


        /**
         * Output the variable product add to cart area. 
         */
        function variable_add_to_cart() {
            global $product;

            // Enqueue variation scripts
            wp_enqueue_script( 'wc-add-to-cart-variation' );

            $attributes = $product->get_variation_attributes();

            // get default attributes
            $selected_attributes = is_callable( array( $product, 'get_default_attributes' ) ) ? $product->get_default_attributes() : $product->get_variation_default_attributes();

            /** FIX WOO 2.1 */
            $wc_get_template = function_exists('wc_get_template') ? 'wc_get_template' : 'woocommerce_get_template';

            // Load the template
            $wc_get_template( 'single-product/add-to-cart/variable.php', array(
                'available_variations'  => $product->get_available_variations(),
                'attributes'   			=> $attributes,
                'selected_attributes' 	=> $selected_attributes,
                'attributes_types'      => $this->get_variation_attributes_types( $attributes )
            ), '', MCLV_DIR . 'templates/' );
        }


        /**
         * Get an array of types and values for each attribute 
         *
         * @access public
         * @since 1.0.0
         */
        public function get_variation_attributes_types( $attributes ) {
            global $wpdb;
            $types = array();

            if( !empty($attributes) ) {
                foreach( $attributes as $name => $options ) {
                    $attribute_name = substr($name, 3);
                    $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute_name'");
                    if ( isset( $attribute ) ) {
                        $types[$name] = $attribute->attribute_type;
                    }
                    else {
                        $types[$name] = 'select';
                    }
                }
            }

            return $types;
        }


        /**
         * Enqueue frontend styles and scripts 
         *
         */
        public function enqueue_static() {
            global $post, $woocommerce;

			wp_register_script( 'mclv_frontend', MCLV_URL . 'assets/js/frontend.js', array('jquery', 'tooltipster', 'wc-add-to-cart-variation'), $this->version, true );
            wp_register_script( 'tooltipster', MCLV_URL . 'assets/js/tooltipster.bundle.min.js', array('jquery'), $this->version, true );
			wp_register_style( 'mclv_frontend', MCLV_URL . 'assets/css/frontend.css', false, $this->version );
            wp_register_style( 'mclv_tooltipster', MCLV_URL . 'assets/css/tooltipster.bundle.min.css', false, $this->version );
            wp_register_style( 'mclv_tooltipster-sideTip-borderless', MCLV_URL . 'assets/css/tooltipster-sideTip-borderless.min.css', false, $this->version );

            if( is_product() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[product_page' ) ) ) {
                wp_enqueue_script('tooltipster');
                wp_enqueue_script('mclv_frontend');
                wp_enqueue_style('mclv_frontend');
                wp_enqueue_style('mclv_tooltipster');
                wp_enqueue_style('mclv_tooltipster-sideTip-borderless');
            }
        }

        /**
         * single variation template for variable
         *
         */
        public function single_variation() {

            global $product, $woocommerce;

            if( version_compare( preg_replace( '/-beta-([0-9]+)/', '', $woocommerce->version ), '2.4', '>=' ) ) {
                return;
            }
                
            $product_id = mclv_check_wc_version( '2.6', '>=' ) ? $product->get_id() : $product->id;
            
            ob_start();

            ?>

            <div class="single_variation"></div>
            <div class="variations_button">

                <?php woocommerce_quantity_input(); ?>
                <button type="submit" class="single_add_to_cart_button button alt"><?php echo apply_filters('single_add_to_cart_text', __( 'Add to cart', 'woocommerce' ), $product->product_type); ?></button>
            </div>

            <input type="hidden" name="add-to-cart" value="<?php echo $product_id; ?>" />
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
            <input type="hidden" name="variation_id" class="variation_id" value="" />

            <?php

            echo ob_get_clean();
        }

    }
}
