<?php

function wpseo_hide_blog_public_warning() {
	$options = get_option('wpseo');
	$options['blog_public_warning'] = 'nolonger';
	update_option('wpseo', $options);
	echo 'nolonger';
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
	$p['post_title'] = stripslashes($_POST['curtitle']);
	if ($options['title-'.$p['post_type']] != '')
		echo wpseo_replace_vars($options['title-'.$p['post_type']], $p );
	else
		echo $p['post_title'] . ' - ' .get_bloginfo('name'); 
	die();
}
add_action('wp_ajax_wpseo_autogen_title', 'wpseo_autogen_title_callback');

function wpseo_test_sitemap_callback($return = false, $type = '') {	
	if (empty($type) && isset($_POST['type']))
		$type = $_POST['type'];
		
	$options = get_wpseo_options();
	if (isset($_POST['sitemappath']) && !empty($_POST['sitemappath'])) {
		$fpath 	= $_POST['sitemappath'];
		$url	= $_POST['sitemapurl'];
	} else {
		$fpath 	= $options[$type.'sitemappath'];
		$url 	= $options[$type.'sitemapurl'];
	}
	
	$type = ucfirst($type).' ';

	$output = '';
	if (file_exists($fpath)) {
		if (is_writable($fpath)) {
			$output .= '<div class="correct">XML '.$type.'Sitemap file found and writable.</div>';
			if (file_exists($fpath.'.gz') && !is_writable($fpath)) {
				$output .= '<div class="wrong">XML '.$type.'Sitemap GZ file found but not writable, please make it writable!</div>';
			}
		} else {
			$output .= '<div class="wrong">XML '.$type.'Sitemap file found but not writable, please make it writable!</div>';
		}
	} else {
		if ( @touch($fpath) ) {
			touch($fpath.'.gz');
			$output .= '<div class="correct">XML '.$type.'Sitemap file created (but still empty).</div>';
		} else {
			$output .= '<div class="wrong">XML '.$type.'Sitemap file not found and it could not be created, is the directory correct? And is it writable?</div>';
		}
	}
	$output .= '<br/>';

	$resp = wp_remote_get($url);
		
	if ( is_array($resp) && $resp['response']['code'] == 200 )
		$output .= '<div class="correct">XML '.$type.'Sitemap URL correct.</div>';
	else
		$output .= '<div class="wrong">XML '.$type.'Sitemap URL could not be verified, please make sure it\'s correct.</div>';
	if ($return)
		return $output;
	else
		echo $output;
	die();
}
add_action('wp_ajax_wpseo_test_sitemap', 'wpseo_test_sitemap_callback');

function wpseo_ajax_generate_sitemap_callback() {
	$options = get_option('wpseo');
	$type = (isset($_POST['type'])) ? $_POST['type'] : '';

	$options[$type.'sitemappath'] = $_POST['sitemappath'];
	$options[$type.'sitemapurl'] = $_POST['sitemapurl'];
	update_option('wpseo',$options);
	
	global $wpseo_echo;
	
	$wpseo_echo = true;
	
	if (!empty($type))
		$type .= '-';
	
	if ($type == '')
		require_once WPSEO_PATH.'/sitemaps/xml-sitemap-class.php';
	else
		require_once WPSEO_PATH.'/modules/admin/xml-'.$type.'sitemap-class.php';
		
	die();
}
add_action('wp_ajax_wpseo_generate_sitemap', 'wpseo_ajax_generate_sitemap_callback');
