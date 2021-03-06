<?php

/**
 * Handles mentioning of buddies
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.1 dev
 *
 */

namespace ElkArte\sources\subs\MentionType;

if (!defined('ELK'))
	die('No access...');

class Buddy_Mention extends Mention_Message_Abstract
{
	/**
	 * {@inheritdoc }
	 */
	protected static $_type = 'buddy';

	/**
	 * {@inheritdoc }
	 */
	public function view($type, &$mentions)
	{
		foreach ($mentions as $key => $row)
		{
			// To ensure it is not done twice
			if ($row['mention_type'] != $type)
				continue;

			$mentions[$key]['message'] = $this->_replaceMsg($row);
		}

		return false;
	}

	/**
	 * {@inheritdoc }
	 */
	public function getNotificationBody($lang_data, $members)
	{
		if (empty($lang_data['subject']))
			return $this->_getNotificationStrings('', array('subject' => static::$_type, 'body' => static::$_type), $members, $this->_task);
		else
			$keys = array('subject' => 'notify_new_buddy_' . $lang_data['subject'], 'body' => 'notify_new_buddy_' . $lang_data['body']);

		$notifier = $this->_task->getNotifierData();
		$replacements = array(
			'ACTIONNAME' => $notifier['real_name'],
		);

		return $this->_getNotificationStrings('notify_new_buddy', $keys, $members, $this->_task, array(), $replacements);
	}
}