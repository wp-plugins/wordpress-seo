<?php 
/*
Plugin Name: WordPress SEO
Version: 0.1.3
Plugin URI: http://yoast.com/wordpress/seo/
Description: The first true all-in-one SEO solution for WordPress.
Author: Joost de Valk
Author URI: http://yoast.com/
*/

define('WPSEO_URL', plugin_dir_url(__FILE__));
define('WPSEO_PATH', plugin_dir_path(__FILE__));
define('WPSEO_BASENAME', plugin_basename( __FILE__ ));

define('WPSEO_VERSION', '0.1.3');

require_once 'inc/wpseo-functions.php';
$options = get_wpseo_options();

if (is_admin()) {
	require_once 'admin/ajax.php';
	if (!defined('DOING_AJAX')) {
		wpseo_load_plugins('modules/admin/');
		
		require_once 'admin/yst_plugin_tools.php';
		require_once 'admin/class-config.php';
		require_once 'admin/class-metabox.php';		
		require_once 'admin/class-taxonomy.php';
	}
} else {
	wpseo_load_plugins('modules/');
	
	require_once 'frontend/class-frontend.php';
	// require_once 'sitemaps/feed-class.php';
	
	if ( isset($options['breadcrumbs-enable']) && $options['breadcrumbs-enable'] )
		require_once 'frontend/class-breadcrumbs.php';
}

if ( !class_exists('All_in_One_SEO_Pack') ) {
	class All_in_One_SEO_Pack {
		function All_in_One_SEO_Pack() {
			return true;
		}
	}
}
?>