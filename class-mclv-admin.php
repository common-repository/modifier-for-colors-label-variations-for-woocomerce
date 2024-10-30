<?php
/**
 * Admin class
 *
 */

if ( !defined( 'MCLV' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'MCLV_Admin' ) ) {
    /**
     * Admin class.
     * The class manage all the admin behaviors.
     *
     * @since 1.0.0
     */
    class MCLV_Admin {
        /**
         * Constructor
         *
         * @access public
         * @since 1.0.0
         */
        public $version;

        public function __construct( $version ) {
            $this->version      = $version;
            
            //product attribute taxonomies 
            add_action('init', array($this, 'attribute_taxonomies'));

            //print attribute field type 
            add_action('mclv_print_attribute_field', array($this, 'print_attribute_type'), 10, 3);

            //save new term 
            add_action('created_term', array($this, 'attribute_save'), 10, 3);
            add_action('edit_term', array($this, 'attribute_save'), 10, 3);

            //choose variations in product page 
            add_action('woocommerce_product_option_terms', array($this, 'product_option_terms'), 10, 2);

            //enqueue static content 
            add_action('admin_enqueue_scripts', array($this, 'enqueue'));

            // Loaded 
            do_action( 'mclv_loaded' ); 

        }

        /**
         * Enqueue static content 
         */
        public function enqueue() {
            global $pagenow;

            if( in_array( $pagenow, array( 'term.php', 'edit-tags.php' ) ) && isset( $_GET['post_type'] ) && 'product' == $_GET['post_type'] ) {
                wp_enqueue_media();
                wp_enqueue_style( 'yith-wccl-admin', MCLV_URL . '/assets/css/admin.css', array('wp-color-picker'), $this->version );
                wp_enqueue_script( 'yith-wccl-admin', MCLV_URL . '/assets/js/admin.js', array('jquery', 'wp-color-picker' ), $this->version, true );
            }
        }

        /**
         * Init product attribute taxonomies 
         *
         * @access public
         * @since 1.0.0
         */
        public function attribute_taxonomies() {

            /* FIX WooCommerce 2.1.X */
            global $woocommerce;

            $attribute_taxonomies = function_exists('wc_get_attribute_taxonomies') ? wc_get_attribute_taxonomies() : $woocommerce->get_attribute_taxonomies();
            if ($attribute_taxonomies) {
                foreach ($attribute_taxonomies as $tax) {
                    
                    add_action(  wc_attribute_taxonomy_name( $tax->attribute_name ) . '_add_form_fields', array($this, 'add_attribute_field') );
                    add_action(  wc_attribute_taxonomy_name( $tax->attribute_name ) . '_edit_form_fields', array($this, 'edit_attribute_field'), 10, 2);

                    add_filter('manage_edit-' .  wc_attribute_taxonomy_name( $tax->attribute_name ) . '_columns', array($this, 'product_attribute_columns') );
                    add_filter('manage_' .  wc_attribute_taxonomy_name( $tax->attribute_name ) . '_custom_column', array($this, 'product_attribute_column'), 10, 3);
                }
            }
        }


        /**
         * Add field for each product attribute taxonomy 
         *
         * @access public
         * @since 1.0.0
         */
        public function add_attribute_field( $taxonomy ) {
            global $wpdb;

            $attribute = substr($taxonomy, 3);
            $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'");
            
            do_action('mclv_print_attribute_field', $attribute->attribute_type, false );
        }


        /**
         * Edit field for each product attribute taxonomy 
         *
         * @access public
         * @since 1.0.0
         */
        public function edit_attribute_field( $term, $taxonomy ) {
            global $wpdb;

            $attribute = substr( $taxonomy, 3 );

            $attribute = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'" );
            
            $value = mclv_get_term_meta( $term->term_id, $taxonomy . '_mclv_value' );

            do_action('mclv_print_attribute_field', $attribute, $value, 1);
        }


        /**
         * Print Color Picker Type HTML 
         *
         * @access public
         * @since 1.0.0
         */
        public function print_attribute_type($attribute, $value = '', $table = 0){

            isset($attribute->attribute_type)? $type = $attribute->attribute_type : $type = $attribute;
            
            $custom_types = mclv_get_custom_tax_types();
            $name_type = ucfirst($type);
            $re = '/two/';

            preg_match_all($re, $type, $matches, PREG_SET_ORDER, 0);

            if($matches){
                $values = substr($value, 0, 7);
                $values2 = substr($value, 7, 14);
                $type = 'two_colorpicker';
            }else{
                $values = $value;
            }
            
            if( $table ): ?>
             <tr class="form-field">
                <th scope="row" valign="top" ><label for="term-value"><?php echo $name_type; ?></label></th>
                <td>
            <?php else: ?>
            <div class="form-field">
                <label for="term-value"><?php $name_type; ?></label>
            <?php endif ?>

            <input type="text" name="term-value" id="term-value" value="<?php if ($values) echo $values  ?>" data-type="<?php echo $type ?>" />

            <input type="text" hidden="true" name="custom_types" value="<?php echo $type ?>" />

            <?php if( $type == 'two_colorpicker'): ?>
                <input type="text" name="term-value2" id="term-value2" value="<?php if ($values2) echo $values2  ?>" data-type="<?php echo $type ?>" />
                 </tr>
            <?php endif ?>
                <br/>
            <?php if( $table ): ?>
                </td>
                </tr>
            <?php else: ?>
                </div>
            <?php endif ?>
        <?php
        }


        /**
         * Save attribute field 
         *
         * @access public
         * @since 1.0.0
         */
        public function attribute_save($term_id, $tt_id, $taxonomy) {
            if (isset($_POST['term-value'])) {

                $type = $_POST['custom_types'];
                $val = $_POST['term-value'];

                if($type == 'two_colorpicker'){
                    $val .= $_POST['term-value2'];
                } 
                    
                mclv_update_term_meta( $term_id, $taxonomy . '_mclv_value', $val );
            }

        }

        /**
         * Create new column for product attributes 
         *
         * @access public
         * @since 1.0.0
         */
        public function product_attribute_columns( $columns ) {

            if( empty( $columns ) ) {
                return $columns;
            }

            $temp_cols = array();
            $temp_cols['cb'] = $columns['cb'];
            $temp_cols['mclv_value'] = __('Value', 'modifier-for-colors-labels-variations-for-woocommerce');
            unset($columns['cb']);
            $columns = array_merge( $temp_cols, $columns );
            return $columns;
        }

        /**
         * Print the column content 
         *
         * @access public
         * @since 1.0.0
         */
        public function product_attribute_column($columns, $column, $id) {
            global $taxonomy, $wpdb;

            if ($column == 'mclv_value') {
                $attribute = substr($taxonomy, 3);
                $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'");
                $att_type 	= $attribute->attribute_type;

                $value = mclv_get_term_meta( $id, $taxonomy . '_mclv_value' );
                $columns .= $this->_print_attribute_column( $value, $att_type );
            }

            return $columns;
        }


        /**
         * Print the column content according to attribute type 
         *
         */
        protected function _print_attribute_column( $value, $type ) {
            $output = '';

            //array with attribute
            $colorpicker = array('colorpicker', 'tooltip_color', 'color_desc');
            $two_colorpicker = array('two_colorpicker', 'two_color_desc', 'tooltip_two_color');
            $image = array('image', 'tooltip_image', 'desc_image');

            //chenge in switch
            switch ($type) {
                case ( in_array($type, $colorpicker)):
                    $output = '<span class="mclv-color" style="background-color:'. $value .'"></span>';
                    break;
                case 'round_color':
                    $output = '<span class="mclv-color mclv-round" style="background-color:'. $value .'"></span>';
                    break;
                case ( in_array($type, $two_colorpicker)):
                    $output = $this->_output_for_print_attribute($value, $new_class = '');
                    break;
                case 'round_two_color':
                    $output = $this->_output_for_print_attribute($value, $new_class = 'mclv-round');
                    break;
                case 'label':
                    $output = '<span class="mclv-label">'. $value .'</span>';
                    break;
                case ( in_array($type, $image)):
                    $output = '<img class="mclv-image" src="'. $value .'" alt="" />';
                    break;
                case 'round_image':
                    $output = '<img class="mclv-image mclv-round-img" src="'. $value .'" alt="" />';
                    break;
            }
            return $output;
        }

        /*
        * Additional function edit 2 color view
        *
        *
        */

        protected function _output_for_print_attribute($value, $new_class){
            $values = substr($value, 0, 7);
            $values2 = substr($value, 7, 14);
            $output = '<span class="mclv-color '.$new_class.'" style="background:linear-gradient(135deg, '. $values .' 51%, '. $values2 .' 51%);"></span>'; 
            return $output;
        }

        /**
         * Print select for product variations 
         *
         *
         */
        function product_option_terms( $tax, $i ) {
            global $woocommerce, $thepostid;

            $arr_for_attribute = array('colorpicker', 'two_colorpicker', 'image', 'round_color', 'round_two_color', 'round_image', 'color_desc', 'tooltip_color', 'two_color_desc', 'tooltip_two_color', 'tooltip_image', 'desc_image', 'label');

            if( in_array( $tax->attribute_type, $arr_for_attribute ) ) {

                if ( function_exists('wc_attribute_taxonomy_name') ) {
                    $attribute_taxonomy_name = wc_attribute_taxonomy_name( $tax->attribute_name );
                } else {
                    $attribute_taxonomy_name = $woocommerce->attribute_taxonomy_name( $tax->attribute_name );
                }

                ?>
	            <select multiple="multiple" data-placeholder="<?php _e( 'Select terms', 'modifier-for-colors-labels-variations-for-woocommerce' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo $i; ?>][]">
		            <?php
		            $all_terms = $this->get_terms( $attribute_taxonomy_name );
		            if ( $all_terms ) {
			            foreach ( $all_terms as $term ) {
				            echo '<option value="' . esc_attr( $term['value'] ) . '" ' . selected( has_term( absint( $term['id'] ), $attribute_taxonomy_name, $thepostid ), true, false ) . '>' . $term['name'] . '</option>';
			            }
		            }
		            ?>
	            </select>
                <button class="button plus select_all_attributes"><?php _e( 'Select all', 'modifier-for-colors-labels-variations-for-woocommerce' ); ?></button>
	            <button class="button minus select_no_attributes"><?php _e( 'Select none', 'modifier-for-colors-labels-variations-for-woocommerce' ); ?></button>
                <button class="button fr plus add_new_attribute" data-attribute="<?php echo $attribute_taxonomy_name; ?>"><?php _e( 'Add new', 'modifier-for-colors-labels-variations-for-woocommerce' ); ?></button>
                <?php
            }
        }

        /**
         * Get terms attributes array 
         *
         */
        protected function get_terms( $tax_name ) {

            global $wp_version;

            if( version_compare($wp_version, '4.5', '<' ) ) {
                $terms = get_terms( $tax_name, array(
                    'orderby'       => 'name',
                    'hide_empty'    => '0'
                ) );
            }
            else {
                $args = array(
                    'taxonomy'      => $tax_name,
                    'orderby'       => 'name',
                    'hide_empty'    => '0'
                );
                // get terms
                $terms = get_terms( $args );
            }
            $all_terms = array();

            foreach( $terms as $term ) {
                $all_terms[] = array(
                    'id'    => $term->term_id,
                    'value' => $term->term_id,
                    'name'  => $term->name
                );
            }

            return $all_terms;
        }
        
    }
}
