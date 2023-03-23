if (active_page == 'thread' || active_page == 'index') {
	$(document).ready(function(){
		function arrayRemove(a, v) { a.splice(a.indexOf(v) == -1 ? a.length : a.indexOf(v), 1); }

		var idshighlighted = [];

		function getPostsById(id){
			return $(".poster_id").filter(function(i){
				return $(this).text() == id;
			});
		}

		function getMasterPosts(parents){
			if(!parents.hasClass("post")) return;
			
			var toRet = [];
			
			$(parents).each(function(){
				if($(this).hasClass("post"))
					toRet.push($(this));
			});
			
			return toRet;
		}

		var id_highlighter = function(){
			var id = $(this).text();
			
			if($.inArray(id, idshighlighted) !== -1){
				arrayRemove(idshighlighted, id);
				
				$(getMasterPosts(getPostsById(id).parents())).each(function(i){
					$(this).removeClass("highlighted");
				});
			}else{
				idshighlighted.push(id);
				
				$(getMasterPosts(getPostsById(id).parents())).each(function(i){
					$(this).addClass("highlighted");
				});
			}
		}

		$(".poster_id").on('click', id_highlighter);

		$(document).on('new_post', function(e, post) {
			$(post).find('.poster_id').on('click', id_highlighter);
		});
	});
}
