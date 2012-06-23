<?php

class WPSEO_Pointers {

	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}
	
	function enqueue() {
		$options = get_option('wpseo');
		if ( !isset($options['ignore_tour']) || !$options['ignore_tour'] ) {
			wp_enqueue_style( 'wp-pointer' ); 
			wp_enqueue_script( 'jquery-ui' ); 
			wp_enqueue_script( 'wp-pointer' ); 
			wp_enqueue_script( 'utils' );
			add_action( 'admin_print_footer_scripts', array( $this, 'print_scripts' ), 99 );
			add_action( 'admin_head', array( $this, 'admin_head' ) );
		}
	}

	function print_scripts() {
		global $pagenow, $current_user;
		
		$adminpages = array( 
			'wpseo_dashboard' => array(
					'content'  => '<h3>'.__( 'Dashboard', 'wordpress-seo' ).'</h3><p>'.__( 'This is the WordPress SEO Dashboard, here you can restart this tour or revert the WP SEO settings to default.', 'wordpress-seo' ).'</p>'
					.'<p><strong>'.__( 'More WordPress SEO', 'wordpress-seo' ).'</strong><br/>'.sprintf( __( 'There\'s more to learn about WordPress & SEO than just using this plugin. Read our article %1$sthe definitive guide to WordPress SEO%2$s.', 'wordpress-seo' ), '<a target="_blank" href="http://yoast.com/articles/wordpress-seo/#utm_source=wpadmin&utm_medium=wpseo_tour&utm_term=link&utm_campaign=wpseoplugin">', '</a>').'</p>'
					.'<p><strong>'.__( 'Webmaster Tools', 'wordpress-seo' ).'</strong><br/>'.__( 'Underneath the General Settings, you can add the verification codes for the different Webmaster Tools programs, I highly encourage you to check out both Google and Bing\'s Webmaster Tools.', 'wordpress-seo' ).'</p>'
					.'<p><strong>'.__( 'About This Tour', 'wordpress-seo' ).'</strong><br/>'.__( 'Clicking Next below takes you to the next page of the tour. If you want to stop this tour, click "Close".', 'wordpress-seo' ).'</p>'
					.'<p><strong>'.__( 'Like this plugin?', 'wordpress-seo' ).'</strong><br/>'.sprintf(__( 'If you like this plugin, please %srate it 5 stars on WordPress.org%s and consider making a donation by clicking the button on the right!', 'wordpress-seo' ),'<a target="_blank" href="http://wordpress.org/extend/plugins/wordpress-seo/">','</a>').'</p>'.
					 '<p><strong>'.__('Newsletter','wordpress-seo').'</strong><br/>'.
					 __( 'If you would like to keep up to date regarding the WordPress SEO plugin and other plugins by Yoast, subscribe to the newsletter:', 'wordpress-seo').'</p>'.
					 '<form action="http://yoast.us1.list-manage.com/subscribe/post?u=ffa93edfe21752c921f860358&amp;id=972f1c9122" method="post" id="newsletter-form">'.
					 '<p>'.
					 '<label for="newsletter-name">'.__('Name','wordpress-seo').':</label><input style="color:#666" name="MMERGE9" value="'.$current_user->display_name.'" id="newsletter-name" placeholder="'.__('Name','wordpress-seo').'"/><br/>'.
					 '<label for="newsletter-email">'.__('Email','wordpress-seo').':</label><input style="color:#666" name="EMAIL" value="'.$current_user->user_email.'" id="newsletter-email" placeholder="'.__('Email','wordpress-seo').'"/><br/>'.
					 '<input type="hidden" name="group" value="2"/>'.
					 '<button type="submit" class="button-primary">'.__('Subscribe','wordpress-seo').'</button>'.
					 '</p></form>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_titles').'";'
			),
			'wpseo_titles' => array(
				'content'  => "<h3>".__( "Title &amp; Description settings", 'wordpress-seo' )."</h3>"
							   ."<p>".__( "This is were you set the templates for your titles and descriptions of all the different types of pages on your blog, be it your homepage, posts & pages (under post types), category or tag archives (under taxonomy archives), or even custom post type archives and custom posts: all of that is done from here.", 'wordpress-seo' )."</p>"
							   ."<p><strong>".__( "Templates", 'wordpress-seo' )."</strong><br/>"
							   .__( "The templates are built using variables, the help tab for all the different variables available to you to use in these.", 'wordpress-seo' )."</p>"
							   ."<p><strong>".__( "Sitewide settings", 'wordpress-seo' )."</strong><br/>"
							   .__( "You can also set some sidewide settings here to add specific meta tags or to remove some unneeded cruft.", 'wordpress-seo' )."</p>",
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_social').'";'
			),
			'wpseo_social' => array(
				'content'  => "<h3>".__( "Social settings", 'wordpress-seo' )."</h3>"
								."<p><strong>".__('Facebook OpenGraph','wordpress-seo').'</strong><br/>'
							  	.__( "On this page you can enable the OpenGraph functionality from this plugin, as well as assign a Facebook user or Application to be the admin of your site, so you can view the Facebook insights.", 'wordpress-seo' )."</p>"
							  	.'<p>'.sprintf( __( 'Read more about %1$sFacebook OpenGraph%2$s.', 'wordpress-seo' ), '<a target="_blank" href="http://yoast.com/facebook-open-graph-protocol/#utm_source=wpadmin&utm_medium=wpseo_tour&utm_term=link&utm_campaign=wpseoplugin">', '</a>' )."</p>"
								."<p><strong>".__('Twitter Cards','wordpress-seo').'</strong><br/>'
							  	.sprintf( __( 'This functionality is currently in beta, but it allows for %1$sTwitter Cards%2$s.', 'wordpress-seo' ), '<a target="_blank" href="http://yoast.com/twitter-cards/#utm_source=wpadmin&utm_medium=wpseo_tour&utm_term=link&utm_campaign=wpseoplugin">', '</a>' )."</p>",
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_xml').'";'
			),
			'wpseo_xml' => array(
				'content'  => '<h3>'.__( 'XML Sitemaps', 'wordpress-seo' ).'</h3><p>'.__( 'This plugin adds an XML sitemap to your site. It\'s automatically updated when you publish a new post, page or custom post and Google and Bing will be automatically notified.', 'wordpress-seo' ).'</p><p>'.__( 'Be sure to check whether post types or taxonomies are showing that search engines shouldn\'t be indexing, if so, check the box before them to hide them from the XML sitemaps.', 'wordpress-seo' ).'</p>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_permalinks').'";'
			),
			'wpseo_permalinks' => array(
				'content'  => '<h3>'.__( 'Permalink Settings', 'wordpress-seo' ).'</h3><p>'.__( 'All of the options here are for advanced users only, if you don\'t know whether you should check any, don\'t touch them.', 'wordpress-seo' ).'</p>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_internal-links').'";'
			),
			'wpseo_internal-links' => array(
				'content'  => '<h3>'.__( 'Breadcrumbs Settings', 'wordpress-seo' ).'</h3><p>'.sprintf(__( 'If your theme supports my breadcrumbs, as all Genesis and WooThemes themes as well as a couple of other ones do, you can change the settings for those here. If you want to modify your theme to support them, %sfollow these instructions%s.', 'wordpress-seo' ),'<a target="_blank" href="http://yoast.com/wordpress/breadcrumbs/#utm_source=wpadmin&utm_medium=wpseo_tour&utm_term=link&utm_campaign=wpseoplugin">','</a>').'</p>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_rss').'";'
			),
			'wpseo_rss' => array(
				'content'  => '<h3>'.__( 'RSS Settings', 'wordpress-seo' ).'</h3><p>'.__( 'This incredibly powerful function allows you to add content to the beginning and end of your posts in your RSS feed. This helps you gain links from people who steal your content!', 'wordpress-seo' ).'</p>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_import').'";'
			),
			'wpseo_import' => array(
				'content'  => '<h3>'.__( 'Import &amp; Export', 'wordpress-seo' ).'</h3><p>'.__( 'Just switched over from another SEO plugin? Use the options here to switch your data over. If you were using some of my older plugins like Robots Meta &amp; RSS Footer, you can import the settings here too.', 'wordpress-seo' ).'</p><p>'.__( 'If you have multiple blogs and you\'re happy with how you\'ve configured this blog, you can export the settings and import them on another blog so you don\'t have to go through this process twice!', 'wordpress-seo' ).'</p>',
				'button2'  => __( 'Next', 'wordpress-seo' ),
				'function' => 'window.location="'.admin_url('admin.php?page=wpseo_files').'";'
			),
			'wpseo_files' => array(
				'content'  => '<h3>'.__( 'File Editor', 'wordpress-seo' ).'</h3><p>'.__( 'Here you can edit the .htaccess and robots.txt files, two of the most powerful files in your WordPress install. Only touch these files if you know what you\'re doing!', 'wordpress-seo' ).'</p>'
				 .'<p>'.sprintf( __( 'The tour ends here, thank you for using my plugin and good luck with your SEO!<br/><br/>Best,<br/>Joost de Valk - %1$sYoast.com%2$s', 'wordpress-seo' ), '<a target="_blank" href="http://yoast.com/#utm_source=wpadmin&utm_medium=wpseo_tour&utm_term=link&utm_campaign=wpseoplugin">', '</a>').'</p>',
			),
		);

		if ( ( defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ) || ( defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS ) ) {
			unset( $adminpages['wpseo_files'] );
			$adminpages['wpseo_import']['function'] = '';
			unset($adminpages['wpseo_import']['button2']);
			$adminpages['wpseo_import']['content'] .= '<p>'.sprintf( __( 'The tour ends here,thank you for using my plugin and good luck with your SEO!<br/><br/>Best,<br/>Joost de Valk - %1$sYoast.com%2$s', 'wordpress-seo' ), '<a href="http://yoast.com/">', '</a>').'</p>';
		}
		$page = '';
		if ( isset($_GET['page']) )
			$page = $_GET['page'];

		if ( 'admin.php' != $pagenow || !array_key_exists( $page, $adminpages ) ) {
			$id 			= 'li.toplevel_page_wpseo_dashboard';
			$content 		= '<h3>'.__( 'Congratulations!', 'wordpress-seo' ).'</h3>';
			$content 		.= '<p>'.__( 'You\'ve just installed WordPress SEO by Yoast! Click "Start Tour" to view a quick introduction of this plugins core functionality.', 'wordpress-seo' ).'</p>';
			$opt_arr		= array(
				'content' => $content,
				'position' => array( 'edge' => 'top', 'align' => 'center' )
			);
			$button2 		= __( "Start Tour", 'wordpress-seo' );
			$function 		= 'document.location="'.admin_url('admin.php?page=wpseo_dashboard').'";';
		} else {
			if ( '' != $page && in_array( $page, array_keys( $adminpages ) ) ) {
				$opt_arr = array(
					'content' => $adminpages[$page]['content'],
					'position' => array( 'edge' => 'top', 'align' => 'left' ),
					'pointerWidth' => 400
				);
				$id 			= '#wpseo-title';
				$button2 		= $adminpages[$page]['button2'];
				$function 		= $adminpages[$page]['function'];
			}
		}

		$this->print_buttons( $id, $opt_arr, __( "Close", 'wordpress-seo' ), $position_at, $button2, $function );
	}
	
	function admin_head() {
	?>
		<style type="text/css" media="screen">
			#pointer-primary, #tour-close {
				margin: 0 5px 0 0;
			}
		</style>
	<?php
	}
	
	function print_buttons( $selector, $options, $button1, $position_at, $button2 = false, $button2_function = '' ) {
	?>
	<script type="text/javascript"> 
	//<![CDATA[ 
	(function($){
		var wpseo_pointer_options = <?php echo json_encode( $options ); ?>, setup;
		
		wpseo_pointer_options = $.extend( wpseo_pointer_options, {
			buttons: function( event, t ) {
				button = jQuery('<a id="pointer-close" class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
				button.bind( 'click.pointer', function() {
					t.element.pointer('close');
				});
				return button;
			},
			close: function() { }
		});

		setup = function() {
			$('<?php echo $selector; ?>').pointer( wpseo_pointer_options ).pointer('open');
			<?php if ( $button2 ) { ?> 
			jQuery('#pointer-close').after('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
			jQuery('#pointer-primary').click( function() {
				<?php echo $button2_function; ?>
			});
			jQuery('#pointer-close').click( function() {
				wpseo_setIgnore("tour","wp-pointer-0","<?php echo wp_create_nonce('wpseo-ignore'); ?>");
			});
			<?php } ?>
		};
		
		if ( wpseo_pointer_options.position && wpseo_pointer_options.position.defer_loading )
			$(window).bind( 'load.wp-pointers', setup );
		else
			$(document).ready( setup );
	})( jQuery );
	//]]> 
	</script>
	<?php
	}
}

$wpseo_pointers = new WPSEO_Pointers;
