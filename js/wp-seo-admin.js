jQuery(document).ready(function () {
	jQuery("#disableexplanation").change(function() {
		if (jQuery("#disableexplanation").is(':checked')) {
			jQuery("p.desc").css("display","none");
		} else {
			jQuery("p.desc").css("display","block");
		}
	}).change();
	jQuery("#enablexmlsitemap").change(function() {
		if (jQuery("#enablexmlsitemap").is(':checked')) {
			jQuery("#sitemapinfo").css("display","block");
		} else {
			jQuery("#sitemapinfo").css("display","none");
		}
	}).change();
	jQuery("#enablexmlnewssitemap").change(function() {
		if (jQuery("#enablexmlnewssitemap").is(':checked')) {
			jQuery("#newssitemapinfo").css("display","block");
		} else {
			jQuery("#newssitemapinfo").css("display","none");
		}
	}).change();
	jQuery("#enablexmlvideositemap").change(function() {
		if (jQuery("#enablexmlvideositemap").is(':checked')) {
			jQuery("#videositemapinfo").css("display","block");
		} else {
			jQuery("#videositemapinfo").css("display","none");
		}
	}).change();
	jQuery("#cleanpermalinks").change(function() {
		if (jQuery("#cleanpermalinks").is(':checked')) {
			jQuery("#cleanpermalinksdiv").css("display","block");
		} else {
			jQuery("#cleanpermalinksdiv").css("display","none");
		}
	}).change();		
});

function wpseo_exportSettings() {
	jQuery.post(ajaxurl, { 
			action: 'wpseo_export_settings', 
		}, function(data) { 
			if (data) {
				jQuery('#exportbutton').attr('href', data);
				jQuery('#exportbutton').text('Download export file');
			}
		}
	);
}

function setWPOption( option, newval, hide, nonce ) {
	jQuery.post(ajaxurl, { 
			action: 'wpseo_set_option', 
			option: option,
			newval: newval,
			_wpnonce: nonce 
		}, function(data) {
			if (data)
				jQuery('#'+hide).hide();
		}
	);
}

function rebuildSitemap( baseurl, type ) {
	jQuery('#'+type+'sitemapgeneration').html('<img src="'+baseurl+'/images/waiting.gif" alt="Waiting" />');
	jQuery.post(ajaxurl, { 
			action: 'wpseo_generate_sitemap', 
			type: type, 
		}, function(data) { 
			if (data)
				jQuery('#'+type+'sitemapgeneration').html(data); 
		}
	);
}

function rebuildKml( baseurl ) {
	jQuery('#kmlgeneration').html('<img src="'+baseurl+'/images/waiting.gif" alt="Waiting" />');
	jQuery.post(ajaxurl, { 
			action: 'wpseo_generate_sitemap', 
			type: 'kml', 
		}, function(data) { 
			if (data) {
				jQuery('#kmlgeneration').html(data); 
				rebuildSitemap(baseurl, 'geo');
			}
		}
	);
}
