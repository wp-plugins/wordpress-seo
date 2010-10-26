<?php

function yoast_get_value( $val, $postid = '' ) {
	if ( empty($postid) ) {
		global $post;
		$postid = $post->ID;
	}
	$custom = get_post_custom($postid);
	if (!empty($custom['_yoast_wpseo_'.$val][0]))
		return maybe_unserialize( $custom['_yoast_wpseo_'.$val][0] );
	else
		return false;
}

function yoast_set_value( $meta, $val, $postid ) {
	$oldmeta = get_post_meta($postid, '_yoast_wpseo_'.$meta, true);
	if (!empty($oldmeta)) {
		delete_post_meta($postid, '_yoast_wpseo_'.$meta, $oldmeta );
	}
	add_post_meta($postid, '_yoast_wpseo_'.$meta, $val, true);
}

function yoast_wpseo_value( $val, $filter = false ) {
	$val = yoast_get_value( $val );
	if ( $filter )
		$val = apply_filters('the_content',$val);
	echo $val;
}

function get_wpseo_options() {
	return array_merge(
		(array) get_option('wpseo'),
		(array) get_option('wpseo_indexation'),
		(array) get_option('wpseo_permalinks'),
		(array) get_option('wpseo_titles'),
		(array) get_option('wpseo_rss'),
		(array) get_option('wpseo_internallinks')
	);	
}

function wpseo_replace_vars($string, $args) {
	global $wp_query;
	
	$defaults = array(
		'ID' => '',
		'name' => '',
		'post_author' => '',
		'post_content' => '',
		'post_date' => '',
		'post_excerpt' => '',
		'post_modified' => '',
		'post_title' => '',
		'taxonomy' => '',
	);
	
	$pagenum = get_query_var('paged');
	if ($pagenum === 0) {
		if ($wp_query->max_num_pages > 1)
			$pagenum = 1;
		else
			$pagenum = '';
	}
	
	$r = wp_parse_args($args, $defaults);
	
	$replacements = array(
		'%%date%%' 					=> $r['post_date'],
		'%%title%%'					=> stripslashes($r['post_title']),
		'%%sitename%%'				=> get_bloginfo('name'),
		'%%sitedesc%%'				=> get_bloginfo('description'),
		'%%excerpt%%'				=> !empty($r['post_excerpt']) ? apply_filters('get_the_excerpt', $r['post_excerpt']) : substr(wp_trim_excerpt($r['post_content']), 0, 155),
		'%%excerpt_only%%'			=> $r['post_excerpt'],
		'%%category%%'				=> ( get_the_category_list('','',$r['ID']) != '' ) ? get_the_category_list('','',$r['ID']) : $r['name'],
		'%%category_description%%'	=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%tag_description%%'		=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%term_description%%'		=> !empty($r['taxonomy']) ? trim(strip_tags(get_term_field( 'description', $r['term_id'], $r['taxonomy'] ))) : '',
		'%%term_title%%'			=> $r['name'],
		'%%tag%%'					=> $r['name'],
		'%%modified%%'				=> $r['post_modified'],
		'%%id%%'					=> $r['ID'],
		'%%name%%'					=> get_the_author_meta('display_name', !empty($r['post_author']) ? $r['post_author'] : get_query_var('author')),
		'%%userid%%'				=> !empty($r['post_author']) ? $r['post_author'] : get_query_var('author'),
		'%%searchphrase%%'			=> esc_html(get_query_var('s')),
		'%%currenttime%%'			=> date('H:i'),
		'%%currentdate%%'			=> date('M jS Y'),
		'%%currentmonth%%'			=> date('F'),
		'%%currentyear%%'			=> date('Y'),
		'%%page%%'		 			=> ( get_query_var('paged') != 0 ) ? 'Page '.get_query_var('paged').' of '.$wp_query->max_num_pages : '', 
		'%%pagetotal%%'	 			=> ( $wp_query->max_num_pages > 1 ) ? $wp_query->max_num_pages : '', 
		'%%pagenumber%%' 			=> $pagenum,
		'%%caption%%'				=> $r['post_excerpt'],
	);
	
	foreach ($replacements as $var => $repl) {
		$string = str_replace($var, $repl, $string);
	}
	
	return $string;
}

// Don't do plugin update checks just yet, for this plugin that is.
function wpseo_hide_plugin( $r, $url ) {
	if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
		return $r; // Not a plugin update request. Bail immediately.
	$plugins = unserialize( $r['body']['plugins'] );
	unset( $plugins->plugins[ WPSEO_BASENAME ] );
	unset( $plugins->active[ array_search( WPSEO_BASENAME, $plugins->active ) ] );
	$r['body']['plugins'] = serialize( $plugins );
	return $r;
}
add_filter( 'http_request_args', 'wpseo_hide_plugin', 5, 2 );

function wpseo_get_term_meta( $term, $taxonomy, $meta ) {
	if ( is_string( $term ) ) 
		$term = get_term_by('slug', $term, $taxonomy);

	if ( is_object( $term ) )
		$term = $term->term_id;
	
	$tax_meta = get_option( 'wpseo_taxonomy_meta' );

	return (isset($tax_meta[$taxonomy][$term][$meta])) ? $tax_meta[$taxonomy][$term][$meta] : false;
}

function wpseo_load_plugins( $path ) {
	$dir = @opendir( WPSEO_PATH . $path );
	if ($dir) {
		while (($entry = @readdir($dir)) !== false) {
			if (strrchr($entry, '.') === '.php') {
				require_once WPSEO_PATH. $path . $entry;
			}
		}
		@closedir($dir);
	}
}

?>