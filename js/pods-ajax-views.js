jQuery( function() {

	// Check if defined and has values
	if ( 'undefined' != typeof pods_ajax_views && {} != pods_ajax_views ) {
		// Send to queue
		Pods_AJAX_View_Processor.queue = Pods_AJAX_View_Processor.queue.concat( pods_ajax_views );

		// Start processing
		Pods_AJAX_View_Processor.process_next();
	}

} );

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
	 * Process the next view from the queue
	 */
	process_next : function() {

		// Check if there are views in the queue
		if ( queue.length ) {
			// Get the next view in line
			view = queue.shift();

			// Check for valid view data, then load the view
			if ( 'undefined' != typeof view.cache_key && 'undefined' != typeof view.cache_mode && 'undefined' != typeof view.nonce ) {
				this.load_view( view.cache_key, view.cache_mode, view.nonce );
			}
			// If invalid, process next view from the queue
			else {
				this.process_next();
			}
		}

	},

	/**
	 * Load Pods AJAX View
	 *
	 * @param cache_key
	 * @param cache_mode
	 * @param nonce
	 */
	load_view : function( cache_key, cache_mode, nonce ) {

		// Get view container(s)
		var $view_container = jQuery( 'div.' + nonce );

		// If view container found (and not already processed by another view in the queue)
		if ( $view_container.length ) {
			jQuery.ajax( {
				type : 'POST',
				dataType : 'html',
				url : pods_ajax_views_config.ajax_url + '?action=pods_ajax_view',
				cache : false,
				data : {
					pods_ajax_view_key : cache_key,
					pods_ajax_view_mode : cache_mode,
					pods_ajax_view_nonce : nonce
				},
				success : function ( content ) {

					// Replace temporary container with the real content
					$view_container.replaceWith( content );

					// Process next view from the queue
					Pods_AJAX_View_Processor.process_next();

				},
				error : function ( jqXHR, textStatus, errorThrown ) {

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