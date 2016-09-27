<?php
/**
 * Upgrades
 *
 * Upgrade functions go here
 *
 * @subpackage  includes/admin/upgrades
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Upgrade from Google Reference ID to Places ID
 *
 * @since 2.0
 * @uses  WP_Query
 * @return void
 */
function gmb_v2_upgrades() {

	//Set key variables
	$google_api_key = gmb_get_option( 'gmb_api_key' );

	//Loop through maps
	$args = array(
		'post_type'      => 'google_maps',
		'posts_per_page' => - 1
	);

	// The Query
	$the_query = new WP_Query( $args );

	// The CPT Loop
	if ( $the_query->have_posts() ) : while ( $the_query->have_posts() ) : $the_query->the_post();

		//Repeater markers data
		$markers = get_post_meta( get_the_ID(), 'gmb_markers_group', true );

		//If no markers skip
		if ( ! empty( $markers ) ) {

			//Markers loop
			foreach ( $markers as $key => $marker ) {

				$ref_id   = isset( $marker['reference'] ) ? $marker['reference'] : '';
				$place_id = isset( $marker['place_id'] ) ? $marker['place_id'] : '';

				//No ref ID -> skip; If place_id already there skip
				if ( empty( $ref_id ) ) {
					continue;
				}
				if ( ! empty( $place_id ) ) {
					continue;
				}
				//cURL the Google API for the Google Place ID
				$google_places_url = add_query_arg(
					array(
						'reference' => $ref_id,
						'key'       => $google_api_key
					),
					'https://maps.googleapis.com/maps/api/place/details/json'
				);

				$response = wp_remote_get( $google_places_url,
					array(
						'timeout'   => 15,
						'sslverify' => false
					)
				);

				// make sure the response came back okay
				if ( is_wp_error( $response ) ) {
					return;
				}

				// decode the license data
				$response = json_decode( $response['body'], true );

				//Place ID is there, now let's update the widget data
				if ( isset( $response['result']['place_id'] ) ) {

					//Add Place ID to markers array
					$markers[ $key ]['place_id'] = $response['result']['place_id'];

				}

				//Pause for 2 seconds so we don't overwhelm the Google API with requests
				sleep( 2 );


			} //end foreach

			//Update repeater data with new data
			update_post_meta( get_the_ID(), 'gmb_markers_group', $markers );

		} //endif

	endwhile; endif;

	// Reset Post Data
	wp_reset_postdata();

	//Update our options and GTF out
	gmb_set_upgrade_complete( 'gmb_refid_upgraded' );
	update_option( 'gmb_refid_upgraded', 'upgraded' );

}

/**
 * Upgrade Marker Paths.
 *
 * Marker paths were hard coded into the db causing issues when structures change.
 *
 * @since 2.1
 * @return void
 */
function gmb_v21_marker_upgrades() {

	//Loop through maps
	$args = array(
		'post_type'      => 'google_maps',
		'posts_per_page' => - 1
	);

	// The Query
	$the_query = new WP_Query( $args );

	// The CPT Loop
	if ( $the_query->have_posts() ) : while ( $the_query->have_posts() ) : $the_query->the_post();

		/**
		 * Individual Markers
		 */
		$markers = get_post_meta( get_the_ID(), 'gmb_markers_group', true );

		//If no markers skip
		if ( ! empty( $markers ) ) {

			//Markers loop
			foreach ( $markers as $key => $marker ) {

				//Conditional:
				//a) check for a marker image
				if ( isset( $marker['marker_img'] ) && ! empty( $marker['marker_img'] ) &&

				     // AND b) If this marker has an included
				     // image associate with it that has not been uploaded
				     ( isset( $marker['marker_img_id'] ) && empty( $marker['marker_img_id'] ) ||
				       strpos( $marker['marker_img'], GMB_PLUGIN_URL ) !== false )
				) {

					$new_marker_path                        = str_replace( GMB_PLUGIN_URL, '', $marker['marker_img'] );
					$markers[ $key ]['marker_included_img'] = $new_marker_path;

					//Save default markers into a new meta option without full path
					unset( $markers[ $key ]['marker_img'] );
					unset( $markers[ $key ]['marker_img_id'] );
					unset( $markers[ $key ]['marker'] );
				} else {
					//This is an uploaded marker
					unset( $markers[ $key ]['marker_included_img'] );
				}

			}

		}

		//Update repeater data with new data
		update_post_meta( get_the_ID(), 'gmb_markers_group', $markers );

		/**
		 * Mashups
		 */
		$mashup_markers = get_post_meta( get_the_ID(), 'gmb_mashup_group', true );

		//If no $mashup_markers skip
		if ( ! empty( $mashup_markers ) ) {

			//Markers loop
			foreach ( $mashup_markers as $key => $marker ) {

				//Conditional:
				//a) check for a marker image
				if ( isset( $marker['marker_img'] ) && ! empty( $marker['marker_img'] ) &&

				     // AND b) If this marker has an included
				     // image associate with it that has not been uploaded
				     ( isset( $marker['marker_img_id'] ) && empty( $marker['marker_img_id'] ) ||
				       strpos( $marker['marker_img'], GMB_PLUGIN_URL ) !== false )
				) {

					$new_marker_path                               = str_replace( GMB_PLUGIN_URL, '', $marker['marker_img'] );
					$mashup_markers[ $key ]['marker_included_img'] = $new_marker_path;

					//Save default markers into a new meta option without full path
					unset( $mashup_markers[ $key ]['marker_img'] );
					unset( $mashup_markers[ $key ]['marker_img_id'] );
					unset( $mashup_markers[ $key ]['marker'] );
				} else {
					//This is an uploaded marker
					unset( $mashup_markers[ $key ]['marker_included_img'] );
				}

			}

			//Update repeater data with new data
			update_post_meta( get_the_ID(), 'gmb_mashup_group', $mashup_markers );

		}

	endwhile; endif;

	// Reset Post Data
	wp_reset_postdata();

	//Update our options and GTF out
	gmb_set_upgrade_complete( 'gmb_markers_upgraded' );

}

/**
 * Upgrade API Keys.
 *
 * API keys were stored under several option values over plugin versions, requiring reconciliation.
 *
 * @since 2.1
 * @return void
 */
function gmb_v21_api_key_upgrades() {

    // Establish an array with all possible key values
    $api_key_values = array(
        'gmb_maps_api_key' => gmb_get_option( 'gmb_maps_api_key' ),
        'gmb_api_key'      => gmb_get_option( 'gmb_api_key' ),
        'maps_api_key'     => gmb_get_option( 'maps_api_key' ),
    );

    // Remove all false/empty values, then get rid of duplicates, then reset the array indices (array_unique preserves indices)
    $unique_api_key_values = array_values( array_unique( array_filter( $api_key_values ) ) );

    // Start with an empty API key
    $reconciled_api_key = '';

    // If there was only one API key value in the list, we'll use that one
    if ( count( $unique_api_key_values ) === 1 ) {

        $reconciled_api_key = $unique_api_key_values[0];

    // There was more than one API key value in the list
    } else {

        /**
         * Given that there are many API key values, we need to pick just one. So, we prioritize
         * `gmb_maps_api_key` over `gmb_api_key` over `maps_api_key`.
         */
        $reconciled_api_key = ( ! empty( $api_key_values['maps_api_key'] ) ) ? $api_key_values['maps_api_key'] : $reconciled_api_key;
        $reconciled_api_key = ( ! empty( $api_key_values['gmb_api_key'] ) ) ? $api_key_values['gmb_api_key'] : $reconciled_api_key;
        $reconciled_api_key = ( ! empty( $api_key_values['gmb_maps_api_key'] ) ) ? $api_key_values['gmb_maps_api_key'] : $reconciled_api_key;

    }

    // Set our API key under the `gmb_maps_api_key` key
    $gmb_settings = get_option( 'gmb_settings' );

    $gmb_settings[ 'gmb_maps_api_key' ] = $reconciled_api_key;

    update_option( 'gmb_settings', $gmb_settings );

    // Woo, we made it!
    gmb_set_upgrade_complete( 'gmb_api_keys_upgraded' );

}