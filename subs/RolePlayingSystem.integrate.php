<?php

class Role_Playing_System_Integrate
{
	public static function register()
	{
		loadLanguage('RolePlayingSystem');
		loadCSSFile('RolePlayingSystem/rps.css');
		// $hook, $function, $file
		return array(
			array('integrate_log_types', 'Role_Playing_System_Integrate::integrate_log_types'),
			array('integrate_quickhelp', 'Role_Playing_System_Integrate::integrate_quickhelp'),
			array('integrate_menu_buttons', 'Role_Playing_System_Integrate::integrate_menu_buttons'),
		
//			array('integrate_profile_areas', 'Role_Playing_System_Integrate::integrate_profile_areas'),
			array('integrate_profile_summary', 'Role_Playing_System_Integrate::integrate_profile_summary'),
			
			//Load.php
			array('integrate_user_info', 'Role_Playing_System_Integrate::integrate_user_info'),
			array('integrate_load_board_query', 'Role_Playing_System_Integrate::integrate_load_board_query'),
			array('integrate_loaded_board', 'Role_Playing_System_Integrate::integrate_loaded_board'),
			array('integrate_add_member_data', 'Role_Playing_System_Integrate::integrate_add_member_data'),
			array('integrate_member_context', 'Role_Playing_System_Integrate::integrate_member_context'),

			array('integrate_sa_xmlhttp', 'Role_Playing_System_Integrate::integrate_sa_xmlhttp'),

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
		);
	}
	
	public static function settingsRegister()
	{
		return array(
			array('integrate_edit_board', 'RolePlayingSystem_Admin_Module::integrate_edit_board'),
			array('integrate_board_tree_query', 'RolePlayingSystem_Admin_Module::integrate_board_tree_query'),
			array('integrate_board_tree', 'RolePlayingSystem_Admin_Module::integrate_board_tree'),
			array('integrate_save_board', 'RolePlayingSystem_Admin_Module::integrate_save_board'),
			array('integrate_modify_board', 'RolePlayingSystem_Admin_Module::integrate_modify_board'),
			array('integrate_load_permissions', 'RolePlayingSystem_Admin_Module::integrate_load_permissions'),
		);
	}
	
	public static function integrate_log_types(&$log_types) {
		$log_types += array(
			'character'=> 20,
			);
	}
	
	public static function integrate_quickhelp() {
		loadLanguage('RolePlayingSystemAdmin');
	}
	
	public static function integrate_menu_buttons(&$buttons, &$menu_count)
	{
		global $scripturl, $txt, $context;

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
						'show' => true,
					),
					'tags' => array(
						'title' => $txt['rps_tags'],
						'href' => $scripturl . '?action=tags',
						'show' => true,
					),
					'characters' => array(
						'title' => $txt['rps_characters'],
						'href' => $scripturl . '?action=characterlist',
						'show' => true,
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
		if ($context['allow_admin'])
		{
			$rps_admin['rps'] = array(
				'title' => $txt['rps'],
				'href' => $scripturl . '?action=admin;area=rps',
				'show' => allowedTo('admin_rps'),
			);
			
			$buttons['admin']['sub_buttons'] = elk_array_insert($buttons['admin']['sub_buttons'], 'errorlog', $rps_admin, 'after');
		}
	}
	
	public static function integrate_user_info()
	{
		global $user_info, $context;

		$db = database();
		
		$request = $db->query('', '
			SELECT id_character
			FROM {db_prefix}rps_characters
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $user_info['id'],
			)
		);
		$user_info['characters'] = array();
		while ($row = $db->fetch_assoc($request))
			$user_info['characters'][] = $row['id_character'];
		$db->free_result($request);
		
		$search = 	array('/%a/','/%A/','/%b/',	'/%B/','/%d/','/%D/',	'/%e/','/%m/','/%[y|Y]/',	'/%\w/','/[[:punct:]\s]{2,}$/');
		$replace = 	array('D', 'l', 'M',		'F', 'd', 'm/d/Y',		'j', 'm', 'Y' );
		
		$user_info['datetime_format'] = preg_replace($search, $replace, $user_info['time_format']);
		$user_info['datetime_format_noyear'] = preg_replace('/[[:punct:][:space:]]{0,2}[Y|y][[:punct:]]{0,1}/', '', $user_info['datetime_format']);
	}
	
	public static function integrate_load_board_query(&$select_columns, &$select_tables)
	{
		$select_columns = array_merge($select_columns, array('b.in_character'));
	}
	
	public static function integrate_loaded_board(&$board_info, &$row)
	{
		$board_info['in_character'] = $row['in_character'];
	}
	
	public static function integrate_add_member_data ($new_loaded_ids, $set)
	{
		global $user_profile, $txt;

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
				'gender' => $row['title'],
				'personal_text' => $row['title'],
				'posts' => $row['posts'],
				'date_created' => $row['date_created'],
				'last_active' => $row['last_active'],
				'main_group' => $row['main_group'],
				'approved' => $row['approved'],
				'retired' => $row['retired'],
			);
		$db->free_result($request);
		
		if ( $set !== 'minimal' )
		{
			$request = $db->query('', '
				SELECT d.id_character, d.variable, d.value, c.id_member 
				FROM {db_prefix}rps_character_fields_data AS d 
				LEFT JOIN {db_prefix}rps_characters AS c ON c.id_character = d.id_character 			
				WHERE c.id_member' . (count($new_loaded_ids) == 1 ? ' = {int:loaded_ids}' : ' IN ({array_int:loaded_ids})'),
				array(
					'loaded_ids' => count($new_loaded_ids) == 1 ? $new_loaded_ids[0] : $new_loaded_ids,
				)
			);
			while ($row = $db->fetch_assoc($request))
				$user_profile[$row['id_member']]['characters'][$row['id_character']]['options'][$row['variable']] = $row['value'];
			$db->free_result($request);
		}
		
	}

	public static function integrate_member_context($user, $display_custom_fields)
	{
		global $memberContext, $user_profile, $scripturl, $txt, $modSettings, $settings;
		
		$parsers = \BBC\ParserWrapper::getInstance();
		
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
					'gender' => $profile['gender'],

					'posts' => comma_format($profile['posts']),
					'real_posts' => $profile['posts'],
					'last_active' => empty($profile['last_active']) ? $txt['never'] : standardTime($profile['last_active']),
					'last_active_timestamp' => empty($profile['last_active']) ? 0 : forum_time(false, $profile['last_active']),
					'main_group' => $profile['main_group'],
					'approved' => $profile['approved'],
					'retired' => $profile['retired'],
				);
				
					// Are we also loading the members custom fields into context?
					
					if (!empty($modSettings['displayCharacterFields']))
					{
						if (!isset($context['display_character_fields']))
							$context['display_character_fields'] = Util::unserialize($modSettings['displayCharacterFields']);
						
						foreach ($context['display_character_fields'] as $custom)
						{
							if (!isset($custom['title']) || trim($custom['title']) == '' || empty($profile['options'][$custom['colname']]))
								continue;

							$value = $profile['options'][$custom['colname']];

							// BBC?
							if ($custom['bbc'])
								$value = $parsers->parseCustomFields($value);
							// ... or checkbox?
							elseif (isset($custom['type']) && $custom['type'] == 'check')
								$value = $value ? $txt['yes'] : $txt['no'];

							// Enclosing the user input within some other text?
							if (!empty($custom['enclose']))
								$value = strtr($custom['enclose'], array(
									'{SCRIPTURL}' => $scripturl,
									'{IMAGES_URL}' => $settings['images_url'],
									'{DEFAULT_IMAGES_URL}' => $settings['default_images_url'],
									'{INPUT}' => $value,
								));

							$memberContext[$user]['characters'][$id]['custom_fields'][] = array(
								'title' => $custom['title'],
								'colname' => $custom['colname'],
								'value' => $value,
								'placement' => !empty($custom['placement']) ? $custom['placement'] : 0,
							);
						}
					}
			}
		}
			
	}
	
	public static function integrate_profile_summary($memID)
	{
		global $context;
		
		loadTemplate('RpsCharacter');
		
		$context['summarytabs']['summary']['templates'] = elk_array_insert($context['summarytabs']['summary']['templates'], 0, array('characters'), 'after');
	}
	
	public static function integrate_sa_xmlhttp(&$subActions)
	{
		
		$subActions['characterorder'] = array(
			'controller' => 'ManageCharacters_Controller',
			'function' => 'action_characterorder',
			'permission' => 'admin_forum'
		);
		
	}
	
	/**
	 * Determine the user's avatar type and return the information as an array
	 *
	 * @todo this function seems more useful than expected, it should be improved. :P
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


