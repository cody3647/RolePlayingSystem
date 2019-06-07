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


function previewBio()
{	
	var textFields = [
		post_box_name
	];
	var numericFields = [
		'id_bio'
	];
	var checkboxFields = [
	];

	// Get the values from the form
	var x = [];
	x = getFields(textFields, numericFields, checkboxFields, 'rpsbiomodify');
	
	x[x.length] = 'item=character_biography';

	sendXMLDocument(elk_prepareScriptUrl(elk_scripturl) + 'action=xmlpreview;xml', x.join('&'), previewBioSent);

	// Show the preview section and load it with "pending results" text, onDocSent will finish things off
	document.getElementById('preview_section').style.display = 'block';
	document.getElementById('preview_body').innerHTML = txt_preview_fetch;

	return false;
}

/**
 * Callback function of the XMLhttp request
 *
 * @param {object} XMLDoc
 */
function previewBioSent(XMLDoc)
{
	var form_name = 'rpsbiomodify';
	var i = 0,
		n = 0,
		numErrors = 0,
		numCaptions = 0,
		$editor;

	if (!XMLDoc || !XMLDoc.getElementsByTagName('elk')[0])
	{
		document.forms[form_name].preview.onclick = function() {return true;};
		document.forms[form_name].preview.click();
		return true;
	}

	// Read the preview section data from the xml response
	var preview = XMLDoc.getElementsByTagName('elk')[0].getElementsByTagName('preview')[0];

	// Load in the body
	var bodyText = '';
	for (i = 0, n = preview.getElementsByTagName('body')[0].childNodes.length; i < n; i++)
		bodyText += preview.getElementsByTagName('body')[0].childNodes[i].nodeValue;

	document.getElementById('preview_body').innerHTML = bodyText;
	document.getElementById('preview_body').className = 'post';

	// Show a list of errors (if any).
	var errors = XMLDoc.getElementsByTagName('elk')[0].getElementsByTagName('errors')[0],
		errorList = '',
		errorCode = '',
		error_area = 'post_error',
		error_list = error_area + '_list',
		error_post = false;

	// @todo: this should stay together with the rest of the error handling or
	// should use errorbox_handler (at the moment it cannot be used because is not enough generic)
	for (i = 0, numErrors = errors.getElementsByTagName('error').length; i < numErrors; i++)
	{
		errorCode = errors.getElementsByTagName('error')[i].attributes.getNamedItem("code").value;
		if (errorCode === 'no_message' || errorCode === 'long_message')
			error_post = true;
		errorList += '<li id="' + error_area + '_' + errorCode + '" class="error">' + errors.getElementsByTagName('error')[i].firstChild.nodeValue + '</li>';
	}

	var oError_box = $(document.getElementById(error_area));
	if ($.trim(oError_box.children(error_list).html()) === '')
		oError_box.append("<ul id='" + error_list + "'></ul>");

	// Add the error it and show it
	if (numErrors === 0)
		oError_box.css("display", "none");
	else
	{
		document.getElementById(error_list).innerHTML = errorList;
		oError_box.css("display", "");
		oError_box.attr('class', parseInt(errors.getAttribute('serious')) === 0 ? 'warningbox' : 'errorbox');
	}

	// Adjust the color of captions if the given data is erroneous.
	var captions = errors.getElementsByTagName('caption');
	for (i = 0, numCaptions = errors.getElementsByTagName('caption').length; i < numCaptions; i++)
	{
		if (document.getElementById('caption_' + captions[i].getAttribute('name')))
			document.getElementById('caption_' + captions[i].getAttribute('name')).className = captions[i].getAttribute('class');
	}

	if (typeof $editor_container[post_box_name] !== 'undefined')
		$editor = $editor_container[post_box_name];
	else
		$editor = $(document.forms[form_name][post_box_name]);

	if (error_post)
		$editor.find("textarea, iframe").addClass('border_error');
	else
		$editor.find("textarea, iframe").removeClass('border_error');

	$('html, body').animate({ scrollTop: $('#preview_section').offset().top }, 'slow');

	// Preview video links if the feature is available
	if ($.isFunction($.fn.linkifyvideo))
		$().linkifyvideo(oEmbedtext, 'preview_body');

	// Spoilers, Sweetie
	$('.spoilerheader').on('click', function(){
		$(this).next().children().slideToggle("fast");
	});

	// Fix and Prettify code blocks
	if (typeof elk_codefix === 'function')
		elk_codefix();
	if (typeof prettyPrint === 'function')
		prettyPrint();

	// Prevent lighbox or default action on the preview
	$('[data-lightboximage]').on('click.elk_lb', function(e) {
		e.preventDefault();
	});
}

function biography_length() 
{

	var new_length = $("#rps_bio").data("sceditor").val().length;
	
	var diff = rps_minor_edit - Math.abs(rps_length - new_length);
	
	if(diff < 0)
		rps_span_modified.innerHTML = rps_modified_approval_text;
	if(diff >= 0)
		rps_span_modified.innerHTML = rps_modified_remaining_text;

	rps_span_length.innerHTML = rps_minor_edit_text[0] + diff + rps_minor_edit_text[1];
}