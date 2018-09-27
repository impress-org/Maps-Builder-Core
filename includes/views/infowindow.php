<?php
/**
 * Represents the html of infowwindow content
 *
 * @package   Google_Maps_Builder
 *
 * @since     2.2.0
 *
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress, WordImpress
 */


$taxonomy         = isset( $_POST['taxonomy'] ) ? sanitize_text_field( $_POST['taxonomy'] ) : '';
$terms            = isset( $_POST['terms'] ) && is_array( $_POST['terms'] ) ? array_map( 'intval', $_POST['terms'] ) : '';
$post_type        = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';
$lat_field        = isset( $_POST['lat_field'] ) ? sanitize_text_field( $_POST['lat_field'] ) : '_gmb_lat';
$lng_field        = isset( $_POST['lng_field'] ) ? sanitize_text_field( $_POST['lng_field'] ) : '_gmb_lng';
$group_data_array = maybe_unserialize( get_post_meta( $_POST['map_post_id'], 'gmb_mashup_group', true ) );

/**
 * Filter added for infowindow width and height
 *
 * @since 2.2.0
 */

$min_width  = apply_filters( 'gmb_infowindow_img_min_width', '335' );
$min_height = apply_filters( 'gmb_infowindow_img_min_height', '80' );

/**
 * Filter added for infowindow image size
 *
 * @since 2.2.0
 */

$gmb_image_size = apply_filters( 'gmb_infowindow_img_size', 'large' );

$args = array(
	'post_type'      => $post_type,
	'posts_per_page' => - 1,
	'post_status'    => 'publish',
);

// Filter posts by taxonomy terms if applicable.
if ( ! empty( $taxonomy ) && $taxonomy !== 'none' ) {
	$args['tax_query'] = array(
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $terms,
			'operator' => 'IN',
		),
	);
}

$transient_name = 'gmb_mashup_' . $post_type . '_' . md5( http_build_query( $args ) . '_' . uniqid() );

// Load marker data from transient if available.
if ( false === ( $response = get_transient( $transient_name ) ) ) {
	// Transient does not exist or is expired. Proceed with query.
	$wp_query = new WP_Query( $args );

	if ( $wp_query->have_posts() ) : while ( $wp_query->have_posts() ) :
		$wp_query->the_post();
		$post_id = get_the_ID();

		// Get latitude and longitude associated with post.
		$lat = get_post_meta( $post_id, $lat_field, true );
		$lng = get_post_meta( $post_id, $lng_field, true );

		if ( empty( $lat ) || empty( $lng ) ) {
			// Do not add marker if latitude or longitude are empty.
			continue;
		}

		// Add marker data to response.
		$response[ $post_id ]['title']     = get_the_title( $post_id );
		$response[ $post_id ]['id']        = $post_id;
		$response[ $post_id ]['address']   = get_post_meta( $post_id, '_gmb_address', true ); //Geocoding Coming soon
		$response[ $post_id ]['latitude']  = $lat;
		$response[ $post_id ]['longitude'] = $lng;

		// Info bubble content set
		if ( isset($group_data_array[0]['show_excerpts']) && 'yes' === $group_data_array[0]['show_excerpts'] ) {
			$marker_post_content = get_the_excerpt( $post_id );
		} else {
			$marker_post_content = get_post_field( 'post_content', $post_id );
		}
		$marker_content                     = wp_trim_words( $marker_post_content, 55 );
		$marker_thumbnail                   = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $gmb_image_size );
		$response[ $post_id ]['infowindow'] = '<div id="infobubble-content" class="main-place-infobubble-content">';
		if ( 'yes' === $group_data_array[0]['featured_img'] ) {
			$response[ $post_id ]['infowindow'] .= '<div class="place-thumb"><img src="' . $marker_thumbnail[0] . '" alt="' . $response[ $post_id ]['title'] . '" style="' . esc_attr( sprintf( 'min-width: %1$spx;min-height: %2$spx', $min_width, $min_height ) ) . '"></div>';
		}
		if ( ! empty( $marker_title ) ) {
			$response[ $post_id ]['infowindow'] .= '<p class="place-title">' . $response[ $post_id ]['title'] . '</p>';
		}
		if ( ! empty( $marker_content ) ) {
			$response[ $post_id ]['infowindow'] .= '<p class="place-description">' . $marker_content . '</p>';
		}
		$response[ $post_id ]['infowindow'] .= '<a href="' . get_permalink( $post_id ) . '" title="' . $marker_title . '" class="gmb-mashup-single-link">' . apply_filters( 'gmb_mashup_infowindow_content_readmore', __( 'Read More &raquo;', 'google-maps-builder' ) ) . '</a>';
		$response[ $post_id ]['infowindow'] .= '</div>';
	endwhile; endif;
	wp_reset_postdata();

	/**
	 * Filters the array of mash-up markers.
	 *
	 * @author Tobias Malikowski tobias.malikowski@gmail.com
	 *
	 * @param array    $response       Array of mash-up marker data.
	 * @param WP_Query $wp_query       Query used to retrieve mash-up posts.
	 * @param string   $transient_name Transient used to store marker data.
	 * @param array    $args           Args passed to WP_Query.
	 */
	apply_filters( 'gmb_get_mashup_markers_callback', $response, $wp_query, $transient_name, $args );

	if ( is_array( $response ) ) {
		// Store marker data in transient to speed up future callbacks.
		set_transient( $transient_name, $response, 30 * DAY_IN_SECONDS );
	} else {
		$response['error'] = __( 'Error - No posts found.', 'google-maps-builder' );
	}
}

echo json_encode( $response );