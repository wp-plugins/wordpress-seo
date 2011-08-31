<?php 
/*
Plugin Name: WordPress SEO
Version: 0.4.3
Plugin URI: http://yoast.com/wordpress/seo/
Description: The first true all-in-one SEO solution for WordPress, including on-page content analysis, XML sitemaps and much more.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

if ( version_compare(PHP_VERSION, '5.2', '<') ) {
	if ( is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	    wp_die( __('WordPress SEO requires PHP 5.2 or higher, as will WordPress 3.2 and higher. The plugin has now disabled itself. For more info, <a href="http://yoast.com/requires-php-52/">see this post</a>.') );
	} else {
		return;
	}
}

define( 'WPSEO_VERSION', '0.4.3' );

$pluginurl = plugin_dir_url(__FILE__);
if ( preg_match( '/^https/', $pluginurl ) && !preg_match( '/^https/', get_bloginfo('url') ) )
	$pluginurl = preg_replace( '/^https/', 'http', $pluginurl );
define( 'WPSEO_FRONT_URL', $pluginurl );

define( 'WPSEO_URL', plugin_dir_url(__FILE__) );
define( 'WPSEO_PATH', plugin_dir_path(__FILE__) );
define( 'WPSEO_BASENAME', plugin_basename( __FILE__ ) );

require WPSEO_PATH.'inc/wpseo-functions.php';
require WPSEO_PATH.'inc/class-rewrite.php';
require WPSEO_PATH.'inc/class-widgets.php';
require WPSEO_PATH.'inc/class-sitemaps.php';

if ( !defined('DOING_AJAX') || !DOING_AJAX )
	require WPSEO_PATH.'inc/wpseo-non-ajax-functions.php';
	
$options = get_wpseo_options();

if ( is_admin() ) {
	require WPSEO_PATH.'admin/ajax.php';
	if ( !defined('DOING_AJAX') || !DOING_AJAX ) {
		require WPSEO_PATH.'admin/yst_plugin_tools.php';
		require WPSEO_PATH.'admin/class-config.php';
		require WPSEO_PATH.'admin/class-metabox.php';		
		require WPSEO_PATH.'admin/class-taxonomy.php';
		if ( isset( $options['opengraph'] )  && $options['opengraph'] )
			require WPSEO_PATH.'admin/class-opengraph-admin.php';
	}
} else {
	require WPSEO_PATH.'frontend/class-frontend.php';
	if ( isset($options['breadcrumbs-enable']) && $options['breadcrumbs-enable'] )
		require WPSEO_PATH.'frontend/class-breadcrumbs.php';
	if ( isset( $options['opengraph'] )  && $options['opengraph'] )
		require WPSEO_PATH.'frontend/class-opengraph.php';
}

// Load all extra modules
if ( !defined('DOING_AJAX') || !DOING_AJAX )
	wpseo_load_plugins( WP_PLUGIN_DIR.'/wordpress-seo-modules/' );

// Let's act as though this is AIOSEO so plugins and themes that act differently for that will fix do it for this plugin as well.
if ( !class_exists('All_in_One_SEO_Pack') ) {
	class All_in_One_SEO_Pack {
		function All_in_One_SEO_Pack() {
			return true;
		}
	}
}

function wpseo_maybe_upgrade() {
	$options = get_option( 'wpseo' );
	$current_version = isset($options['version']) ? $options['version'] : 0;
	if ( version_compare( $current_version, WPSEO_VERSION, '==' ) )
		return;

	// <= 0.3.5: flush rewrite rules for new XML sitemaps
	if ( $current_version == 0 ) {
		flush_rewrite_rules();
	}

	if ( version_compare( $current_version, '0.4.2', '<' ) ) {
		$xml_opt = array();
		// Move XML Sitemap settings from general array to XML specific array, general settings first
		foreach ( array('enablexmlsitemap', 'xml_include_images', 'xml_ping_google', 'xml_ping_bing', 'xml_ping_yahoo', 'xml_ping_ask', 'xmlnews_posttypes') as $opt ) {
			if ( isset( $options[$opt] ) ) {
				$xml_opt[$opt] = $options[$opt];
				unset( $options[$opt] );
			}
		}
		// Per post type settings
		foreach ( get_post_types() as $post_type ) {
			if ( in_array( $post_type, array('revision','nav_menu_item','attachment') ) ) 
				continue;

			if ( isset( $options['post_types-'.$post_type.'-not_in_sitemap'] ) ) {
				$xml_opt['post_types-'.$post_type.'-not_in_sitemap'] = $options['post_types-'.$post_type.'-not_in_sitemap'];
				unset( $options['post_types-'.$post_type.'-not_in_sitemap'] );
			}
		}
		// Per taxonomy settings
		foreach ( get_taxonomies() as $taxonomy ) {
			if ( in_array( $taxonomy, array('nav_menu','link_category','post_format') ) )
				continue;

			if ( isset( $options['taxonomies-'.$taxonomy.'-not_in_sitemap'] ) ) {
				$xml_opt['taxonomies-'.$taxonomy.'-not_in_sitemap'] = $options['taxonomies-'.$taxonomy.'-not_in_sitemap'];
				unset( $options['taxonomies-'.$taxonomy.'-not_in_sitemap'] );
			}
		}
		if ( get_option('wpseo_xml') === false )
			update_option( 'wpseo_xml', $xml_opt );
		unset( $xml_opt );

		// Clean up other no longer used settings
		unset( $options['wpseodir'], $options['wpseourl'] );
	}

	$options['version'] = WPSEO_VERSION;
	update_option( 'wpseo', $options );
}
add_action( 'admin_init', 'wpseo_maybe_upgrade' );
