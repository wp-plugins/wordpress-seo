<?php

class WPSEO_Taxonomy {
	
	function WPSEO_Taxonomy() {
		
		if (is_admin() && isset($_GET['taxonomy']))
			add_action($_GET['taxonomy'] . '_edit_form', array(&$this,'term_additions_form'), 10, 2 );
		
		add_action('edit_term', array(&$this,'update_term'), 10, 3 );
	}
	
	function form_row( $id, $label, $desc, $tax_meta, $type = 'text', $options = '' ) {
		if ( isset($tax_meta[$id]) )
			$val = stripslashes($tax_meta[$id]);
		
		echo '<tr class="form-field">'."\n";
		echo "\t".'<th scope="row" valign="top"><label for="'.$id.'">'.$label.':</label></th>'."\n";
		echo "\t".'<td>'."\n";
		if ($type == 'text') {
?>
			<input name="<?php echo $id; ?>" id="<?php echo $id; ?>" type="text" value="<?php if (isset($val)) echo $val; ?>" size="40"/>
			<p class="description"><?php echo $desc; ?></p>
<?php	
		} else if ($type == 'checkbox') {
?>
			<input name="<?php echo $id; ?>" id="<?php echo $id; ?>" type="checkbox" <?php checked($val); ?>/>
<?php
		} else if ($type == 'select') {
?>
			<select name="<?php echo $id; ?>" id="<?php echo $id; ?>">
				<?php foreach ($options as $option => $label) {
					$sel = '';
					if ($option == $val)
						$sel = " selected='selected'";
					echo "<option".$sel." value='".$option."'>".$label."</option>";
				}?>
			</select>
<?php
		}
		echo "\t".'</td>'."\n";
		echo '</tr>'."\n";
	
	}
	
	function term_additions_form( $term, $taxonomy ) {
		$tax_meta = get_option('wpseo_taxonomy_meta');
		if (isset($tax_meta[$taxonomy][$term->term_id]))
			$tax_meta = $tax_meta[$taxonomy][$term->term_id];

		echo '<h3>Yoast WordPress SEO Settings</h3>';
		echo '<table class="form-table">';

		$this->form_row( 'wpseo_title', 'SEO Title', 'The SEO title is used on the archive page for this term.', $tax_meta );
		$this->form_row( 'wpseo_desc', 'SEO Description', 'The SEO description is used for the meta description on the archive page for this term.', $tax_meta );
		$this->form_row( 'wpseo_canonical', 'Canonical', 'The canonical link is shown on the archive page for this term.', $tax_meta );
		$this->form_row( 'wpseo_bctitle', 'Breadcrumbs Title', 'The Breadcrumbs title is used in the breadcrumbs where this '.$taxonomy.' appears.', $tax_meta );

		$this->form_row( 'wpseo_noindex', 'Noindex this '.$taxonomy, '', $tax_meta, 'checkbox' );
		$this->form_row( 'wpseo_nofollow', 'Nofollow this '.$taxonomy, '', $tax_meta, 'checkbox' );

		$this->form_row( 'wpseo_sitemap_include', 'Include in sitemap?', '', $tax_meta, 'select', array(
			"-" => __("Auto detect"),
			"always" => __("Always include"),
			"never" => __("Never include"),
		) );

		echo '</table>';
	}
	
	function update_term( $term_id, $tt_id, $taxonomy ) {
		$tax_meta = get_option( 'wpseo_taxonomy_meta' );

		foreach (array('title', 'desc', 'bctitle', 'canonical', 'sitemap_include') as $key) {
			$tax_meta[$taxonomy][$term_id]['wpseo_'.$key] 	= $_POST['wpseo_'.$key];
		}

		foreach (array('noindex', 'nofollow') as $key) {
			if ( isset($_POST['wpseo_'.$key]) )
				$tax_meta[$taxonomy][$term_id]['wpseo_'.$key] = true;
			else
				$tax_meta[$taxonomy][$term_id]['wpseo_'.$key] = false;			
		}
		
		update_option( 'wpseo_taxonomy_meta', $tax_meta );

		if ( defined('W3TC_DIR') ) {
			require_once W3TC_DIR . '/lib/W3/ObjectCache.php';
		    $w3_objectcache = & W3_ObjectCache::instance();

		    $w3_objectcache->flush();			
		}
	    
		global $wpseo_generate, $wpseo_echo;
		$wpseo_generate = true;
		$wpseo_echo = false;
		require_once WPSEO_PATH.'/sitemaps/xml-sitemap-class.php';
	}	
}
$wpseo_taxonomy = new WPSEO_Taxonomy();

?>