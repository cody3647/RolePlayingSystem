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


function template_rps_post_above()
{
	global $user_info, $txt, $context, $board_info;

	echo '
						<dl class="postbox">';
	if ($board_info['in_character'])
	{
		//If there are characters, show the character select.
		if(!empty($context['characters']))
		{
			echo '
							<dt class="clear">
								<label for="character" ', isset($context['post_error']['errors']['no_character_ic']) ? ' class="error"' : '', 'id="caption_character">' . $txt['rps_post_character'] . '</label>
							</dt>
							<dd>
								<select name="character" id="post_character" ', isset($context['post_error']['errors']['no_character_ic']) ? ' class="error"' : '', '>
									<option value="0"></option>';

			foreach ($context['characters'] as $id_char => $character)
				echo '
									<option value="', $id_char, '"', ( (isset($context['character']) && $context['character'] == $id_char) ? ' selected="selected"' : '' ) ,'>', $character['name'], '</option>';


	
			echo '
								</select>
							</dd>';
		}
		if ($context['is_new_topic'])
		{
			echo '
							<dt class="clear">
								<label for="date" id="caption_date">' . $txt['rps_post_date'] . '</label>
							</dt>
							<dd class="post_date">
								<input id="post_date" type="text" name="date" ', (isset($context['date']) ? 'value="'.$context['date'].'"' : 'value="" ' ) ,'style="position:relative; z-index:10;"/>
							</dd>';
		}
	}
	echo '
							<dt class="clear">
								<label for="tags" id="caption_tags">' . $txt['rps_post_tags'] . '</label>
							</dt>
							<dd class="post_tags">
								<input id="post_tags" size="80" type="text" name="tags" ', (isset($context['tags']) ? 'value="'.$context['tags'].'"' : 'value="" ' ) ,' />
								<br />
								<span class="smalltext">' . $txt['rps_post_tags_desc'] . '</span>
							</dd>';
	echo '
						</dl>';
	if ($context['is_new_topic'])
		addInlineJavascript('
						$( "#post_date" ).datepicker({
							defaultDate: "2001-01-01",
							dateFormat: "yy-mm-dd",
							appendTo:".post_date",
						});
						', true);
}

function template_rps_display_tags()
{
	global $context, $scripturl, $user_info;
	if(!empty($context['date_tag']) || !empty($context['tags']))
	{
		echo '
			<div class="generalinfo">
				<dl class="rps_tags">';
		if (!empty($context['date_tag']))
		{
			echo '
					<dt class="rps_tag"><i class="icon i-calendar"><s>' . $txt['rps_display_date'] . '</s></i></dt>
					<dd class="rps_tag"><a href="' , $scripturl , '?action=gamecalendar;viewweek;year=' , $context['year_tag'] , ';month=' , $context['month_tag'] , ';day=' , $context['day_tag'] , '" title="', $context['date_tag'] , '">', $context['date_tag'] , '</a></dd>';
		}

		// Is this topic a redirect?
		if (!empty($context['tags']))
		{
			echo '
					<dt class="rps_tag"><i class="icon rps_i-tags"><s>' . $txt['rps_display_tags'] . '</s></i></dt>';

			foreach ($context['tags'] as $tag_id => $tag)
			{
				echo '
					<dd class="rps_tag"><a href="' , $scripturl , '?action=tags;tag=' , $tag_id , '">' , $tag , '</a></dd>';
			}
		}
		echo '
				</dl>
			</div>';		
	}
}

function template_rps_manageboards_ic()
{
	global $context, $txt;
		echo '
				<hr />
				<div id="in_character_div">
					<dl class="settings">
						<dt>
							<label for "in_character">' . $txt['rps_ic_board'] . '</label><br />
							<span class="smalltext">' . $txt['rps_ic_board_desc'] . '</span>
						</dt>
						<dd>
							<input type="checkbox" id="in_character" name="in_character"', $context['board']['in_character'] ? ' checked="checked"': '', ' />
						</dd>
					</dl>
				</div>';	
}