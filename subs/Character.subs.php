<?php

/**
 * Functions for loading, saving, editing character information.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 * @param $charID
 * @return bool|int
 * @throws Exception
 */
 
function memberID($charID) {
	$db = database();
	
	$request = $db->query('', '
		SELECT id_member
		FROM {db_prefix}rps_characters
		WHERE id_character = {int:charID}
		LIMIT 1',
		array(
			'charID' => $charID,
		)
	);
	
	$memID = false;

	while ($row = $db->fetch_assoc($request))
		$memID = (int) $row['id_member'];
	$db->free_result($request);
	
	if ($memID)
		loadMemberData($memID);
	
	return $memID;
}

/**
 * Setup the context for a page load!
 *
 * @param mixed[] $fields
 */
function setupCharacterContext($fields)
{
	global $character_fields, $context, $cur_profile, $txt;

	call_integration_hook('integrate_character_fields', array(&$fields));

	// Make sure we have this!
	loadCharacterFields(true);

	// First check for any linked sets.
	foreach ($character_fields as $key => $field)
		if (isset($field['link_with']) && in_array($field['link_with'], $fields))
			$fields[] = $key;

	// Some default bits.
	$context['profile_prehtml'] = '';
	$context['profile_posthtml'] = '';
	$context['profile_onsubmit_javascript'] = '';

	$i = 0;
	$last_type = '';
	foreach ($fields as $key => $field)
	{
		if (isset($character_fields[$field]))
		{
			// Shortcut.
			$cur_field = &$character_fields[$field];

			// Does it have a preload and does that preload succeed?
			if (isset($cur_field['preload']) && !$cur_field['preload']())
				continue;

			// If this is anything but complex we need to do more cleaning!
			if ($cur_field['type'] != 'callback' && $cur_field['type'] != 'hidden')
			{
				if (!isset($cur_field['label']))
					$cur_field['label'] = isset($txt[$field]) ? $txt[$field] : $field;

				// Everything has a value!
				if (!isset($cur_field['value']))
					$cur_field['value'] = isset($cur_profile[$field]) ? $cur_profile[$field] : '';

				// Any input attributes?
				$cur_field['input_attr'] = !empty($cur_field['input_attr']) ? implode(',', $cur_field['input_attr']) : '';
			}

			// Was there an error with this field on posting?
			if (isset($context['character_errors'][$field]))
				$cur_field['is_error'] = true;

			// Any javascript stuff?
			if (!empty($cur_field['js_submit']))
				$context['character_onsubmit_javascript'] .= $cur_field['js_submit'];
			if (!empty($cur_field['js']))
				addInlineJavascript($cur_field['js']);
			if (!empty($cur_field['js_load']))
				loadJavascriptFile($cur_field['js_load']);

			// Any template stuff?
			if (!empty($cur_field['prehtml']))
				$context['character_prehtml'] .= $cur_field['prehtml'];
			if (!empty($cur_field['posthtml']))
				$context['character_posthtml'] .= $cur_field['posthtml'];

			// Finally put it into context?
			if ($cur_field['type'] != 'hidden')
			{
				$last_type = $cur_field['type'];
				$context['character_fields'][$field] = &$character_fields[$field];
			}
		}
		// Bodge in a line break - without doing two in a row ;)
		elseif ($field == 'hr' && $last_type != 'hr' && $last_type != '')
		{
			$last_type = 'hr';
			$context['character_fields'][$i++]['type'] = 'hr';
		}
	}

	// Free up some memory.
	unset($characters_fields);
}


/**
 * This defines every profile field known to man.
 *
 * @param bool $force_reload = false
 */
function loadCharacterFields($force_reload = false)
{
	global $context, $character_fields, $txt, $scripturl, $modSettings, $user_info, $cur_profile, $language, $settings;

	// Don't load this twice!
	if (!empty($character_fields) && !$force_reload)
		return;

	/**
	 * This horrific array defines all the profile fields in the whole world!
	 * In general each "field" has one array - the key of which is the database
	 * column name associated with said field.
	 *
	 * Each item can have the following attributes:
	 *
	 * string $type: The type of field this is - valid types are:
	 *   - callback: This is a field which has its own callback mechanism for templating.
	 *   - check:    A simple checkbox.
	 *   - hidden:   This doesn't have any visual aspects but may have some validity.
	 *   - password: A password box.
	 *   - select:   A select box.
	 *   - text:     A string of some description.
	 *
	 * string $label:       The label for this item - default will be $txt[$key] if this isn't set.
	 * string $subtext:     The subtext (Small label) for this item.
	 * int $size:           Optional size for a text area.
	 * array $input_attr:   An array of text strings to be added to the input box for this item.
	 * string $value:       The value of the item. If not set $cur_profile[$key] is assumed.
	 * string $permission:  Permission required for this item (Excluded _any/_own subfix which is applied automatically).
	 * func $input_validate: A runtime function which validates the element before going to the database. It is passed
	 *                       the relevant $_POST element if it exists and should be treated like a reference.
	 *
	 * Return types:
	 *   - true:          Element can be stored.
	 *   - false:         Skip this element.
	 *   - a text string: An error occurred - this is the error message.
	 *
	 * function $preload: A function that is used to load data required for this element to be displayed. Must return
	 *                    true to be displayed at all.
	 *
	 * string $cast_type: If set casts the element to a certain type. Valid types (bool, int, float).
	 * string $save_key:  If the index of this element isn't the database column name it can be overriden with this string.
	 * bool $is_dummy:    If set then nothing is acted upon for this element.
	 * bool $enabled:     A test to determine whether this is even available - if not is unset.
	 * string $link_with: Key which links this field to an overall set.
	 *
	 * string $js_submit: javascript to add insisde the function checkProfileSubmit() in the template
	 * string $js:        javascript to add to the page in general
	 * string $js_load:   filename of js to be loaded with loadJavasciptFile
	 *
	 * Note that all elements that have a custom input_validate must ensure they set the value of $cur_profile correct to enable
	 * the changes to be displayed correctly on submit of the form.
	 */

	$character_fields = array(
		'name' => array(
			'type' => 'text',
			'label' => $txt['name'],
			'subtext' => $txt['display_name_desc'],
			'log_change' => true,
			'input_attr' => array('maxlength="60"'),
			'permission' => 'rps_char_edit',
			'input_validate' =>  function (&$value) {
				global $cur_profile, $context;

				$value = trim(preg_replace('~[\s]~u', ' ', $value));

				if (trim($value) == '')
					return 'no_name';
				elseif (Util::strlen($value) > 60)
					return 'name_too_long';
				elseif ($cur_profile['name'] != $value)
				{
					if (isReservedCharacterName($value, $context['id_member'], $context['id_character']))
						return 'name_taken';
				}
				return true;
			},
			'postinput' => '<i id="name_img" class="icon i-help" alt="*" title="'.$txt['rps_name_check'].'"></i>',
			
		),
		'avatar_choice' => array(
			'type' => 'callback',
			'callback_func' => 'avatar_select',
			// This handles the permissions too.
			'preload' => 'characterLoadAvatarData',
			'input_validate' => 'characterSaveAvatarData',
			'save_key' => 'avatar',
		),
		'birth_year' => array(
			'type' => 'callback',
			'callback_func' => 'birthdate',
			'permission' => 'rps_char_edit',
			'preload' =>  function () {
				global $cur_profile, $context;
				$req = HttpReq::instance();

				// Split up the birth date....
				list ($uyear, $umonth, $uday) = explode('-', empty($cur_profile['birthdate']) || $cur_profile['birthdate'] === '0001-01-01' ? '0000-00-00' : $cur_profile['birthdate']);
				$context['character']['birth_date'] = array(
					'year' => !empty($req->post->birth_year) ? sprintf('%04d', $req->getPost('birth_year', 'intval')): $uyear,
					'month' => !empty($req->post->birth_month) ? sprintf('%02d', $req->getPost('birth_month', 'intval')) : $umonth,
					'day' => !empty($req->post->birth_day) ? sprintf('%02d', $req->getPost('birth_day', 'intval')) : $uday,
				);

				return true;
			},
			'input_validate' => function (&$value) {
				global $profile_vars, $cur_profile, $context;

				$req = HttpReq::instance();

				$birthdate = array(
					'day' => $req->getPost('birth_day', 'intval', ''),
					'month' => $req->getPost('birth_month', 'intval', ''),
					'year' => $req->getPost('birth_year', 'intval', ''),
				);
				if( !empty($birthdate['day']) && !empty($birthdate['month']) && !empty($birthdate['year']) )
				{
					if(checkdate($birthdate['month'], $birthdate['day'], $birthdate['year']))
					{
						$profile_vars['birthdate'] = sprintf('%04d-%02d-%02d', $birthdate['year'], $birthdate['month'], $birthdate['day']);
						$cur_profile['birthdate'] = $profile_vars['birthdate'];
						return false;
					}
					
					else
					{
						$days = $birthdate['month'] == 2 ? ($birthdate['year'] % 4 ? 28 : ($birthdate['year'] % 100 ? 29 : ($birthdate['year'] % 400 ? 28 : 29))) : (($birthdate['month'] - 1) % 7 % 2 ? 30 : 31);
						
						if($birthdate['month'] < 1 || $birthdate['month'] > 12)
						{
							
							$context['error_birthmonth'] = true;
							return 'rps_invalid_month';
						}
						elseif($days <= $birthdate['day'] || $birthdate['day'] < 0)
						{
							$context['error_birthday'] = true;
							return 'rps_invalid_day';
						}
						else
							return 'rps_invalid_date';
					}
				}
				else
					return 'rps_missing_date';
				},
		),
		'signature' => array(
			'type' => 'callback',
			'callback_func' => 'signature_modify',
			'permission' => 'rps_char_edit',
			'preload' => 'characterLoadSignatureData',
			'input_validate' => 'profileValidateSignature',
		),
		'title' => array(
			'type' => 'text',
			'label' => $txt['custom_title'],
			'log_change' => true,
			'input_attr' => array('maxlength="50"'),
			'size' => 50,
			'permission' => 'rps_char_title',
			'input_validate' => function (&$value) {
				if (Util::strlen($value) > 50)
					return 'user_title_too_long';

				return true;
			},
		),
	);

	call_integration_hook('integrate_load_character_fields', array(&$character_fields));


	// For each of the above let's take out the bits which don't apply - to save memory and security!
	foreach ($character_fields as $key => $field)
	{
		// Do we have permission to do this?
		if (isset($field['permission']) && !allowedTo(($context['user']['is_owner'] ? array($field['permission'] . '_own', $field['permission'] . '_any') : $field['permission'] . '_any')) && !allowedTo($field['permission']))
			unset($character_fields[$key]);
	}
}

/**
 * Load key signature context data.
 * @return boolean
 */
function characterLoadSignatureData()
{
	global $modSettings, $context, $txt, $cur_profile;

	// Signature limits.
	list ($sig_limits, $sig_bbc) = explode(':', $modSettings['signature_settings']);
	$sig_limits = explode(',', $sig_limits);

	$context['signature_enabled'] = isset($sig_limits[0]) ? $sig_limits[0] : 0;
	$context['signature_limits'] = array(
		'max_length' => isset($sig_limits[1]) ? $sig_limits[1] : 0,
		'max_lines' => isset($sig_limits[2]) ? $sig_limits[2] : 0,
		'max_images' => isset($sig_limits[3]) ? $sig_limits[3] : 0,
		'max_smileys' => isset($sig_limits[4]) ? $sig_limits[4] : 0,
		'max_image_width' => isset($sig_limits[5]) ? $sig_limits[5] : 0,
		'max_image_height' => isset($sig_limits[6]) ? $sig_limits[6] : 0,
		'max_font_size' => isset($sig_limits[7]) ? $sig_limits[7] : 0,
		'bbc' => !empty($sig_bbc) ? explode(',', $sig_bbc) : array(),
	);
	// Kept this line in for backwards compatibility!
	$context['max_signature_length'] = $context['signature_limits']['max_length'];

	// Warning message for signature image limits?
	$context['signature_warning'] = '';
	if ($context['signature_limits']['max_image_width'] && $context['signature_limits']['max_image_height'])
		$context['signature_warning'] = sprintf($txt['profile_error_signature_max_image_size'], $context['signature_limits']['max_image_width'], $context['signature_limits']['max_image_height']);
	elseif ($context['signature_limits']['max_image_width'] || $context['signature_limits']['max_image_height'])
		$context['signature_warning'] = sprintf($txt['profile_error_signature_max_image_' . ($context['signature_limits']['max_image_width'] ? 'width' : 'height')], $context['signature_limits'][$context['signature_limits']['max_image_width'] ? 'max_image_width' : 'max_image_height']);

	$context['show_spellchecking'] = !empty($modSettings['enableSpellChecking']) && function_exists('pspell_new');
	if ($context['show_spellchecking'])
		loadJavascriptFile('spellcheck.js', array('defer' => true));

	if (empty($context['do_preview']))
		$context['character']['signature'] = empty($cur_profile['signature']) ? '' : str_replace(array('<br />', '<', '>', '"', '\''), array("\n", '&lt;', '&gt;', '&quot;', '&#039;'), $cur_profile['signature']);
	else
	{
		$signature = !empty($_POST['signature']) ? $_POST['signature'] : '';
		require_once(SUBSDIR . '/Profile.subs.php');
		$validation = profileValidateSignature($signature);
		if (empty($context['post_errors']))
		{
			loadLanguage('Errors');
			$context['post_errors'] = array();
		}

		$context['post_errors'][] = 'signature_not_yet_saved';
		if ($validation !== true && $validation !== false)
			$context['post_errors'][] = $validation;

		$context['character']['signature'] = censor($context['character']['signature']);
		$context['character']['current_signature'] = $context['character']['signature'];
		$signature = censor($signature);
		$bbc_parser = \BBC\ParserWrapper::instance();
		$context['character']['signature_preview'] = $bbc_parser->parseSignature($signature, true);
		$context['character']['signature'] = $_POST['signature'];

	}

	return true;
}

/**
 * Load avatar context data.
 *
 * @return boolean
 */
/**
 * Load avatar context data.
 *
 * @return boolean
 */
function characterLoadAvatarData()
{
	global $context, $cur_profile, $modSettings, $scripturl;

	$context['avatar_url'] = $modSettings['avatar_url'];

	$valid_protocol = preg_match('~^https' . (detectServer()->supportsSSL() ? '' : '?') . '://~i', $cur_profile['avatar']) === 1;
	$schema = 'http' . (detectServer()->supportsSSL() ? 's' : '') . '://';

	// @todo Temporary
	if ($context['user']['is_owner'])
		$allowedChange = allowedTo('rps_char_set_avatar') && allowedTo(array('rps_char_edit_any', 'rps_char_edit_own'));
	else
		$allowedChange = allowedTo('rps_char_set_avatar') && allowedTo('rps_char_edit_any');

	// Default context.
	$context['character']['avatar'] += array(
		'custom' => $valid_protocol ? $cur_profile['avatar'] : $schema,
		'selection' => $valid_protocol ? $cur_profile['avatar'] : '',
		'allow_server_stored' => !empty($modSettings['avatar_stored_enabled']) && $allowedChange,
		'allow_external' =>  !empty($modSettings['avatar_external_enabled']) && $allowedChange,
	);

	if ($valid_protocol && $context['character']['avatar']['allow_external'])
		$context['character']['avatar'] += array(
			'choice' => 'external',
			'server_pic' => 'blank.png',
			'external' => $cur_profile['avatar']
		);
	elseif ($cur_profile['avatar'] != '' && file_exists($modSettings['avatar_directory'] . '/' . $cur_profile['avatar']) && $context['character']['avatar']['allow_server_stored'])
		$context['character']['avatar'] += array(
			'choice' => 'server_stored',
			'server_pic' => $cur_profile['avatar'] == '' ? 'blank.png' : $cur_profile['avatar'],
			'external' => $schema
		);
	else
		$context['character']['avatar'] += array(
			'choice' => 'none',
			'server_pic' => 'blank.png',
			'external' => 'http://'
		);

	// Get a list of all the avatars.
	if ($context['character']['avatar']['allow_server_stored'])
	{
		require_once(SUBSDIR . '/Attachments.subs.php');
		$context['member']['avatar'] = $context['character']['avatar'];
		$context['avatar_list'] = array();
		$context['avatars'] = is_dir($modSettings['avatar_directory']) ? getServerStoredAvatars('', 0) : array();
	}
	else
	{
		$context['avatar_list'] = array();
		$context['avatars'] = array();
	}

	// Second level selected avatar...
	$context['avatar_selected'] = substr(strrchr($context['character']['avatar']['server_pic'], '/'), 1);
	return true;
}

/**
 * Save the profile changes.
 * @param $memID
 * @param $charID
 */
function saveCharacterFields($memID, $charID)
{
	global $character_fields, $profile_vars, $context, $old_profile, $post_errors, $cur_profile, $user_info, $log_changes;

	// Load them up.
	loadCharacterFields();

	// This makes things easier...
	$old_profile = $cur_profile;

	// Assume we log nothing.
	$log_changes = array();
	$profile_vars = array();

	// Cycle through the profile fields working out what to do!
	foreach ($character_fields as $key => $field)
	{
		if (!isset($_POST[$key]) || !empty($field['is_dummy']) || (isset($_POST['preview_signature']) && $key === 'signature'))
			continue;

		// What gets updated?
		$db_key = isset($field['save_key']) ? $field['save_key'] : $key;

		// Right - we have something that is enabled, we can act upon and has a value posted to it. Does it have a validation function?
		if (isset($field['input_validate']))
		{
			
			$is_valid = $field['input_validate']($_POST[$key]);

			// An error occurred - set it as such!
			if ($is_valid !== true)
			{
				// Is this an actual error?
				if ($is_valid !== false)
				{
					$post_errors[$key] = $is_valid;
					$character_fields[$key]['is_error'] = $is_valid;
				}

				// Retain the old value.
				$context['character'][$key] = $_POST[$key];
				continue;
			}
		}

		// Are we doing a cast?
		$field['cast_type'] = empty($field['cast_type']) ? $field['type'] : $field['cast_type'];

		// Finally, clean up certain types.
		if ($field['cast_type'] === 'int')
			$_POST[$key] = (int) $_POST[$key];
		elseif ($field['cast_type'] === 'float')
			$_POST[$key] = (float) $_POST[$key];
		elseif ($field['cast_type'] === 'check')
			$_POST[$key] = !empty($_POST[$key]) ? 1 : 0;

		// If we got here we're doing OK.
		if ($field['type'] !== 'hidden' && (!isset($old_profile[$key]) || $_POST[$key] != $old_profile[$key]))
		{
			// Set the save variable.
			$profile_vars[$db_key] = $_POST[$key];

			// Are we logging it?
			if (!empty($field['log_change']) && isset($old_profile[$key]))
				$log_changes[] = array(
					'action' => $key,
					'log_type' => 'character',
					'extra' => array(
						'previous' => $old_profile[$key],
						'new' => $_POST[$key],
						'applicator' => $user_info['id'],
						'character' => $charID,
						),
				);
		}
	}

	// @todo Temporary
	if ($context['user']['is_owner'])
		$changeOther = allowedTo(array('rps_char_edit_any', 'rps_char_edit_own'));
	else
		$changeOther = allowedTo('rps_char_edit_any');

	if(!empty($profile_vars) && empty($post_errors))
		updateCharacterData($charID, $profile_vars);
	if ($changeOther && empty($post_errors))
	{
		if (!empty($log_changes))
			logActions($log_changes);
	}

	// Free memory!
	unset($character_fields);
}

function updateCharacterData($characters, $data)
{
	global $modSettings, $user_info;
	
	$db = database();

	$parameters = array();
	if (is_array($characters))
	{
		$condition = 'id_character IN ({array_int:characters})';
		$parameters['characters'] = $characters;
	}
	elseif ($characters === null)
		$condition = '1=1';
	else
	{
		$condition = 'id_character = {int:character}';
		$parameters['character'] = $characters;
	}

	// Everything is assumed to be a string unless it's in the below.
	$knownInts = array(
		'id_member', 'posts','date_created','last_active',
		'main_group', 'approved', 'retired',
	);
	$knownFloats = array();

	$setString = '';
	foreach ($data as $var => $val)
	{
		$type = 'string';

		if (in_array($var, $knownInts))
			$type = 'int';
		elseif (in_array($var, $knownFloats))
			$type = 'float';
		elseif ($var == 'birthdate')
			$type = 'date';

		// Doing an increment?
		if ($type == 'int' && ($val === '+' || $val === '-'))
		{
			$val = $var . ' ' . $val . ' 1';
			$type = 'raw';
		}

		// Ensure posts, personal_messages, and unread_messages don't overflow or underflow.
		if (in_array($var, array('posts')))
		{
			if (preg_match('~^' . $var . ' (\+ |- |\+ -)([\d]+)~', $val, $match))
			{
				if ($match[1] != '+ ')
					$val = 'CASE WHEN ' . $var . ' <= ' . abs($match[2]) . ' THEN 0 ELSE ' . $val . ' END';
				$type = 'raw';
			}
		}

		$setString .= ' ' . $var . ' = {' . $type . ':p_' . $var . '},';
		$parameters['p_' . $var] = $val;
	}

	$db->query('', '
		UPDATE {db_prefix}rps_characters
		SET' . substr($setString, 0, -1) . '
		WHERE ' . $condition,
		$parameters
	);

	$cache = Cache::instance();

	// Clear any caching?
	if ($cache->levelHigherThan(1) && !empty($members))
	{
		if (!is_array($members))
			$members = array($members);

		foreach ($members as $member)
		{
			if ($cache->levelHigherThan(2))
			{
				$cache->remove('member_data-profile-' . $member);
				$cache->remove('member_data-normal-' . $member);
				$cache->remove('member_data-minimal-' . $member);
			}

			$cache->remove('user_settings-' . $member);
		}
	}
}


/**
 * Check if a name is in the reserved words list. (name, current member id, name/username?.)
 *
 * - checks if name is a reserved name or username.
 * - if is_name is false, the name is assumed to be a username.
 * - the id_member variable is used to ignore duplicate matches with the current member.
 *
 * @package Members
 * @param string $name
 * @param int $current_ID_MEMBER
 * @param int $character_id
 * @param bool $fatal
 * @return bool
 * @throws Exception
 */
function isReservedCharacterName($name, $current_ID_MEMBER = 0, $character_id = 0, $fatal = true)
{
	global $modSettings;

	$db = database();

	$name = preg_replace_callback('~(&#(\d{1,7}|x[0-9a-fA-F]{1,6});)~', 'replaceEntities__callback', $name);
	$checkName = Util::strtolower($name);

	// Administrators are never restricted ;).
	$reservedNames = explode("\n", $modSettings['reserveNames']);
	// Case sensitive check?
	$checkMe = empty($modSettings['reserveCase']) ? $checkName : $name;

	// Check each name in the list...
	foreach ($reservedNames as $reserved)
	{
		if ($reserved == '')
			continue;

		// The admin might've used entities too, level the playing field.
		$reservedCheck = preg_replace_callback('~(&#(\d{1,7}|x[0-9a-fA-F]{1,6});)~', 'replaceEntities__callback', $reserved);

		// Case sensitive name?
		if (empty($modSettings['reserveCase']))
			$reservedCheck = Util::strtolower($reservedCheck);

		// If it's not just entire word, check for it in there somewhere...
		if ($checkMe == $reservedCheck || (Util::strpos($checkMe, $reservedCheck) !== false && empty($modSettings['reserveWord'])))
			if ($fatal)
				throw new Elk_Exception('name_reserved', 'password', array($reserved));
			else
				return true;
	}

	$censor_name = $name;
	if (censorText($censor_name) != $name)
		if ($fatal)
			throw new Elk_Exception('name_censored', 'password', array($name));
		else
			return true;

	// Characters we just shouldn't allow, regardless.
	foreach (array('*') as $char)
		if (strpos($checkName, $char) !== false)
			if ($fatal)
				throw new Elk_Exception('name_reserved', 'password', array($char));
			else
				return true;

	// Get rid of any SQL parts of the reserved name...
	$checkName = strtr($name, array('_' => '\\_', '%' => '\\%'));

	// Make sure they don't want someone else's name.
	$request = $db->query('', '
		SELECT id_member
		FROM {db_prefix}members
		WHERE ({raw:real_name} LIKE {string:check_name} OR {raw:member_name} LIKE {string:check_name}) AND id_member <> {int:current_member}
		LIMIT 1',
		array(
			'real_name' => defined('DB_CASE_SENSITIVE') ? 'LOWER(real_name)' : 'real_name',
			'member_name' => defined('DB_CASE_SENSITIVE') ? 'LOWER(member_name)' : 'member_name',
			'current_member' => $current_ID_MEMBER,
			'check_name' => $checkName,
		)
	);
	if ($db->num_rows($request) > 0)
	{
		$db->free_result($request);
		return true;
	}
		// Make sure they don't want someone else's name.
	$request = $db->query('', '
		SELECT id_character
		FROM {db_prefix}rps_characters
		WHERE ({raw:name} LIKE {string:check_name})
			AND id_character <> {int:char_id}
		LIMIT 1',
		array(
			'name' => defined('DB_CASE_SENSITIVE') ? 'LOWER(name)' : 'name',
			'check_name' => $checkName,
			'char_id' => $character_id
		)
	);
	if ($db->num_rows($request) > 0)
	{
		$db->free_result($request);
		return true;
	}
	
	// Does name case insensitive match a member group name?
	$request = $db->query('', '
		SELECT id_group
		FROM {db_prefix}membergroups
		WHERE {raw:group_name} LIKE {string:check_name}
		LIMIT 1',
		array(
			'group_name' => defined('DB_CASE_SENSITIVE') ? 'LOWER(group_name)' : 'group_name',
			'check_name' => $checkName,
		)
	);
	if ($db->num_rows($request) > 0)
	{
		$db->free_result($request);
		return true;
	}

	// Okay, they passed.
	return false;
}

/**
 * The avatar is incredibly complicated, what with the options... and what not.
 *
 * @todo argh, the avatar here. Take this out of here!
 *
 * @param mixed[] $value
 *
 * @return false|string
 * @throws Exception
 */
function characterSaveAvatarData(&$value)
{
	global $modSettings, $profile_vars,$cur_profile, $context;

	$db = database();

	$downloadedExternalAvatar = false;
	$valid_http = isset($_POST['userpicpersonal']) && substr($_POST['userpicpersonal'], 0, 7) === 'http://' && strlen($_POST['userpicpersonal']) > 7;
	$valid_https = isset($_POST['userpicpersonal']) && substr($_POST['userpicpersonal'], 0, 8) === 'https://' && strlen($_POST['userpicpersonal']) > 8;

	if ($value === 'none')
	{
		$profile_vars['avatar'] = '';

		// Reset the attach ID.
		$cur_profile['id_attach'] = 0;
		$cur_profile['attachment_type'] = 0;
		$cur_profile['filename'] = '';
	}
	elseif ($value === 'server_stored' && !empty($modSettings['avatar_stored_enabled']))
	{
		$profile_vars['avatar'] = strtr(empty($_POST['file']) ? (empty($_POST['cat']) ? '' : $_POST['cat']) : $_POST['file'], array('&amp;' => '&'));
		$profile_vars['avatar'] = preg_match('~^([\w _!@%*=\-#()\[\]&.,]+/)?[\w _!@%*=\-#()\[\]&.,]+$~', $profile_vars['avatar']) != 0 && preg_match('/\.\./', $profile_vars['avatar']) == 0 && file_exists($modSettings['avatar_directory'] . '/' . $profile_vars['avatar']) ? ($profile_vars['avatar'] === 'blank.png' ? '' : $profile_vars['avatar']) : '';

		// Clear current profile...
		$cur_profile['id_attach'] = 0;
		$cur_profile['attachment_type'] = 0;
		$cur_profile['filename'] = '';
	}

	elseif ($value === 'external' && !empty($modSettings['avatar_external_enabled']) && ($valid_http || $valid_https))
	{
		// We need these clean...
		$context['character']['id_attach'] = 0;
		$context['character']['attachment_type'] = 0;
		$context['character']['filename'] = '';

		$profile_vars['avatar'] = str_replace(' ', '%20', preg_replace('~action(?:=|%3d)(?!dlattach)~i', 'action-', $_POST['userpicpersonal']));

		if (preg_match('~^https?:///?$~i', $profile_vars['avatar']) === 1)
			$profile_vars['avatar'] = '';
		// Trying to make us do something we'll regret?
		elseif ((!$valid_http && !$valid_https) || ($valid_http && detectServer()->supportsSSL()))
			return 'bad_avatar';
		// Should we check dimensions?
		elseif (!empty($modSettings['avatar_max_height']) || !empty($modSettings['avatar_max_width']))
		{
			require_once(SUBSDIR . '/Attachments.subs.php');
			// Now let's validate the avatar.
			$sizes = url_image_size($profile_vars['avatar']);

			if (is_array($sizes) && (($sizes[0] > $modSettings['avatar_max_width'] && !empty($modSettings['avatar_max_width'])) || ($sizes[1] > $modSettings['avatar_max_height'] && !empty($modSettings['avatar_max_height']))))
			{
				// Houston, we have a problem. The avatar is too large!!
				if ($modSettings['avatar_action_too_large'] === 'option_refuse')
					return 'bad_avatar';
			
			}
		}
	}
	else
		$profile_vars['avatar'] = '';

	// Setup the profile variables so it shows things right on display!
	$cur_profile['avatar'] = $profile_vars['avatar'];

	return false;
}

/**
 * Returns the total number of new topics a user has made
 *
 * - Counts all posts or just the topics made on a particular board
 *
 * @param int $memID
 * @param int|null $board
 * @return integer
 */
function count_character_topics($memID, $board = null)
{
	global $modSettings, $user_info;

	$db = database();

	$is_owner = $memID == $user_info['id'];

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}topics AS t' . ($user_info['query_see_board'] === '1=1' ? '' : '
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board AND {query_see_board})') . '
		WHERE t.id_member_started = {int:current_member}' . (!empty($board) ? '
			AND t.id_board = {int:board}' : '') . (!$modSettings['postmod_active'] || $is_owner ? '' : '
			AND t.approved = {int:is_approved}'),
		array(
			'current_member' => $memID,
			'is_approved' => 1,
			'board' => $board,
		)
	);

	list ($msgCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $msgCount;
}

/*
Select * FROM smf_topics AS t

INNER JOIN smf_messages AS m ON t.id_topic = m.id_topic

WHERE m.id_member = 156  
GROUP BY t.id_topic
ORDER BY `t`.`id_topic` ASC

*/

