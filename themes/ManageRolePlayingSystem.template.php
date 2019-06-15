<?php

/**
 * Role Playing System Admin templates
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright: ElkArte Forum contributors
 * license:   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 */

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

function template_recount_character_posts_below()
{
	global $txt, $scripturl, $context;

		echo '
		<h2 class="category_header">', $txt['rps_maintain_recount_chars'], '</h2>
		<div class="content">
			<form action="', $scripturl , '?action=admin;area=rps;sa=recountcharsposts', '" method="post" accept-charset="UTF-8">
				<p>', $txt['rps_maintain_recount_chars_info'], '</p>
				<div class="submitbutton">
					<input type="submit" value="', $txt['maintain_run_now'], '" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="hidden" name="', $context['admin-maint_token_var'], '" value="', $context['admin-maint_token'], '" />
				</div>
			</form>
		</div>';	
}