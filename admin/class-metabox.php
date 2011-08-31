<?php

class WPSEO_Metabox {
	
	var $wpseo_meta_length = 156;
	var $wpseo_meta_length_reason = '';
	
	function __construct() {
		$options = get_wpseo_options();

		add_action( 'add_meta_boxes',                  array( $this, 'add_meta_box' ) );
		add_action( 'admin_print_styles-post-new.php', array( $this, 'enqueue'      ) );
		add_action( 'admin_print_styles-post.php',     array( $this, 'enqueue'      ) );

		add_action( 'admin_head', array( $this, 'script') );

		add_action('add_meta_boxes', array(&$this, 'add_custom_box') );

		add_action('save_post', array($this,'save_postdata') );
		
		add_filter('manage_page_posts_columns',array($this,'page_title_column_heading'),10,1);
		add_filter('manage_post_posts_columns',array($this,'page_title_column_heading'),10,1);
		add_action('manage_pages_custom_column',array($this,'page_title_column_content'), 10, 2);
		add_action('manage_posts_custom_column',array($this,'page_title_column_content'), 10, 2);
	}

	public function add_custom_box() {
		$options = get_wpseo_options();

		foreach ( get_post_types() as $posttype ) {
			if ( in_array( $posttype, array('revision','nav_menu_item','attachment') ) )
				continue;
			if ( isset($options['hideeditbox-'.$posttype]) && $options['hideeditbox-'.$posttype] )
				continue;
			add_meta_box( 'wpseo_meta', 'WordPress SEO by Yoast', array( $this, 'meta_box' ), $posttype, 'normal', 'high' );
		}
	}
	
	public function script() {
		global $post;
		if ( !isset($post) )
			return;
			
		$options = get_wpseo_options();
		
		$date = '';
		if ( $post->post_type == 'post' && ( !isset($options['disabledatesnippet']) || !$options['disabledatesnippet'] ) ) {
			$date = $this->get_post_date( $post );

			$this->wpseo_meta_length = $this->wpseo_meta_length - (strlen($date)+5);
			$this->wpseo_meta_length_reason = ' (because of date display)';
		}
		unset($date);
		
		$wpseo_meta_length = apply_filters('wpseo_metadesc_length', $this->wpseo_meta_length );

		$title_template = '';
		if ( isset( $options['title-'.$post->post_type] ) )
			$title_template = $options['title-'.$post->post_type];
			
		// If there's no title template set, use the default, otherwise title preview won't work.
		if ( $title_template == '' )
			$title_template = '%%title%% - %%sitename%%';
		$title_template = wpseo_replace_vars( $title_template, $post, array('%%title%%') );

		$metadesc_template = '';
		if ( isset( $options['metadesc-'.$post->post_type] ) )
			$metadesc_template = wpseo_replace_vars( $options['metadesc-'.$post->post_type], $post, array( '%%excerpt%%', '%%excerpt_only%%' ) );
		
		$sample_permalink = get_sample_permalink( $post->ID );
		$sample_permalink = str_replace('%page','%post',$sample_permalink[0]);
		?>
		<script type="text/javascript">
			var wpseo_lang ='<?php echo substr(get_locale(),0,2); ?>';
			var wpseo_meta_desc_length = '<?php echo $this->wpseo_meta_length; ?>';
			var wpseo_title_template = '<?php echo $title_template; ?>';
			var wpseo_metadesc_template = '<?php echo $metadesc_template; ?>';
			var wpseo_permalink_template = '<?php echo $sample_permalink; ?>'
		</script>
		<?php
	}
	
	public function add_meta_box() {
		$options = get_wpseo_options();
		
		foreach ( get_post_types() as $posttype ) {
			if ( in_array( $posttype, array('revision','nav_menu_item','post_format','attachment') ) )
				continue;
			if ( isset($options['hideeditbox-'.$posttype]) && $options['hideeditbox-'.$posttype] )
				continue;
			add_meta_box( 'wpseo_meta', 'WordPress SEO by Yoast', array( $this, 'meta_box' ), $posttype, 'normal', 'high' );
		}
	}
	
	public function do_tab( $id, $heading, $content ) {
?>
	<div class="<?php echo $id ?>">
		<h4 class="wpseo-heading"><?php echo $heading ?></h4>
		<table class="form-table">
			<?php echo $content ?>
		</table>
	</div>
<?php		
	}
	
	public function get_meta_boxes( $post_type = 'post' ) {
		global $post;
		
		$options = get_wpseo_options();

		$mbs = array();
		$mbs['snippetpreview'] = array(
			"name" => "snippetpreview",
			"type" => "snippetpreview",
			"title" => __("Snippet Preview"),
		);
		$mbs['focuskw'] = array(
			"name" => "focuskw",
			"std" => "",
			"type" => "text",
			"title" => __("Focus Keyword"),
			"description" => "<div class='alignright' style='width: 300px;'>"
			."<a class='preview button' id='wpseo_relatedkeywords' href='#wpseo_tag_suggestions'>".__('Find related keywords')."</a> "
			."<p id='related_keywords_heading'>".__('Related keywords:')."</p><div id='wpseo_tag_suggestions'></div></div><div id='focuskwresults'><p>".__("What is the main keyword or key phrase this page should be found for?")."</p></div>",
			"autocomplete" => "off",
		);
		$mbs['title'] = array(
			"name" => "title",
			"std" => "",
			"type" => "text",
			"title" => __("SEO Title"),
			"description" => '<div class="alignright" style="padding:5px;"><a class="button" href="#snippetpreview" id="wpseo_regen_title">'.__('Generate SEO title').'</a></div><p>'
				.__("Title display in search engines is limited to 70 chars").", <span id='yoast_wpseo_title-length'></span> ".__("chars left.")."<br/>"
				.__("If the SEO Title is empty, the preview shows what the plugin generates based on your ")
				."<a target='_blank' href='".admin_url('admin.php?page=wpseo_titles#'.$post_type)."'>".__("title template")."</a>.".'</p>',
		);
		$mbs['metadesc'] = array(
			"name" => "metadesc",
			"std" => "",
			"class" => "metadesc",
			"type" => "textarea",
			"title" => __("Meta Description"),
			"rows" => 2,
			"richedit" => false,
			"description" => "The <code>meta</code> description will be limited to ".$this->wpseo_meta_length." chars".$this->wpseo_meta_length_reason.", <span id='yoast_wpseo_metadesc-length'></span> chars left. <div id='yoast_wpseo_metadesc_notice'></div>"."<p>If the meta description is empty, the preview shows what the plugin generates based on your <a target='_blank' href='".admin_url('admin.php?page=wpseo_titles#'.$post_type)."'>meta description template</a>.</p>"
		);
		if ( isset($options['usemetakeywords']) && $options['usemetakeywords'] ) {
			$mbs['metakeywords'] = array(
				"name" => "metakeywords",
				"std" => "",
				"class" => "metakeywords",
				"type" => "text",
				"title" => __("Meta Keywords"),
				"description" => "If you type something above it will override your <a target='_blank' href='".admin_url('admin.php?page=wpseo_titles#'.$post_type)."'>meta keywords template</a>."
			);
		}
		
		// Apply filters before entering the advanced section
		$mbs = apply_filters('wpseo_metabox_entries', $mbs);

		return $mbs;
	}
	
	function get_advanced_meta_boxes() {
		$options = get_wpseo_options();
		
		$mbs = array();
		
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
		if (isset($options['enablexmlsitemap']) && $options['enablexmlsitemap']) {		
			$mbs['sitemap-include'] = array(
				"name" => "sitemap-include",
				"std" => "-",
				"type" => "select",
				"title" => __("Include in Sitemap"),
				"description" => __("Should this page be in the XML Sitemap at all times, regardless of Robots Meta settings?"),
				"options" => array(
					"-" => __("Auto detect"),
					"always" => __("Always include"),
					"never" => __("Never include"),
				),
			);
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
	
		// Apply filters for in advanced section
		$mbs = apply_filters('wpseo_metabox_entries_advanced', $mbs);

		return $mbs;
	}

	function meta_box() {
		global $post;

		$options = get_wpseo_options();
		
?>
	<div class="wpseo-metabox-tabs-div">
		<ul class="wpseo-metabox-tabs" id="wpseo-metabox-tabs">
			<li class="active general"><a class="active" href="javascript:void(null);">General</a></li>
			<li class="linkdex"><a href="javascript:void(null);">Page Analysis</a></li>
			<li class="advanced"><a href="javascript:void(null);">Advanced</a></li>
			<?php do_action('wpseo_tab_header'); ?>
		</ul>
<?php		
		$content = '';
		foreach( $this->get_meta_boxes($post->post_type) as $meta_box) {
			$content .= $this->do_meta_box( $meta_box );
		}
		$this->do_tab( 'general', 'General', $content );

		require WPSEO_PATH.'/admin/linkdex/linkdex.php';
		
		$linkdex = new Linkdex();
		$this->do_tab( 'linkdex', 'Page Analysis', $linkdex->output( $post ) );
		unset($linkdex);
		
		if ( ! isset($options['disableadvanced_meta']) || !$options['disableadvanced_meta'] ) {
			$content = '';
			foreach( $this->get_advanced_meta_boxes() as $meta_box ) {
				$content .= $this->do_meta_box( $meta_box );
			}
			$this->do_tab( 'advanced', 'Advanced', $content );
		}
		
		do_action('wpseo_tab_content');
		
		echo '</div>';
	}

	function do_meta_box( $meta_box ) {
		global $post;

		$content = '';

		if (!isset($meta_box['name'])) {
			$meta_box['name'] = '';
		} else {
			$meta_box_value = wpseo_get_value($meta_box['name']);
		}
	
		$class = '';
		if (!empty($meta_box['class']))
			$class = ' '.$meta_box['class'];

		if( ( !isset($meta_box_value) || empty($meta_box_value) ) && isset($meta_box['std']) )  
			$meta_box_value = $meta_box['std'];  

		$content .= '<tr>';
		$content .= '<th scope="row"><label for="yoast_wpseo_'.$meta_box['name'].'">'.$meta_box['title'].':</label></th>';  
		$content .= '<td>';		

		switch($meta_box['type']) { 
			case "snippetpreview":
				$content .= $this->snippet();
				break;
			case "text":
				$ac = '';
				if ( isset( $meta_box['autocomplete']) && $meta_box['autocomplete'] == 'off' )
					$ac = 'autocomplete="off" ';
				$content .= '<input type="text" id="yoast_wpseo_'.$meta_box['name'].'" '.$ac.'name="yoast_wpseo_'.$meta_box['name'].'" value="'.esc_attr($meta_box_value).'" class="large-text"/><br />';  
				break;
			case "textarea":
				$rows = 5;
				if (isset($meta_box['rows']))
					$rows = $meta_box['rows'];
				if (!isset($meta_box['richedit']) || $meta_box['richedit'] == true) {
					$content .= '<div class="editor_container">';
					wp_tiny_mce( true, array( "editor_selector" => $meta_box['name'].'_class' ) );
					$content .= '<textarea class="large-text '.$meta_box['name'].'_class" rows="'.$rows.'" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'">'.esc_html($meta_box_value).'</textarea>';
					$content .= '</div>';
				} else {
					$content .= '<textarea class="large-text" rows="3" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'">'.esc_html($meta_box_value).'</textarea>';
				}
				break;
			case "select":
				$content .= '<select name="yoast_wpseo_'.$meta_box['name'].'" id="yoast_wpseo_'.$meta_box['name'].'" class="yoast'.$class.'">';
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if ($meta_box_value == $val)
						$selected = 'selected="selected"';
					$content .= '<option '.$selected.' value="'.esc_attr($val).'">'.$option.'</option>';
				}
				$content .= '</select>';
				break;
			case "multiselect":
				$selectedarr = explode(',',$meta_box_value);
				$meta_box['options'] = array('none' => 'None') + $meta_box['options'];
				$content .= '<select multiple="multiple" size="'.count($meta_box['options']).'" style="height: '.(count($meta_box['options'])*16).'px;" name="yoast_wpseo_'.$meta_box['name'].'[]" id="yoast_wpseo_'.$meta_box['name'].'" class="yoast'.$class.'">';
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if (in_array($val, $selectedarr))
						$selected = 'selected="selected"';
					$content .= '<option '.$selected.' value="'.esc_attr($val).'">'.$option.'</option>';
				}
				$content .= '</select>';
				break;
			case "checkbox":
				$checked = '';
				if ($meta_box_value != 'off')
					$checked = 'checked="checked"';
				$content .= '<input type="checkbox" id="yoast_wpseo_'.$meta_box['name'].'" name="yoast_wpseo_'.$meta_box['name'].'" '.$checked.' class="yoast'.$class.'"/><br />';
				break;
			case "radio":
				if ($meta_box_value == '')
					$meta_box_value = $meta_box['std'];
				foreach ($meta_box['options'] as $val => $option) {
					$selected = '';
					if ($meta_box_value == $val)
						$selected = 'checked="checked"';
					$content .= '<input type="radio" '.$selected.' id="yoast_wpseo_'.$meta_box['name'].'_'.$val.'" name="yoast_wpseo_'.$meta_box['name'].'" value="'.esc_attr($val).'"/> <label for="yoast_wpseo_'.$meta_box['name'].'_'.$val.'">'.$option.'</label> ';
				}
				break;
			case "divtext":
				$content .= '<p>' . $meta_box['description'] . '</p>';
		}
		
		if ( isset($meta_box['description']) )
			$content .= '<p>'.$meta_box['description'].'</p>';
	
		$content .= '</td>';  
		$content .= '</tr>';	
		
		return $content;
	}
	
	function get_post_date( $post ) {
		if ( isset($post->post_date) && $post->post_status == 'publish' )
			$date = date('j M Y', strtotime($post->post_date) );
		else 
			$date = date('j M Y');
		return $date;
	}
	
	function snippet() {
		global $post;
		
		$options = get_wpseo_options();
		
		$content = '';
		
		// TODO: make this configurable per post type.
		$date = '';
		if ( $post->post_type == 'post' && ( !isset($options['disabledatesnippet']) || !$options['disabledatesnippet'] ) )
			$date = $this->get_post_date( $post );
		
		$title = wpseo_get_value('title');
		$desc = wpseo_get_value('metadesc');

		$slug = $post->post_name;
		if (empty($slug))
			$slug = sanitize_title($title);

		$video = wpseo_get_value('video_meta',$post->ID);
		if ( $video && $video != 'none' ) {
			// TODO: improve snippet display of video duration to include seconds for shorter video's
			// echo '<pre>'.print_r(wpseo_get_value('video_meta'),1).'</pre>';
		
		$content .= '<div id="wpseosnippet" class="video">
				<h4 style="margin:0;font-weight:normal;"><a class="title" href="#"><?php echo $title; ?></a></h4>
				<div style="margin:5px 10px 10px 0;width:82px;height:62px;float:left;">
					<img style="border: 1px solid blue;padding: 1px;width:80px;height:60px;" src="'.$video['thumbnail_loc'].'"/>
					<div style="margin-top:-23px;margin-right:4px;text-align:right"><img src="http://www.google.com/images/icons/sectionized_ui/play_c.gif" alt="" border="0" height="20" style="-moz-opacity:.88;filter:alpha(opacity=88);opacity:.88" width="20"></div>
				</div>
				<div style="float:left;width:440px;">
					<p style="color:#767676;font-size:13px;line-height:15px;">'.number_format($video['duration']/60).' mins - '.$date.'</p>
					<p style="color:#000;font-size:13px;line-height:15px;" class="desc"><span>'.$desc.'</span></p>
					<a href="#" class="url">'.str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/</a> - <a href="#" class="util">More videos &raquo;</a>
				</div>
			</div>';
		} else {
			if ( !empty($date) )
				$date .= ' – ';
			$content .= '<div id="wpseosnippet">
				<a class="title" href="#">'.$title.'</a><br/>
			<a href="#" style="font-size: 13px; color: #282; line-height: 15px;" class="url">'.str_replace('http://','',get_bloginfo('url')).'/'.$slug.'/</a> - <a href="#" class="util">Cached</a>
				<p class="desc" style="font-size: 13px; color: #000; line-height: 15px;">'.$date.'<span>'.$desc.'</span></p>
			</div>';
		} 
		return $content;
	}

	function save_postdata( $post_id ) {  
		
		if ( $post_id == null || empty($_POST) )
			return;

		if ( wp_is_post_revision( $post_id ) )
			$post_id = wp_is_post_revision( $post_id );
			
		if ( isset( $_POST['post_type'] ) ) {  
			if ( !current_user_can( 'edit_post', $post_id ) )  {
				return $post_id;  
			}
		} else {  
			if ( !current_user_can( 'edit_post', $post_id ))  
				return $post_id;  
		}  

		global $post;  
		if ( empty( $post ) )
			$post = get_post($post_id);

		$metaboxes = array_merge( $this->get_meta_boxes( $post->post_type ), $this->get_advanced_meta_boxes() );
		
		$metaboxes = apply_filters( 'wpseo_save_metaboxes', $metaboxes );
		
		foreach( $metaboxes as $meta_box ) {  
			if ( !isset($meta_box['name']) )
				continue;

			if ( 'checkbox' == $meta_box['type'] ) {
				if ( isset( $_POST['yoast_wpseo_'.$meta_box['name']] ) )
					$data = 'on';
				else
					$data = 'off';
			} else if ( 'multiselect' == $meta_box['type'] ) {
				if ( isset( $_POST['yoast_wpseo_'.$meta_box['name']] ) ) {
					if (is_array($_POST['yoast_wpseo_'.$meta_box['name']]))
						$data = implode( ",", $_POST['yoast_wpseo_'.$meta_box['name']] );
					else
						$data = $_POST['yoast_wpseo_'.$meta_box['name']];
				} else {
					continue;
				}
			} else {
				if ( isset($_POST['yoast_wpseo_'.$meta_box['name']]) )
					$data = $_POST['yoast_wpseo_'.$meta_box['name']];  
				else 
					continue;
			}

			$option = '_yoast_wpseo_'.$meta_box['name'];
			$oldval = get_post_meta($post_id, $option, true);

			update_post_meta($post_id, $option, $data, $oldval);  
		}  
		do_action('wpseo_saved_postdata');
	}

	public function enqueue() {
		$color = get_user_meta( get_current_user_id(), 'admin_color', true );

		wp_enqueue_style( 'metabox-tabs', WPSEO_URL.'css/metabox-tabs.css', WPSEO_VERSION );
		wp_enqueue_style( "metabox-$color", WPSEO_URL.'css/metabox-'.$color.'.css', WPSEO_VERSION );

		wp_enqueue_script( 'jquery-ui-autocomplete', WPSEO_URL.'js/jquery-ui-autocomplete.min.js', array( 'jquery', 'jquery-ui-core' ), WPSEO_VERSION, true );		
		wp_enqueue_script( 'wp-seo-metabox', WPSEO_URL.'js/wp-seo-metabox.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-autocomplete' ), WPSEO_VERSION, true );
	}

	function page_title_column_heading( $columns ) {
		return array_merge(array_slice($columns, 0, 6), array('page-meta-robots' => 'Robots Meta'), array_slice($columns, 6, count($columns)));
	}

	function page_title_column_content( $column_name, $id ) {
		// if ( $column_name == 'page-title' ) {
		// 	echo esc_html( $this->page_title($id) );
		// }
		if ( $column_name == 'page-meta-robots' ) {
			$robots 			= array();
			$robots['index'] 	= 'Index';
			$robots['follow'] 	= 'Follow';

			if ( wpseo_get_value('meta-robots-noindex') )
				$robots['index'] = 'Noindex';
			if ( wpseo_get_value('meta-robots-nofollow') )
				$robots['follow'] = 'Nofollow';
			
			echo $robots['index'].', '.$robots['follow'];
		}
	}	
	function page_title( $postid ) {
		$fixed_title = wpseo_get_value('title', $postid );
		if ($fixed_title) {
			return $fixed_title;
		} else {
			$post = get_post( $postid );
			$options = get_wpseo_options();
			if ( isset($options['title-'.$post->post_type]) && !empty($options['title-'.$post->post_type]) )
				return wpseo_replace_vars($options['title-'.$post->post_type], (array) $post );				
			else
				return wpseo_replace_vars('%%title%%', (array) $post );				
		}
	}
}
$wpseo_metabox = new WPSEO_Metabox();
