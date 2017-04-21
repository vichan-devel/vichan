/*
 * auto-reload.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/auto-reload.js
 *
 * Brings AJAX to Tinyboard.
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net>
 * Copyright (c) 2013 undido <firekid109@hotmail.com>
 * Copyright (c) 2014-2015 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/titlebar-notifications.js';
 *   $config['additional_javascript'][] = 'js/auto-reload.js';
 *
 */

// From http://stackoverflow.com/a/14035162
$.fn.scrollStopped = function(callback) {		   
	$(this).scroll(function(){
		var self = this, $this = $(self);
		if ($this.data('scrollTimeout')) {
		  clearTimeout($this.data('scrollTimeout'));
		}
		$this.data('scrollTimeout', setTimeout(callback,250,self));
	});
};

function makeIcon(mode){
	var favicon = $("link[rel='shortcut icon']");

	if (!favicon.length) {
		var favicon = $('<link rel="shortcut icon"></link>').appendTo('head');
	}

	$("link[rel='shortcut icon']").attr("href", configRoot+"static/favicon"+(mode?"-"+mode:"")+".ico");
}

+function(){
var notify = false;
auto_reload_enabled = true; // for watch.js to interop

$(document).ready(function(){

	// Adds Options panel item
	if (typeof localStorage.auto_thread_update === 'undefined') {
		localStorage.auto_thread_update = 'true'; //default value
	}
	if (window.Options && Options.get_tab('general')) {
		Options.extend_tab("general", "<fieldset id='auto-update-fs'><legend>"+_("Auto update")+"</legend>"
		+ ('<label id="auto-thread-update"><input type="checkbox">' + _('Auto update thread') + '</label>')
		// + ('<label id="auto_thread_desktop_notifications"><input type="checkbox">' + _('Show desktop notifications when users quote me') + '</label>')
		+ ('<label id="auto_thread_desktop_notifications_all"><input type="checkbox">' + _('Show desktop notifications on all replies') + '</label>')
		+ '</fieldset>');

		$('#auto-thread-update>input').on('click', function() {
			if ($('#auto-thread-update>input').is(':checked')) {
				localStorage.auto_thread_update = 'true';
			} else {
				localStorage.auto_thread_update = 'false';
			}
		});

		$('#auto_thread_desktop_notifications>input,#auto_thread_desktop_notifications_all>input').on('click', function() {
			if (!("Notification" in window)) return;
	
			var setting = $(this).parent().attr('id');

			if ($(this).is(':checked')) {
				Notification.requestPermission(function(permission){
					if (permission === "granted") {
						localStorage[setting] = 'true';
					}
				});
				if (Notification.permission === "granted") {
					localStorage[setting] = 'true';
				}
			} else {
				localStorage[setting] = 'false';
			}
		});

		if (localStorage.auto_thread_update === 'true') {
			$('#auto-thread-update>input').prop('checked', true);
		}

		if (localStorage.auto_thread_desktop_notifications === 'true') {
			$('#auto_thread_desktop_notifications>input').prop('checked', true);
			notify = "mention";
		}

		if (localStorage.auto_thread_desktop_notifications_all === 'true') {
			$('#auto_thread_desktop_notifications_all>input').prop('checked', true);
			notify = "all";
		}
	}

	// not thread
	if (active_page != 'thread')
		return;

	var countdown_interval;

	// Add an update link
	$('span#thread-links').append("<span id='updater'><a href='#' id='update_thread'>["+_("Update")+"]</a> (<input type='checkbox' id='auto_update_status'> "+_("Auto")+") <span id='update_secs'></span></span>");

	// Set the updater checkbox according to user setting
	if (localStorage.auto_thread_update === 'true') {
		$('#auto_update_status').prop('checked', true);
	}

	// Grab the settings
	var settings = new script_settings('auto-reload');
	var poll_interval_mindelay        = settings.get('min_delay_bottom', 5000);
	var poll_interval_maxdelay        = settings.get('max_delay', 600000);
	var poll_interval_errordelay      = settings.get('error_delay', 30000);

	// number of ms to wait before reloading
	var poll_interval_delay = poll_interval_mindelay;
	var poll_current_time = poll_interval_delay;

	var end_of_page = false;

	var new_posts = 0;
	var first_new_post = null;
	
	var title = document.title;

	if (typeof update_title == "undefined") {
		var update_title = function() { 
			if (new_posts) {
				document.title = "("+new_posts+") "+title;
			} else {
				document.title = title;
				makeIcon(false);
			}
		};
	}

	if (typeof add_title_collector != "undefined")
	add_title_collector(function(){
	  return new_posts;
	});

	var window_active = true;
	$(window).focus(function() {
		window_active = true;
		recheck_activated();

		// Reset the delay if needed
		if(settings.get('reset_focus', true)) {
			poll_interval_delay = poll_interval_mindelay;
		}
	});
	$(window).blur(function() {
		window_active = false;
	});
	

	$('#auto_update_status').click(function() {
		if($("#auto_update_status").is(':checked')) {
			auto_update(poll_interval_mindelay);
		} else {
			stop_auto_update();
			$('#update_secs').text("");
		}

	});
	

	var decrement_timer = function() {
		poll_current_time = poll_current_time - 1000;
		$('#update_secs').text(poll_current_time/1000);
		
		if (poll_current_time <= 0) {
			poll(manualUpdate = false);
		}
	}

	var recheck_activated = function(end_of_page) {
		if (typeof end_of_page == "undefined") var end_of_page = false;
		if (end_of_page || (new_posts && window_active &&
			$(window).scrollTop() + $(window).height() >=
			$('div.boardlist.bottom').position().top)) {

			new_posts = 0;
		}
		update_title();
		first_new_post = null;
	};
	
	// automatically updates the thread after a specified delay
	var auto_update = function(delay) {
		clearInterval(countdown_interval);

		poll_current_time = delay;		
		countdown_interval = setInterval(decrement_timer, 1000);
		$('#update_secs').text(poll_current_time/1000);		
	}
	
	var stop_auto_update = function() {
		clearInterval(countdown_interval);
	}
		
	var epoch = (new Date).getTime();
	var epochold = epoch;
		
	var timeDiff = function (delay) {
		if((epoch-epochold) > delay) {
			epochold = epoch = (new Date).getTime();
			return true;
		}else{
			epoch = (new Date).getTime();
			return;
		}
	}
	
	var poll = function(manualUpdate) {
		stop_auto_update();
		$('#update_secs').text(_("Updating..."));
	
		$.ajax({
			url: document.location,
			success: function(data) {
				var loaded_posts = 0;	// the number of new posts loaded in this update
				$(data).find('div.post.reply').each(function() {
					var id = $(this).attr('id');
					if($('#' + id).length == 0) {
						if (!new_posts) {
							first_new_post = this;
							makeIcon('reply');
							if (notify === "all" || (notify === "mention" && $(this).find('.own_post').length)) {
								var body = $(this).children('.body').html().replace(/<br\s*[\/]?>/gi, "\n");
								var n = new Notification("New reply to "+$('title').text(), {body: $('<div/>').html(body).text()});
							}
						}
						$(this).insertAfter($('div.post:not(.post-hover):last').next()).after('<br class="clear">');
						new_posts++;
						loaded_posts++;
						$(document).trigger('new_post', this);
						recheck_activated();
					}
				});
				time_loaded = Date.now(); // interop with watch.js
				
				
				if ($('#auto_update_status').is(':checked')) {
					// If there are no new posts, double the delay. Otherwise set it to the min.
					if(loaded_posts == 0) {
						// if the update was manual, don't increase the delay
						if (manualUpdate == false) {
							poll_interval_delay *= 2;
				
							// Don't increase the delay beyond the maximum
							if(poll_interval_delay > poll_interval_maxdelay) {
								poll_interval_delay = poll_interval_maxdelay;
							}
						}
					} else {
						poll_interval_delay = poll_interval_mindelay;
					}
					
					auto_update(poll_interval_delay);
				} else {
					// Decide the message to show if auto update is disabled
					if (loaded_posts > 0)
						$('#update_secs').text(fmt(_("Thread updated with {0} new post(s)"), [loaded_posts]));
					else
						$('#update_secs').text(_("No new posts found"));
				}
			},
			error: function(xhr, status_text, error_text) {
				if (status_text == "error") {
					if (error_text == "Not Found") {
						$('#update_secs').text(_("Thread deleted or pruned"));
						$('#auto_update_status').prop('checked', false);
						$('#auto_update_status').prop('disabled', true); // disable updates if thread is deleted
						return;
					} else {
						$('#update_secs').text("Error: "+error_text);
					}
				} else if (status_text) {
					$('#update_secs').text(_("Error: ")+status_text);
				} else {
					$('#update_secs').text(_("Unknown error"));
				}
				
				// Keep trying to update
				if ($('#auto_update_status').is(':checked')) {
					poll_interval_delay = poll_interval_errordelay;
					auto_update(poll_interval_delay);
				}
			}
		});
		
		return false;
	};
	
	$(window).scrollStopped(function() {
		// if the newest post is not visible
		if($(this).scrollTop() + $(this).height() <
			$('div.post:last').position().top + $('div.post:last').height()) {
			end_of_page = false;
		} else {
			if($("#auto_update_status").is(':checked') && timeDiff(poll_interval_mindelay)) {
				poll(manualUpdate = true);
			}
			end_of_page = true;
		}
		recheck_activated(end_of_page);
	});

	$('#update_thread').on('click', function() { poll(manualUpdate = true); return false; });

	if($("#auto_update_status").is(':checked')) {
		auto_update(poll_interval_delay);
	}
});
}();
