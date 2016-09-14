<?php
/**
 * Represents the view for the public-facing component
 *
 * @package   Google_Maps_Builder
 * @author    WordImpress
 * @license   GPL-2.0+
 * @link      http://wordimpress.com
 * @copyright 2016 WordImpress, WordImpress
 */

global $post;
$map_width = isset( $visual_info['width'] ) ? $visual_info['width'] : '100';
$map_width .= isset( $visual_info['map_width_unit'] ) ? $visual_info['map_width_unit'] : '%';
$map_height = isset( $visual_info['height'] ) ? $visual_info['height'] : '500';
$map_height .= isset( $visual_info['map_height_unit'] ) ? $visual_info['map_height_unit'] : 'px';
if ( ! isset( $text_directions ) ) {
	$text_directions = '';
}
?>

<div class="google-maps-builder-wrap">

	<div id="google-maps-builder-<?php echo $atts['id']; ?>" class="google-maps-builder" <?php echo ! empty( $atts['id'] ) ? ' data-map-id="' . $atts['id'] . '"' : '">Error: NO MAP ID'; ?> style="width:<?php echo $map_width; ?>; height:<?php echo $map_height; ?>"></div>

	<?php do_action( 'gmb_public_view_bottom', $atts, $text_directions, $post ); ?>

</div>
