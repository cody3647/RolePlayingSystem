<?php
/**
 * Functions for getting, saving and removing tags.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 * @param $input_tags
 * @param $topic
 * @param $memID
 * @param $timestamp
 * @throws Exception
 */

function save_tags($input_tags, $topic, $memID, $timestamp)
{
	if (empty($input_tags) || empty($topic) || empty($memID))
		return;
	
	$db = database();
	
	$input_tags = explode(',', $input_tags);
	$input_tags = htmltrim__recursive($input_tags);
	$input_tags = array_filter($input_tags);
	
	$existant_tags = array();
	$request = $db->query('', '
		SELECT id_tag, tag
		FROM {db_prefix}rps_tags
		WHERE tag IN({array_string:tags})',
		array(
			'tags' => $input_tags,
		)
	);
	while ($row = $db->fetch_assoc($request))
		$existant_tags[$row['id_tag']] = $row['tag'];
	
	$db->free_result($request);
	
	$new_tags = array_udiff($input_tags, $existant_tags, 'strcasecmp');
	
	foreach ($new_tags as $tag)
	{				
		if (!empty($tag))
		{
			$db->insert('',
				'{db_prefix}rps_tags',
				array('tag' => 'string-255'),
				array($tag),
				array('id_tag')
			);
			$tagID = $db->insert_id('{db_prefix}rps_tags', 'id_tag');
			$existant_tags[$tagID] = $tag;
		}
	}

	foreach ($existant_tags as $id => $tag)
	{
		$insert_tags[] = array($topic, $id, $timestamp, $memID);
	}

	$db->insert('ignore',
		'{db_prefix}rps_tags_data',
		array('id_topic' => 'int', 'id_tag' => 'int', 'date_added' => 'int', 'id_member' => 'int'),
		$insert_tags,
		''
	);

}

function remove_tags($remove_tags, $topic)
{
	if (empty($remove_tags) || empty($topic))
		return;
	
	$db = database();

	if (!is_array($remove_tags))
		$remove_tags = array($remove_tags);

	if ($topic != '1=1' && is_numeric($topic))
		$topic = 'id_topic = ' . $topic;
	
	$db->query('', '
		DELETE FROM {db_prefix}rps_tags_data
		WHERE id_tag IN ({array_int:id_tags})
			AND {raw:id_topic}',
		array(
			'id_tags' => $remove_tags,
			'id_topic' => $topic
		)
	);
	
	$db->query('', '
		DELETE tg
		FROM {db_prefix}rps_tags AS tg
        LEFT OUTER JOIN {db_prefix}rps_tags_data AS td ON (tg.id_tag = td.id_tag)
		WHERE td.id_tag IS NULL
        	AND tg.id_tag IN ({array_int:id_tags})',
		array(
			'id_tags' => $remove_tags,
		)
	);
	
}

function edit_tags($edited, $original, $user_id, $timestamp)
{
	$db = database();
	$changed = array();
	foreach($original as $id => $tag)
	{
		if($tag != $edited[$id])
			$changed[$id] = $edited[$id];
	}
	if(empty($changed))
		return;
	
	$remove_tags = array();
	foreach($changed as $id => $tag)
	{
		$request = $db->query('', '
			SELECT id_tag
			FROM {db_prefix}rps_tags
			WHERE tag =({string:tag})',
			array(
				'tag' => $tag,
			)
		);

		if($db->num_rows($request) > 0)
		{
			$row = $db->fetch_assoc($request);
			$request = $db->query('', '
				UPDATE IGNORE {db_prefix}rps_tags_data
				SET id_tag={int:new_id}
				WHERE id_tag={int:old_id}', 
				array(
					'new_id' => $row['id_tag'],
					'old_id' => $id
				)
			);
			$remove_tags[] = $id;
		}
		else
			$request = $db->query('', '
				UPDATE {db_prefix}rps_tags 
				SET tag={string:tag} 
				WHERE id_tag={int:id_tag}', 
				array(
					'tag' => $tag,
					'id_tag' => $id
				)
			);
		
	}
	
	remove_tags($remove_tags, '1=1');	
}

function getNumTaggedTopics($tag)
{
	$db = database();

	// Get the total number of attachments they have posted.
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}topics AS t
		INNER JOIN {db_prefix}rps_tags_data AS td ON (td.id_topic = t.id_topic)
		INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board AND {query_see_board})
		WHERE td.id_tag = {int:tag}
			AND t.approved = 1',
		array(
			'tag' => $tag,
		)
	);
	list ($tagCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $tagCount;
}

function list_getTags($start, $items_per_page, $sort, $where = '1=1')
{
	global $user_info;
	$db = database();
	
	$request = $db->query('', '
		SELECT
			tg.tag, td.id_tag, count(td.id_tag) as tag_count
			FROM {db_prefix}rps_tags_data as td
			LEFT JOIN {db_prefix}rps_tags AS tg ON tg.id_tag = td.id_tag
			WHERE {raw:where}
			GROUP BY td.id_tag
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:maxindex}',
		array(
			'start' => $start,
			'maxindex' => $items_per_page,
			'sort' => $sort,
			'where' => $where,
		)
	);
	$tags = array();
	while ($row = $db->fetch_assoc($request))
		$tags[] = $row;
	$db->free_result($request);
	return $tags;
}

function list_getNumTags()
{
	$db = database();

	// Get the total number of tags
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_tags AS tg
		WHERE 1=1'
	);
	list ($tagCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $tagCount;
}

function list_getNumTopicTags($topic)
{
	$db = database();

	// Get the total number of tags on a topic
	$request = $db->query('', '
		SELECT COUNT(*)
			FROM {db_prefix}rps_tags_data as td
			WHERE td.id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	list ($tagCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $tagCount;
}

//Load tag details, returns details of requested tag in array
function loadTag($tagid)
{
	$db = database();
		$request = $db->query('', '
			SELECT *
			FROM {db_prefix}rps_tags
			WHERE id_tag = {int:id_tag}
			LIMIT 1',
			array(
				'id_tag' => $tagid,
			)
		);
		while ($row = $db->fetch_assoc($request))
			$tag = array(
				'name' => un_htmlspecialchars($row['tag']),
				'description' => ''
			);
		$db->free_result($request);
		
		return $tag;
}

/**
 * Builds the message index with the supplied parameters
 * creates all you ever wanted on message index, returns the data in array
 * Function was originally messageIndexTopics
 *
 * @param $id_tag
 * @param int $id_member who we are building it for so we don't show unapproved topics
 * @param int $start where to start from
 * @param int $items_per_page The number of items to show per page
 * @param string $sort_by how to sort the results asc/desc
 * @param string $sort_column which value we sort by
 * @param mixed[] $indexOptions
 *     'include_sticky' => if on, loads sticky topics as additional
 *     'only_approved' => if on, only load approved topics
 *     'previews' => if on, loads in a substring of the first/last message text for use in previews
 *     'include_avatars' => if on loads the last message posters avatar
 *     'ascending' => ASC or DESC for the sort
 *     'fake_ascending' =>
 *     'custom_selects' => loads additional values from the tables used in the query, for addon use
 * @return array
 * @throws Exception
 */
function tagIndexTopics($id_tag, $id_member, $start, $items_per_page, $sort_by, $sort_column, $indexOptions)
{
	$db = database();
	$topics = array();
	$topic_ids = array();
	$indexOptions = array_merge(array(
		'include_sticky' => true,
		'fake_ascending' => false,
		'ascending' => true,
		'only_approved' => true,
		'previews' => -1,
		'include_avatars' => false,
		'custom_selects' => array(),
		'custom_joins' => array(),
	), $indexOptions);
	// Extra-query for the pages after the first
	$ids_query = $start > 0;
	if ($ids_query && $items_per_page > 0)
	{
		$request = $db->query('', '
			SELECT t.id_topic
			FROM {db_prefix}topics AS t' . ($sort_by === 'last_poster' ? '
				INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)' : (in_array($sort_by, array('starter', 'subject')) ? '
				INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)' : '')) . ($sort_by === 'starter' ? '
				LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)' : '') . ($sort_by === 'last_poster' ? '
				LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)' : '') . '
			WHERE td.id_tag = {int:current_tag}' . (!$indexOptions['only_approved'] ? '' : '
				AND (t.approved = {int:is_approved}' . ($id_member == 0 ? '' : ' OR t.id_member_started = {int:current_member}') . ')') . '
			ORDER BY ' . ($indexOptions['include_sticky'] ? 'is_sticky' . ($indexOptions['fake_ascending'] ? '' : ' DESC') . ', ' : '') . $sort_column . ($indexOptions['ascending'] ? '' : ' DESC') . '
			LIMIT {int:start}, {int:maxindex}',
			array(
				'current_tag' => $id_tag,
				'current_member' => $id_member,
				'is_approved' => 1,
				'id_member_guest' => 0,
				'start' => $start,
				'maxindex' => $items_per_page,
			)
		);
		$topic_ids = array();
		while ($row = $db->fetch_assoc($request))
			$topic_ids[] = $row['id_topic'];
		$db->free_result($request);
	}
	// And now, all you ever wanted on message index...
	// and some you wish you didn't! :P
	if (!$ids_query || !empty($topic_ids))
	{
		// If -1 means preview the whole body
		if ($indexOptions['previews'] === -1)
			$indexOptions['custom_selects'] = array_merge($indexOptions['custom_selects'], array('ml.body AS last_body', 'mf.body AS first_body'));
		// Default: a SUBSTRING
		elseif (!empty($indexOptions['previews']))
			$indexOptions['custom_selects'] =  array_merge($indexOptions['custom_selects'], array('SUBSTRING(ml.body, 1, ' . ($indexOptions['previews'] + 256) . ') AS last_body', 'SUBSTRING(mf.body, 1, ' . ($indexOptions['previews'] + 256) . ') AS first_body'));
		if (!empty($indexOptions['include_avatars']))
		{
			// Double equal comparison for 1 because it is backward compatible with 1.0 where the value was true/false
			if ($indexOptions['include_avatars'] == 1 || $indexOptions['include_avatars'] === 3)
			{
				$indexOptions['custom_selects'] = array_merge($indexOptions['custom_selects'], array('meml.avatar', 'COALESCE(a.id_attach, 0) AS id_attach', 'a.filename', 'a.attachment_type', 'meml.email_address'));
				$indexOptions['custom_joins'] = array_merge($indexOptions['custom_joins'], array('LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = ml.id_member AND a.id_member != 0)'));
			}
			if ($indexOptions['include_avatars'] === 2 || $indexOptions['include_avatars'] === 3)
			{
				$indexOptions['custom_selects'] = array_merge($indexOptions['custom_selects'], array('memf.avatar AS avatar_first', 'COALESCE(af.id_attach, 0) AS id_attach_first', 'af.filename AS filename_first', 'af.attachment_type AS attachment_type_first', 'memf.email_address AS email_address_first'));
				$indexOptions['custom_joins'] = array_merge($indexOptions['custom_joins'], array('LEFT JOIN {db_prefix}attachments AS af ON (af.id_member = mf.id_member AND af.id_member != 0)'));
			}
		}
		$request = $db->query('substring', '
			SELECT
				t.id_topic, t.num_replies, t.locked, t.num_views, t.num_likes, t.is_sticky, t.id_poll, t.id_previous_board,
				' . ($id_member == 0 ? '0' : 'COALESCE(lt.id_msg, lmr.id_msg, -1) + 1') . ' AS new_from,
				t.id_last_msg, t.approved, t.unapproved_posts, t.id_redirect_topic, t.id_first_msg,
				ml.poster_time AS last_poster_time, ml.id_msg_modified, ml.subject AS last_subject, ml.icon AS last_icon,
				ml.poster_name AS last_member_name, ml.id_member AS last_id_member, ml.smileys_enabled AS last_smileys,
				COALESCE(meml.real_name, ml.poster_name) AS last_display_name,
				mf.poster_time AS first_poster_time, mf.subject AS first_subject, mf.icon AS first_icon,
				mf.poster_name AS first_member_name, mf.id_member AS first_id_member, mf.smileys_enabled AS first_smileys,
				COALESCE(memf.real_name, mf.poster_name) AS first_display_name
				' . (!empty($indexOptions['custom_selects']) ? ' ,' . implode(',', $indexOptions['custom_selects']) : '') . '
			FROM {db_prefix}topics AS t
				INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
				INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
				LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)
				LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)
				INNER JOIN {db_prefix}rps_tags_data AS td ON (td.id_topic = t.id_topic)
				INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board AND {query_see_board})' . ($id_member == 0 ? '' : '
				LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = t.id_topic AND lt.id_member = {int:current_member})
				LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = t.id_board AND lmr.id_member = {int:current_member})') .
				(!empty($indexOptions['custom_joins']) ? implode("\n\t\t\t\t", $indexOptions['custom_joins']) : '') . '
			WHERE ' . ($ids_query ? 't.id_topic IN ({array_int:topic_list})' : 'td.id_tag = {int:current_tag}') . (!$indexOptions['only_approved'] ? '' : '
				AND (t.approved = {int:is_approved}' . ($id_member == 0 ? '' : ' OR t.id_member_started = {int:current_member}') . ')') . '
			ORDER BY ' . ($ids_query ? 'FIND_IN_SET(t.id_topic, {string:find_set_topics})' : ($indexOptions['include_sticky'] ? 'is_sticky' . ($indexOptions['fake_ascending'] ? '' : ' DESC') . ', ' : '') . $sort_column . ($indexOptions['ascending'] ? '' : ' DESC')) . '
			LIMIT ' . ($ids_query ? '' : '{int:start}, ') . '{int:maxindex}',
			array(
				'current_tag' => $id_tag,
				'current_member' => $id_member,
				'topic_list' => $topic_ids,
				'is_approved' => 1,
				'find_set_topics' => implode(',', $topic_ids),
				'start' => $start,
				'maxindex' => $items_per_page,
			)
		);
		// Lets take the results
		while ($row = $db->fetch_assoc($request))
			$topics[$row['id_topic']] = $row;
		$db->free_result($request);
	}

	return $topics;
}