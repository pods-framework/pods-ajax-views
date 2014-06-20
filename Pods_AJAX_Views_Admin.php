<?php
/**
 * Class Pods_AJAX_Views_Admin
 */
class Pods_AJAX_Views_Admin {

	/**
	 * Setup admin hooks
	 */
	public static function init() {

		// Admin UI
		add_filter( 'pods_admin_components_menu', array( __CLASS__, 'admin_menu' ) );

		// Admin AJAX callbacks
		add_action( 'wp_ajax_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );
		add_action( 'wp_ajax_nopriv_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );

		add_action( 'wp_ajax_pods_ajax_view_regenerate', array( __CLASS__, 'admin_ajax_view_regenerate' ) );
		add_action( 'wp_ajax_nopriv_pods_ajax_view_regenerate', array( __CLASS__, 'admin_ajax_view_regenerate' ) );

		add_action( 'wp_ajax_pods_ajax_view_sitemap', array( __CLASS__, 'admin_ajax_view_sitemap' ) );
		add_action( 'wp_ajax_nopriv_pods_ajax_view_sitemap', array( __CLASS__, 'admin_ajax_view_sitemap' ) );

	}

	/**
	 * Add options page to menu
	 *
	 * @param array $admin_menus The submenu items in Pods Admin menu.
	 *
	 * @return mixed
	 *
	 * @since 0.0.1
	 */
	public static function admin_menu( $admin_menus ) {

		$admin_menus[ 'AJAX Views' ] = array(
			'menu_page' => 'pods-ajax-views',
			'page_title' => __( 'Pods AJAX Views', 'pods-ajax-views' ),
			'capability' => 'manage_options',
			'callback' => array( __CLASS__, 'admin_page' )
		);

		return $admin_menus;

	}

	/**
	 * Output admin page
	 */
	public static function admin_page() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$ui = array(
			'item' => __( 'Pods AJAX View', 'pods-ajax-views' ),
			'items' => __( 'Pods AJAX Views', 'pods-ajax-views' ),
			'header' => array(
				'view' => __( 'AJAX View Stats', 'pods-ajax-views' )
			),
			'sql' => array(
				'table' => $wpdb->prefix . 'podsviews',
				'field_id' => 'view_id',
				'field_index' => 'view'
			),
			'orderby' => 't.avg_time DESC',
			'fields' => array(
				'manage' => array(
					'view' =>  array(
						'name' => 'view',
						'label' => 'View',
						'type' => 'text'
					),
					'cache_mode' => array(
						'name' => 'cache_mode',
						'label' => 'Cache Mode',
						'type' => 'text',
						'width' => '10%'
					),
					'uri' => array(
						'name' => 'uri',
						'label' => 'URL',
						'type' => 'text'
					),
					'expires' => array(
						'name' => 'expires',
						'label' => 'Expires (seconds)',
						'type' => 'number',
						'options' => array(
							'number_format_type' => '9999.99',
							'number_decimals' => 0
						),
						'width' => '12%'
					),
					'avg_time' => array(
						'name' => 'avg_time',
						'label' => 'Average Load Time (seconds)',
						'type' => 'number',
						'options' => array(
							'number_decimals' => 3
						),
						'width' => '12%'
					),
					'total_calls' => array(
						'name' => 'total_calls',
						'label' => 'Total Calls',
						'type' => 'number',
						'options' => array(
							'number_decimals' => 0
						),
						'width' => '10%'
					),
					'last_generated' => array(
						'name' => 'last_generated',
						'label' => 'Last Generated',
						'type' => 'datetime'
					)
				),
				'search' => array(
					'view' =>  array(
						'name' => 'view',
						'label' => 'View',
						'type' => 'text'
					),
					'cache_key' => array(
						'name' => 'cache_key',
						'label' => 'Cache Key',
						'type' => 'text'
					),
					'cache_mode' => array(
						'name' => 'cache_mode',
						'label' => 'Cache Mode',
						'type' => 'text'
					),
					'last_generated' => array(
						'name' => 'last_generated',
						'label' => 'Last Generated',
						'type' => 'datetime'
					),
					'tracking_data' => array(
						'name' => 'tracking_data',
						'label' => 'Tracking Data',
						'type' => 'paragraph'
					)
				)
			),
			'filters' => array(
				'view',
				'cache_mode',
				'last_generated'
			),
			'filters_enhanced' => true,
			'actions_disabled' => array(
				'add',
				'edit',
				'duplicate',
				'export'
			),
			'actions_custom' => array(
				'regenerate_view' => array(
					'callback' => array( __CLASS__, 'admin_page_regenerate_view' )
				),
				'view' => array(
					'callback' => array( __CLASS__, 'admin_page_view_stats' )
				),
				'delete' => array(
					'callback' => array( __CLASS__, 'admin_page_delete_view' )
				)
			),
			'actions_bulk' => array(
				'delete' => array(
					'label' => __( 'Delete', 'pods' )
					// callback not needed, Pods has this built-in for delete
				),
				'regenerate_views' => array(
					'callback' => array( __CLASS__, 'admin_page_regenerate_views' )
				)
			)
		);

		$ui[ 'fields' ][ 'view' ] = array();

		$ui[ 'fields' ][ 'view' ][ 'cache_key' ] = array(
			'name' => 'cache_key',
			'label' => 'Cache Key',
			'type' => 'text'
		);

		$ui[ 'fields' ][ 'view' ] = array_merge( $ui[ 'fields' ][ 'view' ], $ui[ 'fields' ][ 'manage' ] );

		$ui[ 'fields' ][ 'view' ][ 'tracking_data' ] = array(
			'name' => 'tracking_data',
			'label' => 'Tracking Data',
			'type' => 'paragraph'
		);

		unset( $ui[ 'fields' ][ 'view' ][ 'view' ] );

		if ( 1 == pods_v( 'deleted_bulk' ) ) {
			unset( $ui[ 'actions_custom' ][ 'delete' ] );
		}

		pods_ui( $ui );

	}

	/**
	 * Handle View action
	 *
	 * @param PodsUI $obj
	 * @param string $id
	 */
	public static function admin_page_view_stats( $obj, $id ) {

		$item = $obj->get_row();

		$item = array_map( 'maybe_unserialize', $item );

		include_once 'ui/view-stats.php';

	}

	/**
	 * Handle Regenerate View action
	 *
	 * @param PodsUI $obj
	 * @param string $id
	 */
	public static function admin_page_regenerate_view( $obj, $id ) {

		self::admin_page_regenerate_views_ajax( array( $id ) );

		$obj->action = 'manage';
		$obj->id = 0;

		unset( $_GET[ 'action' ] );
		unset( $_GET[ 'id' ] );

		$obj->manage();

	}

	public static function admin_page_delete_view( $id, $obj ) {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$sql = "
			SELECT `cache_key`, `cache_mode`
			FROM `{$wpdb->prefix}podsviews`
			WHERE `view_id` = %d
		";

		// Get item info
		$view = $wpdb->get_row( $wpdb->prepare( $sql, $id ) );

		if ( $view ) {
			include_once 'Pods_AJAX_Views_Frontend.php';

			$deleted = Pods_AJAX_Views_Frontend::delete_ajax_view( $view->cache_key, $view->cache_mode );

			if ( $deleted && 0 < $obj->id ) {
				pods_message( sprintf( __( "<strong>Deleted:</strong> %s has been deleted.", 'pods' ), $obj->item ) );
			}

			return $deleted;
		}

		return false;

	}

	/**
	 * Handle Regenerate Views bulk action
	 *
	 * @param array<string> $ids
	 * @param PodsUI $obj
	 */
	public static function admin_page_regenerate_views( $ids, $obj ) {

		self::admin_page_regenerate_views_ajax( $ids );

		$obj->action_bulk = false;
		unset( $_GET[ 'action_bulk' ] );

		$obj->bulk = array();
		unset( $_GET[ 'action_bulk_ids' ] );

		$obj->manage();

	}

	/**
	 * Handle AJAX regeneration of views
	 *
	 * @param array<string> $ids
	 */
	public static function admin_page_regenerate_views_ajax( $ids ) {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$ids = array_map( 'absint', $ids );

		$pods_ajax_views = array();

		$sql = "
			SELECT `cache_key`, `cache_mode`
			FROM `{$wpdb->prefix}podsviews`
			WHERE `view_id` = %d
		";

		foreach ( $ids as $id ) {
			// Get item info
			$view = $wpdb->get_row( $wpdb->prepare( $sql, $id ) );

			// Set cache key / mode for noncing
			$cache_key  = $view->cache_key;
			$cache_mode = $view->cache_mode;
			$uri        = $view->uri;

			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $cache_key . '/' . $cache_mode . '|' . $uri ) . '/regenerate';

			// Build nonce from action
			$nonce = wp_create_nonce( $nonce_action );

			// Setup object to push for processing
			$pods_ajax_views[] = array(
				'cache_key' => $cache_key,
				'cache_mode' => $cache_mode,
				'nonce' => $nonce,
				'uri' => $uri
			);
		}

		// Enqueue Pods AJAX Views JS
		wp_enqueue_script( 'pods-ajax-views' );

		// Queue view to be included via AJAX
		echo '<script>' . "\n"
			. 'var pods_ajax_views = ' . json_encode( $pods_ajax_views ) . ';' . "\n"
			. '</script>' . "\n";

		// Enqueue jQuery UI Progressbar
		wp_enqueue_script( 'jquery-ui-progressbar' );

		$message = '<span id="pods-ajax-views-progress-status">%s</span>'
			. '<div id="pods-ajax-views-progress-indicator" style="position:relative;max-width:300px;display:none;">'
			. '<div id="pods-ajax-views-progress-label" style="position:absolute;left:45%%;top:6px;font-weight:bold;text-shadow:1px 1px 0 #FFF;font-size:12px;">%s</div>'
			. '</div>';

		$message = sprintf( $message, _n( 'Regenerating Pods AJAX View', 'Regenerating Pods AJAX Views', count( $pods_ajax_views ), 'pods-ajax-views' ), __( 'Loading...', 'pods-ajax-views' ) );

		pods_message( $message );

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View
	 */
	public static function admin_ajax_view() {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			@ini_set( 'display_errors', 'on' );
			@error_reporting( E_ALL | E_STRICT );
		}

		include_once 'Pods_AJAX_Views_Frontend.php';

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			$uri = Pods_AJAX_Views_Frontend::get_uri();

			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] . '|' . $uri );

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				Pods_AJAX_Views_Frontend::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ] );

				// View found, bail (needed here if using template_redirect request)
				die();
			}
		}

		// AJAX must die, won't break if doing template_redirect hook
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			die();
		}

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View regeneration
	 */
	public static function admin_ajax_view_regenerate() {

		@header( 'Cache-Control: private, max-age=0, no-cache' );

		include_once 'Pods_AJAX_Views_Frontend.php';

		// Check if request uses API key, and if incorrect, don't serve request
		if ( isset( $_REQUEST[ 'pods_ajax_view_api_key' ] ) ) {
			if ( ! defined( 'PODS_AJAX_VIEWS_API_KEY' ) || PODS_AJAX_VIEWS_API_KEY != $_REQUEST[ 'pods_ajax_view_api_key' ]  ) {
				die();
			}
		}
		// If user is not logged in or not a Pods admin, don't serve request
		elseif ( ! is_user_logged_in() || ! pods_is_admin( 'pods' ) ) {
			// AJAX must die, won't break if doing template_redirect hook
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				die();
			}
		}

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			$uri = Pods_AJAX_Views_Frontend::get_uri();

			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] . '|' . $uri ) . '/regenerate';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				Pods_AJAX_Views_Frontend::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ], true, true );
			}

			// Bail (needed here if using template_redirect request)
			die();
		}

		// AJAX must die, won't break if doing template_redirect hook
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			die();
		}

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View regeneration URLs in XML Sitemap format
	 */
	public static function admin_ajax_view_sitemap() {

		@header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ) );

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		// Check if request uses API key, and if incorrect, don't serve request
		if ( isset( $_REQUEST[ 'pods_ajax_view_api_key' ] ) ) {
			if ( ! defined( 'PODS_AJAX_VIEWS_API_KEY' ) || PODS_AJAX_VIEWS_API_KEY != $_REQUEST[ 'pods_ajax_view_api_key' ]  ) {
				die();
			}
		}
		// If user is not logged in or not a Pods admin, don't serve request
		elseif ( ! is_user_logged_in() || ! pods_is_admin( 'pods' ) ) {
			die();
		}

		// XML opening tag
		echo '<' . '?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?' . '>' . "\n";

		// URL set open
		echo '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"'
			. ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$limit = 250;

		if ( defined( 'PODS_AJAX_VIEWS_SITEMAP_LIMIT' ) ) {
			$limit = (int) PODS_AJAX_VIEWS_SITEMAP_LIMIT;
		}

		$where = '';

		if ( isset( $_REQUEST[ 'lastmod' ] ) ) {
			$where = $wpdb->prepare( 'WHERE FROM_UNIXTIME( %d ) <= `last_generated`', strtotime( $_REQUEST[ 'lastmod' ] ) );
		}


		$sql = "
			SELECT *
			FROM `{$wpdb->prefix}podsviews`
			{$where}
			LIMIT %d
		";

		$views = $wpdb->get_results( $wpdb->prepare( $sql, $limit ) );

		foreach ( $views as $view ) {
			// Build nonce action
			$nonce_action = 'pods-ajax-view-' . md5( $view->cache_key . '/' . $view->cache_mode . '|' . $view->uri ) . '/regenerate';
			$nonce = wp_create_nonce( $nonce_action );

			$loc = $view->uri;

			$query_args = array(
				'pods_ajax_view_action' => 'view_regenerate',
				'pods_ajax_view_key' => $view->cache_key,
				'pods_ajax_view_mode' => $view->cache_mode,
				'pods_ajax_view_nonce' => $nonce,
				'pods_ajax_view_api_key' => PODS_AJAX_VIEWS_API_KEY
			);

			$loc = add_query_arg( $query_args, $loc );

			$loc = esc_url( $loc );

			// Sanitize URL for XML
			$loc = str_replace( '&', '&amp;', $loc );
			$loc = str_replace( "'", '&apos;', $loc );
			$loc = str_replace( '"', '&quot;', $loc );
			$loc = str_replace( '>', '&gt;', $loc );
			$loc = str_replace( '<', '&lt;', $loc );

			// Sanitize URL for XML
			$uri = $view->uri;
			$uri = str_replace( '&', '&amp;', $uri );
			$uri = str_replace( "'", '&apos;', $uri );
			$uri = str_replace( '"', '&quot;', $uri );
			$uri = str_replace( '>', '&gt;', $uri );
			$uri = str_replace( '<', '&lt;', $uri );

			$lastmod = $view->last_generated;

			$changefreq = 'always';

			if ( 0 < $view->expires && $view->expires <= HOUR_IN_SECONDS ) {
				$changefreq = 'hourly';
			}
			elseif ( HOUR_IN_SECONDS < $view->expires && $view->expires <= DAY_IN_SECONDS ) {
				$changefreq = 'daily';
			}
			elseif ( DAY_IN_SECONDS < $view->expires && $view->expires <= WEEK_IN_SECONDS ) {
				$changefreq = 'weekly';
			}
			elseif ( WEEK_IN_SECONDS < $view->expires && $view->expires <= ( DAY_IN_SECONDS * 30 ) ) {
				$changefreq = 'monthly';
			}
			elseif ( ( DAY_IN_SECONDS * 30 ) < $view->expires ) {
				$changefreq = 'yearly';
			}

			$priority = '1.0';

			echo "\t<url>\n";
			echo "\t\t<loc>{$loc}</loc>\n";
			echo "\t\t<lastmod>{$lastmod}</lastmod>\n";
			echo "\t\t<changefreq>{$changefreq}</changefreq>\n";
			echo "\t\t<priority>{$priority}</priority>\n";
			echo "\t</url>\n";
		}

		echo '</urlset>';

		// AJAX must die
		die();

	}

	public static function admin_ajax_clean_anon_cache() {

		@header( 'Cache-Control: private, max-age=0, no-cache' );

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_ajax_view_url' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			include_once 'Pods_AJAX_Views_Frontend.php';

			$uri = Pods_AJAX_Views_Frontend::get_uri( $_REQUEST[ 'pods_ajax_view_url' ] );

			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $uri ) . '/clean';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// WPEngine
				// Credit: https://github.com/cftp/WPEngine-Clear-URL-Cache
				if ( defined( 'WPE_PLUGIN_VERSION' ) ) {
					global $wpe_varnish_servers, $wpe_ec_servers;

					$post_parts = parse_url( $uri );
					$post_uri = $post_parts[ 'path' ];

					if ( ! empty( $post_parts[ 'query' ] ) ) {
						$post_uri .= '?' . $post_parts[ 'query' ];
					}

					$path = $post_uri;

					if ( ! $path ) {
						$path = '/';
					}

					$hostname = $post_parts[ 'host' ];

					if ( 'pod' == WPE_CLUSTER_TYPE ) {
						$wpe_varnish_servers = array( 'localhost' );
					}
					elseif ( ! isset( $wpe_varnish_servers ) ) {
						if ( 'pod' == WPE_CLUSTER_TYPE ) {
							$lbmaster = 'localhost';
						}
						elseif ( ! defined( 'WPE_CLUSTER_ID' ) || ! WPE_CLUSTER_ID ) {
							$lbmaster = 'lbmaster';
						}
						elseif ( 4 <= WPE_CLUSTER_ID ) {
							$lbmaster = 'localhost';
						}
						else {
							$lbmaster = 'lbmaster-' . WPE_CLUSTER_ID;
						}

						$wpe_varnish_servers = array( $lbmaster );
					}

					if ( ! isset( $wpe_ec_servers ) || empty( $wpe_ec_servers ) ) {
						foreach ( $wpe_varnish_servers as $varnish ) {
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( "Pods AJAX Views: PURGE, {$varnish}, 9002, {$hostname}, {$path}, array(), 0" );
							}

							WpeCommon::http_request_async( 'PURGE', $varnish, 9002, $hostname, $path, array(), 0 );
						}
					}
				}
				// W3TC
				elseif ( 1 == 0 ) {

				}
				// Others?
				elseif ( 1 == 0 ) {

				}
			}
		}

		// AJAX must die
		die();

	}

}