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
 * RolePlayingSystem administration controller.
 * This class allows to modify admin RPS settings for the forum.
 *
 */
class ManageRolePlayingSystemModule_Controller extends Action_Controller
{
	/**
	 * Used to add the RolePlayingSystem entry to the Core Features list.
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
		
		isAllowedTo(array('admin_rps'));

		loadLanguage('RolePlayingSystemAdmin');
		
		//loadCSSFile(array('RolePlayingSystem/jquery-ui.css', 'RolePlayingSystem/jquery-ui.theme.css', 'RolePlayingSystem/jquery-ui.structure.css'));

		// Everything's gonna need this.
		loadLanguage('Help');
		loadLanguage('ManageSettings');
		loadLanguage('ManageCalendar');
		loadLanguage('Maintenance');
		
		$this->rps_date = RpsCurrentDate::instance();
		
		// Default text.
		$context['explain_text'] = $txt['calendar_desc'];

		// Little short on the ground of functions here... but things can and maybe will change...
		$subActions = array(
			'tags' => array(
				'controller' => $this, 
				'function' => 'action_manage_tags', 
				'permission' => 'admin_rps'
			),
			'events' => array(
				'controller' => $this, 
				'function' => 'action_events', 
				'permission' => 'admin_rps'
			),
			'phases' => array(
				'controller' => $this, 
				'function' => 'action_phases', 
				'permission' => 'admin_rps'
			),
			'download' => array(
				'controller' => $this, 
				'function' => 'action_download_events', 
				'permission' => 'admin_rps'
			),
			'settings' => array(
				'controller' => $this, 
				'function' => 'action_rpsSettings_display', 
				'permission' => 'admin_rps'
			),
			'editevent' => array(
				'controller' => $this, 
				'function' => 'action_editevent', 
				'permission' => 'admin_rps'
			),
			'editphase' => array(
				'controller' => $this, 
				'function' => 'action_editphase', 
				'permission' => 'admin_rps'
			),
			'characters'  => array(
				'controller' => $this, 
				'function' => 'action_manage_characters', 
				'permission' => 'admin_rps'
			),
			'bios' => array(
				'controller' => $this,
				'function' => 'action_manage_bios',
				'permission' => 'admin_rps',
			),
			'recountcharsposts' => array(
				'controller' => $this,
				'function' => 'action_recount_chars_posts',
				'permission' => 'admin_rps',
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
				'characters' => array(
					'description' => $txt['rps_manage_characters_desc'],
				),
				'bios' => array(
					'description' => $txt['rps_manage_bios_desc'],
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
		
		$context['settings_message'] = (isset($this->_req->query->msg) && isset($txt[$this->_req->query->msg])) ? $txt[$this->_req->query->msg] : '';
		
		// Off we go
		$action->dispatch($subAction);
		
		createToken('admin-maint');
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
		loadTemplate('ManageRolePlayingSystem');
		Template_Layers::instance()->add('recount_character_posts');
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

			$overrides['rps_cf_overrides'] = serialize($config_values['rps_cf_overrides']);
			
			updateSettings($overrides);
			unset($config_values['rps_cf_overrides']);
			
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
     * @param $errors
     * @return array
     */
	private function _settings($errors)
	{
		global $txt, $modSettings;

		// Load the boards list.
		require_once(SUBSDIR . '/Boards.subs.php');
		require_once(SUBSDIR . '/ManageFeatures.subs.php');
		$boards_list = getBoardList(array('override_permissions' => true, 'not_redirection' => true), true);
		$boards = array('');
		foreach ($boards_list as $board)
			$boards[$board['id_board']] = $board['cat_name'] . ' - ' . $board['board_name'];
		foreach(DateTimeZone::listIdentifiers() as $zone)
			$timezones[$zone] = $zone;
			
		$custom_fields = list_getProfileFields(0,100,'vieworder',false);
		foreach($custom_fields as $field)
		{
			$field_selects[$field['col_name']] = $field['field_name'];
		}

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
			array('title', 'rps_biography_settings'),
				array('int', 'rps_bio_edit_count', 'subtext' => $txt['rps_bio_edit_count_desc'], 'postinput' => $txt['rps_bio_edit_count_after']),
				array('int', 'rps_bio_edit_chars', 'subtext' => $txt['rps_bio_edit_chars_desc'], 'postinput' => $txt['rps_bio_edit_chars_after']),
			array('title', 'rps_calendar_settings'),
				array('select', 'rps_timezone', $timezones),
				// How many days to show on board index, and where to display events etc?
				array('select', 'rps_showholidays', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
				array('select', 'rps_showbdays', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
				array('select', 'rps_showtopics', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
			array('title', 'rps_ignore_custom_fields'),
			array('desc', 'rps_ignore_custom_fields_desc'),
				array('callback', 'rps_cf_overrides', $field_selects),
			
			
		);

		// Add new settings with a nice hook, makes them available for admin settings search as well
		call_integration_hook('integrate_modify_rps_settings', array(&$config_vars));

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
		
		loadLanguage('RolePlayingSystem');

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
					'value' => '<input type="submit" name="save" value="' . $txt['rps_save_changes'] . '" />',
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
					'value' => '<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" onclick="return confirm(\'' . $txt['rps_events_delete_confirm'] . '\');" />
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
							global $modSettings, $user_info;
							
							$timezone = new DateTimeZone($modSettings['rps_timezone']);
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
					'value' => '<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" onclick="return confirm(\'' . $txt['rps_events_delete_confirm'] . '\');" />
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
	
	public function action_manage_characters()
	{
		global $context, $scripturl, $txt;
		
		if(isset($this->_req->save))
		{
			$approved_characters = $this->_req->getPost('approve_characters');
			$approved_bios = $this->_req->getPost('approve_bios');
			
			require_once(SUBSDIR . '/ManageCharacters.subs.php');
			
			if(!empty($approved_characters))
				approve_characters($approved_characters);

			//redirectexit('topic=' . $topic . ';updatetags');
		}
		
		loadLanguage('RolePlayingSystem');

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_manage_characters'],
		);

		createToken('admin-rps-characters');
		
				// Create a listing for all our standard fields
		$listOptions = array(
			'id' => 'manage_characters',
			'title' => $txt['rps_manage_characters'],
			'base_href' => $scripturl . '?action=admin;area=rps;sa=characters',
			'items_per_page' => 25,
			'default_sort_col' => 'character',
			'no_items_label' => $txt['rps_characters_unapproved_list_none'],
			'items_per_page' => 50,
			'get_items' => array(
				'file' => SUBSDIR . '/ManageCharacters.subs.php',
				'function' => 'list_get_unapproved_characters',
				'params' => array(
				),
			),
			'get_count' => array(
				'file' => SUBSDIR . '/ManageCharacters.subs.php',
				'function' => 'list_num_unapproved_characters',
				'params' => array(
				),
			),
			'columns' => array(
				'character' => array(
					'header' => array(
						'value' => $txt['rps_characters_list_character'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'.$scripturl.'?action=character;c=%1$d">%2$s</a>',
							'params' => array(
								'id_character' => false,
								'name' => false
							),
						),
						'style' => 'width: 60%;',
					),
					'sort' => array(
						'default' => 'name',
						'reverse' => 'name DESC',
					),
				),
				'member' => array(
					'header' => array(
						'value' => $txt['rps_characters_list_member'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'.$scripturl.'?action=profile;u=%1$d">%2$s</a>',
							'params' => array(
								'id_member' => false,
								'real_name' => false
							),
						),
						'style' => 'width: 60%;',
					),
					'sort' => array(
						'default' => 'real_name',
						'reverse' => 'real_name DESC',
					),
				),
				'approve' => array(
					'header' => array(
						'value' => $txt['rps_characters_list_approve'],
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="approve_characters[]" id="approve_%1$d" value="%1$s" class="input_check" />',
							'params' => array(
								'id_character' => false
							),
						),
						'class' => 'centertext',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=characters',
				'token' => 'admin-rps-characters',
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'class' => 'submitbutton',
					'value' => '<input type="submit" name="save" value="' . $txt['rps_approve_characters'] . '" />',
				),
			),
		);
		createList($listOptions);
	}
	
	public function action_manage_bios()
	{
		global $context, $scripturl, $txt;
		
		if(isset($this->_req->save))
		{
			$approved_bios = $this->_req->getPost('approve_bios');
			
			require_once(SUBSDIR . '/ManageCharacters.subs.php');
			
			if(!empty($approved_bios))
				approve_bios($approved_bios);

			//redirectexit('topic=' . $topic . ';updatetags');
		}
		
		loadLanguage('RolePlayingSystem');

		// Set up the stuff and load the user.
		$context += array(
			'page_title' => $txt['rps_manage_bios'],
		);

		createToken('admin-rps-bios');
		
		
		// Create a listing for all our standard fields
		$listOptions = array(
			'id' => 'manage_bios',
			'id' => 'manage_bios',
			'title' => $txt['rps_manage_bios'],
			'base_href' => $scripturl . '?action=admin;area=rps;sa=bios',
			'items_per_page' => 25,
			'default_sort_col' => 'biography',
			'no_items_label' => $txt['rps_bios_unapproved_list_none'],
			'items_per_page' => 50,
			'get_items' => array(
				'file' => SUBSDIR . '/ManageCharacters.subs.php',
				'function' => 'list_get_unapproved_biographies',
				'params' => array(
				),
			),
			'get_count' => array(
				'file' => SUBSDIR . '/ManageCharacters.subs.php',
				'function' => 'list_num_unapproved_biographies',
				'params' => array(
				),
			),
			'columns' => array(
				'biography' => array(
					'header' => array(
						'value' => $txt['rps_bios_list_character'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'.$scripturl.'?action=character;c=%1$d#tab_2">%2$s</a>',
							'params' => array(
								'id_character' => false,
								'name' => false
							),
						),
						'style' => 'width: 20%;',
					),
					'sort' => array(
						'default' => 'name',
						'reverse' => 'name DESC',
					),
				),
				'member' => array(
					'header' => array(
						'value' => $txt['rps_bios_list_member'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="'.$scripturl.'?action=profile;u=%1$d">%2$s</a>',
							'params' => array(
								'id_member' => false,
								'real_name' => false
							),
						),
						'style' => 'width: 20%;',
					),
					'sort' => array(
						'default' => 'real_name',
						'reverse' => 'real_name DESC',
					),
				),
				'date_added' => array(
					'header' => array(
						'value' => $txt['rps_bios_list_date'],
					),
					'data' => array(
						'db' => 'date_added',
						'timeformat' => true,
						'style' => 'width: 20%;',
					),
					'sort' => array(
						'default' => 'date_added',
						'reverse' => 'date_added DESC',
					),
				),
				'approve' => array(
					'header' => array(
						'value' => $txt['rps_bios_list_approve'],
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="approve_bios[]" id="approve_%1$d" value="%1$s" class="input_check" />',
							'params' => array(
								'id_bio' => false
							),
						),
						'class' => 'centertext',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=bios',
				'token' => 'admin-rps-bios',
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'class' => 'submitbutton',
					'value' => '<input type="submit" name="save" value="' . $txt['rps_approve_bios'] . '" />',
				),
			),
		);
		createList($listOptions);
	}

		/**
	 * Recalculate all members post counts
	 *
	 * What it does:
	 *
	 * - It requires the admin_forum permission.
	 * - Recounts all posts for members found in the message table
	 * - Updates the members post count record in the members table
	 * - Honors the boards post count flag
	 * - Does not count posts in the recycle bin
	 * - Zeros post counts for all members with no posts in the message table
	 * - Runs as a delayed loop to avoid server overload
	 * - Uses the not_done template in Admin.template
	 * - Redirects back to action=admin;area=maintain;sa=members when complete.
	 * - Accessed via ?action=admin;area=maintain;sa=members;activity=recountposts
	 */
	public function action_recount_chars_posts()
	{
		global $txt, $context;
		
		// Check the session
		checkSession();

		// Set up to the context for the pause screen
		$context['page_title'] = $txt['not_done_title'];
		$context['continue_countdown'] = 3;
		$context['continue_get_data'] = '';
		$context['sub_template'] = 'not_done';

		// Init, do 200 members in a bunch
		$increment = 200;
		$start = $this->_req->getQuery('start', 'intval', 0);

		// Ask for some extra time, on big boards this may take a bit
		detectServer()->setTimeLimit(600);
		Debug::instance()->off();

		// Only run this query if we don't have the total number of members that have posted
		if (!isset($this->_req->session->total_characters) || $start === 0)
		{
			validateToken('admin-maint');
			$total_characters = $this->countCharacterContributors();
			$_SESSION['total_characters'] = $total_characters;
		}
		else
		{
			validateToken('admin-rps-recountposts');
			$total_characters = $this->_req->session->total_characters;
		}

		// Lets get the next group of members and determine their post count
		// (from the boards that have post count enabled of course).
		$total_rows = $this->updateCharactersPostCount($start, $increment);

		// Continue?
		if ($total_rows == $increment)
		{
			createToken('admin-rps-recountposts');

			$start += $increment;
			$context['continue_get_data'] = '?action=admin;area=rps;sa=recountcharposts;start=' . $start;
			$context['continue_percent'] = round(100 * $start / $total_characters);
			$context['not_done_title'] = $txt['not_done_title'] . ' (' . $context['continue_percent'] . '%)';
			$context['continue_post_data'] = '<input type="hidden" name="' . $context['admin-recountposts_token_var'] . '" value="' . $context['admin-recountposts_token'] . '" />
				<input type="hidden" name="' . $context['session_var'] . '" value="' . $context['session_id'] . '" />';

			Debug::instance()->on();
			return;
		}

		// No countable posts? set posts counter to 0
		$this->updateZeroPostCharacters();

		Debug::instance()->on();
		// All done, clean up and go back to maintenance
		unset($_SESSION['total_characters']);
		redirectexit('action=admin;area=rps;done=recountcharposts;msg=rps_recount_success');
	}
	
	/**
	 * Counts members with posts > 0, we name them contributors
	 *
	 * @package Maintenance
	 * @return int
	 */
	public function countCharacterContributors()
	{
		$db = database();

		$request = $db->query('', '
			SELECT COUNT(DISTINCT m.id_character)
			FROM ({db_prefix}messages AS m, {db_prefix}boards AS b)
			WHERE m.id_character != 0
				AND b.count_posts = 0
				AND m.id_board = b.id_board',
			array(
			)
		);

		// save it so we don't do this again for this task
		list ($total_characters) = $db->fetch_row($request);
		$db->free_result($request);

		return $total_characters;
	}
	
	/**
	 * Recount the members posts.
	 *
	 * @package Maintenance
	 * @param int $start The item to start with (for pagination purposes)
	 * @param int $increment
	 * @return int
	 */
	private function updateCharactersPostCount($start, $increment)
	{
		global $modSettings;

		$db = database();

		$request = $db->query('', '
			SELECT /*!40001 SQL_NO_CACHE */ m.id_character, COUNT(m.id_character) AS posts
			FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}boards AS b ON (m.id_board = b.id_board)
			WHERE m.id_character != {int:zero}
				AND b.count_posts = {int:zero}
				AND b.in_character != {int:zero}' . (!empty($modSettings['recycle_enable']) ? '
				AND b.id_board != {int:recycle}' : '') . '
			GROUP BY m.id_character
			LIMIT {int:start}, {int:number}',
			array(
				'start' => $start,
				'number' => $increment,
				'recycle' => $modSettings['recycle_board'],
				'zero' => 0,
			)
		);
		$total_rows = $db->num_rows($request);

		// Update the post count for this group
		require_once(SUBSDIR . '/Character.subs.php');
		while ($row = $db->fetch_assoc($request))
			updateCharacterData($row['id_character'], array('posts' => $row['posts']));
		$db->free_result($request);

		return $total_rows;
	}
	
	/**
	 * Used to find members who have a post count >0 that should not.
	 *
	 * - Made more difficult since we don't yet support sub-selects on joins so we
	 * place all members who have posts in the message table in a temp table
	 *
	 * @package Maintenance
	 */
	private function updateZeroPostCharacters()
	{
		global $modSettings;

		$db = database();

		$db->skip_next_error();
		$createTemporary = $db->query('', '
			CREATE TEMPORARY TABLE {db_prefix}tmp_maint_recountposts (
				id_character mediumint(8) unsigned NOT NULL default {string:string_zero},
				PRIMARY KEY (id_character)
			)
			SELECT m.id_character
			FROM {db_prefix}messages AS m
				INNER JOIN {db_prefix}boards AS b ON (m.id_board = b.id_board)
			WHERE m.id_character != {int:zero}
				AND b.count_posts = {int:zero}
				AND b.in_character != {int:zero}' . (!empty($modSettings['recycle_enable']) ? '
				AND b.id_board != {int:recycle}' : '') . '
			GROUP BY m.id_character',
			array(
				'zero' => 0,
				'string_zero' => '0',
				'recycle' => $modSettings['recycle_board'],
			)
		) !== false;

		if ($createTemporary)
		{
			// Outer join the members table on the temporary table finding the members that
			// have a post count but no posts in the message table
			$characters = $db->fetchQueryCallback('
				SELECT c.id_character, c.posts
				FROM {db_prefix}rps_characters AS c
					LEFT OUTER JOIN {db_prefix}tmp_maint_recountposts AS res ON (res.id_character = c.id_character)
				WHERE res.id_character IS NULL
					AND c.posts != {int:zero}',
				array(
					'zero' => 0,
				),
				function ($row)
				{
					// Set the post count to zero for any delinquents we may have found
					return $row['id_character'];
				}
			);

			if (!empty($characters))
			{
				require_once(SUBSDIR . '/Character.subs.php');
				updateCharacterData($characters, array('posts' => 0));
			}
		}
	}
}
