<?php
/**
 * Goole Maps Builder  Widget
 *
 * @package     GMB
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


//IMPORTANT -- Resist the urge to change name of class to match pattern!
//Doing so would be more _doing_it_right() but would cause WP to think this was a different widget.
class Google_Maps_Builder_Widget extends WP_Widget {

	/**
	 * Array of Private Options
	 *
	 * @since    2.0
	 *
	 * @var array
	 */
	public $widget_defaults = array(
		'title' => '',
		'id'    => '',
	);


	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {

		parent::__construct(
			'gmb_maps_widget', // Base ID
			__( 'Maps Builder Widget', 'google-maps-builder' ), // Name
			array(
				'classname'   => 'gmb-maps-widget',
				'description' => __( 'Display a Google Map in your theme\'s widget powered sidebar.', 'google-maps-builder' )
			) //Args
		);

		//Actions
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_widget_scripts' ) );


	}

	//Load Widget JS Script ONLY on Widget page
	public function admin_widget_scripts( $hook ) {

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		//Widget Script
		if ( $hook == 'widgets.php' ) {

			wp_register_style( 'google-maps-builder-admin-styles', GMB_CORE_URL . 'assets/css/gmb-admin' . $suffix . '.css', array(), GMB_VERSION );
			wp_enqueue_style( 'google-maps-builder-admin-styles' );

			wp_register_script( 'gmb-qtip', GMB_CORE_URL . 'assets/js/plugins/jquery.qtip' . $suffix . '.js', array( 'jquery' ), GMB_VERSION );
			wp_enqueue_script( 'gmb-qtip' );

			wp_register_script( 'gmb-admin-widgets-scripts', GMB_CORE_URL . 'assets/js/admin/admin-widget' . $suffix . '.js', array( 'jquery' ), GMB_VERSION, false );
			wp_enqueue_script( 'gmb-admin-widgets-scripts' );
		}


	}


	/**
	 * Back-end widget form.
	 *
	 * @param array $instance
	 *
	 * @return null
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {

		$instance = wp_parse_args( (array) $instance, $this->widget_defaults ); ?>

		<!-- Title -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Widget Title', 'gpr' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $instance['title']; ?>" />
		</p>


		<?php
		//Query Give Forms
		$args      = array(
			'post_type'      => 'google_maps',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		);
		$gmb_forms = get_posts( $args );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'id' ) ); ?>"><?php _e( 'Select a Map:', 'google-maps-builder' ); ?>
				<span class="dashicons gmb-tooltip-icon" data-tooltip="<?php _e( 'Select a map that you would like to embed in this widget area.', 'google-maps-builder' ); ?>"></span>
			</label>
			<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'id' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'id' ) ); ?>">
				<option value="current"><?php _e( 'Please select...', 'google-maps-builder' ); ?></option>
				<?php foreach ( $gmb_forms as $gmb_form ) { ?>
					<option <?php selected( absint( $instance['id'] ), $gmb_form->ID ); ?> value="<?php echo esc_attr( $gmb_form->ID ); ?>"><?php echo $gmb_form->post_title; ?></option>
				<?php } ?>
			</select>
		</p>
		<!-- Give Form Field -->

		<?php
		/**
		 * Runs at end of the widget admin form
		 *
		 * @since 2.1.0
		 */
		do_action( 'gmb_after_widget_form' );
	} //end form function


	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];

		do_action( 'gmb_before_forms_widget' );

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$atts = array(
			'id' => $instance['id'],
		);

		//Ensure a map has been set
		if ( $instance['id'] !== 'current' ) {
			echo Google_Maps_Builder()->engine->google_maps_shortcode( $atts );
		}

		echo $args['after_widget'];

		/**
		 * Runs at end of the widget front-end display
		 *
		 * @since 2.1.0
		 */
		do_action( 'gmb_after_forms_widget' );

	}


	/**
	 * Updates the widget options via foreach loop
	 *
	 * @DESC: Saves the widget options
	 * @SEE WP_Widget::update
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		//loop through options array and save to new instance
		foreach ( $this->widget_defaults as $field => $value ) {
			$instance[ $field ] = strip_tags( stripslashes( $new_instance[ $field ] ) );
		}

		return $instance;

	}


}
