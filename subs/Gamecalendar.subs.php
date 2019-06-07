<?php

/**
 * Functions for loading holiday, character birthdate, moon phase, and date tagged threads into the game calendar.
 * And functions for creating, editing, and removing holidays and moon phases.
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
 * Provides information (link, month, year) about the previous and next month.
 *
 * @package Calendar
 * @param int $month
 * @param int $year
 * @param mixed[] $calendarOptions
 * @return array containing all the information needed to show a calendar grid for the given month
 * @throws Exception
 */
function getCalendarGrid($month, $year, $calendarOptions)
{
	global $scripturl, $modSettings;

	// Get todays date.
	$current_dates = RpsCurrentDate::instance();
	
	// Eventually this is what we'll be returning.
	$calendarGrid = array(
		'week_days' => array(),
		'weeks' => array(),
		'short_day_titles' => !empty($calendarOptions['short_day_titles']),
		'current_month' => $month,
		'current_year' => $year,
		'show_next_prev' => !empty($calendarOptions['show_next_prev']),
		'show_week_links' => !empty($calendarOptions['show_week_links']),
		'previous_calendar' => array(
			'year' => $month == 1 ? $year - 1 : $year,
			'month' => $month == 1 ? 12 : $month - 1,
			'disabled' => $current_dates->minyear > ($month == 1 ? $year - 1 : $year),
		),
		'next_calendar' => array(
			'year' => $month == 12 ? $year + 1 : $year,
			'month' => $month == 12 ? 1 : $month + 1,
			'disabled' => $current_dates->maxyear < ($month == 12 ? $year + 1 : $year),
		),
		'size' => isset($calendarOptions['size']) ? $calendarOptions['size'] : 'large',
	);
	$first_day = new DateTimeImmutable($year . '-' . $month . '-01');
	$last_day = $first_day->modify('Last day of this month');
	// Get information about this month.
	$month_info = array(
		'first_day' => array(
			'day_of_week' => (int) $first_day->format('w'),
			'week_num' => (int) $first_day->format('W'),
			'date' => $first_day->format('Y-m-d'),
		),
		'last_day' => array(
			'day_of_month' => (int) $last_day->format('d'),
			'date' => $last_day->format('Y-m-d'),
		),
		'first_day_of_year' => (int) $first_day->modify('first day of this year')->format('w'),
		'first_day_of_next_year' => (int) $first_day->modify('first day of next year')->format('w'),
	);

	// The number of days the first row is shifted to the right for the starting day.
	$nShift = $month_info['first_day']['day_of_week'];

	$calendarOptions['start_day'] = empty($calendarOptions['start_day']) ? 0 : (int) $calendarOptions['start_day'];

	// Starting any day other than Sunday means a shift...
	if (!empty($calendarOptions['start_day']))
	{
		$nShift -= $calendarOptions['start_day'];
		if ($nShift < 0)
			$nShift = 7 + $nShift;
	}

	// Number of rows required to fit the month.
	$nRows = floor(($month_info['last_day']['day_of_month'] + $nShift) / 7);
	if (($month_info['last_day']['day_of_month'] + $nShift) % 7)
		$nRows++;

	// Fetch the arrays for birthdays, posted events, and holidays.
	$bday = $calendarOptions['show_birthdays'] ? getBirthdayRange($month_info['first_day']['date'], $month_info['last_day']['date']) : array();
	$events = $calendarOptions['show_topics'] ? getTopicRange($month_info['first_day']['date'], $month_info['last_day']['date']) : array();
	$holidays = $calendarOptions['show_holidays'] ? getHolidayRange($month_info['first_day']['date'], $month_info['last_day']['date']) : array();
	$phases = $calendarOptions['show_phases'] ? getPhaseRange($month_info['first_day']['date'], $month_info['last_day']['date']) : array();

	// Days of the week taking into consideration that they may want it to start on any day.
	$count = $calendarOptions['start_day'];
	for ($i = 0; $i < 7; $i++)
	{
		$calendarGrid['week_days'][] = $count;
		$count++;
		if ($count == 7)
			$count = 0;
	}

	// Iterate through each week.
	$calendarGrid['weeks'] = array();
	for ($nRow = 0; $nRow < $nRows; $nRow++)
	{
		// Start off the week - and don't let it go above 52, since that's the number of weeks in a year.
		$calendarGrid['weeks'][$nRow] = array(
			'days' => array(),
			'number' => $month_info['first_day']['week_num'] + $nRow
		);

		// Handle the dreaded "week 53", it can happen, but only once in a blue moon ;)
		if ($calendarGrid['weeks'][$nRow]['number'] == 53 && $nShift != 4 && $month_info['first_day_of_next_year'] < 4)
			$calendarGrid['weeks'][$nRow]['number'] = 1;

		// And figure out all the days.
		for ($nCol = 0; $nCol < 7; $nCol++)
		{
			$nDay = ($nRow * 7) + $nCol - $nShift + 1;

			if ($nDay < 1 || $nDay > $month_info['last_day']['day_of_month'])
				$nDay = 0;

			$date = sprintf('%04d-%02d-%02d', $year, $month, $nDay);

			$calendarGrid['weeks'][$nRow]['days'][$nCol] = array(
				'day' => $nDay,
				'date' => $date,
				'is_today' => $current_dates->between($date),
				'is_first_day' => !empty($calendarOptions['show_week_num']) && (($month_info['first_day']['day_of_week'] + $nDay - 1) % 7 == $calendarOptions['start_day']),
				'holidays' => !empty($holidays[$date]) ? $holidays[$date] : array(),
				'events' => !empty($events[$date]) ? $events[$date] : array(),
				'birthdays' => !empty($bday[$date]) ? $bday[$date] : array(),
				'phases' => !empty($phases[$date]) ? $phases[$date] : array(),
			);
		}
	}

	// Set the previous and the next month's links.
	$calendarGrid['previous_calendar']['href'] = $scripturl . '?action=gamecalendar;year=' . $calendarGrid['previous_calendar']['year'] . ';month=' . $calendarGrid['previous_calendar']['month'];
	$calendarGrid['next_calendar']['href'] = $scripturl . '?action=gamecalendar;year=' . $calendarGrid['next_calendar']['year'] . ';month=' . $calendarGrid['next_calendar']['month'];

	return $calendarGrid;
}

/**
 * Returns the information needed to show a calendar for the given week.
 *
 * @package Calendar
 * @param int $month
 * @param int $year
 * @param int $day
 * @param mixed[] $calendarOptions
 * @return array
 * @throws Exception
 */
function getCalendarWeek($month, $year, $day, $calendarOptions)
{
	global $scripturl, $modSettings;

	// Get todays date.
	$current_dates = RpsCurrentDate::instance();
	$date = new DateTime($year . '-' . $month . '-' . $day);

	// What is the actual "start date" for the passed day.
	$calendarOptions['start_day'] = empty($calendarOptions['start_day']) ? 0 : (int) $calendarOptions['start_day'];
	$day_of_week = (int) $date->format('w');
	if ($day_of_week != $calendarOptions['start_day'])
	{
		// Here we offset accordingly to get things to the real start of a week.
		$date_diff = $day_of_week - $calendarOptions['start_day'];
		if ($date_diff < 0)
			$date_diff += 7;
		$date = $date->modify('-'.$date_diff.' day');
		
		$day = (int) $date->format('d');
		$month = (int)$date->format('m');
		$year = (int) $date->format('Y');
	}
	$date = DateTimeImmutable::createFromMutable( $date );
	
	$previous = $date->modify('-1 week');
	$next = $date->modify('+1 week');


	// Now start filling in the calendar grid.
	$calendarGrid['show_next_prev'] =  !empty($calendarOptions['show_next_prev']);

	// The next week calculation requires a bit more work.
	$curTimestamp = mktime(0, 0, 0, $month, $day, $year);
	$nextWeekTimestamp = $curTimestamp + 604800;


	// Fetch the arrays for birthdays, posted events, and holidays.
	$startDate = $date->format('Y-m-d');
	$endDate = $next->format('Y-m-d');
	$bday = $calendarOptions['show_birthdays'] ? getBirthdayRange($startDate, $endDate) : array();
	$events = $calendarOptions['show_topics'] ? getTopicRange($startDate, $endDate) : array();
	$holidays = $calendarOptions['show_holidays'] ? getHolidayRange($startDate, $endDate) : array();
	$phases = $calendarOptions['show_phases'] ? getPhaseRange($startDate, $endDate) : array();

	// An adjustment value to apply to all calculated week numbers.
	if (!empty($calendarOptions['show_week_num']))
	{
		/*$first_day_of_year = (int) strftime('%w', mktime(0, 0, 0, 1, 1, $year));
		$first_day_of_next_year = (int) strftime('%w', mktime(0, 0, 0, 1, 1, $year + 1));
		// this one is not used in its scope
		// $last_day_of_last_year = (int) strftime('%w', mktime(0, 0, 0, 12, 31, $year - 1));

		// All this is as getCalendarGrid.
		if ($calendarOptions['start_day'] === 0)
			$nWeekAdjust = $first_day_of_year === 0 && $first_day_of_year > 3 ? 0 : 1;
		else
			$nWeekAdjust = $calendarOptions['start_day'] > $first_day_of_year && $first_day_of_year !== 0 ? 2 : 1;

		$calendarGrid['week_number'] = (int) strftime('%U', mktime(0, 0, 0, $month, $day, $year)) + $nWeekAdjust;

		// If this crosses a year boundary and includes january it should be week one.
		if ((int) strftime('%Y', $curTimestamp + 518400) != $year && $calendarGrid['week_number'] > 53 && $first_day_of_next_year < 5)
			$calendarGrid['week_number'] = 1;
		*/
		$calendarGrid['week_number'] = $date->modify('monday')->format('W');
	}

	// This holds all the main data - there is at least one month!
	$calendarGrid['months'] = array();
	$lastDay = 99;
	$curDay = $day;
	$curDayOfWeek = $calendarOptions['start_day'];
	for ($i = 0; $i < 7; $i++)
	{
		// Have we gone into a new month (Always happens first cycle too)
		if ($lastDay > $curDay)
		{
			$curMonth = $lastDay == 99 ? $month : ($month == 12 ? 1 : $month + 1);
			$curYear = $lastDay == 99 ? $year : ($curMonth == 1 && $month == 12 ? $year + 1 : $year);
			$calendarGrid['months'][$curMonth] = array(
				'current_month' => $curMonth,
				'current_year' => $curYear,
				'days' => array(),
			);
		}

		// Add todays information to the pile!
		$date = sprintf('%04d-%02d-%02d', $curYear, $curMonth, $curDay);

		$calendarGrid['months'][$curMonth]['days'][$curDay] = array(
			'day' => $curDay,
			'day_of_week' => $curDayOfWeek,
			'date' => $date,
			'is_today' => $current_dates->between($date),
			'holidays' => !empty($holidays[$date]) ? $holidays[$date] : array(),
			'events' => !empty($events[$date]) ? $events[$date] : array(),
			'birthdays' => !empty($bday[$date]) ? $bday[$date] : array(),
			'phases' => !empty($phases[$date]) ? $phases[$date] : array(),
		);

		// Make the last day what the current day is and work out what the next day is.
		$lastDay = $curDay;
		$curTimestamp += 86400;
		$curDay = (int) strftime('%d', $curTimestamp);

		// Also increment the current day of the week.
		$curDayOfWeek = $curDayOfWeek >= 6 ? 0 : ++$curDayOfWeek;
	}

	// Set the previous and the next week's links.
	$calendarGrid['previous_week']['href'] = $scripturl . '?action=gamecalendar;viewweek;year=' . $previous->format('Y') . ';month=' . $previous->format('m') . ';day=' . $previous->format('d');
	$calendarGrid['next_week']['href'] = $scripturl . '?action=gamecalendar;viewweek;year=' . $next->format('Y') . ';month=' . $next->format('m') . ';day=' . $next->format('d');

	return $calendarGrid;
}

/**
 * Get all birthdays within the given time range.
 *
 * What it does:
 *
 * - finds all the birthdays in the specified range of days.
 * - works with birthdays set for no year, or any other year, and respects month and year boundaries.
 *
 * @package Calendar
 * @param string $low_date inclusive, YYYY-MM-DD
 * @param string $high_date inclusive, YYYY-MM-DD
 * @return mixed[] days, each of which an array of birthday information for the context
 * @throws Exception
 */
function getBirthdayRange($low_date, $high_date)
{
	$year_low = (int) substr($low_date, 0, 4);
	$year_high = (int) substr($high_date, 0, 4);
	
	$db = database();
	
	$result = $db->query('birthday_array', '
		SELECT id_character, name, birthdate, YEAR(birthdate) AS birth_year
		FROM {db_prefix}rps_characters
		WHERE MONTH(birthdate) != {int:no_month}
			AND DAYOFMONTH(birthdate) != {int:no_day}
			AND YEAR(birthdate) <= {int:max_year}
			AND (
				DATE_FORMAT(birthdate, {string:year_low}) BETWEEN {date:low_date} AND {date:high_date}' . ($year_low == $year_high ? '' : '
				OR DATE_FORMAT(birthdate, {string:year_high}) BETWEEN {date:low_date} AND {date:high_date}') . '
			)',
	//		AND approved = {int:approved}',
		array(
			'approved' => 1,
			'no_month' => 0,
			'no_day' => 0,
			'year_zero' => '0000',
			'year_low' => $year_low . '-%m-%d',
			'year_high' => $year_high . '-%m-%d',
			'low_date' => $low_date,
			'high_date' => $high_date,
			'max_year' => $year_high,
		)
	);
		
	$bday = array();
	while ($row = $db->fetch_assoc($result))
	{
		if ($year_low != $year_high)
			$age_year = substr($row['birthdate'], 5) < substr($high_date, 5) ? $year_high : $year_low;
		else
			$age_year = $year_low;
		
		$bday[$age_year . substr($row['birthdate'], 4)][] = array(
			'id' => $row['id_character'],
			'name' => $row['name'],
			'age' => $row['birth_year'] > 4 && $row['birth_year'] <= $age_year ? $age_year - $row['birth_year'] : null,
			'is_last' => false
		);
	}
	$db->free_result($result);

	// Set is_last, so the themes know when to stop placing separators.
	foreach ($bday as $mday => $array)
		$bday[$mday][count($array) - 1]['is_last'] = true;

	return $bday;
}

/**
 * Get all calendar events within the given time range.
 *
 * What it does:
 *
 * - finds all the posted calendar events within a date range.
 * - both the earliest_date and latest_date should be in the standard YYYY-MM-DD format.
 * - censors the posted event titles.
 * - uses the current user's permissions if use_permissions is true, otherwise it does nothing "permission specific"
 *
 * @package Calendar
 * @param string $low_date
 * @param string $high_date
 * @param bool $use_permissions = true
 * @param integer|null $limit
 * @return array contextual information if use_permissions is true, and an array of the data needed to build that otherwise
 * @throws Exception
 */
function getTopicRange($low_date, $high_date, $use_permissions = true, $limit = null)
{
	global $scripturl, $modSettings, $user_info, $context;

	$db = database();

	$low_date_time = sscanf($low_date, '%04d-%02d-%02d');
	$low_date_time = mktime(0, 0, 0, $low_date_time[1], $low_date_time[2], $low_date_time[0]);
	$high_date_time = sscanf($high_date, '%04d-%02d-%02d');
	$high_date_time = mktime(0, 0, 0, $high_date_time[1], $high_date_time[2], $high_date_time[0]);

	// Find all the calendar info...
	$result = $db->query('', '
		SELECT
			t.id_topic, b.member_groups, t.id_first_msg, t.approved, b.id_board,
			msg.subject, t.date_tag
		FROM {db_prefix}topics AS t
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
			LEFT JOIN {db_prefix}messages AS msg ON (t.id_first_msg = msg.id_msg)
		WHERE t.date_tag <= {date:high_date}
			AND t.date_tag >= {date:low_date}' . ($use_permissions ? '
			AND (t.id_board = {int:no_board_link} OR {query_wanna_see_board})' : '') . (!empty($limit) ? '
		LIMIT {int:limit}' : ''),
		array(
			'high_date' => $high_date,
			'low_date' => $low_date,
			'no_board_link' => 0,
			'limit' => $limit,
		)
	);
	$events = array();
	while ($row = $db->fetch_assoc($result))
	{
		// If the attached topic is not approved then for the moment pretend it doesn't exist
		if (!empty($row['id_first_msg']) && $modSettings['postmod_active'] && !$row['approved'])
			continue;

		// Force a censor of the title - as often these are used by others.
		$row['subject'] = censor($row['subject'], $use_permissions ? false : true);

		// If we're using permissions (calendar pages?) then just ouput normal contextual style information.
		if ($use_permissions)
			$events[$row['date_tag']][] = array(
				'id' => $row['id_topic'],
				'subject' => $row['subject'],
				'id_board' => $row['id_board'],
				'id_topic' => $row['id_topic'],
				'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0',
				'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0">' . $row['subject'] . '</a>',
				'is_last' => false,
			);
		// Otherwise, this is going to be cached and the VIEWER'S permissions should apply... just put together some info.
		else
			$events[strftime('%Y-%m-%d', $date)][] = array(
				'id' => $row['id_topic'],
				'title' => $row['subject'],
				'id_board' => $row['id_board'],
				'id_topic' => $row['id_topic'],
				'href' => $scripturl . '?topic=' . $row['id_topic'] . '.0',
				'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.0">' . $row['title'] . '</a>',
				'topic' => $row['id_topic'],
				'msg' => $row['id_first_msg'],
				'poster' => $row['id_member'],
				'allowed_groups' => explode(',', $row['member_groups']),
				'is_last' => false,
			);
	}
	$db->free_result($result);

	// If we're doing normal contextual data, go through and make things clear to the templates ;).
	if ($use_permissions)
	{
		foreach ($events as $mday => $array)
			$events[$mday][count($array) - 1]['is_last'] = true;
	}

	return $events;
}

/**
 * Get all holidays within the given time range.
 *
 * @package Calendar
 * @param string $low_date YYYY-MM-DD
 * @param string $high_date YYYY-MM-DD
 * @return array an array of days, which are all arrays of holiday names.
 * @throws Exception
 */
function getHolidayRange($low_date, $high_date)
{
	$db = database();

	// Find some holidays... ;).
	$result = $db->query('', '
		SELECT event_year, event_month, event_day, title
		FROM {db_prefix}rps_events
		WHERE event_month BETWEEN MONTH({date:low_date}) AND MONTH({date:high_date})
			AND (event_year = 0 OR event_year BETWEEN YEAR({date:low_date}) AND YEAR({date:high_date}))',
		array(
			'low_date' => $low_date,
			'high_date' => $high_date,
		)
	);
	$holidays = array();
	$months = array(1=>'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	while ($row = $db->fetch_assoc($result))
	{
		if (substr($low_date, 0, 4) != substr($high_date, 0, 4))
			$event_year = $row['event_month'] < substr($high_date, 5, 2) ? substr($high_date, 0, 4) : substr($low_date, 0, 4);
		else
			$event_year = substr($low_date, 0, 4);
		$day = is_numeric($row['event_day']) ? $row['event_day'] : $row['event_day'] . ' of';
		$date = new DateTime($day . ' ' . $months[$row['event_month']] . ' ' . $event_year);
		$holidays[$date->format('Y-m-d')][] = $row['title'];
	}
	$db->free_result($result);

	return $holidays;
}

function getPhaseRange($low_date, $high_date)
{
	$db = database();
	$phases = array();
	
	// Find some holidays... ;).
	$result = $db->query('', '
		SELECT phase, phase_date, phase_time
		FROM {db_prefix}rps_phases
		WHERE phase_date <= {date:high_date}
			AND phase_date >= {date:low_date}',
		array(
			'low_date' => $low_date,
			'high_date' => $high_date,
		)
	);

	while ($row = $db->fetch_assoc($result))
	{
		$phases[$row['phase_date']] = $row['phase'];
	}
	$db->free_result($result);

	return $phases;
}

/**
 * Retrieve all events for the given days, independently of the users offset.
 *
 * What it does:
 *
 * - cache callback function used to retrieve the birthdays, holidays, and events between now and now + days_to_index.
 * - widens the search range by an extra 24 hours to support time offset shifts.
 * - used by the cache_getRecentEvents function to get the information needed to calculate the events taking the users time offset into account.
 *
 * @package Calendar
 * @param RpsCurrentDate $current_dates
 * @return array
 * @throws Exception
 */
function cache_getCurrentEvents(RpsCurrentDate $current_dates)
{
	$low_date = strftime('%Y-%m-%d', forum_time(false) - 24 * 3600);
	$high_date = strftime('%Y-%m-%d', forum_time(false) + $days_to_index * 24 * 3600);

	return array(
		'data' => array(
			'holidays' => getHolidayRange($current_dates->start_date, $current_dates->end_date),
			'birthdays' => getBirthdayRange($current_dates->start_date, $current_dates->end_date),
			'phases' => getEventRange($current_dates->start_date, $current_dates->end_date, false),
		),
		'refresh_eval' => 'return \'' . strftime('%Y%m%d', forum_time(false)) . '\' != strftime(\'%Y%m%d\', forum_time(false)) || (!empty($modSettings[\'rps_gamecalendar_updated\']) && ' . time() . ' < $modSettings[\'rps_gamecalendar_updated\']);',
		'expires' => time() + 3600,
	);
}

/**
 * cache callback function used to retrieve the upcoming birthdays, holidays, and events
 * within the given period, taking into account the users time offset.
 *
 * - Called from the BoardIndex to display the current day's events on the board index
 * - used by the board index and SSI to show the upcoming events.
 *
 * @package Calendar
 * @param mixed[] $eventOptions
 * @return array
 */
function cache_getRecentEvents($eventOptions)
{
	$current_dates = RpsCurrentDate::instance();
	
	// With the 'static' cached data we can calculate the user-specific data.
	$cached_data = cache_quick_get('gamecalendar_index', 'subs/Gamecalendar.subs.php', 'cache_getCurrentEvents', array($current_dates));

	// Get the information about today (from user perspective).
	

	$return_data = array(
		'rps_holidays' => array(),
		'rps_birthdays' => array(),
		'rps_phases' => array(),
	);

	// Set the event span to be shown in seconds.
	$days_for_index = $eventOptions['num_days_shown'] * 86400;

	// Get the current member time/date.
	$now = forum_time();

	// Holidays between now and now + days.
	for ($i = $now; $i < $now + $days_for_index; $i += 86400)
	{
		if (isset($cached_data['holidays'][strftime('%Y-%m-%d', $i)]))
			$return_data['calendar_holidays'] = array_merge($return_data['calendar_holidays'], $cached_data['holidays'][strftime('%Y-%m-%d', $i)]);
	}

	// Happy Birthday, guys and gals!
	for ($i = $now; $i < $now + $days_for_index; $i += 86400)
	{
		$loop_date = strftime('%Y-%m-%d', $i);
		if (isset($cached_data['birthdays'][$loop_date]))
		{
			foreach ($cached_data['birthdays'][$loop_date] as $index => $dummy)
				$cached_data['birthdays'][strftime('%Y-%m-%d', $i)][$index]['is_today'] = $current_dates->between($loop_date);
			$return_data['calendar_birthdays'] = array_merge($return_data['calendar_birthdays'], $cached_data['birthdays'][$loop_date]);
		}
	}

	$duplicates = array();
	for ($i = $now; $i < $now + $days_for_index; $i += 86400)
	{
		// Determine the date of the current loop step.
		$loop_date = strftime('%Y-%m-%d', $i);

		// No events today? Check the next day.
		if (empty($cached_data['events'][$loop_date]))
			continue;

		// Loop through all events to add a few last-minute values.
		foreach ($cached_data['events'][$loop_date] as $ev => $event)
		{
			// Create a shortcut variable for easier access.
			$this_event = &$cached_data['events'][$loop_date][$ev];

			// Skip duplicates.
			if (isset($duplicates[$this_event['topic'] . $this_event['title']]))
			{
				unset($cached_data['events'][$loop_date][$ev]);
				continue;
			}
			else
				$duplicates[$this_event['topic'] . $this_event['title']] = true;

			// Might be set to true afterwards, depending on the permissions.
			$this_event['can_edit'] = false;
			$this_event['is_today'] = $current_dates->between($loop_date);
			$this_event['date'] = $loop_date;
		}

		if (!empty($cached_data['events'][$loop_date]))
			$return_data['calendar_events'] = array_merge($return_data['calendar_events'], $cached_data['events'][$loop_date]);
	}

	// Mark the last item so that a list separator can be used in the template.
	for ($i = 0, $n = count($return_data['calendar_birthdays']); $i < $n; $i++)
		$return_data['calendar_birthdays'][$i]['is_last'] = !isset($return_data['calendar_birthdays'][$i + 1]);
	for ($i = 0, $n = count($return_data['calendar_events']); $i < $n; $i++)
		$return_data['calendar_events'][$i]['is_last'] = !isset($return_data['calendar_events'][$i + 1]);

	return array(
		'data' => $return_data,
		'expires' => time() + 3600,
		'refresh_eval' => 'return \'' . strftime('%Y%m%d', forum_time(false)) . '\' != strftime(\'%Y%m%d\', forum_time(false)) || (!empty($modSettings[\'rps_gamecalendar_updated\']) && ' . time() . ' < $modSettings[\'rps_gamecalendar_updated\']);',
		'post_retri_eval' => '
			global $context, $scripturl, $user_info;

			foreach ($cache_block[\'data\'][\'calendar_events\'] as $k => $event)
			{
				// Remove events that the user may not see or wants to ignore.
				if ((count(array_intersect($user_info[\'groups\'], $event[\'allowed_groups\'])) === 0 && !allowedTo(\'admin_forum\') && !empty($event[\'id_board\'])) || in_array($event[\'id_board\'], $user_info[\'ignoreboards\']))
					unset($cache_block[\'data\'][\'calendar_events\'][$k]);
				else
				{
					// Whether the event can be edited depends on the permissions.
					$cache_block[\'data\'][\'calendar_events\'][$k][\'can_edit\'] = allowedTo(\'calendar_edit_any\') || ($event[\'poster\'] == $user_info[\'id\'] && allowedTo(\'calendar_edit_own\'));

					// The added session code makes this URL not cachable.
					$cache_block[\'data\'][\'calendar_events\'][$k][\'modify_href\'] = $scripturl . \'?action=\' . ($event[\'topic\'] == 0 ? \'calendar;sa=post;\' : \'post;msg=\' . $event[\'msg\'] . \';topic=\' . $event[\'topic\'] . \'.0;calendar;\') . \'eventid=\' . $event[\'id\'] . \';\' . $context[\'session_var\'] . \'=\' . $context[\'session_id\'];
				}
			}

			if (empty($params[0][\'include_holidays\']))
				$cache_block[\'data\'][\'calendar_holidays\'] = array();
			if (empty($params[0][\'include_birthdays\']))
				$cache_block[\'data\'][\'calendar_birthdays\'] = array();
			if (empty($params[0][\'include_events\']))
				$cache_block[\'data\'][\'calendar_events\'] = array();

			$cache_block[\'data\'][\'show_calendar\'] = !empty($cache_block[\'data\'][\'calendar_holidays\']) || !empty($cache_block[\'data\'][\'calendar_birthdays\']) || !empty($cache_block[\'data\'][\'calendar_events\']);',
	);
}

/**
 * Remove a holiday from the calendar.
 *
 * @package Calendar
 * @param $event_ids
 * @param string $type
 * @throws Exception
 */
function removeEvents($event_ids, $type = 'event')
{
	$db = database();

	if (!is_array($event_ids))
		$event_ids = array($event_ids);

	$db->query('', '
		DELETE FROM {db_prefix}rps_'.$type.'s
		WHERE id_'.$type.' IN ({array_int:id_event})',
		array(
			'id_event' => $event_ids,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}

/**
 * Updates a calendar holiday
 *
 * @package Calendar
 * @param $event
 * @param $year
 * @param $month
 * @param $day
 * @param string $title
 * @throws Exception
 */
function editEvent($event, $year, $month, $day, $title)
{
	$db = database();

	$db->query('', '
		UPDATE {db_prefix}rps_events
		SET event_year = {int:year}, event_month = {int:month}, event_day = {string:day}, title = {string:title}
		WHERE id_event = {int:selected_event}',
		array(
			'year' => $year,
			'month' => $month,
			'day' => $day,
			'selected_event' => $event,
			'title' => $title,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}

/**
 * Insert a new holiday
 *
 * @package Calendar
 * @param $year
 * @param $month
 * @param $day
 * @param string $title
 * @throws Exception
 */
function insertEvent($year, $month, $day, $title)
{
	$db = database();

	$db->insert('ignore',
		'{db_prefix}rps_events',
		array(
			'event_year' => 'int', 'event_month' => 'int', 'event_day' => 'string-20', 'title' => 'string-60',
		),
		array(
			$year, $month, $day, $title,
		),
		array('id_event')
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}

/**
 * Get a specific holiday
 *
 * @package Calendar
 * @param $id_event
 * @return array
 * @throws Exception
 */
function getEvent($id_event)
{
	$db = database();

	$request = $db->query('', '
		SELECT *
		FROM {db_prefix}rps_events
		WHERE id_event = {int:selected_event}
		LIMIT 1',
			array(
				'selected_event' => $id_event,
			)
		);
	while ($row = $db->fetch_assoc($request))
	{
		if(!is_numeric($row['event_day']))
		{
			$relative = explode(' ', $row['event_day']);
			$row['event_day'] = '';
		}
		
		$event = array(
			'id' => $row['id_event'],
			'day' => $row['event_day'],
			'month' => $row['event_month'],
			'year' => $row['event_year'],
			'title' => $row['title'],
			'ordinal' => !empty($relative[0]) ? $relative[0] : '',
			'dayname' => !empty($relative[1]) ? $relative[1] : '',
		);
	}
	$db->free_result($request);

	return $event;
}

/**
 * Gets all of the holidays for the listing
 *
 * @package Calendar
 * @param int $start The item to start with (for pagination purposes)
 * @param int $items_per_page The number of items to show per page
 * @param string $sort A string indicating how to sort the results
 * @return array
 * @throws Exception
 */
function list_getEvents($start, $items_per_page, $sort)
{
	$db = database();

	return $db->fetchQuery('
		SELECT id_event, event_year, event_month, event_day, title
		FROM {db_prefix}rps_events
		ORDER BY {raw:sort}
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
			'sort' => $sort,
		)
	);
}

/**
 * Helper function to get the total number of holidays
 *
 * @package Calendar
 * @return int
 * @throws Exception
 */
function list_getNumEvents()
{
	$db = database();

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_events',
		array(
		)
	);
	list ($num_items) = $db->fetch_row($request);
	$db->free_result($request);

	return (int) $num_items;
}

/**
 * Gets all of the holidays for the listing
 *
 * @package Calendar
 * @param int $start The item to start with (for pagination purposes)
 * @param int $items_per_page The number of items to show per page
 * @param string $sort A string indicating how to sort the results
 * @return array
 * @throws Exception
 */
function list_getPhases($start, $items_per_page, $sort)
{
	$db = database();

	return $db->fetchQuery('
		SELECT id_phase, phase, phase_date, phase_time
		FROM {db_prefix}rps_phases
		ORDER BY {raw:sort}
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
			'sort' => $sort,
		)
	);
}

/**
 * Helper function to get the total number of holidays
 *
 * @package Calendar
 * @return int
 * @throws Exception
 */
function list_getNumPhases()
{
	$db = database();

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_phases',
		array(
		)
	);
	list ($num_items) = $db->fetch_row($request);
	$db->free_result($request);

	return (int) $num_items;
}

function insert_downloaded_events($holidays, $phases, $postData)
{
	$db = database();

	foreach($holidays as $holiday)
	{
		list( $year, $month, $day) = explode('-',$holiday['holiday_date']);
		$insert_holidays[] = array((int) $year, (int) $month, (int) $day, $holiday['title']);
		unset($year);
		unset($month);
		unset($day);
	}
	
	//phases array('phase' => , 'phase_date' => , 'phase_time' => )
	
	$db->insert('ignore',
		'{db_prefix}rps_events',
		array(
			'event_year' => 'int', 'event_month' => 'int', 'event_day' => 'string-20', 'title' => 'string-60',
		),
		$insert_holidays,
		array('id_event')
	);
	
	$db->insert('ignore',
		'{db_prefix}rps_phases',
		array(
			'phase' => 'string-13', 'phase_date' => 'date', 'phase_time' => 'string-8',
		),
		$phases,
		array('id_phase')
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
	
}