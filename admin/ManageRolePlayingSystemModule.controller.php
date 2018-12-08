<?php

/**
 * Allows for the changing of Role Playing System Settings.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * Drafts administration controller.
 * This class allows to modify admin drafts settings for the forum.
 *
 * @package Drafts
 */
class ManageRolePlayingSystemModule_Controller extends Action_Controller
{
	/**
	 * Used to add the Drafts entry to the Core Features list.
	 *
	 * @param mixed[] $core_features The core features array
	 */
	public static function addCoreFeature(&$core_features)
	{
		loadLanguage('RolePlayingSystemAdmin');
		$core_features['rps'] = array(
			'url' => 'action=admin;area=managerps',
			'settings' => array(
				'rps_enabled' => 1,
				'displayCharacterFields' => '',
			),
			'setting_callback' => function ($value) {
				$modules = array('post', 'display', 'admin', 'messageindex', 'boardindex');

				// Enabling, let's register the modules and prepare the scheduled task
				if ($value)
				{
					enableModules('role_playing_system', $modules);
					Hooks::instance()->enableIntegration('Role_Playing_System_Integrate');
				}
				// Disabling, just forget about the modules
				else
				{
					disableModules('role_playing_system', $modules);
					Hooks::instance()->disableIntegration('Role_Playing_System_Integrate');
				}
			},
		);
	}
	
	/**
	 * Default method.
	 * Requires admin_forum permissions
	 *
	 * @uses Drafts language file
	 */
	public function action_index()
	{
		global $context, $txt, $settings, $scripturl;
		
		isAllowedTo('admin_forum');
		loadLanguage('RolePlayingSystemAdmin');
		
		loadCSSFile(array('RolePlayingSystem/jquery-ui.css', 'RolePlayingSystem/jquery-ui.theme.css', 'RolePlayingSystem/jquery-ui.structure.css'));

		// Everything's gonna need this.
		loadLanguage('Help');
		loadLanguage('ManageSettings');
		loadLanguage('ManageCalendar');
		
		$this->rps_date = RpsCurrentDate::instance();
		
		// Default text.
		$context['explain_text'] = $txt['calendar_desc'];

		// Little short on the ground of functions here... but things can and maybe will change...
		$subActions = array(
			'tags' => array(
				'controller' => $this, 
				'function' => 'action_manage_tags', 
				'permission' => 'admin_forum'
			),
			'events' => array(
				'controller' => $this, 
				'function' => 'action_events', 
				'permission' => 'admin_forum'
			),
			'phases' => array(
				'controller' => $this, 
				'function' => 'action_phases', 
				'permission' => 'admin_forum'
			),
			'download' => array(
				'controller' => $this, 
				'function' => 'action_download_events', 
				'permission' => 'admin_forum'
			),
			'settings' => array(
				'controller' => $this, 
				'function' => 'action_rpsSettings_display', 
				'permission' => 'admin_forum'
			),
			'editevent' => array(
				'controller' => $this, 
				'function' => 'action_editevent', 
				'permission' => 'admin_forum'
			),
			'editphase' => array(
				'controller' => $this, 
				'function' => 'action_editphase', 
				'permission' => 'admin_forum'
			),
		);

		// Action control
		$action = new Action('manage_RolePlayingSystem');

		// Set up the two tabs here...
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['rps_manage'],
			'tabs' => array(
				'settings' => array(
					'description' => $txt['rps_settings_desc'],
				),
				'tags' => array(
					'description' => $txt['rps_manage_tags_desc'],
				),
				'events' => array(
					'description' => $txt['rps_events_desc'],
				),
				'phases' => array(
					'description' => $txt['rps_phases_desc'],
				),
				'download' => array(
					'description' => $txt['rps_download_desc'],
				),
			),
		);

		// Set up the default subaction, call integrate_sa_manage_calendar
		$subAction = $action->initialize($subActions, 'settings');
		$context['sub_action'] = $subAction;

		// Off we go
		$action->dispatch($subAction);
	}

	/**
	 * Modify any setting related to drafts.
	 *
	 * - Requires the admin_forum permission.
	 * - Accessed from ?action=admin;area=managedrafts
	 *
	 * @uses Admin template, edit_topic_settings sub-template.
	 */
	public function action_rpsSettings_display()
	{
		global $context, $txt, $scripturl, $modSettings;

		isAllowedTo('admin_forum');

		// Initialize the form
		$settingsForm = new Settings_Form(Settings_Form::DB_ADAPTER);

		// Initialize it with our settings
		$settingsForm->setConfigVars($this->_settings( $this->_checkDates() ));

		// Setup the template.
		$context['page_title'] = $txt['rps_manage'] . ': ' . $txt['rps_settings'];
		$context['sub_template'] = 'show_settings';

		// Saving them ?
		if (isset($this->_req->query->save))
		{
			checkSession();

			call_integration_hook('integrate_save_rps_settings');
			
			$config_values = (array) $this->_req->post;
			if($modSettings['rps_current_start'] != $config_values['rps_current_start'] || $modSettings['rps_current_end'] != $config_values['rps_current_end'])
				$config_values['rps_gamecalendar_updated'] = time();

			
			$settingsForm->setConfigValues($config_values);
			$settingsForm->save();
			redirectexit('action=admin;area=rps');
		}

		// Final settings...
		$context['post_url'] = $scripturl . '?action=admin;area=rps;save';

		// Prepare the settings...
		$settingsForm->prepare();
	}

	/**
	 * Retrieve and return all admin settings for the calendar.
	 */
	private function _settings($errors)
	{
		global $txt, $modSettings;

		// Load the boards list.
		require_once(SUBSDIR . '/Boards.subs.php');
		$boards_list = getBoardList(array('override_permissions' => true, 'not_redirection' => true), true);
		$boards = array('');
		foreach ($boards_list as $board)
			$boards[$board['id_board']] = $board['cat_name'] . ' - ' . $board['board_name'];

		// Look, all the calendar settings - of which there are many!
		$config_vars = array(
			array('title', 'rps_general_settings'),

				empty($errors['begin']) ? 
					array('text', 'rps_begining', 'subtext' => $txt['rps_beginning_desc'], 'postinput' => $txt['rps_date_format']) : 
					array('text', 'rps_begining', 'subtext' => $txt['rps_beginning_desc'], 'invalid' =>true, 'postinput' => $errors['begin']),
				empty($errors['range']) ?
					array('var_message', 'rps_current_date_range', 'message' => 'rps_dates_message'): 
					array('var_message', 'rps_current_date_range', 'message' => 'rps_dates_message', 'postinput' => $errors['range']),
				empty($errors['start']) ?
					array('text', 'rps_current_start', 'postinput' => $txt['rps_date_format']) : 
					array('text', 'rps_current_start', 'invalid' =>true, 'postinput' => $errors['start']),
				empty($errors['end']) ?
					array('text', 'rps_current_end', 'postinput' => $txt['rps_date_format']) :
					array('text', 'rps_current_end', 'invalid' =>true, 'postinput' => $errors['end']),
				
			array('title', 'rps_calendar_settings'),
				// How many days to show on board index, and where to display events etc?
				array('select', 'rps_showholidays', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
				array('select', 'rps_showbdays', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
				array('select', 'rps_showtopics', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
		);

		// Add new settings with a nice hook, makes them available for admin settings search as well
		call_integration_hook('integrate_modify_calendar_settings', array(&$config_vars));

		return $config_vars;
	}
	
	private function _checkDates()
	{
		global $txt;
		if(!isset($this->_req->query->save))
			return array();

		$errors = array();
		$begin = DateTime::createFromFormat('Y-m-d|', $this->_req->getPost('rps_begining'));
		$start = DateTime::createFromFormat('Y-m-d|', $this->_req->getPost('rps_current_start'));
		$end = DateTime::createFromFormat('Y-m-d|', $this->_req->getPost('rps_current_end'));
		if(!$begin)
			$errors['begin'] = sprintf($txt['rps_error_incorrect_format'], $this->_req->getPost('rps_begining'));
		if(!$start)
			$errors['start'] = sprintf($txt['rps_error_incorrect_format'], $this->_req->getPost('rps_current_start'));
		if(!$end)
			$errors['end'] = sprintf($txt['rps_error_incorrect_format'], $this->_req->getPost('rps_current_end'));
		if($begin && $start && $begin > $start)
			$errors['start'] = sprintf($txt['rps_error_begining_date_later_start'], $this->_req->getPost('rps_current_start'), $this->_req->getPost('rps_begining'));
		if($end && $start)
		{
			$diff = $start->diff($end);
			if($diff->invert)
				$errors['start'] = sprintf($txt['rps_error_start_date_later_end'], $this->_req->getPost('rps_current_start'), $this->_req->getPost('rps_current_end'));

			if(($diff->m == 4 && $diff->d != 0) || ($diff->m > 4))
				$errors['range'] =sprintf($txt['rps_error_large_range'], $diff->m, $diff->d, $this->_req->getPost('rps_current_start'), $this->_req->getPost('rps_current_end'));
		}
		
		if(!empty($errors))
			unset($this->_req->query->save);
		else{
			$this->_req->post->rps_begining = $begin->format('Y-m-d');
			$this->_req->post->rps_current_start = $start->format('Y-m-d');
			$this->_req->post->rps_current_end = $end->format('Y-m-d');
		}
		
		return $errors;
	}

	/**
	 * Return the form settings for use in admin search
	 */
	public function settings_search()
	{
		return $this->_settings();
	}
	
		/**
	 * The function that handles adding, and deleting holiday data
	 */
	public function action_manage_tags()
	{
		global $context, $scripturl, $txt, $user_info;
		
		if(isset($this->_req->save))
		{
			$timestamp = time();
			$edited_tags = htmltrim__recursive($this->_req->getPost('edits'));
			$original_tags = htmltrim__recursive($this->_req->getPost('tags'));
			$remove_tags = $this->_req->getPost('remove');

			require_once(SUBSDIR . '/Tags.subs.php');
			edit_tags($edited_tags, $original_tags , $user_info['id'], $timestamp);
			remove_tags($remove_tags, '1=1');
			
			
			//redirectexit('topic=' . $topic . ';updatetags');
		}		

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_manage_tags'],
		);

		createToken('admin-rps-tags');
		
				// Create a listing for all our standard fields
		$listOptions = array(
			'id' => 'manage_tags',
			'title' => $txt['rps_manage_tags'],
			'base_href' => $scripturl . '?action=admin;area=rps;sa=tags',
			'items_per_page' => 25,
			'default_sort_col' => 'tag',
			'no_items_label' => $txt['rps_tags_remove_list_none'],
			'items_per_page' => 50,
			'get_items' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getTags',
				'params' => array(
				),
			),
			'get_count' => array(
				'file' => SUBSDIR . '/Tags.subs.php',
				'function' => 'list_getNumTags',
				'params' => array(
				),
			),
			'columns' => array(
				'tag' => array(
					'header' => array(
						'value' => $txt['rps_tags_list_tag'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="text" name="edits[%1$d]" id="edit_%1$d" value="%2$s" class="input_text" />
										<input type="hidden" name="tags[%1$d]" id="tag_%1$d" value="%2$s" />',
							'params' => array(
								'id_tag' => false,
								'tag' => false
							),
						),
						'style' => 'width: 60%;',
					),
					'sort' => array(
						'default' => 'tag',
						'reverse' => 'tag DESC',
					),
				),
				'remove' => array(
					'header' => array(
						'value' => $txt['rps_tags_list_removetag'],
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="remove[]" id="remove_%1$d" value="%1$s" class="input_check" />',
							'params' => array(
								'id_tag' => false
							),
						),
						'class' => 'centertext',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=tags',
				'token' => 'admin-rps-tags',
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '<input type="submit" name="save" value="' . $txt['rps_save_changes'] . '" class="right_submit" />',
				),
			),
		);
		createList($listOptions);
	}

	/**
	 * The function that handles adding, and deleting holiday data
	 */
	public function action_events()
	{
		global $scripturl, $txt, $context;

		// Submitting something...
		if (isset($this->_req->post->delete) && !empty($this->_req->post->event))
		{
			checkSession();
			validateToken('admin-rps-events');

			$to_remove = array_map('intval', array_keys($this->_req->post->event));

			// Now the IDs are "safe" do the delete...
			require_once(SUBSDIR . '/ManageGamecalendar.subs.php');
			removeEvents($to_remove);
		}

		createToken('admin-rps-events');
		$listOptions = array(
			'id' => 'event_list',
			'title' => $txt['rps_events_list'],
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=rps;sa=events',
			'default_sort_col' => 'name',
			'get_items' => array(
				'file' => SUBSDIR . '/ManageGamecalendar.subs.php',
				'function' => 'list_getEvents',
			),
			'get_count' => array(
				'file' => SUBSDIR . '/ManageGamecalendar.subs.php',
				'function' => 'list_getNumEvents',
			),
			'no_items_label' => $txt['rps_events_none'],
			'columns' => array(
				'name' => array(
					'header' => array(
						'value' => $txt['rps_header_event'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $scripturl . '?action=admin;area=rps;sa=editevent;event=%1$d">%2$s</a>',
							'params' => array(
								'id_event' => false,
								'title' => false,
							),
						),
					),
					'sort' => array(
						'default' => 'title',
						'reverse' => 'title DESC',
					)
				),
				'date' => array(
					'header' => array(
						'value' => $txt['date'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $txt;
							
							$recurring = empty($rowData['event_year']) ? true : false;
							$relative = !is_numeric($rowData['event_day']) ? explode(' ', $rowData['event_day']) : $rowData['event_day'];
							$day = is_array($relative) ? $txt[$relative[0]] . ' ' . $txt[$relative[1]] . ' ' . $txt['of'] : $relative;
							// Recurring every year or just a single year?
							$year = $recurring ? sprintf('(%1$s)', $txt['every_year']) : $rowData['event_year'];

							return sprintf('%1$s %2$s %3$s', $day , $txt['months'][(int) $rowData['event_month']], $year);
						},
					),
					'sort' => array(
						'default' => 'event_year, event_month, event_day',
						'reverse' => 'event_year DESC, event_month DESC, event_day DESC',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="event[%1$d]" class="input_check" />',
							'params' => array(
								'id_event' => false,
							),

						),
						'class' => 'centertext'
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=events',
				'token' => 'admin-rps-events',
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'class' => 'submitbutton',
					'value' => '<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" class="right_submit" onclick="return confirm(\'' . $txt['rps_events_delete_confirm'] . '\');" />
					<a class="linkbutton" href="' . $scripturl . '?action=admin;area=rps;sa=editevent">'. $txt['rps_add_event'] .'</a>',
				),
			),
		);

		createList($listOptions);
		
		$context['page_title'] = $txt['rps_manage'] . ': ' . $txt['rps_events'];
	}

	/**
	 * This function is used for adding/editing a specific holiday
	 *
	 * @uses ManageCalendar template, edit_holiday sub template
	 */
	public function action_editevent()
	{
		global $txt, $context, $modSettings;

		//We need this, really..
		require_once(SUBSDIR . '/ManageGamecalendar.subs.php');

		loadTemplate('ManageRolePlayingSystem');

		$context['is_new'] = !isset($this->_req->query->event);
		$context['cal_minyear'] = $this->rps_date->minyear;
		$context['cal_maxyear'] = $this->rps_date->maxyear;
		$context['page_title'] = $context['is_new'] ? $txt['rps_add_event'] : $txt['rps_edit_event'];
		$context['sub_template'] = 'edit_event';

		// Cast this for safety...
		$this->_req->query->event = $this->_req->getQuery('event', 'intval');

		// Submitting?

		if (isset($this->_req->post->{$context['session_var']}) && (isset($this->_req->post->delete) || $this->_req->post->title != ''))
		{
			checkSession();

			// Not too long good sir?
			$this->_req->post->title = Util::substr($this->_req->post->title, 0, 60);
			$this->_req->post->event = $this->_req->getPost('event', 'intval', 0);
			
			if (isset($this->_req->post->delete))
				removeEvents($this->_req->post->event);
			else
			{
				$year = empty($this->_req->post->year) ? 0 : $this->_req->post->year;
				$month = (int) $this->_req->post->month;
				$day = $this->_req->post->date_type == 'exact' ? $this->_req->post->day : $this->_req->post->ordinal . ' ' . $this->_req->post->dayname;

				if (isset($this->_req->post->edit))
					editEvent($this->_req->post->event, $year, $month, $day, $this->_req->post->title);
				else
					insertEvent(array($year, $month, $day, $this->_req->post->title));
			}

			redirectexit('action=admin;area=rps;sa=events');
		}

		// Default states...
		if ($context['is_new'])
		{
			$context['event'] = array(
				'id' => 0,
				'day' => '',
				'month' => $this->rps_date->start_month,
				'year' => $this->rps_date->start_year,
				'title' => '',
				'ordinal' => '',
				'dayname' => '',
			);
		}
		// If it's not new load the data.
		else
			$context['event'] = getEvent($this->_req->query->event);
		
		// Last day for the drop down?
		$context['event']['last_day'] = cal_days_in_month(CAL_GREGORIAN, $context['event']['month'], empty($context['event']['year']) ? 4 : $context['event']['year']);
	}
	
		/**
	 * The function that handles adding, and deleting holiday data
	 */
	public function action_phases()
	{
		global $scripturl, $txt, $context, $modSettings;;

		// Submitting something...
		if (isset($this->_req->post->delete) && !empty($this->_req->post->phase))
		{
			checkSession();
			validateToken('admin-rps-phases');

			$to_remove = array_map('intval', array_keys($this->_req->post->phase));

			// Now the IDs are "safe" do the delete...
			require_once(SUBSDIR . '/ManageGamecalendar.subs.php');
			removePhases($to_remove);
		}
		
		$timezone = new DateTimeZone($modSettings['rps_timezone']);

		createToken('admin-rps-phases');
		$listOptions = array(
			'id' => 'phase_list',
			'title' => $txt['rps_phases_list'],
			'items_per_page' => 50,
			'base_href' => $scripturl . '?action=admin;area=rps;sa=phases',
			'default_sort_col' => 'phase_date',
			'get_items' => array(
				'file' => SUBSDIR . '/ManageGamecalendar.subs.php',
				'function' => 'list_getPhases',
			),
			'get_count' => array(
				'file' => SUBSDIR . '/ManageGamecalendar.subs.php',
				'function' => 'list_getNumPhases',
			),
			'no_items_label' => $txt['rps_moon_phases_list_none'],
			'columns' => array(
				'phase' => array(
					'header' => array(
						'value' => $txt['rps_phases_list_none'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $scripturl . '?action=admin;area=rps;sa=editphase;moonphase=%1$d">%2$s</a>',
							'params' => array(
								'id_phase' => false,
								'phase' => false,
							),
						),
					),
					'sort' => array(
						'default' => 'phase',
						'reverse' => 'phase DESC',
					)
				),
				'phase_date' => array(
					'header' => array(
						'value' => $txt['date'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $timezone, $user_info;
							$date = new DateTime($rowData['phase_date'] . ' ' . $rowData['phase_time'], $timezone);

							return $date->format( $user_info['datetime_format'] . ', G:i e ' );
						},
					),
					'sort' => array(
						'default' => 'phase_date',
						'reverse' => 'phase_date DESC',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="phase[%1$d]" class="input_check" />',
							'params' => array(
								'id_phase' => false,
							),

						),
						'class' => 'centertext'
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=phases',
				'token' => 'admin-rps-phases',
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'class' => 'submitbutton',
					'value' => '<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" class="right_submit" onclick="return confirm(\'' . $txt['rps_events_delete_confirm'] . '\');" />
					<a class="linkbutton" href="' . $scripturl . '?action=admin;area=rps;sa=editphase">'. $txt['rps_add_event'] .'</a>',
				),
			),
		);

		createList($listOptions);
		
		$context['page_title'] = $txt['rps_manage'] . ': ' . $txt['rps_events'];
	}

	/**
	 * This function is used for adding/editing a specific holiday
	 *
	 * @uses ManageCalendar template, edit_holiday sub template
	 */
	public function action_editphase()
	{
		global $txt, $context, $modSettings;

		//We need this, really..
		require_once(SUBSDIR . '/ManageGamecalendar.subs.php');

		loadTemplate('ManageRolePlayingSystem');

		$context['is_new'] = !isset($this->_req->query->moonphase);
		$context['cal_minyear'] = $this->rps_date->minyear;
		$context['cal_maxyear'] = $this->rps_date->maxyear;
		$context['page_title'] = $context['is_new'] ? $txt['rps_phase_title_add'] : $txt['rps_phase_title_edit'];
		$context['sub_template'] = 'edit_phase';

		// Cast this for safety...
		$this->_req->query->moonphase = $this->_req->getQuery('moonphase', 'intval');

		// Submitting?

		if (isset($this->_req->post->{$context['session_var']}) && (isset($this->_req->post->delete) || $this->_req->post->phase != ''))
		{
			checkSession();

			// Not too long good sir?
			$this->_req->post->phase = Util::substr($this->_req->post->phase, 0, 60);
			$this->_req->post->moonphase = $this->_req->getPost('moonphase', 'intval', 0);
			
			if (isset($this->_req->post->delete))
				removePhases($this->_req->post->moonphase);
			else
			{
				$year = empty($this->_req->post->year) ? 0 : $this->_req->post->year;
				$month = (int) $this->_req->post->month;
				$day =  $this->_req->post->day;
				$hour = $this->_req->post->hour;
				$minute = $this->_req->post->minute;

				$datetime = new DateTime($year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':00');
				
				if (isset($this->_req->post->edit))
					editPhase($this->_req->post->moonphase, $datetime->format('Y-m-d'), $datetime->format('H:i:s'), $this->_req->post->phase);
				else
					insertPhase(array($date->format('Y-m-d'), $date->format('H:i:s'), $this->_req->post->phase));
				updateSettings(array('rps_gamecalendar_updated' => time()));
			}

			redirectexit('action=admin;area=rps;sa=phases');
		}

		// Default states...
		if ($context['is_new'])
		{
			$context['moonphase'] = array(
				'id' => 0,
				'day' => '',
				'month' => $this->rps_date->start_month,
				'year' => $this->rps_date->start_year,
				'phase' => '',
				'hour' => '',
				'minute' => '',
			);
		}
		// If it's not new load the data.
		else
			$context['moonphase'] = getPhase($this->_req->query->moonphase);
		
		// Last day for the drop down?
		$context['moonphase']['last_day'] = cal_days_in_month(CAL_GREGORIAN, $context['moonphase']['month'], empty($context['moonphase']['year']) ? 4 : $context['moonphase']['year']);
	}
	
	public function action_download_events()
	{
		global $txt, $context, $modSettings;

		//We need this, really..
		require_once(SUBSDIR . '/ManageGamecalendar.subs.php');
		
		loadTemplate('ManageRolePlayingSystem');

		$context['is_new'] = !isset($this->_req->query->event);
		$context['cal_minyear'] = $this->rps_date->minyear;
		$context['cal_maxyear'] = $this->rps_date->maxyear;
		$context['page_title'] = $txt['rps_download'];
		$context['sub_template'] = 'download_events';
		
		$context['rps_download_events'] = !empty($modSettings['rps_download_events']) ? json_decode($modSettings['rps_download_events']) : array();
		
		// Get all the time zones.
		$context['rps_timezone_selection'] = DateTimeZone::listIdentifiers();

		// Submitting?

		if (isset($this->_req->post->{$context['session_var']}) && isset($this->_req->post->download) && !empty($this->_req->post->year) && (!empty($this->_req->post->Christian) || !empty($this->_req->post->Jewish) || !empty($this->_req->post->Islamic) || !empty($this->_req->post->moon_phases)))
		{
			checkSession();

			$christian =!empty($this->_req->post->Christian) ?  $this->_req->post->Christian : array();
			$jewish =!empty($this->_req->post->Jewish) ? $this->_req->post->Jewish : array();
			$islamic = !empty($this->_req->post->Islamic) ? $this->_req->post->Islamic : array();
			
			$holidays = array_merge($christian, $jewish, $islamic);
			if(!$holidays){
				$holidays = array();
			}
			$moon_phases = !empty($this->_req->post->moon_phases) ? $this->_req->post->moon_phases : array();
			
			$postData = array(
				'year' => $this->_req->getPost('year', 'trim|intval', ''),
				'holidays' => json_encode($holidays),
				'phases' => json_encode($moon_phases),
				'timezone' => $this->_req->post->timezone,
			);

			$c_holidays = curl_init('http://events.cody-williams.info/get_holidays.php');
			$c_phases = curl_init('http://events.cody-williams.info/get_phases.php');

			curl_setopt_array($c_holidays, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postData,
				CURLOPT_FOLLOWLOCATION => true
			));

			curl_setopt_array($c_phases, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postData,
				CURLOPT_FOLLOWLOCATION => true
			));

			// build the multi-curl handle, adding both $ch
			$mh = curl_multi_init();
			curl_multi_add_handle($mh, $c_holidays);
			curl_multi_add_handle($mh, $c_phases);

			// execute all queries simultaneously, and continue when all are complete
			  $running = null;
			  do {
				curl_multi_exec($mh, $running);
			  } while ($running);

			//close the handles
			curl_multi_remove_handle($mh, $c_holidays);
			curl_multi_remove_handle($mh, $c_phases);
			curl_multi_close($mh);
			  
			// all of our requests are done, we can now access the results
			$json_holidays = curl_multi_getcontent($c_holidays);
			$json_phases = curl_multi_getcontent($c_phases);
			
			$event_selections = array_merge($holidays, $moon_phases);
			
			$rps_download_settings = array(
				'rps_download_events' => json_encode($event_selections),
				'rps_timezone' => $this->_req->post->timezone,
			);
			
			insert_downloaded_events(json_decode($json_holidays, true), json_decode($json_phases, true), $postData);

			updateSettings($rps_download_settings);

		}
			
		$context['download']['Christian'] = array('Ash Wednesday', 'Palm Sunday', 'Good Friday', 'Easter', 'Ascension Day', 'Whit Sunday -- Pentecost', 'Trinity Sunday', 'First Sunday in Advent');
		$context['download']['Jewish'] = array('First Day of Pesach (Passover)', 'Shavuot (Feast of Weeks)', 'Rosh Hashanah (Jewish New Year)', 'Yom Kippur (Day of Atonement)', 'First Day of Succoth (Feast of Tabernacles)', 'First Day of Hanukkah (Festival of Lights)');
		$context['download']['Islamic'] = array('Islamic New Year', 'First Day of Ramadan', 'First Day of Shawwal');
		$context['download']['moon_phases'] = array('New Moon', 'First Quarter', 'Full Moon', 'Last Quarter');
	}
	
}
