<?php 

require_once 'xml-sitemap-base-class.php';

class WPSEO_XML_Sitemap extends WPSEO_XML_Sitemap_Base {

	function WPSEO_XML_Sitemap() {
		global $wpseo_generate, $wpseo_echo;
		
		$options = get_option("wpseo");

		if ( ( !isset($options['enablexmlsitemap']) || !$options['enablexmlsitemap'] ) && !$wpseo_generate )
			return;

		if ( ( !isset($options['enablexmlsitemap']) || !$options['enablexmlsitemap'] ) && $wpseo_generate && $wpseo_echo ) {
			$options['enablexmlsitemap'] = 'on';
			update_option('wpseo', $options);
		}
		
		$this->generate_sitemap( 'sitemap.xml', $wpseo_echo );
		$this->ping_search_engines( 'sitemap.xml', $wpseo_echo );
	}
	
	function write_sitemap_loc( $f, $url, $echo = false ) {
		global $count;

		if (!isset($url['mod']))
			$url['mod'] = '';
		$output = "\t<url>\n";
		$output .= "\t\t<loc>".$url['loc']."</loc>\n";
		$output .= "\t\t<lastmod>".$this->w3c_date($url['mod'])."</lastmod>\n";
		$output .= "\t\t<changefreq>".$url['chf']."</changefreq>\n";
		$output .= "\t\t<priority>".$url['pri']."</priority>\n";
		if ( isset($url['images']) && count($url['images']) > 0 ) {
			foreach($url['images'] as $img) {
				$output .= "\t\t<image:image>\n";
				$output .= "\t\t\t<image:loc>".$this->xml_clean($img['src'])."</image:loc>\n";
				if ( isset($img['title']) )
					$output .= "\t\t\t<image:title>".$this->xml_clean($img['title'])."</image:title>\n";
				if ( isset($img['alt']) )
					$output .= "\t\t\t<image:caption>".$this->xml_clean($img['alt'])."</image:caption>\n";
				$output .= "\t\t</image:image>\n";
			}
		}
		$output .= "\t</url>\n"; 
		$count++;
		fwrite($f, $output);
	}
	
	function generate_sitemap( $filename, $echo = false ) {
		global $wpdb, $wp_taxonomies, $count;

		$f = fopen( WPSEO_UPLOAD_DIR.$filename, 'w');

		$options = get_wpseo_options();
		
		$images_in_sitemap = false;
		if ( isset($options['xml_include_images']) && $options['xml_include_images'] )
			$images_in_sitemap = true;
			
		$output = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="'.WPSEO_FRONT_URL.'css/xml-sitemap.xsl"?>'."\n";
		$output .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
		if ( $images_in_sitemap )
			$output .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ';
		$output .= 	'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" '
					.'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"; 

		fwrite($f, $output);
		
		if ( $echo )
			echo date('H:i:s').': Starting to generate output.<br/>';

		// If sitemap is regenerated manually, throw away rewrite rules to make sure /sitemap.xml is rewriting properly.
		if ( $echo )
			delete_option('rewrite_rules');
		
		// The stack of URL's to add to the sitemap
		$stackedurls = array();

		// Add the homepage first
		$url = array();
		$url['loc'] = get_bloginfo('url').'/';
		$url['pri'] = 1;
		$url['chf'] = 'daily';

		$count = 0;
		$this->write_sitemap_loc( $f, $url, $echo );

		foreach ( get_post_types() as $post_type ) {
			if ( isset($options['post_types-'.$post_type.'-not_in_sitemap']) && $options['post_types-'.$post_type.'-not_in_sitemap'] )
				continue;
			if ( in_array( $post_type, array('revision','nav_menu_item','attachment') ) )
				continue;

			$typecount = $wpdb->get_var("SELECT COUNT(ID) 
												FROM $wpdb->posts 
												WHERE post_status = 'publish' 
												AND	post_password = ''
												AND post_type = '$post_type'");
			
			$steps		= 25;
			$offset 	= 0;
			$postscount = 0;

			while( $typecount > $offset ) {
				// Grab posts of $post_type

				// If we're not going to include images, we might as well save ourselves the memory of grabbing post_content.
				$post_content_query = '';
				if ( $images_in_sitemap )
					$post_content_query = "post_content,";
				
				$posts = $wpdb->get_results("SELECT ID, $post_content_query post_parent, post_type, post_modified_gmt, post_date_gmt 
												FROM $wpdb->posts 
												WHERE post_status = 'publish' 
												AND	post_password = ''
												AND post_type = '$post_type'
												ORDER BY post_modified DESC
												LIMIT $steps OFFSET $offset");
												
				$offset = $offset + $steps;

				foreach ( $posts as $p ) {
					if ( $p->ID == get_option('page_on_front') )
						continue;
						
					if ( yoast_get_value('meta-robots-noindex', $p->ID) && yoast_get_value('sitemap-include', $p->ID) != 'always' )
						continue;
					if ( yoast_get_value('sitemap-include', $p->ID) == 'never' )
						continue;
					if ( yoast_get_value('redirect', $p->ID) && strlen( yoast_get_value('redirect', $p->ID) ) > 0 )
						continue;	

					// If a post has just been updated, make sure you scan the *new* content for images, not the old one.
					if ( isset($_POST) && isset($_POST['post_ID']) && $_POST['post_ID'] == $p->ID ) {
						$p->post_modified = current_time( 'mysql' );
						$p->post_content = stripslashes($_POST['post_content']);
					}	

					$url = array();
					
					$url['mod']	= ( isset( $p->post_modified_gmt ) && $p->post_modified_gmt != '0000-00-00 00:00:00' ) ? $p->post_modified_gmt : $p->post_date_gmt ;
					$url['chf'] = 'weekly';

					if ( yoast_get_value('canonical', $p->ID) && yoast_get_value('canonical', $p->ID) != '' && yoast_get_value('canonical', $p->ID) != $link ) {
						$url['loc'] = yoast_get_value('canonical', $p->ID);
					} else { 
						$url['loc'] = get_permalink( $p->ID );

						if ( isset($options['trailingslash']) && $options['trailingslash'] && $p->post_type != 'post' )
							$url['loc'] = trailingslashit( $url['loc'] );
					}

					$pri = yoast_get_value('sitemap-prio', $p->ID);
					if (is_numeric($pri))
						$url['pri'] = $pri;
					elseif ($p->post_parent == 0 && $p->post_type = 'page')
						$url['pri'] = 0.8;
					else 
						$url['pri'] = 0.6;

					if ( $images_in_sitemap ) {
						$url['images'] = array();

						preg_match_all("|(<img [^>]+?>)|", $p->post_content, $matches, PREG_SET_ORDER);

						if ( count($matches) > 0 ) {
							$tmp_img = array();
							foreach ($matches as $imgarr) {
								unset($imgarr[0]);
								foreach($imgarr as $img) {
									if ( isset( $image ) ) {
										unset($image['title']);
										unset($image['alt']);
									}

									// FIXME: get true caption instead of alt / title
									$res = preg_match( '/src=("|\')([^"|\']+)("|\')/', $img, $match );
									if ($res) {
										$image['src'] = $match[2];							
										if ( strpos($image['src'], 'http') !== 0 ) {
											$image['src'] = get_bloginfo('url').$image['src'];
										}
										if ( in_array( $image['src'], $tmp_img ) )
											continue;
										else
											$tmp_img[] = $image['src'];

										$res = preg_match( '/title=("|\')([^"\']+)("|\')/', $img, $match );
										if ($res)
											$image['title'] = str_replace('-',' ',str_replace('_',' ',$match[2]));

										$res = preg_match( '/alt=("|\')([^"\']+)("|\')/', $img, $match );
										if ($res)
											$image['alt'] = str_replace('-',' ',str_replace('_',' ',$match[2]));

										if (empty($image['title']))
											unset($image['title']);
										if (empty($image['alt']))
											unset($image['alt']);
										$url['images'][] = $image;
									} 
								}
							}
						}
					}
					
					if ( !in_array( $url['loc'], $stackedurls ) ) {
						$this->write_sitemap_loc( $f, $url, $echo );
						$stackedurls[] = $url['loc'];
						$postscount++;
					}
				}
				wp_cache_flush();
				// echo '<br/><br/><strong>Cache flushed.</strong><br/><br/>';
			}
			
			if ( $echo )
				echo date('H:i:s').': '.$postscount.' posts of type '.$post_type.' found.<br/>';
		}

		// Grab all taxonomies and add to stack
		foreach( $wp_taxonomies as $taxonomy ) {
			if ( isset($options['taxonomies-'.$taxonomy->name.'-not_in_sitemap']) && $options['taxonomies-'.$taxonomy->name.'-not_in_sitemap'] )
				continue;

			// Skip link, nav and post_format taxonomies
			if ( in_array( $taxonomy->name, array('link_category', 'nav_menu', 'post_format') ) )
				continue;

			$terms = get_terms( $taxonomy->name, array('hide_empty' => true) );
			
			if ( $echo )
				echo date('H:i:s').': '.count($terms).' taxonomy entries of type '.$taxonomy->name.' found.<br/>';

			foreach( $terms as $c ) {
				$url = array();

				if ( wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_noindex' ) 
					&& wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_sitemap_include' ) != 'always' )
					continue;

				if ( wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_sitemap_include' ) == 'never' )
					continue;

				$url['loc'] = wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_canonical' );
				if ( !$url['loc'] ) {
					$url['loc'] = get_term_link( $c, $c->taxonomy );
					if ( isset($options['trailingslash']) && $options['trailingslash'] )
						$url['loc'] = trailingslashit($url['loc']);
				}
				if ($c->count > 10) {
					$url['pri'] = 0.6;
				} else if ($c->count > 3) {
					$url['pri'] = 0.4;
				} else {
					$url['pri'] = 0.2;
				}

				// Grab last modified date
				$sql = "SELECT MAX(p.post_date) AS lastmod
						FROM	$wpdb->posts AS p
						INNER JOIN $wpdb->term_relationships AS term_rel
						ON		term_rel.object_id = p.ID
						INNER JOIN $wpdb->term_taxonomy AS term_tax
						ON		term_tax.term_taxonomy_id = term_rel.term_taxonomy_id
						AND		term_tax.taxonomy = '$c->taxonomy'
						AND		term_tax.term_id = $c->term_id
						WHERE	p.post_status = 'publish'
						AND		p.post_password = ''";						
				$url['mod'] = $wpdb->get_var( $sql ); 
				$url['chf'] = 'weekly';
				$this->write_sitemap_loc( $f, $url, $echo );
			}
			$wpdb->flush();
			wp_cache_flush();
		}

		// If WP E-commerce is running, grab all product categories and all products and add to stack
		if ( defined('WPSC_VERSION') ) {
			// Categories first
			$product_list_table 		= WPSC_TABLE_PRODUCT_LIST;
			$item_category_assoc_table 	= WPSC_TABLE_ITEM_CATEGORY_ASSOC;
			$product_categories_table 	= WPSC_TABLE_PRODUCT_CATEGORIES;

			$sql = "SELECT id FROM $product_categories_table WHERE active = 1";

			$results = $wpdb->get_results($sql);

			if ($echo)
				echo count($results).' WP E-Commerce categories found.<br/>';

			foreach ($results as $cat) {
				$url = array();
				$url['loc'] = html_entity_decode(wpsc_category_url($cat->id));
				$url['pri'] = 0.5;
				$url['chf'] = 'monthly';
				$this->write_sitemap_loc( $f, $url );
			}

			// Then products
			$sql = "SELECT id, date_added
				      FROM $product_list_table
					 WHERE active = 1
			           AND publish = 1";

			$results = $wpdb->get_results($sql);

			if ($echo) {
				echo count($results).' WP E-Commerce products found.<br/>';
			}

			foreach ($results as $prod) {
				$url = array();
				$url['loc'] = html_entity_decode(wpsc_product_url($prod->id));
				$url['mod'] = $prod->date_added;
				$url['chf'] = 'monthly';
				$url['pri'] = 0.5;
				$this->write_sitemap_loc( $f, $url, $echo );
			}
			wp_cache_flush();
		}


		$output = '<!-- XML Sitemap Generated by Yoast WordPress SEO, containing '.$count.' URLs -->'."\n";
		$output .= '</urlset>';

		fwrite($f, $output);

		if ( $echo ) 
			echo date('H:i:s').': <a href="'.get_bloginfo('url').'/'.$filename.'">Sitemap</a> successfully (re-)generated with '.$count.' URLs.<br/>';

		fclose($f);
		$wpdb->flush();
		
		if ( $this->gzip_sitemap( $filename, file_get_contents( WPSEO_UPLOAD_DIR.$filename ) ) & $echo )
			echo date('H:i:s').': <a href="'.get_bloginfo('url').'/'.$filename.'.gz">Sitemap</a> successfully gzipped.<br/>';
			
		if ( file_exists( ABSPATH.'/'.$filename ) )
			unlink( ABSPATH.'/'.$filename );
	}
} 

$wpseo_xml = new WPSEO_XML_Sitemap();
