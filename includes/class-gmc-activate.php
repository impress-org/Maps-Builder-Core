<?php
/**
 * Plugin Activation
 *
 * @package   Google_Maps_Builder
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress
 */

/**
 * Class Google_Maps_Builder_Core_Activate
 */
class Google_Maps_Builder_Core_Activate {

	/**
	 * API Nag Meta Tag
	 *
	 * @var $nag_meta_key
	 */
	protected $nag_meta_key;


	/**
	 * Initialize the plugin by setting localization and loading public scripts.
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	public function __construct() {

		$this->nag_meta_key = 'gmb_api_activation_ignore';

		// Activate plugin when new blog is added.
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		//Activation tooltips.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_pointer_script_style' ) );

		//Init CPT (after CMB2 -> hence the 10000 priority).
		add_action( 'init', array( $this, 'setup_post_type' ), 10000 );


		//API Admin Notice for New Installs.
		add_action( 'current_screen', array( $this, 'api_notice_ignore' ) );
		add_action( 'admin_notices', array( $this, 'activation_notice' ) );

	}

	/**
	 * Maps activation banner.
	 *
	 * Welcome the user and provide instructions to create and add a Google Maps API key.
	 *
	 * @see http://stackoverflow.com/questions/2769148/whats-the-api-key-for-in-google-maps-api-v3/37994162#37994162
	 *
	 * @since  1.0
	 */
	public function activation_notice() {

		$current_user = wp_get_current_user();

		// If the user has already dismissed our alert, bounce.
		if ( get_user_meta( $current_user->ID, $this->nag_meta_key ) ) {
			return;
		}

		//Only display if an API key is not entered.
		$api_key = gmb_get_option( 'gmb_maps_api_key' );

		if ( ! empty( $api_key ) ) {
			return;
		}

		//Only display if this is a new install (no maps).
		$count_maps = wp_count_posts( 'google_maps' );
		if ( $count_maps->publish != 0 ) {
			return;
		}

		//Don't show on the plugin's settings screen.
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'gmb_settings' ) {
			return;
		}


		//Free pr
		//@TODO: Provide support link
		$support_link = 'https://wordpress.org/plugins/google-maps-builder/';

		?>

		<style>

			div.gmb-svg-banner {
				float: left;
			}

			div.gmb-svg-banner > svg {
				width: 50px;
				height: 50px;
				float: left;
			}

			div.gmb-api-alert.updated {
				padding: 1em 2em;
				position: relative;
				border-color: #66BB6A;
			}

			div.gmb-api-alert img {
				max-width: 50px;
				position: relative;
				top: 1em;
			}

			div.gmb-banner-content-wrap {
				margin: 0 30px 0 70px;
			}

			div.gmb-api-alert h3 {
				font-weight: 300;
				margin: 5px 0 0;
			}

			div.gmb-api-alert h3 span {
				font-weight: 600;
				color: #66BB6A;
			}

			div.gmb-api-alert .alert-actions {
				position: relative;
				left: 70px;
			}

			div.gmb-api-alert a {
				color: #66BB6A;
			}

			div.gmb-api-alert .alert-actions a {
				text-decoration: underline;
				color: #808080;
			}

			div.gmb-api-alert .alert-actions a:hover {
				color: #555555;
			}

			div.gmb-api-alert .alert-actions a span {
				text-decoration: none;
				margin-right: 5px;
			}

			div.gmb-api-alert .dismiss {
				position: absolute;
				right: 15px;
				height: 100%;
				top: 50%;
				margin-top: -10px;
				outline: none;
				box-shadow: none;
				text-decoration: none;
				color: #333;
			}
		</style>

		<div class="updated gmb-api-alert">

			<div class="gmb-svg-banner"><?php gmb_include_view( 'admin/views/mascot-svg.php' ); ?></div>

			<div class="gmb-banner-content-wrap">
				<h3><?php
					printf(
						__( "Welcome to %s! Let's get started.", 'google-maps-builder' ),
						'<span>' . $this->plugin_name() . '</span>'
					);
					?></h3>

				<p><?php echo sprintf( __( 'You\'re almost ready to start building awesome Google Maps! But first, in order to use %1$s you need to enter a Google Maps API key in the %2$splugin settings%5$s. An API key with Maps and Places APIs is now required by Google to access their mapping services. Don\'t worry, getting an API key is free and easy.<br> %3$sLearn How to Create a Maps API Key &raquo; %5$s | %4$s Google API Console &raquo; %5$s', 'google-maps-builder' ), $this->plugin_name(), '<a href="' . esc_url( admin_url( 'edit.php?post_type=google_maps&page=gmb_settings' ) ) . '">', '<a href="https://wordimpress.com/documentation/maps-builder-pro/creating-maps-api-key/" target="_blank">', '<a href="https://console.cloud.google.com/" target="_blank">', '</a>' ); ?></p>
			</div>

			<a href="<?php
			//The Dismiss Button
			$nag_admin_dismiss_url = add_query_arg( array(
				$this->nag_meta_key => 0
			), admin_url() );

			echo esc_url( $nag_admin_dismiss_url ); ?>" class="dismiss"><span class="dashicons dashicons-dismiss"></span></a>

			<div class="alert-actions">

				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=google_maps&page=gmb_settings' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span><?php _e( 'Go to Settings', 'google-maps-builder' ); ?>
				</a>

				<a href="https://wordimpress.com/documentation/maps-builder-pro" target="_blank" style="margin-left:30px;"><span class="dashicons dashicons-media-text"></span><?php _e( 'Plugin Documentation', 'google-maps-builder' ); ?>
				</a>

				<a href="<?php echo $support_link; ?>" target="_blank" style="margin-left:30px;">
					<span class="dashicons dashicons-sos"></span><?php _e( 'Get Support', 'google-maps-builder' ); ?>
				</a>

			</div>

		</div>
		<?php
	}


	/**
	 * Ignore Nag.
	 *
	 * This is the action that allows the user to dismiss the banner it basically sets
	 * a tag to their user meta data.
	 */
	public function api_notice_ignore() {

		/* If user clicks to ignore the notice, add that to their user meta the banner then checks whether this tag exists already or not.
		 * See here: http://codex.wordpress.org/Function_Reference/add_user_meta
		 */
		if ( isset( $_GET[ $this->nag_meta_key ] ) && '0' == $_GET[ $this->nag_meta_key ] ) {

			//Get the global user
			global $current_user;
			$user_id = $current_user->ID;

			add_user_meta( $user_id, $this->nag_meta_key, 'true', true );
		}
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		//Remove Welcome Message Meta so User Sees it Again
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		//Display Tooltip
		$dismissed_pointers = explode( ',', get_user_meta( $user_id, 'dismissed_wp_pointers', true ) );

		// Check if our pointer is among dismissed ones and delete the meta so it displays again.
		if ( in_array( 'gmb_welcome_pointer', $dismissed_pointers ) ) {
			$key = array_search( 'gmb_welcome_pointer', $dismissed_pointers );
			delete_user_meta( $user_id, 'dismissed_wp_pointers', $key['gmb_welcome_pointer'] );
		}


		//Multisite Checks
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean $network_wide True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}


	/**
	 * ADMIN: Activation Welcome Tooltip Scripts.
	 *
	 * @param $hook
	 */
	function admin_enqueue_pointer_script_style( $hook ) {

		global $post;
		global $current_screen;

		// Assume pointer shouldn't be shown
		$enqueue_pointer_script_style = false;

		//For testing ONLY!:
		//delete_user_meta( get_current_user_id(), 'dismissed_wp_pointers' );

		// Get array list of dismissed pointers for current user and convert it to array
		$dismissed_pointers = explode( ',', get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		// Check if our pointer is not among dismissed ones. And we're on Google Maps settings screen.
		if ( ! in_array( 'gmb_welcome_pointer', $dismissed_pointers ) && $current_screen->post_type === 'google_maps' ) {

//			$enqueue_pointer_script_style = true;

			// Add footer scripts using callback function
//			add_action( 'admin_print_footer_scripts', array( $this, 'welcome_pointer_print_scripts' ) );
		}

		// Map Customizer Tooltip - Check if our pointer is not among dismissed ones.
		if ( ! in_array( 'gmb_customizer_pointer', $dismissed_pointers ) && isset( $post->post_type ) && $post->post_type === 'google_maps' ) {

			$enqueue_pointer_script_style = true;

			// Add footer scripts using callback function.
			add_action( 'admin_print_footer_scripts', array( $this, 'maps_customizer_tooltip' ) );
		}

		// Enqueue pointer CSS and JS files, if needed.
		if ( $enqueue_pointer_script_style ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
		}

	}

	/**
	 * Welcome Activation Message
	 */
	function welcome_pointer_print_scripts() {

		$current_user = wp_get_current_user();

		//Pointer Content
		$pointer_content = '<h3>' . __( 'Welcome to', 'google-maps-builder' ) . ' ' . $this->plugin_name() . '</h3>';
		$pointer_content .= '<p>' . __( sprintf( 'Thank you for activating %s for WordPress.  Sign up for the latest plugin updates, enhancements, and news.', $this->plugin_name() ), 'google-maps-builder' ) . '</p>';

		//MailChimp Form
		$pointer_content .= '<div id="mc_embed_signup" style="padding: 0 15px;"><form action="//wordimpress.us3.list-manage.com/subscribe/post?u=3ccb75d68bda4381e2f45794c&amp;id=cf1af2563c" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>';
		$pointer_content .= '<div class="mc-field-group" style="margin:0 0 5px">';
		$pointer_content .= '<label for="mce-EMAIL" style="float: left;width: 90px;margin: 5px 0 0;">Email Address</label>';
		$pointer_content .= '<input type="email" value="' . $current_user->user_email . '" name="EMAIL" class="required email" id="mce-EMAIL">';
		$pointer_content .= '</div>';
		$pointer_content .= '<div class="mc-field-group" style="margin: 0 0 10px;">';
		$pointer_content .= '<label for="mce-FNAME" style="float: left;width: 90px;margin: 5px 0 0;">First Name </label>';
		$pointer_content .= '<input type="text" value="' . $current_user->first_name . '" name="FNAME" class="" id="mce-FNAME">';
		$pointer_content .= '</div>';
		$pointer_content .= '<input type="radio" value="64" name="group[13857]" id="mce-group[13857]-13857-6" checked="checked" checked style="display:none;">';
		$pointer_content .= '<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button">';
		$pointer_content .= '</form></div>'; ?>

		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function ($) {
				$('#menu-posts-google_maps').pointer({
					content: '<?php echo $pointer_content; ?>',
					position: {
						edge: 'left', // arrow direction
						align: 'center' // vertical alignment
					},
					pointerWidth: 350,
					close: function () {
						$.post(ajaxurl, {
							pointer: 'gmb_welcome_pointer', // pointer ID
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			});
			//]]>
		</script>

		<?php
	}

	/**
	 * Get name of plugin
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	protected function plugin_name() {
		return __( 'Maps Builder', 'gooogle-maps-builder' );
	}

	/**
	 * Maps Builder Customizer Tooltio
	 */
	function maps_customizer_tooltip() {

		$pointer_content = '<h3>' . __( 'Introducing the Map Builder', 'google-maps-builder' ) . '</h3>';
		$pointer_content .= '<p>' . sprintf( __( 'Building maps has never been easier. With the Map Builder all maps controls are within your reach and the map always stays in view. Try it out! If you like it, you can enable the view by default within the <a href="%s">plugin settings</a>. Enjoy!', 'google-maps-builder' ), admin_url( 'edit.php?post_type=google_maps&page=gmb_settings&tab=general_settings' ) ) . '</p>';
		?>

		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function ($) {
				$('#map-builder').pointer({
					content: '<?php echo $pointer_content; ?>',
					position: {
						edge: 'right', // arrow direction
						align: 'center' // vertical alignment
					},
					pointerWidth: 350,
					close: function () {
						$.post(ajaxurl, {
							pointer: 'gmb_customizer_pointer', // pointer ID
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			});
			//]]>
		</script>

		<?php
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Flush Rewrite Rules
	 *
	 * @description Ensures single post type views don't 404 and more
	 * @since       2.0
	 */
	public function activation_flush_rewrites() {

		// call CPT registration function here (it should also be hooked into 'init')
		$this->setup_post_type();
		flush_rewrite_rules( false );

	}

	/**
	 * Register Post Type
	 * @description Registers and sets up the Maps Builder custom post type
	 *
	 * @since       1.0
	 * @return void
	 */
	public function setup_post_type() {

		$settings      = get_option( 'gmb_settings' );
		$post_slug     = isset( $settings['gmb_custom_slug'] ) ? sanitize_title( $settings['gmb_custom_slug'] ) : 'google-maps';
		$menu_position = isset( $settings['gmb_menu_position'] ) ? $settings['gmb_menu_position'] : '';
		$has_archive   = isset( $settings['gmb_has_archive'] ) ? filter_var( $settings['gmb_has_archive'], FILTER_VALIDATE_BOOLEAN ) : '';

		$labels = array(
			'name'               => _x( 'Google Maps', 'post type general name', 'google-maps-builder' ),
			'singular_name'      => _x( 'Map', 'post type singular name', 'google-maps-builder' ),
			'menu_name'          => _x( 'Google Maps', 'admin menu', 'google-maps-builder' ),
			'name_admin_bar'     => _x( 'Google Maps', 'add new on admin bar', 'google-maps-builder' ),
			'add_new'            => _x( 'Add New', 'map', 'google-maps-builder' ),
			'add_new_item'       => __( 'Add New Map', 'google-maps-builder' ),
			'new_item'           => __( 'New Map', 'google-maps-builder' ),
			'edit_item'          => __( 'Edit Map', 'google-maps-builder' ),
			'view_item'          => __( 'View Map', 'google-maps-builder' ),
			'all_items'          => __( 'All Maps', 'google-maps-builder' ),
			'search_items'       => __( 'Search Maps', 'google-maps-builder' ),
			'parent_item_colon'  => __( 'Parent Maps:', 'google-maps-builder' ),
			'not_found'          => __( 'No Maps found.', 'google-maps-builder' ),
			'not_found_in_trash' => __( 'No Maps found in Trash.', 'google-maps-builder' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug' => $post_slug,
			),
			'capability_type'    => 'post',
			'has_archive'        => isset( $has_archive ) ? $has_archive : true,
			'hierarchical'       => false,
			'menu_position'      => ! empty( $menu_position ) ? intval( $menu_position ) : '23.1',
			'supports'           => array( 'title', 'thumbnail' ),
		);

		register_post_type( 'google_maps', $args );

	}

}