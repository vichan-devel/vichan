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
 * Copyright (c) 2014 Fredrick Brennan <admin@8chan.co>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/titlebar-notifications.js';
 *   $config['additional_javascript'][] = 'js/auto-reload.js';
 *
 */


auto_reload_enabled = true; // for watch.js to interop

$(document).ready(function(){
	if($('div.banner').length == 0)
		return; // not index
		
	if($(".post.op").size() != 1)
		return; //not thread page
	
	var countdown_interval;

	// Add an update link
	$('.boardlist.bottom').prev().after("<span id='updater'><a href='#' id='update_thread' style='padding-left:10px'>["+_("Update")+"]</a> (<input type='checkbox' id='auto_update_status' checked> "+_("Auto")+") <span id='update_secs'></span></span>");

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

	var recheck_activated = function() {
		if (new_posts && window_active &&
			$(window).scrollTop() + $(window).height() >=
			$('div.boardlist.bottom').position().top) {

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
				var elementsToAppend = [];
				var elementsToTriggerNewpostEvent = [];
				$(data).find('div.post.reply').each(function() {
					var id = $(this).attr('id');
					if($('#' + id).length == 0) {
						if (!new_posts) {
							first_new_post = this;
						}
						new_posts++;
						loaded_posts++;
						elementsToAppend.push($(this));
						elementsToAppend.push($('<br class="clear">'));
						elementsToTriggerNewpostEvent.push(this);
					}
				});
				$('div.post:last').next().after(elementsToAppend);
				recheck_activated();
				elementsToTriggerNewpostEvent.forEach(function(ele){
					$(document).trigger('new_post', ele);
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
	
	$(window).scroll(function() {
		recheck_activated();
		
		// if the newest post is not visible
		if($(this).scrollTop() + $(this).height() <
			$('div.post:last').position().top + $('div.post:last').height()) {
			end_of_page = false;
			return;
		} else {
			if($("#auto_update_status").is(':checked') && timeDiff(poll_interval_mindelay)) {
				poll(manualUpdate = true);
			}
			end_of_page = true;
		}
	});

	$('#update_thread').on('click', function() { poll(manualUpdate = true); return false; });

	if($("#auto_update_status").is(':checked')) {
		auto_update(poll_interval_delay);
	}
});

