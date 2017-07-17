var announcementlist_init = function(token, inMod) {
  inMod = !inMod;

  $.getJSON(inMod ? ("?/announcements.json/"+token) : (token), function(json) {
    var tr;

    var thead = $("<thead/>");
    tr = $("<tr/>");
    tr.append("<th width='150px'>Date</th>");
    tr.append("<th>Announcement</th>");
    if(inMod) {
      tr.append("<th width='100px'>Staff</th>");
      tr.append("<th width='40px'><img src='/static/icons/add.png' alt='Add' title='Add' width='16' height='16' id='btnAdd'/></th>");
      tr.append("<th width='0px' style='display: none'></th>");
    }
    thead.append(tr);

    var tbody = $("<tbody/>");
    for (var i = 0; i < json.length; i++) {
        tr = $("<tr/>");
        tr.append("<td>" + json[i].date_formated + "</td>");
        tr.append("<td>" + json[i].text + "</td>");
        if(inMod) {
          tr.append("<td><a href='?/new_PM/" + json[i].username + "'>" + json[i].username + "</td>");
          tr.append("<td><img src='/static/icons/cancel_delete.png' alt='Delete' title='Delete' width='16' height='16' class='btnDelete'/><img src='/static/icons/edit.png' alt='Edit' title='Edit' width='16' height='16' class='btnEdit'/></td>");
          tr.append("<td style='display: none'>" + json[i].id + "</td>");
        }
        tbody.append(tr);
    }

    $("#announcements-list").append(thead);
    $("#announcements-list").append(tbody);

    if(inMod) {
      // $(".btnSave").bind("click", SaveAnnouncement);
      $(".btnEdit").bind("click", EditAnnouncement);
      $(".btnDelete").bind("click", DeleteAnnouncement);
			$("#btnAdd").bind("click", AddAnnouncement);
    }
  });

} 
