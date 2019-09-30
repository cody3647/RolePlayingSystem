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
 * Class RolePlayingSystem Display Module
 *
 * Hooks and events for Displaying Characters instead of Members
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
			
			$modSettings['rps_cf_overrides'] = unserialize($modSettings['rps_cf_overrides']);
		}

		return $return;
	}
	
	/**
	 * Adds the id_characgter column to the message query, Called in controller/Display.controller.php action_display()
	 *
	 * @param array $msg_selects
	 * @param array $msg_tables
	 * @param array $msg_parameters
	 */
	
	public static function integrate_message_query(&$msg_selects, &$msg_tables, &$msg_parameters) {
		$msg_selects[] = 'm.id_character';
		
	
	}
	
	/**
	 * Overrides member with character for display, Called in controller/Display.controller.php prepareDisplayContext_callback()
	 *
	 * @param array $output
	 * @param array $message
	 */

	public static function integrate_prepare_display_context( &$output, &$message)
	{
		global $memberContext, $modSettings;

		$overrides = array('name','title','avatar','signature','href', 'posts');
		$cf_overrides = array('cust_gender' => 'gender', 'cust_locate' => 'cust_locate', 'cust_blurb' => 'personal_text');
		
		if (empty($output['member']['member']))
		{
			foreach($overrides as $key)
				$output['member']['member'][$key] = $memberContext[$message['id_member']][$key];
		}
		
		else
		{
			foreach($overrides as $key)
				$memberContext[$message['id_member']][$key] = $output['member']['member'][$key];
		}
		
		//standard field replacements
		if (!empty($message['id_character']))
		{
			foreach($overrides as $key)
				$output['member'][$key] = empty($memberContext[$message['id_member']]['characters'][$message['id_character']][$key]) ? '' : $memberContext[$message['id_member']]['characters'][$message['id_character']][$key];
			
			if(empty($output['member']['signature'])
				$output['member']['signature'] = $$output['member']['member']['signature'];
			
			//custom field replacement
			foreach($output['member']['custom_fields'] as $key => &$field)
			{
				if(in_array($field['colname'], $modSettings['rps_cf_overrides']))
				{
					if(!empty($memberContext[$message['id_member']]['characters'][$message['id_character']][$cf_overrides[$field['colname']]]))
					{
						$field['value'] =  $memberContext[$message['id_member']]['characters'][$message['id_character']][$cf_overrides[$field['colname']]];
						
						if($field['colname'] == 'cust_gender')
						{
							$field['value'] =  '<i class="icon i-'. $field['value'] .'" title="'. $field['value'] .'"><s>'. $field['value'] .'</s></i>';
						}
					}
					else
					{
						unset($output['member']['custom_fields'][$key]);
					}
				}
			}
		}
	}
	
	/**
	 * Adds edit tags button to thread, Called in controllers/Display.controller.php action_display()
	 *
	 */
	
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
	
	/**
	 * Adds date tag column to the topic query,  controllers/Display.controller.php action_display()
	 *
	 * @param array $topic_selects
	 * @param array $topic_tables
	 * @param array $topic_parameter
	 */
	
	public static function integrate_topic_query(&$topic_selects, &$topic_tables, &$topic_parameters)
	{
		//$topicinfo is set in the query based on select name
		$topic_selects = array_merge(
			$topic_selects, 
			array('t.date_tag', 'YEAR(t.date_tag) as year_tag', 'MONTH(t.date_tag) as month_tag','DAYOFMONTH(t.date_tag) as day_tag')
		);
		$topic_tables = array_merge(
			$topic_tables, 
			array()
		);
	}
	
	/**
	 * Only allow a member to reply on in character board if they have characters, event trigger in controller/Display.controller.php
	 *
	 */
	
	public function prepare_context()
	{
		global $context, $user_info;
		
		if(self::$_inCharacter)
			$context['can_reply'] &= !empty($user_info['characters']);
	}
	
	/**
	 * Adds the in_character column to the load board query, event trigger in controller/Display.controller.php
	 *
	 * @param array $topicinfo
	 * @param array $context
	 * @param array $user_info
	 */
	
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
