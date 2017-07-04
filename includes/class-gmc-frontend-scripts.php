<?php
/**
 * Load front-end scripts
 *
 * @package     GMB-Core
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
 */

/**
 * Class Google_Maps_Builder_Core_Front_End_Scripts
 */
class Google_Maps_Builder_Core_Front_End_Scripts extends Google_Maps_Builder_Core_Scripts {

	/**
	 * Hooks
	 */
	protected function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ), 11 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
	}

	/**
	 * Load Frontend Scripts.
	 *
	 * Enqueues the required scripts to display maps on the frontend only.
	 */
	function load_frontend_scripts() {

		$libraries           = 'places';
		$google_maps_api_url = $this->google_maps_url( false, $libraries );

		wp_register_script( 'google-maps-builder-gmaps', $google_maps_api_url, array( 'jquery' ) );
		wp_enqueue_script( 'google-maps-builder-gmaps' );

		$js_dir     = $this->paths->front_end_js_dir();
		$js_plugins = $this->paths->front_end_js_url();

		// Use minified libraries if SCRIPT_DEBUG is turned off.
		$suffix = $this->paths->suffix();

		wp_register_script( 'google-maps-builder-infowindows', $js_plugins . '/gmb-infobubble' . $suffix . '.js', array( 'jquery' ), GMB_VERSION, true );
		wp_enqueue_script( 'google-maps-builder-infowindows' );

		wp_register_script( 'google-maps-builder-plugin-script', $js_dir . 'google-maps-builder' . $suffix . '.js', array(
			'jquery',
			'google-maps-builder-infowindows'
		), GMB_VERSION, true );
		wp_enqueue_script( 'google-maps-builder-plugin-script' );

		wp_register_script( 'google-maps-builder-maps-icons', GMB_CORE_URL . 'includes/libraries/map-icons/js/map-icons.js', array( 'jquery' ), GMB_VERSION, true );
		wp_enqueue_script( 'google-maps-builder-maps-icons' );

		// Initial data to pass to the `gmb_data` front-end JS object.
		$maps_data = apply_filters( 'gmb_frontend_data_array', array(
				'i18n'            => array(
					'get_directions' => __( 'Get Directions', 'google-maps-builder' ),
					'visit_website'  => __( 'Visit Website', 'google-maps-builder' ),
				),
				'infobubble_args' => array(
					'shadowStyle'         => 0,
					'padding'             => 12,
					'backgroundColor'     => 'rgb(255, 255, 255)',
					'borderRadius'        => 3,
					'arrowSize'           => 15,
					'minHeight'           => 20,
					'maxHeight'           => 450,
					'minWidth'            => 200,
					'maxWidth'            => 350,
					'borderWidth'         => 0,
					'disableAutoPan'      => true,
					'disableAnimation'    => true,
					'backgroundClassName' => 'gmb-infobubble',
					'closeSrc'            => 'https://www.google.com/intl/en_us/mapfiles/close.gif'
				)
			)
		);

		wp_localize_script( 'google-maps-builder-plugin-script', 'gmb_data', $maps_data );

	}


	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    2.0
	 */
	function enqueue_frontend_styles() {

		$suffix = $this->paths->suffix();

		wp_register_style( 'google-maps-builder-plugin-styles', GMB_CORE_URL . 'assets/css/google-maps-builder' . $suffix . '.css', array(), GMB_VERSION );
		wp_enqueue_style( 'google-maps-builder-plugin-styles' );

		wp_register_style( 'google-maps-builder-map-icons', GMB_CORE_URL . 'includes/libraries/map-icons/css/map-icons.css', array(), GMB_VERSION );
		wp_enqueue_style( 'google-maps-builder-map-icons' );

	}

}
