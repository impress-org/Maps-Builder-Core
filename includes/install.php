<?php
/**
 * Install Function
 *
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install
 *
 * Runs on plugin install
 *
 * @since 2.1
 * @global $wpdb
 * @global $wp_version
 * @return void
 */
function gmb_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {

		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {

			switch_to_blog( $blog_id );
			gmb_run_install();
			restore_current_blog();

		}

	} else {

		gmb_run_install();

	}

}

register_activation_hook( GMB_PLUGIN_FILE, 'gmb_install' );

/**
 * Run the gmb Install process
 *
 * @since  2.1
 * @return void
 */
function gmb_run_install() {
	// Set up post types and flush rewrite rules.
	Google_Maps_Builder()->activate->activation_flush_rewrites();

	// Add Upgraded From Option.
	$current_version = get_option( 'gmb_version' );
	if ( $current_version ) {
		update_option( 'gmb_version_upgraded_from', $current_version );
	}

	if ( ! $current_version ) {

		require_once GMB_CORE_PATH . 'includes/admin/upgrades/upgrade-functions.php';

		// When new upgrade routines are added, mark them as complete on fresh install.
		$upgrade_routines = array(
			'gmb_markers_upgraded',
			'gmb_refid_upgraded',
		);

		foreach ( $upgrade_routines as $upgrade ) {
			gmb_set_upgrade_complete( $upgrade );
		}
	}

}

/**
 * Network Activated New Site Setup
 *
 * When a new site is created when Maps Builder is network activated this function runs the appropriate install function to set up the site for the plugin.
 *
 * @since      2.1
 *
 * @param  int $blog_id The Blog ID created
 * @param  int $user_id The User ID set as the admin
 * @param  string $domain The URL
 * @param  string $path Site Path
 * @param  int $site_id The Site ID
 * @param  array $meta Blog Meta
 */
function gmb_on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	if ( is_plugin_active_for_network( GMB_PLUGIN_BASE ) ) {
		switch_to_blog( $blog_id );
		gmb_install();
		restore_current_blog();
	}

}

add_action( 'wpmu_new_blog', 'gmb_on_create_blog', 10, 6 );