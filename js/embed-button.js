/*
 * embed-button.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/embed-button.js';
 *
 */

$(document).ready(function () {
	if (active_page == 'catalog')
		return;

	if (window.Options && Options.get_tab('general')) {
		Options.extend_tab("general", "<label><input type='checkbox' id='disable-embedding' /> " + _('Disable embedding') + "</label>");

		$('#disable-embedding').on('change', function () {
			if ($(this).is(':checked')) {
				localStorage.disable_embedding = 'true';
			} else {
				localStorage.disable_embedding = 'false';
			}
		});

		if (localStorage.disable_embedding !== 'true') {
			addEmbedButtons();
		}
		else {
			$('#disable-embedding').attr('checked', 'checked');
		}
	}
	else {
		addEmbedButtons();
	}
});

function addEmbedButtons() {
	$('a.embed-link').after(function () { return '<span> [<a href="javascript:void(0)" class="embed-button no-decoration" data-embed-type="' + this.getAttribute('data-embed-type') + '" data-embed-data="' + this.getAttribute('data-embed-data') + '">Embed</a>]</span>'; });
	$('.embed-button').click(function () { toggleEmbed(this); });
}

var embedIdCounter = 0;
function generateEmbedId() {
	embedIdCounter++;
	return embedIdCounter;
}

function toggleEmbed(node) {
	if (node.textContent == 'Embed') {
		var embedId = generateEmbedId();
		node.setAttribute('data-embed-id', embedId);

		var embedCode = getEmbedHTML(node.getAttribute("data-embed-type"));
		embedCode = embedCode.replace("%video_width%", '640');
		embedCode = embedCode.replace("%video_height%", '360');
		embedCode = embedCode.replace("%embed_data%", node.getAttribute("data-embed-data"));

		var embeddedElement = $(embedCode).insertAfter($(node).parent());
		embeddedElement.attr('id', 'embed_frame_' + embedId);
		embeddedElement.addClass('embed_container');
	}
	else {
		var embedId = node.getAttribute('data-embed-id');

		$('#embed_frame_' + embedId).remove();
	}

	if (node.textContent == 'Embed')
		node.textContent = 'Remove';
	else
		node.textContent = 'Embed';
}

function getEmbedHTML(type) {
	switch (type) {
		case 'youtube':
			return '<iframe width="%video_width%" height="%video_height%" src="https://youtube.com/embed/%embed_data%" frameborder="0" allowfullscreen></iframe>';
		case 'dailymotion':
			return '<iframe width="%video_width%" height="%video_height%" src="https://www.dailymotion.com/embed/video/%embed_data%" frameborder="0" allowfullscreen></iframe>';
		case 'vimeo':
			return '<iframe width="%video_width%" height="%video_height%" src="https://player.vimeo.com/video/%embed_data%?byline=0&portrait=0" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		case 'vidme':
			return '<iframe width="%video_width%" height="%video_height%" src="https://vid.me/e/%embed_data%" frameborder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen scrolling="no"></iframe>';
		case 'liveleak':
			return '<iframe width="%video_width%" height="%video_height%" src="http://www.liveleak.com/ll_embed?i=%embed_data%" frameborder="0" allowfullscreen></iframe>';
		case 'metacafe':
			return '<iframe width="%video_width%" height="%video_height%" src="http://www.metacafe.com/embed/%embed_data%/" frameborder="0" allowfullscreen></iframe>';
		case 'vocaroo':
			return '<object width="148" height="44"><param name="movie" value="http://vocaroo.com/player.swf?playMediaID=%embed_data%&autoplay=0"></param><param name="wmode" value="transparent"></param><embed src="http://vocaroo.com/player.swf?playMediaID=%embed_data%&autoplay=0" width="148" height="44" wmode="transparent" type="application/x-shockwave-flash"></embed></object>';
		case 'soundcloud':
			return '<iframe width="640" height="166" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https%3A//soundcloud.com/%embed_data%&amp;color=ff5500&amp;auto_play=false&amp;hide_related=false&amp;show_comments=true&amp;show_user=true&amp;show_reposts=false"></iframe>';

		default:
			return '<span>Unknown embed type: "' + type + '"</span>';
	}
}