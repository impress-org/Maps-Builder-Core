<?php

/**
 * Base class for both plugins to extend in order to trigger the asset loading process.
 *
 * @package     GMB-Core
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1.0
 */
abstract class Google_Maps_Builder_Core_Scripts_Init {

	/**
	 * Asset paths.
	 *
	 * @since 2.1.0
	 *
	 * @var Google_Maps_Builder_Core_Asset_Paths
	 */
	protected $paths;

	/**
	 * Load scripts by context.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		$this->paths = Google_Maps_Builder_Core_Asset_Paths::get_instance();

		if ( is_admin() ) {
			new Google_Maps_Builder_Core_Admin_Scripts();
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_hooks' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_late' ), 50 );
		} else {
			add_action( 'wp_enqueue_scripts', array( $this, 'front_end_hooks' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'front_end_late' ), 50 );
			new Google_Maps_Builder_Core_Front_End_Scripts();

		}

	}

	/**
	 * Enqueue admin scripts that need to run late.
	 *
	 * @since 2.1.0
	 *
	 * @uses "admin_enqueue_scripts
	 *
	 * @param $hook
	 */
	public function admin_late( $hook ) {
	}

	/**
	 * Load additional admin scripts.
	 *
	 * @since 2.1.0
	 *
	 * @uses "admin_enqueue_scripts"
	 *
	 * @param $hook
	 */
	public function admin_hooks( $hook ) {
	}

	/**
	 * Load additional front-end scripts.
	 *
	 * @since 2.1.0
	 *
	 * @uses "enqueue_scripts"
	 *
	 */
	public function front_end_hooks() {
	}


	/**
	 * Enqueue front-end scripts that need to run late.
	 *
	 * @since 2.1.0
	 *
	 * @uses "wp_enqueue_scripts
	 *
	 * @param $hook
	 */
	public function front_end_late( $hook ) {
	}


}
