<?php
/**
 * General Settings: Represents the tab view for Google Maps Builder.
 *
 * @package   Google_Maps_Builder
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress
 */

?>

<div class="container">
	<div class="row">
		<div class="col-md-10">

			<h3><?php _e( 'General Options', 'google-maps-builder' ); ?></h3>

			<p><?php _e( 'Customize how Google Maps Builder functions within WordPress.', 'google-maps-builder' ); ?></p>

			<?php cmb2_metabox_form( $general_option_fields, $key ); ?>
		</div>
		<div class="col-md-2"></div>
	</div>
</div>
