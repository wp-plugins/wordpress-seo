<?php 

class WPSEO_Breadcrumbs {

	function WPSEO_Breadcrumbs() {
		$options = get_option("wpseo_internallinks");

		if (isset($options['trytheme']) && $options['trytheme']) {
			// Thesis
			add_action('thesis_hook_before_content', array(&$this, 'breadcrumb_output'),10,1);

			// Hybrid
			remove_action( 'hybrid_before_content', 'hybrid_breadcrumb' );
			add_action( 'hybrid_before_content', array(&$this, 'breadcrumb_output'), 10, 1 );

			// Thematic
			add_action('thematic_belowheader', array(&$this, 'breadcrumb_output'),10,1);
						
			add_action('framework_hook_content_open', array(&$this, 'breadcrumb_output'),10,1);			
		}

	}

	function breadcrumb_output() {
		$this->breadcrumb('<div id="wpseobreadcrumb">','</div>');
		return;
	}

	function bold_or_not($input) {
		$opt = get_option("wpseo_internallinks");
		if ( isset($opt['breadcrumbs-boldlast']) && $opt['breadcrumbs-boldlast'] ) {
			return '<strong>'.$input.'</strong>';
		} else {
			return $input;
		}
	}		
	
	function get_bc_title( $id_or_name, $type = 'post_type' ) {
		$bctitle = yoast_get_value( 'bctitle', $id_or_name );
		return ( !empty($bctitle) ) ? $bctitle : strip_tags( get_the_title( $id_or_name ) );
	}
	
	function get_term_parents($term, $taxonomy) {
		$origterm = $term;
		$parents = array();
		while ($term->parent != 0) {
			$term = get_term($term->parent, $taxonomy);
			if ($term != $origterm)
				$parents[] = $term;
		}
		return $parents;
	}
	
	function breadcrumb($prefix = '', $suffix = '', $display = true) {
		global $wp_query, $post, $paged;

		$opt 		= get_option("wpseo_internallinks");
		$on_front 	= get_option('show_on_front');
		$blog_page 	= get_option('page_for_posts');
		$sep		= ( isset($opt['breadcrumbs-sep']) && $opt['breadcrumbs-sep'] != '' ) ? $opt['breadcrumbs-sep'] : '&raquo;';
		$home		= ( isset($opt['breadcrumbs-home']) && $opt['breadcrumbs-home'] != '' ) ? $opt['breadcrumbs-home'] : __('Home');
		
		if ($on_front == "page") {
			$homelink = '<a href="'.get_permalink(get_option('page_on_front')).'">'.$home.'</a>';
			$bloglink = $homelink;
			if ( $blog_page && ( !isset($opt['breadcrumbs-blog-remove']) || !$opt['breadcrumbs-blog-remove'] ) )
				$bloglink = $homelink.' '.$sep.' <a href="'.get_permalink($blog_page).'">'.$this->get_bc_title($blog_page).'</a>';
		} else {
			$homelink = '<a href="'.get_bloginfo('url').'">'.$home.'</a>';
			$bloglink = $homelink;
		}

		if ( ( $on_front == "page" && is_front_page() ) || ( $on_front == "posts" && is_home() ) ) {
			$output = $this->bold_or_not($home);
		} else if ( $on_front == "page" && is_home() ) {
			$output = $homelink.' '.$sep.' '.$this->bold_or_not( $this->get_bc_title($blog_page) );
		} else if ( is_singular() ) {
			$output = $bloglink.' '.$sep.' ';
			if ( 0 == $post->post_parent ) {
				if ( isset( $opt['post_types-'.$post->post_type.'-maintax'] ) && $opt['post_types-'.$post->post_type.'-maintax'] != '0' ) {
					$main_tax = $opt['post_types-'.$post->post_type.'-maintax'];
					$terms = wp_get_object_terms( $post->ID, $main_tax );
					if (is_taxonomy_hierarchical($main_tax) && $terms[0]->parent != 0) {
						$parents = $this->get_term_parents($terms[0], $main_tax);
						foreach($parents as $parent) {
							$bctitle = wpseo_get_term_meta( $parent, $main_tax, 'wpseo_bctitle' );
							if (!$bctitle)
								$bctitle = $parent->name;
							$output .= '<a href="'.get_term_link( $parent, $main_tax ).'">'.$bctitle.'</a> '.$sep.' ';
						}
					}
					if ( count($terms) > 0 ) {
						$bctitle = wpseo_get_term_meta( $terms[0], $main_tax, 'wpseo_bctitle' );
						if (!$bctitle)
							$bctitle = $terms[0]->name;
						$output .= '<a href="'.get_term_link($terms[0], $main_tax).'">'.$bctitle.'</a> '.$sep.' ';
					}
				}
				$output .= $this->bold_or_not( $this->get_bc_title( $post->ID ) );
			} else {
				if ( 0 == $post->post_parent ) {
					$output = $homelink." ".$sep." ".$this->bold_or_not( $this->get_bc_title() );
				} else {
					if (isset($post->ancestors)) {
						if (is_array($post->ancestors))
							$ancestors = array_values($post->ancestors);
						else 
							$ancestors = array($post->ancestors);				
					} else {
						$ancestors = array($post->post_parent);
					}

					// Reverse the order so it's oldest to newest
					$ancestors = array_reverse($ancestors);

					// Add the current Page to the ancestors list (as we need it's title too)
					$ancestors[] = $post->ID;

					$output = $homelink;

					foreach ( $ancestors as $ancestor ) {
						$output .= ' '.$sep.' ';
						if ($ancestor != $post->ID)
							$output .= '<a href="'.get_permalink($ancestor).'">'.$this->get_bc_title( $ancestor ).'</a>';
						else
							$output .= $this->bold_or_not( $this->get_bc_title( $ancestor ) );
					}
				}
			}
		} else {
			if (! is_404() ) {
				$output = $bloglink.' '.$sep.' ';
			} else {
				$output = $homelink.' '.$sep.' ';
			}
			
			if ( is_tax() || is_tag() || is_category() ) {
				$term = $wp_query->get_queried_object();
			
				if ( is_taxonomy_hierarchical($term->taxonomy) && $term->parent != 0 ) {
					$parents = $this->get_term_parents($term, $term->taxonomy);

					foreach($parents as $parent) {
						$bctitle = wpseo_get_term_meta( $parent, $term->taxonomy, 'wpseo_bctitle' );
						if (!$bctitle)
							$bctitle = $parent->name;
						$output .= '<a href="'.get_term_link( $parent, $term->taxonomy ).'">'.$bctitle.'</a> '.$sep.' ';
					}
				}

				$bctitle = wpseo_get_term_meta( $term, $term->taxonomy, 'wpseo_bctitle' );
				if (!$bctitle)
					$bctitle = $term->name;
				
				if ($paged)
					$output .= $this->bold_or_not('<a href="'.get_term_link( $term, $term->taxonomy ).'">'.$bctitle.'</a>');
				else
					$output .= $bctitle;
			} else if ( is_date() ) { 
				if ( isset($opt['breadcrumbs-archiveprefix']) )
					$bc = $opt['breadcrumbs-archiveprefix'];
				else
					$bc = __('Archives for');
				$output .= $this->bold_or_not($bc." ".single_month_title(' ',false));
			} elseif ( is_author() ) {
				if ( isset($opt['breadcrumbs-archiveprefix']) )
					$bc = $opt['breadcrumbs-archiveprefix'];
				else
					$bc = __('Archives for');
				$user = $wp_query->get_queried_object();
				$output .= $this->bold_or_not($bc." ".$user->display_name);
			} elseif ( is_search() ) {
				if ( isset($opt['breadcrumbs-searchprefix']) && $opt['breadcrumbs-searchprefix'] != '' )
					$bc = $opt['breadcrumbs-searchprefix'];
				else
					$bc = __('You searched for');
				$output .= $this->bold_or_not($bc.' "'.stripslashes(strip_tags(get_search_query())).'"');
			} elseif ( is_404() ) {
				if ( isset($opt['breadcrumbs-404crumb']) && $opt['breadcrumbs-404crumb'] != '' )
					$crumb404 = $opt['breadcrumbs-404crumb'];
				else
					$crumb404 = __('Error 404: Page not found');
				$output .= $this->bold_or_not($crumb404);
			}
		}
		
		if ( isset($opt['breadcrumbs-prefix']) && $opt['breadcrumbs-prefix'] != "" ) {
			$output = $opt['breadcrumbs-prefix']." ".$output;
		}
		if ($display) {
			echo $prefix.$output.$suffix;
		} else {
			return $prefix.$output.$suffix;
		}
	}
} 

if (!function_exists('yoast_breadcrumb')) {
	function yoast_breadcrumb($prefix = '', $suffix = '', $display = true) {
		$wpseo_bc = new WPSEO_Breadcrumbs();
		$wpseo_bc->breadcrumb($prefix, $suffix, $display);
	}	
}

?>