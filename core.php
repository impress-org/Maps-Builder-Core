<?php
/**
 * Setup and load Google Maps Core lib
 *
 * @package     GMB-Core
 * @copyright   Copyright (c) 2016 WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Abort load if
 */
if( defined( 'GMB_CORE_PATH' ) ) {
	return;
}

// Core Lib Folder Path
define( 'GMB_CORE_PATH', plugin_dir_path( __FILE__ ) );

// Core Lib  Folder URL
define( 'GMB_CORE_URL', plugin_dir_url( __FILE__ ) );

// Core Lib version
define( 'GMB_CORE_VERSION', '0.1.0' );

// Core Lib Root File
define( 'GMB_CORE_FILE', __FILE__ );

//Load

add_action( 'plugins_loaded', 'gmb_core_init' );
function gmb_core_init(){

}

function gmb_core_include_classes(){
	$files = glob( GMB_CORE_PATH . 'classes/*.php' );
	foreach( $files as $file ){
		include_once( $file );
	}
}
