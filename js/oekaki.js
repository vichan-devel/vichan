$(function() {
var canvas = $("#cvs");
var context = canvas[0].getContext("2d");
var is_drawing = false;
var text = "";
var eraser = getcolor = fill = false;
context.strokeStyle = context.fillStyle = "black";

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
		if (arraysEqual(d, t) && n[0] < 500 && n[1] < 250 && n[0] > -1 && n[1] > -1){
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
			url: "/post.php",
			data: fd,
			processData: false,
			contentType: false,
			success: function(data) {
				location.reload();
			},
			error: function(data) {alert("Something went wrong!"); console.log(data)}
		});
	}

	else {
		return true;
	};
});
});
