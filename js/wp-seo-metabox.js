// Taken and slightly modified from http://phpjs.org/functions/asort:351
function asort (inputArr, sort_flags) {
    var valArr=[], keyArr=[], k, i, ret, sorter, that = this, strictForIn = false, populateArr = {};

    switch (sort_flags) {
        case 'SORT_NUMERIC': // compare items numerically
            sorter = function (a, b) {
                return (b - a);
            };
            break;
    }

    var bubbleSort = function (keyArr, inputArr) {
        var i, j, tempValue, tempKeyVal;
        for (i = inputArr.length-2; i >= 0; i--) {
            for (j = 0; j <= i; j++) {
                ret = sorter(inputArr[j+1], inputArr[j]);
                if (ret < 0) {
                    tempValue = inputArr[j];
                    inputArr[j] = inputArr[j+1];
                    inputArr[j+1] = tempValue;
                    tempKeyVal = keyArr[j];
                    keyArr[j] = keyArr[j+1];
                    keyArr[j+1] = tempKeyVal;
                }
            }
        }
    };

    // BEGIN REDUNDANT
    this.php_js = this.php_js || {};
    this.php_js.ini = this.php_js.ini || {};
    // END REDUNDANT

    strictForIn = this.php_js.ini['phpjs.strictForIn'] && this.php_js.ini['phpjs.strictForIn'].local_value && 
                    this.php_js.ini['phpjs.strictForIn'].local_value !== 'off';
    populateArr = strictForIn ? inputArr : populateArr;

    // Get key and value arrays
    for (k in inputArr) {
        if (inputArr.hasOwnProperty(k)) {
            valArr.push(inputArr[k]);
            keyArr.push(k);
            if (strictForIn) {
                delete inputArr[k];
            }
        }
    }
    try {
        // Sort our new temporary arrays
        bubbleSort(keyArr, valArr);
    } catch (e) {
        return false;
    }

    // Repopulate the old array
    for (i = 0; i < valArr.length; i++) {
        populateArr[keyArr[i]] = valArr[i];
    }

    return strictForIn || populateArr;
}

function yst_strip_tags( str ) { 
	return str.replace(/<\/?[^>]+>/gi, ''); 
}

function ptest(str, p) {
	var r = str.match(p);
	if (r != null)
		return '<span class="good">Yes ('+r.length+')</span>';
	else
		return '<span class="wrong">No</span>';
}

function testfocuskw() {
	// Retrieve focus keyword and trim
	var focuskw = jQuery.trim( jQuery('#yoast_wpseo_focuskw').val() );

	var p = new RegExp(focuskw,'gim');
	var p2 = new RegExp(focuskw.replace(/\s+/g,"[-_\\/]"),'gim');
	if (focuskw != '') {
		var html = '<p>Your focus keyword was found in:<br/>';
		html += 'Article Heading: ' + ptest( jQuery('#title').val(), p ) + '<br/>';
		html += 'Page title: ' + ptest( jQuery('#snippet .title').text(), p ) + '<br/>';
		html += 'Page URL: ' + ptest( jQuery('#editable-post-name-full').text(), p2 ) + '<br/>';
		html += 'Content: ' + ptest( jQuery('#content').val(), p ) + '<br/>';
		html += 'Meta description: ' + ptest( jQuery('#yoast_wpseo_metadesc').text(), p );
		html += '</p>';
		jQuery('#focuskwresults').html(html);
	}
	updateSnippet( focuskw );
}

function updateTitleLength() {
	var title = jQuery('#yoast_wpseo_title').val();
	if ( !title || title == "" )
		title = jQuery('#snippet .title').text();

	var len = 70 - title.length;
	if (len < 0)
		len = '<span class="wrong">'+len+'</span>';
	else
		len = '<span class="good">'+len+'</span>';
	jQuery('#yoast_wpseo_title-length').html(len);
}

function updateDescLength() {
	var desc = jQuery("#yoast_wpseo_metadesc").val();
	if ( !desc || desc == "" )
		desc = jQuery("#snippet .desc span").text();
		
	var len = wpseo_meta_desc_length - desc.length;
	if (len < 0)
		len = '<span class="wrong">'+len+'</span>';
	else
		len = '<span class="good">'+len+'</span>';
	jQuery('#yoast_wpseo_metadesc-length').html(len);
}

function getAutogenTitle( force ) {
	var template = jQuery('#wpseo_title_template').text();
	var title = template.replace('%%title%%', jQuery('#title').val() );
	
	if ( force ) 
		jQuery('#yoast_wpseo_title').val( title );

	jQuery('#snippet .title').text( title );
	updateTitleLength();
	testfocuskw();
}

function getAutogenDesc() {
	var desc = jQuery("#yoast_wpseo_metadesc").val();
	if ( desc != '' ) {
		jQuery("#snippet .desc span").css('color','#000000');
		jQuery('#snippet .desc span').text( desc );
		updateDescLength();
		testfocuskw();
		return;
	} else {
		jQuery('#snippet .desc span').text( '' );
	}
	
	var template = jQuery('#wpseo_metadesc_template').text();

	if ( template == '' ) {
		updateSnippet();
		return;
	}
		
	var excerpt = jQuery("#excerpt").val();
	
	desc = template.replace('%%excerpt_only%%', excerpt);
	desc = template.replace('%%excerpt%%', excerpt);

	if ( desc != '' ) {
		jQuery('#snippet .desc span').text( desc );
		jQuery("#snippet .desc span").css('color','#000000');
		updateDescLength();
		testfocuskw();
		return;
	}	
	updateSnippet();
	return;
}

function updateSnippet( focuskw ) {
	if ( !focuskw || focuskw == '' ) {
		focuskw = jQuery.trim( jQuery('#yoast_wpseo_focuskw').val() );
	}
	if ( focuskw.search(' ') != -1 ) {
		var keywords 	= focuskw.split(' ');
	} else {
		var keywords	= new Array(focuskw);
	}
	var url	 	= jQuery('#sample-permalink').text().replace('http://','');
	if ( jQuery("#yoast_wpseo_title").val() )
		var title 	= jQuery("#yoast_wpseo_title").val();
	else
		var title 	= jQuery("#snippet .title").text();

	desc = jQuery("#snippet .desc span").text();
	
	if ( !desc || desc.length < 1 ) {
		desc = jQuery("#content").val();
		desc = yst_strip_tags( desc );
		var descsearch = new RegExp( focuskw, 'gim');
		if ( desc.search(descsearch) != -1 ) {
			desc = desc.substr( desc.search(descsearch), wpseo_meta_desc_length );
		} else {
			desc = desc.substr(0, wpseo_meta_desc_length );
		}
		jQuery("#snippet .desc span").css('color','#777777');
	}
	
	for (var i in keywords) {
		var urlfocuskw 	= keywords[i].replace(' ','-').toLowerCase();
		focuskwregex 	= new RegExp( '('+keywords[i]+')', 'gim');
		urlfocuskwregex = new RegExp( '('+urlfocuskw+')', 'gim' );
		desc 			= desc.replace( focuskwregex, '<strong>'+"$1"+'</strong>' );
		title 			= title.replace( focuskwregex, '<strong>'+"$1"+'</strong>' );
		url 			= url.replace( urlfocuskwregex, '<strong>'+"$1"+'</strong>' );
	}
	jQuery("#snippet .title").html( title );
	jQuery('#snippet').css('display','block');
	jQuery("#snippet .url").html( url );
	jQuery("#snippet .desc span").html( desc );		
}

jQuery(document).ready(function(){	
	// Tabs, based on code by Pete Mall - https://github.com/PeteMall/Metabox-Tabs
	jQuery('.metabox-tabs li.tab a').each(function(i) {
		var thisTab = jQuery(this).parent().attr('class').replace(/tab /, '');
		if ( 'active' != jQuery(this).attr('class') )
			jQuery('div.' + thisTab).hide();
 
		jQuery(this).click(function(){
 
			// hide all child content
			jQuery(this).parent().parent().parent().children('div').hide();
 
			// remove all active tabs
			jQuery(this).parent().parent('ul').find('li a.active').removeClass('active');
 
			// show selected content
			jQuery(this).parent().parent().parent().find('div.'+thisTab).show();
			jQuery(this).parent().parent().parent().find('li.'+thisTab+' a').addClass('active');
		});

		jQuery('.heading').hide();
		jQuery('.metabox-tabs').show();
	});
	
	wpseo_doing_title = false;
	wpseo_doing_desc = false;
	
	jQuery('#related_keywords_heading').hide();
	
	jQuery('#yoast_wpseo_title').keyup(function() {
		var title = jQuery.trim( jQuery('#yoast_wpseo_title').val() );
		if ( title == '' || title == null ) {
			getAutogenTitle();
		}
		updateTitleLength();
		testfocuskw();
	});
	
	jQuery('#yoast_wpseo_metadesc').keyup(function() {
		getAutogenDesc();
	});
	
	jQuery('#yoast_wpseo_focuskw').change(function() {
		jQuery.getJSON('http://www.google.com/complete/search?hl='+wpseo_lang+'&qu='+jQuery(this).val()+'&callback=?', function (data) {
			var suggested = new Array();
			for (x in data[1]) {
				suggested[x] = data[1][x][0];
			}
			jQuery("#yoast_wpseo_focuskw").autocomplete(suggested, { selectFirst: false } );
		});
		testfocuskw();
		jQuery('#wpseo_relatedkeywords').show();
		jQuery('#related_keywords_heading').hide();
		jQuery('#wpseo_tag_suggestions').html('');
	});
	jQuery('#excerpt').keyup(function() {
		getAutogenDesc();
	});
	jQuery('#content').keyup(function() {
		getAutogenDesc();
	});
	jQuery('#tinymce').keyup(function() {
		getAutogenDesc();
	});
	jQuery('#titlewrap #title').keyup(function() {
		getAutogenTitle();
	});
	jQuery('#sample-permalink').change( function() {
		testfocuskw();
	});
	// updateSnippet();
	
	jQuery('#advancedseo').hide();
	jQuery('.divtoggle').show();
	jQuery('#advancedseo_open').click(function(){
		jQuery('#advancedseo').toggle();
		var html = jQuery(this).html();
		if (html.search(/↓/) != -1)
			jQuery(this).html( html.replace('↓','↑') );
		else
			jQuery(this).html( html.replace('↑','↓') );
		return false;
	});
	
	jQuery('#wpseo_regen_title').click(function() {
		getAutogenTitle(1);
		return false;
	});

	jQuery('#wpseo_relatedkeywords').click(function() {
		if (jQuery('#yoast_wpseo_focuskw').val() == '')
			return false;
		jQuery.getJSON("http://boss.yahooapis.com/ysearch/web/v1/"+jQuery('#yoast_wpseo_focuskw').val()+"?"
			+"appid=NTPCcr7V34Gspq8myEAxcQZs2w.WLOE2a2z.p.1WjSc_u5XQn9xnf8n_N9oOCOs-"
			+"&lang="+wpseo_lang
			+"&format=json"
			+"&count=50"
			+"&view=keyterms"
			+"&callback=?",
			function (data) {
				var keywords = new Array();
//				console.log('Related Keyword Data: ', data);
				jQuery.each(data['ysearchresponse']['resultset_web'], function(i,item) {
					jQuery.each(item['keyterms']['terms'], function(i,kw) {
						key = kw.toLowerCase();

						if (keywords[key] == undefined)
							keywords[key] = 1;
						else
							keywords[key] = (keywords[key] + 1);										
					});
				});

				keywords = asort(keywords, 'SORT_NUMERIC');

				var result = '<p class="clear">';
				for (key in keywords) {
					if (keywords[key] > 5)
						result += '<span class="wpseo_yahoo_kw">' + key + '</span>';
				}
				result += '</p>';
				jQuery('#wpseo_tag_suggestions').html( result );
				jQuery('#related_keywords_heading').show();
			});	
		jQuery(this).hide();
		return false;
	});
	
	getAutogenDesc();
	getAutogenTitle();
});