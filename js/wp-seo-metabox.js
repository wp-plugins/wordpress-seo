/** Google Suggest for jQuery plugin (licensed under GPLv3) by Haochi Chen ( http://ihaochi.com ) - http://code.google.com/p/googlesuggest-jquery/ */
jQuery.fn.googleSuggest = function(opts){
  var services = {youtube: 'yt', books: 'bo', products: 'pr', news: 'n', images: 'i'};
  
  opts = jQuery.extend({service: '', secure: false}, opts);
  opts.source = function(request, response){
    jQuery.ajax({
      url: 'http'+(opts.secure?'s':'')+'://clients1.google.com/complete/search',
      dataType: 'jsonp',
      data: {
        q: request.term,
        ds: opts.service in services ? services[opts.service] : '',
        nolabels: 't'
      },
      success: function( data ) {
        response(jQuery.map(data[1], function(item){
          return { value: item[0] }
        }));
      }
    });  
  };
  
  return this.each(function(){
    jQuery(this).autocomplete(opts);
  });
}
// End Google Suggest library

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
	if ( str == '' )
		return '';
	
	str = str.replace(/<\/?[^>]+>/gi, ''); 
	str = str.replace(/\[(.+?)\](.+?\[\/\\1\])?/, '');
	return str;
}

function ptest(str, p) {
	str = yst_strip_tags( str );
	var r = str.match(p);
	if (r != null)
		return '<span class="good">Yes ('+r.length+')</span>';
	else
		return '<span class="wrong">No</span>';
}

function testFocusKw() {
	// Retrieve focus keyword and trim
	var focuskw = jQuery.trim( jQuery('#yoast_wpseo_focuskw').val() );

	var postname = jQuery('#editable-post-name-full').text();
	var url	= wpseo_permalink_template.replace('%postname%', postname).replace('http://','');

	var p = new RegExp(focuskw,'gim');
	var p2 = new RegExp(focuskw.replace(/\s+/g,"[-_\\\//]"),'gim');
	if (focuskw != '') {
		var html = '<p>Your focus keyword was found in:<br/>';
		html += 'Article Heading: ' + ptest( jQuery('#title').val(), p ) + '<br/>';
		html += 'Page title: ' + ptest( jQuery('#snippet .title').text(), p ) + '<br/>';
		html += 'Page URL: ' + ptest( url, p2 ) + '<br/>';
		html += 'Content: ' + ptest( jQuery('#content').val(), p ) + '<br/>';
		html += 'Meta description: ' + ptest( jQuery('#yoast_wpseo_metadesc').val(), p );
		html += '</p>';
		jQuery('#focuskwresults').html(html);
	}
}

function updateTitle( force ) {
	if ( jQuery("#yoast_wpseo_title").val() ) {
		var title = jQuery("#yoast_wpseo_title").val();
	} else {
		var title = wpseo_title_template.replace('%%title%%', jQuery('#title').val() );
	}
	if ( title == '' )
		return;
		
	if ( force ) 
		jQuery('#yoast_wpseo_title').val( title );

	title = yst_strip_tags( title );
	title = jQuery.trim( title );

	if ( title.length > 70 ) {
		var space = title.lastIndexOf( " ", 67 );
		title = title.substring( 0, space ).concat( ' <strong>...</strong>' );
	}
	var len = 70 - title.length;
	if (len < 0)
		len = '<span class="wrong">'+len+'</span>';
	else
		len = '<span class="good">'+len+'</span>';

	title = boldKeywords( title, false );

	jQuery('#snippet .title').html( title );
	jQuery('#yoast_wpseo_title-length').html( len );
}

function updateDesc( desc ) {
	var autogen 	= false;
	var desc 		= jQuery.trim( yst_strip_tags( jQuery("#yoast_wpseo_metadesc").val() ) );
	var color 		= '#000';

	if ( desc == '' ) {
		if ( wpseo_metadesc_template != '' ) {
			var excerpt = yst_strip_tags( jQuery("#excerpt").val() );
			desc = wpseo_metadesc_template.replace('%%excerpt_only%%', excerpt);
			desc = desc.replace('%%excerpt%%', excerpt);
		}

		desc = jQuery.trim ( desc );

		if ( desc == '' ) {
			desc = jQuery("#content").val();
			desc = yst_strip_tags( desc );
			var focuskw = jQuery.trim( jQuery('#yoast_wpseo_focuskw').val() );
			if ( focuskw != '' ) {
				var descsearch = new RegExp( focuskw, 'gim');
				if ( desc.search(descsearch) != -1 ) {
					desc = desc.substr( desc.search(descsearch), wpseo_meta_desc_length );
				} else {
					desc = desc.substr( 0, wpseo_meta_desc_length );
				}
			} else {
				desc = desc.substr( 0, wpseo_meta_desc_length );
			}
			var color = "#888";
			autogen = true;			
		}
	}

	if ( !autogen )
		var len = wpseo_meta_desc_length - desc.length;
	else
		var len = wpseo_meta_desc_length;
		
	if (len < 0)
		len = '<span class="wrong">'+len+'</span>';
	else
		len = '<span class="good">'+len+'</span>';

	if ( autogen || desc.length > wpseo_meta_desc_length ) {
		var space = desc.lastIndexOf( " ", ( wpseo_meta_desc_length - 3 ) );
		desc = desc.substring( 0, space ).concat( ' <strong>...</strong>' );
	}

	desc = boldKeywords( desc, false );

	jQuery('#yoast_wpseo_metadesc-length').html(len);
	jQuery("#snippet .desc span").css( 'color', color );
	jQuery("#snippet .desc span").html( desc );
}

function updateURL() {
	var name = jQuery('#editable-post-name-full').text();
	var url	= wpseo_permalink_template.replace('%postname%', name).replace('http://','');
	url = boldKeywords( url, true );
	jQuery("#snippet .url").html( url );
}

function boldKeywords( str, url ) {
	focuskw = jQuery.trim( jQuery('#yoast_wpseo_focuskw').val() );

	if ( focuskw == '' ) 
		return str;
		
	if ( focuskw.search(' ') != -1 ) {
		var keywords 	= focuskw.split(' ');
	} else {
		var keywords	= new Array(focuskw);
	}
	for (var i in keywords) {
		var kw		= yst_strip_tags( keywords[i] );
		if ( url )
			var kw 		= kw.replace(' ','-').toLowerCase();

		kwregex = new RegExp( '('+kw+')', 'gim' );
		str 	= str.replace( kwregex, '<strong>'+"$1"+'</strong>' );
	}
	return str;
}

function updateSnippet() {
	updateURL();
	updateTitle();
	updateDesc();
	testFocusKw();
}

jQuery(document).ready(function(){	
	// Tabs, based on code by Pete Mall - https://github.com/PeteMall/Metabox-Tabs
	jQuery('.metabox-tabs li a').each(function(i) {
		var thisTab = jQuery(this).parent().attr('class').replace(/active /, '');

		if ( 'active' != jQuery(this).attr('class') )
			jQuery('div.' + thisTab).hide();

		jQuery('div.' + thisTab).addClass('tab-content');

		jQuery(this).click(function(){
			// hide all child content
			jQuery(this).parent().parent().parent().children('div').hide();

			// remove all active tabs
			jQuery(this).parent().parent('ul').find('li.active').removeClass('active');

			// show selected content
			jQuery(this).parent().parent().parent().find('div.'+thisTab).show();
			jQuery(this).parent().parent().parent().find('li.'+thisTab).addClass('active');
		});
	});

	jQuery('.heading').hide();
	jQuery('.metabox-tabs').show();
	// End Tabs code
	
	jQuery('#related_keywords_heading').hide();
	
	jQuery('#yoast_wpseo_focuskw').googleSuggest();
	
	jQuery('#yoast_wpseo_title').keyup( function() {
		updateTitle();		
	});
	jQuery('#yoast_wpseo_metadesc').keyup( function() {
		updateDesc();
	});
	
	jQuery('#yoast_wpseo_title').live('change', function() {
		updateTitle();
		testFocusKw();
	});
	jQuery('#yoast_wpseo_metadesc').live('change', function() {
		updateDesc();
		testFocusKw();
	});
	jQuery('#yoast_wpseo_focuskw').live('change', function() {
		jQuery('#wpseo_relatedkeywords').show();
		jQuery('#wpseo_tag_suggestions').hide();
		jQuery('#related_keywords_heading').hide();
		testFocusKw();
	});
	jQuery('#excerpt').live('change', function() {
		updateDesc();
		testFocusKw();
	});
	jQuery('#content').live('change', function() {
		updateDesc();
		testFocusKw();
	});
	jQuery('#tinymce').live('change', function() {
		updateDesc();
		testFocusKw();
	});
	jQuery('#titlewrap #title').live('change', function() {
		updateTitle();
		testFocusKw();
	});
	jQuery('#wpseo_regen_title').click(function() {
		updateTitle(1);
		testFocusKw();
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
	
	updateSnippet();
});