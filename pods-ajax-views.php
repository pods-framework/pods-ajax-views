<?php
/*
Plugin Name: Pods AJAX Views
Plugin URI: https://pods.io/2014/04/16/introducing-pods-alternative-cache/
Description: An easy way to generate cached views from AJAX when they haven't been cached yet
Version: 1.2
Author: The Pods Framework Team
Author URI: https://pods.io/
*/

// Pods AJAX Views version
define( 'PODS_AJAX_VIEWS_VERSION', '1.2' );

// Include class
include_once 'Pods_AJAX_Views.php';

/**
 * Get Pods View or add view to the Pods AJAX View queue
 *
 * @param string         $view            Path of the view file
 * @param array|null     $data            (optional) Data to pass on to the template
 * @param bool|int|array $expires         (optional) Time in seconds for the cache to expire, if 0 no expiration.
 * @param string         $cache_mode      (optional) Decides the caching method to use for the view.
 * @param bool           $return          (optional) Whether to return the view or not, defaults to false and will echo
 *                                        it
 * @param bool           $forced_generate Force generation, even already cached
 *
 * @return string|null
 * @see  pods_view
 * @uses Pods_AJAX_Views::ajax_view
 *
 */
function pods_ajax_view( $view, $data = null, $expires = false, $cache_mode = 'cache', $return = false, $forced_generate = false ) {
	include_once 'Pods_AJAX_Views_Frontend.php';

	// Setup AJAX View
	$view = Pods_AJAX_Views_Frontend::ajax_view( $view, $data, $expires, $cache_mode, $forced_generate );

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
add_action( 'plugins_loaded', [ 'Pods_AJAX_Views', 'init' ] );

// Activation / Deactivation hooks
register_activation_hook( __FILE__, [ 'Pods_AJAX_Views', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Pods_AJAX_Views', 'deactivate' ] );

/**
 * Register add-on with Pods Freemius connection.
 *
 * @since 1.2
 */
function pods_ajax_views_freemius() {
	try {
		fs_dynamic_init( [
			'id'               => '5755',
			'slug'             => 'pods-ajax-views',
			'type'             => 'plugin',
			'public_key'       => 'pk_8606e36bd5153a1faf1e041342634',
			'is_premium'       => false,
			'has_paid_plans'   => false,
			'is_org_compliant' => true,
			'parent'           => [
				'id'         => '5347',
				'slug'       => 'pods',
				'public_key' => 'pk_737105490825babae220297e18920',
				'name'       => 'Pods',
			],
			'menu'             => [
				'slug'        => 'pods-settings',
				'contact'     => false,
				'support'     => false,
				'affiliation' => false,
				'account'     => true,
				'pricing'     => false,
				'addons'      => true,
				'parent'      => [
					'slug' => 'pods',
				],
			],
		] );
	} catch ( \Exception $exception ) {
		return;
	}
}

add_action( 'pods_freemius_init', 'pods_ajax_views_freemius' );
