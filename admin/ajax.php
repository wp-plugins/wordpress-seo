<?php

function wpseo_set_option() {
	update_option($_POST['option'], $_POST['newval']);
	return 1;
	die();
}
add_action('wp_ajax_wpseo_set_option', 'wpseo_set_option');

function wpseo_set_ignore() {
	$options = get_option('wpseo');
	$options['ignore_'.$_POST['option']] = 'ignore';
	update_option('wpseo', $options);
	return 1;
	die();
}
add_action('wp_ajax_wpseo_set_ignore', 'wpseo_set_ignore');

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
		
		$mem_before = memory_get_peak_usage() / 1024;
		require_once WPSEO_PATH.'/sitemaps/xml-sitemap-class.php';
		$mem_after = memory_get_peak_usage() / 1024;
		echo number_format($mem_after - $mem_before).'KB of memory used.';

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