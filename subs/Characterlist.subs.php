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
 * Reads the custom profile fields table and gets all items that were defined
 * as being shown on the memberlist
 *  - Loads the fields in to $context['custom_profile_fields']
 *  - Defines the sort querys for the custom columns
 *  - Defines additional query parameters and joins needed to the memberlist
 */
function cl_CustomProfile()
{
	global $context;

	$db = database();

	$context['custom_profile_fields'] = array();

	// Find any custom profile fields that are to be shown for the memberlist?
	$request = $db->query('', '
		SELECT col_name, field_name, field_desc, field_type, bbc, enclose, vieworder
		FROM {db_prefix}rps_character_fields
		WHERE active = {int:active}
			AND show_memberlist = {int:show}
			AND private < {int:private_level}
		ORDER BY vieworder',
		array(
			'active' => 1,
			'show' => 1,
			'private_level' => 2,
		)
	);
	while ($row = $db->fetch_assoc($request))
	{
		// Avoid collisions
		$curField = 'cust_' . $row['col_name'];

		// Load the standard column info
		$context['custom_profile_fields']['columns'][$curField] = array(
			'label' => $row['field_name'],
			'class' => $row['field_name'],
			'type' => $row['field_type'],
			'bbc' => !empty($row['bbc']),
			'enclose' => $row['enclose'],
		);

		// Have they selected to sort on a custom column? .., then we build the query
		if (isset($_REQUEST['sort']) && $_REQUEST['sort'] === $curField)
		{
			// Build the sort queries.
			if ($row['field_type'] != 'check')
				$context['custom_profile_fields']['columns'][$curField]['sort'] = array(
					'down' => 'LENGTH(cfd' . $curField . '.value) > 0 ASC, COALESCE(cfd' . $curField . '.value, 1=1) DESC, cfd' . $curField . '.value DESC',
					'up' => 'LENGTH(cfd' . $curField . '.value) > 0 DESC, COALESCE(cfd' . $curField . '.value, 1=1) ASC, cfd' . $curField . '.value ASC'
				);
			else
				$context['custom_profile_fields']['columns'][$curField]['sort'] = array(
					'down' => 'cfd' . $curField . '.value DESC',
					'up' => 'cfd' . $curField . '.value ASC'
				);

			// Build the join and parameters for the sort query
			$context['custom_profile_fields']['join'] = 'LEFT JOIN {db_prefix}rps_character_fields_data AS cfd' . $curField . ' ON (cfd' . $curField . '.variable = {string:cfd' . $curField . '} AND cfd' . $curField . '.id_character = chr.id_character)';
			$context['custom_profile_fields']['parameters']['cfd' . $curField] = $row['col_name'];
		}
	}
	$db->free_result($request);

	return !empty($context['custom_profile_fields']);
}

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
 */
function cl_selectCharacters($query_parameters, $where = '', $limit = 0, $sort = '')
{
	global $context, $modSettings;

	$db = database();

	// Select the members from the database.
	$request = $db->query('', '
		SELECT chr.id_character
		FROM {db_prefix}rps_characters AS chr ' . 
			(!empty($context['custom_profile_fields']['join']) ? $context['custom_profile_fields']['join'] : '') . '
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
 * @param string|string[]|null $customJoin
 * @param string $where
 * @param int $limit
 * @return integer
 */
function cl_searchMembers($query_parameters, $customJoin = '', $where = '', $limit = 0)
{
	global $modSettings;

	$db = database();

	// Get the number of results
	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_characters AS chr
			LEFT JOIN {db_prefix}members AS mem ON chr.id_member = mem.id_member
			' . (empty($customJoin) ? '' : implode('
			', $customJoin)) . '
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
			' . (empty($customJoin) ? '' : implode('
			', $customJoin)) . '
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
 * Finds custom profile fields that were defined as searchable
 */
function cl_findSearchableCustomFields()
{
	global $context;

	$db = database();

	$request = $db->query('', '
		SELECT col_name, field_name, field_desc
			FROM {db_prefix}rps_character_fields
		WHERE active = {int:active}
			' . (allowedTo('admin_forum') ? '' : ' AND private < {int:private_level}') . '
			AND can_search = {int:can_search}
			AND (field_type IN ({string:field_type_text}, {string:field_type_textarea}, {string:field_type_select}))',
		array(
			'active' => 1,
			'can_search' => 1,
			'private_level' => 2,
			'field_type_text' => 'text',
			'field_type_textarea' => 'textarea',
			'field_type_select' => 'select',
		)
	);
	$context['custom_search_fields'] = array();
	while ($row = $db->fetch_assoc($request))
		$context['custom_search_fields'][$row['col_name']] = array(
			'colname' => $row['col_name'],
			'name' => $row['field_name'],
			'desc' => $row['field_desc'],
		);
	$db->free_result($request);
}

/**
 * Retrieves results of the request passed to it
 * Puts results of request into the context for the sub template.
 *
 * @param resource $request
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

	$bbc_parser = \BBC\ParserWrapper::getInstance();

	$context['characters'] = array();
	foreach ($characters as $character)
	{
		if (!empty($context['characters'][$character]))
			continue;

		$context['characters'][$character] = $context['character'][$character];
		$context['characters'][$character]['avatar'] = '<a href="' . $context['characters'][$character]['href'] . '">' . $context['characters'][$character]['avatar']['image'] . '</a>';

		// Take care of the custom fields if any are being displayed
		if (!empty($context['custom_profile_fields']['columns']))
		{
			foreach ($context['custom_profile_fields']['columns'] as $key => $column)
			{
				$curField = substr($key, 5);

				// Does this member even have it filled out?
				if (!isset($context['characters'][$character]['options'][$curField]))
				{
					$context['characters'][$character]['options'][$curField] = '';
					continue;
				}

				// Should it be enclosed for display?
				if (!empty($column['enclose']) && !empty($context['characters'][$character]['options'][$curField]))
					$context['characters'][$character]['options'][$curField] = strtr($column['enclose'], array(
						'{SCRIPTURL}' => $scripturl,
						'{IMAGES_URL}' => $settings['images_url'],
						'{DEFAULT_IMAGES_URL}' => $settings['default_images_url'],
						'{INPUT}' => $context['characters'][$character]['options'][$curField],
					));

				// Anything else to make it look "nice"
				if ($column['bbc'])
					$context['characters'][$character]['options'][$curField] = strip_tags($bbc_parser->parseCustomFields($context['characters'][$character]['options'][$curField]));
				elseif ($column['type'] === 'check')
					$context['characters'][$character]['options'][$curField] = $context['characters'][$character]['options'][$curField] == 0 ? $txt['no'] : $txt['yes'];
			}
		}
	}
}

function loadCharacterContext ($character_ids)
{
	global $txt, $scripturl;

	$db = database();

	$request = $db->query('', '
		SELECT chr.id_character, chr.name, chr.avatar, chr.birthdate, chr.title, chr.gender, chr.posts, chr.date_created, chr.last_active, mem.real_name, mem.id_member
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
	

	$request = $db->query('', '
		SELECT d.id_character, d.variable, d.value
		FROM {db_prefix}rps_character_fields_data AS d 		
		WHERE d.id_character' . (count($character_ids) == 1 ? ' = {int:loaded_ids}' : ' IN ({array_int:loaded_ids})'),
		array(
			'loaded_ids' => count($character_ids) == 1 ? $character_ids[0] : $character_ids,
		)
	);
	while ($row = $db->fetch_assoc($request))
		$characters[$row['id_character']]['options'][$row['variable']] = $row['value'];
	$db->free_result($request);

	return $characters;
		
}