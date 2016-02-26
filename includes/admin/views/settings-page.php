<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Google_Maps_Builder
 * @author    Devin Walker <devin@wordimpress.com>
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2015 WordImpress, Devin Walker
 */

?>

<div class="wrap">

	<?php global $current_user;
	$user_id = $current_user->ID;
	// Check that the user hasn't already clicked to ignore the welcome message and that they have appropriate permissions
	if ( ! get_user_meta( $user_id, Google_Maps_Builder()->get_hide_welcome_key() ) && current_user_can( 'install_plugins' ) ) {
		?>
		<div class="container welcome-header">
			<div class="row">

				<div class="col-md-9">
					<h1 class="main-heading">
						<?php _e( 'Welcome to Maps Builder <em>Pro</em> ', $plugin_slug ); ?><?php echo Google_Maps_Builder()->meta['Version']; ?>
					</h1>

					<p class="main-subheading">
						<?php _e( 'Thank you for upgrading to the Maps Builder Pro', $plugin_slug ); ?>. <?php echo sprintf( __( 'As a Pro active license holder you receive %3$spriority support%2$s, awesome plugin features, and thoroughly written plugin %1$sdocumentation%2$s. We hope you enjoy using the Pro plugin version!', $plugin_slug ), '<a href="https://wordimpress.com/documentation/maps-builder-pro/" target="_blank">', '</a>', '<a href="https://wordimpress.com/support/forum/maps-builder-pro" target="_blank">' ); ?>
					</p>
					<?php gmb_include_view( 'admin/views/social-media.php', false, $data ); ?>

				</div>

				<div class="col-md-3">
					<div class="logo-svg">
						<?php gmb_include_view( 'admin/views/mascot-svg.php', false, $data ); ?>
					</div>
				</div>
			</div>
		</div>

	<?php } ?>

	<div class="logo-svg logo-svg-small pull-right" <?php echo( ! get_user_meta( $user_id, 'gmb_hide_pro_welcome' ) ?
		'style="display:none;"' : '' ); ?>>
		<div class="gmb-plugin-heading">
			Google Maps Builder
		</div>
		<?php
			gmb_include_view( 'admin/views/logo-svg.php' );
			add_action( 'gmb_settings_page_after_logo' );
		?>
	</div>


	<?php
	/**
	 * Option tabs
	 *
	 * Better organize our options in tabs
	 *
	 * @see: http://code.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page--wp-24971
	 */
	$active_tab = isset( $_GET['tab'] ) ? strip_tags( $_GET[ 'tab' ] ) : 'map_options';
	?>
	<h2 class="nav-tab-wrapper">
		<?php do_action( 'gmb_settings_tabs', $active_tab ); ?>
	</h2>


	<?php
	/**
	 * Get the appropriate tab
	 */
	switch ( $active_tab ) {
		case 'map_options':
			$view = 'tab-map-options.php';
			break;
		case 'general_settings':
			$view = 'tab-general-settings.php';
			break;
		case 'license':
			$view = 'tab-license.php';
			break;
		case 'system_info':
			$view = 'tab-system-info.php';
			break;
		default :
			$view = 'tab-map-options.php';
			break;
	}
	gmb_include_view( 'admin/views/' . $view, false, $data  );
	?>


</div>
