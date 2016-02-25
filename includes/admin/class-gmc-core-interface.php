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
abstract class Google_Maps_Builder_Core_Interface {

	/**
	 * Slug for plugin
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->plugin_slug = Google_Maps_Builder()->get_plugin_slug();
	}

	/**
	 * Prepare data to be included in a view loaded with gmb_include_view()
	 *
	 * @since 0.1.0
	 *
	 * @param array $data Optional. Data to include. If empty, the default, plugin slug is used.
	 * @param bool $merge Optional. If true and $data isn't empty, $data will be merged with defaults.
	 * @return array
	 */
	protected function view_data( $data = array(), $merge = false ) {
		$_data = array( 'plugin_slug' => $this->plugin_slug );
		if( empty( $data ) ) {
			$data = $_data;
		}elseif( ! empty ( $data ) && false != $merge && is_array( $data ) ){
			$data = array_merge( $data, $_data );
		}

		return $data;
	}

}
