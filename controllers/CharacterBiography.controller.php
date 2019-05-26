<?php

/**
 * Editing and display of character profiles.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

use ElkArte\Errors\ErrorContext;

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
	
	/**
	 * Called before all other methods when coming from the dispatcher or
	 * action class.
	 *
	 * - If you initiate the class outside of those methods, call this method.
	 * or setup the class yourself or fall awaits.
	 */
	public function pre_dispatch()
	{
		global $context, $user_info, $memberContext, $user_profile, $cur_profile;

		require_once(SUBSDIR . '/Character.subs.php');
		require_once(SUBSDIR . '/Menu.subs.php');
		require_once(SUBSDIR . '/Profile.subs.php');

		$this->_charID = $this->_req->getQuery('c', 'intval', 0);
		$this->_memID = memberID($this->_charID);
		
		loadMemberContext($this->_memID);
		$context['member'] = &$memberContext[$this->_memID];
		$context['character'] = &$memberContext[$this->_memID]['characters'][$this->_charID];
		
		$cur_profile = $user_profile[$this->_memID]['characters'][$this->_charID];
		$context['id_member'] = $this->_memID;
		$context['id_character'] = $this->_charID;
		
		if (!isset($context['user']['is_owner']))
			$context['user']['is_owner'] = in_array($this->_charID, $user_info['characters']);
		
		loadLanguage('Profile');
		loadLanguage('RolePlayingSystem');
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
		global $context, $user_info, $txt, $scripturl;
		
		loadTemplate('RpsCharacter');
		loadTemplate('GenericControls');
		require_once(SUBSDIR . '/Editor.subs.php');		
		
		$this->_bio_errors = ErrorContext::context('bio', 1);
		$this->preparse = \BBC\PreparseCode::instance();
		
		$context['becomes_approved'] = allowedTo('rps_bio_approved');

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
		
		if($this->_req->__isset('rps_bio'))
		{
			$context['biography'] = $this->_req->getPost('rps_bio');
		}
		else
		{
			$db = database();

			$request = $db->query('', '
				SELECT
					id_bio, id_character, approved, date_approved, date_added, biography
				FROM {db_prefix}rps_biographies
				WHERE id_character = {int:id_character}
				ORDER BY id_bio DESC
				LIMIT 1',
				array(
					'id_character' => $this->_charID,
				)
			);
			
			while ($row = $db->fetch_assoc($request))
			{
				$context['biography'] = array(
					'id_bio' => $row['id_bio'],
					'id_character' => $row['id_character'],
					'approved' => $row['approved' ],
					'date_approved' => $row['date_approved'],
					'date_added' => $row['date_added'],
					'biography' => censor($this->preparse->un_preparsecode($row['biography'])),
				);
			}
			
			$db->free_result($request);
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
			'preview_type' => 2
		);
		create_control_richedit($editorOptions);
		return;
	}
	
	public function save_bio()
	{
		global $context;
		$db = database();
		
		$approved = $context['becomes_approved'] ? 1 : 0;
		$approved_time = $context['becomes_approved'] ? time() : 0;
		
		
		if (!isset($_POST['rps_bio']) || Util::htmltrim(Util::htmlspecialchars($_POST['rps_bio'], ENT_QUOTES)) === '')
			$this->_bio_errors->addError('no_message');
		elseif (Util::strlen($_POST['rps_bio']) > 55534)
			$this->_bio_errors->addError(array('long_message', array('55534')));
		else
		{
			// Prepare the message a bit for some additional testing.
			$_POST['rps_bio'] = Util::htmlspecialchars($_POST['rps_bio'], ENT_QUOTES, 'UTF-8', true);

			$this->preparse->preparsecode($_POST['rps_bio']);

			$bbc_parser = \BBC\ParserWrapper::instance();

			// Let's see if there's still some content left without the tags.
			if (Util::htmltrim(strip_tags($bbc_parser->parseMessage($_POST['rps_bio'], false), '<img>')) === '' && (strpos($_POST['message'], '[html]') === false))
				$this->_post_errors->addError('no_message');
		}
		
		$bio_columns = array(
			'id_character' => 'int',
			'approved' => 'int',
			'date_approved' => 'int',
			'date_added' => 'int',
			'biography' => 'string-65534',
		);
		
		$bio_parameters = array(
			'id_character' => $this->_charID,
			'approved' => $approved,
			'date_approved' => $approved_time,
			'date_added' => time(),
			'biography' => $_POST['rps_bio'],
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
		if($context['becomes_approved'])
		{
			// Change the post.
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
}