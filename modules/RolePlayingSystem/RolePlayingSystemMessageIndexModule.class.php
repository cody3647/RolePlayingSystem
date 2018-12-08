<?php

/**
 * Integration of characters and date tag into MessageIndex controller.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * This class's task is to bind the posting of a topic to a calendar event.
 * Used when from the calendar controller the poster is redirected to the post page.
 *
 * @package Calendar
 */
class RolePlayingSystem_MessageIndex_Module extends ElkArte\sources\modules\Abstract_Module
{
	/**
	 * {@inheritdoc}
	 */
	public static function hooks(\Event_Manager $eventsManager)
	{
		return array();
	}

	public static function integrate_messageindex_topics(&$sort_column, &$indexOptions)
	{
		if (!isset($indexOptions['custom_selects']))
			$indexOptions['custom_selects'] = array();
		if (!isset($indexOptions['custom_joins']))
			$indexOptions['custom_joins'] = array();
		
		$indexOptions['custom_selects']= array_merge($indexOptions['custom_selects'], array('ml.id_character AS last_id_character', 'mf.id_character AS first_id_character', 'charl.name AS last_character_name', 'charf.name AS first_character_name','t.date_tag'));
		$indexOptions['custom_joins'] = array_merge($indexOptions['custom_joins'], array('
		LEFT JOIN {db_prefix}rps_characters AS charl ON (charl.id_character = ml.id_character)', 'LEFT JOIN {db_prefix}rps_characters AS charf ON (charf.id_character = mf.id_character)'));

		if (!empty($indexOptions['include_avatars']))
		{
			// Double equal comparison for 1 because it is backward compatible with 1.0 where the value was true/false
			if ($indexOptions['include_avatars'] == 1 || $indexOptions['include_avatars'] === 3)
			{
				$indexOptions['custom_selects'] = array_merge($indexOptions['custom_selects'], array('charl.avatar AS character_avatar'));
			}

			if ($indexOptions['include_avatars'] === 2 || $indexOptions['include_avatars'] === 3)
			{
				$indexOptions['custom_selects'] = array_merge($indexOptions['custom_selects'], array('charf.avatar AS character_avatar_first'));
			}
		}
	}
	
	public static function integrate_messageindex_listing($topics_info)
	{
		global $scripturl, $txt, $context;
		
		foreach ($topics_info as $row)
		{	
			$context['topics'][$row['id_topic']]['date_tag'] = !empty($row['date_tag']) ? self::dateComparedFormat($row['date_tag']) : false;

			if(!empty($row['first_id_character']))
			{
				unset($context['topics'][$row['id_topic']]['first_post']['member']);
				$context['topics'][$row['id_topic']]['first_post']['member'] = array(
					'username' => $row['first_character_name'],
					'name' => $row['first_character_name'],
					'id' => $row['first_id_character'],
					'href' => !empty($row['first_id_character']) ? $scripturl . '?action=character;c=' . $row['first_id_character'] : '',
					'link' => !empty($row['first_id_character']) ? '<a href="' . $scripturl . '?action=character;c=' . $row['first_id_character'] . '" title="' . $txt['profile_of'] . ' ' . $row['first_character_name'] . '" class="preview">' . $row['first_character_name'] . '</a>' : $row['first_character_name']
				);
			}
			if(!empty($row['last_id_character']))
			{
				unset($context['topics'][$row['id_topic']]['last_post']['member']);
				$context['topics'][$row['id_topic']]['last_post']['member'] = array(
					'username' => $row['last_character_name'],
					'name' => $row['last_character_name'],
					'id' => $row['last_id_character'],
					'href' => !empty($row['last_id_character']) ? $scripturl . '?action=character;c=' . $row['last_id_character'] : '',
					'link' => !empty($row['last_id_character']) ? '<a href="' . $scripturl . '?action=character;c=' . $row['last_id_character'] . '">' . $row['last_character_name'] . '</a>' : $row['last_character_name']
				);
			}
			
			if (!empty($row['character_avatar']))
			{

				$context['topics'][$row['id_topic']]['last_post']['member']['avatar'] = Role_Playing_System_Integrate::determineCharacterAvatar(array('avatar' => $row['character_avatar']));
			}
			if (!empty($row['character_avatar_first']))
			{
				$context['topics'][$row['id_topic']]['first_post']['member']['avatar'] = Role_Playing_System_Integrate::determineCharacterAvatar(array('avatar' => $row['character_avatar_first']));
			}
		}
	}
	
	public static function integrate_action_messageindex_after($test)
	{
		global $board_info, $context, $user_info;
		
		if ($board_info['in_character']) {
			$context['can_post_new'] &= !empty($user_info['characters']);
		}
	}
	
	public static function dateComparedFormat($date = null)
	{
		global $user_info;
		$current_dates = RpsCurrentDate::instance();
		$tag_date = new DateTime($date);
		
		//$format = 'j M Y';
		$format = $user_info['datetime_format'];
		if($tag_date->format('Y') == $current_dates->end_year)
			$format = $user_info['datetime_format_noyear']; //str_replace(array('y', 'Y'), '', $format);
		
		return $tag_date->format($format);
	}
}
