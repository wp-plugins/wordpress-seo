<?php
/**
 * @package Frontend
 */

/**
 * This class handles the Breadcrumbs generation and display
 */
class WPSEO_Breadcrumbs {

	/**
	 * Class constructor
	 */
	function __construct() {
		$options = get_option( "wpseo_internallinks" );

		if ( isset( $options['trytheme'] ) && $options['trytheme'] ) {
			// Thesis
			add_action( 'thesis_hook_before_headline', array( $this, 'breadcrumb_output' ), 10, 1 );

			// Hybrid
			remove_action( 'hybrid_before_content', 'hybrid_breadcrumb' );
			add_action( 'hybrid_before_content', array( $this, 'breadcrumb_output' ), 10, 1 );

			// Thematic
			add_action( 'thematic_belowheader', array( $this, 'breadcrumb_output' ), 10, 1 );

			add_action( 'framework_hook_content_open', array( $this, 'breadcrumb_output' ), 10, 1 );
		}

		// If breadcrumbs are active (which they are otherwise this class wouldn't be instantiated), there's no reason
		// to have bbPress breadcrumbs as well.
		add_filter( 'bbp_get_breadcrumb', '__return_false' );
	}

	/**
	 * Wrapper function for the breadcrumb so it can be output for the supported themes.
	 */
	function breadcrumb_output() {
		$this->breadcrumb( '<div  id="wpseobreadcrumb">', '</div>' );
	}

	/**
	 * Bold the last part of the breadcrumb, depending on settings.
	 *
	 * @param string $input String to bold, or not to bold.
	 * @return string
	 */
	function bold_or_not( $input ) {
		$opt = get_option( "wpseo_internallinks" );
		if ( isset( $opt['breadcrumbs-boldlast'] ) && $opt['breadcrumbs-boldlast'] ) {
			return '<strong>' . $input . '</strong>';
		} else {
			return $input;
		}
	}

	/**
	 * Get breadcrumbs title for a post.
	 *
	 * @param int $post_id Post to grab the breadcrumbs title for.
	 * @return string Breadcrumbs title
	 */
	function get_bc_title( $post_id ) {
		$bctitle = wpseo_get_value( 'bctitle', $post_id );
		$bctitle = ( !empty( $bctitle ) ) ? $bctitle : strip_tags( get_the_title( $post_id ) );
		return apply_filters( 'wp_seo_get_bc_title', $bctitle, $post_id );
	}

	/**
	 * Get a term's parents.
	 *
	 * @param object $term Term to get the parents for
	 * @return array
	 */
	function get_term_parents( $term ) {
		$origterm = $term;
		$parents  = array();
		while ( $term->parent != 0 ) {
			$term = get_term( $origterm->parent, $origterm->taxonomy );
			if ( $term != $origterm )
				$parents[] = $term;
		}
		return $parents;
	}

	/**
	 * Display or return the full breadcrumb path.
	 *
	 * @param string $prefix The prefix for the breadcrumb, usually something like "You're here".
	 * @param string $suffix The suffix for the breadcrumb.
	 * @param bool   $display When true, echo the breadcrumb, if not, return it as a string.
	 * @return string
	 */
	function breadcrumb( $prefix = '', $suffix = '', $display = true ) {
		$options = get_wpseo_options();

		global $wp_query, $post, $paged;

		$opt       = get_option( "wpseo_internallinks" );
		$on_front  = get_option( 'show_on_front' );
		$blog_page = get_option( 'page_for_posts' );
		$home      = ( isset( $opt['breadcrumbs-home'] ) && $opt['breadcrumbs-home'] != '' ) ? $opt['breadcrumbs-home'] : __( 'Home', 'wordpress-seo' );

		$links = array();

		$links[] = array(
			'url'  => get_site_url(),
			'text' => $home
		);

		if ( "page" == $on_front && 'post' == get_post_type() ) {
			if ( $blog_page && ( !isset( $opt['breadcrumbs-blog-remove'] ) || !$opt['breadcrumbs-blog-remove'] ) ) {
				$links[] = array( 'id' => $blog_page );
			}
		}

		if ( ( $on_front == "page" && is_front_page() ) || ( $on_front == "posts" && is_home() ) ) {

		} else if ( $on_front == "page" && is_home() ) {
			$links[] = array( 'id' => $blog_page );
		} else if ( is_singular() ) {
			if ( function_exists( 'get_post_type_archive_link' ) && get_post_type_archive_link( $post->post_type ) ) {
				if ( isset( $options['bctitle-ptarchive-' . $post->post_type] ) && '' != $options['bctitle-ptarchive-' . $post->post_type] ) {
					$archive_title = $options['bctitle-ptarchive-' . $post->post_type];
				} else {
					$post_type_obj = get_post_type_object( $post->post_type );
					$archive_title = $post_type_obj->labels->menu_name;
				}
				$links[] = array(
					'url'  => get_post_type_archive_link( $post->post_type ),
					'text' => $archive_title
				);
			}

			if ( 0 == $post->post_parent ) {
				if ( isset( $opt['post_types-' . $post->post_type . '-maintax'] ) && $opt['post_types-' . $post->post_type . '-maintax'] != '0' ) {
					$main_tax = $opt['post_types-' . $post->post_type . '-maintax'];
					$terms    = wp_get_object_terms( $post->ID, $main_tax );
					if ( is_taxonomy_hierarchical( $main_tax ) && $terms[0]->parent != 0 ) {
						$parents = $this->get_term_parents( $terms[0] );
						$parents = array_reverse( $parents );
						foreach ( $parents as $parent ) {
							$bctitle = wpseo_get_term_meta( $parent, $main_tax, 'bctitle' );
							if ( !$bctitle )
								$bctitle = $parent->name;
							$links[] = array(
								'url'  => get_term_link( $parent, $main_tax ),
								'text' => $bctitle
							);
						}
					}

					if ( count( $terms ) > 0 ) {
						$bctitle = wpseo_get_term_meta( $terms[0], $main_tax, 'bctitle' );
						if ( !$bctitle )
							$bctitle = $terms[0]->name;
						$links[] = array(
							'url'  => get_term_link( $terms[0], $main_tax ),
							'text' => $bctitle
						);
					}
				}
			} else {
				if ( isset( $post->ancestors ) ) {
					if ( is_array( $post->ancestors ) )
						$ancestors = array_values( $post->ancestors );
					else
						$ancestors = array( $post->ancestors );
				} else {
					$ancestors = array( $post->post_parent );
				}

				// Reverse the order so it's oldest to newest
				$ancestors = array_reverse( apply_filters( 'wp_seo_get_bc_ancestors', $ancestors ) );

				foreach ( $ancestors as $ancestor ) {
					$links[] = array( 'id' => $ancestor );
				}
			}
			$links[] = array( 'id' => $post->ID );
		} else {
			if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive() ) {
				$post_type = get_post_type();
				if ( isset( $options['bctitle-ptarchive-' . $post_type] ) && '' != $options['bctitle-ptarchive-' . $post_type] ) {
					$archive_title = $options['bctitle-ptarchive-' . $post_type];
				} else {
					$post_type_obj = get_post_type_object( $post_type );
					$archive_title = $post_type_obj->labels->menu_name;
				}
				$links[] = array(
					'text' => $archive_title
				);
			} else if ( is_tax() || is_tag() || is_category() ) {
				$term = $wp_query->get_queried_object();

				if ( isset( $options['taxonomy-' . $term->taxonomy . '-ptparent'] ) && $options['taxonomy-' . $term->taxonomy . '-ptparent'] != '' ) {
					$post_type = $options['taxonomy-' . $term->taxonomy . '-ptparent'];
					if ( 'post' == $post_type && get_option( 'show_on_front' ) == 'page' ) {
						$posts_page = get_option( 'page_for_posts' );
						if ( $posts_page ) {
							$links[] = array( 'id' => $posts_page );
						}
					} else {
						if ( isset( $options['bctitle-ptarchive-' . $post_type] ) && '' != $options['bctitle-ptarchive-' . $post_type] ) {
							$archive_title = $options['bctitle-ptarchive-' . $post_type];
						} else {
							$post_type_obj = get_post_type_object( $post_type );
							$archive_title = $post_type_obj->labels->menu_name;
						}
						$links[] = array(
							'url'  => get_post_type_archive_link( $post_type ),
							'text' => $archive_title
						);
					}
				}

				if ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent != 0 ) {
					$parents = $this->get_term_parents( $term );
					$parents = array_reverse( $parents );

					foreach ( $parents as $parent ) {
						$bctitle = wpseo_get_term_meta( $parent, $term->taxonomy, 'bctitle' );
						if ( !$bctitle )
							$bctitle = $parent->name;
						$links[] = array(
							'url'  => get_term_link( $parent, $term->taxonomy ),
							'text' => $bctitle
						);
					}
				}

				$bctitle = wpseo_get_term_meta( $term, $term->taxonomy, 'bctitle' );
				if ( !$bctitle )
					$bctitle = $term->name;

				if ( $paged ) {
					$links[] = array(
						'url'  => get_term_link( $term, $term->taxonomy ),
						'text' => $bctitle
					);
				} else {
					$links[] = array(
						'text' => $bctitle
					);
				}
			} else if ( is_date() ) {
				if ( isset( $opt['breadcrumbs-archiveprefix'] ) )
					$bc = $opt['breadcrumbs-archiveprefix'];
				else
					$bc = __( 'Archives for', 'wordpress-seo' );
				if ( is_day() ) {
					global $wp_locale;
					$links[] = array(
						'url'  => get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) ),
						'text' => $wp_locale->get_month( get_query_var( 'monthnum' ) ) . ' ' . get_query_var( 'year' )
					);
					$links[] = array(
						'text' => $bc . " " . get_the_date()
					);
				} else if ( is_month() ) {
					$links[] = array(
						'text' => $bc . " " . single_month_title( ' ', false )
					);
				} else if ( is_year() ) {
					$links[] = array(
						'text' => $bc . " " . get_query_var( 'year' )
					);
				}
			} elseif ( is_author() ) {
				if ( isset( $opt['breadcrumbs-archiveprefix'] ) )
					$bc = $opt['breadcrumbs-archiveprefix'];
				else
					$bc = __( 'Archives for', 'wordpress-seo' );
				$user    = $wp_query->get_queried_object();
				$links[] = array(
					'text' => $bc . " " . $user->display_name
				);
			} elseif ( is_search() ) {
				if ( isset( $opt['breadcrumbs-searchprefix'] ) && $opt['breadcrumbs-searchprefix'] != '' )
					$bc = $opt['breadcrumbs-searchprefix'];
				else
					$bc = __( 'You searched for', 'wordpress-seo' );
				$links[] = array(
					'text' => $bc . ' "' . esc_html( get_search_query() ) . '"'
				);
			} elseif ( is_404() ) {
				if ( isset( $opt['breadcrumbs-404crumb'] ) && $opt['breadcrumbs-404crumb'] != '' )
					$crumb404 = $opt['breadcrumbs-404crumb'];
				else
					$crumb404 = __( 'Error 404: Page not found', 'wordpress-seo' );
				$links[] = array(
					'text' => $crumb404
				);
			}
		}

		$links = apply_filters( 'wpseo_breadcrumb_links', $links );

		$output = $this->create_breadcrumbs_thread( $links );

		if ( isset( $opt['breadcrumbs-prefix'] ) && $opt['breadcrumbs-prefix'] != "" )
			$output = $opt['breadcrumbs-prefix'] . " " . $output;

		if ( $display ) {
			echo $prefix . $output . $suffix;
			return true;
		} else {
			return $prefix . $output . $suffix;
		}
	}

	/**
	 * Take the links array and return a full breadcrumb string.
	 *
	 * Each element of the links array can either have a key "id" or the keys "url" and "text". If "id" is set,
	 * the function will retrieve both URL and text by itself.
	 *
	 * @param array $links The links that should be contained in the breadcrumb.
	 * @param string $element
	 * @return mixed|void
	 */
	function create_breadcrumbs_thread( $links, $element = 'span' ) {
		$sep    = ( isset( $opt['breadcrumbs-sep'] ) && $opt['breadcrumbs-sep'] != '' ) ? $opt['breadcrumbs-sep'] : '&raquo;';
		$output = '';
		$i      = 0;
		while ( $i < count( $links ) ) {
			$link = $links[$i];

			if ( !empty( $output ) )
				$output .= " $sep ";

			if ( isset( $link['id'] ) ) {
				$link['url']  = get_permalink( $link['id'] );
				$link['text'] = $this->get_bc_title( $link['id'] );
			}

			$output .= '<span typeof="v:Breadcrumb">';

			if ( isset( $link['url'] ) && $i < ( count( $links ) - 1 ) )
				$output .= '<a href="' . $link['url'] . '" rel="v:url" property="v:title">' . $link['text'] . '</a>';
			else
				$output .= '<span class="breadcrumb_last" property="v:title">' . $this->bold_or_not( $link['text'] ) . '</span>';

			$output .= '</span>';

			$output = apply_filters( 'wpseo_breadcrumb_single_link', $output, $link );

			$i++;
		}

		$element = apply_filters( 'wpseo_breadcrumb_surrounding_element', $element );
		return apply_filters( 'wpseo_breadcrumb_output', '<' . $element . ' xmlns:v="http://rdf.data-vocabulary.org/#">' . $output . '</' . $element . '>' );
	}

}

if ( !function_exists( 'yoast_breadcrumb' ) ) {
	/**
	 * Template tag for breadcrumbs.
	 *
	 * @param string $prefix What to show before the breadcrumb.
	 * @param string $suffix What to show after the breadcrumb.
	 * @param bool   $display Whether to display the breadcrumb (true) or return it (false).
	 * @return string
	 */
	function yoast_breadcrumb( $prefix = '', $suffix = '', $display = true ) {
		$wpseo_bc = new WPSEO_Breadcrumbs();
		return $wpseo_bc->breadcrumb( $prefix, $suffix, $display );
	}
}
