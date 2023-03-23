/*
 * comment-toolbar.js
 *   - Adds a toolbar above the commenting area containing most of 8Chan's formatting options
 *   - Press Esc to close quick-reply window when it's in focus
 * 
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/comment-toolbar.js';
 */
if (active_page == 'thread' || active_page == 'index') {
	var formatText = (function($){
		"use strict";
		var self = {};
		self.rules = {
			spoiler: {
				text: _('Spoiler'),
				key: 's',
				multiline: false, 
				exclusiveline: false, 
				prefix:'**',
				suffix:'**'
			},
			italics: {
				text: _('Italics'),
				key: 'i',
				multiline: false, 
				exclusiveline: false, 
				prefix: "''",
				suffix: "''"
			},
			bold: {
				text: _('Bold'),
				key: 'b',
				multiline: false, 
				exclusiveline: false, 
				prefix: "'''",
				suffix: "'''"
			},
			underline: {
				text: _('Underline'),
				key: 'u',
				multiline: false, 
				exclusiveline: false, 
				prefix:'__',
				suffix:'__'
			},
			code: {
				text: _('Code'),
				key: 'f',
				multiline: true, 
				exclusiveline: false, 
				prefix: '[code]',
				suffix: '[/code]'
			},
			strike: {
				text: _('Strike'),
				key: 'd',
				multiline:false, 
				exclusiveline:false, 
				prefix:'~~',
				suffix:'~~'
			},
			heading: {
				text: _('Heading'),
				key: 'r',
				multiline:false, 
				exclusiveline:true, 
				prefix:'==',
				suffix:'=='
			}
		};
		
		self.toolbar_wrap = function(node) {
			var parent = $(node).parents('form[name="post"]');
			self.wrap(parent.find('#body')[0],'textarea[name="body"]', parent.find('.format-text > select')[0].value, false);
		};
	
		self.wrap = function(ref, target, option, expandedwrap) {
			// clean and validate arguments
			if (ref == null) return;
			var settings = {multiline: false, exclusiveline: false, prefix:'', suffix: null};
			$.extend(settings,JSON.parse(localStorage.formatText_rules)[option]);
			
			// resolve targets into array of proper node elements
			// yea, this is overly verbose, oh well.
			var res = [];
			if (target instanceof Array) {
				for (var indexa in target) {
					if (target.hasOwnProperty(indexa)) {
						if (typeof target[indexa] == 'string') {
							var nodes = $(target[indexa]);
							for (var indexb in nodes) {
								if (indexa.hasOwnProperty(indexb)) res.push(nodes[indexb]);
							}
						} else {
							res.push(target[indexa]);
						}
					}
				}
			} else {
				if (typeof target == 'string') {
					var nodes = $(target);
					for (var index in nodes) {
						if (nodes.hasOwnProperty(index)) res.push(nodes[index]);
					}
				} else {
					res.push(target);
				}
			}
			target = res;
			//record scroll top to restore it later.
			var scrollTop = ref.scrollTop;

			//We will restore the selection later, so record the current selection
			var selectionStart = ref.selectionStart;
			var selectionEnd = ref.selectionEnd;

			var text = ref.value;
			var before = text.substring(0, selectionStart);
			var selected = text.substring(selectionStart, selectionEnd);
			var after = text.substring(selectionEnd);
			var whiteSpace = [" ","\t"];
			var breakSpace = ["\r","\n"];
			var cursor;
			
			// handles multiline selections on formatting that doesn't support spanning over multiple lines
			if (!settings.multiline) selected = selected.replace(/(\r|\n|\r\n)/g,settings.suffix +"$1"+ settings.prefix);
			
			// handles formatting that requires it to be on it's own line OR if the user wishes to expand the wrap to the nearest linebreak
			if (settings.exclusiveline || expandedwrap) {
				// buffer the begining of the selection until a linebreak
				cursor = before.length -1;
				while (cursor >= 0 && breakSpace.indexOf(before.charAt(cursor)) == -1) {
					cursor--;
				}
				selected = before.substring(cursor +1) + selected;
				before = before.substring(0, cursor +1);
				
				// buffer the end of the selection until a linebreak
				cursor = 0;
				while (cursor < after.length && breakSpace.indexOf(after.charAt(cursor)) == -1) {
					cursor++;
				}
				selected += after.substring(0, cursor);
				after = after.substring(cursor);
			}
			
			// set values
			var res = before + settings.prefix + selected + settings.suffix + after;
			$(target).val(res);
			
			// restore the selection area and scroll of the reference
			ref.selectionEnd = before.length + settings.prefix.length + selected.length;
			if (selectionStart === selectionEnd) {
				ref.selectionStart = ref.selectionEnd;
			} else {
				ref.selectionStart = before.length + settings.prefix.length;
			}
			ref.scrollTop = scrollTop;
		};
		
		self.build_toolbars = function(){
			if (localStorage.formatText_toolbar == 'true'){
				// remove existing toolbars
				if ($('.format-text').length > 0) $('.format-text').remove();
				
				// Place toolbar above each textarea input
				var name, options = '', rules = JSON.parse(localStorage.formatText_rules);
				for (var index in rules) {
					if (!rules.hasOwnProperty(index)) continue;
					name = rules[index].text;

					//add hint if key exists
					if (rules[index].key) {
						name += ' (CTRL + '+ rules[index].key.toUpperCase() +')';
					}
					options += '<option value="'+ index +'">'+ name +'</option>';
				}
				$('[name="body"]').before('<div class="format-text"><a href="javascript:;" onclick="formatText.toolbar_wrap(this);">Wrap</a><select>'+ options +'</select></div>');
				$('body').append('<style>#quick-reply .format-text>a{width:15%;display:inline-block;text-align:center;}#quick-reply .format-text>select{width:85%;};</style>');
			}
		};
		
		self.add_rule = function(rule, index){
			if (rule === undefined) rule = {
				text: 'New Rule',
				key: '',
				multiline:false, 
				exclusiveline:false, 
				prefix:'',
				suffix:''
			}
			
			// generate an id for the rule
			if (index === undefined) {
				var rules = JSON.parse(localStorage.formatText_rules);
				while (rules[index] || index === undefined) {
					index = ''
					index +='abcdefghijklmnopqrstuvwxyz'.substr(Math.floor(Math.random()*26),1);
					index +='abcdefghijklmnopqrstuvwxyz'.substr(Math.floor(Math.random()*26),1);
					index +='abcdefghijklmnopqrstuvwxyz'.substr(Math.floor(Math.random()*26),1);
				}
			}
			if (window.Options && Options.get_tab('formatting')){
				var html = $('<div class="format_rule" name="'+ index +'"></div>').html('\
				<input type="text" name="text" class="format_option" size="10" value=\"'+ rule.text.replace(/"/g, '&quot;') +'\">\
				<input type="checkbox" name="multiline" class="format_option" '+ (rule.multiline ? 'checked' : '') +'>\
				<input type="checkbox" name="exclusiveline" class="format_option" '+ (rule.exclusiveline ? 'checked' : '') +'>\
				<input type="text" name="prefix" class="format_option" size="8" value=\"'+ (rule.prefix ? rule.prefix.replace(/"/g, '&quot;') : '') +'\">\
				<input type="text" name="suffix" class="format_option" size="8" value=\"'+ (rule.suffix ? rule.suffix.replace(/"/g, '&quot;') : '') +'\">\
				<input type="text" name="key" class="format_option" size="2" maxlength="1" value=\"'+ rule.key +'\">\
				<input type="button" value="X" onclick="if(confirm(\'Do you wish to remove the '+ rule.text +' formatting rule?\'))$(this).parent().remove();">\
				');
				
				if ($('.format_rule').length > 0) {
					$('.format_rule').last().after(html);
				} else {
					Options.extend_tab('formatting', html);
				}
			}
		};
		
		self.save_rules = function(){
			var rule, newrules = {}, rules = $('.format_rule');
			for (var index=0;rules[index];index++) {
				rule = $(rules[index]);
				newrules[rule.attr('name')] = {
					text: rule.find('[name="text"]').val(),
					key: rule.find('[name="key"]').val(),
					prefix: rule.find('[name="prefix"]').val(),
					suffix: rule.find('[name="suffix"]').val(),
					multiline: rule.find('[name="multiline"]').is(':checked'),
					exclusiveline: rule.find('[name="exclusiveline"]').is(':checked')
				};
			}
			localStorage.formatText_rules = JSON.stringify(newrules);
			self.build_toolbars();
		};
		
		self.reset_rules = function(to_default) {
			$('.format_rule').remove();
			var rules;
			if (to_default) rules = self.rules;
			else rules = JSON.parse(localStorage.formatText_rules);
			for (var index in rules){
				if (!rules.hasOwnProperty(index)) continue;
				self.add_rule(rules[index], index);
			}
		};
		
		// setup default rules for customizing
		if (!localStorage.formatText_rules) localStorage.formatText_rules = JSON.stringify(self.rules);
		
		// setup code to be ran when page is ready (work around for main.js compilation).
		$(document).ready(function(){
			// Add settings to Options panel general tab
			if (window.Options && Options.get_tab('general')) {
				var s1 = '#formatText_keybinds>input', s2 = '#formatText_toolbar>input', e = 'change';
				Options.extend_tab('general', '\
					<fieldset>\
						<legend>Formatting Options</legend>\
						<label id="formatText_keybinds"><input type="checkbox">' + _('Enable formatting keybinds') + '</label>\
						<label id="formatText_toolbar"><input type="checkbox">' + _('Show formatting toolbar') + '</label>\
					</fieldset>\
				');
			} else {
				var s1 = '#formatText_keybinds', s2 = '#formatText_toolbar', e = 'click';
				$('hr:first').before('<div id="formatText_keybinds" style="text-align:right"><a class="unimportant" href="javascript:void(0)">'+ _('Enable formatting keybinds') +'</a></div>');
				$('hr:first').before('<div id="formatText_toolbar" style="text-align:right"><a class="unimportant" href="javascript:void(0)">'+ _('Show formatting toolbar') +'</a></div>');
			}
			
			// add the tab for customizing the format settings
			if (window.Options && !Options.get_tab('formatting')) {
				Options.add_tab('formatting', 'angle-right', _('Customize Formatting'));
				Options.extend_tab('formatting', '\
				<style>\
					.format_option{\
						margin-right:5px;\
						overflow:initial;\
						font-size:15px;\
					}\
					.format_option[type="text"]{\
						text-align:center;\
						padding-bottom: 2px;\
						padding-top: 2px;\
					}\
					.format_option:last-child{\
						margin-right:0;\
					}\
					fieldset{\
						margin-top:5px;\
					}\
				</style>\
				');
				
				// Data control row
				Options.extend_tab('formatting', '\
				<button onclick="formatText.add_rule();">'+_('Add Rule')+'</button>\
				<button onclick="formatText.save_rules();">'+_('Save Rules')+'</button>\
				<button onclick="formatText.reset_rules(false);">'+_('Revert')+'</button>\
				<button onclick="formatText.reset_rules(true);">'+_('Reset to Default')+'</button>\
				');
				
				// Descriptor row
				Options.extend_tab('formatting', '\
					<span class="format_option" style="margin-left:25px;">Name</span>\
					<span class="format_option" style="margin-left:45px;" title="Multi-line: Allow formatted area to contain linebreaks.">ML</span>\
					<span class="format_option" style="margin-left:0px;" title="Exclusive-line: Require formatted area to start after and end before a linebreak.">EL</span>\
					<span class="format_option" style="margin-left:25px;" title="Text injected at the start of a format area.">Prefix</span>\
					<span class="format_option" style="margin-left:60px;" title="Text injected at the end of a format area.">Suffix</span>\
					<span class="format_option" style="margin-left:40px;" title="Optional keybind value to allow keyboard shortcut access.">Key</span>\
				');
				
				// Rule rows
				var rules = JSON.parse(localStorage.formatText_rules);
				for (var index in rules){
					if (!rules.hasOwnProperty(index)) continue;
					self.add_rule(rules[index], index);
				}
			}
		
			// setting for enabling formatting keybinds
			$(s1).on(e, function(e) {
				console.log('Keybind');
				if (!localStorage.formatText_keybinds || localStorage.formatText_keybinds == 'false') {
					localStorage.formatText_keybinds = 'true';
					if (window.Options && Options.get_tab('general')) e.target.checked = true;
				} else {
					localStorage.formatText_keybinds = 'false';
					if (window.Options && Options.get_tab('general')) e.target.checked = false;
				}
			});
			
			// setting for toolbar injection
			$(s2).on(e, function(e) {
				console.log('Toolbar');
				if (!localStorage.formatText_toolbar || localStorage.formatText_toolbar == 'false') {
					localStorage.formatText_toolbar = 'true';
					if (window.Options && Options.get_tab('general')) e.target.checked = true;
					formatText.build_toolbars();
				} else {
					localStorage.formatText_toolbar = 'false';
					if (window.Options && Options.get_tab('general')) e.target.checked = false;
					$('.format-text').remove();
				}
			});
			
			// make sure the tab settings are switch properly at loadup
			if (window.Options && Options.get_tab('general')) {
				if (localStorage.formatText_keybinds == 'true') $(s1)[0].checked = true;
				else $(s1)[0].checked = false;
				if (localStorage.formatText_toolbar == 'true') $(s2)[0].checked = true;
				else $(s2)[0].checked = false;
			}
			
			// Initial toolbar injection
			formatText.build_toolbars();
			
			//attach listener to <body> so it also works on quick-reply box
			$('body').on('keydown', '[name="body"]', function(e) {
				if (!localStorage.formatText_keybinds || localStorage.formatText_keybinds == 'false') return;
				var key = String.fromCharCode(e.which).toLowerCase();
				var rules = JSON.parse(localStorage.formatText_rules);
				for (var index in rules) {
					if (!rules.hasOwnProperty(index)) continue;
					if (key === rules[index].key && e.ctrlKey) {
						e.preventDefault();
						if (e.shiftKey) {
							formatText.wrap(e.target, 'textarea[name="body"]', index, true);
						} else {
							formatText.wrap(e.target, 'textarea[name="body"]', index, false);
						}
					}
				}
			});
			
			// Signal that comment-toolbar loading has completed.
			$(document).trigger('formatText');
		});
		
		return self;
    })(jQuery);
}
