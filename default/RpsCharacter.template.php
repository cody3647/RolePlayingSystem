<?php

/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0.5
 *
 */


/**
 * Before registering - get their information.
 */
function template_create_form()
{
	global $context, $settings, $scripturl, $txt, $modSettings, $cur_profile;

	// Any errors?
	if (!empty($context['creation_errors']))
	{
		echo '
		<div class="errorbox">
			<span>', $txt['registration_errors_occurred'], '</span>
			<ul>';

		// Cycle through each error and display an error message.
		foreach ($context['creation_errors'] as $error)
			echo '
				<li>', $error, '</li>';

		echo '
			</ul>
		</div>';
	}

	echo '
		<form action="', $scripturl, '?action=character;sa=create2" method="post" accept-charset="UTF-8" name="character_creation" id="character_creation">
			<h2 class="category_header">TXT Create Form Header</h2>
				<fieldset class="content">
					<dl class="settings">
						<dt>
							<strong><label for="elk_autov_name">TXT Name Label:</label></strong>
						</dt>
						<dd>
							<input type="text" name="name" id="elk_autov_name" size="30" tabindex="', $context['tabindex']++, '" maxlength="60" value="', isset($cur_profile['name']) ? $cur_profile['name'] : '', '" class="input_text" placeholder="TXT Name" required="required" autofocus="autofocus" />
							<span id="elk_autov_name_div" class="hide">
								<a id="elk_autov_name_link" href="#">
									<i id="elk_autov_name_img" class="icon i-check" alt="*"></i>
								</a>
							</span>
						</dd>';
	echo '
						<dt>
							<strong>TXT Birthdate</strong><br />
							<span class="smalltext">TXT Explanation</span>
						</dt>
						<dd>
							<input type="text" name="bday3" size="4" maxlength="4" value="', isset($cur_profile['birth_date']['year']) ? $cur_profile['birth_date']['year'] : '', '" class="input_text" tabindex="', $context['tabindex']++, '" /> -
							<input type="text" name="bday1" size="2" maxlength="2" value="', isset($cur_profile['birth_date']['month']) ? $cur_profile['birth_date']['month'] : '', '" class="input_text" tabindex="', $context['tabindex']++, '" /> -
							<input type="text" name="bday2" size="2" maxlength="2" value="', isset($cur_profile['birth_date']['day']) ? $cur_profile['birth_date']['day'] : '', '" class="input_text" tabindex="', $context['tabindex']++, '" />
						</dd>';

	echo '
						<dt>
							<strong>TXT Title:</strong>
						</dt>
						<dd>
							<input type="text" name="title" id="title" size="30" value="', isset( $cur_profile['title'] ) ? $cur_profile['title'] : '', '" tabindex="', $context['tabindex']++, '" class="input_text" />
						</dd>
					</dl>';

	$lastItem = 'hr';

	// Are there any custom profile fields - if so print them!
	if (!empty($context['character_custom_fields']))
	{
		if ($lastItem !== 'hr')
			echo '
					<hr class="clear" />';

		echo '
					<dl class="settings">';

		foreach ($context['character_custom_fields'] as $field)
		{
			echo '
						<dt>
							<strong>', $field['name'], '</strong><br />
							<span class="smalltext">', $field['desc'], '</span>
						</dt>
						<dd>
							', $field['input_html'], '
						</dd>';
		}

		echo '
					</dl>';
	}

	echo '
			<div id="confirm_buttons" class="flow_auto">';

	
	echo '
				<input type="submit" name="createSubmit" value="TXT create" tabindex="', $context['tabindex']++, '" class="right_submit" />';

	echo '
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="u" value="', $context['user']['id'], '" />
			<input type="hidden" name="c" value="0" />
			<input type="hidden" name="', $context['create_token_var'], '" value="', $context['create_token'], '" />
			<input type="hidden" name="step" value="2" />
		</form>';
		template_js_nameCheck();
}

function template_js_nameCheck()
{
	global $txt;
	addInlineJavascript('				
		var checkNameTxt = {
			"valid": {alt: "'.$txt['rps_name_valid'].'", title:"'.$txt['rps_name_valid'].'"},
			"invalid": {alt: "'.$txt['rps_name_invalid'].'", title:"'.$txt['rps_name_invalid'].'"},
			"check": {alt: "'.$txt['rps_name_help'].'", title: "'.$txt['rps_name_help'].'"}
		};

		function getData() {
			$.post(elk_scripturl + "?action=character;sa=checkname;xml", {
				u: elk_member_id,
				c: $("input[name=c]").val(),
				name: $("input[name=name]").val()
			})
			.done(checkName);
		}

		function checkName(data) {
			var xml_node = $("elk", data);
			var valid = xml_node.find("name").attr("valid");
			if (valid == 1) {
				$("#name_img").addClass("i-check").removeClass("i-warn").removeClass("i-help").attr(checkNameTxt["valid"]);
			} else if (valid == 0) {
				$("#name_img").removeClass("i-check").addClass("i-warn").removeClass("i-help").attr(checkNameTxt["invalid"]);
			} else {
				$("#name_img").removeClass("i-check").removeClass("i-warn").addClass("i-help").attr(checkNameTxt["check"]);
			}
		}

		$("#name").blur(getData);
		$( document ).ready(getData);', true);
}

/**
 * After registration... all done ;).
 */
function template_after()
{
	global $context;

	// Not much to see here, just a quick... "you're now registered!" or what have you.
	echo '
		<div id="registration_success">
			<h2 class="category_header">', $context['title'], '</h2>
			<div class="windowbg">
				<p class="content">', $context['description'], '</p>
			</div>
		</div>';
}

/**
 * Template for editing profile options.
 */
function template_character_form()
{
	global $context, $scripturl, $txt, $settings;

	// The main header!
	echo '
		<form action="', $context['form_action'], '" method="post" accept-charset="UTF-8" name="creator" id="creator" enctype="multipart/form-data">
			<h2 class="category_header hdicon cat_img_profile">
				', $context['header_text'] ,'
			</h2>';

	// Have we some description?
	if ($context['page_desc'])
		echo '
			<p class="description">', $context['page_desc'], '</p>';

	echo '
			<div class="windowbg2">
				<div class="content">';

	// Start the big old loop 'of love.
	$lastItem = 'hr';

	foreach ($context['character_fields'] as $key => $field)
	{
		// We add a little hack to be sure we never get more than one hr in a row!
		if ($lastItem == 'hr' && $field['type'] == 'hr')
			continue;

		$lastItem = $field['type'];
		if ($field['type'] == 'hr')
		{
			echo '
					</dl>
					<hr class="clear" />
					<dl>';
		}
		elseif ($field['type'] == 'callback')
		{
			if (isset($field['callback_func']) && function_exists('template_character_' . $field['callback_func']))
			{
				$callback_func = 'template_character_' . $field['callback_func'];
				$callback_func();
			}
		}
		else
		{
			echo '
						<dt>
							<strong', !empty($field['is_error']) ? ' class="error"' : '', '>', $field['type'] !== 'label' ? '<label for="' . $key . '">' : '', $field['label'], $field['type'] !== 'label' ? '</label>' : '', '</strong>';

			// Does it have any subtext to show?
			if (!empty($field['subtext']))
				echo '
							<br />
							<span class="smalltext">', $field['subtext'], '</span>';

			echo '
						</dt>
						<dd>';

			// Want to put something in front of the box?
			if (!empty($field['preinput']))
				echo '
							', $field['preinput'];

			// What type of data are we showing?
			if ($field['type'] == 'label')
				echo '
							', $field['value'];

			// Maybe it's a text box - very likely!
			elseif (in_array($field['type'], array('int', 'float', 'text', 'password')))
				echo '
							<input type="', $field['type'] == 'password' ? 'password' : 'text', '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" ', $field['input_attr'], ' class="input_', $field['type'] == 'password' ? 'password' : 'text', '" />';

			// Maybe it's an html5 input
			elseif (in_array($field['type'], array('url', 'search', 'date', 'email', 'color')))
				echo '
							<input type="', $field['type'], '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" ', $field['input_attr'], ' class="input_', $field['type'] == 'password' ? 'password' : 'text', '" />';

			// You "checking" me out? ;)
			elseif ($field['type'] == 'check')
				echo '
							<input type="hidden" name="', $key, '" value="0" /><input type="checkbox" name="', $key, '" id="', $key, '" ', !empty($field['value']) ? ' checked="checked"' : '', ' value="1" class="input_check" ', $field['input_attr'], ' />';

			// Always fun - select boxes!
			elseif ($field['type'] == 'select')
			{
				echo '
							<select name="', $key, '" id="', $key, '">';

				if (isset($field['options']))
				{
					// Is this some code to generate the options?
					if (!is_array($field['options']))
						$field['options'] = eval($field['options']);

					// Assuming we now have some!
					if (is_array($field['options']))
						foreach ($field['options'] as $value => $name)
							echo '
								<option value="', $value, '" ', $value == $field['value'] ? 'selected="selected"' : '', '>', $name, '</option>';
				}

				echo '
							</select>';
			}

			// Something to end with?
			if (!empty($field['postinput']))
				echo '
							', $field['postinput'];

			echo '
						</dd>';
		}
	}

	if (!empty($context['character_fields']))
		echo '
					</dl>';
	
	// Are there any custom profile fields - if so print them!
	if (!empty($context['character_custom_fields']))
	{
		if ($lastItem !== 'hr')
			echo '
				<hr class="clear" />';

		echo '
				<dl>';

		foreach ($context['character_custom_fields'] as $field)
		{
			echo '
					<dt>
						<strong>', $field['name'], '</strong><br />
						<span class="smalltext">', $field['desc'], '</span>
					</dt>
					<dd>
						', $field['input_html'], '
					</dd>';
		}

		echo '
				</dl>';
	}
	
	echo '
				</div>
			</div>';

	// The button shouldn't say "Change profile" unless we're changing the profile...
		echo '
			<input type="submit" name="save" value="' , $context['submit_txt'] , '" class="right_submit" />';

	if (!empty($context['token_check']))
		echo '
			<input type="hidden" name="', $context[$context['token_check'] . '_token_var'], '" value="', $context[$context['token_check'] . '_token'], '" />';

	echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" id="u" name="u" value="', $context['user']['id'], '" />
			<input type="hidden" id="c" name="c" value="', $context['character']['id'], '" />
			<input type="hidden" name="sa" value="edit" />
		</form>';
		
		template_js_nameCheck();
}

/**
 * Returns if the username is valid or not, used during registration
 */
function template_action_checkname()
{
	global $context;

	echo '<', '?xml version="1.0" encoding="UTF-8"?', '>
<elk>
	<name valid="', $context['valid_name'] ? 1 : 0, '">', cleanXml($context['checked_name']), '</name>
</elk>';
}

/**
 * Callback function for entering a birthdate!
 */
function template_character_birthdate()
{
	global $txt, $context;

	// Just show the pretty box!
	echo '
							<dt>
								<strong>', $txt['dob'], '</strong><br />
								<span class="smalltext">', $txt['dob_year'], ' - ', $txt['dob_month'], ' - ', $txt['dob_day'], '</span>
							</dt>
							<dd>
								<input type="text" name="bday3" size="4" maxlength="4" value="', $context['character']['birth_date']['year'], '" class="input_text" /> -
								<input type="text" name="bday1" size="2" maxlength="2" value="', $context['character']['birth_date']['month'], '" class="input_text" /> -
								<input type="text" name="bday2" size="2" maxlength="2" value="', $context['character']['birth_date']['day'], '" class="input_text" />
							</dd>';
}

/**
 * Show the signature editing box.
 */
function template_character_signature_modify()
{
	global $txt, $context;

	echo '
							<dt id="current_signature"', !isset($context['character']['current_signature']) ? ' style="display:none"' : '', '>
								<strong>', $txt['current_signature'], ':</strong>
							</dt>
							<dd id="current_signature_display"', !isset($context['character']['current_signature']) ? ' style="display:none"' : '', '>
								', isset($context['character']['current_signature']) ? $context['character']['current_signature'] : '', '<hr />
							</dd>

							<dt id="preview_signature"', !isset($context['character']['signature_preview']) ? ' style="display:none"' : '', '>
								<strong>', $txt['signature_preview'], ':</strong>
							</dt>
							<dd id="preview_signature_display"', !isset($context['character']['signature_preview']) ? ' style="display:none"' : '', '>
								', isset($context['character']['signature_preview']) ? $context['character']['signature_preview'] : '', '<hr />
							</dd>

							<dt>
								<strong>', $txt['signature'], '</strong><br />
								<span class="smalltext">', $txt['sig_info'], '</span>
							</dt>
							<dd>
								<textarea class="editor" onkeyup="calcCharLeft();" id="signature" name="signature" rows="5" cols="50" style="min-width: 50%; max-width: 99%;">', $context['character']['signature'], '</textarea><br />';

	// If there is a limit at all!
	if (!empty($context['signature_limits']['max_length']))
		echo '
								<span class="smalltext">', sprintf($txt['max_sig_characters'], $context['signature_limits']['max_length']), ' <span id="signatureLeft">', $context['signature_limits']['max_length'], '</span></span><br />';

	if ($context['show_spellchecking'])
		echo '
								<input type="button" value="', $txt['spell_check'], '" onclick="spellCheck(\'creator\', \'signature\', false);"  tabindex="', $context['tabindex']++, '" class="right_submit" />';

	if (!empty($context['show_preview_button']))
		echo '
								<input type="submit" name="preview_signature" id="preview_button" value="', $txt['preview_signature'], '"  tabindex="', $context['tabindex']++, '" class="right_submit" />';

	if ($context['signature_warning'])
		echo '
								<span class="smalltext">', $context['signature_warning'], '</span>';

	// Some javascript used to count how many characters have been used so far in the signature.
	echo '
								<script><!-- // --><![CDATA[
									var maxLength = ', $context['signature_limits']['max_length'], ';

									$(document).ready(function() {
										calcCharLeft();
										$("#preview_button").click(function() {
											return ajax_getCharacterSignaturePreview(true);
										});
									});
								// ]]></script>
							</dd>';
}

/**
 * Interface to select an avatar in profile.
 */
function template_character_avatar_select()
{
	global $context, $txt, $modSettings;

	// Start with left side menu
	echo '
							<dt>
								<strong id="personal_picture">', $txt['personal_picture'], '</strong>
								<ul id="avatar_choices">
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_none" value="none"' . ($context['character']['avatar']['choice'] == 'none' ? ' checked="checked"' : '') . ' class="input_radio" />
										<label for="avatar_choice_none"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['no_avatar'] . '
										</label>
									</li>', !empty($context['character']['avatar']['allow_server_stored']) ? '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_server_stored" value="server_stored"' . ($context['character']['avatar']['choice'] == 'server_stored' ? ' checked="checked"' : '') . ' class="input_radio" />
										<label for="avatar_choice_server_stored"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['choose_avatar_gallery'] . '
										</label>
									</li>' : '', !empty($context['character']['avatar']['allow_external']) ? '
									<li>
										<input type="radio" onclick="swap_avatar();" name="avatar_choice" id="avatar_choice_external" value="external"' . ($context['character']['avatar']['choice'] == 'external' ? ' checked="checked"' : '') . ' class="input_radio" />
										<label for="avatar_choice_external"' . (isset($context['modify_error']['bad_avatar']) ? ' class="error"' : '') . '>
											' . $txt['my_own_pic'] . '
										</label>
									</li>' : '', '
								</ul>
							</dt>
							<dd>';

	// If users are allowed to choose avatars stored on the server show the selection boxes to choose them.
	if (!empty($context['character']['avatar']['allow_server_stored']))
	{
		echo '
								<div id="avatar_server_stored">
									<div>
										<select name="cat" id="cat" size="10" onchange="changeSel(\'\');">';

		// This lists all the file categories.
		foreach ($context['avatars'] as $avatar)
			echo '
											<option value="', $avatar['filename'] . ($avatar['is_dir'] ? '/' : ''), '"', ($avatar['checked'] ? ' selected="selected"' : ''), '>', $avatar['name'], '</option>';
		echo '
										</select>
									</div>
									<div>
										<select name="file" id="file" size="10" style="display: none;" onchange="showAvatar()" disabled="disabled">
											<option> </option>
										</select>
									</div>
									<div>
										<img id="avatar" src="', $modSettings['avatar_url'] . '/blank.png', '" alt="" />
									</div>
								</div>';
	}

	// If the user can link to an off server avatar, show them a box to input the address.
	if (!empty($context['character']['avatar']['allow_external']))
	{
		echo '
								<div id="avatar_external">
									<div class="smalltext"><label for="userpicpersonal">', $txt['avatar_by_url'], '</label></div>
									<input type="text" id="userpicpersonal" name="userpicpersonal" value="', $context['character']['avatar']['external'], '" onchange="previewExternalAvatar(this.value);" class="input_text" />
									<br /><br />
									<img id="external" src="', !empty($context['character']['avatar']['allow_external']) && $context['character']['avatar']['choice'] == 'external' ? $context['character']['avatar']['external'] : $modSettings['avatar_url'] . '/blank.png', '" alt="" ', !empty($modSettings['avatar_max_height']) ? 'height="' . $modSettings['avatar_max_height'] . '" ' : '', !empty($modSettings['avatar_max_width']) ? 'width="' . $modSettings['avatar_max_width'] . '"' : '', '/>
								</div>';
	}


	echo '
								<script><!-- // --><![CDATA[
									var files = ["' . implode('", "', $context['avatar_list']) . '"],
										cat = document.getElementById("cat"),
										file = document.getElementById("file"),
										selavatar = "' . $context['avatar_selected'] . '",
										avatardir = "' . $modSettings['avatar_url'] . '/",
										refuse_too_large = ', !empty($modSettings['avatar_action_too_large']) && $modSettings['avatar_action_too_large'] == 'option_refuse' ? 'true' : 'false', ',
										maxHeight = ', !empty($modSettings['avatar_max_height']) ? $modSettings['avatar_max_height'] : 0, ',
										maxWidth = ', !empty($modSettings['avatar_max_width']) ? $modSettings['avatar_max_width'] : 0, ';

									// Display the right avatar box based on what they are using
									init_avatars();
								// ]]></script>
							</dd>';
}

/**
 * Profile Summary Block
 *
 * Show avatar, title, group info, number of posts, karma, likes
 * Has links to show posts, drafts and attachments
 */
function template_profile_block_characters()
{
	global $txt, $context, $modSettings, $scripturl;

	echo '
			
				<h2 class="category_header hdicon cat_img_profile">
					', 'TXT Characters', '
				</h2>
			
			<div class="profileblock">
			
					';
	if (!empty($context['member']['characters']))
	{
		foreach($context['member']['characters'] as $charid => $character) {
			echo '
				<a class="content floatleft centertext" href="' , $scripturl , '?action=character;c=' , $charid , '">' , $character['name'] , '<br />
					' , !empty($character['title']) ?  $character['title'] : '&nbsp;' , '
				</a>';
		}
	}
	else
		echo '
				', ($context['user']['is_owner']) ? '<p><a href="' . $scripturl . '?action=character;sa=create">Create a character</a></p>' : '<p>TXT This member does not yet have any characters.</p>';

	// close this block up
	echo '
				<div class="clear"></div>
			</div>';
}