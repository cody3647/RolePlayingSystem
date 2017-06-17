<?php

/**
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright (c) Cody Williams, 2017
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

$tables = array(
    array(
        'name' => '{db_prefix}rps_characters',
        'columns' => array(
			array('name' => 'id_character',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'id_member',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true),
			array('name' => 'name',			'type' => 'varchar',	'size' => 255),
			array('name' => 'avatar',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'signature',	'type' => 'text'),
			array('name' => 'birthdate',	'type' => 'date',		'default' => '0000-00-01'),
			array('name' => 'title',		'type' => 'varchar',	'size' => 255,	'default' => ''),
			array('name' => 'posts',		'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'default' => 0),
			array('name' => 'date_created',	'type' => 'int',		'size' => 10,	'unsigned' => true,	'default' => 0),
			array('name' => 'last_active',	'type' => 'int',		'size' => 10,	'unsigned' => true,	'default' => 0),
			array('name' => 'approved',	'type' => 'tinyint',		'size' => 3,	'default' => 0	),
			array('name' => 'retired',	'type' => 'tinyint',		'size' => 3,	'default' => 0	)
			),
        'indexes' => array(
			array('name' => 'primary', 'type' => 'primary',	'columns' => array('id_character')	),
			array('name' => 'member', 'type' => 'key',	'columns' => array('id_member')		),
        )
    ),
	array(
        'name' => '{db_prefix}rps_events',
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
        'name' => '{db_prefix}rps_phases',
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
        'name' => '{db_prefix}rps_tags',
        'columns' => array(
            array('name' => 'id_tag',	'type' => 'mediumint',	'size' => 8,	'unsigned' => true,	'auto' => true),
			array('name' => 'tag',		'type' => 'varchar',	'size' => 255),
        ),
        'indexes' => array(
			array('name' => 'primary',	'type' => 'primary',	'columns' => array('id_tag')	),
			array('name' => 'uniq_tag',	'type' => 'unique',		'columns' => array('tag')	),
        )
    ),
	array(
        'name' => '{db_prefix}rps_tags_data',
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
	array(
		'name' => '{db_prefix}rps_character_fields',
		'columns' => array(
			array('name' => 'id_field',        'type' => 'smallint', 'size' => 5, 'auto' => true),
			array('name' => 'col_name',        'type' => 'varchar', 'default' => '', 'size' => 12),
			array('name' => 'field_name',      'type' => 'varchar', 'default' => '', 'size' => 40),
			array('name' => 'field_desc',      'type' => 'varchar', 'default' => '', 'size' => 255),
			array('name' => 'field_type',      'type' => 'varchar', 'default' => 'text', 'size' => 8),
			array('name' => 'field_length',    'type' => 'smallint', 'size' => 5, 'default' => 255),
			array('name' => 'field_options',   'type' => 'text'),
			array('name' => 'mask',            'type' => 'varchar', 'default' => '', 'size' => 255),
			array('name' => 'rows',            'type' => 'smallint', 'size' => 5, 'default' => 3),
			array('name' => 'cols',            'type' => 'smallint', 'size' => 5, 'default' => 30),
			array('name' => 'show_reg',        'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'show_display',    'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'show_memberlist', 'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'show_profile',    'type' => 'varchar', 'default' => 'forumprofile', 'size' => 20),
			array('name' => 'private',         'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'active',          'type' => 'tinyint', 'size' => 3, 'default' => 1),
			array('name' => 'bbc',             'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'can_search',      'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'default_value',   'type' => 'varchar', 'default' => '', 'size' => 255),
			array('name' => 'enclose',         'type' => 'text'),
			array('name' => 'placement',       'type' => 'tinyint', 'size' => 3, 'default' => 0),
			array('name' => 'vieworder',       'type' => 'smallint', 'size' => 5, 'default' => 0),
		),
		'indexes' => array(
			array('name' => 'id_field', 'columns' => array('id_field'), 'type' => 'primary'),
			array('name' => 'col_name', 'columns' => array('col_name'), 'type' => 'unique'),
		),
	),
	array(
		'name' => '{db_prefix}custom_fields_data',
		'columns' => array(
			array('name' => 'id_character', 'type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'default' => 0),
			array('name' => 'variable',  'type' => 'varchar', 'size' => 255, 'default' => ''),
			array('name' => 'value',     'type' => 'text'),
		),
		'indexes' => array(
				array('name' => 'id_character', 'columns' => array('id_character', 'variable(30)'), 'type' => 'primary'),
				array('name' => 'id_character', 'columns' => array('id_character'), 'type' => 'key'),
		),
	)
);


foreach ($tables as $table)
    $dbtbl->db_create_table('{db_prefix}' . $table['name'], $table['columns'], $table['indexes'], array(), 'ignore');
