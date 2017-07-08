<?php
/**
 * HTML elements
 *
 * A helper class for outputting common HTML elements, such as map drop downs
 *
 * @package     Google_Maps_Builder
 * @subpackage  Classes/HTML
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google_Maps_Builder_HTML_Elements Class
 *
 * @since 2.0
 */
class Google_Maps_Builder_Core_HTML_Elements {
	/**
	 * Renders an HTML Dropdown of all the Map posts.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param array $args Arguments for the dropdown
	 *
	 * @return string $output Map posts dropdown
	 */
	public function maps_dropdown( $args = array() ) {

		$defaults = array(
			'name'        => 'gmb-maps',
			'id'          => 'gmb-maps',
			'class'       => '',
			'multiple'    => false,
			'selected'    => 0,
			'chosen'      => false,
			'number'      => -1,
			'placeholder' => __( 'Select a Map', 'google-maps-builder' )
		);

		$args = wp_parse_args( $args, $defaults );

		$maps = get_posts( array(
			'post_type'      => 'google_maps',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => $args['number']
		) );

		$options = array();

		if ( $maps ) {
			$options[0] = __( 'Select a Map', 'google-maps-builder' );
			foreach ( $maps as $map ) {
				$options[ absint( $map->ID ) ] = esc_html( $map->post_title );
			}
		} else {
			$options[0] = __( 'No Maps Found', 'google-maps-builder' );
		}

		// This ensures that any selected maps are included in the drop down
		if ( is_array( $args['selected'] ) ) {
			foreach ( $args['selected'] as $item ) {
				if ( ! in_array( $item, $options ) ) {
					$options[ $item ] = get_the_title( $item );
				}
			}
		} elseif ( is_numeric( $args['selected'] ) && $args['selected'] !== 0 ) {
			if ( ! in_array( $args['selected'], $options ) ) {
				$options[ $args['selected'] ] = get_the_title( $args['selected'] );
			}
		}

		$output = self::select( array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'],
			'options'          => $options,
			'chosen'           => $args['chosen'],
			'multiple'         => $args['multiple'],
			'placeholder'      => $args['placeholder'],
			'show_option_all'  => false,
			'show_option_none' => false
		) );

		return $output;
	}


	/**
	 * Renders an HTML Dropdown
	 *
	 * @since 2.0
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function select( $args = array() ) {

		$defaults = array(
			'options'          => array(),
			'name'             => null,
			'class'            => '',
			'id'               => '',
			'selected'         => 0,
			'chosen'           => false,
			'placeholder'      => null,
			'multiple'         => false,
			'show_option_all'  => _x( 'All', 'all dropdown items', 'google-maps-builder' ),
			'show_option_none' => _x( 'None', 'no dropdown items', 'google-maps-builder' )
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['multiple'] ) {
			$multiple = ' MULTIPLE';
		} else {
			$multiple = '';
		}

		if ( $args['chosen'] ) {
			$args['class'] .= 'gmb-select-chosen';
		}

		if ( $args['placeholder'] ) {
			$placeholder = $args['placeholder'];
		} else {
			$placeholder = '';
		}

		$output = '<select name="' . esc_attr( $args['name'] ) . '" id="' . esc_attr( sanitize_key( str_replace( '-', '_', $args['id'] ) ) ) . '" class="gmb-select ' . esc_attr( $args['class'] ) . '"' . $multiple . ' data-placeholder="' . $placeholder . '">';

		if ( $args['show_option_all'] ) {
			if ( $args['multiple'] ) {
				$selected = selected( true, in_array( 0, $args['selected'] ), false );
			} else {
				$selected = selected( $args['selected'], 0, false );
			}
			$output .= '<option value="all"' . $selected . '>' . esc_html( $args['show_option_all'] ) . '</option>';
		}

		if ( ! empty( $args['options'] ) ) {

			if ( $args['show_option_none'] ) {
				if ( $args['multiple'] ) {
					$selected = selected( true, in_array( - 1, $args['selected'] ), false );
				} else {
					$selected = selected( $args['selected'], - 1, false );
				}
				$output .= '<option value="-1"' . $selected . '>' . esc_html( $args['show_option_none'] ) . '</option>';
			}

			foreach ( $args['options'] as $key => $option ) {

				if ( $args['multiple'] && is_array( $args['selected'] ) ) {
					$selected = selected( true, in_array( $key, $args['selected'] ), false );
				} else {
					$selected = selected( $args['selected'], $key, false );
				}

				$output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option ) . '</option>';
			}
		}

		$output .= '</select>';

		return $output;
	}


}
