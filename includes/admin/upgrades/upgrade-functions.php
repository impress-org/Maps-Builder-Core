<?php
/**
 * Handles Upgrade Functionality.
 *
 * @copyright   Copyright (c) 2016, WordImpress
 * @since       : 2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Upgrade Notices.
 *
 * @since 2.0
 * @return void
 */
function gmb_show_upgrade_notices() {

	// Don't show notices on the upgrades page.
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'gmb-upgrades' ) {
		return;
	}

	//Check to see if we have any posts.
	$gmb_posts = get_posts( array( 'post_type' => 'google_maps', 'posts_per_page' => 10 ) );
	if ( empty( $gmb_posts ) ) {
		update_option( 'gmb_refid_upgraded', 'upgraded' ); //mark as updated.
		return; //Don't run if there's no posts!
	}

	$gmb_version = get_option( 'gmb_version' );

	if ( ! $gmb_version ) {
		// 2.0 is the first version to use this option so we must add it.
		$gmb_version = '2.0';
	}
	update_option( 'gmb_version', GMB_VERSION );

	$gmb_version = preg_replace( '/[^0-9.].*/', '', $gmb_version );

	if ( version_compare( $gmb_version, '2.0', '<=' ) && ! get_option( 'gmb_refid_upgraded' ) ) {
		printf(
			'<div class="updated"><p><strong>' . __( 'Maps Builder Update Required', 'google-maps-builder' ) . ':</strong> ' . esc_html__( 'Google has updated their Maps API to use the new Google Places ID rather than previous Reference ID. The old method will soon be deprecated and eventually go offline. We are being proactive and would like to update your maps to use the new Places ID. Once you upgrade, your maps should work just fine but remember to make a backup prior to upgrading. If you choose not to upgrade Google will eventually take the old reference ID offline (no date has been given). Please contact WordImpress support via our website if you have any further questions or issues. %sClick here to upgrade your maps to use the new Places ID%s', 'google-maps-builder' ) . '</p></div>',
			'<br><a href="' . esc_url( admin_url( 'options.php?page=gmb-upgrades' ) ) . '" class="button button-primary" style="margin-top:10px;">',
			'</a>'
		);
	} elseif ( version_compare( $gmb_version, '2.1', '<=' ) && ! gmb_has_upgrade_completed( 'gmb_markers_upgraded' ) ) {
		printf(
			'<div class="updated"><p><strong>' . __( 'Maps Builder Update Required', 'google-maps-builder' ) . ':</strong> ' . esc_html__( 'An upgrade is required to update your Google maps with the latest plugin version. Please perform a site backup and then upgrade. %sClick here to upgrade your maps%s', 'google-maps-builder' ) . '</p></div>',
			'<br><a href="' . esc_url( admin_url( 'options.php?page=gmb-upgrades' ) ) . '" class="button button-primary" style="margin-top:10px;">',
			'</a>'
		);
	}


}

add_action( 'admin_notices', 'gmb_show_upgrade_notices' );


/**
 * Creates the upgrade page.
 *
 * links to global variables.
 *
 * @since 2.0
 */
function gmb_add_upgrade_submenu_page() {

	$gmb_upgrades_screen = add_submenu_page( null, __( 'Maps Builder Upgrades', 'google-maps-builder' ), __( 'Maps Builder Upgrades', 'google-maps-builder' ), 'activate_plugins', 'gmb-upgrades', 'gmb_upgrades_screen' );

}

add_action( 'admin_menu', 'gmb_add_upgrade_submenu_page', 10 );


/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since  2.1
 *
 * @param  string $upgrade_action The upgrade action to check completion for
 *
 * @return bool                   If the action has been added to the completed actions array
 */
function gmb_has_upgrade_completed( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades = gmb_get_completed_upgrades();

	return in_array( $upgrade_action, $completed_upgrades );

}


/**
 * Adds an upgrade action to the completed upgrades array
 *
 * @since  2.1
 *
 * @param  string $upgrade_action The action to add to the completed upgrades array
 *
 * @return bool                   If the function was successfully added
 */
function gmb_set_upgrade_complete( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades   = gmb_get_completed_upgrades();
	$completed_upgrades[] = $upgrade_action;

	// Remove any blanks, and only show uniques
	$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

	return update_option( 'gmb_completed_upgrades', $completed_upgrades );
}

/**
 * Get's the array of completed upgrade actions
 *
 * @since  2.1
 * @return array The array of completed upgrades
 */
function gmb_get_completed_upgrades() {

	$completed_upgrades = get_option( 'gmb_completed_upgrades' );

	if ( false === $completed_upgrades ) {
		$completed_upgrades = array();
	}

	return $completed_upgrades;

}


/**
 * Triggers all upgrade functions
 *
 * This function is usually triggered via AJAX
 *
 * @since 2.0
 * @return void
 */
function gmb_trigger_upgrades() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( __( 'You do not have permission to do plugin upgrades', 'google-maps-builder' ), __( 'Error', 'google-maps-builder' ), array( 'response' => 403 ) );
	}

	$gmb_version = get_option( 'gmb_version' );

	//Is the option above in the db?
	if ( ! $gmb_version ) {
		// 2.0 is the first version to use this option so we must add it.
		$gmb_version = '2.0';
		add_option( 'gmb_version', $gmb_version );
	}
	//Version 2.0 upgrades
	if ( version_compare( GMB_VERSION, '2.0', '>=' ) && ! get_option( 'gmb_refid_upgraded' ) ) {
		gmb_v2_upgrades();
	}

	//Version 2.1 upgrades
	if ( version_compare( GMB_VERSION, '2.1', '>=' ) ) {
		if ( ! gmb_has_upgrade_completed( 'gmb_markers_upgraded' ) ) {
			gmb_v21_marker_upgrades();
		}

		if ( ! gmb_has_upgrade_completed( 'gmb_api_keys_upgraded' ) ) {
			gmb_v21_api_key_upgrades();
		}
	}

	update_option( 'gmb_version', $gmb_version );

	if ( DOING_AJAX ) {
		die( 'complete' );
	} // Let AJAX know that the upgrade is complete
}

add_action( 'wp_ajax_gmb_trigger_upgrades', 'gmb_trigger_upgrades' );


/**
 * Render Upgrades Screen
 *
 * @since 2.0
 * @return void
 */
function gmb_upgrades_screen() {

	$action = isset( $_GET['gmb-upgrade'] ) ? sanitize_text_field( $_GET['gmb-upgrade'] ) : '';
	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	$custom = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 0;
	$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 100;
	$steps  = round( ( $total / $number ), 0 );


	$doing_upgrade_args = array(
		'page'        => 'gmb-upgrades',
		'gmb-upgrade' => $action,
		'step'        => $step,
		'total'       => $total,
		'custom'      => $custom,
		'steps'       => $steps
	);
	update_option( 'gmb_doing_upgrade', $doing_upgrade_args );
	if ( $step > $steps ) {
		// Prevent a weird case where the estimate was off. Usually only a couple.
		$steps = $step;
	} ?>
	<div class="wrap">
		<h2><?php _e( 'Maps Builder - Upgrade', 'google-maps-builder' ); ?></h2>

		<?php if ( ! empty( $action ) ) : ?>

			<div id="gmb-upgrade-status">
				<p><?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'google-maps-builder' ); ?></p>

				<?php if ( ! empty( $total ) ) : ?>
					<p>
						<strong><?php printf( __( 'Step %d of approximately %d running', 'google-maps-builder' ), $step, $steps ); ?></strong>
					</p>
				<?php endif; ?>
			</div>
			<script type="text/javascript">
				setTimeout(function () {
					document.location.href = "index.php?gmb_action=<?php echo $action; ?>&step=<?php echo $step; ?>&total=<?php echo $total; ?>&custom=<?php echo $custom; ?>";
				}, 250);
			</script>

		<?php else : ?>

			<div id="gmb-upgrade-status" class="updated" style="margin-top:15px;">
				<p style="margin-bottom:8px;">
					<?php _e( 'The upgrade process has started, please do not close your browser or refresh. This could take several minutes. You will be automatically redirected when the upgrade has finished.', 'google-maps-builder' ); ?>
					<img src="<?php echo GMB_PLUGIN_URL . '/assets/img/loading.gif'; ?>" id="gmb-upgrade-loader" style="position:relative; top:3px;"/>
				</p>
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					// Trigger upgrades on page load
					var data = {action: 'gmb_trigger_upgrades'};
					var el_upgrade_status = jQuery('#gmb-upgrade-status');

					//Trigger via AJAX
					jQuery.post(ajaxurl, data, function (response) {

						//Uncomment for debugging
//						jQuery( '#gmb-upgrade-status' ).after( response );

						//Success Message
						if (response == 'complete') {

							el_upgrade_status.hide();
							el_upgrade_status.after('<div class="updated"><p><strong><?php _e( 'Upgrade Successful:', 'google-maps-builder' ); ?></strong> <?php _e( 'The upgrade process has completed successfully. You will now be redirected to your admin dashboard.', 'google-maps-builder' ); ?></p></div>');

							//Send user back to prev page
							setTimeout(function () {
								window.location = '<?php echo admin_url(); ?>';
							}, 4000);

						}
					});
				});
			</script>

		<?php endif; ?>

	</div>
	<?php
}