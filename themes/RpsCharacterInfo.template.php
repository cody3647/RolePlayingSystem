<?php

/**
 * Character Profile Templates
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This file contains code covered by:
 * copyright: ElkArte Forum contributors
 * license:   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 */

/**
 * Add settings that will be used in the template
 */
function template_RpsCharacterInfo_init()
{
	global $settings;

	loadTemplate('GenericMessages');
}

/**
 * This template displays users details without any option to edit them.
 */
function template_action_rps_summary()
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
function template_action_rps_showPosts()
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
 * Consolidated profile block output.
 */
function template_rps_profile_blocks()
{
	global $context;

	if (empty($context['profile_blocks']))
	{
		return;
	}
	else
	{
		foreach ($context['profile_blocks'] as $profile_block)
		{
			$profile_block();
		}
	}
}

function template_profile_block_rps_unapproved()
{
	global $txt, $user_info;
	echo '
		<div class="information">
		<p><i class="icon i-warning"></i>', $txt['rps_character_unapproved_msg'], '</p>';
	
	if($user_info['is_admin'])
	{
		
	}
		
	echo'
		</div>';
	
}

/**
 * Profile Summary Block
 *
 * Show avatar, title, blurb, group info, number of posts, karma, likes
 * Has links to show posts, drafts and attachments
 */
function template_profile_block_rps_summary()
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
	
	echo '				<dt>', $txt['rps_profile_birthdate'], ':</dt>
						<dd><time title="', $txt['rps_profile_birthdate'], '" datetime="', $context['character']['birth_datetime'], '">', $context['character']['birth_date'], '</time></dd>';


	// And how old are we, oh my!
	echo '
						<dt>', $txt['age'], ':</dt>
						<dd>', $context['character']['age'] . ($context['character']['today_is_birthday'] ? ' &nbsp; <img src="' . $settings['images_url'] . '/cake.png" alt="" />' : ''), '</dd>';
	echo '
						<dt>', $txt['rps_gender'], ':</dt>
						<dd>', $context['character']['gender'], '</dd>';
	echo '
						<dt>', $txt['rps_location'], ':</dt>
						<dd>', $context['character']['location'], '</dd>';
	echo '
						<dt>', $txt['rps_personal_text'], ':</dt>
						<dd>', $context['character']['personal_text'], '</dd>';
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
function template_profile_block_rps_user_info()
{
	global $settings, $txt, $context, $scripturl, $modSettings;

	echo '
		<div class="profileblock_right">
			<h3 class="category_header hdicon cat_img_stats_info">
				', $txt['rps_profile_info'], '
			</h3>
			<div class="profileblock">
				<dl>';
	// Some links to this users fine work
	echo '
					<dt>', $txt['profile_activity'], ': </dt>
					<dd>
						<a href="', $scripturl, '?action=character;area=showposts;c=', $context['character']['id'], '">', $txt['showPosts'], '</a>
						<br />';

	echo '
						<a href="', $scripturl, '?action=character;area=showtopics;c=', $context['character']['id'], '">', $txt['rps_showTopics'], '</a>
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
function template_profile_block_rps_posts()
{
	global $txt, $context, $scripturl;

	// The posts block
	echo '
	<h3 class="category_header hdicon cat_img_posts">
		<a href="', $scripturl, '?action=character;sa=showposts;c=', $context['character']['id'], '">', $txt['profile_recent_posts'], '</a>
	</h3>
	<div class="windowbg">
		<div class="content">
			<table id="ps_recentposts">';

	if (!empty($context['posts']))
	{
		echo '
				<tr>
					<th class="recentpost">', $txt['message'], '</th>
					<th class="recentboard">', $txt['subject'], '</th>
					<th class="recentposter">', $txt['board'], '</th>
					<th class="recenttime">', $txt['date'], '</th>
				</tr>';

		foreach ($context['posts'] as $post)
			echo '
				<tr>
					<td class="recentpost">', $post['body'], '</td>
					<td class="recentsubject">', $post['link'], '</td>
					<td class="recentboard">', $post['board']['link'], '</td>
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
function template_profile_block_rps_topics()
{
	global $txt, $context, $scripturl;

	// The topics block
	echo '
	<h3 class="category_header hdicon cat_img_topics">
		<a href="', $scripturl, '?action=character;sa=showtopics;c=', $context['character']['id'], '">', $txt['profile_topics'], '</a>
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

/**
 * Profile Summary Block
 *
 * Shows a list of characters 
 * Has links to show posts, drafts and attachments
 */
function template_profile_block_rps_characters()
{
	global $txt, $context, $modSettings, $scripturl;

	echo '
			
				<h2 class="category_header hdicon cat_img_profile">
					', $txt['rps_profile_characters'] , '
				</h2>
			
			<div class="profileblock">';
			
	if (!empty($context['member']['characters']))
	{
		$characterList = '';
		foreach($context['member']['characters'] as $charid => $character)
		{
			if(!empty($character['approved']))
			{
				$characterList .= '
				<li>
					<a class="content centertext" href="' . $scripturl . '?action=character;c=' . $charid . '" title="' . $character['name'] . '">' . $character['name'] . '
					' . (!empty($character['title']) ?  '<br />' . $character['title'] : '') . '
					</a>
				</li>';
			}
			elseif($context['user']['is_owner'])
			{
				$characterList .=  '
				<li>
					<a class="content centertext" href="' . $scripturl . '?action=character;c=' . $charid . '" title="' . $character['name'] . '"><i class="icon i-warning"></i>' . $character['name'] . '
					<br />' . $txt['rps_not_approved'] . '
					</a>
				</li>';
			}
		}
		
	}
	
	if(empty($characterList))
	{
		echo '
				', ($context['user']['is_owner']) ? '<p><a href="' . $scripturl . '?action=character;sa=create">' . $txt['rps_profile_none_create'] . '</a></p>' : '<p>' . $txt['rps_profile_none_characters'] . '</p>';
	}
	else
	{
		echo '
				<ul class="characters">',
				$characterList,'
				</ul>';
	}

	// close this block up
	echo '
			</div>';
}

function template_profile_block_rps_biography()
{
	global $context, $user_info, $txt;

	//0 = current approved, 1 = current bio not approved, 2 = no bio
	switch($context['bio_approved'])
	{
		case 0:
			break;
		case 1:
			if($context['user']['is_owner'] || $user_info['is_admin'])
			{
				echo '
				<div class="information">
					<p><i class="icon i-warning"></i>', $txt['rps_bio_unapproved_msg'], '</p>
					<hr />
					', $context['unapproved_biography'], '
				</div>';
			}
			else
			{
				no_bio_yet($context['character']['name']);
			}
			break;
		case 2:
			no_bio_yet($context['character']['name']);
			break;
		default;
	}
	
	if(!empty($context['biography']))
	{
		echo '
			<div>
			',$context['biography']['biography'],'
			</div>';
	}
}

function no_bio_yet($name)
{
	echo '
			<div class="information">
				<p><i class="icon i-warning"></i>', $name, ' does not have a biography yet.</p>
			</div>';
}