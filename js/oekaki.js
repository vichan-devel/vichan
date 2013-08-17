$(function() {
// Init
var oekaki_form = '\
		<tr id="oekaki">\
			<th>\
				Oekaki\
			</th>\
			<td>\
				<canvas width="'+oekaki_options.width+'" height="'+oekaki_options.height+'" id="oekaki_canvas" style="border:1px solid black;-webkit-user-select: none;-moz-user-select: none;">lol what you looking at the source for nerd</canvas>\
				<p><button type="button" id="brushsize">Brush size</button><input class="color" id="color" value="000000" placeholder="Color"/><button type="button" id="text">Set text</button><button type="button" id="clear">Clear</button><button type="button" id="save">Save</button><button type="button" id="load">Load</button><br/>\
				<button type="button" id="eraser">Toggle eraser</button><button type="button" id="getcolor">Get color</button><button type="button" id="fill">Fill</button>\
				</p><p><textarea id="savebox"></textarea><label><input id="confirm_oekaki" type="checkbox"/> Use oekaki instead of file?</label></p>\
				<img id="saved" style="display:none">\
			</td>\
		</tr>'

function enable_oekaki() {
	// Add oekaki after the file input
	$('input[type="file"]').parent().parent().after(oekaki_form);
	// Add "edit in oekaki" links
	$(".fileinfo").append(' <a href="javascript:void(0)" class="edit_in_oekaki">'+_('Edit in oekaki')+'</a>');
	// Init oekaki vars
	canvas = $("#oekaki_canvas");
	context = canvas[0].getContext("2d");
	is_drawing = false;
	text = "";
	eraser = getcolor = fill = false;
	context.strokeStyle = context.fillStyle = "black";
	// Attach canvas events
	attach_events();
	localStorage['oekaki'] = true;
}

function disable_oekaki(){
	$("#oekaki").detach();
	$(".edit_in_oekaki").detach();
	localStorage['oekaki'] = false;
}

if (localStorage['oekaki'] === undefined) { localStorage['oekaki'] = true }

$('hr:first').before('<div id="oekaki-status" style="text-align:right"><a class="unimportant" href="javascript:void(0)">-</a></div>');
$('div#oekaki-status a').text(_('Oekaki')+' (' + (localStorage['oekaki'] === 'true' ? _('enabled') : _('disabled')) + ')');

$('div#oekaki-status a').on('click', function(){
	var enabled = !JSON.parse(localStorage['oekaki']);

	if(enabled){
		enable_oekaki();
	} else {
		disable_oekaki();
	}

	$('div#oekaki-status a').text(_('Oekaki')+' (' + (enabled ? _('enabled') : _('disabled')) + ')');
});

if (localStorage['oekaki'] === "true") { enable_oekaki(); }

//http://stackoverflow.com/a/5624139/1901658
function hexToRgb(hex) {
	var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
	return result ? {
		r: parseInt(result[1], 16),
		g: parseInt(result[2], 16),
		b: parseInt(result[3], 16)
	} : null;
}

//http://stackoverflow.com/a/4025958/1901658
function arraysEqual(arr1, arr2) {
	if(arr1.length !== arr2.length)
		return false;
	for(var i = arr1.length; i--;) {
		if(arr1[i] !== arr2[i])
			return false;
	}

	return true;
}

function getmousepos(e){
	var r = canvas[0].getBoundingClientRect();
	mouseX = Math.round(e.clientX - r.left);
	mouseY = Math.round(e.clientY - r.top);
}

function setcolor(){
	context.strokeStyle = context.fillStyle = "#"+$(".color").val();
}

function flood_fill(x, y, target){
	var pixel = context.createImageData(1,1);
	var color = hexToRgb("#"+$(".color").val());
	pixel.data[0] = color.r; pixel.data[1] = color.g; pixel.data[2] = color.b; pixel.data[3] = 255;
	var queue = [];
	var node = [x, y];
	queue.push(node);
	//var iters = 0;
	while (queue.length > 0) {
		var n = queue.pop();
		var data = context.getImageData(n[0], n[1], 1, 1).data;
		var d = [data[0], data[1], data[2], data[3]];
		var t = [target[0], target[1], target[2], target[3]];
		if (arraysEqual(d, t) && n[0] < canvas.width() && n[1] < canvas.height() && n[0] > -1 && n[1] > -1){
			context.putImageData(pixel, n[0], n[1]);
			queue.push([n[0], n[1]-1]);
			queue.push([n[0], n[1]+1]);
			queue.push([n[0]-1, n[1]]);
			queue.push([n[0]+1, n[1]]);
			//iters++;
		}
		//if (iters%100===0){console.log(n[0]);console.log(n[1])}
		
	}
	return;
}

function color_under_pixel(x, y){
	return context.getImageData(x, y, 1, 1).data;
}

function attach_events(){

canvas.on("mousedown", function(e){
	getmousepos(e);
	$(this).css("cursor","none");
	if (getcolor) {
		var imagedata = color_under_pixel(mouseX, mouseY);
		$("#color")[0].color.fromRGB(imagedata[0], imagedata[1], imagedata[2]);
		getcolor = false;
		setcolor();
	}
	else if (fill && !eraser) {
		flood_fill(mouseX, mouseY, color_under_pixel(mouseX, mouseY));
		fill = false;
	}
	else {
		is_drawing = true;
		context.beginPath();
		context.moveTo(mouseX,mouseY);
		context.fillText(text,mouseX,mouseY);
		$("#confirm_oekaki").attr("checked",true);
	}
	
});		

canvas.on("mousemove", function(e){
	getmousepos(e);
	if (is_drawing) {
		context.lineTo(mouseX,mouseY);
		context.stroke()
	}
});

canvas.on("mouseup mouseout", function(e){
	context.stroke()
	$(this).css("cursor","auto");
	is_drawing = false;
});

$("#brushsize").on("click",function(){
	var size = prompt("Enter brush size");
	if (parseInt(size) == NaN) {
		return
	}
	else {
		context.lineWidth = size;
	}
});

$(".color").on("change", setcolor);

$("#text").on("click", function(e){
	text = prompt("Enter some text") || "";
	context.font = prompt("Enter font or leave alone", context.font)
});

function clear(){
	context.beginPath();
	context.clearRect(0,0,canvas.width(),canvas.height());
	$("#confirm_oekaki").attr("checked",false)
	canvas[0].height = oekaki_options.height; canvas[0].width = oekaki_options.width;
};

$("#clear").on("click", clear);

$("#save").on("click",function(){
	$("#savebox").val(canvas[0].toDataURL());
});

$("#load").on("click", function(){
	clear();
	var img = new Image();
	img.src = $("#savebox").val();
	img.onload = function(){context.drawImage(img,0,0);};
	$("#confirm_oekaki").attr("checked",true)
});

$("#eraser").on("click", function(){
	if (eraser) {
		eraser = false;
		context.strokeStyle = context.fillStyle = "#"+$(".color").val();
		context.globalCompositeOperation = old_gco;
	}
	else {
		eraser = true;
		old_gco = context.globalCompositeOperation;
		context.globalCompositeOperation = "destination-out";
		context.strokeStyle = "rgba(0,0,0,1)";
	}
});

$("#getcolor").on("click", function(){
	getcolor = true;
});

$("#fill").on("click", function(){
	fill = true;
});

$(".edit_in_oekaki").on("click", function(){ 
	var img_link = $(this).parent().parent().find("a>img.post-image").parent()[0]
	var img = new Image();
	img.onload = function() {
		canvas[0].width = img.width; canvas[0].height = img.height;
		context.drawImage(img, 0, 0);
	}
	img.src = $(img_link).attr("href");
});
}

function dataURItoBlob(dataURI) {
    var binary = atob(dataURI.split(',')[1]);
    var array = new Array(binary.length);
    for(var i = 0; i < binary.length; i++) {
        array[i] = binary.charCodeAt(i);
    }
    return new Blob([new Uint8Array(array)], {type: 'image/jpeg'});
}

$("form[name='post']").on("submit", function(e){
	if ($("#confirm_oekaki").is(":checked")) {
		e.preventDefault();
		$("input[type='file']").remove();
		var dataURL = canvas[0].toDataURL();
		var blob = dataURItoBlob(dataURL);
		var fd = new FormData(document.forms[0]);
		fd.append("file", blob, "Oekaki.png");
		fd.append("post", $("input[name='post']").val());
		$.ajax({
			type: "POST",
			url: oekaki_options.root+"post.php",
			data: fd,
			processData: false,
			contentType: false,
			success: function(data) {
				location.reload();
			},
			error: function(jq, data) {alert($('h2',jq.responseText).text());}
		});
	}

	else {
		return true;
	};
});
});
