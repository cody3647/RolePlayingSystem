<?php

/**
 * Functions for administration of characters.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

function list_get_unapproved_characters($start, $items_per_page, $sort, $where = '1 = 1')
{
	$db = database();
	
	$request = $db->query('', '
		SELECT
			c.id_character, c.name, m.real_name, m.id_member
			FROM {db_prefix}rps_characters AS c
			LEFT JOIN {db_prefix}members AS m
				ON c.id_member = m.id_member
			WHERE approved = 0 AND {raw:where}
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:maxindex}',
		array(
			'start' => $start,
			'maxindex' => $items_per_page,
			'sort' => $sort,
			'where' => $where,
		)
	);
	$characters = array();
	while ($row = $db->fetch_assoc($request))
		$characters[] = $row;
	$db->free_result($request);
	return $characters;
}
	
function list_num_unapproved_characters()
{
	$db = database();

	// Get the total number of tags
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_characters
		WHERE approved = 0'
	);
	list ($characterCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $characterCount;
}

function approve_characters($ids)
{
	$db = database();
	
	$ids = is_array($ids) ? $ids : array($ids);

	$request = $db->query('', '
		UPDATE {db_prefix}rps_characters
		SET approved=1
		WHERE id_character IN({array_int:ids})', 
		array(
			'ids' => $ids
		)
	);
		
}

function list_get_unapproved_biographies($start, $items_per_page, $sort, $where = '1 = 1')
{
	$db = database();
	
	$request = $db->query('', '
		SELECT
			b.id_bio, b.id_character, b.date_added, c.name, m.real_name, m.id_member
			FROM {db_prefix}rps_biographies AS b
			LEFT JOIN {db_prefix}rps_characters AS c
				ON b.id_character = c.id_character
			LEFT JOIN {db_prefix}members AS m
				ON c.id_member = m.id_member
			WHERE b.approved = 0 AND {raw:where}
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:maxindex}',
		array(
			'start' => $start,
			'maxindex' => $items_per_page,
			'sort' => $sort,
			'where' => $where,
		)
	);
	$biographies = array();
	while ($row = $db->fetch_assoc($request))
		$biographies[] = $row;
	$db->free_result($request);
	return $biographies;
}
	
function list_num_unapproved_biographies()
{
	$db = database();

	// Get the total number of tags
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_biographies
		WHERE approved = 0'
	);
	list ($biographyCount) = $db->fetch_row($request);
	$db->free_result($request);

	return $biographyCount;
}

function approve_bios($ids)
{
	$db = database();
	
	$time = time();
	$ids = is_array($ids) ? $ids : array($ids);

	$request = $db->query('', '
		UPDATE {db_prefix}rps_biographies
		SET approved = 1, date_approved = {int:timestamp}
		WHERE id_bio IN({array_int:ids})', 
		array(
			'timestamp' => $time,
			'ids' => $ids
		)
	);		
}
