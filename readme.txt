=== Pods AJAX Views ===
Contributors: sc0ttkclark
Donate link: http://podsfoundation.org/donate/
Tags: pods, ajax
Requires at least: 3.9
Tested up to: 5.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Utilize AJAX cache generation and loading for Pods Views. If a view takes too long to load, use Pods AJAX Views to offload it to AJAX so the rest of the page loads faster.

== Description ==

Pods AJAX Views offers an easy way to generate cached views from AJAX when they haven't been cached yet. It will serve straight from cache and output on the page if the view has already been generated.

This plugin requires the [Pods Framework](http://wordpress.org/plugins/pods/) version 2.4.1 or later to run.

= Why AJAX Views? =

If you are using `pods_view` to cache template output, you're already on the right track to improving performance of your site through it's powerful Partial Page Caching. However, what if you have a complicated view that may take a few seconds to generate? What if you have a few of those views on the same page?

On hosts like WPEngine, there's a strict 30 second limit and then a 502 server error is sent to the visitor. Waiting a long time for a page to load, or especially being sent a server error like that just isn't acceptable for visitors and they may choose to bail on your site.

Pods AJAX Views takes those complicated views and lets you off-load them into a separate asynchronous AJAX call that allows the rest of the page to be built and sent to the browser. When the AJAX request runs, the view is cached like normal, so subsequent calls to the Pods AJAX View code will produce the exact same result as a default `pods_view` would.

= Usage =

Use the same way as `pods_view`, it accepts the same arguments, except one additional argument `$forced_regenerate` at the end which can be set to true (default: false) that will force the view to be deleted from cache and reloaded.

`pods_ajax_view( 'my-big-cached-template.php' );`

AJAX requests are done through the same URL, so you still have access to the query and postdata like normal. We hook into template_redirect to stop Beware that AJAXed views are loaded from AJAX, not through the current page. They do not have access to the current WP_Query or Query variables, or the Post loop. If you want to pass anything into it, use the $data argument.

For information about `pods_view`, see these resources:

* [Partial Page Caching and Smart Template Parts with Pods](http://pods.io/tutorials/partial-page-caching-smart-template-parts-pods/)
* [Code Reference: pods_view](http://pods.io/docs/code/pods-view/)

= Available Constants =

* `define( 'PODS_AJAX_VIEWS_STATS', true )` - Creates a table for Stats tracking and regeneration ability; Must be enabled before activating, if you enable it, just deactivate / activate again
* `define( 'PODS_AJAX_VIEWS_OVERRIDE', true )` - Overrides all pods_view() calls in the theme and turns them into AJAX views, even if they aren't set to cache (it'll load the non-cached version via AJAX)
* `define( 'PODS_AJAX_VIEWS_API_KEY', 'abcdefghijk' )` - This should be highly randomized, it's the API key used to access the sitemap at yoursite.com/?pods_ajax_view_action=view_sitemap&pods_ajax_view_api_key=XXXX which must be accessed through the user (or anon) the regeneration will be run as because the URLs are specific to the user and have nonces on them; You can use this with a plugin like Warm Cache to keep your views fresh and always generated
* `define( 'PODS_AJAX_VIEWS_SITEMAP_LIMIT', -1 )` - You can override how many sitemap items will show, default is 250

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Screenshots ==
1. Manage page.
2. Regnerating views from manage page.

== Contributors ==

Check out our GitHub for a list of contributors, or search our GitHub issues to see everyone involved in adding features, fixing bugs, or reporting issues/testing.

[github.com/pods-framework/pods-ajax-views/graphs/contributors](https://github.com/pods-framework/pods-ajax-views/graphs/contributors)


== Changelog ==

= 1.0 - June 19th, 2014 =
* First official release!
* Found a bug? Have a great feature idea? Get on GitHub and tell us about it and we'll get right on it: [github.com/pods-framework/pods-ajax-views/issues/new](https://github.com/pods-framework/pods-ajax-views/issues/new)
