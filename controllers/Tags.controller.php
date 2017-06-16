<?php

/**
 * This file is the main Package Manager.
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0.8
 *
 */

use ElkArte\Errors\ErrorContext;

class Tags_Controller extends Action_Controller
{
	
	/**
	 * Intended as entry point which delegates to methods in this class...
	 */
	public function action_index()
	{
		global $context;
		
		loadTemplate('RpsTags');

		if(isset($this->_req->api))
			$this->action_fetch();
		elseif(isset($this->_req->topic))
			$this->action_edit_topic($this->_req->topic);
		elseif(isset($this->_req->tag))
			$this->action_view_tag($this->_req->tag);
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
		$template_layers = Template_Layers::getInstance();
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
	
	public function action_view_tag($tag)
	{
		global $txt, $scripturl, $context;
		
		$db = database();
		$request = $db->query('', '
			SELECT tag
			FROM {db_prefix}rps_tags
			WHERE id_tag = {int:id_tag}
			LIMIT 1',
			array(
				'id_tag' => $tag,
			)
		);
		while ($row = $db->fetch_assoc($request))
			$context['tag_name'] = un_htmlspecialchars($row['tag']);
		$db->free_result($request);
		
		if(empty($context['tag_name']))
			throw new Elk_Exception('RolePlayingSystem.rps_no_tag', false);

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_tag_title'] . $context['tag_name'],
			'canonical_url' => $scripturl . '?action=tags;tag=' . $tag,
			'sub_template' => 'view_tag',
		);

		$context['linktree']+=array(
			1=> array(
				'name' => $txt['rps_tags'],
				'url' => $scripturl . '?action=tags',
			),
			2 => array(
				'name' => $context['tag_name'],
				'url' => $context['canonical_url'],
			)
		);
		
		// And now we do the same for all of our custom ones
		$listOptions = array(
			'id' => 'view_tag',
			'title' => $txt['rps_tag_list_title'] . $context['tag_name'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'date_tag',
			'no_items_label' => ['rps_tag_list_none'],
			'items_per_page' => 25,
			'sortable' => true,
			'get_items' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getTopics',
				'params' => array(
					$tag,
				),
			),
			'get_count' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getNumTopics',
				'params' => array(
					$tag,
				),
			),
			'columns' => array(

				'first_subject' => array(
					'header' => array(
						'value' => $txt['rps_tag_topic'],
						'style' => 'width: 30%;',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<div class="rps_topic"><h4><a href="'. $scripturl .'?topic=%1$d">%2$s</a></h4></div>
								<div class="rps_starter">' . $txt['rps_tag_started'] . '%3$s</div>', 
							'params' => array(
								'id_topic' => false, 
								'first_subject' => false,
								'first_display_name' => false,
							),
						),
					),
					'sort' => array(
						'default' => 'first_subject',
						'reverse' => 'first subject DESC',
					),
				),
				/*'first_display_name' => array(
					'header' => array(
						'value' => $txt['rps_tag_started'],
						'class' => 'centertext',
					),
					'data' => array(
						'db_htmlsafe' => 'first_display_name',
						'style' => 'width: 10%;',
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'first_display_name DESC',
						'reverse' => 'first_display_name',
					),
				),*/
				'num_replies' => array(
					'header' => array(
						'value' => $txt['rps_tag_replies'],
					),
					'data' => array(
						'db' => 'num_replies',
						'comma_format' => true,
						'style' => 'width: 10%;',
					),
				),
				'last_poster_time' => array(
					'data' => array(
						'db' => 'last_poster_time',
						'timeformat' => true,
						'style' => 'width: 15%;',
					),
					'header' => array(
						'value' => $txt['rps_tag_last_post'],
					),
					'sort' => array(
						'default' => 'last_poster_time DESC',
						'reverse' => 'last_poster_time',
					)
				),
				'date_tag' => array(
					'data' => array(
						'db' => 'date_tag',
						'style' => 'width: 10%;',
					),
					'header' => array(
						'value' => $txt['rps_tag_topic_date'],
					),
					'sort' => array(
						'default' => 't.date_tag DESC',
						'reverse' => 't.date_tag',
					),
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
}