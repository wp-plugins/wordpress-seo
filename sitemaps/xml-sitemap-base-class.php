<?php 

class WPSEO_XML_Sitemap_Base {

	function WPSEO_XML_Sitemap_Base() {
	}

	function generate_sitemap() {
	}
	
	function write_sitemap( $filename, $output ) {
		$f = fopen( WPSEO_UPLOAD_DIR.$filename, 'w+');
		fwrite($f, $output);
		fclose($f);

		if ( $this->gzip_sitemap( $filename, $output ) )
			return true;
		return false;
	}
	
	function gzip_sitemap( $filename, $output ) {
		$f = fopen( WPSEO_UPLOAD_DIR . $filename . '.gz', "w" );
		if ( $f ) {
			fwrite( $f, gzencode( $output , 9 ) );
			fclose( $f );
			return true;
		} 
		return false;
	}
	
	function ping_search_engines( $filename, $echo = false ) {
		$sitemapurl = urlencode( get_bloginfo('url') . '/' . $filename . '.gz');

		$resp = wp_remote_get('http://www.google.com/webmasters/tools/ping?sitemap='.$sitemapurl);

		if ($echo && $resp['response']['code'] == '200')
			echo 'Successfully notified Google of updated sitemap.<br/>';

		$appid = '3usdTDLV34HbjQpIBuzMM1UkECFl5KDN7fogidABihmHBfqaebDuZk1vpLDR64I-';
		$resp = wp_remote_get('http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid='.$appid.'&url='.$sitemapurl);

		if ($echo && $resp['response']['code'] == '200')
			echo 'Successfully notified Yahoo! of updated sitemap.<br/>';

		$resp = wp_remote_get('http://www.bing.com/webmaster/ping.aspx?sitemap='.$sitemapurl);

		if ($echo && $resp['response']['code'] == '200')
			echo 'Successfully notified Bing of updated sitemap.<br/>';	

		$resp = wp_remote_get('http://submissions.ask.com/ping?sitemap='.$sitemapurl);

		if ($echo && $resp['response']['code'] == '200')
			echo 'Successfully notified Ask.com of updated sitemap.<br/>';	
	}

	function w3c_date($time='') { 
	    if (empty($time)) 
	        $time = time();
		else
			$time = strtotime($time);
	    $offset = date("O",$time); 
	    return date("Y-m-d\TH:i:s",$time).substr($offset,0,3).":".substr($offset,-2); 
	}

	function xml_clean( $str ) {
		return ent2ncr( esc_html( str_replace ( "’", "&quot;", $str ) ) );
	}

	function make_image_local( $url, $post_id, $title, $type = '' ) {
		$tmp = download_url( $url );
		
		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);

		$title = sanitize_title( strtolower($title) );
		$file_array['name'] = $title . '.' . $matches[1];
		$file_array['tmp_name'] = $tmp;
		
		if ( is_wp_error($tmp) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
			return false;
		} else {
			$ret = media_handle_sideload($file_array, $post_id, 'Poster image for '.$type.' video in '.$title);
			if ( is_numeric($ret) )
				$ret = get_attachment_link($ret);
			return $ret;
		}
	}
	
	function update_video_meta( $post, $echo = false ) {
		global $shortcode_tags;

		$options = get_wpseo_options();
		
		$shortcode_tags = array( 		
			'blip.tv' => '',
			// 'dailymotion' => '',
			// 'flickrvideo' => '',
			// 'flash' => '',
			'flv' => '',
			// 'googlevideo' => '',
			// 'metacafe' => '',
			// 'myspace' => '',
			// 'quicktime' => '',
			// 'spike' => '',
			// 'veoh' => '',
			// 'videopress' => '',
			// 'viddler' => '',
			// 'videofile' => '',
			'vimeo' => '',
			// 'wpvideo' => '',
			'youtube' => '',
		);

		$oldvid = yoast_get_value('video_meta', $post->ID);
		
		if (preg_match('/'.get_shortcode_regex().'/', $post->post_content, $matches)) {
			$_GLOBALS['post'] 	= $post;

			if ($post->post_type == 'post') {
				$wp_query->is_single = true;
				$wp_query->is_page = false;
			} else {
				$wp_query->is_single = false;
				$wp_query->is_page = true;
			}
			// Grab the meta data from the post
			$cats = get_the_terms($post->ID, 'category');
			$tags = get_the_terms($post->ID, 'post_tag');
			$tag  = array();
			if (is_array($tags)) {
				foreach ($tags as $t) {
					$tag[] = $t->name;
				}				
			} else {
				$tag[] = $cats[0]->name;
			}
			
			$focuskw = yoast_get_value('focuskw', $post->ID);
			if (!empty($focuskw))
				$tag[] = $focuskw;
			
			$title = yoast_get_value('title', $post->ID);
			if (empty($title)) {
				$title = wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );
			}
			
			$vid 						= array();			
			$vid['loc'] 				= get_permalink($post->ID);
			$vid['title']				= $this->xml_clean($title);
			$vid['publication_date'] 	= $this->w3c_date($post->post_date);
			$vid['category']			= $cats[0]->name;
			$vid['tag']					= $tag;

			$vid['description'] 		= yoast_get_value('metadesc', $post->ID);
			if ( !$vid['description'] ) {
				$vid['description']	= $this->xml_clean(substr(preg_replace('/\s+/',' ',strip_tags(strip_shortcodes($post->post_content))), 0, 300));
			}

			preg_match('/image=(\'|")?([^\'"\s]+)(\'|")?/', $matches[3], $match);
			if (isset($match[2]) && !empty($match[2]))
				$vid['thumbnail_loc'] 	= $match[2];
			
			if (!isset($vid['thumbnail_loc']) && isset($oldvid['thumbnail_loc']))
				$vid['thumbnail_loc'] 	= $oldvid['thumbnail_loc'];
			
			if ($vid['thumbnail_loc'] == 'n')
				unset($vid['thumbnail_loc']);
				
			switch ($matches[2]) {
				case 'vimeo':
					$videoid 	= preg_replace('|http://(www\.)?vimeo\.com/|','',$matches[5]);
					$url 		= 'http://vimeo.com/api/v2/video/'.$videoid.'.php';
					$vimeo_info = wp_remote_get($url);
					$vimeo_info = unserialize($vimeo_info['body']);

					// echo '<pre>'.print_r($vimeo_info, 1).'</pre>';

					$vid['player_loc'] 		= 'http://www.vimeo.com/moogaloop.swf?clip_id='.$videoid;
					$vid['duration']		= $vimeo_info[0]['duration'];
					$vid['view_count']		= $vimeo_info[0]['stats_number_of_plays'];

					if (!isset($vid['thumbnail_loc']))
						$vid['thumbnail_loc'] 	= $this->make_image_local($vimeo_info[0]['thumbnail_medium'], $post->ID, $title, $matches[2]);
					break;
				case 'blip.tv':
					preg_match('|posts_id=(\d+)|', $matches[3], $match);
					$videoid	= $match[1];

					$blip_info	= wp_remote_get('http://blip.tv/rss/view/'.$videoid);
					$blip_info	= $blip_info['body'];

					preg_match("|<blip:runtime>([\d]+)</blip:runtime>|", $blip_info, $match);
					$vid['duration']		= $match[1];

					preg_match('|<media:player url="([^"]+)">|', $blip_info, $match);
					$vid['player_loc']		= $match[1];

					preg_match('|<enclosure length="[\d]+" type="[^"]+" url="([^"]+)"/>|', $blip_info, $match);
					$vid['content_loc']		= $match[1];

					preg_match('|<media:thumbnail url="([^"]+)"/>|', $blip_info, $match);

					if (!isset($vid['thumbnail_loc'])) {
						// $vid['thumbnail_loc']	=  $this->make_image_local($match[1], $post->ID, $title, $matches[2]);
						$vid['thumbnail_loc']	=  $match[1];
					}
					break;
				case 'youtube':					
					$videoid	= preg_replace('|http://(www\.)?youtube.com/(watch)?\?v=|','',$matches[5]);

					$youtube_info = wp_remote_get('http://gdata.youtube.com/feeds/api/videos/'.$videoid);
					$youtube_info = $youtube_info['body'];

					preg_match("|<yt:duration seconds='([\d]+)'/>|", $youtube_info, $match);
					$vid['duration']		= $match[1];

					$vid['player_loc']		= 'http://www.youtube-nocookie.com/v/'.$videoid;

					if (!isset($vid['thumbnail_loc']))
						$vid['thumbnail_loc']	= $this->make_image_local('http://img.youtube.com/vi/'.$videoid.'/0.jpg', $post->ID, $title, $matches[2]);
					break;
				case 'flv':
					// TODO add fallback poster image for when no poster image present
					$vid['content_loc']		= $matches[5];
					break;
				default:
					echo '<pre>'.print_r($matches,1).'</pre>';
					echo '<pre>'.print_r($vid,1).'</pre>';
					$vid = 'none';
					break;
			} 
			if ($echo)
				echo 'Video Metadata updated for '.$post->post_title.'<br/>';
		} else {
			$vid = 'none';
		}
		
		yoast_set_value( 'video_meta', $vid, $post->ID );
		return $vid;
	}
} 

