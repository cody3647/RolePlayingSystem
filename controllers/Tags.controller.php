<?php

/**
 * Controller for all non-admin tag functions.  Tag index is based on message index
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */
 

use ElkArte\Errors\ErrorContext;

class Tags_Controller extends Action_Controller
{
	private $_id = null;
	private $_tag = array();
	private $_start = 0;
	
	public function pre_dispatch()
	{
		global $modSettings;
		if (isset($this->_req->api))
			return;
		require_once(SUBSDIR . '/Tags.subs.php');
		
		loadTemplate('RpsTags');
		// Is the Role Playing System Enabled?
		if (empty($modSettings['rps_enabled']))
			throw new Elk_Exception('feature_disabled', true);
		
		
	}
	
	/**
	 * Intended as entry point which delegates to methods in this class...
	 */
	public function action_index()
	{
		global $context;

		if(isset($this->_req->api))
			$this->action_fetch();
		elseif(isset($this->_req->topic))
		{
			$this->action_edit_topic($this->_req->topic);
		}
		elseif(isset($this->_req->tag))
		{
			if (strpos($this->_req->tag, '.') !== false)
				list ($this->_id, $this->_start) = explode('.', $this->_req->tag);
			else
				$this->_id = $this->_req->tag;
			// Now make absolutely sure it's a number.
			$this->_id = (int) $this->_id;
			if (empty($this->_id))
				throw new Elk_Exception('RolePlayingSystem.rps_no_tag', false);

			$this->_start =  (int) $this->_start;
			$this->_tag = loadTag($this->_id);

			if (empty($this->_tag) || empty($this->_tag['name']))
				throw new Elk_Exception('RolePlayingSystem.rps_no_tag', false);
			
			$this->action_tagindex();
		}
		else 
			$this->action_tags_list();
	}
	
	
	public function action_fetch()
	{
		global $context, $txt;

		// Start off with nothing
		$_req = HttpReq::instance();
		$search_tag = $_req->getQuery('term', 'trim|strval', '');	

		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
		$template_layers = Template_Layers::instance();
		$template_layers->removeAll();
		$context['json_data'] = array();
		
		if (empty($search_tag))
		{
			return;
		}
		
		$errors = array();
		$db = database();
		$request = $db->query('', '
			SELECT tag, id_tag
			FROM {db_prefix}rps_tags
			WHERE tag REGEXP {string:search_tag}',
			array(
				'search_tag' => '[[:<:]]' . $search_tag,
			)
		);
		while ($row = $db->fetch_assoc($request))
			$context['json_data'][] = un_htmlspecialchars($row['tag']);
		$db->free_result($request);

	}
	
	public function action_edit_topic($topic)
	{
		global $context, $scripturl, $txt, $user_info;
		
		if(isset($this->_req->save))
		{
			$timestamp = time();
			$input_tags = $this->_req->getPost('tags', 'trim|Util::htmlspecialchars[ENT_QUOTES]');
			$remove_tags = $this->_req->getPost('remove');

			require_once(SUBSDIR . '/Tags.subs.php');
			save_tags($input_tags, $topic, $user_info['id'], $timestamp);
			remove_tags($remove_tags, $topic);
			
			
			//redirectexit('topic=' . $topic . ';updatetags');
		}
		
		$db = database();
		$request = $db->query('', '
			SELECT
			t.id_first_msg, t.date_tag,
			mf.subject AS first_subject
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
		WHERE t.id_topic = {int:id_topic}
		LIMIT 1',
			array(
				'id_topic' => $topic,
			)
		);
		while ($row = $db->fetch_assoc($request))
			$context['topic_subject'] = un_htmlspecialchars($row['first_subject']);
		$db->free_result($request);
		

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_tags_add_remove_title'] . $context['topic_subject'],
			'canonical_url' => $scripturl . '?action=tags;topic=' . $topic,
			'sub_template' => 'edit_topic',
		);

		$context['linktree'][] = array(
				'name' => $context['topic_subject'],
				'url' => $scripturl . '?topic=' . $topic,
			);
			
		$context['linktree'][] = array(
				'name' => $txt['rps_tags_add_remove_linktree'],
				'url' => $context['canonical_url'],
			);
		
		createToken('rps-tags');
		
				// Create a listing for all our standard fields
		$listOptions = array(
			'id' => 'edit_topic',
			'title' => $txt['rps_tags_remove_list'] . $context['topic_subject'],
			'base_href' => $scripturl . '?action=tags;topic=' . $topic,
			'items_per_page' => 25,
			'default_sort_col' => 'tag',
			'no_items_label' => $txt['rps_tags_remove_list_none'],
			'items_per_page' => 50,
			'get_items' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getTags',
				'params' => array(
					'td.id_topic = ' . $topic,
				),
			),
			'get_count' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getNumTopicTags',
				'params' => array(
					$topic,
				),
			),
			'columns' => array(
				'tag' => array(
					'header' => array(
						'value' => $txt['rps_tags_list_tag'],
					),
					'data' => array(
						'db' => 'tag',
						'style' => 'width: 60%;',
					),
					'sort' => array(
						'default' => 'tag',
						'reverse' => 'tag DESC',
					),
				),
				'remove' => array(
					'header' => array(
						'value' => $txt['rps_tags_list_removetag'],
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="remove[]" id="remove_%1$s" value="%1$s" class="input_check" />',
							'params' => array(
								'id_tag' => false
							),
						),
						'class' => 'centertext',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=tags;topic='. $topic,
				'name' => 'removeTags',
				'token' => 'rps-tags',
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '
					<h2 class="category_header">'. $txt['rps_tags_add_tags'] . $context['topic_subject']. '</h2>
					<div class="post_tags tags_bottom_of_list">
						<label for="tags" id="caption_tags">'. $txt['rps_tags_add_tags_label'].'</label>
						<input id="post_tags" size="80" type="text" name="tags" '. (isset($context['tags']) ? 'value="'.$context['tags'].'"' : '' ) . ' />
					</div>
								',
				),
				array(
					'position' => 'bottom_of_list',
					'value' => '<input type="submit" name="save" value="' . $txt['rps_save_changes'] . '" class="right_submit" />',
				),
			),
		);
		createList($listOptions);
	}
	
	public function action_tags_list()
	{
		global $txt, $scripturl, $context;

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_tags_title'],
			'canonical_url' => $scripturl . '?action=tags',
			'sub_template' => 'tags_list',
		);

		$context['linktree']+=array(
			1=> array(
				'name' => $txt['rps_tags_title'] ,
				'url' => $scripturl . '?action=tags',
			),
		);
		
		// And now we do the same for all of our custom ones
		$listOptions = array(
			'id' => 'tags_list',
			'title' => $txt['rps_tags_list'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'tag',
			'no_items_label' => $txt['rps_tags_list_none'],
			'items_per_page' => 25,
			'sortable' => true,
			'get_items' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getTags',
			),
			'get_count' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getNumTags',
			),
			'columns' => array(
				'tag' => array(
					'header' => array(
						'value' => $txt['rps_tags_tag'],
						'style' => 'width: 30%;',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<div class="rps_topic"><h4><a href="'. $scripturl .'?action=tags;tag=%1$d">%2$s</a></h4></div>', 
							'params' => array(
								'id_tag' => false, 
								'tag' => false,
							),
						),
					),
					'sort' => array(
						'default' => 'tag',
						'reverse' => 'tag DESC',
					),
				),
				'tag_count' => array(
					'header' => array(
						'value' => $txt['rps_tags_number'],
						'class' => 'centertext',
						'style' => 'width: 10%;',
					),
					'data' => array(
						'db' => 'tag_count',
						'comma_format' => true,
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'tag_count DESC',
						'reverse' => 'tag_count',
					),
				),
			),
		);

		createList($listOptions);
	}
	
	/**
	 * Show the list of topics with a tag.
	 *
	 * @uses template_topic_listing() sub template of the MessageIndex template
	 */
	public function action_tagindex()
	{
		global $txt, $scripturl, $board, $modSettings, $context;
		global $options, $settings, $board_info, $user_info;
		
		// Fairly often, we'll work with boards. Current board, sub-boards.
		require_once(SUBSDIR . '/Boards.subs.php');
		
		loadTemplate('MessageIndex');
		loadJavascriptFile('topic.js');
		
		$bbc = \BBC\ParserWrapper::instance();
		$context['name'] = $txt['rps_tag_title'] . $this->_tag['name'];
		$context['sub_template'] = 'topic_listing';
		$context['description'] = $bbc->parseBoard($this->_tag['description']);
		
		$template_layers = Template_Layers::instance();
		// How many topics do we have in total?
		//$board_info['total_topics'] = allowedTo('approve_posts') ? $board_info['num_topics'] + $board_info['unapproved_topics'] : $board_info['num_topics'] + $board_info['unapproved_user_topics'];
		$total_topics = getNumTaggedTopics($this->_id);
		
		// View all the topics, or just a few?
		$context['topics_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['topics_per_page']) ? $options['topics_per_page'] : $modSettings['defaultMaxTopics'];
		$context['messages_per_page'] = empty($modSettings['disableCustomPerPage']) && !empty($options['messages_per_page']) ? $options['messages_per_page'] : $modSettings['defaultMaxMessages'];
		$maxindex = isset($this->_req->query->all) && !empty($modSettings['enableAllMessages']) ? $total_topics : $context['topics_per_page'];

		// Right, let's only index normal stuff!
		$session_name = session_name();
		
		foreach ($this->_req->query as $k => $v)
		{
			// Don't index a sort result etc.
			if (!in_array($k, array('tag', 'start', $session_name)))
				$context['robot_no_index'] = true;
		}
		
		if (!empty($this->_start) && (!is_numeric($this->_start) || $this->_start % $context['messages_per_page'] != 0))
		{
			$context['robot_no_index'] = true;
		}
		
/*		// If we can view unapproved messages and there are some build up a list.
		if (allowedTo('approve_posts') && ($board_info['unapproved_topics'] || $board_info['unapproved_posts']))
		{
			$untopics = $board_info['unapproved_topics'] ? '<a href="' . $scripturl . '?action=moderate;area=postmod;sa=topics;brd=' . $board . '">' . $board_info['unapproved_topics'] . '</a>' : 0;
			$unposts = $board_info['unapproved_posts'] ? '<a href="' . $scripturl . '?action=moderate;area=postmod;sa=posts;brd=' . $board . '">' . ($board_info['unapproved_posts'] - $board_info['unapproved_topics']) . '</a>' : 0;
			$context['unapproved_posts_message'] = sprintf($txt['there_are_unapproved_topics'], $untopics, $unposts, $scripturl . '?action=moderate;area=postmod;sa=' . ($board_info['unapproved_topics'] ? 'topics' : 'posts') . ';brd=' . $board);
		} */
		
		// We only know these.
		if (isset($this->_req->query->sort) && !in_array($this->_req->query->sort, array('subject', 'starter', 'last_poster', 'replies', 'views', 'likes', 'first_post', 'last_post')))
			$this->_req->query->sort = 'last_post';
		
		// Make sure the starting place makes sense and construct the page index.
		if (isset($this->_req->query->sort))
			$sort_string = ';sort=' . $this->_req->query->sort . (isset($this->_req->query->desc) ? ';desc' : '');
		else
			$sort_string = '';
		
		$context['page_index'] = constructPageIndex($scripturl . '?action=tags;tag=' . $this->_id . '.%1$d' . $sort_string, $this->_start, $total_topics, $maxindex, true);
		
		$context['start'] = &$this->_start;
		
		// Set a canonical URL for this page.
		$context['canonical_url'] = $scripturl . '?action=tags;tag=' . $this->_id . '.' . $context['start'];
		
		$context['links'] += array(
			'prev' => $this->_start >= $context['topics_per_page'] ? $scripturl . '?action=tags;tag=' . $this->_id . '.' . ($this->_start - $context['topics_per_page']) : '',
			'next' => $this->_start + $context['topics_per_page'] < $total_topics ? $scripturl . '?action=tags;tag=' . $this->_id . '.' . ($this->_start + $context['topics_per_page']) : '',
		);
		$context['page_info'] = array(
			'current_page' => $this->_start / $context['topics_per_page'] + 1,
			'num_pages' => floor(($total_topics - 1) / $context['topics_per_page']) + 1
		);
		$context['linktree']+=array(
			1=> array(
				'name' => $txt['rps_tags_title'],
				'url' => $scripturl . '?action=tags',
			),
			2 => array(
				'name' => $this->_tag['name'],
				'url' => $context['canonical_url'],
			)
		);
		
		if (isset($this->_req->query->all) && !empty($modSettings['enableAllMessages']) && $maxindex > $modSettings['enableAllMessages'])
		{
			$maxindex = $modSettings['enableAllMessages'];
			$this->_start = 0;
		}
/*		// Build a list of the board's moderators.
		$context['moderators'] = &$board_info['moderators'];
		$context['link_moderators'] = array();
		if (!empty($board_info['moderators']))
		{
			foreach ($board_info['moderators'] as $mod)
				$context['link_moderators'][] = '<a href="' . $scripturl . '?action=profile;u=' . $mod['id'] . '" title="' . $txt['board_moderator'] . '">' . $mod['name'] . '</a>';
		}
*/
		// Mark current and parent boards as seen.
		if (!$user_info['is_guest'])
		{
			// We can't know they read it if we allow prefetches.
			stop_prefetching();
/*			// Mark the board as read, and its parents.
			if (!empty($board_info['parent_boards']))
			{
				$board_list = array_keys($board_info['parent_boards']);
				$board_list[] = $board;
			}
			else
				$board_list = array($board);
			// Mark boards as read. Boards alone, no need for topics.
			markBoardsRead($board_list, false, false);
			// Clear topicseen cache
			if (!empty($board_info['parent_boards']))
			{
				// We've seen all these boards now!
				foreach ($board_info['parent_boards'] as $k => $dummy)
				{
					if (isset($_SESSION['topicseen_cache'][$k]))
					{
						unset($_SESSION['topicseen_cache'][$k]);
					}
				}
			}
			if (isset($_SESSION['topicseen_cache'][$board]))
			{
				unset($_SESSION['topicseen_cache'][$board]);
			}
			// From now on, they've seen it. So we reset notifications.
			$context['is_marked_notify'] = resetSentBoardNotification($user_info['id'], $board);
			*/
		}
		//else
			$context['is_marked_notify'] = false;
		// 'Print' the header and board info.
		$context['page_title'] = $txt['rps_tag_title'] . strip_tags($this->_tag['name']);
		// Set the variables up for the template.
		$context['can_mark_notify'] = false && allowedTo('mark_notify') && !$user_info['is_guest'];
		$context['can_post_new'] = false && allowedTo('post_new') || ($modSettings['postmod_active'] && allowedTo('post_unapproved_topics'));
		$context['can_post_poll'] = false && !empty($modSettings['pollMode']) && allowedTo('poll_post') && $context['can_post_new'];
		$context['can_moderate_forum'] = false && allowedTo('moderate_forum');
		$context['can_approve_posts'] = false && allowedTo('approve_posts');
		
/*		// Prepare sub-boards for display.
		$boardIndexOptions = array(
			'include_categories' => false,
			'base_level' => $board_info['child_level'] + 1,
			'parent_id' => $board_info['id'],
			'set_latest_post' => false,
			'countChildPosts' => !empty($modSettings['countChildPosts']),
		);
		$boardlist = new Boards_List($boardIndexOptions);
		$context['boards'] = $boardlist->getBoards();
*/
		// Nosey, nosey - who's viewing this board?
		if (!empty($settings['display_who_viewing']))
		{
			require_once(SUBSDIR . '/Who.subs.php');
			formatViewers($this->_id, 'tags');
		}
		// And now, what we're here for: topics!
		require_once(SUBSDIR . '/MessageIndex.subs.php');
		
		// Known sort methods.
		$sort_methods = messageIndexSort();
		
		// They didn't pick one, default to by last post descending.
		if (!isset($this->_req->query->sort) || !isset($sort_methods[$this->_req->query->sort]))
		{
			$context['sort_by'] = 'last_post';
			$ascending = isset($this->_req->query->asc);
		}
		// Otherwise sort by user selection and default to ascending.
		else
		{
			$context['sort_by'] = $this->_req->query->sort;
			$ascending = !isset($this->_req->query->desc);
		}
		
		$sort_column = $sort_methods[$context['sort_by']];
		$context['sort_direction'] = $ascending ? 'up' : 'down';
		$context['sort_title'] = $ascending ? $txt['sort_desc'] : $txt['sort_asc'];
		
		// Trick
		$txt['starter'] = $txt['started_by'];
		
		// todo: Need to move this to theme.
		foreach ($sort_methods as $key => $val)
		{
			switch ($key)
			{
				case 'subject':
				case 'starter':
				case 'last_poster':
					$sorticon = 'alpha';
					break;
				default:
					$sorticon = 'numeric';
			}
			$context['topics_headers'][$key] = array(
				'url' => $scripturl . '?action=tags;tag=' . $this->_id . '.' . $context['start'] . ';sort=' . $key . ($context['sort_by'] == $key && $context['sort_direction'] === 'up' ? ';desc' : ''),
				'sort_dir_img' => $context['sort_by'] == $key ? '<i class="icon icon-small i-sort-' . $sorticon . '-' . $context['sort_direction'] . '" title="' . $context['sort_title'] . '"><s>' . $context['sort_title'] . '</s></i>' : '',
			);
		}
		// Calculate the fastest way to get the topics.
		$start = (int) $this->_start;
		if ($start > ($total_topics - 1) / 2)
		{
			$ascending = !$ascending;
			$fake_ascending = true;
			$maxindex = $total_topics < $start + $maxindex + 1 ? $total_topics - $start : $maxindex;
			$start = $total_topics < $start + $maxindex + 1 ? 0 : $total_topics - $start - $maxindex;
		}
		else
			$fake_ascending = false;
		$context['topics'] = array();
		// Set up the query options
		$indexOptions = array(
			'only_approved' => $modSettings['postmod_active'] && !allowedTo('approve_posts'),
			'previews' => !empty($modSettings['message_index_preview']) ? (empty($modSettings['preview_characters']) ? -1 : $modSettings['preview_characters']) : 0,
			'include_avatars' => $settings['avatars_on_indexes'],
			'ascending' => $ascending,
			'fake_ascending' => $fake_ascending
		);
		// Allow integration to modify / add to the $indexOptions
		call_integration_hook('integrate_tagindex_topics', array(&$sort_column, &$indexOptions));

		$topics_info = tagIndexTopics($this->_id, $user_info['id'], $start, $maxindex, $context['sort_by'], $sort_column, $indexOptions);
		$context['topics'] = Topic_Util::prepareContext($topics_info, false, !empty($modSettings['preview_characters']) ? $modSettings['preview_characters'] : 128);
		// Allow addons to add to the $context['topics']
		call_integration_hook('integrate_tagindex_listing', array($topics_info));
		
		// Fix the sequence of topics if they were retrieved in the wrong order. (for speed reasons...)
		if ($fake_ascending)
			$context['topics'] = array_reverse($context['topics'], true);
		$topic_ids = array_keys($context['topics']);
		if (!empty($modSettings['enableParticipation']) && !$user_info['is_guest'] && !empty($topic_ids))
		{
			$topics_participated_in = topicsParticipation($user_info['id'], $topic_ids);
			foreach ($topics_participated_in as $participated)
			{
				$context['topics'][$participated['id_topic']]['is_posted_in'] = true;
				$context['topics'][$participated['id_topic']]['class'] = 'my_' . $context['topics'][$participated['id_topic']]['class'];
			}
		}
		$context['current_board'] = 0;
		$context['jump_to'] = array(
			'label' => addslashes(un_htmlspecialchars($txt['jump_to'])),
			'board_name' => htmlspecialchars(strtr(strip_tags($this->_tag['name']), array('&amp;' => '&')), ENT_COMPAT, 'UTF-8'),
			'child_level' => 0,
		);
/*		// Is Quick Moderation active/needed?
		if (!empty($options['display_quick_mod']) && !empty($context['topics']))
		{
			$context['can_markread'] = $context['user']['is_logged'];
			$context['can_lock'] = allowedTo('lock_any');
			$context['can_sticky'] = allowedTo('make_sticky');
			$context['can_move'] = allowedTo('move_any');
			$context['can_remove'] = allowedTo('remove_any');
			$context['can_merge'] = allowedTo('merge_any');
			// Ignore approving own topics as it's unlikely to come up...
			$context['can_approve'] = $modSettings['postmod_active'] && allowedTo('approve_posts') && !empty($board_info['unapproved_topics']);
			// Can we restore topics?
			$context['can_restore'] = allowedTo('move_any') && !empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] == $board;
			// Set permissions for all the topics.
			foreach ($context['topics'] as $t => $topic)
			{
				$started = $topic['first_post']['member']['id'] == $user_info['id'];
				$context['topics'][$t]['quick_mod'] = array(
					'lock' => allowedTo('lock_any') || ($started && allowedTo('lock_own')),
					'sticky' => allowedTo('make_sticky'),
					'move' => allowedTo('move_any') || ($started && allowedTo('move_own')),
					'modify' => allowedTo('modify_any') || ($started && allowedTo('modify_own')),
					'remove' => allowedTo('remove_any') || ($started && allowedTo('remove_own')),
					'approve' => $context['can_approve'] && $topic['unapproved_posts']
				);
				$context['can_lock'] |= ($started && allowedTo('lock_own'));
				$context['can_move'] |= ($started && allowedTo('move_own'));
				$context['can_remove'] |= ($started && allowedTo('remove_own'));
			}
			// Can we use quick moderation checkboxes?
			if ($options['display_quick_mod'] == 1)
				$context['can_quick_mod'] = $context['user']['is_logged'] || $context['can_approve'] || $context['can_remove'] || $context['can_lock'] || $context['can_sticky'] || $context['can_move'] || $context['can_merge'] || $context['can_restore'];
			// Or the icons?
			else
				$context['can_quick_mod'] = $context['can_remove'] || $context['can_lock'] || $context['can_sticky'] || $context['can_move'];
		}*/
		if (!empty($context['can_quick_mod']) && $options['display_quick_mod'] == 1)
		{
			$context['qmod_actions'] = array('approve', 'remove', 'lock', 'sticky', 'move', 'merge', 'restore', 'markread');
			call_integration_hook('integrate_quick_mod_actions');
		}
		if (!empty($context['boards']) && $context['start'] == 0)
			$template_layers->add('display_child_boards');
		// If there are children, but no topics and no ability to post topics...
		//$context['no_topic_listing'] = !empty($context['boards']) && empty($context['topics']) && !$context['can_post_new'];
		$context['no_topic_listing'] = false;
		$template_layers->add('topic_listing');
		
		addJavascriptVar(array('notification_board_notice' => $context['is_marked_notify'] ? $txt['notification_disable_board'] : $txt['notification_enable_board']), true);
		/*
		// Build the message index button array.
		$context['normal_buttons'] = array(
			'new_topic' => array('test' => 'can_post_new', 'text' => 'new_topic', 'image' => 'new_topic.png', 'lang' => true, 'url' => $scripturl . '?action=post;board=' . $context['current_board'] . '.0', 'active' => true),
			'notify' => array('test' => 'can_mark_notify', 'text' => $context['is_marked_notify'] ? 'unnotify' : 'notify', 'image' => ($context['is_marked_notify'] ? 'un' : '') . 'notify.png', 'lang' => true, 'custom' => 'onclick="return notifyboardButton(this);"', 'url' => $scripturl . '?action=notifyboard;sa=' . ($context['is_marked_notify'] ? 'off' : 'on') . ';board=' . $context['current_board'] . '.' . $context['start'] . ';' . $context['session_var'] . '=' . $context['session_id']),
		);
		addJavascriptVar(array(
			'txt_mark_as_read_confirm' => $txt['mark_these_as_read_confirm']
		), true);
		// They can only mark read if they are logged in and it's enabled!
		if (!$user_info['is_guest'] && $settings['show_mark_read'])
			$context['normal_buttons']['markread'] = array(
				'text' => 'mark_read_short',
				'image' => 'markread.png',
				'lang' => true,
				'url' => $scripturl . '?action=markasread;sa=board;board=' . $context['current_board'] . '.0;' . $context['session_var'] . '=' . $context['session_id'],
				'custom' => 'onclick="return markboardreadButton(this);"'
			);
			*/
		// Allow adding new buttons easily.
		call_integration_hook('integrate_tagindex_buttons');
		
		 $txt['who_viewing_board'] =  $txt['who_viewing_tag'];
	}
}
