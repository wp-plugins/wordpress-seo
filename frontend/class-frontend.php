<?php 

class WPSEO_Frontend {

	function WPSEO_Frontend() {
		$options = get_wpseo_options();

		add_action('wp_head', array(&$this, 'head'), 10, 1);
		remove_action('wp_head', 'rel_canonical');

		add_filter( 'wp_title', array(&$this, 'title'), 10, 3);
		add_filter( 'thematic_doctitle', array(&$this, 'force_wp_title') );
		add_filter( 'headway_title', array(&$this, 'force_wp_title') );
		
		add_action('wp',array(&$this,'page_redirect'),99,1);

		if (isset($options['login']) && $options['login'])
			add_action('login_head', array(&$this, 'noindex_page') );
		if (isset($options['admin']) && $options['admin'])
			add_action('admin_head', array(&$this, 'noindex_page') );

		if (isset($options['allfeeds']) && $options['allfeeds']) {
			add_action('rss_head', array(&$this, 'noindex_feed') );
			add_action('rss2_head', array(&$this, 'noindex_feed') );
			add_action('commentsrss2_head', array(&$this, 'noindex_feed') );
		} else if (isset($options['commentfeeds']) && $options['commentfeeds']) {
			add_action('commentsrss2_head', array(&$this, 'noindex_feed') );
		}

		if (isset($options['nofollowmeta']) && $options['nofollowmeta']) {
			add_filter('loginout',array(&$this,'nofollow_link'));
			add_filter('register',array(&$this,'nofollow_link'));
		}

		if ( isset($options['hidersdlink']) && $options['hidersdlink'] )
			remove_action('wp_head', 'rsd_link');
		if ( isset($options['hidewlwmanifest']) && $options['hidewlwmanifest'] )
			remove_action('wp_head', 'wlwmanifest_link');
		if ( isset($options['hidewpgenerator']) && $options['hidewpgenerator'] )
			add_filter('the_generator', array(&$this, 'fix_generator') ,10,1);
		if ( isset($options['hideindexrel']) && $options['hideindexrel'] )
			remove_action('wp_head', 'index_rel_link');
		if ( isset($options['hidestartrel']) && $options['hidestartrel'] )
			remove_action('wp_head', 'start_post_rel_link');
		if ( isset($options['hideprevnextpostlink']) && $options['hideprevnextpostlink'] )
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
		if ( isset($options['hideshortlink']) && $options['hideshortlink'] )
			remove_action('wp_head', 'wp_shortlink_wp_head');
		if ( isset($options['hidefeedlinks']) && $options['hidefeedlinks'] ) {
			// TODO: add option to display just normal feed and hide comment feed.
			remove_action('wp_head', 'feed_links', 2);
			remove_action('wp_head', 'feed_links_extra', 3);
		}
		if (isset($options['replacemetawidget']) && $options['replacemetawidget'])
			add_action('plugins_loaded', array(&$this, 'widget_yoast_wpseo_meta_init') );

		if ( (isset($options['disabledate']) && $options['disabledate']) || 
			 (isset($options['disableauthor']) && $options['disableauthor']) )
			add_action('wp', array(&$this, 'archive_redirect') );

		if (isset($options['redirectattachment']) && $options['redirectattachment'])
			add_action('template_redirect', array(&$this,'attachment_redirect'),1);

		if (isset($options['nofollowcommentlinks']) && $options['nofollowcommentlinks'])
			add_filter('comments_popup_link_attributes',array(&$this,'echo_nofollow'));

		if (isset($options['trailingslash']) && $options['trailingslash'])
			add_filter('user_trailingslashit', array(&$this, 'add_trailingslash') , 10, 2);

		if (isset($options['cleanpermalinks']) && $options['cleanpermalinks'])
			add_action('template_redirect',array(&$this,'clean_permalink'),1);	

		add_filter('robots_txt', array(&$this,'sitemap_output'), 10, 2 ); 
		add_action('do_robotstxt', array(&$this,'sitemap_header'), 99);		
		
		add_filter('the_content_feed', array(&$this, 'embed_rssfooter') );
		add_filter('the_excerpt_rss', array(&$this, 'embed_rssfooter_excerpt') );	
		
		if (isset($options['forcerewritetitle']) && $options['forcerewritetitle']) {
			add_action('get_header', array(&$this, 'force_rewrite_output_buffer') );
			add_action('wp_footer', array(&$this, 'flush_cache') );			
		}
	}

	function title( $title, $sep = '-', $seplocation = '', $postid = '' ) {
		global $post, $wp_query;
		if ( empty($post) && is_singular() ) {
			$post = $wp_query->get_queried_object();
		}

		$options = get_wpseo_options();

		if ( is_home() && 'posts' == get_option('show_on_front') ) {
			if ( isset($options['title-home']) && $options['title-home'] != '' )
				$title = wpseo_replace_vars( $options['title-home'], array() );
			else
				$title = get_bloginfo('name').' '.$sep.' '.get_bloginfo('description');
		} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
			$post = get_post( get_option( 'page_for_posts' ) );
			$fixed_title = yoast_get_value('title');
			if ( $fixed_title ) { 
				$title = $fixed_title; 
			} else {
				if (isset($options['title-'.$post->post_type]) && !empty($options['title-'.$post->post_type]) )
					$title = wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );
				else
					$title = get_bloginfo('name').' '.$sep.' '.get_bloginfo('description');
			}
		} else if ( is_singular() ) {
			$fixed_title = yoast_get_value('title');
			if ( $fixed_title ) { 
				$title = $fixed_title; 
			} else {
				if (isset($options['title-'.$post->post_type]) && !empty($options['title-'.$post->post_type]) )
					$title = wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );
				else
					$title = $post->post_title.' '.$sep.' '.get_bloginfo('name'); 
			}
		} else if ( is_category() || is_tag() || is_tax() ) {
			$term = $wp_query->get_queried_object();
			$title = trim( wpseo_get_term_meta( $term, $term->taxonomy, 'title' ) );
			if ( !$title || empty($title) ) {
				if ( isset($options['title-'.$term->taxonomy]) && !empty($options['title-'.$term->taxonomy]) ) {
					$title = wpseo_replace_vars($options['title-'.$term->taxonomy], (array) $term );
				} else {
					if ( is_category() )
						$title = single_cat_title('', false);
					else if ( is_tag() )
						$title = single_tag_title('', false);
					else if ( is_tax() ) {
						if ( function_exists('single_term_title') ) {
							$title = single_term_title('', false);
						} else {
							$term = $wp_query->get_queried_object();
							$title = $term->name;
						}
					}
						
						
					$title .= ' '.$sep.' '.get_bloginfo('name'); 
				}
			}
		} else if ( is_search() ) {
			if ( isset($options['title-search']) && !empty($options['title-search']) )
				$title = wpseo_replace_vars($options['title-search'], (array) $wp_query->get_queried_object() );	
			else
				$title = __('Search for "').get_search_query().'" '.$sep.' '.get_bloginfo('name');
		} else if ( is_author() ) {
			$author_id = get_query_var('author');
			$title = get_the_author_meta('title', $author_id);
			if ( empty($title) ) {
				if ( isset($options['title-author']) && !empty($options['title-author']) )
					$title = wpseo_replace_vars($options['title-author'], array() );
				else
					$title = get_the_author_meta('display_name', $author_id).' '.$sep.' '.get_bloginfo('name'); 
			}
		} else if ( is_archive() ) {
		 	if ( isset($options['title-archive']) && !empty($options['title-archive']) )
				$title = wpseo_replace_vars($options['title-archive'], array('post_title' => $title) );
			else {
				if ( is_month() )
					$title = single_month_title(' ', false).' '.__('Archives').' '.$sep.' '.get_bloginfo('name'); 
				else if ( is_year() )
					$title = get_query_var('year').' '.__('Archives').' '.$sep.' '.get_bloginfo('name'); 
			}
		} else if ( is_404() ) {
		 	if ( isset($options['title-404']) && !empty($options['title-404']) )
				$title = wpseo_replace_vars($options['title-404'], array('post_title' => $title) );
			else
				$title = __('Page not found').' '.$sep.' '.get_bloginfo('name');
		} 
		return esc_html( stripslashes( $title ) );
	}
	
	function force_wp_title() {
		return wp_title('', 0);
	}
	
	function fix_generator($generator) {
		return preg_replace('/\s?'.get_bloginfo('version').'/','',$generator);
	}
	
	function head() {
		$options = get_wpseo_options();

		global $wp_query, $paged;
		
		$robots = '';

		echo "\t<!-- This site is optimized with the Yoast WordPress SEO plugin v".WPSEO_VERSION." - http://yoast.com/wordpress/seo/ -->\n";
		$this->metadesc();
		$this->metakeywords();
		
		// Set decent canonicals for homepage, singulars and taxonomy pages
		if ( yoast_get_value('canonical') && yoast_get_value('canonical') != '' ) { 
			echo "\t".'<link rel="canonical" href="'.yoast_get_value('canonical').'" />'."\n";
		} else {
			if ( is_singular() ) {
				global $post;
				$canonical = get_permalink( $post->ID );
				// Fix paginated pages
				$page = get_query_var('page');
				if ( $page && $page != 1 ) {
					// If below doesn't return true, there actually aren't that much pages in the post.
					if ( substr_count($wp_query->queried_object->post_content, '<!--nextpage-->') >= ($page-1) )
						$canonical = user_trailingslashit( trailingslashit($properurl) . get_query_var('page') );
				}
			} else {
				if ( is_front_page() ) {
					$canonical = get_bloginfo('url').'/';
				} else if (is_home() && get_option('show_on_front') == "page") {
					$canonical = get_permalink( get_option( 'page_for_posts' ) );
				} else if ( is_tax() || is_tag() || is_category() ) {
					$term = $wp_query->get_queried_object();
					
					$canonical = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_canonical' );
					if ( !$canonical )
						$canonical = get_term_link( $term, $term->taxonomy );
				} else if ( is_archive() ) {
					if ( is_date() ) {
						if ( is_day() ) {
							$canonical = get_day_link( get_query_var('year'), get_query_var('monthnum'), get_query_var('day') );
						} else if ( is_month() ) {
							$canonical = get_month_link( get_query_var('year'), get_query_var('monthnum') );
						} else if ( is_year() ) {
							$canonical = get_year_link( get_query_var('year') );
						}						
					}
				}
				
				if ($paged)
					$canonical = user_trailingslashit( trailingslashit( $canonical ) . 'page/' . $paged );
					
			}
			if ( !empty($canonical) )
				echo "\t".'<link rel="canonical" href="'.$canonical.'" />'."\n";
		}
		
		$robots 			= array();
		$robots['index'] 	= 'index';
		$robots['follow'] 	= 'follow';
		$robots['other'] 	= array();
		
		if (is_singular()) {
			if ( yoast_get_value('meta-robots-noindex') )
				$robots['index'] = 'noindex';
			if ( yoast_get_value('meta-robots-nofollow') )
				$robots['follow'] = 'nofollow';
			if ( yoast_get_value('meta-robots-adv') && yoast_get_value('meta-robots-adv') != 'none' ) { 
				foreach ( explode( ',', yoast_get_value('meta-robots-adv') ) as $r ) {
					$robots['other'][] = $r;
				}
			}
		} else {
			if ( isset($term) && is_object($term) ) {
				if ( wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_noindex' ) )
					$robots['index'] = 'noindex';
				if ( wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_nofollow' ) )
					$robots['follow'] = 'nofollow';
			} 
			if ( 
				(is_author() 	&& isset($options['noindexauthor']) && $options['noindexauthor']) || 
				(is_category() 	&& isset($options['noindexcat']) && $options['noindexcat']) || 
				(is_date() 		&& isset($options['noindexdate']) && $options['noindexdate']) || 
				(is_tag() 		&& isset($options['noindextag']) && $options['noindextag']) || 
				(is_search() 	&& isset($options['search']) && $options['search']) || 
				(is_home() 		&& isset($options['pagedhome']) && $options['pagedhome'] && get_query_var('paged') > 1) )
			{
				$robots['index']  = 'noindex';
				$robots['follow'] = 'follow';
			}
		}
		
		foreach ( array('noodp','noydir','noarchive','nosnippet') as $robot ) {
			if ( isset($options[$robot]) && $options[$robot] ) {
				$robots['other'][] = $robot;
			}
		}

		$robotsstr = $robots['index'].','.$robots['follow'];

		$robots['other'] = array_unique( $robots['other'] );
		foreach ($robots['other'] as $robot) {
			$robotsstr .= ','.$robot;
		}

		$robotsstr = preg_replace( '/^index,follow,?/', '', $robotsstr );
		
		if ($robotsstr != '') {
			echo "\t<meta name='robots' content='".$robotsstr."'/>\n";
		}
		
		if ( is_front_page() ) {
			if (!empty($options['googleverify'])) {
				$google_meta = $options['googleverify'];
				if ( strpos($google_meta, 'content') ) {
					preg_match('/content="([^"]+)"/', $google_meta, $match);
					$google_meta = $match[1];
				}
				echo "\t<meta name='google-site-verification' content='$google_meta' />\n";
			}
			if (!empty($options['yahooverify'])) {
				$yahoo_meta = $options['yahooverify'];
				if ( strpos($yahoo_meta, 'content') ) {
					preg_match('/content="([^"]+)"/', $yahoo_meta, $match);
					$yahoo_meta = $match[1];
				}				
				echo "\t<meta name='y_key' content='$yahoo_meta' />\n";
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

	function metakeywords() {
		global $wp_query;
		
		$options = get_wpseo_options();
		if ( !isset( $options['usemetakeywords'] ) || !$options['usemetakeywords'] )
			return;
			
		if ( is_singular() ) { 
			global $post;
			$metakey = yoast_get_value('metakeywords');
			if ( !$metakey || empty($metakey) ) {
				$metakey = wpseo_replace_vars($options['metakey-'.$post->post_type], (array) $post );
			}
		} else {
			if ( is_home() && 'posts' == get_option('show_on_front') && isset($options['metakey-home']) ) {
				$metakey = wpseo_replace_vars($options['metakey-home'], array() );
			} else if ( is_home() && 'posts' != get_option('show_on_front') ) {
				$post = get_post( get_option('page_for_posts') );
				$metakey = yoast_get_value('metakey');
				if ( ($metakey == '' || !$metakey) && isset($options['metakey-'.$post->post_type]) )
					$metakey = wpseo_replace_vars($options['metakey-'.$post->post_type], (array) $post );
			} else if ( is_category() || is_tag() || is_tax() ) {
				$term = $wp_query->get_queried_object();

				$metakey = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_metakey' );
				if ( !$metakey && isset($options['metakey-'.$term->taxonomy]))
					$metakey = wpseo_replace_vars($options['metakey-'.$term->taxonomy], (array) $term );
			} else if ( is_author() ) {
				$author_id = get_query_var('author');
				$metakey = get_the_author_meta('metakey', $author_id);
				if ( !$metakey && isset($options['metakey-author']) )
					$metakey = wpseo_replace_vars($options['metakey-author'], (array) $wp_query->get_queried_object() );
			} 
			
		}

		$metakey = trim( $metakey );
		if ( !empty( $metakey ) ) 
			echo "\t<meta name='keywords' content='".esc_attr( strip_tags( stripslashes( $metakey ) ) )."'/>\n";

	}
	
	function metadesc() {
		if ( get_query_var('paged') && get_query_var('paged') > 1 )
			return;
			
		global $post, $wp_query;
		$options = get_wpseo_options();

		$metadesc = '';
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
				if ( ($metadesc == '' || !$metadesc) && isset($options['metadesc-'.$post->post_type]) )
					$metadesc = wpseo_replace_vars($options['metadesc-'.$post->post_type], (array) $post );
			} else if ( is_category() || is_tag() || is_tax() ) {
				$term = $wp_query->get_queried_object();

				$metadesc = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_desc' );
				if ( !$metadesc && isset($options['metadesc-'.$term->taxonomy]))
					$metadesc = wpseo_replace_vars($options['metadesc-'.$term->taxonomy], (array) $term );
			} else if ( is_author() ) {
				$author_id = get_query_var('author');
				$metadesc = get_the_author_meta('metadesc', $author_id);
				if ( !$metadesc && isset($options['metadesc-author']))
					$metadesc = wpseo_replace_vars($options['metadesc-author'], (array) $wp_query->get_queried_object() );
			} 
		}
	
		$metadesc = trim( $metadesc );
		if ( !empty( $metadesc ) )
			echo "\t<meta name='description' content='".esc_attr( strip_tags( stripslashes( $metadesc ) ) )."'/>\n";
		else if ( current_user_can('manage_options') && is_singular() )
			echo "\t".'<!-- Admin only notice: this page doesn\'t show a meta description because it doesn\'t have one, either write it for this page specifically or go into the SEO -> Titles menu and set up a template. -->'."\n";
		
	}

	function page_redirect( $input ) {
		global $post;
		if ( !isset($post) )
			return;
		$redir = yoast_get_value('redirect', $post->ID);
		if (!empty($redir)) {
			wp_redirect($redir, 301);
			exit;
		}
	}
	
	function noindex_page() {
		echo "\t<!-- This site is optimized with the Yoast WordPress SEO plugin. -->\n";
		echo "\t".'<meta name="robots" content="noindex" />'."\n";
	}

	function noindex_feed() {
		echo '<xhtml:meta xmlns:xhtml="http://www.w3.org/1999/xhtml" name="robots" content="noindex" />'."\n";
	}

	function nofollow_link($output) {
		return str_replace('<a ','<a rel="nofollow" ',$output);
	}

	function echo_nofollow() {
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

	function archive_redirect() {
		global $wp_query;
		$options  = get_wpseo_options();
		if ( ($options['disabledate'] && $wp_query->is_date) || ($options['disableauthor'] && $wp_query->is_author) ) {
			wp_redirect(get_bloginfo('url'),301);
			exit;
		}
	}

	function attachment_redirect() {
		global $post;
		if ( is_attachment() ) {
			wp_redirect(get_permalink($post->post_parent), 301);
			exit;
		}
	}

	function add_trailingslash($url, $type) {
		// trailing slashes for everything except is_single()
		// Thanks to Mark Jaquith for this
		if ( 'single' === $type ) {
			return $url;
		} else {
			return trailingslashit($url);
		}
	}

	function clean_permalink( $headers ) {
		if ( is_robots() )
			return;

		global $wp_query;
		
		$options = get_wpseo_options();
	
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
			
			// Fixed paginated pages
			$page = get_query_var('page');
			if ( $page && $page != 1 ) {
				// If below doesn't return true, there actually aren't that much pages in the post.
				if ( substr_count($wp_query->queried_object->post_content, '<!--nextpage-->') >= ($page-1) )
					$properurl = user_trailingslashit( trailingslashit($properurl) . get_query_var('page') );
			}
				
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
			$s = preg_replace( '/(%20|\+)/', ' ', get_search_query() );
			$properurl = get_bloginfo('url').'/?s=' . rawurlencode( $s );
		}
		if ( !empty($properurl) && $wp_query->query_vars['paged'] != 0 && $wp_query->post_count != 0 ) {
			if ( is_search() ) {
				$properurl = get_bloginfo('url').'/page/' . $wp_query->query_vars['paged'] . '/?s=' . rawurlencode( $s );
			} else {
				$properurl = user_trailingslashit( trailingslashit($properurl). 'page/' . $wp_query->query_vars['paged'] );
			}
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

		if ( isset($options['cleanpermalink-extravars']) && strlen($options['cleanpermalink-extravars']) > 0 ) {
			foreach ( explode( ',', $options['cleanpermalink-extravars'] ) as $get ) {
				if ( isset($_GET[ trim( $get ) ]) ) {
					$properurl = '';
				}		
			}
		}
		
		if ( !empty($properurl) && $cururl != $properurl ) {	
			wp_redirect($properurl, 301);
			exit;
		}
	}

	function rss_replace_vars($temp) {
		$postlink = '<a href="'.get_permalink().'">'.get_the_title()."</a>";
		$bloglink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').'</a>';
		$blogdesclink = '<a href="'.get_bloginfo('url').'">'.get_bloginfo('name').' - '.get_bloginfo('description').'</a>';

		$temp = stripslashes($temp);
		$temp = str_replace("%%POSTLINK%%", $postlink, $temp);
		$temp = str_replace("%%BLOGLINK%%", $bloglink, $temp);		
		$temp = str_replace("%%BLOGDESCLINK%%", $blogdesclink, $temp);					
		return $temp;
	}

	function embed_rssfooter($content) {
		if(is_feed()) {
			$options  = get_wpseo_options();

			if ( isset($options['rssbefore']) && !empty($options['rssbefore']) ) {
				$content = "<p>" . $this->rss_replace_vars($options['rssbefore']) . "</p>" . $content;
			} 
			if ( isset($options['rssafter']) && !empty($options['rssafter']) ) {
				$content .= "<p>" . $this->rss_replace_vars($options['rssafter']). "</p>";
			} 
		}
		return $content;
	}

	function embed_rssfooter_excerpt($content) {
		if(is_feed()) {
			$options  = get_wpseo_options();

			if ( isset($options['rssbefore']) && !empty($options['rssbefore']) ) {
				$content = "<p>".$this->rss_replace_vars($options['rssbefore']) . "</p><p>" . $content ."</p>";
			} 
			if ( isset($options['rssafter']) && !empty($options['rssafter']) ) {
				$content = "<p>".$content."</p><p>".$this->rss_replace_vars($options['rssafter'])."</p>";
			} 
		}
		return $content;
	}
	
	function sitemap_output( $robots, $public ) {
		if ( !get_query_var('wpseo_sitemap') )
			return $robots;

		if ( !WPSEO_UPLOAD_DIR )
			return $robots;

		$file = WPSEO_UPLOAD_DIR . get_query_var('wpseo_sitemap') . get_query_var('wpseo_sitemap_gz');

		if ( file_exists( $file ) ) 
			$robots = file_get_contents( $file );
		else
			$robots = '';

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
	
	function flush_cache() {
		global $wp_query, $post, $wpseo_ob;

		if ( !$wpseo_ob )
			return;
			
		$content = ob_get_contents();
		$title = $this->title( '' );
		
		$content = preg_replace('/<title>(.*)<\/title>/','<title>'.$title.'</title>', $content);
		ob_end_clean();
		echo $content;
	}
	
	function force_rewrite_output_buffer() {
		global $wpseo_ob;
		$wpseo_ob = true;
		ob_start();
	}
}

$wpseo_front = new WPSEO_Frontend;
