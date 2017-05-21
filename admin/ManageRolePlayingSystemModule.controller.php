<?php

/**
 * Allows for the modifying of the forum drafts settings.
 *
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.1 beta 4
 */

/**
 * Drafts administration controller.
 * This class allows to modify admin drafts settings for the forum.
 *
 * @package Drafts
 */
class ManageRolePlayingSystemModule_Controller extends Action_Controller
{
	
	public $rps_date;
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
				$modules = array('post', 'display', 'admin', 'messageindex');

				// Enabling, let's register the modules and prepare the scheduled task
				if ($value)
				{
					enableModules('role_playing_system', $modules);
					Hooks::get()->enableIntegration('Role_Playing_System_Integrate');
				}
				// Disabling, just forget about the modules
				else
				{
					disableModules('role_playing_system', $modules);
					Hooks::get()->disableIntegration('Role_Playing_System_Integrate');
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
		
		$this->rps_date = RpsCurrentDate::get();
		
		// Default text.
		$context['explain_text'] = $txt['calendar_desc'];

		// Little short on the ground of functions here... but things can and maybe will change...
		$subActions = array(
			'editevent' => array(
				'controller' => $this, 
				'function' => 'action_editevent', 
				'permission' => 'admin_forum'
			),
			'events' => array(
				'controller' => $this, 
				'function' => 'action_events', 
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
			'fields' => array(
				'controller' => 'ManageCharacters_Controller',
				'function' => 'action_fields',
				'permission' => 'admin_forum'
			),
			'fieldedit' => array(
				'controller' => 'ManageCharacters_Controller',
				'function' => 'action_field_edit',
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
				'events' => array(
					'description' => $txt['rps_events_desc'],
				),
				'fields' => array(
					'description' => $txt['rps_fields_desc'],
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
		global $context, $txt, $scripturl;

		isAllowedTo('admin_forum');

		// Initialize the form
		$settingsForm = new Settings_Form(Settings_Form::DB_ADAPTER);

		// Initialize it with our settings
		$settingsForm->setConfigVars($this->_settings());

		// Setup the template.
		$context['page_title'] = $txt['rps_manage'] . ': ' . $txt['rps_settings'];
		$context['sub_template'] = 'show_settings';

		// Saving them ?
		if (isset($this->_req->query->save))
		{
			checkSession();
			
			
			
			call_integration_hook('integrate_save_rps_settings');


			$settingsForm->setConfigValues((array) $this->_req->post);
			$settingsForm->save();
			redirectexit('action=admin;area=rps');
		}
		
		addInlineJavascript('
			$(function() {
				$(\'#rps_real_date\').change(function() {
					$(\'#rps_current_end, #rps_current_start\').prop(\'disabled\', this.checked);
				}).change();
			});
			', true);


		// Final settings...
		$context['post_url'] = $scripturl . '?action=admin;area=rps;save';

		// Prepare the settings...
		$settingsForm->prepare();
	}

	/**
	 * Retrieve and return all admin settings for the calendar.
	 */
	private function _settings()
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
				array('text', 'rps_begining'),
				array('check', 'rps_real_date'),
				array('text', 'rps_current_start', 'disabled' => !empty($modSettings['rps_real_date'])),
				array('text', 'rps_current_end', 'disabled' => !empty($modSettings['rps_real_date'])),
				
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
			require_once(SUBSDIR . '/Gamecalendar.subs.php');
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
				'file' => SUBSDIR . '/Gamecalendar.subs.php',
				'function' => 'list_getEvents',
			),
			'get_count' => array(
				'file' => SUBSDIR . '/Gamecalendar.subs.php',
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
		require_once(SUBSDIR . '/Gamecalendar.subs.php');

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
					insertEvent($year, $month, $day, $this->_req->post->title);
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
	
	public function action_download_events()
	{
		global $txt, $context, $modSettings;

		//We need this, really..
		require_once(SUBSDIR . '/Gamecalendar.subs.php');
		
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
		
			var_dump($json_holidays);
			var_dump($json_phases);

		}
			
		$context['download']['Christian'] = array('Ash Wednesday', 'Palm Sunday', 'Good Friday', 'Easter', 'Ascension Day', 'Whit Sunday -- Pentecost', 'Trinity Sunday', 'First Sunday in Advent');
		$context['download']['Jewish'] = array('First Day of Pesach (Passover)', 'Shavuot (Feast of Weeks)', 'Rosh Hashanah (Jewish New Year)', 'Yom Kippur (Day of Atonement)', 'First Day of Succoth (Feast of Tabernacles)', 'First Day of Hanukkah (Festival of Lights)');
		$context['download']['Islamic'] = array('Islamic New Year', 'First Day of Ramadan', 'First Day of Shawwal');
		$context['download']['moon_phases'] = array('New Moon', 'First Quarter', 'Full Moon', 'Last Quarter');
	}
	
}
