<?php 

class WPSEO_XML_Sitemap_Base {

	function WPSEO_XML_Sitemap_Base() {
	}

	function generate_sitemap() {
	}
	
	function write_sitemap( $filename, $output ) {
		$f = fopen( WPSEO_UPLOAD_DIR.$filename, 'w+');
		fwrite($f, $output);
		fclose($f);

		if ( $this->gzip_sitemap( $filename, $output ) )
			return true;
		return false;
	}
	
	function gzip_sitemap( $filename, $output ) {
		$f = fopen( WPSEO_UPLOAD_DIR . $filename . '.gz', "w" );
		if ( $f ) {
			fwrite( $f, gzencode( $output , 9 ) );
			fclose( $f );
			return true;
		} 
		return false;
	}
	
	function ping_search_engines( $filename, $echo = false ) {
		$sitemapurl = urlencode( get_bloginfo('url') . '/' . $filename . '.gz');

		$resp = wp_remote_get('http://www.google.com/webmasters/tools/ping?sitemap='.$sitemapurl);
		if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
			$success[] = 'Google';

		$resp = wp_remote_get('http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=3usdTDLV34HbjQpIBuzMM1UkECFl5KDN7fogidABihmHBfqaebDuZk1vpLDR64I-&url='.$sitemapurl);
		if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
			$success[] = 'Yahoo!';

		$resp = wp_remote_get('http://www.bing.com/webmaster/ping.aspx?sitemap='.$sitemapurl);
		if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
			$success[] = 'Bing';

		$resp = wp_remote_get('http://submissions.ask.com/ping?sitemap='.$sitemapurl);
		if ( !is_wp_error($resp) && $resp['response']['code'] == '200')
			$success[] = 'Ask.com';
		
		if ( $echo ) {
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

