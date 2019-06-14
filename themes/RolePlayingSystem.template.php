<?php

/**
 * Sub templates for integrating Role Playing System into other templates.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 */

function template_ic_role_playing_system()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					<i class="icon icon-big i-dice"></i>' . $txt['rps_game_stats'] . '</a>
				</h3>
				<p class="inline">
					', $context['common_stats']['boardindex_total_posts'], '', !empty($settings['show_latest_member']) ? ' - ' . $txt['latest_member'] . ': <strong> ' . $context['common_stats']['latest_member']['link'] . '</strong>' : '', ' - ', $txt['most_online_today'], ': ', comma_format($modSettings['mostOnlineToday']), '<br />
					', (!empty($context['latest_post']) ? $txt['latest_post'] . ': <strong>&quot;' . $context['latest_post']['link'] . '&quot;</strong>  ( ' . $context['latest_post']['time'] . ' )' : ''), ' - <a href="', $scripturl, '?action=recent">', $txt['recent_view'], '</a>
				</p>
			</li>';
}

function template_rps_post_above()
{
	global $txt, $context, $board_info;

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
									<option value="', $id_char, '"', ( (isset($context['character']) && $context['character'] == $id_char) ? ' selected' : '' ) , (empty($character['approved']) ? ' disabled' : ''), '>', $character['name'], '</option>';


	
			echo '
								</select>
								', (isset($context['character']) ? '<input id="original_character" type="hidden" name="original_character" value="' . $context['character'] . '">' : '' ), '
							</dd>';
		}
		if ($context['is_new_topic'])
		{
			echo '
							<dt class="clear">
								<label for="date" id="caption_date">' . $txt['rps_post_date'] . '</label>
							</dt>
							<dd class="post_date">
								<input id="post_date" type="text" name="date" ', (isset($context['date']) ? 'value="'.$context['date'].'"' : 'value="" ' ) ,'style="position:relative; z-index:10;" autocomplete="off" />
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
	{
		$current_dates = RpsCurrentDate::instance();

		addInlineJavascript('
						$( "#post_date" ).datepicker({
							defaultDate: "'. $current_dates->start_date->format('Y-m-d') .'",
							dateFormat: "yy-mm-dd",
							appendTo:".post_date",
							numberOfMonths: '. ($current_dates->diff->m +1) .',
						});
						', true);
	}
}

function template_rps_display_tags()
{
	global $context, $scripturl, $txt;
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