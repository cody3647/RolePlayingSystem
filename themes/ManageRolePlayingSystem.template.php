<?php

/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.1 beta 4
 *
 */

/**
 * Template for showing custom profile fields.
 */
function template_show_character_fields()
{

	// Custom fields.
	template_show_list('character_fields');
}

/**
 * Template to edit a profile field
 */
function template_edit_character_field()
{
	global $context, $txt, $scripturl;

	// any errors messages to show?
	if (!empty($context['custom_option__error']))
	{
		echo '
	<div class="errorbox">
		', $context['custom_option__error'], '
	</div>';
	}

	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=rps;sa=fieldedit;fid=', $context['fid'], ';', $context['session_var'], '=', $context['session_id'], '" method="post" accept-charset="UTF-8">
			<h2 class="category_header">', $context['page_title'], '</h2>
			<div class="content">
				<fieldset>
					<legend>', $txt['custom_edit_general'], '</legend>
					<dl class="settings">
						<dt>
							<label for="field_name">', $txt['custom_edit_name'], ':</label>
						</dt>
						<dd>
							<input type="text" name="field_name" id="field_name" value="', $context['field']['name'], '" size="20" maxlength="40" class="input_text" />
						</dd>
						<dt>
							<label for="field_desc">', $txt['custom_edit_desc'], ':</label>
						</dt>
						<dd>
							<textarea name="field_desc" id="field_desc" rows="3" cols="40">', $context['field']['desc'], '</textarea>
						</dd>
						<dt>
							<label for="reg">', $txt['custom_edit_registration'], ':</label>
						</dt>
						<dd>
							<select name="reg" id="reg">
								<option value="0"', $context['field']['reg'] == 0 ? ' selected="selected"' : '', '>', $txt['custom_edit_registration_disable'], '</option>
								<option value="1"', $context['field']['reg'] == 1 ? ' selected="selected"' : '', '>', $txt['custom_edit_registration_allow'], '</option>
								<option value="2"', $context['field']['reg'] == 2 ? ' selected="selected"' : '', '>', $txt['custom_edit_registration_require'], '</option>
							</select>
						</dd>
						<dt>
							<label for="display">', $txt['custom_edit_display'], ':</label>
						</dt>
						<dd>
							<input type="checkbox" name="display" id="display"', $context['field']['display'] ? ' checked="checked"' : '', ' />
						</dd>
						<dt>
							<label for="memberlist">', $txt['custom_edit_memberlist'], ':</label>
						</dt>
						<dd>
							<input type="checkbox" name="memberlist" id="memberlist"', $context['field']['memberlist'] ? ' checked="checked"' : '', ' />
						</dd>
						<dt>
							<label for="placement">', $txt['custom_edit_placement'], ':</label>
						</dt>
						<dd>
							<select name="placement" id="placement">
								<option value="0"', $context['field']['placement'] == '0' ? ' selected="selected"' : '', '>', $txt['custom_edit_placement_standard'], '</option>
								<option value="1"', $context['field']['placement'] == '1' ? ' selected="selected"' : '', '>', $txt['custom_edit_placement_withicons'], '</option>
								<option value="2"', $context['field']['placement'] == '2' ? ' selected="selected"' : '', '>', $txt['custom_edit_placement_abovesignature'], '</option>
								<option value="3"', $context['field']['placement'] == '3' ? ' selected="selected"' : '', '>', $txt['custom_edit_placement_aboveicons'], '</option>
							</select>
						</dd>
						<dt>
							<a id="field_show_enclosed" href="', $scripturl, '?action=quickhelp;help=field_show_enclosed" onclick="return reqOverlayDiv(this.href);" class="helpicon i-help"><s>', $txt['help'], '</s></a>
							<label for="enclose">', $txt['custom_edit_enclose'], ':</label><br>
							<span class="smalltext">', $txt['custom_edit_enclose_desc'], '</span>
						</dt>
						<dd>
							<textarea name="enclose" id="enclose" rows="10" cols="50">' . (isset($context['field']['enclose']) ? $context['field']['enclose'] : '') . '</textarea>
						</dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['custom_edit_input'], '</legend>
					<dl class="settings">
						<dt>
							<label for="field_type">', $txt['custom_edit_picktype'], ':</label>
						</dt>
						<dd>
							<select name="field_type" id="field_type" onchange="updateInputBoxes();">
								<option value="text"', $context['field']['type'] == 'text' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_text'], '</option>
								<option value="email"', $context['field']['type'] == 'email' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_email'], '</option>
								<option value="url"', $context['field']['type'] == 'url' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_url'], '</option>
								<option value="date"', $context['field']['type'] == 'date' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_date'], '</option>
								<option value="color"', $context['field']['type'] == 'color' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_color'], '</option>
								<option value="textarea"', $context['field']['type'] == 'textarea' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_textarea'], '</option>
								<option value="select"', $context['field']['type'] == 'select' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_select'], '</option>
								<option value="radio"', $context['field']['type'] == 'radio' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_radio'], '</option>
								<option value="check"', $context['field']['type'] == 'check' ? ' selected="selected"' : '', '>', $txt['custom_profile_type_check'], '</option>
							</select>
						</dd>
						<dt id="max_length_dt">
							<label for="max_length_dd">', $txt['custom_edit_max_length'], ':</label><br />
							<span class="smalltext">', $txt['custom_edit_max_length_desc'], '</span>
						</dt>
						<dd>
							<input type="text" name="max_length" id="max_length_dd" value="', $context['field']['max_length'], '" size="7" maxlength="6" class="input_text" />
						</dd>
						<dt id="dimension_dt">
							<label for="dimension_dd">', $txt['custom_edit_dimension'], ':</label>
						</dt>
						<dd id="dimension_dd">
							<strong>', $txt['custom_edit_dimension_row'], ':</strong> <input type="text" name="rows" value="', $context['field']['rows'], '" size="5" maxlength="3" class="input_text" />
							<strong>', $txt['custom_edit_dimension_col'], ':</strong> <input type="text" name="cols" value="', $context['field']['cols'], '" size="5" maxlength="3" class="input_text" />
						</dd>
						<dt id="bbc_dt">
							<label for="bbc_dd">', $txt['custom_edit_bbc'], '</label>
						</dt>
						<dd >
							<input type="checkbox" name="bbc" id="bbc_dd"', $context['field']['bbc'] ? ' checked="checked"' : '', ' />
						</dd>
						<dt id="defaultval_dt">
							<label for="default_dd">', $txt['custom_edit_default_value'], '</label>
						</dt>
						<dd id="defaultval_dd">
							<input type="text" name="default_value" size="40" maxlength="255" value="' , $context['field']['default_value'], '" class="input_text">
						</dd>
						<dt id="options_dt">
							<a href="', $scripturl, '?action=quickhelp;help=customoptions" onclick="return reqOverlayDiv(this.href);" class="helpicon i-help"><s>', $txt['help'], '</s></a>
							<label for="options_dd">', $txt['custom_edit_options'], ':</label><br>
							<span class="smalltext">', $txt['custom_edit_options_desc'], '</span>
						</dt>
						<dd id="options_dd">
							<div>';

	if (!empty($context['field']['show_nodefault']))
	{
		echo '
								<input type="radio" name="default_select" value="no_default"', $context['field']['default_select'] == 'no_default' ? ' checked="checked"' : '', ' class="input_radio" /><label>' . $txt['custom_edit_options_no_default'] . '</label><br>';
	}

	foreach ($context['field']['options'] as $k => $option)
		echo '
							', $k == 0 ? '' : '<br>', '<input type="radio" name="default_select" value="', $k, '"', $context['field']['default_select'] == $option ? ' checked="checked"' : '', ' /><input type="text" name="select_option[', $k, ']" value="', $option, '" class="input_text" />';

	echo '
							<span id="addopt"></span>
							[<a href="" onclick="addOption(); return false;">', $txt['custom_edit_options_more'], '</a>]
							</div>
						</dd>
						<dt id="default_dt">
							<label for="default_dd">', $txt['custom_edit_default'], ':</label>
						</dt>
						<dd>
							<input type="checkbox" name="default_check" id="default_dd"', $context['field']['default_check'] ? ' checked="checked"' : '', ' />
						</dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['custom_edit_advanced'], '</legend>
					<dl class="settings">
						<dt id="mask_dt">
							<a id="custom_mask" href="', $scripturl, '?action=quickhelp;help=custom_mask" onclick="return reqOverlayDiv(this.href);" class="helpicon i-help"><s>', $txt['help'], '</s></a>
							<label for="mask">', $txt['custom_edit_mask'], ':</label><br>
							<span class="smalltext">', $txt['custom_edit_mask_desc'], '</span>
						</dt>
						<dd>
							<select name="mask" id="mask" onchange="updateInputBoxes();">
								<option value="nohtml"', $context['field']['mask'] == 'nohtml' ? ' selected="selected"' : '', '>', $txt['custom_edit_mask_nohtml'], '</option>
								<option value="email"', $context['field']['mask'] == 'email' ? ' selected="selected"' : '', '>', $txt['custom_edit_mask_email'], '</option>
								<option value="number"', $context['field']['mask'] == 'number' ? ' selected="selected"' : '', '>', $txt['custom_edit_mask_number'], '</option>
								<option value="regex"', strpos($context['field']['mask'], 'regex') === 0 ? ' selected="selected"' : '', '>', $txt['custom_edit_mask_regex'], '</option>
							</select>
							<br />
							<span id="regex_div">
								<input type="text" name="regex" value="', $context['field']['regex'], '" size="30" class="input_text" />
							</span>
						</dd>
						<dt>
							<label for="private">', $txt['custom_edit_privacy'], ':</label>
							<span class="smalltext">', $txt['custom_edit_privacy_desc'], '</span>
						</dt>
						<dd>
							<select name="private" id="private" onchange="updateInputBoxes();">
								<option value="0"', $context['field']['private'] == 0 ? ' selected="selected"' : '', '>', $txt['custom_edit_privacy_all'], '</option>
								<option value="1"', $context['field']['private'] == 1 ? ' selected="selected"' : '', '>', $txt['custom_edit_privacy_see'], '</option>
								<option value="2"', $context['field']['private'] == 2 ? ' selected="selected"' : '', '>', $txt['custom_edit_privacy_owner'], '</option>
								<option value="3"', $context['field']['private'] == 3 ? ' selected="selected"' : '', '>', $txt['custom_edit_privacy_none'], '</option>
							</select>
						</dd>
						<dt id="can_search_dt">
							<label for="can_search_dd">', $txt['custom_edit_can_search'], ':</label><br />
							<span class="smalltext">', $txt['custom_edit_can_search_desc'], '</span>
						</dt>
						<dd>
							<input type="checkbox" name="can_search" id="can_search_dd"', $context['field']['can_search'] ? ' checked="checked"' : '', ' />
						</dd>
						<dt>
							<label for="can_search_check">', $txt['custom_edit_active'], ':</label><br />
							<span class="smalltext">', $txt['custom_edit_active_desc'], '</span>
						</dt>
						<dd>
							<input type="checkbox" name="active" id="can_search_check"', $context['field']['active'] ? ' checked="checked"' : '', ' />
						</dd>
					</dl>
				</fieldset>
				<div class="submitbutton">
					<input type="submit" name="save" value="', $txt['save'], '" />';

	if ($context['fid'])
		echo '
					<input type="submit" name="delete" value="', $txt['delete'], '" onclick="return confirm(\'', $txt['custom_edit_delete_sure'], '\');" />';

	echo '
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="hidden" name="', $context['admin-rps-ecp_token_var'], '" value="', $context['admin-rps-ecp_token'], '" />
				</div>
			</div>
		</form>
	</div>';

	// Get the javascript bits right!
	echo '
	<script>
		updateInputBoxes();
	</script>';
}

/**
 * Editing or adding holidays.
 */
function template_edit_event()
{
	global $context, $scripturl, $txt;

	// Show a form for all the holiday information.
	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=rps;sa=editevent" method="post" accept-charset="UTF-8">
			<h2 class="category_header">', $context['page_title'], '</h2>
				<div class="content">
				<dl class="settings">
					<dt class="small_caption">
						<label for="title">', $txt['holidays_title_label'], ':</label>
					</dt>
					<dd class="small_caption">
						<input type="text" id="title" name="title" value="', $context['event']['title'], '" size="55" maxlength="60" />
					</dd>
				</dl>
				<hr />
				<dl class="settings">
					<dt class="small_caption">
						<label for="year">', $txt['calendar_year'], '</label>
					</dt>
					<dd class="small_caption">
						<select name="year" id="year" onchange="generateDays();">
							<option value="0"', $context['event']['year'] == 0 ? ' selected="selected"' : '', '>', $txt['every_year'], '</option>';

	// Show a list of all the years we allow...
	for ($year = $context['cal_minyear']; $year <= $context['cal_maxyear']; $year++)
		echo '
							<option value="', $year, '"', $year == $context['event']['year'] ? ' selected="selected"' : '', '>', $year, '</option>';

	echo '
						</select>
					</dd>
					<dt class="small_caption">
						<label for="month">', $txt['calendar_month'], '</label>
					</dt>
					<dd class="small_caption">
						<select name="month" id="month" onchange="generateDays();">';

	// There are 12 months per year - ensure that they all get listed.
	for ($month = 1; $month <= 12; $month++)
		echo '
							<option value="', $month, '"', $month == $context['event']['month'] ? ' selected="selected"' : '', '>', $txt['months'][$month], '</option>';

	echo '
						</select>
					</dd>
					<dt class="small_caption">
						<label><input id="exact" type="radio" name="date_type" value="exact" ', empty($context['event']['day']) ? '' : 'checked', '/> ',$txt['exact_day'],'</label>
					</dt>
					<dd class="small_caption">
						<select name="day" id="day" onchange="generateDays();" ', empty($context['event']['day']) ? 'disabled' : '', '>
							<option value="0"', 0 == $context['event']['day'] ? ' selected="selected"' : '', '></option>';

	// This prints out all the days in the current month - this changes dynamically as we switch months.
	for ($day = 1; $day <= $context['event']['last_day']; $day++)
		echo '
							<option value="', $day, '"', $day == $context['event']['day'] ? ' selected="selected"' : '', '>', $day, '</option>';

	echo '				</select>
					</dd>
					<dt class="small_caption">
						<label><input id="relative" type="radio" name="date_type" value="relative" ', empty($context['event']['ordinal']) ? '' : 'checked', '/> ',$txt['relative_day'],'</label>
					</dt>
					<dd class="small_caption">
						<select size="8" name="ordinal" id="ordinal" ', empty($context['event']['ordinal']) ? 'disabled' : '', '>
							<option value=""', '' == $context['event']['ordinal'] ? ' selected="selected"' : '', '></option>
							<option value="first"', 'first' == $context['event']['ordinal'] ? ' selected="selected"' : '', '>', $txt['first'] ,'</option>
							<option value="second"', 'second' == $context['event']['ordinal'] ? ' selected="selected"' : '', '>', $txt['second'] ,'</option>
							<option value="third"', 'third' == $context['event']['ordinal'] ? ' selected="selected"' : '', '>', $txt['third'] ,'</option>
							<option value="fourth"', 'fourth' == $context['event']['ordinal'] ? ' selected="selected"' : '', '>', $txt['fourth'] ,'</option>
							<option value="last"', 'last' == $context['event']['ordinal'] ? ' selected="selected"' : '', '>', $txt['last'] ,'</option>
						</select>
						<select size="8" name="dayname" id="dayname" ', empty($context['event']['dayname']) ? 'disabled' : '', '>
							<option value=""', '' == $context['event']['dayname'] ? ' selected="selected"' : '', '></option>
							<option value="sunday"', 'sunday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['sunday'] ,'</option>
							<option value="monday"', 'monday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['monday'] ,'</option>
							<option value="tuesday"', 'tuesday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['tuesday'] ,'</option>
							<option value="wednesday"', 'wednesday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['wednesday'] ,'</option>
							<option value="thursday"', 'thursday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['thursday'] ,'</option>
							<option value="friday"', 'friday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['friday'] ,'</option>
							<option value="saturday"', 'saturday' == $context['event']['dayname'] ? ' selected="selected"' : '', '>', $txt['saturday'] ,'</option>
						</select>
					</dd>';
	addInlineJavascript('
			$(document).ready(function() {
				$("#exact").click(function() {
					$("#ordinal, #dayname").attr("disabled", "disabled").addClass("disabled");
					$("#day").removeAttr("disabled").removeClass("disabled");
				});
				$("#relative").click(function() {
					$("#day").attr("disabled", "disabled").addClass("disabled");
					$("#ordinal, #dayname").removeAttr("disabled").removeClass("disabled");
				});
			});
			', true);
			
	echo '
				</dl>
				<hr />
				<div class="submitbutton">';

	if ($context['is_new'])
		echo '
						<input type="submit" value="', $txt['holidays_button_add'], '" />';
	else
		echo '
						<input type="submit" name="edit" value="', $txt['holidays_button_edit'], '" />
						<input type="submit" name="delete" value="', $txt['holidays_button_remove'], '" />
						<input type="hidden" name="event" value="', $context['event']['id'], '" />';

	echo '
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>
	</div>';
}

/**
 * Editing or adding holidays.
 */
function template_edit_phase()
{
	global $context, $scripturl, $txt;

	// Show a form for all the holiday information.
	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=rps;sa=editphase" method="post" accept-charset="UTF-8">
			<h2 class="category_header">', $context['page_title'], '</h2>
				<div class="content">
				<dl class="settings">
					<dt class="small_caption">
						<label for="title">'. $txt['rps_phase_label_phase'] .'</label>
					</dt>
					<dd class="small_caption">
						<input type="text" id="title" name="phase" value="', $context['moonphase']['phase'], '" size="55" maxlength="60" />
					</dd>
				</dl>
				<hr />
				<dl class="settings">
					<dt class="small_caption">
						<label for="year">', $txt['calendar_year'], '</label>
					</dt>
					<dd class="small_caption">
						<select name="year" id="year" onchange="generateDays();">
							<option value="0"', $context['moonphase']['year'] == 0 ? ' selected="selected"' : '', '>', $txt['every_year'], '</option>';

	// Show a list of all the years we allow...
	for ($year = $context['cal_minyear']; $year <= $context['cal_maxyear']; $year++)
		echo '
							<option value="', $year, '"', $year == $context['moonphase']['year'] ? ' selected="selected"' : '', '>', $year, '</option>';

	echo '
						</select>
					</dd>
					<dt class="small_caption">
						<label for="month">', $txt['calendar_month'], '</label>
					</dt>
					<dd class="small_caption">
						<select name="month" id="month" onchange="generateDays();">';

	// There are 12 months per year - ensure that they all get listed.
	for ($month = 1; $month <= 12; $month++)
		echo '
							<option value="', $month, '"', $month == $context['moonphase']['month'] ? ' selected="selected"' : '', '>', $txt['months'][$month], '</option>';

	echo '
						</select>
					</dd>
					<dd class="small_caption">
						<select name="day" id="day" onchange="generateDays();" ', empty($context['moonphase']['day']) ? 'disabled' : '', '>
							<option value="0"', 0 == $context['moonphase']['day'] ? ' selected="selected"' : '', '></option>';

	// This prints out all the days in the current month - this changes dynamically as we switch months.
	for ($day = 1; $day <= $context['moonphase']['last_day']; $day++)
		echo '
							<option value="', $day, '"', $day == $context['moonphase']['day'] ? ' selected="selected"' : '', '>', $day, '</option>';

	echo '				</select>
					</dd>
					<dt class="small_caption">
						<label for="time">' . $txt['rps_phase_label_time'] . '</label>
					</dt>
					<dd class="small_caption">
						<select name="hour">
							<option value=" "></option>';
	for ($hour = 0; $hour <= 23; $hour++)
		echo '
							<option value="', $hour, '"', $hour == $context['moonphase']['hour'] ? ' selected="selected"' : '', '>', $hour, '</option>';
	echo '
						</select>
						<select name="minute">
							<option value=" "></option>';
	for ($minute = 0; $minute <= 59; $minute++)
		echo '
							<option value="', $minute, '"', $minute == $context['moonphase']['minute'] ? ' selected="selected"' : '', '>', $minute, '</option>';
	echo '
						</select>';
	echo '
				</dl>
				<hr />
				<div class="submitbutton">';

	if ($context['is_new'])
		echo '
						<input type="submit" value="', $txt['holidays_button_add'], '" />';
	else
		echo '
						<input type="submit" name="edit" value="', $txt['holidays_button_edit'], '" />
						<input type="submit" name="delete" value="', $txt['holidays_button_remove'], '" />
						<input type="hidden" name="moonphase" value="', $context['moonphase']['id'], '" />';

	echo '
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>
	</div>';
}

/**
 * Editing or adding holidays.
 */
function template_download_events()
{
	global $context, $scripturl, $txt, $modSettings;

	// Show a form for all the holiday information.
	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=rps;sa=download" method="post" accept-charset="UTF-8">
			<h2 class="category_header">', $context['page_title'], '</h2>
				<div class="content">';
	foreach($context['download'] as $category => $events)
	{
		echo '
					<dl class="settings">
						<dt class="small_caption">
							<label for="holidays[]">', $txt['rps_'.$category],':</label>
							<br />
							<span class="smalltext">', $txt['rps_'.$category.'_desc'],'</span>
						</dt>';
		foreach($events as $event)
			echo'
							<dd class="small_caption">
								<label><input type="checkbox" name="',$category,'[]" value="', $event ,'" ',(in_array($event, $context['rps_download_events']) ? 'checked ' : '') ,'/>', $event ,'</label>
							</dd>';
		echo'				
					</dl>
					<hr />';
	}	
	echo '
					<dl class="settings">
						<dt class="small_caption">
							<label for="year">', $txt['calendar_year'], '</label>
						</dt>
						<dd class="small_caption">
							<select name="year" id="year">';

		// Show a list of all the years we allow...
	for ($year = $context['cal_minyear']; $year <= $context['cal_maxyear']; $year++)
		echo '
								<option value="', $year, '">', $year, '</option>';

	echo '
							</select>
						</dd>
					</dl>';
						
	if(is_array($context['rps_timezone_selection']))
	{					
		echo '		<dl class="settings">
						<dt class="small_caption">
							<label for="year">', $txt['rps_timezone'], '</label>
						</dt>
						<dd class="small_caption">
							<select name="timezone" id="timezone">';

		// Show a list of all the years we allow...
		foreach ($context['rps_timezone_selection'] as $timezone)
			echo '
								<option value="', $timezone, '"' , ($timezone == $modSettings['rps_timezone'] ? ' selected' : '') , '>', $timezone, '</option>';

		echo '
							</select>
						</dd>
					</dl>';
	}
	echo '
				<hr />
				<div class="submitbutton">';

	echo '
						<input type="submit" name="download" value="', $txt['rps_download'], '" />';

	echo '
						<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</div>
			</div>
		</form>
	</div>';
}