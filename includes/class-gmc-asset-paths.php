<?php
/**
 * Utility class for assets paths
 *
 * @package     GMB-Core
 * @copyright   Copyright (c) 2016 WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1.0
 */
class Google_Maps_Builder_Core_Asset_Paths {

	/**
	 * Instance
	 *
	 * @since 2.1.0
	 *
	 * @var Google_Maps_Builder_Core_Asset_Paths
	 */
	private static $instance;


	private function __construct(){
		//you shall not pass!
	}

	/**
	 * Get class instance
	 *
	 * @since 2.1.0
	 *
	 * @return Google_Maps_Builder_Core_Asset_Paths
	 */
	public static function get_instance(){
		if( ! self::$instance ){
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Get front-end JS Dir
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function front_end_js_dir() {
		return GMB_CORE_URL . 'assets/js/frontend/';
	}

	/**
	 * Get front-end JS URL
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function front_end_js_url() {
		return GMB_CORE_URL . 'assets/js/plugins/';
	}

	/**
	 * Get admin JS Dir
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function admin_js_dir() {
		return GMB_CORE_URL . 'assets/js/admin/';
	}

	/**
	 * Get admin JS URL
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function admin_js_url() {
		return GMB_CORE_URL . 'assets/js/plugins/';
	}

	/**
	 * Get suffix
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function suffix(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		return $suffix;
	}

}
