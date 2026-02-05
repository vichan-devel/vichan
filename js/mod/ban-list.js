const banlist_init = function(token, my_boards, inMod) {
  inMod = !inMod;

  const lt;

  const selected = {};

  const time = () => { return Date.now()/1000|0; }

  $.getJSON(inMod ? ("?/bans.json/"+token) : token, (t) => {
    document.getElementById('banlist').on("new-row", function(e, drow, el) {
      const sel = selected[drow.id];
      if (sel) {
        $(el).querySelector('input.unban').prop("checked", true);
      }
      $(el).querySelector('input.unban').on("click", () => {
        selected[drow.id] = $(this).prop("checked");
      });


      if (drow.expires && drow.expires != 0 && drow.expires < time()) {
        $(el).querySelector('td').css("text-decoration", "line-through");
      }
    });

    const selall = "<input type='checkbox' id='select-all' style='float: left;'>";

    lt = document.getElementById('banlist').longtable({
      mask: {name: selall+_("IP address"), width: "220px", fmt: (f) => {
        const pre = "";
        if (inMod && f.access) {
          pre = "<input type='checkbox' class='unban'>";
        }

        if (inMod && f.single_addr && !f.masked) {
	  return pre+"<a href='?/IP/"+f.mask+"'>"+f.mask+"</a>";
	}
	return pre+f.mask;
      } },
      reason: {name: _("Reason"), width: "calc(100% - 770px - 6 * 4px)", fmt: (f) => {
	const add = "", suf = '';
        if (f.seen === 1) add += "<i class='fa fa-check' title='"+_("Seen")+"'></i>";
	if (f.message) {
	  add += "<i class='fa fa-comment' title='"+_("Message for which user was banned is included")+"'></i>";
	  suf = "<br /><br /><strong>"+_("Message:")+"</strong><br />"+f.message;
	}

	if (add) { add = "<div style='float: right;'>"+add+"</div>"; }

        if (f.reason) return add + f.reason + suf;
        else return add + "-" + suf;
      } },
      board: {name: _("Board"), width: "60px", fmt: (f) => {
        if (f.board) return "/"+f.board+"/";
	else return "<em>"+_("all")+"</em>";
      } },
      created: {name: _("Set"), width: "100px", fmt: (f) => {
        return ago(f.created) + _(" ago"); // in AGO form
      } },
      // duration?
      expires: {name: _("Expires"), width: "235px", fmt: (f) => {
	if (!f.expires || f.expires === 0) return "<em>"+_("never")+"</em>";
  const formattedDate = strftime("%m/%d/%Y (%a) %H:%M:%S", new Date((f.expires|0)*1000), datelocale);
  return formattedDate + ((f.expires < time()) ? "" : " <small>"+_("in ")+until(f.expires|0)+"</small>");
      } },
      username: {name: _("Staff"), width: "100px", fmt: (f) => {
	const pre='',suf='',un=f.username;
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
         name: (inMod)?_("Edit"):"&nbsp;", width: (inMod)?"35px":"0px", fmt: (f) => {
	 if (!inMod) return '';
	 return "<a href='?/edit_ban/"+f.id+"'>Edit</a>";
       } }
    }, {}, t);

    document.getElementById('select-all').click((e) => {
      const $this = $(this);
      document.querySelector('input.unban').prop("checked", $this.prop("checked"));
      lt.get_data().forEach((v) => { v.access && (selected[v.id] = $this.prop("checked")); });
      e.stopPropagation();
    });

    const filter = (e) => {
      if (document.getElementById('only_mine').prop("checked") && ($.inArray(e.board, my_boards) === -1)) return false;
      if (document.getElementById('only_not_expired').prop("checked") && e.expires && e.expires != 0 && e.expires < time()) return false;
      if (document.getElementById('search').value) {
        const terms = document.getElementById('search').value.split(" ");

        const fields = ["mask", "reason", "board", "staff", "message"];

        const ret_false = false;
	terms.forEach((t) => {
          const fs = fields;

	  const re = /^(mask|reason|board|staff|message):/, ma;
          if (ma = t.match(re)) {
            fs = [ma[1]];
	    t = t.replace(re, "");
	  }

	  const found = false
	  fs.forEach((f) => {
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

    document.querySelector('#only_mine, #only_not_expired, #search').on("click input", () => {
      lt.set_filter(filter);
    });
    lt.set_filter(filter);

    document.querySelector('.banform').on("submit", () => { return false; });

    document.getElementById('unban').on("click", () => {
      if (confirm('Are you sure you want to unban the selected IPs?')) {
        document.querySelectorAll('.banform .hiddens').remove();
        $("<input type='hidden' name='unban' value='unban' class='hiddens'>").appendTo(".banform");
    
        $.each(selected, (e) => {
          $("<input type='hidden' name='ban_"+e+"' value='unban' class='hiddens'>").appendTo(".banform");
        });
    
        document.querySelector('.banform').removeEventListener('submit').submit();
      }
    });

    if (device_type === 'desktop') {
      // Stick topbar
      const stick_on = document.querySelector('.banlist-opts').offset().top;
      const state = true;
      $(window).on("scroll resize", () => {
        if ($(window).scrollTop() > stick_on && state === true) {
  	  document.querySelector('body').css("margin-top", document.querySelector('.banlist-opts').height());
          document.querySelector('.banlist-opts').classList.add('boardlist top').detach().prependTo("body");
  	  document.querySelector('#banlist tr:not(.row)').classList.add('tblhead').detach().appendTo(".banlist-opts");
	  state = !state;
        }
        else if ($(window).scrollTop() < stick_on && state === false) {
	  document.querySelector('body').css("margin-top", "auto");
          document.querySelector('.banlist-opts').classList.remove('boardlist top').detach().prependTo(".banform");
	  document.querySelector('.tblhead').detach().prependTo("#banlist");
          state = !state;
        }
      });
    }
  });
}
