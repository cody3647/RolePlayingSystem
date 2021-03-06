<?php

/**
 * Functions for administration of game calendar events.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Remove a holiday from the calendar.
 *
 * @package Calendar
 * @param $event_ids
 * @throws Exception
 */
function removeEvents($event_ids)
{
	$db = database();

	if (!is_array($event_ids))
		$event_ids = array($event_ids);

	$db->query('', '
		DELETE FROM {db_prefix}rps_events
		WHERE id_event IN ({array_int:id_event})',
		array(
			'id_event' => $event_ids,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}

/**
 * Updates a calendar holiday
 *
 * @package Calendar
 * @param $event
 * @param $year
 * @param $month
 * @param $day
 * @param string $title
 * @throws Exception
 */
function editEvent($event, $year, $month, $day, $title)
{
	$db = database();

	$db->query('', '
		UPDATE {db_prefix}rps_events
		SET event_year = {int:year}, event_month = {int:month}, event_day = {string:day}, title = {string:title}
		WHERE id_event = {int:selected_event}',
		array(
			'year' => $year,
			'month' => $month,
			'day' => $day,
			'selected_event' => $event,
			'title' => $title,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}


/**
 * Get a specific holiday
 *
 * @package Calendar
 * @param $id_event
 * @return array
 * @throws Exception
 */
function getEvent($id_event)
{
	$db = database();

	$request = $db->query('', '
		SELECT *
		FROM {db_prefix}rps_events
		WHERE id_event = {int:selected_event}
		LIMIT 1',
			array(
				'selected_event' => $id_event,
			)
		);
	while ($row = $db->fetch_assoc($request))
	{
		if(!is_numeric($row['event_day']))
		{
			$relative = explode(' ', $row['event_day']);
			$row['event_day'] = '';
		}
		
		$event = array(
			'id' => $row['id_event'],
			'day' => $row['event_day'],
			'month' => $row['event_month'],
			'year' => $row['event_year'],
			'title' => $row['title'],
			'ordinal' => !empty($relative[0]) ? $relative[0] : '',
			'dayname' => !empty($relative[1]) ? $relative[1] : '',
		);
	}
	$db->free_result($request);

	return $event;
}

/**
 * Gets all of the holidays for the listing
 *
 * @package Calendar
 * @param int $start The item to start with (for pagination purposes)
 * @param int $items_per_page The number of items to show per page
 * @param string $sort A string indicating how to sort the results
 * @return array
 * @throws Exception
 */
function list_getEvents($start, $items_per_page, $sort)
{
	$db = database();

	return $db->fetchQuery('
		SELECT id_event, event_year, event_month, event_day, title
		FROM {db_prefix}rps_events
		ORDER BY {raw:sort}
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
			'sort' => $sort,
		)
	);
}

/**
 * Helper function to get the total number of holidays
 *
 * @package Calendar
 * @return int
 * @throws Exception
 */
function list_getNumEvents()
{
	$db = database();

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_events',
		array(
		)
	);
	list ($num_items) = $db->fetch_row($request);
	$db->free_result($request);

	return (int) $num_items;
}

/**
 * Remove a holiday from the calendar.
 *
 * @package Calendar
 * @param $event_ids
 * @throws Exception
 */
function removePhases($event_ids)
{
	$db = database();

	if (!is_array($event_ids))
		$event_ids = array($event_ids);

	$db->query('', '
		DELETE FROM {db_prefix}rps_phases
		WHERE id_phase IN ({array_int:id_phase})',
		array(
			'id_phase' => $event_ids,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}

/**
 * Updates a calendar holiday
 *
 * @package Calendar
 * @param $id
 * @param int $date
 * @param $time
 * @param $phase
 * @throws Exception
 */
function editPhase($id, $date, $time, $phase)
{
	$db = database();
	
	$db->query('', '
		UPDATE {db_prefix}rps_phases
		SET phase_date = {date:phase_date}, phase_time = {string:phase_time}, phase = {string:phase}
		WHERE id_phase = {int:selected_event}',
		array(
			'phase_date' => $date,
			'phase_time' => $time,
			'phase' => $phase,
			'selected_event' => $id,
		)
	);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
}


/**
 * Get a specific holiday
 *
 * @package Calendar
 * @param $id_phase
 * @return array
 * @throws Exception
 */
function getPhase($id_phase)
{
	$db = database();

	$request = $db->query('', '
		SELECT id_phase, phase, YEAR(phase_date) as phase_year, MONTH(phase_date) as phase_month, DAY(phase_date) as phase_day, HOUR(phase_time) as phase_hour, MINUTE(phase_time) as phase_minute
		FROM {db_prefix}rps_phases
		WHERE id_phase = {int:selected_event}
		LIMIT 1',
			array(
				'selected_event' => $id_phase,
			)
		);
	while ($row = $db->fetch_assoc($request))
	{
		
		$event = array(
			'id' => $row['id_phase'],
			'day' => $row['phase_day'],
			'month' => $row['phase_month'],
			'year' => $row['phase_year'],
			'phase' => $row['phase'],
			'hour' => $row['phase_hour'],
			'minute' => $row['phase_minute'],
		);
	}
	$db->free_result($request);

	return $event;
}


/**
 * Gets all of the holidays for the listing
 *
 * @package Calendar
 * @param int $start The item to start with (for pagination purposes)
 * @param int $items_per_page The number of items to show per page
 * @param string $sort A string indicating how to sort the results
 * @return array
 * @throws Exception
 */
function list_getPhases($start, $items_per_page, $sort)
{
	$db = database();

	return $db->fetchQuery('
		SELECT id_phase, phase, phase_date, phase_time
		FROM {db_prefix}rps_phases
		ORDER BY {raw:sort}
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
			'sort' => $sort,
		)
	);
}

/**
 * Helper function to get the total number of holidays
 *
 * @package Calendar
 * @return int
 * @throws Exception
 */
function list_getNumPhases()
{
	$db = database();

	$request = $db->query('', '
		SELECT COUNT(*)
		FROM {db_prefix}rps_phases',
		array(
		)
	);
	list ($num_items) = $db->fetch_row($request);
	$db->free_result($request);

	return (int) $num_items;
}






function insert_downloaded_events($holidays, $phases, $postData)
{
	$db = database();

	foreach($holidays as $holiday)
	{
		list( $year, $month, $day) = explode('-',$holiday['holiday_date']);
		$insert_holidays[] = array((int) $year, (int) $month, (int) $day, $holiday['title']);
		unset($year);
		unset($month);
		unset($day);
	}
	
	//phases array('phase' => , 'phase_date' => , 'phase_time' => )
	
	insertEvent($insert_holidays);
	
	
	insertPhase($phases);

	updateSettings(array(
		'rps_gamecalendar_updated' => time(),
	));
	
}

/**
 * Insert a new holiday
 *
 * @package Calendar
 * @param $events
 * @throws Exception
 */
function insertEvent($events)
{
	$db = database();

	$db->insert('ignore',
		'{db_prefix}rps_events',
		array(
			'event_year' => 'int', 'event_month' => 'int', 'event_day' => 'string-20', 'title' => 'string-60',
		),
		$events,
		array('id_event')
	);
}

/**
 * Insert a new holiday
 *
 * @package Calendar
 * @param $phases
 * @throws Exception
 */
function insertPhase($phases)
{
	$db = database();

	$db->insert('ignore',
		'{db_prefix}rps_phases',
		array(
			'phase_date' => 'date', 'phase_time' => 'string-8', 'phase' => 'string-13',
		),
		$phases,
		array('id_phase')
	);
}

