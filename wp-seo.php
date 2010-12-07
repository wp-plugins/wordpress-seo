<?php 
/*
Plugin Name: WordPress SEO
Version: 0.2.1
Plugin URI: http://yoast.com/wordpress/seo/
Description: The first true all-in-one SEO solution for WordPress.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

$pluginurl = plugin_dir_url(__FILE__);
if ( preg_match( '/^https/', $pluginurl ) && !preg_match( '/^https/', get_bloginfo('url') ) )
	$pluginurl = preg_replace( '/^https/', 'http', $pluginurl );
define( 'WPSEO_FRONT_URL', $pluginurl );

define( 'WPSEO_URL', plugin_dir_url(__FILE__) );
define( 'WPSEO_PATH', plugin_dir_path(__FILE__) );
define( 'WPSEO_BASENAME', plugin_basename( __FILE__ ) );

define( 'WPSEO_VERSION', '0.2.1' );

require_once 'inc/wpseo-functions.php';
require_once 'inc/class-rewrite.php';
require_once 'inc/class-widgets.php';

if ( !defined('DOING_AJAX') || !DOING_AJAX )
	require_once 'inc/wpseo-non-ajax-functions.php';
	
$options = get_wpseo_options();

wpseo_dir_setup();

if ( is_admin() ) {
	require_once 'admin/ajax.php';
	if ( !defined('DOING_AJAX') || !DOING_AJAX ) {
		require_once 'admin/yst_plugin_tools.php';
		require_once 'admin/class-config.php';
		require_once 'admin/class-metabox.php';		
		require_once 'admin/class-taxonomy.php';
	}
} else {
	require_once 'frontend/class-frontend.php';
	if ( isset($options['breadcrumbs-enable']) && $options['breadcrumbs-enable'] ) {
		require_once 'frontend/class-breadcrumbs.php';
	}
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
