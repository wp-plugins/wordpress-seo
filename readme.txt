=== WordPress SEO ===
Contributors: joostdevalk
Donate link: http://yoast.com/
Tags: seo, google, meta, meta description, search engine optimization, xml sitemaps, robots meta, rss footer
Requires at least: 3.0
Tested up to: 3.1

WordPress SEO is an all in one solution to search engine optimize your WordPress blog: SEO titles, meta descriptions, XML sitemaps & much more.

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
	* Pages or posts that have been noindexed will not show in XML sitemap (but can if you want them too).
* XML News Sitemaps.
* .htaccess and robots.txt editor.
* Basic import functionality for HeadSpace2 and All in One SEO.

== Installation ==

1. Upload the `plugin` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the `SEO` menu that appears in your admin menu

== Changelog ==

= 0.1.8 =

* Notice: The functionality in the post / page editor has changed quite a bit. Meta descriptions are now generated using the meta descriptions template if no meta description is entered, so it will for instance use the post excerpt, the SEO title is no longer filled automatically BUT it is properly shown in the snippet preview based on your title template. It should work faster, more intuitive and just better in general, but I do need your feedback, let me know if it's an improvement.
	
* Bugs fixed:
	* Fixed a notice for non existing metadesc.
	* Fixed several notices in title generation.
	* Directory paths in backend now properly recognized even when erroneously set to 1.
	* Fixed bug where frontpage title wouldn't be generated properly.
	* Made sure unzip of settings.zip (for settings import) works properly everywhere (by getting rid of `WP_Filesystem` and `unzip_file()`, as they do not work reliably).
	* Made sure meta descriptions are not shown on paged archives or homepages.
	
* Changes:
	* Admin:
		* Moved image used in news widget into images directory instead of loading from CDN to prevent https issues.
	* Breadcrumbs:
		* Creating proper breadcrumbs for daily archives now (linking back to month archives).
	* Post / Page edit box:
		* Meta description now properly generated using template for that particular post_type.
		* SEO Title is no longer auto filled, if you leave it empty "normal" title template generation is used.
		* Several improvements to javascripts.
	* Titles, Meta descriptions & Canonicals:
		* Speed up of variable replacement for titles and meta descriptions.
		* In fallback titles (when there's no template), plugin now sticks to `$sep` defined in `wp_title`.
		* Now properly generating canonical links for date archives.
		* The %%date%% variable now works properly on date archives too.
		* Added new filter to make title work properly on HeadWay 2.0.5 and up.
		* Fixed canonical and permalink redirection for paginated pages and posts (props to @rrolfe for finding the bug and coming up with first patch).
	* XML Sitemaps:
		* During sitemap generation, plugin now checks whether old sitemap.xml or sitemap.xml.gz files exist in root and deletes those if so.
		* Made including images optional.
		* Made it possible to pick which search engines to ping.
		* Fix in XSL path generation on HTTPS admin backends when frontend is normal HTTP.
		* XML Sitemap update on post publish now actually works properly.
		* No longer are XML Sitemaps enabled automatically when publishing a post (sorry about that).
	
= 0.1.7.1 =

* Apparently `is_network_admin()` didn't exist before WP 3.1. D0h!!!

= 0.1.7 =

* Bugs fixed:
	* Empty Home link when blog page is used and no settings have been set.
	* Fixed couple more notices (well, like, 10).
	* Bug in directory creation that would create the directory correctly but still throw an error and save the path wrongly to options.
	* Dismissing Blog public warning was only possible on SEO pages, now it's possible everywhere.
	* Excerpts, when used in description, are now properly sanitized from tags and shortcodes.
	* Properly fallback to `$wp_query->get_queried_object()` instead of `get_queried_object()` for < 3.1 installs.
	* Fixed several bugs in title generation, making it more stable and faster in the process.
	* Properly escape entities in page titles, both in front end and in posts overview.
	
* Changes:
	* Latest news from Yoast now appears on Network Admin too, and you can disable it there and on normal admin pages individually. First step towards getting a Multi Site Network Admin SEO page.
	* Added a "Re-test focus keyword" button for people using the Rich Text editor, which wasn't sending update events properly.
	
= 0.1.6 =

* New features:
	* Export & Import your WordPress Settings easily.
	* You can now supply extra variables to prevent from being cleaned out when clean permalinks is on.
	
* Bugs fixed:
	* No longer throw errors when wpseo dir cannot be created.
	* Your blog is not public warning can now be properly dismissed.
	* Fixed rewrite issues: apparently if you only load rewrite rules on the front-end, they don't get added when changing rewrites in the backend. D0h.
	* Rewrite rule for sitemap is now forced even harder when regenerating sitemap by hand.
	* Search permalinks now work properly, though in "old" ?s=query style, because of a bug in core. 
	* Breadcrumbs no longer errors when term that is supposed to show is empty.
	* Enabling breadcrumbs without setting any of the text fields no longer gives notices but proper defaults.
	* Proper fallback for get_term_title for pre WP 3.1 sites with custom taxonomies.
	
* Changes:
	* You can now dismiss settings advice.
	* You can now fix some of the settings advice just by clicking the button.
	* You can now make posts, pages and taxonomy terms of any kind always appear in sitemap even if they're noindex, or never, set on a piece by piece basis.
	* Permalink changes now invoke immediate XML sitemap update.
	* Added canonical url to the blog page if using a static page for front page (props [@rrolfe](http://twitter.com/rrolfe)).
	* Removing RSS feeds now actually works (props @rrolfe).
	* Added breadcrumb for 404 pages (props @rrolfe).
	* Drastically reduced memory usage during XML sitemap generation.
	
= 0.1.5 =

* Bugs fixed:
	* Duplicate noodp,noydir showing up in some occasions. Reworked most of robots meta output function.
	* Fixed couple more notices.
	* Trailing slash (when option set) now applied correctly in XML sitemap too.
	* Made sure regenerating sitemap worked again on post publish.
	* Force flush rewrite rules on activation / upgrade of plugin to make rewrite work.
	* Fixed empty RSS content bug caused in 0.1.4.
	
* Changes:
	* Removed part done quick edit functionality, will need to revisit once API improves.
	* Implemented a hook that would make the title work with Thematic based themes properly.
	* Added option to remove "start" rel link from head section.
	* Several style sheet changes to make backend styling easier and more robust.
	* Added option to force rewrite titles for people that can't adapt their theme.
	* If title templates aren't set, the plugin now generates proper default titles.
	* The News module has moved to a separate directory, where all other modules will reside too, so they can be updated individually later. Download link for the news module will appear on yoast.com shortly.
	
* Documentation:
	* Added Admin Only notice in HTML code when no meta description could be generated.
	* Added a donation box, I'll gladly take your money ;)
	
= 0.1.4 =

* Bugs fixed:
	* Fixed canonical for paginated archives of any kind when permalink structure doesn't end with /
	* Fixed permalink redirect for paginated archives of any kind when permalink structure doesn't end with /
	* Made sure blog shows up in breadcrumbs when you want it too.
	* Fixed small javascript notice for js/wp-seo-metabox.js
	* Rewrote parts of XML Sitemap generation so it's now fully compliant with MultiSite. You no longer have to choose paths for sitemaps, they'll all have fixed locations and using WP Rewrite will be placed in the correct positions, f.i. example.com/sitemap.xml.
	* Heavily reduced memory usage on admin pages.
	* Rewrote module structure and added some API's to be used in the modules.
	* Plugin now creates uploads/wpseo dir to store all files it creates and takes in.
	* Fixed several notices throughout the code.
	* Made sure SEO title in edit posts screen shows correct SEO Title.
	* Changed table sorting javascript for XSL's to Yoast CDN.

= 0.1.3 =

* Bugs fixed:
	* SEO Title no longer being overwritten when it's already set.
	* Titles for date archives work too now.
	* On initial page load or SEO title regeneration number of chars remaining updates properly.
	* Entities in titles and meta descriptions should now work correctly.
	* When editing SEO title snippet preview now correctly updates with focus keyword bolded.
	* Entities in XML sitemap should now show correctly.
	* When using %%excerpt%% in descriptions it now correctly is shortened to 155 chars.
	* Regenerating XML News sitemaps should no longer give a Fatal error but just work.
	* Focus keyword should now properly be recognized in slug even when slug is too long to display.
	* Breadcrumbs now show proper home link when showing blog link is disabled.
	* Non post singular pages (pages and custom post types) no longer show blog link in breadcrumb path.

* New features:
	* Added option to regenerate SEO title (just click the button).
	* Advanced button now looks cooler (hey even little changes deserve a changelog line!).
	* Now pinging Ask.com too for updated sitemaps.
	* Added plugin version number to "branding" comment to help in bug fixing.
	
= 0.1.2.1 =

* Added a missing ) to prevent death on install / going into wp-admin.

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

== Upgrade Notice ==

= 0.1.8 =
Several fixes to how SEO title is handled and generated to make editor faster and more intuitive.