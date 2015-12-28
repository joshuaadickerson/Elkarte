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
 * @version 1.1 dev
 *
 */

/**
 * Builds the poster area, avatar, group icons, pulldown information menu, etc
 *
 * @param mixed[] $message
 * @param boolean $ignoring
 *
 * @return string
 */
function template_build_poster_div($message, $ignoring = false)
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	// Attempt to make this template easier to read
	$id = $message['id'];
	$member = $message['member'];

	$poster_div = '';

	// Show information about the poster of this message.
	$poster_div .= '<li class="' . ($ignoring ? 'subsections"' : 'listlevel1 subsections"') . ' aria-haspopup="true">';

	// Show a link to the member's profile.
	if (!empty($id))
	{
		$poster_div .= '
			<a class="linklevel1 name" href="' . $scripturl . '?action=profile;u=' . $member['id'] . '" itemprop="url">
				<span itemprop="name">' . $member['name'] . '</span>
			</a>';
	}
	else
	{
		$poster_div .= '
			<a class="linklevel1 name">
				<span itemprop="name">' . $member['name'] . '</span>
			</a>';
	}

	// The new member info dropdown starts here. Note that conditionals have not been fully checked yet.
	$poster_div .= '<ul id="msg_' . $id . '_extra_info" class="menulevel2' . ($ignoring ? ' hide"' : '" aria-haspopup="true"') . '>';

	// Don't show these things for guests.
	if (!$member['is_guest'])
	{
		// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
		if ((empty($settings['hide_post_group']) || $member['group'] == '') && $member['post_group'] != '')
		{
			$poster_div .= '
				<li class="listlevel2 postgroup">' . $member['post_group'] . '</li>';
		}

		// Show how many posts they have made.
		if (!isset($context['disabled_fields']['posts']))
		{
			$poster_div .= '
				<li class="listlevel2 postcount">' . $txt['member_postcount'] . ': ' . $member['posts'] . '</li>';
		}

		// Is karma display enabled?  Total or +/-?
		if ($modSettings['karmaMode'] == '1')
		{
			$poster_div .= '
				<li class="listlevel2 karma">' . $modSettings['karmaLabel'] . ' ' . $member['karma']['good'] - $member['karma']['bad'] . '</li>';
		}
		elseif ($modSettings['karmaMode'] == '2')
		{
			$poster_div .= '
				<li class="listlevel2 karma">' . $modSettings['karmaLabel'] . ' +' . $member['karma']['good'] . '/-' . $member['karma']['bad'] . '</li>';
		}

		// Is this user allowed to modify this member's karma?
		if ($member['karma']['allow'])
		{
			$poster_div .= '
				<li class="listlevel2 karma_allow">
					<a class="linklevel2" href="' . $member['karma']['applaud_url'] . '">' . $modSettings['karmaApplaudLabel'] . '</a>' .
				(empty($modSettings['karmaDisableSmite']) ? '<a class="linklevel2" href="' . $member['karma']['smite_url'] . '">' . $modSettings['karmaSmiteLabel'] . '</a>' : '') . '
				</li>';
		}

		// Show the member's gender icon?
		if (!empty($settings['show_gender']) && $member['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
		{
			$poster_div .= '
				<li class="listlevel2 gender">' . $txt['gender'] . ': ' . $member['gender']['image'] . '</li>';
		}

		// Show their personal text?
		if (!empty($settings['show_blurb']) && $member['blurb'] != '')
		{
			$poster_div .= '
				<li class="listlevel2 blurb">' . $member['blurb'] . '</li>';
		}

		// Any custom fields to show as icons?
		if (!empty($member['custom_fields']))
		{
			$shown = false;
			foreach ($member['custom_fields'] as $custom)
			{
				if ($custom['placement'] != 1 || empty($custom['value']))
					continue;

				if (empty($shown))
				{
					$shown = true;
					$poster_div .= '
									<li class="listlevel2 cf_icons">
										<ol>';
				}

				$poster_div .= '
											<li>' . $custom['value'] . '</li>';
			}

			if ($shown)
				$poster_div .= '
										</ol>
									</li>';
		}

		// Show the website and email address buttons.
		if ($member['show_profile_buttons'])
		{
			$poster_div .= '
				<li class="listlevel2 profile">
					<ol>';

			// Don't show an icon if they haven't specified a website.
			if ($member['website']['url'] != '' && !isset($context['disabled_fields']['website']))
				$poster_div .= '
					<li><a href="' . $member['website']['url'] . '" title="' . $member['website']['title'] . '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $member['website']['title'] . '" />' : $txt['www']) . '</a></li>';

			// Don't show the email address if they want it hidden.
			if (in_array($member['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')) && $context['can_send_email'])
				$poster_div .= '
					<li><a href="' . $scripturl . '?action=emailuser;sa=email;msg=' . $message['id'] . '" rel="nofollow">' . ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '</a></li>';

			$poster_div .= '
					</ol>
				</li>';
		}

		// Any custom fields for standard placement?
		if (!empty($member['custom_fields']))
		{
			foreach ($member['custom_fields'] as $custom)
			{
				if (empty($custom['placement']) || empty($custom['value']))
				{
					$poster_div .= '
						<li class="listlevel2 custom">' . $custom['title'] . ': ' . $custom['value'] . '</li>';
				}
			}
		}
	}
	// Otherwise, show the guest's email.
	elseif (!empty($member['email']) && in_array($member['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')) && $context['can_send_email'])
	{
		$poster_div .= '
			<li class="listlevel2 email"><a class="linklevel2" href="' . $scripturl . '?action=emailuser;sa=email;msg=' . $id . '" rel="nofollow">' . ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '</a></li>';
	}

	// Stuff for the staff to wallop them with.
	$poster_div .= '
		<li class="listlevel2 report_seperator"></li>';

	// Can we issue a warning because of this post?  Remember, we can't give guests warnings.
	if ($context['can_issue_warning'] && !$message['is_message_author'] && !$member['is_guest'])
	{
		$poster_div .= '
			<li class="listlevel2 warning">
				<a class="linklevel2" href="' . $scripturl . '?action=profile;area=issuewarning;u=' . $member['id'] . ';msg=' . $id . '"><img src="' . $settings['images_url'] . '/profile/warn.png" alt="' . $txt['issue_warning_post'] . '" title="' . $txt['issue_warning_post'] . '" />' . $txt['warning_issue'] . '</a>';

		// Do they have a warning in place?
		if ($member['can_see_warning'] && !empty($options['hide_poster_area']))
		{
			$poster_div .= '
				<a class="linklevel2" href="' . $scripturl . '?action=profile;area=issuewarning;u=' . $member['id'] . '"><img src="' . $settings['images_url'] . '/profile/warning_' . $member['warning_status'] . '.png" alt="' . $txt['user_warn_' . $member['warning_status']] . '" /><span class="warn_' . $member['warning_status'] . '">' . $txt['warn_' . $member['warning_status']] . '</span></a>';
		}

		$poster_div .= '
			</li>';
	}

	// Show the IP to this user for this post - because you can moderate?
	if (!empty($context['can_moderate_forum']) && !empty($member['ip']))
		$poster_div .= '
			<li class="listlevel2 poster_ip">
				<a class="linklevel2 help" href="' . $scripturl . '?action=' . (!empty($member['is_guest']) ? 'trackip' : 'profile;area=history;sa=ip;u=' . $member['id'] . ';searchip=' . $member['ip']) . '"><img src="' . $settings['images_url'] . '/ip.png" alt="" /> ' . $member['ip'] . '</a>
				<a class="linklevel2 help" href="' . $scripturl . '?action=quickhelp;help=see_admin_ip" onclick="return reqOverlayDiv(this.href);"><img src="' . $settings['images_url'] . '/helptopics.png" alt="(?)" /></a>
			</li>';
	// Or, should we show it because this is you?
	elseif ($message['can_see_ip'] && !empty($member['ip']))
		$poster_div .= '
			<li class="listlevel2 poster_ip">
				<a class="linklevel2 help" href="' . $scripturl . '?action=quickhelp;help=see_member_ip" onclick="return reqOverlayDiv(this.href);"><img src="' . $settings['images_url'] . '/ip.png" alt="" /> ' . $member['ip'] . '</a>
			</li>';
	// Okay, are you at least logged in?  Then we can show something about why IPs are logged...
	elseif (!$context['user']['is_guest'])
		$poster_div .= '
			<li class="listlevel2 poster_ip">
				<a class="linklevel2 help" href="' . $scripturl . '?action=quickhelp;help=see_member_ip" onclick="return reqOverlayDiv(this.href);">' . $txt['logged'] . '</a>
			</li>';
	// Otherwise, you see NOTHING!
	else
		$poster_div .= '
									<li class="listlevel2 poster_ip">' . $txt['logged'] . '</li>';

	// Done with the detail information about the poster.
	$poster_div .= '
								</ul>
							</li>';

	// Show avatars, images, etc.?
	if (empty($options['hide_poster_area']) && !$ignoring)
	{
		if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($member['avatar']['image']))
			$poster_div .= '
							<li class="listlevel1 poster_avatar">
								<a class="linklevel1" href="' . $scripturl . '?action=profile;u=' . $member['id'] . '" itemprop="url">
									' . $member['avatar']['image'] . '
								</a>
							</li>';

		// Show the post group icons, but not for guests.
		if (!$member['is_guest'])
			$poster_div .= '
							<li class="listlevel1 icons">' . $member['group_icons'] . '</li>';

		// Show the member's primary group (like 'Administrator') if they have one.
		if (!empty($member['group']))
			$poster_div .= '
							<li class="listlevel1 membergroup">' . $member['group'] . '</li>';

		// Show the member's custom title, if they have one.
		if (!empty($member['title']))
			$poster_div .= '
							<li class="listlevel1 title">' . $member['title'] . '</li>';

		// Show online and offline buttons? PHP could do with a little bit of cleaning up here for brevity, but it works.
		// The plan is to make these buttons act sensibly, and link to your own inbox in your own posts (with new PM notification).
		// Still has a little bit of hard-coded text. This may be a place where translators should be able to write inclusive strings,
		// instead of dealing with $txt['by'] etc in the markup. Must be brief to work, anyway. Cannot ramble on at all.

		// we start with their own..
		if ($context['can_send_pm'] && $message['is_message_author'])
		{
			$poster_div .= '
							<li class="listlevel1 poster_online"><a class="linklevel1' . ($context['user']['unread_messages'] > 0 ? ' new_pm' : '') . '" href="' . $scripturl . '?action=pm">' . $txt['pm_short'] . ' ' . ($context['user']['unread_messages'] > 0 ? '<span class="pm_indicator">' . $context['user']['unread_messages'] . '</span>' : '') . '</a></li>';
		}
		// Allowed to send PMs and the message is not their own and not from a guest.
		elseif ($context['can_send_pm'] && !$message['is_message_author'] && !$member['is_guest'])
		{
			if (!empty($modSettings['onlineEnable']))
				$poster_div .= '
							<li class="listlevel1 poster_online"><a class="linklevel1" href="' . $scripturl . '?action=pm;sa=send;u=' . $member['id'] . '" title="' . $member['online']['member_online_text'] . '">' . $txt['send_message'] . ' <img src="' . $member['online']['image_href'] . '" alt="" /></a></li>';
			else
				$poster_div .= '
							<li class="listlevel1 poster_online"><a class="linklevel1" href="' . $scripturl . '?action=pm;sa=send;u=' . $member['id'] . '">' . $txt['send_message'] . ' </a></li>';
		}
		// Not allowed to send a PM, online status disabled and not from a guest.
		elseif (!$context['can_send_pm'] && !empty($modSettings['onlineEnable']) && !$member['is_guest'])

			// Are we showing the warning status?
			if (!$member['is_guest'] && $member['can_see_warning'])
				$poster_div .= '
							<li class="listlevel1 warning">' . ($context['can_issue_warning'] ? '<a class="linklevel1" href="' . $scripturl . '?action=profile;area=issuewarning;u=' . $member['id'] . '">' : '') . '<img src="' . $settings['images_url'] . '/profile/warning_' . $member['warning_status'] . '.png" alt="' . $txt['user_warn_' . $member['warning_status']] . '" />' . ($context['can_issue_warning'] ? '</a>' : '') . '<span class="warn_' . $member['warning_status'] . '">' . $txt['warn_' . $member['warning_status']] . '</span></li>';
	}

	return $poster_div;
}

/**
 * Formats a very simple message view (for example search results, list of
 * posts and topics in profile, unapproved, etc.)
 *
 * @param mixed[] $msg associative array contaning the data to output:
 * - class => a class name (mandatory)
 * - counter => Usually a number used as counter next to the subject
 * - title => Usually the subject of the topic (mandatory)
 * - date => frequently the "posted on", but can be anything
 * - body => message body (mandatory)
 * - buttons => an associative array that allows create a "quickbutton" strip
 *  (see template_quickbutton_strip for details on the parameters)
 */
function template_simple_message($msg)
{
	// @todo find a better name for $msg['date']
	echo '
			<div class="', $msg['class'], ' forumposts">', !empty($msg['counter']) ? '
				<div class="counter">' . $msg['counter'] . '</div>' : '', '
				<div class="topic_details">
					<h5>
						', $msg['title'], '
					</h5>', !empty($msg['date']) ? '
					<span class="smalltext">' . $msg['date'] . '</span>' : '', '
				</div>
				<div class="inner">
					', $msg['body'], '
				</div>';

	if (!empty($msg['buttons']))
		template_quickbutton_strip($msg['buttons'], !empty($msg['tests']) ? $msg['tests'] : array());

	echo '
			</div>';
}