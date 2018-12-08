<?php

/**
 * Integration of characters into last posted by on the board index.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
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
	 * post load functions, load Role Playing Game System elements into the board index.
	 * An infocenter section and character that posted in in-character boards
	 *
	 * @param array $callbacks
	 */

	public function post_load(&$callbacks)
	{
		global $context, $scripturl, $txt;

		loadTemplate('RolePlayingSystem');
		loadLanguage('RolePlayingSystem');
		$callbacks[] = 'role_playing_system';
		
		$db = database();
		$request = $db->query('boardindex_fetch_boards', '
			SELECT 
				b.id_board, b.id_cat, b.id_parent, m.id_msg, ch.id_character, ch.name, ch.avatar
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = b.id_last_msg)
				LEFT JOIN {db_prefix}rps_characters AS ch ON (ch.id_character = m.id_character)
			WHERE {query_see_board}
				AND b.in_character = 1
				AND b.child_level < 2
			ORDER BY b.board_order',
			array()
		);
		$last_posts = array();
		while ($row = $db->fetch_assoc($request)) {
			$last_posts[$row['id_msg']] = array(
				'id' => $row['id_msg'],
				'name' => $row['name'],
				'username' => $row['name'],
				'href' => !empty($row['name']) && !empty($row['id_character']) ? $scripturl . '?action=character;c=' . $row['id_character'] : '',
				'link' => !empty($row['name']) && !empty($row['id_character']) ? '<a href="' . $scripturl . '?action=character;c=' . $row['id_character'] . '">' . $row['name'] . '</a>' : '',
				'avatar' => Role_Playing_System_Integrate::determineCharacterAvatar(array('avatar' => $row['avatar'])),
			);

		}
		
		foreach ($context['categories'] as &$category){
			foreach ($category['boards'] as &$board){
				
				if (isset($last_posts[$board['last_post']['id']])) {
					$board['last_post']['last_post_message'] = sprintf($txt['last_post_message'], $last_posts[$board['last_post']['id']]['link'], $board['last_post']['link'], $board['last_post']['html_time']);
					$board['last_post']['member']['avatar'] = $last_posts[$board['last_post']['id']]['avatar'];
				}
			}
		}
	
	}
}
