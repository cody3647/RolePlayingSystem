<?php

/**
 * Handles the retrieving and display of a Character's information
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright: ElkArte Forum contributors
 * license:   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 */

/**
 * CharacterInfo Controller Class
 * Access all profile summary areas for a character including overall summary,
 * post listing
 */
class CharacterInfo_Controller extends Action_Controller
{
	/**
	 * Member id for the profile being worked with
	 * @var int
	 */
	private $_memID = 0;
	
	private $_charID = 0;

	/**
	 * Holds the current summary tabs to load
	 * @var array
	 */
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
		global $context, $user_info;

		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_charID = $this->_req->getQuery('c', 'intval', 0);
		$this->_memID = memberID($this->_charID);

		if (!isset($context['user']['is_owner']))
			$context['user']['is_owner'] = (int) $this->_memID === (int) $user_info['id'];

		loadLanguage('Profile');
	}

	/**
	 * Intended as entry point which delegates to methods in this class...
	 *
	 * - But here, today, for now, the methods are mainly called from other places
	 * like menu picks and the like.
	 */
	public function action_index()
	{
/*		global $context;

		// What do we do, do you even know what you do?
		$subActions = array(
			'recent' => array($this, 'action_profile_recent'),
			'summary' => array('file' => 'Profile.controller.php', 'dir' => CONTROLLERDIR, 'controller' => 'Profile_Controller', 'function' => 'action_index'),
		);

		// Action control
		$action = new Action('profile_info');

		// By default we want the summary
		$subAction = $action->initialize($subActions, 'summary');

		// Final bits
		$context['sub_action'] = $subAction;

		// Call the right function for this sub-action.
		$action->dispatch($subAction);*/
	}

public function action_summary()
	{
		global $context, $memberContext, $txt, $modSettings, $user_info, $user_profile, $scripturl, $settings;
		
		// Attempt to load the member's profile data.
		if (!loadMemberContext($this->_memID) || empty($context['character']))
			throw new Elk_Exception('not_a_user', false);

		loadTemplate('RpsCharacterInfo');

		$context['sub_template'] = 'action_rps_summary';
		
		$context['bio_tab'] = $this->_req->__isset('biography');

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => sprintf($txt['profile_of_username'], $context['character']['name']),
			'can_send_pm' => allowedTo('pm_send'),
		);
		
		// Set a canonical URL for this page.
		$context['canonical_url'] = $scripturl . '?action=character;c=' . $this->_charID;
		
		// Profile summary tabs, like Summary, Recent, Buddies
		$this->_register_summarytabs();

		// They haven't even been registered for a full day!?
		$days_registered = (int) ((time() - $context['character']['created_timestamp']) / (3600 * 24));
		if (empty($context['character']['created']) || $days_registered < 1)
			$context['character']['posts_per_day'] = $txt['not_applicable'];
		else
			$context['character']['posts_per_day'] = comma_format($context['character']['real_posts'] / $days_registered, 3);

		// Set the age...
		try {
			$birthdate = new DateTime($context['character']['birth_date']);
			$context['character']['birth_date'] =  $birthdate->format($user_info['datetime_format']);
			
			$rpsdate = RpsCurrentDate::instance();

			$context['character'] += array(
				'age' => $birthdate->diff($rpsdate->end_date, true)->y,
				'today_is_birthday' => $rpsdate->between($rpsdate->end_year . '-' . $birthdate->format('m-d')),
				'birth_datetime' => $birthdate->format('Y-m-d'),
			);
			
			
		}
		
		catch (Exception $e) {
			$context['character'] += array(
				'age' => $txt['not_applicable'],
				'today_is_birthday' => false
			);
		}
		
		// Is the signature even enabled on this forum?
		$context['signature_enabled'] = substr($modSettings['signature_settings'], 0, 1) == 1;

		// How about thier most recent posts?
		if (in_array('rps_posts', $this->_summary_areas))
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

				// If they are a frequent poster, we guess the range to help minimize what the query work
				if ($msgCount > 1000)
				{
					list ($min_msg_member, $max_msg_member) = findMinMaxCharacterMessage();
					$margin = floor(($max_msg_member - $min_msg_member) * (($modSettings['defaultMaxMessages']) / $msgCount) + .1 * ($max_msg_member - $min_msg_member));
					$range_limit = 'm.id_msg > ' . ($max_msg_member - $margin);
				}

				// Find this user's most recent posts
				$rows = $this->_load_character_posts(true, 0, $maxIndex, $range_limit);
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
		if (in_array('rps_topics', $this->_summary_areas))
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
					$margin = floor(($max_topic_member - $min_topic_member) * (($modSettings['defaultMaxMessages']) / $topicCount) + .1 * ($max_topic_member - $min_topic_member));
					$margin *= 5;
					$range_limit = 't.id_first_msg > ' . ($max_topic_member - $margin);
				}

				// Find this user's most recent topics
				$rows = $this->_load_character_posts(false, 0, $maxIndex, $range_limit);
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
		
		if(in_array('rps_biography', $this->_summary_areas))
		{
			$db = database();

			$request = $db->query('', '
				SELECT
					id_bio, id_character, approved, date_approved, date_added, biography
				FROM {db_prefix}rps_biographies
				WHERE id_character = {int:id_character}
				ORDER BY id_bio DESC',
				array(
					'id_character' => $this->_charID,
				)
			);
			
			$num_rows = $db->num_rows($request);
			
			if ($num_rows == 0)
			{
				
			}
			else
			{
				$bbc_wrapper = \BBC\ParserWrapper::instance();
				//0 = current approved, 1 = current bio not approved, 2 = no bio
				$context['bio_approved'] = 0;
				
				while ($row = $db->fetch_assoc($request))
					$context['biographies'][] = array(
						'id_bio' => $row['id_bio'],
						'id_character' => $row['id_character'],
						'approved' => $row['approved' ],
						'date_approved' => $row['date_approved'],
						'date_added' => $row['date_added'],
						'biography' => $bbc_wrapper->parseMessage(censor($row['biography']), false) ,
				);

				if($context['biographies'][0]['approved'] == 1)
				{
					$context['biography'] = $context['biographies'][0];
				}
				elseif($num_rows > 0)
				{
					$context['unapproved_biography'] = $context['biographies'][0]['biography'];
					$context['bio_approved'] = 1;
					foreach($context['biographies'] as $key => $bio)
					{
						if($bio['approved'] == 1)
						{
							$context['biography'] = $bio;
						}
					}
				}
				else {
					$context['bio_approved'] = 2;
				}
			}
			
			$db->free_result($request);

			
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
					array('rps_summary', 'rps_user_info'),
					array('rps_posts', 'rps_topics'),
				),
				'active' => true,
			),
			'biography' => array(
				'name' => $txt['rps_profile_biography_title'],
				'templates' => array(
					array('rps_biography'),
				),
				'active' => true,
			),

		);
		
		if(empty($context['character']['approved']))
		{
			array_unshift($context['summarytabs']['summary']['templates'], array('rps_unapproved'));
		}

		// Let addons add or remove to the tabs array
		call_integration_hook('integrate_character_summary', array($this->_memID, $this->_charID));

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
					$summary_areas .= is_array($template) ? ',' . implode(',', $template) : ',' . $template;
				}
			}
		}
		
		$this->_summary_areas = explode(',', substr($summary_areas,1));
	}

	/**
	 * Show all posts by the current user.
	 *
	 * @todo This function needs to be split up properly.
	 */
	public function action_showPosts()
	{
		global $txt, $user_info, $scripturl, $modSettings, $context, $user_profile, $board;
		loadTemplate('RpsCharacterInfo');
		
		// Some initial context.
		$context['start'] = $this->_req->getQuery('start', 'intval', 0);
		$context['current_character'] = $this->_charID;
		$context['sub_template'] = 'action_rps_showPosts';

		// What are we viewing
		$action = $this->_req->getQuery('area', 'trim', '');
		$action_title = array('showposts' => 'showPosts', 'showtopics' => 'showTopics');
		$action_title = $action_title[$action];

		


		// Set the page title

		$context['page_title'] = $txt[$action_title] . ' - ' . $user_profile[$this->_memID]['characters'][$this->_charID]['name'];

		// Is the load average too high to allow searching just now?
		if (!empty($modSettings['loadavg_show_posts']) && $modSettings['current_load'] >= $modSettings['loadavg_show_posts'])
			throw new Elk_Exception('loadavg_show_posts_disabled', false);

		// Are we just viewing topics?
		$context['is_topics'] = $action === 'showtopics' ? true : false;

		// If just deleting a message, do it and then redirect back.
		if (isset($this->_req->query->delete) && !$context['is_topics'])
		{
			checkSession('get');

			// We can be lazy, since removeMessage() will check the permissions for us.
			$remover = new MessagesDelete($modSettings['recycle_enable'], $modSettings['recycle_board']);
			$remover->removeMessage((int) $this->_req->query->delete);

			// Back to... where we are now ;).
			redirectexit('action=profile;u=' . $this->_memID . ';area=showposts;start=' . $context['start']);
		}

		if ($context['is_topics'])
			$msgCount = count_user_topics($this->_memID, $board);
		else
			$msgCount = count_user_posts($this->_memID, $board);

		list ($min_msg_member, $max_msg_member) = findMinMaxUserMessage($this->_memID, $board);
		$range_limit = '';
		$maxIndex = (int) $modSettings['defaultMaxMessages'];

		// Make sure the starting place makes sense and construct our friend the page index.
		$context['page_index'] = constructPageIndex($scripturl . '?action=profile;u=' . $this->_memID . ';area=showposts' . ($context['is_topics'] ? ';sa=topics' : ';sa=messages') . (!empty($board) ? ';board=' . $board : ''), $context['start'], $msgCount, $maxIndex);
		$context['current_page'] = $context['start'] / $maxIndex;

		// Reverse the query if we're past 50% of the pages for better performance.
		$start = $context['start'];
		$reverse = $this->_req->getQuery('start', 'intval', 0) > $msgCount / 2;
		if ($reverse)
		{
			$maxIndex = $msgCount < $context['start'] + $modSettings['defaultMaxMessages'] + 1 && $msgCount > $context['start'] ? $msgCount - $context['start'] : (int) $modSettings['defaultMaxMessages'];
			$start = $msgCount < $context['start'] + $modSettings['defaultMaxMessages'] + 1 || $msgCount < $context['start'] + $modSettings['defaultMaxMessages'] ? 0 : $msgCount - $context['start'] - $modSettings['defaultMaxMessages'];
		}

		// Guess the range of messages to be shown to help minimize what the query needs to do
		if ($msgCount > 1000)
		{
			$margin = floor(($max_msg_member - $min_msg_member) * (($start + $modSettings['defaultMaxMessages']) / $msgCount) + .1 * ($max_msg_member - $min_msg_member));

			// Make a bigger margin for topics only.
			if ($context['is_topics'])
			{
				$margin *= 5;
				$range_limit = $reverse ? 't.id_first_msg < ' . ($min_msg_member + $margin) : 't.id_first_msg > ' . ($max_msg_member - $margin);
			}
			else
				$range_limit = $reverse ? 'm.id_msg < ' . ($min_msg_member + $margin) : 'm.id_msg > ' . ($max_msg_member - $margin);
		}

		// Find this user's posts or topics started
		if ($context['is_topics'])
			$rows = load_user_topics($this->_memID, $start, $maxIndex, $range_limit, $reverse, $board);
		else
			$rows = load_user_posts($this->_memID, $start, $maxIndex, $range_limit, $reverse, $board);

		// Start counting at the number of the first message displayed.
		$counter = $reverse ? $context['start'] + $maxIndex + 1 : $context['start'];
		$context['posts'] = array();
		$board_ids = array('own' => array(), 'any' => array());
		$bbc_parser = \BBC\ParserWrapper::instance();
		foreach ($rows as $row)
		{
			// Censor....
			$row['body'] = censor($row['body']);
			$row['subject'] = censor($row['subject']);

			// Do the code.
			$row['body'] = $bbc_parser->parseMessage($row['body'], $row['smileys_enabled']);

			// And the array...
			$context['posts'][$counter += $reverse ? -1 : 1] = array(
				'body' => $row['body'],
				'counter' => $counter,
				'alternate' => $counter % 2,
				'category' => array(
					'name' => $row['cname'],
					'id' => $row['id_cat']
				),
				'board' => array(
					'name' => $row['bname'],
					'id' => $row['id_board'],
					'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '.0">' . $row['bname'] . '</a>',
				),
				'topic' => array(
					'id' => $row['id_topic'],
					'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#msg' . $row['id_msg'] . '">' . $row['subject'] . '</a>',
				),
				'subject' => $row['subject'],
				'start' => 'msg' . $row['id_msg'],
				'time' => standardTime($row['poster_time']),
				'html_time' => htmlTime($row['poster_time']),
				'timestamp' => forum_time(true, $row['poster_time']),
				'id' => $row['id_msg'],
				'tests' => array(
					'can_reply' => false,
					'can_mark_notify' => false,
					'can_delete' => false,
				),
				'delete_possible' => ($row['id_first_msg'] != $row['id_msg'] || $row['id_last_msg'] == $row['id_msg']) && (empty($modSettings['edit_disable_time']) || $row['poster_time'] + $modSettings['edit_disable_time'] * 60 >= time()),
				'approved' => $row['approved'],

				'buttons' => array(
					// How about... even... remove it entirely?!
					'remove' => array(
						'href' => $scripturl . '?action=deletemsg;msg=' . $row['id_msg'] . ';topic=' . $row['id_topic'] . ';profile;u=' . $context['member']['id'] . ';start=' . $context['start'] . ';' . $context['session_var'] . '=' . $context['session_id'],
						'text' => $txt['remove'],
						'test' => 'can_delete',
						'custom' => 'onclick="return confirm(' . JavaScriptEscape($txt['remove_message'] . '?') . ');"',
					),
					// Can we request notification of topics?
					'notify' => array(
						'href' => $scripturl . '?action=notify;topic=' . $row['id_topic'] . '.msg' . $row['id_msg'],
						'text' => $txt['notify'],
						'test' => 'can_mark_notify',
					),
					// If they *can* reply?
					'reply' => array(
						'href' => $scripturl . '?action=post;topic=' . $row['id_topic'] . '.msg' . $row['id_msg'],
						'text' => $txt['reply'],
						'test' => 'can_reply',
					),
					// If they *can* quote?
					'quote' => array(
						'href' => $scripturl . '?action=post;topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . ';quote=' . $row['id_msg'],
						'text' => $txt['quote'],
						'test' => 'can_quote',
					),
				)
			);

			if ($user_info['id'] == $row['id_member_started'])
				$board_ids['own'][$row['id_board']][] = $counter;
			$board_ids['any'][$row['id_board']][] = $counter;
		}

		// All posts were retrieved in reverse order, get them right again.
		if ($reverse)
			$context['posts'] = array_reverse($context['posts'], true);

		// These are all the permissions that are different from board to board..
		if ($context['is_topics'])
			$permissions = array(
				'own' => array(
					'post_reply_own' => 'can_reply',
				),
				'any' => array(
					'post_reply_any' => 'can_reply',
					'mark_any_notify' => 'can_mark_notify',
				)
			);
		else
			$permissions = array(
				'own' => array(
					'post_reply_own' => 'can_reply',
					'delete_own' => 'can_delete',
				),
				'any' => array(
					'post_reply_any' => 'can_reply',
					'mark_any_notify' => 'can_mark_notify',
					'delete_any' => 'can_delete',
				)
			);

		// For every permission in the own/any lists...
		foreach ($permissions as $type => $list)
		{
			foreach ($list as $permission => $allowed)
			{
				// Get the boards they can do this on...
				$boards = boardsAllowedTo($permission);

				// Hmm, they can do it on all boards, can they?
				if (!empty($boards) && $boards[0] == 0)
					$boards = array_keys($board_ids[$type]);

				// Now go through each board they can do the permission on.
				foreach ($boards as $board_id)
				{
					// There aren't any posts displayed from this board.
					if (!isset($board_ids[$type][$board_id]))
						continue;

					// Set the permission to true ;).
					foreach ($board_ids[$type][$board_id] as $counter)
						$context['posts'][$counter]['tests'][$allowed] = true;
				}
			}
		}

		// Clean up after posts that cannot be deleted and quoted.
		$quote_enabled = empty($modSettings['disabledBBC']) || !in_array('quote', explode(',', $modSettings['disabledBBC']));
		foreach ($context['posts'] as $counter => $dummy)
		{
			$context['posts'][$counter]['tests']['can_delete'] &= $context['posts'][$counter]['delete_possible'];
			$context['posts'][$counter]['tests']['can_quote'] = $context['posts'][$counter]['tests']['can_reply'] && $quote_enabled;
		}
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
	private function _load_character_posts($posts=true, $start, $count, $range_limit = '', $reverse = false)
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
					WHERE m.id_character = {int:current_character}' . (empty($range_limit) ? '' : '
						AND ' . $range_limit) . '
						AND {query_see_board}' . (!$modSettings['postmod_active'] || $is_owner ? '' : '
						AND t.approved = {int:is_approved} AND m.approved = {int:is_approved}') . '
						AND b.in_character = 1
					ORDER BY m.id_msg ' . ($reverse ? 'ASC' : 'DESC') . '
					LIMIT ' . $start . ', ' . $count,
					array(
						'current_member' => $this->_memID,
						'current_character' => $this->_charID,
						'is_approved' => 1,
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
						INNER JOIN {db_prefix}messages AS m ON (t.id_topic = m.id_topic)
					WHERE m.id_character = {int:current_character}' . (empty($range_limit) ? '' : '
						AND ' . $range_limit) . '
						AND {query_see_board}' . (!$modSettings['postmod_active'] || $is_owner ? '' : '
						AND t.approved = {int:is_approved} AND m.approved = {int:is_approved}') . '
						AND b.in_character = 1
					GROUP BY t.id_topic
					ORDER BY t.id_first_msg ' . ($reverse ? 'ASC' : 'DESC') . '
					LIMIT ' . $start . ', ' . $count,
					array(
						'current_member' => $this->_memID,
						'current_character' => $this->_charID,
						'is_approved' => 1,
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
}
