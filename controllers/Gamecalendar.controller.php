<?php

/**
 * Shows the Game Calendar.  Based on the Elkarte Forum Calendar.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 * 
 * 
 * Original module by Aaron O'Neil - aaron@mud-master.com
 *
 * name      ElkArte Forum
 * copyright ElkArte Forum contributors
 * license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 *
 *
 */

/**
 * Calendar_Controller class
 * Displays the calendar for the site and provides for its navigation
 */
class Gamecalendar_Controller extends Action_Controller
{
	public $current_dates;
	/**
	 * Default action handler for requests on the calendar
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		$this->current_dates = RpsCurrentDate::instance();
		// when you don't know what you're doing... we know! :P

		$this->action_calendar();
	}

	/**
	 * Show the calendar.
	 *
	 * - It loads the specified month's events, holidays, and birthdays.
	 * - It requires the calendar_view permission.
	 * - It depends on the cal_enabled setting, and many of the other cal_ settings.
	 * - It uses the calendar_start_day theme option. (Monday/Sunday)
	 * - It goes to the month and year passed in 'month' and 'year' by get or post.
	 * - It is accessed through ?action=calendar.
	 *
	 * @uses the main sub template in the Calendar template.
	 */
	public function action_calendar()
	{
		global $txt, $context, $modSettings, $scripturl, $options;

		// This is gonna be needed...
		loadTemplate('RpsCalendar');

		// Set the page title to mention the calendar ;).
		$context['page_title'] = $txt['rps_game_calendar'];
		$context['sub_template'] = 'show_calendar';

		// Is this a week view?
		$context['view_week'] = isset($_GET['viewweek']);
		$context['cal_minyear'] = $this->current_dates->minyear;
		$context['cal_maxyear'] = $this->current_dates->maxyear;

		// Don't let search engines index weekly calendar pages.
		if ($context['view_week'])
			$context['robot_no_index'] = true;

		// Get the current day of month...
		require_once(SUBSDIR . '/Gamecalendar.subs.php');

		// If the month and year are not passed in, use today's date as a starting point.
		$curPage = array(
			'day' => $this->_req->getQuery('day', 'intval', $this->current_dates->start_day),
			'month' => $this->_req->getQuery('month', 'intval', $this->current_dates->start_month),
			'year' => $this->_req->getQuery('year', 'intval', $this->current_dates->start_year)
		);

		// Make sure the year and month are in valid ranges.
		if ($curPage['month'] < 1 || $curPage['month'] > 12)
			throw new Elk_Exception('invalid_month', false);

		if ($curPage['year'] < $context['cal_minyear'] || $curPage['year'] > $context['cal_maxyear'])
		{
			if ($curPage['month'] == 12 && $curPage['day'] > 25)
			{
				$curPage['day'] = 01;
				$curPage['month'] = 01;
				$curPage['year'] += 1;
			}
			else
				throw new Elk_Exception('invalid_year', false);
		}
		// If we have a day clean that too.
		if ($context['view_week'])
		{
			if($curPage['day'] == 0)
				$curPage['day']++;
			if ($curPage['day'] > 31 || !checkDate($curPage['month'], $curPage['day'], $curPage['year']))
				throw new Elk_Exception('invalid_day', false);
		}

		// Load all the context information needed to show the calendar grid.
		$calendarOptions = array(
			'start_day' => !empty($options['calendar_start_day']) ? $options['calendar_start_day'] : 0,
			'show_birthdays' => in_array($modSettings['rps_showbdays'], array(1, 2)),
			'show_topics' => in_array($modSettings['rps_showtopics'], array(1, 2)),
			'show_holidays' => in_array($modSettings['rps_showholidays'], array(1, 2)),
			'show_phases' => true,
			'show_week_num' => true,
			'short_day_titles' => false,
			'show_next_prev' => true,
			'show_week_links' => true,
			'size' => 'large',
		);

		// Load up the main view.
		if ($context['view_week'])
			$context['calendar_grid_main'] = getCalendarWeek($curPage['month'], $curPage['year'], $curPage['day'], $calendarOptions);
		else
			$context['calendar_grid_main'] = getCalendarGrid($curPage['month'], $curPage['year'], $calendarOptions);

		// Load up the previous and next months.
		$calendarOptions['show_birthdays'] = $calendarOptions['show_topics'] = $calendarOptions['show_holidays'] = $calendarOptions['show_phases'] = false;
		$calendarOptions['short_day_titles'] = true;
		$calendarOptions['show_next_prev'] = false;
		$calendarOptions['show_week_links'] = false;
		$calendarOptions['size'] = 'small';
		$context['calendar_grid_current'] = getCalendarGrid($curPage['month'], $curPage['year'], $calendarOptions);
		
		// Only show previous month if it isn't pre-January of the min-year
		if ($context['calendar_grid_current']['previous_calendar']['year'] >= $context['cal_minyear'] || $curPage['month'] != 1)
			$context['calendar_grid_prev'] = getCalendarGrid($context['calendar_grid_current']['previous_calendar']['month'], $context['calendar_grid_current']['previous_calendar']['year'], $calendarOptions);

		// Only show next month if it isn't post-December of the max-year
		if ($context['calendar_grid_current']['next_calendar']['year'] < $context['cal_maxyear'] || $curPage['month'] != 12)
			$context['calendar_grid_next'] = getCalendarGrid($context['calendar_grid_current']['next_calendar']['month'], $context['calendar_grid_current']['next_calendar']['year'], $calendarOptions);

		// Basic template stuff.
		$context['current_day'] = $curPage['day'];
		$context['current_month'] = $curPage['month'];
		$context['current_year'] = $curPage['year'];
		$context['show_all_birthdays'] = isset($_GET['showbd']);

		// Set the page title to mention the month or week, too
		$context['page_title'] .= ' - ' . ($context['view_week'] ? sprintf($txt['calendar_week_title'], $context['calendar_grid_main']['week_number'], ($context['calendar_grid_main']['week_number'] == 53 ? $context['current_year'] - 1 : $context['current_year'])) : $txt['months'][$context['current_month']] . ' ' . $context['current_year']);

		// Load up the linktree!
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=gamecalendar',
			'name' => $txt['rps_gamecalendar']
		);

		// Add the current month to the linktree.
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=gamecalendar;year=' . $context['current_year'] . ';month=' . $context['current_month'],
			'name' => $txt['months'][$context['current_month']] . ' ' . $context['current_year']
		);

		// If applicable, add the current week to the linktree.
		if ($context['view_week'])
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=gamecalendar;viewweek;year=' . $context['current_year'] . ';month=' . $context['current_month'] . ';day=' . $context['current_day'],
				'name' => $txt['calendar_week'] . ' ' . $context['calendar_grid_main']['week_number']
			);
	}
}
