<?php

function yoast_get_value( $val, $postid = '' ) {
	if ( empty($postid) ) {
		global $post;
		if (isset($post))
			$postid = $post->ID;
		else 
			return false;
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

function get_wpseo_options_arr() {
	$optarr = array('wpseo', 'wpseo_indexation', 'wpseo_permalinks', 'wpseo_titles', 'wpseo_rss', 'wpseo_internallinks');
	return apply_filters( 'wpseo_options', $optarr );
}

function get_wpseo_options() {
	$options = array();
	foreach( get_wpseo_options_arr() as $opt ) {
		$options = array_merge( $options, (array) get_option($opt) );
	}
	return $options;
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
		'%%excerpt%%'				=> !empty($r['post_excerpt']) ? $r['post_excerpt'] : substr(wp_trim_excerpt($r['post_content']), 0, 155),
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

function wpseo_get_term_meta( $term, $taxonomy, $meta ) {
	if ( is_string( $term ) ) 
		$term = get_term_by('slug', $term, $taxonomy);

	if ( is_object( $term ) )
		$term = $term->term_id;
	
	$tax_meta = get_option( 'wpseo_taxonomy_meta' );

	return (isset($tax_meta[$taxonomy][$term][$meta])) ? $tax_meta[$taxonomy][$term][$meta] : false;
}

function wpseo_dir_setup() {
	$options = get_option('wpseo');
	
	if ( isset( $options['wpseodir'] ) && $options['wpseodir'] ) {
		$wpseodir = $options['wpseodir'];
		$wpseourl = $options['wpseourl'];
	} else {
		$dir = wp_upload_dir();
		if ( is_wp_error($dir) ) {
			$wpseodir = false;
		} else if ( !file_exists( $dir['basedir'].'/wpseo/' ) ) {
			$wpseodir = @mkdir( $dir['basedir'].'/wpseo/' );
			if ( $wpseodir ) {
				$stat = @stat( dirname( $wpseodir ) );
				$dir_perms = $stat['mode'] & 0007777;
				@chmod( dirname( $wpseodir ), $dir_perms );
				$options['wpseodir'] = $wpseodir;
				$wpseourl = $options['wpseourl'] = $dir['baseurl'].'/wpseo/';
				update_option( 'wpseo' , $options );
			}
		} else {
			$wpseodir = $options['wpseodir'] = $dir['basedir'].'/wpseo/';
			$wpseourl = $options['wpseourl'] = $dir['baseurl'].'/wpseo/';
			update_option('wpseo', $options);
		}
	}

	if ( $wpseodir && @is_writable( $wpseodir ) ) {
		define( 'WPSEO_UPLOAD_DIR', $wpseodir );
		define( 'WPSEO_UPLOAD_URL', $wpseourl );
	} else {
		define( 'WPSEO_UPLOAD_DIR', false );
		define( 'WPSEO_UPLOAD_NOTDIR', $wpseodir );
	}
}