<?php
/**
 * Plugin Name: Modifier For Colors Label Variations For Woocomerce
 * Plugin URI: 
 * Description: By using my woocommerce plugin you can generate color and image swatches to display the available product variable attributes like colors, sizes, styles etc.
 * Version: 1.0.0
 * Author: Stepanchenko Nikolay
 * Author URI: https://github.com/woop666
 *
 */
/* 

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110 1301  USA
*/
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! function_exists( 'mclv_plugin_registration_hook' ) ) {
	require_once 'plugin-options/mclv-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'mclv_plugin_registration_hook' );

if ( ! defined( 'MCLV' ) ) {
	define( 'MCLV', true );
}
if ( ! defined( 'MCLV_URL' ) ) {
	define( 'MCLV_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'MCLV_DIR' ) ) {
	define( 'MCLV_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MCLV_VERSION' ) ) {
	define( 'MCLV_VERSION', '1.0.0' );

}

function mclv_constructor() {
    global $woocommerce;
    if ( ! isset( $woocommerce ) ) return;

    load_plugin_textdomain( 'colors-labels-variations', false, dirname( plugin_basename( __FILE__ ) ). '/languages/' );

    // Load required classes and functions
    require_once('functions.php');
    require_once('class-mclv-admin.php');
    require_once('class-mclv-frontend.php');
    require_once('class-mclv.php');

    // Let's start the game!
    global $mclv;
    $mclv = new MCLV();
}

add_action('plugins_loaded', 'mclv_constructor' );