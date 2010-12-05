<?php 

class WPSEO_XML_Sitemap_Base {

	function WPSEO_XML_Sitemap_Base() {
	}

	function generate_sitemap() {
	}
	
	function write_sitemap( $filename, $output ) {
		$f = fopen( WPSEO_UPLOAD_DIR.$filename, 'w+');
		if ( $f ) {
			fwrite($f, $output);
			fclose($f);

			if ( file_exists( ABSPATH.$filename ) ) {
				$return = @unlink( ABSPATH.$filename );
				
				if (!$return) {
					$options = get_option('wpseo');
					$options['blocking_files'][] = ABSPATH.$filename;
					update_option( 'wpseo', $options );
				}
			}
			
			if ( $this->gzip_sitemap( $filename, $output ) )
				return true;
		}
		return false;
	}
	
	function gzip_sitemap( $filename, $output ) {
		$filename = $filename . '.gz';
		$f = fopen( WPSEO_UPLOAD_DIR . $filename, "w" );
		if ( $f ) {
			fwrite( $f, gzencode( $output , 9 ) );
			fclose( $f );
			
			if ( file_exists( ABSPATH.$filename ) ) {
				$return = @unlink( ABSPATH.$filename );
				
				if (!$return) {
					$options = get_option('wpseo');
					$options['blocking_files'][] = ABSPATH.$filename;
					update_option( 'wpseo', $options );
				}
			}
			
			return true;
		} 
		return false;
	}
	
	function ping_search_engines( $filename, $echo = false ) {
		$options= get_wpseo_options();
		
		$sitemapurl = urlencode( get_bloginfo('url') . '/' . $filename . '.gz');
		
		$success = array();
		
		if ( isset($options['xml_ping_google']) && $options['xml_ping_google'] ) {
			$resp = wp_remote_get('http://www.google.com/webmasters/tools/ping?sitemap='.$sitemapurl);
			if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
				$success[] = 'Google';
		}

		if ( isset($options['xml_ping_yahoo']) && $options['xml_ping_yahoo'] ) {
			$resp = wp_remote_get('http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=3usdTDLV34HbjQpIBuzMM1UkECFl5KDN7fogidABihmHBfqaebDuZk1vpLDR64I-&url='.$sitemapurl);
			if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
				$success[] = 'Yahoo!';
		}
		
		if ( isset($options['xml_ping_bing']) && $options['xml_ping_bing'] ) {
			$resp = wp_remote_get('http://www.bing.com/webmaster/ping.aspx?sitemap='.$sitemapurl);
			if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
				$success[] = 'Bing';
		}
		
		if ( isset($options['xml_ping_ask']) && $options['xml_ping_ask'] ) {
			$resp = wp_remote_get('http://submissions.ask.com/ping?sitemap='.$sitemapurl);
			if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
				$success[] = 'Ask.com';
		}
		
		if ( $echo && count($success) > 0 ) {
			echo date('H:i:s').': '.__('Successfully notified of updated sitemap:').' ';
			foreach ($success as $se)
				echo $se.' ';
			echo '<br/><br/>';
		}
	}

	function w3c_date( $time = '' ) { 
	    if ( empty( $time ) ) 
	        $time = time();
		return mysql2date( "Y-m-d\TH:i:s+00:00", $time );
	}

	function xml_clean( $str ) {
		return str_replace ( array ( '&', '"', "'", '<', '>'), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'), $str );
	}
} 

