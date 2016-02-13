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
		''      => __( 'User Default', Google_Maps_Builder()->get_plugin_slug() ),
		'af'    => __( 'Afrikaans', Google_Maps_Builder()->get_plugin_slug() ),
		'sq'    => __( 'Albanian', Google_Maps_Builder()->get_plugin_slug() ),
		'eu'    => __( 'Basque', Google_Maps_Builder()->get_plugin_slug() ),
		'be'    => __( 'Belarusian', Google_Maps_Builder()->get_plugin_slug() ),
		'bg'    => __( 'Bulgarian', Google_Maps_Builder()->get_plugin_slug() ),
		'ca'    => __( 'Catalan', Google_Maps_Builder()->get_plugin_slug() ),
		'zh-cn' => __( 'Chinese (Simplified)', Google_Maps_Builder()->get_plugin_slug() ),
		'zh-tw' => __( 'Chinese (Traditional)', Google_Maps_Builder()->get_plugin_slug() ),
		'hr'    => __( 'Croatian', Google_Maps_Builder()->get_plugin_slug() ),
		'cs'    => __( 'Czech', Google_Maps_Builder()->get_plugin_slug() ),
		'da'    => __( 'Danish', Google_Maps_Builder()->get_plugin_slug() ),
		'nl'    => __( 'Dutch', Google_Maps_Builder()->get_plugin_slug() ),
		'nl-be' => __( 'Dutch (Belgium)', Google_Maps_Builder()->get_plugin_slug() ),
		'nl-nl' => __( 'Dutch (Netherlands)', Google_Maps_Builder()->get_plugin_slug() ),
		'en'    => __( 'English', Google_Maps_Builder()->get_plugin_slug() ),
		'en-au' => __( 'English (Australia)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-bz' => __( 'English (Belize)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-ca' => __( 'English (Canada)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-ie' => __( 'English (Ireland)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-jm' => __( 'English (Jamaica)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-nz' => __( 'English (New Zealand)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-ph' => __( 'English (Phillipines)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-za' => __( 'English (South Africa)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-tt' => __( 'English (Trinidad)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-gb' => __( 'English (United Kingdom)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-us' => __( 'English (United States)', Google_Maps_Builder()->get_plugin_slug() ),
		'en-zw' => __( 'English (Zimbabwe)', Google_Maps_Builder()->get_plugin_slug() ),
		'et'    => __( 'Estonian', Google_Maps_Builder()->get_plugin_slug() ),
		'fo'    => __( 'Faeroese', Google_Maps_Builder()->get_plugin_slug() ),
		'fi'    => __( 'Finnish', Google_Maps_Builder()->get_plugin_slug() ),
		'fr'    => __( 'French', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-be' => __( 'French (Belgium)', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-ca' => __( 'French (Canada)', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-fr' => __( 'French (France)', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-lu' => __( 'French (Luxembourg)', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-mc' => __( 'French (Monaco)', Google_Maps_Builder()->get_plugin_slug() ),
		'fr-ch' => __( 'French (Switzerland)', Google_Maps_Builder()->get_plugin_slug() ),
		'gl'    => __( 'Galician', Google_Maps_Builder()->get_plugin_slug() ),
		'gd'    => __( 'Gaelic', Google_Maps_Builder()->get_plugin_slug() ),
		'de'    => __( 'German', Google_Maps_Builder()->get_plugin_slug() ),
		'de-at' => __( 'German (Austria)', Google_Maps_Builder()->get_plugin_slug() ),
		'de-de' => __( 'German (Germany)', Google_Maps_Builder()->get_plugin_slug() ),
		'de-li' => __( 'German (Liechtenstein)', Google_Maps_Builder()->get_plugin_slug() ),
		'de-lu' => __( 'German (Luxembourg)', Google_Maps_Builder()->get_plugin_slug() ),
		'de-ch' => __( 'German (Switzerland)', Google_Maps_Builder()->get_plugin_slug() ),
		'el'    => __( 'Greek', Google_Maps_Builder()->get_plugin_slug() ),
		'haw'   => __( 'Hawaiian', Google_Maps_Builder()->get_plugin_slug() ),
		'hu'    => __( 'Hungarian', Google_Maps_Builder()->get_plugin_slug() ),
		'is'    => __( 'Icelandic', Google_Maps_Builder()->get_plugin_slug() ),
		'in'    => __( 'Indonesian', Google_Maps_Builder()->get_plugin_slug() ),
		'ga'    => __( 'Irish', Google_Maps_Builder()->get_plugin_slug() ),
		'it'    => __( 'Italian', Google_Maps_Builder()->get_plugin_slug() ),
		'it-it' => __( 'Italian (Italy)', Google_Maps_Builder()->get_plugin_slug() ),
		'it-ch' => __( 'Italian (Switzerland)', Google_Maps_Builder()->get_plugin_slug() ),
		'ja'    => __( 'Japanese', Google_Maps_Builder()->get_plugin_slug() ),
		'ko'    => __( 'Korean', Google_Maps_Builder()->get_plugin_slug() ),
		'mk'    => __( 'Macedonian', Google_Maps_Builder()->get_plugin_slug() ),
		'no'    => __( 'Norwegian', Google_Maps_Builder()->get_plugin_slug() ),
		'pl'    => __( 'Polish', Google_Maps_Builder()->get_plugin_slug() ),
		'pt'    => __( 'Portuguese', Google_Maps_Builder()->get_plugin_slug() ),
		'pt-br' => __( 'Portuguese (Brazil)', Google_Maps_Builder()->get_plugin_slug() ),
		'pt-pt' => __( 'Portuguese (Portugal)', Google_Maps_Builder()->get_plugin_slug() ),
		'ro'    => __( 'Romanian', Google_Maps_Builder()->get_plugin_slug() ),
		'ro-mo' => __( 'Romanian (Moldova)', Google_Maps_Builder()->get_plugin_slug() ),
		'ro-ro' => __( 'Romanian (Romania)', Google_Maps_Builder()->get_plugin_slug() ),
		'ru'    => __( 'Russian', Google_Maps_Builder()->get_plugin_slug() ),
		'ru-mo' => __( 'Russian (Moldova)', Google_Maps_Builder()->get_plugin_slug() ),
		'ru-ru' => __( 'Russian (Russia)', Google_Maps_Builder()->get_plugin_slug() ),
		'sr'    => __( 'Serbian', Google_Maps_Builder()->get_plugin_slug() ),
		'sk'    => __( 'Slovak', Google_Maps_Builder()->get_plugin_slug() ),
		'sl'    => __( 'Slovenian', Google_Maps_Builder()->get_plugin_slug() ),
		'es'    => __( 'Spanish', Google_Maps_Builder()->get_plugin_slug() ),
		'es-ar' => __( 'Spanish (Argentina)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-bo' => __( 'Spanish (Bolivia)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-cl' => __( 'Spanish (Chile)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-co' => __( 'Spanish (Colombia)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-cr' => __( 'Spanish (Costa Rica)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-do' => __( 'Spanish (Dominican Republic)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-ec' => __( 'Spanish (Ecuador)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-sv' => __( 'Spanish (El Salvador)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-gt' => __( 'Spanish (Guatemala)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-hn' => __( 'Spanish (Honduras)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-mx' => __( 'Spanish (Mexico)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-ni' => __( 'Spanish (Nicaragua)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-pa' => __( 'Spanish (Panama)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-py' => __( 'Spanish (Paraguay)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-pe' => __( 'Spanish (Peru)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-pr' => __( 'Spanish (Puerto Rico)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-es' => __( 'Spanish (Spain)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-uy' => __( 'Spanish (Uruguay)', Google_Maps_Builder()->get_plugin_slug() ),
		'es-ve' => __( 'Spanish (Venezuela)', Google_Maps_Builder()->get_plugin_slug() ),
		'sv'    => __( 'Swedish', Google_Maps_Builder()->get_plugin_slug() ),
		'sv-fi' => __( 'Swedish (Finland)', Google_Maps_Builder()->get_plugin_slug() ),
		'sv-se' => __( 'Swedish (Sweden)', Google_Maps_Builder()->get_plugin_slug() ),
		'tr'    => __( 'Turkish', Google_Maps_Builder()->get_plugin_slug() ),
		'uk'    => __( 'Ukranian', Google_Maps_Builder()->get_plugin_slug() ),
	) );

	return $lanugages;

}