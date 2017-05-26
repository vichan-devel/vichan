
var announcementEditActive = false;

function AddAnnouncement(){
	// Return if an edit already is active
	if(announcementEditActive)
		return;
	announcementEditActive = true;

	$("#announcements-list tbody").prepend(
		"<tr>"+
		"<td>--</td>" +
		"<td><input type='text' name='announcement'/><input type='hidden' name='add_announcement'/></td>" +
		"<td>--</td>" +
		"<td><input type='image' src='/static/icons/save.png' alt='Save' title='Save' width='16' height='16' class='btnSave'/><img src='/static/icons/cancel_delete.png' alt='Delete' title='Delete' width='16' height='16' class='btnDelete'/></td>" +
		"<td style='display: none'/>" +
		"</tr>"
	);

	$(".btnDelete").bind("click", function() {
		var par = $(this).parent().parent(); //tr
		par.remove();
		
		announcementEditActive = false;
	});
};


function EditAnnouncement(){
	// Return if an edit already is active
	if(announcementEditActive)
		return;
	announcementEditActive = true;

	// Get random number to use for date select id (prevent shared id)
	var rand = Math.random();
	rand = rand.toString().replace(".", "0");

	var par = $(this).parent().parent(); //tr
	var tdAnnouncement = par.children("td:nth-child(2)");
	var tdButtons = par.children("td:nth-child(4)");
	var tdID = par.children("td:nth-child(5)");

	var tdAnnouncementText = tdAnnouncement.html();
	var tdIDText = tdID.html();

	tdAnnouncement.html("<input type='text' name='announcement' value='"+tdAnnouncement.text()+"'/><input type='hidden' name='edit_announcement'/>");
	tdButtons.html("<input type='image' src='/static/icons/save.png' alt='Save' title='Save' width='16' height='16' class='btnSave' onclick=\"return confirm('Are you sure you want to update Announcement?');\"/>" +
		"<img src='/static/icons/cancel_delete.png' alt='Cancel' title='Cancel' width='16' height='16' class='btnCancel'/>");
	tdID.html("<input type='hidden' name='id' value='"+tdID.html()+"'/>");

	$(".btnCancel").bind("click", function(){
		announcementEditActive = false;

		// tdDate.html(tdDateText);
		tdAnnouncement.html(tdAnnouncementText);
		tdButtons.html("<img src='/static/icons/cancel_delete.png' alt='Delete' title='Delete' width='16' height='16' class='btnDelete'/><img src='/static/icons/edit.png' alt='Edit' title='Edit' width='16' height='16' class='btnEdit'/>");
		tdID.html(tdIDText);

		$(".btnEdit").bind("click", EditAnnouncement);
		$(".btnDelete").bind("click", DeleteAnnouncement);
	});
};



function DeleteAnnouncement(){
	var par = $(this).parent().parent(); //tr

	var tdID = par.children("td:nth-child(5)");
	var tdIDText = tdID.html();
	tdID.html("<input type='hidden' name='id' value='"+tdID.html()+"'/><input type='hidden' name='delete_announcement'/>");

	if(confirm('Are you sure you want to DELETE Announcement?')) {
		$("#announcementForm").trigger('submit');
	}

	// Revert
	tdID.html(tdIDText);

	announcementEditActive = false;
};




