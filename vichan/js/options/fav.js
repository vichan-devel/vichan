$(document).ready(function(){
//Creating functions
var generateList = function(){
	var favStor = [];
  	for(var i=1; i<favorites.length+1; i++){
  		favStor.push($("#sortable > div:nth-child("+i+")").html());
  	}
	return favStor;
} //This will generate a list of boards based off of the list on the screen
function removeBoard(boardNumber){
	favorites.splice(boardNumber, 1);
	localStorage.favorites = JSON.stringify(favorites);
	$("#sortable > div:nth-child("+(boardNumber+1)+")").remove();
	$("#minusList > div:nth-child("+(favorites.length+1)+")").remove();
	add_favorites();
} //This removes a board from favorites, localStorage.favorites and the page
function addBoard(){
	$("#sortable").append("<div>"+($("#plusBox").val())+"</div>");
	$("#minusList").append( $('<div data-board="'+favorites.length+'" style="cursor: pointer; margin-right: 5px">-</div>').on('click', function(e){removeBoard($(this).data('board'));}) );
	favorites.push($("#plusBox").val());
	localStorage.favorites = JSON.stringify(favorites);
	$("#plusBox").val(""); //Removing text from textbox
	add_favorites();
} //This adds the text inside the textbox to favorites, localStorage.favorites and the page

var favorites = JSON.parse(localStorage.favorites);
Options.add_tab('fav-tab','star',_("Favorites"));

//Pregenerating list of boards 
var favList = $('<div id="sortable" style="cursor: pointer; display: inline-block">');
for(var i=0; i<favorites.length; i++){
    favList.append( $('<div>'+favorites[i]+'</div>') );
} 

//Creating list of minus symbols to remove unwanted boards
var minusList = $('<div id="minusList" style="color: #0000FF; display: inline-block">');
for(var i=0; i<favorites.length; i++){
    minusList.append( $('<div data-board="'+i+'" style="cursor: pointer; margin-right: 5px">-</div>').on('click', function(e){removeBoard($(this).data('board'));}) );
} 

//Help message so people understand how sorting boards works
$("<span>"+_("Drag the boards to sort them.")+"</span><br><br>").appendTo(Options.get_tab('fav-tab').content);

//Adding list of boards and minus symbols to remove boards with
$(minusList).appendTo(Options.get_tab('fav-tab').content); //Adding the list of minus symbols to the tab
$(favList).appendTo(Options.get_tab('fav-tab').content);  //Adding the list of favorite boards to the tab

//Adding spacing and text box to right boards into
var addDiv = $("<div id='favs-add-board'>");

var plusBox = $("<input id=\"plusBox\" type=\"text\">").appendTo(addDiv);
plusBox.keydown(function( event ) {
	if(event.keyCode == 13){
		$("#plus").click();
	}
});

//Adding plus symbol to use to add board
$("<div id=\"plus\">+</div>").css({
	cursor: "pointer",
	color: "#0000FF"
}).on('click', function(e){addBoard()}).appendTo(addDiv);

addDiv.appendTo(Options.get_tab('fav-tab').content); //Adding the plus button

favList.sortable(); //Making boards with sortable id use the sortable jquery function
favList.on('sortstop', function() {
	favorites = generateList();	
	localStorage.favorites = JSON.stringify(favorites);
	add_favorites();
});
});
