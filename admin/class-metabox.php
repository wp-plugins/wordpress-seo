<?php

class WPSEO_Metabox {
	
	function WPSEO_Metabox() {
		add_action('admin_print_scripts', array(&$this,'scripts'));
		add_action('admin_print_styles', array(&$this,'styles'));	
		
		// WPSC integration
		add_action('wpsc_edit_product', array(&$this,'rebuild_sitemap'));
		add_action('wpsc_rate_product', array(&$this,'rebuild_sitemap'));

		add_action('admin_menu', array(&$this,'yoast_wpseo_create_meta_box'));
		add_action('save_post', array(&$this,'yoast_wpseo_save_postdata'));
		
		add_action('save_post', array(&$this,'update_video_meta'));
	
		add_action('edit_post',array(&$this,'yoast_wpseo_save_inline_edit'),10,1);

		add_filter('manage_page_posts_columns',array(&$this,'yoast_wpseo_page_title_column_heading'),10,1);
		add_filter('manage_post_posts_columns',array(&$this,'yoast_wpseo_page_title_column_heading'),10,1);
		add_action('manage_pages_custom_column',array(&$this,'yoast_wpseo_page_title_column_content'), 10, 2);
		add_action('manage_posts_custom_column',array(&$this,'yoast_wpseo_page_title_column_content'), 10, 2);

		add_action('quick_edit_custom_box', array(&$this,'yoast_wpseo_quick_edit'), 10, 1 );
		add_action('get_inline_data',array(&$this,'yoast_wpseo_inline_edit'));
	}
	
	function scripts() {
		global $pagenow;
		
		if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php'))) {
			wp_enqueue_script('jquery-bgiframe', WPSEO_URL.'js/jquery.bgiframe.min.js', array('jquery'));
			wp_enqueue_script('jquery-autocomplete', WPSEO_URL.'js/jquery.autocomplete.min.js', array('jquery'));
			wp_enqueue_script('html5-placeholder', WPSEO_URL.'js/html5placeholder.jquery.min.js', array('jquery'));
			wp_enqueue_script('wp-seo-metabox', WPSEO_URL.'js/wp-seo-metabox.js', array('jquery','jquery-bgiframe','jquery-autocomplete'));
		} elseif ($pagenow == 'edit.php') {
			wp_enqueue_script('jquery-bgiframe', WPSEO_URL.'js/inline-edit.js',array('jquery'));
		}
	}
	
	function styles() {
		global $pagenow;
		
		if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php'))) {
			wp_enqueue_style('wp-seo-metabox', WPSEO_URL.'css/wp-seo-metabox.css');
		}
	}
	
	function get_meta_boxes( $post_type = 'post' ) {
		$options = get_wpseo_options();
		$mbs = array();
		$mbs['title'] = array(
			"name" => "title",
			"std" => "",
			"type" => "text",
			"title" => __("SEO Title"),
			"description" => __("The SEO title is limited to 70 chars, <span id='yoast_wpseo_title-length'></span> chars left. It's used in the <code>&lt;title&gt;</code> tag, unlike the page title, which is used on the page itself. This overwrites the post type's title template.")
		);
		$mbs['metadesc'] = array(
			"name" => "metadesc",
			"std" => "",
			"class" => "metadesc",
			"type" => "textarea",
			"title" => __("Meta Description"),
			"rows" => 2,
			"richedit" => false,
			"description" => "The <code>meta</code> description is limited to 160 chars, <span id='yoast_wpseo_description-length'></span> chars left. <div id='yoast_wpseo_metadesc_notice'></div>"
		);
		$mbs['focuskw'] = array(
			"name" => "focuskw",
			"std" => "",
			"type" => "text",
			"title" => __("Focus Keyword"),
			"description" => "<div class='alignright' style='width: 300px;'><a class='preview button' id='wpseo_relatedkeywords' href='#wpseo_tag_suggestions'>".__('Find related keywords')."</a><p id='related_keywords_heading'>".__('Related keywords:')."</p><div id='wpseo_tag_suggestions'></div></div><div id='focuskwresults'><p>".__("What is the main keyword or key phrase this page should be found for?")."</p></div>",
		);
		$mbs['advancedopen'] = array(
			"type" => "div",
			"id" => "advancedseo",
		);
		$mbs['meta-robots-noindex'] = array(
			"name" => "meta-robots-noindex",
			"std" => "index",
			"title" => __("Meta Robots Index"),
			"type" => "radio",
			"options" => array(
				"0" => __("Index"),
				"1" => __("Noindex"),
			),
		);
		$mbs['meta-robots-nofollow'] = array(
			"name" => "meta-robots-nofollow",
			"std" => "follow",
			"title" => __("Meta Robots Follow"),
			"type" => "radio",
			"options" => array(
				"0" => __("Follow"),
				"1" => __("Nofollow"),
			),
		);
		$mbs['meta-robots-adv'] = array(
			"name" => "meta-robots-adv",
			"std" => "none",
			"type" => "multiselect",
			"title" => __("Meta Robots Advanced"),
			"description" => __("Advanced <code>meta</code> robots settings for this page."),
			"options" => array(
				"noodp" => "NO ODP",
				"noydir" => "NO YDIR",
				"noarchive" => __("No Archive"),
				"nosnippet" => __("No Snippet"),
			),
		);
		if (isset($options['breadcrumbs-enable']) && $options['breadcrumbs-enable']) {
			$mbs['bctitle'] = array(
				"name" => "bctitle",
				"std" => "",
				"type" => "text",
				"title" => __("Breadcrumbs title"),
				"description" => __("Title to use for this page in breadcrumb paths"),
			);
		}
		if ($options['enablexmlsitemap']) {		
			$mbs['sitemap-prio'] = array(
				"name" => "sitemap-prio",
				"std" => "-",
				"type" => "select",
				"title" => __("Sitemap Priority"),
				"description" => __("The priority given to this page in the XML sitemap."),
				"options" => array(
					"-" => __("Automatic prioritization"),
					"1" => __("1 - Highest priority"),
					"0.9" => "0.9",
					"0.8" => "0.8 - ".__("Default for first tier pages"),
					"0.7" => "0.7",
					"0.6" => "0.6 - ".__("Default for second tier pages and posts"),
					"0.5" => "0.5 - ".__("Medium priority"),
					"0.4" => "0.4",
					"0.3" => "0.3",
					"0.2" => "0.2",
					"0.1" => "0.1 - ".__("Lowest priority"),
				),
			);
		}
		$mbs['canonical'] = array(
			"name" => "canonical",
			"std" => "",
			"type" => "text",
			"title" => "Canonical URL",
			"description" => "The canonical URL that this page should point to, leave empty to default to permalink. <a target='_blank' href='http://googlewebmastercentral.blogspot.com/2009/12/handling-legitimate-cross-domain.html'>Cross domain canonical</a> supported too."
		);
		$mbs['redirect'] = array(
			"name" => "redirect",
			"std" => "",
			"type" => "text",
			"title" => "301 Redirect",
			"description" => "The URL that this page should redirect to."
		);
		
		$mbs = apply_filters('wpseo_metabox_entries', $mbs);
		
		$mbs['advancedclose'] = array(
			"type" => "divclose",
			"id" => "advancedseo",
			"label" => "Advanced",
		);

		return $mbs;
	}

	function yoast_wpseo_meta_boxes() {
		global $post;

		echo '<script type="text/javascript">var lang = "'.substr(get_locale(),0,2).'";</script>';

		// echo '<pre>'.print_r(get_post_custom($post->ID),1).'</pre>';
		// echo '<pre>'.print_r(get_post($post->ID),1).'</pre>';
		$date = '';
		if ($post->post_type == 'post') {
			if ( isset($post->post_date) )
				$date = date('M j, Y', strtotime($post->post_date));
			else 
				$date = date('M j, Y');
		}
		
		echo '<table class="yoasttable">';
		
		$title = yoast_get_value('title');
		if (empty($title))
			$title = $post->post_title;
		if (empty($title))
			$title = "temp title";
			
		$desc = yoast_get_value('metadesc');
		if (empty($desc))
			$desc = substr(strip_tags($post->post_content), 0, 130).' ...';
		if (empty($desc))
			$desc = 'temp description';
			
		$slug = $post->post_name;
		if (empty($slug))
			$slug = sanitize_title($title);
		
?>
	<tr>
		<th><label>Snippet Preview:</label></th>
		<td>
<?php 
		$video = yoast_get_value('video_meta',$post->ID);
		if ( $video && $video != 'none' ) {
			// TODO: improve snippet display of video duration to include seconds for shorter video's
			// echo '<pre>'.print_r(yoast_get_value('video_meta'),1).'</pre>';
?>
			<div id="snippet" class="video">
				<h4 style="margin:0;font-weight:normal;"><a class="title" href="#"><?php echo $title; ?></a></h4>
				<div style="margin:5px 10px 10px 0;width:82px;height:62px;float:left;">
					<img style="border: 1px solid blue;padding: 1px;width:80px;height:60px;" src="<?php echo $video['thumbnail_loc']; ?>"/>
					<div style="margin-top:-23px;margin-right:4px;text-align:right"><img src="http://www.google.com/images/icons/sectionized_ui/play_c.gif" alt="" border="0" height="20" style="-moz-opacity:.88;filter:alpha(opacity=88);opacity:.88" width="20"></div>
				</div>
				<div style="float:left;width:440px;">
					<p style="color:#767676;font-size:13px;line-height:15px;"><?php echo number_format($video['duration']/60); ?> mins - <?php echo $date; ?></p>
					<p style="color:#000;font-size:13px;line-height:15px;" class="desc"><span><?php echo $desc; ?></span></p>
					<a href="#" class="url"><?php echo str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/'; ?></a> - <a href="#" class="util">More videos &raquo;</a>
				</div>
			</div>
			
<?php
		} else {
			if (!empty($date))
				$date .= ' ... ';
?>
			<div id="snippet">
				<a class="title" href="#"><?php echo $title; ?></a>
				<p class="desc" style="font-size: 13px; color: #000; line-height: 15px;"><?php echo $date; ?><span><?php echo $desc ?></span></p>
				<a href="#" style="font-size: 13px; color: #282; line-height: 15px;" class="url"><?php echo str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/'; ?></a> - <a href="#" class="util">Cached</a> - <a href="#" class="util">Similar</a>
			</div>
<?php } ?>
		</td>
	</tr>
<?php
	
		foreach($this->get_meta_boxes($post->post_type) as $meta_box) {
			$this->yoast_wpseo_do_meta_box( $meta_box );
		}  
		echo '</table>';
	}

	function yoast_wpseo_create_meta_box() {  
		if ( function_exists('add_meta_box') ) {  
			foreach (get_post_types() as $posttype) {
				add_meta_box( 'yoast-wpseo-meta-box', 'Yoast WordPress SEO', array(&$this, 'yoast_wpseo_meta_boxes'), $posttype, 'normal', 'high' );  
			}
		}  
	}

	function yoast_wpseo_save_postdata( $post_id ) {  
		if ($post_id == null || empty($_POST))
			return;

		global $post;  
		if (empty($post))
			$post = get_post($post_id);

		foreach($this->get_meta_boxes($post->post_type) as $meta_box) {  
			// // Verify  
			// if ( !wp_verify_nonce( $_POST['yoast_wpseo_nonce'], 'yoast-wpseo-form-submit' )) {  
			// 	return $post;
			// }  

			if ( 'page' == $_POST['post_type'] ) {  
				if ( !current_user_can( 'edit_page', $post_id ))  
					return $post_id;  
			} else {  
				if ( !current_user_can( 'edit_post', $post_id ))  
					return $post_id;  
			}  

			$data = $_POST['yoast_wpseo_'.$meta_box['name']];  
			if ($meta_box['type'] == 'checkbox') {
				if (isset($_POST['yoast_wpseo_'.$meta_box['name']]))
					$data = true;
				else
					$date = false;
			} elseif ($meta_box['type'] == 'multiselect') {
				if (is_array($_POST['yoast_wpseo_'.$meta_box['name']]))
					$data = implode( ",", $_POST['yoast_wpseo_'.$meta_box['name']] );
				else
					$data = $_POST['yoast_wpseo_'.$meta_box['name']];
			}

			$option = '_yoast_wpseo_'.$meta_box['name'];
			$oldval = get_post_meta($post_id, $option);

			if($oldval == "")  
				add_post_meta($post_id, $option, $data, true);  
			elseif($data != $oldval)  
				update_post_meta($post_id, $option, $data);  
			elseif($data == "")  
				delete_post_meta($post_id, $option, $oldval);  
		}  
		do_action('wpseo_saved_postdata');
		$this->rebuild_sitemap();
	}

	function update_video_meta($post_id, $post = null) {
		$options = get_wpseo_options();
		if ( !$options['enablexmlvideositemap'])
			return;
			
		if (!is_object($post))
			$post = get_post($post_id);
			
		if ( !wp_is_post_revision($post) ) {
			require_once WPSEO_PATH.'/sitemaps/xml-sitemap-base-class.php';
			$wpseo_xml_base = new WPSEO_XML_Sitemap_Base();
			$wpseo_xml_base->update_video_meta($post);
			// echo '<pre>'.print_r(yoast_get_value( 'video_meta', $post->ID ), 1).'</pre>';			
		}
	}
	
	function rebuild_sitemap() {
		require_once WPSEO_PATH.'/sitemaps/xml-sitemap-class.php';
	}

	function ping_feed() {
		// require_once WPSEO_PATH.'/sitemaps/feed-class.php';
	}
	
	function rebuild_news_sitemap() {
		// require_once WPSEO_PATH.'/sitemaps/xml-news-sitemap-class.php';
	}

	function rebuild_video_sitemap() {
		// require_once WPSEO_PATH.'/sitemaps/xml-video-sitemap-class.php';
	}

	function yoast_wpseo_save_inline_edit($post_id) {
		if (!isset($_POST['_inline_edit']))
			return;

		update_post_meta($post_id, '_yoast_wpseo_title', $_POST['yoast_wpseo_page_title']);  
		update_post_meta($post_id, '_yoast_wpseo_meta-robots', $_POST['yoast_wpseo_page_meta_robots']);  

		if ( 'page' == $_POST['post_type'] ) {
			$post[] = get_post($_POST['post_ID']);
			page_rows($post);
		} elseif ( 'post' == $_POST['post_type'] || in_array($_POST['post_type'], get_post_types( array('public' => true) ) ) ) {
			$mode = $_POST['post_view'];
			$post[] = get_post($_POST['post_ID']);
			post_rows($post);
		}
	}

	function yoast_wpseo_page_title_column_heading( $columns ) {
		return array_merge(array_slice($columns, 0, 2), array('page-title' => 'Yoast SEO Title'), array_slice($columns, 2, 6), array('page-meta-robots' => 'Robots Meta'));
	}

	function yoast_wpseo_page_title_column_content( $column_name, $id ) {
		if ( $column_name == 'page-title' ) {
			echo $this->wpseo_page_title($id);
		}
		if ( $column_name == 'page-meta-robots' ) {
			$meta_robots = yoast_get_value( 'meta-robots', $id );
			if ( empty($meta_robots) )
				$meta_robots = 'index,follow';
			echo ucwords( str_replace( ',', ', ', $meta_robots ) );
		}
	}

	function yoast_wpseo_quick_edit( $column_name ) {
		global $screen;
		echo '<fieldset class="inline-edit-col-left">';
		echo '<div class="inline-edit-col">';
		if ( $column_name == 'page-title' ) {
			echo '<label>';
			echo '<span class="title">SEO Title</span>';
			echo '<span class="input-text-wrap"><input type="text" name="yoast_wpseo_page_title" class="ptitle" value=""/></span>';
			echo '</label>';
		} else if ( $column_name == 'page-meta-robots') {
			echo '<label for="yoast_wpseo_page_meta_robots">';
			echo '<span class="title">Robots</span>';
			echo '<select id="yoast_wpseo_page_meta_robots" name="yoast_wpseo_page_meta_robots">';
			foreach (array(
				"index,follow" => "Index, Follow",
				"index,nofollow" => "Index, Nofollow",
				"noindex,follow" => "Noindex, Follow",
				"noindex,nofollow" => "Noindex, Nofollow",
			) as $option => $val) {
				echo '<option value="'.$option.'">'.$val.'</option>';
			}
			echo '</select>';
			echo '</label>';
		}	
		echo '<br class="clear">';
		echo '</div>';
		echo '</fieldset>';
	}

	function yoast_wpseo_inline_edit($post) {
		echo '<div class="yoast_wpseo_page_meta_robots">'.yoast_get_value( 'meta-robots', $post->ID ).'</div>';
		echo '<div class="yoast_wpseo_page_title">'.yoast_get_value( 'title', $post->ID ).'</div>';
	}

	function yoast_wpseo_do_meta_box( $meta_box ) {
		global $post;
		if (!isset($meta_box['name'])) {
			$meta_box['name'] = '';
		} else {
			$meta_box_value = yoast_get_value($meta_box['name']);
		}
	
		$class = '';
		if (!empty($meta_box['class']))
			$class = ' '.$meta_box['class'];

		if( ( !isset($meta_box_value) || empty($meta_box_value) ) && isset($meta_box['std']) )  
			$meta_box_value = $meta_box['std'];  

		if ($meta_box['type'] != 'div' && $meta_box['type'] != 'divclose') {
			echo '<tr>';
			echo '<th><label for="yoast_wpseo_'.$meta_box['name'].'">'.$meta_box['title'].':</label></th>';  
			echo '<td>';		
		}
		switch($meta_box['type']) { 
			case "text":
				echo '<input type="text" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'" value="'.$meta_box_value.'" class="yoast'.$class.'"/><br />';  
				break;
			case "textarea":
				$rows = 5;
				if (isset($meta_box['rows']))
					$rows = $meta_box['rows'];
				if (!isset($meta_box['richedit']) || $meta_box['richedit'] == true) {
					echo '<div class="editor_container">';
					wp_tiny_mce( true, array( "editor_selector" => $meta_box['name'].'_class' ) );
					echo '<textarea class="yoast'.$class.' '.$meta_box['name'].'_class" rows="'.$rows.'" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'">'.$meta_box_value.'</textarea>';
					echo '</div>';
				} else {
					echo '<textarea class="yoast'.$class.'" rows="5" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'">'.$meta_box_value.'</textarea>';
				}
				break;
			case "select":
				echo '<select name="yoast_wpseo_'.$meta_box['name'].'" id="yoast_wpseo_'.$meta_box['name'].'" class="yoast'.$class.'">';
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if ($meta_box_value == $val)
						$selected = 'selected="selected"';
					echo '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
				}
				echo '</select>';
				break;
			case "multiselect":
				$selectedarr = explode(',',$meta_box_value);
				$meta_box['options'] = array('none' => 'None') + $meta_box['options'];
				echo '<select multiple="multiple" size="'.count($meta_box['options']).'" style="height: '.(count($meta_box['options'])*16).'px;" name="yoast_wpseo_'.$meta_box['name'].'[]" id="yoast_wpseo_'.$meta_box['name'].'" class="yoast'.$class.'">';
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if (in_array($val, $selectedarr))
						$selected = 'selected="selected"';
					echo '<option '.$selected.' value="'.$val.'">'.$option.'</option>';
				}
				echo '</select>';
				break;
			case "checkbox":
				$checked = '';
				if ($meta_box_value != false)
					$checked = 'checked="checked"';
				echo '<input type="checkbox" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'" '.$checked.' class="yoast'.$class.'"/><br />';
				break;
			case "radio":
				if ($meta_box_value == '')
					$meta_box_value = $meta_box['std'];
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if ($meta_box_value == $val)
						$selected = 'checked="checked"';
					echo '<input type="radio" '.$selected.' id="yoast_wpseo_'.$meta_box['name'].'_'.$val.'" name="yoast_wpseo_'.$meta_box['name'].'" value="'.$val.'"/> <label for="yoast_wpseo_'.$meta_box['name'].'_'.$val.'">'.$option.'</label> ';
				}
				break;
			case "div":
				echo '</table>';
				echo '<br class="clear"/>';
				echo '<div id="'.$meta_box['id'].'">';
				echo '<table class="yoasttable">';
				break;
			case "divclose":
				$tableopen = false;
				echo '</table>';
				echo '</div>';
				echo '<div class="divtoggle"><small><a href="" id="'.$meta_box['id'].'_open">'.$meta_box['label'].' &darr;</a></small></div>';
				break;
		}
		if ($meta_box['type'] != 'div' && $meta_box['type'] != 'divclose') {
			echo '<p>'.$meta_box['description'].'</p></td>';  
			echo '</tr>';	
		}
	}
	
	function wpseo_page_title( $postid ) {

		$fixed_title = yoast_get_value('title', $post->ID);
		if ($fixed_title) {
			return $fixed_title;
		} else {
			$options = get_wpseo_options();
			if (!empty($options['title-'.$post->post_type]))
				return wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );				
			else
				return '';
		}
	}
}
$wpseo_metabox = new WPSEO_Metabox();
