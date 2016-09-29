<?php

/**
 * Class Google_Maps_Builder_Core_Settings
 */
abstract class Google_Maps_Builder_Core_Settings extends Google_Maps_Builder_Core_Interface {

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	protected $plugin_options = array();


	/**
	 * @var string
	 */
	public $options_page;

	/**
	 * Option key, and option page slug
	 *
	 * @var string
	 */
	protected static $key = 'gmb_settings';

	/**
	 * Holds settings page name
	 *
	 * @since 2.1.0
	 *
	 * @var string
	 */
	protected $page_name;


	/**
	 * Google_Maps_Builder_Core_Settings constructor.
	 */
	public function __construct() {

		parent::__construct();
		$this->page_name = __( 'Maps Builder Settings', 'google-maps-builder' );

		//Create Settings submenu
		add_action( 'admin_init', array( $this, 'mninit' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'wp_ajax_hide_welcome', array( $this, 'hide_welcome_callback' ) );

		//Add links/information to plugin row meta
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'add_plugin_page_links' ), 10, 2 );

		add_action( 'gmb_settings_tabs', array( $this, 'settings_tabs' ) );

	}


	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function mninit() {
		register_setting( self::$key, self::$key );
	}

	/**
	 * Add menu options page
	 * @since 1.0.0
	 */
	public function add_page() {

		$this->options_page = add_submenu_page(
			'edit.php?post_type=google_maps',
			$this->page_name,
			__( 'Settings', 'google-maps-builder' ),
			'manage_options',
			self::$key,
			array( $this, 'admin_page_display' )
		);

	}


	/**
	 * Hide the Settings welcome on click
	 *
	 * Sets a user meta key that once set
	 */
	public function hide_welcome_callback() {
		global $current_user;
		$user_id = $current_user->ID;
		add_user_meta( $user_id, 'gmb_hide_pro_welcome', 'true', true );
		wp_die(); // ajax call must die to avoid trailing 0 in your response
	}


	/**
	 * Admin Page Display
	 *
	 * Admin page markup. Mostly handled by CMB
	 */
	public function admin_page_display() {

		gmb_include_view( 'admin/views/settings-page.php', false, array_merge( $this->common_settings_page_data(), $this->settings_page_data() ) );

	}

	/**
	 * Handle main data for the settings page
	 *
	 * Must override in plugin
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	protected function settings_page_data() {
		_doing_it_wrong( __FUNCTION__, __( 'Must override in plugin', 'google-maps-builder' ), '2.1.0' );

		//place holder
		$data = array(
			'welcome'     => '',
			'sub_heading' => ''
		);

		return $this->view_data( $data, true );
	}

	/**
	 * Common data for settings page.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	protected function common_settings_page_data() {
		return array(
			'plugin_slug'           => 'google-maps-builder',
			'key'                   => $this->key(),
			'general_option_fields' => $this->general_option_fields(),
			'map_option_fields'     => $this->map_option_fields()
		);
	}

	/**
	 * General Option Fields
	 * Defines the plugin option metabox and field configuration
	 * @since  1.0.0
	 * @return array
	 */
	public function general_option_fields() {
		$prefix = $this->prefix();

		$this->plugin_options = array(
			'id'         => 'plugin_options',
			'show_on'    => array( 'key' => 'options-page', 'value' => array( self::$key, ), ),
			'show_names' => true,
			'fields'     => array(
				array(
					'name'    => __( 'Post Type Slug', 'google-maps-builder' ),
					'desc'    => sprintf( __( 'Customize the default slug for the Maps Builder post type. %1$sResave (flush) permalinks%2$s after customizing.', 'google-maps-builder' ), '<a href="' . esc_url( '/wp-admin/options-permalink.php' ) . '">"', '</a>' ),
					'default' => 'google-maps',
					'id'      => $prefix . 'custom_slug',
					'type'    => 'text_small'
				),
				array(
					'name'    => __( 'Menu Position', 'google-maps-builder' ),
					'desc'    => sprintf( __( 'Set the menu position for Google Maps Builder. See the %1$smenu_position arg%2$s.', 'google-maps-builder' ), '<a href="' . esc_url( 'https://codex.wordpress.org/Function_Reference/register_post_type#menu_position' ) . '" class="new-window" target="_blank">', '</a>' ),
					'default' => '21.3',
					'id'      => $prefix . 'menu_position',
					'type'    => 'text_small'
				),
				array(
					'name'    => __( 'Has Archive', 'google-maps-builder' ),
					'id'      => $prefix . 'has_archive',
					'desc'    => sprintf( __( 'Controls the post type archive page. See <a href="%s">Resave (flush) permalinks</a> after customizing.', 'google-maps-builder' ), esc_url( '/wp-admin/options-permalink.php' ) ),
					'type'    => 'radio_inline',
					'options' => array(
						'true'  => __( 'Yes', 'cmb' ),
						'false' => __( 'No', 'cmb' ),
					),
				),
				array(
					'name'    => __( 'Opening Map Builder', 'google-maps-builder' ),
					'id'      => $prefix . 'open_builder',
					'desc'    => __( 'Do you want the Map Builder customizer to open by default when editing maps?', 'google-maps-builder' ),
					'type'    => 'radio_inline',
					'options' => array(
						'true'  => __( 'Yes', 'cmb' ),
						'false' => __( 'No', 'cmb' ),
					),
				),

			),
		);

		return apply_filters( 'gmb_general_options_fields', $this->plugin_options );

	}

	/**
	 * Map Option Fields
	 *
	 * Defines the plugin option metabox and field configuration
	 * @return array
	 */
	public function map_option_fields() {

		$prefix = $this->prefix();

		$this->plugin_options = array(
			'id'         => 'plugin_options',
			'show_on'    => array( 'key' => 'options-page', 'value' => array( self::$key, ), ),
			'show_names' => true,
			'fields'     => array(
				array(
					'name' => __( 'Google Maps API Key', 'google-maps-builder' ),
					'desc' => sprintf( __( 'Google now requires a valid Google Maps JavaScript API key to function. <br><a href="%1$s" target="_blank" class="new-window">Learn how to obtain a Google Maps API key</a>.', 'google-maps-builder' ),  esc_url( 'https://wordimpress.com/documentation/maps-builder-pro/creating-maps-api-key/' ) ),
					'id'   => $prefix . 'maps_api_key',
					'type' => 'text',
				),
				array(
					'name'           => __( 'Map Size', 'google-maps-builder' ),
					'id'             => $prefix . 'width_height',
					'type'           => 'width_height',
					'width_std'      => '100',
					'width_unit_std' => '%',
					'height_std'     => '600',
					'lat_std'        => '32.7153292',
					'lng_std'        => '-117.15725509',
					'desc'           => '',
				),
				array(
					'name'    => __( 'Default Location', 'google-maps-builder' ),
					'id'      => $prefix . 'lat_lng',
					'type'    => 'lat_lng_default',
					'lat_std' => '32.7153292',
					'lng_std' => '-117.15725509',
					'desc'    => '',
				),

			),
		);

		return apply_filters( 'gmb_map_options_fields', $this->plugin_options );

	}


	/**
	 * CMB Lat Lng.
	 *
	 * Custom CMB field for Gmap latitude and longitude.
	 *
	 * @param $field
	 * @param $meta
	 */
	function cmb2_render_lat_lng_default( $field, $meta ) {

		$meta = wp_parse_args(
			$meta, array(
				'geolocate_map' => 'no',
				'latitude'      => '',
				'longitude'     => '',
			)
		);

		//lat_lng
		$output = '<div id="lat-lng-wrap"><div class="coordinates-wrap clear">';
		$output .= '<div class="lat-lng-wrap lat-wrap clear"><span>' . __( 'Latitude', 'google-maps-builder' ) . ': </span>
						<input type="text" class="regular-text latitude" name="' . $field->args['id'] . '[latitude]" id="' . $field->args['id'] . '-latitude" value="' . ( $meta['latitude'] ? $meta['latitude'] : $field->args['lat_std'] ) . '" /></div><div class="lat-lng-wrap lng-wrap clear"><span>' . __( 'Longitude', 'google-maps-builder' ) . ': </span>
						<input type="text" class="regular-text longitude" name="' . $field->args['id'] . '[longitude]" id="' . $field->args['id'] . '-longitude" value="' . ( $meta['longitude'] ? $meta['longitude'] : $field->args['lng_std'] ) . '" />
				</div>';

		$output .= '<p class="small-desc">' . sprintf( __( 'For quick lat/lng lookup use %1$sthis service%2$s', 'google-maps-builder' ), '<a href="' . esc_url( 'http://www.latlong.net/' ) . '" class="new-window" target="_blank">', '</a>' ) . '</p>';
		$output .= '</div><!-- /.search-coordinates-wrap -->';
		$output .= '</div>';

		//Geolocate
		$output .= '<div id="geolocate-wrap" class="clear">';
		$output .= '<label class="geocode-label size-label">' . __( 'Geolocate Position', 'google-maps-builder' ) . ':</label>';
		$output .= '<div class="geolocate-radio-wrap size-labels-wrap">';
		$output .= '<label class="yes-label label-left"><input id="geolocate_map_yes" type="radio" name="' . $field->args['id'] . '[geolocate_map]" class="geolocate_map_radio radio-left" value="yes" ' . ( $meta['geolocate_map'] === 'yes' ? 'checked="checked"' : '' ) . '>' . __( 'Yes', 'google-maps-builder' ) . '</label>';

		$output .= '<label class="no-label label-left"><input id="geolocate_map_no" type="radio" name="' . $field->args['id'] . '[geolocate_map]" class="geolocate_map_radio radio-left" value="no" ' . ( ( $meta['geolocate_map'] === 'no' ) ? 'checked="checked"' : '' ) . ' >' . __( 'No', 'google-maps-builder' ) . '</label>';
		$output .= '</div>';
		$output .= '<p class="cmb2-metabox-description clear">' . sprintf( __( 'When creating a new map the plugin will use your current longitude and latitude for the base location. Please note, Chrome 50+ %1$srequires a secure https connection%2$s (SSL certificate) to access geolocation features and other browsers may soon follow suit.', 'google-maps-builder' ), '<a href="https://developers.google.com/web/updates/2016/04/geolocation-on-secure-contexts-only" class="new-window" target="_blank">', '</a>' ) . '</p>';

		$output .= '</div><!--/end. geolocate-wrap -->';

		echo $output;


	}


	/**
	 * Make public the protected $key variable.
	 * @since  0.1.0
	 * @return string  Option key
	 */
	public static function key() {
		return self::$key;
	}


	/**
	 * Add links to Plugin listings view
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	function add_plugin_page_links( $links, $file ) {

		if ( $file == GMB_PLUGIN_BASE ) {

			// Add Widget Page link to our plugin
			$settings_link = '<a href="edit.php?post_type=google_maps&page=' . self::$key . '" title="' . __( 'Visit the Google Maps Builder plugin settings page', 'google-maps-builder' ) . '">' . __( 'Settings', 'google-maps-builder' ) . '</a>';

			array_unshift( $links, $settings_link );

		}

		return $links;
	}

	/**
	 * Add Plugin Meta Links
	 *
	 * Adds links on the admin plugin listing page
	 *
	 * @param $meta
	 * @param $file
	 *
	 * @return array
	 */
	function add_plugin_meta_links( $meta, $file ) {

		if ( $file == GMB_PLUGIN_BASE ) {
			$meta[] = "<a href='https://wordpress.org/support/view/plugin-reviews/google-maps-builder' target='_blank' title='" . __( 'Rate Google Maps Builder on WordPress.org', 'google-maps-builder' ) . "'>" . __( 'Rate Plugin', 'google-maps-builder' ) . "</a>";
			$meta[] = '<a href="https://wordpress.org/support/plugin/google-maps-builder/" target="_blank" title="' . __( 'Get plugin support via the WordPress community', 'google-maps-builder' ) . '">' . __( 'Support', 'google-maps-builder' ) . '</a>';

		}

		return $meta;
	}


	/**
	 * Modify CMB2 Default Form Output
	 *
	 * @param string @args
	 *
	 * @since 2.0
	 *
	 * @param $form_format
	 * @param $object_id
	 * @param $cmb
	 *
	 * @return string
	 */
	function gmb_modify_cmb2_form_output( $form_format, $object_id, $cmb ) {

		//only modify the give settings form
		if ( 'gmb_settings' == $object_id && 'plugin_options' == $cmb->cmb_id ) {

			return '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s<div class="gmb-submit-wrap"><input type="submit" name="submit-cmb" value="' . __( 'Save Settings', 'give' ) . '" class="button-primary"></div></form>';
		}

		return $form_format;

	}

	/**
	 * Get cmb2 prefix
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	protected function prefix() {
		$prefix = 'gmb_';

		return $prefix;
	}

	/**
	 * Markup for settings tab switcher
	 *
	 * @since 2.1.0
	 *
	 * @uses "gmb_settings_tabs" action
	 */
	public function settings_tabs( $active_tab ) {
		gmb_include_view( 'admin/views/settings-tabs.php', false, $this->view_data( $this->tab_settings( $active_tab ), true ) );
	}

	/**
	 * Tab Settings
	 *
	 * @param $active_tab
	 *
	 * @return array
	 */
	protected function tab_settings( $active_tab ) {
		return array(
			'active_tab' => $active_tab,
			'key'        => $this->key()
		);
	}


}

/**
 * Wrapper function around cmb_get_option
 *
 * @param  string $key Options array key
 *
 * @return mixed        Option value
 */
function gmb_get_option( $key = '' ) {
	return cmb2_get_option( Google_Maps_Builder_Settings::key(), $key );
}


/**
 * Remove an option
 *
 * Removes a setting value in the serialized settings option
 *
 * @since 2.1
 *
 * @param string $key The Key to delete
 *
 * @return boolean True if updated, false if not.
 */
function gmb_delete_option( $key = '' ) {

	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'gmb_settings' );

	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {
		unset( $options[ $key ] );
	}
	if ( isset( $_POST[ $key ] ) ) {
		unset( $_POST[ $key ] );
	}

	$did_update = update_option( 'gmb_settings', $options );

	return $did_update;
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 2.1
 * @return array Give settings
 */
function gmb_get_settings() {

	$settings = get_option( 'gmb_settings' );

	return (array) apply_filters( 'gmb_get_settings', $settings );

}
