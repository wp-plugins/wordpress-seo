function wpseo_setIgnore( option, hide ) {
	jQuery.post(ajaxurl, { 
			action: 'wpseo_set_ignore', 
			option: option,
		}, function(data) { 
			if (data) {
				jQuery('#'+hide).hide();
				jQuery('#hidden_ignore_'+option).val('ignore');
			}
		}
	);
}