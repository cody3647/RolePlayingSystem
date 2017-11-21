<?php

/**
 * Integration system for drafts into Post controller
 *
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

use ElkArte\Errors\ErrorContext;

/**
 * Class Drafts_Post_Module
 *
 * Events and functions for post based drafts
 */
class RolePlayingSystem_BoardIndex_Module extends ElkArte\sources\modules\Abstract_Module
{
	/**
	 * {@inheritdoc }
	 */
	public static function hooks(\Event_Manager $eventsManager)
	{
		global $modSettings;
		
		$return = array();
		if (!empty($modSettings['rps_enabled']) )
		{

			$return = array(
				array('post_load', array('RolePlayingSystem_BoardIndex_Module', 'post_load'), array()),
			);
		}

		return $return;
	}
	
	/**
	 * Pre-load hooks as part of board index
	 */
	public function pre_load()
	{
		global $modSettings, $user_info, $context;

		// Retrieve the calendar data (events, birthdays, holidays).
		$eventOptions = array(
			'include_holidays' => $modSettings[$modSettings['rps_showholidays']] > 1,
			'include_birthdays' => $modSettings['rps_showbdays'] > 1,
			'num_days_shown' => empty($modSettings['cal_days_for_index']) || $modSettings['cal_days_for_index'] < 1 ? 1 : $modSettings['cal_days_for_index'],
		);

		$context += cache_quick_get('calendar_index_offset_' . ($user_info['time_offset'] + $modSettings['time_offset']), 'subs/Calendar.subs.php', 'cache_getRecentEvents', array($eventOptions));

		// Whether one or multiple days are shown on the board index.
		$context['calendar_only_today'] = $modSettings['cal_days_for_index'] == 1;

		// This is used to show the "how-do-I-edit" help.
		$context['calendar_can_edit'] = allowedTo('calendar_edit_any');
	}

	/**
	 * post load functions, load calendar events for the board index as part of BoardIndex
	 *
	 * @param array $callbacks
	 */
	public function post1_load(&$callbacks)
	{
		global $context;

		if (empty($context['calendar_holidays']) && empty($context['calendar_birthdays']) && empty($context['calendar_events']))
			return;

		$callbacks = elk_array_insert($callbacks, 'recent_posts', array('show_events'), 'after', false);
	}

	public function post_load(&$callbacks)
	{
		global $context;

		loadTemplate('RolePlayingSystem');
		loadLanguage('RolePlayingSystem');
		$callbacks[] = 'role_playing_system';
	}
	

}
