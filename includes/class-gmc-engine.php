<?php

/**
 * Class Google_Maps_Builder_Core_Engine
 *
 * Google Maps Builder Engine
 *
 * The Google Maps engine class for WordPress Google Maps Builder
 *
 * @package   Google_Maps_Builder
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress, Devin Walker
 */
abstract class Google_Maps_Builder_Core_Engine {


	/**
	 * Google Maps Builder Engine
	 *
	 * Hooks and actions start here.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {

		// Filter to automatically add maps to post type content
		add_filter( 'the_content', array( $this, 'the_content' ), 2 );

		//add shortcode support
		add_shortcode( 'google_maps', array( $this, 'google_maps_shortcode' ) );

	}

	/**
	 * Google Map display on Single Posts.
	 *
	 * The [google_maps] shortcode will be prepended/appended to the post body,
	 * once for each map. The shortcode is used so it can be filtered.
	 * For example WordPress will remove it in excerpts by default.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	function the_content( $content ) {

		global $post;

		if ( is_main_query() && is_singular( 'google_maps' ) || is_post_type_archive( 'google_maps' ) ) {

			$shortcode = '[google_maps ';
			$shortcode .= 'id="' . $post->ID . '"';
			$shortcode .= ']';

			//Output shortcode
			return $shortcode;

		}

		return $content;

	}


	/**
	 * Single Template Function
	 *
	 * @param $single_template
	 *
	 * @return string
	 */
	public function get_google_maps_template( $single_template ) {

		if ( file_exists( get_stylesheet_directory() . '/google-maps/' . $single_template ) ) {
			$output = get_stylesheet_directory() . '/google-maps/' . $single_template;
		} else {
			$output = gmb_find_view( 'views/' . $single_template );
		}

		return $output;
	}


	/**
	 * Google Maps Builder Shortcode
	 *
	 * Google Maps output relies on the shortcode to display
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function google_maps_shortcode( $atts ) {
		_doing_it_wrong( __FUNCTION__, 'Override in parent class!', '2.0.0' );
	}

	/**
	 * Localize Scripts
	 *
	 * Add params to AJAX for Shortcode Usage
	 * @see        : http://benjaminrojas.net/using-wp_localize_script-dynamically/
	 *
	 * @param $localized_data
	 */
	function array_push_localized_script( $localized_data ) {
		global $wp_scripts;
		$data = $wp_scripts->get_data( 'google-maps-builder-plugin-script', 'data' );

		if ( empty( $data ) ) {
			wp_localize_script( 'google-maps-builder-plugin-script', 'gmb_data', $localized_data );
		} else {

			if ( ! is_array( $data ) ) {

				$data = json_decode( str_replace( 'var gmb_data = ', '', substr( $data, 0, - 1 ) ), true );

			}

			foreach ( $data as $key => $value ) {
				$localized_data[ $key ] = $value;
			}

			$wp_scripts->add_data( 'google-maps-builder-plugin-script', 'data', '' );
			wp_localize_script( 'google-maps-builder-plugin-script', 'gmb_data', $localized_data );

		}

	}


}
