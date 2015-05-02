<?php

/**
 * This file is the main Package Action Manager
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:		BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0
 *
 */

/**
 * Coordiantes the processing for all known package actions
 */
class Package_Actions extends Action_Controller
{
	protected $_actions = array();
	protected $_passed_actions;
	protected $_base_path;
	protected $_uninstalling;
	protected $_theme_paths;

	private $_failed_count = 0;
	private $_action;
	private $_actual_filename;

	public $failed = false;
	public $thisAction = array();
	public $chmod_files = array();
	public $ourActions = array();
	public $has_failure = false;
	public $failure_details;
	public $themeFinds;
	public $failed_steps = array();
	public $credits_tag = array();
	public $themes_installed = array();

	/**
	 * Start the Package Actions
	 *
	 * @param mixed[] $actions
	 */
	public function action_index()
	{
	}

	/**
	 * Called from the packages.controller as part of the "test" phase
	 *
	 * @param array $actions set of actions as defined by parsePackageInfo
	 * @param boolean $uninstalling Yea or Nay
	 * @param string $base_path base path for the package within the temp directory
	 * @param array $theme_paths
	 */
	public function test_init($actions, $uninstalling, $base_path, $theme_paths)
	{
		// This will hold data about anything that can be installed in other themes.
		$this->themeFinds = array(
			'candidates' => array(),
			'other_themes' => array(),
		);

		// Pass the vars
		$this->_passed_actions = $actions;
		$this->_uninstalling = $uninstalling;
		$this->_base_path = $base_path;
		$this->_theme_paths = $theme_paths;

		// Run the test install, looking for problems
		$this->action_test();
	}

	/**
	 * Called from the packages.controller as part of the "install" phase
	 *
	 * @param array $actions set of actions as defined by parsePackageInfo
	 * @param boolean $uninstalling Yea or Nay
	 * @param string $base_path base path for the package within the temp directory
	 * @param array $theme_paths
	 * @param array $themes_installed
	 */
	public function install_init($actions, $uninstalling, $base_path, $theme_paths, $themes_installed)
	{
		// Pass the vars
		$this->_passed_actions = $actions;
		$this->_uninstalling = $uninstalling;
		$this->_base_path = $base_path;
		$this->_theme_paths = $theme_paths;
		$this->themes_installed = $themes_installed;

		// Give installation a chance
		$this->action_install();
	}

	/**
	 * "controller" for the test install actions
	 */
	public function action_test()
	{
		// Admins-only!
		isAllowedTo('admin_forum');

		// Generic subs for this controller
		require_once(SUBSDIR . '/Package.subs.php');

		// Oh my
		$subActions = array(
			'chmod' => array($this, 'action_chmod'),
			'license' => array($this, 'action_readme'),
			'readme' => array($this, 'action_readme'),
			'redirect' => array($this, 'action_redirect'),
			'error' => array($this, 'action_error'),
			'modification' => array($this, 'action_modification'),
			'code' => array($this, 'action_code'),
			'database' => array($this, 'action_database'),
			'create-dir' => array($this, 'action_create_dir_file'),
			'create-file' => array($this, 'action_create_dir_file'),
			'hook' => array($this, 'action_hook'),
			'credits' => array($this, 'action_credits'),
			'requires' => array($this, 'action_requires'),
			'require-dir' => array($this, 'action_require_dir_file'),
			'require-file' => array($this, 'action_require_dir_file'),
			'move-dir' => array($this, 'action_move_dir_file'),
			'move-file' => array($this, 'action_move_dir_file'),
			'remove-dir' => array($this, 'action_remove_dir_file'),
			'remove-file' => array($this, 'action_remove_dir_file'),
		);

		// Set up action/subaction stuff.
		$action = new Action();

		foreach ($this->_passed_actions as $this->_action)
		{
			// Yes I know, but thats how this works right now
			$_REQUEST['sa'] = $this->_action['type'];

			// Work out exactly which test function we are calling
			$subAction = $action->initialize($subActions, 'unknown');

			// Lets just do it!
			$action->dispatch($subAction);

			// Loop collector
			$this->_action_post();
		}
	}

	/**
	 * Chmod action requested, make a note
	 */
	public function action_chmod()
	{
		$this->chmod_files[] = $this->_action['filename'];
	}

	/**
	 * The readme that addon authors always spend quality time producing
	 */
	public function action_readme()
	{
		global $context;

		$type = 'package_' . $this->_action['type'];

		if (file_exists(BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename']))
			$context[$type] = htmlspecialchars(trim(file_get_contents(BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename']), "\n\r"), ENT_COMPAT, 'UTF-8');
		elseif (file_exists($this->_action['filename']))
			$context[$type] = htmlspecialchars(trim(file_get_contents($this->_action['filename']), "\n\r"), ENT_COMPAT, 'UTF-8');

		// Fancy or plain
		if (!empty($this->_action['parse_bbc']))
		{
			require_once(SUBSDIR . '/Post.subs.php');
			preparsecode($context[$type]);

			$context[$type] = parse_bbc($context[$type]);
		}
		else
			$context[$type] = nl2br($context[$type]);
	}

	/**
	 * Noted for test, handled in the real install
	 */
	public function action_redirect()
	{
		return;
	}

	/**
	 * Set the error mesasge
	 */
	public function action_error()
	{
		global $txt;

		$this->has_failure = true;

		if (isset($this->_action['error_msg']) && isset($this->_action['error_var']))
			$this->failure_details = sprintf($txt['package_will_fail_' . $this->_action['error_msg']], $this->_action['error_var']);
		elseif (isset($this->_action['error_msg']))
			$this->failure_details = isset($txt['package_will_fail_' . $this->_action['error_msg']]) ? $txt['package_will_fail_' . $this->_action['error_msg']] : $this->_action['error_msg'];
	}

	public function action_modification()
	{
		global $context, $txt;

		// Can't find the file, thats a failure !
		if (!file_exists(BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename']))
		{
			$this->has_failure = true;
			$this->ourActions[] = array(
				'type' => $txt['execute_modification'],
				'action' => Util::htmlspecialchars(strtr($this->_action['filename'], array(BOARDDIR => '.'))),
				'description' => $txt['package_action_error'],
				'failed' => true,
			);
		}
		else
		{
			$mod_actions = parseModification(@file_get_contents(BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename']), true, $this->_action['reverse'], $this->_theme_paths);

			if (count($mod_actions) === 1 && isset($mod_actions[0]) && $mod_actions[0]['type'] === 'error' && $mod_actions[0]['filename'] === '-')
				$mod_actions[0]['filename'] = $this->_action['filename'];

			foreach ($mod_actions as $key => $mod_action)
			{
				// Lets get the last section of the file name.
				if (isset($mod_action['filename']) && substr($mod_action['filename'], -13) != '.template.php')
					$this->_actual_filename = strtolower(substr(strrchr($mod_action['filename'], '/'), 1) . '||' . $this->_action['filename']);
				elseif (isset($mod_action['filename']) && preg_match('~([\w]*)/([\w]*)\.template\.php$~', $mod_action['filename'], $matches))
					$this->_actual_filename = strtolower($matches[1] . '/' . $matches[2] . '.template.php||' . $this->_action['filename']);
				else
					$this->_actual_filename = $key;

				$this->_check_modification($mod_action);
			}

			// We need to loop again just to get the operations down correctly.
			foreach ($mod_actions as $operation_key => $mod_action)
			{
				// Lets get the last section of the file name.
				if (isset($mod_action['filename']) && substr($mod_action['filename'], -13) != '.template.php')
					$this->_actual_filename = strtolower(substr(strrchr($mod_action['filename'], '/'), 1) . '||' . $this->_action['filename']);
				elseif (isset($mod_action['filename']) && preg_match('~([\w]*)/([\w]*)\.template\.php$~', $mod_action['filename'], $matches))
					$this->_actual_filename = strtolower($matches[1] . '/' . $matches[2] . '.template.php||' . $this->_action['filename']);
				else
					$this->_actual_filename = $operation_key;

				// We just need it for actual parse changes.
				if (!in_array($mod_action['type'], array('error', 'result', 'opened', 'saved', 'end', 'missing', 'skipping', 'chmod')))
				{
					if (empty($mod_action['is_custom']))
						$this->ourActions[$this->_actual_filename]['operations'][] = array(
							'type' => $txt['execute_modification'],
							'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
							'description' => $mod_action['failed'] ? $txt['package_action_failure'] : $txt['package_action_success'],
							'position' => $mod_action['position'],
							'operation_key' => $operation_key,
							'filename' => $this->_action['filename'],
							'failed' => $mod_action['failed'],
							'ignore_failure' => !empty($mod_action['ignore_failure']),
						);

					// Themes are under the saved type.
					if (isset($mod_action['is_custom']) && isset($context['theme_actions'][$mod_action['is_custom']]))
					{
						$context['theme_actions'][$mod_action['is_custom']]['actions'][$this->_actual_filename]['operations'][] = array(
							'type' => $txt['execute_modification'],
							'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
							'description' => $mod_action['failed'] ? $txt['package_action_failure'] : $txt['package_action_success'],
							'position' => $mod_action['position'],
							'operation_key' => $operation_key,
							'filename' => $this->_action['filename'],
							'failed' => $mod_action['failed'],
							'ignore_failure' => !empty($mod_action['ignore_failure']),
						);
					}
				}
			}
		}
	}

	private function _check_modification($mod_action)
	{
		global $context, $txt;

		switch ($mod_action['type'])
		{
			case 'opened':
				$this->failed = false;
				break;
			case 'failure':
				if (empty($mod_action['is_custom']))
					$this->has_failure = true;

				$this->failed = true;
				break;
			case 'chmod':
				$this->chmod_files[] = $mod_action['filename'];
				break;
			case 'saved':
				if (!empty($mod_action['is_custom']))
				{
					if (!isset($context['theme_actions'][$mod_action['is_custom']]))
						$context['theme_actions'][$mod_action['is_custom']] = array(
							'name' => $this->_theme_paths[$mod_action['is_custom']]['name'],
							'actions' => array(),
							'has_failure' => $this->failed,
						);
					else
						$context['theme_actions'][$mod_action['is_custom']]['has_failure'] |= $this->failed;

					$context['theme_actions'][$mod_action['is_custom']]['actions'][$this->_actual_filename] = array(
						'type' => $txt['execute_modification'],
						'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
						'description' => $this->failed ? $txt['package_action_failure'] : $txt['package_action_success'],
						'failed' => $this->failed,
					);
				}
				elseif (!isset($this->ourActions[$this->_actual_filename]))
				{
					$this->ourActions[$this->_actual_filename] = array(
						'type' => $txt['execute_modification'],
						'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
						'description' => $this->failed ? $txt['package_action_failure'] : $txt['package_action_success'],
						'failed' => $this->failed,
					);
				}
				else
				{
					$this->ourActions[$this->_actual_filename]['failed'] |= $this->failed;
					$this->ourActions[$this->_actual_filename]['description'] = $this->ourActions[$this->_actual_filename]['failed'] ? $txt['package_action_failure'] : $txt['package_action_success'];
				}
				break;
			case 'skipping':
				$this->ourActions[$this->_actual_filename] = array(
					'type' => $txt['execute_modification'],
					'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
					'description' => $txt['package_action_skipping']
				);
				break;
			case 'missing':
				if (empty($mod_action['is_custom']))
				{
					$this->has_failure = true;
					$this->ourActions[$this->_actual_filename] = array(
						'type' => $txt['execute_modification'],
						'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
						'description' => $txt['package_action_missing'],
						'failed' => true,
					);
				}
				break;
			case 'error':
				$this->ourActions[$this->_actual_filename] = array(
					'type' => $txt['execute_modification'],
					'action' => Util::htmlspecialchars(strtr($mod_action['filename'], array(BOARDDIR => '.'))),
					'description' => $txt['package_action_error'],
					'failed' => true,
				);
				break;
		}
	}

	public function action_code()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['execute_code'],
			'action' => Util::htmlspecialchars($this->_action['filename']),
		);
	}

	public function action_database()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['execute_database_changes'],
			'action' => Util::htmlspecialchars($this->_action['filename']),
		);
	}

	public function action_create_dir_file()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['package_create'] . ' ' . ($this->_action['type'] == 'create-dir' ? $txt['package_tree'] : $txt['package_file']),
			'action' => Util::htmlspecialchars(strtr($this->_action['destination'], array(BOARDDIR => '.')))
		);
	}

	public function action_hook()
	{
		global $txt;

		$this->_action['description'] = !isset($this->_action['hook'], $this->_action['function']) ? $txt['package_action_failure'] : $txt['package_action_success'];

		if (!isset($this->_action['hook'], $this->_action['function']))
			$this->has_failure = true;

		$this->thisAction = array(
			'type' => $this->_action['reverse'] ? $txt['execute_hook_remove'] : $txt['execute_hook_add'],
			'action' => sprintf($txt['execute_hook_action'], Util::htmlspecialchars($this->_action['hook'])),
		);
	}

	public function action_credits()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['execute_credits_add'],
			'action' => sprintf($txt['execute_credits_action'], Util::htmlspecialchars($this->_action['title'])),
		);
	}

	public function action_requries()
	{
		global $txt;

		$installed_version = false;
		$version_check = true;

		// Package missing required values?
		if (!isset($this->_action['id']))
			$this->has_failure = true;
		else
		{
			// See if this dependency is installed
			$installed_version = checkPackageDependency($this->_action['id']);

			// Do a version level check (if requested) in the most basic way
			$version_check = (isset($this->_action['version']) ? $installed_version == $this->_action['version'] : true);
		}

		// Set success or failure information
		$this->_action['description'] = ($installed_version && $version_check) ? $txt['package_action_success'] : $txt['package_action_failure'];
		$this->has_failure = !($installed_version && $version_check);
		$this->thisAction = array(
			'type' => $txt['package_requires'],
			'action' => $txt['package_check_for'] . ' ' . $this->_action['id'] . (isset($this->_action['version']) ? (' / ' . ($version_check ? $this->_action['version'] : '<span class="error">' . $this->_action['version'] . '</span>')) : ''),
		);
	}

	public function action_require_dir_file()
	{
		global $txt;

		// Do this one...
		$this->thisAction = array(
			'type' => $txt['package_extract'] . ' ' . ($this->_action['type'] == 'require-dir' ? $txt['package_tree'] : $txt['package_file']),
			'action' => Util::htmlspecialchars(strtr($this->_action['destination'], array(BOARDDIR => '.')))
		);

		// Could this be theme related?
		if (!empty($this->_action['unparsed_destination']) && preg_match('~^\$(languagedir|languages_dir|imagesdir|themedir|themes_dir)~i', $this->_action['unparsed_destination'], $matches))
		{
			// Is the action already stated?
			$theme_action = !empty($this->_action['theme_action']) && in_array($this->_action['theme_action'], array('no', 'yes', 'auto')) ? $this->_action['theme_action'] : 'auto';

			// If it's not auto do we think we have something we can act upon?
			if ($theme_action != 'auto' && !in_array($matches[1], array('languagedir', 'languages_dir', 'imagesdir', 'themedir')))
				$theme_action = '';
			// ... or if it's auto do we even want to do anything?
			elseif ($theme_action == 'auto' && $matches[1] != 'imagesdir')
				$theme_action = '';

			// So, we still want to do something?
			if ($theme_action != '')
				$this->themeFinds['candidates'][] = $this->_action;
			// Otherwise is this is going into another theme record it.
			elseif ($matches[1] == 'themes_dir')
				$this->themeFinds['other_themes'][] = strtolower(strtr(parse_path($this->_action['unparsed_destination']), array('\\' => '/')) . '/' . basename($this->_action['filename']));
		}
	}

	public function action_move_dir_file()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['package_move'] . ' ' . ($this->_action['type'] == 'move-dir' ? $txt['package_tree'] : $txt['package_file']),
			'action' => Util::htmlspecialchars(strtr($this->_action['source'], array(BOARDDIR => '.'))) . ' => ' . Util::htmlspecialchars(strtr($this->_action['destination'], array(BOARDDIR => '.')))
		);
	}

	public function action_remove_dir_file()
	{
		global $txt;

		$this->thisAction = array(
			'type' => $txt['package_delete'] . ' ' . ($this->_action['type'] == 'remove-dir' ? $txt['package_tree'] : $txt['package_file']),
			'action' => Util::htmlspecialchars(strtr($this->_action['filename'], array(BOARDDIR => '.')))
		);

		// Could this be theme related?
		if (!empty($this->_action['unparsed_filename']) && preg_match('~^\$(languagedir|languages_dir|imagesdir|themedir|themes_dir)~i', $this->_action['unparsed_filename'], $matches))
		{
			// Is the action already stated?
			$theme_action = !empty($this->_action['theme_action']) && in_array($this->_action['theme_action'], array('no', 'yes', 'auto')) ? $this->_action['theme_action'] : 'auto';
			$this->_action['unparsed_destination'] = $this->_action['unparsed_filename'];

			// If it's not auto do we think we have something we can act upon?
			if ($theme_action != 'auto' && !in_array($matches[1], array('languagedir', 'languages_dir', 'imagesdir', 'themedir')))
				$theme_action = '';
			// ... or if it's auto do we even want to do anything?
			elseif ($theme_action == 'auto' && $matches[1] != 'imagesdir')
				$theme_action = '';

			// So, we still want to do something?
			if ($theme_action != '')
				$this->themeFinds['candidates'][] = $this->_action;
			// Otherwise is this is going into another theme record it.
			elseif ($matches[1] == 'themes_dir')
				$this->themeFinds['other_themes'][] = strtolower(strtr(parse_path($this->_action['unparsed_filename']), array('\\' => '/')) . '/' . basename($this->_action['filename']));
		}
	}

	private function _action_post()
	{
		global $txt;

		// Now prepare things for the template.
		if (empty($this->thisAction))
			return;

		if (isset($this->_action['filename']))
		{
			if ($this->_uninstalling)
				$file = in_array($this->_action['type'], array('remove-dir', 'remove-file')) ? $this->_action['filename'] : BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename'];
			else
				$file = BOARDDIR . '/packages/temp/' . $this->_base_path . $this->_action['filename'];

			if (!file_exists($file))
			{
				$this->has_failure = true;

				$this->thisAction += array(
					'description' => $txt['package_action_error'],
					'failed' => true,
				);
			}
		}

		// @todo None given?
		if (empty($this->thisAction['description']))
			$this->thisAction['description'] = isset($this->_action['description']) ? $this->_action['description'] : '';

		$this->ourActions[] = $this->thisAction;
	}

	public function action_install()
	{
		// Admins-only!
		isAllowedTo('admin_forum');

		// Generic subs for this controller
		require_once(SUBSDIR . '/Package.subs.php');

		// Here is what we know to do!
		$subActions = array(
			'redirect' => array($this, 'action_redirect2'),
			'modification' => array($this, 'action_modification2'),
			'code' => array($this, 'action_code2'),
			'database' => array($this, 'action_database2'),
			'hook' => array($this, 'action_hook2'),
			'credits' => array($this, 'action_credits2'),
		);

		$this->_failed_count = 0;
		$this->failed_steps = array();

		// Set up action/subaction stuff.
		$action = new Action();

		foreach ($this->_passed_actions as $this->_action)
		{
			$this->_failed_count++;

			// Yes I know, but thats how this works right now
			$_REQUEST['sa'] = $this->_action['type'] . '2';

			// Work out exactly who it is we are calling. call integrate_sa_packages
			$subAction = $action->initialize($subActions, 'unknown');

			// Lets just do it!
			$action->dispatch($subAction);
		}
	}

	public function action_modification2()
	{
		global $context;

		if (!empty($this->_action['filename']))
		{
			$mod_actions = parseModification(file_get_contents(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename']), false, $this->_action['reverse'], $this->_theme_paths);

			// Any errors worth noting?
			foreach ($mod_actions as $key => $action)
			{
				if ($this->_action['type'] === 'failure')
					$this->failed_steps[] = array(
						'file' => $action['filename'],
						'large_step' => $this->_failed_count,
						'sub_step' => $key,
						'theme' => 1,
					);

				// Gather the themes we installed into.
				if (!empty($this->_action['is_custom']))
					$this->themes_installed[] = $this->_action['is_custom'];
			}
		}
	}

	public function action_code2()
	{
		if ($this->_action['type'] == 'code' && !empty($this->_action['filename']))
		{
			// This is just here as reference for what is available.
			global $txt, $modSettings, $context;

			// Now include the file and be done with it ;).
			if (file_exists(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename']))
				require(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename']);
		}
	}

	public function action_credits2()
	{
		if ($this->_action['type'] == 'credits')
		{
			// Time to build the billboard
			$this->credits_tag = array(
				'url' => $this->_action['url'],
				'license' => $this->_action['license'],
				'copyright' => $this->_action['copyright'],
				'title' => $this->_action['title'],
			);
		}
	}

	public function action_hook2()
	{
		if (isset($this->_action['hook'], $this->_action['function']))
		{
			if ($this->_action['reverse'])
				remove_integration_function($this->_action['hook'], $this->_action['function'], $this->_action['include_file']);
			else
				add_integration_function($this->_action['hook'], $this->_action['function'], $this->_action['include_file']);
		}
	}

	public function action_database2()
	{
		// Only do the database changes on uninstall if requested.
		if (!empty($this->_action['filename']) && (!$this->_uninstalling || !empty(HttpReq::instance()->post->do_db_changes)))
		{
			// These can also be there for database changes.
			global $txt, $modSettings, $context;

			// Let the file work its magic ;)
			if (file_exists(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename']))
				require(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename']);
		}
	}

	public function action_redirect2()
	{
		global $boardurl, $scripturl, $context, $txt;

		// Handle a redirect...
		if ($this->_action['type'] === 'redirect' && !empty($this->_action['redirect_url']))
		{
			$context['redirect_url'] = $this->_action['redirect_url'];
			$context['redirect_text'] = !empty($this->_action['filename']) && file_exists(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename'])
				? file_get_contents(BOARDDIR . '/packages/temp/' . $context['base_path'] . $this->_action['filename'])
				: ($this->_uninstalling ? $txt['package_uninstall_done'] : $txt['package_installed_done']);
			$context['redirect_timeout'] = $this->_action['redirect_timeout'];

			// Parse out a couple of common urls.
			$urls = array(
				'$boardurl' => $boardurl,
				'$scripturl' => $scripturl,
				'$session_var' => $context['session_var'],
				'$session_id' => $context['session_id'],
			);

			$context['redirect_url'] = strtr($context['redirect_url'], $urls);
		}
	}
}