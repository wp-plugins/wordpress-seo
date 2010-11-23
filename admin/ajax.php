<?php

function wpseo_hide_blog_public_warning() {
	$options = get_option('wpseo');
	$options['blog_public_warning'] = 'nolonger';
	update_option('wpseo', $options);
	die();
}
add_action('wp_ajax_wpseo_hide_blog_public_warning', 'wpseo_hide_blog_public_warning');

function wpseo_set_option() {
	$option = $_POST['option'];
	$newval = $_POST['newval'];
	
	update_option($option, $newval);
	return 1;
	die();
}

function wpseo_autogen_title_callback() {
	$options = get_wpseo_options();
	$p = get_post($_POST['postid'], ARRAY_A);
	$p['post_title'] = trim(stripslashes($_POST['curtitle']));
	if ( empty($p['post_title']) )
		die();
	if ( isset($options['title-'.$p['post_type']]) && $options['title-'.$p['post_type']] != '' )
		echo wpseo_replace_vars($options['title-'.$p['post_type']], $p );
	else
		echo $p['post_title'] . ' - ' .get_bloginfo('name'); 
	die();
}
add_action('wp_ajax_wpseo_autogen_title', 'wpseo_autogen_title_callback');

function wpseo_ajax_generate_sitemap_callback() {
	$options = get_option('wpseo');
	$type = (isset($_POST['type'])) ? $_POST['type'] : '';
	
	if ($type == '') {
		global $wpseo_generate, $wpseo_echo;
		$wpseo_generate = true;
		$wpseo_echo = true;
		require_once WPSEO_PATH.'/sitemaps/xml-sitemap-class.php';
	} else {
		global $wpseo_generate, $wpseo_echo;
		$wpseo_generate = true;
		$module_name = $type;
		if($type == 'kml' || $type == 'geo') {
			$module_name = 'local';
			$type = 'geo';
		}
		require_once WP_PLUGIN_DIR.'/wordpress-seo-modules/wpseo-' . $module_name . '/xml-' . $type . '-sitemap-class.php';
	}	
	die();
}
add_action('wp_ajax_wpseo_generate_sitemap', 'wpseo_ajax_generate_sitemap_callback');
