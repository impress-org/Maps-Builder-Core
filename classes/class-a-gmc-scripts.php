<?php
/**
 * Base class for loading scripts
 *
 * @package     GMB
 * @subpackage  Functions
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */
abstract class Google_Maps_Builder_Core_Scripts {

	/**
	 * The plugin's menu slug
	 *
	 * @since 2.0.0
	 *
	 * @var string
	 */
	protected $plugin_slug;

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

	public function __construct(){
		$this->plugin_slug     = Google_Maps_Builder()->get_plugin_slug();
		$this->plugin_settings = get_option( 'gmb_settings' );
		if( is_admin() ) {
			add_action( 'admin_print_scripts', array( $this, 'check_for_multiple_google_maps_api_calls' ) );
		}else{
			add_action( 'wp_print_scripts', array( $this, 'check_for_multiple_google_maps_api_calls' ) );

		}
		$this->hooks();
	}

	/**
	 * Get plugin slug
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_plugin_slug(){
		return $this->plugin_slug;
	}

	/**
	 * Use to add hooks in parent class
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function hooks() {
		//left empty since can't declare abstract in dinoPHP
	}

	/**
	 * Load Google Maps API
	 *
	 * @description: Determine if Google Maps API script has already been loaded
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
	 * @param bool $signed_in_option
	 * @param string $libraries Optional. Default is 'places,drawing'. Which libraries to load.
	 *
	 * @return string
	 */
	protected function google_maps_url( $signed_in_option, $libraries = 'places,drawing' ) {
		$google_maps_api_key = gmb_get_option( 'gmb_maps_api_key' );
		$gmb_language        = gmb_get_option( 'gmb_language' );


		$google_maps_api_url_args = array(
			'sensor'    => 'false',
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

		//Signed In?
		if ( ! empty( $signed_in_option ) && $signed_in_option == 'enabled' ) {
			$google_maps_api_url_args[ 'signed_in' ] = true;
		}

		$google_maps_api_url = add_query_arg( $google_maps_api_url_args, 'https://maps.googleapis.com/maps/api/js?v=3.exp' );

		return $google_maps_api_url;
	}

	/**
	 * Get front-end JS Dir
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function front_end_js_dir() {
		return GMB_CORE_URL . 'assets/js/frontend/';
	}

	/**
	 * Get front-end JS URL
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function front_end_js_url() {
		return GMB_CORE_URL . 'assets/js/plugins/';
	}

	/**
	 * Get admin JS Dir
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function admin_js_dir() {
		return GMB_CORE_URL . 'assets/js/admin/';
	}

	/**
	 * Get admin JS URL
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function admin_js_url() {
		return GMB_CORE_URL . 'assets/js/plugins/';
	}

}
