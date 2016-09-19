<?php
/**
 * Base class for settings and admin class to share common code.
 *
 * @package   gmb
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 WordImpress
 */

/**
 * Class Google_Maps_Builder_Core_Interface
 */
abstract class Google_Maps_Builder_Core_Interface {

	/**
	 * Google_Maps_Builder_Core_Interface constructor.
	 */
	public function __construct() {
		//Silencio.
	}

	/**
	 * Prepare data to be included in a view loaded with gmb_include_view()
	 *
	 * @param array $data Optional. Data to include. If empty, the default, plugin slug is used.
	 * @param bool  $merge Optional. If true and $data isn't empty, $data will be merged with defaults.
	 *
	 * @return array
	 */
	protected function view_data( $data = array(), $merge = false ) {
		$_data = array( 'plugin_slug' => 'google-maps-builder' );
		if ( empty( $data ) ) {
			$data = $_data;
		} elseif ( ! empty ( $data ) && false != $merge && is_array( $data ) ) {
			$data = array_merge( $data, $_data );
		}

		return $data;
	}

}
