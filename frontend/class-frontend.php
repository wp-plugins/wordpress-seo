<?php 

class WPSEO_Frontend {

	function WPSEO_Frontend() {
		$options = get_wpseo_options();

		add_action('wp_head', array(&$this, 'wpseo_head'), 10, 1);
		remove_action('wp_head', 'rel_canonical');

		add_filter('wp_title', array(&$this, 'wpseo_title'), 10, 3);
		
		add_action('wp',array(&$this,'wpseo_yoast_page_redirect'),99,1);

		if (isset($options['login']) && $options['login'])
			add_action('login_head', array(&$this, 'wpseo_noindex_page') );
		if (isset($options['admin']) && $options['admin'])
			add_action('admin_head', array(&$this, 'wpseo_noindex_page') );

		if (isset($options['allfeeds']) && $options['allfeeds']) {
			add_action('rss_head', array(&$this, 'wpseo_noindex_feed') );
			add_action('rss2_head', array(&$this, 'wpseo_noindex_feed') );
			add_action('commentsrss2_head', array(&$this, 'wpseo_noindex_feed') );
		} else if (isset($options['commentfeeds']) && $options['commentfeeds']) {
			add_action('commentsrss2_head', array(&$this, 'wpseo_noindex_feed') );
		}

		if (isset($options['nofollowmeta']) && $options['nofollowmeta']) {
			add_filter('loginout',array(&$this,'wpseo_nofollow_link'));
			add_filter('register',array(&$this,'wpseo_nofollow_link'));
		}

		if ( isset($options['hidersdlink']) && $options['hidersdlink'] )
			remove_action('wp_head', 'rsd_link');
		if ( isset($options['hidewlwmanifest']) && $options['hidewlwmanifest'] )
			remove_action('wp_head', 'wlwmanifest_link');
		if ( isset($options['hidewpgenerator']) && $options['hidewpgenerator'] ) {
			add_filter('the_generator', array(&$this, 'wpseo_fix_generator') ,10,1);
		}
		if ( isset($options['hideindexrel']) && $options['hideindexrel'] )
			remove_action('wp_head', 'index_rel_link');
		if ( isset($options['hideprevnextpostlink']) && $options['hideprevnextpostlink'] )
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
		if ( isset($options['hideshortlink']) && $options['hideshortlink'] )
			remove_action('wp_head', 'wp_shortlink_wp_head');

		if (isset($options['replacemetawidget']) && $options['replacemetawidget'])
			add_action('plugins_loaded', array(&$this, 'widget_yoast_wpseo_meta_init') );

		if (
			(isset($options['disabledate']) && $options['disabledate']) || 
			(isset($options['disableauthor']) && $options['disableauthor']) )
			add_action('wp', array(&$this, 'wpseo_archive_redirect') );

		if (isset($options['redirectattachment']) && $options['redirectattachment'])
			add_action('template_redirect', array(&$this,'wpseo_attachment_redirect'),1);

		if (isset($options['nofollowcommentlinks']) && $options['nofollowcommentlinks'])
			add_filter('comments_popup_link_attributes',array(&$this,'wpseo_echo_nofollow'));

		if (isset($options['trailingslash']) && $options['trailingslash'])
			add_filter('user_trailingslashit', array(&$this, 'wpseo_add_trailingslash') , 10, 2);

		if (isset($options['cleanpermalinks']) && $options['cleanpermalinks'])
			add_action('get_header',array(&$this,'wpseo_clean_permalink'),1);	

		if (isset($options['enablexmlsitemap']) && $options['enablexmlsitemap']) {
			add_action('generate_rewrite_rules', array(&$this,'add_rewrite_rules') );
		}
		
		add_filter('query_vars', array(&$this,'add_sitemap_query_var') );
		add_filter('robots_txt', array(&$this,'sitemap_output'), 10, 2 ); 
		add_action('do_robotstxt', array(&$this,'sitemap_header'), 99);		
		
		add_filter('the_content_feed', array(&$this, 'embed_rssfooter') );
		add_filter('the_excerpt_rss', array(&$this, 'embed_rssfooter_excerpt') );	
		
	}

	function wpseo_title( $title, $sep = '', $seplocation = '', $postid = '' ) {
		global $post, $wp_query;
		if ( empty($post) && is_singular() ) {
			$post = get_post($postid);
		}

		$options = get_wpseo_options();

		if ( is_home() && 'posts' == get_option('show_on_front') && isset($options['title-home']) && $options['title-home'] != '' ) {
			$title = wpseo_replace_vars($options['title-home'], (array) $post );
		} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
			$post = get_post(get_option('page_for_posts'));
			$fixed_title = yoast_get_value('title');
			if ( $fixed_title ) { 
				$title = $fixed_title; 
			} else if (isset($options['title-'.$post->post_type]) && !empty($options['title-'.$post->post_type]) ) {
				$title = wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );
			}
		} else if ( is_singular() ) {
			$fixed_title = yoast_get_value('title');
			if ( $fixed_title ) { 
				$title = $fixed_title; 
			} else if (isset($options['title-'.$post->post_type]) && !empty($options['title-'.$post->post_type]) ) {
				$title = wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$title = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_title' );
			if ( !$title && isset($options['title-'.$term->taxonomy]) && !empty($options['title-'.$term->taxonomy]) )
				$title = wpseo_replace_vars($options['title-'.$term->taxonomy], (array) $term );
		} else if ( is_search() && isset($options['title-search']) && !empty($options['title-search']) ) {
			$title = wpseo_replace_vars($options['title-search'], (array) $wp_query->get_queried_object() );
		} else if ( is_author() ) {
			$author_id = get_query_var('author');
			$title = get_the_author_meta('wpseo_title', $author_id);
			if ( empty($title) && isset($options['title-author']) && !empty($options['title-author']) ) {
				$title = wpseo_replace_vars($options['title-author'], array() );
			}
		} else if ( is_archive() && isset($options['title-archive']) && !empty($options['title-archive']) ) {
			$title = wpseo_replace_vars($options['title-archive'], array('post_title' => $title) );
		} else if ( is_404() && isset($options['title-404']) && !empty($options['title-404']) ) {
			$title = wpseo_replace_vars($options['title-404'], array('post_title' => $title) );
		} 
		return esc_html( strip_tags( stripslashes( $title ) ) );
	}
	
	function wpseo_fix_generator($generator) {
		return preg_replace('/\s?'.get_bloginfo('version').'/','',$generator);
	}
	
	function wpseo_head() {
		$options = get_wpseo_options();

		global $wp_query, $paged;
		
		$robots = '';

		echo "\t<!-- This site is optimized with the Yoast WordPress SEO plugin v".WPSEO_VERSION.". -->\n";
		$this->wpseo_metadesc();
		
		// Set decent canonicals for homepage, singulars and taxonomy pages
		if ( yoast_get_value('canonical') && yoast_get_value('canonical') != '' ) { 
			echo "\t".'<link rel="canonical" href="'.yoast_get_value('canonical').'" />'."\n";
		} else {
			if (is_singular()) {
				echo "\t";
				rel_canonical();							
			} else {
				if ( is_front_page() ) {
					$canonical = get_bloginfo('url').'/';
				} else if ( is_tax() || is_tag() || is_category() ) {
					$term = $wp_query->get_queried_object();
					
					$canonical = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_canonical' );
					if ( !$canonical )
						$canonical = get_term_link( $term, $term->taxonomy );
				}
				if ($paged)
					$canonical = user_trailingslashit( trailingslashit( $canonical ) . 'page/' . $paged );
					
				if ( !empty($canonical) )
					echo "\t".'<link rel="canonical" href="'.$canonical.'" />'."\n";
			}
		}
		
		if (is_singular()) {
			if ( yoast_get_value('meta-robots-noindex') )
				$robots .= 'noindex,';
			else
				$robots .= 'index,';
			if ( yoast_get_value('meta-robots-nofollow') )
				$robots .= 'nofollow';
			else
				$robots .= 'follow';
			if ( yoast_get_value('meta-robots-adv') && yoast_get_value('meta-robots-adv') != 'none' ) { 
				$robots .= ','.yoast_get_value('meta-robots-adv');
			}
		} else {
			if ( isset($term) && is_object($term) ) {
				if ( wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_noindex' ) )
					$robots .= 'noindex,';
				else
					$robots .= 'index,';
				if ( wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_nofollow' ) )
					$robots .= 'nofollow';
				else
					$robots .= 'follow';
			} 
			if ( 
				(is_author() 	&& isset($options['noindexauthor']) && $options['noindexauthor']) || 
				(is_category() 	&& isset($options['noindexcat']) && $options['noindexcat']) || 
				(is_date() 		&& isset($options['noindexdate']) && $options['noindexdate']) || 
				(is_tag() 		&& isset($options['noindextag']) && $options['noindextag']) || 
				(is_search() 	&& isset($options['search']) && $options['search']) || 
				(is_home() 		&& isset($options['pagedhome']) && $options['pagedhome'] && get_query_var('paged') > 1) )
			{
				$robots = "noindex,follow";
			}
		}
		
		// Clean up, index, follow is the default and doesn't need to be in output. All other combinations should be.
		if ($robots == 'index,follow')
			$robots = '';
		if (strpos($robots, 'index,follow,') === 0)
			$robots = str_replace('index,follow,','',$robots);

		foreach (array('noodp','noydir','noarchive','nosnippet') as $robot) {
			if (isset($options[$robot]) && $options[$robot]) {
				if (!empty($robots) && substr($robots, -1) != ',')
					$robots .= ',';
				$robots .= $robot;
			}
		}

		if ($robots != '') {
			$robots = rtrim($robots,',');
			echo "\t".'<meta name="robots" content="'.$robots.'"/>'."\n";
		}
		if ( is_front_page() ) {
			if (!empty($options['googleverify'])) {
				$google_meta = $options['googleverify'];
				if ( strpos($google_meta, 'content') ) {
					preg_match('/content="([^"]+)"/', $google_meta, $match);
					$google_meta = $match[1];
				}
				echo "\t".'<meta name="google-site-verification" content="'.$google_meta.'" />'."\n";
			}
			if (!empty($options['yahooverify'])) {
				$yahoo_meta = $options['yahooverify'];
				if ( strpos($yahoo_meta, 'content') ) {
					preg_match('/content="([^"]+)"/', $yahoo_meta, $match);
					$yahoo_meta = $match[1];
				}				
				echo "\t".'<meta name="y_key" content="'.$yahoo_meta.'" />'."\n";
			}
				
			if (!empty($options['msverify'])) {
				$bing_meta = $options['msverify'];
				if ( strpos($bing_meta, 'content') ) {
					preg_match('/content="([^"]+)"/', $bing_meta, $match);
					$bing_meta = $match[1];
				}								
				echo "\t".'<meta name="msvalidate.01" content="'.$bing_meta.'" />'."\n";
			}
				
		}
		echo "\t<!-- / Yoast WordPress SEO plugin. -->\n";
	}

	function wpseo_metadesc() {
		if ( !is_admin() ) {
			global $post, $wp_query;
			$options = get_wpseo_options();

			if (is_singular()) { 
				$metadesc = yoast_get_value('metadesc');
				if ($metadesc == '' || !$metadesc) {
					if ( isset($options['metadesc-'.$post->post_type]) && $options['metadesc-'.$post->post_type] != '' )
						$metadesc = wpseo_replace_vars($options['metadesc-'.$post->post_type], (array) $post );
				}
			} else {
				if ( is_home() && 'posts' == get_option('show_on_front') && isset($options['metadesc-home']) ) {
					$metadesc = wpseo_replace_vars($options['metadesc-home'], array() );
				} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
					$post = get_post( get_option('page_for_posts') );
					$metadesc = yoast_get_value('metadesc');
					if ( ($metadesc == '' || !$metadesc) && isset($options['metadesc-'.$post->post_type]) ) { 
						$metadesc = wpseo_replace_vars($options['metadesc-'.$post->post_type], (array) $post );
					}
				} else if ( is_category() || is_tag() || is_tax() ) {
					$term = $wp_query->get_queried_object();
					
					$metadesc = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_desc' );
					if ( !$metadesc && isset($options['metadesc-'.$term->taxonomy])) {
						$metadesc = wpseo_replace_vars($options['metadesc-'.$term->taxonomy], (array) $term );
					}
				} else if ( is_author() ) {
					$author_id = get_query_var('author');
					$metadesc = get_the_author_meta('wpseo_metadesc', $author_id);
				} 
			}
		
			if (!empty($metadesc))
				echo "\t".'<meta name="description" content="'. esc_attr( strip_tags( stripslashes( $metadesc ) ) ).'"/>'."\n";
		}
	}

	function wpseo_yoast_page_redirect($input) {
		global $post;
		if ( !isset($post) )
			return;
		$redir = yoast_get_value('redirect', $post->ID);
		if (!empty($redir)) {
			wp_redirect($redir, 301);
			exit;
		}
	}
	
	function wpseo_noindex_page() {
		echo "\t<!-- This site is optimized with the Yoast WordPress SEO plugin. -->\n";
		echo "\t".'<meta name="robots" content="noindex" />'."\n";
	}

	function wpseo_noindex_feed() {
		echo '<xhtml:meta xmlns:xhtml="http://www.w3.org/1999/xhtml" name="robots" content="noindex" />'."\n";
	}

	function wpseo_nofollow_link($output) {
		return str_replace('<a ','<a rel="nofollow" ',$output);
	}

	function wpseo_echo_nofollow() {
		return ' rel="nofollow"';
	}

	function widget_yoast_wpseo_meta_init() {
		function yoast_wpseo_meta($args) {
			extract($args);
			$options = get_option('widget_meta');
			$title = empty($options['title']) ? __('Meta', 'robots-meta') : $options['title'];
		?>
				<?php echo $before_widget; ?>
					<?php echo $before_title . $title . $after_title; ?>
					<ul>
					<?php wp_register(); ?>
					<li><?php wp_loginout(); ?></li>
					<li><a rel="nofollow" href="<?php bloginfo('rss2_url'); ?>" title="<?php echo attribute_escape(__('Syndicate this site using RSS 2.0', 'robots-meta')); ?>"><?php _e('Entries <abbr title="Really Simple Syndication">RSS</abbr>', 'robots-meta'); ?></a></li>
					<li><a rel="nofollow"href="<?php bloginfo('comments_rss2_url'); ?>" title="<?php echo attribute_escape(__('The latest comments to all posts in RSS', 'robots-meta')); ?>"><?php _e('Comments <abbr title="Really Simple Syndication">RSS</abbr>', 'robots-meta'); ?></a></li>
					<li><a rel="nofollow" href="http://wordpress.org/" title="<?php echo attribute_escape(__('Powered by WordPress, state-of-the-art semantic personal publishing platform.', 'robots-meta')); ?>">WordPress.org</a></li>
					<?php wp_meta(); ?>
					</ul>
				<?php echo $after_widget; ?>
		<?php
		}

		wp_register_sidebar_widget('meta','meta','yoast_wpseo_meta');
	}

	function wpseo_archive_redirect() {
		global $wp_query;
		$options  = get_wpseo_options();
		if ( ($options['disabledate'] && $wp_query->is_date) || ($options['disableauthor'] && $wp_query->is_author) ) {
			wp_redirect(get_bloginfo('url'),301);
			exit;
		}
	}

	function wpseo_attachment_redirect() {
		global $post;
		if (is_attachment()) {
			wp_redirect(get_permalink($post->post_parent), 301);
			exit;
		}
	}

	function wpseo_add_trailingslash($url, $type) {
		// trailing slashes for everything except is_single()
		// Thanks to Mark Jaquith for this
		if ( 'single' === $type ) {
			return $url;
		} else {
			return trailingslashit($url);
		}
	}

	function wpseo_clean_permalink() {
		if ( is_robots() )
			return;

		$options = get_wpseo_options();
		global $wp_query;
	
		// Recreate current URL
		$cururl = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$cururl .= "s";
		}
		$cururl .= "://";
		if ($_SERVER["SERVER_PORT"] != "80")
			$cururl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else
			$cururl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		$properurl = '';
		if ( is_singular() ) {
			$properurl = get_permalink($wp_query->post->ID);
			// Fix reply to comment links, whoever decided this should be a GET variable?
			$result = preg_match('/(\?replytocom=[^&]+)/', $_SERVER["REQUEST_URI"], $matches);
			if ( $result )
				$properurl .= str_replace('?replytocom=','#comment-',$matches[0]);
		} else if ( is_front_page() ) {
			if ( 'posts' == get_option('show_on_front') && is_home() ) {
				$properurl = get_bloginfo('url').'/';
			} elseif ( 'page' == get_option('show_on_front') ) {
			 	$properurl = get_permalink(get_option('page_on_front'));
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$properurl = get_term_link( $term, $term->taxonomy );
		} else if ( is_search() ) {
			if ( function_exists('get_search_link') )
				$properurl = get_search_link($wp_query->query_vars['s']); // get_search_link is new in WP 3.0
			else
				$properurl = get_bloginfo('url').'/search/'.$wp_query->query_vars['s'].'/';
		}
		if ( !empty($properurl) && $wp_query->query_vars['paged'] != 0 && $wp_query->post_count != 0 ) {
			$properurl = user_trailingslashit( trailingslashit($properurl). 'page/' . $wp_query->query_vars['paged'] );
		}
		// TODO: add option to edit the array below through admin.
		if (isset($options['cleanpermalink-googlesitesearch']) && $options['cleanpermalink-googlesitesearch']) {
			// Prevent cleaning out Google Site searches
			foreach (array('q','cx','debug','cof','ie','sa') as $get) {
				if ( isset($_GET[$get]) ) {
					$properurl = '';
				}		
			}		
		}

		if (isset($options['cleanpermalink-googlecampaign']) && $options['cleanpermalink-googlecampaign']) {
			// Prevent cleaning out Google Analytics campaign variables
			foreach (array('utm_campaign','utm_medium','utm_source','utm_content','utm_term') as $get) {
				if ( isset($_GET[$get]) ) {
					$properurl = '';
				}		
			}		
		}
	
		if ( !empty($properurl) && $cururl != $properurl ) {	
			wp_redirect($properurl, 301);
			exit;
		}
	}

	function wpseo_rss_replace_vars($temp) {
		$postlink = '<a href="'.get_permalink().'">'.get_the_title()."</a>";
		$bloglink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a>';
		$blogdesclink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').' - '.get_bloginfo('description').'</a>';

		$temp = stripslashes($temp);
		$temp = str_replace("%%POSTLINK%%", $postlink, $temp);
		$temp = str_replace("%%BLOGLINK%%", $bloglink, $temp);		
		$temp = str_replace("%%BLOGDESCLINK%%", $blogdesclink, $temp);					
		return $temp;
	}

	function wpseo_embed_rssfooter($content) {
		if(is_feed()) {
			$options  = get_wpseo_options();

			if ( isset($options['rssbefore']) && !empty($options['rssbefore']) ) {
				$content = "<p>" . $this->wpseo_rss_replace_vars($options['rssbefore']) . "</p>" . $content;
			} 
			if ( isset($options['rssafter']) && !empty($options['rssafter']) ) {
				$content .= "<p>" . $this->wpseo_rss_replace_vars($options['rssafter']). "</p>";
			} 
		}
		return $content;
	}

	function wpseo_embed_rssfooter_excerpt($content) {
		if(is_feed()) {
			$options  = get_wpseo_options();

			if ( isset($options['rssbefore']) && !empty($options['rssbefore']) ) {
				$content = "<p>".$this->wpseo_rss_replace_vars($options['rssbefore']) . "</p><p>" . $content ."</p>";
			} 
			if ( isset($options['rssafter']) && !empty($options['rssafter']) ) {
				$content = "<p>".$content."</p><p>".$this->wpseo_rss_replace_vars($options['rssafter'])."</p>";
			} 
		}
		return $content;
	}
	
	function add_rewrite_rules( $rewrite ) { 
		$new_rules = array(
			'((news)?_?sitemap\.xml)(\.gz)?$' => 'index.php?robots=1&wpseo_sitemap='.$rewrite->preg_index(1).'&wpseo_sitemap_gz='.$rewrite->preg_index(3),
		);
		$rewrite->rules = $new_rules + $rewrite->rules;
	} 
	
	function add_sitemap_query_var( $query_vars ) {
		$query_vars[] = 'wpseo_sitemap';
		$query_vars[] = 'wpseo_sitemap_gz';
		return $query_vars;
	}
	
	function sitemap_output( $robots, $public ) {
		if ( !get_query_var('wpseo_sitemap') )
			return $robots;

		if ( !WPSEO_UPLOAD_DIR )
			return $robots;

		$robots = file_get_contents(WPSEO_UPLOAD_DIR.get_query_var('wpseo_sitemap').get_query_var('wpseo_sitemap_gz'));

		return $robots;
	}
	
	function sitemap_header() {
		if ( get_query_var('wpseo_sitemap') ) {
			if ( get_query_var('wpseo_sitemap_gz') )
				header( 'Content-Type: application/x-gzip; charset=utf-8' );
			else
				header( 'Content-Type: application/xml; charset=utf-8' );
		}
	}
}

$wpseo_front = new WPSEO_Frontend;
?>