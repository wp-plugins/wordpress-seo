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
		(array) get_option('wpseo_internallinks'),
		(array) get_option('wpseo_local')
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

function wpseo_get_term_meta( $term, $taxonomy, $meta ) {
	if ( is_string( $term ) ) 
		$term = get_term_by('slug', $term, $taxonomy);

	if ( is_object( $term ) )
		$term = $term->term_id;
	
	$tax_meta = get_option( 'wpseo_taxonomy_meta' );

	return (isset($tax_meta[$taxonomy][$term][$meta])) ? $tax_meta[$taxonomy][$term][$meta] : false;
}

function wpseo_load_plugins( $path ) {
	$allowed_plugins = array('wpseo-local', 'wpseo-video', 'wpseo-news');
	
	$dir = @opendir( $path );
	if ($dir) {
		while (($entry = @readdir($dir)) !== false) {
			$full_dir_path = $path . "/" . $entry;
			if(is_readable($full_dir_path) && is_dir($full_dir_path) && in_array($entry, $allowed_plugins)) {
				$module_dir = @opendir( $full_dir_path );
				if ($module_dir) {
					while (($module_entry = @readdir($module_dir)) !== false) {
						if (strrchr($module_entry, '.') === '.php') {
							require_once $full_dir_path . '/' . $module_entry;
						}
					}
				}
			}
		}
		@closedir($dir);
	}
}

function wpseo_get_country($country_code) {
	$country_arr = wpseo_get_country_arr();
	return $country_arr[$country_code];
}

function wpseo_get_country_arr(){
	$countries = array(
		'AF'=>'Afghanistan', 'AL'=>'Albania', 'DZ'=>'Algeria', 'AS'=>'American Samoa', 'AD'=>'Andorra', 'AO'=>'Angola', 'AI'=>'Anguilla', 'AQ'=>'Antarctica', 'AG'=>'Antigua And Barbuda', 'AR'=>'Argentina', 'AM'=>'Armenia', 'AW'=>'Aruba', 'AU'=>'Australia', 'AT'=>'Austria', 'AZ'=>'Azerbaijan', 'BS'=>'Bahamas', 'BH'=>'Bahrain', 'BD'=>'Bangladesh', 'BB'=>'Barbados', 'BY'=>'Belarus', 'BE'=>'Belgium', 'BZ'=>'Belize', 'BJ'=>'Benin', 'BM'=>'Bermuda', 'BT'=>'Bhutan', 'BO'=>'Bolivia', 'BA'=>'Bosnia And Herzegovina', 'BW'=>'Botswana', 'BV'=>'Bouvet Island', 'BR'=>'Brazil', 'IO'=>'British Indian Ocean Territory', 'BN'=>'Brunei', 'BG'=>'Bulgaria', 'BF'=>'Burkina Faso', 'BI'=>'Burundi', 'KH'=>'Cambodia', 'CM'=>'Cameroon', 'CA'=>'Canada', 'CV'=>'Cape Verde', 'KY'=>'Cayman Islands', 'CF'=>'Central African Republic', 'TD'=>'Chad', 'CL'=>'Chile', 'CN'=>'China', 'CX'=>'Christmas Island', 'CC'=>'Cocos (Keeling) Islands', 'CO'=>'Columbia', 'KM'=>'Comoros', 'CG'=>'Congo', 'CK'=>'Cook Islands', 'CR'=>'Costa Rica', 'CI'=>'Cote D\'Ivorie (Ivory Coast)', 'HR'=>'Croatia (Hrvatska)', 'CU'=>'Cuba', 'CY'=>'Cyprus', 'CZ'=>'Czech Republic', 'CD'=>'Democratic Republic Of Congo (Zaire)', 'DK'=>'Denmark', 'DJ'=>'Djibouti', 'DM'=>'Dominica', 'DO'=>'Dominican Republic', 'TP'=>'East Timor', 'EC'=>'Ecuador', 'EG'=>'Egypt', 'SV'=>'El Salvador', 'GQ'=>'Equatorial Guinea', 'ER'=>'Eritrea', 'EE'=>'Estonia', 'ET'=>'Ethiopia', 'FK'=>'Falkland Islands (Malvinas)', 'FO'=>'Faroe Islands', 'FJ'=>'Fiji', 'FI'=>'Finland', 'FR'=>'France', 'FX'=>'France, Metropolitan', 'GF'=>'French Guinea', 'PF'=>'French Polynesia', 'TF'=>'French Southern Territories', 'GA'=>'Gabon', 'GM'=>'Gambia', 'GE'=>'Georgia', 'DE'=>'Germany', 'GH'=>'Ghana', 'GI'=>'Gibraltar', 'GR'=>'Greece', 'GL'=>'Greenland', 'GD'=>'Grenada', 'GP'=>'Guadeloupe', 'GU'=>'Guam', 'GT'=>'Guatemala', 'GN'=>'Guinea', 'GW'=>'Guinea-Bissau', 'GY'=>'Guyana', 'HT'=>'Haiti', 'HM'=>'Heard And McDonald Islands', 'HN'=>'Honduras', 'HK'=>'Hong Kong', 'HU'=>'Hungary', 'IS'=>'Iceland', 'IN'=>'India', 'ID'=>'Indonesia', 'IR'=>'Iran', 'IQ'=>'Iraq', 'IE'=>'Ireland', 'IL'=>'Israel', 'IT'=>'Italy', 'JM'=>'Jamaica', 'JP'=>'Japan', 'JO'=>'Jordan', 'KZ'=>'Kazakhstan', 'KE'=>'Kenya', 'KI'=>'Kiribati', 'KW'=>'Kuwait', 'KG'=>'Kyrgyzstan', 'LA'=>'Laos', 'LV'=>'Latvia', 'LB'=>'Lebanon', 'LS'=>'Lesotho', 'LR'=>'Liberia', 'LY'=>'Libya', 'LI'=>'Liechtenstein', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MO'=>'Macau', 'MK'=>'Macedonia', 'MG'=>'Madagascar', 'MW'=>'Malawi', 'MY'=>'Malaysia', 'MV'=>'Maldives', 'ML'=>'Mali', 'MT'=>'Malta', 'MH'=>'Marshall Islands', 'MQ'=>'Martinique', 'MR'=>'Mauritania', 'MU'=>'Mauritius', 'YT'=>'Mayotte', 'MX'=>'Mexico', 'FM'=>'Micronesia', 'MD'=>'Moldova', 'MC'=>'Monaco', 'MN'=>'Mongolia', 'MS'=>'Montserrat', 'MA'=>'Morocco', 'MZ'=>'Mozambique', 'MM'=>'Myanmar (Burma)', 'NA'=>'Namibia', 'NR'=>'Nauru', 'NP'=>'Nepal', 'NL'=>'Netherlands', 'AN'=>'Netherlands Antilles', 'NC'=>'New Caledonia', 'NZ'=>'New Zealand', 'NI'=>'Nicaragua', 'NE'=>'Niger', 'NG'=>'Nigeria', 'NU'=>'Niue', 'NF'=>'Norfolk Island', 'KP'=>'North Korea', 'MP'=>'Northern Mariana Islands', 'NO'=>'Norway', 'OM'=>'Oman', 'PK'=>'Pakistan', 'PW'=>'Palau', 'PA'=>'Panama', 'PG'=>'Papua New Guinea', 'PY'=>'Paraguay', 'PE'=>'Peru', 'PH'=>'Philippines', 'PN'=>'Pitcairn', 'PL'=>'Poland', 'PT'=>'Portugal', 'PR'=>'Puerto Rico', 'QA'=>'Qatar', 'RE'=>'Reunion', 'RO'=>'Romania', 'RU'=>'Russia', 'RW'=>'Rwanda', 'SH'=>'Saint Helena', 'KN'=>'Saint Kitts And Nevis', 'LC'=>'Saint Lucia', 'PM'=>'Saint Pierre And Miquelon', 'VC'=>'Saint Vincent And The Grenadines', 'SM'=>'San Marino', 'ST'=>'Sao Tome And Principe', 'SA'=>'Saudi Arabia', 'SN'=>'Senegal', 'SC'=>'Seychelles', 'SL'=>'Sierra Leone', 'SG'=>'Singapore', 'SK'=>'Slovak Republic', 'SI'=>'Slovenia', 'SB'=>'Solomon Islands', 'SO'=>'Somalia', 'ZA'=>'South Africa', 'GS'=>'South Georgia And South Sandwich Islands', 'KR'=>'South Korea', 'ES'=>'Spain', 'LK'=>'Sri Lanka', 'SD'=>'Sudan', 'SR'=>'Suriname', 'SJ'=>'Svalbard And Jan Mayen', 'SZ'=>'Swaziland', 'SE'=>'Sweden', 'CH'=>'Switzerland', 'SY'=>'Syria', 'TW'=>'Taiwan', 'TJ'=>'Tajikistan', 'TZ'=>'Tanzania', 'TH'=>'Thailand', 'TG'=>'Togo', 'TK'=>'Tokelau', 'TO'=>'Tonga', 'TT'=>'Trinidad And Tobago', 'TN'=>'Tunisia', 'TR'=>'Turkey', 'TM'=>'Turkmenistan', 'TC'=>'Turks And Caicos Islands', 'TV'=>'Tuvalu', 'UG'=>'Uganda', 'UA'=>'Ukraine', 'AE'=>'United Arab Emirates', 'UK'=>'United Kingdom', 'US'=>'United States', 'UM'=>'United States Minor Outlying Islands', 'UY'=>'Uruguay', 'UZ'=>'Uzbekistan', 'VU'=>'Vanuatu', 'VA'=>'Vatican City (Holy See)', 'VE'=>'Venezuela', 'VN'=>'Vietnam', 'VG'=>'Virgin Islands (British)', 'VI'=>'Virgin Islands (US)', 'WF'=>'Wallis And Futuna Islands', 'EH'=>'Western Sahara', 'WS'=>'Western Samoa', 'YE'=>'Yemen', 'YU'=>'Yugoslavia', 'ZM'=>'Zambia', 'ZW'=>'Zimbabwe'
	);
	return $countries;
}

function wpseo_activate() {
	// Force a flush of rewrite rules
	delete_option('rewrite_rules');
}