<?php
/**
 * All integration hooks called, as well as methods for integrations needed across multiple controllers.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

class Role_Playing_System_Integrate
{
	/**
	 * Registers hooks as needed for the drafts function to work
	 * @return array
	 */
	public static function register()
	{		
		// $hook, $function, $file
		return array(
			array('integrate_log_types', 'Role_Playing_System_Integrate::integrate_log_types'),
			array('integrate_quickhelp', 'Role_Playing_System_Integrate::integrate_quickhelp'),
			array('integrate_menu_buttons', 'Role_Playing_System_Integrate::integrate_menu_buttons'),
			array('integrate_profile_summary', 'Role_Playing_System_Integrate::integrate_profile_summary'),
			array('integrate_pre_load', 'Role_Playing_System_Integrate::integrate_pre_load'),
			array('integrate_user_info', 'Role_Playing_System_Integrate::integrate_user_info'),
			array('integrate_load_board_query', 'Role_Playing_System_Integrate::integrate_load_board_query'),
			array('integrate_loaded_board', 'Role_Playing_System_Integrate::integrate_loaded_board'),
			array('integrate_add_member_data', 'Role_Playing_System_Integrate::integrate_add_member_data'),
			array('integrate_member_context', 'Role_Playing_System_Integrate::integrate_member_context'),
			array('integrate_sa_xmlhttp', 'Role_Playing_System_Integrate::integrate_sa_xmlhttp'),
			array('integrate_sa_xmlpreview', 'Role_Playing_System_Integrate::integrate_sa_xmlpreview'),
			
			array('integrate_before_create_post', 'RolePlayingSystem_Post_Module::integrate_before_create_post'),
			array('integrate_before_create_topic', 'RolePlayingSystem_Post_Module::integrate_before_create_topic'),
			array('integrate_before_modify_topic', 'RolePlayingSystem_Post_Module::integrate_before_modify_topic'),
			array('integrate_before_modify_post', 'RolePlayingSystem_Post_Module::integrate_before_modify_post'),
			array('integrate_create_post', 'RolePlayingSystem_Post_Module::integrate_create_post'),
			
			array('integrate_message_query','RolePlayingSystem_Display_Module::integrate_message_query'),
			array('integrate_prepare_display_context', 'RolePlayingSystem_Display_Module::integrate_prepare_display_context'),
			array('integrate_display_buttons', 'RolePlayingSystem_Display_Module::integrate_display_buttons'),
			array('integrate_topic_query', 'RolePlayingSystem_Display_Module::integrate_topic_query'),
			
			array('integrate_messageindex_topics', 'RolePlayingSystem_MessageIndex_Module::integrate_messageindex_topics'),
			array('integrate_messageindex_listing', 'RolePlayingSystem_MessageIndex_Module::integrate_messageindex_listing'),
			array('integrate_action_messageindex_after', 'RolePlayingSystem_MessageIndex_Module::integrate_action_messageindex_after'),
			array('integrate_tagindex_topics', 'RolePlayingSystem_MessageIndex_Module::integrate_messageindex_topics'),
			array('integrate_tagindex_listing', 'RolePlayingSystem_MessageIndex_Module::integrate_messageindex_listing'),
			array('integrate_action_tagindex_after', 'RolePlayingSystem_MessageIndex_Module::integrate_action_messageindex_after'),
			
			//array('integrate_moderation_areas', 'RolePlayingSystem_Admin_Module::integrate_moderation_areas'),
			
		);
	}
	
	/**
	 * Returns the config settings from the RolePlayingSettings module
	 *
	 * @return array
	 */
	public static function settingsRegister()
	{
		return array(
			array('integrate_edit_board', 'RolePlayingSystem_Admin_Module::integrate_edit_board'),
			array('integrate_board_tree_query', 'RolePlayingSystem_Admin_Module::integrate_board_tree_query'),
			array('integrate_board_tree', 'RolePlayingSystem_Admin_Module::integrate_board_tree'),
			array('integrate_save_board', 'RolePlayingSystem_Admin_Module::integrate_save_board'),
			array('integrate_modify_board', 'RolePlayingSystem_Admin_Module::integrate_modify_board'),
			//array('integrate_load_illegal_guest_permissions', 'RolePlayingSystem_Admin_Module::integrate_load_illegal_guest_permissions'),
			array('integrate_load_permissions', 'RolePlayingSystem_Admin_Module::integrate_load_permissions'),
			array('integrate_routine_maintenance', 'RolePlayingSystem_Admin_module::integrate_routine_maintenance'),
		);
	}
	
	/**
	 * Adds a log_type, Called in sources/Logging.php logActions
	 *
	 * @param array $log_types
	 */
	
	public static function integrate_log_types(&$log_types) {
		$log_types += array(
			'character'=> 20,
			);
	}
	
	/**
	 * Loads the RolePlayingSystemAdmin language file for controllers/Help.controller.php action_quickhelp()
	 *
	 */
	public static function integrate_quickhelp() {
		loadLanguage('RolePlayingSystemAdmin');
	}
	
	
	/**
	 * Adds RolePlayingSystem links to the menu, Called in themes' Theme.php setupMenuContext()
	 *
	 * @param array $buttons
	 * @param array $menu_count
	 */
	public static function integrate_menu_buttons(&$buttons, &$menu_count)
	{
		global $scripturl, $txt, $context, $user_info;
		
		loadLanguage('RolePlayingSystem');
		loadCSSFile('RolePlayingSystem/rps.css');
		
		//Main game button
		$rps_buttons = array(
			'rps' => array(
				'title' => $txt['rps_game'],
				'data-icon' => 'i-dice',
				'href' => '#',
				'show' => true,
				'sub_buttons' => array(
					'create' => array(
						'title' => $txt['rps_create'],
						'href' => $scripturl . '?action=character;sa=create',
						'show' => allowedTo('rps_char_create'),
					),
					'tags' => array(
						'title' => $txt['rps_tags'],
						'href' => $scripturl . '?action=tags',
						'show' => true,
					),
					'characters' => array(
						'title' => $txt['rps_characters'],
						'href' => $scripturl . '?action=characterlist',
						'show' => allowedTo('rps_charlist_view'),
					),
					'Gamecalendar' => array(
						'title' => $txt['rps_gamecalendar'],
						'href' => $scripturl . '?action=gamecalendar',
						'show' => true,
					),
				),
			)
		);
		
		$buttons = elk_array_insert($buttons, 'home', $rps_buttons, 'after');
		
		//Add quick link to RolePlayingSystem settings under Admin
		if ($context['allow_admin'])
		{
			$rps_admin['rps'] = array(
				'title' => $txt['rps'],
				'href' => $scripturl . '?action=admin;area=rps',
				'show' => allowedTo('admin_rps'),
				'counter' => 'rps',
			);
			
			$buttons['admin']['sub_buttons'] = elk_array_insert($buttons['admin']['sub_buttons'], 'errorlog', $rps_admin, 'after');
		}
		
		//Adds character links to the Account button
		if (!empty($user_info['characters']))
		{
			$characters['characters'] = array(
				'title' => $txt['rps_account_characters'],
				'href' => '',
				'show' => true,
				);
			foreach($user_info['characters'] as $character)
			{
				$characters['characters']['sub_buttons'][$character['name']] = array(
					'title' => $character['name'],
					'href' => $scripturl . '?action=character;c=' . $character['id'],
					'show' => true,
				);
			}
			$buttons['profile']['sub_buttons'] = elk_array_insert($buttons['profile']['sub_buttons'], 'account', $characters, 'after');
		}
		
		//If there are bios or characters to approve, add the number to Admin and to Role Playing System buttons
		if(allowedTo('moderate_forum'))
		{
			require_once(SUBSDIR . '/ManageCharacters.subs.php');
			$characters = list_num_unapproved_characters();
			$bios = list_num_unapproved_biographies();
			
			$total = $characters + $bios;
			
			if(!empty($characters))
			{
				$context['warning_controls']['rps_character'] = sprintf($txt[$characters == 1 ? 'rps_one_character_waiting' : 'rps_many_characters_waiting'], $scripturl . '?action=admin;area=rps;sa=characters', $characters);
			}
			
			if(!empty($bios))
			{
				$context['warning_controls']['rps_bio'] = sprintf($txt[$bios == 1 ? 'rps_one_bio_waiting' : 'rps_many_bios_waiting'], $scripturl . '?action=admin;area=rps;sa=bios', $bios);
			}
			
			if(!empty($total))
			{
				$menu_count['grand_total'] = $menu_count['grand_total'] + $total;
				$menu_count['rps'] = $total;
			}
			
			
			
			
		}
	}
	
	/**
	 * Adds Character name and ID to $user_info. Called in sources/Load.php loadUserSettings()
	 * 
	 * Also adds $user_info['datetime_format'] and $user_info['datetime_format_noyear']
	 */
	
	public static function integrate_user_info()
	{
		global $user_info;

		$db = database();
		
		$request = $db->query('', '
			SELECT id_character, name
			FROM {db_prefix}rps_characters
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $user_info['id'],
			)
		);
		$user_info['characters'] = array();
		while ($row = $db->fetch_assoc($request))
		{
			$user_info['characters'][] = array(
				'id' => $row['id_character'],
				'name' => $row['name'],
				);
		}
		$db->free_result($request);
		
		$search = 	array('/%a/','/%A/','/%b/',	'/%B/','/%d/','/%D/',	'/%e/','/%m/','/%[y|Y]/',	'/%\w/','/[[:punct:]\s]{2,}$/');
		$replace = 	array('D', 'l', 'M',		'F', 'd', 'm/d/Y',		'j', 'm', 'Y' );
		
		$user_info['datetime_format'] = preg_replace($search, $replace, $user_info['time_format']);
		$user_info['datetime_format_noyear'] = preg_replace('/[[:punct:][:space:]]{0,2}[Y|y][[:punct:]]{0,1}/', '', $user_info['datetime_format']);
	}
	
	/**
	 * Adds the in_character column to the load board query, Called in sources/Load.php loadBoard()
	 *
	 * @param array $select_columns
	 * @param array $select_tables
	 */
	
	public static function integrate_load_board_query(&$select_columns, &$select_tables)
	{
		$select_columns = array_merge($select_columns, array('b.in_character'));
	}
	
	/**
	 * Add in_character to board_info, Called in sources/Load.php loadBoard()
	 *
	 * @param array $board_info
	 * @param array $row
	 */
	 
	public static function integrate_loaded_board(&$board_info, &$row)
	{
		$board_info['in_character'] = $row['in_character'];
	}
	
	/**
	 * Adds characters info to $user_profile, Called in sources/Load.php loadMemberData()
	 *
	 * @param array $new_loaded_ids
	 * @param string $set
	 */
	
	public static function integrate_add_member_data ($new_loaded_ids, $set)
	{
		global $user_profile;

		$db = database();

		$request = $db->query('', '
			SELECT *
			FROM {db_prefix}rps_characters
			WHERE id_member' . (count($new_loaded_ids) == 1 ? ' = {int:loaded_ids}' : ' IN ({array_int:loaded_ids})'),
			array(
				'loaded_ids' => count($new_loaded_ids) == 1 ? $new_loaded_ids[0] : $new_loaded_ids,
			)
		);
		while ($row = $db->fetch_assoc($request))
			$user_profile[$row['id_member']]['characters'][$row['id_character']] = array(
				'id' => $row['id_character'],
				'name' => $row['name'],
				'avatar' => $row['avatar'],
				'signature' => $row['signature'],
				'birthdate' => $row['birthdate'],
				'title' => $row['title'],
				'posts' => $row['posts'],
				'date_created' => $row['date_created'],
				'last_active' => $row['last_active'],
				'approved' => $row['approved'],
				'retired' => $row['retired'],
				'gender' => $row['gender'],
				'personal_text' => $row['personal_text'],
				'location' => $row['location'],
			);
		$db->free_result($request);
		
	}
	
	/**
	 * Adds characters context to $memberContext, Called in sources/Load.php loadMemberContext()
	 *
	 * @param array $user
	 * @param array $display_custom_fields
	 */

	public static function integrate_member_context($user, $display_custom_fields)
	{
		global $memberContext, $user_profile, $scripturl, $txt, $modSettings, $settings;
		
		$parsers = \BBC\ParserWrapper::instance();
		
		if (!empty($user_profile[$user]['characters']))
		{
			$characters = $user_profile[$user]['characters'];
			
			foreach($characters as $id => $profile)
			{
				$profile['signature'] = str_replace(array("\n", "\r"), array('<br />', ''), $profile['signature']);
				$profile['signature'] = $parsers->parseSignature($profile['signature'], true);
				
				$memberContext[$user]['characters'][$id] = array(
					'id' => $profile['id'],
					'name' => $profile['name'],
					'href' => $scripturl . '?action=character;c=' . $profile['id'],
					'link' => '<a href="' . $scripturl . '?action=character;c=' . $profile['id'] . '" title="' . $txt['profile_of'] . ' ' . trim($profile['name']) . '">' . $profile['name'] . '</a>',
					'created_raw' => empty($profile['date_created']) ? 0 : $profile['date_created'],
					'created' => empty($profile['date_created']) ? $txt['not_applicable'] : standardTime($profile['date_created']),
					'created_timestamp' => empty($profile['date_created']) ? 0 : forum_time(true, $profile['date_created']),
					
					'avatar' => self::determineCharacterAvatar($profile),
					'signature' => $profile['signature'],
					'title' => $profile['title'],
					'birth_date' => $profile['birthdate'],

					'posts' => comma_format($profile['posts']),
					'real_posts' => $profile['posts'],
					'last_active' => empty($profile['last_active']) ? $txt['never'] : standardTime($profile['last_active']),
					'last_active_timestamp' => empty($profile['last_active']) ? 0 : forum_time(false, $profile['last_active']),
					'approved' => $profile['approved'],
					'retired' => $profile['retired'],
					
					//cust field replacements
					'gender' => $profile['gender'],
					'location' => $profile['location'],
					'personal_text' => $profile['personal_text'],
				);
			}
		}
			
	}
	
	/**
	 * Adds character section to member profile, Called in controllers/ProfileInfo.controller.php _register_summarytabs()
	 *
	 * @param array $memID
	 */
	
	public static function integrate_profile_summary($memID)
	{
		global $context;

        loadTemplate('RpsCharacterInfo');
		
		$context['summarytabs']['summary']['templates'] = elk_array_insert($context['summarytabs']['summary']['templates'], 0, array('rps_characters'), 'after');
	}

	
	/**
	 * Adds signature and biography preview xml response subactions, Called in controllers/XmlPreview.controller.php action_index()
	 *
	 * @param array $subActions
	 */
	
	public static function integrate_sa_xml_preview(&$subActions)
	{
		
		$subActions['character_signature'] = array(
			'controller' => 'Characters_Controller',
			'function' => 'action_sig_preview',
		);
		
		$subActions['character_biography'] = array(
			'controller' => 'CharacterBiography_Controller',
			'function' => 'action_biography_preview',
		);
		
	}
	
	/**
	 * Determine the characters's avatar type and return the information as an array
	 *
	 * @param mixed[] $profile array containing the users profile data
	 * @return mixed[] $avatar
	 */
	public static function determineCharacterAvatar($profile)
	{
		global $modSettings, $scripturl, $settings;

		if (empty($profile))
			return array();

		$avatar_protocol = substr(strtolower($profile['avatar']), 0, 7);

	
		// remote avatar?
		if ($avatar_protocol === 'http://' || $avatar_protocol === 'https:/')
		{
			$avatar = array(
				'name' => $profile['avatar'],
				'image' => '<img class="avatar avatarresize" src="' . $profile['avatar'] . '" alt="" />',
				'href' => $profile['avatar'],
				'url' => $profile['avatar'],
			);
		}
		// an avatar from the gallery?
		elseif (!empty($profile['avatar']) && !($avatar_protocol === 'http://' || $avatar_protocol === 'https:/'))
		{
			$avatar = array(
				'name' => $profile['avatar'],
				'image' => '<img class="avatar avatarresize" src="' . $modSettings['avatar_url'] . '/' . $profile['avatar'] . '" alt="" />',
				'href' => $modSettings['avatar_url'] . '/' . $profile['avatar'],
				'url' => $modSettings['avatar_url'] . '/' . $profile['avatar'],
			);
		}
		// no custom avatar found yet, maybe a default avatar?
		elseif (!empty($modSettings['avatar_default']) && empty($profile['avatar']) && empty($profile['filename']))
		{
			// $settings not initialized? We can't do anything further..
			if (!empty($settings))
			{
				// Let's proceed with the default avatar.
				// TODO: This should be incorporated into the theme.
				$avatar = array(
					'name' => '',
					'image' => '<img class="avatar avatarresize" src="' . $settings['images_url'] . '/default_avatar.png" alt="" />',
					'href' => $settings['images_url'] . '/default_avatar.png',
					'url' => 'http://',
				);
			}
			else
			{
				$avatar = array();
			}
		}
		// finally ...
		else
			$avatar = array(
				'name' => '',
				'image' => '',
				'href' => '',
				'url' => ''
			);

		call_integration_hook('integrate_avatar', array(&$avatar, $profile));

		return $avatar;
	}
}