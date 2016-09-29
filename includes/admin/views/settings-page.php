<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide.
 * The User Interface to the end user.
 *
 * @package   Google_Maps_Builder
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress, Devin Walker
 */
?>

<div class="wrap">

	<?php
	global $current_user;
	$user_id      = $current_user->ID;
	$hide_welcome = get_user_meta( $user_id, 'gmb_hide_pro_welcome' );

	// Check that the user hasn't already clicked to ignore the welcome message
	// and that they have appropriate permissions.
	if ( empty( $hide_welcome ) && current_user_can( 'install_plugins' ) ) {
		?>
		<div class="container welcome-header gmb-clearfix">
			<div class="row">

				<div class="col-md-9">
					<h1 class="main-heading">
						<?php echo $welcome; ?>
					</h1>

					<p class="main-subheading">
						<?php echo $sub_heading; ?>
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

	<?php }
	//Show Top Right Logo? Only if
	$hide_side_logo = '';
	if ( empty( $hide_welcome ) ) {
		$hide_side_logo = 'style="display:none;"';
	}
	?>

	<div class="logo-svg logo-svg-small pull-right" <?php echo $hide_side_logo; ?>>
		<div class="gmb-plugin-heading">Google Maps Builder</div>
		<?php
		gmb_include_view( 'admin/views/logo-svg.php' );
		do_action( 'gmb_settings_page_after_logo' );
		?>
	</div>


	<?php
	/**
	 * Option tabs.
	 *
	 * Better organize our options in tabs.
	 *
	 * @see: http://code.tutsplus.com/tutorials/the-complete-guide-to-the-wordpress-settings-api-part-5-tabbed-navigation-for-your-settings-page--wp-24971
	 */
	$active_tab = isset( $_GET['tab'] ) ? strip_tags( $_GET['tab'] ) : 'map_options';
	?>
	<h1 class="nav-tab-wrapper"><?php do_action( 'gmb_settings_tabs', $active_tab ); ?></h1>

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

	gmb_include_view( 'admin/views/' . $view, false, $data ); ?>

</div>
