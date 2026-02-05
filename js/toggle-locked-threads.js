/*
 * toggle-locked-threads.js
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net> 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   //$config['additional_javascript'][] = 'js/options.js';
 *   //$config['additional_javascript'][] = 'js/style-select.js';
 *   //$config['additional_javascript'][] = 'js/options/general.js';
 *   $config['additional_javascript'][] = 'js/toggle-locked-threads.js';
 *
 */

if (active_page === 'ukko' || active_page === 'index' || (window.Options && Options.get_tab('general')))
onReady(() => {
	const hide_locked_threads = localStorage['hidelockedthreads'] ? true : false;

	$('<style type="text/css"> img.hidden{ opacity: 0.1; background: grey; border: 1px solid #000; } </style>').appendTo(document.querySelector('head'));
	
	const hideLockedThread = function($thread) {
		if (active_page === 'ukko' || active_page === 'index')
		$thread
			.style.display = 'none'
			.classList.add('hidden');
	};
	
	const restoreLockedThread = function($thread) {
		$thread
			.style.display = ''
			.classList.remove('hidden');
	};
	
	const getThreadFromIcon = function($icon) {
		return $icon.parent().parent().parent()
	};

	const selector, event;
        if (window.Options && Options.get_tab('general')) {
                selector = '#toggle-locked-threads>input';
                event = 'change';
                Options.extend_tab("general", "<label id='toggle-locked-threads'><input type='checkbox' /> "+_('Hide locked threads')+"</label>");
        }
        else {
                selector = '#toggle-locked-threads a';
                event = 'click';
		document.querySelector('hr:first').before('<div id="toggle-locked-threads" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
        }
	
	document.querySelector('div#toggle-locked-threads a')
		.text(hide_locked_threads ? _('Show locked threads') : _('Hide locked threads'));

	$(selector)
		.on(event, () => {
			hide_locked_threads = !hide_locked_threads;
			if (hide_locked_threads) {
				$('img.icon[title="Locked"], i.fa-lock.fa').each(() => {
					hideLockedThread(getThreadFromIcon($(this)));
				});
				localStorage.hidelockedthreads = true;
			} else {
				$('img.icon[title="Locked"], i.fa-lock.fa').each(() => {
					restoreLockedThread(getThreadFromIcon($(this)));
				});
				delete localStorage.hidelockedthreads;
			}
			
			$(this).text(hide_locked_threads ? _('Show locked threads') : _('Hide locked threads'))
		});
	
	if (hide_locked_threads) {
		$('img.icon[title="Locked"], i.fa-lock.fa').each(() => {
			hideLockedThread(getThreadFromIcon($(this)));
		});

		if (window.Options && Options.get_tab('general')) {
			document.querySelector('#toggle-locked-threads>input').prop('checked', true);
		}
	}
        $(document).addEventListener('new_post', function(e, post) {
		if (hide_locked_threads) {
			$(post).find('img.icon[title="Locked"], i.fa-lock.fa').each(() => {
	                        hideLockedThread(getThreadFromIcon($(this)));
       		        });
		}
	});
});

