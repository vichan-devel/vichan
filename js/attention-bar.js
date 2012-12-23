$(document).ready(function(){
 $("#attention_bar").click(function(eO){ $("#attention_bar").css("display","none");
	 $("#attention_bar_form").css("display","block"); });
 $.get("/attentionbar.php", function(data) {
  $("#attention_bar").text(data);
  $("#attention_bar_input").val(data);
 });
});
