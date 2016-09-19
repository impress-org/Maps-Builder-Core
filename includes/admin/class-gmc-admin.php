<?php

/**
 * Class Google_Maps_Builder_Core_Admin
 *
 * Google Maps Admin - The admin is considered the single post view where you build maps.
 *
 * @package   Google_Maps_Builder_Admin
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2015 WordImpress
 */
abstract class Google_Maps_Builder_Core_Admin extends Google_Maps_Builder_Core_Interface {

	/**
	 * Markerbox CMB2 object.
	 *
	 * @since 2.1.0
	 *
	 * @var CMB2
	 */
	protected $marker_box;

	/**
	 * Markerbox group field ID.
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected $marker_box_group_field_id;

	/**
	 * Search options CMB2 object.
	 *
	 * @since 2.1.0
	 *
	 * @var CMB2
	 */
	protected $search_options;

	/**
	 * Display options CMB2 object.
	 *
	 * @since 2.1.0
	 *
	 * @var CMB2
	 */
	protected $display_options;

	/**
	 * Control options CMB2 object.
	 *
	 * @since 2.1.0
	 *
	 * @var CMB2
	 */
	protected $control_options;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a settings page and menu.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		parent::__construct();

		//CPT
		add_filter( 'manage_edit-google_maps_columns', array( $this, 'setup_custom_columns' ) );
		add_action( 'manage_google_maps_posts_custom_column', array( $this, 'configure_custom_columns' ), 10, 2 );
		add_filter( 'get_user_option_closedpostboxes_google_maps', array( $this, 'closed_meta_boxes' ) );

		//Custom Meta Fields
		add_action( 'cmb2_render_google_geocoder', array( $this, 'cmb2_render_google_geocoder' ), 10, 2 );
		add_action( 'cmb2_render_google_maps_preview', array( $this, 'cmb2_render_google_maps_preview' ), 10, 2 );
		//		add_action( 'cmb2_render_destination_point', array( $this, 'cmb2_render_destination_point' ), 10, 5 );
		//		add_action( 'cmb2_sanitize_destination_point', array( $this, 'cmb2_sanitize_destination_point' ), 10, 5 );
		add_action( 'cmb2_render_search_options', array( $this, 'cmb2_render_search_options' ), 10, 2 );
		add_action( 'cmb2_render_width_height', array( $this, 'cmb2_render_width_height' ), 10, 2 );
		add_action( 'cmb2_render_lat_lng', array( $this, 'cmb2_render_lat_lng' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'gmb_add_shortcode_to_publish_metabox' ) );

		//Add metaboxes and fields to CPT
		add_action( 'cmb2_init', array( $this, 'cpt2_metaboxes_fields' ) );
	}


	/**
	 * Add Shortcode to Publish Metabox
	 * @return bool
	 */
	public function gmb_add_shortcode_to_publish_metabox() {

		if ( 'google_maps' !== get_post_type() ) {
			return false;
		}

		global $post;

		//Only enqueue scripts for CPT on post type screen
		if ( 'google_maps' === $post->post_type ) {
			echo '<a href="#" class="button disabled button-primary" id="map-builder"><span class="dashicons dashicons-location-alt"></span>' . __( 'Open Map Builder', 'google-maps-builder' ) . '</a>';
			//Shortcode column with select all input
			$shortcode = htmlentities( '[google_maps id="' . $post->ID . '"]' );
			echo '<div class="shortcode-wrap box-sizing"><label>' . __( 'Map Shortcode:', 'google-maps-builder' ) . '</label><input onClick="this.setSelectionRange(0, this.value.length)" type="text" class="shortcode-input" readonly value="' . $shortcode . '"></div>';

		}

		return false;
	}

	/**
	 * Get Default Map Options.
	 *
	 * Helper function that returns default map options from settings.
	 *
	 * @return array
	 */
	public function get_default_map_options() {

		$width_height = gmb_get_option( 'gmb_width_height' );

		$defaults = array(
			'width'       => ( isset( $width_height['width'] ) ) ? $width_height['width'] : '100',
			'width_unit'  => ( isset( $width_height['map_width_unit'] ) ) ? $width_height['map_width_unit'] : '%',
			'height'      => ( isset( $width_height['height'] ) ) ? $width_height['height'] : '600',
			'height_unit' => ( isset( $width_height['map_height_unit'] ) ) ? $width_height['map_height_unit'] : 'px'
		);

		return $defaults;

	}

	/**
	 * Register our settings with WP.
	 *
	 * @since  1.0
	 */
	public function settings_init() {
		register_setting( 'google-maps-builder', 'google-maps-builder' );
	}

	/**
	 * Defines the Google Places CPT metabox and field configuration.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function cpt2_metaboxes_fields() {

		$prefix          = 'gmb_';

		$default_options = $this->get_default_map_options();

		// Google map preview.
		$preview_box = cmb2_get_metabox( array(
			'id'           => 'google_maps_preview_metabox',
			'title'        => __( 'Google Map Preview', 'google-maps-builder' ),
			'object_types' => array( 'google_maps' ), // post type
			'context'      => 'normal', //  'normal', 'advanced', or 'side'
			'priority'     => 'high', //  'high', 'core', 'default' or 'low'
			'show_names'   => false, // Show field names on the left
		) );
		$preview_box->add_field( array(
			'name'    => __( 'Map Preview', 'google-maps-builder' ),
			'id'      => $prefix . 'preview',
			'type'    => 'google_maps_preview',
			'default' => '',
		) );

		// Google maps markers.
		$this->marker_box = cmb2_get_metabox( array(
			'id'           => 'google_maps_markers',
			'title'        => __( 'Map Markers', 'google-maps-builder' ),
			'object_types' => array( 'google_maps' ), // post type
			'context'      => 'normal', //  'normal', 'advanced', or 'side'
			'priority'     => 'high', //  'high', 'core', 'default' or 'low'
			'show_names'   => true, // Show field names on the left
		) );
		$this->marker_box->add_field( array(
			'name' => __( 'Create Marker', 'google-maps-builder' ),
			'id'   => $prefix . 'geocoder',
			'type' => 'google_geocoder'
		) );

		$this->marker_box_group_field_id = $this->marker_box->add_field( array(
			'name'        => __( 'Existing Markers', 'google-maps-builder' ),
			'id'          => $prefix . 'markers_group',
			'type'        => 'group',
			'description' => __( 'Map marker data is contained within the repeatable fields below. You may add or update marker data here or directly on the map.', 'google-maps-builder' ) . '<a href="#" class="button button-small toggle-repeater-groups">' . __( 'Toggle Marker Groups', 'google-maps-builder' ) . '</a>',
			'options'     => array(
				'group_title'   => __( 'Marker: {#}', 'cmb' ),
				'add_button'    => __( 'Add Another Marker', 'google-maps-builder' ),
				'remove_button' => __( 'Remove Marker', 'google-maps-builder' ),
				'sortable'      => true, // beta
			),
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Marker Title', 'google-maps-builder' ),
			'id'   => 'title',
			'type' => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name'            => __( 'Marker Description', 'google-maps-builder' ),
			'description'     => __( 'Write a short description for this marker', 'google-maps-builder' ),
			'id'              => 'description',
			'type'            => 'textarea_small',
			'sanitization_cb' => false
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Marker Reference', 'google-maps-builder' ),
			'id'   => 'reference',
			'type' => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Marker Place ID', 'google-maps-builder' ),
			'id'   => 'place_id',
			'type' => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Hide Place Details', 'google-maps-builder' ),
			'id'   => 'hide_details',
			'type' => 'checkbox',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Marker Latitude', 'google-maps-builder' ),
			'id'   => 'lat',
			'type' => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name' => __( 'Marker Longitude', 'google-maps-builder' ),
			'id'   => 'lng',
			'type' => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name'        => __( 'Custom Marker Image', 'google-maps-builder' ),
			'id'          => 'marker_img',
			'row_classes' => 'gmb-hidden',
			'type'        => 'file',
			'options'     => array(
				'url'                  => false,
				'add_upload_file_text' => __( 'Add Marker Image', 'google-maps-builder' )
			),
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name'        => __( 'Included Marker Icon', 'google-maps-builder' ),
			'row_classes' => 'gmb-hidden',
			'id'          => 'marker_included_img',
			'type'        => 'text',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name'        => __( 'Marker Data', 'google-maps-builder' ),
			'row_classes' => 'gmb-hidden',
			'id'          => 'marker',
			'type'        => 'textarea_code',
		) );
		$this->marker_box->add_group_field( $this->marker_box_group_field_id, array(
			'name'        => __( 'Marker Label Data', 'google-maps-builder' ),
			'row_classes' => 'gmb-hidden',
			'id'          => 'label',
			'type'        => 'textarea_code',
		) );

		// Search options.
		$this->search_options = cmb2_get_metabox( array(
			'id'           => 'google_maps_search_options',
			'title'        => __( 'Google Places', 'google-maps-builder' ),
			'object_types' => array( 'google_maps' ), // post type
			'context'      => 'normal', //  'normal', 'advanced', or 'side'
			'priority'     => 'core', //  'high', 'core', 'default' or 'low'
			'show_names'   => true, // Show field names on the left
		) );

		$this->search_options->add_field(
			array(
				'name'    => __( 'Show Places?', 'google-maps-builder' ),
				'desc'    => __( 'Display establishments, prominent points of interest, geographic locations, and more.', 'google-maps-builder' ),
				'id'      => $prefix . 'show_places',
				'type'    => 'radio_inline',
				'options' => array(
					'yes' => __( 'Yes', 'cmb' ),
					'no'  => __( 'No', 'cmb' ),
				),
			)
		);

		$this->search_options->add_field(
			array(
				'name'    => __( 'Search Radius', 'google-maps-builder' ),
				'desc'    => __( 'Defines the distance (in meters) within which to return Place markers. The maximum allowed radius is 50,000 meters.', 'google-maps-builder' ),
				'default' => '3000',
				'id'      => $prefix . 'search_radius',
				'type'    => 'text_small'
			)
		);

		$this->search_options->add_field(
			array(
				'name'    => __( 'Place Types', 'google-maps-builder' ),
				'desc'    => __( 'Select which type of places you would like to display on this map.', 'google-maps-builder' ),
				'id'      => $prefix . 'places_search_multicheckbox',
				'type'    => 'multicheck',
				'options' => apply_filters( 'gmb_place_types', array(
					'accounting'              => __( 'Accounting', 'google-maps-builder' ),
					'airport'                 => __( 'Airport', 'google-maps-builder' ),
					'amusement_park'          => __( 'Amusement Park', 'google-maps-builder' ),
					'aquarium'                => __( 'Aquarium', 'google-maps-builder' ),
					'art_gallery'             => __( 'Art Gallery', 'google-maps-builder' ),
					'atm'                     => __( 'ATM', 'google-maps-builder' ),
					'bakery'                  => __( 'Bakery', 'google-maps-builder' ),
					'bank'                    => __( 'Bank', 'google-maps-builder' ),
					'bar'                     => __( 'Bar', 'google-maps-builder' ),
					'beauty_salon'            => __( 'Beauty Salon', 'google-maps-builder' ),
					'bicycle_store'           => __( 'Bicycle Store', 'google-maps-builder' ),
					'book_store'              => __( 'Book Store', 'google-maps-builder' ),
					'bowling_alley'           => __( 'Bowling Alley', 'google-maps-builder' ),
					'bus_station'             => __( 'Bus Station', 'google-maps-builder' ),
					'cafe'                    => __( 'Cafe', 'google-maps-builder' ),
					'campground'              => __( 'Campground', 'google-maps-builder' ),
					'car_dealer'              => __( 'Car Dealer', 'google-maps-builder' ),
					'car_rental'              => __( 'Car Rental', 'google-maps-builder' ),
					'car_repair'              => __( 'Car Repair', 'google-maps-builder' ),
					'car_wash'                => __( 'Car Wash', 'google-maps-builder' ),
					'casino'                  => __( 'Casino', 'google-maps-builder' ),
					'cemetery'                => __( 'Cemetery', 'google-maps-builder' ),
					'church'                  => __( 'Church', 'google-maps-builder' ),
					'city_hall'               => __( 'City Hall', 'google-maps-builder' ),
					'clothing_store'          => __( 'Clothing Store', 'google-maps-builder' ),
					'convenience_store'       => __( 'Convenience Store', 'google-maps-builder' ),
					'courthouse'              => __( 'Courthouse', 'google-maps-builder' ),
					'dentist'                 => __( 'Dentist', 'google-maps-builder' ),
					'department_store'        => __( 'Department Store', 'google-maps-builder' ),
					'doctor'                  => __( 'Doctor', 'google-maps-builder' ),
					'electrician'             => __( 'Electrician', 'google-maps-builder' ),
					'electronics_store'       => __( 'Electronics Store', 'google-maps-builder' ),
					'embassy'                 => __( 'Embassy', 'google-maps-builder' ),
					'establishment'           => __( 'Establishment', 'google-maps-builder' ),
					'finance'                 => __( 'Finance', 'google-maps-builder' ),
					'fire_station'            => __( 'Fire Station', 'google-maps-builder' ),
					'florist'                 => __( 'Florist', 'google-maps-builder' ),
					'food'                    => __( 'Food', 'google-maps-builder' ),
					'funeral_home'            => __( 'Funeral Home', 'google-maps-builder' ),
					'furniture_store'         => __( 'Furniture_store', 'google-maps-builder' ),
					'gas_station'             => __( 'Gas Station', 'google-maps-builder' ),
					'general_contractor'      => __( 'General Contractor', 'google-maps-builder' ),
					'grocery_or_supermarket'  => __( 'Grocery or Supermarket', 'google-maps-builder' ),
					'gym'                     => __( 'Gym', 'google-maps-builder' ),
					'hair_care'               => __( 'Hair Care', 'google-maps-builder' ),
					'hardware_store'          => __( 'Hardware Store', 'google-maps-builder' ),
					'health'                  => __( 'Health', 'google-maps-builder' ),
					'hindu_temple'            => __( 'Hindu Temple', 'google-maps-builder' ),
					'home_goods_store'        => __( 'Home Goods Store', 'google-maps-builder' ),
					'hospital'                => __( 'Hospital', 'google-maps-builder' ),
					'insurance_agency'        => __( 'Insurance Agency', 'google-maps-builder' ),
					'jewelry_store'           => __( 'Jewelry Store', 'google-maps-builder' ),
					'laundry'                 => __( 'Laundry', 'google-maps-builder' ),
					'lawyer'                  => __( 'Lawyer', 'google-maps-builder' ),
					'library'                 => __( 'Library', 'google-maps-builder' ),
					'liquor_store'            => __( 'Liquor Store', 'google-maps-builder' ),
					'local_government_office' => __( 'Local Government Office', 'google-maps-builder' ),
					'locksmith'               => __( 'Locksmith', 'google-maps-builder' ),
					'lodging'                 => __( 'Lodging', 'google-maps-builder' ),
					'meal_delivery'           => __( 'Meal Delivery', 'google-maps-builder' ),
					'meal_takeaway'           => __( 'Meal Takeaway', 'google-maps-builder' ),
					'mosque'                  => __( 'Mosque', 'google-maps-builder' ),
					'movie_rental'            => __( 'Movie Rental', 'google-maps-builder' ),
					'movie_theater'           => __( 'Movie Theater', 'google-maps-builder' ),
					'moving_company'          => __( 'Moving Company', 'google-maps-builder' ),
					'museum'                  => __( 'Museum', 'google-maps-builder' ),
					'night_club'              => __( 'Night Club', 'google-maps-builder' ),
					'painter'                 => __( 'Painter', 'google-maps-builder' ),
					'park'                    => __( 'Park', 'google-maps-builder' ),
					'parking'                 => __( 'Parking', 'google-maps-builder' ),
					'pet_store'               => __( 'Pet Store', 'google-maps-builder' ),
					'pharmacy'                => __( 'Pharmacy', 'google-maps-builder' ),
					'physiotherapist'         => __( 'Physiotherapist', 'google-maps-builder' ),
					'place_of_worship'        => __( 'Place of Worship', 'google-maps-builder' ),
					'plumber'                 => __( 'Plumber', 'google-maps-builder' ),
					'police'                  => __( 'Police', 'google-maps-builder' ),
					'post_office'             => __( 'Post Office', 'google-maps-builder' ),
					'real_estate_agency'      => __( 'Real Estate Agency', 'google-maps-builder' ),
					'restaurant'              => __( 'Restaurant', 'google-maps-builder' ),
					'roofing_contractor'      => __( 'Roofing Contractor', 'google-maps-builder' ),
					'rv_park'                 => __( 'RV Park', 'google-maps-builder' ),
					'school'                  => __( 'School', 'google-maps-builder' ),
					'shoe_store'              => __( 'Shoe Store', 'google-maps-builder' ),
					'shopping_mall'           => __( 'Shopping Mall', 'google-maps-builder' ),
					'spa'                     => __( 'Spa', 'google-maps-builder' ),
					'stadium'                 => __( 'Stadium', 'google-maps-builder' ),
					'storage'                 => __( 'Storage', 'google-maps-builder' ),
					'store'                   => __( 'Store', 'google-maps-builder' ),
					'subway_station'          => __( 'Subway Station', 'google-maps-builder' ),
					'synagogue'               => __( 'Synagogue', 'google-maps-builder' ),
					'taxi_stand'              => __( 'Taxi Stand', 'google-maps-builder' ),
					'train_station'           => __( 'Train Station', 'google-maps-builder' ),
					'travel_agency'           => __( 'Travel Agency', 'google-maps-builder' ),
					'university'              => __( 'University', 'google-maps-builder' ),
					'veterinary_care'         => __( 'Veterinary Care', 'google-maps-builder' ),
					'zoo'                     => __( 'Zoo', 'google-maps-builder' )
				) )
			)
		);

		/**
		 * Display Options
		 */
		$this->display_options = cmb2_get_metabox( array(
			'id'           => 'google_maps_options',
			'title'        => __( 'Display Options', 'google-maps-builder' ),
			'object_types' => array( 'google_maps' ), // post type
			'context'      => 'side', //  'normal', 'advanced', or 'side'
			'priority'     => 'default', //  'high', 'core', 'default' or 'low'
			'show_names'   => true, // Show field names on the left
		) );

		$this->display_options->add_field( array(
			'name'           => __( 'Map Size', 'google-maps-builder' ),
			'id'             => $prefix . 'width_height',
			'type'           => 'width_height',
			'width_std'      => $default_options['width'],
			'width_unit_std' => $default_options['width_unit'],
			'height_std'     => $default_options['height'],
			'desc'           => '',
		) );
		$this->display_options->add_field( array(
			'name'    => __( 'Map Location', 'google-maps-builder' ),
			'id'      => $prefix . 'lat_lng',
			'type'    => 'lat_lng',
			'lat_std' => '',
			'lng_std' => '',
			'desc'    => '',
		) );
		$this->display_options->add_field( array(
			'name'    => __( 'Map Type', 'google-maps-builder' ),
			'id'      => $prefix . 'type',
			'type'    => 'select',
			'default' => 'default',
			'options' => array(
				'RoadMap'   => __( 'Road Map', 'google-maps-builder' ),
				'Satellite' => __( 'Satellite', 'google-maps-builder' ),
				'Hybrid'    => __( 'Hybrid', 'google-maps-builder' ),
				'Terrain'   => __( 'Terrain', 'google-maps-builder' )
			),
		) );
		$this->display_options->add_field( array(
			'name'    => 'Zoom',
			'desc'    => __( 'Adjust the map zoom (0-21)', 'google-maps-builder' ),
			'id'      => $prefix . 'zoom',
			'type'    => 'select',
			'default' => '15',
			'options' => apply_filters( 'gmb_map_zoom_levels', array(
					'21' => '21',
					'20' => '20',
					'19' => '19',
					'18' => '18',
					'17' => '17',
					'16' => '16',
					'15' => '15',
					'14' => '14',
					'13' => '13',
					'12' => '12',
					'11' => '11',
					'10' => '10',
					'9'  => '9',
					'8'  => '8',
					'7'  => '7',
					'6'  => '6',
					'5'  => '5',
					'4'  => '4',
					'3'  => '3',
					'2'  => '2',
					'1'  => '1',
					'0'  => '0',
				)
			)
		) );
		$this->display_options->add_field( array(
			'name'              => 'Map Layers',
			'desc'              => __( 'Layers provide additional information overlayed on the map.', 'google-maps-builder' ),
			'id'                => $prefix . 'layers',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => apply_filters( 'gmb_map_zoom_levels', array(
					'traffic' => __( 'Traffic', 'google-maps-builder' ),
					'transit' => __( 'Transit', 'google-maps-builder' ),
					'bicycle' => __( 'Bicycle', 'google-maps-builder' ),
				)
			)
		) );

		$this->display_options->add_field( array(
			'name'    => __( 'Map Theme', 'google-maps-builder' ),
			'desc'    => sprintf( __( 'Set optional preconfigured <a href="%1s" class="snazzy-link new-window"  target="_blank">Snazzy Maps</a> styles by selecting from the dropdown above.', 'google-maps-builder' ), esc_url( 'http://snazzymaps.com' ) ),
			'id'      => $prefix . 'theme',
			'type'    => 'select',
			'default' => 'none',
			'options' => apply_filters( 'gmb_snazzy_maps', array(
				'none' => __( 'None', 'google-maps-builder' ),
				'68'   => __( 'Aqua', 'google-maps-builder' ),
				'73'   => __( 'A Dark World', 'google-maps-builder' ),
				'28'   => __( 'Bluish', 'google-maps-builder' ),
				'80'   => __( 'Cool Grey', 'google-maps-builder' ),
				'77'   => __( 'Clean Cut', 'google-maps-builder' ),
				'36'   => __( 'Flat Green', 'google-maps-builder' ),
				'44'   => __( 'MapBox', 'google-maps-builder' ),
				'83'   => __( 'Muted Blue', 'google-maps-builder' ),
				'22'   => __( 'Old Timey', 'google-maps-builder' ),
				'1'    => __( 'Pale Dawn', 'google-maps-builder' ),
				'19'   => __( 'Paper', 'google-maps-builder' ),
				'37'   => __( 'Lunar Landscape', 'google-maps-builder' ),
				'75'   => __( 'Shade of Green', 'google-maps-builder' ),
				'27'   => __( 'Shift Worker', 'google-maps-builder' ),
				'15'   => __( 'Subtle Grayscale', 'google-maps-builder' ),
				'50'   => __( 'The Endless Atlas', 'google-maps-builder' ),
			) )
		) );
		$this->display_options->add_field( array(
			'name' => __( 'Custom Map Theme JSON', 'google-maps-builder' ),
			'desc' => __( 'Paste the Snazzy Map JSON code into the field above to set the theme.', 'google-maps-builder' ),
			'id'   => $prefix . 'theme_json',
			'type' => 'textarea_code'
		) );


		// Control options.
		$this->control_options = cmb2_get_metabox( array(
			'id'           => 'google_maps_control_options',
			'title'        => __( 'Map Controls', 'google-maps-builder' ),
			'object_types' => array( 'google_maps' ), // post type
			'context'      => 'side', //  'normal', 'advanced', or 'side'
			'priority'     => 'default', //  'high', 'core', 'default' or 'low'
			'show_names'   => true, // Show field names on the left
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Zoom Control', 'google-maps-builder' ),
			'id'      => $prefix . 'zoom_control',
			'type'    => 'select',
			'default' => 'default',
			'options' => array(
				'none'    => __( 'None', 'google-maps-builder' ),
				'small'   => __( 'Small', 'google-maps-builder' ),
				'large'   => __( 'Large', 'google-maps-builder' ),
				'default' => __( 'Default', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Street View', 'google-maps-builder' ),
			'id'      => $prefix . 'street_view',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'none' => __( 'None', 'google-maps-builder' ),
				'true' => __( 'Standard', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Pan Control', 'google-maps-builder' ),
			'id'      => $prefix . 'pan',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'none' => __( 'None', 'google-maps-builder' ),
				'true' => __( 'Standard', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Map Type Control', 'google-maps-builder' ),
			'id'      => $prefix . 'map_type_control',
			'type'    => 'select',
			'default' => 'horizontal_bar',
			'options' => array(
				'none'           => __( 'None', 'google-maps-builder' ),
				'dropdown_menu'  => __( 'Dropdown Menu', 'google-maps-builder' ),
				'horizontal_bar' => __( 'Horizontal Bar', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Draggable Map', 'google-maps-builder' ),
			'id'      => $prefix . 'draggable',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'none' => __( 'None', 'google-maps-builder' ),
				'true' => __( 'Standard', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Double Click to Zoom', 'google-maps-builder' ),
			'id'      => $prefix . 'double_click',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'none' => __( 'None', 'google-maps-builder' ),
				'true' => __( 'Standard', 'google-maps-builder' ),
			),
		) );

		$this->control_options->add_field( array(
			'name'    => __( 'Mouse Wheel to Zoom', 'google-maps-builder' ),
			'id'      => $prefix . 'wheel_zoom',
			'type'    => 'select',
			'default' => 'true',
			'options' => array(
				'none' => __( 'Disabled', 'google-maps-builder' ),
				'true' => __( 'Standard', 'google-maps-builder' ),
			),
		) );


	}

	/**
	 * CMB Width Height
	 *
	 * Custom CMB field for Gmap width and height
	 *
	 * @param $field
	 * @param $meta
	 */
	function cmb2_render_width_height( $field, $meta ) {
		$default_options = $this->get_default_map_options();
		$meta            = wp_parse_args(
			$meta, array(
				'width'           => $default_options['width'],
				'map_width_unit'  => $default_options['width_unit'],
				'height'          => $default_options['height'],
				'map_height_unit' => $default_options['height_unit'],
			)
		);

		$output = '<div id="width_height_wrap" class="clear">';

		//width
		$output .= '<div id="width_wrap" class="clear">';
		$output .= '<label class="width-label size-label">' . __( 'Width', 'google-maps-builder' ) . ':</label><input type="text" class="regular-text map-width" name="' . $field->args( 'id' ) . '[width]" id="' . $field->args( 'id' ) . '-width" value="' . ( $meta['width'] ? $meta['width'] : $field->args( 'width_std' ) ) . '" />';
		$output .= '<div class="size-labels-wrap">';
		$output .= '<input id="width_unit_percent" type="radio" name="' . $field->args( 'id' ) . '[map_width_unit]" class="width_radio" value="%" ' . ( $meta['map_width_unit'] === '%' || $field->args( 'width_unit_std' ) === '%' ? 'checked="checked"' : '' ) . '><label class="width_unit_label">%</label>';
		$output .= '<input id="width_unit_px" type="radio" name="' . $field->args( 'id' ) . '[map_width_unit]" class="width_radio" value="px" ' . ( $meta['map_width_unit'] === 'px' ? 'checked="checked"' : '' ) . ' ><label class="width_unit_label">px</label>';
		$output .= '</div>';
		$output .= '</div>';

		//height
		$output .= '<div id="height_wrap" class="clear clearfix">';
		$output .= '<label for="' . $field->args( 'id' ) . '[height]" class="height-label size-label">' . __( 'Height', 'google-maps-builder' ) . ':</label><input type="text" class="regular-text map-height" name="' . $field->args( 'id' ) . '[height]" id="' . $field->args( 'id' ) . '-height" value="' . ( $meta['height'] ? $meta['height'] : $field->args( 'height_std' ) ) . '" />';

		$output .= '<div class="size-labels-wrap">';
		$output .= '<input id="height_unit_percent" type="radio" name="' . $field->args( 'id' ) . '[map_height_unit]" class="height_radio" value="%" ' . ( $meta['map_height_unit'] === '%' || $field->args( 'height_unit_std' ) === '%' ? 'checked="checked"' : '' ) . '><label class="height_unit_label">%</label>';
		$output .= '<input id="height_unit_px" type="radio" name="' . $field->args( 'id' ) . '[map_height_unit]" class="height_radio" value="px" ' . ( $meta['map_height_unit'] === 'px' ? 'checked="checked"' : '' ) . ' ><label class="height_unit_label">px</label>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<p class="cmb2-metabox-description">' . __( 'Configure the default map width and height.', 'google-maps-builder' ) . '</p>';

		$output .= '</div>'; //end #width_height_wrap


		echo $output;


	}


	/**
	 * CMB Lat Lng
	 *
	 * Custom CMB field for Gmap latitude and longitude
	 *
	 * @param $field
	 * @param $meta
	 */
	function cmb2_render_lat_lng( $field, $meta ) {
		$meta = wp_parse_args(
			$meta, array(
				'latitude'  => '',
				'longitude' => '',
			)
		);

		//lat lng
		$output = '<div id="lat-lng-wrap">
					<div class="coordinates-wrap clear">
							<div class="lat-lng-wrap lat-wrap clear"><span>' . __( 'Latitude:', 'google-maps-builder' ) . '</span>
							<input type="text" class="regular-text latitude" name="' . $field->args( 'id' ) . '[latitude]" id="' . $field->args( 'id' ) . '-latitude" value="' . ( $meta['latitude'] ? $meta['latitude'] : $field->args( 'lat_std' ) ) . '" />
							</div>
							<div class="lat-lng-wrap lng-wrap clear"><span>' . __( 'Longitude:', 'google-maps-builder' ) . '</span>
							<input type="text" class="regular-text longitude" name="' . $field->args( 'id' ) . '[longitude]" id="' . $field->args( 'id' ) . '-longitude" value="' . ( $meta['longitude'] ? $meta['longitude'] : $field->args( 'lng_std' ) ) . '" />
							</div>';
		$output .= '<div class="wpgp-message lat-lng-change-message clear"><p>' . __( 'Lat/lng changed', 'google-maps-builder' ) . '</p><a href="#" class="button lat-lng-update-btn button-small" data-lat="" data-lng="">' . __( 'Update', 'google-maps-builder' ) . '</a></div>';
		$output .= '</div><!-- /.coordinates-wrap -->
						</div>';


		echo $output;


	}

	/**
	 * Custom Google Geocoder field
	 * @since  1.0.0
	 *
	 * @param $field
	 * @param $meta
	 *
	 * @return array
	 */
	function cmb2_render_google_geocoder( $field, $meta ) {

		$meta = wp_parse_args(
			$meta, array(
				'geocode' => '',
			)
		);

		echo '<div class="autocomplete-wrap"><input type="text" name="' . $field->args( 'id' ) . '[geocode]" id="' . $field->args( 'id' ) . '" value="" class="search-autocomplete" /><p class="autocomplete-description">' .
		     sprintf( __( 'Enter the name of a place or an address above to create a map marker or %1$sDrop a Marker%2$s', 'google-maps-builder' ), '<a href="#" class="drop-marker button button-small"><span class="dashicons dashicons-location"></span>', '</a>' ) .
		     '</p></div>';

	}

	/**
	 *  Custom Google Geocoder field
	 * @since  1.0.0
	 */
	function cmb2_render_google_maps_preview( $field, $meta ) {
		global $post;
		$meta            = wp_parse_args( $meta, array() );
		$wh_value        = get_post_meta( $post->ID, 'gmb_width_height', true );
		$lat_lng         = get_post_meta( $post->ID, 'gmb_lat_lng', true );
		$default_options = $this->get_default_map_options();


		$output = '<div class="places-loading wpgp-loading">' . __( 'Loading Places', 'google-maps-builder' ) . '</div>';
		$output .= '<div id="google-map-wrap">';
		$output .= '<div id="map" style="height:600px; width:100%;"></div>';

		//Toolbar
		$output .= '<div id="map-toolbar">';
		$output .= '<button class="add-location button button-small gmb-magnific-inline" data-target="cmb2-id-gmb-geocoder" data-auto-focus="true"><span class="dashicons dashicons-pressthis"></span>' . __( 'Add Location', 'google-maps-builder' ) . '</button>';
		$output .= '<button class="drop-marker button button-small"><span class="dashicons dashicons-location"></span>' . __( 'Drop a Marker', 'google-maps-builder' ) . '</button>';
		$output .= '<button class="goto-location button button-small gmb-magnific-inline" data-target="map-autocomplete-wrap" data-auto-focus="true"><span class="dashicons dashicons-admin-site"></span>' . __( 'Goto Location', 'google-maps-builder' ) . '</button>';
		$output .= '<button class="edit-title button  button-small gmb-magnific-inline" data-target="map-title-wrap" data-auto-focus="true"><span class="dashicons dashicons-edit"></span>' . __( 'Edit Map Title', 'google-maps-builder' ) . '</button>';

		$output .= '<div class="live-lat-lng-wrap clearfix">';
		$output .= '<button disabled class="update-lat-lng button button-small">' . __( 'Set Lat/Lng', 'google-maps-builder' ) . '</button>';
		$output .= '<div class="live-latitude-wrap"><span class="live-latitude-label">' . __( 'Lat:', 'google-maps-builder' ) . '</span><span class="live-latitude">' . ( isset( $lat_lng['latitude'] ) ? $lat_lng['latitude'] : '' ) . '</span></div>';
		$output .= '<div class="live-longitude-wrap"><span class="live-longitude-label">' . __( 'Lng:', 'google-maps-builder' ) . '</span><span class="live-longitude">' . ( isset( $lat_lng['longitude'] ) ? $lat_lng['longitude'] : '' ) . '</span></div>';
		$output .= '</div>'; //End .live-lat-lng-wrap
		$output .= '</div>'; //End #map-toolbar
		$output .= '</div>'; //End #map

		$output .= '<div class="white-popup mfp-hide map-title-wrap">';
		$output .= '<div class="inner-modal-wrap">';
		$output .= '<div class="inner-modal-container">';
		$output .= '<div class="inner-modal">';
		$output .= '<label for="post_title" class="map-title">' . __( 'Map Title', 'google-maps-builder' ) . '</label>';
		$output .= '<p class="cmb2-metabox-description">' . __( 'Give your Map a descriptive title', 'google-maps-builder' ) . '</p>';
		$output .= '<input type="text" name="model_post_title" size="30" value="' . get_the_title() . '" id="modal_title" spellcheck="true" autocomplete="off" placeholder="' . __( 'Enter map title', 'google-maps-builder' ) . '">';
		$output .= '<button type="button" class="gmb-modal-close">&times;</button>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		$output .= '<div class="white-popup mfp-hide map-autocomplete-wrap">';
		$output .= '<div class="inner-modal-wrap">';
		$output .= '<div class="inner-modal-container">';
		$output .= '<div class="inner-modal">';
		$output .= '<label for="map-location-autocomplete" class="map-title">' . __( 'Enter a Location', 'google-maps-builder' ) . '</label>';
		$output .= '<p class="cmb2-metabox-description">' . __( 'Type your point of interest below and the map will be re-centered over that location', 'google-maps-builder' ) . '</p>';
		$output .= '<button type="button" class="gmb-modal-close">&times;</button>';
		$output .= '<input type="text" name="" size="30" id="map-location-autocomplete">';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		//Markers Modal
		gmb_include_view( 'admin/views/markers.php', false, $this->view_data() );

		//Places search
		$output = $this->places_search( $output );
		echo apply_filters( 'google_maps_preview', $output );

	}


	/**
	 * Setup Custom CPT Columns
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	function setup_custom_columns( $columns ) {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __( 'Google Map Title', 'google-maps-builder' ),
			'shortcode' => __( 'Shortcode', 'google-maps-builder' ),
			'date'      => __( 'Creation Date', 'google-maps-builder' )
		);

		return $columns;
	}


	/**
	 * Configure Custom Columns
	 *
	 * Sets the content of the custom column contents
	 *
	 * @param $column
	 * @param $post_id
	 */
	function configure_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'shortcode' :

				//Shortcode column with select all input
				$shortcode = htmlentities( '[google_maps id="' . $post_id . '"]' );
				echo '<input onClick="this.setSelectionRange(0, this.value.length)" type="text" class="shortcode-input" readonly value="' . $shortcode . '">';

				break;
			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}


	/**
	 * Close certain metaboxes by default
	 *
	 * @param $closed
	 *
	 * @return array
	 */
	function closed_meta_boxes( $closed ) {

		if ( false === $closed ) {
			$closed = array( 'google_maps_options', 'google_maps_control_options', 'google_maps_markers' );
		}

		return $closed;
	}

	/**
	 * Used in pro to add places search to output
	 *
	 * @since 2.1.0
	 *
	 * @param $output
	 *
	 * @return string
	 */
	function places_search( $output ) {
		return $output;
	}


}

