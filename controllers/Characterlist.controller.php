<?php

/**
 * Handles the displaying and searching in the Character list.
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
 *
 */

/**
 * Characterlist_Controller Class
 * Access and search the Character list.
 */
class Characterlist_Controller extends Action_Controller
{
	/**
	 * The fields that we can search
	 * @var array
	 */
	public $_search_fields;

	/**
	 * Entry point function, called before all others
	 */
	public function pre_dispatch()
	{
		global $context, $txt;

		loadLanguage('RolePlayingSystem');
		// These are all the possible fields.
		$this->_search_fields = array(
			'name' => $txt['rps_search_name'],
			'member' => $txt['rps_search_member'],

		);

		require_once(SUBSDIR . '/Characterlist.subs.php');

		// These are handy later
		$context['old_search_value'] = '';
		$context['in_search'] = !empty($this->_req->post->search);
	}

	/**
	 * Sets up the context for showing a listing of registered members.
	 * For the handlers in this file, it requires the view_mlist permission.
	 *
	 * - Accessed by ?action_characterlist.
	 *
	 * @uses Memberlist template, main sub-template.
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
		global $scripturl, $txt, $modSettings, $context;

		// Make sure they can view the memberlist.
		isAllowedTo('view_mlist');

		loadTemplate('RpsCharacterlist');
		$context['sub_template'] = 'characterlist';
		Template_Layers::instance()->add('clsearch');

		$context['listing_by'] = $this->_req->getQuery('sa', 'trim', 'all');

		// $subActions array format:
		// 'subaction' => array('label', 'function', 'is_selected')
		$subActions = array(
			'all' => array($txt['view_all_members'], 'action_clall', $context['listing_by'] == 'all'),
			'search' => array($txt['mlist_search'], 'action_clsearch', $context['listing_by'] == 'search'),
		);

		// Set up the sort links.
		$context['sort_links'] = array();
		foreach ($subActions as $act => $text)
		{
			$context['sort_links'][] = array(
				'label' => $text[0],
				'action' => $act,
				'selected' => $text[2],
			);
		}

		$context['num_members'] = $modSettings['totalMembers'];

		// Set up the standard columns...
		$context['columns'] = array(
			'avatar' => array(
				'label' => '',
				'class' => 'avatar',
			),
			'name' => array(
				'label' => $txt['rps_name'],
				'class' => 'name',
				'sort' => array(
					'down' => 'chr.name DESC',
					'up' => 'chr.name ASC'
				),
			),
			'title' => array(
				'label' => $txt['rps_title'],
				'class' => 'title',
				'sort' => array(
					'down' => 'chr.title DESC',
					'up' => 'chr.title ASC'
				),
			),
			'posts' => array(
				'label' => $txt['posts'],
				'class' => 'posts',
				'default_sort_rev' => true,
				'sort' => array(
					'down' => 'chr.posts DESC',
					'up' => 'chr.posts ASC'
				),
			),
			'member' => array(
				'label' => $txt['rps_player'],
				'class' => 'name',
				'sort' => array(
					'down' => 'mem.real_name DESC',
					'up' => 'mem.real_name ASC'
				),
			),
			'last_active' => array(
				'label' => $txt['rps_last_active'],
				'class' => 'last_active',
				'sort' => array(
					'down' => 'chr.last_active DESC',
					'up' => 'chr.last_active ASC'
				),
			),
		);

		// The template may appreciate how many columns it needs to display
		$context['colspan'] = 0;
		foreach ($context['columns'] as $key => $column)
		{
			$context['colspan'] += isset($column['colspan']) ? $column['colspan'] : 1;
		}

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=characterlist',
			'name' => $txt['rps_characters']
		);

		// Build the memberlist button array.
		if ($context['in_search'])
		{
			$context['memberlist_buttons'] = array(
				'view_all_members' => array('text' => 'view_all_members', 'image' => 'mlist.png', 'lang' => true, 'url' => $scripturl . '?action=characterlist;sa=all', 'active' => true),
			);
		}
		else
			$context['memberlist_buttons'] = array();

		// Make fields available to the template
		$context['search_fields'] = $this->_search_fields;

		// What do we search for by default?
		$context['search_defaults'] = array('name');


		// Jump to the sub action.
		if (isset($subActions[$context['listing_by']]))
			$this->{$subActions[$context['listing_by']][1]}();
		else
			$this->{$subActions['all'][1]}();
	}

	/**
	 * List all members, page by page, with sorting.
	 *
	 * - Called from MemberList().
	 * - Can be passed a sort parameter, to order the display of members.
	 * - Calls printMemberListRows to retrieve the results of the query.
	 */
	public function action_clall()
	{
		global $txt, $scripturl, $modSettings, $context;

		require_once(SUBSDIR . '/Characterlist.subs.php');

		// Some handy short cuts
		$start = $this->_req->getQuery('start', '', null);
		$desc = $this->_req->getQuery('desc', '', null);
		$sort = $this->_req->getQuery('sort', '', null);

		$context['num_members'] = cl_memberCount();

		// Set defaults for sort (real_name)
		if (!isset($sort) || !isset($context['columns'][$sort]['sort']))
			$sort = 'name';

		// Looking at a specific rolodex letter?
		if (!is_numeric($start))
		{
			if (preg_match('~^[^\'\\\\/]~u', Util::strtolower($start), $match) === 0)
				throw new Elk_Exception('Hacker?', false);

			$start = cl_alphaStart($match[0]);
		}

		// Build out the letter selection link bar
		$context['letter_links'] = '';
		for ($i = 97; $i < 123; $i++)
			$context['letter_links'] .= '<a href="' . $scripturl . '?action=characterlist;sa=all;start=' . chr($i) . '#letter' . chr($i) . '">' . chr($i - 32) . '</a> ';

		// Sort out the column information.
		foreach ($context['columns'] as $col => $column_details)
		{
			$context['columns'][$col]['href'] = $scripturl . '?action=characterlist;sort=' . $col . ';start=0';

			if ((!isset($desc) && $col == $sort)
				|| ($col != $sort && !empty($column_details['default_sort_rev'])))
			{
				$context['columns'][$col]['href'] .= ';desc';
			}

			$context['columns'][$col]['link'] = '<a href="' . $context['columns'][$col]['href'] . '" rel="nofollow">' . $context['columns'][$col]['label'] . '</a>';
			$context['columns'][$col]['selected'] = $sort == $col;
			if ($context['columns'][$col]['selected'])
				$context['columns'][$col]['class'] .= ' selected';
		}

		// Are we sorting the results
		$context['sort_by'] = $sort;
		$context['sort_direction'] = !isset($desc) ? 'up' : 'down';

		// Construct the page index.
		$context['page_index'] = constructPageIndex($scripturl . '?action=characterlist;sort=' . $sort . (isset($desc) ? ';desc' : ''), $start, $context['num_members'], $modSettings['defaultMaxMembers']);

		// Send the data to the template.
		$context['start'] = $start + 1;
		$context['end'] = min($start + $modSettings['defaultMaxMembers'], $context['num_members']);
		$context['can_moderate_forum'] = allowedTo('moderate_forum');
		$context['page_title'] = sprintf($txt['viewing_members'], $context['start'], $context['end']);
		$context['linktree'][] = array(
			'url' => $scripturl . '?action=characterlist;sort=' . $sort . ';start=' . $start,
			'name' => &$context['page_title'],
			'extra_after' => ' (' . sprintf($txt['of_total_members'], $context['num_members']) . ')'
		);

		$limit = $start;
		$where = '';
		$query_parameters = array(
			'is_activated' => 1,
			'sort' => $context['columns'][$sort]['sort'][$context['sort_direction']],
		);

		// Select the members from the database.
		cl_selectCharacters($query_parameters, $where, $limit, $sort);

		// Add anchors at the start of each letter.
		if ($sort === 'name')
		{
			$last_letter = '';
			foreach ($context['characters'] as $i => $dummy)
			{
				$this_letter = Util::strtolower(Util::substr($context['characters'][$i]['name_actual'], 0, 1));

				if ($this_letter != $last_letter && preg_match('~[a-z]~', $this_letter) === 1)
				{
					$context['characters'][$i]['sort_letter'] = Util::htmlspecialchars($this_letter);
					$last_letter = $this_letter;
				}
			}
		}
	}

	/**
	 * Search for members, or display search results.
	 *
	 * - If variable $_REQUEST['search'] is empty displays search dialog box,
	 * using the search sub-template.
	 * - Calls printMemberListRows to retrieve the results of the query.
	 */
	public function action_clsearch()
	{
		global $txt, $scripturl, $context, $modSettings;

		$context['page_title'] = $txt['rps_characters_search'];
		$context['can_moderate_forum'] = allowedTo('moderate_forum');

		// They're searching..
		if (isset($this->_req->query->search, $this->_req->query->fields)
			|| isset($this->_req->post->search, $this->_req->post->fields))
		{
			// Some handy short cuts
			$start = $this->_req->getQuery('start', '', null);
			$desc = $this->_req->getQuery('desc', '', null);
			$sort = $this->_req->getQuery('sort', '', null);
			$search = Util::htmlspecialchars(trim(isset($this->_req->query->search) ? $this->_req->query->search : $this->_req->post->search), ENT_QUOTES);
			$input_fields = isset($this->_req->query->fields) ? explode(',', $this->_req->query->fields) : $this->_req->post->fields;

			$fields_key = array_keys($this->_search_fields);
			$context['search_defaults'] = array();
			foreach ($input_fields as $val)
			{
				if (in_array($val, $fields_key))
					$context['search_defaults'] = $input_fields;
			}
			$context['old_search_value'] = $search;

			// No fields?  Use default...
			if (empty($input_fields))
				$input_fields = array('name');

			// Set defaults for how the results are sorted
			if (!isset($sort) || !isset($context['columns'][$sort]))
				$sort = 'name';

			// Build the column link / sort information.
			foreach ($context['columns'] as $col => $column_details)
			{
				$context['columns'][$col]['href'] = $scripturl . '?action=characterlist;sa=search;start=0;sort=' . $col;

				if ((!isset($desc) && $col == $sort) || ($col != $sort && !empty($column_details['default_sort_rev'])))
					$context['columns'][$col]['href'] .= ';desc';

				$context['columns'][$col]['href'] .= ';search=' . $search . ';fields=' . implode(',', $input_fields);
				$context['columns'][$col]['link'] = '<a href="' . $context['columns'][$col]['href'] . '" rel="nofollow">' . $context['columns'][$col]['label'] . '</a>';
				$context['columns'][$col]['selected'] = $sort == $col;
			}

			// set up some things for use in the template
			$context['sort_direction'] = !isset($desc) ? 'up' : 'down';
			$context['sort_by'] = $sort;
			$context['memberlist_buttons'] = array(
				'view_all_members' => array('text' => 'view_all_members', 'image' => 'mlist.png', 'lang' => true, 'url' => $scripturl . '?action=characterlist;sa=all', 'active' => true),
			);

			$query_parameters = array(
				'is_activated' => 1,
				'blank_string' => '',
				'search' => '%' . strtr($search, array('_' => '\\_', '%' => '\\%', '*' => '%')) . '%',
				'sort' => $context['columns'][$sort]['sort'][$context['sort_direction']],
			);

			// Search for a name
			if (in_array('name', $input_fields))
				$fields =  array('name');
			else
				$fields = array();

			// Search for websites.
			if (in_array('member', $input_fields))
				$fields += array(7 => 'mem.real_name');

			$condition = '';

			if (defined('DB_CASE_SENSITIVE'))
			{
				foreach ($fields as $key => $field)
					$fields[$key] = 'LOWER(' . $field . ')';
			}

			
			
			$validFields = isset($input_fields) ? $input_fields : array();

			if (empty($fields))
				redirectexit('action=characterlist');

			$validFields = array_unique($validFields);
			$query = $search == '' ? '= {string:blank_string}' : (defined('DB_CASE_SENSITIVE') ? 'LIKE LOWER({string:search})' : 'LIKE {string:search}');
			$where = implode(' ' . $query . ' OR ', $fields) . ' ' . $query . $condition;

			// Find the members from the database.
			$numResults = cl_searchMembers($query_parameters, $where, $start);
			$context['letter_links'] = '';
			$context['page_index'] = constructPageIndex($scripturl . '?action=characterlist;sa=search;search=' . $search . ';fields=' . implode(',', $validFields), $start, $numResults, $modSettings['defaultMaxMembers']);
		}
		else
			redirectexit('action=characterlist');

		$context['linktree'][] = array(
			'url' => $scripturl . '?action=characterlist;sa=search',
			'name' => &$context['page_title']
		);

		// Highlight the correct button, too!
		unset($context['memberlist_buttons']['view_all_members']['active']);
	}
}