<?php

/**
 * Editing and display of character profiles.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

use ElkArte\Errors\ErrorContext;

class Character_Controller extends Action_Controller
{
	/**
	 * If the save was successful or not
	 * @var boolean
	 */
	private $_completed_save = false;

	/**
	 * If this was a request to save an update
	 * @var null
	 */
	private $_saving = null;

	/**
	 * What it says, on completion
	 * @var bool
	 */
	private $_force_redirect;

	/**
	 * Holds the output of createMenu for the profile areas
	 * @var array|boolean
	 */
	private $_profile_include_data;

	/**
	 * The current area chosen from the menu
	 * @var string
	 */
	private $_current_area;

	/**
	 * Member id for the history being viewed
	 * @var int
	 */
	private $_memID = 0;
	
	private $_charID = 0;
	
	private $_summary_areas;
	
	/**
	 * Called before all other methods when coming from the dispatcher or
	 * action class.
	 *
	 * - If you initiate the class outside of those methods, call this method.
	 * or setup the class yourself or fall awaits.
	 */
	public function pre_dispatch()
	{
		global $context, $user_info, $memberContext, $user_profile, $cur_profile;

		require_once(SUBSDIR . '/Character.subs.php');
		require_once(SUBSDIR . '/Menu.subs.php');
		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_charID = $this->_req->getQuery('c', 'intval', 0);
		$this->_memID = memberID($this->_charID);
		
		loadMemberContext($this->_memID);
		$context['member'] = &$memberContext[$this->_memID];
		$context['character'] = &$memberContext[$this->_memID]['characters'][$this->_charID];
		
		$cur_profile = $user_profile[$this->_memID]['characters'][$this->_charID];
		$context['id_member'] = $this->_memID;
		$context['id_character'] = $this->_charID;
		
		if (!isset($context['user']['is_owner']))
			$context['user']['is_owner'] = in_array($this->_charID, $user_info['characters']);

		loadLanguage('Profile');
		loadLanguage('RolePlayingSystem');
	}
	
	/**
	 * Allow the change or view of profiles.
	 *
	 * - Fires the pre_load event
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		global $txt, $user_info, $context, $user_profile, $cur_profile, $memberContext;
		global $profile_vars, $post_errors;

		// Don't reload this as we may have processed error strings.
		if (empty($post_errors))
			loadLanguage('Profile');
		loadTemplate('Profile');

		// Trigger profile pre-load event
		$this->_events->trigger('pre_load', array('post_errors' => $post_errors));

		// A little bit about this member
		$context['id_member'] = $this->_memID;
		$cur_profile = $user_profile[$this->_memID];

		// Let's have some information about this member ready, too.
		loadMemberContext($this->_memID);
		$context['member'] = $memberContext[$this->_memID];

		// Is this the profile of the user himself or herself?
		$context['user']['is_owner'] = (int) $this->_memID === (int) $user_info['id'];

		// Create the menu of profile options
		$this->_define_profile_menu();

		// Is there an updated message to show?
		if (isset($this->_req->query->updated))
			$context['profile_updated'] = $txt['profile_updated_own'];

		// If it said no permissions that meant it wasn't valid!
		if ($this->_profile_include_data && empty($this->_profile_include_data['permission']))
			$this->_profile_include_data['enabled'] = false;

		// No menu and guest? A warm welcome to register
		if (!$this->_profile_include_data && $user_info['is_guest'])
			is_not_guest();

		// No menu means no access at all.
		if (!$this->_profile_include_data || (isset($this->_profile_include_data['enabled']) && $this->_profile_include_data['enabled'] === false))
			throw new Elk_Exception('no_access', false);

		// Make a note of the Unique ID for this menu.
		$context['profile_menu_id'] = $context['max_menu_id'];
		$context['profile_menu_name'] = 'menu_data_' . $context['profile_menu_id'];

		// Set the selected item - now it's been validated.
		$this->_current_area = $this->_profile_include_data['current_area'];
		$context['menu_item_selected'] = $this->_current_area;

		// Before we go any further, let's work on the area we've said is valid.
		// Note this is done here just in case we ever compromise the menu function in error!
		$this->_completed_save = false;
		$context['do_preview'] = isset($this->_req->post->preview_signature);

		// Are we saving data in a valid area?
		$this->_saving = $this->_req->getPost('save', 'trim', $this->_req->getQuery('save', 'trim', null));
		if (isset($this->_profile_include_data['sc']) && (isset($this->_saving) || $context['do_preview']))
		{
			checkSession($this->_profile_include_data['sc']);
			$this->_completed_save = true;
		}

		// Permissions for good measure.
		if (!empty($this->_profile_include_data['permission']))
			isAllowedTo($this->_profile_include_data['permission'][$context['user']['is_owner'] ? 'own' : 'any']);

		// Session validation and/or Token Checks
		//$this->_check_access();

		// Build the link tree.
		$this->_build_profile_linktree();

		// Set the template for this area... if you still can :P
		// and add the profile layer.
		$context['sub_template'] = $this->_profile_include_data['function'];
		Template_Layers::instance()->add('profile');

		// Need JS if we made it this far
		loadJavascriptFile('profile.js');

		// Have some errors for some reason?
		// @todo check that this can be safely removed.
		if (!empty($post_errors))
		{
			// Set all the errors so the template knows what went wrong.
			foreach ($post_errors as $error_type)
				$context['modify_error'][$error_type] = true;
		}
		// If it's you then we should redirect upon save.
		elseif (!empty($profile_vars) && $context['user']['is_owner'] && !$context['do_preview'])
			redirectexit('action=profile;area=' . $this->_current_area . ';updated');
		elseif (!empty($this->_force_redirect))
			redirectexit('action=profile' . ($context['user']['is_owner'] ? '' : ';u=' . $this->_memID) . ';area=' . $this->_current_area);

		// Let go to the right place
		if (isset($this->_profile_include_data['file']))
			require_once($this->_profile_include_data['file']);

		callMenu($this->_profile_include_data);

		// Set the page title if it's not already set...
		if (!isset($context['page_title']))
			$context['page_title'] = $txt['profile'] . (isset($txt[$this->_current_area]) ? ' - ' . $txt[$this->_current_area] : '');
	}
	
	/**
	 * Intended as entry point which delegates to methods in this class...
	 */
/*	public function action_index()
	{
		global $context;

		require_once(SUBSDIR . '/Action.class.php');

		// Little short on the list here
		$subActions = array(
			'summary' => array($this, 'action_summary'),
			'showtopics' => array($this, 'action_topics'),
			'showposts' => array($this, 'action_posts'),
			'edit' => array($this, 'action_edit'),
			'create' => array($this, 'action_create', 'permission' => ''),
			'create2' => array($this, 'action_create2'),
			'checkname' => array($this, 'action_checkname'),
			'sig_preview' => array($this, 'action_sig_preview'),
			'recent' => array($this, 'action_character_recent'),
		);
		
		// I don't think we know what to do... throw dies?
		$action = new Action();
		$subAction = $action->initialize($subActions, 'summary');
		
		$context['sub_action'] = $subAction;
		$action->dispatch($subAction);
	}*/
	
		/**
	 * Define all the sections within the profile area!
	 *
	 * We start by defining the permission required - then we take this and turn
	 * it into the relevant context ;)
	 *
	 * Possible fields:
	 *   For Section:
	 *    - string $title: Section title.
	 *    - array $areas:  Array of areas within this section.
	 *
	 *   For Areas:
	 *    - string $label:      Text string that will be used to show the area in the menu.
	 *    - string $file:       Optional text string that may contain a file name that's needed for inclusion in order to display the area properly.
	 *    - string $custom_url: Optional href for area.
	 *    - string $function:   Function to execute for this section.
	 *    - bool $enabled:      Should area be shown?
	 *    - string $sc:         Session check validation to do on save - note without this save will get unset - if set.
	 *    - bool $hidden:       Does this not actually appear on the menu?
	 *    - bool $password:     Whether to require the user's password in order to save the data in the area.
	 *    - array $subsections: Array of subsections, in order of appearance.
	 *    - array $permission:  Array of permissions to determine who can access this area. Should contain arrays $own and $any.
	 */
	private function _define_profile_menu()
	{
		global $txt, $scripturl, $context, $cur_profile, $modSettings;

		$profile_areas = array(
			'character' => array(
				'title' => $txt['rps_profile_info'],
				'areas' => array(
					'summary' => array(
						'label' => $txt['summary'],
						'controller' => 'CharacterInfo_Controller',
						'function' => 'action_summary',
						'custom_url' => $this->_url('summary'),
						'permission' => array(
							'own' => array('rps_char_view'),
							'any' => array('rps_char_view'),
						),
					),
					'showposts' => array(
						'label' => $txt['showPosts'],
						'controller' => 'CharacterInfo_Controller',
						'function' => 'action_showPosts',
						'custom_url' => $this->_url('showposts'),
						'permission' => array(
							'own' => array('rps_char_view' ),
							'any' => array('rps_char_view'),
						),
					),
					'showtopics' => array(
						'label' => $txt['rps_showTopics'],
						'controller' => 'CharacterInfo_Controller',
						'function' => 'action_showPosts',
						'custom_url' => $this->_url('showtopics'),
						'permission' => array(
							'own' => array('rps_char_view'),
							'any' => array('rps_char_view'),
						),
					),
				),
			),
			'character_edit' => array(
				'title' => $txt['rps_modify_character'],
				'areas' => array(
					'edit' => array(
						'label' => $txt['rps_modify_profile'],
						'controller' => 'Character_Controller',
						'function' => 'action_edit',
						'custom_url' => $this->_url('edit'),
						'sc' => 'profile',
						'token' => 'profile-ac%u',
						'password' => true,
						'permission' => array(
							'own' => array('rps_char_edit_any', 'rps_char_edit_own', 'rps_char_title_any', 'rps_char_title_own'),
							'any' => array('rps_char_edit_any', 'rps_char_title_any'),
						),
					),
					'biography_edit' => array(
						'label' => $txt['rps_modify_bio'],
						'controller' => 'CharacterBiography_Controller',
						'function' => 'action_biography_edit',
						'custom_url' => $this->_url('biography_edit'),
						'sc' => 'biography',
						'token' => 'profile-fp%c',
						'permission' => array(
							'own' => array('rps_char_edit_any', 'rps_char_edit_own'),
							'any' => array('rps_char_edit_any'),
						),
					),
				),
			),
		);
		if(!empty($context['member']['characters']) && count($context['member']['characters']) > 1) {
			$profile_areas['characters']['title'] = $txt['rps_profile_characters'];
			
			foreach($context['member']['characters'] as $character) {
				$profile_areas['characters']['areas'][$character['name']] = array(
					'label' => $character['name'],
					'custom_url' => $character['href'],
				);
			}
		}

		// Set a few options for the menu.
		$menuOptions = array(
			'disable_url_session_check' => true,
			'hook' => 'character',
			'default_include_dir' => CONTROLLERDIR,
		);

		// Actually create the menu!
		$this->_profile_include_data = createMenu($profile_areas, $menuOptions);
		unset($profile_areas);
	}
	
	private function _url($area)
	{
		global $scripturl;
		
		return $scripturl . '?action=character;area=' . $area . ';c=' . $this->_charID;
	}
	
	
	
	/**
	 * Allow the user to change the forum options in their profile.
	 *
	 */
	public function action_edit()
	{
		global $context, $txt, $memberContext, $scripturl, $post_errors;

		if (empty($context['character']))
			throw new Elk_Exception('not_a_user', false);
		
		if (isset($_POST['save']))
		{
			saveCharacterFields($this->_memID, $this->_charID);
			
			// There was a problem, let them try to re-enter.
			if (!empty($post_errors))
			{
				// Load the language file so we can give a nice explanation of the errors.
				loadLanguage('Errors');
				$context['post_errors'] = $post_errors;
			}
			
			else
			{
				redirectexit('action=character;c=' . $this->_charID . ';update');
			}
		}

		loadJavascriptFile('profile.js');
		
		addInlineJavascript('disableAutoComplete();', true);
		loadTemplate('RpsCharacter');

		$context['sub_template'] = 'character_form';
		$context['page_desc'] = $txt['rps_edit_character_desc'];
		$context['show_preview_button'] = true;
		$context['header_text'] = $txt['rps_edit_character'] . $context['character']['name'];
		$context['submit_txt'] = $txt['rps_save_changes'];
		$context['form_action'] = $scripturl . '?action=character;area=edit;c=' . $this->_charID;
		$context['page_title'] = $txt['rps_edit_character'] . $context['character']['name'];

		setupCharacterContext(
			array(
				'name',
				'avatar_choice', 'hr',
				'bday1', 'gender', 'personal_text', 'hr',
				'title', 'signature'
			)
		);
	}
	
	
	/**
	 * Begin the registration process.
	 * Accessed by ?action=register
	 *
	 * @uses Register template, registration_agreement or registration_form sub template
	 * @uses Login language file
	 */
	public function action_create()
	{
		global $txt, $context, $modSettings, $user_info, $language, $scripturl, $cur_profile;

		loadLanguage('Login');
		loadTemplate('RpsCharacter');

		$cur_profile = array();
		
		// Show the user the right form.
		$context['sub_template'] = 'create_form';
		$context['page_title'] = $txt['rps_create_character'];
		loadJavascriptFile('rps_character.js');
		addInlineJavascript('disableAutoComplete();', true);

		// Add the register chain to the link tree.
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=character',
			'name' => $txt['rps_create_character'],
		);
		
		// Setup some important context.
		loadLanguage('Profile');

		$context['user']['is_owner'] = true;

		// Here, and here only, emulate the permissions the user would have to do this.
		$user_info['permissions'] = array_merge($user_info['permissions'], array('profile_account_own', 'profile_extra_own'));
		$create_fields = array('name','birthdate','title');

		// We might have had some submissions on this front - go check.
		foreach ($create_fields as $field)
		{
			if (isset($this->_req->post->{$field}))
			{
				$cur_profile[$field] = Util::htmlspecialchars($this->_req->post->{$field});
			}
		}
		
		if (!empty($cur_profile['birthdate']))
			list($cur_profile['birth_date']['year'],$cur_profile['birth_date']['month'],$cur_profile['birth_date']['day']) = explode('-',$cur_profile['birthdate']);
		
		setupCharacterContext($create_fields, 'registration');
		
		// Were there any errors?
		$context['creation_errors'] = array();
		$creation_errors = ErrorContext::context('create', 0);
		if ($creation_errors->hasErrors())
			$context['creation_errors'] = $creation_errors->prepareErrors();

		createToken('create');
	}

    /**
     * Actually create the character
     *
     * @return bool
     * @throws \ElkArte\Exceptions\Exception
     */
	public function action_create2()
	{
		global $txt, $modSettings, $context, $user_info;

		// Start collecting together any errors.
		$creation_errors = ErrorContext::context('create', 0);

		checkSession();
		if (!validateToken('create', 'post', true, false))
			$creation_errors->addError('token_verification');

		// Make sure they came from *somewhere*, have a session.
		if (!isset($_SESSION['old_url']))
			redirectexit('action=character;sa=create');

		// Clean the form values
		foreach ($this->_req->post as $key => $value)
		{
			if (!is_array($value))
			{
				$this->_req->post->{$key} = htmltrim__recursive(str_replace(array("\n", "\r"), '', $value));
			}
		}

		// Collect all extra registration fields someone might have filled in.
		$possible_strings = array(
			'birthdate',
			'title',
		);
		
		// Validation... even if we're not a mall.
			$this->_req->post->name = trim(preg_replace('~[\t\n\r \x0B\0\x{A0}\x{AD}\x{2000}-\x{200F}\x{201F}\x{202F}\x{3000}\x{FEFF}]+~u', ' ', $this->_req->post->name));
		
		// Handle a string as a birth date...
		if ($this->_req->getPost('birthdate', 'trim', '') !== '')
			$this->_req->post->birthdate = strftime('%Y-%m-%d', strtotime($this->_req->post->birthdate));
		// Or birthdate parts...
		elseif (!empty($this->_req->post->bday1) && !empty($this->_req->post->bday2))
			$this->_req->post->birthdate = sprintf('%04d-%02d-%02d', empty($this->_req->post->bday3) ? 0 : (int) $this->_req->post->bday3, (int) $this->_req->post->bday1, (int) $this->_req->post->bday2);

				// Set the options needed for registration.
		$createOptions = array(
			'id_member' => (int) $context['user']['id'],
			'name' => $this->_req->post->name,
		);
		
		foreach ($possible_strings as $var)
			if (isset($this->_req->post->{$var}))
				$createOptions[$var] = Util::htmlspecialchars($this->_req->post->{$var}, ENT_QUOTES);
		
		require_once(SUBSDIR . '/Profile.subs.php');

		// Lets check for other errors before trying to register the member.
		if ($creation_errors->hasErrors())
		{
			$this->action_create();
			return false;
		}

		$characterID = $this->createCharacter($createOptions);

		// If there are "important" errors and you are not an admin: log the first error
		// Otherwise grab all of them and don't log anything
		if ($creation_errors->hasErrors(1) && !$user_info['is_admin'])
		{
			foreach ($creation_errors->prepareErrors(1) as $error)
				throw new Elk_Exception($error, 'general');
		}
		
		// Was there actually an error of some kind dear boy?
		if ($creation_errors->hasErrors())
		{
			$this->action_create();
			return false;
		}
		
		 loadTemplate('RpsCharacter');

		$context += array(
			'page_title' => $txt['rps_create_character'],
			'title' => $txt['rps_create_character_success'],
			'sub_template' => 'after',
			'description' => $txt['rps_create_character_success_desc'],
		);
	}
	
	/**
	* See if a username already exists.
	*/
	public function action_checkname()
	{
		global $context;

		// This is XML!
		loadTemplate('RpsCharacter');

		$context['sub_template'] = 'action_checkname';
		$context['checked_name'] = $this->_req->getPost('name', 'trim|strval', '');
		$context['valid_name'] = true;
		
		$memID= $this->_req->getPost('u', 'trim|intval', 0);
		$charID= $this->_req->getPost('c', 'trim|intval', 0);

		// Clean it up like mother would.
		$context['checked_name'] = trim(preg_replace('~[\s]~u', ' ', $context['checked_name']));
		
		if (trim($context['checked_name']) == '' || Util::strlen($context['checked_name']) > 60)
		{
			$context['valid_name'] = false;
		}
		else
			$context['valid_name'] = !isReservedCharacterName($context['checked_name'], $context['id_member'], $context['id_character'], false);
	}

    /**
     * Registers a member to the forum.
     *
     * What it does:
     * - Allows two types of interface: 'guest' and 'admin'. The first
     * - includes hammering protection, the latter can perform the registration silently.
     * - The strings used in the options array are assumed to be escaped.
     * - Allows to perform several checks on the input, e.g. reserved names.
     * - The function will adjust member statistics.
     * - If an error is detected will fatal error on all errors unless return_errors is true.
     *
     * @package Members
     * @uses Auth.subs.php
     * @uses Mail.subs.php
     * @param $characterOptions
     * @param string $ErrorContext
     * @return integer the ID of the newly created member
     * @throws Exception
     */
	function createCharacter(&$characterOptions, $ErrorContext = 'character')
	{
		global $scripturl, $txt, $modSettings, $user_info;

		$db = database();

		loadLanguage('Login');

		// Put any errors in here.
		$character_errors = ErrorContext::context($ErrorContext, 0);

		// Some of these might be overwritten. (the lower ones that are in the arrays below.)
		$characterOptions += array(
			'posts' => 0,
			'date_created' =>  time(),
		);

		// Right, now let's prepare for insertion.
		$knownInts = array(
			'date_created', 'posts', 'id_member'
		);
		$knownFloats = array();

		$column_names = array();
		$values = array();
		foreach ($characterOptions as $var => $val)
		{
			$type = 'string';
			if (in_array($var, $knownInts))
				$type = 'int';
			elseif (in_array($var, $knownFloats))
				$type = 'float';
			elseif ($var == 'birthdate')
				$type = 'date';

			$column_names[$var] = $type;
			$values[$var] = $val;
		}

		// Register them into the database.
		$db->insert('',
			'{db_prefix}rps_characters',
			$column_names,
			$values,
			array('id_character')
		);
		$charID = $db->insert_id('{db_prefix}rps_character', 'id_character');

		// Update the number of members and latest member's info - and pass the name, but remove the 's.
	//	if ($regOptions['register_vars']['is_activated'] == 1)
	//		updateMemberStats($memberID, $regOptions['register_vars']['real_name']);
	//	else
	//		updateMemberStats();

		// If it's enabled, increase the registrations for today.
	//	trackStats(array('registers' => '+'));
		// If they are for sure registered, let other people to know about it
		call_integration_hook('integrate_create_after', array($characterOptions, $charID));

		return $charID;
	}

 

    /**
     * Gets a members minimum and maximum message id
     *
     * - Can limit the results to a particular board
     * - Used to help limit queries by proving start/stop points
     *
     * @param boolean $posts
     * @param int|null $board
     * @return array
     * @throws Exception
     */
	function findMinMaxCharacterMessage($posts = true, $board = null)
	{
		global $modSettings, $user_info;

		$db = database();

		$is_owner = $this->_memID == $user_info['id'];
		
		if ($posts)
		{
			$request = $db->query('', '
				SELECT MIN(id_msg), MAX(id_msg)
				FROM {db_prefix}messages AS m
				WHERE m.id_member = {int:current_member}
					AND m.id_character = {int:current_character}' . (!empty($board) ? '
					AND m.id_board = {int:board}' : '') . (!$modSettings['postmod_active'] || $is_owner ? '' : '
					AND m.approved = {int:is_approved}'),
				array(
					'current_member' => $this->_memID,
					'current_character' => $this->_charID,
					'is_approved' => 1,
					'board' => $board,
				)
			);
		}
		else 
		{
			$request = $db->query('', '
				SELECT MIN(id_topic), MAX(id_topic)
				FROM {db_prefix}topics AS t
					INNER JOIN {db_prefix}messages AS m ON (t.id_first_msg = m.id_msg)
				WHERE t.id_member_started = {int:current_member}
					AND m.id_character = {int:current_character}' . (!empty($board) ? '
					AND t.id_board = {int:board}' : '') . (!$modSettings['postmod_active'] || $is_owner ? '' : '
					AND t.approved = {int:is_approved}'),
				array(
					'current_member' => $this->_memID,
					'current_character' => $this->_charID,
					'is_approved' => 1,
					'board' => $board,
				)
			);
		}
		$minmax = $db->fetch_row($request);
		$db->free_result($request);

		return empty($minmax) ? array(0, 0) : $minmax;
	}


	
	/**
	 * Let them see what their signature looks like before they use it like spam
	 */
	public function action_sig_preview()
	{
		global $context, $txt, $user_info;

		loadTemplate('Xml');
		$context['sub_template'] = 'generic_xml';
		
		require_once(SUBSDIR . '/Profile.subs.php');
		loadLanguage('Profile');
		loadLanguage('Errors');

		$character = isset($this->_req->post->character) ? (int) $this->_req->post->character : 0;
		$is_owner =  is_array($user_info['characters']) ? in_array($character, $user_info['characters']) : ($user_info['characters'] == $character) ;

		// @todo Temporary
		// Borrowed from loadAttachmentContext in Display.controller.php
		$can_change = $is_owner ? allowedTo(array('profile_extra_any', 'profile_extra_own')) : allowedTo('profile_extra_any');

		$errors = array();
		if (!empty($character) && $can_change)
		{
			$db = database();
			
			$request = $db->query('', '
				SELECT signature
				FROM {db_prefix}rps_characters
				WHERE id_character = ({int:character})
				LIMIT 1',
				array(
					'character' => $character,
				)
			);
			while ($row = $db->fetch_assoc($request))
			{
					$signature = $row['signature'];
			}
			$db->free_result($request);

			$signature = censor($signature);
			$bbc_parser = \BBC\ParserWrapper::instance();
			$signature = $bbc_parser->parseSignature($signature, true);

			// And now what they want it to be
			$preview_signature = !empty($this->_req->post->signature) ? Util::htmlspecialchars($this->_req->post->signature) : '';
			$validation = profileValidateSignature($preview_signature);

			// An odd check for errors to be sure
			if ($validation !== true && $validation !== false)
				$errors[] = array('value' => $txt['profile_error_' . $validation], 'attributes' => array('type' => 'error'));

			preparsecode($preview_signature);
			$preview_signature = censor($preview_signature);
			$preview_signature = $bbc_parser->parseSignature($preview_signature, true);
		}
		// Sorry but you can't change the signature
		elseif (!$can_change)
		{
			if ($is_owner)
				$errors[] = array('value' => $txt['cannot_profile_extra_own'], 'attributes' => array('type' => 'error'));
			else
				$errors[] = array('value' => $txt['cannot_profile_extra_any'], 'attributes' => array('type' => 'error'));
		}
		else
			$errors[] = array('value' => $txt['no_user_selected'], 'attributes' => array('type' => 'error'));

		// Return the response for the template
		$context['xml_data']['signatures'] = array(
			'identifier' => 'signature',
			'children' => array()
		);

		if (isset($signature))
			$context['xml_data']['signatures']['children'][] = array(
				'value' => $signature,
				'attributes' => array('type' => 'current'),
			);

		if (isset($preview_signature))
			$context['xml_data']['signatures']['children'][] = array(
				'value' => $preview_signature,
				'attributes' => array('type' => 'preview'),
			);

		if (!empty($errors))
			$context['xml_data']['errors'] = array(
				'identifier' => 'error',
				'children' => array_merge(
						array(
					array(
						'value' => $txt['profile_errors_occurred'],
						'attributes' => array('type' => 'errors_occurred'),
					),
						), $errors
				),
			);
	}
	
	/**
	 * Does session and token checks for the areas that require those
	 */
	private function _check_access()
	{
		global $context;

		// Does this require session validating?
		if (!empty($this->_profile_include_data['validate'])
			|| (isset($this->_saving) && !$context['user']['is_owner']))
		{
			validateSession();
		}

		// Do we need to perform a token check?
		if (!empty($this->_profile_include_data['token']))
		{
			if ($this->_profile_include_data['token'] !== true)
			{
				$token_name = str_replace('%u', $context['id_member'], $this->_profile_include_data['token']);
			}
			else
			{
				$token_name = 'profile-u' . $context['id_member'];
			}

			if (isset($this->_profile_include_data['token_type']) && in_array($this->_profile_include_data['token_type'], array('request', 'post', 'get')))
			{
				$token_type = $this->_profile_include_data['token_type'];
			}
			else
			{
				$token_type = 'post';
			}

			if (isset($this->_saving))
			{
				validateToken($token_name, $token_type);
			}

			createToken($token_name, $token_type);
			$context['token_check'] = $token_name;
		}
	}

	/**
	 * Just builds the link tree based on where were are in the profile section
	 * and who's profile is being viewed, etc.
	 */
	private function _build_profile_linktree()
	{
		global $context, $scripturl, $txt, $user_info;

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=profile' . ($this->_memID != $user_info['id'] ? ';u=' . $this->_memID : ''),
			'name' => sprintf($txt['profile_of_username'], $context['member']['name']),
		);
		
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=character;c=' . $this->_charID,
			'name' => $context['character']['name'],
			);

		if (!empty($this->_profile_include_data['label']))
		{
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=character;c=' . $this->_charID . ';area=' . $this->_profile_include_data['current_area'],
				'name' => $this->_profile_include_data['label'],
			);
		}

		if (!empty($this->_profile_include_data['current_subsection']) && $this->_profile_include_data['subsections'][$this->_profile_include_data['current_subsection']][0] != $this->_profile_include_data['label'])
		{
			$context['linktree'][] = array(
				'url' => $scripturl . '?action=character;c=' . $this->_charID . ';area=' . $this->_profile_include_data['current_area'] . ';sa=' . $this->_profile_include_data['current_subsection'],
				'name' => $this->_profile_include_data['subsections'][$this->_profile_include_data['current_subsection']][0],
			);
		}
	}

}




