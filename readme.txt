=== Pods AJAX Views ===
Contributors: sc0ttkclark
Donate link: http://podsfoundation.org/donate/
Tags: pods, ajax
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Utilize AJAX cache generation and loading for Pods Views. If a view takes too long to load, use Pods AJAX Views to offload it to AJAX so the rest of the page loads faster.

== Description ==

Pods AJAX Views offers an easy way to generate cached views from AJAX when they haven't been cached yet. It will serve straight from cache and output on the page if the view has already been generated.

This plugin requires the [Pods Framework](http://wordpress.org/plugins/pods/) version 2.4 or later to run.

= Why AJAX Views? =

If you are using pods_view to cache template output, you're already on the right track to improving performance of your site through it's powerful Partial Page Caching. However, what if you have a complicated view that may take a few seconds to generate? What if you have a few of those views on the same page?

On hosts like WPEngine, there's a strict 30 second limit and then a 502 server error is sent to the visitor. Waiting a long time for a page to load, or especially being sent a server error like that just isn't acceptable for visitors and they may choose to bail on your site.

Pods AJAX Views takes those complicated views and lets you off-load them into a separate asynchronous AJAX call that allows the rest of the page to be built and sent to the browser. When the AJAX request runs, the view is cached like normal, so subsequent calls to the Pods AJAX View code will produce the exact same result as a default pods_view would.


== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Contributors ==

Check out our GitHub for a list of contributors, or search our GitHub issues to see everyone involved in adding features, fixing bugs, or reporting issues/testing.

[github.com/pods-framework/pods-alternative-cache/graphs/contributors](https://github.com/pods-framework/pods-alternative-cache/graphs/contributors)


== Changelog ==

= 1.0 - June 16, 2014 =
* First official release!
* Found a bug? Have a great feature idea? Get on GitHub and tell us about it and we'll get right on it: [github.com/pods-framework/pods-ajax-views/issues/new](https://github.com/pods-framework/pods-ajax-views/issues/new)
