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
 * @version 1.1 Release Candidate 1
 *
 */

/**
 * Start the calendar
 */
function template_Calendar_init()
{
	loadTemplate('GenericHelpers');
}

/**
 * The main calendar - January, for example.
 */
function template_show_calendar()
{
	global $context, $txt, $scripturl;

	echo '
		<div id="calendar">
			<div id="month_grid">
				', template_show_month_grid('prev'), '
				', template_show_month_grid('current'), '
				', template_show_month_grid('next'), '
			</div>
			<div id="main_grid">
				', $context['view_week'] ? template_show_week_grid('main') : template_show_month_grid('main');

	// Show some controls to allow easy calendar navigation.
	echo '
				<form id="calendar_navigation" action="', $scripturl, '?action=gamecalendar" method="post" accept-charset="UTF-8">';

	echo '
					<select name="month">';

	// Show a select box with all the months.
	foreach ($txt['months'] as $number => $month)
		echo '
						<option value="', $number, '"', $number == $context['current_month'] ? ' selected="selected"' : '', '>', $month, '</option>';

	echo '
					</select>
					<select name="year">';

	// Show a link for every year.....
	for ($year = $context['cal_minyear']; $year <= $context['cal_maxyear']; $year++)
		echo '
						<option value="', $year, '"', $year == $context['current_year'] ? ' selected="selected"' : '', '>', $year, '</option>';

	echo '
					</select>
					<input type="submit" value="', $txt['view'], '" />
				</form>
			</div>
		</div>';
}

/**
 * Display a monthly calendar grid.
 *
 * @param string $grid_name
 */
function template_show_month_grid($grid_name)
{
	global $context, $txt, $scripturl, $modSettings;

	if (!isset($context['calendar_grid_' . $grid_name]))
		return false;

	$calendar_data = &$context['calendar_grid_' . $grid_name];

	if (empty($calendar_data['disable_title']))
	{
		echo '
				<h2 class="category_header">';

		if (empty($calendar_data['previous_calendar']['disabled']) && $calendar_data['show_next_prev'])
			echo '
					<a href="', $calendar_data['previous_calendar']['href'], '" class="previous_month">
						<i class="icon icon-lg i-chevron-circle-left"></i>
					</a>';

		if (empty($calendar_data['next_calendar']['disabled']) && $calendar_data['show_next_prev'])
			echo '
					<a href="', $calendar_data['next_calendar']['href'], '" class="next_month">
						<i class="icon icon-lg i-chevron-circle-right"></i>
					</a>';

		if ($calendar_data['show_next_prev'])
			echo '
					', $txt['months_titles'][$calendar_data['current_month']], ' ', $calendar_data['current_year'];
		else
			echo '
					<a href="', $scripturl, '?action=gamecalendar;year=', $calendar_data['current_year'], ';month=', $calendar_data['current_month'], '">
						<i class="icon icon-small i-calendar"></i> ', $txt['months_titles'][$calendar_data['current_month']], ' ', $calendar_data['current_year'], '
					</a>';

		echo '
				</h2>';
	}

	// Show the sidebar months
	echo '
				<table class="calendar_table">';

	// Show each day of the week.
	if (empty($calendar_data['disable_day_titles']))
	{
		echo '
					<tr class="table_head">';

		if (!empty($calendar_data['show_week_links']))
			echo '
						<th>&nbsp;</th>';

		foreach ($calendar_data['week_days'] as $day)
			echo '
						<th scope="col" class="days">', !empty($calendar_data['short_day_titles']) ? (Util::substr($txt['days'][$day], 0, 1)) : $txt['days'][$day], '</th>';

		echo '
					</tr>';
	}

	// Each week in weeks contains the following:
	// days (a list of days), number (week # in the year.)
	foreach ($calendar_data['weeks'] as $week)
	{
		echo '
					<tr>';

		if (!empty($calendar_data['show_week_links']))
			echo '
						<td class="weeks">
							<a href="', $scripturl, '?action=gamecalendar;viewweek;year=', $calendar_data['current_year'], ';month=', $calendar_data['current_month'], ';day=', $week['days'][0]['day'], '">
								<i class="icon i-eye-plus"></i>
							</a>
						</td>';

		// Every day has the following:
		// day (# in month), is_today (is this day *today*?), is_first_day (first day of the week?),
		// holidays, events, birthdays. (last three are lists.)
		foreach ($week['days'] as $day)
		{

			// If this is today, make it a different color and show a border.
			echo '
						<td class="', $day['is_today'] ? 'calendar_today' : '', ' days">';

			// Skip it if it should be blank - it's not a day if it has no number.
			if (!empty($day['day']))
			{
				echo '
							', $day['day'];
				// Are there any holidays?
				if (!empty($day['phases']))
					echo '
							<span class="icon icon-small i-', str_replace(' ', '', $day['phases']), '" title="', $day['phases'], '"></span>';

				// Are there any holidays?
				if (!empty($day['holidays']))
					echo '
							<div class="holiday lefttext">', $txt['calendar_prompt'], ' ', implode(', ', $day['holidays']), '</div>';

				// Show any birthdays...
				if (!empty($day['birthdays']))
				{
					echo '
							<div class="lefttext">
								<span class="birthday">', $txt['birthdays'], '</span>';

					// Each of the birthdays has:
					// id, name (person), age (if they have one set?), and is_last. (last in list?)
					$use_js_hide = empty($context['show_all_birthdays']) && count($day['birthdays']) > 10;
					$count = 0;

					foreach ($day['birthdays'] as $character)
					{
						echo '
									<a href="', $scripturl, '?action=character;c=', $character['id'], '">', $character['name'], isset($character['age']) ? ' (' . $character['age'] . ')' : '', '</a>', $character['is_last'] || ($count == 10 && $use_js_hide) ? '' : ', ';

						// Stop at ten?
						if ($count == 10 && $use_js_hide)
							echo '
									<span class="hidelink" id="bdhidelink_', $day['day'], '">...<br />
										<a href="', $scripturl, '?action=gamecalendar;month=', $calendar_data['current_month'], ';year=', $calendar_data['current_year'], ';showbd" onclick="document.getElementById(\'bdhide_', $day['day'], '\').style.display = \'block\'; document.getElementById(\'bdhidelink_', $day['day'], '\').style.display = \'none\'; return false;">(', sprintf($txt['calendar_click_all'], count($day['birthdays'])), ')</a>
									</span>
									<span id="bdhide_', $day['day'], '" class="hide">, ';

						$count++;
					}

					if ($use_js_hide)
						echo '
								</span>';

					echo '
							</div>';
				}

				// Any special posted events?
				if (!empty($day['events']))
				{
					echo '
							<div class="lefttext">
								<span class="event">TXT Topics</span><br />';

					// The events are made up of:
					// title, href, is_last, can_edit (are they allowed to?), and modify_href.
					foreach ($day['events'] as $event)
					{

						echo '
								', $event['link'], $event['is_last'] ? '' : '<br />';
					}

					echo '
							</div>';
				}
			}

			echo '
						</td>';
		}

		echo '
					</tr>';
	}

	echo '
				</table>';
}

/**
 * Or show a weekly one?
 *
 * @param string $grid_name
 */
function template_show_week_grid($grid_name)
{
	global $context, $txt, $scripturl, $modSettings;

	if (!isset($context['calendar_grid_' . $grid_name]))
		return false;

	$calendar_data = &$context['calendar_grid_' . $grid_name];
	$done_title = false;

	// Loop through each month (At least one) and print out each day.
	foreach ($calendar_data['months'] as $month_data)
	{
		echo '
				<h2 class="category_header">';

		if (empty($calendar_data['previous_calendar']['disabled']) && $calendar_data['show_next_prev'] && empty($done_title))
			echo '
					<span class="previous_month">
						<a href="', $calendar_data['previous_week']['href'], '">
							<i class="icon icon-lg i-chevron-circle-left"></i>
						</a>
					</span>';

		if (empty($calendar_data['next_calendar']['disabled']) && $calendar_data['show_next_prev'] && empty($done_title))
			echo '
					<span class="next_month">
						<a href="', $calendar_data['next_week']['href'], '">
							<i class="icon icon-lg i-chevron-circle-right"></i>
						</a>
					</span>';

		echo '
					<a href="', $scripturl, '?action=gamecalendar;month=', $month_data['current_month'], ';year=', $month_data['current_year'], '">', $txt['months_titles'][$month_data['current_month']], ' ', $month_data['current_year'], '</a>', empty($done_title) && !empty($calendar_data['week_number']) ? (' - ' . $txt['calendar_week'] . ' ' . $calendar_data['week_number']) : '', '
				</h2>';

		$done_title = true;

		echo '
				<ul class="weeklist">';

		foreach ($month_data['days'] as $day)
		{
			echo '
					<li>
						<h4>';

			echo '
							', $txt['days'][$day['day_of_week']], ' - ', $day['day'];
			if (!empty($day['phases']))
				echo '
							<span class="icon icon-lg i-', str_replace(' ', '', $day['phases']), '" title="', $day['phases'], '"></span>';

			echo '
						</h4>
						<div class="', $day['is_today'] ? 'calendar_today' : '', ' weekdays">';

			// Are there any holidays?
			if (!empty($day['holidays']))
				echo '
							<div class="smalltext holiday">', $txt['calendar_prompt'], ' ', implode(', ', $day['holidays']), '</div>';

			// Show any birthdays...
			if (!empty($day['birthdays']))
			{
				echo '
							<div class="smalltext">
								<span class="birthday">', $txt['birthdays'], '</span>';

				// Each of the birthdays has:
				// id, name (person), age (if they have one set?), and is_last. (last in list?)
				foreach ($day['birthdays'] as $character)
					echo '
								<a href="', $scripturl, '?action=character;c=', $character['id'], '">', $character['name'], isset($character['age']) ? ' (' . $character['age'] . ')' : '', '</a>', $character['is_last'] ? '' : ', ';

				echo '
							</div>';
			}

			// Any special posted events?
			if (!empty($day['events']))
			{
				echo '
							<div class="smalltext">
								<span class="event">TXT Topics</span>';

				// The events are made up of:
				// title, href, is_last, can_edit (are they allowed to?), and modify_href.
				foreach ($day['events'] as $event)
				{
					// If they can edit the event, show a star they can click on....
					echo '
								', $event['link'], $event['is_last'] ? '' : ', ';
				}

				echo '
							</div>';
			}

			echo '
						</div>
					</li>';
		}

		echo '
				</ul>';
	}
}