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
 * You must have boardlinks or else this script will not load.
 * Search for "$config['boards'] = array(" within your inc/config.php and add something similar to your instance-config.php.
 *
 */


auto_reload_enabled = true; // for watch.js to interop

onReady(() => {
	if(document.querySelectorAll('div.banner').length === 0)
		return; // not index
		
	if(document.querySelectorAll(".post.op").length !== 1)
		return; //not thread page
	
	let countdown_interval;

	// Add an update link
	const boardlistBottom = document.querySelector('.boardlist.bottom');
	if (boardlistBottom && boardlistBottom.previousElementSibling) {
		const span = document.createElement('span');
		span.id = 'updater';
		span.innerHTML = "<a href='#' id='update_thread' style='padding-left:10px'>["+_("Update")+"]</a> (<input type='checkbox' id='auto_update_status' checked> "+_("Auto")+") <span id='update_secs'></span>";
		boardlistBottom.previousElementSibling.insertAdjacentElement('afterend', span);
	}

	// Grab the settings
	const settings = new script_settings('auto-reload');
	const poll_interval_mindelay        = settings.get('min_delay_bottom', 5000);
	const poll_interval_maxdelay        = settings.get('max_delay', 600000);
	const poll_interval_errordelay      = settings.get('error_delay', 30000);

	// number of ms to wait before reloading
	let poll_interval_delay = poll_interval_mindelay;
	let poll_current_time = poll_interval_delay;

	let end_of_page = false;

        let new_posts = 0;
	let first_new_post = null;
	
	const title = document.title;

	if (typeof update_title === "undefined") {
	   var update_title = function() { 
	   	if (new_posts) {
	   		document.title = "("+new_posts+") "+title;
	   	} else {
	   		document.title = title;
	   	}
	   };
	}

	if (typeof add_title_collector !== "undefined")
	add_title_collector(() => {
	  return new_posts;
	});

	let window_active = true;
	window.addEventListener('focus', () => {
		window_active = true;
		recheck_activated();

		// Reset the delay if needed
		if(settings.get('reset_focus', true)) {
			poll_interval_delay = poll_interval_mindelay;
		}
	});
	window.addEventListener('blur', () => {
		window_active = false;
	});
	
	const autoUpdateStatus = document.getElementById('auto_update_status');
	if (autoUpdateStatus) {
		autoUpdateStatus.addEventListener('click', function() {
			if(document.getElementById('auto_update_status').checked) {
				auto_update(poll_interval_mindelay);
			} else {
				stop_auto_update();
				document.getElementById('update_secs').textContent = "";
			}
		});
	}

	const decrement_timer = () => {
		poll_current_time = poll_current_time - 1000;
		const updateSecsEl = document.getElementById('update_secs');
		if (updateSecsEl) {
			updateSecsEl.textContent = poll_current_time/1000;
		}
		
		if (poll_current_time <= 0) {
			poll(false);
		}
	}

	const recheck_activated = function() {
		const boardlistBottomEl = document.querySelector('div.boardlist.bottom');
		if (new_posts && window_active && boardlistBottomEl &&
			window.scrollY + window.innerHeight >=
			boardlistBottomEl.getBoundingClientRect().top + window.scrollY) {

			new_posts = 0;
		}
		update_title();
		first_new_post = null;
	};
	
	// automatically updates the thread after a specified delay
	const auto_update = function(delay) {
		clearInterval(countdown_interval);

		poll_current_time = delay;		
		countdown_interval = setInterval(decrement_timer, 1000);
		const updateSecsEl = document.getElementById('update_secs');
		if (updateSecsEl) {
			updateSecsEl.textContent = poll_current_time/1000;
		}
	}
	
	const stop_auto_update = function() {
		clearInterval(countdown_interval);
	}
		
    	let epoch = new Date().getTime();
    	let epochold = epoch;
    	
	const timeDiff = function (delay) {
		if((epoch-epochold) > delay) {
			epochold = epoch = new Date().getTime();
			return true;
		}else{
			epoch = new Date().getTime();
			return false;
		}
	}
	
	const poll = async (manualUpdate) => {
		stop_auto_update();
		const updateSecsEl = document.getElementById('update_secs');
		if (updateSecsEl) {
			updateSecsEl.textContent = _("Updating...");
		}
	
		try {
			const response = await fetch(document.location);
			const data = await response.text();
			
			const loaded_posts = 0;	// the number of new posts loaded in this update
			const elementsToAppend = [];
			const elementsToTriggerNewpostEvent = [];
			
			// Parse the response HTML
			const parser = new DOMParser();
			const htmlDoc = parser.parseFromString(data, 'text/html');
			const newPosts = htmlDoc.querySelectorAll('div.post.reply');
			
			newPosts.forEach((post) => {
				const id = post.getAttribute('id');
				if(!document.getElementById(id)) {
					if (!new_posts) {
						first_new_post = post;
					}
					new_posts++;
					elementsToAppend.push(post.cloneNode(true));
					const br = document.createElement('br');
					br.className = 'clear';
					elementsToAppend.push(br);
					elementsToTriggerNewpostEvent.push(post);
				}
			});
			
			const lastPost = document.querySelector('div.post:last-of-type');
			if (lastPost && elementsToAppend.length > 0) {
				const container = lastPost.nextElementSibling;
				if (container) {
					elementsToAppend.forEach(el => {
						container.insertAdjacentElement('afterend', el);
					});
				}
			}
			recheck_activated();
			elementsToTriggerNewpostEvent.forEach((ele) => {
				document.dispatchEvent(new CustomEvent('new_post', { detail: ele }));
			});
			window.time_loaded = Date.now(); // interop with watch.js
			
			const autoUpdateEl = document.getElementById('auto_update_status');
			if (autoUpdateEl && autoUpdateEl.checked) {
				// If there are no new posts, double the delay. Otherwise set it to the min.
				if(elementsToAppend.length === 0) {
					// if the update was manual, don't increase the delay
					if (manualUpdate !== true) {
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
				if (elementsToAppend.length > 0) {
					const updateSecsEl = document.getElementById('update_secs');
					if (updateSecsEl) {
						updateSecsEl.textContent = fmt(_("Thread updated with {0} new post(s)"), [elementsToAppend.length]);
					}
				} else {
					const updateSecsEl = document.getElementById('update_secs');
					if (updateSecsEl) {
						updateSecsEl.textContent = _("No new posts found");
					}
				}
			}
		} catch (error) {
			const updateSecsEl = document.getElementById('update_secs');
			if (error.message === "Not Found" || error.status === 404) {
				if (updateSecsEl) {
					updateSecsEl.textContent = _("Thread deleted or pruned");
				}
				const autoUpdateEl = document.getElementById('auto_update_status');
				if (autoUpdateEl) {
					autoUpdateEl.checked = false;
					autoUpdateEl.disabled = true; // disable updates if thread is deleted
				}
				return;
			} else {
				if (updateSecsEl) {
					updateSecsEl.textContent = "Error: " + error.message;
				}
			}
			
			// Keep trying to update
			const autoUpdateEl = document.getElementById('auto_update_status');
			if (autoUpdateEl && autoUpdateEl.checked) {
				poll_interval_delay = poll_interval_errordelay;
				auto_update(poll_interval_delay);
			}
		}
		
		return false;
	};
	
	window.addEventListener('scroll', () => {
		recheck_activated();
		
		// if the newest post is not visible
		const lastPost = document.querySelector('div.post:last-of-type');
		if (lastPost &&
			window.scrollY + window.innerHeight <
			lastPost.getBoundingClientRect().top + window.scrollY + lastPost.offsetHeight) {
			end_of_page = false;
			return;
		} else {
			const autoUpdateEl = document.getElementById('auto_update_status');
			if(autoUpdateEl && autoUpdateEl.checked && timeDiff(poll_interval_mindelay)) {
				poll(true);
			}
			end_of_page = true;
		}
	});

	const updateThreadBtn = document.getElementById('update_thread');
	if (updateThreadBtn) {
		updateThreadBtn.addEventListener('click', () => { poll(true); return false; });
	}

	const autoUpdateEl = document.getElementById('auto_update_status');
	if(autoUpdateEl && autoUpdateEl.checked) {
		auto_update(poll_interval_delay);
	}
});
