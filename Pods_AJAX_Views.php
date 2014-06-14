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

		if ( is_admin() ) {
			include_once 'Pods_AJAX_Views_Admin.php';

			add_action( 'init', array( 'Pods_AJAX_Views_Admin', 'init' ) );
		}
		else {
			include_once 'Pods_AJAX_Views_Frontend.php';

			add_action( 'init', array( 'Pods_AJAX_Views_Frontend', 'init' ) );
			add_action( 'pods_view_alt_view', array( 'Pods_AJAX_Views_Frontend', 'pods_view_alt' ) );
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
					`view` LONGTEXT NOT NULL,
					`view_data` LONGTEXT NOT NULL,
					`expires` INT(10) UNSIGNED NOT NULL,
					`avg_time` DECIMAL(20, 3) NOT NULL,
					`total_time` DECIMAL(20, 3) NOT NULL,
					`total_calls` BIGINT(20) UNSIGNED NOT NULL,
					`last_generated` DATETIME NOT NULL,
					`tracking_data` LONGTEXT NOT NULL,
					PRIMARY KEY (`view_id`),
					UNIQUE INDEX `cache_key_mode` (`cache_key`, `cache_mode`)
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

}