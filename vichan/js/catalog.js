if (active_page == 'catalog') $(function(){
	if (localStorage.catalog !== undefined) {
		var catalog = JSON.parse(localStorage.catalog);
	} else {
		var catalog = {};
		localStorage.catalog = JSON.stringify(catalog);
	}

	$("#sort_by").change(function(){
		var value = this.value;
		$('#Grid').mixItUp('sort', (value == "random" ? value : "sticky:desc " + value));
		catalog.sort_by = value;
		localStorage.catalog = JSON.stringify(catalog);
	});

	$("#image_size").change(function(){
		var value = this.value, old;
		$(".grid-li").removeClass("grid-size-vsmall");
		$(".grid-li").removeClass("grid-size-small");
		$(".grid-li").removeClass("grid-size-large");
		$(".grid-li").addClass("grid-size-"+value);
		catalog.image_size = value;
		localStorage.catalog = JSON.stringify(catalog);
	});

	$('#Grid').mixItUp({
		animation: {
			enable: false
		}
	});

	if (catalog.sort_by !== undefined) {
		$('#sort_by').val(catalog.sort_by).trigger('change');
	}
	if (catalog.image_size !== undefined) {
		$('#image_size').val(catalog.image_size).trigger('change');
	}

	$('div.thread').on('click', function(e) {
		if ($(this).css('overflow-y') === 'hidden') {
			$(this).css('overflow-y', 'auto');
			$(this).css('width', '100%');
		} else {
			$(this).css('overflow-y', 'hidden');
			$(this).css('width', 'auto');
		}
	});
});
