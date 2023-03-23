// Thanks to Khorne on #8chan at irc.rizon.net
// https://gitlab.com/aymous/8chan-watchlist

'use strict';
/* jshint globalstrict:true, quotmark:single */
/* jshint browser:true, jquery:true, devel:true, unused:true, undef:true */
/* global active_page:false, board_name:false */
if(!localStorage.watchlist){
	//If the watchlist is undefined in the localStorage,
	//initialize it as an empty array.
	localStorage.watchlist = '[]';
}
var watchlist = {};

/**
 * [render /> Creates a watchlist container and populates it with info
 * about each thread that's currently being watched. If the watchlist container
 * already exists, it empties it out and repopulates it.]
 * @param  {[Bool]} reset [If true and the watchlist is rendered, remove it]
 */
watchlist.render = function(reset) {
	/* jshint eqnull:true */
	if (reset == null) reset = false;
	/* jshint eqnull:false */
	if (reset && $('#watchlist').length) $('#watchlist').remove();
	var threads = [];
	//Read the watchlist and create a new container for each thread.
	JSON.parse(localStorage.watchlist).forEach(function(e, i) {
		//look at line 69, that's what (e) is here.
		threads.push('<div class="watchlist-inner" id="watchlist-'+i+'">' +
		'<span>/'+e[0]+'/ - ' +
		'<a href="'+e[3]+'">'+e[1].replace("thread_", _("Thread #"))+'</a>' +
		' ('+e[2]+') </span>' +
		'<a class="watchlist-remove">X</a>'+
	'</div>');
	});
	if ($('#watchlist').length) {
		//If the watchlist is already there, empty it and append the threads.
		$('#watchlist').children('.watchlist-inner').remove();
		$('#watchlist').append(threads.join(''));
	} else {
		//If the watchlist has not yet been rendered, create it.
		var menuStyle = getComputedStyle($('.boardlist')[0]);
		$((active_page == 'ukko') ? 'hr:first' : (active_page == 'catalog') ? 'body>span:first' : 'form[name="post"]').before(
			$('<div id="watchlist">'+
					'<div class="watchlist-controls">'+
						'<span><a id="clearList">['+_('Clear List')+']</a></span>&nbsp'+
						'<span><a id="clearGhosts">['+_('Clear Ghosts')+']</a></span>'+
					'</div>'+
					threads.join('')+
				'</div>').css("background-color", menuStyle.backgroundColor).css("border", menuStyle.borderBottomWidth+" "+menuStyle.borderBottomStyle+" "+menuStyle.borderBottomColor));
	}
	return this;
};

/**
 * [add /> adds the given item to the watchlist]
 * @param {[Obj/Str]} sel [An unwrapped jquery selector.]
 */
watchlist.add = function(sel) {
	var threadName, threadInfo;

	var board_name = $(sel).parents('.thread').data('board');

	if (active_page === 'thread') {
		if ($('.subject').length){
			//If a subject is given, use the first 20 characters as the thread name.
			threadName = $('.subject').text().substring(0,20);
		} else { //Otherwise use the thread id.
			threadName = $('.op').parent().attr('id');
		}
		//board name, thread name as defined above, current amount of posts, thread url
		threadInfo = [board_name, threadName, $('.post').length, location.href];

	} else if (active_page === 'index' || active_page === 'ukko') {

		var postCount;
		//Figure out the post count.
		if ($(sel).parents('.op').children('.omitted').length) {
			postCount = $(sel).parents('.op').children('.omitted').text().split(' ')[0];
		} else {
			postCount = $(sel).parents('.op').siblings('.post').length+1;
		}
		//Grab the reply link.;
		var threadLink = $(sel).siblings('a:not(.watchThread)').last().attr('href');
		//Figure out the thread name. If anon, use the thread id.
		if ($(sel).parent().find('.subject').length) {
			threadName = $(sel).parent().find('.subject').text().substring(0,20);
		} else {
			threadName = $(sel).parents('div').last().attr('id');
		}

		threadInfo = [board_name, threadName, postCount, threadLink];

	} else {
		alert('Functionality not yet implemented for this type of page.');
		return this;
	}

	//if the thread is already being watched, cancel the function.
	if (localStorage.watchlist.indexOf(JSON.stringify(threadInfo)) !== -1) {
		return this;
	}

	var _watchlist = JSON.parse(localStorage.watchlist); //Read the watchlist
	_watchlist.push(threadInfo); //Add the new watch item.
	localStorage.watchlist = JSON.stringify(_watchlist); //Save the watchlist.
	return this;
};

/**
 * [remove /> removes the given item from the watchlist]
 * @param  {[Int]} n [The index at which to remove.]
 */
watchlist.remove = function(n) {
	var _watchlist = JSON.parse(localStorage.watchlist);
	_watchlist.splice(n, 1);
	localStorage.watchlist = JSON.stringify(_watchlist);
	return this;
};

/**
 * [clear /> resets the watchlist to the initial empty array]
 */
watchlist.clear = function() {
	localStorage.watchlist = '[]';
	return this;
};

/**
 * [exists /> pings every watched thread to check if it exists and removes it if not]
 * @param  {[Obj/Str]} sel [an unwrapped jq selector]
 */
watchlist.exists = function(sel) {
	$.ajax($(sel).children().children('a').attr('href'), {
		type :'HEAD',
		error: function() {
			watchlist.remove(parseInt($(sel).attr('id').split('-')[1])).render();
		},
		success : function(){
			return;
		}
	});
};

$(document).ready(function(){
	if (!(active_page == 'thread' || active_page == 'index' || active_page == 'catalog' || active_page == 'ukko')) {
		return;
	}

	//Append the watchlist toggle button.
	$('.boardlist').append(' <span>[ <a class="watchlist-toggle" href="#">'+_('watchlist')+'</a> ]</span>');
    	//Append a watch thread button after every OP post number.
    	$('.op>.intro>.post_no:odd').after('<a class="watchThread" href="#">['+_('Watch Thread')+']</a>');

	//Draw the watchlist, hidden.
	watchlist.render();

	//Show or hide the watchlist.
	$('.watchlist-toggle').on('click', function(e) {
		e.preventDefault();
		//if ctrl+click, reset the watchlist.
		if (e.ctrlKey) {
			watchlist.render(true);
		}
		if ($('#watchlist').css('display') !== 'none') {
			$('#watchlist').css('display', 'none');
		} else {
			$('#watchlist').css('display', 'block');
		} //Shit got really weird with hide/show. Went with css manip. Probably faster anyway.
	});

	//Trigger the watchlist add function.
	//The selector is passed as an argument in case the page is not a thread.
	$('.watchThread').on('click', function(e) {
		e.preventDefault();
		watchlist.add(this).render();
	});

	//The index is saved in .watchlist-inner so that it can be passed as the argument here.
	//$('.watchlist-remove').on('click') won't work in case of re-renders and
	//the page will need refreshing. This works around that.
	$(document).on('click', '.watchlist-remove', function() {
		var item = parseInt($(this).parent().attr('id').split('-')[1]);
		watchlist.remove(item).render();
	});

	//Empty the watchlist and redraw it.
	$('#clearList').on('click', function(){
		watchlist.clear().render();
	});

	//Get rid of every watched item that no longer directs to an existing page.
	$('#clearGhosts').on('click', function() {
		$('.watchlist-inner').each(function(){
			watchlist.exists(this);
		});
	});

});

