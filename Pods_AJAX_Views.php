<?php

/**
 * Class Pods_AJAX_Views
 */
class Pods_AJAX_Views {

	/**
	 * @var bool $compatible Whether plugin is compatible with Pods install
	 */
	public static $compatible = null;

	/**
	 * Setup default constants, add hooks
	 */
	public static function init() {

		// Default stats tracking and advanced functionality is off
		if ( ! defined( 'PODS_AJAX_VIEWS_STATS' ) ) {
			define( 'PODS_AJAX_VIEWS_STATS', false );
		}

		if ( ! defined( 'PODS_AJAX_VIEWS_OVERRIDE' ) ) {
			define( 'PODS_AJAX_VIEWS_OVERRIDE', false );
		}

		if ( is_admin() ) {
			include_once 'Pods_AJAX_Views_Admin.php';

			// Init admin
			add_action( 'init', array( 'Pods_AJAX_Views_Admin', 'init' ) );

			// Register assets
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		}
		else {
			include_once 'Pods_AJAX_Views_Frontend.php';

			// Init frontend
			add_action( 'init', array( 'Pods_AJAX_Views_Frontend', 'init' ) );

			// Register assets
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		}

	}

	/**
	 * Check if plugin is compatible with Pods install
	 *
	 * @return bool
	 */
	public static function is_compatible() {

		// See if compatible has been checked yet, if not, check it and set it
		if ( null === self::$compatible ) {
			// Default compatible is false
			self::$compatible = false;

			// Check if Pods is installed, that it's 2.4+, and that pods_view exists
			if ( defined( 'PODS_VERSION' ) && version_compare( '2.4.1', PODS_VERSION, '<=' ) && function_exists( 'pods_view' ) ) {
				// Set compatible to true for future reference
				self::$compatible = true;

				// Setup plugin if not yet setup
				if ( PODS_AJAX_VIEWS_VERSION !== get_option( 'pods_ajax_views_version' ) ) {
					self::activate();
				}
			}
		}

		return self::$compatible;

	}

	/**
	 * Activate plugin routine
	 */
	public static function activate() {

		// Create table for stats tracking and other advanced features
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			/**
			 * @var $wpdb wpdb
			 */
			global $wpdb;

			// Table definitions
			$tables = array();

			$tables[] = "
				CREATE TABLE `{$wpdb->prefix}podsviews` (
					`view_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`cache_key` VARCHAR(255) NOT NULL,
					`cache_mode` VARCHAR(14) NOT NULL,
					`uri` VARCHAR(255) NOT NULL,
					`view` LONGTEXT NOT NULL,
					`view_data` LONGTEXT NOT NULL,
					`expires` INT(10) UNSIGNED NOT NULL,
					`avg_time` DECIMAL(20,3) NOT NULL,
					`total_time` DECIMAL(20,3) NOT NULL,
					`total_calls` BIGINT(20) UNSIGNED NOT NULL,
					`last_generated` DATETIME NOT NULL,
					`tracking_data` LONGTEXT NOT NULL,
					PRIMARY KEY (`view_id`),
					UNIQUE KEY `cache_key_mode_uri` (`cache_key`, `cache_mode`, `uri`)
				)
			";

			// Create / alter table handling
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( $tables );
		}

		// Update version in DB
		update_option( 'pods_ajax_views_version', PODS_AJAX_VIEWS_VERSION );

	}

	/**
	 * Deactivate plugin routine
	 */
	public static function deactivate() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		include_once 'Pods_AJAX_Views_Frontend.php';

		// Reset AJAX View data
		Pods_AJAX_Views_Frontend::reset_ajax_views();

		// Delete table if it exists
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}podsviews`" );

	}

	/**
	 * Register assets for Pods AJAX Views
	 */
	public function register_assets() {

		// Register JS script for Pods AJAX View processing
		wp_register_script( 'pods-ajax-views', plugins_url( 'js/pods-ajax-views.js', __FILE__ ), array( 'jquery' ), PODS_AJAX_VIEWS_VERSION, true );

		$is_admin = is_admin();

		$additional_urls = array();

		// Clear page cache for URL when finished loading all AJAX Views for a page
		// so that the next time the page is loaded, no AJAX is used
		if ( ! $is_admin && ! is_user_logged_in() ) {
			$clean_anon_cache = false;

			// WPEngine
			if ( defined( 'WPE_PLUGIN_VERSION' ) ) {
				$clean_anon_cache = true;
			}
			// W3 Total Cache
			elseif ( 1 == 0 ) {
				$clean_anon_cache = true;
			}
			// Others?
			elseif ( 1 == 0 ) {
				$clean_anon_cache = true;
			}

			// Add AJAX request to clean anon cache
			if ( $clean_anon_cache ) {
				include_once 'Pods_AJAX_Views_Frontend.php';

				$uri = Pods_AJAX_Views_Frontend::get_uri();

				// Build nonce action from request
				$nonce_action = 'pods-ajax-view-' . md5( $uri ) . '/clean';

				// Build nonce from action
				$nonce = wp_create_nonce( $nonce_action );

				$ajax_args = array(
					'pods_ajax_view_action' => 'clean_anon_cache',
					'pods_ajax_view_url' => $uri,
					'pods_ajax_view_nonce' => $nonce
				);

				$ajax_uri = add_query_arg( $ajax_args, $uri );

				$additional_urls[] = $ajax_uri;
			}
		}

		// Setup config values for reference
		$config = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'version' => PODS_AJAX_VIEWS_VERSION,
			'is_admin' => $is_admin,
			'status_complete' => __( 'Pods AJAX View generated successfully', 'pods-ajax-views' ),
			'status_complete_plural' => __( 'Pods AJAX Views generated successfully', 'pods-ajax-views' ),
			'additional_urls' => $additional_urls
		);

		// Setup variable for output when JS enqueued
		wp_localize_script( 'pods-ajax-views', 'pods_ajax_views_config', $config );
	}

}