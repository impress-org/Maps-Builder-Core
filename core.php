<?php
/**
 * Setup and load Google Maps Core lib
 *
 * @package     GMB-Core
 * @copyright   Copyright (c) 2016 WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Abort load if core path defined
 */
if ( defined( 'GMB_CORE_PATH' ) ) {
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

//Install Procees
if ( file_exists( GMB_CORE_PATH . 'includes/install.php' ) ) {
	require_once GMB_CORE_PATH . 'includes/install.php';
}

/**
 * Load plugin
 */
function gmb_core_init() {
	do_action( 'gmb_core_before_init' );
	add_action( 'plugins_loaded', array( Google_Maps_Builder(), 'instance' ) );
	add_action( 'widgets_init', array( Google_Maps_Builder(), 'init_widget' ) );
	do_action( 'gmb_core_init' );
}

add_action( 'plugins_loaded', 'gmb_core_init' );


/**
 * The main function responsible for returning the one true Maps Builder instance to function everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $gmb = Google_Maps_Builder(); ?>
 *
 * @since 2.0
 * @return object|Google_Maps_Builder
 */
function Google_Maps_Builder() {
	return Google_Maps_Builder::instance();
}

/**
 * Class Google_Maps_Builder_Core
 */
abstract class Google_Maps_Builder_Core {

	/**
	 * Activation Object
	 *
	 * @var Google_Maps_Builder_Activate
	 * @since 2.0
	 */
	public $activate;


	/**
	 * GMB Scripts Object
	 *
	 * @var Google_Maps_Builder_Scripts
	 * @since 2.0
	 */
	public $scripts;

	/**
	 * GMB Settings Object
	 *
	 * @var Google_Maps_Builder_Settings
	 * @since 2.0
	 */
	public $settings;

	/**
	 * GMB Engine Object
	 *
	 * @var Google_Maps_Builder_Engine
	 * @since 2.0
	 */
	public $engine;

	/**
	 * GMB Plugin Meta
	 *
	 * @var array
	 * @since 2.0
	 */
	public $meta;

	/**
	 * GMB HTML elements
	 *
	 * @var Google_Maps_Builder_HTML_Elements
	 * @since 2.0
	 */
	public $html;

	/**
	 * Include required files
	 *
	 * OVERRIDE IN PLUGIN
	 */
	protected function includes() {
		_doing_it_wrong( __FUNCTION__, __( 'Must overide.', 'google-maps-builder' ), '2.1.0' );
	}

	/**
	 * Get instance
	 *
	 * OVERRIDE IN PLUGIN
	 */
	public static function instance() {
		_doing_it_wrong( __FUNCTION__, __( 'Must overide.', 'google-maps-builder' ), '2.1.0' );
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object, therefore we don't want the object to be cloned.
	 *
	 * @since  2.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'google-maps-builder' ), '2.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since  2.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'google-maps-builder' ), '2.0' );
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since  2.0
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for Give's languages directory
		$gmb_lang_dir = dirname( plugin_basename( GMB_PLUGIN_FILE ) ) . '/languages/';
		$gmb_lang_dir = apply_filters( 'gmb_languages_directory', $gmb_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'google-maps-builder' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'google-maps-builder', $locale );

		// Setup paths to current locale file
		$mofile_local  = $gmb_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/gmb/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/gmb folder.
			load_textdomain( 'google-maps-builder', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/gmb/languages/ folder.
			load_textdomain( 'google-maps-builder', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'google-maps-builder', false, $gmb_lang_dir );
		}
	}

	/**
	 * Registers the Google Maps Builder Widget.
	 *
	 * @since 2.1.0
	 * @return void
	 */
	public function init_widget() {
		register_widget( 'Google_Maps_Builder_Widget' );
	}

	/**
	 * Get the CMB2 bootstrap!
	 *
	 * Checks to see if CMB2 plugin is installed first the uses included CMB2;
	 * we can still use it even it it's not active. This prevents fatal error conflicts with other themes and users of the CMB2 WP.org plugin.
	 *
	 */
	public function cmb2_load() {

		if ( file_exists( WP_PLUGIN_DIR . '/cmb2/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once WP_PLUGIN_DIR . '/cmb2/init.php';
		} elseif ( file_exists( GMB_CORE_PATH . '/includes/libraries/metabox/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once GMB_CORE_PATH . '/includes/libraries/metabox/init.php';
		} elseif ( file_exists( GMB_CORE_PATH . '/includes/libraries/CMB2/init.php' ) && ! defined( 'CMB2_LOADED' ) ) {
			require_once GMB_CORE_PATH . '/includes/libraries/CMB2/init.php';
		}

	}


	/**
	 * Load activation classes.
	 *
	 * @since 2.1.0
	 */
	public function load_activate() {
		require_once GMB_CORE_PATH . 'includes/class-gmc-activate.php';
		require_once GMB_PLUGIN_PATH . 'includes/class-gmb-activate.php';
	}

	/**
	 * Load maps admin.
	 *
	 * @since 2.1.0
	 */
	public function init_map_editor_admin() {
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-admin.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-admin.php';

		new Google_Maps_Builder_Admin();
	}

	/**
	 * Load files needed in both front-end and admin.
	 *
	 * @since 2.1.0
	 */
	public function load_files() {

		require_once GMB_CORE_PATH . 'includes/misc-functions.php';
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-settings.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-settings.php';

		require_once GMB_CORE_PATH . 'includes/class-gmc-engine.php';
		require_once GMB_PLUGIN_PATH . 'includes/class-gmb-engine.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-widget.php';

	}

	/**
	 * Load files that we need in the admin.
	 *
	 * @since 2.1.0
	 */
	public function load_admin() {

		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-core-interface.php';

		//Upgrades.
		require_once GMB_CORE_PATH . 'includes/admin/upgrades/upgrade-functions.php';
		require_once GMB_CORE_PATH . 'includes/admin/upgrades/upgrades.php';
		require_once GMB_CORE_PATH . 'includes/admin/system-info.php';
		require_once GMB_CORE_PATH . 'includes/admin/admin-actions.php';

		//shortcode generator.
		//@todo load conditionally
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-shortcode-generator.php';
		require_once GMB_PLUGIN_PATH . 'includes/admin/class-gmb-shortcode-generator.php';
		new GMB_Shortcode_Generator();

	}

	/**
	 * Base classes that need to load first.
	 *
	 * @since 2.1.0
	 */
	public function include_core_classes() {
		require_once GMB_CORE_PATH . 'includes/class-gmc-asset-paths.php';
		require_once GMB_CORE_PATH . 'includes/admin/class-gmc-core-interface.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-scripts-init.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-scripts.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-admin-scripts.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-frontend-scripts.php';
		require_once GMB_CORE_PATH . 'includes/class-gmc-html-elements.php';
	}


}