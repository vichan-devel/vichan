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
const watchlist = {};

/**
 * [render /> Creates a watchlist container and populates it with info
 * about each thread that's currently being watched. If the watchlist container
 * already exists, it empties it out and repopulates it.]
 * @param  {[Bool]} reset [If true and the watchlist is rendered, remove it]
 */
watchlist.render = (reset) => {
	/* jshint eqnull:true */
	if (reset === null) reset = false;
	/* jshint eqnull:false */
	if (reset && document.getElementById('watchlist').length) document.getElementById('watchlist').remove();
	const threads = [];
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
	if (document.getElementById('watchlist').length) {
		//If the watchlist is already there, empty it and append the threads.
		document.getElementById('watchlist').children('.watchlist-inner').remove();
		document.getElementById('watchlist').appendChild(threads.join(''));
	} else {
		//If the watchlist has not yet been rendered, create it.
		const menuStyle = getComputedStyle(document.querySelector('.boardlist')[0]);
		$((active_page === 'ukko') ? 'hr:first' : (active_page === 'catalog') ? 'body>span:first' : 'form[name="post"]').before(
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
watchlist.add = (sel) => {
	const threadName, threadInfo;

	const board_name = $(sel).parents('.thread').data('board');

	if (active_page === 'thread') {
		if (document.querySelector('.subject').length){
			//If a subject is given, use the first 20 characters as the thread name.
			threadName = document.querySelector('.subject').textContent.substring(0,20);
		} else { //Otherwise use the thread id.
			threadName = document.querySelector('.op').parent().getAttribute('id');
		}
		//board name, thread name as defined above, current amount of posts, thread url
		threadInfo = [board_name, threadName, document.querySelector('.post').length, location.href];

	} else if (active_page === 'index' || active_page === 'ukko') {

		const postCount;
		//Figure out the post count.
		if ($(sel).parents('.op').children('.omitted').length) {
			postCount = $(sel).parents('.op').children('.omitted').textContent.split(' ')[0];
		} else {
			postCount = $(sel).parents('.op').siblings('.post').length+1;
		}
		//Grab the reply link.;
		const threadLink = $(sel).siblings('a:not(.watchThread)').last().getAttribute('href');
		//Figure out the thread name. If anon, use the thread id.
		if ($(sel).parent().querySelector('.subject').length) {
			threadName = $(sel).parent().querySelector('.subject').textContent.substring(0,20);
		} else {
			threadName = $(sel).parents('div').last().getAttribute('id');
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

	const _watchlist = JSON.parse(localStorage.watchlist); //Read the watchlist
	_watchlist.push(threadInfo); //Add the new watch item.
	localStorage.watchlist = JSON.stringify(_watchlist); //Save the watchlist.
	return this;
};

/**
 * [remove /> removes the given item from the watchlist]
 * @param  {[Int]} n [The index at which to remove.]
 */
watchlist.remove = (n) => {
	const _watchlist = JSON.parse(localStorage.watchlist);
	_watchlist.splice(n, 1);
	localStorage.watchlist = JSON.stringify(_watchlist);
	return this;
};

/**
 * [clear /> resets the watchlist to the initial empty array]
 */
watchlist.clear = () => {
	localStorage.watchlist = '[]';
	return this;
};

/**
 * [exists /> pings every watched thread to check if it exists and removes it if not]
 * @param  {[Obj/Str]} sel [an unwrapped jq selector]
 */
watchlist.exists = (sel) => {
	fetch($(sel).children().children('a').getAttribute('href'), {
		type :'HEAD',
		error: () => {
			watchlist.remove(parseInt($(sel).getAttribute('id').split('-')[1])).render();
		},
		success : () => {
			return;
		}
	});
};

onReady(() => {
	if (!(active_page === 'thread' || active_page === 'index' || active_page === 'catalog' || active_page === 'ukko')) {
		return;
	}

	//Append the watchlist toggle button.
	document.querySelector('.boardlist').appendChild(' <span>[ <a class="watchlist-toggle" href="#">'+_('watchlist')+'</a> ]</span>');
    	//Append a watch thread button after every OP post number.
    	document.querySelectorAll('.op>.intro>.post_no:odd').after('<a class="watchThread" href="#">['+_('Watch Thread')+']</a>');

	//Draw the watchlist, hidden.
	watchlist.render();

	//Show or hide the watchlist.
	document.querySelector('.watchlist-toggle').on('click', (e) => {
		e.preventDefault();
		//if ctrl+click, reset the watchlist.
		if (e.ctrlKey) {
			watchlist.render(true);
		}
		if (document.getElementById('watchlist').style.display !== 'none') {
			document.getElementById('watchlist').css('display', 'none');
		} else {
			document.getElementById('watchlist').css('display', 'block');
		} //Shit got really weird with hide/show. Went with css manip. Probably faster anyway.
	});

	//Trigger the watchlist add function.
	//The selector is passed as an argument in case the page is not a thread.
	document.querySelector('.watchThread').on('click', (e) => {
		e.preventDefault();
		watchlist.add(this).render();
	});

	//The index is saved in .watchlist-inner so that it can be passed as the argument here.
	//document.querySelector('.watchlist-remove').on('click') won't work in case of re-renders and
	//the page will need refreshing. This works around that.
	$(document).on('click', '.watchlist-remove', () => {
		const item = parseInt($(this).parent().getAttribute('id').split('-')[1]);
		watchlist.remove(item).render();
	});

	//Empty the watchlist and redraw it.
	document.getElementById('clearList').on('click', () => {
		watchlist.clear().render();
	});

	//Get rid of every watched item that no longer directs to an existing page.
	document.getElementById('clearGhosts').on('click', () => {
		document.querySelector('.watchlist-inner').each(() => {
			watchlist.exists(this);
		});
	});

});

