<?php

class WPSEO_Admin_Pages {

	var $currentoption  = 'wpseo';
	var $feed			= 'http://yoast.com/feed/';
	var $adminpages 	= array( 'wpseo_dashboard', 'wpseo_rss', 'wpseo_files', 'wpseo_permalinks', 'wpseo_internal-links', 'wpseo_import', 'wpseo_titles', 'wpseo_xml', 'wpseo_social');
	
	function __construct() {
		add_action( 'init', array( $this, 'init'), 20 );
	}
	
	function init() {
		if ( isset( $_GET['wpseo_reset_defaults']) ) {
			$this->reset_defaults();
			wp_redirect( admin_url('admin.php?page=wpseo_dashboard') );
		}

		global $wpseo_admin, $pagenow;
		
		if ( $wpseo_admin->grant_access() ) {
			add_action( 'admin_print_scripts', array( $this,'config_page_scripts') );
			add_action( 'admin_print_styles', array( $this,'config_page_styles') );	
		}
	}

	function reset_defaults() {
		foreach ( get_wpseo_options_arr() as $opt ) {
			delete_option( $opt );
		}
		wpseo_defaults();

		wpseo_title_test();
	}
	
	function admin_sidebar() {
	?>
		<div class="postbox-container" style="width:25%;max-width:250px;">
			<div id="sidebar">	
					<?php
						$this->postbox('sitereview','<span class="promo">'.__('Improve your Site!','wordpress-seo').'</span>','<p>'.sprintf( __('Don\'t know where to start? Order a %1$swebsite review%2$s from Yoast!','wordpress-seo'), '<a href="http://yoast.com/hire-me/website-review/#utm_source=wpadmin&utm_medium=sidebanner&utm_term=link&utm_campaign=wpseoplugin">', '</a>').'</p>'.'<p><a class="button-primary" href="http://yoast.com/hire-me/website-review/#utm_source=wpadmin&utm_medium=sidebanner&utm_term=button&utm_campaign=wpseoplugin">'.__('Read more &raquo;','wordpress-seo').'</a></p>');
						$this->plugin_support();
						$this->postbox('donate','<span class="promo">'.__( 'Spread the Word!', 'wordpress-seo' ).'</span>','<p>'.__( 'Want to help make this plugin even better? All donations are used to improve this plugin, so donate $10, $20 or $50 now!', 'wordpress-seo' ).'</p><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="83KQ269Q2SR82">
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit">
						</form>'
						.'<p>'.__('Or you could:','wordpress-seo').'</p>'
						.'<ul>'
						.'<li><a href="http://wordpress.org/extend/plugins/wordpress-seo/">'.__('Rate the plugin 5★ on WordPress.org','wordpress-seo').'</a></li>'
						.'<li><a href="http://yoast.com/wordpress/seo/#utm_source=wpadmin&utm_medium=sidebanner&utm_term=link&utm_campaign=wpseoplugin">'.__('Blog about it & link to the plugin page','wordpress-seo').'</a></li>'
						.'</ul>');
						$this->news(); 
					?>
				<br/><br/><br/>
			</div>
		</div>
	<?php
	}
	
	function admin_header($title, $expl = true, $form = true, $option = 'yoast_wpseo_options', $optionshort = 'wpseo', $contains_files = false) {
		?>
		<div class="wrap">
			<?php 
			if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {
				$msg = __('Settings updated', 'wordpress-seo' );

				if ( function_exists('w3tc_pgcache_flush') ) {
					w3tc_pgcache_flush();
					$msg .= __(' &amp; W3 Total Cache Page Cache flushed', 'wordpress-seo' );
				} else if (function_exists('wp_cache_clear_cache')) {
					wp_cache_clear_cache();
					$msg .= __(' &amp; WP Super Cache flushed', 'wordpress-seo' );
				}

				// flush rewrite rules if XML sitemap settings have been updated.
				if ( isset($_GET['page']) && 'wpseo_xml' == $_GET['page'] )
					flush_rewrite_rules();

				echo '<div id="message" style="width:94%;" class="message updated"><p><strong>'.$msg.'.</strong></p></div>';
			}  
			?>
			<a href="http://yoast.com/"><div id="yoast-icon" style="background: url(<?php echo WPSEO_URL; ?>images/wordpress-SEO-32x32.png) no-repeat;" class="icon32"><br /></div></a>
			<h2 id="wpseo-title"><?php _e("Yoast WordPress SEO: ", 'wordpress-seo' ); echo $title; ?></h2>
			<div id="wpseo_content_top" class="postbox-container" style="width:75%;">
				<div class="metabox-holder">	
					<div class="meta-box-sortables">
		<?php
		if ($form) {
			echo '<form action="'.admin_url('options.php').'" method="post" id="wpseo-conf"' . ($contains_files ? ' enctype="multipart/form-data"' : '') . '>';
			settings_fields($option); 
			$this->currentoption = $optionshort;
			// Set some of the ignore booleans here to prevent unsetting.
		}
		
	}
	
	function admin_footer($title, $submit = true) {
		if ($submit) {
		?>
						<div class="submit"><input type="submit" class="button-primary" name="submit" value="<?php _e("Save Settings", 'wordpress-seo'); ?>" /></div>
		<?php } ?>
						</form>
					</div>
				</div>
			</div>
			<?php $this->admin_sidebar(); ?>
		</div>				
		<?php
	}

	function replace_meta($old_metakey, $new_metakey, $replace = false) {
		global $wpdb;
		$oldies = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = '$old_metakey'");
		foreach ($oldies as $old) {
			// Prevent inserting new meta values for posts that already have a value for that new meta key
			$check = $wpdb->get_var("SELECT count(*) FROM $wpdb->postmeta WHERE meta_key = '$new_metakey' AND post_id = ".$old->post_id);
			if ($check == 0)
				$wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES (".$old->post_id.",'".$new_metakey."','".addslashes($old->meta_value)."')");
		}
		
		if ($replace) {
			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '$old_metakey'");
		}
	}
	
	function delete_meta($metakey) {
		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '$metakey'");
	}
	
	function export_settings( $include_taxonomy ) {
	    $content = "; ".__( "This is a settings export file for the WordPress SEO plugin by Yoast.com", 'wordpress-seo' )." - http://yoast.com/wordpress/seo/ \r\n"; 

		$optarr = get_wpseo_options_arr();

		foreach ($optarr as $optgroup) {
			$content .= "\n".'['.$optgroup.']'."\n";
			$options = get_option($optgroup);
			if (!is_array($options))
				continue;
		    foreach ($options as $key => $elem) { 
		        if( is_array($elem) ) { 
		            for($i=0;$i<count($elem);$i++)  { 
		                $content .= $key."[] = \"".$elem[$i]."\"\n"; 
		            } 
		        } 
		        else if($elem=="") 
					$content .= $key." = \n"; 
		        else 
					$content .= $key." = \"".$elem."\"\n"; 
		    }		
		}

		if ( $include_taxonomy ) {
			$content .= "\r\n\r\n[wpseo_taxonomy_meta]\r\n";
			$content .= "wpseo_taxonomy_meta = \"".urlencode( json_encode( get_option('wpseo_taxonomy_meta') ) )."\"";
		}

		$dir = wp_upload_dir();

	    if ( !$handle = fopen( $dir['path'].'/settings.ini', 'w' ) )
	        die();

	    if ( !fwrite($handle, $content) ) 
	        die();

	    fclose($handle);

		require_once (ABSPATH . 'wp-admin/includes/class-pclzip.php');

		chdir( $dir['path'] );
		$zip = new PclZip('./settings.zip');
		if ($zip->create('./settings.ini') == 0)
		  	return false;

		return $dir['url'].'/settings.zip'; 
	}

	function config_page_styles() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset($_GET['page']) && in_array($_GET['page'], $this->adminpages) ) {
			wp_enqueue_style('dashboard');
			wp_enqueue_style('thickbox');
			wp_enqueue_style('global');
			wp_enqueue_style('wp-admin');
			wp_enqueue_style('yoast-admin-css', WPSEO_URL . 'css/yst_plugin_tools.css', WPSEO_VERSION );
		}
	}

	function config_page_scripts() {
		global $pagenow;
		if ( $pagenow == 'admin.php' && isset($_GET['page']) && in_array($_GET['page'], $this->adminpages) ) {
			wp_enqueue_script( 'wpseo-admin-script', WPSEO_URL.'js/wp-seo-admin.js', array('jquery'), WPSEO_VERSION, true );
			wp_enqueue_script( 'postbox' );
			wp_enqueue_script( 'dashboard' );
			wp_enqueue_script( 'thickbox' );
		}
	}

	/**
	 * Create a Checkbox input field
	 */
	function checkbox($id, $label, $label_left = false, $option = '') {
		if ( $option == '' && $this->currentoption != '' ) {
			$options = get_option( $this->currentoption );
			$option = $this->currentoption;
		} else if ( $option == ''  && $this->currentoption != '' ) {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}

		if (!isset($options[$id]))
			$options[$id] = false;
		
		if ( $label_left !== false ) {
			if ( !empty( $label_left ) )
				$label_left .= ':';
			$output_label = '<label class="checkbox" for="'.$id.'">'.$label_left.'</label>';
			$class 		  = 'checkbox';
		} else {
			$output_label = '<label for="'.$id.'">'.$label.'</label>';
			$class 		  = 'checkbox double';
		}
		
		$output_input = "<input class='$class' type='checkbox' id='${id}' name='${option}[${id}]' ".checked($options[$id],'on',false).'/>';
		
		if( $label_left !== false ) {
			$output = $output_label . $output_input . '<label class="checkbox" for="'.$id.'">'.$label.'</label>';
		} else {
			$output = $output_input . $output_label;
		}
		return $output . '<br class="clear" />';
	}
	
	/**
	 * Create a Text input field
	 */
	function textinput($id, $label, $option = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}
		
		$val = '';
		if (isset($options[$id]))
			$val = _wp_specialchars($options[$id]);
		
		return '<label class="textinput" for="'.$id.'">'.$label.':</label><input class="textinput" type="text" id="'.$id.'" name="'.$option.'['.$id.']" value="'.$val.'"/>' . '<br class="clear" />';
	}
	
	/**
	 * Create a small textarea
	 */
	function textarea($id, $label, $option = '', $class = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}
		
		$val = '';
		if (isset($options[$id]))
			$val = esc_html($options[$id]);
		
		return '<label class="textinput" for="'.$id.'">'.$label.':</label><textarea class="textinput '.$class.'" id="'.$id.'" name="'.$option.'['.$id.']">' . $val . '</textarea>' . '<br class="clear" />';
	}
	
	/**
	 * Create a Hidden input field
	 */
	function hiddeninput($id, $option = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}
		
		$val = '';
		if (isset($options[$id]))
			$val = _wp_specialchars($options[$id]);
		return '<input class="hidden" type="hidden" id="'.$id.'" name="'.$option.'['.$id.']" value="'.$val.'"/>';
	}
	
	/**
	 * Create a Select Box
	 */
	function select($id, $label, $values, $option = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}
		
		$output = '<label class="select" for="'.$id.'">'.$label.':</label>';
		$output .= '<select class="select" name="'.$option.'['.$id.']" id="'.$id.'">';
		
		foreach($values as $value => $label) {
			$sel = '';
			if (isset($options[$id]) && $options[$id] == $value)
				$sel = 'selected="selected" ';

			if (!empty($label))
				$output .= '<option '.$sel.'value="'.$value.'">'.$label.'</option>';
		}
		$output .= '</select>';
		return $output . '<br class="clear"/>';
	}
	
	/**
	 * Create a File upload
	 */
	function file_upload($id, $label, $option = '') {
		$option = !empty($option) ? $option : $this->currentoption;
		$options = get_wpseo_options();
		
		$val = '';
		if (isset($options[$id]) && strtolower(gettype($options[$id])) == 'array') {
			$val = $options[$id]['url'];
		}
		$output = '<label class="select" for="'.$id.'">'.$label.':</label>';
		$output .= '<input type="file" value="' . $val . '" class="textinput" name="'.$option.'['.$id.']" id="'.$id.'"/>';
		
		// Need to save separate array items in hidden inputs, because empty file inputs type will be deleted by settings API.
		if(!empty($options[$id])) {
			$output .= '<input class="hidden" type="hidden" id="' . $id . '_file" name="wpseo_local[' . $id . '][file]" value="' . $options[$id]['file'] . '"/>'; 
			$output .= '<input class="hidden" type="hidden" id="' . $id . '_url" name="wpseo_local[' . $id . '][url]" value="' . $options[$id]['url'] . '"/>'; 
			$output .= '<input class="hidden" type="hidden" id="' . $id . '_type" name="wpseo_local[' . $id . '][type]" value="' . $options[$id]['type'] . '"/>'; 
		}
		$output .= '<br class="clear"/>';
		
		return $output;
	}
	
	/**
	 * Create a Radio input field
	 */
	function radio($id, $values, $label, $option = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}
		
		if (!isset($options[$id]))
			$options[$id] = false;

		$output = '<br/><label class="select">'.$label.':</label>'; 
		foreach($values as $key => $value) {
			$output .= '<input type="radio" class="radio" id="'.$id.'-' . $key . '" name="'.$option.'['.$id.']" value="'. $key.'" ' . ($options[$id] == $key ? ' checked="checked"' : '') . ' /> <label class="radio" for="'.$id.'-' . $key . '">'.$value.'</label>';
		}
		$output .= '<br/>';
		
		return $output;
	}
	
	/**
	 * Create a hidden input field
	 */
	function hidden($id, $option = '') {
		if ( $option == '') {
			$options = get_wpseo_options();
			$option = !empty($option) ? $option : $this->currentoption;
		} else {
			if ( function_exists('is_network_admin') && is_network_admin() ) {
				$options = get_site_option($option);
			} else {
				$options = get_option($option);
			}
		}

		if (!isset($options[$id]))
			$options[$id] = '';
		
		return '<input type="hidden" id="hidden_'.$id.'" name="'.$option.'['.$id.']" value="'.$options[$id].'"/>';
	}

	/**
	 * Create a potbox widget
	 */
	function postbox($id, $title, $content) {
	?>
		<div id="<?php echo $id; ?>" class="yoastbox">
			<h2><?php echo $title; ?></h2>
			<?php echo $content; ?>
		</div>
	<?php
	}


	/**
	 * Create a form table from an array of rows
	 */
	function form_table($rows) {
		$content = '<table class="form-table">';
		foreach ($rows as $row) {
			$content .= '<tr><th valign="top" scrope="row">';
			if (isset($row['id']) && $row['id'] != '')
				$content .= '<label for="'.$row['id'].'">'.$row['label'].':</label>';
			else
				$content .= $row['label'];
			if (isset($row['desc']) && $row['desc'] != '')
				$content .= '<br/><small>'.$row['desc'].'</small>';
			$content .= '</th><td valign="top">';
			$content .= $row['content'];
			$content .= '</td></tr>'; 
		}
		$content .= '</table>';
		return $content;
	}

	/**
	 * Info box with link to the support forums.
	 */
	function plugin_support() {
		$content = '<p>'.__('If you are having problems with this plugin, please talk about them in the', 'wordpress-seo' ).' <a href="http://wordpress.org/support/plugin/wordpress-seo/">'.__("Support forums", 'wordpress-seo' ).'</a>.</p>';
		$this->postbox('support', __('Need support?', 'wordpress-seo' ), $content);
	}

	function fetch_rss_items( $num ) {
		include_once(ABSPATH . WPINC . '/feed.php');
		$rss = fetch_feed( $this->feed );
		
		// Bail if feed doesn't work
		if ( is_wp_error($rss) )
			return false;
		
		$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
		
		// If the feed was erroneous 
		if ( !$rss_items ) {
			$md5 = md5( $this->feed );
			delete_transient( 'feed_' . $md5 );
			delete_transient( 'feed_mod_' . $md5 );
			$rss = fetch_feed( $this->feed );
			$rss_items = $rss->get_items( 0, $rss->get_item_quantity( $num ) );
		}
		
		return $rss_items;
	}
	
	/**
	 * Box with latest news from Yoast.com for sidebar
	 */
	function news() {
		$rss_items = $this->fetch_rss_items( 3 );
		
		$content = '<ul>';
		if ( !$rss_items ) {
		    $content .= '<li class="yoast">'.__( 'No news items, feed might be broken...', 'wordpress-seo' ).'</li>';
		} else {
		    foreach ( $rss_items as $item ) {
		    	$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls=null, 'display' ) );
				$content .= '<li class="yoast">';
				$content .= '<a class="rsswidget" href="'.$url.'#utm_source=wpadmin&utm_medium=sidebarwidget&utm_term=newsitem&utm_campaign=wpseoplugin">'. esc_html( $item->get_title() ) .'</a> ';
				$content .= '</li>';
		    }
		}						
		$content .= '<li class="facebook"><a href="https://www.facebook.com/yoast">'.__( 'Like Yoast on Facebook', 'wordpress-seo' ).'</a></li>';
		$content .= '<li class="twitter"><a href="http://twitter.com/yoast">'.__( 'Follow Yoast on Twitter', 'wordpress-seo' ).'</a></li>';
		$content .= '<li class="googleplus"><a href="https://plus.google.com/115369062315673853712/posts">'.__( 'Circle Yoast on Google+', 'wordpress-seo' ).'</a></li>';
		$content .= '<li class="email"><a href="http://yoast.com/wordpress-newsletter/">'.__( 'Subscribe by email', 'wordpress-seo' ).'</a></li>';
		$content .= '</ul>';
		$this->postbox('yoastlatest', __( 'Latest news from Yoast', 'wordpress-seo' ), $content);
	}
	
} // end class WPSEO_Admin
global $wpseo_admin_pages;
$wpseo_admin_pages = new WPSEO_Admin_Pages();
