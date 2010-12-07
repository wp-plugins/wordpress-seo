<?php

function wpseo_get_value( $val, $postid = '' ) {
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

function wpseo_set_value( $meta, $val, $postid ) {
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
	
	$args = (array) $args;
	
	// Let's see if we can bail super early.
	if ( strpos( $string, '%%' ) === false )
		return trim( preg_replace('/\s+/',' ', $string) );

	$simple_replacements = array(
		'%%sitename%%'				=> get_bloginfo('name'),
		'%%sitedesc%%'				=> get_bloginfo('description'),
		'%%currenttime%%'			=> date('H:i'),
		'%%currentdate%%'			=> date('M jS Y'),
		'%%currentmonth%%'			=> date('F'),
		'%%currentyear%%'			=> date('Y'),
	);

	foreach ($simple_replacements as $var => $repl) {
		$string = str_replace($var, $repl, $string);
	}
	
	// Let's see if we can bail early.
	if ( strpos( $string, '%%' ) === false )
		return trim( preg_replace('/\s+/',' ', $string) );

	global $wp_query;
	
	$defaults = array(
		'ID' => '',
		'name' => '',
		'post_author' => '',
		'post_content' => '',
		'post_date' => '',
		'post_content' => '',
		'post_excerpt' => '',
		'post_modified' => '',
		'post_title' => '',
		'taxonomy' => '',
		'term_id' => '',
	);
	
	$pagenum = get_query_var('paged');
	if ($pagenum === 0) {
		if ($wp_query->max_num_pages > 1)
			$pagenum = 1;
		else
			$pagenum = '';
	}

	// Strip out the shortcodes with a filthy regex, because people don't properly register their shortcodes.
	$args['post_content'] = preg_replace('/(.?)\[([a-zA-Z_-]+)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s', '$1$6', $args['post_content'] );
	$args['post_excerpt'] = preg_replace('/(.?)\[([a-zA-Z_-]+)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s', '$1$6', $args['post_excerpt'] );
		
	$r = (object) wp_parse_args($args, $defaults);

	// Only global $post on single's, otherwise some expressions will return wrong results.
	if ( is_singular() || ( is_front_page() && 'posts' != get_option('show_on_front') ) ) {
		global $post;
	}
	
	// Let's do date first as it's a bit more work to get right.
	if ( $r->post_date != '' ) {
		$date = mysql2date( get_option('date_format'), $r->post_date );
	} else {
		if ( get_query_var('day') && get_query_var('day') != '' ) {
			$date = get_the_date();
		} else {
			if ( single_month_title(' ', false) && single_month_title(' ', false) != '' ) {
				$date = single_month_title(' ', false);
			} else if ( get_query_var('year') != '' ){
				$date = get_query_var('year');
			} else {
				$date = '';
			}
		}
	}
	
	$replacements = array(
		'%%date%%'					=> $date,
		'%%title%%'					=> stripslashes( $r->post_title ),
		'%%excerpt%%'				=> ( !empty($r->post_excerpt) ) ? strip_tags( $r->post_excerpt ) : substr( strip_shortcodes( strip_tags( $r->post_content ) ), 0, 155 ),
		'%%excerpt_only%%'			=> strip_tags( $r->post_excerpt ),
		'%%category%%'				=> wpseo_get_terms($r->ID, 'category'),
		'%%category_description%%'	=> !empty($r->taxonomy) ? trim(strip_tags(get_term_field( 'description', $r->term_id, $r->taxonomy ))) : '',
		'%%tag_description%%'		=> !empty($r->taxonomy) ? trim(strip_tags(get_term_field( 'description', $r->term_id, $r->taxonomy ))) : '',
		'%%term_description%%'		=> !empty($r->taxonomy) ? trim(strip_tags(get_term_field( 'description', $r->term_id, $r->taxonomy ))) : '',
		'%%term_title%%'			=> $r->name,
		'%%focuskw%%'				=> wpseo_get_value('focuskw', $r->ID),
		'%%tag%%'					=> wpseo_get_terms($r->ID, 'post_tag'),
		'%%modified%%'				=> mysql2date( get_option('date_format'), $r->post_modified ),
		'%%id%%'					=> $r->ID,
		'%%name%%'					=> get_the_author_meta('display_name', !empty($r->post_author) ? $r->post_author : get_query_var('author')),
		'%%userid%%'				=> !empty($r->post_author) ? $r->post_author : get_query_var('author'),
		'%%searchphrase%%'			=> esc_html(get_query_var('s')),
		'%%page%%'		 			=> ( get_query_var('paged') != 0 ) ? 'Page '.get_query_var('paged').' of '.$wp_query->max_num_pages : '', 
		'%%pagetotal%%'	 			=> ( $wp_query->max_num_pages > 1 ) ? $wp_query->max_num_pages : '', 
		'%%pagenumber%%' 			=> $pagenum,
		'%%caption%%'				=> $r->post_excerpt,
	);
	
	foreach ($replacements as $var => $repl) {
		$string = str_replace($var, $repl, $string);
	}
	
	$string = preg_replace( '/\s\s+/',' ', $string );
	return trim( $string );
}

function wpseo_get_terms($id, $taxonomy) {
	// If we're on a specific tag, category or taxonomy page, return that and bail.
	if ( is_category() || is_tag() || is_tax() ) {
		global $wp_query;
		$term = $wp_query->get_queried_object();
		return $term->name;
	}
	
	$output = '';
	$terms = get_the_terms($id, $taxonomy);
	if ( $terms ) {
		foreach ($terms as $term) {
			$output .= $term->name.', ';
		}
		return rtrim( trim($output), ',' );
	}
	return '';
}

function wpseo_get_term_meta( $term, $taxonomy, $meta ) {
	if ( is_string( $term ) ) 
		$term = get_term_by('slug', $term, $taxonomy);

	if ( is_object( $term ) )
		$term = $term->term_id;
	
	$tax_meta = get_option( 'wpseo_taxonomy_meta' );
	if ( isset($tax_meta[$taxonomy][$term]) )
		$tax_meta = $tax_meta[$taxonomy][$term];
	else
		return false;
	
	return (isset($tax_meta['wpseo_'.$meta])) ? $tax_meta['wpseo_'.$meta] : false;
}

function wpseo_dir_setup() {
	$options = get_option('wpseo');
	
	if ( !is_array($options) )
		$options = array();
		
	if ( isset( $options['wpseodir'] ) ) {
		if ( @is_writable( $options['wpseodir'] ) ) {
			$wpseodir = $options['wpseodir'];
			$wpseourl = $options['wpseourl'];
		} else {
			unset($options['wpseodir']);
			unset($options['wpseourl']);
			update_option('wpseo', $options);
		}
	} 
	
	if ( !isset( $wpseodir ) ) {
		$dir = wp_upload_dir();
		if ( is_wp_error($dir) ) {
			$error = __('Trying to get the upload dir gave the following error:').'<br/>';
			foreach ( $dir->get_error_messages() as $msg ) {
				$error .= $msg.'<br/>';
			}
			$wpseodir = false;
		} else if ( !file_exists( $dir['basedir'].'/wpseo/' ) ) {
			$dircreated = @mkdir( $dir['basedir'].'/wpseo/' );
			if ( $dircreated ) {
				$wpseodir = $dir['basedir'].'/wpseo/';
				$stat = @stat( dirname( $wpseodir ) );
				$dir_perms = $stat['mode'] & 0007777;
				@chmod( dirname( $wpseodir ), $dir_perms );
				
				$options['wpseodir'] = $wpseodir;
				$wpseourl = $options['wpseourl'] = $dir['baseurl'].'/wpseo/';
				update_option( 'wpseo' , $options );
			} else {
				$error = '<code>'.$dir['basedir'].'/wpseo/</code> could not be created';
				$wpseodir = false;
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
		define( 'WPSEO_UPLOAD_URL', false );
		define( 'WPSEO_UPLOAD_ERROR', $error );
	}
}
