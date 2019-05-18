/**
 * Gets the signature preview via ajax and populates the preview box
 *
 * @param {boolean} showPreview
 */
function ajax_getSignaturePreview(showPreview)
{
	showPreview = (typeof showPreview === 'undefined') ? false : showPreview;
	$.ajax({
		type: "POST",
		url: elk_scripturl + "?action=xmlpreview;xml",
		data: {item: "sig_preview", signature: $("#signature").val(), user: $('input[name="u"]').attr("value")},
		context: document.body
	})
	.done(function(request) {
		var i = 0;

		if (showPreview)
		{
			var signatures = ["current", "preview"];
			for (i = 0; i < signatures.length; i++)
			{
				$("#" + signatures[i] + "_signature").css({display:"block"});
				$("#" + signatures[i] + "_signature_display").css({display:"block"}).html($(request).find('[type="' + signatures[i] + '"]').text() + '<hr />');
			}

			$('.spoilerheader').on('click', function(){
				$(this).next().children().slideToggle("fast");
			});
		}

		var $_profile_error = $("#profile_error");

		if ($(request).find("error").text() !== '')
		{
			if (!$_profile_error.is(":visible"))
				$_profile_error.css({display: "", position: "fixed", top: 0, left: 0, width: "100%", 'z-index': '100'});

			var errors = $(request).find('[type="error"]'),
				errors_html = '<span>' + $(request).find('[type="errors_occurred"]').text() + '</span><ul>';

			for (i = 0; i < errors.length; i++)
				errors_html += '<li>' + $(errors).text() + '</li>';

			errors_html += '</ul>';
			$(document).find("#profile_error").html(errors_html);
		}
		else
		{
			$_profile_error.css({display:"none"});
			$_profile_error.html('');
		}

		return false;
	});

	return false;
}