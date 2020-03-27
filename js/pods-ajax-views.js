/**
 * Pods AJAX Views processor
 *
 * @type {{queue: Array, process_next: process_next, load_view: load_view}}
 */
var Pods_AJAX_View_Processor = {

	/**
	 * Store current queue of views to process
	 */
	queue : [],

	/**
	 * Store total number of original queue
	 */
	total : 0,

	/**
	 * Store progress indicator objects
	 */
	progress_indicator : {
		status : null, progress : null, progress_label : null
	},

	/**
	 * Process the next view from the queue
	 */
	process_next : function () {

		if ( pods_ajax_views_config.is_admin && null === Pods_AJAX_View_Processor.progress_indicator.progress ) {
			Pods_AJAX_View_Processor.progress_indicator.status = jQuery( '#pods-ajax-views-progress-status' );
			Pods_AJAX_View_Processor.progress_indicator.progress = jQuery( '#pods-ajax-views-progress-indicator' );
			Pods_AJAX_View_Processor.progress_indicator.progress_label = jQuery( '#pods-ajax-views-progress-label' );

			Pods_AJAX_View_Processor.progress_indicator.progress.show();

			Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( {
																				  value    : 0,
																				  max      : 200,
																				  change   : function () {

																					  var value = Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( 'value' );

																					  // Value / 2 because max is 200 and we want 0-100% format
																					  Pods_AJAX_View_Processor.progress_indicator.progress_label.text( (value / 2) + '%' );

																				  },
																				  complete : function () {

																					  Pods_AJAX_View_Processor.progress_indicator.progress_label.text( '100%' );

																				  }
																			  } );
		}

		// Check if there are views in the queue
		if ( Pods_AJAX_View_Processor.queue.length ) {
			// Get the next view in line
			view = Pods_AJAX_View_Processor.queue.shift();

			// Check for valid view data, then load the view
			if ( 'undefined' != typeof view.cache_key && 'undefined' != typeof view.cache_mode && 'undefined' != typeof view.nonce ) {
				Pods_AJAX_View_Processor.load_view( view.cache_key, view.cache_mode, view.nonce, view.uri );
			}
			// If invalid, process next view from the queue
			else {
				Pods_AJAX_View_Processor.process_next();
			}
		}
		else {
			if ( pods_ajax_views_config.is_admin ) {
				Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( {value : 200} );

				var status_text = pods_ajax_views_config.status_complete;

				if ( 1 < Pods_AJAX_View_Processor.total ) {
					status_text = pods_ajax_views_config.status_complete_plural;
				}

				Pods_AJAX_View_Processor.progress_indicator.status.text( status_text );
			}
			else {
				if ( 0 < Pods_AJAX_View_Processor.total && pods_ajax_views_config.additional_urls.length ) {
					var additional_url, k;

					for ( k in pods_ajax_views_config.additional_urls ) {
						additional_url = pods_ajax_views_config.additional_urls[k];

						jQuery.ajax( {
										 type     : 'POST',
										 dataType : 'html',
										 url      : additional_url,
										 cache    : false,
										 success  : function ( content ) {

										 },
										 error    : function ( jqXHR, textStatus, errorThrown ) {

											 // Log error if console is available
											 if ( window.console ) {
												 console.log( 'Pods AJAX View Error: ' + errorThrown + ' (' + additional_url + ')' );
											 }

										 }
									 } );
					}
				}
			}
		}

	},

	/**
	 * Load Pods AJAX View
	 *
	 * @param cache_key Cache key
	 * @param cache_mode Cache mode
	 * @param nonce Nonce
	 * @param uri Current URI
	 */
	load_view : function ( cache_key, cache_mode, nonce, uri ) {

		// Get view container(s)
		var $view_container = jQuery( 'div.pods-ajax-view-loader-' + nonce );

		// If view container found (and not already processed by another view in the queue)
		if ( $view_container.length || pods_ajax_views_config.is_admin ) {
			var pods_ajax_view_action = 'view';

			// Get current progress based on 0-100%
			var progress_value = ((Pods_AJAX_View_Processor.total - Pods_AJAX_View_Processor.queue.length) * 100) / Pods_AJAX_View_Processor.total;

			if ( pods_ajax_views_config.is_admin ) {
				pods_ajax_view_action = 'view_regenerate';

				// Only do special calculation for first run to indicate progress is happening
				if ( 0 === Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( 'value' ) ) {
					// Set value to x*2 because progress is 0-100% format, but progressbar is tracking 0-200 for pre/loaded indication
					// We use (x*2)-1 because we want to show the indication of it getting ready to load
					Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( {value : (Math.round( progress_value * 2 ) - 1)} );
				}
			}

			if ( '' === uri ) {
				uri = pods_ajax_views_config.ajax_url + '?action=pods_ajax_'.pods_ajax_view_action;
			}

			jQuery.ajax( {
							 type : 'POST', dataType : 'html', url : uri, cache : false, data : {
					pods_ajax_view_action : pods_ajax_view_action,
					pods_ajax_view_key    : cache_key,
					pods_ajax_view_mode   : cache_mode,
					pods_ajax_view_nonce  : nonce
				}, success        : function ( content ) {

					// Update progress indicator
					if ( pods_ajax_views_config.is_admin ) {
						Pods_AJAX_View_Processor.progress_indicator.progress.progressbar( {value : Math.round( progress_value * 2 )} );
					}
					// Replace temporary container with the real content
					else {
						$view_container.replaceWith( content );
					}

					// Trigger events for advanced functionality
					jQuery( document ).trigger( 'pods-ajax-view-loaded', [
						cache_key,
						cache_mode,
						content
					] );
					jQuery( document ).trigger( 'pods-ajax-view-loaded-' + cache_key + '-' + cache_mode, [
						cache_key,
						cache_mode,
						content
					] );

					// Process next view from the queue
					Pods_AJAX_View_Processor.process_next();

				}, error          : function ( jqXHR, textStatus, errorThrown ) {

					// Log error if console is available
					if ( window.console ) {
						console.log( 'Pods AJAX View Error: ' + errorThrown + ' (' + cache_key + ')' );
					}

					// Hide the container, subsequent containers can still be processed if successful
					$view_container.hide();

					// Process next view from the queue
					Pods_AJAX_View_Processor.process_next();

				}
						 } );
		}

	}

};

jQuery( function () {

	// Check if defined and has values
	if ( 'undefined' != typeof pods_ajax_views && {} != pods_ajax_views ) {
		// Send to queue
		Pods_AJAX_View_Processor.queue = Pods_AJAX_View_Processor.queue.concat( pods_ajax_views );
		Pods_AJAX_View_Processor.total = Pods_AJAX_View_Processor.queue.length;

		// Start processing
		Pods_AJAX_View_Processor.process_next();
	}

} );