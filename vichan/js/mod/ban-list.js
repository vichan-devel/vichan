var banlist_init = function(token, my_boards, inMod) {
  inMod = !inMod;

  var lt;

  var selected = {};

  var time = function() { return Date.now()/1000|0; }

  $.getJSON(inMod ? ("?/bans.json/"+token) : token, function(t) {
    $("#banlist").on("new-row", function(e, drow, el) {
      var sel = selected[drow.id];
      if (sel) {
        $(el).find('input.unban').prop("checked", true);
      }
      $(el).find('input.unban').on("click", function() {
        selected[drow.id] = $(this).prop("checked");
      });


      if (drow.expires && drow.expires != 0 && drow.expires < time()) {
        $(el).find("td").css("text-decoration", "line-through");
      }
    });

    var selall = "<input type='checkbox' id='select-all' style='float: left;'>";

    lt = $("#banlist").longtable({
      mask: {name: selall+_("IP address"), width: "220px", fmt: function(f) {
        var pre = "";
        if (inMod && f.access) {
          pre = "<input type='checkbox' class='unban'>";
        }

        if (inMod && f.single_addr && !f.masked) {
	  return pre+"<a href='?/IP/"+f.mask+"'>"+f.mask+"</a>";
	}
	return pre+f.mask;
      } },
      reason: {name: _("Reason"), width: "calc(100% - 770px - 6 * 4px)", fmt: function(f) {
	var add = "", suf = '';
        if (f.seen == 1) add += "<i class='fa fa-check' title='"+_("Seen")+"'></i>";
	if (f.message) {
	  add += "<i class='fa fa-comment' title='"+_("Message for which user was banned is included")+"'></i>";
	  suf = "<br /><br /><strong>"+_("Message:")+"</strong><br />"+f.message;
	}

	if (add) { add = "<div style='float: right;'>"+add+"</div>"; }

        if (f.reason) return add + f.reason + suf;
        else return add + "-" + suf;
      } },
      board: {name: _("Board"), width: "60px", fmt: function(f) {
        if (f.board) return "/"+f.board+"/";
	else return "<em>"+_("all")+"</em>";
      } },
      created: {name: _("Set"), width: "100px", fmt: function(f) {
        return ago(f.created) + _(" ago"); // in AGO form
      } },
      // duration?
      expires: {name: _("Expires"), width: "235px", fmt: function(f) {
	if (!f.expires || f.expires == 0) return "<em>"+_("never")+"</em>";
        return strftime(window.post_date, new Date((f.expires|0)*1000), datelocale) + 
          ((f.expires < time()) ? "" : " <small>"+_("in ")+until(f.expires|0)+"</small>");
      } },
      username: {name: _("Staff"), width: "100px", fmt: function(f) {
	var pre='',suf='',un=f.username;
	if (inMod && f.username && f.username != '?' && !f.vstaff) {
	  pre = "<a href='?/new_PM/"+f.username+"'>";
	  suf = "</a>";
	}
	if (!f.username) {
	  un = "<em>"+_("system")+"</em>";
	}
	return pre + un + suf;
      } },
      id: {
         name: (inMod)?_("Edit"):"&nbsp;", width: (inMod)?"35px":"0px", fmt: function(f) {
	 if (!inMod) return '';
	 return "<a href='?/edit_ban/"+f.id+"'>Edit</a>";
       } }
    }, {}, t);

    $("#select-all").click(function(e) {
      var $this = $(this);
      $("input.unban").prop("checked", $this.prop("checked"));
      lt.get_data().forEach(function(v) { v.access && (selected[v.id] = $this.prop("checked")); });
      e.stopPropagation();
    });

    var filter = function(e) {
      if ($("#only_mine").prop("checked") && ($.inArray(e.board, my_boards) === -1)) return false;
      if ($("#only_not_expired").prop("checked") && e.expires && e.expires != 0 && e.expires < time()) return false;
      if ($("#search").val()) {
        var terms = $("#search").val().split(" ");

        var fields = ["mask", "reason", "board", "staff", "message"];

        var ret_false = false;
	terms.forEach(function(t) {
          var fs = fields;

	  var re = /^(mask|reason|board|staff|message):/, ma;
          if (ma = t.match(re)) {
            fs = [ma[1]];
	    t = t.replace(re, "");
	  }

	  var found = false
	  fs.forEach(function(f) {
	    if (e[f] && e[f].indexOf(t) !== -1) {
	      found = true;
	    }
	  });
	  if (!found) ret_false = true;
        });

        if (ret_false) return false;
      }

      return true;
    };

    $("#only_mine, #only_not_expired, #search").on("click input", function() {
      lt.set_filter(filter);
    });
    lt.set_filter(filter);

    $(".banform").on("submit", function() { return false; });

    $("#unban").on("click", function() {
      $(".banform .hiddens").remove();
      $("<input type='hidden' name='unban' value='unban' class='hiddens'>").appendTo(".banform");

      $.each(selected, function(e) {
        $("<input type='hidden' name='ban_"+e+"' value='unban' class='hiddens'>").appendTo(".banform");
      });

      $(".banform").off("submit").submit();
    });

    if (device_type == 'desktop') {
      // Stick topbar
      var stick_on = $(".banlist-opts").offset().top;
      var state = true;
      $(window).on("scroll resize", function() {
        if ($(window).scrollTop() > stick_on && state == true) {
  	  $("body").css("margin-top", $(".banlist-opts").height());
          $(".banlist-opts").addClass("boardlist top").detach().prependTo("body");
  	  $("#banlist tr:not(.row)").addClass("tblhead").detach().appendTo(".banlist-opts");
	  state = !state;
        }
        else if ($(window).scrollTop() < stick_on && state == false) {
	  $("body").css("margin-top", "auto");
          $(".banlist-opts").removeClass("boardlist top").detach().prependTo(".banform");
	  $(".tblhead").detach().prependTo("#banlist");
          state = !state;
        }
      });
    }
  });
}
