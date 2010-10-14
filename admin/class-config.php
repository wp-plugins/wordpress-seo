<?php
if ( ! class_exists( 'WPSEO_Admin' ) ) {
	
	class WPSEO_Admin extends Yoast_WPSEO_Plugin_Admin {

		var $hook 			= 'wordpress-seo';
		var $filename		= 'wordpress-seo/wp-seo.php';
		var $longname		= 'WordPress SEO Configuration';
		var $shortname		= 'SEO';
		var $currentoption 	= 'wpseo';
		var $ozhicon		= 'tag.png';
		
		function WPSEO_Admin() {
			add_action( 'admin_menu', array(&$this, 'register_settings_page') );
			add_filter( 'plugin_action_links', array(&$this, 'add_action_link'), 10, 2 );
			add_filter( 'ozh_adminmenu_icon', array(&$this, 'add_ozh_adminmenu_icon' ) );				
			
			add_action('admin_print_scripts', array(&$this,'config_page_scripts'));
			add_action('admin_print_styles', array(&$this,'config_page_styles'));	
			
			add_action('wp_dashboard_setup', array(&$this,'widget_setup'));	

			add_action('admin_init', array(&$this, 'options_init') );

			add_action('show_user_profile', array(&$this,'wpseo_user_profile'));
			add_action('edit_user_profile', array(&$this,'wpseo_user_profile'));
			add_action('personal_options_update', array(&$this,'wpseo_process_user_option_update'));
			add_action('edit_user_profile_update', array(&$this,'wpseo_process_user_option_update'));
						
			if ( '0' == get_option('blog_public') )
				add_action('admin_footer', array(&$this,'blog_public_warning'));		
		}

		function options_init(){
			register_setting( 'yoast_wpseo_options', 'wpseo' );
			register_setting( 'yoast_wpseo_indexation_options', 'wpseo_indexation' );
			register_setting( 'yoast_wpseo_permalinks_options', 'wpseo_permalinks' );
			register_setting( 'yoast_wpseo_titles_options', 'wpseo_titles' );
			register_setting( 'yoast_wpseo_rss_options', 'wpseo_rss' );
			register_setting( 'yoast_wpseo_internallinks_options', 'wpseo_internallinks' );
		}
				
		function blog_public_warning() {
			$options = get_option('wpseo');
			if ($options['blog_public_warning'] == 'nolonger')
				return;
			echo "<div id='message' class='error'><p><strong>Huge SEO Issue: You're blocking access to robots.</strong> You must <a href='options-privacy.php'>go to your Privacy settings</a> and set your blog visible to everyone. <a href='javascript:wpseo_hide_blog_public_warning()' class='button'>I know, don't bug me.</a></p></div>";
			echo "<script type='text/javascript'>
			function wpseo_hide_blog_public_warning() {
				var data = {
					action: 'wpseo_hide_blog_public_warning',
				}
				jQuery.post(ajaxurl, data, function(response) {
					if (response == 'nolonger')
						jQuery('#message.error').hide();
				});
			} </script>";
		}
		
		function admin_sidebar() {
		?>
			<div class="postbox-container" style="width:20%;">
				<div class="metabox-holder">	
					<div class="meta-box-sortables">
						<?php
							$this->plugin_like();
							$this->plugin_support();
							$this->postbox('wpseo','SEO &amp; WordPress','<p>'.__('If you haven\'t read it yet, my <a href="http://yoast.com/articles/wordpress-seo/">article on WordPress SEO</a> is probably a good place to start learning about how to optimize your WordPress.', 'yoast-wpseo').'</p>');
							$this->news(); 
						?>
					</div>
					<br/><br/><br/>
				</div>
			</div>
		<?php
		}
		
		function admin_header($title, $expl = true, $form = true, $option = 'yoast_wpseo_options', $optionshort = 'wpseo') {
			?>
			<div class="wrap">
				<?php 
				if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
					$msg = __('Settings updated');

					if ( function_exists('w3tc_pgcache_flush') ) {
						w3tc_pgcache_flush();
						$msg .= __(' &amp; W3 Total Cache Page Cache flushed');
					} else if (function_exists('wp_cache_clear_cache() ')) {
						wp_cache_clear_cache();
						$msg .= __(' &amp; WP Super Cache flushed');
					}
					
					echo '<div id="message" style="width:94%;" class="message updated"><p><strong>'.$msg.'.</strong></p></div>';
				}  
				?>
				<a href="http://yoast.com/"><div id="yoast-icon" style="background: url(http://netdna.yoast.com/wp-content/themes/yoast-v2/images/yoast-32x32.png) no-repeat;" class="icon32"><br /></div></a>
				<h2><?php _e("Yoast WordPress SEO: ".$title, 'yoast-wpseo'); ?></h2>
				<div class="postbox-container" style="width:70%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">
			<?php
			if ($form) {
				echo '<form action="options.php" method="post" id="wpseo-conf">';
				settings_fields($option); 
				$this->currentoption = $optionshort;
			}
			if ($expl)
				$this->postbox('pluginsettings',__('Plugin Settings', 'yoast-wpseo'),$this->checkbox('disableexplanation',__('Hide verbose explanations of settings', 'yoast-wpseo'))); 
			
		}
		
		function admin_footer($title, $submit = true) {
			if ($submit) {
			?>
							<div class="submit"><input type="submit" class="button-primary" name="submit" value="<?php _e("Save Yoast WordPress SEO ".$title." Settings", 'yoast-wpseo'); ?>" /></div>
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
		
		function import_page() {
			$msg = '';
			if ( isset($_POST['import']) ) {
				global $wpdb;
				$msg 		= '';
				$replace 	= false;
				$deletekw	= false;
				
				if (isset($_POST['wpseo']['deleteolddata']) && $_POST['wpseo']['deleteolddata'] == 'on') {
					$replace = true;
				}
				if (isset($_POST['wpseo']['deletekeywords']) && $_POST['wpseo']['deletekeywords'] == 'on') {
					$deletekw = true;
				}
				if ( isset($_POST['wpseo']['importheadspace']) ) {
					$this->replace_meta('_headspace_description', '_yoast_wpseo_metadesc', $replace);
					$this->replace_meta('_headspace_page_title', '_yoast_wpseo_title', $replace);
					$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts");
					foreach ($posts as $post) {
						$custom = get_post_custom($post->ID);
						if (isset($custom['_headspace_noindex'])) {
							$robotsmeta = 'noindex';
						} else {
							$robotsmeta = 'index';
						}
							
						if (isset($custom['_headspace_nofollow'])) {
							$robotsmeta .= ',nofollow';
						} else {
							$robotsmeta .= ',follow';
						}
						yoast_set_value('meta-robots', $robotsmeta, $post->ID);

						$robotsmeta_adv = '';
						if (isset($custom['_headspace_noarchive'])) {
							$robotsmeta_adv .= 'noarchive,';
						}
						if (isset($custom['_headspace_noodp'])) {
							$robotsmeta_adv .= 'noodp,';
						}
						if (isset($custom['_headspace_noydir'])) {
							$robotsmeta_adv .= 'noydir';
						}
						$robotsmeta_adv = preg_replace('/,$/','',$robotsmeta_adv);
						yoast_set_value('meta-robots-adv', $robotsmeta_adv, $post->ID);
						
						if ($replace) {
							foreach (array('noindex','nofollow','noarchive','noodp','noydir') as $meta) {
								delete_post_meta($post->ID, '_headspace_'.$meta);
							}
						}
					}
					$msg .= '<p>HeadSpace2 data successfully imported.</p>';
				} 
				if ( isset($_POST['wpseo']['importaioseo']) ) {
					$this->replace_meta('_aioseop_description', '_yoast_wpseo_metadesc', $replace);
					$this->replace_meta('_aioseop_title', '_yoast_wpseo_title', $replace);
					if ($deletekw)
						$this->delete_meta('_aioseop_keywords');
					$msg .= '<p>All in One SEO data successfully imported.</p>';
				}
				if ( isset($_POST['wpseo']['importaioseoold']) ) {
					$this->replace_meta('description', '_yoast_wpseo_metadesc', $replace);
					$this->replace_meta('title', '_yoast_wpseo_title', $replace);
					if ($deletekw)
						$this->delete_meta('keywords');
					$msg .= '<p>'.__('All in One SEO (Old version) data successfully imported.').'</p>';
				}
				if ( isset($_POST['wpseo']['importrobotsmeta']) ) {
					$posts = $wpdb->get_results("SELECT ID, robotsmeta FROM $wpdb->posts");
					$i = 0;
					foreach ($posts as $post) {
						if ($post->robotsmeta != '') {
							yoast_set_value('meta-robots', $post->robotsmeta, $post->ID);
							$i++;
						}
					}
					$msg .= '<p>'.__('Robots Meta values imported.').'</p>';
				}
				if ( isset($_POST['wpseo']['importrssfooter']) ) {
					$optold = get_option( 'RSSFooterOptions' );
					$optnew = get_option( 'wpseo_rss' );
					if ($optold['position'] == 'after') {
						if ( empty($optnew['rssafter']) )
							$optnew['rssafter'] = $optold['footerstring'];
					} else {
						if ( empty($optnew['rssbefore']) )
							$optnew['rssbefore'] = $optold['footerstring'];						
					}
					update_option( 'wpseo_rss', $optnew );
					$msg .= '<p>'.__('RSS Footer options imported successfully.').'</p>';
				}
				if ( isset($_POST['wpseo']['importbreadcrumbs']) ) {
					$optold = get_option( 'yoast_breadcrumbs' );
					$optnew = get_option( 'wpseo_internallinks' );

					if (is_array($optold)) {
						foreach ($optold as $opt => $val) {
							if (is_bool($val) && $val == true)
								$optnew['breadcrumbs-'.$opt] = 'on';
							else
								$optnew['breadcrumbs-'.$opt] = $val;
						}						
						update_option( 'wpseo_internallinks', $optnew );
						$msg .= '<p>'.__('Yoast Breadcrumbs options imported successfully.').'</p>';
					} else {
						$msg .= '<p>'.__('Yoast Breadcrumbs options could not be found').'</p>';
					}
				}
				if ($replace)
					$msg .= __(', and old data deleted');
				if ($deletekw)
					$msg .= __(', and meta keywords data deleted');
			}
			
			$this->admin_header('Import', false, false);
			if ($msg != '')
				echo '<div id="message" class="message updated" style="width:94%;">'.$msg.'</div>';
				
			$content = "<p>".__("No doubt you've used an SEO plugin before if this site isn't new. Let's make it easy on you, you can import the data below. If you want, you can import first, check if it was imported correctly, and then import &amp; delete. No duplicate data will be imported.")."</p>";
			$content .= '<p>A note on meta keywords data: it can be safely deleted: no search engine uses the meta keywords for any real ranking. Don\'t believe me? Read <a target="_blank" href="http://searchengineland.com/yahoo-search-no-longer-uses-meta-keywords-tag-27303">this article</a>, and then <a target="_blank" href="http://searchengineland.com/sorry-yahoo-you-do-index-the-meta-keywords-tag-27743">this article</a>.</p>';
			$content .= '<form action="" method="post">';
			$content .= $this->checkbox('importheadspace',__('Import from HeadSpace2?','yoast-wpseo'));
			$content .= $this->checkbox('importaioseo',__('Import from All-in-One SEO?','yoast-wpseo'));
			$content .= $this->checkbox('importaioseoold',__('Import from OLD All-in-One SEO?','yoast-wpseo'));
			$content .= '<br/>';
			$content .= $this->checkbox('deleteolddata',__('Delete the old data after import? (recommended)','yoast-wpseo'));
			$content .= $this->checkbox('deletekeywords',__('Delete meta keywords data? (recommended)','yoast-wpseo'));
			$content .= '<input type="submit" class="button-primary" name="import" value="Import" />';
			$content .= '<br/><br/>';
			$content .= '<form action="" method="post">';
			$content .= '<h2>Import settings from other plugins</h2>';
			$content .= $this->checkbox('importrobotsmeta',__('Import from Robots Meta (by Yoast)?','yoast-wpseo'));
			$content .= $this->checkbox('importrssfooter',__('Import from RSS Footer (by Yoast)?','yoast-wpseo'));
			$content .= $this->checkbox('importbreadcrumbs',__('Import from Yoast Breadcrumbs?','yoast-wpseo'));
			$content .= '<input type="submit" class="button-primary" name="import" value="Import" />';
			$content .= '</form>';
			
			$this->postbox('import',__('Import', 'yoast-wpseo'),$content); 
			$this->admin_footer('Import', false);
		}

		function titles_page() {
			$this->admin_header('Titles', false, true, 'yoast_wpseo_titles_options', 'wpseo_titles');
			$options = get_wpseo_options();
			$content = '<p>'.__('Be aware that for WordPress SEO to be able to modify your page titles, your header.php file should contain a reference to <code>wp_title(\'\')</code>, and preferably nothing else.').'</p>';
			$content .= '<p>'.__("For some pages, like the homepage, you'll want to set a fixed title in some occasions. For others, you can define a template here.").'</p>';
			$content .= '<h4 class="big">Singular pages</h4>';
			if ( 'posts' == get_option('show_on_front') ) {
				$content .= '<h4>Homepage</h4>';
				$content .= $this->textinput('title-home','Title template');
				$content .= $this->textinput('metadesc-home','Meta description template');
			} else {
				$content .= '<h4>Homepage &amp; Frontpage</h4>';
				$content .= '<p>'.__('You can determine the title and description for the frontpage by').' <a href="'.get_edit_post_link( get_option('page_on_front') ).'">'.__('editing the frontpage itself').' &raquo;</a>.</p>';
				if ( is_numeric( get_option('page_for_posts') ) )
				$content .= '<p>'.__('You can determine the title and description for the blog page by').' <a href="'.get_edit_post_link( get_option('page_for_posts') ).'">'.__('editing the blog page itself').' &raquo;</a>.</p>';
			}
			// $content .= '<pre>'.print_r(get_post_types(),1).'</pre>';
			foreach (get_post_types() as $posttype) {
				if ( in_array($posttype, array('revision','nav_menu_item') ) )
					continue;
				if (isset($options['redirectattachment']) && $options['redirectattachment'] && $posttype == 'attachment')
					continue;
				$content .= '<h4>'.ucfirst($posttype).'</h4>';
				$content .= $this->textinput('title-'.$posttype,'Title template');
				$content .= $this->textinput('metadesc-'.$posttype,'Meta description template');
				$content .= '<br/>';
			}
			$content .= '<br/>';
			$content .= '<h4 class="big">Taxonomies</h4>';
			foreach (get_taxonomies() as $taxonomy) {
				if ( in_array($taxonomy, array('link_category','nav_menu') ) )
					continue;				
				$content .= '<h4>'.ucfirst($taxonomy).'</h4>';
				$content .= $this->textinput('title-'.$taxonomy,'Title template');
				$content .= $this->textinput('metadesc-'.$taxonomy,'Meta description template');
				$content .= '<br/>';				
			}
			$content .= '<br/>';
			$content .= '<h4 class="big">Special pages</h4>';
			$content .= '<h4>Author Archives</h4>';
			$content .= $this->textinput('title-author','Title template');
			$content .= $this->textinput('metadesc-author','Meta description template');
			$content .= '<br/>';
			$content .= '<h4>Date Archives</h4>';
			$content .= $this->textinput('title-archive','Title template');
			$content .= $this->textinput('metadesc-archive','Meta description template');
			$content .= '<br/>';
			$content .= '<h4>Search pages</h4>';
			$content .= $this->textinput('title-search','Title template');
			$content .= '<h4>404 pages</h4>';
			$content .= $this->textinput('title-404','Title template');
			$content .= '<br class="clear"/>';
			
			$this->postbox('titles',__('Title Settings', 'yoast-wpseo'), $content); 
			
			$content = '
				<p>These tags can be included and will be replaced by Yoast WordPress SEO when a page is displayed. For convenience sake, they\'re the same as HeadSpace2 uses.</p>
					<table class="yoast_help">
						<tr>
							<th>%%date%%</th>
							<td>Replaced with the date of the post/page</td>
						</tr>
						<tr class="alt">
							<th>%%title%%</th>
							<td>Replaced with the title of the post/page</td>
						</tr>
						<tr>
							<th>%%sitename%%</th>
							<td>The site\'s name</td>
						</tr>
						<tr class="alt">
							<th>%%sitedesc%%</th>
							<td>The site\'s tagline / description</td>
						</tr>
						<tr>
							<th>%%excerpt%%</th>
							<td>Replaced with the post/page excerpt (or auto-generated if it does not exist)</td>
						</tr>
						<tr class="alt">
							<th>%%excerpt_only%%</th>
							<td>Replaced with the post/page excerpt (without auto-generation)</td>
						</tr>
						<tr>
							<th>%%tag%%</th>
							<td>Replaced with the current tag/tags</td>
						</tr>
						<tr class="alt">
							<th>%%category%%</th>
							<td>Replaced with the post categories (comma separated)</td>
						</tr>
						<tr>
							<th>%%category_description%%</th>
							<td>Replaced with the category description</td>
						</tr>
						<tr class="alt">
							<th>%%tag_description%%</th>
							<td>Replaced with the tag description</td>
						</tr>
						<tr>
							<th>%%term_description%%</th>
							<td>Replaced with the term description</td>
						</tr>
						<tr class="alt">
							<th>%%term_title%%</th>
							<td>Replaced with the term name</td>
						</tr>
						<tr>
							<th>%%modified%%</th>
							<td>Replaced with the post/page modified time</td>
						</tr>
						<tr class="alt">
							<th>%%id%%</th>
							<td>Replaced with the post/page ID</td>
						</tr>
						<tr>
							<th>%%name%%</th>
							<td>Replaced with the post/page author\'s \'nicename\'</td>
						</tr>
						<tr class="alt">
							<th>%%userid%%</th>
							<td>Replaced with the post/page author\'s userid</td>
						</tr>
						<tr>
							<th>%%searchphrase%%</th>
							<td>Replaced with the current search phrase</td>
						</tr>
						<tr class="alt">
							<th>%%currenttime%%</th>
							<td>Replaced with the current time</td>
						</tr>
						<tr>
							<th>%%currentdate%%</th>
							<td>Replaced with the current date</td>
						</tr>
						<tr class="alt">
							<th>%%currentmonth%%</th>
							<td>Replaced with the current month</td>
						</tr>
						<tr>
							<th>%%currentyear%%</th>
							<td>Replaced with the current year</td>
						</tr>
						<tr class="alt">
							<th>%%page%%</th>
							<td>Replaced with the current page number (i.e. page 2 of 4)</td>
						</tr>
						<tr>
							<th>%%pagetotal%%</th>
							<td>Replaced with the current page total</td>
						</tr>
						<tr class="alt">
							<th>%%pagenumber%%</th>
							<td>Replaced with the current page number</td>
						</tr>
						<tr>
							<th>%%caption%%</th>
							<td>Attachment caption</td>
						</tr>
					</table>';
			$this->postbox('titleshelp',__('Help on Title Settings', 'yoast-wpseo'), $content); 
			
			$this->admin_footer('Titles');
		}
				
		function settings_advice_page() {
			$this->admin_header('Settings Advice', false, true, 'yoast_wpseo_advice_options', 'wpseo_advice');
		}
		
		function permalinks_page() {
			$this->admin_header('Permalinks', true, true, 'yoast_wpseo_permalinks_options', 'wpseo_permalinks');
			$content = $this->checkbox('trailingslash','Enforce a trailing slash on all category and tag URL\'s');
			$content .= '<p class="desc">'.__('If you choose a permalink for your posts with <code>.html</code>, or anything else but a / on the end, this will force WordPress to add a trailing slash to non-post pages nonetheless.', 'yoast-wpseo').'</p>';

			$content .= $this->checkbox('redirectattachment','Redirect attachment URL\'s to parent post URL.');
			$content .= '<p class="desc">'.__('Attachments to posts are stored in the database as posts, this means they\'re accessible under their own URL\'s if you do not redirect them, enabling this will redirect them to the post they were attached to.', 'yoast-wpseo').'</p>';

			$content .= $this->checkbox('cleanpermalinks','Redirect ugly URL\'s to clean permalinks.');
			$content .= '<p class="desc">'.__('People make mistakes in their links towards you sometimes, or unwanted parameters are added to the end of your URLs, this allows you to redirect them all away.', 'yoast-wpseo').'</p>';

			$this->postbox('permalinks',__('Permalink Settings', 'yoast-wpseo'),$content); 

			$content = $this->checkbox('cleanpermalink-googlesitesearch','Prevent cleaning out Google Site Search URL\'s.');
			$content .= '<p class="desc">'.__('Google Site Search URL\'s look weird, and ugly, but if you\'re using Google Site Search, you probably do not want them cleaned out.', 'yoast-wpseo').'</p>';

			$content .= $this->checkbox('cleanpermalink-googlecampaign','Prevent cleaning out Google Analytics Campaign Parameters.');
			$content .= '<p class="desc">'.__('If you use Google Analytics campaign parameters starting with <code>?utm_</code>, check this box. You shouldn\'t use these btw, you should instead use the hash tagged version instead.', 'yoast-wpseo').'</p>';
			
			$this->postbox('cleanpermalinksdiv',__('Clean Permalink Settings', 'yoast-wpseo'),$content); 
			
			$this->admin_footer('Permalinks');
		}
		
		function internallinks_page() {
			$this->admin_header(__('Internal Links'), false, true, 'yoast_wpseo_internallinks_options', 'wpseo_internallinks');

			$content = $this->checkbox('breadcrumbs-enable',__('Enable Breadcrumbs'));
			$content .= '<br/>';
			$content .= $this->textinput('breadcrumbs-sep',__('Separator between breadcrumbs'));
			$content .= $this->textinput('breadcrumbs-home',__('Anchor text for the Homepage'));
			$content .= $this->textinput('breadcrumbs-prefix',__('Prefix for the breadcrumb path'));
			$content .= $this->textinput('breadcrumbs-archiveprefix',__('Prefix for Archive breadcrumbs'));
			$content .= $this->textinput('breadcrumbs-searchprefix',__('Prefix for Search Page breadcrumbs'));
			$content .= $this->checkbox('breadcrumbs-blog-remove',__('Remove Blog page from Breadcrumbs'));
			$content .= '<br class="clear"><br/><br/>';
			$content .= '<strong>'.__('Taxonomy to show in breadcrumbs for:').'</strong><br/>';
			foreach (get_post_types() as $pt) {
				if (in_array($pt, array('revision', 'attachment', 'nav_menu_item')))
					continue;

				$taxonomies = get_object_taxonomies($pt);
				if (count($taxonomies) > 0) {
					$values = array(0 => 'None');
					foreach (get_object_taxonomies($pt) as $tax) {
						$taxobj = get_taxonomy($tax);
						$values[$tax] = $taxobj->labels->singular_name;
					}
					$ptobj = get_post_type_object($pt);
					$content .= $this->select('post_types-'.$pt.'-maintax', $ptobj->labels->name, $values);					
				}
			}
			$content .= '<br class="clear"/><br/><br/>';
			$content .= $this->checkbox('breadcrumbs-boldlast',__('Bold the last page in the breadcrumb'));
			$content .= $this->checkbox('breadcrumbs-trytheme',__('Try to add automatically'));
			$content .= '<p class="desc">'.__('If you\'re using Hybrid, Thesis or Thematic, check this box for some lovely simple action').'.</p>';

			$content .= '<br class="clear"/>';
			$content .= '<h4>'.__('How to insert breadcrumbs in your theme').'</h4>';
			$content .= '<p>'.__('Usage of this breadcrumbs feature is explained <a href="http://yoast.com/wordpress/breadcrumbs/">here</a>. For the more code savvy, insert this in your theme:').'</p>';
			$content .= '<pre>&lt;?php if ( function_exists(&#x27;yoast_breadcrumb&#x27;) ) {
	yoast_breadcrumb(&#x27;&lt;p id=&quot;breadcrumbs&quot;&gt;&#x27;,&#x27;&lt;/p&gt;&#x27;);
} ?&gt;</pre>';
			$this->postbox('internallinks',__('Breadcrumbs Settings', 'yoast-wpseo'),$content); 
			
			$this->admin_footer('Internal Links');
		}
				
		function files_page() {
			if ( isset($_POST['submitrobots']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the robots.txt file.', 'yoast-wpseo'));
				
				if (file_exists( get_home_path()."robots.txt") ) {
					$robots_file = get_home_path()."robots.txt";
					$robotsnew = stripslashes($_POST['robotsnew']);
					if (is_writable($robots_file)) {
						$f = fopen($robots_file, 'w+');
						fwrite($f, $robotsnew);
						fclose($f);
						$msg = 'Updated Robots.txt';
					}
				} 
			}
			
			if ( isset($_POST['submithtaccess']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the .htaccess file.', 'yoast-wpseo'));

				if (file_exists( get_home_path().".htaccess" ) ) {
					$htaccess_file = get_home_path().".htaccess";
					$htaccessnew = stripslashes($_POST['htaccessnew']);
					if (is_writeable($htaccess_file)) {
						$f = fopen($htaccess_file, 'w+');
						fwrite($f, $htaccessnew);
						fclose($f);
					}
				} 
			}

			if ( isset($_POST['submitcachehtaccess']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the .htaccess file.', 'yoast-wpseo'));

				if (file_exists(WP_CONTENT_DIR."/cache/.htaccess")) {
					$htaccess_file = WP_CONTENT_DIR."/cache/.htaccess";
					$htaccessnew = stripslashes($_POST['cachehtaccessnew']);
					if (is_writeable($htaccess_file)) {
						$f = fopen($htaccess_file, 'w+');
						fwrite($f, $htaccessnew);
						fclose($f);
					}
				} 
			}
			
			$this->admin_header('Files', false, false);
			if (isset($msg) && !empty($msg)) {
				echo '<div id="message" style="width:94%;" class="updated fade"><p>'.$msg.'</p></div>';
			}

			if (file_exists( get_home_path()."robots.txt")) {
				$robots_file = get_home_path()."robots.txt";
				$f = fopen($robots_file, 'r');
				if (filesize($robots_file) > 0)
					$content = fread($f, filesize($robots_file));
				else
					$content = '';
				$robotstxtcontent = htmlspecialchars($content);

				if (!is_writable($robots_file)) {
					$content = "<p><em>".__("If your robots.txt were writable, you could edit it from here.", 'yoast-wpseo')."</em></p>";
					$content .= '<textarea disabled="disabled" style="width: 90%;" rows="15" name="robotsnew">'.$robotstxtcontent.'</textarea><br/>';
				} else {
					$content = '<form action="" method="post" id="robotstxtform">';
					$content .= "<p>".__("Edit the content of your robots.txt:", 'yoast-wpseo')."</p>";
					$content .= '<textarea style="width: 90%;" rows="15" name="robotsnew">'.$robotstxtcontent.'</textarea><br/>';
					$content .= '<div class="submit"><input class="button" type="submit" name="submitrobots" value="'.__("Save changes to Robots.txt", 'yoast-wpseo').'" /></div>';
					$content .= '</form>';
				}
				$this->postbox('robotstxt',__('Robots.txt', 'yoast-wpseo'),$content);
			}
			
			if (file_exists( get_home_path().".htaccess" )) {
				$htaccess_file = get_home_path()."/.htaccess";
				$f = fopen($htaccess_file, 'r');
				$contentht = fread($f, filesize($htaccess_file));
				$contentht = htmlspecialchars($contentht);

				if (!is_writable($htaccess_file)) {
					$content = "<p><em>".__("If your .htaccess were writable, you could edit it from here.", 'yoast-wpseo')."</em></p>";
					$content .= '<textarea disabled="disabled" style="width: 90%;" rows="15" name="robotsnew">'.$contentht.'</textarea><br/>';
				} else {
					$content = '<form action="" method="post" id="htaccessform">';
					$content .=  "<p>Edit the content of your .htaccess:</p>";
					$content .= '<textarea style="width: 90%;" rows="15" name="htaccessnew">'.$contentht.'</textarea><br/>';
					$content .= '<div class="submit"><input class="button" type="submit" name="submithtaccess" value="'.__('Save changes to .htaccess', 'yoast-wpseo').'" /></div>';
					$content .= '</form>';
				}
				$this->postbox('htaccess',__('.htaccess file', 'yoast-wpseo'),$content);
			}
			
			if (is_plugin_active('wp-super-cache/wp-cache.php')) {
				$cachehtaccess = WP_CONTENT_DIR.'/cache/.htaccess';
				$f = fopen($cachehtaccess, 'r');
				$cacheht = fread($f, filesize($cachehtaccess));
				$cacheht = htmlspecialchars($cacheht);

				if (!is_writable($cachehtaccess)) {
					$content = "<p><em>".__("If your", 'yoast-wpseo')." ".WP_CONTENT_DIR."/cache/.htaccess ".__("were writable, you could edit it from here.", 'yoast-wpseo')."</em></p>";
					$content .= '<textarea disabled="disabled" style="width: 90%;" rows="15" name="robotsnew">'.$cacheht.'</textarea><br/>';
				} else {
					$content = '<form action="" method="post" id="htaccessform">';
					$content .=  "<p>".__("Edit the content of your cache directory's .htaccess:", 'yoast-wpseo')."</p>";
					$content .= '<textarea style="width: 90%;" rows="15" name="cachehtaccessnew">'.$cacheht.'</textarea><br/>';
					$content .= '<div class="submit"><input class="button" type="submit" name="submitcachehtaccess" value="'.__('Save changes to .htaccess', 'yoast-wpseo').'" /></div>';
					$content .= '</form>';
				}
				$this->postbox('cachehtaccess',__('wp-super-cache cache dir .htaccess file', 'yoast-wpseo'),$content);
			}
			
			$this->admin_footer('', false);
		}
		
		function indexation_page() {
			$this->admin_header('Indexation', true, true, 'yoast_wpseo_indexation_options', 'wpseo_indexation');
					
			$content = '<p>'.__("Below you'll find checkboxes for each of the sections of your site that you might want to disallow the search engines from indexing. Be aware that this is a powerful tool, blocking category archives, for instance, really blocks all category archives from showing up in the index.").'</p>';
			$content .= $this->checkbox('search',__('This site\'s search result pages', 'yoast-wpseo'));
			$content .= '<p class="desc">'.__('Prevents the search engines from indexing your search result pages, by a <code>noindex,follow</code> robots tag to them. The <code>follow</code> part means that search engine crawlers <em>will</em> spider the pages listed in the search results.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('logininput',__('The login and register pages', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('(warning: don\'t enable this if you have the <a href="http://wordpress.org/extend/plugins/minimeta-widget/">minimeta widget</a> installed!)', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('admin',__('All admin pages', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('The above two options prevent the search engines from indexing your login, register and admin pages.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('pagedhome',__('Subpages of the homepage', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Prevent the search engines from indexing your subpages, if you want them to only index your category and / or tag archives.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('noindexauthor',__('Author archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('By default, WordPress creates author archives for each user, usually available under <code>/author/username</code>. If you have sufficient other archives, or yours is a one person blog, there\'s no need and you can best disable them or prevent search engines from indexing them.', 'yoast-wpseo').'</p>';
			
			$content .= $this->checkbox('noindexdate',__('Date-based archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('If you want to offer your users the option of crawling your site by date, but have ample other ways for the search engines to find the content on your site, I highly encourage you to prevent your date-based archives from being indexed.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('noindexcat',__('Category archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('If you\'re using tags as your only way of structure on your site, you would probably be better off when you prevent your categories from being indexed.', 'yoast-wpseo').'</p>';

			$content .= $this->checkbox('noindextag',__('Tag archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Read the categories explanation above for categories and switch the words category and tag around ;)', 'yoast-wpseo').'</p>';

			$this->postbox('preventindexing',__('Indexation Rules', 'yoast-wpseo'),$content);

			$content = $this->checkbox('nofollowmeta',__('Nofollow login and registration links', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('This might have happened to you: logging in to your admin panel to notice that it has become PR6... Nofollow those admin and login links, there\'s no use flowing PageRank to those pages!', 'yoast-wpseo').'</p>';			
			$content .= $this->checkbox('nofollowcommentlinks',__('Nofollow comments links', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Simple way to decrease the number of links on your pages: nofollow all the links pointing to comment sections.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('replacemetawidget',__('Replace the Meta Widget with a nofollowed one', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('By default the Meta widget links to your RSS feeds and to WordPress.org with a follow link, this will replace that widget by a custom one in which all these links are nofollowed.', 'yoast-wpseo').'</p>';
			
			$this->postbox('internalnofollow',__('Internal nofollow settings', 'yoast-wpseo'),$content);

			$content = $this->checkbox('disableauthor',__('Disable the author archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('If you\'re running a one author blog, the author archive will always look exactly the same as your homepage. And even though you may not link to it, others might, to do you harm. Disabling them here will make sure any link to those archives will be 301 redirected to the blog homepage.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('disabledate',__('Disable the date-based archives', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('For the date based archives, the same applies: they probably look a lot like your homepage, and could thus be seen as duplicate content.', 'yoast-wpseo').'</p>';
						
			$this->postbox('archivesettings',__('Archive Settings', 'yoast-wpseo'),$content);
					
			$content = '<p>'.__("You can add all these on a per post / page basis from the edit screen, by clicking on advanced. Should you wish to use any of these sitewide, you can do so here. (This is <em>not</em> recommended.)").'</p>';
			$content .= $this->checkbox('noodp',__('Add <code>noodp</code> meta robots tag sitewide', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Prevents search engines from using the DMOZ description for pages from this site in the search results.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('noydir',__('Add <code>noydir</code> meta robots tag sitewide', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Prevents search engines from using the Yahoo! directory description for pages from this site in the search results.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('nosnippet',__('Add <code>nosnippet</code> meta robots tag sitewide', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Prevents search engines from displaying snippets for your pages.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('noarchive',__('Add <code>noarchive</code> meta robots tag sitewide', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('Prevents search engines from caching pages from this site.', 'yoast-wpseo').'</p>';
			
			$this->postbox('directories',__('Robots Meta Settings', 'yoast-wpseo'),$content); 
			
			$content = '<p>'.__('Some of us like to keep our &lt;heads&gt; clean. The settings below allow you to make it happen.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hidersdlink','Hide RSD Links');
			$content .= '<p class="desc">'.__('Might be necessary if you or other people on this site use remote editors.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hidewlwmanifest','Hide WLW Manifest Links');
			$content .= '<p class="desc">'.__('Might be necessary if you or other people on this site use Windows Live Writer.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hidewpgenerator','Hide WordPress Generator');
			$content .= '<p class="desc">'.__('If you want to show off that you\'re on the latest version, don\'t check this box.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hideindexrel','Hide Index Relation Links');
			$content .= '<p class="desc">'.__('Check this box, or please tell the plugin author why you shouldn\'t.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hideprevnextpostlink','Hide Previous &amp; Next Post Links');
			$content .= $this->checkbox('hideshortlink','Hide Shortlink for posts');
			$content .= '<p class="desc">'.__('Hides the shortlink for the current post.', 'yoast-wpseo').'</p>';
			$content .= $this->checkbox('hidefeedlinks','Hide RSS Links');
			$content .= '<p class="desc">'.__('Check this box only if you\'re absolutely positive your site doesn\'t need and use RSS feeds.', 'yoast-wpseo').'</p>';

			$this->postbox('headsection','Clean up &lt;head&gt; section',$content);
			
			$this->admin_footer('Indexation');
		}

		function rss_page() {
			$options = get_wpseo_options();
			$this->admin_header('RSS', true, true, 'yoast_wpseo_rss_options', 'wpseo_rss');
			$content = $this->checkbox('commentfeeds',__('<code>noindex</code> the comment RSS feeds', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('This will prevent the search engines from indexing your comment feeds.', 'yoast-wpseo').'</p>';
		
			$content .= $this->checkbox('allfeeds',__('<code>noindex</code> <strong>all</strong> RSS feeds', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('This will prevent the search engines from indexing <strong>all your</strong> feeds. Highly discouraged.', 'yoast-wpseo').'</p>';

			$content .= $this->checkbox('pingfeed',__('Ping the Search Engines with feed on new post', 'yoast-wpseo') );
			$content .= '<p class="desc">'.__('This will ping search engines that your RSS feed has been updated.', 'yoast-wpseo').'</p>';

			$this->postbox('rssfeeds',__('RSS Feeds', 'yoast-wpseo'),$content); 			
			
			$content = '<p>'."The feature below is used to automatically add content to your RSS, more specifically, it's meant to add links back to your blog and your blog posts, so dumb scrapers will automatically add these links too, helping search engines identify you as the original source of the content.".'</p>';
			$rows = array();
			$rssbefore = '';
			if ( isset($options['rssbefore']) )
				$rssbefore = stripslashes(htmlentities($options['rssbefore']));

			$rssafter = '';
			if ( isset($options['rssafter']) )
				$rssafter = stripslashes(htmlentities($options['rssafter']));
			
			$rows[] = array(
				"id" => "rssbefore",
				"label" => __("Content to put before each post in the feed", 'yoast-wpseo'),
				"desc" => __("(HTML allowed)", 'yoast-wpseo'),
				"content" => '<textarea cols="50" rows="5" id="rssbefore" name="wpseo_rss[rssbefore]">'.$rssbefore.'</textarea>',
			);
			$rows[] = array(
				"id" => "rssafter",
				"label" => __("Content to put after each post", 'yoast-wpseo'),
				"desc" => __("(HTML allowed)", 'yoast-wpseo'),
				"content" => '<textarea cols="50" rows="5" id="rssafter" name="wpseo_rss[rssafter]">'.$rssafter.'</textarea>',
			);
			$rows[] = array(
				"label" => __('Explanation', 'yoast-wpseo'),
				"content" => '<p>'.__('You can use the following variables within the content, they will be replaced by the value on the right.', 'yoast-wpseo').'</p>'.
				'<ul>'.
				'<li><strong>%%POSTLINK%%</strong> : '.__('A link to the post, with the title as anchor text.', 'yoast-wpseo').'</li>'.
				'<li><strong>%%BLOGLINK%%</strong> : '.__("A link to your site, with your site's name as anchor text.", 'yoast-wpseo').'</li>'.
				'<li><strong>%%BLOGDESCLINK%%</strong> : '.__("A link to your site, with your site's name and description as anchor text.", 'yoast-wpseo').'</li>'.
				'</ul>'
			);
			$this->postbox('rssfootercontent',__('Content of your RSS Feed', 'yoast-wpseo'),$content.$this->form_table($rows));
			
			$this->admin_footer('RSS');
		}
		
		function config_page() {			
			$this->admin_header('General', false);

			$content = '';
			
			if ( strpos( get_option('permalink_structure'), '%postname%' ) === false )
				$content .= '<p class="wrong"><a href="'.admin_url('options-permalink.php').'" class="button fixit">'.__('Go fix it.').'</a>'.__('You do not have your postname in the URL of your posts and pages, it is highly recommended that you do. Consider setting your permalink structure to <strong>/%postname%/</strong>.').'</p>';

			if ( get_option('page_comments') )
				$content .= '<p class="wrong"><a href="'.admin_url('options-discussion.php').'" class="button fixit">'.__('Go fix it.').'</a>'.__('Paging comments is enabled, this is not needed in 999 out of 1000 cases, so the suggestion is to disable it, to do that, simply uncheck the box before "Break comments into pages..."').'</p>';

			if ( strpos( get_option('ping_sites'), 'http://blogsearch.google.com/ping/RPC2' ) === false )
				$content .= '<p class="wrong"><a href="'.admin_url('options-writing.php').'" class="button fixit">'.__('Go fix it.').'</a>'.__('You\'re not pinging Google Blogsearch when you publish new blogposts, you should add <strong>http://blogsearch.google.com/ping/RPC2</strong> to the textarea under the "Update Services" header.').'</p>';

			// $content .= '<pre>'.print_r(get_option('ping_sites'),1).'</pre>';
			if ($content != '')
				$this->postbox('advice',__('Settings Advice', 'yoast-wpseo'),$content); 
							
			$content = '<p>'.__('You can use the boxes below to verify with the different Webmaster Tools, if your site is already verified, you can just forget about these. Enter the verify meta values for:').'</p>';
			$content .= $this->textinput('googleverify', '<a target="_blank" href="https://www.google.com/webmasters/tools/dashboard?hl=en&amp;siteUrl='.urlencode(get_bloginfo('url')).'%2F">'.__('Google Webmaster Tools', 'yoast-wpseo').'</a>');
			$content .= $this->textinput('yahooverify','<a target="_blank" href="https://siteexplorer.search.yahoo.com/mysites">'.__('Yahoo! Site Explorer', 'yoast-wpseo').'</a>');
			$content .= $this->textinput('msverify','<a target="_blank" href="http://www.bing.com/webmaster/?rfp=1#/Dashboard/?url='.str_replace('http://','',get_bloginfo('url')).'">'.__('Bing Webmaster Tools', 'yoast-wpseo').'</a>');

			$content .= '<br class="clear"/><br/>';
			
			$this->postbox('webmastertools',__('Webmaster Tools', 'yoast-wpseo'),$content);
			
			$options = get_option($this->currentoption);
			if ( empty($options['sitemappath']) )
				$options['sitemappath'] = get_home_path()."sitemap.xml";
			if ( empty($options['sitemapurl']) )
				$options['sitemapurl'] = get_bloginfo('url')."/sitemap.xml";
			if ( empty($options['newssitemappath']) )
				$options['newssitemappath'] = get_home_path()."news_sitemap.xml";
			if ( empty($options['newssitemapurl']) )
				$options['newssitemapurl'] = get_bloginfo('url')."/news_sitemap.xml";
			update_option($this->currentoption, $options);
			
			$content = $this->checkbox('enablexmlsitemap',__('Check this box to enable XML sitemap functionality.'));
			$content .= '<div id="sitemapinfo">';
			$content .= '<p>'.__('Please check whether the auto-detected path and URL are correct:').'</p>';
			$content .= $this->textinput('sitemappath',__('Path to the XML Sitemap', 'yoast-wpseo'));
			$content .= $this->textinput('sitemapurl',__('URL to the XML Sitemap', 'yoast-wpseo'));
			
			$content .= '<br class="clear"/><br/>';
			$content .= '<strong>'.__('Exclude post types').'</strong><br/>';
			$content .= '<p>'.__('Please check the appropriate box below if there\'s a post type that you do <strong>NOT</strong> want to include in your sitemap:').'</p>';
			foreach (get_post_types() as $post_type) {
				if ( !in_array( $post_type, array('revision','nav_menu_item','attachment') ) ) {
					$pt = get_post_type_object($post_type);
					$content .= $this->checkbox('post_types-'.$post_type.'-not_in_sitemap', $pt->labels->name);
				}
			}

			$content .= '<br class="clear"/><br/>';
			$content .= '<strong>'.__('Exclude taxonomies').'</strong><br/>';
			$content .= '<p>'.__('Please check the appropriate box below if there\'s a taxonomy that you do <strong>NOT</strong> want to include in your sitemap:').'</p>';
			foreach (get_taxonomies() as $taxonomy) {
				if ( !in_array( $taxonomy, array('nav_menu','link_category') ) ) {
					$tax = get_taxonomy($taxonomy);
					$content .= $this->checkbox('taxonomies-'.$taxonomy.'-not_in_sitemap', $tax->labels->name);
				}
			}
			
			$content .= '<br class="clear"/>';
			$content .= '<p>'.__('<strong>Note:</strong> make sure to save the settings if you\'ve changed anything above before regenerating the XML sitemap.').'</p>';
			$content .= '<a class="button" href="javascript:testSitemap(\''.WPSEO_URL.'\',\'\');">Test XML sitemap values</a> ';
			$content .= '<a class="button" href="javascript:rebuildSitemap(\''.WPSEO_URL.'\',\'\');">(Re)build XML sitemap</a><br/><br/>';
			$content .= '<div id="sitemaptestresult">'.wpseo_test_sitemap_callback(true).'</div>';
			$content .= '<br/>';
			$content .= '<div id="sitemapgeneration"></div>';
			$content .= '</div>';
			$this->postbox('xmlsitemaps',__('XML Sitemap', 'yoast-wpseo'),$content);
			
			do_action('wpseo_dashboard', $this);
			
			$this->admin_footer('');
		}
		
		function wpseo_user_profile($user) {
			if (!current_user_can('edit_users'))
				return;
			?>
				<h3 id="wordpress-seo">WordPress SEO settings</h3>
				<table class="form-table">
					<tr>
						<th>Title to use for Author page</th>
						<td><input class="regular-text" type="text" name="wpseo_author_title" value="<?php echo esc_attr(get_the_author_meta('wpseo_title', $user->ID) ); ?>"/></td>
					</tr>
					<tr>
						<th>Meta description to use for Author page</th>
						<td><textarea rows="3" cols="30" name="wpseo_author_metadesc"><?php echo esc_html(get_the_author_meta('wpseo_metadesc', $user->ID) ); ?></textarea></td>
					</tr>
				</table>
				<br/><br/>
			<?php
		}
		
		function wpseo_process_user_option_update($user_id) {
			update_usermeta($user_id, 'wpseo_title', ( isset($_POST['wpseo_author_title']) ? $_POST['wpseo_author_title'] : '' ) );
			update_usermeta($user_id, 'wpseo_metadesc', ( isset($_POST['wpseo_author_metadesc']) ? $_POST['wpseo_author_metadesc'] : '' ) );
		}
		
	} // end class WPSEO_Admin
	$wpseo_admin = new WPSEO_Admin();
}