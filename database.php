<?php

/**
 * Database changes and default settings.
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

// If we have found SSI.php and we are outside of ELK, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
{
	require_once(dirname(__FILE__) . '/SSI.php');
}
// If we are outside ELK and can't find SSI.php, then throw an error
elseif (!defined('ELK'))
{
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as Elkarte\'s SSI.php.');
}

$dbtbl = db_table();

#New database tables to add.
$new_tables = array(
    array(
        'name' => 'characters',
        'columns' => array(
			array('name' => 'id_character',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'id_member',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true),
			array('name' => 'name',			'type' => 'varchar',	'size' => 255),
			array('name' => 'avatar',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'signature',	'type' => 'text'),
			array('name' => 'posts',		'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'default' => 0),
			array('name' => 'date_created',	'type' => 'int',		'size' => 10,	'unsigned' => true,	'default' => 0),
			array('name' => 'last_active',	'type' => 'int',		'size' => 10,	'unsigned' => true,	'default' => 0),
			array('name' => 'approved',	'type' => 'tinyint',		'size' => 3,	'default' => 0	),
			array('name' => 'retired',	'type' => 'tinyint',		'size' => 3,	'default' => 0	),
			array('name' => 'title',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'birthdate',	'type' => 'date',		'default' => '0000-00-01'),
			array('name' => 'personal_text',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'custom_1',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'custom_2',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'custom_3',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'custom_4',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'custom_5',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			),
        'indexes' => array(
			array('name' => 'primary', 'type' => 'primary',	'columns' => array('id_character')	),
			array('name' => 'member', 'type' => 'key',	'columns' => array('id_member')		),
        )
    ),
	array(
        'name' => 'events',
        'columns' => array(
            array('name' => 'id_event',		'type' => 'smallint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'event_year',	'type' => 'smallint',	'size' => 4,	'default' => 0 ),
			array('name' => 'event_month',	'type' => 'varchar',	'size' => 2),
			array('name' => 'event_day',	'type' => 'varchar',	'size' => 20),
			array('name' => 'title',		'type' => 'varchar',	'size' => 60),
        ),
        'indexes' => array(
			array('name' => 'primary',			'type' => 'primary',	'columns' => array('id_event')	),
			array('name' => 'uniq_date_title',	'type' => 'unique',		'columns' => array('event_year', 'event_month', 'event_day', 'title')	),
        )
    ),
	array(
        'name' => 'phases',
        'columns' => array(
            array('name' => 'id_phase',		'type' => 'smallint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'phase_date',	'type' => 'date'),
			array('name' => 'phase_time',	'type' => 'time'),
			array('name' => 'phase',		'type' => 'varchar',	'size' => 13),
        ),
        'indexes' => array(
			array('name' => 'primary',			'type' => 'primary',	'columns' => array('id_phase')	),
			array('name' => 'uniq_date_phase',	'type' => 'unique',		'columns' => array('phase_date', 'phase_time', 'phase')	),
        )
    ),
	array(
        'name' => 'tags',
        'columns' => array(
            array('name' => 'id_tag',		'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'tag',			'type' => 'varchar',	'size' => 255),
			array('name' => 'description',	'type' => 'text',		'default' => '' ),
        ),
        'indexes' => array(
			array('name' => 'primary',	'type' => 'primary',	'columns' => array('id_tag')	),
			array('name' => 'uniq_tag',	'type' => 'unique',		'columns' => array('tag')	),
        )
    ),
	array(
        'name' => 'tags_data',
        'columns' => array(
            array('name' => 'id_topic',		'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'id_tag',		'type' => 'mediumint',	'size' => 8,	'unsigned' => true),
			array('name' => 'date_added',	'type' => 'int',	'size' => 10,		'unsigned' => true),
			array('name' => 'id_member',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true),
        ),
        'indexes' => array(
			array('name' => 'primary',	'type' => 'primary',	'columns' => array('id_topic', 'id_tag')	),
			array('name' => 'topic',	'type' => 'key',		'columns' => array('id_topic')	),
			array('name' => 'tag',		'type' => 'key',		'columns' => array('id_tag')	),
        )
    ),
);

foreach ($new_tables as $table)
    $dbtbl->db_create_table('{db_prefix}rps_' . $table['name'], $table['columns'], $table['indexes'], array(), 'ignore');

#New columns to add to the Elkarte tables
$new_columns = array(
	array(
		'table' => '{db_prefix}boards',
		'info' => array(
			'name' => 'in_character',
			'type' => 'tinyint',
			'size' => '3',
			'default'=> '0',
		)
	),
	array(
		'table' => '{db_prefix}topics',
		'info' => array(
			'name' => 'date_tag',
			'type' => 'date', 
			'null' => true,
		)
	),
	array(
		'table' => '{db_prefix}messages',
		'info' => array(
			'name' => 'id_character',
			'type' => 'mediumint',
			'size' => 8,
			'unsigned' => true,
			'default' => 0,
		)
	),
);

foreach($new_columns as $column)
	$dbtbl->db_add_column($column['table'],$column['info']);


//Default Settings
global $modSettings;

$defaults = array(
	'rps_current_start' => '2000-01-01',
	'rps_current_end' => '2000-01-01',
	'rps_begining' => '2000-01-01',

);

$updates = array(
	'rps_version' => '1.0',
);

foreach ($defaults as $index => $value)
{
	if (!isset($modSettings[$index]))
		$updates[$index] = $value;
}

updateSettings($updates);
