<?php

if ( ! class_exists( 'WPSEO_Admin' ) ) {
	
	class WPSEO_Admin {
		
		function __construct() {
			$this->multisite_defaults();
	
			$options = get_wpseo_options();
		
			if ( isset( $options['stripcategorybase']) && $options['stripcategorybase'] ) {
				add_action( 'created_category', 'flush_rewrite_rules' );
				add_action( 'edited_category', 'flush_rewrite_rules' );
				add_action( 'delete_category', 'flush_rewrite_rules' );
			}
		
			if ( $this->grant_access() ) {
				global $pagenow;
				if ( $pagenow != 'admin.php' )
					add_action( 'admin_menu', array(&$this, 'register_settings_page') );
				add_action( 'network_admin_menu', array(&$this, 'register_network_settings_page') );
			
				add_filter( 'plugin_action_links', array(&$this, 'add_action_link'), 10, 2 );
				add_action( 'admin_print_scripts', array(&$this,'config_page_scripts'));
		
				if ( '0' == get_option('blog_public') )
					add_action('admin_footer', array(&$this,'blog_public_warning'));				
			}
		
			add_action( 'show_user_profile', array(&$this,'wpseo_user_profile'));
			add_action( 'edit_user_profile', array(&$this,'wpseo_user_profile'));
			add_action( 'personal_options_update', array(&$this,'wpseo_process_user_option_update'));
			add_action( 'edit_user_profile_update', array(&$this,'wpseo_process_user_option_update'));
			add_filter( 'user_contactmethods', array(&$this,'add_google_plus_contactmethod'), 10, 1 );	
		}
		
		function multisite_defaults() {
			$option = get_option('wpseo');
			if ( function_exists('is_multisite') && is_multisite() && !is_array($option) ) {
				$options = get_site_option('wpseo_ms');
				if ( is_array($options) && isset($options['defaultblog']) && !empty($options['defaultblog']) && $options['defaultblog'] != 0 ) {
					foreach ( get_wpseo_options_arr() as $option ) {
						update_option( $option, get_blog_option( $options['defaultblog'], $option) );
					}
				}
				$option['ms_defaults_set'] = true;
				update_option( 'wpseo', $option );
			}
		}
		
		function grant_access() {
			if ( !function_exists('is_multisite') || !is_multisite() )
				return true;
			
			$options = get_site_option('wpseo_ms');
			if ( !is_array( $options ) || !isset( $options['access'] ) )
				return true;
			
			if ( $options['access'] == 'superadmin' && !is_super_admin() )
				return false;
			
			return true;
		}
		
		function register_network_settings_page() {
			add_menu_page( __( 'WordPress SEO Configuration', 'wordpress-seo' ), __( 'SEO', 'wordpress-seo' ), 'delete_users', 'wpseo_dashboard', array(&$this,'network_config_page'), WPSEO_URL.'images/yoast-icon.png');
		}
		
		function register_settings_page() {
			add_menu_page( __( 'WordPress SEO Configuration', 'wordpress-seo' ), __( 'SEO', 'wordpress-seo' ), 'manage_options', 'admin.php?page=wpseo_dashboard', '', WPSEO_URL.'images/yoast-icon.png');
			add_submenu_page('wpseo_dashboard',__( 'Titles &amp; Metas', 'wordpress-seo' ),__( 'Titles &amp; Metas', 'wordpress-seo' ), 'manage_options', 'admin.php?page=wpseo_titles');
			add_submenu_page('wpseo_dashboard',__( 'Social', 'wordpress-seo' ),__( 'Social', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_social');
			add_submenu_page('wpseo_dashboard',__( 'XML Sitemaps', 'wordpress-seo' ),__( 'XML Sitemaps', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_xml');
			add_submenu_page('wpseo_dashboard',__( 'Permalinks', 'wordpress-seo' ),__( 'Permalinks', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_permalinks');
			add_submenu_page('wpseo_dashboard',__( 'Internal Links', 'wordpress-seo' ),__( 'Internal Links', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_internal-links');
			add_submenu_page('wpseo_dashboard',__( 'RSS', 'wordpress-seo' ),__( 'RSS', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_rss');
			add_submenu_page('wpseo_dashboard',__( 'Import & Export', 'wordpress-seo' ),__( 'Import & Export', 'wordpress-seo' ),'manage_options', 'admin.php?page=wpseo_import');
			
			if ( !( defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ) && ! ( defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS ) ) {
				// Make sure on a multi site install only super admins can edit .htaccess and robots.txt
				if ( !function_exists('is_multisite') || !is_multisite() )
					add_submenu_page('wpseo_dashboard',__( 'Edit files', 'wordpress-seo' ),__( 'Edit files', 'wordpress-seo' ),'manage_options', 'wpseo_files', array(&$this,'files_page'));
				else
					add_submenu_page('wpseo_dashboard',__( 'Edit files', 'wordpress-seo' ),__( 'Edit files', 'wordpress-seo' ),'delete_users', 'wpseo_files', array(&$this,'files_page'));
			}
			
			global $submenu;
			if ( isset($submenu['wpseo_dashboard']) )
				$submenu['wpseo_dashboard'][0][0] = __( 'Dashboard', 'wordpress-seo' );
		}
		
		function blog_public_warning() {
			if ( function_exists('is_network_admin') && is_network_admin() )
				return;
				
			$options = get_option('wpseo');
			if ( isset($options['ignore_blog_public_warning']) && $options['ignore_blog_public_warning'] == 'ignore' )
				return;
			echo "<div id='message' class='error'>";
			echo "<p><strong>".__( "Huge SEO Issue: You're blocking access to robots.", 'wordpress-seo' )."</strong> ".sprintf( __( "You must %sgo to your Privacy settings%s and set your blog visible to everyone.", 'wordpress-seo' ), "<a href='options-privacy.php'>", "</a>" )." <a href='javascript:wpseo_setIgnore(\"blog_public_warning\",\"message\",\"".wp_create_nonce('wpseo-ignore')."\");' class='button'>".__( "I know, don't bug me.", 'wordpress-seo' )."</a></p></div>";
		}
		
		/**
		 * Add a link to the settings page to the plugins list
		 */
		function add_action_link( $links, $file ) {
			static $this_plugin;
			if( empty($this_plugin) ) $this_plugin = 'wordpress-seo/wp-seo.php';
			if ( $file == $this_plugin ) {
				$settings_link = '<a href="' . admin_url( 'admin.php?page=wpseo_dashboard' ) . '">' . __('Settings', 'wordpress-seo' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;
		}
		
		function config_page_scripts() {
			wp_enqueue_script( 'wpseo-admin-global-script', WPSEO_URL.'js/wp-seo-admin-global.js', array('jquery'), WPSEO_VERSION, true );
		}
		
		function wpseo_process_user_option_update($user_id) {
			update_user_meta($user_id, 'wpseo_title', ( isset($_POST['wpseo_author_title']) ? $_POST['wpseo_author_title'] : '' ) );
			update_user_meta($user_id, 'wpseo_metadesc', ( isset($_POST['wpseo_author_metadesc']) ? $_POST['wpseo_author_metadesc'] : '' ) );
			update_user_meta($user_id, 'wpseo_metakey', ( isset($_POST['wpseo_author_metakey']) ? $_POST['wpseo_author_metakey'] : '' ) );
		}
		
		function add_google_plus_contactmethod( $contactmethods ) {
		  // Add Twitter
		  $contactmethods['googleplus'] = 'Google+';
		  $contactmethods['twitter'] = 'Twitter username';

		  return $contactmethods;
		}		
		
		function wpseo_user_profile($user) {
			if (!current_user_can('edit_users'))
				return;
				
			$options = get_wpseo_options();
			?>
				<h3 id="wordpress-seo"><?php _e( "WordPress SEO settings", 'wordpress-seo' ); ?></h3>
				<table class="form-table">
					<tr>
						<th><?php _e( "Title to use for Author page", 'wordpress-seo' ); ?></th>
						<td><input class="regular-text" type="text" name="wpseo_author_title" value="<?php echo esc_attr(get_the_author_meta('wpseo_title', $user->ID) ); ?>"/></td>
					</tr>
					<tr>
						<th><?php _e( "Meta description to use for Author page", 'wordpress-seo' ); ?></th>
						<td><textarea rows="3" cols="30" name="wpseo_author_metadesc"><?php echo esc_html(get_the_author_meta('wpseo_metadesc', $user->ID) ); ?></textarea></td>
					</tr>
			<?php 	if ( isset($options['usemetakeywords']) && $options['usemetakeywords'] ) {  ?>
					<tr>
						<th><?php _e( "Meta keywords to use for Author page", 'wordpress-seo' ); ?></th>
						<td><input class="regular-text" type="text" name="wpseo_author_metakey" value="<?php echo esc_attr(get_the_author_meta('wpseo_metakey', $user->ID) ); ?>"/></td>
					</tr>
			<?php } ?>
				</table>
				<br/><br/>
			<?php
		}
	}
	$wpseo_admin = new WPSEO_Admin();	
}