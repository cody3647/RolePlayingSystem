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
		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_charID = !empty($_REQUEST['c']) ? (int) $_REQUEST['c'] : 0;
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
	}
	
	/**
	 * Intended as entry point which delegates to methods in this class...
	 */
	public function action_index()
	{
		global $context;

		require_once(SUBSDIR . '/Action.class.php');

		// Little short on the list here
		$subActions = array(
			'summary' => array($this, 'action_summary'),
			'edit' => array($this, 'action_edit'),
			'create' => array($this, 'action_create', 'permission' => ''),
			'create2' => array($this, 'action_create2'),
			'checkname' => array($this, 'action_checkname'),
			'sig_preview' => array($this, 'action_sig_preview'),
		);
		
		// I don't think we know what to do... throw dies?
		$action = new Action();
		$subAction = $action->initialize($subActions, 'summary');
		
		$context['sub_action'] = $subAction;
		$action->dispatch($subAction);
	}
	
	public function action_summary()
	{
		global $context, $memberContext, $txt, $modSettings, $user_info, $user_profile, $scripturl, $settings;
		
		// Attempt to load the member's profile data.
		if (!loadMemberContext($this->_memID) || empty($context['character']))
			throw new Elk_Exception('not_a_user', false);

		loadTemplate('RpsCharacterProfile');

		$context['sub_template'] = 'action_summary';

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => sprintf($txt['profile_of_username'], $context['character']['name']),
			'can_send_pm' => allowedTo('pm_send'),
		);
		
		// Set a canonical URL for this page.
		$context['canonical_url'] = $scripturl . '?action=character;c=' . $this->_charID;
		$context['linktree']+=array(
			1=> array(
				'name' => 'Profile of ' . $context['member']['name'],
				'url' => $scripturl . '?action=profile;u=' . $this->_memID,
			),
			2 => array(
				'name' => $context['character']['name'],
				'url' => $context['canonical_url'],
			)
		);

		// Profile summary tabs, like Summary, Recent, Buddies
		$this->_register_summarytabs();

		// They haven't even been registered for a full day!?
		$days_registered = (int) ((time() - $context['character']['created']) / (3600 * 24));
		if (empty($context['character']['created']) || $days_registered < 1)
			$context['character']['posts_per_day'] = $txt['not_applicable'];
		else
			$context['character']['posts_per_day'] = comma_format($context['character']['real_posts'] / $days_registered, 3);

		// Set the age...
		if (empty($context['character']['birth_date']))
		{
			$context['character'] += array(
				'age' => $txt['not_applicable'],
				'today_is_birthday' => false
			);
		}
		else
		{
			list ($birth_year, $birth_month, $birth_day) = sscanf($context['character']['birth_date'], '%d-%d-%d');
			$datearray = getdate(forum_time());
			$context['character'] += array(
				'age' => $birth_year <= 4 ? $txt['not_applicable'] : $datearray['year'] - $birth_year - (($datearray['mon'] > $birth_month || ($datearray['mon'] == $birth_month && $datearray['mday'] >= $birth_day)) ? 0 : 1),
				'today_is_birthday' => $datearray['mon'] == $birth_month && $datearray['mday'] == $birth_day
			);
		}

		// Is the signature even enabled on this forum?
		$context['signature_enabled'] = substr($modSettings['signature_settings'], 0, 1) == 1;

		// How about thier most recent posts?
		if (in_array('posts', $this->_summary_areas))
		{
			// Is the load average too high just now, then let them know
			if (!empty($modSettings['loadavg_show_posts']) && $modSettings['current_load'] >= $modSettings['loadavg_show_posts'])
				$context['loadaverage'] = true;
			else
			{
				// Set up to get the last 10 psots of this member
				$msgCount = $this->count_character_posts();
				$range_limit = '';
				$maxIndex = 10;
				$start = (int) $_REQUEST['start'];

				// If they are a frequent poster, we guess the range to help minimize what the query work
				if ($msgCount > 1000)
				{
					list ($min_msg_member, $max_msg_member) = findMinMaxCharacterMessage();
					$margin = floor(($max_msg_member - $min_msg_member) * (($start + $modSettings['defaultMaxMessages']) / $msgCount) + .1 * ($max_msg_member - $min_msg_member));
					$range_limit = 'm.id_msg > ' . ($max_msg_member - $margin);
				}

				// Find this user's most recent posts
				$rows = $this->load_character_posts(true, 0, $maxIndex, $range_limit);
				$context['posts'] = array();
				foreach ($rows as $row)
				{
					// Censor....
					censorText($row['body']);
					censorText($row['subject']);

					// Do the code.
					$row['body'] = parse_bbc($row['body'], $row['smileys_enabled'], $row['id_msg']);
					$preview = strip_tags(strtr($row['body'], array('<br />' => '&#10;')));
					$preview = Util::shorten_text($preview, !empty($modSettings['ssi_preview_length']) ? $modSettings['ssi_preview_length'] : 128);
					$short_subject = Util::shorten_text($row['subject'], !empty($modSettings['ssi_subject_length']) ? $modSettings['ssi_subject_length'] : 24);

					// And the array...
					$context['posts'][] = array(
						'body' => $preview,
						'board' => array(
							'name' => $row['bname'],
							'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '.0">' . $row['bname'] . '</a>'
						),
						'subject' => $row['subject'],
						'short_subject' => $short_subject,
						'time' => standardTime($row['poster_time']),
						'html_time' => htmlTime($row['poster_time']),
						'timestamp' => forum_time(true, $row['poster_time']),
						'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '" rel="nofollow">' . $short_subject . '</a>',
					);
				}
			}
		}

		// How about the most recent topics that they started?
		if (in_array('topics', $this->_summary_areas))
		{
			// Is the load average still too high?
			if (!empty($modSettings['loadavg_show_posts']) && $modSettings['current_load'] >= $modSettings['loadavg_show_posts'])
				$context['loadaverage'] = true;
			else
			{
				// Set up to get the last 10 topics of this member
				$topicCount = $this->count_character_posts(false);
				$range_limit = '';
				$maxIndex = 10;

				// If they are a frequent topic starter we guess the range to help the query
				if ($topicCount > 1000)
				{
					list ($min_topic_member, $max_topic_member) = findMinMaxCharacterMessage(false);
					$margin = floor(($max_topic_member - $min_topic_member) * (($start + $modSettings['defaultMaxMessages']) / $topicCount) + .1 * ($max_topic_member - $min_topic_member));
					$margin *= 5;
					$range_limit = 't.id_first_msg > ' . ($max_topic_member - $margin);
				}

				// Find this user's most recent topics
				$rows = $this->load_character_posts(false, 0, $maxIndex, $range_limit);
				$context['topics'] = array();
				foreach ($rows as $row)
				{
					// Censor....
					censorText($row['body']);
					censorText($row['subject']);

					// Do the code.
					$short_subject = Util::shorten_text($row['subject'], !empty($modSettings['ssi_subject_length']) ? $modSettings['ssi_subject_length'] : 24);

					// And the array...
					$context['topics'][] = array(
						'board' => array(
							'name' => $row['bname'],
							'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '.0">' . $row['bname'] . '</a>'
						),
						'subject' => $row['subject'],
						'short_subject' => $short_subject,
						'time' => standardTime($row['poster_time']),
						'html_time' => htmlTime($row['poster_time']),
						'timestamp' => forum_time(true, $row['poster_time']),
						'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '" rel="nofollow">' . $short_subject . '</a>',
					);
				}
			}
		}

		// To make tabs work, we need jQueryUI
		$modSettings['jquery_include_ui'] = true;
		addInlineJavascript('start_tabs();', true);
		loadCSSFile('jquery.ui.tabs.css');
		loadJavascriptFile('profile.js');
	}
	
		/**
	 * Prepares the tabs for the profile summary page
	 *
	 * What it does:
	 * - Tab information for use in the summary page
	 * - Each tab template defines a div, the value of which are the template(s) to load in that div
	 * - array(array(1, 2), array(3, 4)) <div>template 1, template 2</div><div>template 3 template 4</div>
	 * - Templates are named template_profile_block_YOURNAME
	 * - Tabs with href defined will not preload/create any page divs but instead be loaded via ajax
	 */
	private function _register_summarytabs()
	{
		global $txt, $context, $modSettings, $scripturl;

		$context['summarytabs'] = array(
			'summary' => array(
				'name' => $txt['summary'],
				'templates' => array(
					array('summary', 'user_info'),

				),
				'active' => true,
			),
			'recent' => array(
				'name' => $txt['profile_recent_activity'],
				'templates' => array('posts', 'topics'),
				'active' => true,
				'href' => $scripturl . '?action=profileInfo;sa=recent;xml;u=' . $this->_memID . ';' . $context['session_var'] . '=' . $context['session_id'],
			),
		);

		// Let addons add or remove to the tabs array
		call_integration_hook('integrate_character_summary', array($this->_memID));

		// Go forward with whats left after integration adds or removes
		$summary_areas = '';
		foreach ($context['summarytabs'] as $id => $tab)
		{
			// If the tab is active we add it
			if ($tab['active'] !== true)
			{
				unset($context['summarytabs'][$id]);
			}
			else
			{
				// All the active templates, used to prevent processing data we don't need
				foreach ($tab['templates'] as $template)
				{
					$summary_areas .= is_array($template) ? implode(',', $template) : ',' . $template;
				}
			}
		}

		$this->_summary_areas = explode(',', $summary_areas);
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
		$context['form_action'] = $scripturl . '?action=character;sa=edit;c=' . $this->_charID;
		$context['page_title'] = $txt['rps_edit_character'] . $context['character']['name'];
		$context['linktree']+=array(
			1=> array(
				'name' => $txt['rps_profile_of'] . $context['member']['name'],
				'url' => $scripturl . '?action=profile;u=' . $this->_memID,
			),
			2 => array(
				'name' => $context['character']['name'],
				'url' => $scripturl . '?action=character;c=' . $this->_charID,
			),
			3 => array(
				'name' => $txt['rps_edit_character'] . $context['character']['name'],
			)
		);

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
     * Returns the total number of posts or new topics a user has made
     *
     * - Counts all posts or just the topics made on a particular board
     *
     * @param boolean $posts
     * @param int|null $board
     * @return integer
     * @throws Exception
     */

	function count_character_posts($posts=true, $board = null)
	{
		global $modSettings, $user_info;

		$db = database();

		$is_owner = $this->_memID == $user_info['id'];
		
		if($posts)
		{
			$request = $db->query('', '
				SELECT COUNT(*)
				FROM {db_prefix}messages AS m' . ($user_info['query_see_board'] == '1=1' ? '' : '
					INNER JOIN {db_prefix}boards AS b ON (b.id_board = m.id_board AND {query_see_board})') . '
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
				SELECT COUNT(*)
				FROM {db_prefix}topics AS t' . ($user_info['query_see_board'] == '1=1' ? '' : '
					INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board AND {query_see_board})') . '
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
		list ($msgCount) = $db->fetch_row($request);
		$db->free_result($request);

		return $msgCount;
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
     * Used to load all the posts of a user
     *
     * - Can limit to just the posts of a particular board
     * - If range_limit is supplied, will check if count results were returned, if not
     * will drop the limit and try again
     *
     * @param boolean $posts
     * @param int $start
     * @param int $count
     * @param string|null $range_limit
     * @param boolean $reverse
     * @param int|null $board
     * @return array
     * @throws Exception
     */
	function load_character_posts($posts=true, $start, $count, $range_limit = '', $reverse = false, $board = null)
	{
		global $modSettings, $user_info;

		$db = database();

		$is_owner = $this->_memID == $user_info['id'];
		$user_posts = array();
		
		if ($posts) 
		{
			// Find this user's posts. The left join on categories somehow makes this faster, weird as it looks.
			for ($i = 0; $i < 2; $i++)
			{
				$request = $db->query('', '
					SELECT
						b.id_board, b.name AS bname,
						c.id_cat, c.name AS cname,
						m.id_topic, m.id_msg, m.body, m.smileys_enabled, m.subject, m.poster_time, m.approved,
						t.id_member_started, t.id_first_msg, t.id_last_msg
					FROM {db_prefix}messages AS m
						INNER JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
						INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
						LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
					WHERE m.id_member = {int:current_member}
						AND m.id_character = {int:current_character}' . (!empty($board) ? '
						AND b.id_board = {int:board}' : '') . (empty($range_limit) ? '' : '
						AND ' . $range_limit) . '
						AND {query_see_board}' . (!$modSettings['postmod_active'] || $is_owner ? '' : '
						AND t.approved = {int:is_approved} AND m.approved = {int:is_approved}') . '
					ORDER BY m.id_msg ' . ($reverse ? 'ASC' : 'DESC') . '
					LIMIT ' . $start . ', ' . $count,
					array(
						'current_member' => $this->_memID,
						'current_character' => $this->_charID,
						'is_approved' => 1,
						'board' => $board,
					)
				);

				// Did we get what we wanted, if so stop looking
				if ($db->num_rows($request) === $count || empty($range_limit))
					break;
				else
					$range_limit = '';
			}
		}
		
		else
		{
				// Find this user's topics.  The left join on categories somehow makes this faster, weird as it looks.
			for ($i = 0; $i < 2; $i++)
			{
				$request = $db->query('', '
					SELECT
						b.id_board, b.name AS bname,
						c.id_cat, c.name AS cname,
						t.id_member_started, t.id_first_msg, t.id_last_msg, t.approved,
						m.body, m.smileys_enabled, m.subject, m.poster_time, m.id_topic, m.id_msg
					FROM {db_prefix}topics AS t
						INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
						LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
						INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
					WHERE t.id_member_started = {int:current_member}
						AND m.id_character = {int:current_character}' . (!empty($board) ? '
						AND t.id_board = {int:board}' : '') . (empty($range_limit) ? '' : '
						AND ' . $range_limit) . '
						AND {query_see_board}' . (!$modSettings['postmod_active'] || $is_owner ? '' : '
						AND t.approved = {int:is_approved} AND m.approved = {int:is_approved}') . '
					ORDER BY t.id_first_msg ' . ($reverse ? 'ASC' : 'DESC') . '
					LIMIT ' . $start . ', ' . $count,
					array(
						'current_member' => $this->_memID,
						'current_character' => $this->_charID,
						'is_approved' => 1,
						'board' => $board,
					)
				);

				// Did we get what we wanted, if so stop looking
				if ($db->num_rows($request) === $count || empty($range_limit))
					break;
				else
					$range_limit = '';
			}
		}
		
		// Place them in the post array
		while ($row = $db->fetch_assoc($request))
			$user_posts[] = $row;
		$db->free_result($request);

		return $user_posts;
	}
	
	private function _load_summary()
	{
		// Load all areas of interest in to context for template use
		$this->_determine_posts_per_day();
		$this->_determine_age_birth();
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
}




