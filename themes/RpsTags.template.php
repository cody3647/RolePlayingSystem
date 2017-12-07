<?php

/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0.5
 *
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