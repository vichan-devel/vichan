if (active_page == 'thread' || active_page == 'index') {
	$(document).ready(function(){
		if (window.Options && Options.get_tab('general')) {
			var selector = '#color-ids>input';
			var e = 'change';
			Options.extend_tab("general", "<label id='color-ids'><input type='checkbox' /> "+_('Color IDs')+"</label>");
		}

		else {
			var selector = '#color-ids';
			var e = 'click';
			$('hr:first').before('<div id="color-ids" style="text-align:right"><a class="unimportant" href="javascript:void(0)">'+_('Color IDs')+'</a></div>')
		}

		$(selector).on(e, function() {
			if (localStorage.color_ids === 'true') {
				localStorage.color_ids = 'false';
			} else {
				localStorage.color_ids = 'true';
			}
		});

		if (!localStorage.color_ids || localStorage.color_ids === 'false') {
			return;
		} else {
			$('#color-ids>input').attr('checked','checked');
		}

		function IDToRGB(id_str){
			var id = id_str.match(/.{1,2}/g);
			var rgb = new Array();

			for (i = 0; i < id.length; i++) {
				rgb[i] = parseInt(id[i], 16);
			}

			return rgb;
		}

		function colorPostId(el) {
			var rgb = IDToRGB($(el).text());
			var ft = "#fff";

			if ((rgb[0]*0.299 + rgb[1]*0.587 + rgb[2]*0.114) > 125)
				ft = "#000";

			$(el).css({
				"background-color": "rgb("+rgb[0]+", "+rgb[1]+", "+rgb[2]+")",
				"padding": "0px 5px",
				"border-radius": "8px",
				"color": ft
			});
		}

		$(".poster_id").each(function(k, v){
			colorPostId(v);
		});

		$(document).on('new_post', function(e, post) {
			$(post).find('.poster_id').each(function(k, v) {
				colorPostId(v);
			});
		});
	});
}
