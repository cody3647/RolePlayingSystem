<?php

/**
 * Integration of characters and tags into display controller.
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
class RolePlayingSystem_Display_Module extends ElkArte\sources\modules\Abstract_Module
{
	/**
	 * @var Event_Manager
	 */
	protected static $_eventsManager = null;
	
	protected static $_inCharacter = false;

	/**
	 * {@inheritdoc }
	 */
	public static function hooks(\Event_Manager $eventsManager)
	{
		global $modSettings, $board_info;
		
		self::$_inCharacter = $board_info['in_character'];
		
		$return = array();
		if (!empty($modSettings['rps_enabled']) )
		{
			self::$_eventsManager = $eventsManager;

			$return = array(
				array('prepare_context', array('RolePlayingSystem_Display_Module', 'prepare_context'), array()),
				array('topicinfo', array('RolePlayingSystem_Display_Module', 'topicinfo'), array('topicinfo','context','user_info')),
			);
		}

		return $return;
	}
	
	public static function integrate_message_query(&$msg_selects, &$msg_tables, &$msg_parameters) {
		$msg_selects[] = 'm.id_character';
		
	
	}

	public static function integrate_prepare_display_context( &$output, &$message) {
		global $memberContext;

		$overrides = array('name','title','avatar','signature', 'custom_fields','href', 'posts');
		
		if (empty($output['member']['member'])) {
			foreach($overrides as $key)
				$output['member']['member'][$key] = $memberContext[$message['id_member']][$key];
		}
		
		else {
			foreach($overrides as $key)
				$memberContext[$message['id_member']][$key] = $output['member']['member'][$key];
		}
		
		if (!empty($message['id_character'])) {
			foreach($overrides as $key)
				$output['member'][$key] = empty($memberContext[$message['id_member']]['characters'][$message['id_character']][$key]) ? '' : $memberContext[$message['id_member']]['characters'][$message['id_character']][$key];
		}
	}
	
	public static function integrate_display_buttons()
	{
		global $context, $scripturl;
		
		$tag_button =  array(
			'rps_tags' => array(
				//'test' => 'can_reply',
				'text' => 'rps_tag_edit',
				'image' => 'reply.png',
				'lang' => true,
				'url' => $scripturl . '?action=tags;topic=' . $context['current_topic'],
			)
		);
			
		$context['normal_buttons'] = elk_array_insert($context['normal_buttons'], 'reply', $tag_button, 'after');
	}
	
	//$topicinfo is set in the query based on select name
	public static function integrate_topic_query(&$topic_selects, &$topic_tables, &$topic_parameters)
	{
		$topic_selects = array_merge(
			$topic_selects, 
			array('t.date_tag', 'YEAR(t.date_tag) as year_tag', 'MONTH(t.date_tag) as month_tag','DAYOFMONTH(t.date_tag) as day_tag')
		);
		$topic_tables = array_merge(
			$topic_tables, 
			array()
		);
	}
	
	public function prepare_context()
	{
		global $context, $user_info;
		
		if(self::$_inCharacter)
			$context['can_reply'] &= !empty($user_info['characters']);
	}
	
	public function topicinfo(&$topicinfo, &$context, $user_info)
	{
		$db = database();
		
		$request = $db->query('', '
			SELECT t.tag, t.id_tag
			FROM {db_prefix}rps_tags_data AS td
			LEFT JOIN {db_prefix}rps_tags AS t ON (t.id_tag = td.id_tag)
			WHERE id_topic = {int:id_topic}',
			array(
				'id_topic' => $topicinfo['id_topic'],
			)
		);
		$context['tags'] = array();
		while ($row = $db->fetch_assoc($request))
			$context['tags'][$row['id_tag']] = un_htmlspecialchars($row['tag']);
		$db->free_result($request);
		if(!empty($topicinfo['date_tag']))
		{
			$date = new DateTime($topicinfo['date_tag']);
			$context['date_tag'] = $date->format($user_info['datetime_format']);
			$context['year_tag'] = $topicinfo['year_tag'];
			$context['month_tag'] = $topicinfo['month_tag'];
			$context['day_tag'] = $topicinfo['day_tag'];
		}

		loadTemplate('RolePlayingSystem');
	}

}
