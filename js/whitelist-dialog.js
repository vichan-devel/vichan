/*
 * Do not add this file to $config['additional_javascript'][]
 */

$("#whitelist_form").submit(function() {
	$('#whitelist_submit').prop('disabled', true);

	var formData = new FormData($("#whitelist_form")[0]);
	formData.append("json_response", "1");
	formData.append("whitelist", "1");

	$.ajax({
		url: "/post.php",
		type: "POST",
		data: formData,
		processData: false,
		contentType: false,
		success: function(post_response) {
			$("#alert_message").html(post_response.error);
		},
		error: function(xhr, status, er) {
			$("#alert_message").html(xhr);
		}
	}, "json");

	return false;
});