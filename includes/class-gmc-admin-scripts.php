<?php

/**
 * Load admin scripts.
 *
 * @package     GMB-Core
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class Google_Maps_Builder_Core_Admin_Scripts extends Google_Maps_Builder_Core_Scripts {

	/**
	 *
	 */
	protected function hooks() {
		add_action( 'admin_head', array( $this, 'icon_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}

	/**
	 * Admin Dashicon.
	 *
	 * Displays a cute lil map dashicon on our CPT.
	 */
	function icon_style() {
		?>
		<style rel="stylesheet" media="screen">
			#adminmenu #menu-posts-google_maps div.wp-menu-image:before {
				font-family: 'dashicons' !important;
				content: '\f231';
			}
		</style>
		<?php return;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * Return early if no settings page is registered.
	 * @since     2.0
	 *
	 * @param $hook
	 *
	 * @return    null
	 */
	function enqueue_admin_styles( $hook ) {

		global $post;
		$suffix = $this->paths->suffix();

		//Only enqueue scripts for CPT on post type screen
		if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && 'google_maps' === $post->post_type || $hook == 'google_maps_page_gmb_settings' || $hook == 'google_maps_page_gmb_import_export' ) {

			wp_register_style( 'google-maps-builder-admin-styles', GMB_CORE_URL . 'assets/css/gmb-admin' . $suffix . '.css', array(), GMB_VERSION );
			wp_enqueue_style( 'google-maps-builder-admin-styles' );

			wp_register_style( 'google-maps-builder-map-icons', GMB_CORE_URL . 'includes/libraries/map-icons/css/map-icons.css', array(), GMB_VERSION );
			wp_enqueue_style( 'google-maps-builder-map-icons' );

		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since    2.0
	 *
	 * @param $hook
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	function enqueue_admin_scripts( $hook ) {
		global $post;
		$suffix     = $this->paths->suffix();
		$js_dir     = $this->paths->admin_js_dir();
		$js_plugins = $this->paths->admin_js_url();

		//Build Google Maps API URL
		$google_maps_api_url = $this->google_maps_url();

		//Only enqueue scripts for CPT on post type screen
		if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && 'google_maps' === $post->post_type ) {
			$this->admin_scripts( $js_plugins, $suffix, $google_maps_api_url, $js_dir, $post, false );
		}

		//Setting Scripts
		if ( $hook == 'google_maps_page_gmb_settings' ) {
			wp_register_script( 'google-maps-builder-admin-settings', $js_dir . 'admin-settings' . $suffix . '.js', array( 'jquery' ), GMB_VERSION );
			wp_enqueue_script( 'google-maps-builder-admin-settings' );
		}
		wp_enqueue_style( 'dashicons' );


	}

	/**
	 * Load admin scripts
	 *
	 * @since 1.0
	 * @since 2.1.2 Deprecated parameter $signed_in_option.
	 *
	 * @param string      $js_plugins
	 * @param string      $suffix
	 * @param string      $google_maps_api_url
	 * @param string      $js_dir
	 * @param WP_Post     $post
	 * @param bool|string $deprecated Deprecated. Google dropped support for signed-in maps.
	 */
	protected function admin_scripts( $js_plugins, $suffix, $google_maps_api_url, $js_dir, $post, $deprecated = false ) {

		wp_enqueue_style( 'wp-color-picker' );

		//Magnific popup
		wp_register_script( 'google-maps-builder-admin-magnific-popup', $js_plugins . 'gmb-magnific' . $suffix . '.js', array( 'jquery' ), GMB_VERSION );
		wp_enqueue_script( 'google-maps-builder-admin-magnific-popup' );

		//Core plugin scripts.
		wp_register_script( 'google-maps-builder-admin-gmaps', $google_maps_api_url, array( 'jquery' ) );
		wp_enqueue_script( 'google-maps-builder-admin-gmaps' );

		//Maps icons
		wp_register_script( 'google-maps-builder-map-icons', GMB_CORE_URL . 'includes/libraries/map-icons/js/map-icons.js', array( 'jquery' ) );
		wp_enqueue_script( 'google-maps-builder-map-icons' );

		//Qtip
		wp_register_script( 'google-maps-builder-admin-qtip', $js_plugins . 'jquery.qtip' . $suffix . '.js', array( 'jquery' ), GMB_VERSION, true );
		wp_enqueue_script( 'google-maps-builder-admin-qtip' );

		//Map base
		wp_register_script( 'google-maps-builder-admin-map-builder', $js_dir . 'admin-google-map' . $suffix . '.js', array(
			'jquery',
			'wp-color-picker'
		), GMB_VERSION );
		wp_enqueue_script( 'google-maps-builder-admin-map-builder' );

		//Modal magnific builder
		wp_register_script( 'google-maps-builder-admin-magnific-builder', $js_dir . 'admin-maps-magnific' . $suffix . '.js', array(
			'jquery',
			'wp-color-picker'
		), GMB_VERSION );
		wp_enqueue_script( 'google-maps-builder-admin-magnific-builder' );

		//Map Controls
		wp_register_script( 'google-maps-builder-admin-map-controls', $js_dir . 'admin-maps-controls' . $suffix . '.js', array( 'jquery' ), GMB_VERSION );
		wp_enqueue_script( 'google-maps-builder-admin-map-controls' );

		$api_key     = gmb_get_option( 'gmb_maps_api_key' );
		$geolocate   = gmb_get_option( 'gmb_lat_lng' );
		$post_status = get_post_status( $post->ID );

		$maps_data = array(
			'api_key'           => $api_key,
			'geolocate_setting' => isset( $geolocate['geolocate_map'] ) ? $geolocate['geolocate_map'] : 'yes',
			'default_lat'       => isset( $geolocate['latitude'] ) ? $geolocate['latitude'] : '32.715738',
			'default_lng'       => isset( $geolocate['longitude'] ) ? $geolocate['longitude'] : '-117.16108380000003',
			'plugin_url'        => GMB_PLUGIN_URL,
			'default_marker'    => apply_filters( 'gmb_default_marker', GMB_PLUGIN_URL . 'assets/img/spotlight-poi.png' ),
			'ajax_loader'       => set_url_scheme( apply_filters( 'gmb_ajax_preloader_img', GMB_PLUGIN_URL . 'assets/images/spinner.gif' ), 'relative' ),
			'snazzy'            => GMB_PLUGIN_URL . 'assets/js/admin/snazzy.json',
			'modal_default'     => gmb_get_option( 'gmb_open_builder' ),
			'post_status'       => $post_status,
			'site_name'         => get_bloginfo( 'name' ),
			'site_url'          => get_bloginfo( 'url' ),
			'i18n'              => array(
				'update_map'               => $post_status == 'publish' ? __( 'Update Map', 'google-maps-builder' ) : __( 'Publish Map', 'google-maps-builder' ),
				'set_place_types'          => __( 'Update Map', 'google-maps-builder' ),
				'places_selection_changed' => __( 'Place selections have changed.', 'google-maps-builder' ),
				'multiple_places'          => __( 'Hmm, it looks like there are multiple places in this area. Please confirm which place you would like this marker to display:', 'google-maps-builder' ),
				'btn_drop_marker'          => '<span class="dashicons dashicons-location"></span>' . __( 'Drop a Marker', 'google-maps-builder' ),
				'btn_drop_marker_click'    => __( 'Click on the Map', 'google-maps-builder' ),
				'btn_edit_marker'          => __( 'Edit Marker', 'google-maps-builder' ),
				'btn_delete_marker'        => __( 'Delete Marker', 'google-maps-builder' ),
				'visit_website'            => __( 'Visit Website', 'google-maps-builder' ),
				'get_directions'           => __( 'Get Directions', 'google-maps-builder' ),
				'api_key_required'         => sprintf( __( '%1$sGoogle API Error:%2$s Please include your Google Maps API key in the %3$splugin settings%5$s to start using the plugin. An valid API key with Google Maps and Places API access to your website is now required due to recent changes by Google. Getting an API key is free and easy. %4$sLearn how to obtain a Google Maps API key%5$s', 'google-maps-builder' ), '<strong>', '</strong>', '<a href="' . esc_url( admin_url( 'edit.php?post_type=google_maps&page=gmb_settings' ) ) . '">', '<a href="https://wordimpress.com/documentation/maps-builder-pro/creating-maps-api-key/" target="_blank" class="new-window">', '</a>' )
			)
		);

		wp_localize_script( 'google-maps-builder-admin-map-builder', 'gmb_data', $maps_data );

	}


}
