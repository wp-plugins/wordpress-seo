<?php 

require_once 'xml-sitemap-base-class.php';

class WPSEO_XML_Sitemap extends WPSEO_XML_Sitemap_Base {

	function WPSEO_XML_Sitemap() {
		global $wpseo_echo;
		
		$options = get_option("wpseo");

		if ( !$options['enablexmlsitemap'])
			return;
	}
	
	function generate_sitemap( $filename, $echo = false ) {
		global $wpdb, $wp_taxonomies, $wp_rewrite;

		$options = get_option("wpseo");
		
		// $wp_rewrite->flush_rules();

		// The stack of URL's to add to the sitemap
		$stack = array();
		$stackedurls = array();

		// Add the homepage first
		$url = array();
		$url['loc'] = get_bloginfo('url').'/';
		$url['pri'] = 1;
		$url['chf'] = 'daily';

		$stackedurls[] = $url['loc'];
		$stack[] = $url;

		$post_types = array();
		foreach (get_post_types() as $post_type) {
			if ( isset($options['post_types-'.$post_type.'-not_in_sitemap']) && $options['post_types-'.$post_type.'-not_in_sitemap'] )
				continue;
			if ( in_array( $post_type, array('revision','nav_menu_item','attachment') ) )
				continue;
			$post_types[] = $post_type;
		}

		$pt_query = 'AND post_type IN (';
		foreach ($post_types as $pt) {
			$pt_query .= '"'.$pt.'",';
		}
		$pt_query = rtrim($pt_query,',').')';

		// Grab posts and pages and add to stack
		$posts = $wpdb->get_results("SELECT ID, post_content, post_parent, post_type, post_modified 
										FROM $wpdb->posts 
										WHERE post_status = 'publish' 
										AND	post_password = ''
										$pt_query
										ORDER BY post_parent ASC, post_modified DESC");
		if ($echo) {
			echo count($posts).' posts and pages found.<br/>';
		}

		foreach ($posts as $p) {
			$link 		= get_permalink($p->ID);
			
			if (isset($options['trailingslash']) && $options['trailingslash'] && $p->post_type != 'single')
				$link = trailingslashit($link);
			
			$canonical 	= yoast_get_value('canonical', $p->ID);
			if ( !empty($canonical) && $canonical != $link )
				$link = $canonical;
			if ( yoast_get_value('meta-robots-noindex', $p->ID) )
				continue;
			if ( strlen( yoast_get_value('redirect', $p->ID) ) > 0 )
				continue;	
			if ($p->ID == get_option('page_on_front'))
				continue;

			$url = array();
			$pri = yoast_get_value('sitemap-prio', $p->ID);
			if (is_numeric($pri))
				$url['pri'] = $pri;
			elseif ($p->post_parent == 0 && $p->post_type = 'page')
				$url['pri'] = 0.8;
			else
				$url['pri'] = 0.6;

			$url['images'] = array();

			preg_match_all("|(<img [^>]+?>)|", $p->post_content, $matches, PREG_SET_ORDER);

			if (count($matches) > 0) {
				$tmp_img = array();
				foreach ($matches as $imgarr) {
					unset($imgarr[0]);
					foreach($imgarr as $img) {
						unset($image['title']);
						unset($image['alt']);
						
						// FIXME: get true caption instead of alt / title
						$res = preg_match( '/src=("|\')([^"\']+)("|\')/', $img, $match );
						if ($res) {
							$image['src'] = $match[2];							
							if ( strpos($image['src'], 'http') !== 0 ) {
								$image['src'] = get_bloginfo('url').$image['src'];
							}
						}
						if ( in_array($image['src'], $tmp_img) )
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
			
			// echo '<pre>'.print_r($url,1).'</pre>';
			
			$url['mod']	= $p->post_modified;
			$url['loc'] = $link;
			$url['chf'] = 'weekly';
			if (!in_array($url['loc'], array_values($stackedurls))) {
				$stack[] = $url;
				$stackedurls[] = $url['loc'];
			} 
		}
		unset($posts);

		// Grab all taxonomies and add to stack
		$sitemap_taxonomies = array();
		foreach($wp_taxonomies as $taxonomy) {
			if ( isset($options['taxonomies-'.$taxonomy->name.'-not_in_sitemap']) && $options['taxonomies-'.$taxonomy->name.'-not_in_sitemap'] )
				continue;

			// Skip link and nav categories
			if ($taxonomy->name == 'link_category' || $taxonomy->name == 'nav_menu')
				continue;

			$sitemap_taxonomies[] = $taxonomy->name;
		}
		$terms = get_terms( $sitemap_taxonomies, array('hide_empty' => true) );
		if ($echo) {
			echo count($terms).' taxonomy entries found.<br/>';
		}
		foreach( $terms as $c ) {
			$url = array();

			if ( wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_noindex' ) )
				continue;
				
			$url['loc'] = wpseo_get_term_meta( $c, $c->taxonomy, 'wpseo_canonical' );
			if ( !$url['loc'] )
				$url['loc'] = get_term_link( $c, $c->taxonomy );
			
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
			// echo '<pre>'.print_r($url,1).'</pre>';
			$stack[] = $url;
		}
		unset($terms);

		// If WP E-commerce is running, grab all product categories and all products and add to stack
		if ( defined('WPSC_VERSION') ) {
			// Categories first
			$product_list_table 		= WPSC_TABLE_PRODUCT_LIST;
			$item_category_assoc_table 	= WPSC_TABLE_ITEM_CATEGORY_ASSOC;
			$product_categories_table 	= WPSC_TABLE_PRODUCT_CATEGORIES;

			$sql = "SELECT id FROM $product_categories_table WHERE active = 1";

			$results = $wpdb->get_results($sql);

			if ($echo) {
				echo count($results).' WP E-Commerce categories found.<br/>';
			}

			foreach ($results as $cat) {
				$url = array();
				$url['loc'] = html_entity_decode(wpsc_category_url($cat->id));
				$url['pri'] = 0.5;
				$url['chf'] = 'monthly';
				$stack[] = $url;
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
				$stack[] = $url;
			}
		} 

		$output = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="'.WPSEO_URL.'css/xml-sitemap.xsl"?>'."\n";
		$output .= '<!-- XML Sitemap Generated by Yoast WordPress SEO, containing '.count($stack).' URLs -->'."\n";
		$output .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"; 
		if ($echo)
			echo 'Starting to generate output.<br/><br/>';
		foreach ($stack as $url) {
			if (!isset($url['mod']))
				$url['mod'] = '';
			$output .= "\t<url>\n";
			$output .= "\t\t<loc>".$url['loc']."</loc>\n";
			$output .= "\t\t<lastmod>".$this->w3c_date($url['mod'])."</lastmod>\n";
			$output .= "\t\t<changefreq>".$url['chf']."</changefreq>\n";
			$output .= "\t\t<priority>".number_format($url['pri'],1)."</priority>\n";
			if (isset($url['images']) && count($url['images']) > 0) {
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
		}
		$output .= '</urlset>';

		if ($this->write_sitemap( $filename, $output ) && $echo)
			echo date('H:i').': <a href="'.get_bloginfo('url').'/'.$filename.'">Sitemap</a> successfully (re-)generated.<br/><br/>';
		else if ($echo)
			echo date('H:i').': <a href="'.get_bloginfo('url').'/'.$filename.'">Something went wrong...</a>.<br/><br/>';
	}
} 

$wpseo_xml = new WPSEO_XML_Sitemap();

?>