if (device_type == 'desktop') { /* we don't want it to launch on mobiles */

$(document).ready(function(x){
  var frameskip = 0;
  $(".boardlist").on("mousemove", function(e){
    if (frameskip) {
      frameskip--;
    }
    else {
      frameskip = 5;

      var myx = e.pageX;
    
      var bl_want = "unstick";
      if (localStorage.bl_menu !== undefined && localStorage.bl_menu == "stick") {
        bl_want = "stick";
      }
      if (this.boardlist === undefined || this.bl_preparedfor != bl_want) {
        this.bl_preparedfor = bl_want;
        var n = [];
      
        $(".board", this).each(function(a,b) { n[a] = [b, $(b).position().left, $(b).width()]; });
       
        this.boardlist = n;
      }
    
      for (u in this.boardlist) {
        var t = this.boardlist[u][0];

        if (t.saved_h === undefined) {
          t.saved_h = 1.6;
        }
      
        var pos = this.boardlist[u][1] + (this.boardlist[u][2]/2);
        if (localStorage.bl_menu !== undefined && localStorage.bl_menu == "stick") {
          pos += 200;
        }
        var newh = 3.5 - 0.03*Math.abs(pos - myx);
        newh = (newh < 1.6) ? 1.6 : newh;
      
        if (newh != t.saved_h) {
          t.style.height = newh+"em";
          t.style.width = newh+"em";
          this.boardlist[u][1] = $(t).position().left;
          this.boardlist[u][2] = $(t).width();
	  t.saved_h = newh;
        }
      }
    }
  });
  
  $(".boardlist").on("mouseleave", function(x){
    //$(this).css({height: "2em"});
    $(".board", this).each(function(y){
      $(this).animate({height: "1.6em", width: "1.6em"});
      this.saved_h = 1.6;
    });
    frameskip = 0;
  });
  
  $(".bl-show").on("click", function() {
    $(".bl-menu").show();
    $(".bl-show").hide();
  });
  
  $(".bl-menu").on("mouseleave", function() {
    if (localStorage.bl_menu === undefined || localStorage.bl_menu != "stick") {
      $(".bl-menu").hide();
      $(".bl-show").show();
    }
  });
  
  var bl_stick = function() {
    $(".bl-show").hide();
    $(".bl-menu").show();
    $("body").addClass("bl-sticked");
  };
  
  var bl_unstick = function() {
    $(".bl-show").show();
    $(".bl_menu").hide();
    $("body").removeClass("bl-sticked");
  };
  
  $("#bl-stick").on("click", function() {
    if (localStorage.bl_menu !== undefined && localStorage.bl_menu == "stick") {
      localStorage.bl_menu = "unstick";
      bl_unstick();
    }
    else {
      localStorage.bl_menu = "stick";
      bl_stick();
    }
  });
  
  if (localStorage.bl_menu !== undefined && localStorage.bl_menu == "stick") {
    bl_stick();
  }
});

}
