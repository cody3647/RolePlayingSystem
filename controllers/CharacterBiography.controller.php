<?php

/**
 * Editing and saving of character biographies.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

use ElkArte\Errors\ErrorContext;

/*
* CharacterBiography Controller Class
* Handles editing and saving of character biographies
*/

class CharacterBiography_Controller extends Action_Controller
{
	/**
	 * Member id for the history being viewed
	 * @var int
	 */
	private $_memID = 0;
	
	private $_charID = 0;
	
	private $_saving;
	
	/** @var null|ErrorContext The post (messages) errors object */
	protected $_character_errors = null;

	/** @var \BBC\PreparseCode */
	protected $preparse;
	
	protected $approve;
	
	/**
	 * Called before all other methods when coming from the dispatcher or
	 * action class.
	 *
	 * - If you initiate the class outside of those methods, call this method.
	 * or setup the class yourself or fall awaits.
	 */
	public function pre_dispatch()
	{
		global $context;

		$this->_charID = $this->_req->getQuery('c', 'intval', 0);
		$this->_memID = memberID($this->_charID);
		
		$this->becomes_approved = allowedTo('rps_bio_approved') ? 1 : 0;
		$context['becomes_approved'] = $this->becomes_approved;	
	}
	
	/**
	 * Allow the change or view of profiles.
	 *
	 * - Fires the pre_load event
	 *
	 * @see Action_Controller::action_index()
	 */
	public function action_index()
	{
	}
	
	public function action_biography_edit()
	{
		global $context, $txt, $scripturl, $modSettings;
		
		loadTemplate('RpsCharacter');
		loadTemplate('GenericControls');
		require_once(SUBSDIR . '/Editor.subs.php');		
		loadJavascriptFile('RolePlayingSystem.js');
		loadJavascriptFile('post.js');
		
		addInlineJavascript('
			var txt_preview_fetch = "' . $txt['rps_bio_preview_fetch'] . '";
		');
		
		$this->_bio_errors = ErrorContext::context('bio', 1);
		$this->preparse = \BBC\PreparseCode::instance();

		if($this->_req->__isset('post'))
		{
			$this->save_bio();
			
			// There was a problem, let them try to re-enter.
			if (!empty($post_errors))
			{
				// Load the language file so we can give a nice explanation of the errors.
				loadLanguage('Errors');
				$context['post_errors'] = $post_errors;
			}
			
			else
			{
				redirectexit('action=character;c=' . $this->_charID . ';area=summary;#tab_2');
			}		
		}
		
		if($this->_req->__isset('preview'))
		{
			$bbc_wrapper = \BBC\ParserWrapper::instance();
			$context['preview_message'] = $bbc_wrapper->parseMessage(censor($this->_req->getPost('rps_bio')), false);
		}
		
		if($this->_req->__isset('rps_bio'))
		{
			$context['biography'] = $this->get_biography($this->_req->getPost('id_bio', 'intval',0));
			$context['biography']['biography'] = $this->_req->getPost('rps_bio');
		}
		else
		{
			$context['biography'] = $this->get_biography();
		}
		
		if(!empty($context['biography']['approved']))
		{
			addInlineJavascript('
				$(function() {

					$editor_container["rps_bio"].bind("keydown paste pasteraw valuechange blur focus", biography_length);
				});
				var rps_minor_edit_text = "' . $txt['rps_bio_minor_edit'] . '".split("%1$d");
				var rps_modified_remaining_text = "' . sprintf($txt['rps_bio_modified_remaining'], $modSettings['rps_bio_edit_count'] - $context['biography']['modified_count']) . '";
				var rps_modified_approval_text = "' . $txt['rps_bio_modified_approval'] . '";
				var rps_minor_edit = ' . $modSettings['rps_bio_edit_chars'] . ';
				var rps_length = ' . $context['biography']['bio_length'] . ';
				
				var rps_span_length = document.getElementById("rps_length");
				var rps_span_modified = document.getElementById("rps_modified");
				

			', true);
		}
		
		$context['destination'] = 'character;area=biography_edit;c=' . $this->_charID;
		$context['page_title'] = $context['character']['name'] . ' - Edit Biography';
		
		$editorOptions = array(
			'id' => 'rps_bio',
			'value' => $context['biography']['biography'],
			'labels' => array(
				'post_button' => $txt['rps_bio_save'],
			),
			// add height and width for the editor
			'height' => '350px',
			'width' => '100%',
			'disable_smiley_box' => true,
			// We do XML preview here.
			'preview_type' => 0,
			'buttons' => array(
				'preview' => array(
					'name' => 'preview',
					'value' => $txt['preview'],
					'options' => 'date-test="test" accesskey="p" onclick="return false || previewBio();"',
				),
			),
			'hidden_fields' => array(
				array(
					'name' => 'id_bio',
					'value' => !empty($context['biography']['id_bio']) ? $context['biography']['id_bio'] : 0,
					),
			),
		);
		
		create_control_richedit($editorOptions);
		return;
	}
	
	public function action_biography_preview()
	{
		global $context;
		
		loadTemplate('RpsCharacter');
		$context['sub_template'] = 'biography_preview';
		
		// Prepare the message a bit for some additional testing.
		$biography = Util::htmlspecialchars($this->_req->getPost('rps_bio'), ENT_QUOTES, 'UTF-8', true);
		
		$bbc_parser = \BBC\ParserWrapper::instance();

		$context['preview_biography'] = $bbc_parser->parseMessage($biography, false);
			
	}
	
	private function get_biography($id = 0)
	{
		$db = database();

		$request = $db->query('', '
			SELECT
				id_bio, id_character, approved, date_approved, date_added, modified_count, biography
			FROM {db_prefix}rps_biographies
			WHERE id_character = {int:id_character} ' . (!empty($id) ? 'AND id_bio = {int:id_bio}' : '') . '
			ORDER BY id_bio DESC
			LIMIT 1',
			array(
				'id_character' => $this->_charID,
				'id_bio' => $id,
			)
		);
		
		while ($row = $db->fetch_assoc($request))
		{
			$bio = censor($this->preparse->un_preparsecode($row['biography']));
			$biography = array(
				'id_bio' => $row['id_bio'],
				'id_character' => $row['id_character'],
				'approved' => $row['approved' ],
				'date_approved' => $row['date_approved'],
				'date_added' => $row['date_added'],
				'modified_count' => $row['modified_count'],
				'biography' => $bio,
				'bio_length' => Util::strlen($bio),
				'hash' => hash('md5', $row['biography']),
			);
		}
		
		$db->free_result($request);
		
		return $biography;
	}
	
	public function save_bio()
	{
		global $context, $modSettings;
		$db = database();
		
		$bio = $this->_req->getPost('rps_bio', 'Util::htmlspecialchars[ENT_QUOTES, UTF-8, true]|Util::htmltrim', '');
		
		if (empty($bio))
			$this->_bio_errors->addError('no_message');
		elseif (Util::strlen($bio) > 55534)
			$this->_bio_errors->addError(array('long_message', array('55534')));
		else
		{
			// Prepare the message a bit for some additional testing.
			
			$this->preparse->preparsecode($bio);

			$bbc_parser = \BBC\ParserWrapper::instance();

			// Let's see if there's still some content left without the tags.
			if (Util::htmltrim(strip_tags($bbc_parser->parseMessage($bio, false), '<img>')) === '' && (strpos($bio, '[html]') === false))
				$this->_post_errors->addError('no_message');
		}
		
		
		if(!empty($this->_req->getPost('id_bio', 'intval')))
			$prev_bio = $this->get_biography($this->_req->getPost('id_bio', 'intval'));
		
		if(!empty($prev_bio))
		{
			if($prev_bio['hash'] == hash('md5', $bio) )
				return;

			if($prev_bio['modified_count'] < $modSettings['rps_bio_edit_count'] || empty($prev_bio['approved']))
			{
				$diff_length = abs($prev_bio['bio_length'] - Util::strlen($bio));
				
				if( $diff_length <= $modSettings['rps_bio_edit_chars'] || empty($prev_bio['approved']))
				{
					$db->query('', '
						UPDATE {db_prefix}rps_biographies
						SET biography = {string:bio}' . ( empty($prev_bio['approved']) ? '' : ', modified_count = modified_count +1') . '
						WHERE id_bio = {int:id_bio}',
						array(
							'id_bio' => $prev_bio['id_bio'],
							'bio' => $bio,
						)
					);
				}
				
				else
				{
					$id_bio = $this->insert_bio($bio);
				}
			}
			
			else
			{
				$id_bio = $this->insert_bio($bio);
			}
		}
		
		else
		{
			$id_bio = $this->insert_bio($bio);
		}
		if($this->becomes_approved && !empty($id_bio))
		{
			// 
			$db->query('', '
				UPDATE {db_prefix}rps_characters
				SET id_bio = {int:id_bio}
				WHERE id_character = {int:id_character}',
				array(
					'id_bio' => $id_bio,
					'id_character' => $this->_charID,
				)
			);
		}
		
	}
	
	private function insert_bio($bio)
	{
		$db = database();
		
		$bio_columns = array(
			'id_character' => 'int',
			'approved' => 'int',
			'date_approved' => 'int',
			'date_added' => 'int',
			'biography' => 'string-65534',
		);
		
		$bio_parameters = array(
			'id_character' => $this->_charID,
			'approved' => $this->becomes_approved,
			'date_approved' => $this->becomes_approved ? time() : 0,
			'date_added' => time(),
			'biography' => $bio,
		);
		
		// Insert the post.
		$db->insert('',
			'{db_prefix}rps_biographies',
			$bio_columns,
			$bio_parameters,
			array('id_bio', 'id_character')
		);
		$id_bio = $db->insert_id('{db_prefix}rps_bio', 'id_bio');
		
		// Something went wrong creating the message...
		if (empty($id_bio))
		{
			return false;
		}
		
		else
			return $id_bio;
	}
}