<?php

/**
 * This file contains several functions for retrieving and manipulating calendar events, birthdays and holidays.
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

/**
 * This class's task is to bind the posting of a topic to a calendar event.
 * Used when from the calendar controller the poster is redirected to the post page.
 *
 * @package Calendar
 */
class RolePlayingSystem_Admin_Module extends ElkArte\sources\modules\Abstract_Module
{
	/**
	 * {@inheritdoc}
	 */
	public static function hooks(\Event_Manager $eventsManager)
	{
		loadLanguage('RolePlayingSystemAdmin');
		
		return array(
			array('addMenu', array('RolePlayingSystem_Admin_Module', 'addMenu'), array()),
			//array('addSearch', array('RolePlayingSystem_Admin_Module', 'search'), array()),
		);
	}

	/**
	 * Used to add the Calendar entry to the admin menu.
	 *
	 * @param mixed[] $admin_areas The admin menu array
	 */
	public function addMenu(&$admin_areas)
	{
		global $txt, $context, $modSettings;

		$rps_menu['rps'] = array(
				'label' => $txt['rps_title'],
				'controller' => 'ManageRolePlayingSystemModule_Controller',
				'function' => 'action_index',
				'icon' => 'transparent.png',
				'class' => 'admin_img_features',
				'permission' => array('admin_rps'),
				'enabled' => in_array('rps', $context['admin_features']),
				'subsections' => array(
					'settings' => array($txt['rps_settings'], 'admin_rps'),
					'events' => array($txt['rps_events'], 'admin_rps', 'active' => array('editevent')),
					'fields' => array($txt['rps_fields'], 'admin_rps', 'active' => array('fieldedit')),
					'download' => array($txt['rps_download'], 'admin_rps')
				),
			);

		$admin_areas['config']['areas'] = elk_array_insert($admin_areas['config']['areas'], 'addonsettings', $rps_menu, 'before');
	}

	/**
	 * Used to add the Calendar entry to the admin search.
	 *
	 * @param string[] $language_files
	 * @param string[] $include_files
	 * @param mixed[] $settings_search
	 
	 
	public function search(&$language_files, &$include_files, &$settings_search)
	{
		$language_files[] = 'ManageCharacters';
		$include_files[] = 'ManageCharacters.controller';
		$settings_search[] = array('settings_search', 'area=managecharacters', 'ManageCharacters_Controller');
	}
	*/
	
	public static function integrate_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
	{
		global $context;
		loadLanguage('RolePlayingSystemAdmin');
		
		$permissionGroups['membergroup'][] = 'rps';
		/*   The format of this list is as follows:
			'membergroup' => array(
				'permissions_inside' => array(has_multiple_options, view_group),
			),
			'board' => array(
				'permissions_inside' => array(has_multiple_options, view_group),
			);
		*/
		
		if (isset($context['group']['id']) && $context['group']['id'] == -1)
			$rpsPermissions = array(
				'rps_char_view' => array(false, 'rps'),
			);
		else
			$rpsPermissions = array(
				'rps_create_char' => array(false, 'rps'),
				'rps_char_view' => array(false, 'rps'),
				'rps_char_edit' => array(true, 'rps'),
				'rps_char_title' => array(true, 'rps'),
				'rps_char_set_avatar' => array(true, 'rps'),
				'rps_add_tags' => array(true, 'rps'),
				'rps_remove_tags' => array(true, 'rps'),
			);
		$permissionList['membergroup'] = array_merge($permissionList['membergroup'], $rpsPermissions);
	}
	
	public static function integrate_edit_board()
	{
		global $context;
		loadLanguage('RolePlayingSystemAdmin');
		loadTemplate('RolePlayingSystem');
		
		$_req = HttpReq::instance();
		
		if ($_req->query->sa == 'newboard')
		{
			$context['board']['in_character']= true;
		}
	}
	
	public static function integrate_board_tree_query(&$query)
	{
		$query['select'] = ', b.in_character';
	}

	public static function integrate_board_tree($row)
	{
		global $boards;
		
		if (!empty($row['id_board']))
		{
			$boards[$row['id_board']]['in_character'] = !empty($row['in_character']);
		}
	}
	
	public static function integrate_save_board($board_id, &$boardOptions)
	{
		$_req = HttpReq::instance();
		$boardOptions['in_character'] = isset($_req->post->in_character);
	}
	
	public static function integrate_modify_board($board_id, $boardOptions, &$boardUpdates, &$boardUpdateParameters)
	{
			// This setting is a little twisted in the database...
		if (isset($boardOptions['in_character']))
		{
			$boardUpdates[] = 'in_character = {int:in_character}';
			$boardUpdateParameters['in_character'] = $boardOptions['in_character'] ? 1 : 0;
		}
	}
}