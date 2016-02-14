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
	do_action( 'gmb_core_before_init' );
	$Google_Maps_Builder_Core = new Google_Maps_Builder_Core();
	do_action( 'gmb_core_init', $Google_Maps_Builder_Core );
}

class Google_Maps_Builder_Core{

	/**
	 * Get the CMB2 bootstrap!
	 *
	 * @description: Checks to see if CMB2 plugin is installed first the uses included CMB2; we can still use it even it it's not active. This prevents fatal error conflicts with other themes and users of the CMB2 WP.org plugin
	 *
	 */
	public static function cmb2_load(){

		if ( file_exists( WP_PLUGIN_DIR . '/cmb2/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once WP_PLUGIN_DIR . '/cmb2/init.php';
		} elseif ( file_exists( GMB_CORE_PATH . '/includes/libraries/metabox/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once GMB_CORE_PATH . '/includes/libraries/metabox/init.php';
		} elseif ( file_exists( GMB_CORE_PATH . '/includes/libraries/CMB2/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once GMB_CORE_PATH . '/includes/libraries/CMB2/init.php';
		}

	}

	/**
	 * Load activation classes
	 *
	 * @since 2.1.0
	 */
	public static function load_activate(){
		require_once GMB_CORE_PATH . 'includes/class-gmc-activate.php';
		require_once GMB_PLUGIN_PATH . 'includes/class-gmb-activate.php';
	}

	/**
	 * Load maps admin
	 *
	 * @since 2.1.0
	 *
	 * @TODO add CMB2 stuff here
	 */
	public static function init_map_editor_admin(){
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-admin.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-admin.php';

		new Google_Maps_Builder_Admin();
	}

	/**
	 * Load files needed in front-end and admin
	 *
	 * @since 2.1.0
	 */
	public static function load_files(){
		require_once GMB_CORE_PATH . 'includes/misc-functions.php';
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-settings.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-settings.php';

		require_once GMB_CORE_PATH . 'includes/class-gmc-engine.php';
		require_once GMB_PLUGIN_PATH . 'includes/class-gmb-engine.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-widget.php';
	}

	/**
	 * Load files that we need in the admin
	 *
	 * @since 2.1.0
	 */
	public static function load_admin(){
		//Upgrades
		require_once GMB_CORE_PATH . 'includes/admin/upgrades/upgrade-functions.php';
		require_once GMB_CORE_PATH . 'includes/admin/upgrades/upgrades.php';



		require_once GMB_CORE_PATH . 'includes/admin/system-info.php';

		require_once GMB_CORE_PATH . 'includes/admin/admin-actions.php';



		//shortcode generator
		//@todo load conditionally
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-shortcode-generator.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-shortcode-generator.php';
		new GMB_Shortcode_Generator();

	}

	public static function include_core_classes(){
		require_once GMB_CORE_PATH . 'includes/class-gmc-scripts.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-admin-scripts.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-frontend-scripts.php';
	}


}
