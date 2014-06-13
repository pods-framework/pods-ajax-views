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
	 * @var array $cache_modes Array of available cache modes
	 */
	public static $cache_modes = array();

	/**
	 * Setup default constants, add hooks
	 */
	public static function init() {

		// Default stats tracking and advanced functionality is off
		if ( !defined( 'PODS_AJAX_VIEWS_STATS' ) ) {
			define( 'PODS_AJAX_VIEWS_STATS', false );
		}

		// Admin AJAX callbacks
		add_action( 'wp_ajax_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );
		add_action( 'wp_ajax_nopriv_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );

		// Register assets
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

	}

	/**
	 * Check if plugin is compatible with Pods install
	 *
	 * @return bool
	 */
	private static function check_compatibility() {

		// See if compatible has been checked yet, if not, check it and set it
		if ( null === self::$compatible ) {
			// Default compatible is false
			self::$compatible = false;

			// Check if Pods is installed, that it's 2.4+, and that pods_view exists
			if ( defined( 'PODS_VERSION' ) && version_compare( '2.4', PODS_VERSION, '<=' ) && function_exists( 'pods_view' ) ) {
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
	public static function activate( $network_wide = false ) {

		// Create table for stats tracking and other advanced features
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			/**
			 * @var $wpdb wpdb
			 */
			global $wpdb;

			// Table definitions
			$tables = array( "
				CREATE TABLE `{$wpdb->prefix}podsviews` (
					`cache_key` VARCHAR(255) NOT NULL,
					`cache_mode` VARCHAR(14) NOT NULL,
					`view` LONGTEXT NOT NULL,
					`view_data` LONGTEXT NOT NULL,
					`expires` INT(10) NOT NULL,
					`tracking_data` LONGTEXT NOT NULL,
					PRIMARY KEY (`cache_key`),
					UNIQUE INDEX `cache_key_mode` (`cache_key`, `cache_mode`)
				)
			" );

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
	public static function deactivate( $network_wide = false ) {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		// Reset AJAX View data
		self::reset_ajax_views();

		// Delete table if it exists
		$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}podsviews`" );

	}

	/**
	 * Restrict the cache mode used to only those supported by Pods Views
	 *
	 * @uses PodsView::$cache_modes
	 *
	 * @param string $cache_mode Cache mode to restrict
	 *
	 * @return string Cache mode as restricted by Pods Views
	 */
	private static function restrict_cache_mode( $cache_mode ) {

		// Check if cache modes has been set yet
		if ( empty( self::$cache_modes ) ) {
			// Check compatibility
			if ( !self::check_compatibility() ) {
				return $cache_mode;
			}

			// Pods 3.0 support
			if ( version_compare( '3.0-a-1', PODS_VERSION, '<=' ) ) {
				self::$cache_modes = Pods_View::$cache_modes;
			}
			// Default Pods 2.x support
			else {
				// Include if it hasn't been called yet on the page
				if ( !class_exists( 'PodsView' ) ) {
					require_once( PODS_DIR . 'classes/PodsView.php' );
				}

				self::$cache_modes = PodsView::$cache_modes;
			}
		}

		// If cache mode not supported, set default to 'cache'
		if ( !in_array( $cache_mode, self::$cache_modes ) ) {
			$cache_mode = 'cache';
		}

		return $cache_mode;

	}

	/**
	 * Advanced expires handling for Pods Views
	 *
	 * @uses PodsView::expires
	 *
	 * @param array|bool|int $expires
	 * @param string $cache_mode Cache mode
	 *
	 * @return mixed
	 */
	private static function handle_expires( $expires, $cache_mode ) {

		// Check compatibility
		if ( !self::check_compatibility() ) {
			return $expires;
		}

		// Pods 3.0 support
		if ( version_compare( '3.0-a-1', PODS_VERSION, '<=' ) ) {
			$expires = Pods_View::expires( $expires, $cache_mode );
		}
		// Default Pods 2.x support
		else {
			// Include if it hasn't been called yet on the page
			if ( !class_exists( 'PodsView' ) ) {
				require_once( PODS_DIR . 'classes/PodsView.php' );
			}

			$expires = PodsView::expires( $expires, $cache_mode );
		}

		return $expires;

	}

	/**
	 * Get cached view from Pods Views
	 *
	 * @uses PodsView::get
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 *
	 * @return bool|mixed
	 */
	private static function get_cached_view( $cache_key, $cache_mode ) {

		// Check compatibility
		if ( !self::check_compatibility() ) {
			return false;
		}

		return pods_view_get( $cache_key, $cache_mode, 'pods_view' );

	}

	/**
	 * Delete cached view from Pods Views
	 *
	 * @uses PodsView::clear
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 *
	 * @return bool|mixed
	 */
	private static function delete_cached_view( $cache_key, $cache_mode ) {

		// Check compatibility
		if ( !self::check_compatibility() ) {
			return false;
		}

		return pods_view_clear( $cache_key, $cache_mode, 'pods_view' );

	}

	/**
	 * Get cache key that Pods Views use
	 *
	 * @see pods_view
	 *
	 * @param string $view Path of the view file
	 * @param array|null $data (optional) Data to pass on to the template
	 * @param bool|int|array $expires (optional) Time in seconds for the cache to expire, if 0 no expires.
	 * @param string $cache_mode (optional) Decides the caching method to use for the view.
	 *
	 * @return string
	 */
	private static function get_cache_key_from_view( $view, $data = null, $expires = false, $cache_mode = 'cache' ) {

		// Check compatibility
		if ( !self::check_compatibility() ) {
			return $view;
		}

		// Restrict cache mode
		$cache_mode = self::restrict_cache_mode( $cache_mode );

		// Advanced $expires handling
		$expires = self::handle_expires( $expires, $cache_mode );

		// Support my-view.php?custom-key=X#hash keying for cache
		$view_id = '';

		// If $view is not an array, look for unique identifiers for segmented caching
		if ( !is_array( $view ) ) {
			// Get query value for segmenting
			$view_q = explode( '?', $view );

			if ( 1 < count( $view_q ) ) {
				$view_id = '?' . $view_q[ 1 ];

				$view = $view_q[ 0 ];
			}

			// Get hash value for segmenting
			$view_h = explode( '#', $view );

			if ( 1 < count( $view_h ) ) {
				$view_id .= '#' . $view_h[ 1 ];

				$view = $view_h[ 0 ];
			}

			// Support dynamic tags!
			$view_id = pods_evaluate_tags( $view_id );
		}

		// Allow filtering of view
		$view = apply_filters( 'pods_view_inc', $view, $data, $expires, $cache_mode );

		// Setup cache key
		$cache_key = $view;

		// If is array, implode
		if ( is_array( $cache_key ) ) {
			$cache_key = implode( '-', $cache_key ) . '.php';
		}

		// If exists, use realpath for proper directory separators
		if ( false !== realpath( $cache_key ) ) {
			$cache_key = realpath( $cache_key );
		}

		// Get abspath
		$abspath_dir = realpath( ABSPATH );

		// Remove abspath from key
		$cache_key = pods_str_replace( $abspath_dir, '/', $cache_key, 1 );

		// Assemble cache key
		$cache_key = 'pods-view-' . $cache_key . $view_id;

		return $cache_key;

	}

	/**
	 * Get AJAX View data
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 *
	 * @return array|bool Array of AJAX View data or false if not found
	 */
	public static function get_ajax_view( $cache_key, $cache_mode ) {

		// Restrict cache mode
		$cache_mode = self::restrict_cache_mode( $cache_mode );

		$ajax_view = array();

		// Get transient
		$ajax_view_transient = get_transient( 'pods_ajax_view_' . md5( $cache_key . '/' . $cache_mode ) );

		// Get stats data
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			/**
			 * @var $wpdb wpdb
			 */
			global $wpdb;

			$ajax_view = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM `{$wpdb->prefix}podsviews`
				WHERE `cache_key` = %s AND `cache_mode` = %s
			", $cache_key, $cache_mode ) );

			if ( !empty( $ajax_view ) ) {
				$ajax_view = array_map( 'maybe_unserialize', $ajax_view );
			}
			else {
				$ajax_view = array();
			}
		}

		// Combine stats data (if found) with transient data
		if ( !empty( $ajax_view_transient ) ) {
			$ajax_view = array_merge( $ajax_view_transient, $ajax_view );
		}

		return $ajax_view;

	}

	/**
	 * Save AJAX View data
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 * @param array $data AJAX View data
	 *
	 * @return bool
	 */
	public static function save_ajax_view( $cache_key, $cache_mode, $data ) {

		// Restrict cache mode
		$cache_mode = self::restrict_cache_mode( $cache_mode );

		// Save stats data
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			/**
			 * @var $wpdb wpdb
			 */
			global $wpdb;

			// Set cache data
			$data[ 'cache_key' ] = $cache_key;
			$data[ 'cache_mode' ] = $cache_mode;

			if ( isset( $data[ 'expires' ] ) ) {
				if ( false === $data[ 'expires' ] ) {
					 $data[ 'expires' ] = -1;
				}

				$data[ 'expires' ] = (int) $data[ 'expires' ];
			}

			// Serialize arrays
			$data = array_map( 'maybe_serialize', $data );

			// Setup format for wpdb::prepare
			$format = array_fill( 0, count( $data ), '%s' );

			// REPLACE INTO
			$wpdb->replace(
				$wpdb->prefix . 'podsviews',
				$data,
				$format
			);
		}

		return true;

	}

	/**
	 * Delete AJAX View
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 *
	 * @return mixed|null
	 */
	public static function delete_ajax_view( $cache_key, $cache_mode ) {

		// Restrict cache mode
		$cache_mode = self::restrict_cache_mode( $cache_mode );

		// Delete from stats
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			/**
			 * @var $wpdb wpdb
			 */
			global $wpdb;

			$wpdb->delete(
				$wpdb->prefix . 'podsviews',
				array(
					'cache_key' => $cache_key,
					'cache_mode' => $cache_mode
				),
				array(
					'%s',
					'%s'
				)
			);
		}

		// Delete transient
		delete_transient( 'pods_ajax_view_' . md5( $cache_key . '/' . $cache_mode ) );

		return true;

	}

	/**
	 * Reset AJAX View tracking
	 *
	 * @return array Affected rows for each reset done
	 */
	public static function reset_ajax_views() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$queries = array();

		// Delete AJAX Views from transients
		$queries[] = "
			DELETE FROM
				`{$wpdb->options}`
			WHERE
				`option_name` LIKE '_transient_pods_ajax_view_%'
		";

		// TRUNCATE stats table
		if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS ) {
			$queries[] = "TRUNCATE `{$wpdb->prefix}podsviews`";
		}

		// Run all queries
		return array_map( array( $wpdb, 'query' ), $queries );

	}

	/**
	 * Generate Pods View from AJAX View
	 *
	 * @param string $cache_key Cache key
	 * @param string $cache_mode Cache mode
	 * @param bool $forced_generate Force generation, even already cached
	 *
	 * @return mixed|null
	 */
	public static function generate_view( $cache_key, $cache_mode, $forced_generate = false ) {

		// Get AJAX View
		$ajax_view = self::get_ajax_view( $cache_key, $cache_mode );

		if ( !empty( $ajax_view ) ) {
			// Start timer
			$start = time();

			// Enforce int on expires value
			$ajax_view[ 'expires' ] = (int) $ajax_view[ 'expires' ];

			// If -1, translate back to false
			if ( -1 == $ajax_view[ 'expires' ] ) {
				$ajax_view[ 'expires' ] = false;
			}

			// Use/generate cache and output view
			pods_view( $ajax_view[ 'view' ], $ajax_view[ 'view_data' ], $ajax_view[ 'expires' ], $cache_mode );

			// Track total time to run
			$total = time() - $start;

			// Stats tracking of views
			if ( defined( 'PODS_AJAX_VIEWS_STATS' ) && PODS_AJAX_VIEWS_STATS && !empty( $ajax_view[ 'path' ] ) ) {
				$data = array();

				// Default tracking data
				$tracking_data = array(
					'total_time' => $total,
					'total_calls' => 1,
					'last_generated' => current_time( 'mysql' )
				);

				// Merge tracking data if path called from before
				if ( !empty( $ajax_view[ 'tracking_data' ] ) && !empty( $ajax_view[ 'tracking_data' ][ $ajax_view[ 'path' ] ] ) ) {
					$tracking_data = $ajax_view[ 'tracking_data' ][ $ajax_view[ 'path' ] ];

					$data[ 'tracking_data' ] = $ajax_view[ 'tracking_data' ];

					$tracking_data[ 'total_time' ] += $total;
					$tracking_data[ 'total_calls' ] += 1;
					$tracking_data[ 'last_generated' ] = current_time( 'mysql' );
				}

				// Set tracking data to be saved for path
				$data[ 'tracking_data' ][ $ajax_view[ 'path' ] ] = $tracking_data;

				// Save AJAX View data
				self::save_ajax_view( $cache_key, $cache_mode, $data );
			}
		}

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View
	 */
	public static function admin_ajax_view() {

		// Check if request is there
		if ( !empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && !empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && !empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] );

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				self::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ] );
			}
		}

		// AJAX must die
		die();

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View regeneration
	 */
	public static function admin_ajax_regenerate() {

		// Check if request is there
		if ( !empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && !empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && !empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] ) . '/regenerate';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				self::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ], true );
			}
		}

		// AJAX must die
		die();

	}

	/**
	 * Register assets for Pods AJAX Views
	 */
	public function register_assets() {

		// Register JS script for Pods AJAX View processing
		wp_register_script( 'pods-ajax-views', plugins_url( 'js/pods-ajax-views.js', __FILE__ ), array( 'jquery' ), PODS_AJAX_VIEWS_VERSION, true );

		// Setup config values for reference
		$config = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'version' => PODS_AJAX_VIEWS_VERSION
		);

		// Setup variable for output when JS enqueued
		wp_localize_script( 'pods-ajax-views', 'pods_ajax_views_config', $config );

	}

	/**
	 * Get Pods View or add view to the Pods AJAX View queue
	 *
	 * @see pods_view
	 *
	 * @param string $view Path of the view file
	 * @param array|null $data (optional) Data to pass on to the template
	 * @param bool|int|array $expires (optional) Time in seconds for the cache to expire, if 0 no expires.
	 * @param string $cache_mode (optional) Decides the caching method to use for the view.
	 * @param bool $forced_generate Force generation, even already cached
	 *
	 * @return string
	 */
	public static function ajax_view( $view, $data = null, $expires = false, $cache_mode = 'cache', $forced_generate = false ) {

		// Get cache key for request
		$cache_key = self::get_cache_key_from_view( $view, $data, $expires, $cache_mode );

		// Get cached view
		$output = self::get_cached_view( $cache_key, $cache_mode );

		// If not cached, add to the queue and include it via AJAX
		if ( false === $output || $forced_generate ) {
			if ( $forced_generate ) {
				// Delete cached view
				self::delete_cached_view( $cache_key, $cache_mode );
			}

			// Advanced $expires handling
			$expires = self::handle_expires( $expires, $cache_mode );

			// Translate explicit false to -1
			if ( false === $expires ) {
				$expires = '-1';
			}

			// Get backtrace to build path information
			$debug_backtrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 3 );

			// Get backtrace for the pods ajax view call
			$depth = 1;

			// If calling Pods_AJAX_Views::ajax_view from pods_ajax_view, go one more level deep
			if ( 'pods_ajax_view' == $debug_backtrace[ $depth ][ 'function' ] ) {
				$depth++;
			}

			// Get function info from backtrace
			$debug_backtrace = $debug_backtrace[ $depth ];

			// Setup path information
			$path = '';

			// Add class to path
			if ( isset( $debug_backtrace[ 'class' ] ) ) {
				$path .= $debug_backtrace[ 'class' ];

				// Add type -> or :: to path
				if ( isset( $debug_backtrace[ 'type' ] ) ) {
					$path .= $debug_backtrace[ 'type' ];
				}
			}

			// Add function / method name to path
			$path .= $debug_backtrace[ 'function' ];

			// Add file location to path
			if ( isset( $debug_backtrace[ 'file' ] ) ) {
				$path .= ' ' . $debug_backtrace[ 'file' ];
			}

			// Add line number to path
			if ( isset( $debug_backtrace[ 'line' ] ) ) {
				$path .= ':' . $debug_backtrace[ 'line' ];
			}

			// Setup data to save to transient
			$data = array(
				'cache_key' => $cache_key,
				'cache_mode' => $cache_mode,
				'view' => $view,
				'view_data' => $data,
				'expires' => $expires,
				'path' => $path
			);

			// Save AJAX View to transient for AJAX processing
			set_transient( 'pods_ajax_view_' . md5( $cache_key . '/' . $cache_mode ), $data );

			// Enqueue Pods AJAX Views JS
			wp_enqueue_script( 'pods-ajax-views' );

			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $cache_key . '/' . $cache_mode );

			// Build nonce from action
			$nonce = wp_create_nonce( $nonce_action );

			// Setup object to push for processing
			$pods_ajax_view = array(
				'cache_key' => $cache_key,
				'cache_mode' => $cache_mode,
				'nonce' => $nonce
			);

			// Queue view to be included via AJAX
			$output = '<script>' . "\n"
				. 'var pods_ajax_views = pods_ajax_views || [];' . "\n"
				. 'pods_ajax_views.push( ' . json_encode( $pods_ajax_view ) . ' );' . "\n"
				. '</script>' . "\n";

			// Allow for override of loading image
			$spinner = apply_filters( 'pods_ajax_view_loader', includes_url( 'images/wpspin.gif' ), $view, $data, $expires, $cache_mode );

			// Output div with loading image
			$output .= '<div class="pods-ajax-view-loader ' . sanitize_html_class( 'pods-ajax-view-loader-' . $nonce ) . '">'
				. '<img src="' . esc_url( $spinner ) . '" style="max-width:100%;max-height:100%;" />'
				. '</div>';
		}

		// Return output of either cached view or JS to queue for AJAX processing
		return $output;

	}

}