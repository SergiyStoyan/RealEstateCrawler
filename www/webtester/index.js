(function($) {
var tmplRecords;
var tmplRecordsSale;
var tmplRecordsAuction;
var noFrame=false;
var showTags=false;

var updateShowTags=function() {
	if (showTags) {
		$('#search_wrap').addClass('show_tags');
		$('#search_wrap').removeClass('strip_tags');
	} else {
		$('#search_wrap').addClass('strip_tags');
		$('#search_wrap').removeClass('show_tags');
	}
	$.jStorage.set('showTags', showTags );
};
var showRecords=function(d) {
	var $results=$('#search_results');
	if (d.crawler_type=='sale')
		tmplRecords=tmplRecordsSale;
	if (d.crawler_type=='auction')
		tmplRecords=tmplRecordsAuction;
	if (d.status == 'OK')
		$results.html($.mustache(tmplRecords,{ records: d.results}));
	else	$results.empty();
	$('.frame_target',$results).click(function() {
		$('#search_results tr').removeClass('selected');
		$(this).closest('tr').addClass('selected');
		updateFrameSrc();
		return false;
	});
	$('#search_resultinfo').show();
	if (d.status == 'OK') {
		$('#search_form_by_id').hide();
		$('#search_form_by_crawler').hide();
		$('#search_menu p.new_search').show();
	}
	$('#search_paginate').empty();
	$('#search_response').html(d.response);

	$('#search_results img').each(function() {
		var $img=$(this);
		$img.imagesLoaded(function() {
			$img.parent().imagefit();
			$img.after('<div class="orig_size">'+$img.attr('startwidth')+'x'+$img.attr('startheight')+'</div>');
		});
	});

	$('span.has_highlight').click(function() {
		var $t=$(this);
		var dtype=$t.attr('dtype');
		$t.closest('dl').find('dd.highlight[dtype="'+dtype+'"]').dialog(
			{	buttons: { Ok: function() { $(this).dialog('destroy'); } },
				draggable: false,
				width: $('#search_results').width()*0.9,
				height: $('#search_results').height()*0.9,
//				maxHeight: $('#search_results').height()*0.9,
//				maxWidth: $('#search_results').width()*0.9,
				modal: true,
				position: {my:'center', at:'center', of:'#search_results',  collision:'fit' },
				resizable: false,
				title: dtype
			}
		).bind('dialogclose',function() {$(this).dialog('destroy'); });

	});

	$results.scrollTop(0);
	updateFrameSrc();
};
var updateFrameSrc=function() {
	var $original_page_iframe=$('#original_page_wrap > iframe');
	if (noFrame) {
		$original_page_iframe.removeAttr('src');
		return;
	}
	var selected_href=$('#search_results tr.selected a.frame_target').attr('href');
	if (selected_href == $original_page_iframe.attr('src'))
		return;
	if (!selected_href)
		$original_page_iframe.removeAttr('src');
	else
		$original_page_iframe.attr('src',selected_href);
};
var updateSplit=function(results_percent) {
//	results_percent=Math.round(results_percent);
	if (results_percent>95)
		results_percent=100;
	if (results_percent<20)
		results_percent=20;
	noFrame=(results_percent==100);
	$('#search_wrap').css('right',(101-results_percent)+'%');
	$('#original_page_wrap').css('left',(results_percent+1)+'%');
	$('#original_page_wrap_overlay').css('left',(results_percent+1)+'%');

	$('#vertical_separator').css('left',(results_percent-1)+'%');
	$('#vertical_separator').css('right',Math.max(99-results_percent,0)+'%');

	updateFrameSrc();
	$.jStorage.set('results_percent',{ results_percent: Math.round(results_percent)} );
	$('#settings [name=results_percent]').val(Math.round(results_percent));
};



var init=function() {
	//set split if saved
	var results_percent=$.jStorage.get('results_percent');
	if (results_percent)
		results_percent=parseInt(results_percent.results_percent);
	if (results_percent<=100 && results_percent>=20)
		updateSplit(results_percent);
	//set show or strip html tags
	showTags=$.jStorage.get('showTags');
	updateShowTags();

	$('#search_header').resize(function() {
		var header_height=$('#search_header').outerHeight();
		$('#search_results').css('top',header_height+'px');
	});

	//set up page
	$('#search_header p.new_search').hide();
	$('#search_resultinfo').hide();
	$('#settings').hide();
	$('#search_header p.settings a').click(function() {
		$('#settings').toggle();
		return false;
	});
	$('#search_menu p.new_search a').click(function() {
		$('#search_form_by_id').show();
		$('#search_form_by_crawler').show();
//		$('#search_menu p.settings').show();
		$('#search_menu p.new_search').hide();
		return false;
	});
/*	$('#search_header p.by_id a').click(function() {
		$('#search_forms form').hide();
		$('#search_form_by_id').show();
		return false;
	});

	$('#search_header p.by_crawler a').click(function() {
		$('#search_forms form').hide();
		$('#search_form_by_crawler').show();
		return false;
	}); */




	//load current crawlers
	$.post('getCrawlers.php', {}, function(d) {
		var $selectCrawler = $('#search_form_by_crawler select[name=crawler]');
		$.each(d,function(i,e) {
			$selectCrawler.append('<option value="'+e.id+'">'+
								e.id+', '+ e.state+', '+ e._last_session_state+
								'</option>');
		});
	},'json');
	$.post('getStates.php', {}, function(d) {
		var $selectState = $('#search_form_by_crawler select[name=state]');
		$.each(d,function(i,e) {
			$selectState.append(  '<option value="'+e+'">'+e+ '</option>');
		});
	},'json');
	$.get('tmpl/records-list-sale.html',function(d) { tmplRecordsSale=d; });
	$.get('tmpl/records-list-auction.html',function(d) { tmplRecordsAuction=d; });




	$('#search_form_by_crawler .random[type=submit]').click(function() {
		var postData={
			crawler:	$('#search_form_by_crawler [name=crawler]').val(),
			state:		$('#search_form_by_crawler [name=state]').val(),
			limit:		$('#search_form_by_crawler [name=limit]').val(),
			xfilter_status: $('#search_form_by_crawler [name=xfilter_status]:visible').val(),
			xfilter_status_nonempty: $('#search_form_by_crawler [name=xfilter_status_nonempty]:visible:checked').val()
		};
		if (postData.limit==0) {
			alert('Cannot list ALL records in random order!');
			return false;
		}
		if (!postData.crawler) {
			alert('Select a crawler!');
			return false;
		}

		var $t=$('#search_form_by_crawler .random[type=submit]');
		var $t_val=$t.attr('value');
		$t.attr('value','Loading...');
		$.post('getRecords.php', postData, function(d) {
			showRecords(d);
			$t.attr('value',$t_val);
		},'json');
		return false;
	});

	var listRetrieve=function(postData,cb) {
		$.post('getRecords.php', postData, function(d) {
			showRecords(d);
			if (cb)	cb();
			//paginate
			var $search_paginate=$('#search_paginate');
			if (d.status=='OK' && postData.limit!=0) {
				if (d.from>0) {
					$('<span>&nbsp;&nbsp; <a class="prev" href="#">prev</a> &nbsp;&nbsp;</span>').appendTo($search_paginate);
					$('a.prev',$search_paginate).click(function() {
						postData.from-=postData.limit;
						listRetrieve(postData);
						return false;
					});
				}

				if (d.from+d.limit<d.count) {
					$('<span>&nbsp;&nbsp; <a class="next" href="#">next</a> &nbsp;&nbsp;</span>').appendTo($search_paginate);
					$('a.next',$search_paginate).click(function() {
						postData.from+=postData.limit;
						listRetrieve(postData);
						return false;
					});
				}
				
			}
		},'json'); 


	};

	$('#search_form_by_crawler .list[type=submit]').click(function() {
		var postData={
			crawler:	$('#search_form_by_crawler [name=crawler]').val(),
			state:		$('#search_form_by_crawler [name=state]').val(),
			limit:		parseInt($('#search_form_by_crawler [name=limit]').val()),
			xfilter_status: $('#search_form_by_crawler [name=xfilter_status]:visible').val(),
			xfilter_status_nonempty: $('#search_form_by_crawler [name=xfilter_status_nonempty]:visible:checked').val(),
			type:		'LIST',
			from:		0
		};
		if (!postData.crawler) {
			alert('Select a crawler!');
			return false;
		}

		var $t=$('#search_form_by_crawler .list[type=submit]');
		var $t_val=$t.attr('value');
		$t.attr('value','Loading...');
		listRetrieve(postData, function() { $t.attr('value',$t_val); });
		return false;
	});

	$('#search_form_by_id').submit(function() {
		var postData={
			id:	$('#search_form_by_id [name=id]').val()
		};
		if (!postData.id) {
			alert('Enter a record id!');
			return false;
		}

		var $t=$('#search_form_by_id [type=submit]');
		var $t_val=$t.attr('value');
		$t.attr('value','Loading...');
		$.post('getRecords.php', postData, function(d) {
			showRecords(d);
			$t.attr('value',$t_val);
		},'json');
		return false;
	});


	var separator_dragging=false;
	$('#vertical_separator').on('mousedown', function(e) {
		separator_dragging=true;
		console.log($('#original_page_wrap_overlay'));
		$('#original_page_wrap_overlay').css('display','block');
		return false;
	});
	$(document).on('mouseup', function() {
		separator_dragging=false;
		$('#original_page_wrap_overlay').css('display','none');
	});

	$(document).on('mousemove',function(e) {
		if (!separator_dragging)
			return;
		updateSplit(100*e.pageX/$(document).width());
	});


	$('#settings .results_percent[type=submit]').click(function() {
		var results_percent=parseInt($('#settings [name=results_percent]').val());
		if (isNaN(results_percent) || results_percent<20 || results_percent >100) {
			alert('Split has to be between 20 and 100%');
			return false;
		}
		
		$('#settings').hide();
		updateSplit(results_percent);
		updateFrameSrc();
		return false;
	});
	$('#settings .strip_tags[type=submit]').click(function() {
		showTags=false;
		updateShowTags();
		return false;
	});
	$('#settings .show_tags[type=submit]').click(function() {
		showTags=true;
		updateShowTags();
		return false;
	});


	$('#xfilter_opener').click(function() {
		$('#xfilter').toggle();
		return false;
	});

};





//start when 
$(document).ready(function() {

	init();




});


})(jQuery);