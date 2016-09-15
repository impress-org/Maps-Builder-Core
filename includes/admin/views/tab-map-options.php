<?php
/**
 * Represents the tab view for Google Places widget.
 * *
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

			<h3>
				<?php _e('Default Map Settings', 'google-maps-builder'); ?>
			</h3>
			<p>
				<?php _e('The following settings change the default map options that display when created a new map.', 'google-maps-builder'); ?>
			</p>
			<?php cmb2_metabox_form( $map_option_fields, $key ); ?>
		</div>

		<div class="col-md-2">

		</div>
	</div>
</div>
