<?php

/**
 * Handle memberlist functions
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.1 Release Candidate 1
 *
 */



/**
 * Counts the number of active members in the system
 */
function cl_memberCount()
{
	$db = database();

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_characters
		WHERE approved = {int:is_activated}',
		array(
			'is_activated' => 1,
		)
	);
	list ($num_characters) = $db->fetch_row($request);
	$db->free_result($request);

	return $num_characters;
}

/**
 * Get all all the members who's name starts below a given letter
 *
 * @param string $start single letter to start with
 * @return string
 * @throws Exception
 */
function cl_alphaStart($start)
{
	$db = database();

	$request = $db->query('substring', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_characters
		WHERE LOWER(SUBSTRING(name, 1, 1)) < {string:first_letter}
			AND approved = {int:is_activated}',
		array(
			'is_activated' => 1,
			'first_letter' => $start,
		)
	);
	list ($start) = $db->fetch_row($request);
	$db->free_result($request);

	return $start;
}

/**
 * Primary query for the memberlist display, runs the query based on the users
 * sort and start selections.
 *
 * @param mixed[] $query_parameters
 * @param string $where
 * @param int $limit
 * @param string $sort
 * @throws Exception
 */
function cl_selectCharacters($query_parameters, $where = '', $limit = 0, $sort = '')
{
	global $context, $modSettings;

	$db = database();

	// Select the members from the database.
	$request = $db->query('', '
		SELECT chr.id_character
		FROM {db_prefix}rps_characters AS chr
			LEFT JOIN {db_prefix}members AS mem ON (chr.id_member = mem.id_member)
		WHERE chr.approved = {int:is_activated}' . (empty($where) ? '' : '
			AND ' . $where) . '
		ORDER BY {raw:sort}
		LIMIT ' . $limit . ', ' . $modSettings['defaultMaxMembers'],
		$query_parameters
	);

	printCharacterListRows($request);
	$db->free_result($request);
}

/**
 * Primary query for the memberlist display, runs the query based on the users
 * sort and start selections.
 *  - Uses printMemberListRows to load the query results in to context
 *
 * @param mixed[] $query_parameters
 * @param string $where
 * @param int $limit
 * @return integer
 * @throws Exception
 */
function cl_searchMembers($query_parameters, $where = '', $limit = 0)
{
	global $modSettings;

	$db = database();

	// Get the number of results
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_characters AS chr
			LEFT JOIN {db_prefix}members AS mem ON chr.id_member = mem.id_member
		WHERE (' . $where . ')
			AND chr.approved = {int:is_activated}',
		$query_parameters
	);
	list ($numResults) = $db->fetch_row($request);
	$db->free_result($request);

	// Select the members from the database.
	$request = $db->query('', '
		SELECT chr.id_character
		FROM {db_prefix}rps_characters AS chr
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = chr.id_member)
		WHERE (' . $where . ')
			AND chr.approved = {int:is_activated}
		ORDER BY {raw:sort}
		LIMIT ' . $limit . ', ' . $modSettings['defaultMaxMembers'],
		$query_parameters
	);

	// Place everything context so the template can use it
	printCharacterListRows($request);
	$db->free_result($request);

	return $numResults;
}

/**
 * Retrieves results of the request passed to it
 * Puts results of request into the context for the sub template.
 *
 * @param resource $request
 * @throws Exception
 */
function printCharacterListRows($request)
{
	global $txt, $context, $scripturl, $memberContext, $settings;

	$db = database();

	// Get the max post number for the bar graph
	$result = $db->query('', '
		SELECT MAX(posts)
		FROM {db_prefix}rps_characters',
		array(
		)
	);
	list ($most_posts) = $db->fetch_row($result);
	$db->free_result($result);

	// Avoid division by zero...
	if ($most_posts == 0)
		$most_posts = 1;

	$characters = array();
	while ($row = $db->fetch_assoc($request)) 
		$characters[] = $row['id_character'];

	// Load all the members for display.
	$context['character'] = loadCharacterContext($characters);

	$bbc_parser = \BBC\ParserWrapper::instance();

	$context['characters'] = array();
	foreach ($characters as $character)
	{
		if (!empty($context['characters'][$character]))
			continue;

		$context['characters'][$character] = $context['character'][$character];
		$context['characters'][$character]['avatar'] = '<a href="' . $context['characters'][$character]['href'] . '">' . $context['characters'][$character]['avatar']['image'] . '</a>';
	}
}

function loadCharacterContext ($character_ids)
{
	global $txt, $scripturl;

	$db = database();

	$request = $db->query('', '
		SELECT chr.id_character, chr.name, chr.avatar, chr.birthdate, chr.title, chr.posts, chr.date_created, chr.last_active, mem.real_name, mem.id_member
		FROM {db_prefix}rps_characters AS chr
			LEFT JOIN {db_prefix}members AS mem ON chr.id_member = mem.id_member
		WHERE id_character' . (count($character_ids) == 1 ? ' = {int:loaded_ids}' : ' IN ({array_int:loaded_ids})'),
		array(
			'loaded_ids' => count($character_ids) == 1 ? $character_ids[0] : $character_ids,
		)
	);
	while ($row = $db->fetch_assoc($request))
		$characters[$row['id_character']] = array(
			'id' => $row['id_character'],
			'name' => '<a href="' . $scripturl . '?action=character;c=' . $row['id_character'] . '" title="' . $txt['profile_of'] . ' ' . trim($row['name']) . '">' . $row['name'] . '</a>',
			'name_actual' => $row['name'],
			'avatar' => Role_Playing_System_Integrate::determineCharacterAvatar(array('avatar' => $row['avatar'])),
			'birthdate' => $row['birthdate'],
			'title' => $row['title'],
			'posts' => comma_format($row['posts']),
			'last_active' => empty($row['last_active']) ? $txt['never'] : standardTime($row['last_active']),
			'member' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '" title="' . $txt['profile_of'] . ' ' . trim($row['real_name']) . '">' . $row['real_name'] . '</a>',
			'href' => $scripturl . '?action=character;c=' . $row['id_character'],
		);
	$db->free_result($request);

	return $characters;
		
}