<?php
/**
 * Misc Functions
 *
 * @package     Google_Maps_Builder
 * @subpackage  Functions
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Checks whether function is disabled.
 *
 * @since 2.0
 *
 * @param string $function Name of the function.
 *
 * @return bool Whether or not function is disabled.
 */
function gmb_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

/**
 * Wrapper function for wp_die(). This function adds filters for wp_die() which
 * kills execution of the script using wp_die(). This allows us to then to work
 * with functions using gmb_die() in the unit tests.
 *
 * @since  2.0
 * @return void
 */
function gmb_die( $message = '', $title = '', $status = 400 ) {
	add_filter( 'wp_die_ajax_handler', '_gmb_die_handler', 10, 3 );
	add_filter( 'wp_die_handler', '_gmb_die_handler', 10, 3 );
	wp_die( $message, $title, array( 'response' => $status ) );
}


/**
 * Check if AJAX works as expected
 *
 * @since 2.0
 * @return bool True if AJAX works, false otherwise
 */
function gmb_test_ajax_works() {

	// Check if the Airplane Mode plugin is installed
	if ( class_exists( 'Airplane_Mode_Core' ) ) {

		$airplane = Airplane_Mode_Core::getInstance();

		if ( method_exists( $airplane, 'enabled' ) ) {

			if ( $airplane->enabled() ) {
				return true;
			}

		} else {

			if ( $airplane->check_status() == 'on' ) {
				return true;
			}
		}
	}

	add_filter( 'block_local_requests', '__return_false' );

	if ( get_transient( '_gmb_ajax_works' ) ) {
		return true;
	}

	$params = array(
		'sslverify' => false,
		'timeout'   => 30,
		'body'      => array(
			'action' => 'gmb_test_ajax'
		)
	);

	$ajax  = wp_remote_post( gmb_get_ajax_url(), $params );
	$works = true;

	if ( is_wp_error( $ajax ) ) {

		$works = false;

	} else {

		if ( empty( $ajax['response'] ) ) {
			$works = false;
		}

		if ( empty( $ajax['response']['code'] ) || 200 !== (int) $ajax['response']['code'] ) {
			$works = false;
		}

		if ( empty( $ajax['response']['message'] ) || 'OK' !== $ajax['response']['message'] ) {
			$works = false;
		}

		if ( ! isset( $ajax['body'] ) || 0 !== (int) $ajax['body'] ) {
			$works = false;
		}

	}

	if ( $works ) {
		set_transient( '_gmb_ajax_works', '1', DAY_IN_SECONDS );
	}

	return $works;
}


/**
 * Get AJAX URL
 *
 * @since 2.0
 * @return string
 */
function gmb_get_ajax_url() {
	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$current_url = gmb_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'gmb_ajax_url', $ajax_url );
}

/**
 * Get the current page URL
 *
 * @since 2.0
 * @return string $page_url Current page URL
 */
function gmb_get_current_page_url() {

	if ( is_front_page() ) :
		$page_url = home_url();
	else :
		$page_url = 'http';

		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
			$page_url .= "s";
		}

		$page_url .= "://";

		if ( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" ) {
			$page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
	endif;

	return apply_filters( 'gmb_get_current_page_url', esc_url( $page_url ) );
}


/**
 * Get user host
 *
 * Returns the webhost this site is using if possible
 *
 * @since 1.0
 * @return mixed string $host if detected, false otherwise
 */
function gmb_get_host() {
	$host = false;

	if ( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} elseif ( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} elseif ( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} elseif ( DB_HOST == 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} elseif ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} elseif ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} elseif ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} elseif ( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} elseif ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} elseif ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} elseif ( strpos( $_SERVER['SERVER_NAME'], 'Flywheel' ) !== false ) {
		$host = 'Flywheel';
	} else {
		// Adding a general fallback for data gathering
		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];
	}

	return $host;
}


/**
 * Map Languages
 *
 * @return array
 */
function gmb_get_map_languages() {

	$lanugages = apply_filters( 'gmb_map_languages', array(
		''      => __( 'User Default', 'google-maps-builder' ),
		'af'    => __( 'Afrikaans', 'google-maps-builder' ),
		'sq'    => __( 'Albanian', 'google-maps-builder' ),
		'eu'    => __( 'Basque', 'google-maps-builder' ),
		'be'    => __( 'Belarusian', 'google-maps-builder' ),
		'bg'    => __( 'Bulgarian', 'google-maps-builder' ),
		'ca'    => __( 'Catalan', 'google-maps-builder' ),
		'zh-cn' => __( 'Chinese (Simplified)', 'google-maps-builder' ),
		'zh-tw' => __( 'Chinese (Traditional)', 'google-maps-builder' ),
		'hr'    => __( 'Croatian', 'google-maps-builder' ),
		'cs'    => __( 'Czech', 'google-maps-builder' ),
		'da'    => __( 'Danish', 'google-maps-builder' ),
		'nl'    => __( 'Dutch', 'google-maps-builder' ),
		'nl-be' => __( 'Dutch (Belgium)', 'google-maps-builder' ),
		'nl-nl' => __( 'Dutch (Netherlands)', 'google-maps-builder' ),
		'en'    => __( 'English', 'google-maps-builder' ),
		'en-au' => __( 'English (Australia)', 'google-maps-builder' ),
		'en-bz' => __( 'English (Belize)', 'google-maps-builder' ),
		'en-ca' => __( 'English (Canada)', 'google-maps-builder' ),
		'en-ie' => __( 'English (Ireland)', 'google-maps-builder' ),
		'en-jm' => __( 'English (Jamaica)', 'google-maps-builder' ),
		'en-nz' => __( 'English (New Zealand)', 'google-maps-builder' ),
		'en-ph' => __( 'English (Phillipines)', 'google-maps-builder' ),
		'en-za' => __( 'English (South Africa)', 'google-maps-builder' ),
		'en-tt' => __( 'English (Trinidad)', 'google-maps-builder' ),
		'en-gb' => __( 'English (United Kingdom)', 'google-maps-builder' ),
		'en-us' => __( 'English (United States)', 'google-maps-builder' ),
		'en-zw' => __( 'English (Zimbabwe)', 'google-maps-builder' ),
		'et'    => __( 'Estonian', 'google-maps-builder' ),
		'fo'    => __( 'Faeroese', 'google-maps-builder' ),
		'fi'    => __( 'Finnish', 'google-maps-builder' ),
		'fr'    => __( 'French', 'google-maps-builder' ),
		'fr-be' => __( 'French (Belgium)', 'google-maps-builder' ),
		'fr-ca' => __( 'French (Canada)', 'google-maps-builder' ),
		'fr-fr' => __( 'French (France)', 'google-maps-builder' ),
		'fr-lu' => __( 'French (Luxembourg)', 'google-maps-builder' ),
		'fr-mc' => __( 'French (Monaco)', 'google-maps-builder' ),
		'fr-ch' => __( 'French (Switzerland)', 'google-maps-builder' ),
		'gl'    => __( 'Galician', 'google-maps-builder' ),
		'gd'    => __( 'Gaelic', 'google-maps-builder' ),
		'de'    => __( 'German', 'google-maps-builder' ),
		'de-at' => __( 'German (Austria)', 'google-maps-builder' ),
		'de-de' => __( 'German (Germany)', 'google-maps-builder' ),
		'de-li' => __( 'German (Liechtenstein)', 'google-maps-builder' ),
		'de-lu' => __( 'German (Luxembourg)', 'google-maps-builder' ),
		'de-ch' => __( 'German (Switzerland)', 'google-maps-builder' ),
		'el'    => __( 'Greek', 'google-maps-builder' ),
		'haw'   => __( 'Hawaiian', 'google-maps-builder' ),
		'hu'    => __( 'Hungarian', 'google-maps-builder' ),
		'is'    => __( 'Icelandic', 'google-maps-builder' ),
		'in'    => __( 'Indonesian', 'google-maps-builder' ),
		'ga'    => __( 'Irish', 'google-maps-builder' ),
		'it'    => __( 'Italian', 'google-maps-builder' ),
		'it-it' => __( 'Italian (Italy)', 'google-maps-builder' ),
		'it-ch' => __( 'Italian (Switzerland)', 'google-maps-builder' ),
		'ja'    => __( 'Japanese', 'google-maps-builder' ),
		'ko'    => __( 'Korean', 'google-maps-builder' ),
		'mk'    => __( 'Macedonian', 'google-maps-builder' ),
		'no'    => __( 'Norwegian', 'google-maps-builder' ),
		'pl'    => __( 'Polish', 'google-maps-builder' ),
		'pt'    => __( 'Portuguese', 'google-maps-builder' ),
		'pt-br' => __( 'Portuguese (Brazil)', 'google-maps-builder' ),
		'pt-pt' => __( 'Portuguese (Portugal)', 'google-maps-builder' ),
		'ro'    => __( 'Romanian', 'google-maps-builder' ),
		'ro-mo' => __( 'Romanian (Moldova)', 'google-maps-builder' ),
		'ro-ro' => __( 'Romanian (Romania)', 'google-maps-builder' ),
		'ru'    => __( 'Russian', 'google-maps-builder' ),
		'ru-mo' => __( 'Russian (Moldova)', 'google-maps-builder' ),
		'ru-ru' => __( 'Russian (Russia)', 'google-maps-builder' ),
		'sr'    => __( 'Serbian', 'google-maps-builder' ),
		'sk'    => __( 'Slovak', 'google-maps-builder' ),
		'sl'    => __( 'Slovenian', 'google-maps-builder' ),
		'es'    => __( 'Spanish', 'google-maps-builder' ),
		'es-ar' => __( 'Spanish (Argentina)', 'google-maps-builder' ),
		'es-bo' => __( 'Spanish (Bolivia)', 'google-maps-builder' ),
		'es-cl' => __( 'Spanish (Chile)', 'google-maps-builder' ),
		'es-co' => __( 'Spanish (Colombia)', 'google-maps-builder' ),
		'es-cr' => __( 'Spanish (Costa Rica)', 'google-maps-builder' ),
		'es-do' => __( 'Spanish (Dominican Republic)', 'google-maps-builder' ),
		'es-ec' => __( 'Spanish (Ecuador)', 'google-maps-builder' ),
		'es-sv' => __( 'Spanish (El Salvador)', 'google-maps-builder' ),
		'es-gt' => __( 'Spanish (Guatemala)', 'google-maps-builder' ),
		'es-hn' => __( 'Spanish (Honduras)', 'google-maps-builder' ),
		'es-mx' => __( 'Spanish (Mexico)', 'google-maps-builder' ),
		'es-ni' => __( 'Spanish (Nicaragua)', 'google-maps-builder' ),
		'es-pa' => __( 'Spanish (Panama)', 'google-maps-builder' ),
		'es-py' => __( 'Spanish (Paraguay)', 'google-maps-builder' ),
		'es-pe' => __( 'Spanish (Peru)', 'google-maps-builder' ),
		'es-pr' => __( 'Spanish (Puerto Rico)', 'google-maps-builder' ),
		'es-es' => __( 'Spanish (Spain)', 'google-maps-builder' ),
		'es-uy' => __( 'Spanish (Uruguay)', 'google-maps-builder' ),
		'es-ve' => __( 'Spanish (Venezuela)', 'google-maps-builder' ),
		'sv'    => __( 'Swedish', 'google-maps-builder' ),
		'sv-fi' => __( 'Swedish (Finland)', 'google-maps-builder' ),
		'sv-se' => __( 'Swedish (Sweden)', 'google-maps-builder' ),
		'tr'    => __( 'Turkish', 'google-maps-builder' ),
		'uk'    => __( 'Ukranian', 'google-maps-builder' ),
	) );

	return $lanugages;

}

/**
 * Include a view
 *
 * NOTE: First this attempts to load from GMB_PLUGIN_PATH . '/includes/' then it trys GMP_CORE_PATH .'/includes', unless $full
 * NOTE: Uses include() not include_once()
 *
 * @since 2.1
 *
 * @return string
 *
 * @param string $file File path relative to either core includes path or plugin includes path. Use full absolute path if $full param is true
 * @param bool $full Optional. If true, $file param should be a full absolute path. Default is false.
 * @param array $data Optional. An array of values to be used in the view via output buffering. Default is an empty array which skips output buffering.
 *
 * @return bool True if file was included, false if not.
 */
function gmb_include_view( $file, $full = false, $data = array() ) {
	$file = gmb_find_view( $file, $full );

	/**
	 * Filter file path for gmb_include_view
	 *
	 * @since 2.1
	 *
	 * @param string $file File path -- should be a full absolute path
	 * @param bool $full If this function is using full file path mode or not
	 */
	$file = apply_filters( 'gmb_gmb_include_view_file', $file, $full );

	if ( file_exists( $file ) ) {
		if ( ! empty( $data ) ) {
			extract( $data, EXTR_SKIP );
			ob_start();
		}
		include( $file );
		if ( ! empty( $data ) ) {
			echo ob_get_clean();
		}

		return true;
	} else {
		return false;
	}
}

/**
 * Find view file
 *
 * NOTE: First this attempts to load from GMB_PLUGIN_PATH . '/includes/' then it trys GMP_CORE_PATH .'/includes', unless $full
 *
 * @since 2.1
 *
 * @param string $file File path relative to either core includes path or plugin includes path. Use full absolute path if $full param is true
 * @param bool $full Optional. If true, $file param should be a full absolute path. Default is false.
 *
 * @return string
 */
function gmb_find_view( $file, $full = false ) {
	if ( ! $full ) {
		$_file = GMB_PLUGIN_PATH . 'includes/' . $file;
		if ( ! file_exists( $_file ) ) {
			$_file = GMB_CORE_PATH . 'includes/' . $file;
		}

		$file = $_file;
	}

	return $file;
}
