if(active_page === "index" || active_page === "ukko"){
	const hoverElem = null;
	
	$(document).mouseover((e) => {
		const x = e.clientX, y = e.clientY,
			elementOnMouseOver = document.elementFromPoint(x, y);
			hoverElem = $(elementOnMouseOver);
	});
	
	$(document).keydown((e) => {
		//Up arrow
		if(e.which === 38){
			const ele = hoverElem;
			const par = $(ele).parents('div[id^="thread_"]');
			
			if(par.length === 1){
				if(par.prev().getAttribute('id') != null){
					if(par.prev().getAttribute('id').match("^thread")){
						par.prev()[0].scrollIntoView(true);
					}
				}
			}
		//Down arrow
		}else if(e.which === 40){
			const ele = hoverElem;
			const par = $(ele).parents('div[id^="thread_"]');
			
			if(par.length === 1){
				if(par.next().getAttribute('id') != null){
					if(par.next().getAttribute('id').match("^thread")){
						par.next()[0].scrollIntoView(true);
					}
				}
			}
		}
	});
}
