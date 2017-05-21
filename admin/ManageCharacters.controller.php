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
class ManageCharacters_Controller extends Action_Controller
{
	/**
	 * Pre Dispatch, called before other methods.
	 */
	public function pre_dispatch()
	{
		// We need this in few places so it's easier to have it loaded here
		
	}

	/**
	 * This function passes control through to the relevant tab.
	 *
	 * @see Action_Controller::action_index()
	 * @uses Help, ManageSettings languages
	 * @uses sub_template show_settings
	 */
	public function action_index()
	{
	}
	
	/**
	 * Show all the custom profile fields available to the user.
	 *
	 * - Allows for drag/drop sorting of custom profile fields
	 * - Accessed with ?action=admin;area=featuresettings;sa=profile
	 *
	 * @uses sub template show_custom_profile
	 */
	public function action_fields()
	{
		global $txt, $scripturl, $context;
		require_once(SUBSDIR . '/ManageCharacters.subs.php');
		
		loadTemplate('ManageRolePlayingSystem');
		
		$context['page_title'] =  $txt['rps_manage'] . ': ' . $txt['rps_fields'];
		$context['sub_template'] = 'show_character_fields';

		// And now we do the same for all of our custom ones
		$token = createToken('admin-rps-cp');
		$token = createToken('admin-rps-sort');
		$listOptions = array(
			'id' => 'character_fields',
			'title' =>  $txt['rps_fields_list'],
			'base_href' => $scripturl . '?action=admin;area=rps;sa=fields',
			'default_sort_col' => 'vieworder',
			'no_items_label' => $txt['rps_fields_none'],
			'items_per_page' => 25,
			'sortable' => true,
			'get_items' => array(
				'function' => 'list_getCharacterFields',
				'params' => array(
					false,
				),
			),
			'get_count' => array(
				'function' => 'list_getCharacterFieldsSize',
			),
			'columns' => array(
				'vieworder' => array(
					'header' => array(
						'value' => '',
						'class' => 'hide',
					),
					'data' => array(
						'db' => 'vieworder',
						'class' => 'hide',
					),
					'sort' => array(
						'default' => 'vieworder',
					),
				),
				'field_name' => array(
					'header' => array(
						'value' => $txt['custom_profile_fieldname'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $scripturl;

							return sprintf('<a href="%1$s?action=admin;area=rps;sa=fieldedit;fid=%2$d">%3$s</a><div class="smalltext">%4$s</div>', $scripturl, $rowData['id_field'], $rowData['field_name'], $rowData['field_desc']);
						},
						'style' => 'width: 65%;',
					),
					'sort' => array(
						'default' => 'field_name',
						'reverse' => 'field_name DESC',
					),
				),
				'field_type' => array(
					'header' => array(
						'value' => $txt['custom_profile_fieldtype'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $txt;

							$textKey = sprintf('custom_profile_type_%1$s', $rowData['field_type']);
							return isset($txt[$textKey]) ? $txt[$textKey] : $textKey;
						},
						'style' => 'width: 10%;',
					),
					'sort' => array(
						'default' => 'field_type',
						'reverse' => 'field_type DESC',
					),
				),
				'cust' => array(
					'header' => array(
						'value' => $txt['custom_profile_active'],
						'class' => 'centertext',
					),
					'data' => array(
						'function' => function ($rowData) {
							$isChecked = $rowData['active'] ? ' checked="checked"' : '';
							return sprintf('<input type="checkbox" name="cust[]" id="cust_%1$s" value="%1$s" class="input_check"%2$s />', $rowData['id_field'], $isChecked);
						},
						'style' => 'width: 8%;',
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'active DESC',
						'reverse' => 'active',
					),
				),
				'placement' => array(
					'header' => array(
						'value' => $txt['custom_profile_placement'],
					),
					'data' => array(
						'function' => function ($rowData) {
							global $txt;
							$placement = 'custom_profile_placement_';

							switch ((int) $rowData['placement'])
							{
								case 0:
									$placement .= 'standard';
									break;
								case 1:
									$placement .= 'withicons';
									break;
								case 2:
									$placement .= 'abovesignature';
									break;
								case 3:
									$placement .= 'aboveicons';
									break;
							}

							return $txt[$placement];
						},
						'style' => 'width: 5%;',
					),
					'sort' => array(
						'default' => 'placement DESC',
						'reverse' => 'placement',
					),
				),
				'show_on_registration' => array(
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $scripturl . '?action=admin;area=rps;sa=fieldedit;fid=%1$s">' . $txt['modify'] . '</a>',
							'params' => array(
								'id_field' => false,
							),
						),
						'style' => 'width: 5%;',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=rps;sa=fieldedit',
				'name' => 'characterProfileFields',
				'token' => 'admin-rps-cp',
			),
			'additional_rows' => array(
				array(
					'class' => 'submitbutton',
					'position' => 'below_table_data',
					'value' => '<input type="submit" name="onoff" value="' . $txt['save'] . '" class="right_submit" />
					<input type="submit" name="new" value="' . $txt['custom_profile_make_new'] . '" class="right_submit" />',
				),
				array(
					'position' => 'top_of_list',
					'value' => '<p class="infobox">' . $txt['custom_profile_sort'] . '</p>',
				),
			),
			'javascript' => '
				$().elkSortable({
					sa: "characterorder",
					error: "' . $txt['admin_order_error'] . '",
					title: "' . $txt['admin_order_title'] . '",
					placeholder: "ui-state-highlight",
					href: "?action=admin;area=rps;sa=fields",
					token: {token_var: "' . $token['admin-rps-sort_token_var'] . '", token_id: "' . $token['admin-rps-sort_token'] . '"}
				});
			',
		);

		createList($listOptions);
	}

	/**
	 * Edit some profile fields?
	 *
	 * - Accessed with ?action=admin;area=featuresettings;sa=profileedit
	 *
	 * @uses sub template edit_profile_field
	 */
	public function action_field_edit()
	{
		global $txt, $scripturl, $context;
		require_once(SUBSDIR . '/ManageCharacters.subs.php');
		
		loadTemplate('ManageRolePlayingSystem');

		// Sort out the context!
		$context['fid'] = $this->_req->getQuery('fid', 'intval', 0);
		$context[$context['admin_menu_name']]['current_subsection'] = 'fields';
		$context['page_title'] = $context['fid'] ? $txt['custom_edit_title'] : $txt['custom_add_title'];
		$context['sub_template'] = 'edit_character_field';

		// Any errors messages to show?
		if (isset($this->_req->query->msg))
		{
			loadLanguage('Errors');

			if (isset($txt['custom_option_' . $this->_req->query->msg]))
				$context['custom_option__error'] = $txt['custom_option_' . $this->_req->query->msg];
		}

		// Load the profile language for section names.
		loadLanguage('Profile');

		// Load up the profile field, if one was supplied
		if ($context['fid'])
			$context['field'] = getCharacterField($context['fid']);

		// Setup the default values as needed.
		if (empty($context['field']))
			$context['field'] = array(
				'name' => '',
				'colname' => '???',
				'desc' => '',
				'profile_area' => 'forumprofile',
				'reg' => false,
				'display' => false,
				'memberlist' => false,
				'type' => 'text',
				'max_length' => 255,
				'rows' => 4,
				'cols' => 30,
				'bbc' => false,
				'default_check' => false,
				'default_select' => '',
				'default_value' => '',
				'options' => array('', '', ''),
				'active' => true,
				'private' => false,
				'can_search' => false,
				'mask' => 'nohtml',
				'regex' => '',
				'enclose' => '',
				'placement' => 0,
			);

		// All the javascript for this page... everything else is in admin.js
		addJavascriptVar(array('startOptID' => count($context['field']['options'])));
		addInlineJavascript('updateInputBoxes();', true);

		// Are we toggling which ones are active?
		if (isset($this->_req->post->onoff))
		{
			checkSession();
			validateToken('admin-rps-cp');

			// Enable and disable custom fields as required.
			$enabled = array(0);
			foreach ($this->_req->post->cust as $id)
				$enabled[] = (int) $id;

			updateRenamedCharacterStatus($enabled);
		}
		// Are we saving?
		elseif (isset($this->_req->post->save))
		{
			checkSession();
			validateToken('admin-rps-ecp');

			// Everyone needs a name - even the (bracket) unknown...
			if (trim($this->_req->post->field_name) == '')
				redirectexit($scripturl . '?action=admin;area=rps;sa=fieldedit;fid=' . $this->_req->query->fid . ';msg=need_name');

			// Regex you say?  Do a very basic test to see if the pattern is valid
			if (!empty($this->_req->post->regex) && @preg_match($this->_req->post->regex, 'dummy') === false)
				redirectexit($scripturl . '?action=admin;area=rps;sa=fieldedit;fid=' . $this->_req->query->fid . ';msg=regex_error');

			$this->_req->post->field_name = $this->_req->getPost('field_name', 'Util::htmlspecialchars');
			$this->_req->post->field_desc = $this->_req->getPost('field_desc', 'Util::htmlspecialchars');

			$rows = isset($this->_req->post->rows) ? (int) $this->_req->post->rows : 4;
			$cols = isset($this->_req->post->cols) ? (int) $this->_req->post->cols : 30;

			// Checkboxes...
			$show_reg = $this->_req->getPost('reg', 'intval', 0);
			$show_display = isset($this->_req->post->display) ? 1 : 0;
			$show_memberlist = isset($this->_req->post->memberlist) ? 1 : 0;
			$bbc = isset($this->_req->post->bbc) ? 1 : 0;
			$show_profile = 'forumprofile';
			$active = isset($this->_req->post->active) ? 1 : 0;
			$private = $this->_req->getPost('private', 'intval', 0);
			$can_search = isset($this->_req->post->can_search) ? 1 : 0;

			// Some masking stuff...
			$mask = $this->_req->getPost('mask', 'strval', '');
			if ($mask == 'regex' && isset($this->_req->post->regex))
				$mask .= $this->_req->post->regex;

			$field_length = $this->_req->getPost('max_length', 'intval', 255);
			$enclose = $this->_req->getPost('enclose', 'strval', '');
			$placement = $this->_req->getPost('placement', 'intval', 0);

			// Select options?
			$field_options = '';
			$newOptions = array();

			// Set default
			$default = '';

			switch ($this->_req->post->field_type)
			{
				case 'check':
					$default = isset($this->_req->post->default_check) ? 1 : '';
					break;
				case 'select':
				case 'radio':
					if (!empty($this->_req->post->select_option))
					{
						foreach ($this->_req->post->select_option as $k => $v)
						{
							// Clean, clean, clean...
							$v = Util::htmlspecialchars($v);
							$v = strtr($v, array(',' => ''));

							// Nada, zip, etc...
							if (trim($v) == '')
								continue;

							// Otherwise, save it boy.
							$field_options .= $v . ',';

							// This is just for working out what happened with old options...
							$newOptions[$k] = $v;

							// Is it default?
							if (isset($this->_req->post->default_select) && $this->_req->post->default_select == $k)
								$default = $v;
						}

						if (isset($_POST['default_select']) && $_POST['default_select'] == 'no_default')
							$default = 'no_default';

						$field_options = substr($field_options, 0, -1);
					}
					break;
				default:
					$default = isset($this->_req->post->default_value) ? $this->_req->post->default_value : '';
			}

			// Text area by default has dimensions
//			if ($this->_req->post->field_type == 'textarea')
//				$default = (int) $this->_req->post->rows . ',' . (int) $this->_req->post->cols;

			// Come up with the unique name?
			if (empty($context['fid']))
			{
				$colname = Util::substr(strtr($this->_req->post->field_name, array(' ' => '')), 0, 6);
				preg_match('~([\w\d_-]+)~', $colname, $matches);

				// If there is nothing to the name, then let's start our own - for foreign languages etc.
				if (isset($matches[1]))
					$colname = $initial_colname = 'cust_' . strtolower($matches[1]);
				else
					$colname = $initial_colname = 'cust_' . mt_rand(1, 999999);

				$unique = ensureUniqueCharacterField($colname, $initial_colname);

				// Still not a unique column name? Leave it up to the user, then.
				if (!$unique)
					throw new Elk_Exception('custom_option_not_unique');

				// And create a new field
				$new_field = array(
					'col_name' => $colname,
					'field_name' => $this->_req->post->field_name,
					'field_desc' => $this->_req->post->field_desc,
					'field_type' => $this->_req->post->field_type,
					'field_length' => $field_length,
					'field_options' => $field_options,
					'show_reg' => $show_reg,
					'show_display' => $show_display,
					'show_memberlist' => $show_memberlist,
					'show_profile' => $show_profile,
					'private' => $private,
					'active' => $active,
					'default_value' => $default,
					'rows' => $rows,
					'cols' => $cols,
					'can_search' => $can_search,
					'bbc' => $bbc,
					'mask' => $mask,
					'enclose' => $enclose,
					'placement' => $placement,
					'vieworder' => list_getCharacterFieldsSize() + 1,
				);
				addCharacterField($new_field);
			}
			// Work out what to do with the user data otherwise...
			else
			{
				// Anything going to check or select is pointless keeping - as is anything coming from check!
				if (($this->_req->post->field_type == 'check' && $context['field']['type'] != 'check')
					|| (($this->_req->post->field_type == 'select' || $this->_req->post->field_type == 'radio') && $context['field']['type'] != 'select' && $context['field']['type'] != 'radio')
					|| ($context['field']['type'] == 'check' && $this->_req->post->field_type != 'check'))
				{
					deleteCharacterFieldUserData($context['field']['colname']);
				}
				// Otherwise - if the select is edited may need to adjust!
				elseif ($this->_req->post->field_type == 'select' || $this->_req->post->field_type == 'radio')
				{
					$optionChanges = array();
					$takenKeys = array();

					// Work out what's changed!
					foreach ($context['field']['options'] as $k => $option)
					{
						if (trim($option) == '')
							continue;

						// Still exists?
						if (in_array($option, $newOptions))
						{
							$takenKeys[] = $k;
							continue;
						}
					}

					// Finally - have we renamed it - or is it really gone?
					foreach ($optionChanges as $k => $option)
					{
						// Just been renamed?
						if (!in_array($k, $takenKeys) && !empty($newOptions[$k]))
							updateRenamedCharacterField($k, $newOptions, $context['field']['colname'], $option);
					}
				}
				// @todo Maybe we should adjust based on new text length limits?

				// And finally update an existing field
				$field_data = array(
					'field_length' => $field_length,
					'show_reg' => $show_reg,
					'show_display' => $show_display,
					'show_memberlist' => $show_memberlist,
					'private' => $private,
					'active' => $active,
					'can_search' => $can_search,
					'bbc' => $bbc,
					'current_field' => $context['fid'],
					'field_name' => $this->_req->post->field_name,
					'field_desc' => $this->_req->post->field_desc,
					'field_type' => $this->_req->post->field_type,
					'field_options' => $field_options,
					'show_profile' => $show_profile,
					'default_value' => $default,
					'mask' => $mask,
					'enclose' => $enclose,
					'placement' => $placement,
					'rows' => $rows,
					'cols' => $cols,
				);

				updateCharacterField($field_data);

				// Just clean up any old selects - these are a pain!
				if (($this->_req->post->field_type == 'select' || $this->_req->post->field_type == 'radio') && !empty($newOptions))
					deleteOldCharacterFieldSelects($newOptions, $context['field']['colname']);
			}
		}
		// Deleting?
		elseif (isset($this->_req->post->delete) && $context['field']['colname'])
		{
			checkSession();
			validateToken('admin-rps-ecp');

			// Delete the old data first, then the field.
			deleteCharacterFieldUserData($context['field']['colname']);
			deleteCharacterField($context['fid']);
		}

		// Rebuild display cache etc.
		if (isset($this->_req->post->delete) || isset($this->_req->post->save) || isset($this->_req->post->onoff))
		{
			checkSession();

			// Update the display cache
			updateCharacterDisplayCache();
			redirectexit('action=admin;area=rps;sa=fields');
		}

		createToken('admin-rps-ecp');
	}
	
	public function action_characterorder()
	{
		global $context, $txt;
		require_once(SUBSDIR . '/ManageCharacters.subs.php');
		
		// Start off with nothing
		$context['xml_data'] = array();
		$errors = array();
		$order = array();

		// Chances are
		loadLanguage('Errors');
		loadLanguage('ManageSettings');

		// You have to be allowed to do this
		$validation_token = validateToken('admin-rps-sort', 'post', false, false);
		$validation_session = validateSession();

		if ($validation_session === true && $validation_token === true)
		{
			// No questions that we are reordering
			if ($this->_req->getPost('order', 'trim', '') === 'reorder')
			{
				$view_order = 1;
				$replace = '';

				// The field ids arrive in 1-n view order ...
				foreach ($this->_req->post->list_character_fields as $id)
				{
					$id = (int) $id;
					$replace .= '
						WHEN id_field = ' . $id . ' THEN ' . $view_order++;
				}

				// With the replace set
				if (!empty($replace))
					updateCharacterFieldOrder($replace);
				else
					$errors[] = array('value' => $txt['no_sortable_items']);
			}

			$order[] = array(
				'value' => $txt['custom_profile_reordered'],
			);
		}
		// Failed validation, tough to be you
		else
		{
			if (!empty($validation_session))
				$errors[] = array('value' => $txt[$validation_session]);

			if (empty($validation_token))
				$errors[] = array('value' => $txt['token_verify_fail']);
		}

		// New generic token for use
		createToken('admin-rps-sort', 'post');
		$tokens = array(
			array(
				'value' => $context['admin-rps-sort_token'],
				'attributes' => array('type' => 'token'),
			),
			array(
				'value' => $context['admin-rps-sort_token_var'],
				'attributes' => array('type' => 'token_var'),
			),
		);

		// Return the response
		$context['sub_template'] = 'generic_xml';
		$context['xml_data'] = array(
			'orders' => array(
				'identifier' => 'order',
				'children' => $order,
			),
			'tokens' => array(
				'identifier' => 'token',
				'children' => $tokens,
			),
			'errors' => array(
				'identifier' => 'error',
				'children' => $errors,
			),
		);
	}
}
