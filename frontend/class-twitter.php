<?php

class WPSEO_Twitter extends WPSEO_Frontend {

	var $options;
	
	public function __construct() {
		$this->options = get_option('wpseo_social');
		
		add_action( 'wpseo_head', array( &$this, 'twitter' ) );
	}

	public function twitter() {
		if ( !is_singular() )
			return;
		
		wp_reset_query();
		
		$this->type();
		$this->twitter_title();
		$this->twitter_description();
		$this->twitter_url();
		$this->site_name();
		$this->author_twitter();
		$this->image();

		do_action('wpseo_twitter');
	}

	public function type() {
		echo '<meta name="twitter:card" value="' . apply_filters('wpseo_twitter_card_type','summary') . '">'."\n";
	}
	
	public function twitter_title() {
		echo '<meta name="twitter:title" value="' . $this->title('') . '">'."\n";
	}

	public function twitter_description() {
		$metadesc = trim( $this->metadesc( false ) );
		if ( !$metadesc || empty( $metadesc ) )
			$metadesc = strip_tags( get_the_excerpt() );
			
		echo '<meta name="twitter:description" value="' . esc_attr( $metadesc ) . '">'."\n";
	}
	
	public function twitter_url() {
		echo '<meta name="twitter:url" value="' . $this->canonical( false ) . '">'."\n";
	}
	
	public function site_name() {
		if ( isset( $this->options['twitter_site'] ) )
			echo '<meta name="twitter:site" value="@' . $this->options['twitter_site'] . '">'."\n";
	}

	public function author_twitter() {
		$twitter = get_the_author_meta( 'twitter' );
		
		if ( $twitter )
			echo '<meta name="twitter:creator" value="@' . $twitter . '">'."\n";
	}

	public function image() {
		global $post;
		
		if ( function_exists('has_post_thumbnail') && has_post_thumbnail( $post->ID ) ) {
			$featured_img = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), apply_filters( 'wpseo_opengraph_image_size', 'medium' ) );
			
			if ( $featured_img ) {
				$img = apply_filters( 'wpseo_opengraph_image', $featured_img[0] );
				echo "<meta name='twitter:image' content='".esc_attr( $img )."'>\n";
				$shown_images[] = $img;
			}
		} 
		
	}
}

$wpseo_twitter = new WPSEO_Twitter();