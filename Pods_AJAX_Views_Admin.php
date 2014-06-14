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
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// Admin AJAX callbacks
		add_action( 'wp_ajax_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );
		add_action( 'wp_ajax_nopriv_pods_ajax_view', array( __CLASS__, 'admin_ajax_view' ) );

		add_action( 'wp_ajax_pods_ajax_regenerate', array( __CLASS__, 'admin_ajax_regenerate' ) );

	}

	public static function admin_menu() {

		if ( Pods_AJAX_Views::is_compatible() && pods_is_admin( 'pods' ) ) {
			add_options_page( __( 'Pods AJAX Views', 'pods-ajax-views' ), __( 'Pods AJAX Views', 'pods-ajax-views' ), 'read', 'pods-ajax-views', array( __CLASS__, 'admin_options' ) );
		}

	}

	public static function admin_options() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$ui = array(
			'item' => __( 'Pods AJAX View', 'pods-ajax-views' ),
			'items' => __( 'Pods AJAX Views', 'pods-ajax-views' ),
			'sql' => array(
				'table' => $wpdb->prefix . 'podsviews',
				'field_id' => 'view_id',
				'field_index' => 'view'
			),
			'orderby' => 't.avg_time DESC',
			'fields' => array(
				'manage' => array(
					'view' => array(
						'name' => 'view',
						'label' => 'View',
						'type' => 'text'
					),
					'cache_mode' => array(
						'name' => 'cache_mode',
						'label' => 'Cache Mode',
						'type' => 'text'
					),
					'expires' => array(
						'name' => 'expires',
						'label' => 'Expires (seconds)',
						'type' => 'number',
						'options' => array(
							'number_format_type' => '9999.99',
							'number_decimals' => 0
						)
					),
					'avg_time' => array(
						'name' => 'avg_time',
						'label' => 'Average Load Time (seconds)',
						'type' => 'number',
						'options' => array(
							'number_decimals' => 3
						)
					),
					'total_calls' => array(
						'name' => 'total_calls',
						'label' => 'Total Calls',
						'type' => 'number',
						'options' => array(
							'number_decimals' => 0
						)
					),
					'last_generated' => array(
						'name' => 'last_generated',
						'label' => 'Last Generated',
						'type' => 'datetime'
					)
				),
				'search' => array(
					'view' => array(
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
				'export',
				'view'
			),
			'actions_custom' => array(
				'regenerate_view' => array(
					'callback' => array( __CLASS__, 'admin_options_regenerate_view' )
				)
			),
			'actions_bulk' => array(
				'delete' => array(
					'label' => __( 'Delete', 'pods' )
					// callback not needed, Pods has this built-in for delete
				),
				'regenerate_views' => array(
					'callback' => array( __CLASS__, 'admin_options_regenerate_views' )
				)
			)
		);

		pods_ui( $ui );

	}

	/**
	 * @param PodsUI $obj
	 * @param string $id
	 */
	public static function admin_options_regenerate_view( $obj, $id ) {

		wp_enqueue_script( 'pods-ajax-views' );

		// @todo Enqueue JS

		// @todo Echo queue JS

		// @todo Echo progress indicator

		$obj->manage();

	}

	/**
	 * @param array<string> $ids
	 * @param PodsUI $obj
	 */
	public static function admin_options_regenerate_views( $ids, $obj ) {

		// @todo Enqueue JS

		// @todo Echo queue JS

		// @todo Echo progress indicator

		$obj->action_bulk = false;
		unset( $_GET[ 'action_bulk' ] );

		$obj->bulk = array();
		unset( $_GET[ 'action_bulk_ids' ] );

		$obj->manage();

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View
	 */
	public static function admin_ajax_view() {

		include_once 'Pods_AJAX_Views_Frontend.php';

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] );

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				Pods_AJAX_Views_Frontend::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ] );
			}
		}

		// AJAX must die
		die();

	}

	/**
	 * Handle the Admin AJAX request for a Pods AJAX View regeneration
	 */
	public static function admin_ajax_regenerate() {

		include_once 'Pods_AJAX_Views_Frontend.php';

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_ajax_view_key' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_mode' ] ) && ! empty( $_REQUEST[ 'pods_ajax_view_nonce' ] ) ) {
			// Build nonce action from request
			$nonce_action = 'pods-ajax-view-' . md5( $_REQUEST[ 'pods_ajax_view_key' ] . '/' . $_REQUEST[ 'pods_ajax_view_mode' ] ) . '/regenerate';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_ajax_view_nonce' ], $nonce_action ) ) {
				// Generate view and cache it
				Pods_AJAX_Views_Frontend::generate_view( $_REQUEST[ 'pods_ajax_view_key' ], $_REQUEST[ 'pods_ajax_view_mode' ], true );
			}
		}

		// AJAX must die
		die();

	}

}