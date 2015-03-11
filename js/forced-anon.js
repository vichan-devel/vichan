/*
 * forced-anon.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/forced-anon.js
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
 *   $config['additional_javascript'][] = 'js/forced-anon.js';
 *
 */

if (active_page == 'ukko' || active_page == 'thread' || active_page == 'index' || (window.Options && Options.get_tab('general')))
$(document).ready(function() {
	var force_anon = function() {
		if($(this).children('a.capcode').length == 0) {
			var id = $(this).parent().children('a.post_no:eq(1)').text();
			
			if($(this).children('a.email').length != 0)
				var p = $(this).children('a.email');
			else
				var p = $(this);
			
			old_info[id] = {'name': p.children('span.name').text(), 'trip': p.children('span.trip').text()};
			
			p.children('span.name').text('Anonymous');
			if(p.children('span.trip').length != 0)
				p.children('span.trip').text('');
		}
	};
		
	var enable_fa = function() {
		$('p.intro label').each(force_anon);
	};
	
	var disable_fa = function() {
		$('p.intro label').each(function() {
			if($(this).children('a.capcode').length == 0) {
				var id = $(this).parent().children('a.post_no:eq(1)').text();
				
				if(old_info[id]) {
					if($(this).children('a.email').length != 0)
						var p = $(this).children('a.email');
					else
						var p = $(this);
					
					p.children('span.name').text(old_info[id]['name']);
					if(p.children('span.trip').length != 0)
						p.children('span.trip').text(old_info[id]['trip']);
				}
			}
		});
	};
	
	old_info = {};
	forced_anon = localStorage['forcedanon'] ? true : false;

	var selector, event;
        if (window.Options && Options.get_tab('general')) {
                selector = '#forced-anon';
                event = 'change';
                Options.extend_tab("general", "<label id='forced-anon'><input type='checkbox' />"+_('Forced anonymity')+"</label>");
        }
        else {
                selector = '#forced-anon';
                event = 'click';
		$('hr:first').before('<div id="forced-anon" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
		$('div#forced-anon a').text(_('Forced anonymity')+' (' + (forced_anon ? _('enabled') : _('disabled')) + ')');
        }
	
	
	$(selector).on(event, function() {
		forced_anon = !forced_anon;
		
		if(forced_anon) {
			$('div#forced-anon a').text(_('Forced anonymity')+' ('+_('enabled')+')');
			localStorage.forcedanon = true;
			enable_fa();
		} else {
			$('div#forced-anon a').text(_('Forced anonymity')+' ('+_('disabled')+')');
			delete localStorage.forcedanon;
			disable_fa();
		}
		
		return false;
	});
	
	if(forced_anon) {
		enable_fa();

                if (window.Options && Options.get_tab('general')) {
                        $('#toggle-locked-threads>input').prop('checked', true);
                }
	}
	
	$(document).on('new_post', function(e, post) {
		if(forced_anon)
			$(post).find('p.intro label').each(force_anon);
	});
});

