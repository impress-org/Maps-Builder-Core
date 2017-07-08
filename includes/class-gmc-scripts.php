<?php
/**
 * Base class for loading scripts.
 *
 * @package     GMB
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

/**
 * Class Google_Maps_Builder_Core_Scripts
 */
abstract class Google_Maps_Builder_Core_Scripts {

	/**
	 * The plugin's settings
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	protected $plugin_settings;

	/**
	 * Var for loading google maps api
	 * Var for dependency
	 *
	 * @var bool
	 */
	protected $google_maps_conflict = false;

	/**
	 * Asset paths
	 *
	 * @since 2.1.0
	 *
	 * @var Google_Maps_Builder_Core_Asset_Paths
	 */
	protected $paths;

	/**
	 * Google_Maps_Builder_Core_Scripts constructor.
	 */
	public function __construct(){
		$this->paths = Google_Maps_Builder_Core_Asset_Paths::get_instance();
		$this->plugin_settings = get_option( 'gmb_settings' );
		if( is_admin() ) {
			add_action( 'admin_print_scripts', array( $this, 'check_for_multiple_google_maps_api_calls' ) );
		}else{
			add_action( 'wp_print_scripts', array( $this, 'check_for_multiple_google_maps_api_calls' ) );

		}
		$this->hooks();
	}

	/**
	 * Use to add hooks in parent class
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function hooks() {
		//left empty since can't declare abstract in dinoPHP
	}

	/**
	 * Load Google Maps API
	 *
	 * Determine if Google Maps API script has already been loaded
	 * @since      : 1.0.3
	 * @return bool $multiple_google_maps_api
	 */
	public function check_for_multiple_google_maps_api_calls() {
		global $wp_scripts;

		if ( ! $wp_scripts ) {
			return false;
		}

		//loop through registered scripts
		foreach ( $wp_scripts->registered as $registered_script ) {

			//find any that have the google script as the source, ensure it's not enqueud by this plugin
			if (
				( strpos( $registered_script->src, 'maps.googleapis.com/maps/api/js' ) !== false &&
				  strpos( $registered_script->handle, 'google-maps-builder' ) === false ) ||
				( strpos( $registered_script->src, 'maps.google.com/maps/api/js' ) !== false &&
				  strpos( $registered_script->handle, 'google-maps-builder' ) === false )
			) {

				//Remove this script from loading
				wp_deregister_script( $registered_script->handle );
				wp_dequeue_script( $registered_script->handle );


				$this->google_maps_conflict = true;
				//ensure we can detect scripts on the frontend from backend; we'll use an option to do this
				if ( ! is_admin() ) {
					update_option( 'gmb_google_maps_conflict', true );
				}

			}

		}

		//Ensure that if user resolved conflict on frontend we remove the option flag
		if ( $this->google_maps_conflict === false && ! is_admin() ) {
			update_option( 'gmb_google_maps_conflict', false );
		}

	}

	/**
	 * Construct a Google Maps API URL
	 *
	 * @since 2.1.2 Deprecated parameter $signed_in_option.
	 *
	 * @param bool $deprecated Deprecated. Google dropped support for signed-in maps.
	 * @param string $libraries Optional. Default is 'places,drawing'. Which libraries to load.
	 *
	 * @return string
	 */
	protected function google_maps_url( $deprecated = false, $libraries = 'places,drawing' ) {

		$google_maps_api_key = gmb_get_option( 'gmb_maps_api_key' );
		$gmb_language        = gmb_get_option( 'gmb_language' );

		$google_maps_api_url_args = array(
			'libraries' => $libraries
		);

		//Google Maps API key present?
		if ( ! empty( $google_maps_api_key ) ) {
			$google_maps_api_url_args[ 'key' ] = $google_maps_api_key;
		}

		//Preferred Language?
		if ( ! empty( $google_maps_api_key ) ) {
			$google_maps_api_url_args[ 'language' ] = $gmb_language;
		}

		$google_maps_api_url = add_query_arg( $google_maps_api_url_args, 'https://maps.googleapis.com/maps/api/js?v=3.exp' );

		return $google_maps_api_url;
	}


}
