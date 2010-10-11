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
		html += 'Page title: ' + ptest( jQuery('#title').val(), p ) + '<br/>';
		if (jQuery('#yoast_wpseo_title').val().length > 0) {
			updateAutogenTitle();
		}
		html += 'SEO title: ' + ptest( jQuery('#yoast_wpseo_title').val(), p ) + '<br/>';
		html += 'Page URL: ' + ptest( jQuery('#sample-permalink').text(), p2 ) + '<br/>';
		html += 'Content: ' + ptest( jQuery('#content').val(), p ) + '<br/>';
		html += 'Meta description: ' + ptest( jQuery('#yoast_wpseo_metadesc').val(), p );
		html += '</p>';
		jQuery('#focuskwresults').html(html);
	}
	
	return focuskw;
}

function getAutogenTitle() {
	var data = {
		action: 'wpseo_autogen_title',
		curtitle: jQuery('#title').val(),
		postid: jQuery('#post_ID').val(),
	}
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#yoast_wpseo_title').attr('placeholder',response);
		return response;
	});	
	return false;
}

function updateAutogenTitle() {
	if ( !(jQuery('#yoast_wpseo_title').val().length > 0) && jQuery('#title').val().length > 0) {
		title = getAutogenTitle();
		if ( title )
			updateSnippetTitle( title );
	}
}

function updateSnippetTitle( title ) {
	jQuery("#snippet .title").html( title );	
}

function updateSnippet() {
	focuskw = testfocuskw();

	if ( !focuskw.length > 0 )
		focuskw = 'yoast';
	if ( focuskw.search(' ') != '-1' ) {
		var keywords 	= focuskw.split(' ');
	} else {
		var keywords	= new Array(focuskw);
	}
	var url 	= jQuery('#sample-permalink').text().replace('http://','');
	var title 	= jQuery("#yoast_wpseo_title").val();
	if ( !title || !title.length > 0 ) {
		if (!jQuery("#yoast_wpseo_title").attr('placeholder')) {
			getAutogenTitle();
		}
		title = jQuery("#yoast_wpseo_title").attr('placeholder');
		
	}
	if ( !title )
		return;
	var desc	= jQuery("#yoast_wpseo_metadesc").val();
	if ( !desc.length > 0 ) {
		desc = jQuery("#content").text();
		if (jQuery("#snippet").hasClass('video'))
			desc = yst_strip_tags(desc).substr(0, 130);
		else
			desc = yst_strip_tags(desc).substr(0, 145);
		desc += ' ...';
	}
	for (var i in keywords) {
		var urlfocuskw 	= keywords[i].replace(' ','-').toLowerCase();
		focuskwregex 	= new RegExp( '('+keywords[i]+')', 'gim');
		urlfocuskwregex = new RegExp( '('+urlfocuskw+')', 'gim' );
		desc 			= desc.replace( focuskwregex, '<strong>'+"$1"+'</strong>' );
		title 			= title.replace( focuskwregex, '<strong>'+"$1"+'</strong>' );
		url 			= url.replace( urlfocuskwregex, '<strong>'+"$1"+'</strong>' );
	}
	jQuery('#snippet').css('display','block');
	updateSnippetTitle( title );
	jQuery("#snippet .url").html( url );
	jQuery("#snippet .desc span").html( desc );		
}

jQuery(document).ready(function(){	
	jQuery('#related_keywords_heading').hide();
	
	jQuery('#yoast_wpseo_title[placeholder]').placeholder({color: '#aaa'});
	
	jQuery('#yoast_wpseo_title').keyup(function() {
		var len = 70 - jQuery('#yoast_wpseo_title').val().length;
		if (len < 0)
			len = '<span class="wrong">'+len+'</span>';
		else
			len = '<span class="good">'+len+'</span>';
		jQuery('#yoast_wpseo_title-length').html(len);
		updateSnippet();
	}).keyup();
	
	jQuery('#yoast_wpseo_metadesc').keyup(function() {
		var len = 160 - jQuery('#yoast_wpseo_metadesc').val().length;
		if (len < 0)
			out = '<span class="wrong">'+len+'</span>';
		else
			out = '<span class="good">'+len+'</span>';
		jQuery('#yoast_wpseo_description-length').html(out);
		if (len > 80 && len < 160)
			jQuery('#yoast_wpseo_metadesc_notice').html(' <p class="warn">Notice: You might want to make your description a bit longer, this\'ll only stretch one line.</p>');
		else
			jQuery('#yoast_wpseo_metadesc_notice').html('');
		updateSnippet();
	}).keyup();
	
	jQuery('#yoast_wpseo_focuskw').change(function() {
		jQuery.getJSON('http://www.google.com/complete/search?hl='+lang+'&qu='+jQuery(this).val()+'&callback=?', function (data) {
			var suggested = new Array();
			for (x in data[1]) {
				suggested[x] = data[1][x][0];
			}
			jQuery("#yoast_wpseo_focuskw").autocomplete(suggested, { selectFirst: false } );
		});
		testfocuskw();
	}).change();
	jQuery('#content').change(function() {
		updateSnippet();
	}).change();
	jQuery('#tinymce').change(function() {
		updateSnippet();
	}).change();
	jQuery('#titlewrap #title').keyup(function() {
		title = updateAutogenTitle();
		updateSnippetTitle( title );
	}).change();
	jQuery('#sample-permalink').keyup( function() {
		updateSnippet();
	});
	jQuery('#yoast_wpseo_focuskw').change( function() {
		updateSnippet();
		jQuery('#wpseo_relatedkeywords').show();
		jQuery('#related_keywords_heading').hide();
		jQuery('#wpseo_tag_suggestions').html('');
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
			
	jQuery('#wpseo_relatedkeywords').click(function() {
		if (jQuery('#yoast_wpseo_focuskw').val() == '')
			return false;
		jQuery.getJSON("http://boss.yahooapis.com/ysearch/web/v1/"+jQuery('#yoast_wpseo_focuskw').val()+"?"
			+"appid=NTPCcr7V34Gspq8myEAxcQZs2w.WLOE2a2z.p.1WjSc_u5XQn9xnf8n_N9oOCOs-"
			+"&lang="+lang
			+"&format=json"
			+"&count=50"
			+"&view=keyterms"
			+"&callback=?",
			function (data) {
				var keywords = new Array();
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
});