<?php

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

function list_getTopics($start, $items_per_page, $sort, $tag)
{
	global $user_info;
	$db = database();
	
	$request = $db->query('', '
		SELECT
			t.id_topic, t.num_replies, t.num_views, 
			t.id_last_msg, t.id_first_msg, t.date_tag,
			ml.poster_time AS last_poster_time,
			ml.id_member AS last_id_member,
			COALESCE(cl.name, meml.real_name, ml.poster_name) AS last_display_name,
			mf.subject AS first_subject,
			mf.id_member AS first_id_member,
			COALESCE(cf.name, memf.real_name, mf.poster_name) AS first_display_name,
			b.in_character
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
			INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
			LEFT JOIN {db_prefix}members AS meml ON (meml.id_member = ml.id_member)
			LEFT JOIN {db_prefix}members AS memf ON (memf.id_member = mf.id_member)
			INNER JOIN {db_prefix}rps_tags_data AS td ON (td.id_topic = t.id_topic)
			LEFT JOIN {db_prefix}rps_characters AS cf ON (cf.id_character = mf.id_character)
			LEFT JOIN {db_prefix}rps_characters AS cl ON (cl.id_character = ml.id_character)
			INNER JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board AND {query_see_board})
		WHERE td.id_tag = {int:tag}
			AND (t.approved = 1 OR t.id_member_started = {int:current_member})
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:maxindex}',
		array(
			'current_member' => $user_info['id'],
			'id_member_guest' => 0,
			'start' => $start,
			'maxindex' => $items_per_page,
			'tag' => $tag,
			'sort' => $sort,
		)
	);
	$tags = array();
	while ($row = $db->fetch_assoc($request))
		$tags[] = $row;
	$db->free_result($request);
	return $tags;
}

function list_getNumTopics($tag)
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

	// Get the total number of attachments they have posted.
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

	// Get the total number of attachments they have posted.
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