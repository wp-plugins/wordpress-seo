<?php
/**
 * @package Admin
 */

/**
 * Class that creates the tracking functionality for WP SEO, as the core class might be used in more plugins, it's checked for existence first.
 */
if ( !class_exists( 'Yoast_Tracking' ) ) {
	class Yoast_Tracking {

		function __construct() {
			add_action( 'admin_footer', array( $this, 'tracking' ), 99 );
		}

		function tracking() {
			// Start of Metrics
			global $wpdb;

			$options = get_option( 'wpseo' );

			if ( !isset( $options['hash'] ) || empty( $options['hash'] ) ) {
				$options['hash'] = md5( site_url() );
				update_option( 'wpseo', $options );
			}

			$data = get_transient( 'yoast_tracking_cache' );
			if ( WP_DEBUG || !$data || $data == '' ) {

				$pts = array();
				foreach ( get_post_types( array( 'public' => true ) ) as $pt ) {
					$count    = wp_count_posts( $pt );
					$pts[$pt] = $count->publish;
				}

				$comments_count = wp_count_comments();

				// wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
				if ( function_exists( 'wp_get_theme' ) ) {
					$theme_data = wp_get_theme();
					$theme      = array(
						'name'      => $theme_data->display( 'Name', false, false ),
						'version'   => $theme_data->display( 'Version', false, false ),
						'author'    => $theme_data->display( 'Author', false, false ),
						'author_uri'=> $theme_data->display( 'AuthorURI', false, false ),
					);
					if ( isset( $theme_data->template ) && !empty( $theme_data->template ) && $theme_data->parent() ) {
						$theme['template'] = array(
							'version'   => $theme_data->parent()->display( 'Version', false, false ),
							'name'      => $theme_data->parent()->display( 'Name', false, false ),
							'author'    => $theme_data->parent()->display( 'Author', false, false ),
							'author_uri'=> $theme_data->parent()->display( 'AuthorURI', false, false ),
						);
					} else {
						$theme['template'] = '';
					}
				} else {
					$theme_data = (object) get_theme_data( get_stylesheet_directory() . '/style.css' );
					$theme      = array(
						'version'     => $theme_data->Version,
						'name'        => $theme_data->Name,
						'author'      => $theme_data->Author,
						'template'    => $theme_data->Template,
					);
				}

				$plugins = array();
				foreach ( get_option( 'active_plugins' ) as $plugin_path ) {
					$plugin_info    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path );
					$slug           = str_replace( '/' . basename( $plugin_path ), '', $plugin_path );
					$plugins[$slug] = array(
						'version'    => $plugin_info['Version'],
						'name'       => $plugin_info['Name'],
						'plugin_uri' => $plugin_info['PluginURI'],
						'author'     => $plugin_info['AuthorName'],
						'author_uri' => $plugin_info['AuthorURI'],
					);
				}

				$data = array(
					'site'      => array(
						'hash'        => $options['hash'],
						'url'         => site_url(),
						'name'        => get_bloginfo( 'name' ),
						'version'     => get_bloginfo( 'version' ),
						'multisite'   => is_multisite(),
						'users'       => count( get_users() ),
						'lang'        => get_locale(),
					),
					'pts'       => $pts,
					'comments'  => array(
						'total'    => $comments_count->total_comments,
						'approved' => $comments_count->approved,
						'spam'     => $comments_count->spam,
						'pings'    => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
					),
					'options'   => apply_filters( 'yoast_tracking_filters', array() ),
					'theme'     => $theme,
					'plugins'   => $plugins,
				);


				$url = 'https://tracking.yoast.com/';

				$args = array(
					'body' => $data
				);
				wp_remote_post( $url, $args );

				// Store for a week, then push data again.
				set_transient( 'yoast_tracking_cache', $data, 60 * 60 * 24 );
			}
		}
	}

	$yoast_tracking = new Yoast_Tracking;
}

/**
 * Adds tracking parameters for WP SEO settings. Outside of the main class as the class could also be used in other plugins.
 *
 * @param array $options
 * @return array
 */
function wpseo_tracking_additions( $options ) {
	$opt = get_wpseo_options();

	$options['wpseo'] = array(
		'xml_sitemaps'          => isset( $opt['enablexmlsitemap'] ) ? intval( $opt['enablexmlsitemap'] ) : 0,
		'force_rewrite'         => isset( $opt['forcerewritetitle'] ) ? intval( $opt['forcerewritetitle'] ) : 0,
		'opengraph'             => isset( $opt['opengraph'] ) ? intval( $opt['opengraph'] ) : 0,
		'twitter'               => isset( $opt['twitter'] ) ? intval( $opt['twitter'] ) : 0,
		'strip_category_base'   => isset( $opt['stripcategorybase'] ) ? intval( $opt['stripcategorybase'] ) : 0,
		'on_front'              => get_option( 'show_on_front' ),
	);
	return $options;
}

add_filter( 'yoast_tracking_filters', 'wpseo_tracking_additions' );