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
	// Custom fields.
	template_show_list('edit_topic');
}

function template_view_tag()
{
	// Custom fields.
	template_show_list('view_tag');
}

function template_tags_list()
{
	// Custom fields.
	template_show_list('tags_list');
}