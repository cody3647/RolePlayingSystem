<?php

/**
 * Character Profile Templates
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Add settings that will be used in the template
 */
function template_Characterinfo_init()
{
	global $settings;

	loadTemplate('GenericMessages');
}

/**
 * This template displays users details without any option to edit them.
 */
function template_action_summary()
{
	global $context;

	// We do have some data to show I would hope
	if (!empty($context['summarytabs']))
	{
		// All the tab names
		$tabs = array_keys($context['summarytabs']);
		$tab_num = 0;

		// Start with the navigation ul, its converted to the tab navigation by jqueryUI
		echo '
			<div class="profile_center">
				<div id="tabs">
					<ul>';

		// A link for every tab
		foreach ($tabs as $tab)
		{
			$tab_num++;
			echo '
						<li>
							<a href="', (isset($context['summarytabs'][$tab]['href']) ? $context['summarytabs'][$tab]['href'] : '#tab_' . $tab_num), '">', $context['summarytabs'][$tab]['name'], '</a>
						</li>';
		}

		echo '
					</ul>';

		// For preload tabs (those without href), output the content divs and call the templates as defined by the tabs
		$tab_num = 0;
		foreach ($tabs as $tab)
		{
			if (isset($context['summarytabs'][$tab]['href']))
				continue;

			// Start a tab
			$tab_num++;
			echo '
					<div id="tab_', $tab_num, '">';

			// Each template in the tab gets placed in a container
			foreach ($context['summarytabs'][$tab]['templates'] as $templates)
			{
				echo '
						<div class="profile_content">';

				// This container has multiple templates in it (like side x side)
				if (is_array($templates))
				{
					foreach ($templates as $template)
					{
						$block = 'template_profile_block_' . $template;
						$block();
					}
				}
				// Or just a single template is fine
				else
				{
					$block = 'template_profile_block_' . $templates;
					$block();
				}

				echo '
						</div>';
			}

			// Close the tab
			echo '
					</div>';
		}

		// Close the profile center
		echo '
				</div>
			</div>';
	}
}

/**
 * Template for showing all the posts of the user, in chronological order.
 */
function template_action_showPosts()
{
	global $context, $txt;

	echo '
		<div id="profilecenter">
			<h2 class="category_header">
				', empty($context['is_topics']) ? $txt['showMessages'] : $txt['showTopics'], $context['user']['is_owner'] ? '' : ' - ' . $context['character']['name'], '
			</h2>';

	template_pagesection();

	// No posts? Just end the table with a informative message.
	if (empty($context['posts']))
		echo '
				<div class="windowbg2">
					<div class="content">
						', $context['is_topics'] ? $txt['show_topics_none'] : $txt['show_posts_none'], '
					</div>
				</div>';
	else
	{
		// For every post to be displayed, give it its own div, and show the important details of the post.
		foreach ($context['posts'] as $post)
		{
			$post['title'] = '<strong>' . $post['board']['link'] . ' / ' . $post['topic']['link'] . '</strong>';
			$post['date'] = $post['html_time'];
			$post['class'] = $post['alternate'] === 0 ? 'windowbg2' : 'windowbg';

			if (!$post['approved'])
				$post['body'] = '
						<div class="approve_post">
							<em>' . $txt['post_awaiting_approval'] . '</em>
						</div>' . '
					' . $post['body'];

			template_simple_message($post);
		}
	}

	// Show more page numbers.
	template_pagesection();

	echo '
		</div>';
}

/**
 * Template for user statistics, showing graphs and the like.
 */
function template_action_statPanel()
{
	global $context, $txt;

	// First, show a few text statistics such as post/topic count.
	echo '
	<div id="profileview">
		<div id="generalstats">
			<div class="windowbg2">
				<div class="content">
					<dl>
						<dt>', $txt['statPanel_total_time_online'], ':</dt>
						<dd>', $context['time_logged_in'], '</dd>
						<dt>', $txt['statPanel_total_posts'], ':</dt>
						<dd>', $context['num_posts'], ' ', $txt['statPanel_posts'], '</dd>
						<dt>', $txt['statPanel_total_topics'], ':</dt>
						<dd>', $context['num_topics'], ' ', $txt['statPanel_topics'], '</dd>
						<dt>', $txt['statPanel_users_polls'], ':</dt>
						<dd>', $context['num_polls'], ' ', $txt['statPanel_polls'], '</dd>
						<dt>', $txt['statPanel_users_votes'], ':</dt>
						<dd>', $context['num_votes'], ' ', $txt['statPanel_votes'], '</dd>
					</dl>
				</div>
			</div>
		</div>';

	// This next section draws a graph showing what times of day they post the most.
	echo '
		<div id="activitytime" class="flow_hidden">
			<h3 class="category_header hdicon cat_img_clock">
				', $txt['statPanel_activityTime'], '
			</h3>
			<div class="windowbg2">
				<div class="content">';

	// If they haven't post at all, don't draw the graph.
	if (empty($context['posts_by_time']))
		echo '
					<span class="centertext">', $txt['statPanel_noPosts'], '</span>';
	// Otherwise do!
	else
	{
		echo '
					<ul class="activity_stats flow_hidden">';

		// The labels.
		foreach ($context['posts_by_time'] as $time_of_day)
		{
			echo '
						<li', $time_of_day['is_last'] ? ' class="last"' : '', '>
							<div class="bar" style="padding-top: ', ((int) (100 - $time_of_day['relative_percent'])), 'px;" title="', sprintf($txt['statPanel_activityTime_posts'], $time_of_day['posts'], $time_of_day['posts_percent']), '">
								<div style="height: ', (int) $time_of_day['relative_percent'], 'px;">
									<span>', sprintf($txt['statPanel_activityTime_posts'], $time_of_day['posts'], $time_of_day['posts_percent']), '</span>
								</div>
							</div>
							<span class="stats_hour">', $time_of_day['hour_format'], '</span>
						</li>';
		}

		echo '
					</ul>';
	}

	echo '
					<span class="clear" />
				</div>
			</div>
		</div>';

	// Two columns with the most popular boards by posts and activity (activity = users posts / total posts).
	echo '
		<div class="flow_hidden">
			<div id="popularposts">
				<h3 class="category_header hdicon cat_img_write">
					', $txt['statPanel_topBoards'], '
				</h3>
				<div class="windowbg2">
					<div class="content">';

	if (empty($context['popular_boards']))
		echo '
						<span class="centertext">', $txt['statPanel_noPosts'], '</span>';

	else
	{
		echo '
						<dl>';

		// Draw a bar for every board.
		foreach ($context['popular_boards'] as $board)
		{
			echo '
							<dt>', $board['link'], '</dt>
							<dd>
								<div class="profile_pie" style="background-position: -', ((int) ($board['posts_percent'] / 5) * 20), 'px 0;" title="', sprintf($txt['statPanel_topBoards_memberposts'], $board['posts'], $board['total_posts_member'], $board['posts_percent']), '">
									', sprintf($txt['statPanel_topBoards_memberposts'], $board['posts'], $board['total_posts_member'], $board['posts_percent']), '
								</div>
								<span>', empty($context['hide_num_posts']) ? $board['posts'] : '', '</span>
							</dd>';
		}

		echo '
						</dl>';
	}

	echo '
					</div>
				</div>
			</div>
			<div id="popularactivity">
				<h3 class="category_header hdicon cat_img_piechart">
					', $txt['statPanel_topBoardsActivity'], '
				</h3>
				<div class="windowbg2">
					<div class="content">';

	if (empty($context['board_activity']))
		echo '
						<span>', $txt['statPanel_noPosts'], '</span>';
	else
	{
		echo '
						<dl>';

		// Draw a bar for every board.
		foreach ($context['board_activity'] as $activity)
		{
			echo '
							<dt>', $activity['link'], '</dt>
							<dd>
								<div class="profile_pie" style="background-position: -', ((int) ($activity['percent'] / 5) * 20), 'px 0;" title="', sprintf($txt['statPanel_topBoards_posts'], $activity['posts'], $activity['total_posts'], $activity['posts_percent']), '">
									', sprintf($txt['statPanel_topBoards_posts'], $activity['posts'], $activity['total_posts'], $activity['posts_percent']), '
								</div>
								<span>', $activity['percent'], '%</span>
							</dd>';
		}

		echo '
						</dl>';
	}

	echo '
					</div>
				</div>
			</div>
		</div>
	</div>';
}

/**
 * Profile Summary Block
 *
 * Show avatar, title, blurb, group info, number of posts, karma, likes
 * Has links to show posts, drafts and attachments
 */
function template_profile_block_summary()
{
	global $txt, $context, $modSettings, $scripturl, $settings;

	echo '
			<div class="profileblock_left">
				<h2 class="category_header hdicon cat_img_profile">
					', ($context['user']['is_owner']) ? '<a href="' . $scripturl . '?action=character;sa=edit;c=' . $context['character']['id'] . ';u=' . $context['member']['id'] . '">' . $txt['profile_user_summary'] . '</a>' : $txt['profile_user_summary'], '
				</h2>
				<div id="basicinfo">
					<div class="username">
				</div>';
echo
					$context['character']['avatar']['image'], '
					<span id="userstatus">', template_member_online($context['member']), '<span class="smalltext"> ' . $context['member']['online']['label'] . '</span>', '</span>
				</div>
				<div id="detailedinfo">
					<dl>';

	// The members display name
	echo '
						<dt>', $txt['display_name'], ':</dt>
						<dd>', $context['character']['name'], '</dd>';


	// And how old are we, oh my!
	echo '
						<dt>', $txt['age'], ':</dt>
						<dd>', $context['character']['age'] . ($context['character']['today_is_birthday'] ? ' &nbsp; <img src="' . $settings['images_url'] . '/cake.png" alt="" />' : ''), '</dd>';

	// Title?
	if (!empty($modSettings['titlesEnable']) && !empty($context['character']['title']))
		echo '
						<dt>', $txt['custom_title'], ':</dt>
						<dd>', $context['character']['title'], '</dd>';



	// Show the users signature.
	if ($context['signature_enabled'] && !empty($context['character']['signature']))
	{
		echo '
						<dt>', $txt['signature'], ':</dt>
						<dd>', $context['character']['signature'], '</dd>';
	}
	// close this block up
	echo '
					</dl>
				</div>
			</div>';
}

/**
 * Profile Info Block
 *
 * Show additional user details including: age, gender, location, join date,
 * localization details (language and time)
 * If user has permissions can see IP address
 */
function template_profile_block_user_info()
{
	global $settings, $txt, $context, $scripturl, $modSettings;

	echo '
		<div class="profileblock_right">
			<h3 class="category_header hdicon cat_img_stats_info">
				', ($context['user']['is_owner']) ? '<a href="' . $scripturl . '?action=profile;area=forumprofile;u=' . $context['member']['id'] . '">' . $txt['profile_user_info'] . '</a>' : $txt['profile_user_info'], '
			</h3>
			<div class="profileblock">
				<dl>';
	// Some links to this users fine work
	echo '
					<dt>', $txt['profile_activity'], ': </dt>
					<dd>
						<a href="', $scripturl, '?action=profile;area=showposts;u=', $context['member']['id'], '">', $txt['showPosts'], '</a>
						<br />';

	echo '
						<a href="', $scripturl, '?action=profile;area=statistics;u=', $context['member']['id'], '">', $txt['statPanel'], '</a>
					</dd>';
		// How long have they been a member, and when were they last on line?
	echo '
					<dt>', $txt['date_registered'], ':</dt>
					<dd>', $context['character']['created'], '</dd>

					<dt>', $txt['lastLoggedIn'], ':</dt>
					<dd>', $context['character']['last_active'], '</dd>';

	// Some posts stats for fun
	echo '
					<dt>', $txt['profile_posts'], ':</dt>
					<dd>', $context['character']['posts'], ' (', $context['character']['posts_per_day'], ' ', $txt['posts_per_day'], ')</dd>';

	// nuff about them, lets get back to me!
	echo '
				</dl>
			</div>
	</div>';
}

/**
 * Profile Posts Block
 *
 * Shows the most recent posts for this user
 */
function template_profile_block_posts()
{
	global $txt, $context, $scripturl;

	// The posts block
	echo '
	<h3 class="category_header hdicon cat_img_posts">
		<a href="', $scripturl, '?action=profile;area=showposts;sa=messages;u=', $context['member']['id'], '">', $txt['profile_recent_posts'], '</a>
	</h3>
	<div class="windowbg">
		<div class="content">
			<table id="ps_recentposts">';

	if (!empty($context['posts']))
	{
		echo '
				<tr>
					<th class="recentpost">', $txt['message'], '</th>
					<th class="recentposter">', $txt['board'], '</th>
					<th class="recentboard">', $txt['subject'], '</th>
					<th class="recenttime">', $txt['date'], '</th>
				</tr>';

		foreach ($context['posts'] as $post)
			echo '
				<tr>
					<td class="recentpost">', $post['body'], '</td>
					<td class="recentboard">', $post['board']['link'], '</td>
					<td class="recentsubject">', $post['link'], '</td>
					<td class="recenttime">', $post['time'], '</td>
				</tr>';
	}
	// No data for this member
	else
		echo '
				<tr>
					<td class="norecent">', (isset($context['loadaverage']) ? $txt['profile_loadavg'] : $txt['profile_posts_no']), '</td>
				</tr>';

	// All done
	echo '
			</table>
		</div>
	</div>';
}

/**
 * Profile Topics Block
 *
 * Shows the most recent topics that this user has started
 */
function template_profile_block_topics()
{
	global $txt, $context, $scripturl;

	// The topics block
	echo '
	<h3 class="category_header hdicon cat_img_topics">
		<a href="', $scripturl, '?action=profile;area=showposts;sa=topics;u=', $context['member']['id'], '">', $txt['profile_topics'], '</a>
	</h3>
	<div class="windowbg">
		<div class="content">
			<table id="ps_recenttopics">';

	if (!empty($context['topics']))
	{
		echo '
				<tr>
					<th class="recenttopic">', $txt['subject'], '</th>
					<th class="recentboard">', $txt['board'], '</th>
					<th class="recenttime">', $txt['date'], '</th>
				</tr>';

		foreach ($context['topics'] as $post)
			echo '
				<tr>
					<td class="recenttopic">', $post['link'], '</td>
					<td class="recentboard">', $post['board']['link'], '</td>
					<td class="recenttime">', $post['time'], '</td>
				</tr>';
	}
	// No data for this member
	else
		echo '
				<tr>
					<td class="norecent">', $txt['profile_topics_no'], '</td>
				</tr>';

	// All done
	echo '
			</table>
		</div>
	</div>';
}