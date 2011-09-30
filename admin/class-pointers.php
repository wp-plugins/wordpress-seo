<?php

class WPSEO_Pointers {

	function __construct() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue' ) );
	}
	
	function enqueue() {
		$options = get_option('wpseo');
		if ( isset( $_GET['wpseo_restart_tour'] ) ) {
			unset( $options['ignore_tour'] );
			update_option( 'wpseo', $options );
		}
		if ( !isset($options['ignore_tour']) || !$options['ignore_tour'] ) {
			wp_enqueue_style( 'wp-pointer' ); 
			wp_enqueue_script( 'jquery-ui' ); 
			wp_enqueue_script( 'wp-pointer' ); 
			wp_enqueue_script( 'utils' );
			add_action( 'admin_print_footer_scripts', array( &$this, 'print_scripts' ), 99 );
			add_action( 'admin_head', array( &$this, 'admin_head' ) );
		}
	}

	function print_scripts() {
		global $pagenow;
		
		$adminpages = array( 
			'wpseo_dashboard' => array(
				'id'	   => 'general-settings',
				'content'  => '<h3>'.__( 'Dashboard', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'This is the WordPress SEO Dashboard, here you can control some of the basic settings such as for which post types and taxonomies to show the WordPress SEO controls.', WPSEO_TEXT_DOMAIN ).'</p><p><strong>'.__( 'Webmaster Tools', WPSEO_TEXT_DOMAIN ).'</strong><br/>'.__( 'Underneath the General Settings, you can add the verification codes for the different Webmaster Tools programs, I highly encourage you to check out both Google and Bing\'s Webmaster Tools.', WPSEO_TEXT_DOMAIN ).'</p><p><strong>'.__( 'About This Tour', WPSEO_TEXT_DOMAIN ).'</strong><br/>'.__( 'Clicking close below closes the tour for a while, reloading the page will restart it. If you don\'t want to see this tour at all, click "Stop Tour".', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_titles').'";'
			),
			'wpseo_titles' => array(
				'id'	   => 'titles',
				'content'  => "<h3>".__( "Title &amp; Description settings", WPSEO_TEXT_DOMAIN )."</h3>"
							   ."<p>".__( "This is were you set the templates for your titles and descriptions of all the different types of pages on your blog, be it your homepage, posts, pages, category or tag archives, or even custom post type archives and custom posts: all of that is done from here.", WPSEO_TEXT_DOMAIN )."</p>"
							   ."<p><strong>".__( "Templates", WPSEO_TEXT_DOMAIN )."</strong><br/>"
							   .__( "The templates are built using variables, see <a href='#titleshelp'>the bottom of this page</a> for all the different variables available to you to use in these.", WPSEO_TEXT_DOMAIN )."</p>"
							   ."<p><strong>".__( "Trouble?", WPSEO_TEXT_DOMAIN )."</strong><br/>".__( "Be sure to check if your titles are displaying correctly once you've set this up. If you're experiencing trouble with how titles display, be sure to check the 'Force rewrite' checkbox on the left and check again, or follow the instructions on this page on how to modify your theme.", WPSEO_TEXT_DOMAIN )."</p>",
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_indexation').'";'
			),
			'wpseo_indexation' => array(
				'id'	   => 'pluginsettings',
				'content'  => '<h3>'.__( 'Indexation settings', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'There are options here to do a whole lot of things, feel free to read through them and set them appropriately, or skip them entirely: WordPress SEO will do the most important things by default.', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_xml').'";'
			),
			'wpseo_xml' => array(
				'id'	   => 'xmlsitemaps',
				'content'  => '<h3>'.__( 'XML Sitemaps', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'I highly encourage you to check the box to enable XML Sitemaps. Once you do, an XML sitemap will be generated when you publish a new post, page or custom post and Google and Bing will be automatically notified.', WPSEO_TEXT_DOMAIN ).'</p><p>'.__( 'Be sure to check whether post types or taxonomies are showing that search engines shouldn\'t be indexing, if so, check the box before them to hide them from the XML sitemaps.', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_permalinks').'";'
			),
			'wpseo_permalinks' => array(
				'id'	   => 'pluginsettings',
				'content'  => '<h3>'.__( 'Permalink Settings', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'All of the options here are for advanced users only, if you don\'t know whether you should check any, don\'t touch them.', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_internal-links').'";'
			),
			'wpseo_internal-links' => array(
				'id'	   => 'internallinks',
				'content'  => '<h3>'.__( 'Breadcrumbs Settings', WPSEO_TEXT_DOMAIN ).'</h3><p>'.sprintf(__( 'If your theme supports my breadcrumbs, as all Genesis and WooThemes themes as well as a couple of other ones do, you can change the settings for those here. If you want to modify your theme to support them, %sfollow these instructions%s.', WPSEO_TEXT_DOMAIN ),'<a href="http://yoast.com/wordpress/breadcrumbs/">','</a>').'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_rss').'";'
			),
			'wpseo_rss' => array(
				'id'	   => 'rssfootercontent',
				'content'  => '<h3>'.__( 'RSS Settings', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'This incredibly powerful function allows you to add content to the beginning and end of your posts in your RSS feed. This helps you gain links from people who steal your content!', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_import').'";'
			),
			'wpseo_import' => array(
				'id'	   => 'import',
				'content'  => '<h3>'.__( 'Import &amp; Export', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'Just switched over from another SEO plugin? Use the options here to switch your data over. If you were using some of my older plugins like Robots Meta &amp; RSS Footer, you can import the settings here too.', WPSEO_TEXT_DOMAIN ).'</p><p>'.__( 'If you have multiple blogs and you\'re happy with how you\'ve configured this blog, you can export the settings and import them on another blog so you don\'t have to go through this process twice!', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Next', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_files').'";'
			),
			'wpseo_files' => array(
				'id'	   => 'robotstxt',
				'content'  => '<h3>'.__( 'File Editor', WPSEO_TEXT_DOMAIN ).'</h3><p>'.__( 'Here you can edit the .htaccess and robots.txt files, two of the most powerful files in your WordPress install. Only touch these files if you know what you\'re doing!', WPSEO_TEXT_DOMAIN ).'</p><p><strong>'.__( 'Like this plugin?', WPSEO_TEXT_DOMAIN ).'</strong><br/>'.sprintf(__( 'If you like this plugin, please %srate it 5 stars on WordPress.org%s and consider making a donation by clicking the button on the right!', WPSEO_TEXT_DOMAIN ),'<a href="http://wordpress.org/extend/plugins/wordpress-seo/">','</a>').'</p><p>'.__( 'The tour ends here, good luck!', WPSEO_TEXT_DOMAIN ).'</p>',
				'button2'  => __( 'Restart', WPSEO_TEXT_DOMAIN ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_dashboard').'";'
			),
		);

		$page = '';
		if ( isset($_GET['page']) )
			$page = $_GET['page'];

		if ( 'admin.php' != $pagenow || !array_key_exists( $page, $adminpages ) ) {
			$id 			= 'toplevel_page_wpseo_dashboard';
			$content 		= '<h3>'.__( 'Congratulations!', WPSEO_TEXT_DOMAIN ).'</h3>';
			$content 		.= '<p>'.__( 'You\'ve just installed WordPress SEO by Yoast! Click "Start Tour" to view a quick introduction of this plugins core functionality.', WPSEO_TEXT_DOMAIN ).'</p>';
			$position_at 	= 'left top';
			$button2 		= __( "Start Tour", WPSEO_TEXT_DOMAIN );
			$function 		= 'document.location="'.admin_url('admin.php?page=wpseo_dashboard').'";';
		} else {
			if ( '' != $page && in_array( $page, array_keys( $adminpages ) ) ) {
				$id 			= $adminpages[$page]['id'];
				$content 		= $adminpages[$page]['content'];
				$position_at 	= 'middle top';
				$button2 		= $adminpages[$page]['button2'];
				$function 		= $adminpages[$page]['function'];
			}
		}

		$this->print_buttons( $id, $content, __( "Close", WPSEO_TEXT_DOMAIN ), $position_at, $button2, $function );
	}
	
	function admin_head() {
	?>
		<style type="text/css" media="screen">
			#pointer-primary, #tour-close {
				margin: 0 5px 0 0;
			}
			.wp-pointer-content {
				width: 300px;
				border-radius: 3px;
				box-shadow: 1px 2px 2px #333;
			}
			.wp-pointer-content h3 {
				color: #21759B;
			}
		</style>
	<?php
	}
	
	function print_buttons( $id, $content, $button1, $position_at, $button2 = false, $button2_function = '' ) {
	?>
	<script type="text/javascript"> 
	//<![CDATA[ 
	jQuery(document).ready( function() { 
		jQuery('#<?php echo $id; ?>').pointer({ 
			content: '<?php echo addslashes( $content ); ?>', 
			buttons: function( event, t ) {
				button = jQuery('<a id="pointer-close" class="button-<?php if ($button2) { echo "secondary"; } else { echo "primary"; } ?>">' + '<?php echo $button1; ?>' + '</a>');
				button.bind( 'click.pointer', function() {
					t.element.pointer('close');
				});
				return button;
			},
			position: {
				my: 'right top', 
				at: '<?php echo $position_at; ?>', 
				offset: '0 -2'
			},
			arrow: {
				edge: 'left',
				align: 'top',
				offset: 10
			},
			close: function() { },
		}).pointer('open'); 
		<?php if ( $button2 ) { ?> 
		jQuery('#pointer-close').after('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
		jQuery('#pointer-primary').click( function() {
			<?php echo $button2_function; ?>
		});
		jQuery('#pointer-close').after('<a id="tour-close" class="button-secondary">' + <?php _e( "'Stop Tour'", WPSEO_TEXT_DOMAIN ); ?> + '</a>');
		jQuery('#tour-close').click( function() {
			wpseo_setIgnore("tour","wp-pointer-0","<?php echo wp_create_nonce('wpseo-ignore'); ?>");
		});
		<?php } ?>
	}); 
	//]]> 
	</script>
	<?php
	}
}

$wpseo_pointers = new WPSEO_Pointers;
