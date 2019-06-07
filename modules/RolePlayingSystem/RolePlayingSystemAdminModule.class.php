<?php

/**
 * Role Playing System admin menu and admin integration hooks.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Class RolePlayingSystem Admin Module
 * 
 * Events and functions for adding RolePlayingSystem to Admin
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
	 * Used to add the RolePlayingSystem entry to the admin menu.
	 *
	 * Event triggered in admin/Admin.controller.php
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
					'characters' => array($txt['rps_manage_characters'], 'admin_rps'),
					'bios' => array($txt['rps_manage_bios'], 'admin_rps'),
					'tags' => array($txt['rps_manage_tags'], 'admin_rps'),
					'events' => array($txt['rps_events'], 'admin_rps', 'active' => array('editevent')),
					'phases' => array($txt['rps_phases'], 'admin_rps', 'active' => array('editphase')),
					'download' => array($txt['rps_download'], 'admin_rps')
				),
			);

		$admin_areas['config']['areas'] = elk_array_insert($admin_areas['config']['areas'], 'addonsettings', $rps_menu, 'before');
	}
	
	public static function integrate_load_illegal_guest_permissions()
	{
		//Placeholder for if the hook ever does anything useful.
	}

    /**
     * Used to add RolePlayingSystem Permissions
	 *
	 * Called in admin/ManagePermissions.subs.php loadAllPermissions()
     *
     * @param array $permissionGroups
     * @param array $permissionList
     * @param array $leftPermissionGroups
     * @param array $hiddenPermissions
     * @param array $relabelPermissions
     */
	
	public static function integrate_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
	{
		global $context;
		
		$permissionGroups['membergroup'][] = 'rps';
		
		/*   The format of this list is as follows:
			'membergroup' => array(
				'permissions_inside' => array(has_multiple_options, view_group),
			),
			'board' => array(
				'permissions_inside' => array(has_multiple_options, view_group),
			);
		*/
		
		//Only allow the guest membergroup to be given the ability to see character profiles
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
				'rps_char_set_avatar' => array(false, 'rps'),
				'rps_add_tags' => array(true, 'rps'),
				'rps_remove_tags' => array(true, 'rps'),
				'rps_bio_approved' => array(false, 'rps')
			);
		$permissionList['membergroup'] = array_merge($permissionList['membergroup'], $rpsPermissions);
	}
	
	/**
	 * Defaults new boards to be In Character boards
	 * 
	 * Called in controllers/ManageBoards.controller.php
	 *
	 */
	
	public static function integrate_edit_board()
	{
		global $context;
		//loadLanguage('RolePlayingSystemAdmin');
		loadTemplate('RolePlayingSystem');
		
		$req = HttpReq::instance();
		
		if ($req->query->sa == 'newboard')
		{
			$context['board']['in_character']= true;
		}
	}
	
	/**
	 * Adds in_character column to the board tree query
	 *
	 * Called in subs/Boards.subs.php getBoardTree()
	 *
	 * @param array $query
	 */
	
	public static function integrate_board_tree_query(&$query)
	{
		$query['select'] = ', b.in_character';
	}
	
	/**
	 * Processes the in_character column
	 *
	 * Called in subs/Boards.subs.php getBoardTree()
	 *
	 * @param array $row
	 */

	public static function integrate_board_tree($row)
	{
		global $boards;
		
		if (!empty($row['id_board']))
		{
			$boards[$row['id_board']]['in_character'] = !empty($row['in_character']);
		}
	}
	
	/**
	 * Adds in_character to board options to be saved
	 *
	 * Called in admin/ManageBoards.controller.php action_board2()
	 *
	 * @param int $board_id
	 * @param mixed[] $boardOptions
	 *
	 */
	
	public static function integrate_save_board($board_id, &$boardOptions)
	{
		$req = HttpReq::instance();
		$boardOptions['in_character'] = isset($req->post->in_character);
	}
	
	/**
	 * Adds in_character to the database updates
	 * 
	 * Called in subs/Boards.subs.php modifyBoard()
	 *
	 * @param int $board_id
	 * @param mixed[] $boardOptions
	 * @param array $boardUpdates
	 * @param $boardUpdateParameters
	 *
	 */
	
	public static function integrate_modify_board($board_id, $boardOptions, &$boardUpdates, &$boardUpdateParameters)
	{
		if (isset($boardOptions['in_character']))
		{
			$boardUpdates[] = 'in_character = {int:in_character}';
			$boardUpdateParameters['in_character'] = $boardOptions['in_character'] ? 1 : 0;
		}
	}
}
