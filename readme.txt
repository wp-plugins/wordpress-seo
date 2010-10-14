=== WordPress SEO ===
Contributors: joostdevalk
Donate link: http://yoast.com/
Tags: seo, google, meta, meta description, search engine optimization, xml sitemaps, robots meta, rss footer
Requires at least: 3.0
Tested up to: 3.0.1

WordPress SEO is an all in one solution to search engine optimize your WordPress blog, featuring titles, meta descriptions, optimized breadcrumbs, XML sitemaps, robots meta settings and much much more.

== Description ==

The most complete all in one SEO solution for your WordPress blog, this plugin has a huge list of features, including:

* Post title and meta description meta box to change these on a per post basis.
* Taxonomy (tag, category & custom taxonomy) title and meta description support.
* Google search result snippet previews.
* Focus keyword testing.
* Meta Robots configuration:
	* Easily add noodp, noydir meta tags.
	* Easily noindex, or nofollow pages, taxonomies or entire archives.
* Improved canonical support, adding canonical to taxonomy archives, single posts and pages and the front page.
* RSS footer / header configuration.
* Permalink clean ups, while still allowing for, for instance, Google Custom Search.
* Breadcrumbs support, with configurable breadcrumbs titles.
* XML Sitemaps with:
 	* Images
	* Configurable removal of post types and taxonomies
	* Pages or posts that have been noindexed will not show in XML sitemap.
* XML News Sitemaps.
* .htaccess and robots.txt editor.
* Basic import functionality for HeadSpace2 and All in One SEO.

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the `SEO` menu that appears in your admin menu

== Changelog ==

= 0.1.2 =

* Bugs fixed:
	* Non ASCII characaters should now display properly.
	* Google News Module: added input field for Google News publication name, as this has to match how Google has you on file.
	* Stripped tags out of title and meta description output when using, f.i., excerpts in template.
	* Meta description now updates in snippet preview as well when post content changes and no meta description has been set yet.
	* Meta description generated from post content now searches ahead to focus keyword and bolds it.
	* Meta description should now show properly on blog pages when blog page is not site homepage.
	* Alt or title for previous image could show up in image sitemap when one image didn't have that attribute.
	* Prevented fatal error on remote_get of XML sitemap in admin/ajax.php.
	* When there's a blog in / and in /example/ file editor should now properly get robots.txt and .htaccess from /example/ and not /.
	* Reference to wrongly named yoast_breadcrumb_output fixed, should fix auto insertion of breadcrumbs in supported theme frameworks.
	* Prevented error when yoast.com/feed/ doesn't work.
	* Fixed several notices for unset variables.
	* Added get text calls in several places to allow localization.

* (Inline) Documentation fixes:	
	* Exclusion list in XML sitemap box for post types now shows proper label instead of internal name.
	* Exclusion list in XML sitemap box for custom taxonomies now shows plural instead of singular form.
	* Added explanation on how to add breadcrumbs to your theme, as well as link to more explanatory page.
	
* Changes:
	* Links to Webmaster Tools etc. now open in new window.
	* Heavily simplified the javascript used for snippet preview, removing HTML5 placeholder code and instead inserting the title into the input straight away. Lot faster this way.
	* Removed Anchor text for the blog page option from breadcrumbs section as you can simply set a breadcrumbs title on the blog page itself.
	* Added option to always remove the Blog page from the breadcrumb.

= 0.1.1 =

* Bugs fixed:
	* Double comma in robots meta values, as well as index,follow in robots meta.
	* Oddities with categories in breadcrumbs fixed.
	* If complete meta value for SE is entered it's now properly stripped, preventing /> from showing up in your page.
	* Category meta description now shows properly when using category description template.
	* Removed Hybrid breadcrumb in favor of Yoast breadcrumb when automatically adding breadcrumb to Hybrid based themes.
	* First stab at fixing trailing slashed URL's in XML sitemaps.
	* Made %%page%% also work on page 1 of a result set.
	* Fixed design of broken feed error.
	* Made sure %%tag%% works too in title templates.
	
* (Inline) Documentation fixes:	
	* Added this readme.txt file.
	* MS Webmaster Central renamed to Bing Webmaster Tools.
	* Added links to Bing Webmaster Tools and Yahoo! Site explorer to meta values box, as well as an explanation that you do not need to use those values if your site is already verified.
	* Changed wording on description of clean permalinks.
	* Added line explaining that SEO title overwrites the SEO title template.
	* Added line telling to save taxonomy and post_type excludes before rebuilding XML sitemap.
	
* Changes:
	* Changed robots meta noindex and nofollow storage for pages to boolean on noindex and nofollow, please check when upgrading.
	* Now purging W3TC Object Cache when saving taxonomy meta data to make sure new settings are immediately reflected in output.
	* Namespaced all menu items to prevent collissions with other plugins.
	* Several code optimizations in admin panels.
	* Huge code optimizations in breadcrumbs generation and permalink clean up.
	* Permalink cleaning now works for taxonomies too.
	* Faked All in One SEO class to make plugin work with themes that check for that.
	
* New features:
	* Noindex and nofollow options for taxonomies (noindexing a term automatically removes it from XML sitemap too).
	* Editable canonicals for taxonomies.
	* Completed module functionality, using the XML News sitemap as first module.
	* Added experimental "Find related keywords" feature that'll return keywords that are related to your focus keyword.
	
* Issues currently in progress:
	* WPML compatibility.
	* XML Sitemap errors in Bing Webmaster Tools (due to use of "caption" for images).
	

= 0.1 =

* Initial beta release.

