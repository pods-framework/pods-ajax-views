<?php
/*
Plugin Name: Pods AJAX Views
Plugin URI: http://pods.io/2014/04/16/introducing-pods-alternative-cache/
Description: An easy way to generate cached views from AJAX when they haven't been cached yet
Version: 0.1
Author: The Pods Framework Team
Author URI: http://pods.io/
*/

// Pods AJAX Views version
define( 'PODS_AJAX_VIEWS_VERSION', '0.1.1' );

// Include Pods_AJAX_Views plugin class
include_once 'Pods_AJAX_Views.php';

/**
 * Get Pods View or add view to the Pods AJAX View queue
 *
 * @see pods_view
 * @uses Pods_AJAX_Views::ajax_view
 *
 * @param string $view Path of the view file
 * @param array|null $data (optional) Data to pass on to the template
 * @param bool|int|array $expires (optional) Time in seconds for the cache to expire, if 0 no expiration.
 * @param string $cache_mode (optional) Decides the caching method to use for the view.
 * @param bool $return (optional) Whether to return the view or not, defaults to false and will echo it
 * @param bool $forced_generate Force generation, even already cached
 *
 * @return string|null
 */
function pods_ajax_view( $view, $data = null, $expires = false, $cache_mode = 'cache', $return = false, $forced_generate = false ) {

	// Setup AJAX View
	$view = Pods_AJAX_Views::ajax_view( $view, $data, $expires, $cache_mode, $forced_generate );

	// echo if not set to return
	if ( ! $return ) {
		// Output view
		echo $view;

		// Return null
		$view = null;
	}

	return $view;

}

// On plugins loaded, run our init
add_action( 'plugins_loaded', array( 'Pods_AJAX_Views', 'init' ) );

// Activation / Deactivation hooks
register_activation_hook( __FILE__, array( 'Pods_AJAX_Views', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pods_AJAX_Views', 'deactivate' ) );