<?php

/**
 * Tag templates
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */
 
/**
 * Loads other needed templates
 */
function template_RpsTags_init()
{
	//loadTemplate('GenericMessages');
}

function template_edit_topic()
{
	// Show the list to edit a topic's tags
	template_show_list('edit_topic');
}

function template_tags_list()
{
	// Show the list of tags
	template_show_list('tags_list');
}